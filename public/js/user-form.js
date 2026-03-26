/**
 * Rollenabhaengige Pflichtfelder im Benutzer-Formular ein-/ausblenden
 */
(function () {
    var roleSelect = document.getElementById('role');
    var profileFields = document.getElementById('profile-fields');
    var teacherFields = document.getElementById('teacher-fields');
    var studentFields = document.getElementById('student-fields');
    var firstname = document.getElementById('firstname');
    var lastname = document.getElementById('lastname');
    var abbreviation = document.getElementById('abbreviation');
    var classId = document.getElementById('class_id');

    if (!roleSelect || !profileFields) {
        return; // Nicht auf dieser Seite
    }

    function toggleFields() {
        var role = roleSelect.value;
        var isTeacher = (role === 'lehrer');
        var isStudent = (role === 'schueler');
        var needsProfile = isTeacher || isStudent;

        profileFields.style.display = needsProfile ? '' : 'none';
        teacherFields.style.display = isTeacher ? '' : 'none';
        studentFields.style.display = isStudent ? '' : 'none';

        if (firstname) firstname.required = needsProfile;
        if (lastname) lastname.required = needsProfile;
        if (abbreviation) abbreviation.required = isTeacher;
        if (classId) classId.required = isStudent;
    }

    roleSelect.addEventListener('change', toggleFields);
    toggleFields();
})();
