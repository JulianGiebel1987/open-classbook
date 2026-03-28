/**
 * Zeugnis Fill-In Interface
 * Renders the certificate canvas with editable fields for teachers.
 * Provides AJAX autosave for individual field values.
 */
(function () {
    'use strict';

    var SCALE = 3; // px per mm

    var PAGE_FORMATS = {
        A4: { w: 210, h: 297 },
        A3: { w: 297, h: 420 },
    };

    var canvas, instanceId, canEdit, csrfToken;
    var autosaveTimeout = null;
    var saveStatus;

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    function init() {
        canvas     = document.getElementById('zeugnis-canvas');
        saveStatus = document.getElementById('autosave-status');

        if (!canvas) return;

        instanceId = parseInt(canvas.dataset.instanceId, 10);
        canEdit    = canvas.dataset.canEdit === '1';
        csrfToken  = window.ZEUGNIS_CSRF_TOKEN || '';

        var canvasData  = window.ZEUGNIS_CANVAS_DATA  || { pages: [] };
        var fieldValues = window.ZEUGNIS_FIELD_VALUES || {};
        var tokens      = window.ZEUGNIS_TOKENS       || {};

        renderFillCanvas(canvasData, fieldValues, tokens);
    }

    // -------------------------------------------------------------------------
    // Render canvas with fill-in fields
    // -------------------------------------------------------------------------

    function renderFillCanvas(canvasData, fieldValues, tokens) {
        var pages = canvasData.pages || [];
        if (!pages.length) return;

        // Determine page dimensions from first page's template metadata
        var pageFormat      = window.ZEUGNIS_CANVAS_DATA.meta?.pageFormat      || 'A4';
        var pageOrientation = window.ZEUGNIS_CANVAS_DATA.meta?.pageOrientation || 'P';
        var fmt = PAGE_FORMATS[pageFormat] || PAGE_FORMATS.A4;
        var pageDim = pageOrientation === 'L'
            ? { w: fmt.h, h: fmt.w }
            : { w: fmt.w, h: fmt.h };

        canvas.style.width  = (pageDim.w * SCALE) + 'px';
        canvas.style.height = (pages.length * pageDim.h * SCALE + (pages.length - 1) * 24) + 'px';
        canvas.style.position = 'relative';
        canvas.style.background = '#fff';
        canvas.innerHTML = '';

        var offsetY = 0;
        pages.forEach(function (page, pageIdx) {
            if (pageIdx > 0) {
                // Page separator
                var sep = document.createElement('div');
                sep.className = 'zeugnis-page-separator';
                sep.style.top = (offsetY * SCALE) + 'px';
                sep.style.position = 'absolute';
                sep.style.width = '100%';
                sep.textContent = 'Seite ' + (pageIdx + 1);
                canvas.appendChild(sep);
                offsetY += 24 / SCALE; // separator height in mm equivalent
            }

            (page.elements || []).forEach(function (el) {
                var node = buildFillNode(el, fieldValues, tokens, offsetY);
                if (node) canvas.appendChild(node);
            });

            offsetY += pageDim.h;
        });
    }

    function buildFillNode(el, fieldValues, tokens, offsetY) {
        var x = el.x;
        var y = el.y + offsetY;
        var w = el.width;
        var h = el.height;

        switch (el.type) {
            case 'text_static':
                return buildStaticText(el, x, y, w, h, tokens);
            case 'placeholder':
                return buildStaticText(el, x, y, w, h, tokens, true);
            case 'text_free':
                return buildTextField(el, x, y, w, h, fieldValues);
            case 'date':
                return buildDateField(el, x, y, w, h, fieldValues);
            case 'grade':
                return buildGradeField(el, x, y, w, h, fieldValues);
            case 'checkbox':
                return buildCheckboxField(el, x, y, w, h, fieldValues);
            case 'signature':
                return buildSignatureBlock(el, x, y, w, h);
            case 'divider':
                return buildDivider(el, x, y, w);
            case 'image':
                return buildImage(el, x, y, w, h);
            case 'table':
                return buildTable(el, x, y, w, h, fieldValues);
            default:
                return null;
        }
    }

    // -------------------------------------------------------------------------
    // Element renderers
    // -------------------------------------------------------------------------

    function applyBaseStyles(el, node, x, y, w, h) {
        node.style.position = 'absolute';
        node.style.left   = (x * SCALE) + 'px';
        node.style.top    = (y * SCALE) + 'px';
        node.style.width  = (w * SCALE) + 'px';
        node.style.height = (h * SCALE) + 'px';
        node.style.boxSizing = 'border-box';
    }

    function applyTextStyles(el, node) {
        node.style.fontSize   = (el.fontSize || 11) * SCALE / 3 + 'px';
        node.style.fontFamily = el.fontFamily || 'sans-serif';
        node.style.fontWeight = (el.fontStyle || '').includes('B') ? 'bold' : 'normal';
        node.style.fontStyle  = (el.fontStyle || '').includes('I') ? 'italic' : 'normal';
        node.style.color      = el.color || '#000';
        node.style.textAlign  = { L: 'left', C: 'center', R: 'right' }[el.align] || 'left';
    }

    function resolvePlaceholders(text, tokens) {
        if (!text) return '';
        Object.keys(tokens).forEach(function (token) {
            text = text.split(token).join(tokens[token]);
        });
        return text;
    }

    function buildStaticText(el, x, y, w, h, tokens, isPlaceholder) {
        var div = document.createElement('div');
        applyBaseStyles(el, div, x, y, w, h);
        applyTextStyles(el, div);
        div.style.overflow = 'hidden';
        div.style.wordBreak = 'break-word';
        div.style.whiteSpace = 'pre-wrap';
        var content = isPlaceholder
            ? resolvePlaceholders(el.content || '', tokens)
            : (el.content || '');
        div.textContent = content;
        return div;
    }

    function buildTextField(el, x, y, w, h, fieldValues) {
        var wrapper = document.createElement('div');
        applyBaseStyles(el, wrapper, x, y, w, h);
        wrapper.className = 'zeugnis-fill-field';

        if (!canEdit) {
            var span = document.createElement('span');
            span.textContent = fieldValues[el.id] || '';
            applyTextStyles(el, span);
            span.style.display = 'block';
            wrapper.appendChild(span);
            return wrapper;
        }

        var isMultiline = h * SCALE > 30;
        var input;
        if (isMultiline) {
            input = document.createElement('textarea');
            input.rows = Math.floor(h * SCALE / 20);
        } else {
            input = document.createElement('input');
            input.type = 'text';
        }

        input.name = 'field_' + el.id;
        input.value = fieldValues[el.id] || '';
        input.placeholder = el.placeholder || '';
        applyTextStyles(el, input);
        input.style.width  = '100%';
        input.style.height = '100%';

        input.addEventListener('input', function () {
            scheduleAutosave(el.id, input.value);
        });

        wrapper.appendChild(input);
        return wrapper;
    }

    function buildDateField(el, x, y, w, h, fieldValues) {
        var wrapper = document.createElement('div');
        applyBaseStyles(el, wrapper, x, y, w, h);
        wrapper.className = 'zeugnis-fill-field';

        if (!canEdit) {
            var span = document.createElement('span');
            span.textContent = fieldValues[el.id] || '';
            applyTextStyles(el, span);
            wrapper.appendChild(span);
            return wrapper;
        }

        var input = document.createElement('input');
        input.type = 'date';
        input.name = 'field_' + el.id;
        input.value = fieldValues[el.id] || '';
        applyTextStyles(el, input);
        input.style.width  = '100%';
        input.style.height = '100%';
        input.style.border = '1px solid var(--color-primary, #0d9488)';
        input.style.background = 'rgba(13,148,136,0.05)';
        input.style.borderRadius = '2px';

        input.addEventListener('change', function () {
            scheduleAutosave(el.id, input.value);
        });

        wrapper.appendChild(input);
        return wrapper;
    }

    function buildGradeField(el, x, y, w, h, fieldValues) {
        var wrapper = document.createElement('div');
        applyBaseStyles(el, wrapper, x, y, w, h);
        wrapper.className = 'zeugnis-fill-field zeugnis-fill-field--grade';

        var label = document.createElement('label');
        label.textContent = el.label || 'Note';
        label.style.position = 'absolute';
        label.style.top = '-16px';
        label.style.left = '0';
        label.style.fontSize = '9px';
        label.style.color = '#888';
        wrapper.appendChild(label);

        if (!canEdit) {
            var span = document.createElement('span');
            span.textContent = fieldValues[el.id] || '—';
            applyTextStyles(el, span);
            span.style.display = 'flex';
            span.style.justifyContent = 'center';
            span.style.alignItems = 'center';
            span.style.height = '100%';
            wrapper.appendChild(span);
            return wrapper;
        }

        var select = document.createElement('select');
        select.name = 'field_' + el.id;
        applyTextStyles(el, select);
        select.style.width  = '100%';
        select.style.height = '100%';
        select.style.border = '1px solid var(--color-primary, #0d9488)';

        var emptyOpt = document.createElement('option');
        emptyOpt.value = '';
        emptyOpt.textContent = '—';
        select.appendChild(emptyOpt);

        ['1', '2', '3', '4', '5', '6', '+', '-'].forEach(function (grade) {
            var opt = document.createElement('option');
            opt.value = grade;
            opt.textContent = grade;
            if (fieldValues[el.id] === grade) opt.selected = true;
            select.appendChild(opt);
        });

        select.addEventListener('change', function () {
            scheduleAutosave(el.id, select.value);
        });

        wrapper.appendChild(select);
        return wrapper;
    }

    function buildCheckboxField(el, x, y, w, h, fieldValues) {
        var wrapper = document.createElement('div');
        applyBaseStyles(el, wrapper, x, y, w, h);
        wrapper.className = 'zeugnis-fill-field';
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '6px';

        var cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.name = 'field_' + el.id;
        cb.value = '1';
        cb.checked = !!fieldValues[el.id];
        cb.disabled = !canEdit;
        cb.style.width  = '16px';
        cb.style.height = '16px';
        cb.style.flexShrink = '0';

        var lbl = document.createElement('span');
        lbl.textContent = el.label || '';
        applyTextStyles(el, lbl);

        if (canEdit) {
            cb.addEventListener('change', function () {
                scheduleAutosave(el.id, cb.checked ? '1' : '0');
            });
        }

        wrapper.appendChild(cb);
        wrapper.appendChild(lbl);
        return wrapper;
    }

    function buildSignatureBlock(el, x, y, w, h) {
        var div = document.createElement('div');
        applyBaseStyles(el, div, x, y, w, h);
        div.style.border = '1px dashed #ccc';
        div.style.display = 'flex';
        div.style.flexDirection = 'column';
        div.style.justifyContent = 'flex-end';
        div.style.paddingBottom = '4px';

        var line = document.createElement('div');
        line.style.borderTop = '1px solid #000';
        line.style.margin = '0 8px';

        var lbl = document.createElement('div');
        lbl.textContent = el.label || 'Unterschrift';
        lbl.style.textAlign = 'center';
        lbl.style.fontSize = '9px';
        lbl.style.color = '#888';
        lbl.style.marginTop = '2px';

        div.appendChild(line);
        div.appendChild(lbl);
        return div;
    }

    function buildDivider(el, x, y, w) {
        var div = document.createElement('div');
        div.style.position = 'absolute';
        div.style.left    = (x * SCALE) + 'px';
        div.style.top     = (y * SCALE) + 'px';
        div.style.width   = (w * SCALE) + 'px';
        div.style.height  = '1px';
        div.style.borderTop = (el.lineWidth || 0.5) + 'px solid ' + (el.color || '#000');
        return div;
    }

    function buildImage(el, x, y, w, h) {
        if (!el.src) return null;
        var div = document.createElement('div');
        applyBaseStyles(el, div, x, y, w, h);
        div.style.overflow = 'hidden';

        var img = document.createElement('img');
        if (el.src.startsWith('zeugnis-img:')) {
            img.src = '/zeugnis/images/' + el.src.split(':')[1];
        } else {
            img.src = el.src;
        }
        img.alt = '';
        img.style.width  = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'contain';
        div.appendChild(img);
        return div;
    }

    function buildTable(el, x, y, w, h, fieldValues) {
        var div = document.createElement('div');
        applyBaseStyles(el, div, x, y, w, h);
        div.style.overflow = 'hidden';

        var cols = el.tableColumns || [];
        var rows = el.tableRows || 3;
        var colW = (w * SCALE) / Math.max(cols.length, 1);
        var rowH = (h * SCALE) / (rows + 1);

        var table = document.createElement('table');
        table.style.width  = '100%';
        table.style.height = '100%';
        table.style.borderCollapse = 'collapse';
        table.style.fontSize = (el.fontSize || 9) * SCALE / 3 + 'px';

        // Header
        var thead = document.createElement('thead');
        var headRow = document.createElement('tr');
        cols.forEach(function (col) {
            var th = document.createElement('th');
            th.textContent = col.label || '';
            th.style.border = '1px solid #999';
            th.style.background = '#eee';
            th.style.padding = '1px 3px';
            th.style.fontWeight = 'bold';
            th.style.width = colW + 'px';
            headRow.appendChild(th);
        });
        thead.appendChild(headRow);
        table.appendChild(thead);

        // Body
        var tbody = document.createElement('tbody');
        for (var r = 0; r < rows; r++) {
            var tr = document.createElement('tr');
            cols.forEach(function (col, c) {
                var td = document.createElement('td');
                td.style.border = '1px solid #ccc';
                td.style.padding = '1px 3px';

                var fieldKey = (el.id || 'table') + '_r' + r + '_c' + c;

                if (canEdit) {
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'field_' + fieldKey;
                    input.value = fieldValues[fieldKey] || '';
                    input.style.width  = '100%';
                    input.style.border = 'none';
                    input.style.outline = 'none';
                    input.style.fontSize = 'inherit';
                    input.addEventListener('input', (function (fk, inp) {
                        return function () { scheduleAutosave(fk, inp.value); };
                    })(fieldKey, input));
                    td.appendChild(input);
                } else {
                    td.textContent = fieldValues[fieldKey] || '';
                }

                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        }
        table.appendChild(tbody);
        div.appendChild(table);
        return div;
    }

    // -------------------------------------------------------------------------
    // Autosave
    // -------------------------------------------------------------------------

    function scheduleAutosave(fieldId, value) {
        if (!canEdit) return;
        clearTimeout(autosaveTimeout);
        if (saveStatus) {
            saveStatus.textContent = 'Änderungen …';
        }
        autosaveTimeout = setTimeout(function () {
            saveField(fieldId, value);
        }, 600);
    }

    function saveField(fieldId, value) {
        fetch('/zeugnis/' + instanceId + '/field', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({ field_id: fieldId, value: value }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (saveStatus) {
                if (data.ok) {
                    saveStatus.textContent = 'Gespeichert ✓';
                    // Mark field as saved
                    var input = document.querySelector('[name="field_' + fieldId + '"]');
                    if (input) {
                        input.closest('.zeugnis-fill-field')?.classList.add('zeugnis-fill-field--saved');
                        setTimeout(function () {
                            input.closest('.zeugnis-fill-field')?.classList.remove('zeugnis-fill-field--saved');
                        }, 2000);
                    }
                } else {
                    saveStatus.textContent = 'Fehler: ' + (data.error || 'Unbekannt');
                }
            }
        })
        .catch(function () {
            if (saveStatus) saveStatus.textContent = 'Verbindungsfehler.';
        });
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
