/**
 * Dynamische Schueler-Auswahl im Fehlzeit-Formular
 * Laedt Schueler einer Klasse per Fetch und befuellt das Dropdown.
 */
(function () {
    var classSelect = document.getElementById('class_id');
    var studentSelect = document.getElementById('student_id');
    var submitBtn = document.getElementById('submitBtn');

    if (!classSelect || !studentSelect) {
        return; // Nicht auf dieser Seite
    }

    function loadStudents(classId) {
        studentSelect.innerHTML = '<option value="">- Laden... -</option>';
        studentSelect.disabled = true;
        if (submitBtn) submitBtn.disabled = true;

        if (!classId) {
            studentSelect.innerHTML = '<option value="">- Zuerst Klasse waehlen -</option>';
            return;
        }

        fetch('/absences/students/by-class/' + encodeURIComponent(classId), {
            credentials: 'same-origin'
        })
        .then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function (students) {
            if (!Array.isArray(students) || students.length === 0) {
                studentSelect.innerHTML = '<option value="">- Keine Schueler in dieser Klasse -</option>';
                studentSelect.disabled = false;
                return;
            }
            studentSelect.innerHTML = '<option value="">- Schueler/in waehlen -</option>';
            students.forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.lastname + ', ' + s.firstname;
                studentSelect.appendChild(opt);
            });
            studentSelect.disabled = false;
            if (submitBtn) submitBtn.disabled = false;
        })
        .catch(function () {
            studentSelect.innerHTML = '<option value="">- Fehler beim Laden -</option>';
            studentSelect.disabled = false;
        });
    }

    classSelect.addEventListener('change', function () {
        loadStudents(this.value);
    });

    // Falls Klasse bereits vorausgewaehlt ist (z.B. nach Seitenreload)
    if (classSelect.value) {
        loadStudents(classSelect.value);
    }
})();
