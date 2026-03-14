# Open-Classbook - Update-Anleitung

## Vor dem Update

1. **Backup erstellen** (unbedingt!)

```bash
# Datenbank sichern
mysqldump -u root -p open_classbook > backup_vor_update_$(date +%Y%m%d).sql

# Konfiguration und Uploads sichern
cp config/config.php config/config.php.backup
tar -czf uploads_backup.tar.gz storage/uploads/
```

2. **Changelog lesen** - Pruefen Sie [CHANGELOG.md](CHANGELOG.md) auf Breaking Changes

## Update durchfuehren

### Option A: Aus ZIP-Paket (empfohlen)

```bash
cd /var/www/open-classbook

# Konfiguration sichern
cp config/config.php /tmp/config.php.backup

# Neue Version entpacken (ueberschreibt Dateien)
unzip -o open-classbook-v1.x.x.zip -d /var/www/open-classbook

# Konfiguration wiederherstellen
cp /tmp/config.php.backup config/config.php

# Abhaengigkeiten aktualisieren
composer install --no-dev --optimize-autoloader

# Neue Migrationen ausfuehren
php database/migrate.php

# Berechtigungen pruefen
chown -R www-data:www-data storage/
chmod -R 755 storage/
```

### Option B: Aus Git

```bash
cd /var/www/open-classbook

# Aenderungen holen
git fetch origin
git pull origin main

# Abhaengigkeiten aktualisieren
composer install --no-dev --optimize-autoloader

# Neue Migrationen ausfuehren
php database/migrate.php

# Berechtigungen pruefen
chown -R www-data:www-data storage/
chmod -R 755 storage/
```

## Nach dem Update

1. **Anwendung testen** - Melden Sie sich an und pruefen Sie die Grundfunktionen
2. **Logs pruefen** - Schauen Sie in `storage/logs/` nach Fehlern
3. **Cache leeren** - Falls vorhanden: `rm -rf storage/cache/*`

## Rollback

Falls das Update Probleme verursacht:

```bash
# Datenbank wiederherstellen
mysql -u root -p open_classbook < backup_vor_update_XXXXXXXX.sql

# Dateien wiederherstellen (Git)
git checkout v1.0.0

# Oder alte ZIP-Version erneut einspielen
```

## Versionierung

Open-Classbook verwendet Semantic Versioning (SemVer):
- **Major** (1.x.x): Inkompatible Aenderungen, Migrationsschritte noetig
- **Minor** (x.1.x): Neue Features, abwaertskompatibel
- **Patch** (x.x.1): Bugfixes, abwaertskompatibel

Migrationen werden automatisch erkannt und nur neue ausgefuehrt. Bereits ausgefuehrte Migrationen werden uebersprungen.
