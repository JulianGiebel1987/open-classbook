/**
 * Stundenplan-Einstellungen: Pausen dynamisch hinzufügen/entfernen.
 */
(function () {
    'use strict';

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
