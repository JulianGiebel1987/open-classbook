<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Open-Classbook' ?></title>
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
            $nav = $navConfig[$role] ?? [];
            ?>
            <?php
            $unreadMessages = 0;
            if ($role && isset($_SESSION['user_id'])) {
                $unreadMessages = \OpenClassbook\Models\Message::countUnread($_SESSION['user_id']);
            }
            ?>
            <?php foreach ($nav as $item): ?>
                <li role="none">
                    <a href="<?= $item['url'] ?>" role="menuitem">
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
