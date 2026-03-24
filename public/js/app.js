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

    // === Chat-Funktionen ===
    initChat();

    // === Dateiverwaltung ===
    initFileManager();

    // === Listen ===
    initLists();
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

/**
 * Chat-Funktionen: Scroll, aeltere Nachrichten laden, Enter-Senden
 */
function initChat() {
    var chatContainer = document.getElementById('chatContainer');
    if (!chatContainer) return;

    var conversationId = chatContainer.dataset.conversationId;
    var currentUser = parseInt(chatContainer.dataset.currentUser, 10);

    // Zum Ende scrollen
    chatContainer.scrollTop = chatContainer.scrollHeight;

    // Enter = Senden, Shift+Enter = Zeilenumbruch
    var chatInput = document.getElementById('chatInput');
    var chatForm = document.getElementById('chatForm');
    if (chatInput && chatForm) {
        chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (chatInput.value.trim() !== '') {
                    chatForm.submit();
                }
            }
        });
    }

    // Aeltere Nachrichten laden
    var loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function () {
            var offset = parseInt(loadMoreBtn.dataset.offset, 10);
            loadMoreBtn.textContent = 'Laden...';
            loadMoreBtn.disabled = true;

            fetch('/messages/' + conversationId + '/older?offset=' + offset)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.messages || data.messages.length === 0) {
                        loadMoreBtn.textContent = 'Keine weiteren Nachrichten';
                        return;
                    }

                    var fragment = document.createDocumentFragment();
                    data.messages.reverse().forEach(function (m) {
                        var bubble = document.createElement('div');
                        var isMine = parseInt(m.sender_id, 10) === currentUser;
                        bubble.className = 'chat-bubble ' + (isMine ? 'chat-bubble--mine' : 'chat-bubble--theirs');

                        var bodyDiv = document.createElement('div');
                        bodyDiv.className = 'chat-bubble-body';
                        bodyDiv.textContent = m.body;
                        bubble.appendChild(bodyDiv);

                        var metaDiv = document.createElement('div');
                        metaDiv.className = 'chat-bubble-meta';
                        var date = new Date(m.created_at);
                        metaDiv.textContent = date.toLocaleDateString('de-DE', {day: '2-digit', month: '2-digit'}) + ' ' + date.toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'});
                        bubble.appendChild(metaDiv);

                        fragment.appendChild(bubble);
                    });

                    // Vor dem ersten Chat-Bubble einfuegen (nach dem Button)
                    var firstBubble = chatContainer.querySelector('.chat-bubble');
                    if (firstBubble) {
                        chatContainer.insertBefore(fragment, firstBubble);
                    } else {
                        chatContainer.appendChild(fragment);
                    }

                    loadMoreBtn.dataset.offset = offset + data.messages.length;
                    loadMoreBtn.textContent = 'Aeltere Nachrichten laden';
                    loadMoreBtn.disabled = false;

                    if (data.messages.length < 50) {
                        loadMoreBtn.textContent = 'Keine weiteren Nachrichten';
                        loadMoreBtn.disabled = true;
                    }
                })
                .catch(function () {
                    loadMoreBtn.textContent = 'Fehler beim Laden';
                    loadMoreBtn.disabled = false;
                });
        });
    }

    // Polling: alle 15 Sekunden neue Nachrichten pruefen
    var messageCount = parseInt(chatContainer.dataset.messageCount, 10) || 0;
    setInterval(function () {
        fetch('/messages/' + conversationId + '/older?offset=0')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.messages) return;
                var newCount = data.messages.length;
                if (newCount > messageCount) {
                    // Neue Nachrichten vorhanden - Seite neu laden
                    window.location.reload();
                }
            })
            .catch(function () { /* ignore */ });
    }, 15000);
}

/**
 * Dateiverwaltung: Upload-Validierung, Ordner-Toggle
 */
function initFileManager() {
    // Ordner-Erstellung ein-/ausblenden
    var toggleBtn = document.getElementById('toggleFolderForm');
    var folderForm = document.getElementById('folderForm');
    if (toggleBtn && folderForm) {
        toggleBtn.addEventListener('click', function () {
            var visible = folderForm.style.display !== 'none';
            folderForm.style.display = visible ? 'none' : 'block';
            if (!visible) {
                folderForm.querySelector('input[name="name"]').focus();
            }
        });
    }

    // Dateigroesse clientseitig pruefen
    var fileInput = document.getElementById('fileInput');
    if (fileInput) {
        var maxSize = parseInt(fileInput.dataset.maxSize, 10) || (15 * 1024 * 1024);
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0 && fileInput.files[0].size > maxSize) {
                alert('Die Datei ist zu gross. Maximale Groesse: 15 MB.');
                fileInput.value = '';
            }
        });
    }
}

/**
 * Listen: Inline-Speicherung, Formular-Toggles, Spaltentyp-Logik
 */
function initLists() {
    // Toggle-Buttons fuer Inline-Formulare
    var togglePairs = [
        ['toggleAddColumn', 'addColumnForm'],
        ['toggleAddRow', 'addRowForm'],
        ['toggleEditMeta', 'editMetaForm'],
    ];
    togglePairs.forEach(function (pair) {
        var btn = document.getElementById(pair[0]);
        var form = document.getElementById(pair[1]);
        if (btn && form) {
            btn.addEventListener('click', function () {
                var visible = form.style.display !== 'none';
                form.style.display = visible ? 'none' : 'block';
                if (!visible) {
                    var firstInput = form.querySelector('input[type="text"], select');
                    if (firstInput) firstInput.focus();
                }
            });
        }
    });

    // Select-Optionen ein-/ausblenden bei Spaltentyp-Auswahl
    document.querySelectorAll('.list-col-type-select').forEach(function (select) {
        var optionsInput = select.parentElement.querySelector('.list-col-options');
        if (!optionsInput) return;
        function toggleOptions() {
            optionsInput.style.display = select.value === 'select' ? '' : 'none';
            optionsInput.required = select.value === 'select';
        }
        toggleOptions();
        select.addEventListener('change', toggleOptions);
    });

    // Spalte hinzufuegen im Erstellformular
    var addColBtn = document.getElementById('addColumnBtn');
    var colContainer = document.getElementById('columnContainer');
    if (addColBtn && colContainer) {
        addColBtn.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'list-column-row';
            row.innerHTML =
                '<input type="text" name="col_title[]" class="form-control" placeholder="Spaltenname" required>' +
                '<select name="col_type[]" class="form-control list-col-type-select">' +
                    '<option value="text">Freitext</option>' +
                    '<option value="checkbox">Checkbox</option>' +
                    '<option value="number">Zahl</option>' +
                    '<option value="date">Datum</option>' +
                    '<option value="select">Auswahl</option>' +
                    '<option value="rating">Bewertung (1-6)</option>' +
                '</select>' +
                '<input type="text" name="col_options[]" class="form-control list-col-options" placeholder="Optionen (kommasepariert)" style="display:none">' +
                '<button type="button" class="btn-icon btn-icon-danger list-remove-col">&times;</button>';
            colContainer.appendChild(row);

            // Event-Listener fuer neuen Type-Select
            var newSelect = row.querySelector('.list-col-type-select');
            var newOptions = row.querySelector('.list-col-options');
            newSelect.addEventListener('change', function () {
                newOptions.style.display = newSelect.value === 'select' ? '' : 'none';
                newOptions.required = newSelect.value === 'select';
            });

            // Entfernen-Button
            row.querySelector('.list-remove-col').addEventListener('click', function () {
                row.remove();
            });
        });
    }

    // Inline-Zellen-Speicherung (AJAX)
    var listTable = document.querySelector('.list-table');
    if (!listTable) return;

    var listId = listTable.dataset.listId;
    var canEdit = listTable.dataset.canEdit === '1';
    if (!canEdit) return;

    listTable.querySelectorAll('.list-cell').forEach(function (cell) {
        var input = cell.querySelector('.list-cell-input');
        if (!input) return;

        var rowId = cell.dataset.rowId;
        var columnId = cell.dataset.columnId;

        function saveValue() {
            var value;
            if (input.type === 'checkbox') {
                value = input.checked ? '1' : '';
            } else {
                value = input.value;
            }

            cell.classList.add('list-cell--saving');
            cell.classList.remove('list-cell--saved');

            fetch('/lists/cell', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    list_id: parseInt(listId, 10),
                    row_id: parseInt(rowId, 10),
                    column_id: parseInt(columnId, 10),
                    value: value
                })
            })
            .then(function (res) { return res.json(); })
            .then(function () {
                cell.classList.remove('list-cell--saving');
                cell.classList.add('list-cell--saved');
                setTimeout(function () {
                    cell.classList.remove('list-cell--saved');
                }, 1000);
            })
            .catch(function () {
                cell.classList.remove('list-cell--saving');
                alert('Fehler beim Speichern.');
            });
        }

        if (input.type === 'checkbox') {
            input.addEventListener('change', saveValue);
        } else if (input.tagName === 'SELECT') {
            input.addEventListener('change', saveValue);
        } else {
            input.addEventListener('blur', saveValue);
        }
    });
}
