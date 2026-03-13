<?php

/**
 * Open-Classbook - Front-Controller
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\App;
use OpenClassbook\Router;
use OpenClassbook\Middleware\CsrfMiddleware;

// Zeitzone setzen
date_default_timezone_set(App::config('app.timezone') ?? 'Europe/Berlin');

// Session starten
$sessionName = App::config('session.name') ?? 'open_classbook_session';
session_name($sessionName);
session_start();

// CSRF-Token sicherstellen
CsrfMiddleware::generateToken();

// Navigation laden
$nav = require __DIR__ . '/../config/navigation.php';

// Router initialisieren und Routen laden
$router = new Router();
require __DIR__ . '/../config/routes.php';

// Request dispatchen
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
