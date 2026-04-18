<?php

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\App;

// Test configuration (no real DB needed for unit tests)
App::setConfig([
    'app' => [
        'name' => 'Open-Classbook Test',
        'url' => 'http://localhost:8080',
        'debug' => true,
        'timezone' => 'Europe/Berlin',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'test',
        'user' => 'test',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'timeout' => 3600,
        'name' => 'test_session',
    ],
    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 900,
        'password_min_length' => 10,
        'password_reset_token_lifetime' => 3600,
        'password_reset_rate_limit' => 3,
        'password_reset_rate_window' => 3600,
    ],
    'mail' => [
        'enabled' => false,
    ],
]);
