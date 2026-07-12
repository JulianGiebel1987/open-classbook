/**
 * Pausenaufsichtsplan-Editor JavaScript
 *
 * Zuweisung erfolgt direkt über ein Dropdown pro Aufsichtspunkt-Zelle:
 * Beim Auswählen einer Lehrkraft wird sie sofort zugewiesen.
 */
(function () {
    'use strict';

    var grid = document.getElementById('supervisionGrid');
    if (!grid) return;

    var planId = grid.dataset.planId;
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    csrfToken = csrfToken ? csrfToken.content : '';
    var teacherSearch = document.getElementById('teacherSearch');

    // === Zelle: Lehrkraft per Dropdown zuweisen ===

    grid.addEventListener('change', function (e) {
        var select = e.target.closest('.supervision-select');
        if (!select || !select.value) return;
        assignTeacher(select);
    });

    // === Zelle: Zuweisung entfernen ===

    grid.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.slot-entry .slot-remove');
        if (removeBtn && removeBtn.dataset.id) {
            deleteAssignment(removeBtn.dataset.id);
        }
    });

    // === Zuweisung speichern ===

    function assignTeacher(select) {
        var location = select.dataset.location;
        var day = select.dataset.day;
        var breakId = select.dataset.break;
        var teacherId = select.value;
        var teacherLabel = select.options[select.selectedIndex].text.trim();

        select.disabled = true;
        clearCellConflict(select);

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('plan_id', planId);
        data.append('location_id', location);
        data.append('day_of_week', day);
        data.append('break_id', breakId);
        data.append('teacher_id', teacherId);

        fetch('/supervision/assignment', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler beim Speichern.');
                    return;
                }

                var cell = select.closest('td.supervision-cell');
                if (cell) {
                    var entry = buildEntry(result.assignment, teacherLabel);
                    cell.insertBefore(entry, select);
                }

                updateTeacherCount(result.assignment.teacher_id, result.teacher_count);

                if (result.conflict_warning) {
                    showCellConflict(select, result.conflict_warning);
                }
            })
            .catch(function () {
                alert('Netzwerkfehler beim Speichern.');
            })
            .finally(function () {
                select.disabled = false;
                select.value = '';
            });
    }

    function buildEntry(assignment, fallbackLabel) {
        var entry = document.createElement('div');
        entry.className = 'slot-entry';
        entry.dataset.assignmentId = assignment.id;
        entry.dataset.teacherId = assignment.teacher_id;

        var teacherSpan = document.createElement('span');
        teacherSpan.className = 'slot-teacher';
        teacherSpan.textContent = assignment.abbreviation || assignment.lastname || fallbackLabel || '';
        entry.appendChild(teacherSpan);

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'slot-remove';
        removeBtn.dataset.id = assignment.id;
        removeBtn.setAttribute('aria-label', 'Zuweisung entfernen');
        removeBtn.title = 'Entfernen';
        removeBtn.textContent = '×';
        entry.appendChild(removeBtn);

        return entry;
    }

    // === Konflikt-Hinweis je Zelle ===

    function showCellConflict(select, message) {
        clearCellConflict(select);
        var note = document.createElement('div');
        note.className = 'supervision-conflict';
        note.setAttribute('role', 'alert');
        note.textContent = message;
        select.parentNode.insertBefore(note, select);
    }

    function clearCellConflict(select) {
        var cell = select.closest('td.supervision-cell');
        if (!cell) return;
        var existing = cell.querySelector('.supervision-conflict');
        if (existing) existing.remove();
    }

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
