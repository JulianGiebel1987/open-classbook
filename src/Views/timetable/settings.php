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
