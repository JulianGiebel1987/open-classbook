# Changelog

Alle wesentlichen Aenderungen an Open-Classbook werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.1.0/)
und dieses Projekt verwendet [Semantic Versioning](https://semver.org/lang/de/).

## [1.0.0] - 2026-03-14

### Hinzugefuegt

**Authentifizierung & Sicherheit**
- Login/Logout mit Session-basierter Authentifizierung
- Passwort-Hashing mit bcrypt
- CSRF-Token-Schutz fuer alle Formulare
- Brute-Force-Schutz (5 Versuche, 15 Min. Sperre)
- Session-Timeout nach 60 Minuten Inaktivitaet
- Erstlogin-Passwortaenderung
- Passwort-Zuruecksetzen per E-Mail (Token-basiert)
- Passwort-Komplexitaetspruefung (min. 10 Zeichen)

**Rollenbasierte Zugriffskontrolle (RBAC)**
- Rollen: Admin, Schulleitung, Sekretariat, Lehrer
- Middleware-basierte Zugriffspruefung
- Dynamische Navigation nach Rolle

**Benutzerverwaltung**
- Benutzer anlegen, bearbeiten, deaktivieren
- Filter nach Rolle und Suche
- Admin-Passwort-Reset

**Klassenverwaltung**
- Klassen anlegen und bearbeiten
- Klassenlehrer und Fachlehrer zuweisen
- Schueler-Uebersicht pro Klasse
- Filter nach Schuljahr

**Digitales Klassenbuch**
- Tageseintraege mit Datum, Stunde, Thema, Notizen
- Bearbeitungssperre nach 24 Stunden (Admin ausgenommen)
- Filter nach Datum und Lehrer
- CSV-Export
- PDF-Export (via TCPDF)

**Fehlzeiten-Management Schueler**
- Fehlzeiten erfassen (entschuldigt/unentschuldigt/offen)
- Filter nach Klasse, Status, Zeitraum
- Bearbeiten und Loeschen mit Bestaetigungsdialog
- Zugriffsbeschraenkung auf eigene Klassen (Lehrer)

**Fehlzeiten-Management Lehrer**
- Typen: krank, Fortbildung, sonstiges
- Lehrer-Selbstmeldung
- Admin/Sekretariat-Verwaltung
- Filter nach Typ und Zeitraum

**Excel-Import**
- Lehrer-Import mit automatischer Account-Erstellung
- Schueler-Import mit Klassenvalidierung
- Duplikaterkennung und Fehlerprotokoll
- Vorschau vor finalem Import
- Downloadbare Vorlagen

**Dashboards**
- Admin-Dashboard mit Widgets (Statistiken, Abwesenheiten)
- Lehrer-Dashboard (eigene Klassen, Schnellzugriff)
- Schulleitung/Sekretariat-Dashboard

**UI & Barrierefreiheit**
- Responsive Design (Mobile-First, CSS Grid/Flexbox)
- Hamburger-Menue auf mobilen Geraeten
- WCAG 2.1 AA konforme Kontraste
- ARIA-Labels und Tastaturnavigation
- Flash Messages (Erfolg/Fehler/Info)
- Bestaetigungsdialoge fuer kritische Aktionen
- Breadcrumb-Navigation
- Clientseitige Suchfunktion

**Infrastruktur**
- PHP 8.2+ mit Vanilla MVC-Architektur
- MariaDB 10.6+ mit PDO Prepared Statements
- Nummerierte SQL-Migrationen (11 Dateien)
- PSR-4 Autoloading via Composer
- Interaktiver Installer (`install.php`)
- Testdaten-Seed-Skript (`database/seed.php`)
- PHPUnit-Testsuite (147 Tests)
- Audit-Logging
- Performance-Indizes auf Fremdschluessel und Datumsspalten
