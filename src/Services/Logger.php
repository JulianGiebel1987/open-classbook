<?php

namespace OpenClassbook\Services;

class Logger
{
    private static string $logDir = __DIR__ . '/../../storage/logs/';

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
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
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
