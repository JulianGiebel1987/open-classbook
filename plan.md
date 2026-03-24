# Implementierungsplan: Dateiverwaltung

## Uebersicht

Dateibereich fuer Lehrer, Sekretariat, Schulleitung und Admin. Schueler haben **keinen** Zugriff. Jeder berechtigte Nutzer hat einen **privaten Bereich** und Zugang zu einem **gemeinschaftlichen Bereich**. Unterordner koennen erstellt werden. Max. 15 MB pro Datei, max. 100 MB Speicher pro Nutzer.

---

## Schritt 1: Datenbank-Migration

**Datei:** `database/migrations/014_create_files.sql`

### `folders` — Ordnerstruktur
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| name | VARCHAR(255) NOT NULL | Ordnername |
| parent_id | INT NULL FK → folders.id | Uebergeordneter Ordner (NULL = Root) |
| owner_id | INT NULL FK → users.id | Besitzer (NULL = gemeinschaftlich) |
| is_shared | TINYINT(1) DEFAULT 0 | Gemeinschaftlicher Ordner? |
| created_by | INT NOT NULL FK → users.id | Ersteller |
| created_at | TIMESTAMP DEFAULT NOW() | |

- INDEX auf `(parent_id)`, `(owner_id)`, `(is_shared)`
- UNIQUE KEY auf `(name, parent_id, owner_id)` — keine doppelten Ordnernamen auf gleicher Ebene

### `files` — Hochgeladene Dateien
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| folder_id | INT NULL FK → folders.id | Ordner (NULL = Root-Ebene) |
| owner_id | INT NOT NULL FK → users.id | Besitzer/Hochlader |
| is_shared | TINYINT(1) DEFAULT 0 | Im gemeinschaftlichen Bereich? |
| original_name | VARCHAR(255) NOT NULL | Originaler Dateiname |
| stored_name | VARCHAR(255) NOT NULL | Gespeicherter Dateiname (unique) |
| mime_type | VARCHAR(100) NOT NULL | MIME-Typ |
| file_size | INT NOT NULL | Groesse in Bytes |
| created_at | TIMESTAMP DEFAULT NOW() | |

- INDEX auf `(folder_id)`, `(owner_id)`, `(is_shared)`

---

## Schritt 2: Storage-Verzeichnis

**Verzeichnis:** `storage/files/`

Dateien werden mit einem eindeutigen Dateinamen (`uniqid` + Original-Extension) gespeichert. Keine Unterordner im Dateisystem — die Ordnerstruktur existiert nur in der Datenbank.

```
storage/files/
├── .gitkeep
├── 66a1b2c3d4e5f_bericht.pdf
├── 66a1b2c3d4e60_foto.jpg
└── ...
```

---

## Schritt 3: Model-Klassen

### `src/Models/Folder.php`
- `findById(int $id): ?array`
- `findByParent(?int $parentId, int $ownerId, bool $shared): array` — Ordner in einem Verzeichnis
- `create(array $data): int` — Neuen Ordner anlegen
- `delete(int $id): void` — Ordner und Inhalt loeschen (rekursiv)
- `getPath(int $id): array` — Breadcrumb-Pfad (rekursiv nach oben)
- `hasAccess(int $folderId, int $userId): bool` — Darf der Nutzer auf diesen Ordner zugreifen?

### `src/Models/FileEntry.php`
- `findById(int $id): ?array`
- `findByFolder(?int $folderId, int $ownerId, bool $shared): array` — Dateien in einem Ordner
- `create(array $data): int` — Datei-Metadaten speichern
- `delete(int $id): void` — Datei-Eintrag + physische Datei loeschen
- `getTotalSizeByUser(int $userId): int` — Gesamtspeicher eines Nutzers (fuer Quota-Pruefung)
- `hasAccess(int $fileId, int $userId): bool` — Zugriffspruefung (eigene oder shared)

---

## Schritt 4: Controller

### `src/Controllers/FileController.php`

| Methode | Route | Beschreibung |
|---------|-------|-------------|
| `index()` | GET `/files` | Uebersicht: "Meine Dateien" + "Gemeinschaftlich" |
| `browse(string $folderId)` | GET `/files/folder/{folderId}` | Ordnerinhalt anzeigen |
| `privateBrowse()` | GET `/files/private` | Root des privaten Bereichs |
| `sharedBrowse()` | GET `/files/shared` | Root des gemeinschaftlichen Bereichs |
| `upload()` | POST `/files/upload` | Datei hochladen |
| `download(string $id)` | GET `/files/{id}/download` | Datei herunterladen |
| `createFolder()` | POST `/files/folder` | Unterordner erstellen |
| `deleteFile(string $id)` | POST `/files/{id}/delete` | Datei loeschen |
| `deleteFolder(string $id)` | POST `/files/folder/{id}/delete` | Ordner loeschen |

**Sicherheit in jeder Methode:**
- AuthMiddleware + Rollenpruefung (kein `schueler`)
- CsrfMiddleware bei POST-Requests
- Zugriffspruefung: Private Dateien nur fuer Besitzer, gemeinschaftliche fuer alle Berechtigten
- Dateigroesse: Max. 15 MB pro Datei (serverseitige Pruefung)
- Speicher-Quota: Max. 100 MB pro Nutzer (vor Upload pruefen)
- Dateiname-Sanitisierung (kein Path Traversal)
- MIME-Typ-Validierung

---

## Schritt 5: Views

### `src/Views/files/index.php` — Uebersichtsseite
- Zwei Kacheln: "Meine Dateien" und "Gemeinschaftliche Dateien"
- Speicherverbrauch-Anzeige (z.B. "42 MB / 100 MB")

### `src/Views/files/browse.php` — Ordneransicht
- Breadcrumb-Navigation (Ordnerpfad)
- Ordner-Liste (mit Ordner-Icon, anklickbar)
- Datei-Liste (Tabelle: Name, Groesse, Hochgeladen am, Aktionen)
- "Datei hochladen"-Button mit Formular (Datei-Input, max 15 MB Hinweis)
- "Neuer Ordner"-Button mit Formular (Ordnername eingeben)
- "Loeschen"-Aktionen fuer Dateien und Ordner (mit Bestaetigungsdialog)
- Leerer Zustand: "Dieser Ordner ist leer"

---

## Schritt 6: Routen

**Datei:** `config/routes.php`

```php
// Dateiverwaltung
$router->get('/files', [FileController::class, 'index'], [AuthMiddleware::class]);
$router->get('/files/private', [FileController::class, 'privateBrowse'], [AuthMiddleware::class]);
$router->get('/files/shared', [FileController::class, 'sharedBrowse'], [AuthMiddleware::class]);
$router->get('/files/folder/{folderId}', [FileController::class, 'browse'], [AuthMiddleware::class]);
$router->post('/files/upload', [FileController::class, 'upload'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/files/folder', [FileController::class, 'createFolder'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/files/{id}/download', [FileController::class, 'download'], [AuthMiddleware::class]);
$router->post('/files/{id}/delete', [FileController::class, 'deleteFile'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/files/folder/{id}/delete', [FileController::class, 'deleteFolder'], [AuthMiddleware::class, CsrfMiddleware::class]);
```

Reihenfolge: Statische Routen (`/files/private`, `/files/shared`, `/files/upload`, `/files/folder`) vor dynamischen (`/files/{id}/...`, `/files/folder/{folderId}`).

---

## Schritt 7: Navigation

**Datei:** `config/navigation.php`

"Dateien"-Link nur fuer admin, schulleitung, sekretariat, lehrer:
```php
['label' => 'Dateien', 'url' => '/files'],
```

**Nicht** fuer `schueler`.

---

## Schritt 8: CSS-Styling

**Datei:** `public/css/style.css` (ergaenzen)

- `.file-overview` — Kacheln fuer Privat/Gemeinschaftlich
- `.folder-item` — Ordner-Eintrag mit Icon
- `.file-table` — Datei-Tabelle
- `.storage-bar` — Speicherverbrauch-Balken (Fortschrittsanzeige)
- `.upload-form` — Upload-Bereich mit Drag-and-Drop-Styling
- Responsive: Mobile-optimierte Datei-Liste

---

## Schritt 9: JavaScript

**Datei:** `public/js/app.js` (ergaenzen)

- Upload-Formular: Dateigroesse clientseitig pruefen (max 15 MB) vor dem Absenden
- Ordner-Erstellung: Inline-Formular ein-/ausblenden
- Dateigroesse-Formatierung (KB, MB)

---

## Schritt 10: Seed-Daten

**Datei:** `database/seed.php` (ergaenzen)

- Root-Ordner fuer gemeinschaftlichen Bereich anlegen
- 2-3 Beispielordner (z.B. "Lehrplaene", "Formulare")
- Keine echten Dateien im Seed (nur Ordnerstruktur)

---

## Schritt 11: Sicherheitsmassnahmen

- **Rollenpruefung:** Schueler werden in jeder Controller-Methode mit Redirect abgewiesen
- **Dateigroesse:** `$_FILES['file']['size'] <= 15 * 1024 * 1024` (serverseitig)
- **Quota:** `FileEntry::getTotalSizeByUser()` vor Upload pruefen
- **Dateiname:** `basename()` + `preg_replace` zum Entfernen unsicherer Zeichen
- **MIME-Typ:** `finfo_file()` zur echten MIME-Typ-Erkennung (nicht nur Extension)
- **Path Traversal:** Nur gespeicherte Dateinamen verwenden, nie User-Input als Pfad
- **Zugriffskontrolle:** Private Dateien/Ordner nur fuer den Besitzer, gemeinschaftliche fuer alle berechtigten Rollen

---

## Implementierungsreihenfolge

1. Migration (Tabellen)
2. Storage-Verzeichnis anlegen
3. Models (Folder + FileEntry)
4. Controller (FileController)
5. Views (index, browse)
6. Routen + Navigation
7. CSS-Styling
8. JavaScript
9. Seed-Daten
