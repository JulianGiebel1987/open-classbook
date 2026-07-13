<div class="page-header">
    <h1><?= $plan ? 'Pausenaufsichtsplan bearbeiten' : 'Neuer Pausenaufsichtsplan' ?></h1>
</div>

<div class="card">
    <form method="post" action="/supervision/settings">
        <?= \OpenClassbook\View::csrfField() ?>
        <?php if ($plan): ?>
            <input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="form-control" maxlength="150"
                   value="<?= htmlspecialchars($plan['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="z.B. Pausenaufsichten Schulhof" required>
        </div>

        <div class="form-group">
            <label for="school_year">Schuljahr <span class="required">*</span></label>
            <input type="text" id="school_year" name="school_year" class="form-control"
                   value="<?= htmlspecialchars($plan['school_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="z.B. 2025/2026" pattern="\d{4}/\d{4}" required>
            <small class="form-hint">Format: JJJJ/JJJJ</small>
        </div>

        <div class="form-group" id="breaksSection">
            <label>Pausenspalten <span class="required">*</span></label>
            <p class="form-hint" style="margin-bottom: 0.5rem; font-size: 0.875rem;">
                Pausen definieren, in denen Aufsichten geplant werden (gelten für alle ausgewählten Wochentage).
            </p>
            <div id="breaksList">
                <?php foreach ($breaks as $brk): ?>
                <div class="break-row" style="display:flex; gap:0.5rem; margin-bottom:0.5rem; align-items:flex-end;">
                    <input type="hidden" name="break_id[]" value="<?= (int) $brk['id'] ?>">
                    <div>
                        <label style="font-size:0.8rem;">Bezeichnung</label>
                        <input type="text" name="break_label[]" maxlength="80"
                               value="<?= htmlspecialchars($brk['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               class="form-control" style="width:12rem;" required>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;">Von</label>
                        <input type="time" name="break_start[]"
                               value="<?= htmlspecialchars(substr($brk['start_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>"
                               class="form-control" style="width:8rem;">
                    </div>
                    <div>
                        <label style="font-size:0.8rem;">Bis</label>
                        <input type="time" name="break_end[]"
                               value="<?= htmlspecialchars(substr($brk['end_time'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>"
                               class="form-control" style="width:8rem;">
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary break-remove" title="Pause entfernen">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" id="addBreakBtn" style="margin-top:0.25rem;">+ Pausenspalte hinzufügen</button>
        </div>

        <div class="form-group">
            <label>Wochentage <span class="required">*</span></label>
            <?php
            $activeDays = $plan['days_of_week'] ?? [1, 2, 3, 4, 5];
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
            <a href="/supervision" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<script src="<?= \OpenClassbook\View::asset('/js/supervision-settings.js') ?>"></script>
