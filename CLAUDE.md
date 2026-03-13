# Open-Classbook

## Projektbeschreibung

Open-Classbook ist eine schlanke, webbasierte Schulverwaltungsplattform fuer kleinere Schulen (Foerder-, Grund- und Hauptschulen). Die Plattform wird On-Premises auf Servern des Schultraegers betrieben und fokussiert sich auf Klassenbuch, Fehlzeitverwaltung und Schulkommunikation.

**Lizenz:** Open Source (MIT oder GPL)
**Datenschutz:** DSGVO-konform (deutsches Schulrecht)

## Tech-Stack

- **Backend:** PHP 8.2+ (kein Framework - Vanilla PHP mit sauberem MVC-Pattern)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla oder leichtes Framework)
- **Datenbank:** MariaDB 10.6+
- **Webserver:** Apache 2.4+ oder Nginx
- **Deployment:** ZIP-Paket / Installer-Skript

## Projektstruktur

```
open-classbook/
├── public/              # Webroot (index.php, Assets)
│   ├── index.php        # Front-Controller
│   ├── css/
│   ├── js/
│   └── img/
├── src/                 # Anwendungscode
│   ├── Controllers/     # Request-Handler
│   ├── Models/          # Datenbank-Modelle (PDO)
│   ├── Views/           # PHP-Templates
│   ├── Middleware/       # Auth, CSRF, RBAC
│   └── Services/        # Business-Logik (Import, Export, Mail)
├── config/              # Konfigurationsdateien
│   └── config.php
├── database/            # SQL-Migrationen
│   └── migrations/
├── templates/           # Import-Vorlagen (Excel)
├── storage/             # Logs, Uploads, Cache
├── tests/               # PHPUnit-Tests
├── CLAUDE.md
├── PROJECT_PLAN.md
└── Open-Classbook-PRD.docx
```

## Architektur-Entscheidungen

- **Kein PHP-Framework:** Vanilla PHP mit eigenem leichtgewichtigen MVC-Pattern fuer maximale Einfachheit und geringe Serveranforderungen
- **PDO mit Prepared Statements:** Fuer alle Datenbankzugriffe (SQL-Injection-Schutz)
- **Session-basierte Authentifizierung** mit CSRF-Token
- **bcrypt** fuer Passwort-Hashing via `password_hash()`
- **phpmailer** fuer E-Mail-Versand (zuverlaessiger als direktes SMTP)
- **TCPDF** fuer PDF-Export (reines PHP, keine externen Abhaengigkeiten)

## Nutzerrollen (RBAC)

| Rolle         | Beschreibung                          |
|---------------|---------------------------------------|
| Admin         | Vollzugriff, Systemkonfiguration      |
| Schulleitung  | Klassenbuecher einsehen, Berichte     |
| Sekretariat   | Schueler/Lehrer verwalten, Fehlzeiten |
| Lehrer/in     | Klassenbucheintraege, Fehlzeiten      |
| Schueler/in   | Leserechte (Phase 2)                  |

## Datenbank-Kerntabellen

- `users` - Alle Nutzer-Accounts mit Rollen
- `teachers` - Lehrerdaten mit Kuerzeln und Faechern
- `students` - Schuelerdaten mit Klassenzuordnung
- `classes` - Klassendefinitionen pro Schuljahr
- `class_teacher` - Lehrer-Klassen-Zuordnung (n:m)
- `classbook_entries` - Taegliche Klassenbucheintraege
- `absences_students` - Schueler-Fehlzeiten
- `absences_teachers` - Lehrer-Fehlzeiten

## Sicherheitsanforderungen

- Alle Eingaben serverseitig validiert und sanitiert
- SQL-Injection-Schutz durch Prepared Statements (PDO)
- XSS-Schutz durch konsequentes HTML-Escaping (`htmlspecialchars()`)
- CSRF-Token fuer alle Formulare
- RBAC-Pruefung auf jeder Seite/Route
- Passwort-Mindestlaenge: 10 Zeichen mit Komplexitaetspruefung
- Session-Timeout nach 60 Minuten Inaktivitaet
- Max. 5 Login-Versuche, dann temporaere Sperre
- HTTPS-Pflicht (Konfigurationshinweis)

## Entwicklungsrichtlinien

- **Sprache im Code:** Englische Variablen-/Funktionsnamen, deutsche UI-Texte
- **Coding-Style:** PSR-12 fuer PHP
- **Datenbank:** SQL-Migrationen als nummerierte .sql-Dateien (z.B. `001_create_users.sql`)
- **Tests:** PHPUnit fuer Backend-Logik
- **Kein ORM:** Direktes PDO mit Prepared Statements
- **Responsive Design:** Mobile-First-Ansatz mit CSS Grid/Flexbox

## Befehle

```bash
# Entwicklungsserver starten
php -S localhost:8080 -t public/

# Tests ausfuehren
./vendor/bin/phpunit

# Datenbankmigrationen ausfuehren
php database/migrate.php
```
