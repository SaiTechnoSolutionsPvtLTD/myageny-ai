@extends('layouts.app')

@section('title', 'OVP Form Builder - ' . $product->package_name)

@include('pages.products.style')

@push('styles')
<style>
.ovpf-shell { display: grid; gap: 18px; }
.ovpf-head-card, .ovpf-table-card, .ovpf-preview-card { background: #fff; border: 1px solid #e1dee3; border-radius: 16px; }
.ovpf-head-card { padding: 20px 24px; display: flex; justify-content: space-between; gap: 16px; align-items: center; }
.ovpf-title { font-size: 22px; font-weight: 800; color: #121212; margin: 0; }
.ovpf-sub { color: #7c7c7c; font-size: 13px; margin-top: 6px; }
.ovpf-chip-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.ovpf-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: #f8f4ef; color: #7a4b25; font-size: 12px; font-weight: 700; }
.ovpf-toolbar { padding: 18px 20px; border-bottom: 1px solid #f1f1f1; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
.ovpf-table-wrap { overflow-x: auto; }
.ovpf-table { width: 100%; border-collapse: collapse; }
.ovpf-table th, .ovpf-table td { padding: 14px 16px; border-bottom: 1px solid #f5f4f7; text-align: left; font-size: 13px; vertical-align: top; }
.ovpf-table th { color: #8a8a8a; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
.ovpf-type { display: inline-flex; padding: 5px 10px; border-radius: 999px; background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 700; }
.ovpf-type.select, .ovpf-type.radio, .ovpf-type.checkbox { background: #ecfdf5; color: #047857; }
.ovpf-type.file { background: #fff7ed; color: #c2410c; }
.ovpf-req { color: #dc2626; font-weight: 700; }
.ovpf-muted { color: #8a8a8a; }
.ovpf-actions { display: flex; gap: 8px; }
.ovpf-icon-btn { width: 32px; height: 32px; border-radius: 10px; border: 1px solid #e1dee3; background: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.ovpf-icon-btn:hover { background: #f8f8f8; }
.ovpf-drag-cell { width: 52px; }
.ovpf-drag-handle { width: 32px; height: 32px; border-radius: 10px; border: 1px dashed #d6d1d8; background: #fcfcfc; color: #7c7c7c; display: inline-flex; align-items: center; justify-content: center; cursor: grab; user-select: none; font-size: 16px; }
.ovpf-drag-handle:active { cursor: grabbing; }
.ovpf-row-draggable { cursor: move; }
.ovpf-row-draggable.dragging { opacity: .45; background: #fffaf5; }
.ovpf-row-draggable.drag-over { outline: 2px dashed #fe5f04; outline-offset: -2px; background: #fff7ed; }
.ovpf-empty { padding: 50px 20px; text-align: center; color: #8a8a8a; }
.ovpf-preview-card { padding: 20px; }
.ovpf-preview-title { font-size: 16px; font-weight: 800; margin: 0 0 14px; color: #121212; }
.ovpf-preview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
.ovpf-preview-field { border: 1px dashed #d7d2d9; border-radius: 12px; padding: 14px; background: #fcfcfc; }
.ovpf-preview-label { font-size: 12px; font-weight: 700; color: #3a3a3a; margin-bottom: 8px; }
.ovpf-preview-box { border: 1px solid #e1dee3; border-radius: 10px; background: #fff; padding: 10px 12px; color: #9e9e9e; font-size: 13px; min-height: 42px; }
.ovpf-overlay { position: fixed; inset: 0; background: rgba(16, 18, 27, .48); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
.ovpf-overlay.open { display: flex; }
.ovpf-modal { width: min(760px, 100%); max-height: 92vh; overflow-y: auto; background: #fff; border-radius: 18px; box-shadow: 0 28px 80px rgba(0, 0, 0, .2); }
.ovpf-modal-head, .ovpf-modal-foot { padding: 18px 22px; border-bottom: 1px solid #f0eef2; display: flex; justify-content: space-between; align-items: center; }
.ovpf-modal-foot { border-bottom: none; border-top: 1px solid #f0eef2; }
.ovpf-modal-body { padding: 22px; display: grid; gap: 16px; }
.ovpf-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.ovpf-group { display: grid; gap: 6px; }
.ovpf-label { font-size: 12px; font-weight: 700; color: #2f2f2f; }
.ovpf-input, .ovpf-select, .ovpf-textarea { width: 100%; border: 1px solid #dfd8e3; border-radius: 12px; padding: 10px 12px; font: inherit; background: #fff; }
.ovpf-textarea { min-height: 90px; resize: vertical; }
.ovpf-checks { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
.ovpf-option-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; margin-bottom: 8px; }
.ovpf-remove { width: 38px; border: 1px solid #fecaca; background: #fff5f5; color: #dc2626; border-radius: 10px; cursor: pointer; }
.ovpf-help { font-size: 11px; color: #8a8a8a; }
@media (max-width: 700px) {
    .ovpf-head-card, .ovpf-toolbar { flex-direction: column; align-items: flex-start; }
    .ovpf-grid-2, .ovpf-option-row { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<main class="main-content">
    <header class="top-header">
        <div class="breadcrumbs">
            <span class="crumb-item">Home</span>
            <span class="crumb-sep">/</span>
            <a href="{{ route('products.index') }}" class="crumb-item">Product Master</a>
            <span class="crumb-sep">/</span>
            <a href="{{ route('products.show', $product) }}" class="crumb-item">{{ $product->package_name }}</a>
            <span class="crumb-sep">/</span>
            <span class="crumb-item active">OVP Form Builder</span>
        </div>
        <div class="header-actions">
            <a href="{{ route('products.show', $product) }}" class="pm-btn pm-btn--ghost">Back to Product</a>
            <button type="button" class="pm-btn pm-btn--primary" onclick="OVPForm.openCreate()">Add OVP Field</button>
        </div>
    </header>

    <div class="pm-page-body">
        <div class="ovpf-shell">
            <section class="ovpf-head-card">
                <div>
                    <h1 class="ovpf-title">{{ $product->package_name }} OVP Form</h1>

                    <div class="ovpf-chip-row">
                        <span class="ovpf-chip">Product ID #{{ $product->id }}</span>
                        <span class="ovpf-chip">{{ $product->category?->name ?? 'No Category' }}</span>
                        <span class="ovpf-chip">{{ $product->ovpFormFields->count() }} field(s) configured</span>
                    </div>
                </div>
                <div class="ovpf-muted">Supported: text, number, textarea, radio, checkbox, select, date, file upload</div>
            </section>

            <section class="ovpf-table-card">
                <div class="ovpf-toolbar">
                    <div>
                        <strong>Configured Fields</strong>
                        <div class="ovpf-muted" id="ovpf-count">Loading...</div>
                    </div>
                    <button type="button" class="pm-btn pm-btn--outline pm-btn--sm" onclick="OVPForm.openCreate()">New Field</button>
                </div>
                <div class="ovpf-table-wrap">
                    <table class="ovpf-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th></th>
                                <th>Label</th>
                                <th>Type</th>
                                <th>Field Key</th>
                                <th>OVP</th>
                                <th>Production Initiate</th>
                                <th>Rules</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ovpf-tbody">
                            <tr><td colspan="10" class="ovpf-empty">Loading fields...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="ovpf-preview-card">
                <h2 class="ovpf-preview-title">Live Preview</h2>
                <div id="ovpf-preview" class="ovpf-preview-grid"></div>
            </section>
        </div>
    </div>
</main>

<div class="ovpf-overlay" id="ovpf-modal-wrap" onclick="OVPForm.backdrop(event)">
    <div class="ovpf-modal" onclick="event.stopPropagation()">
        <div class="ovpf-modal-head">
            <strong id="ovpf-modal-title">Add OVP Field</strong>
            <button type="button" class="ovpf-icon-btn" onclick="OVPForm.close()">✕</button>
        </div>
        <div class="ovpf-modal-body">
            <input type="hidden" id="ovpf-field-id">
            <div class="ovpf-grid-2">
                <div class="ovpf-group">
                    <label class="ovpf-label">Field Label</label>
                    <input id="ovpf-label" class="ovpf-input" type="text" placeholder="e.g. Domain Name">
                </div>
                <div class="ovpf-group">
                    <label class="ovpf-label">Field Type</label>
                    <select id="ovpf-type" class="ovpf-select" onchange="OVPForm.onTypeChange()">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                        <option value="radio">Radio</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="date">Date</option>
                        <option value="file">File Upload</option>
                    </select>
                </div>
            </div>

            <div class="ovpf-grid-2">
                <div class="ovpf-group">
                    <label class="ovpf-label">Placeholder</label>
                    <input id="ovpf-placeholder" class="ovpf-input" type="text" placeholder="Optional helper text inside input">
                </div>
                <div class="ovpf-group">
                    <label class="ovpf-label">Default Value</label>
                    <input id="ovpf-default" class="ovpf-input" type="text" placeholder="Optional default value">
                </div>
            </div>

            <div class="ovpf-group">
                <label class="ovpf-label">Help Text</label>
                <textarea id="ovpf-help" class="ovpf-textarea" placeholder="Short note for production team..."></textarea>
            </div>

            <div class="ovpf-grid-2">
                <div class="ovpf-group">
                    <label class="ovpf-label">Sort Order</label>
                    <input id="ovpf-sort" class="ovpf-input" type="number" min="0" value="0">
                </div>
                <div class="ovpf-group">
                    <label class="ovpf-label">Validation</label>
                    <div class="ovpf-grid-2">
                        <input id="ovpf-min" class="ovpf-input" type="number" placeholder="Min">
                        <input id="ovpf-max" class="ovpf-input" type="number" placeholder="Max">
                    </div>
                </div>
            </div>

            <div class="ovpf-checks">
                <label><input id="ovpf-required" type="checkbox"> Required</label>
                <label><input id="ovpf-active" type="checkbox" checked> Active</label>
                <label><input id="ovpf-use-ovp" type="checkbox"> Use OVP</label>
                <label><input id="ovpf-production-initiate" type="checkbox"> Use this production initiate</label>
            </div>

            <div id="ovpf-options-wrap" style="display:none;">
                <div class="ovpf-group">
                    <label class="ovpf-label">Options</label>
                    <div id="ovpf-options-list"></div>
                    <button type="button" class="pm-btn pm-btn--outline pm-btn--sm" onclick="OVPForm.addOption()">Add Option</button>
                    <div class="ovpf-help">Label user-ku kaanum, value DB-la store aagum.</div>
                </div>
            </div>
        </div>
        <div class="ovpf-modal-foot">
            <button type="button" class="pm-btn pm-btn--ghost" onclick="OVPForm.close()">Cancel</button>
            <button type="button" class="pm-btn pm-btn--primary" onclick="OVPForm.save()">Save Field</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.OVPFormConfig = {
    productId: {{ $product->id }},
    apiBase: '{{ url('/api/products/' . $product->id . '/ovp-form-fields') }}',
    reorderUrl: '{{ url('/api/products/' . $product->id . '/ovp-form-fields/reorder') }}',
    schemaUrl: '{{ url('/api/products/' . $product->id . '/ovp-form-schema') }}',
    csrf: @json(csrf_token()),
};

(function () {
    const cfg = window.OVPFormConfig;
    const state = { fields: [], dragId: null, savingOrder: false };

    function api(path = '', method = 'GET', body = null) {
        return fetch(cfg.apiBase + path, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: body ? JSON.stringify(body) : null,
        }).then(async res => {
            const json = await res.json();
            if (!res.ok) throw json;
            return json;
        });
    }

    function esc(str) {
        return String(str ?? '').replace(/[&<>"]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
    }

    function typeClass(type) {
        return ['select', 'radio', 'checkbox', 'file'].includes(type) ? type : '';
    }

    function renderTable() {
        const tbody = document.getElementById('ovpf-tbody');
        document.getElementById('ovpf-count').textContent = `${state.fields.length} field(s) configured`;

        if (!state.fields.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="ovpf-empty"></td></tr>';
            renderPreview();
            return;
        }

        tbody.innerHTML = state.fields.map((field, index) => {
            const rules = [];
            if (field.is_required) rules.push('Required');
            if (field.validation_rules?.min !== undefined && field.validation_rules?.min !== null) rules.push('Min ' + field.validation_rules.min);
            if (field.validation_rules?.max !== undefined && field.validation_rules?.max !== null) rules.push('Max ' + field.validation_rules.max);
            if (Array.isArray(field.options) && field.options.length) rules.push(field.options.length + ' option(s)');

            return `<tr class="ovpf-row-draggable" data-field-id="${field.id}" draggable="true">
                <td>${index + 1}</td>
                <td class="ovpf-drag-cell"><span class="ovpf-drag-handle" title="Drag to sort">⋮⋮</span></td>
                <td><strong>${esc(field.label)}</strong><div class="ovpf-muted">${esc(field.help_text || '')}</div></td>
                <td><span class="ovpf-type ${typeClass(field.field_type)}">${esc(field.field_type)}</span></td>
                <td><code>${esc(field.field_name)}</code></td>
                <td>${field.use_in_ovp ? 'Yes' : '<span class="ovpf-muted">No</span>'}</td>
                <td>${field.use_in_production_initiation ? 'Yes' : '<span class="ovpf-muted">No</span>'}</td>
                <td>${rules.length ? rules.map(rule => `<div>${esc(rule)}</div>`).join('') : '<span class="ovpf-muted">-</span>'}</td>
                <td>${field.is_active ? 'Active' : 'Inactive'}</td>
                <td>
                    <div class="ovpf-actions">
                        <button type="button" class="ovpf-icon-btn" onclick="OVPForm.openEdit(${field.id})">✎</button>
                        <button type="button" class="ovpf-icon-btn" onclick="OVPForm.toggle(${field.id})">${field.is_active ? 'Off' : 'On'}</button>
                        <button type="button" class="ovpf-icon-btn" onclick="OVPForm.remove(${field.id})">🗑</button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        bindRowDragging();
        renderPreview();
    }

    function bindRowDragging() {
        const rows = Array.from(document.querySelectorAll('#ovpf-tbody .ovpf-row-draggable'));
        rows.forEach(row => {
            row.addEventListener('dragstart', onRowDragStart);
            row.addEventListener('dragover', onRowDragOver);
            row.addEventListener('dragleave', onRowDragLeave);
            row.addEventListener('drop', onRowDrop);
            row.addEventListener('dragend', clearDragState);
        });
    }

    function onRowDragStart(event) {
        const row = event.currentTarget;
        state.dragId = Number(row.dataset.fieldId);
        row.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(state.dragId));
    }

    function onRowDragOver(event) {
        event.preventDefault();
        const row = event.currentTarget;
        if (Number(row.dataset.fieldId) === state.dragId) return;
        row.classList.add('drag-over');
        event.dataTransfer.dropEffect = 'move';
    }

    function onRowDragLeave(event) {
        event.currentTarget.classList.remove('drag-over');
    }

    function onRowDrop(event) {
        event.preventDefault();
        const targetRow = event.currentTarget;
        const targetId = Number(targetRow.dataset.fieldId);
        targetRow.classList.remove('drag-over');

        if (!state.dragId || state.dragId === targetId || state.savingOrder) {
            clearDragState();
            return;
        }

        const fromIndex = state.fields.findIndex(field => Number(field.id) === Number(state.dragId));
        const toIndex = state.fields.findIndex(field => Number(field.id) === targetId);

        if (fromIndex === -1 || toIndex === -1) {
            clearDragState();
            return;
        }

        const reordered = [...state.fields];
        const [moved] = reordered.splice(fromIndex, 1);
        reordered.splice(toIndex, 0, moved);
        state.fields = reordered.map((field, index) => ({ ...field, sort_order: index }));
        renderTable();
        persistOrder();
    }

    function clearDragState() {
        state.dragId = null;
        document.querySelectorAll('#ovpf-tbody .ovpf-row-draggable').forEach(row => {
            row.classList.remove('dragging', 'drag-over');
        });
    }

    function persistOrder() {
        if (state.savingOrder) return;
        state.savingOrder = true;

        fetch(cfg.reorderUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: JSON.stringify({
                order: state.fields.map((field, index) => ({
                    id: field.id,
                    sort_order: index,
                })),
            }),
        })
            .then(async res => {
                const json = await res.json();
                if (!res.ok) throw json;
                return json;
            })
            .then(() => {
                load();
            })
            .catch(err => {
                alert((err.errors && Object.values(err.errors).flat().join('\n')) || err.message || 'Unable to reorder fields.');
                load();
            })
            .finally(() => {
                state.savingOrder = false;
                clearDragState();
            });
    }

    function renderPreview() {
        const wrap = document.getElementById('ovpf-preview');
        if (!state.fields.length) {
            wrap.innerHTML = '<div class="ovpf-empty" style="grid-column:1/-1;">No Data</div>';
            return;
        }

        wrap.innerHTML = state.fields.filter(field => field.is_active).map(field => {
            let preview = field.placeholder || field.default_value || 'Sample input';
            if (field.field_type === 'file') preview = 'Choose file...';
            if (['select', 'radio', 'checkbox'].includes(field.field_type)) {
                preview = (field.options || []).map(opt => opt.label).join(', ') || 'Option list';
            }

            return `<div class="ovpf-preview-field">
                <div class="ovpf-preview-label">${esc(field.label)} ${field.is_required ? '<span class="ovpf-req">*</span>' : ''}</div>
                <div class="ovpf-preview-box">${esc(preview)}</div>
            </div>`;
        }).join('');
    }

    function resetForm() {
        document.getElementById('ovpf-field-id').value = '';
        document.getElementById('ovpf-label').value = '';
        document.getElementById('ovpf-type').value = 'text';
        document.getElementById('ovpf-placeholder').value = '';
        document.getElementById('ovpf-default').value = '';
        document.getElementById('ovpf-help').value = '';
        document.getElementById('ovpf-sort').value = 0;
        document.getElementById('ovpf-min').value = '';
        document.getElementById('ovpf-max').value = '';
        document.getElementById('ovpf-required').checked = false;
        document.getElementById('ovpf-active').checked = true;
        document.getElementById('ovpf-use-ovp').checked = false;
        document.getElementById('ovpf-production-initiate').checked = false;
        document.getElementById('ovpf-options-list').innerHTML = '';
        onTypeChange();
    }

    function onTypeChange() {
        const type = document.getElementById('ovpf-type').value;
        document.getElementById('ovpf-options-wrap').style.display = ['select', 'radio', 'checkbox'].includes(type) ? 'block' : 'none';
    }

    function addOption(label = '', value = '') {
        const row = document.createElement('div');
        row.className = 'ovpf-option-row';
        row.innerHTML = `
            <input class="ovpf-input ovpf-option-label" type="text" placeholder="Label" value="${esc(label)}">
            <input class="ovpf-input ovpf-option-value" type="text" placeholder="Value" value="${esc(value)}">
            <button type="button" class="ovpf-remove" onclick="this.parentElement.remove()">✕</button>
        `;
        document.getElementById('ovpf-options-list').appendChild(row);
    }

    function collectOptions() {
        return Array.from(document.querySelectorAll('.ovpf-option-row')).map(row => ({
            label: row.querySelector('.ovpf-option-label').value.trim(),
            value: row.querySelector('.ovpf-option-value').value.trim(),
        })).filter(opt => opt.label && opt.value);
    }

    function load() {
        api().then(res => {
            state.fields = res.data || [];
            renderTable();
        });
    }

    function payload() {
        const fieldType = document.getElementById('ovpf-type').value;
        const validation = {};
        if (document.getElementById('ovpf-min').value !== '') validation.min = Number(document.getElementById('ovpf-min').value);
        if (document.getElementById('ovpf-max').value !== '') validation.max = Number(document.getElementById('ovpf-max').value);

        const data = {
            label: document.getElementById('ovpf-label').value.trim(),
            field_type: fieldType,
            placeholder: document.getElementById('ovpf-placeholder').value.trim() || null,
            default_value: document.getElementById('ovpf-default').value.trim() || null,
            help_text: document.getElementById('ovpf-help').value.trim() || null,
            sort_order: Number(document.getElementById('ovpf-sort').value || 0),
            is_required: document.getElementById('ovpf-required').checked,
            is_active: document.getElementById('ovpf-active').checked,
            use_in_ovp: document.getElementById('ovpf-use-ovp').checked,
            use_in_production_initiation: document.getElementById('ovpf-production-initiate').checked,
            validation_rules: Object.keys(validation).length ? validation : null,
        };

        if (['select', 'radio', 'checkbox'].includes(fieldType)) {
            data.options = collectOptions();
        }

        return data;
    }

    window.OVPForm = {
        load,
        onTypeChange,
        addOption,
        backdrop(event) {
            if (event.target.id === 'ovpf-modal-wrap') this.close();
        },
        openCreate() {
            resetForm();
            document.getElementById('ovpf-modal-title').textContent = 'Add OVP Field';
            document.getElementById('ovpf-modal-wrap').classList.add('open');
        },
        openEdit(id) {
            const field = state.fields.find(item => Number(item.id) === Number(id));
            if (!field) return;
            resetForm();
            document.getElementById('ovpf-modal-title').textContent = 'Edit OVP Field';
            document.getElementById('ovpf-field-id').value = field.id;
            document.getElementById('ovpf-label').value = field.label || '';
            document.getElementById('ovpf-type').value = field.field_type || 'text';
            document.getElementById('ovpf-placeholder').value = field.placeholder || '';
            document.getElementById('ovpf-default').value = field.default_value || '';
            document.getElementById('ovpf-help').value = field.help_text || '';
            document.getElementById('ovpf-sort').value = field.sort_order || 0;
            document.getElementById('ovpf-min').value = field.validation_rules?.min ?? '';
            document.getElementById('ovpf-max').value = field.validation_rules?.max ?? '';
            document.getElementById('ovpf-required').checked = !!field.is_required;
            document.getElementById('ovpf-active').checked = !!field.is_active;
            document.getElementById('ovpf-use-ovp').checked = !!field.use_in_ovp;
            document.getElementById('ovpf-production-initiate').checked = !!field.use_in_production_initiation;
            onTypeChange();
            (field.options || []).forEach(option => addOption(option.label, option.value));
            document.getElementById('ovpf-modal-wrap').classList.add('open');
        },
        close() {
            document.getElementById('ovpf-modal-wrap').classList.remove('open');
        },
        save() {
            const id = document.getElementById('ovpf-field-id').value;
            const data = payload();
            if (!data.label) {
                alert('Field label is required.');
                return;
            }
            if (['select', 'radio', 'checkbox'].includes(data.field_type) && !data.options.length) {
                alert('At least one option is required.');
                return;
            }

            api(id ? '/' + id : '', id ? 'PUT' : 'POST', data)
                .then(() => {
                    this.close();
                    load();
                })
                .catch(err => alert((err.errors && Object.values(err.errors).flat().join('\n')) || err.message || 'Unable to save field.'));
        },
        toggle(id) {
            api('/' + id + '/toggle', 'PATCH').then(load);
        },
        remove(id) {
            const field = state.fields.find(item => Number(item.id) === Number(id));
            const label = field ? field.label : 'this field';
            if (!confirm(`Delete "${label}" field?`)) return;
            api('/' + id, 'DELETE').then(load);
        },
    };

    load();
})();
</script>
@endpush
