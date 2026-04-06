/**
 * Stundenplan-Editor JavaScript
 */
(function () {
    'use strict';

    // === Klassen-Dropdown Navigation ===
    var classSelect = document.getElementById('classSelect');
    if (classSelect) {
        classSelect.addEventListener('change', function () {
            var sid = classSelect.dataset.settingId;
            var cid = classSelect.value;
            if (sid && cid) {
                window.location.href = '/timetable/' + sid + '/class/' + cid;
            }
        });
    }

    var grid = document.getElementById('timetableGrid');
    var modal = document.getElementById('slotModal');
    var form = document.getElementById('slotForm');
    var cancelBtn = document.getElementById('slotModalCancel');
    var conflictWarning = document.getElementById('slotConflictWarning');
    var teacherSelect = document.getElementById('slotTeacher');
    var teacherSearch = document.getElementById('teacherSearch');

    if (!grid) return;

    var settingId = grid.dataset.settingId;
    var classId = grid.dataset.classId;
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    csrfToken = csrfToken ? csrfToken.content : '';

    // === Slot hinzufügen ===

    grid.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.slot-add-btn');
        if (addBtn) {
            openSlotModal(addBtn.dataset.day, addBtn.dataset.slot);
            return;
        }

        var removeBtn = e.target.closest('.slot-remove');
        if (removeBtn) {
            deleteSlot(removeBtn.dataset.id);
        }
    });

    function openSlotModal(day, slot) {
        document.getElementById('slotDay').value = day;
        document.getElementById('slotNumber').value = slot;
        form.reset();
        document.getElementById('slotDay').value = day;
        document.getElementById('slotNumber').value = slot;
        conflictWarning.style.display = 'none';
        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
        teacherSelect.focus();
    }

    function closeSlotModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
    }

    cancelBtn.addEventListener('click', closeSlotModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeSlotModal();
    });

    // Escape-Taste
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeSlotModal();
        }
    });

    // === Konfliktpruefung bei Lehrerwechsel ===

    teacherSelect.addEventListener('change', function () {
        var teacherId = this.value;
        if (!teacherId) {
            conflictWarning.style.display = 'none';
            return;
        }

        var data = new FormData();
        data.append('csrf_token', csrfToken);
        data.append('timetable_setting_id', settingId);
        data.append('teacher_id', teacherId);
        data.append('day_of_week', document.getElementById('slotDay').value);
        data.append('slot_number', document.getElementById('slotNumber').value);
        data.append('class_id', classId);

        fetch('/timetable/check-conflict', { method: 'POST', body: data })
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

    // === Slot speichern ===

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var data = new FormData(form);
        data.append('csrf_token', csrfToken);

        fetch('/timetable/slot', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler beim Speichern.');
                    return;
                }

                // Slot in Grid einfuegen
                var day = document.getElementById('slotDay').value;
                var slotNum = document.getElementById('slotNumber').value;
                var cell = grid.querySelector(
                    'td.slot-cell[data-day="' + day + '"][data-slot="' + slotNum + '"]'
                );

                if (cell) {
                    var entry = document.createElement('div');
                    entry.className = 'slot-entry';
                    entry.dataset.slotId = result.slot.id;
                    entry.dataset.teacherId = result.slot.teacher_id;

                    var teacherSpan = document.createElement('span');
                    teacherSpan.className = 'slot-teacher';
                    teacherSpan.textContent = result.slot.abbreviation || '';
                    entry.appendChild(teacherSpan);

                    if (result.slot.subject) {
                        var subjectSpan = document.createElement('span');
                        subjectSpan.className = 'slot-subject';
                        subjectSpan.textContent = result.slot.subject;
                        entry.appendChild(subjectSpan);
                    }

                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'slot-remove';
                    removeBtn.dataset.id = result.slot.id;
                    removeBtn.setAttribute('aria-label', 'Eintrag entfernen');
                    removeBtn.title = 'Entfernen';
                    removeBtn.textContent = '\u00D7';
                    entry.appendChild(removeBtn);

                    // Vor dem "+"-Button einfuegen
                    var addButton = cell.querySelector('.slot-add-btn');
                    cell.insertBefore(entry, addButton);
                }

                // Lehrer-Einheiten-Zaehler aktualisieren
                updateTeacherUnitCount(result.slot.teacher_id, result.unit_count);

                // Konfliktwarnung anzeigen falls vorhanden
                if (result.conflict_warning) {
                    alert('Hinweis: ' + result.conflict_warning);
                }

                closeSlotModal();
            })
            .catch(function () {
                alert('Netzwerkfehler beim Speichern.');
            });
    });

    // === Slot löschen ===

    function deleteSlot(slotId) {
        if (!confirm('Eintrag wirklich entfernen?')) return;

        var data = new FormData();
        data.append('csrf_token', csrfToken);

        fetch('/timetable/slot/' + slotId + '/delete', { method: 'POST', body: data })
            .then(function (res) { return res.json(); })
            .then(function (result) {
                if (!result.success) {
                    alert(result.error || 'Fehler beim Löschen.');
                    return;
                }

                // Slot aus DOM entfernen
                var entry = grid.querySelector('.slot-entry[data-slot-id="' + slotId + '"]');
                if (entry) entry.remove();

                // Lehrer-Zaehler aktualisieren
                updateTeacherUnitCount(result.teacher_id, result.unit_count);
            })
            .catch(function () {
                alert('Netzwerkfehler beim Löschen.');
            });
    }

    // === Lehrer-Einheiten-Zaehler ===

    function updateTeacherUnitCount(teacherId, count) {
        var badge = document.getElementById('teacherUnits-' + teacherId);
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
