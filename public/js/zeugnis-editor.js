/**
 * Zeugnis Template Editor
 * Vanilla JS drag-and-drop WYSIWYG canvas editor for certificate templates.
 */
(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    var SCALE = 3;          // px per mm
    var PAGE_FORMATS = {
        A4: { w: 210, h: 297 },
        A3: { w: 297, h: 420 },
    };
    var ELEMENT_DEFAULTS = {
        text_static: { width: 80,  height: 10, content: 'Statischer Text', fontSize: 11, fontFamily: 'helvetica', fontStyle: '', color: '#000000', align: 'L' },
        text_free:   { width: 80,  height: 12, label: 'Freitextfeld', placeholder: 'Hier eingeben …', fontSize: 11, fontFamily: 'helvetica', fontStyle: '', color: '#000000', align: 'L', border: true },
        placeholder: { width: 80,  height: 10, content: '{{student_name}}', fontSize: 11, fontFamily: 'helvetica', fontStyle: '', color: '#000000', align: 'L' },
        image:       { width: 40,  height: 40, src: '' },
        divider:     { width: 180, height: 2,  color: '#000000', lineWidth: 0.5 },
        table:       { width: 150, height: 60, fontSize: 9, fontFamily: 'helvetica', tableColumns: [{label:'Fach'},{label:'Note'}], tableRows: 5 },
        grade:       { width: 20,  height: 10, label: 'Note', fontSize: 11, fontFamily: 'helvetica', fontStyle: 'B', color: '#000000' },
        checkbox:    { width: 60,  height: 8,  label: 'Checkbox', fontSize: 10, fontFamily: 'helvetica', fontStyle: '', color: '#000000' },
        date:        { width: 50,  height: 10, label: 'Datum', placeholder: 'TT.MM.JJJJ', fontSize: 11, fontFamily: 'helvetica', fontStyle: '', color: '#000000', align: 'L', border: false },
        signature:   { width: 70,  height: 25, label: 'Unterschrift' },
    };

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    var state = {
        pages: [],
        meta: {
            pageFormat: 'A4',
            pageOrientation: 'P',
        },
        currentPage: 0,
        selectedId: null,
        gridVisible: false,
    };

    // DOM references
    var canvas, wrapper, propsPanel, pageIndicator;
    var dragState = null;   // { type: 'move'|'resize', elementId, startX, startY, origX, origY, origW, origH, handle }
    var dragOver  = false;  // palette drag

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    function init() {
        canvas       = document.getElementById('zeugnis-canvas');
        wrapper      = document.getElementById('canvas-wrapper');
        propsPanel   = document.getElementById('props-panel');
        pageIndicator = document.getElementById('page-indicator');

        if (!canvas) return; // Not on editor page

        loadState(window.ZEUGNIS_INITIAL_STATE || { pages: [{ id: 'page-1', elements: [] }] });

        // Format/orientation controls update canvas size
        var formatSel = document.getElementById('page_format');
        var orientSel = document.getElementById('page_orientation');
        if (formatSel) formatSel.addEventListener('change', function () {
            state.meta.pageFormat = this.value;
            renderCanvas();
        });
        if (orientSel) orientSel.addEventListener('change', function () {
            state.meta.pageOrientation = this.value;
            renderCanvas();
        });

        // Toolbar buttons
        document.getElementById('btn-toggle-grid')?.addEventListener('click', toggleGrid);
        document.getElementById('btn-add-page')?.addEventListener('click', addPage);
        document.getElementById('btn-remove-page')?.addEventListener('click', removePage);
        document.getElementById('btn-prev-page')?.addEventListener('click', function () { switchPage(state.currentPage - 1); });
        document.getElementById('btn-next-page')?.addEventListener('click', function () { switchPage(state.currentPage + 1); });

        // Save button wires up canvas JSON before form submit
        var form = document.getElementById('zeugnis-editor-form');
        if (form) form.addEventListener('submit', function () {
            document.getElementById('template-canvas-input').value = JSON.stringify({ pages: state.pages });
        });

        // Palette drag start
        document.querySelectorAll('.zeugnis-palette-item').forEach(function (item) {
            item.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('text/plain', item.dataset.type);
                e.dataTransfer.effectAllowed = 'copy';
            });
        });

        // Canvas drop target
        canvas.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });
        canvas.addEventListener('drop', onCanvasDrop);

        // Global mouse events for drag/resize
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);

        // Click outside deselects
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.zeugnis-element') && !e.target.closest('#props-panel')) {
                deselectAll();
            }
        });

        // Image upload
        var btnUpload = document.getElementById('btn-upload-image');
        if (btnUpload) btnUpload.addEventListener('click', uploadImage);

        // Image gallery click (insert into canvas)
        document.querySelectorAll('.image-gallery-item').forEach(function (item) {
            item.addEventListener('click', function () {
                var imageId = item.dataset.imageId;
                var page = state.pages[state.currentPage];
                if (!page) return;
                page.elements.push(Object.assign(
                    { id: genId(), type: 'image', x: 10, y: 10 },
                    ELEMENT_DEFAULTS['image'],
                    { src: 'zeugnis-img:' + imageId }
                ));
                renderCanvas();
                renderProps();
            });
        });

        // Token list click (insert token into selected placeholder/text)
        document.querySelectorAll('.zeugnis-token-list li').forEach(function (li) {
            li.addEventListener('click', function () {
                if (!state.selectedId) return;
                var el = findElement(state.selectedId);
                if (!el) return;
                if (el.type === 'placeholder' || el.type === 'text_static') {
                    el.content = li.dataset.token;
                    renderCanvas();
                    renderProps();
                }
            });
        });

        // Preview mode: read-only
        if (window.ZEUGNIS_PREVIEW_MODE) {
            canvas.style.pointerEvents = 'none';
        }

        renderCanvas();
    }

    // -------------------------------------------------------------------------
    // State helpers
    // -------------------------------------------------------------------------

    function loadState(json) {
        var src = (typeof json === 'string') ? JSON.parse(json) : json;
        state.pages = src.pages || [{ id: 'page-1', elements: [] }];
        state.currentPage = 0;
        // Read format/orientation from selects
        var formatSel = document.getElementById('page_format');
        var orientSel = document.getElementById('page_orientation');
        state.meta.pageFormat = formatSel ? formatSel.value : 'A4';
        state.meta.pageOrientation = orientSel ? orientSel.value : 'P';
    }

    function genId() {
        return 'el-' + Math.random().toString(36).slice(2, 10);
    }

    function findElement(id) {
        for (var i = 0; i < state.pages.length; i++) {
            for (var j = 0; j < state.pages[i].elements.length; j++) {
                if (state.pages[i].elements[j].id === id) return state.pages[i].elements[j];
            }
        }
        return null;
    }

    function getPageDimensions() {
        var fmt = PAGE_FORMATS[state.meta.pageFormat] || PAGE_FORMATS.A4;
        if (state.meta.pageOrientation === 'L') return { w: fmt.h, h: fmt.w };
        return { w: fmt.w, h: fmt.h };
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    function renderCanvas() {
        var dim = getPageDimensions();
        var pxW = dim.w * SCALE;
        var pxH = dim.h * SCALE;

        canvas.style.width  = pxW + 'px';
        canvas.style.height = pxH + 'px';

        if (state.gridVisible) {
            canvas.classList.add('zeugnis-canvas--grid');
        } else {
            canvas.classList.remove('zeugnis-canvas--grid');
        }

        // Clear and re-render current page's elements
        canvas.innerHTML = '';
        var page = state.pages[state.currentPage];
        if (page) {
            page.elements.forEach(function (el) {
                canvas.appendChild(buildElementNode(el));
            });
        }

        // Page indicator
        if (pageIndicator) {
            pageIndicator.textContent = 'Seite ' + (state.currentPage + 1) + ' / ' + state.pages.length;
        }
    }

    function buildElementNode(el) {
        var div = document.createElement('div');
        div.className = 'zeugnis-element zeugnis-element--' + el.type;
        div.dataset.id = el.id;
        div.style.left   = (el.x * SCALE) + 'px';
        div.style.top    = (el.y * SCALE) + 'px';
        div.style.width  = (el.width * SCALE) + 'px';
        div.style.height = (el.height * SCALE) + 'px';

        if (el.id === state.selectedId) div.classList.add('zeugnis-element--selected');

        // Inner preview content
        var inner = document.createElement('div');
        inner.className = 'zeugnis-element__preview';
        inner.style.fontSize   = (el.fontSize || 11) * SCALE / 3 + 'px';
        inner.style.fontFamily = el.fontFamily || 'sans-serif';
        inner.style.fontWeight = (el.fontStyle || '').includes('B') ? 'bold' : 'normal';
        inner.style.fontStyle  = (el.fontStyle || '').includes('I') ? 'italic' : 'normal';
        inner.style.color      = el.color || '#000';
        inner.style.textAlign  = alignToCSS(el.align);

        switch (el.type) {
            case 'text_static':
                inner.textContent = el.content || 'Text';
                break;
            case 'placeholder':
                inner.textContent = el.content || '{{…}}';
                inner.style.background = 'rgba(13,148,136,0.08)';
                inner.style.borderRadius = '2px';
                break;
            case 'text_free':
                inner.style.background = 'rgba(13,148,136,0.08)';
                inner.style.border = '1px dashed var(--color-primary, #0d9488)';
                inner.style.borderRadius = '2px';
                inner.textContent = el.label || 'Freitextfeld';
                break;
            case 'date':
                inner.style.background = 'rgba(59,130,246,0.08)';
                inner.style.border = '1px dashed #3b82f6';
                inner.style.borderRadius = '2px';
                inner.textContent = el.label || 'Datum';
                break;
            case 'grade':
                inner.style.background = 'rgba(249,115,22,0.1)';
                inner.style.border = '1px solid rgba(249,115,22,0.4)';
                inner.style.borderRadius = '2px';
                inner.style.justifyContent = 'center';
                inner.style.fontWeight = 'bold';
                inner.textContent = el.label || 'Note';
                break;
            case 'checkbox':
                inner.innerHTML = '☐ ' + escHtml(el.label || 'Checkbox');
                break;
            case 'signature':
                inner.style.borderTop = '1px solid #000';
                inner.style.alignItems = 'flex-end';
                inner.style.paddingBottom = '2px';
                inner.style.color = '#888';
                inner.style.fontSize = '9px';
                inner.textContent = el.label || 'Unterschrift';
                break;
            case 'divider':
                div.style.borderTop = '2px solid ' + (el.color || '#000');
                div.style.height = '2px';
                break;
            case 'image':
                if (el.src) {
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
                    inner.appendChild(img);
                } else {
                    inner.textContent = '[Bild]';
                    inner.style.background = '#eee';
                    inner.style.justifyContent = 'center';
                }
                break;
            case 'table':
                inner.style.flexDirection = 'column';
                inner.style.fontSize = '9px';
                inner.style.background = 'rgba(0,0,0,0.03)';
                inner.style.border = '1px solid #ccc';
                var cols = (el.tableColumns || []).map(function (c) { return c.label || ''; }).join(' | ');
                inner.textContent = 'Tabelle: ' + cols + ' ('+  (el.tableRows || 3) + ' Zeilen)';
                break;
        }

        div.appendChild(inner);

        // Resize handles (not for dividers)
        if (el.type !== 'divider' && !window.ZEUGNIS_PREVIEW_MODE) {
            ['nw', 'ne', 'sw', 'se'].forEach(function (dir) {
                var h = document.createElement('div');
                h.className = 'resize-handle resize-handle--' + dir;
                h.dataset.handle = dir;
                div.appendChild(h);
            });
        }

        // Mouse events
        if (!window.ZEUGNIS_PREVIEW_MODE) {
            div.addEventListener('mousedown', onElementMouseDown);
        }
        div.addEventListener('click', function (e) {
            e.stopPropagation();
            selectElement(el.id);
        });

        return div;
    }

    function alignToCSS(align) {
        return { L: 'left', C: 'center', R: 'right' }[align] || 'left';
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // -------------------------------------------------------------------------
    // Element selection & properties panel
    // -------------------------------------------------------------------------

    function selectElement(id) {
        state.selectedId = id;
        // Update DOM classes
        document.querySelectorAll('.zeugnis-element').forEach(function (el) {
            el.classList.toggle('zeugnis-element--selected', el.dataset.id === id);
        });
        renderProps();
    }

    function deselectAll() {
        state.selectedId = null;
        document.querySelectorAll('.zeugnis-element').forEach(function (el) {
            el.classList.remove('zeugnis-element--selected');
        });
        if (propsPanel) propsPanel.innerHTML = '<p class="text-muted" style="font-size:var(--font-size-sm)">Element auswählen zum Bearbeiten.</p>';
        document.getElementById('token-list-section') && (document.getElementById('token-list-section').style.display = 'none');
        document.getElementById('image-upload-section') && (document.getElementById('image-upload-section').style.display = 'none');
    }

    function renderProps() {
        if (!propsPanel || !state.selectedId) return;
        var el = findElement(state.selectedId);
        if (!el) return;

        // Show/hide token list and image upload
        var tokenSection = document.getElementById('token-list-section');
        var imageSection = document.getElementById('image-upload-section');
        if (tokenSection) tokenSection.style.display = (el.type === 'placeholder' || el.type === 'text_static') ? 'block' : 'none';
        if (imageSection) imageSection.style.display = el.type === 'image' ? 'block' : 'none';

        var html = '<h3>Eigenschaften</h3>';
        html += prop('x', 'X (mm)', 'number', el.x) + prop('y', 'Y (mm)', 'number', el.y);
        html += prop('width', 'Breite (mm)', 'number', el.width) + prop('height', 'Höhe (mm)', 'number', el.height);

        if (el.type === 'text_static' || el.type === 'placeholder') {
            html += propText('content', 'Inhalt', el.content || '');
        }
        if (el.type === 'text_free' || el.type === 'grade' || el.type === 'checkbox' || el.type === 'date' || el.type === 'signature') {
            html += prop('label', 'Beschriftung', 'text', el.label || '');
        }
        if (el.type === 'text_free' || el.type === 'date') {
            html += prop('placeholder', 'Platzhaltertext', 'text', el.placeholder || '');
        }
        if (['text_static', 'text_free', 'placeholder', 'grade', 'checkbox', 'date'].includes(el.type)) {
            html += prop('fontSize', 'Schriftgröße', 'number', el.fontSize || 11);
            html += propSelect('fontFamily', 'Schriftart', ['helvetica','times','courier'], el.fontFamily || 'helvetica');
            html += propSelect('fontStyle', 'Stil', ['', 'B', 'I', 'BI'], el.fontStyle || '', ['Normal', 'Fett', 'Kursiv', 'Fett+Kursiv']);
            html += propSelect('align', 'Ausrichtung', ['L','C','R'], el.align || 'L', ['Links','Mitte','Rechts']);
            html += propColor('color', 'Farbe', el.color || '#000000');
        }
        if (el.type === 'divider') {
            html += propColor('color', 'Farbe', el.color || '#000000');
            html += prop('lineWidth', 'Linienstärke (pt)', 'number', el.lineWidth || 0.5);
        }
        if (el.type === 'table') {
            html += prop('tableRows', 'Anzahl Zeilen', 'number', el.tableRows || 3);
            html += '<div class="zeugnis-props-group"><label>Spalten (eine pro Zeile)</label>';
            html += '<textarea data-prop="tableColumnsRaw" class="form-control" rows="4" style="font-size:11px">';
            html += escHtml((el.tableColumns || []).map(function (c) { return c.label; }).join('\n'));
            html += '</textarea></div>';
        }

        html += '<div class="zeugnis-props-group" style="margin-top:var(--spacing-md)">';
        html += '<button type="button" id="btn-delete-element" class="btn btn-sm btn-danger">Element löschen</button>';
        html += '</div>';

        propsPanel.innerHTML = html;

        // Re-attach token/image sections after innerHTML overwrite
        var tokenSection2 = document.getElementById('token-list-section');
        if (tokenSection2) {
            propsPanel.appendChild(tokenSection2);
            tokenSection2.style.display = (el.type === 'placeholder' || el.type === 'text_static') ? 'block' : 'none';
        }
        var imageSection2 = document.getElementById('image-upload-section');
        if (imageSection2) {
            propsPanel.appendChild(imageSection2);
            imageSection2.style.display = el.type === 'image' ? 'block' : 'none';
        }

        // Bind prop change events
        propsPanel.querySelectorAll('[data-prop]').forEach(function (input) {
            input.addEventListener('change', onPropChange);
            input.addEventListener('input', onPropChange);
        });

        // Delete button
        document.getElementById('btn-delete-element')?.addEventListener('click', function () {
            deleteElement(state.selectedId);
        });
    }

    function prop(key, label, type, value) {
        return '<div class="zeugnis-props-group"><label>' + escHtml(label) + '</label>' +
            '<input type="' + type + '" data-prop="' + key + '" class="form-control" value="' + escHtml(String(value)) + '" step="0.5"></div>';
    }

    function propText(key, label, value) {
        return '<div class="zeugnis-props-group"><label>' + escHtml(label) + '</label>' +
            '<textarea data-prop="' + key + '" class="form-control" rows="2">' + escHtml(value) + '</textarea></div>';
    }

    function propSelect(key, label, options, current, optLabels) {
        var html = '<div class="zeugnis-props-group"><label>' + escHtml(label) + '</label><select data-prop="' + key + '" class="form-control">';
        options.forEach(function (o, i) {
            var lbl = optLabels ? optLabels[i] : o;
            html += '<option value="' + escHtml(o) + '"' + (o === current ? ' selected' : '') + '>' + escHtml(lbl) + '</option>';
        });
        html += '</select></div>';
        return html;
    }

    function propColor(key, label, value) {
        return '<div class="zeugnis-props-group"><label>' + escHtml(label) + '</label>' +
            '<input type="color" data-prop="' + key + '" class="zeugnis-color-picker" value="' + escHtml(value) + '"></div>';
    }

    function onPropChange(e) {
        var el = findElement(state.selectedId);
        if (!el) return;
        var key = e.target.dataset.prop;
        var val = e.target.value;

        if (key === 'tableColumnsRaw') {
            el.tableColumns = val.split('\n').filter(function (l) { return l.trim(); }).map(function (l) { return { label: l.trim() }; });
        } else if (['x', 'y', 'width', 'height', 'fontSize', 'lineWidth', 'tableRows'].includes(key)) {
            el[key] = parseFloat(val) || 0;
        } else {
            el[key] = val;
        }

        renderCanvas();
        // Re-select to show updated handles
        selectElement(state.selectedId);
    }

    // -------------------------------------------------------------------------
    // Drag and Drop from palette
    // -------------------------------------------------------------------------

    function onCanvasDrop(e) {
        e.preventDefault();
        var type = e.dataTransfer.getData('text/plain');
        if (!type || !ELEMENT_DEFAULTS[type]) return;

        var rect = canvas.getBoundingClientRect();
        var xPx = e.clientX - rect.left;
        var yPx = e.clientY - rect.top;
        var xMm = Math.max(0, Math.round(xPx / SCALE * 2) / 2);
        var yMm = Math.max(0, Math.round(yPx / SCALE * 2) / 2);

        addElement(type, xMm, yMm);
    }

    function addElement(type, x, y) {
        var page = state.pages[state.currentPage];
        if (!page) return;
        var el = Object.assign({ id: genId(), type: type, x: x, y: y }, ELEMENT_DEFAULTS[type]);
        page.elements.push(el);
        renderCanvas();
        selectElement(el.id);
    }

    // -------------------------------------------------------------------------
    // Element drag (move) & resize via mouse events
    // -------------------------------------------------------------------------

    function onElementMouseDown(e) {
        var div = e.currentTarget;
        var id  = div.dataset.id;

        // Resize handle?
        if (e.target.classList.contains('resize-handle')) {
            var el = findElement(id);
            if (!el) return;
            dragState = {
                type: 'resize',
                elementId: id,
                handle: e.target.dataset.handle,
                startX: e.clientX,
                startY: e.clientY,
                origX: el.x,
                origY: el.y,
                origW: el.width,
                origH: el.height,
            };
            e.preventDefault();
            e.stopPropagation();
            return;
        }

        // Move
        var el = findElement(id);
        if (!el) return;
        dragState = {
            type: 'move',
            elementId: id,
            startX: e.clientX,
            startY: e.clientY,
            origX: el.x,
            origY: el.y,
        };
        e.preventDefault();
        e.stopPropagation();
    }

    function onMouseMove(e) {
        if (!dragState) return;

        var el = findElement(dragState.elementId);
        if (!el) return;

        var dxPx = e.clientX - dragState.startX;
        var dyPx = e.clientY - dragState.startY;
        var dxMm = dxPx / SCALE;
        var dyMm = dyPx / SCALE;

        if (dragState.type === 'move') {
            el.x = Math.max(0, Math.round((dragState.origX + dxMm) * 2) / 2);
            el.y = Math.max(0, Math.round((dragState.origY + dyMm) * 2) / 2);
        } else if (dragState.type === 'resize') {
            var h = dragState.handle;
            var newW = dragState.origW;
            var newH = dragState.origH;
            var newX = dragState.origX;
            var newY = dragState.origY;

            if (h.includes('e')) newW = Math.max(5, dragState.origW + dxMm);
            if (h.includes('s')) newH = Math.max(3, dragState.origH + dyMm);
            if (h.includes('w')) { newX = dragState.origX + dxMm; newW = Math.max(5, dragState.origW - dxMm); }
            if (h.includes('n')) { newY = dragState.origY + dyMm; newH = Math.max(3, dragState.origH - dyMm); }

            el.x = Math.round(newX * 2) / 2;
            el.y = Math.round(newY * 2) / 2;
            el.width  = Math.round(newW * 2) / 2;
            el.height = Math.round(newH * 2) / 2;
        }

        // Live update DOM position for performance (avoid full re-render while dragging)
        var node = canvas.querySelector('[data-id="' + dragState.elementId + '"]');
        if (node) {
            node.style.left   = (el.x * SCALE) + 'px';
            node.style.top    = (el.y * SCALE) + 'px';
            node.style.width  = (el.width * SCALE) + 'px';
            node.style.height = (el.height * SCALE) + 'px';
        }
    }

    function onMouseUp() {
        if (!dragState) return;
        // Full re-render to ensure resize handles and props panel are updated
        renderCanvas();
        if (state.selectedId) {
            selectElement(state.selectedId);
            renderProps();
        }
        dragState = null;
    }

    // -------------------------------------------------------------------------
    // Element deletion
    // -------------------------------------------------------------------------

    function deleteElement(id) {
        var page = state.pages[state.currentPage];
        if (!page) return;
        page.elements = page.elements.filter(function (el) { return el.id !== id; });
        state.selectedId = null;
        renderCanvas();
        if (propsPanel) propsPanel.innerHTML = '<p class="text-muted" style="font-size:var(--font-size-sm)">Element auswählen zum Bearbeiten.</p>';
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    function addPage() {
        state.pages.push({ id: 'page-' + (state.pages.length + 1), elements: [] });
        switchPage(state.pages.length - 1);
    }

    function removePage() {
        if (state.pages.length <= 1) {
            alert('Eine Vorlage muss mindestens eine Seite haben.');
            return;
        }
        if (!confirm('Seite ' + (state.currentPage + 1) + ' löschen?')) return;
        state.pages.splice(state.currentPage, 1);
        switchPage(Math.min(state.currentPage, state.pages.length - 1));
    }

    function switchPage(idx) {
        if (idx < 0 || idx >= state.pages.length) return;
        state.currentPage = idx;
        state.selectedId = null;
        renderCanvas();
        if (propsPanel) propsPanel.innerHTML = '<p class="text-muted" style="font-size:var(--font-size-sm)">Element auswählen zum Bearbeiten.</p>';
    }

    // -------------------------------------------------------------------------
    // Grid
    // -------------------------------------------------------------------------

    function toggleGrid() {
        state.gridVisible = !state.gridVisible;
        var btn = document.getElementById('btn-toggle-grid');
        if (btn) btn.classList.toggle('btn-secondary', !state.gridVisible);
        if (btn) btn.classList.toggle('btn-primary', state.gridVisible);
        renderCanvas();
    }

    // -------------------------------------------------------------------------
    // Image upload
    // -------------------------------------------------------------------------

    function uploadImage() {
        var input = document.getElementById('image-upload-input');
        var status = document.getElementById('image-upload-status');
        if (!input || !input.files.length) {
            if (status) status.textContent = 'Bitte Bild auswählen.';
            return;
        }
        if (!window.ZEUGNIS_IMAGE_UPLOAD_URL) {
            if (status) status.textContent = 'Bitte Vorlage zuerst speichern.';
            return;
        }

        var formData = new FormData();
        formData.append('image', input.files[0]);

        if (status) status.textContent = 'Hochladen …';

        fetch(window.ZEUGNIS_IMAGE_UPLOAD_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.error) {
                if (status) status.textContent = data.error;
                return;
            }
            if (status) status.textContent = 'Hochgeladen!';
            input.value = '';

            // Add image element to canvas
            var page = state.pages[state.currentPage];
            if (page) {
                var el = Object.assign({ id: genId(), type: 'image', x: 10, y: 10 }, ELEMENT_DEFAULTS['image'], { src: data.src });
                page.elements.push(el);
                renderCanvas();
                selectElement(el.id);
            }

            // Add to gallery
            var gallery = document.getElementById('image-gallery');
            if (gallery && data.url) {
                var item = document.createElement('div');
                item.className = 'image-gallery-item';
                item.dataset.imageId = data.id;
                item.innerHTML = '<img src="' + data.url + '" alt="">';
                item.addEventListener('click', function () {
                    var p = state.pages[state.currentPage];
                    if (!p) return;
                    p.elements.push(Object.assign({ id: genId(), type: 'image', x: 10, y: 10 }, ELEMENT_DEFAULTS['image'], { src: 'zeugnis-img:' + data.id }));
                    renderCanvas();
                });
                gallery.appendChild(item);
            }
        })
        .catch(function () {
            if (status) status.textContent = 'Fehler beim Hochladen.';
        });
    }

    // -------------------------------------------------------------------------
    // Keyboard shortcuts
    // -------------------------------------------------------------------------

    document.addEventListener('keydown', function (e) {
        if (!state.selectedId) return;
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

        if (e.key === 'Delete' || e.key === 'Backspace') {
            deleteElement(state.selectedId);
            return;
        }

        var el = findElement(state.selectedId);
        if (!el) return;
        var step = e.shiftKey ? 10 : 1;
        if (e.key === 'ArrowLeft')  { el.x = Math.max(0, el.x - step); e.preventDefault(); }
        if (e.key === 'ArrowRight') { el.x += step; e.preventDefault(); }
        if (e.key === 'ArrowUp')    { el.y = Math.max(0, el.y - step); e.preventDefault(); }
        if (e.key === 'ArrowDown')  { el.y += step; e.preventDefault(); }
        renderCanvas();
        selectElement(state.selectedId);
        renderProps();
    });

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
