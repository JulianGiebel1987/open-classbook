# Implementierungsplan: Listen-System

## Uebersicht

Flexibles Listen-System, in dem Nutzer tabellarische Listen erstellen koennen — entweder leer oder mit vorausgefuellten Schuelernamen einer Klasse. Spalten unterstuetzen verschiedene Feldtypen. Listen koennen privat, global oder gezielt freigegeben sein. Alle Rollen ausser Schueler haben Zugriff.

---

## Feldtypen

| Typ | Beschreibung | Darstellung |
|-----|-------------|-------------|
| `text` | Freitext-Eingabe | `<input type="text">` |
| `checkbox` | Abhaken (Ja/Nein) | `<input type="checkbox">` |
| `number` | Zahlenwert | `<input type="number">` |
| `date` | Datum | `<input type="date">` |
| `select` | Auswahl aus vordefinierten Optionen | `<select>` (Optionen in Spalten-Config als JSON) |
| `rating` | Bewertung 1-5 (z.B. Noten) | `<select>` mit 1-6 |

---

## Schritt 1: Datenbank-Migration

**Datei:** `database/migrations/015_create_lists.sql`

### `lists` — Listen-Definitionen
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| title | VARCHAR(255) NOT NULL | Listenname |
| description | TEXT NULL | Optionale Beschreibung |
| owner_id | INT NOT NULL FK → users.id | Ersteller |
| visibility | ENUM('private','global','shared') DEFAULT 'private' | Sichtbarkeit |
| class_id | INT NULL FK → classes.id | Klasse (bei Schuelerlisten, sonst NULL) |
| created_at | TIMESTAMP DEFAULT NOW() | |
| updated_at | TIMESTAMP DEFAULT NOW() ON UPDATE NOW() | |

- INDEX auf `(owner_id)`, `(visibility)`, `(class_id)`

### `list_columns` — Spaltendefinitionen einer Liste
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| list_id | INT NOT NULL FK → lists.id ON DELETE CASCADE | |
| title | VARCHAR(255) NOT NULL | Spaltenname |
| type | ENUM('text','checkbox','number','date','select','rating') DEFAULT 'text' | Feldtyp |
| options | JSON NULL | Optionen fuer Select-Typ (z.B. `["Anwesend","Abwesend","Entschuldigt"]`) |
| position | INT NOT NULL DEFAULT 0 | Reihenfolge |
| created_at | TIMESTAMP DEFAULT NOW() | |

- INDEX auf `(list_id, position)`

### `list_rows` — Zeilen einer Liste
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| list_id | INT NOT NULL FK → lists.id ON DELETE CASCADE | |
| label | VARCHAR(255) NULL | Zeilenbeschriftung (z.B. Schuelername) |
| position | INT NOT NULL DEFAULT 0 | Reihenfolge |
| created_at | TIMESTAMP DEFAULT NOW() | |

- INDEX auf `(list_id, position)`

### `list_cells` — Zellenwerte
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| list_id | INT NOT NULL FK → lists.id ON DELETE CASCADE | |
| row_id | INT NOT NULL FK → list_rows.id ON DELETE CASCADE | |
| column_id | INT NOT NULL FK → list_columns.id ON DELETE CASCADE | |
| value | TEXT NULL | Gespeicherter Wert |
| updated_at | TIMESTAMP DEFAULT NOW() ON UPDATE NOW() | |

- UNIQUE KEY auf `(row_id, column_id)` — ein Wert pro Zelle
- INDEX auf `(list_id)`

### `list_shares` — Gezielte Freigaben
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| list_id | INT NOT NULL FK → lists.id ON DELETE CASCADE | |
| user_id | INT NOT NULL FK → users.id ON DELETE CASCADE | |
| can_edit | TINYINT(1) DEFAULT 0 | Darf der Nutzer bearbeiten? |
| created_at | TIMESTAMP DEFAULT NOW() | |

- UNIQUE KEY auf `(list_id, user_id)`

---

## Schritt 2: Model-Klassen

### `src/Models/ListModel.php`
- `findById(int $id): ?array`
- `findByUser(int $userId): array` — Eigene + globale + freigegebene Listen
- `create(array $data): int`
- `update(int $id, array $data): void`
- `delete(int $id): void`
- `hasAccess(int $listId, int $userId): bool` — Lese-Zugriff pruefen
- `canEdit(int $listId, int $userId): bool` — Schreib-Zugriff pruefen (Besitzer, Admin, oder shared mit can_edit)
- `getShares(int $listId): array` — Freigaben einer Liste
- `addShare(int $listId, int $userId, bool $canEdit): void`
- `removeShare(int $listId, int $userId): void`

### `src/Models/ListColumn.php`
- `findByList(int $listId): array` — Alle Spalten, sortiert nach position
- `create(array $data): int`
- `update(int $id, array $data): void`
- `delete(int $id): void` — Spalte + zugehoerige Zellen loeschen
- `reorder(int $listId, array $columnIds): void` — Reihenfolge aktualisieren

### `src/Models/ListRow.php`
- `findByList(int $listId): array` — Alle Zeilen, sortiert nach position
- `create(array $data): int`
- `delete(int $id): void` — Zeile + zugehoerige Zellen loeschen
- `createFromClass(int $listId, int $classId): void` — Zeilen aus Schuelerliste einer Klasse vorbefuellen

### `src/Models/ListCell.php`
- `findByList(int $listId): array` — Alle Zellen einer Liste (fuer Tabellen-Rendering)
- `upsert(int $rowId, int $columnId, int $listId, ?string $value): void` — Wert setzen (INSERT ON DUPLICATE UPDATE)

---

## Schritt 3: Controller

### `src/Controllers/ListController.php`

| Methode | Route | Beschreibung |
|---------|-------|-------------|
| `index()` | GET `/lists` | Alle zugaenglichen Listen anzeigen |
| `createForm()` | GET `/lists/create` | Formular: neue Liste erstellen |
| `create()` | POST `/lists` | Liste erstellen (+ opt. Schueler-Vorbefuellung) |
| `show(string $id)` | GET `/lists/{id}` | Liste anzeigen und bearbeiten (Tabelle) |
| `update(string $id)` | POST `/lists/{id}` | Listen-Metadaten aktualisieren (Titel, Beschreibung, Sichtbarkeit) |
| `delete(string $id)` | POST `/lists/{id}/delete` | Liste loeschen |
| `addColumn(string $id)` | POST `/lists/{id}/column` | Spalte hinzufuegen |
| `deleteColumn(string $colId)` | POST `/lists/column/{colId}/delete` | Spalte loeschen |
| `addRow(string $id)` | POST `/lists/{id}/row` | Zeile hinzufuegen |
| `deleteRow(string $rowId)` | POST `/lists/row/{rowId}/delete` | Zeile loeschen |
| `saveCell()` | POST `/lists/cell` | Einzelne Zelle speichern (AJAX, JSON) |
| `shareForm(string $id)` | GET `/lists/{id}/share` | Freigabe-Formular |
| `share(string $id)` | POST `/lists/{id}/share` | Freigabe hinzufuegen/aendern |
| `removeShare(string $id)` | POST `/lists/{id}/unshare` | Freigabe entfernen |

**Sicherheit:**
- Rollenpruefung: Schueler werden abgewiesen
- `hasAccess()` fuer Lese-Operationen (show)
- `canEdit()` fuer Schreib-Operationen (update, addColumn, deleteColumn, addRow, deleteRow, saveCell)
- Nur Besitzer/Admin: delete, share-Verwaltung

---

## Schritt 4: Views

### `src/Views/lists/index.php` — Listenuebersicht
- Tabelle aller Listen: Titel, Beschreibung, Sichtbarkeit (Badge), Klasse, Ersteller, Aktionen
- Filter: "Meine Listen" / "Alle" Toggle
- Button "Neue Liste"

### `src/Views/lists/create.php` — Neue Liste erstellen
- Titel, Beschreibung
- Sichtbarkeit: Privat / Global / Freigegeben
- Optional: Klasse auswaehlen (Dropdown) → Schuelernamen werden als Zeilen vorbefuellt
- Erste Spalten definieren: Name + Feldtyp (mind. 1 Spalte)

### `src/Views/lists/show.php` — Listenansicht (Tabelle)
- Tabellarische Darstellung mit:
  - Zeilenlabel links (z.B. Schuelername)
  - Spalten mit passenden Input-Feldern je nach Typ
  - Inline-Bearbeitung: Aenderungen per AJAX-Aufruf speichern (onchange/onblur)
- Aktionen ueber der Tabelle:
  - "Spalte hinzufuegen" (Name + Typ als Mini-Formular)
  - "Zeile hinzufuegen" (opt. Label)
  - Spalten-/Zeilen-Loeschen-Buttons
- Listen-Metadaten bearbeiten (Titel, Sichtbarkeit) im Header
- Freigabe-Button (fuer Besitzer)

### `src/Views/lists/share.php` — Freigabe verwalten
- Aktuelle Freigaben als Tabelle: Nutzer, Berechtigung (Lesen/Bearbeiten), Entfernen
- Nutzer-Dropdown zum Hinzufuegen
- Checkbox: "Bearbeiten erlauben"

---

## Schritt 5: Routen

**Datei:** `config/routes.php`

```php
// Listen
$router->get('/lists', [ListController::class, 'index'], [AuthMiddleware::class]);
$router->get('/lists/create', [ListController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/lists', [ListController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/lists/{id}', [ListController::class, 'show'], [AuthMiddleware::class]);
$router->post('/lists/{id}', [ListController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/delete', [ListController::class, 'delete'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/column', [ListController::class, 'addColumn'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/column/{colId}/delete', [ListController::class, 'deleteColumn'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/row', [ListController::class, 'addRow'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/row/{rowId}/delete', [ListController::class, 'deleteRow'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/cell', [ListController::class, 'saveCell'], [AuthMiddleware::class]);
$router->get('/lists/{id}/share', [ListController::class, 'shareForm'], [AuthMiddleware::class]);
$router->post('/lists/{id}/share', [ListController::class, 'share'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/unshare', [ListController::class, 'removeShare'], [AuthMiddleware::class, CsrfMiddleware::class]);
```

Statische Routen (`/lists/create`, `/lists/cell`, `/lists/column/...`, `/lists/row/...`) vor dynamischen (`/lists/{id}`).

---

## Schritt 6: Navigation

**Datei:** `config/navigation.php`

"Listen"-Link fuer admin, schulleitung, sekretariat, lehrer — **nicht** fuer schueler:
```php
['label' => 'Listen', 'url' => '/lists'],
```

---

## Schritt 7: CSS-Styling

**Datei:** `public/css/style.css` (ergaenzen)

- `.list-table` — Tabellenansicht mit festen Spaltenbreiten
- `.list-cell-input` — Inline-Eingabefelder in Tabellenzellen
- `.list-cell--saving` — Visuelles Feedback beim Speichern (kurze Hintergrundfarbe)
- `.list-cell--saved` — Kurze Bestaetigung nach dem Speichern
- `.list-actions-bar` — Aktionsleiste ueber der Tabelle
- `.list-column-header` — Spaltenkoepfe mit Loeschen-Button
- `.list-row-label` — Zeilenbeschriftung (fett, sticky links)
- `.visibility-badge` — Badges fuer Private/Global/Freigegeben
- Responsive: horizontales Scrollen fuer breite Tabellen

---

## Schritt 8: JavaScript

**Datei:** `public/js/app.js` (ergaenzen)

- **Inline-Speicherung:** Bei `change`/`blur` eines Zellen-Inputs: AJAX-POST an `/lists/cell` mit `{row_id, column_id, list_id, value}`. Visuelles Feedback (kurzes Highlight).
- **Spalte hinzufuegen:** Inline-Formular ein-/ausblenden (wie bei Ordner-Erstellung)
- **Zeile hinzufuegen:** Inline-Formular ein-/ausblenden
- **Select-Optionen:** Beim Waehlen von Typ "select" ein Textfeld fuer Optionen einblenden (kommasepariert)
- **Rating-Felder:** Dropdown mit 1-6 rendern

---

## Schritt 9: Seed-Daten

**Datei:** `database/seed.php` (ergaenzen)

- 1 globale Anwesenheitsliste fuer Klasse 5a mit Spalten: "Anwesend" (checkbox), "Bemerkung" (text)
- 1 private Notenliste fuer Lehrer Mueller mit Spalten: "Test 1" (rating), "Test 2" (rating), "Muendlich" (rating)
- Zeilen vorbefuellt mit Schuelernamen aus der jeweiligen Klasse
- Einige Beispielwerte in Zellen

---

## Implementierungsreihenfolge

1. Migration (5 Tabellen)
2. Models (ListModel, ListColumn, ListRow, ListCell)
3. Controller (ListController mit 14 Methoden)
4. Views (index, create, show, share)
5. Routen + Navigation
6. CSS-Styling
7. JavaScript (Inline-Speicherung, Formular-Toggles)
8. Seed-Daten
