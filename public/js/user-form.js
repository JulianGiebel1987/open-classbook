/**
 * Rollenabhängige Pflichtfelder im Benutzer-Formular ein-/ausblenden.
 *
 * Die E-Mail-Adresse ist bei allen Rollen ausser Schüler:innen der Anmeldename
 * (Pflichtfeld). Schüler:innen erhalten einen generierten Login; für sie wird
 * das E-Mail-Feld ausgeblendet (die Eltern-E-Mail steht im Schüler-Bereich).
 */
(function () {
    var roleSelect = document.getElementById('role');
    var profileFields = document.getElementById('profile-fields');
    var teacherFields = document.getElementById('teacher-fields');
    var studentFields = document.getElementById('student-fields');
    var aideFields = document.getElementById('aide-fields');
    var firstname = document.getElementById('firstname');
    var lastname = document.getElementById('lastname');
    var abbreviation = document.getElementById('abbreviation');
    var classId = document.getElementById('class_id');
    var emailField = document.getElementById('email-field');
    var email = document.getElementById('email');
    var inviteNote = document.getElementById('invite-note');
    var studentNote = document.getElementById('student-note');

    if (!roleSelect || !profileFields) {
        return; // Nicht auf dieser Seite
    }

    function toggleFields() {
        var role = roleSelect.value;
        var isTeacher = (role === 'lehrer');
        var isStudent = (role === 'schueler');
        var isAide = (role === 'schulbegleiter');
        var needsProfile = isTeacher || isStudent || isAide;
        // Alle Rollen ausser Schüler:innen melden sich per E-Mail an.
        var isEmailLogin = role !== '' && !isStudent;

        profileFields.style.display = needsProfile ? '' : 'none';
        teacherFields.style.display = isTeacher ? '' : 'none';
        studentFields.style.display = isStudent ? '' : 'none';
        if (aideFields) aideFields.style.display = isAide ? '' : 'none';

        if (firstname) firstname.required = needsProfile;
        if (lastname) lastname.required = needsProfile;
        if (abbreviation) abbreviation.required = isTeacher;
        if (classId) classId.required = isStudent;

        // E-Mail = Anmeldename: für Schüler:innen ausblenden, sonst Pflichtfeld.
        // Ein verstecktes required-Feld würde den Submit blockieren.
        if (emailField) emailField.style.display = isStudent ? 'none' : '';
        if (email) email.required = isEmailLogin;

        // Hinweistexte (nur im Anlage-Formular vorhanden).
        if (inviteNote) inviteNote.style.display = isStudent ? 'none' : '';
        if (studentNote) studentNote.style.display = isStudent ? '' : 'none';
    }

    roleSelect.addEventListener('change', toggleFields);
    toggleFields();
})();
