<?php

namespace OpenClassbook;

class App
{
    private static ?array $config = null;

    public static function config(?string $key = null): mixed
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/config.php';
        }

        if ($key === null) {
            return self::$config;
        }

        $keys = explode('.', $key);
        $value = self::$config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        return $value;
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function currentUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        return $_SESSION['user'] ?? null;
    }

    public static function currentUserRole(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }
}
