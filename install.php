#!/usr/bin/env php
<?php

/**
 * Open-Classbook Installer
 *
 * Interaktives Installations-Skript für die Ersteinrichtung.
 * Führt folgende Schritte aus:
 *   1. Systemvoraussetzungen prüfen
 *   2. Datenbank-Verbindung konfigurieren
 *   3. config/config.php erstellen
 *   4. Datenbank-Tabellen anlegen (Migrationen)
 *   5. Admin-Account erstellen
 *   6. Verzeichnisberechtigungen setzen
 *
 * Verwendung: php install.php
 */

// Nur CLI erlauben
if (php_sapi_name() !== 'cli') {
    echo "Dieses Skript darf nur über die Kommandozeile ausgeführt werden.\n";
    exit(1);
}

echo "\n";
echo "╔══════════════════════════════════════════════╗\n";
echo "║       Open-Classbook Installer v1.0          ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

// ==========================================
// 1. Systemvoraussetzungen prüfen
// ==========================================
echo "=== Schritt 1: Systemvoraussetzungen prüfen ===\n\n";

$errors = [];
$warnings = [];

// PHP-Version
$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '8.2.0', '>=')) {
    echo "  [OK] PHP {$phpVersion}\n";
} else {
    $errors[] = "PHP 8.2+ erforderlich (aktuell: {$phpVersion})";
    echo "  [FEHLER] PHP {$phpVersion} - mindestens 8.2 erforderlich\n";
}

// Erforderliche Extensions
$requiredExtensions = [
    'pdo' => 'PDO (Datenbankzugriff)',
    'pdo_mysql' => 'PDO MySQL/MariaDB',
    'mbstring' => 'Multibyte String',
    'json' => 'JSON',
    'session' => 'Session',
    'openssl' => 'OpenSSL (Passwortsicherheit)',
];

foreach ($requiredExtensions as $ext => $label) {
    if (extension_loaded($ext)) {
        echo "  [OK] {$label}\n";
    } else {
        $errors[] = "PHP-Extension '{$ext}' fehlt ({$label})";
        echo "  [FEHLER] {$label} - nicht installiert\n";
    }
}

// Optionale Extensions
$optionalExtensions = [
    'zip' => 'ZIP (für Excel-Import)',
    'gd' => 'GD (Bildverarbeitung)',
    'xml' => 'XML (für Excel-Import)',
];

foreach ($optionalExtensions as $ext => $label) {
    if (extension_loaded($ext)) {
        echo "  [OK] {$label}\n";
    } else {
        $warnings[] = "PHP-Extension '{$ext}' fehlt ({$label})";
        echo "  [WARNUNG] {$label} - nicht installiert (optional)\n";
    }
}

// Composer-Abhängigkeiten
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "  [OK] Composer-Abhängigkeiten installiert\n";
} else {
    $errors[] = "Composer-Abhängigkeiten nicht installiert. Bitte 'composer install' ausführen (Composer installieren: apt install composer)";
    echo "  [FEHLER] vendor/ nicht gefunden - bitte 'composer install' ausführen\n";
    echo "           (Composer nicht installiert? -> apt install composer)\n";
}

// Schreibbare Verzeichnisse
$writableDirs = ['storage', 'storage/logs', 'storage/uploads', 'storage/cache', 'storage/files'];
foreach ($writableDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (!is_dir($fullPath)) {
        @mkdir($fullPath, 0755, true);
    }
    if (is_writable($fullPath)) {
        echo "  [OK] {$dir}/ beschreibbar\n";
    } else {
        $errors[] = "Verzeichnis '{$dir}' ist nicht beschreibbar";
        echo "  [FEHLER] {$dir}/ nicht beschreibbar\n";
    }
}

echo "\n";

if (!empty($errors)) {
    echo "Es wurden kritische Fehler gefunden:\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }

    // Hilfreiche apt-Befehle anzeigen
    $phpMajorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    $missingExts = [];
    $extPackageMap = [
        'pdo_mysql' => "php{$phpMajorMinor}-mysql",
        'mbstring' => "php{$phpMajorMinor}-mbstring",
        'xml' => "php{$phpMajorMinor}-xml",
        'zip' => "php{$phpMajorMinor}-zip",
        'gd' => "php{$phpMajorMinor}-gd",
    ];
    foreach ($extPackageMap as $ext => $pkg) {
        if (!extension_loaded($ext)) {
            $missingExts[] = $pkg;
        }
    }
    if (!empty($missingExts)) {
        echo "\nFehlende PHP-Extensions installieren (Ubuntu/Debian):\n";
        echo "  sudo apt install " . implode(' ', $missingExts) . "\n";
    }

    echo "\nBitte beheben Sie die Fehler und führen Sie den Installer erneut aus.\n";
    exit(1);
}

if (!empty($warnings)) {
    echo "Hinweise:\n";
    foreach ($warnings as $w) {
        echo "  - {$w}\n";
    }
    echo "\n";
}

echo "Alle Voraussetzungen erfüllt.\n\n";

// ==========================================
// 2. Datenbank-Konfiguration
// ==========================================
echo "=== Schritt 2: Datenbank konfigurieren ===\n\n";

$dbHost = prompt('Datenbank-Host', '127.0.0.1');
$dbPort = prompt('Datenbank-Port', '3306');
$dbName = prompt('Datenbank-Name', 'open_classbook');
$dbUser = prompt('Datenbank-Benutzer', 'classbook');
$dbPassword = promptPassword('Datenbank-Passwort');

echo "\nVerbindung wird getestet... ";

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "OK\n";
} catch (PDOException $e) {
    echo "FEHLER\n";
    echo "  Verbindung fehlgeschlagen: " . $e->getMessage() . "\n";
    echo "  Bitte prüfen Sie die Zugangsdaten und versuchen Sie es erneut.\n";
    exit(1);
}

// Datenbank erstellen falls nicht vorhanden
echo "Datenbank '{$dbName}' wird erstellt... ";
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    echo "OK\n\n";
} catch (PDOException $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}

// ==========================================
// 3. Konfigurationsdatei erstellen
// ==========================================
echo "=== Schritt 3: Konfiguration erstellen ===\n\n";

$appUrl = prompt('Anwendungs-URL', 'http://localhost:8080');
$appTimezone = prompt('Zeitzone', 'Europe/Berlin');

$configContent = "<?php

return [
    'app' => [
        'name' => 'Open-Classbook',
        'url' => " . var_export($appUrl, true) . ",
        'debug' => false,
        'timezone' => " . var_export($appTimezone, true) . ",
    ],

    'database' => [
        'host' => " . var_export($dbHost, true) . ",
        'port' => " . var_export((int) $dbPort, true) . ",
        'name' => " . var_export($dbName, true) . ",
        'user' => " . var_export($dbUser, true) . ",
        'password' => " . var_export($dbPassword, true) . ",
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'timeout' => 3600, // 60 Minuten
        'name' => 'open_classbook_session',
    ],

    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 Minuten
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
";

$configPath = __DIR__ . '/config/config.php';

if (file_exists($configPath)) {
    $overwrite = prompt('config/config.php existiert bereits. Ueberschreiben? (j/n)', 'n');
    if (strtolower($overwrite) !== 'j') {
        echo "Konfiguration wird beibehalten.\n\n";
    } else {
        file_put_contents($configPath, $configContent);
        echo "Konfiguration gespeichert.\n\n";
    }
} else {
    file_put_contents($configPath, $configContent);
    echo "Konfiguration gespeichert.\n\n";
}

// ==========================================
// 4. Datenbankmigrationen ausführen
// ==========================================
echo "=== Schritt 4: Datenbank-Tabellen anlegen ===\n\n";

// Migrationstabelle erstellen
$pdo->exec('
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
');

// Bereits ausgeführte Migrationen
$stmt = $pdo->query('SELECT filename FROM migrations');
$executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Migrationsdateien laden
$migrationsDir = __DIR__ . '/database/migrations/';
$files = glob($migrationsDir . '*.sql');
sort($files);

$migrationCount = 0;
foreach ($files as $file) {
    $filename = basename($file);

    if (in_array($filename, $executed)) {
        echo "  [SKIP] {$filename} (bereits ausgeführt)\n";
        continue;
    }

    echo "  [RUN]  {$filename} ... ";
    $sql = file_get_contents($file);

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $stmt->execute([$filename]);
        echo "OK\n";
        $migrationCount++;
    } catch (PDOException $e) {
        echo "FEHLER: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if ($migrationCount === 0) {
    echo "  Alle Migrationen waren bereits ausgeführt.\n";
} else {
    echo "  {$migrationCount} Migration(en) ausgeführt.\n";
}
echo "\n";

// ==========================================
// 5. Admin-Account erstellen
// ==========================================
echo "=== Schritt 5: Admin-Account erstellen ===\n\n";

// Prüfen ob bereits ein Admin existiert
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$stmt->execute();
$adminExists = (int) $stmt->fetchColumn() > 0;

if ($adminExists) {
    echo "Ein Admin-Account existiert bereits.\n";
    $createAdmin = prompt('Neuen Admin-Account erstellen? (j/n)', 'n');
} else {
    $createAdmin = 'j';
}

if (strtolower($createAdmin) === 'j') {
    $adminUsername = prompt('Admin-Benutzername', 'admin');
    $adminEmail = prompt('Admin-E-Mail', 'admin@schule.de');

    $adminPassword = '';
    while (true) {
        $adminPassword = promptPassword('Admin-Passwort (mind. 10 Zeichen, Gross-/Kleinbuchstaben, Zahl)');

        if (strlen($adminPassword) < 10) {
            echo "  Passwort muss mindestens 10 Zeichen lang sein.\n";
            continue;
        }
        if (!preg_match('/[a-z]/', $adminPassword)) {
            echo "  Passwort muss mindestens einen Kleinbuchstaben enthalten.\n";
            continue;
        }
        if (!preg_match('/[A-Z]/', $adminPassword)) {
            echo "  Passwort muss mindestens einen Grossbuchstaben enthalten.\n";
            continue;
        }
        if (!preg_match('/[0-9]/', $adminPassword)) {
            echo "  Passwort muss mindestens eine Zahl enthalten.\n";
            continue;
        }

        $confirm = promptPassword('Passwort bestätigen');
        if ($adminPassword !== $confirm) {
            echo "  Passwörter stimmen nicht überein.\n";
            continue;
        }

        break;
    }

    $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash, role, active, must_change_password) VALUES (?, ?, ?, ?, 1, 0)'
    );

    try {
        $stmt->execute([$adminUsername, $adminEmail, $hash, 'admin']);
        echo "  Admin-Account '{$adminUsername}' wurde erstellt.\n\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            echo "  Benutzername '{$adminUsername}' existiert bereits.\n\n";
        } else {
            echo "  Fehler: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

// ==========================================
// 6. Verzeichnisberechtigungen
// ==========================================
echo "=== Schritt 6: Verzeichnisse einrichten ===\n\n";

$dirs = [
    'storage/logs'    => 0775,
    'storage/uploads' => 0775,
    'storage/cache'   => 0775,
    'storage/files'   => 0775,
];

foreach ($dirs as $dir => $perms) {
    $fullPath = __DIR__ . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, $perms, true);
    }
    chmod($fullPath, $perms);
    echo "  [OK] {$dir}/\n";
}

// Hinweis auf Webserver-Berechtigungen
$webUser = trim(shell_exec('ps aux | grep -E "apache|nginx|www-data|php" | grep -v grep | awk \'{print $1}\' | head -1') ?? '');
if ($webUser && $webUser !== 'root') {
    echo "\n  [HINWEIS] Webserver läuft als '{$webUser}'.\n";
    echo "  Damit Datei-Uploads funktionieren, müssen die storage/-\n";
    echo "  Verzeichnisse für diesen Benutzer beschreibbar sein:\n";
    echo "    chown -R {$webUser}:{$webUser} " . __DIR__ . "/storage/\n";
    echo "  (oder mindestens: chmod -R 777 " . __DIR__ . "/storage/)\n\n";
}

// .htaccess in storage/ für Sicherheit
$htaccess = __DIR__ . '/storage/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
    echo "  [OK] storage/.htaccess (Zugriffschutz)\n";
}

echo "\n";

// ==========================================
// Abschluss
// ==========================================
echo "╔══════════════════════════════════════════════╗\n";
echo "║       Installation abgeschlossen!            ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

echo "Naechste Schritte:\n";
echo "  1. Webserver auf public/ als DocumentRoot konfigurieren\n";
echo "  2. HTTPS aktivieren (dringend empfohlen)\n";
echo "  3. Anwendung im Browser öffnen: {$appUrl}\n";
echo "  4. Mit dem Admin-Account einloggen\n";
echo "\nEntwicklungsserver starten:\n";
echo "  php -S localhost:8080 -t public/\n\n";
echo "Weitere Informationen: INSTALL.md und ADMIN_HANDBUCH.md\n\n";

// ==========================================
// Hilfsfunktionen
// ==========================================

function prompt(string $question, string $default = ''): string
{
    $defaultHint = $default !== '' ? " [{$default}]" : '';
    echo "  {$question}{$defaultHint}: ";
    $input = trim(fgets(STDIN));
    return $input !== '' ? $input : $default;
}

function promptPassword(string $question): string
{
    echo "  {$question}: ";

    // Passwort-Eingabe ohne Echo (nur auf Unix)
    if (function_exists('shell_exec') && PHP_OS_FAMILY !== 'Windows') {
        shell_exec('stty -echo 2>/dev/null');
        $password = trim(fgets(STDIN));
        shell_exec('stty echo 2>/dev/null');
        echo "\n";
    } else {
        $password = trim(fgets(STDIN));
    }

    return $password;
}
