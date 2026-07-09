/**
 * Pausenaufsichtsplan-Einstellungen: Pausenspalten dynamisch hinzufügen/entfernen.
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
            '<input type="hidden" name="break_id[]" value="">' +
            '<div>' +
                '<label style="font-size:0.8rem;">Bezeichnung</label>' +
                '<input type="text" name="break_label[]" maxlength="80" class="form-control" style="width:12rem;" required>' +
            '</div>' +
            '<div>' +
                '<label style="font-size:0.8rem;">Von</label>' +
                '<input type="time" name="break_start[]" class="form-control" style="width:8rem;">' +
            '</div>' +
            '<div>' +
                '<label style="font-size:0.8rem;">Bis</label>' +
                '<input type="time" name="break_end[]" class="form-control" style="width:8rem;">' +
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
