# Open-Classbook - Installationsanleitung

## Systemvoraussetzungen

### Server

| Komponente   | Mindestversion | Empfohlen      |
|-------------|---------------|----------------|
| PHP         | 8.3           | 8.4+           |
| MariaDB     | 10.6          | 10.11+         |
| Webserver   | Apache 2.4    | Nginx 1.24+    |
| Composer    | 2.0           | 2.7+           |

### Vorbereitung: Pakete installieren (Ubuntu/Debian)

Die folgende Anleitung gilt fuer Ubuntu 22.04+ / Debian 12+. Passen Sie die PHP-Versionsnummer (z.B. `8.3`) an Ihre installierte PHP-Version an.

**PHP und MariaDB installieren:**
```bash
apt update
apt install php php-cli php-fpm mariadb-server composer
```

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
# Ersetzen Sie 8.4 durch Ihre PHP-Version (z.B. 8.3)
apt install php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip php8.4-gd php8.4-curl
```

Bereits standardmaessig enthalten (kein separates Paket noetig): `json`, `session`, `openssl`, `pdo`.

> **Haeufiger Fehler:** Wenn die CLI-PHP-Version (z.B. 8.4) nicht mit der PHP-FPM-Version (z.B. 8.2) uebereinstimmt, kann `composer install` funktionieren, aber die Webseite bleibt weiss. Stellen Sie sicher, dass PHP-FPM in derselben Version laeuft wie die CLI. Pruefen Sie mit:
> ```bash
> php -v                    # CLI-Version
> systemctl list-units | grep php.*fpm   # Laufende FPM-Version
> ```

Nach der Installation der Extensions PHP-FPM bzw. den Webserver neu starten:
```bash
systemctl restart php8.4-fpm   # bei Nginx
systemctl restart apache2       # bei Apache
```

### MariaDB einrichten

Nach der Installation von MariaDB muss ein Datenbank-Benutzer fuer Open-Classbook angelegt werden.

> **Wichtig:** Unter Ubuntu/Debian verwendet MariaDB fuer den `root`-Benutzer standardmaessig Socket-Authentifizierung (`unix_socket`). Eine Verbindung per Passwort (z.B. ueber `127.0.0.1`) schlaegt daher fehl mit der Meldung: `Access denied for user 'root'@'localhost'`. Erstellen Sie stattdessen einen eigenen Datenbank-Benutzer.

**1. MariaDB-Dienst starten und aktivieren:**
```bash
systemctl start mariadb
systemctl enable mariadb
```

**2. Datenbank und Benutzer anlegen:**

Melden Sie sich als System-Root an der MariaDB-Konsole an:
```bash
mariadb -u root
```

Fuehren Sie dann folgende SQL-Befehle aus:
```sql
CREATE DATABASE open_classbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'classbook'@'localhost' IDENTIFIED BY 'IhrSicheresPasswort';
GRANT ALL PRIVILEGES ON open_classbook.* TO 'classbook'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> Ersetzen Sie `IhrSicheresPasswort` durch ein sicheres Passwort Ihrer Wahl. Merken Sie sich die Zugangsdaten fuer den Installer (Schritt 3).

**3. Verbindung testen:**
```bash
mariadb -u classbook -p open_classbook
```

Geben Sie das zuvor gesetzte Passwort ein. Wenn die Verbindung steht, war die Einrichtung erfolgreich. Verlassen Sie die Konsole mit `EXIT;`.

### PHP-Extensions (erforderlich)

- `pdo` und `pdo_mysql` - Datenbankzugriff
- `mbstring` - Zeichenkodierung
- `json` - JSON-Verarbeitung
- `session` - Session-Management
- `openssl` - Passwortsicherheit
- `xml` / `dom` - Excel-Import (PhpSpreadsheet)
- `gd` - Bildverarbeitung (PhpSpreadsheet)
- `zip` - Excel-Import/Export

> **Wichtig:** Alle Extensions muessen fuer die gleiche PHP-Version installiert sein, die auch PHP-FPM nutzt. Pruefen Sie mit `php -v` (CLI) und `php-fpmX.Y -v` ob die Versionen uebereinstimmen. Falls nicht, installieren Sie die Extensions fuer beide Versionen (z.B. `php8.4-gd` und `php8.2-gd`).

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

> **Hinweis fuer Root-Benutzer:** Composer verweigert die Ausfuehrung als Root. Setzen Sie die Umgebungsvariable:
> ```bash
> COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader
> ```

Fuer Entwicklungsumgebungen (mit PHPUnit):

```bash
composer install
```

### 3. Installer ausfuehren

Der interaktive Installer fuehrt Sie durch die komplette Einrichtung:

```bash
php install.php
```

Der Installer fragt nach den Datenbank-Zugangsdaten. Verwenden Sie den zuvor angelegten Benutzer (nicht `root`):

| Abfrage              | Eingabe                              |
|----------------------|--------------------------------------|
| Datenbank-Host       | `127.0.0.1` (Enter fuer Standard)    |
| Datenbank-Port       | `3306` (Enter fuer Standard)         |
| Datenbank-Name       | `open_classbook` (Enter fuer Standard) |
| Datenbank-Benutzer   | `classbook` (oder Ihr gewaehlter Benutzername) |
| Datenbank-Passwort   | Das bei der MariaDB-Einrichtung vergebene Passwort |

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
Passen Sie die Datenbankzugangsdaten in `config/config.php` an. Verwenden Sie den zuvor angelegten Datenbank-Benutzer (z.B. `classbook`), nicht `root`.

**b) Datenbank erstellen** (falls noch nicht im MariaDB-Einrichtungsschritt geschehen):
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

    # PHP-Verarbeitung (Version im Socket-Pfad anpassen!)
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
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
mariadb-dump -u classbook -p open_classbook > /backup/classbook_$(date +%Y%m%d).sql
```

Cronjob-Eintrag (`crontab -e`):
```
0 2 * * * mariadb-dump -u classbook -p'passwort' open_classbook | gzip > /backup/classbook_$(date +\%Y\%m\%d).sql.gz
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

1. **Webserver-Fehlerlog pruefen** - hier steht meistens die Ursache:
   ```bash
   tail -20 /var/log/nginx/error.log      # Nginx
   tail -20 /var/log/apache2/error.log    # Apache
   ```
2. **PHP direkt testen** (zeigt Fehler ohne Webserver):
   ```bash
   cd /var/www/open-classbook
   php -d display_errors=1 public/index.php
   ```
3. **Haeufigste Ursachen:**
   - `vendor/autoload.php` fehlt → `composer install` ausfuehren
   - PHP-Extension fehlt (z.B. `ext-dom`, `ext-gd`) → fehlende Extension installieren
   - PHP-FPM-Version stimmt nicht mit Composer-Anforderungen ueberein → Socket-Pfad in Nginx/Apache anpassen
4. Debug-Modus aktivieren in `config/config.php`:
   ```php
   'debug' => true,
   ```

### Datenbank-Verbindungsfehler

1. MariaDB-Service pruefen: `systemctl status mariadb`
2. Zugangsdaten in `config/config.php` pruefen
3. Datenbank existiert: `mariadb -u classbook -p -e "SHOW DATABASES;"`

**"Access denied for user 'root'@'localhost'":**

MariaDB unter Ubuntu/Debian verwendet fuer `root` standardmaessig Socket-Authentifizierung. Der Installer kann sich daher nicht mit `root` per Passwort verbinden. Loesung: Eigenen Datenbank-Benutzer anlegen (siehe Abschnitt "MariaDB einrichten" oben).

### Berechtigungsfehler

```bash
chown -R www-data:www-data /var/www/open-classbook/storage/
chmod -R 755 /var/www/open-classbook/storage/
```

### Admin-Passwort zuruecksetzen

Falls Sie sich ausgesperrt haben, koennen Sie das Admin-Passwort per Kommandozeile zuruecksetzen:

```bash
HASH=$(php -r 'echo password_hash("NeuesPasswort2026", PASSWORD_BCRYPT);') && \
mariadb -u classbook -p open_classbook -e "UPDATE users SET password_hash = '$HASH' WHERE username = 'admin';"
```

> **Hinweis:** Vermeiden Sie Sonderzeichen wie `!` im Passwort innerhalb des Befehls, da Bash diese interpretiert. Aendern Sie das Passwort anschliessend ueber die Weboberflaeche.

### PHP-FPM-Version stimmt nicht

Symptom: `composer install` laeuft erfolgreich, aber die Webseite zeigt "Composer detected issues in your platform: Your Composer dependencies require a PHP version >= 8.3.0".

Ursache: Nginx/Apache nutzt eine aeltere PHP-FPM-Version als die CLI.

Loesung fuer Nginx - Socket-Pfad anpassen:
```bash
# Aktuelle PHP-FPM-Versionen anzeigen
ls /run/php/php*-fpm.sock

# In der Nginx-Siteconfig den Socket-Pfad aendern, z.B.:
# fastcgi_pass unix:/run/php/php8.4-fpm.sock;

# Danach Nginx neu laden
nginx -t && systemctl reload nginx
```

### Session-Probleme

1. PHP-Session-Verzeichnis pruefen: `php -i | grep session.save_path`
2. Berechtigungen des Session-Verzeichnisses pruefen
