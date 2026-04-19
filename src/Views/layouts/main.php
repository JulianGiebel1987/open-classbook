<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Open-Classbook' ?></title>
    <?php if (!empty($_SESSION['csrf_token'])): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Zum Inhalt springen</a>

    <?php if (\OpenClassbook\App::isLoggedIn()): ?>
    <nav class="navbar" role="navigation" aria-label="Hauptnavigation">
        <div class="navbar-brand">
            <a href="/dashboard">Open-Classbook</a>
            <button class="navbar-toggle" aria-label="Navigation umschalten" aria-expanded="false" aria-controls="navbarMenu">&#9776;</button>
        </div>
        <ul class="navbar-menu" id="navbarMenu" role="menubar">
            <?php
            $role = \OpenClassbook\App::currentUserRole();
            $navConfig = require __DIR__ . '/../../../config/navigation.php';
            $navAll = $navConfig[$role] ?? [];

            // Filter navigation items based on module settings
            $nav = array_filter($navAll, function (array $item) use ($role): bool {
                // Global module check (admin bypasses global disable)
                if (isset($item['module']) && $role !== 'admin') {
                    if (!\OpenClassbook\Services\ModuleSettings::isModuleEnabled($item['module'])) {
                        return false;
                    }
                }
                // Role-specific module check
                if (isset($item['role_module'])) {
                    if (!\OpenClassbook\Services\ModuleSettings::isRoleModuleAccessible($item['role_module'], $role)) {
                        return false;
                    }
                }
                return true;
            });
            ?>
            <?php
            $unreadMessages = 0;
            if ($role && isset($_SESSION['user_id'])) {
                $unreadMessages = \OpenClassbook\Models\Message::countUnread($_SESSION['user_id']);
            }
            ?>
            <?php
            $navIcons = [
                'dashboard'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
                'users'             => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>',
                'classes'           => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="7" r="3"/><path d="M3 20c0-3 2.7-5.5 6-5.5s6 2.5 6 5.5"/><circle cx="18" cy="7" r="2.5"/><path d="M21 20c0-2.5-1.8-4.5-4-5"/></svg>',
                'timetable'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'substitution'      => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 16H3v-4"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M17 8h4v4"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>',
                'absences-students' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M3 20c0-4 2.7-7 6-7"/><line x1="17" y1="13" x2="21" y2="17"/><line x1="21" y1="13" x2="17" y2="17"/></svg>',
                'absences-teachers' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M3 20c0-4 2.7-7 6-7"/><line x1="15" y1="15" x2="22" y2="15"/></svg>',
                'messages'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>',
                'lists'             => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="4" cy="6" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1" fill="currentColor" stroke="none"/><line x1="9" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="9" y1="18" x2="21" y2="18"/></svg>',
                'files'             => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>',
                'templates'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
                'settings'          => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06-.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                'classbook'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
                'sick-note'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
                'my-absences'       => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            ];
            ?>
            <?php foreach ($nav as $item): ?>
                <li role="none">
                    <a href="<?= $item['url'] ?>" role="menuitem">
                        <?php if (!empty($item['icon']) && isset($navIcons[$item['icon']])): ?>
                            <span class="nav-icon"><?= $navIcons[$item['icon']] ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($item['url'] === '/messages' && $unreadMessages > 0): ?>
                            <span class="unread-badge"><?= $unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li class="navbar-user" role="none">
                <span aria-label="Angemeldet als"><?= htmlspecialchars($_SESSION['user']['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <a href="/logout" class="btn btn-sm" role="menuitem">Abmelden</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <main id="main-content" class="container" role="main">
        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <li>
                        <?php if (isset($crumb['url']) && $i < count($breadcrumbs) - 1): ?>
                            <a href="<?= $crumb['url'] ?>"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <span aria-current="page"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <?= \OpenClassbook\View::flash() ?>
        <?= $content ?>
    </main>

    <footer class="footer" role="contentinfo">
        <p>&copy; <?= date('Y') ?> Open-Classbook</p>
    </footer>

    <!-- Bestaetigungsdialog -->
    <div class="modal-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal">
            <h3 id="modalTitle">Bestaetigung</h3>
            <p class="modal-message">Sind Sie sicher?</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary modal-cancel">Abbrechen</button>
                <button type="button" class="btn btn-danger modal-confirm">Bestaetigen</button>
            </div>
        </div>
    </div>

    <!-- Ladeanimation -->
    <div class="loading-overlay" id="loadingOverlay" role="status" aria-label="Wird geladen">
        <div class="spinner"></div>
        <span class="sr-only">Daten werden verarbeitet...</span>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>
