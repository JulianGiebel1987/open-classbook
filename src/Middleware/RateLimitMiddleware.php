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
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $endpoint = $_SERVER['REQUEST_URI'] ?? '/';

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

        // Alte Eintraege bereinigen (1% Wahrscheinlichkeit pro Request)
        if (random_int(1, 100) === 1) {
            Database::execute(
                'DELETE FROM rate_limits WHERE requested_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)'
            );
        }

        return true;
    }
}
