/**
 * Pausenaufsichtsplan-Editor JavaScript
 */
(function () {
    'use strict';

    var grid = document.getElementById('supervisionGrid');
    var modal = document.getElementById('assignModal');
    var form = document.getElementById('assignForm');
    var cancelBtn = document.getElementById('assignModalCancel');
    var conflictWarning = document.getElementById('assignConflictWarning');
    var teacherSelect = document.getElementById('assignTeacher');
    var teacherSearch = document.getElementById('teacherSearch');

    if (!grid) return;

    var planId = grid.dataset.planId;
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    csrfToken = csrfToken ? csrfToken.content : '';

    // === Zelle: Zuweisung hinzufügen / entfernen ===

    grid.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.slot-add-btn');
        if (addBtn) {
            openAssignModal(addBtn.dataset.location, addBtn.dataset.day, addBtn.dataset.break);
            return;
        }

        var removeBtn = e.target.closest('.slot-entry .slot-remove');
        if (removeBtn && removeBtn.dataset.id) {
            deleteAssignment(removeBtn.dataset.id);
        }
    });

    function openAssignModal(location, day, breakId) {
        form.reset();
        document.getElementById('assignLocation').value = location;
        document.getElementById('assignDay').value = day;
        document.getElementById('assignBreak').value = breakId;
        conflictWarning.style.display = 'none';
        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
        teacherSelect.focus();
    }

    function closeAssignModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
    }

    cancelBtn.addEventListener('click', closeAssignModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeAssignModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeAssignModal();
        }
    });

    // === Konfliktprüfung bei Lehrerwechsel ===

    teacherSelect.addEventListener('change', function () {
        var teacherId = this.value;
        if (!teacherId) {
            conflictWarning.style.display = 'none';
            return;
        }

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('plan_id', planId);
        data.append('teacher_id', teacherId);
        data.append('day_of_week', document.getElementById('assignDay').value);
        data.append('break_id', document.getElementById('assignBreak').value);
        data.append('location_id', document.getElementById('assignLocation').value);

        fetch('/supervision/check-conflict', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (result.has_conflict) {
                    conflictWarning.textContent = result.message;
                    conflictWarning.style.display = 'block';
                } else {
                    conflictWarning.style.display = 'none';
                }
            })
            .catch(function () {
                conflictWarning.style.display = 'none';
            });
    });

    // === Zuweisung speichern ===

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var data = new FormData(form);
        data.append('csrf_token', csrfToken);

        fetch('/supervision/assignment', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler beim Speichern.');
                    return;
                }

                var location = document.getElementById('assignLocation').value;
                var day = document.getElementById('assignDay').value;
                var breakId = document.getElementById('assignBreak').value;
                var cell = grid.querySelector(
                    'td.supervision-cell[data-location="' + location + '"][data-day="' + day + '"][data-break="' + breakId + '"]'
                );

                if (cell) {
                    var entry = document.createElement('div');
                    entry.className = 'slot-entry';
                    entry.dataset.assignmentId = result.assignment.id;
                    entry.dataset.teacherId = result.assignment.teacher_id;

                    var teacherSpan = document.createElement('span');
                    teacherSpan.className = 'slot-teacher';
                    teacherSpan.textContent = result.assignment.abbreviation || result.assignment.lastname || '';
                    entry.appendChild(teacherSpan);

                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'slot-remove';
                    removeBtn.dataset.id = result.assignment.id;
                    removeBtn.setAttribute('aria-label', 'Zuweisung entfernen');
                    removeBtn.title = 'Entfernen';
                    removeBtn.textContent = '×';
                    entry.appendChild(removeBtn);

                    var addButton = cell.querySelector('.slot-add-btn');
                    cell.insertBefore(entry, addButton);
                }

                updateTeacherCount(result.assignment.teacher_id, result.teacher_count);

                if (result.conflict_warning) {
                    alert('Hinweis: ' + result.conflict_warning);
                }

                closeAssignModal();
            })
            .catch(function () {
                alert('Netzwerkfehler beim Speichern.');
            });
    });

    // === Zuweisung löschen ===

    function deleteAssignment(assignmentId) {
        if (!confirm('Zuweisung wirklich entfernen?')) return;

        var data = new FormData();
        data.append('csrf_token', csrfToken);

        fetch('/supervision/assignment/' + assignmentId + '/delete', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler beim Löschen.');
                    return;
                }

                var entry = grid.querySelector('.slot-entry[data-assignment-id="' + assignmentId + '"]');
                if (entry) entry.remove();

                updateTeacherCount(result.teacher_id, result.teacher_count);
            })
            .catch(function () {
                alert('Netzwerkfehler beim Löschen.');
            });
    }

    // === Lehrer-Zähler ===

    function updateTeacherCount(teacherId, count) {
        var badge = document.getElementById('teacherCount-' + teacherId);
        if (badge) {
            badge.textContent = count;
        }
    }

    // === Lehrer-Suche in Sidebar ===

    if (teacherSearch) {
        teacherSearch.addEventListener('input', function () {
            var query = this.value.toLowerCase();
            var items = document.querySelectorAll('#teacherList .teacher-item');
            items.forEach(function (item) {
                var name = item.dataset.name || '';
                item.style.display = name.indexOf(query) !== -1 ? '' : 'none';
            });
        });
    }

})();
