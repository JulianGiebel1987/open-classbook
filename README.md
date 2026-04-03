# Open-Classbook

**Schlanke, webbasierte Schulverwaltungsplattform fuer kleinere Schulen**

Open-Classbook ist eine Open-Source-Loesung fuer digitale Klassenbuchfuehrung, Fehlzeitverwaltung und Schulorganisation. Die Software ist speziell fuer Foerder-, Grund- und Hauptschulen konzipiert und wird On-Premises auf Servern des Schultraegers betrieben.

## Features

- **Digitales Klassenbuch** - Tageseintraege mit Thema, Stunde und Notizen; Filter nach Datum und Lehrer; CSV- und PDF-Export
- **Fehlzeiten-Management** - Schueler- und Lehrer-Fehlzeiten erfassen, entschuldigt/unentschuldigt/offen verwalten
- **Stundenplanung** - Wochenstundenplan je Klasse und Lehrkraft erstellen; Zeitslots konfigurieren; Konfliktpruefung bei Doppelbelegung; Plan fuer Lehrkraefte veroffentlichen
- **Vertretungsplan** - Vertretungen bei Lehrerausfall erfassen und veroffentlichen; automatische Konfliktpruefung; Vertretungsplan als PDF exportieren; Lehrkraefte sehen ihre eigenen Vertretungen
- **Zeugniserstellung** - WYSIWYG-Canvas-Editor fuer Zeugnisvorlagen (A4/A3, Hoch-/Querformat, Raster-Overlay, beliebig viele Seiten); 10 Elementtypen: statischer Text, Freitextfeld, Platzhalter (z.B. `{{student_name}}`), Bild/Logo, Notenfeld, Checkbox, Datumsfeld, Unterschriftsfeld, Trennlinie, Tabelle; Schriftart/Groesse/Farbe pro Element; Vorlagen veroffentlichen fuer Lehrkraefte; Lehrkraefte koennen Zeugnisse fuer einzelne Schueler oder ganze Klassen erstellen, ausfuellen, miteinander teilen und als PDF exportieren (Einzel oder ZIP-Batch)
- **Listen** - Flexible, tabellarische Listen (Anwesenheit, Noten, etc.) mit Inline-Bearbeitung, 6 Feldtypen (Text, Checkbox, Zahl, Datum, Auswahl, Bewertung), Freigabe an einzelne Nutzer oder global
- **Nachrichten** - Internes Nachrichtensystem mit Konversationen, Mehrfachempfaenger und Lesebestaetigung
- **Dateiverwaltung** - Ordnerstruktur mit Upload/Download, Dateityp-Validierung und Groessenbegrenzung
- **Benutzerverwaltung** - Rollenbasierte Zugriffskontrolle (Admin, Schulleitung, Sekretariat, Lehrer)
- **Klassenverwaltung** - Klassen mit Klassenlehrern und Fachlehrern organisieren
- **Import** - Lehrer- und Schuelerdaten per Excel (.xlsx) oder CSV-Datei importieren; Delimiter (Semikolon/Komma) wird automatisch erkannt
- **Schuelerbemerkungen** - Lehrkraefte koennen individuelle Bemerkungen zu Schuelern mit Datumsangabe im Klassenbuch erfassen; filterbar nach Schueler und Zeitraum
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
apt install php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd php8.3-curl

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
| Admin       | Vollzugriff, Benutzerverwaltung, Systemkonfiguration, Zeugnisvorlagen verwalten |
| Schulleitung| Klassenbuecher einsehen, Berichte, Uebersichten, Zeugnisvorlagen verwalten |
| Sekretariat | Schueler/Lehrer verwalten, Fehlzeiten, Import, Zeugnisvorlagen verwalten |
| Lehrer/in   | Eigene Klassen: Klassenbuch, Fehlzeiten, Abwesenheitsmeldung, Stundenplan, Zeugnisse erstellen und exportieren |

## Projektstruktur

```
open-classbook/
├── public/              # Webroot (DocumentRoot hierauf zeigen!)
│   ├── index.php        # Front-Controller
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript
├── src/
│   ├── Controllers/     # Request-Handler (15 Controller)
│   ├── Models/          # Datenbank-Modelle (23 Models)
│   ├── Views/           # PHP-Templates (15 Bereiche)
│   ├── Middleware/       # Auth, CSRF, RBAC, Security Headers, Rate Limiting
│   └── Services/        # Business-Logik (Import, Auth, Logger, Zeugnis-Export)
├── config/              # Konfiguration
├── database/            # Migrationen und Seed-Skript
├── storage/             # Logs, Uploads, Cache
├── tests/               # PHPUnit-Tests
└── templates/           # Excel-Import-Vorlagen
```

## Sicherheit

### Implementierte Schutzmaßnahmen

| Bereich | Maßnahme |
|---|---|
| Datenbank | PDO Prepared Statements gegen SQL-Injection |
| Ausgabe | Konsequentes `htmlspecialchars()` gegen XSS |
| Formulare | CSRF-Token in allen Formularen + AJAX-Endpunkten |
| Passwörter | bcrypt-Hashing via `password_hash()`, Mindestlänge 10 Zeichen mit Komplexitätsprüfung |
| Brute-Force | Max. 5 Login-Versuche, danach 15 Minuten Sperre |
| Sessions | Session-Regenerierung vor Login-Daten (Session-Fixation-Schutz), HttpOnly, Secure, SameSite=Strict, Timeout 60 Min. |
| Datei-Upload | MIME-Typ-Whitelist (15 erlaubte Typen), kryptografische Dateinamen (`random_bytes`), Größenlimit 15 MB |
| Zugriffskontrolle | Rollenbasiertes Berechtigungssystem (RBAC) auf jeder Route, globale Listen nur für Admin/Schulleitung bearbeitbar |
| HTTP-Header | X-Content-Type-Options, X-Frame-Options, HSTS (bei HTTPS), Referrer-Policy, Permissions-Policy, CSP |
| Rate Limiting | Datenbankbasiert per pseudonymisierter IP, 120 Req/Min |
| Import | Temporäre Dateipfade per Regex validiert, keine Path-Traversal-Möglichkeit |
| Redirects | HTTP-Referer-Validierung gegen Open-Redirect-Angriffe |
| Passwort-Reset | Temporäres Passwort nur einmalig auf dedizierter Seite angezeigt, nie in Logs |

### Sicherheitsrelevante Serveranforderungen

```
# HTTPS ist Pflicht – HTTP darf nicht für den Produktivbetrieb verwendet werden
# Webroot muss auf public/ zeigen, nicht auf das Repository-Root
# storage/, config/ und database/ müssen außerhalb des Webroots liegen
# .htaccess (Apache) schützt sensitive Verzeichnisse automatisch
```

> **Hinweis für Administratoren:** Die Datei `config/config.php` enthält Datenbankpasswort und Secret Key. Diese Datei darf **niemals** öffentlich zugänglich sein. Vor dem Produktiveinsatz sollte ein externer Penetrationstest durchgeführt werden.

---

## Datenschutz (DSGVO)

Open-Classbook verarbeitet personenbezogene Daten von Schülerinnen und Schülern sowie Lehrkräften. Der Schulträger ist datenschutzrechtlich **Verantwortlicher** im Sinne von Art. 4 Nr. 7 DSGVO.

### Verarbeitete personenbezogene Daten

| Kategorie | Daten | Betroffene | Rechtsgrundlage |
|---|---|---|---|
| Lehrerdaten | Name, Kürzel, Fächer, E-Mail | Lehrkräfte | Art. 6 Abs. 1 lit. b/c DSGVO |
| Schülerdaten | Vor-/Nachname, Klasse, Schuljahr | Schüler/innen | Art. 6 Abs. 1 lit. c DSGVO (Schulpflicht) |
| Fehlzeiten | Datum, Status (entschuldigt/unentschuldigt/offen), Grund* | Schüler/innen, Lehrkräfte | Art. 6 Abs. 1 lit. c DSGVO |
| Klassenbucheinträge | Datum, Stunde, Thema, Lehrkraft, Notizen | Lehrkräfte (anonym) | Art. 6 Abs. 1 lit. c DSGVO |
| Login-Protokoll | Benutzername, pseudonymisierte IP, Zeitstempel | Alle Nutzer | Art. 6 Abs. 1 lit. f DSGVO (Sicherheit) |
| Audit-Log | Aktion, User-ID, pseudonymisierte IP | Alle Nutzer | Art. 5 Abs. 2 DSGVO (Rechenschaftspflicht) |
| Nachrichten | Nachrichteninhalt, Zeitstempel, Lesebestätigung | Sender/Empfänger | Art. 6 Abs. 1 lit. b DSGVO |

*\* Fehlzeitengründe können Gesundheitsinformationen enthalten (Art. 9 DSGVO, besondere Kategorie) und sind daher nur für Sekretariat und Admin sichtbar – nicht für Lehrkräfte.*

### Technische Datenschutzmaßnahmen

- **IP-Pseudonymisierung:** IP-Adressen werden in allen Logs pseudonymisiert gespeichert (letztes IPv4-Oktett = `xxx`, z.B. `192.168.1.xxx`). Keine personenscharfe IP-Speicherung.
- **Datensparsamkeit:** Fehlzeitengründe (können Gesundheitsdaten enthalten) sind rollenbasiert geschützt – Lehrkräfte sehen nur den Entschuldigungsstatus.
- **Keine Drittanbieter:** Keine externen CDNs, keine Analytics, keine Cloud-Dienste. Alle Daten verbleiben auf dem Server des Schulträgers.
- **Exportauditierung:** Jeder PDF- und CSV-Export wird im Audit-Log festgehalten (wer hat wann welche Klasse exportiert).
- **Browser-Schutz:** Seiten mit temporären Passwörtern werden mit `Cache-Control: no-store` ausgeliefert.
- **On-Premises:** Keine Abhängigkeit von externen Diensten oder Cloud-Infrastruktur.

### Automatische Löschroutinen (Retention-Policies)

Die Datenbank enthält MariaDB-Events für automatische Datenlöschung (Migration `016_add_retention_policies.sql`):

| Datenkategorie | Aufbewahrungsdauer | Löschung |
|---|---|---|
| Login-Versuche | 30 Tage | Automatisch (täglich) |
| Audit-Log | 90 Tage | Automatisch (täglich) |
| Rate-Limit-Einträge | 15 Minuten | Automatisch (alle 15 Min.) |
| Abgelaufene Reset-Tokens | sofort nach Ablauf | Automatisch (stündlich) |

> **Voraussetzung:** Der MariaDB Event-Scheduler muss aktiviert sein:
> ```sql
> SET GLOBAL event_scheduler = ON;
> -- Dauerhaft in my.cnf: event_scheduler=ON
> ```

### Aufbewahrungsfristen (manuell durch Schulträger)

Diese Fristen müssen vom Schulträger gemäß geltendem Schulrecht umgesetzt werden:

| Datenkategorie | Empfohlene Aufbewahrung | Grundlage |
|---|---|---|
| Klassenbucheinträge | 2 Jahre nach Schuljahresende | Landesschulrecht |
| Schüler-Fehlzeiten | 3 Jahre nach Schuljahresende | Landesschulrecht |
| Lehrerdaten (nach Ausscheiden) | Auf Archivminimum reduzieren | Art. 5 Abs. 1 lit. e DSGVO |
| Nachrichten | 2 Jahre | Art. 5 Abs. 1 lit. e DSGVO |

### Betroffenenrechte (Art. 15–22 DSGVO)

Betroffene können folgende Rechte beim Schulträger geltend machen:

- **Auskunft (Art. 15):** Admin kann alle Daten einer Person über die Benutzerverwaltung einsehen.
- **Berichtigung (Art. 16):** Über die jeweiligen Verwaltungsoberflächen möglich.
- **Löschung (Art. 17):** Accounts können deaktiviert werden. Vollständige Datenlöschung muss der Schulträger manuell durchführen (ggf. Pseudonymisierung statt Löschung für archivpflichtige Daten).
- **Datenportabilität (Art. 20):** CSV-Export im Klassenbuch-Bereich vorhanden.

### Pflichten des Schulträgers vor Produktiveinsatz

Folgende Maßnahmen liegen in der Verantwortung des Schulträgers und sind **nicht** Bestandteil der Software:

- [ ] **Datenschutzerklärung** erstellen und unter `/datenschutz` bereitstellen (Art. 13/14 DSGVO)
- [ ] **Verarbeitungsverzeichnis** nach Art. 30 DSGVO führen
- [ ] **Datenschutz-Folgenabschätzung (DPIA)** nach Art. 35 DSGVO durchführen (Verarbeitung von Kinderdaten)
- [ ] **Datenschutzbeauftragten** benennen (sofern gesetzlich vorgeschrieben)
- [ ] **Auftragsverarbeitungsvertrag (AVV)** mit dem Serveranbieter schließen (sofern Hosting extern)
- [ ] **Schulung der Nutzer** zu Datenschutz und sicherem Umgang mit der Software
- [ ] **Aufbewahrungsfristen** für Klassenbuch und Fehlzeiten gemäß Landesschulrecht einhalten
- [ ] **HTTPS** als einzigen Zugangsweg erzwingen

## Mehrere Instanzen auf einem Server

Open-Classbook unterstuetzt den Betrieb mehrerer unabhaengiger Instanzen auf einem Server – z.B. wenn ein Schultraeger mehrere Schulen verwaltet. Jede Schule erhaelt eine eigene Datenbank und einen eigenen Webroot-Ordner, was vollstaendige Datentrennung gewaehrleistet (DSGVO-konform).

### Option A: Mehrere Virtual Hosts (empfohlen)

Jede Instanz ist eine eigene Kopie der Codebase mit eigener Konfiguration:

```bash
# Verzeichnisse anlegen
mkdir -p /var/www/schule-a /var/www/schule-b

# Codebase klonen
git clone https://github.com/JulianGiebel1987/open-classbook.git /var/www/schule-a
git clone https://github.com/JulianGiebel1987/open-classbook.git /var/www/schule-b

# Abhaengigkeiten installieren
composer install -d /var/www/schule-a
composer install -d /var/www/schule-b
```

Fuer jede Schule eine eigene Datenbank anlegen:

```sql
-- Schule A
CREATE DATABASE classbook_schule_a CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'classbook_a'@'localhost' IDENTIFIED BY 'SicheresPasswortA';
GRANT ALL PRIVILEGES ON classbook_schule_a.* TO 'classbook_a'@'localhost';

-- Schule B
CREATE DATABASE classbook_schule_b CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'classbook_b'@'localhost' IDENTIFIED BY 'SicheresPasswortB';
GRANT ALL PRIVILEGES ON classbook_schule_b.* TO 'classbook_b'@'localhost';

FLUSH PRIVILEGES;
```

Installer fuer jede Instanz ausfuehren:

```bash
php /var/www/schule-a/install.php
php /var/www/schule-b/install.php
```

**Apache Virtual Hosts** (`/etc/apache2/sites-available/`):

```apache
# /etc/apache2/sites-available/schule-a.conf
<VirtualHost *:443>
    ServerName schule-a.example.de
    DocumentRoot /var/www/schule-a/public

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/schule-a.crt
    SSLCertificateKeyFile /etc/ssl/private/schule-a.key

    <Directory /var/www/schule-a/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# /etc/apache2/sites-available/schule-b.conf
<VirtualHost *:443>
    ServerName schule-b.example.de
    DocumentRoot /var/www/schule-b/public
    # ... analog
</VirtualHost>
```

**Nginx** (alternativ):

```nginx
# /etc/nginx/sites-available/schule-a
server {
    listen 443 ssl;
    server_name schule-a.example.de;
    root /var/www/schule-a/public;

    ssl_certificate     /etc/ssl/certs/schule-a.crt;
    ssl_certificate_key /etc/ssl/private/schule-a.key;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Virtual Host aktivieren (Apache):

```bash
a2ensite schule-a.conf schule-b.conf
systemctl reload apache2
```

### Option B: Shared Codebase, konfigurationsbasiert

Eine einzige Codebase fuer alle Schulen – die richtige Datenbank wird anhand des Hostnamens gewaehlt. Empfohlen wenn viele Instanzen zentral aktualisiert werden sollen.

```php
// config/config.php
$instanceConfigs = [
    'schule-a.example.de' => [
        'db_name' => 'classbook_schule_a',
        'db_user' => 'classbook_a',
        'db_pass' => 'SicheresPasswortA',
    ],
    'schule-b.example.de' => [
        'db_name' => 'classbook_schule_b',
        'db_user' => 'classbook_b',
        'db_pass' => 'SicheresPasswortB',
    ],
];

$currentHost = $_SERVER['HTTP_HOST'] ?? '';
if (!isset($instanceConfigs[$currentHost])) {
    http_response_code(403);
    exit('Unbekannte Instanz.');
}

$instance = $instanceConfigs[$currentHost];
define('DB_NAME', $instance['db_name']);
define('DB_USER', $instance['db_user']);
define('DB_PASS', $instance['db_pass']);
// ... restliche Konfiguration
```

**Pro:** Ein Update (`git pull` + `php database/migrate.php` fuer jede DB) gilt fuer alle Schulen.
**Con:** Eine fehlerhafte Migration betrifft alle Instanzen gleichzeitig.

### Vergleich der Optionen

| Kriterium | Option A (getrennte Ordner) | Option B (Shared Codebase) |
|---|---|---|
| Datentrennung | Vollstaendig (eigener Webroot) | Vollstaendig (eigene Datenbanken) |
| Updates | Jede Instanz einzeln | Zentral fuer alle |
| Konfigurationsaufwand | Gering pro Instanz | Einmalig, dann minimal |
| Fehlerisolierung | Instanzen unabhaengig | Fehler koennen alle betreffen |
| Empfohlen fuer | 2–5 Schulen | 6+ Schulen |

### Migrationen bei mehreren Instanzen ausfuehren

```bash
# Jede Datenbank muss separat migriert werden
for DB in classbook_schule_a classbook_schule_b; do
    DB_NAME=$DB php /var/www/open-classbook/database/migrate.php
done
```

### Hinweise fuer den Produktivbetrieb

- Jede Instanz benoetigt einen eigenen `storage/`-Ordner (Uploads, Logs) – bei Option A automatisch gegeben
- `config/config.php` und `storage/` muessen ausserhalb des Webroots liegen oder per Webserver-Konfiguration gesperrt sein
- Separate Backups pro Datenbank einrichten (z.B. `mysqldump` via Cronjob)
- SSL-Zertifikat pro Subdomain oder ein Wildcard-Zertifikat (`*.example.de`)

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
