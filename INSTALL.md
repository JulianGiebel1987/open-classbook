# Open-Classbook - Installationsanleitung

## Systemvoraussetzungen

### Server

| Komponente   | Mindestversion | Empfohlen      |
|-------------|---------------|----------------|
| PHP         | 8.2           | 8.3+           |
| MariaDB     | 10.6          | 10.11+         |
| Webserver   | Apache 2.4    | Nginx 1.24+    |
| Composer    | 2.0           | 2.7+           |

### Vorbereitung: Pakete installieren (Ubuntu/Debian)

Die folgende Anleitung gilt fuer Ubuntu 22.04+ / Debian 12+. Passen Sie die PHP-Versionsnummer (z.B. `8.3`) an Ihre installierte PHP-Version an.

**PHP-Version pruefen:**
```bash
php -v
```

**Composer installieren** (falls `composer` nicht gefunden wird):
```bash
apt install composer
```

Alternativ manuell (empfohlen fuer aktuelle Version):
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

**Erforderliche PHP-Extensions installieren:**
```bash
# Ersetzen Sie 8.3 durch Ihre PHP-Version (z.B. 8.2, 8.4)
apt install php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd php8.3-curl
```

Bereits standardmaessig enthalten (kein separates Paket noetig): `json`, `session`, `openssl`, `pdo`.

Nach der Installation der Extensions PHP-FPM bzw. den Webserver neu starten:
```bash
systemctl restart php8.3-fpm   # bei Nginx
systemctl restart apache2       # bei Apache
```

### PHP-Extensions (erforderlich)

- `pdo` und `pdo_mysql` - Datenbankzugriff
- `mbstring` - Zeichenkodierung
- `json` - JSON-Verarbeitung
- `session` - Session-Management
- `openssl` - Passwortsicherheit

### PHP-Extensions (empfohlen)

- `zip` - Excel-Import/Export
- `xml` - Excel-Import (PhpSpreadsheet)
- `gd` - Bildverarbeitung

## Installation

### 1. Dateien bereitstellen

Entpacken Sie das Release-Archiv in Ihr Webverzeichnis:

```bash
unzip open-classbook-v1.0.0.zip -d /var/www/open-classbook
cd /var/www/open-classbook
```

Oder klonen Sie das Repository:

```bash
git clone https://github.com/JulianGiebel1987/open-classbook.git
cd open-classbook
```

### 2. Composer-Abhaengigkeiten installieren

```bash
composer install --no-dev --optimize-autoloader
```

Fuer Entwicklungsumgebungen (mit PHPUnit):

```bash
composer install
```

### 3. Installer ausfuehren

Der interaktive Installer fuehrt Sie durch die komplette Einrichtung:

```bash
php install.php
```

Der Installer prueft:
- Systemvoraussetzungen (PHP-Version, Extensions)
- Datenbank-Verbindung
- Erstellt die Konfigurationsdatei (`config/config.php`)
- Fuehrt alle Datenbankmigrationen aus
- Legt den Admin-Account an
- Richtet Verzeichnisberechtigungen ein

### 4. Alternative: Manuelle Installation

Falls der Installer nicht verwendet werden soll:

**a) Konfiguration erstellen:**
```bash
cp config/config.example.php config/config.php
```
Passen Sie die Datenbankzugangsdaten in `config/config.php` an.

**b) Datenbank erstellen:**
```sql
CREATE DATABASE open_classbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**c) Migrationen ausfuehren:**
```bash
php database/migrate.php
```

**d) Admin-Account manuell erstellen:**
```bash
php database/seed.php
```
Dies erstellt Demo-Daten inklusive Admin-Account (`admin / Admin2026!x`).

### 5. Verzeichnisberechtigungen

```bash
chmod -R 755 storage/
chown -R www-data:www-data storage/
```

## Webserver-Konfiguration

### Apache

Stellen Sie sicher, dass `mod_rewrite` aktiviert ist:

```bash
a2enmod rewrite
```

Virtual-Host-Konfiguration:

```apache
<VirtualHost *:443>
    ServerName classbook.ihre-schule.de
    DocumentRoot /var/www/open-classbook/public

    <Directory /var/www/open-classbook/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Zugriff auf andere Verzeichnisse verhindern
    <Directory /var/www/open-classbook>
        Require all denied
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/classbook.crt
    SSLCertificateKeyFile /etc/ssl/private/classbook.key

    ErrorLog ${APACHE_LOG_DIR}/classbook-error.log
    CustomLog ${APACHE_LOG_DIR}/classbook-access.log combined
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 443 ssl;
    server_name classbook.ihre-schule.de;
    root /var/www/open-classbook/public;
    index index.php;

    ssl_certificate /etc/ssl/certs/classbook.crt;
    ssl_certificate_key /etc/ssl/private/classbook.key;

    # Alle Anfragen an index.php weiterleiten
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-Verarbeitung
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Zugriff auf versteckte Dateien verhindern
    location ~ /\. {
        deny all;
    }

    # Zugriff auf nicht-public Verzeichnisse verhindern
    location ~ ^/(src|config|database|storage|tests|vendor) {
        deny all;
    }

    access_log /var/log/nginx/classbook-access.log;
    error_log /var/log/nginx/classbook-error.log;
}
```

### Entwicklungsserver

Fuer lokale Entwicklung genuegt der eingebaute PHP-Server:

```bash
php -S localhost:8080 -t public/
```

## HTTPS einrichten

**HTTPS ist dringend empfohlen** fuer den Produktivbetrieb. Ohne HTTPS werden Passwoerter und Session-Daten unverschluesselt uebertragen.

### Let's Encrypt (kostenlos)

```bash
apt install certbot python3-certbot-apache
certbot --apache -d classbook.ihre-schule.de
```

### Selbstsigniertes Zertifikat (nur fuer interne Netze)

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/classbook.key \
    -out /etc/ssl/certs/classbook.crt
```

## E-Mail konfigurieren

Fuer Passwort-Zuruecksetzung und Benachrichtigungen kann ein SMTP-Server konfiguriert werden.

Passen Sie die Mail-Einstellungen in `config/config.php` an:

```php
'mail' => [
    'enabled' => true,
    'host' => 'mail.ihre-schule.de',
    'port' => 587,
    'username' => 'classbook@ihre-schule.de',
    'password' => 'sicheres-passwort',
    'encryption' => 'tls',
    'from_address' => 'classbook@ihre-schule.de',
    'from_name' => 'Open-Classbook',
],
```

## Backup-Strategie

### Datenbank-Backup

```bash
# Taegliches Backup (als Cronjob einrichten)
mysqldump -u root -p open_classbook > /backup/classbook_$(date +%Y%m%d).sql
```

Cronjob-Eintrag (`crontab -e`):
```
0 2 * * * mysqldump -u root -p'passwort' open_classbook | gzip > /backup/classbook_$(date +\%Y\%m\%d).sql.gz
```

### Dateien sichern

```bash
# Uploads und Konfiguration sichern
tar -czf /backup/classbook_files_$(date +%Y%m%d).tar.gz \
    /var/www/open-classbook/config/config.php \
    /var/www/open-classbook/storage/uploads/
```

## Fehlerbehebung

### Weisse Seite / 500-Fehler

1. Debug-Modus aktivieren in `config/config.php`:
   ```php
   'debug' => true,
   ```
2. PHP-Fehlerlog pruefen: `storage/logs/`
3. Webserver-Fehlerlog pruefen

### Datenbank-Verbindungsfehler

1. MariaDB-Service pruefen: `systemctl status mariadb`
2. Zugangsdaten in `config/config.php` pruefen
3. Datenbank existiert: `mysql -u root -p -e "SHOW DATABASES;"`

### Berechtigungsfehler

```bash
chown -R www-data:www-data /var/www/open-classbook/storage/
chmod -R 755 /var/www/open-classbook/storage/
```

### Session-Probleme

1. PHP-Session-Verzeichnis pruefen: `php -i | grep session.save_path`
2. Berechtigungen des Session-Verzeichnisses pruefen
