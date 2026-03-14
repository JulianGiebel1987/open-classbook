<?php

/**
 * Open-Classbook - Front-Controller
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\App;
use OpenClassbook\Router;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\Logger;

// Zeitzone setzen
date_default_timezone_set(App::config('app.timezone') ?? 'Europe/Berlin');

// Session starten
$sessionName = App::config('session.name') ?? 'open_classbook_session';
session_name($sessionName);
session_start();

// CSRF-Token sicherstellen
CsrfMiddleware::generateToken();

// Error-Handler
set_exception_handler(function (\Throwable $e) {
    Logger::error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);

    if (App::config('app.debug')) {
        echo '<h1>Fehler</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }

    View::render('errors/500', ['title' => 'Serverfehler']);
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    Logger::error("PHP Error [{$errno}]: {$errstr}", [
        'file' => $errfile,
        'line' => $errline,
    ]);
    return false;
});

// Navigation laden
$nav = require __DIR__ . '/../config/navigation.php';

// Router initialisieren und Routen laden
$router = new Router();
require __DIR__ . '/../config/routes.php';

// Request dispatchen
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
