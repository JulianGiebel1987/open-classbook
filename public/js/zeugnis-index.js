/**
 * Zeugnis-Übersicht: "Alle auswählen"-Checkbox für die Stapelverarbeitung.
 */
(function () {
    'use strict';

    var selectAll = document.getElementById('select-all-cb');
    if (!selectAll) return;

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.batch-cb').forEach(function (cb) {
            cb.checked = selectAll.checked;
        });
    });
})();
