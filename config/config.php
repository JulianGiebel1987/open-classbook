<?php

return [
    'app' => [
        'name' => 'Open-Classbook',
        'url' => 'http://localhost:8080',
        'debug' => false,
        'timezone' => 'Europe/Berlin',
    ],

    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'open_classbook',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'timeout' => 3600, // 60 Minuten in Sekunden
        'name' => 'open_classbook_session',
    ],

    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 Minuten in Sekunden
        'password_min_length' => 10,
        'password_reset_token_lifetime' => 3600, // 1 Stunde
    ],

    'mail' => [
        'enabled' => false,
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => 'noreply@schule.de',
        'from_name' => 'Open-Classbook',
    ],
];
