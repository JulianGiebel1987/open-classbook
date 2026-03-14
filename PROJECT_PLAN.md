# Open-Classbook - Projektplan

## Phase 1: Grundstruktur & Infrastruktur (2-3 Wochen)

### 1.1 Projektsetup
- [ ] Verzeichnisstruktur anlegen (`public/`, `src/`, `config/`, `database/`, `storage/`, `tests/`)
- [ ] Composer initialisieren (`composer.json` mit Autoloading PSR-4)
- [ ] Front-Controller erstellen (`public/index.php`)
- [ ] Einfachen Router implementieren (URL -> Controller-Mapping)
- [ ] Basis-Template-System erstellen (Layout mit Header, Footer, Navigation)
- [ ] Konfigurationsdatei anlegen (`config/config.php` mit DB-Zugang, App-Name, E-Mail-Einstellungen)
- [ ] `.htaccess` / Nginx-Konfiguration fuer URL-Rewriting

### 1.2 Datenbank
- [ ] Migrationssystem erstellen (`database/migrate.php` - fuehrt nummerierte SQL-Dateien aus)
- [ ] Migration `001_create_users.sql` - Users-Tabelle mit Rollen
- [ ] Migration `002_create_teachers.sql` - Lehrerdaten
- [ ] Migration `003_create_students.sql` - Schuelerdaten
- [ ] Migration `004_create_classes.sql` - Klassen
- [ ] Migration `005_create_class_teacher.sql` - Lehrer-Klassen-Zuordnung (n:m)
- [ ] Migration `006_create_classbook_entries.sql` - Klassenbucheintraege
- [ ] Migration `007_create_absences_students.sql` - Schueler-Fehlzeiten
- [ ] Migration `008_create_absences_teachers.sql` - Lehrer-Fehlzeiten
- [ ] Migration `009_create_login_attempts.sql` - Login-Versuche fuer Brute-Force-Schutz
- [ ] Datenbank-Verbindungsklasse erstellen (`src/Database.php` - PDO Singleton)

### 1.3 Authentifizierung
- [ ] Session-Management implementieren (Start, Destroy, Timeout nach 60 Min)
- [ ] Login-Controller & View erstellen (Benutzername/E-Mail + Passwort)
- [ ] Passwort-Hashing mit `password_hash()` / `password_verify()` (bcrypt)
- [ ] CSRF-Token-Generierung und -Validierung als Middleware
- [ ] Brute-Force-Schutz: Max. 5 Login-Versuche, dann 15 Min Sperre
- [ ] Logout-Funktion implementieren
- [ ] Erstlogin-Erkennung: Passwortaenderung bei erster Anmeldung erzwingen
- [ ] Passwort-Aendern-Funktion (min. 10 Zeichen, Komplexitaetspruefung)
- [ ] Passwort-Zuruecksetzen via E-Mail (Token-basiert, 1h Gueltigkeit)

### 1.4 Rollenbasierte Zugriffskontrolle (RBAC)
- [ ] Auth-Middleware erstellen (prueft ob User eingeloggt ist)
- [ ] RBAC-Middleware erstellen (prueft Rolle gegen erlaubte Rollen pro Route)
- [ ] Rechte-Matrix als Konfiguration abbilden
- [ ] Navigationsmenue dynamisch nach Rolle anzeigen

## Phase 2: Kernfunktionen (3-4 Wochen)

### 2.1 Benutzerverwaltung (Admin)
- [ ] Nutzer-Uebersichtsseite mit Filter nach Rolle
- [ ] Nutzer anlegen (Formular fuer alle Rollen)
- [ ] Nutzer bearbeiten (Profildaten aendern)
- [ ] Nutzer deaktivieren/aktivieren
- [ ] Passwort-Reset durch Admin ausloesen
- [ ] Validierung: E-Mail-Pflicht fuer Lehrer, eindeutige Benutzernamen

### 2.2 Excel-Import
- [ ] PhpSpreadsheet als Composer-Abhaengigkeit einbinden
- [ ] Import-Vorlage: `Lehrer-Import.xlsx` erstellen und zum Download bereitstellen
- [ ] Import-Vorlage: `Schueler-Import.xlsx` erstellen und zum Download bereitstellen
- [ ] Import-Controller: Datei-Upload und Validierung
- [ ] Import-Logik Lehrer: Pflichtfelder pruefen, Duplikate erkennen, Fehlerprotokoll
- [ ] Import-Logik Schueler: Pflichtfelder pruefen, Klasse muss existieren, Fehlerprotokoll
- [ ] Import-Vorschau: Ergebnisse vor finalem Speichern anzeigen
- [ ] Automatische User-Account-Erstellung beim Import (mit Zufallspasswort)

### 2.3 Klassenverwaltung
- [ ] Klassen-Uebersichtsseite (alle Klassen des Schuljahres)
- [ ] Klasse anlegen (Name, Schuljahr)
- [ ] Klasse bearbeiten
- [ ] Klassenlehrer zuweisen
- [ ] Fachlehrer einer Klasse zuweisen (Mehrfachauswahl)
- [ ] Uebersicht: Welcher Lehrer unterrichtet welche Klassen
- [ ] Schueler einer Klasse anzeigen

### 2.4 Klassenbuch
- [ ] Klassenbuch-Ansicht pro Klasse (chronologisch, nach Datum filterbar)
- [ ] Neuen Eintrag erstellen: Datum, Unterrichtsstunde (1-10), Thema, Notizen
- [ ] Eigene Eintraege bearbeiten (nur innerhalb von 24h, danach nur Admin)
- [ ] Zugriffskontrolle: Lehrer nur eigene Klassen, Admin/Schulleitung/Sekretariat alle
- [ ] Export als CSV
- [ ] Export als PDF (via TCPDF)
- [ ] Filter: Zeitraum, Klasse, Lehrer

### 2.5 Fehlzeiten-Management Schueler
- [ ] Fehlzeit eintragen: Schueler, Datum von/bis, entschuldigt/unentschuldigt/offen, Grund, Notiz
- [ ] Mehrtageseintraege automatisch auf einzelne Tage aufschluesseln
- [ ] Fehlzeit bearbeiten und loeschen
- [ ] Fehlzeiten-Uebersicht pro Schueler
- [ ] Fehlzeiten-Uebersicht pro Klasse
- [ ] Zugriffskontrolle: Lehrer nur eigene Klassen

### 2.6 Fehlzeiten-Management Lehrer
- [ ] Lehrer-Selbstmeldung: Krank/Fortbildung/Sonstiges, Datum von/bis, Notiz
- [ ] Admin/Sekretariat kann Lehrer-Fehlzeiten eintragen
- [ ] Fehlzeiten bearbeiten und loeschen
- [ ] Fehlzeiten-Uebersicht aller Lehrer
- [ ] E-Mail-Benachrichtigung an Schulleitung/Sekretariat bei Krankmeldung (konfigurierbar)

### 2.7 Dashboards
- [ ] Admin-Dashboard: Widgets (Anzahl Lehrer/Schueler/Klassen, heutige Abwesenheiten Lehrer, heutige Abwesenheiten Schueler nach Klasse, offene unentschuldigte Fehlzeiten, Schnellzugriff)
- [ ] Sekretariat-/Schulleitung-Dashboard: Wie Admin, aber ohne Systemeinstellungen
- [ ] Lehrer-Dashboard: Meine Klassen, Schnellzugriff auf Klassenbuch, eigene Abwesenheiten

## Phase 3: UI & Polish (2 Wochen)

### 3.1 Responsive Design
- [x] CSS-Grundgeruest mit CSS-Variablen (Farben, Schriften, Abstaende)
- [x] Mobile-First-Layout mit CSS Grid/Flexbox
- [x] Navigation: Hamburger-Menue auf mobilen Geraeten
- [x] Tabellen responsive gestalten (horizontales Scrollen oder Card-Layout)
- [x] Formulare fuer Touch-Eingabe optimieren

### 3.2 Barrierefreiheit
- [x] Kontrastverhältnisse pruefen (WCAG 2.1 AA)
- [x] ARIA-Labels fuer interaktive Elemente
- [x] Tastaturnavigation sicherstellen
- [x] Fokus-Indikatoren sichtbar gestalten

### 3.3 UX-Verbesserungen
- [x] Erfolgsmeldungen und Fehlermeldungen einheitlich gestalten (Flash Messages)
- [x] Bestaetigungsdialoge fuer kritische Aktionen (Loeschen, Deaktivieren)
- [x] Ladeanimation fuer laengere Vorgaenge (Import, Export)
- [x] Breadcrumb-Navigation
- [x] Suchfunktion in Listen (clientseitig)

### 3.4 Fehlerbehebung & Optimierung
- [x] SQL-Abfragen optimieren (Indizes auf Fremdschluessel und Datumsspalten)
- [x] Input-Validierung vollstaendig pruefen (alle Formulare)
- [x] Error-Handling vereinheitlichen (404, 403, 500 Fehlerseiten)
- [x] Logging implementieren (Fehler, Login-Versuche, Datenaenderungen)

## Phase 4: Beta-Test (2-3 Wochen)

### 4.1 Testvorbereitung
- [x] PHPUnit-Tests fuer Auth-System schreiben
- [x] PHPUnit-Tests fuer Import-Logik schreiben
- [x] PHPUnit-Tests fuer RBAC-Pruefungen schreiben
- [x] PHPUnit-Tests fuer alle Models schreiben (User, Teacher, Student, SchoolClass, ClassbookEntry, AbsenceStudent, AbsenceTeacher)
- [x] PHPUnit-Tests fuer Router und View schreiben
- [x] Testdaten-Seed-Skript erstellen (Demo-Schule mit Beispieldaten)
- [x] Manuelle Test-Checkliste erstellen

### 4.2 Pilotschule
- [ ] Installation auf Test-Server des Schultraegers
- [ ] Schulung der Administratoren
- [ ] Feedback sammeln und priorisieren
- [ ] Kritische Bugs beheben
- [ ] Usability-Anpassungen vornehmen

## Phase 5: Release (1-2 Wochen)

### 5.1 Installer & Dokumentation
- [ ] Installer-Skript erstellen (PHP): Datenbankeinrichtung, Admin-Account, Grundkonfiguration
- [ ] `INSTALL.md` schreiben (Systemvoraussetzungen, Schritt-fuer-Schritt-Anleitung)
- [ ] `README.md` erstellen (Projektbeschreibung, Features, Screenshots)
- [ ] Admin-Handbuch erstellen (PDF oder HTML)
- [ ] Konfigurationshinweise fuer HTTPS, E-Mail-Server, Backups

### 5.2 Deployment
- [ ] Release-ZIP-Paket erstellen (ohne Entwicklungsdateien)
- [ ] Update-Mechanismus dokumentieren (neue Version einspielen + Migrationen)
- [ ] Changelog schreiben
- [ ] GitHub-Release erstellen mit ZIP-Download

---

## Priorisierung fuer sofortigen Start

Die Umsetzung beginnt mit diesen Aufgaben in folgender Reihenfolge:

1. **Projektsetup** (1.1) - Verzeichnisstruktur, Composer, Router, Templates
2. **Datenbank** (1.2) - Migrationssystem und alle Kerntabellen
3. **Authentifizierung** (1.3) - Login, Session, Passwort-Handling
4. **RBAC** (1.4) - Zugriffskontrolle
5. **Benutzerverwaltung** (2.1) - CRUD fuer alle Nutzertypen
6. **Klassenverwaltung** (2.3) - Klassen und Lehrer-Zuordnung
7. **Klassenbuch** (2.4) - Kern-Feature
8. **Fehlzeiten** (2.5 + 2.6) - Schueler und Lehrer
9. **Dashboards** (2.7) - Uebersichtsseiten
10. **Import/Export** (2.2 + Teile von 2.4) - Excel-Import und PDF/CSV-Export
