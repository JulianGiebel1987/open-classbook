# Implementierungsplan: Nachrichten / Chat-System

## Übersicht

Nachrichten-System, das es allen Benutzern ermöglicht, mit anderen Nutzern 1:1 zu chatten. Die Konversationshistorie bleibt erhalten und ist jederzeit einsehbar. Das Feature folgt den bestehenden MVC-Patterns der Anwendung.

---

## Schritt 1: Datenbank-Migration erstellen

**Datei:** `database/migrations/013_create_messages.sql`

Zwei Tabellen:

### `conversations` — Konversation zwischen zwei Nutzern
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| user_one_id | INT NOT NULL FK → users.id | Nutzer mit kleinerer ID |
| user_two_id | INT NOT NULL FK → users.id | Nutzer mit größerer ID |
| last_message_at | DATETIME | Zeitpunkt der letzten Nachricht |
| created_at | DATETIME DEFAULT NOW() | |

- UNIQUE KEY auf `(user_one_id, user_two_id)` — eine Konversation pro Paar
- Konvention: `user_one_id < user_two_id` für Eindeutigkeit

### `messages` — Einzelne Nachrichten
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | INT AUTO_INCREMENT PK | |
| conversation_id | INT NOT NULL FK → conversations.id | |
| sender_id | INT NOT NULL FK → users.id | |
| body | TEXT NOT NULL | Nachrichtentext |
| read_at | DATETIME NULL | Gelesen-Zeitstempel |
| created_at | DATETIME DEFAULT NOW() | |

- INDEX auf `(conversation_id, created_at)` für schnelles Laden der Chat-Historie
- INDEX auf `(sender_id)` und `(read_at)` für Ungelesen-Zähler

---

## Schritt 2: Model-Klassen

### `src/Models/Conversation.php`
Statische Methoden:
- `findOrCreate(int $userA, int $userB): array` — Konversation finden oder anlegen (sortiert IDs automatisch)
- `findByUserId(int $userId): array` — Alle Konversationen eines Nutzers, sortiert nach `last_message_at DESC`, mit Name des Gegenübers und letzter Nachricht (Preview)
- `findById(int $id): ?array` — Einzelne Konversation laden
- `hasAccess(int $conversationId, int $userId): bool` — Prüfen ob Nutzer Teilnehmer ist
- `updateLastMessageAt(int $conversationId): void`

### `src/Models/Message.php`
Statische Methoden:
- `create(int $conversationId, int $senderId, string $body): int` — Nachricht speichern
- `findByConversation(int $conversationId, int $limit = 50, int $offset = 0): array` — Nachrichten einer Konversation (chronologisch)
- `markAsRead(int $conversationId, int $userId): void` — Alle ungelesenen Nachrichten in einer Konversation als gelesen markieren (wo sender_id ≠ userId)
- `countUnread(int $userId): int` — Gesamtzahl ungelesener Nachrichten für Badge in Navigation

---

## Schritt 3: Controller

### `src/Controllers/MessageController.php`

| Methode | Route | Beschreibung |
|---------|-------|-------------|
| `inbox()` | GET `/messages` | Konversationsliste (Inbox) |
| `show(string $id)` | GET `/messages/{id}` | Chat-Ansicht einer Konversation |
| `send(string $id)` | POST `/messages/{id}` | Nachricht senden |
| `newConversation()` | GET `/messages/new` | Neuen Chat starten (Nutzerauswahl) |
| `createConversation()` | POST `/messages/new` | Konversation anlegen und erste Nachricht senden |
| `loadMore(string $id)` | GET `/messages/{id}/older?offset=N` | Ältere Nachrichten nachladen (JSON) |

**Sicherheit in jeder Methode:**
- AuthMiddleware (eingeloggt)
- CsrfMiddleware bei POST-Requests
- `Conversation::hasAccess()` Prüfung bei show/send/loadMore
- Eingabe-Sanitisierung des Nachrichtentexts

---

## Schritt 4: Views

### `src/Views/messages/inbox.php` — Konversationsliste
- Liste aller Konversationen mit: Name des Gegenübers, letzte Nachricht (gekürzt), Zeitstempel
- Ungelesene Konversationen visuell hervorgehoben (fett)
- Button "Neue Nachricht" → `/messages/new`
- Leerer Zustand: "Noch keine Nachrichten"

### `src/Views/messages/show.php` — Chat-Ansicht
- Header mit Name des Chat-Partners
- Nachrichtenverlauf: eigene Nachrichten rechts, fremde links (Chat-Bubbles)
- Zeitstempel je Nachricht
- Eingabefeld + Senden-Button am unteren Rand
- "Ältere Nachrichten laden"-Button oben (AJAX oder Link mit offset)
- Automatisches Scrollen zum neusten Eintrag

### `src/Views/messages/new.php` — Neuen Chat starten
- Dropdown/Suchfeld zur Nutzerauswahl (alle aktiven Nutzer außer sich selbst)
- Textarea für erste Nachricht
- Absenden erstellt Konversation + leitet zum Chat weiter

---

## Schritt 5: Routen registrieren

**Datei:** `config/routes.php`

```php
// Nachrichten
$router->get('/messages', [MessageController::class, 'inbox'], [AuthMiddleware::class]);
$router->get('/messages/new', [MessageController::class, 'newConversation'], [AuthMiddleware::class]);
$router->post('/messages/new', [MessageController::class, 'createConversation'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/messages/{id}', [MessageController::class, 'show'], [AuthMiddleware::class]);
$router->post('/messages/{id}', [MessageController::class, 'send'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/messages/{id}/older', [MessageController::class, 'loadMore'], [AuthMiddleware::class]);
```

Wichtig: `/messages/new` muss **vor** `/messages/{id}` stehen.

---

## Schritt 6: Navigation erweitern

**Datei:** `config/navigation.php`

Für **alle Rollen** den Menüpunkt hinzufügen:
```php
['label' => 'Nachrichten', 'url' => '/messages'],
```

Ungelesen-Badge im Layout anzeigen: `Message::countUnread($_SESSION['user_id'])` im Navigation-Rendering.

---

## Schritt 7: CSS-Styling

**Datei:** `public/css/style.css` (ergänzen)

- `.conversation-list` — Inbox-Einträge mit Hover, Ungelesen-Hervorhebung
- `.chat-container` — Scrollbarer Nachrichtenbereich mit fester Höhe
- `.chat-bubble`, `.chat-bubble--mine`, `.chat-bubble--theirs` — Sprechblasen links/rechts
- `.chat-input` — Eingabebereich am unteren Rand (sticky)
- `.unread-badge` — Rote Badge-Zahl in der Navigation
- Responsive: Auf Mobile volle Breite, Bubbles angepasst

---

## Schritt 8: JavaScript

**Datei:** `public/js/app.js` (ergänzen)

- Auto-Scroll zum letzten Eintrag beim Laden der Chat-Ansicht
- "Ältere laden"-Button: AJAX-Request an `/messages/{id}/older?offset=N`, Nachrichten oben einfügen
- Optional: Textarea mit Enter = Senden, Shift+Enter = Zeilenumbruch
- Optional: Polling alle 10 Sekunden für neue Nachrichten (einfache Lösung ohne WebSocket)

---

## Schritt 9: Seed-Daten erweitern

**Datei:** `database/seed.php` (ergänzen)

- 2-3 Demo-Konversationen zwischen bestehenden Nutzern erstellen
- Je Konversation 3-5 Beispielnachrichten
- Mix aus gelesenen und ungelesenen Nachrichten

---

## Schritt 10: Tests

**Datei:** `tests/MessageTest.php`

- Conversation::findOrCreate erstellt nur eine Konversation pro Paar
- Message::create speichert korrekt
- Message::countUnread zählt nur ungelesene fremde Nachrichten
- Conversation::hasAccess verhindert Zugriff durch Dritte
- Controller: Unautorisierter Zugriff auf fremde Konversationen wird abgelehnt

---

## Implementierungsreihenfolge

1. Migration (Tabellen)
2. Models (Conversation + Message)
3. Controller (MessageController)
4. Views (inbox, show, new)
5. Routen + Navigation
6. CSS-Styling
7. JavaScript (Scroll, Nachladen)
8. Seed-Daten
9. Tests
