<div class="page-header">
    <h1>Einladungslinks</h1>
</div>

<div class="alert alert-warning" role="alert">
    <strong>Wichtig:</strong> Der E-Mail-Versand ist derzeit deaktiviert oder ist
    fehlgeschlagen. Übermitteln Sie den folgenden Link den betroffenen Personen auf
    sicherem Weg. Über den Link legen sie ihr Passwort selbst fest. Der Link wird
    nach Verlassen dieser Seite nicht erneut angezeigt und ist zeitlich begrenzt gültig.
</div>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Einladungslinks">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">E-Mail (Anmeldename)</th>
                    <th scope="col">Einladungslink</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td><?= htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><code><?= htmlspecialchars($link['email'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><code><?= htmlspecialchars($link['link'], ENT_QUOTES, 'UTF-8') ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-1 btn-group">
    <button type="button" class="btn" data-print>Einladungslinks drucken</button>
    <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">Zurück</a>
</div>
