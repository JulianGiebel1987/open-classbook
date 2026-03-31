# Changelog

Alle wesentlichen Aenderungen an Open-Classbook werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.1.0/)
und dieses Projekt verwendet [Semantic Versioning](https://semver.org/lang/de/).

## [1.0.0] - 2026-03-31

Erstes stabiles Release von Open-Classbook — eine schlanke, datenschutzkonforme
Schulverwaltungsplattform fuer Foerder-, Grund- und Hauptschulen.

### Authentifizierung & Sicherheit

- Login/Logout mit Session-basierter Authentifizierung
- Passwort-Hashing mit bcrypt (min. 10 Zeichen, Komplexitaetspruefung)
- CSRF-Token-Schutz fuer alle Formulare
- Brute-Force-Schutz (5 Versuche, 15 Min. Sperre)
- Session-Timeout nach 60 Minuten Inaktivitaet
- Erstlogin-Passwortaenderung erzwungen
- Passwort-Zuruecksetzen per E-Mail (Token-basiert)
- Session-Haertung: HttpOnly, Secure, SameSite=Lax, use_only_cookies
- Security-Headers-Middleware: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, HSTS
- Content Security Policy (CSP) mit Report-Only-Modus fuer Test-Deployments
- Rate-Limiting-Middleware: datenbankbasiert, per IP, konfigurierbare Schwellwerte
- Umfassendes Sicherheits-Audit mit behobenen Luecken
- Audit-Logging fuer sicherheitsrelevante Aktionen

### Rollenbasierte Zugriffskontrolle (RBAC)

- Rollen: Admin, Schulleitung, Sekretariat, Lehrer, Schueler
- Middleware-basierte Zugriffspruefung auf jeder Route
- Dynamische Navigation nach Rolle
- Lehrer sehen nur eigene Klassen und Fehlzeiten
- Schueler sehen nur eigene Fehlzeiten

### Benutzerverwaltung

- Benutzer anlegen, bearbeiten, deaktivieren, loeschen (inkl. Dateiloeschung)
- Filter nach Rolle und Freitextsuche
- Admin-Passwort-Reset
- Automatische Lehrer-/Schuelerprofil-Erstellung beim Anlegen

### Klassenverwaltung

- Klassen anlegen und bearbeiten (pro Schuljahr)
- Klassenlehrer und Fachlehrer zuweisen (n:m)
- Schueler-Uebersicht pro Klasse
- Schueler-Klassenversetzung (Admin, Sekretariat, Schulleitung)
- Filter nach Schuljahr

### Digitales Klassenbuch

- Tageseintraege mit Datum, Stunde, Thema, Notizen
- Tagesansicht nach Datum gruppiert
- Schuelerbemerkungen pro Eintrag erfassbar
- Bearbeitungssperre nach 24 Stunden (Admin ausgenommen)
- Filter nach Datum und Lehrer
- CSV-Export und PDF-Export (via TCPDF)

### Fehlzeiten-Management Schueler

- Fehlzeiten erfassen (entschuldigt / unentschuldigt / offen)
- Mehrtaegige Fehlzeiten unterstuetzt
- Filter nach Klasse, Status, Zeitraum
- Bearbeiten und Loeschen mit Bestaetigungsdialog
- Zugriffsbeschraenkung auf eigene Klassen (Lehrer)
- E-Mail-Benachrichtigungen bei Erfassung

### Fehlzeiten-Management Lehrer

- Typen: krank, Fortbildung, sonstiges
- Lehrer-Selbstmeldung
- Admin/Sekretariat-Verwaltung
- Filter nach Typ und Zeitraum

### Import (Excel & CSV)

- Lehrer-Import mit automatischer Account- und Profilerstellung
- Schueler-Import mit Klassenvalidierung und Account-Erstellung
- Excel- und CSV-Format unterstuetzt
- Duplikaterkennung und detailliertes Fehlerprotokoll
- Vorschau vor finalem Import
- Downloadbare Vorlagen

### Nachrichtensystem

- Interne Nachrichten zwischen allen Nutzerrollen
- Einzelnachrichten und Gruppennachrichten
- Posteingang mit Gelesen/Ungelesen-Status
- Antwortfunktion (Thread-basiert)

### Dateiverwaltung

- Private und geteilte Ordner pro Nutzer
- Unterordner-Struktur
- Datei-Upload, Download, Loeschung
- PDF/CSV-Export-Funktionen

### Listenverwaltung

- Freie Listen anlegen und verwalten
- Inline-Bearbeitung
- Freigabe zwischen Nutzern
- PDF- und CSV-Export

### Zeugniserstellung

- Canvas-basierter Zeugnis-Editor
- Vorgefertigte und eigene Vorlagen
- Bilder und Textelemente frei positionierbar
- Zugriffssteuerung: Lehrer sehen nur eigene Vorlagen
- PDF-Export

### Stundenplanung

- Wochenplan pro Klasse und Lehrer
- Stunden mit Fach, Raum, Lehrer verknuepft
- Admin- und Sekretariatsverwaltung

### Vertretungsmodul

- Vertretungsplanung bei Lehrerausfall
- Verknuepfung mit Lehrer-Fehlzeiten
- Uebersicht fuer Schulleitung und Sekretariat

### Datenschutz (DSGVO)

- Datenschutzhinweise-Seite eingebunden (`/datenschutz`)
- DSGVO-Konformitaetsanalyse durchgefuehrt
- On-Premises-Betrieb ohne externe Dienste

### Dashboards

- Admin-Dashboard: Statistiken, aktuelle Abwesenheiten, Schnellzugriff
- Lehrer-Dashboard: eigene Klassen, heutige Eintraege, Schnellzugriff
- Schulleitung/Sekretariat-Dashboard: Schuluebersicht, Fehlzeiten

### UI & Barrierefreiheit

- Modernes, professionelles Frontend-Design
- Responsive Design (Mobile-First, CSS Grid/Flexbox)
- Hamburger-Menue auf mobilen Geraeten
- WCAG 2.1 AA konforme Kontraste
- ARIA-Labels und vollstaendige Tastaturnavigation
- Flash Messages (Erfolg / Fehler / Info)
- Bestaetigungsdialoge fuer kritische Aktionen
- Breadcrumb-Navigation
- Clientseitige Suchfunktion

### Infrastruktur

- PHP 8.2+ mit Vanilla MVC-Architektur (kein Framework)
- MariaDB 10.6+ mit PDO Prepared Statements (kein ORM)
- PSR-12 Coding-Style, PSR-4 Autoloading via Composer
- Nummerierte SQL-Migrationen (012 Dateien)
- Interaktiver Web-Installer (`install.php`)
- Testdaten-Seed-Skript (`database/seed.php`)
- PHPUnit-Testsuite
- Performance-Indizes auf Fremdschluessel und Datumsspalten
- Fehlerseiten: 403, 404, 429, 500
- Deployment als ZIP-Paket / Installer-Skript
