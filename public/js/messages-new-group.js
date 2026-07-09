/**
 * Neue Gruppen-Nachricht: Auswahl der Mitglieder zählen und validieren.
 */
(function () {
    'use strict';

    function init() {
        var form = document.getElementById('createGroupForm');
        var memberList = document.getElementById('memberList');
        var countLabel = document.getElementById('memberSelectCount');
        var memberError = document.getElementById('memberError');

        if (!form || !memberList || !countLabel) return;

        function updateMemberCount() {
            var checked = memberList.querySelectorAll('input[type="checkbox"]:checked').length;
            countLabel.textContent = checked + (checked === 1 ? ' Person ausgewählt' : ' Personen ausgewählt');
            if (memberError) {
                memberError.style.display = checked > 0 ? 'none' : memberError.style.display;
            }
        }

        // Event Delegation: ein einziger Listener auf dem Container
        memberList.addEventListener('change', function (e) {
            if (e.target && e.target.type === 'checkbox') {
                updateMemberCount();
            }
        });

        // Fallback: click-Event für ältere Browser
        memberList.addEventListener('click', function (e) {
            if (e.target && (e.target.type === 'checkbox' || e.target.closest('label'))) {
                setTimeout(updateMemberCount, 0);
            }
        });

        // Validierung beim Absenden
        form.addEventListener('submit', function (e) {
            var checked = memberList.querySelectorAll('input[type="checkbox"]:checked').length;
            if (checked < 1) {
                e.preventDefault();
                if (memberError) {
                    memberError.style.display = 'block';
                }
                memberList.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
