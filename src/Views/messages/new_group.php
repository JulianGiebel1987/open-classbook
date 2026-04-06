<div class="page-header">
    <div>
        <a href="/messages" class="btn btn-sm btn-secondary mb-05">Zurück</a>
        <h1>Neue Gruppe erstellen</h1>
    </div>
</div>

<?php
$roleLabels = [
    'admin'        => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat'  => 'Sekretariat',
    'lehrer'       => 'Lehrkraft',
    'schueler'     => 'Schüler:in',
];
?>

<div class="card">
    <form method="post" action="/messages/groups/new" id="createGroupForm">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="group_name">Gruppenname <span aria-hidden="true">*</span></label>
            <input type="text" name="group_name" id="group_name" class="form-control"
                   required maxlength="100" placeholder="z.B. Elternbeirat 4a, Lehrerkonferenz...">
        </div>

        <div class="form-group">
            <label>Mitglieder auswählen <span aria-hidden="true">*</span></label>
            <p class="text-muted" style="margin-bottom: 0.5rem; font-size: 0.875rem;">
                Mindestens eine Person auswählen. Mehrere Personen per Klick auswählen.
            </p>
            <div class="group-member-list" id="memberList">
                <?php foreach ($users as $u): ?>
                    <label class="group-member-item">
                        <input type="checkbox" name="member_ids[]" value="<?= (int) $u['id'] ?>">
                        <span class="group-member-avatar" aria-hidden="true">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($u['username'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="group-member-name">
                            <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>
                            <span class="badge badge-muted"><?= $roleLabels[$u['role']] ?? $u['role'] ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div id="memberSelectCount" class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
                0 Personen ausgewählt
            </div>
            <div id="memberError" class="form-error" style="display:none; color: #c0392b; font-size: 0.875rem; margin-top: 0.25rem;">
                Bitte mindestens eine Person auswählen.
            </div>
        </div>

        <div class="form-group">
            <label for="body">Erste Nachricht <span class="text-muted" style="font-weight:normal;">(optional)</span></label>
            <textarea name="body" id="body" class="form-control" rows="4"
                      maxlength="5000" placeholder="Optionale erste Nachricht an die Gruppe..."></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn" id="createGroupBtn">Gruppe erstellen</button>
            <a href="/messages" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('createGroupForm');
    var memberList = document.getElementById('memberList');
    var countLabel = document.getElementById('memberSelectCount');
    var memberError = document.getElementById('memberError');

    if (!form || !memberList) return;

    function updateMemberCount() {
        var checked = memberList.querySelectorAll('input[type="checkbox"]:checked').length;
        countLabel.textContent = checked + (checked === 1 ? ' Person ausgewählt' : ' Personen ausgewählt');
        if (checked > 0) {
            memberError.style.display = 'none';
        }
    }

    // Direkte Listener auf jede Checkbox (robust gegen Browser-Quirks bei Labels)
    var checkboxes = memberList.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].addEventListener('change', updateMemberCount);
    }

    // Zusaetzlich: Click-Event auf Container als Fallback
    memberList.addEventListener('click', function () {
        setTimeout(updateMemberCount, 0);
    });

    // Validierung beim Absenden
    form.addEventListener('submit', function (e) {
        var checked = memberList.querySelectorAll('input[type="checkbox"]:checked').length;
        if (checked < 1) {
            e.preventDefault();
            memberError.style.display = 'block';
            memberList.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});
</script>
