<div class="page-header">
    <h1><?= $setting ? 'Stundenplan bearbeiten' : 'Neuer Stundenplan' ?></h1>
</div>

<div class="card">
    <form method="post" action="/timetable/settings">
        <?= \OpenClassbook\View::csrfField() ?>
        <?php if ($setting): ?>
            <input type="hidden" name="id" value="<?= (int) $setting['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="school_year">Schuljahr <span class="required">*</span></label>
            <input type="text" id="school_year" name="school_year" class="form-control"
                   value="<?= htmlspecialchars($setting['school_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="z.B. 2025/2026" pattern="\d{4}/\d{4}" required>
            <small class="form-hint">Format: JJJJ/JJJJ</small>
        </div>

        <div class="form-group">
            <label>Einheitsdauer <span class="required">*</span></label>
            <div class="radio-group">
                <?php $currentDuration = (int) ($setting['unit_duration'] ?? 45); ?>
                <label class="radio-label">
                    <input type="radio" name="unit_duration" value="30" <?= $currentDuration === 30 ? 'checked' : '' ?>>
                    30 Minuten
                </label>
                <label class="radio-label">
                    <input type="radio" name="unit_duration" value="45" <?= $currentDuration === 45 ? 'checked' : '' ?>>
                    45 Minuten
                </label>
                <label class="radio-label">
                    <input type="radio" name="unit_duration" value="60" <?= $currentDuration === 60 ? 'checked' : '' ?>>
                    60 Minuten
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="units_per_day">Einheiten pro Tag <span class="required">*</span></label>
            <input type="number" id="units_per_day" name="units_per_day" class="form-control"
                   value="<?= (int) ($setting['units_per_day'] ?? 8) ?>"
                   min="1" max="15" required>
        </div>

        <div class="form-group">
            <label for="day_start_time">Unterrichtsbeginn <span class="required">*</span></label>
            <input type="time" id="day_start_time" name="day_start_time" class="form-control"
                   value="<?= htmlspecialchars(substr($setting['day_start_time'] ?? '08:00', 0, 5), ENT_QUOTES, 'UTF-8') ?>"
                   required>
        </div>

        <div class="form-group" id="breaksSection">
            <label>Pausen</label>
            <p class="form-hint" style="margin-bottom: 0.5rem; font-size: 0.875rem;">
                Pausen zwischen Unterrichtseinheiten definieren (optional).
            </p>
            <div id="breaksList">
                <?php foreach ($setting['breaks'] ?? [] as $brk): ?>
                <div class="break-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items:flex-end;">
                    <div>
                        <label style="font-size:0.8rem;">Nach Einheit</label>
                        <input type="number" name="break_after_slot[]" min="1" max="14"
                               value="<?= (int) $brk['after_slot'] ?>" class="form-control" style="width:5rem;" required>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;">Dauer (Min.)</label>
                        <input type="number" name="break_duration[]" min="5" max="90"
                               value="<?= (int) $brk['duration'] ?>" class="form-control" style="width:5rem;" required>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;">Bezeichnung</label>
                        <input type="text" name="break_label[]" maxlength="50"
                               value="<?= htmlspecialchars($brk['label'] ?? 'Pause', ENT_QUOTES, 'UTF-8') ?>"
                               class="form-control" style="width:10rem;">
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary break-remove" title="Pause entfernen">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" id="addBreakBtn" style="margin-top:0.25rem;">+ Pause hinzufuegen</button>
        </div>

        <div class="form-group">
            <label>Wochentage <span class="required">*</span></label>
            <?php
            $activeDays = $setting['days_of_week'] ?? [1, 2, 3, 4, 5];
            $allDays = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
            ?>
            <div class="checkbox-group">
                <?php foreach ($allDays as $num => $name): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="days_of_week[]" value="<?= $num ?>"
                           <?= in_array($num, $activeDays) ? 'checked' : '' ?>>
                    <?= $name ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="/timetable" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<script>
(function () {
    var breaksList = document.getElementById('breaksList');
    var addBtn = document.getElementById('addBreakBtn');
    if (!breaksList || !addBtn) return;

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'break-row';
        row.style.cssText = 'display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items:flex-end;';
        row.innerHTML =
            '<div>' +
                '<label style="font-size:0.8rem;">Nach Einheit</label>' +
                '<input type="number" name="break_after_slot[]" min="1" max="14" class="form-control" style="width:5rem;" required>' +
            '</div>' +
            '<div>' +
                '<label style="font-size:0.8rem;">Dauer (Min.)</label>' +
                '<input type="number" name="break_duration[]" min="5" max="90" value="15" class="form-control" style="width:5rem;" required>' +
            '</div>' +
            '<div>' +
                '<label style="font-size:0.8rem;">Bezeichnung</label>' +
                '<input type="text" name="break_label[]" maxlength="50" value="Pause" class="form-control" style="width:10rem;">' +
            '</div>' +
            '<button type="button" class="btn btn-sm btn-secondary break-remove" title="Pause entfernen">&times;</button>';
        breaksList.appendChild(row);
    });

    breaksList.addEventListener('click', function (e) {
        if (e.target.classList.contains('break-remove')) {
            e.target.closest('.break-row').remove();
        }
    });
})();
</script>
