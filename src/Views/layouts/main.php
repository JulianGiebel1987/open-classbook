<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Open-Classbook' ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php if (\OpenClassbook\App::isLoggedIn()): ?>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/dashboard">Open-Classbook</a>
            <button class="navbar-toggle" aria-label="Navigation umschalten">&#9776;</button>
        </div>
        <ul class="navbar-menu" id="navbarMenu">
            <?php
            $role = \OpenClassbook\App::currentUserRole();
            $navConfig = require __DIR__ . '/../../../config/navigation.php';
            $nav = $navConfig[$role] ?? [];
            ?>
            <?php foreach ($nav as $item): ?>
                <li><a href="<?= $item['url'] ?>"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php endforeach; ?>
            <li class="navbar-user">
                <span><?= htmlspecialchars($_SESSION['user']['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <a href="/logout" class="btn btn-sm">Abmelden</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <main class="container">
        <?= \OpenClassbook\View::flash() ?>
        <?= $content ?>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Open-Classbook</p>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>
