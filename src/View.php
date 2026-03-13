<?php

namespace OpenClassbook;

class View
{
    private static string $layoutsPath = __DIR__ . '/Views/layouts/';
    private static string $viewsPath = __DIR__ . '/Views/';

    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $data = array_map(function ($value) {
            if (is_string($value)) {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            return $value;
        }, $data);

        extract($data);

        // View-Inhalt rendern
        ob_start();
        $viewFile = self::$viewsPath . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '<p>View nicht gefunden: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $content = ob_get_clean();

        // Layout rendern
        $layoutFile = self::$layoutsPath . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function renderWithoutLayout(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = self::$viewsPath . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }

    /**
     * CSRF-Token-Feld fuer Formulare
     */
    public static function csrfField(): string
    {
        $token = $_SESSION['csrf_token'] ?? '';
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Flash-Nachricht anzeigen
     */
    public static function flash(): string
    {
        $html = '';
        if (isset($_SESSION['flash'])) {
            $type = $_SESSION['flash']['type'] ?? 'info';
            $message = htmlspecialchars($_SESSION['flash']['message'] ?? '', ENT_QUOTES, 'UTF-8');
            $html = '<div class="alert alert-' . $type . '">' . $message . '</div>';
            unset($_SESSION['flash']);
        }
        return $html;
    }
}
