# Open-Classbook - Manuelle Test-Checkliste

## Voraussetzungen
- [ ] PHP 8.2+ installiert
- [ ] MariaDB 10.6+ laeuft
- [ ] Datenbank `open_classbook` existiert
- [ ] `composer install` ausgefuehrt
- [ ] Migrationen ausgefuehrt: `php database/migrate.php`
- [ ] Testdaten geladen: `php database/seed.php`
- [ ] Entwicklungsserver gestartet: `php -S localhost:8080 -t public/`

## 1. Authentifizierung

### Login
- [ ] Login-Seite wird angezeigt unter `/login`
- [ ] Login mit gueltigem Admin-Account: `admin / Admin2026!x`
- [ ] Login mit falschem Passwort zeigt Fehlermeldung
- [ ] Login mit nicht existierendem Benutzer zeigt gleiche Fehlermeldung (User Enumeration Prevention)
- [ ] Nach 5 fehlgeschlagenen Versuchen wird Account temporaer gesperrt
- [ ] Weiterleitung zum Dashboard nach erfolgreichem Login
- [ ] Session-Timeout nach 60 Minuten Inaktivitaet

### Logout
- [ ] Logout ueber Navigation funktioniert
- [ ] Nach Logout ist Dashboard nicht mehr erreichbar
- [ ] Weiterleitung zur Login-Seite nach Logout

### Passwort aendern
- [ ] Passwort-Aendern-Formular erreichbar
- [ ] Altes Passwort wird validiert
- [ ] Neues Passwort muss Komplexitaetsanforderungen erfuellen (min. 10 Zeichen, Gross-/Kleinbuchstaben, Zahl)
- [ ] Fehlermeldung bei zu schwachem Passwort
- [ ] Erfolgsmeldung nach Passwortaenderung

### Passwort vergessen
- [ ] Formular unter `/forgot-password` erreichbar
- [ ] Gleiche Meldung bei gueltigem und ungueltigem E-Mail (User Enumeration Prevention)
- [ ] Gueltige E-Mail eines aktiven Users: E-Mail mit Reset-Link wird empfangen
- [ ] Link oeffnet `/reset-password/{token}` mit Passwort-Formular
- [ ] Inaktiver User: keine E-Mail wird versendet, identische Erfolgsmeldung
- [ ] Abgelaufener Token (expires < NOW): Reset-Link zeigt Fehlermeldung "Ungueltiger oder abgelaufener Link"
- [ ] Token ist Single-Use: zweiter Aufruf desselben Links schlaegt fehl
- [ ] Schwaches neues Passwort: Fehlermeldung mit Komplexitaets-Anforderungen
- [ ] Nach erfolgreichem Reset: Redirect zu `/login`, Login mit neuem Passwort funktioniert
- [ ] `mail.enabled = false` in Config: keine Exception, Logger-Warning, identische Erfolgsmeldung

## 2. Rollenbasierte Zugriffskontrolle (RBAC)

### Als Admin einloggen
- [ ] Dashboard zeigt Admin-Widgets (Anzahl Lehrer, Schueler, Klassen)
- [ ] Zugriff auf Benutzerverwaltung
- [ ] Zugriff auf alle Klassen
- [ ] Zugriff auf alle Klassenbuecher
- [ ] Zugriff auf alle Fehlzeiten
- [ ] Zugriff auf Import-Funktion

### Als Lehrer einloggen (`m.mueller / Lehrer2026!a`)
- [ ] Dashboard zeigt nur eigene Klassen
- [ ] Kein Zugriff auf Benutzerverwaltung (403)
- [ ] Nur eigene Klassen im Klassenbuch sichtbar
- [ ] Fehlzeiten nur fuer eigene Klassen
- [ ] Eigene Abwesenheitsmeldung moeglich

### Als Schulleitung einloggen (`k.schmidt / Leitung2026!`)
- [ ] Dashboard zeigt Uebersicht
- [ ] Leserechte auf Klassenbuecher
- [ ] Kein Zugriff auf Benutzerverwaltung (403)

### Als Sekretariat einloggen (`s.meyer / Sekret2026!!`)
- [ ] Zugriff auf Benutzerverwaltung (eingeschraenkt)
- [ ] Zugriff auf Fehlzeiten
- [ ] Import-Funktion verfuegbar

## 3. Benutzerverwaltung (Admin)

- [ ] Benutzerliste wird angezeigt mit allen Benutzern
- [ ] Filter nach Rolle funktioniert
- [ ] Suche nach Benutzername/E-Mail funktioniert
- [ ] Neuen Benutzer anlegen (alle Rollen testen)
- [ ] Benutzer bearbeiten
- [ ] Benutzer deaktivieren/aktivieren
- [ ] Passwort-Reset durch Admin
- [ ] Button "PW per E-Mail" generiert neues Zufallspasswort und versendet es an die hinterlegte E-Mail (Benutzer muss es beim naechsten Login aendern)
- [ ] Button "PW per E-Mail" nicht sichtbar, wenn User keine E-Mail hat
- [ ] Button "PW per E-Mail" zeigt Fehler, wenn `mail.enabled = false` in Config
- [ ] Sich selbst kann man nicht deaktivieren
- [ ] E-Mail-Pflichtfeld fuer Lehrer-Rolle

## 4. Klassenverwaltung

- [ ] Klassenuebersicht zeigt alle Klassen des Schuljahres
- [ ] Schueleranzahl pro Klasse wird angezeigt
- [ ] Filter nach Schuljahr funktioniert
- [ ] Neue Klasse anlegen (Name, Schuljahr)
- [ ] Klassenlehrer zuweisen
- [ ] Fachlehrer zuweisen (Mehrfachauswahl)
- [ ] Klasse bearbeiten
- [ ] Klassendetails mit Schuelerliste anzeigen

## 5. Klassenbuch

- [ ] Klassenbuch-Ansicht pro Klasse (chronologisch)
- [ ] Neuen Eintrag erstellen: Datum, Stunde (1-10), Thema, Notizen
- [ ] Eigenen Eintrag bearbeiten (innerhalb 24h)
- [ ] Nach 24h ist eigener Eintrag nicht mehr bearbeitbar
- [ ] Admin kann alle Eintraege bearbeiten
- [ ] Filter nach Datum (von/bis) funktioniert
- [ ] Filter nach Lehrer funktioniert
- [ ] CSV-Export funktioniert und laesst sich in Excel oeffnen

## 6. Fehlzeiten-Management Schueler

- [ ] Fehlzeiten-Uebersicht wird angezeigt
- [ ] Filter nach Klasse funktioniert
- [ ] Filter nach entschuldigt/unentschuldigt/offen funktioniert
- [ ] Filter nach Zeitraum funktioniert
- [ ] Neue Fehlzeit eintragen (Schueler, Datum, Status, Grund)
- [ ] Fehlzeit bearbeiten
- [ ] Fehlzeit loeschen (mit Bestaetigungsdialog)
- [ ] Lehrer sehen nur Fehlzeiten eigener Klassen

## 7. Fehlzeiten-Management Lehrer

- [ ] Uebersicht aller Lehrkraft-Abwesenheiten
- [ ] Admin kann Fehlzeiten eintragen
- [ ] Lehrer kann eigene Abwesenheit melden (Selbstmeldung)
- [ ] Filter nach Typ (krank/fortbildung/sonstiges)
- [ ] Filter nach Zeitraum
- [ ] Fehlzeit bearbeiten und loeschen

## 8. Excel-Import

- [ ] Import-Seite erreichbar
- [ ] Vorlage "Lehrer-Import.xlsx" kann heruntergeladen werden
- [ ] Vorlage "Schueler-Import.xlsx" kann heruntergeladen werden
- [ ] Lehrer-Import: Datei hochladen zeigt Vorschau
- [ ] Lehrer-Import: Duplikate werden erkannt
- [ ] Lehrer-Import: Fehlende Pflichtfelder werden angezeigt
- [ ] Lehrer-Import: Bestaetigung erstellt Lehrer und User-Accounts
- [ ] Schueler-Import: Datei hochladen zeigt Vorschau
- [ ] Schueler-Import: Klasse muss existieren
- [ ] Schueler-Import: Bestaetigung erstellt Schueler-Datensaetze

## 9. Responsive Design

- [ ] Desktop-Ansicht: Seitenlayout korrekt
- [ ] Tablet-Ansicht (768px): Layout passt sich an
- [ ] Mobile-Ansicht (375px): Hamburger-Menue sichtbar
- [ ] Tabellen sind auf mobil horizontal scrollbar
- [ ] Formulare sind auf Touch-Geraeten bedienbar
- [ ] Navigation klappt auf mobil korrekt ein/aus

## 10. Barrierefreiheit

- [ ] Tastaturnavigation funktioniert (Tab-Reihenfolge)
- [ ] Fokus-Indikatoren sind sichtbar
- [ ] ARIA-Labels auf interaktiven Elementen
- [ ] Kontrastverhältnisse ausreichend

## 11. Sicherheit

- [ ] CSRF-Token in allen Formularen vorhanden
- [ ] Direkter POST ohne CSRF-Token wird abgelehnt
- [ ] XSS: HTML in Eingabefeldern wird escaped
- [ ] Zugriff auf geschuetzte Seiten ohne Login leitet zur Login-Seite
- [ ] SQL-Injection: Sonderzeichen in Suchfeldern verursachen keinen Fehler
- [ ] Session-ID wird bei Login erneuert

## 12. UX

- [ ] Erfolgsmeldungen (gruene Flash Messages) werden angezeigt
- [ ] Fehlermeldungen (rote Flash Messages) werden angezeigt
- [ ] Bestaetigungsdialoge bei kritischen Aktionen (Loeschen)
- [ ] Breadcrumb-Navigation ist korrekt
- [ ] Clientseitige Suchfunktion in Listen filtert korrekt
