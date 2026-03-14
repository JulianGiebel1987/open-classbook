document.addEventListener('DOMContentLoaded', function () {
    // === Mobile Navigation Toggle ===
    var toggle = document.querySelector('.navbar-toggle');
    var menu = document.getElementById('navbarMenu');

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            menu.classList.toggle('active');
        });

        // Menue schliessen bei Klick ausserhalb
        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !menu.contains(e.target) && menu.classList.contains('active')) {
                menu.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Menue schliessen bei Escape-Taste
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && menu.classList.contains('active')) {
                menu.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        });
    }

    // === Bestaetigungsdialoge ===
    initConfirmDialogs();

    // === Flash-Messages automatisch ausblenden ===
    initFlashMessages();

    // === Ladeanimation fuer Formulare mit Datei-Upload und Exporte ===
    initLoadingSpinner();

    // === Clientseitige Tabellensuche ===
    initTableSearch();

    // === Aktive Navigation markieren ===
    markActiveNavItem();
});

/**
 * Bestaetigungsdialoge fuer kritische Aktionen
 */
function initConfirmDialogs() {
    var modal = document.getElementById('confirmModal');
    if (!modal) return;

    var modalMessage = modal.querySelector('.modal-message');
    var confirmBtn = modal.querySelector('.modal-confirm');
    var cancelBtn = modal.querySelector('.modal-cancel');
    var pendingForm = null;

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            modalMessage.textContent = el.dataset.confirm;
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            cancelBtn.focus();

            // Formular oder Link merken
            if (el.tagName === 'A') {
                pendingForm = { type: 'link', el: el };
            } else if (el.closest('form')) {
                pendingForm = { type: 'form', el: el.closest('form') };
            }
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            if (pendingForm) {
                if (pendingForm.type === 'form') {
                    pendingForm.el.submit();
                } else if (pendingForm.type === 'link') {
                    window.location.href = pendingForm.el.href;
                }
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
        });
    }

    // Schliessen bei Escape
    modal.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
        }
    });

    // Schliessen bei Klick auf Overlay
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
        }
    });
}

/**
 * Flash-Messages nach 5 Sekunden ausblenden
 */
function initFlashMessages() {
    document.querySelectorAll('.alert').forEach(function (alert) {
        // Dismiss-Button
        var dismissBtn = alert.querySelector('.alert-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function () {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-0.5rem)';
                alert.style.transition = 'opacity 0.3s, transform 0.3s';
                setTimeout(function () {
                    alert.remove();
                }, 300);
            });
        }

        // Auto-Dismiss nach 8 Sekunden
        setTimeout(function () {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-0.5rem)';
                alert.style.transition = 'opacity 0.3s, transform 0.3s';
                setTimeout(function () {
                    if (alert.parentNode) alert.remove();
                }, 300);
            }
        }, 8000);
    });
}

/**
 * Ladeanimation fuer laengere Vorgaenge
 */
function initLoadingSpinner() {
    var loadingOverlay = document.getElementById('loadingOverlay');
    if (!loadingOverlay) return;

    // Bei Import-Formularen und Export-Links
    document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            loadingOverlay.classList.add('active');
        });
    });

    // Bei Export-Links
    document.querySelectorAll('a[href*="export"]').forEach(function (link) {
        link.addEventListener('click', function () {
            loadingOverlay.classList.add('active');
            // Nach 5 Sekunden ausblenden (Download sollte gestartet sein)
            setTimeout(function () {
                loadingOverlay.classList.remove('active');
            }, 5000);
        });
    });
}

/**
 * Clientseitige Tabellensuche
 */
function initTableSearch() {
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        var tableId = input.dataset.tableSearch;
        var table = document.getElementById(tableId);
        if (!table) return;

        var tbody = table.querySelector('tbody');
        if (!tbody) return;

        input.addEventListener('input', function () {
            var searchTerm = input.value.toLowerCase().trim();
            var rows = tbody.querySelectorAll('tr');

            rows.forEach(function (row) {
                if (row.querySelector('td[colspan]')) return; // Skip "no data" rows
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });

            // Zaehler aktualisieren
            var counter = document.querySelector('[data-search-count="' + tableId + '"]');
            if (counter) {
                var visible = tbody.querySelectorAll('tr:not([style*="display: none"]):not(:has(td[colspan]))').length;
                counter.textContent = visible;
            }
        });
    });
}

/**
 * Aktives Navigationselement markieren
 */
function markActiveNavItem() {
    var currentPath = window.location.pathname;
    document.querySelectorAll('.navbar-menu a').forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && currentPath.startsWith(href) && href !== '/') {
            link.setAttribute('aria-current', 'page');
        } else if (href === currentPath) {
            link.setAttribute('aria-current', 'page');
        }
    });
}
