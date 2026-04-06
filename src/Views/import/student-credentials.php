<div class="page-header">
    <h1>Schüler-Zugangsdaten</h1>
</div>

<div class="alert alert-warning" role="alert">
    <strong>Wichtig:</strong> Notieren oder drucken Sie diese Zugangsdaten jetzt! Sie werden nach Verlassen dieser Seite nicht erneut angezeigt. Alle Schüler müssen ihr Passwort beim ersten Login ändern.
</div>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Schüler-Zugangsdaten">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Benutzername</th>
                    <th scope="col">Temporaeres Passwort</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($credentials as $cred): ?>
                <tr>
                    <td><?= htmlspecialchars($cred['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><code><?= htmlspecialchars($cred['username'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><code><?= htmlspecialchars($cred['password'], ENT_QUOTES, 'UTF-8') ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-1 btn-group">
    <button type="button" class="btn" onclick="window.print()">Zugangsdaten drucken</button>
    <a href="/users?role=schueler" class="btn btn-secondary">Zur Benutzerverwaltung</a>
</div>
