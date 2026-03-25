<?php

namespace OpenClassbook\Services;

class Logger
{
    private static string $logDir = __DIR__ . '/../../storage/logs/';

    /**
     * IP-Adresse pseudonymisieren (DSGVO Art. 5 Abs. 1 lit. e):
     * Letztes Oktett bei IPv4 / letzte 2 Bloecke bei IPv6 entfernen.
     */
    private static function pseudonymizeIp(string $ip): string
    {
        if (str_contains($ip, ':')) {
            // IPv6: nur erste 4 Bloecke behalten
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . ':xxxx:xxxx:xxxx:xxxx';
        }
        // IPv4: letztes Oktett durch X ersetzen
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'xxx';
            return implode('.', $parts);
        }
        return 'unknown';
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function audit(string $action, ?int $userId = null, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void
    {
        $context = [
            'user_id' => $userId ?? ($_SESSION['user_id'] ?? null),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip' => self::pseudonymizeIp($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
        ];

        self::log('AUDIT', $action, $context);

        // Audit-Log in Datenbank schreiben
        try {
            $stmt = \OpenClassbook\Database::getConnection()->prepare(
                'INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)'
            );
            $stmt->execute([
                'user_id' => $context['user_id'],
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details,
                'ip_address' => $context['ip'],
            ]);
        } catch (\Exception $e) {
            self::error('Failed to write audit log to database: ' . $e->getMessage());
        }
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0750, true);
        }

        $logFile = self::$logDir . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
