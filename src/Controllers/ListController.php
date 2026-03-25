<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\ListModel;
use OpenClassbook\Models\ListColumn;
use OpenClassbook\Models\ListRow;
use OpenClassbook\Models\ListCell;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\User;
use OpenClassbook\Models\Teacher;

class ListController
{
    private const ALLOWED_ROLES = ['admin', 'schulleitung', 'sekretariat', 'lehrer'];
    private const VALID_TYPES = ['text', 'checkbox', 'number', 'date', 'select', 'rating'];

    private function checkRole(): bool
    {
        if (!in_array(App::currentUserRole(), self::ALLOWED_ROLES)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    /**
     * Alle zugaenglichen Listen anzeigen.
     */
    public function index(): void
    {
        if (!$this->checkRole()) return;

        $lists = ListModel::findByUser($_SESSION['user_id']);

        View::render('lists/index', [
            'title' => 'Listen',
            'lists' => $lists,
            'currentUserId' => $_SESSION['user_id'],
        ]);
    }

    /**
     * Formular: neue Liste erstellen.
     */
    public function createForm(): void
    {
        if (!$this->checkRole()) return;

        $role = App::currentUserRole();
        if ($role === 'lehrer') {
            $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            $classes = $teacherId ? Teacher::getClassesForTeacher($teacherId) : [];
        } else {
            $classes = SchoolClass::findAll();
        }

        CsrfMiddleware::generateToken();
        View::render('lists/create', [
            'title' => 'Neue Liste',
            'classes' => $classes,
        ]);
    }

    /**
     * Liste erstellen.
     */
    public function create(): void
    {
        if (!$this->checkRole()) return;

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            App::setFlash('error', 'Titel ist erforderlich.');
            App::redirect('/lists/create');
            return;
        }

        $visibility = $_POST['visibility'] ?? 'private';
        if (!in_array($visibility, ['private', 'global', 'shared'])) {
            $visibility = 'private';
        }

        $classId = !empty($_POST['class_id']) ? (int) $_POST['class_id'] : null;

        try {
            $listId = ListModel::create([
                'title' => mb_substr($title, 0, 255),
                'description' => trim($_POST['description'] ?? '') ?: null,
                'owner_id' => $_SESSION['user_id'],
                'visibility' => $visibility,
                'class_id' => $classId,
            ]);

            // Initiale Spalten erstellen
            $colTitles = $_POST['col_title'] ?? [];
            $colTypes = $_POST['col_type'] ?? [];
            $colOptions = $_POST['col_options'] ?? [];
            foreach ($colTitles as $i => $colTitle) {
                $colTitle = trim($colTitle);
                if ($colTitle === '') continue;
                $type = $colTypes[$i] ?? 'text';
                if (!in_array($type, self::VALID_TYPES)) $type = 'text';

                $options = null;
                if ($type === 'select' && !empty($colOptions[$i])) {
                    $opts = array_map('trim', explode(',', $colOptions[$i]));
                    $opts = array_filter($opts, fn($o) => $o !== '');
                    $options = json_encode(array_values($opts));
                }

                ListColumn::create([
                    'list_id' => $listId,
                    'title' => mb_substr($colTitle, 0, 255),
                    'type' => $type,
                    'options' => $options,
                ]);
            }

            // Schuelerliste vorbefuellen
            if ($classId) {
                ListRow::createFromClass($listId, $classId);
            }

            App::setFlash('success', 'Liste erstellt.');
            App::redirect('/lists/' . $listId);
        } catch (\PDOException $e) {
            App::setFlash('error', 'Fehler beim Erstellen der Liste. Bitte pruefen Sie, ob die Datenbank-Migrationen ausgefuehrt wurden (php database/migrate.php).');
            App::redirect('/lists/create');
        }
    }

    /**
     * Liste anzeigen und bearbeiten.
     */
    public function show(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        $userId = $_SESSION['user_id'];
        $list = ListModel::findById($listId);

        if (!$list || !ListModel::hasAccess($listId, $userId)) {
            App::setFlash('error', 'Liste nicht gefunden oder Zugriff verweigert.');
            App::redirect('/lists');
            return;
        }

        $columns = ListColumn::findByList($listId);
        $rows = ListRow::findByList($listId);
        $cells = ListCell::findByList($listId);
        $canEdit = ListModel::canEdit($listId, $userId, App::currentUserRole());

        CsrfMiddleware::generateToken();
        View::render('lists/show', [
            'title' => $list['title'],
            'list' => $list,
            'columns' => $columns,
            'rows' => $rows,
            'cells' => $cells,
            'canEdit' => $canEdit,
            'isOwner' => (int) $list['owner_id'] === $userId || App::currentUserRole() === 'admin',
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Listen-Metadaten aktualisieren.
     */
    public function update(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        $userId = $_SESSION['user_id'];

        $list = ListModel::findById($listId);
        if (!$list || ((int) $list['owner_id'] !== $userId && App::currentUserRole() !== 'admin')) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists');
            return;
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            App::setFlash('error', 'Titel ist erforderlich.');
            App::redirect('/lists/' . $listId);
            return;
        }

        $visibility = $_POST['visibility'] ?? $list['visibility'];
        if (!in_array($visibility, ['private', 'global', 'shared'])) {
            $visibility = 'private';
        }

        try {
            ListModel::update($listId, [
                'title' => mb_substr($title, 0, 255),
                'description' => trim($_POST['description'] ?? '') ?: null,
                'visibility' => $visibility,
            ]);
            App::setFlash('success', 'Liste aktualisiert.');
        } catch (\PDOException $e) {
            App::setFlash('error', 'Fehler beim Aktualisieren der Liste.');
        }
        App::redirect('/lists/' . $listId);
    }

    /**
     * Liste loeschen.
     */
    public function delete(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        $list = ListModel::findById($listId);
        if (!$list || ((int) $list['owner_id'] !== $_SESSION['user_id'] && App::currentUserRole() !== 'admin')) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists');
            return;
        }

        ListModel::delete($listId);
        App::setFlash('success', 'Liste geloescht.');
        App::redirect('/lists');
    }

    /**
     * Spalte hinzufuegen.
     */
    public function addColumn(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        if (!ListModel::canEdit($listId, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists/' . $listId);
            return;
        }

        $title = trim($_POST['col_title'] ?? '');
        if ($title === '') {
            App::setFlash('error', 'Spaltenname ist erforderlich.');
            App::redirect('/lists/' . $listId);
            return;
        }

        $type = $_POST['col_type'] ?? 'text';
        if (!in_array($type, self::VALID_TYPES)) $type = 'text';

        $options = null;
        if ($type === 'select' && !empty($_POST['col_options'])) {
            $opts = array_map('trim', explode(',', $_POST['col_options']));
            $opts = array_filter($opts, fn($o) => $o !== '');
            $options = json_encode(array_values($opts));
        }

        try {
            ListColumn::create([
                'list_id' => $listId,
                'title' => mb_substr($title, 0, 255),
                'type' => $type,
                'options' => $options,
            ]);
            App::setFlash('success', 'Spalte hinzugefuegt.');
        } catch (\PDOException $e) {
            App::setFlash('error', 'Fehler beim Hinzufuegen der Spalte.');
        }
        App::redirect('/lists/' . $listId);
    }

    /**
     * Spalte loeschen.
     */
    public function deleteColumn(string $colId): void
    {
        if (!$this->checkRole()) return;

        $column = ListColumn::findById((int) $colId);
        if (!$column) {
            App::setFlash('error', 'Spalte nicht gefunden.');
            App::redirect('/lists');
            return;
        }

        $listId = (int) $column['list_id'];
        if (!ListModel::canEdit($listId, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists/' . $listId);
            return;
        }

        ListColumn::delete((int) $colId);
        App::setFlash('success', 'Spalte geloescht.');
        App::redirect('/lists/' . $listId);
    }

    /**
     * Zeile hinzufuegen.
     */
    public function addRow(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        if (!ListModel::canEdit($listId, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists/' . $listId);
            return;
        }

        try {
            ListRow::create([
                'list_id' => $listId,
                'label' => trim($_POST['row_label'] ?? '') ?: null,
            ]);
            App::setFlash('success', 'Zeile hinzugefuegt.');
        } catch (\PDOException $e) {
            App::setFlash('error', 'Fehler beim Hinzufuegen der Zeile.');
        }
        App::redirect('/lists/' . $listId);
    }

    /**
     * Zeile loeschen.
     */
    public function deleteRow(string $rowId): void
    {
        if (!$this->checkRole()) return;

        $row = ListRow::findById((int) $rowId);
        if (!$row) {
            App::setFlash('error', 'Zeile nicht gefunden.');
            App::redirect('/lists');
            return;
        }

        $listId = (int) $row['list_id'];
        if (!ListModel::canEdit($listId, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists/' . $listId);
            return;
        }

        ListRow::delete((int) $rowId);
        App::setFlash('success', 'Zeile geloescht.');
        App::redirect('/lists/' . $listId);
    }

    /**
     * Einzelne Zelle speichern (AJAX).
     */
    public function saveCell(): void
    {
        if (!$this->checkRole()) return;

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        // CSRF-Pruefung fuer AJAX-Anfragen
        $csrfToken = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($csrfToken) || !hash_equals($sessionToken, $csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => 'Ungueltige Anfrage']);
            return;
        }

        $listId = (int) ($input['list_id'] ?? 0);
        $rowId = (int) ($input['row_id'] ?? 0);
        $columnId = (int) ($input['column_id'] ?? 0);
        $value = $input['value'] ?? null;

        if (!$listId || !$rowId || !$columnId) {
            http_response_code(400);
            echo json_encode(['error' => 'Fehlende Parameter']);
            return;
        }

        if (!ListModel::canEdit($listId, $_SESSION['user_id'], App::currentUserRole())) {
            http_response_code(403);
            echo json_encode(['error' => 'Zugriff verweigert']);
            return;
        }

        try {
            ListCell::upsert($rowId, $columnId, $listId, $value);
            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Fehler beim Speichern der Zelle.']);
        }
    }

    /**
     * Freigabe-Formular.
     */
    public function shareForm(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        $list = ListModel::findById($listId);
        if (!$list || ((int) $list['owner_id'] !== $_SESSION['user_id'] && App::currentUserRole() !== 'admin')) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists');
            return;
        }

        $shares = ListModel::getShares($listId);
        $sharedUserIds = array_column($shares, 'user_id');
        $allUsers = User::findAll(['active' => 1]);
        $availableUsers = array_filter($allUsers, function ($u) use ($sharedUserIds) {
            return (int) $u['id'] !== $_SESSION['user_id']
                && !in_array((int) $u['id'], $sharedUserIds)
                && $u['role'] !== 'schueler';
        });

        CsrfMiddleware::generateToken();
        View::render('lists/share', [
            'title' => 'Freigabe: ' . $list['title'],
            'list' => $list,
            'shares' => $shares,
            'availableUsers' => array_values($availableUsers),
        ]);
    }

    /**
     * Freigabe hinzufuegen.
     */
    public function share(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        $list = ListModel::findById($listId);
        if (!$list || ((int) $list['owner_id'] !== $_SESSION['user_id'] && App::currentUserRole() !== 'admin')) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists');
            return;
        }

        $shareUserId = (int) ($_POST['user_id'] ?? 0);
        if ($shareUserId === 0 || $shareUserId === $_SESSION['user_id']) {
            App::setFlash('error', 'Ungueltiger Nutzer.');
            App::redirect('/lists/' . $listId . '/share');
            return;
        }

        $canEdit = (bool) ($_POST['can_edit'] ?? 0);
        ListModel::addShare($listId, $shareUserId, $canEdit);

        // Sichtbarkeit auf shared setzen falls noch private
        if ($list['visibility'] === 'private') {
            ListModel::update($listId, [
                'title' => $list['title'],
                'description' => $list['description'],
                'visibility' => 'shared',
            ]);
        }

        App::setFlash('success', 'Freigabe hinzugefuegt.');
        App::redirect('/lists/' . $listId . '/share');
    }

    /**
     * Freigabe entfernen.
     */
    public function removeShare(string $id): void
    {
        if (!$this->checkRole()) return;

        $listId = (int) $id;
        $list = ListModel::findById($listId);
        if (!$list || ((int) $list['owner_id'] !== $_SESSION['user_id'] && App::currentUserRole() !== 'admin')) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/lists');
            return;
        }

        $shareUserId = (int) ($_POST['user_id'] ?? 0);
        ListModel::removeShare($listId, $shareUserId);

        App::setFlash('success', 'Freigabe entfernt.');
        App::redirect('/lists/' . $listId . '/share');
    }
}
