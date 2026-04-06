/**
 * Vertretungsplan-Editor JavaScript
 */
(function () {
    'use strict';

    var planEl = document.getElementById('substitutionPlan');
    var modal = document.getElementById('subAssignModal');
    var form = document.getElementById('subAssignForm');
    var cancelBtn = document.getElementById('subAssignCancel');
    var conflictWarning = document.getElementById('subConflictWarning');
    var teacherSelect = document.getElementById('assignTeacher');
    var teacherHint = document.getElementById('assignTeacherHint');

    if (!planEl) return;

    var settingId = planEl.dataset.settingId;
    var currentDate = planEl.dataset.date;
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    csrfToken = csrfToken ? csrfToken.content : '';

    // === Offene Slots: Zuweisen / Entfall ===

    document.addEventListener('click', function (e) {
        var assignBtn = e.target.closest('.sub-assign-btn');
        if (assignBtn) {
            var row = assignBtn.closest('.open-slot-row');
            openAssignModal(row);
            return;
        }

        var cancelSlotBtn = e.target.closest('.sub-cancel-btn');
        if (cancelSlotBtn) {
            var row = cancelSlotBtn.closest('.open-slot-row');
            markAsCancelled(row);
            return;
        }

        var deleteBtn = e.target.closest('.sub-delete-btn');
        if (deleteBtn) {
            deleteSubstitution(deleteBtn.dataset.id);
        }
    });

    // === Modal öffnen ===

    function openAssignModal(row) {
        document.getElementById('assignSlotNumber').value = row.dataset.slotNumber;
        document.getElementById('assignClassId').value = row.dataset.classId;
        document.getElementById('assignAbsentTeacherId').value = row.dataset.absentTeacherId;
        document.getElementById('assignAbsenceId').value = row.dataset.absenceId || '';
        document.getElementById('assignSubject').value = row.dataset.subject || '';
        document.getElementById('assignRoom').value = row.dataset.room || '';
        document.getElementById('assignNotes').value = '';
        conflictWarning.style.display = 'none';

        // Verfuegbare Lehrer laden
        loadAvailableTeachers(row.dataset.slotNumber);

        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
    }

    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeModal();
        }
    });

    // === Verfuegbare Lehrer laden ===

    function loadAvailableTeachers(slotNumber) {
        teacherSelect.innerHTML = '<option value="">Wird geladen...</option>';
        teacherHint.textContent = '';

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('date', currentDate);
        data.append('slot_number', slotNumber);

        fetch('/substitution/available-teachers', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                teacherSelect.innerHTML = '<option value="">– Lehrkraft wählen –</option>';

                var available = 0;
                (result.teachers || []).forEach(function (t) {
                    var option = document.createElement('option');
                    option.value = t.id;
                    var label = t.abbreviation + ' – ' + t.lastname + ', ' + t.firstname;

                    if (t.status === 'available') {
                        label += ' (frei)';
                        available++;
                    } else if (t.status === 'busy_regular') {
                        label += ' (im Unterricht)';
                    } else if (t.status === 'busy_substitution') {
                        label += ' (andere Vertretung)';
                    }

                    option.textContent = label;
                    option.dataset.status = t.status;
                    teacherSelect.appendChild(option);
                });

                teacherHint.textContent = available + ' Lehrkräfte frei in dieser Einheit';
                teacherSelect.focus();
            })
            .catch(function () {
                teacherSelect.innerHTML = '<option value="">Fehler beim Laden</option>';
            });
    }

    // === Konfliktpruefung bei Lehrerwechsel ===

    teacherSelect.addEventListener('change', function () {
        var teacherId = this.value;
        if (!teacherId) {
            conflictWarning.style.display = 'none';
            return;
        }

        var slotNumber = document.getElementById('assignSlotNumber').value;

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('date', currentDate);
        data.append('teacher_id', teacherId);
        data.append('slot_number', slotNumber);

        fetch('/substitution/check-conflict', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (result.conflicts && result.conflicts.length > 0) {
                    var msgs = result.conflicts.map(function (c) { return c.message; });
                    conflictWarning.textContent = result.teacher_name + ': ' + msgs.join('; ');
                    conflictWarning.style.display = 'block';
                } else {
                    conflictWarning.style.display = 'none';
                }
            })
            .catch(function () {
                conflictWarning.style.display = 'none';
            });
    });

    // === Vertretung zuweisen ===

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var data = new FormData(form);
        data.append('csrf_token', csrfToken);

        fetch('/substitution/assign', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler beim Zuweisen.');
                    return;
                }

                if (result.conflict_warning) {
                    alert('Hinweis: ' + result.conflict_warning);
                }

                // Seite neu laden um konsistenten Stand zu zeigen
                window.location.reload();
            })
            .catch(function () {
                alert('Netzwerkfehler.');
            });
    });

    // === Entfall markieren ===

    function markAsCancelled(row) {
        if (!confirm('Einheit als Entfall markieren?')) return;

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('date', currentDate);
        data.append('slot_number', row.dataset.slotNumber);
        data.append('class_id', row.dataset.classId);
        data.append('absent_teacher_id', row.dataset.absentTeacherId);
        data.append('absence_teacher_id', row.dataset.absenceId || '');

        fetch('/substitution/cancel', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler.');
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                alert('Netzwerkfehler.');
            });
    }

    // === Vertretung löschen ===

    function deleteSubstitution(subId) {
        if (!confirm('Vertretung wirklich entfernen?')) return;

        var data = new FormData();
        data.append('csrf_token', csrfToken);

        fetch('/substitution/' + subId + '/delete', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler.');
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                alert('Netzwerkfehler.');
            });
    }
})();
