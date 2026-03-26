<?php $role = \OpenClassbook\App::currentUserRole(); ?>

<div class="page-header">
    <h1>Schuelerbemerkungen: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <?php if ($role === 'admin' || $role === 'lehrer'): ?>
            <a href="/classbook/<?= $class['id'] ?>/remarks/create" class="btn">Neue Bemerkung</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Filter</h2>
    </div>
    <form method="get" action="/classbook/<?= $class['id'] ?>/remarks" class="filter-form">
        <div class="form-group">
            <label for="student_id">Schueler/in</label>
            <select name="student_id" id="student_id" class="form-control">
                <option value="">Alle</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($filters['student_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="date_from">Von</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="date_to">Bis</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<?php if (empty($remarks)): ?>
<div class="card mt-1">
    <p class="text-muted text-center">Keine Bemerkungen gefunden.</p>
</div>
<?php else: ?>
<div class="card mt-1">
    <div class="table-responsive">
        <table aria-label="Schuelerbemerkungen">
            <thead>
                <tr>
                    <th scope="col" style="white-space:nowrap">Datum</th>
                    <th scope="col">Schueler/in</th>
                    <th scope="col">Lehrkraft</th>
                    <th scope="col">Bemerkung</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($remarks as $r): ?>
                <tr>
                    <td style="white-space:nowrap"><?= date('d.m.Y', strtotime($r['remark_date'])) ?></td>
                    <td style="white-space:nowrap"><?= htmlspecialchars($r['student_lastname'] . ', ' . $r['student_firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="white-space:nowrap"><?= htmlspecialchars($r['abbreviation'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['remark'], ENT_QUOTES, 'UTF-8')) ?></td>
                    <td style="text-align:right">
                        <?php if (\OpenClassbook\Models\StudentRemark::canDelete($r, $_SESSION['user_id'], $role)): ?>
                        <form method="post" action="/classbook/<?= $class['id'] ?>/remarks/<?= $r['id'] ?>/delete"
                              onsubmit="return confirm('Bemerkung wirklich loeschen?')">
                            <?= \OpenClassbook\View::csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-danger">Loeschen</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="mt-1">
    <a href="/classbook/<?= $class['id'] ?>" class="btn btn-secondary">Zurueck zum Klassenbuch</a>
</div>
