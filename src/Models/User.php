<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class User
{
    /**
     * Alle physischen Dateien eines Nutzers sammeln (private + eigene).
     */
    private static function collectUserFileNames(int $userId): array
    {
        $files = Database::query('SELECT stored_name FROM files WHERE owner_id = ?', [$userId]);
        return array_column($files, 'stored_name');
    }

    /**
     * Benutzer löschen inkl. physischer Dateien.
     * DB-Einträge werden durch ON DELETE CASCADE automatisch entfernt.
     */
    public static function delete(int $id): void
    {
        // Physische Dateien vor dem DB-DELETE sammeln und löschen
        $storedNames = self::collectUserFileNames($id);
        $storagePath = __DIR__ . '/../../storage/files/';

        foreach ($storedNames as $name) {
            $path = $storagePath . $name;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        Database::execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT * FROM users WHERE id = ?',
            [$id]
        );
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::queryOne(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [$username, $username]
        );
    }

    /**
     * Zaehlt aktive Administratoren. Wird genutzt, um zu verhindern,
     * dass der letzte Admin degradiert oder deaktiviert wird.
     */
    public static function countActiveAdmins(): int
    {
        $row = Database::queryOne(
            "SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin' AND active = 1"
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public static function findAll(array $filters = []): array
    {
        $sql = 'SELECT id, username, email, role, active, must_change_password, last_login, created_at FROM users WHERE 1=1';
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= ' AND role = ?';
            $params[] = $filters['role'];
        }

        if (isset($filters['active'])) {
            $sql .= ' AND active = ?';
            $params[] = $filters['active'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (username LIKE ? OR email LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY username ASC';

        return Database::query($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO users (username, email, password_hash, role, active, must_change_password) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['username'],
                $data['email'] ?? null,
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['role'],
                $data['active'] ?? 1,
                $data['must_change_password'] ?? 1,
            ]
        );

        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];

        foreach (['username', 'email', 'role', 'active', 'must_change_password'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return;
        }

        $params[] = $id;
        Database::execute(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $params
        );
    }

    public static function updatePassword(int $id, string $newPassword): void
    {
        Database::execute(
            'UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?',
            [password_hash($newPassword, PASSWORD_BCRYPT), $id]
        );
    }

    public static function setResetToken(int $id, string $token, \DateTime $expires): void
    {
        Database::execute(
            'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?',
            [$token, $expires->format('Y-m-d H:i:s'), $id]
        );
    }

    public static function findByResetToken(string $token): ?array
    {
        return Database::queryOne(
            'SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW() AND active = 1',
            [$token]
        );
    }

    public static function clearResetToken(int $id): void
    {
        Database::execute(
            'UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?',
            [$id]
        );
    }

    public static function updateLastLogin(int $id): void
    {
        Database::execute(
            'UPDATE users SET last_login = NOW() WHERE id = ?',
            [$id]
        );
    }

    public static function getSessionVersion(int $id): ?int
    {
        $row = Database::queryOne(
            'SELECT session_version FROM users WHERE id = ?',
            [$id]
        );

        if ($row === null) {
            return null;
        }

        return (int) $row['session_version'];
    }

    public static function incrementSessionVersion(int $id): void
    {
        Database::execute(
            'UPDATE users SET session_version = session_version + 1 WHERE id = ?',
            [$id]
        );
    }

    /**
     * 2FA-Daten eines Nutzers laden
     */
    public static function getTwoFactorData(int $id): ?array
    {
        return Database::queryOne(
            'SELECT two_factor_method, two_factor_secret, two_factor_confirmed_at, two_factor_recovery_codes FROM users WHERE id = ?',
            [$id]
        );
    }

    /**
     * 2FA komplett zurücksetzen
     */
    public static function clearTwoFactor(int $id): void
    {
        Database::execute(
            'UPDATE users SET two_factor_method = ?, two_factor_secret = NULL, two_factor_confirmed_at = NULL, two_factor_recovery_codes = NULL WHERE id = ?',
            ['none', $id]
        );
    }

    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as cnt FROM users WHERE username = ?';
        $params = [$username];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $result = Database::queryOne($sql, $params);
        return ($result['cnt'] ?? 0) > 0;
    }
}
