/**
 * Unterrichtsinhalt-Vorlagen im Klassenbuch-Formular.
 * Uebernimmt Thema und Notizen der gewaehlten Vorlage per Klick in die Eingabefelder.
 */
(function () {
    var picker = document.getElementById('template_picker');
    var topic = document.getElementById('topic');
    var notes = document.getElementById('notes');

    if (!picker || !topic || !notes) {
        return; // Keine Vorlagen oder nicht auf dieser Seite
    }

    picker.addEventListener('change', function () {
        var option = this.options[this.selectedIndex];
        if (!option || !option.value) {
            return;
        }
        topic.value = option.getAttribute('data-topic') || '';
        notes.value = option.getAttribute('data-notes') || '';
    });
})();
