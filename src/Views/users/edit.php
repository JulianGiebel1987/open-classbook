<div class="page-header">
    <h1>Benutzer bearbeiten</h1>
</div>

<div class="card">
    <form method="post" action="/users/<?= $user['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <?php $old = $old ?? []; ?>

        <div class="form-group">
            <label for="username">Benutzername <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="username" name="username" class="form-control" required value="<?= htmlspecialchars($old['username'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="username">
        </div>

        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email">
        </div>

        <div class="form-group">
            <label for="role">Rolle <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="role" id="role" class="form-control" required>
                <?php $selectedRole = $old['role'] ?? $user['role']; ?>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= $selectedRole === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="profile-fields" style="display: none;">
            <div class="form-group">
                <label for="firstname">Vorname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="firstname" name="firstname" class="form-control" value="<?= htmlspecialchars($profile['firstname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="lastname">Nachname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="lastname" name="lastname" class="form-control" value="<?= htmlspecialchars($profile['lastname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div id="teacher-fields" style="display: none;">
            <div class="form-group">
                <label for="abbreviation">Kuerzel <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="abbreviation" name="abbreviation" class="form-control" maxlength="10" value="<?= htmlspecialchars($profile['abbreviation'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="subjects">Faecher</label>
                <input type="text" id="subjects" name="subjects" class="form-control" value="<?= htmlspecialchars($profile['subjects'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div id="student-fields" style="display: none;">
            <div class="form-group">
                <label for="class_id">Klasse <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <select name="class_id" id="class_id" class="form-control">
                    <option value="">- Klasse waehlen -</option>
                    <?php foreach ($classes ?? [] as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($profile['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Speichern</button>
            <a href="/users" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<script>
(function() {
    var roleSelect = document.getElementById('role');
    var profileFields = document.getElementById('profile-fields');
    var teacherFields = document.getElementById('teacher-fields');
    var studentFields = document.getElementById('student-fields');
    var firstname = document.getElementById('firstname');
    var lastname = document.getElementById('lastname');
    var abbreviation = document.getElementById('abbreviation');
    var classId = document.getElementById('class_id');

    function toggleFields() {
        var role = roleSelect.value;
        var isTeacher = (role === 'lehrer');
        var isStudent = (role === 'schueler');
        var needsProfile = isTeacher || isStudent;

        profileFields.style.display = needsProfile ? '' : 'none';
        teacherFields.style.display = isTeacher ? '' : 'none';
        studentFields.style.display = isStudent ? '' : 'none';

        firstname.required = needsProfile;
        lastname.required = needsProfile;
        abbreviation.required = isTeacher;
        if (classId) classId.required = isStudent;
    }

    roleSelect.addEventListener('change', toggleFields);
    toggleFields();
})();
</script>
