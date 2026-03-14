<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Anmelden' ?> - Open-Classbook</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-container" role="main">
        <div class="auth-header">
            <h1>Open-Classbook</h1>
        </div>
        <?= \OpenClassbook\View::flash() ?>
        <?= $content ?>
    </main>

    <footer class="footer" role="contentinfo">
        <p>&copy; <?= date('Y') ?> Open-Classbook</p>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>
