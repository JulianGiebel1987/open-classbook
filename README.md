# Open-Classbook

**Schlanke, webbasierte Schulverwaltungsplattform fuer kleinere Schulen**

Open-Classbook ist eine Open-Source-Loesung fuer digitale Klassenbuchfuehrung, Fehlzeitverwaltung und Schulorganisation. Die Software ist speziell fuer Foerder-, Grund- und Hauptschulen konzipiert und wird On-Premises auf Servern des Schultraegers betrieben.

## Features

- **Digitales Klassenbuch** - Tageseintraege mit Thema, Stunde und Notizen; Filter nach Datum und Lehrer; CSV- und PDF-Export
- **Fehlzeiten-Management** - Schueler- und Lehrer-Fehlzeiten erfassen, entschuldigt/unentschuldigt/offen verwalten
- **Benutzerverwaltung** - Rollenbasierte Zugriffskontrolle (Admin, Schulleitung, Sekretariat, Lehrer)
- **Klassenverwaltung** - Klassen mit Klassenlehrern und Fachlehrern organisieren
- **Excel-Import** - Lehrer- und Schuelerdaten per Excel-Datei importieren
- **Dashboards** - Rollenspezifische Uebersichten mit Widgets und Schnellzugriff
- **Responsive Design** - Optimiert fuer Desktop, Tablet und Smartphone
- **Barrierefreiheit** - WCAG 2.1 AA konform (Tastaturnavigation, ARIA-Labels, Kontraste)
- **DSGVO-konform** - On-Premises-Betrieb, keine Cloud-Abhaengigkeit

## Tech-Stack

| Komponente | Technologie |
|-----------|-------------|
| Backend   | PHP 8.2+ (Vanilla MVC) |
| Datenbank | MariaDB 10.6+ |
| Frontend  | HTML5, CSS3, Vanilla JavaScript |
| Webserver | Apache 2.4+ oder Nginx |

**Bewusste Entscheidung:** Kein PHP-Framework fuer maximale Einfachheit, geringe Serveranforderungen und leichte Wartbarkeit.

## Schnellstart

**Voraussetzungen:** PHP 8.2+, MariaDB 10.6+, Composer (siehe [INSTALL.md](INSTALL.md) fuer Details)

```bash
# Auf Ubuntu/Debian: Pakete installieren (PHP-Version anpassen!)
apt update
apt install php php-cli mariadb-server composer
apt install php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip

# MariaDB-Benutzer und Datenbank anlegen
systemctl start mariadb
mariadb -u root <<'SQL'
CREATE DATABASE open_classbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'classbook'@'localhost' IDENTIFIED BY 'IhrSicheresPasswort';
GRANT ALL PRIVILEGES ON open_classbook.* TO 'classbook'@'localhost';
FLUSH PRIVILEGES;
SQL

# Repository klonen
git clone https://github.com/JulianGiebel1987/open-classbook.git
cd open-classbook

# Abhaengigkeiten installieren
composer install

# Installer ausfuehren (interaktiv)
# Hinweis: Als DB-Benutzer 'classbook' verwenden, nicht 'root'!
php install.php

# Entwicklungsserver starten
php -S localhost:8080 -t public/
```

Alternativ fuer eine schnelle Demo mit Testdaten:

```bash
composer install
cp config/config.example.php config/config.php
# config/config.php: DB-Benutzer 'classbook' + Passwort eintragen
php database/migrate.php
php database/seed.php
php -S localhost:8080 -t public/
```

Demo-Login: `admin` / `Admin2026!x`

## Nutzerrollen

| Rolle        | Rechte |
|-------------|--------|
| Admin       | Vollzugriff, Benutzerverwaltung, Systemkonfiguration |
| Schulleitung| Klassenbuecher einsehen, Berichte, Uebersichten |
| Sekretariat | Schueler/Lehrer verwalten, Fehlzeiten, Import |
| Lehrer/in   | Eigene Klassen: Klassenbuch, Fehlzeiten, Abwesenheitsmeldung |

## Projektstruktur

```
open-classbook/
├── public/              # Webroot (DocumentRoot hierauf zeigen!)
│   ├── index.php        # Front-Controller
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript
├── src/
│   ├── Controllers/     # Request-Handler (8 Controller)
│   ├── Models/          # Datenbank-Modelle (7 Models)
│   ├── Views/           # PHP-Templates (9 Bereiche)
│   ├── Middleware/       # Auth, CSRF, RBAC, Security Headers, Rate Limiting
│   └── Services/        # Business-Logik (Import, Auth, Logger)
├── config/              # Konfiguration
├── database/            # Migrationen und Seed-Skript
├── storage/             # Logs, Uploads, Cache
├── tests/               # PHPUnit-Tests
└── templates/           # Excel-Import-Vorlagen
```

## Sicherheit

- SQL-Injection-Schutz durch PDO Prepared Statements
- XSS-Schutz durch konsequentes HTML-Escaping
- CSRF-Token in allen Formularen
- Passwort-Hashing mit bcrypt
- Brute-Force-Schutz (5 Versuche, 15 Min. Sperre)
- Session-Timeout nach 60 Minuten
- Session-Haertung (HttpOnly, Secure, SameSite, Strict Mode)
- Sicherheits-Header (X-Content-Type-Options, X-Frame-Options, HSTS, Referrer-Policy, Permissions-Policy)
- Content Security Policy (CSP)
- Rate Limiting (datenbankbasiert, per IP)
- Rollenbasierte Zugriffskontrolle auf jeder Route

## Tests

```bash
# Alle Tests ausfuehren
./vendor/bin/phpunit

# Einzelne Test-Suite
./vendor/bin/phpunit tests/Models/
./vendor/bin/phpunit tests/ViewTest.php
```

## Dokumentation

- [INSTALL.md](INSTALL.md) - Installationsanleitung
- [UPDATE.md](UPDATE.md) - Update-Anleitung
- [ADMIN_HANDBUCH.md](ADMIN_HANDBUCH.md) - Administratorhandbuch
- [CHANGELOG.md](CHANGELOG.md) - Aenderungsprotokoll
- [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md) - Manuelle Test-Checkliste

## Lizenz

MIT License - siehe [LICENSE](LICENSE)

## Beitragen

Beitraege sind willkommen! Bitte erstellen Sie einen Issue oder Pull Request.

1. Fork erstellen
2. Feature-Branch anlegen (`git checkout -b feature/mein-feature`)
3. Aenderungen committen (`git commit -m 'Feature hinzufuegen'`)
4. Branch pushen (`git push origin feature/mein-feature`)
5. Pull Request erstellen
