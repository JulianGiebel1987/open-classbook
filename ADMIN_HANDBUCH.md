# Open-Classbook - Administratorhandbuch

## Inhaltsverzeichnis

1. [Erste Schritte](#1-erste-schritte)
2. [Benutzerverwaltung](#2-benutzerverwaltung)
3. [Klassenverwaltung](#3-klassenverwaltung)
4. [Klassenbuch](#4-klassenbuch)
5. [Fehlzeiten-Management](#5-fehlzeiten-management)
6. [Excel-Import](#6-excel-import)
7. [Dashboard](#7-dashboard)
8. [Systemadministration](#8-systemadministration)

---

## 1. Erste Schritte

### 1.1 Anmeldung

Oeffnen Sie Open-Classbook im Browser und melden Sie sich mit Ihren Zugangsdaten an:

- **Benutzername:** Ihr zugewiesener Benutzername
- **Passwort:** Ihr Passwort

Bei der ersten Anmeldung werden Sie aufgefordert, Ihr Passwort zu aendern.

### 1.2 Passwort-Anforderungen

- Mindestens 10 Zeichen
- Mindestens ein Grossbuchstabe
- Mindestens ein Kleinbuchstabe
- Mindestens eine Zahl

### 1.3 Sicherheit

- Nach 5 fehlgeschlagenen Anmeldeversuchen wird der Account fuer 15 Minuten gesperrt
- Die Sitzung laeuft nach 60 Minuten Inaktivitaet ab
- Melden Sie sich immer ueber den Logout-Button ab

### 1.4 Passwort vergessen

Falls Sie Ihr Passwort vergessen haben:
1. Klicken Sie auf "Passwort vergessen" auf der Login-Seite
2. Geben Sie Ihre E-Mail-Adresse ein
3. Sie erhalten einen Link zum Zuruecksetzen (sofern E-Mail konfiguriert)

Alternativ kann ein Admin das Passwort zuruecksetzen.

---

## 2. Benutzerverwaltung

*Zugriff: Admin, eingeschraenkt Sekretariat*

### 2.1 Benutzerliste

Unter **Benutzerverwaltung** sehen Sie alle registrierten Benutzer. Sie koennen:
- Nach Rolle filtern (Admin, Schulleitung, Sekretariat, Lehrer)
- Nach Benutzername oder E-Mail suchen
- Aktive/inaktive Benutzer anzeigen

### 2.2 Benutzer anlegen

1. Klicken Sie auf **Neuer Benutzer**
2. Fuellen Sie die Pflichtfelder aus:
   - **Benutzername** (eindeutig)
   - **Rolle** (Admin, Schulleitung, Sekretariat, Lehrer)
   - **E-Mail** (Pflicht fuer Lehrer-Rolle)
   - **Passwort** (wird dem Benutzer mitgeteilt)
3. Speichern

Der Benutzer wird bei der ersten Anmeldung zur Passwortaenderung aufgefordert.

### 2.3 Benutzer bearbeiten

- Klicken Sie auf **Bearbeiten** neben dem Benutzernamen
- Aendern Sie die gewuenschten Felder
- Der eigene Account kann nicht deaktiviert werden

### 2.4 Benutzer deaktivieren

- Deaktivierte Benutzer koennen sich nicht mehr anmelden
- Daten bleiben erhalten (kein Loeschen moeglich)
- Jederzeit reaktivierbar

### 2.5 Passwort zuruecksetzen

Als Admin koennen Sie das Passwort eines Benutzers zuruecksetzen. Der Benutzer wird bei der naechsten Anmeldung zur Aenderung aufgefordert.

---

## 3. Klassenverwaltung

*Zugriff: Admin, Sekretariat*

### 3.1 Klassenuebersicht

Die Uebersicht zeigt alle Klassen des aktuellen Schuljahres mit:
- Klassenname
- Klassenlehrer
- Schueleranzahl
- Filter nach Schuljahr

### 3.2 Klasse anlegen

1. Klicken Sie auf **Neue Klasse**
2. Geben Sie ein:
   - **Klassenname** (z.B. "5a")
   - **Schuljahr** (z.B. "2025/2026")
3. Speichern
4. Anschliessend Klassenlehrer und Fachlehrer zuweisen

### 3.3 Lehrer zuweisen

- **Klassenlehrer:** Genau ein Lehrer als Hauptverantwortlicher
- **Fachlehrer:** Mehrere Lehrer koennen der Klasse zugewiesen werden (Mehrfachauswahl)
- Nur zugewiesene Lehrer sehen die Klasse in ihrem Dashboard

### 3.4 Klassenuebersicht

In der Klassendetailansicht sehen Sie:
- Alle Schueler der Klasse
- Zugewiesene Lehrer
- Klassenbucheintraege (Link)

---

## 4. Klassenbuch

*Zugriff: Lehrer (eigene Klassen), Admin/Schulleitung/Sekretariat (alle)*

### 4.1 Eintraege anzeigen

- Waehlen Sie eine Klasse aus
- Eintraege werden chronologisch angezeigt
- Filtern nach Zeitraum und Lehrer moeglich

### 4.2 Neuen Eintrag erstellen

1. Klicken Sie auf **Neuer Eintrag**
2. Fuellen Sie aus:
   - **Datum** (Standard: heute)
   - **Stunde** (1-10)
   - **Thema** (Pflichtfeld)
   - **Notizen** (optional, z.B. Hausaufgaben)
3. Speichern

### 4.3 Eintrag bearbeiten

- **Lehrer:** Eigene Eintraege koennen innerhalb von 24 Stunden bearbeitet werden
- **Admin:** Kann alle Eintraege jederzeit bearbeiten
- Nach 24 Stunden ist der Eintrag fuer Lehrer gesperrt

### 4.4 Export

- **CSV-Export:** Zur Weiterverarbeitung in Excel
- **PDF-Export:** Fuer Ausdrucke und Archivierung
- Filter werden beim Export beruecksichtigt

---

## 5. Fehlzeiten-Management

### 5.1 Schueler-Fehlzeiten

*Zugriff: Lehrer (eigene Klassen), Sekretariat, Admin*

**Fehlzeit eintragen:**
1. Navigieren Sie zu **Fehlzeiten > Schueler**
2. Waehlen Sie die Klasse und den Schueler
3. Geben Sie ein:
   - **Datum von/bis**
   - **Status:** entschuldigt / unentschuldigt / offen
   - **Grund** (optional)
   - **Notizen** (optional)
4. Speichern

**Filtern:**
- Nach Klasse
- Nach Status (entschuldigt/unentschuldigt/offen)
- Nach Zeitraum

**Bearbeiten/Loeschen:**
- Ueber die Aktions-Buttons in der Uebersicht
- Loeschen erfordert Bestaetigung

### 5.2 Lehrer-Fehlzeiten

*Zugriff: Admin, Sekretariat, Lehrer (eigene Abwesenheit)*

**Typen:**
- **Krank** - Krankheitsbedingter Ausfall
- **Fortbildung** - Fortbildungsveranstaltung
- **Sonstiges** - Andere Gruende

**Selbstmeldung (Lehrer):**
Lehrer koennen ihre eigene Abwesenheit ueber **Meine Abwesenheit** melden.

**Admin/Sekretariat:**
Koennen Fehlzeiten fuer alle Lehrer eintragen, bearbeiten und loeschen.

---

## 6. Excel-Import

*Zugriff: Admin, Sekretariat*

### 6.1 Vorlagen herunterladen

Laden Sie die passende Vorlage herunter:
- **Lehrer-Import.xlsx** - Fuer den Lehrer-Import
- **Schueler-Import.xlsx** - Fuer den Schueler-Import

### 6.2 Vorlagen ausfuellen

**Lehrer-Import - Pflichtfelder:**
- Vorname
- Nachname
- Kuerzel (eindeutig, z.B. "MU")
- E-Mail
- Faecher

**Schueler-Import - Pflichtfelder:**
- Vorname
- Nachname
- Klasse (muss bereits existieren)
- Geburtsdatum

### 6.3 Import durchfuehren

1. Navigieren Sie zu **Import**
2. Waehlen Sie den Importtyp (Lehrer oder Schueler)
3. Laden Sie die ausgefuellte Excel-Datei hoch
4. Pruefen Sie die **Vorschau**:
   - Gruene Zeilen: Bereit zum Import
   - Rote Zeilen: Fehler (fehlende Pflichtfelder, Duplikate)
5. Klicken Sie auf **Importieren**

**Wichtig:**
- Beim Lehrer-Import werden automatisch Benutzer-Accounts erstellt
- Das generierte Passwort wird in der Ergebnisanzeige angezeigt
- Notieren Sie die Passwoerter und geben Sie diese an die Lehrer weiter

---

## 7. Dashboard

### 7.1 Admin-Dashboard

Zeigt Widgets fuer:
- Anzahl Lehrer / Schueler / Klassen
- Heutige Lehrer-Abwesenheiten
- Heutige Schueler-Fehlzeiten nach Klasse
- Offene unentschuldigte Fehlzeiten
- Schnellzugriff auf haeufig genutzte Funktionen

### 7.2 Lehrer-Dashboard

Zeigt:
- Eigene zugewiesene Klassen
- Schnellzugriff auf Klassenbuch
- Eigene Abwesenheiten

---

## 8. Systemadministration

### 8.1 Konfiguration

Die Konfiguration befindet sich in `config/config.php`. Aenderungen erfordern einen Neustart des Webservers bei Nginx bzw. werden bei Apache sofort wirksam.

**Wichtige Einstellungen:**
- `app.debug` - Debug-Modus (im Produktivbetrieb auf `false`)
- `session.timeout` - Session-Timeout in Sekunden (Standard: 3600)
- `session.cookie_secure` - Session-Cookie nur ueber HTTPS senden (Standard: `true`, fuer lokale Entwicklung auf `false` setzen)
- `session.cookie_samesite` - SameSite-Attribut des Session-Cookies (Standard: `'Lax'`)
- `security.max_login_attempts` - Maximale Login-Versuche (Standard: 5)
- `security.lockout_duration` - Sperrdauer in Sekunden (Standard: 900)
- `security.csp_enabled` - Content Security Policy aktivieren (Standard: `true`)
- `security.csp_report_only` - CSP nur im Report-Only-Modus (Standard: `false`, fuer initiales Deployment auf `true` setzen)
- `security.rate_limit_requests` - Maximale Anfragen pro Zeitfenster und IP (Standard: 120)
- `security.rate_limit_window` - Zeitfenster fuer Rate Limiting in Sekunden (Standard: 60)

### 8.2 Logs

Anwendungslogs befinden sich in `storage/logs/`. Regelmaessige Pruefung wird empfohlen:
- Fehlerprotokolle
- Login-Versuche
- Datenaenderungen (Audit-Log)

### 8.3 Backup

Taegliche Backups werden dringend empfohlen:
- Datenbank: `mysqldump` (siehe INSTALL.md)
- Dateien: `config/config.php` und `storage/uploads/`

### 8.4 Updates

Siehe [UPDATE.md](UPDATE.md) fuer die Update-Anleitung.

### 8.5 Schuljahreswechsel

Am Anfang eines neuen Schuljahres:
1. Neue Klassen fuer das neue Schuljahr anlegen
2. Lehrer den neuen Klassen zuweisen
3. Schueler den neuen Klassen zuordnen (oder per Excel-Import)
4. Alte Schuljahre bleiben im System erhalten und sind ueber den Filter zugaenglich
