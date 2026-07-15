<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\ContentTemplate;
use OpenClassbook\Services\Logger;

/**
 * Verwaltung wiederverwendbarer Unterrichtsinhalt-Vorlagen fuers Klassenbuch.
 */
class ContentTemplateController
{
    /** Rollen mit Zugriff aufs Feature (analog zu den Klassenbuch-Schreibrechten). */
    private const ACCESS_ROLES = ['admin', 'schulleitung', 'sekretariat', 'lehrer'];

    /** Rollen, die geteilte (schulweite) Vorlagen anlegen/verwalten duerfen. */
    private const SHARED_MANAGER_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    private function requireAccess(): bool
    {
        if (!in_array(App::currentUserRole(), self::ACCESS_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    private function canManageShared(): bool
    {
        return in_array(App::currentUserRole(), self::SHARED_MANAGER_ROLES, true);
    }

    public function index(): void
    {
        if (!$this->requireAccess()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $role   = App::currentUserRole();

        View::render('content-templates/index', [
            'title'     => 'Unterrichtsinhalte',
            'templates' => ContentTemplate::findForUser($userId, $role),
            'userId'    => $userId,
            'role'      => $role,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Unterrichtsinhalte'],
            ]),
        ]);
    }

    public function createForm(): void
    {
        if (!$this->requireAccess()) {
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('content-templates/create', [
            'title'          => 'Neue Vorlage',
            'categories'     => ContentTemplate::getCategories((int) $_SESSION['user_id'], App::currentUserRole()),
            'canManageShared' => $this->canManageShared(),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Unterrichtsinhalte', 'url' => '/content-templates'],
                ['label' => 'Neue Vorlage'],
            ]),
        ]);
    }

    public function create(): void
    {
        if (!$this->requireAccess()) {
            return;
        }

        $data = $this->extractData();
        if ($data === null) {
            App::redirect('/content-templates/create');
            return;
        }

        $id = ContentTemplate::create($data);

        Logger::audit(
            'create_content_template',
            $_SESSION['user_id'] ?? null,
            'ContentTemplate',
            $id,
            'Unterrichtsinhalt-Vorlage angelegt: ' . $data['topic']
        );

        App::setFlash('success', 'Vorlage gespeichert.');
        App::redirect('/content-templates');
    }

    public function editForm(string $id): void
    {
        if (!$this->requireAccess()) {
            return;
        }

        $template = ContentTemplate::findById((int) $id);
        if (!$template || !ContentTemplate::canManage($template, (int) $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Vorlage nicht gefunden oder keine Berechtigung.');
            App::redirect('/content-templates');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('content-templates/edit', [
            'title'          => 'Vorlage bearbeiten',
            'template'       => $template,
            'categories'     => ContentTemplate::getCategories((int) $_SESSION['user_id'], App::currentUserRole()),
            'canManageShared' => $this->canManageShared(),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Unterrichtsinhalte', 'url' => '/content-templates'],
                ['label' => 'Bearbeiten'],
            ]),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->requireAccess()) {
            return;
        }

        $template = ContentTemplate::findById((int) $id);
        if (!$template || !ContentTemplate::canManage($template, (int) $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Vorlage nicht gefunden oder keine Berechtigung.');
            App::redirect('/content-templates');
            return;
        }

        $data = $this->extractData($template);
        if ($data === null) {
            App::redirect('/content-templates/' . (int) $id . '/edit');
            return;
        }

        ContentTemplate::update((int) $id, $data);

        Logger::audit(
            'update_content_template',
            $_SESSION['user_id'] ?? null,
            'ContentTemplate',
            (int) $id,
            'Unterrichtsinhalt-Vorlage aktualisiert: ' . $data['topic']
        );

        App::setFlash('success', 'Vorlage aktualisiert.');
        App::redirect('/content-templates');
    }

    public function delete(string $id): void
    {
        if (!$this->requireAccess()) {
            return;
        }

        $template = ContentTemplate::findById((int) $id);
        if (!$template || !ContentTemplate::canManage($template, (int) $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Vorlage nicht gefunden oder keine Berechtigung.');
            App::redirect('/content-templates');
            return;
        }

        ContentTemplate::delete((int) $id);

        Logger::audit(
            'delete_content_template',
            $_SESSION['user_id'] ?? null,
            'ContentTemplate',
            (int) $id,
            'Unterrichtsinhalt-Vorlage geloescht: ' . $template['topic']
        );

        App::setFlash('success', 'Vorlage gelöscht.');
        App::redirect('/content-templates');
    }

    /**
     * Validiert die Formulardaten. Gibt null zurueck (mit Flash), wenn ungueltig.
     *
     * @param array|null $existing Bei Update: bestehender Datensatz (fuer Eigentuemer-Erhalt).
     */
    private function extractData(?array $existing = null): ?array
    {
        $topic    = trim($_POST['topic'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $notes    = trim($_POST['notes'] ?? '');

        if ($topic === '') {
            App::setFlash('error', 'Thema ist erforderlich.');
            return null;
        }
        if (mb_strlen($topic) > 500) {
            App::setFlash('error', 'Thema darf höchstens 500 Zeichen enthalten.');
            return null;
        }
        if (mb_strlen($category) > 100) {
            App::setFlash('error', 'Kategorie darf höchstens 100 Zeichen enthalten.');
            return null;
        }

        // Sichtbarkeit bestimmen. "Geteilt" (owner NULL) duerfen nur Verwaltungsrollen waehlen.
        $wantShared = $this->canManageShared() && ($_POST['visibility'] ?? 'personal') === 'shared';

        if ($wantShared) {
            $ownerUserId = null;
        } elseif ($existing !== null && $existing['owner_user_id'] !== null) {
            // Bestehende persoenliche Vorlage bleibt persoenlich: urspruenglichen Eigentuemer
            // erhalten (verhindert, dass ein Admin eine fremde Vorlage ungewollt uebernimmt).
            $ownerUserId = (int) $existing['owner_user_id'];
        } else {
            // Neue persoenliche Vorlage oder Umwandlung einer geteilten in eine persoenliche.
            $ownerUserId = (int) $_SESSION['user_id'];
        }

        return [
            'owner_user_id' => $ownerUserId,
            'category'      => $category !== '' ? $category : null,
            'topic'         => $topic,
            'notes'         => $notes !== '' ? $notes : null,
        ];
    }
}
