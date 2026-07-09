/**
 * Zeugnis-Stapelverarbeitung: "Alle auswählen"-Checkbox.
 */
(function () {
    'use strict';

    var selectAll = document.getElementById('select-all-cb');
    if (!selectAll) return;

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.student-cb').forEach(function (cb) {
            cb.checked = selectAll.checked;
        });
    });
})();
