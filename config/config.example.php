<?php

/**
 * Open-Classbook Konfiguration
 *
 * Kopieren Sie diese Datei nach config.php und passen Sie die Werte an:
 *   cp config/config.example.php config/config.php
 *
 * Oder verwenden Sie den Installer: php install.php
 */

return [
    'app' => [
        'name' => 'Open-Classbook',
        'url' => 'https://classbook.ihre-schule.de',  // Anpassen!
        'debug' => false,                               // Im Produktivbetrieb: false
        'timezone' => 'Europe/Berlin',
    ],

    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'open_classbook',   // Datenbankname anpassen
        'user' => 'classbook_user',   // Datenbankbenutzer anpassen
        'password' => '',             // Datenbankpasswort setzen!
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'timeout' => 3600, // 60 Minuten in Sekunden
        'name' => 'open_classbook_session',
    ],

    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 900,      // 15 Minuten in Sekunden
        'password_min_length' => 10,
        'password_reset_token_lifetime' => 3600, // 1 Stunde
        // 2FA: 32-Byte Hex-Key fuer Verschluesselung der TOTP-Secrets
        // Generieren mit: php -r "echo bin2hex(random_bytes(32));"
        'two_factor_encryption_key' => '',
    ],

    // E-Mail-Konfiguration fuer Passwort-Zuruecksetzung und Benachrichtigungen
    // Erfordert einen erreichbaren SMTP-Server
    'mail' => [
        'enabled' => false,                         // Auf true setzen, wenn SMTP verfuegbar
        'host' => 'mail.ihre-schule.de',            // SMTP-Server
        'port' => 587,                              // SMTP-Port (587 fuer TLS, 465 fuer SSL)
        'username' => 'classbook@ihre-schule.de',   // SMTP-Benutzername
        'password' => '',                           // SMTP-Passwort
        'encryption' => 'tls',                      // 'tls' oder 'ssl'
        'from_address' => 'classbook@ihre-schule.de',
        'from_name' => 'Open-Classbook',
    ],
];
