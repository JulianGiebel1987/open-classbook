<?php

namespace OpenClassbook\Middleware;

use OpenClassbook\App;
use OpenClassbook\Database;
use OpenClassbook\View;

class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 120, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(): bool
    {
        // IP pseudonymisieren (DSGVO Art. 5 Abs. 1 lit. e)
        $ip = $this->pseudonymizeIp($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        // Nur Pfad ohne Query-Parameter speichern (keine Schuelerdaten in URL)
        $endpoint = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // Anfrage protokollieren
        Database::execute(
            'INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)',
            [$ip, $endpoint]
        );

        // Anfragen im Zeitfenster zaehlen
        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt FROM rate_limits
             WHERE ip_address = ? AND requested_at > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$ip, $this->windowSeconds]
        );

        if (($result['cnt'] ?? 0) > $this->maxRequests) {
            http_response_code(429);
            header('Retry-After: ' . $this->windowSeconds);
            View::render('errors/429', ['title' => 'Zu viele Anfragen']);
            return false;
        }

        // Alte Eintraege bereinigen (10% Wahrscheinlichkeit pro Request, kuerzere Retention)
        if (random_int(1, 10) === 1) {
            Database::execute(
                'DELETE FROM rate_limits WHERE requested_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
            );
        }

        return true;
    }

    private function pseudonymizeIp(string $ip): string
    {
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . ':xxxx:xxxx:xxxx:xxxx';
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'xxx';
            return implode('.', $parts);
        }
        return 'unknown';
    }
}
