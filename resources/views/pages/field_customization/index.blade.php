@extends('layouts.app')

@section('title', 'Lead Form Customization')

@push('styles')
<style>
/* ===== LEAD FORM CUSTOMIZATION PAGE ===== */
.main-content { flex-grow:1; display:flex; flex-direction:column; min-height:0; }
.page-content { flex-grow:1; overflow-y:auto; padding:32px; }

/* Top Header */
.top-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:20px 32px; border-bottom:1px solid #e1dee3; height:72px;
    background:#fcfcfc; position:sticky; top:0; z-index:10;
}
.breadcrumbs { display:flex; align-items:center; gap:6px; font-size:14px; }
.crumb-item { color:#9e9e9e; }
.crumb-item.active { color:#121212; font-weight:500; }
.crumb-sep { opacity:0.5; }
.header-actions { display:flex; align-items:center; gap:12px; }
.btn-primary {
    display:flex; align-items:center; gap:6px; padding:8px 18px;
    background:#60308c; color:#fff; border:none; border-radius:20px;
    font-size:14px; font-weight:600; cursor:pointer; transition:background 0.2s;
}
.btn-primary:hover { background:#4e2570; }
.btn-primary i { font-size:16px; }

/* Page Title */
.page-title-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.page-title { font-size:20px; font-weight:700; color:#121212; }
.page-subtitle { font-size:13px; color:#9e9e9e; margin-top:2px; }

/* Field Type Tabs */
.type-tabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.type-tab {
    display:flex; align-items:center; gap:6px; padding:6px 14px;
    border:1px solid #e1dee3; border-radius:20px; font-size:13px;
    color:#7c7c7c; cursor:pointer; background:#fcfcfc; transition:all 0.15s;
}
.type-tab.active, .type-tab:hover { background:#f0e8f8; border-color:#60308c; color:#60308c; }
.type-tab i { font-size:14px; }

/* Fields Table */
.fields-table-wrap {
    background:#fcfcfc; border:1px solid #e1dee3; border-radius:12px; overflow:hidden;
    display:flex; flex-direction:column;
}
.fields-table-scroll {
    overflow:auto;
    max-height:calc(100vh - 320px);
    min-height:200px;
    border-radius:0 0 12px 12px;
    /* Custom thin scrollbar */
    scrollbar-width:thin;
    scrollbar-color:#d4d0d8 transparent;
}
.fields-table-scroll::-webkit-scrollbar { width:6px; height:6px; }
.fields-table-scroll::-webkit-scrollbar-track { background:transparent; }
.fields-table-scroll::-webkit-scrollbar-thumb { background:#d4d0d8; border-radius:3px; }
.fields-table-scroll::-webkit-scrollbar-thumb:hover { background:#b0acb5; }
.fields-table-scroll::-webkit-scrollbar-corner { background:transparent; }
.fields-table-toolbar {
    display:flex; justify-content:space-between; align-items:center;
    padding:16px 20px; border-bottom:1px solid #f1f1f1;
}
.fields-count { font-size:13px; color:#9e9e9e; }
.search-input-wrap { position:relative; }
.search-input-wrap input {
    padding:6px 12px 6px 34px; border:1px solid #e1dee3; border-radius:16px;
    font-size:13px; outline:none; width:220px; background:#f8f8f8;
}
.search-input-wrap i { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9e9e9e; font-size:14px; }

table.fields-table { width:100%; border-collapse:collapse; min-width:900px; }
table.fields-table thead th {
    padding:12px 16px; font-size:12px; color:#9e9e9e; font-weight:500;
    text-align:left; background:#f8f8f8; border-bottom:1px solid #f1f1f1;
    position:sticky; top:0; z-index:2;
    white-space:nowrap;
    box-shadow:0 1px 0 #f1f1f1;
}
table.fields-table tbody tr { border-bottom:1px solid #f9f9f9; transition:background 0.1s; }
table.fields-table tbody tr:hover { background:#fafafa; }
table.fields-table tbody td { padding:14px 16px; font-size:13px; color:#121212; vertical-align:middle; }
.drag-handle { cursor:grab; color:#c0c0c0; font-size:16px; }
.drag-handle:hover { color:#9e9e9e; }

.field-label-cell { display:flex; align-items:center; gap:8px; }
.field-required-star { color:#fe5f04; font-weight:700; }

/* Type badges */
.type-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600;
}
.type-text      { background:#f0f4ff; color:#3b5bdb; }
.type-number    { background:#fff3e0; color:#e65100; }
.type-select    { background:#e8f5e9; color:#2e7d32; }
.type-radio     { background:#fce4ec; color:#880e4f; }
.type-textarea  { background:#f3e5f5; color:#6a1b9a; }
.type-date      { background:#e3f2fd; color:#1565c0; }
.type-email     { background:#fff8e1; color:#f57f17; }
.type-phone     { background:#e8eaf6; color:#283593; }
.type-file      { background:#e0f2fe; color:#0369a1; }

/* Toggle switch */
.toggle-switch { position:relative; width:36px; height:20px; }
.toggle-switch input { display:none; }
.toggle-slider {
    position:absolute; cursor:pointer; inset:0;
    background:#e0e0e0; border-radius:20px; transition:background 0.2s;
}
.toggle-slider::before {
    content:''; position:absolute; width:14px; height:14px;
    left:3px; top:3px; background:#fff; border-radius:50%; transition:transform 0.2s;
}
.toggle-switch input:checked + .toggle-slider { background:#60308c; }
.toggle-switch input:checked + .toggle-slider::before { transform:translateX(16px); }

/* Action buttons */
.action-btns { display:flex; align-items:center; gap:8px; }
.icon-action {
    width:28px; height:28px; border-radius:8px; display:flex;
    align-items:center; justify-content:center; cursor:pointer;
    border:1px solid #e1dee3; background:#fcfcfc; transition:all 0.15s;
}
.icon-action:hover.edit-btn  { background:#e8f5e9; border-color:#4caf50; color:#2e7d32; }
.icon-action:hover.del-btn   { background:#ffebee; border-color:#ef9a9a; color:#c62828; }
.icon-action i { font-size:13px; color:inherit; }

/* Empty state */
.empty-state { padding:60px 20px; text-align:center; }
.empty-state i { font-size:48px; color:#e1dee3; margin-bottom:12px; }
.empty-state p { color:#9e9e9e; font-size:14px; }

/* ===== MODAL ===== */
.modal-backdrop {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45);
    z-index:1000; align-items:center; justify-content:center;
}
.modal-backdrop.open { display:flex; }
.modal {
    background:#fff; border-radius:16px; width:560px; max-height:90vh;
    overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2);
    display:flex; flex-direction:column;
}
.modal-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:20px 24px; border-bottom:1px solid #f1f1f1; position:sticky; top:0; background:#fff;
}
.modal-title { font-size:16px; font-weight:700; color:#121212; }
.modal-close { cursor:pointer; color:#9e9e9e; font-size:20px; line-height:1; }
.modal-close:hover { color:#121212; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:18px; }
.modal-footer {
    padding:16px 24px; border-top:1px solid #f1f1f1;
    display:flex; justify-content:flex-end; gap:10px;
}

/* Form elements */
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-row.full { grid-template-columns:1fr; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:13px; font-weight:600; color:#3a3a3a; }
.form-label span.req { color:#fe5f04; }
.form-control {
    padding:9px 12px; border:1px solid #e1dee3; border-radius:10px;
    font-size:13px; outline:none; background:#fcfcfc; color:#121212;
    transition:border-color 0.15s;
}
.form-control:focus { border-color:#60308c; background:#fff; }
select.form-control { appearance:none; cursor:pointer; }
.form-hint { font-size:11px; color:#9e9e9e; }
.form-divider { border:none; border-top:1px solid #f1f1f1; margin:4px 0; }

/* Options builder */
.options-section { display:flex; flex-direction:column; gap:10px; }
.options-header { display:flex; justify-content:space-between; align-items:center; }
.options-list { display:flex; flex-direction:column; gap:8px; }
.option-row { display:flex; align-items:center; gap:8px; }
.option-row input { flex:1; }
.option-remove {
    width:26px; height:26px; border:1px solid #ffcdd2; background:#ffebee;
    border-radius:8px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; color:#c62828; font-size:12px; flex-shrink:0;
}
.btn-add-option {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 12px; border:1px dashed #c8b0e0; border-radius:10px;
    color:#60308c; font-size:12px; font-weight:600; cursor:pointer; background:none;
    transition:background 0.15s;
}
.btn-add-option:hover { background:#f5eeff; }

/* Calculation section */
.calc-section {
    background:#fffbf0; border:1px solid #ffe4a0; border-radius:10px; padding:14px;
    display:flex; flex-direction:column; gap:12px;
}
.calc-section.hidden { display:none; }
.calc-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#b45309; font-weight:600; }

/* Checkbox row */
.check-row { display:flex; align-items:center; gap:8px; cursor:pointer; }
.check-row input[type=checkbox] { width:16px; height:16px; accent-color:#60308c; cursor:pointer; }
.check-row label { font-size:13px; color:#3a3a3a; cursor:pointer; }

/* Btn secondary */
.btn-secondary {
    padding:8px 18px; border:1px solid #e1dee3; border-radius:20px;
    background:#fcfcfc; font-size:14px; color:#3a3a3a; cursor:pointer;
}
.btn-secondary:hover { background:#f5f5f5; }

/* Toast */
.toast {
    position:fixed; bottom:24px; right:24px; background:#121212; color:#fff;
    padding:12px 20px; border-radius:12px; font-size:13px;
    opacity:0; pointer-events:none; transition:opacity 0.3s; z-index:2000;
}
.toast.show { opacity:1; pointer-events:auto; }
.toast.success { background:#225247; }
.toast.error   { background:#c62828; }
</style>
@endpush

@section('content')
<main class="main-content">

    <!-- Top Header -->
    <header class="top-header">
        <div class="breadcrumbs">
            <span class="crumb-item">Home</span>
            <i class="bi bi-chevron-right crumb-sep" style="font-size:11px;"></i>
            <span class="crumb-item">Sales</span>
            <i class="bi bi-chevron-right crumb-sep" style="font-size:11px;"></i>
            <span class="crumb-item active">Form Customization</span>
        </div>
        <div class="header-actions">
            <button class="btn-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Add Field
            </button>
        </div>
    </header>

    <div class="page-content">
        <div class="page-title-row">
            <div>
                <div class="page-title">Lead Form Field Customization</div>
                <div class="page-subtitle">Manage dynamic fields displayed on the Lead creation / edit form</div>
            </div>
        </div>

        <!-- Field Type Filter Tabs -->
        <div class="type-tabs" id="typeTabs">
            <div class="type-tab active" data-type="all" onclick="filterByType('all', this)">
                <i class="bi bi-grid"></i> All
            </div>
            <div class="type-tab" data-type="text" onclick="filterByType('text', this)">
                <i class="bi bi-input-cursor-text"></i> Text
            </div>
            <div class="type-tab" data-type="number" onclick="filterByType('number', this)">
                <i class="bi bi-hash"></i> Number
            </div>
            <div class="type-tab" data-type="select" onclick="filterByType('select', this)">
                <i class="bi bi-menu-button-wide"></i> Select Box
            </div>
            <div class="type-tab" data-type="radio" onclick="filterByType('radio', this)">
                <i class="bi bi-ui-radios"></i> Radio
            </div>
            <div class="type-tab" data-type="textarea" onclick="filterByType('textarea', this)">
                <i class="bi bi-text-left"></i> Textarea
            </div>
            <div class="type-tab" data-type="date" onclick="filterByType('date', this)">
                <i class="bi bi-calendar3"></i> Date
            </div>
            <div class="type-tab" data-type="file" onclick="filterByType('file', this)">
                <i class="bi bi-file-earmark-arrow-up"></i> File Upload
            </div>
        </div>

        <!-- Table -->
        <div class="fields-table-wrap">
            <div class="fields-table-toolbar">
                <span class="fields-count" id="fieldsCount">Loading fields...</span>
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search fields..." oninput="searchFields(this.value)">
                </div>
            </div>
            <div class="fields-table-scroll">
                <table class="fields-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th style="width:36px;">#</th>
                            <th>Field Label</th>
                            <th>Field Name (API Key)</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Options</th>
                            <th>Required</th>
                            <th>Show on Lead Create</th>
                            <th>Calculation</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="fieldsTableBody">
                        <tr>
                            <td colspan="12">
                                <div class="empty-state">
                                    <i class="bi bi-hourglass-split"></i>
                                    <p>Loading...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- end page-content -->
</main>

<!-- ====== CREATE / EDIT MODAL ====== -->
<div class="modal-backdrop" id="fieldModal" onclick="backdropClose(event)">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title" id="modalTitle">Add Custom Field</span>
            <span class="modal-close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editFieldId">

            <div class="form-row full">
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <select id="fCompanyId" class="form-control">
                        <option value="">Default (Current User Company)</option>
                        @if(isset($companies))
                            @foreach($companies as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Field Label <span class="req">*</span></label>
                    <input id="fLabel" type="text" class="form-control" placeholder="e.g. Budget Amount">
                </div>
                <div class="form-group">
                    <label class="form-label">Field Type <span class="req">*</span></label>
                    <select id="fType" class="form-control" onchange="onTypeChange()">
                        <option value="text">Text Input</option>
                        <option value="number">Number Input</option>
                        <option value="select">Select Box</option>
                        <option value="radio">Radio Button</option>
                        <option value="textarea">Text Area</option>
                        <option value="date">Date Picker</option>
                        <option value="email">Email</option>
                        <option value="phone">Phone Number</option>
                        <option value="file">File Upload</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Placeholder</label>
                    <input id="fPlaceholder" type="text" class="form-control" placeholder="e.g. Enter amount...">
                </div>
                <div class="form-group">
                    <label class="form-label">Default Value</label>
                    <input id="fDefault" type="text" class="form-control" placeholder="Optional default">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Sort Order</label>
                    <input id="fSortOrder" type="number" class="form-control" value="0" min="0">
                </div>
                <div class="form-group" style="justify-content:flex-end; flex-direction:row; align-items:center; gap:20px; padding-top:22px; flex-wrap:wrap;">
                    <label class="check-row">
                        <input type="checkbox" id="fRequired">
                        <label for="fRequired">Required</label>
                    </label>
                    <label class="check-row">
                        <input type="checkbox" id="fActive" checked>
                        <label for="fActive">Active</label>
                    </label>
                    <label class="check-row">
                        <input type="checkbox" id="fShowOnLeadCreate" checked>
                        <label for="fShowOnLeadCreate">Show on Lead Create</label>
                    </label>
                </div>
            </div>

            <hr class="form-divider">

            <!-- Options Section (select / radio) -->
            <div id="optionsSection" class="options-section" style="display:none;">
                <div class="options-header">
                    <span class="form-label">Options <span class="req">*</span></span>
                    <button class="btn-add-option" onclick="addOption()">
                        <i class="bi bi-plus"></i> Add Option
                    </button>
                </div>
                <div class="options-list" id="optionsList"></div>
                <span class="form-hint">Each option needs a label (shown to user) and a value (stored in DB).</span>
            </div>

            <!-- Number Validation -->
            <div id="numberSection" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Min Value</label>
                        <input id="fMin" type="number" class="form-control" placeholder="e.g. 0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Value</label>
                        <input id="fMax" type="number" class="form-control" placeholder="e.g. 9999999">
                    </div>
                </div>

                <!-- Calculation -->
                <div style="margin-top:12px;">
                    <label class="check-row" style="margin-bottom:10px;">
                        <input type="checkbox" id="fIsCalc" onchange="toggleCalcSection()">
                        <label for="fIsCalc">This field uses a calculation formula</label>
                    </label>
                    <div class="calc-section hidden" id="calcSection">
                        <span class="calc-badge"><i class="bi bi-calculator"></i> Calculation Field</span>
                        <div class="form-group">
                            <label class="form-label">Formula</label>
                            <input id="fFormula" type="text" class="form-control"
                                placeholder="e.g. cf_quantity * cf_unit_price">
                            <span class="form-hint">Use field_name values (cf_...) with operators: + - * /  and ( )</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Formula Description</label>
                            <input id="fCalcLabel" type="text" class="form-control"
                                placeholder="e.g. Quantity × Unit Price">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn-primary" onclick="saveField()">
                <i class="bi bi-check-lg"></i> <span id="saveBtnText">Create Field</span>
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

@push('scripts')
<script>
// ================================================================
//  Config
// ================================================================
const API_BASE = '/api/lead-form-fields';
const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const USER_COMPANY_ID = {{ auth()->user()?->company_id ? auth()->user()->company_id : 'null' }};

let allFields      = [];
let currentType    = 'all';
let currentSearch  = '';

// ================================================================
//  Boot
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    fetchFields();
});

// ================================================================
//  Fetch all fields
// ================================================================
async function fetchFields() {
    try {
        const res  = await fetch(API_BASE, { headers: apiHeaders() });
        const json = await res.json();
        if (json.success) {
            allFields = json.data;
            renderTable();
        }
    } catch (e) {
        showToast('Failed to load fields', 'error');
    }
}

// ================================================================
//  Render table rows
// ================================================================
function renderTable() {
    const tbody = document.getElementById('fieldsTableBody');

    let filtered = allFields.filter(f => {
        const matchType   = currentType === 'all' || f.field_type === currentType;
        const matchSearch = !currentSearch ||
            f.label.toLowerCase().includes(currentSearch.toLowerCase()) ||
            f.field_name.toLowerCase().includes(currentSearch.toLowerCase());
        return matchType && matchSearch;
    });

    document.getElementById('fieldsCount').textContent =
        `${filtered.length} field${filtered.length !== 1 ? 's' : ''}`;

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state">
            <i class="bi bi-inbox"></i><p>No fields found. Click "Add Field" to create one.</p>
        </div></td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map((f, i) => `
        <tr id="row-${f.id}">
            <td><i class="bi bi-grip-vertical drag-handle"></i></td>
            <td style="color:#9e9e9e">${i + 1}</td>
            <td>
                <div class="field-label-cell">
                    ${f.label}
                    ${f.is_required ? '<span class="field-required-star" title="Required">*</span>' : ''}
                </div>
            </td>
            <td><code style="font-size:11px;color:#60308c;background:#f5eeff;padding:2px 6px;border-radius:4px;">${f.field_name}</code></td>
            <td>${f.company_name ? escHtml(f.company_name) : '<span style="color:#aaa;font-size:12px;">Default</span>'}</td>
            <td>${typeBadge(f.field_type)}</td>
            <td>${optionsPreview(f)}</td>
            <td>${f.is_required ? '<i class="bi bi-check-circle-fill" style="color:#469d89"></i>' : '<i class="bi bi-dash-circle" style="color:#ddd"></i>'}</td>
            <td>${f.show_on_lead_create ? '<i class="bi bi-check-circle-fill" style="color:#60308c"></i>' : '<i class="bi bi-dash-circle" style="color:#ddd"></i>'}</td>
            <td>${f.is_calculation ? '<span style="font-size:11px;color:#b45309;background:#fffbf0;border:1px solid #ffe4a0;padding:2px 8px;border-radius:20px;"><i class="bi bi-calculator"></i> Yes</span>' : '<span style="color:#ccc;font-size:12px;">—</span>'}</td>
            <td>
                <label class="toggle-switch" title="${f.is_active ? 'Active' : 'Inactive'}">
                    <input type="checkbox" ${f.is_active ? 'checked' : ''} onchange="toggleActive(${f.id})">
                    <span class="toggle-slider"></span>
                </label>
            </td>
            <td>
                <div class="action-btns">
                    <div class="icon-action edit-btn" onclick="openEditModal(${f.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <div class="icon-action del-btn" onclick="deleteField(${f.id}, '${escHtml(f.label)}')" title="Delete">
                        <i class="bi bi-trash"></i>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
}

function typeBadge(type) {
    const map = {
        text:'Text', number:'Number', select:'Select', radio:'Radio',
        textarea:'Textarea', date:'Date', email:'Email', phone:'Phone',
        file:'File Upload'
    };
    return `<span class="type-badge type-${type}">${map[type] ?? type}</span>`;
}

function optionsPreview(f) {
    if (!['select','radio'].includes(f.field_type)) return '<span style="color:#ccc;font-size:12px;">—</span>';
    const opts = f.options ?? [];
    const preview = opts.slice(0, 2).map(o => `<span style="font-size:11px;background:#f0f0f0;padding:1px 6px;border-radius:4px;">${escHtml(o.label)}</span>`).join(' ');
    const more   = opts.length > 2 ? `<span style="font-size:11px;color:#9e9e9e;">+${opts.length-2} more</span>` : '';
    return `<div style="display:flex;gap:4px;flex-wrap:wrap;">${preview}${more}</div>`;
}

function escHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ================================================================
//  Filter / Search
// ================================================================
function filterByType(type, el) {
    currentType = type;
    document.querySelectorAll('.type-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    renderTable();
}

function searchFields(val) {
    currentSearch = val;
    renderTable();
}

// ================================================================
//  Modal – Create
// ================================================================
function openCreateModal() {
    document.getElementById('editFieldId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Custom Field';
    document.getElementById('saveBtnText').textContent = 'Create Field';
    resetForm();
    document.getElementById('fieldModal').classList.add('open');
}

function resetForm() {
    ['fLabel','fPlaceholder','fDefault','fFormula','fCalcLabel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const fComp = document.getElementById('fCompanyId');
    if (fComp) fComp.value = USER_COMPANY_ID ? USER_COMPANY_ID : '';
    document.getElementById('fType').value = 'text';
    document.getElementById('fSortOrder').value = 0;
    document.getElementById('fRequired').checked = false;
    document.getElementById('fActive').checked   = true;
    document.getElementById('fShowOnLeadCreate').checked = true;
    document.getElementById('fIsCalc').checked   = false;
    const fMin = document.getElementById('fMin');
    const fMax = document.getElementById('fMax');
    if (fMin) fMin.value = '';
    if (fMax) fMax.value = '';
    document.getElementById('optionsList').innerHTML = '';
    document.getElementById('calcSection').classList.add('hidden');
    onTypeChange();
}

// ================================================================
//  Modal – Edit
// ================================================================
function openEditModal(id) {
    const f = allFields.find(x => x.id === id);
    if (!f) return;
    document.getElementById('editFieldId').value = id;
    document.getElementById('modalTitle').textContent = 'Edit Field';
    document.getElementById('saveBtnText').textContent = 'Update Field';

    const fComp = document.getElementById('fCompanyId');
    if (fComp) fComp.value = f.company_id ?? (USER_COMPANY_ID ? USER_COMPANY_ID : '');

    document.getElementById('fLabel').value       = f.label;
    document.getElementById('fType').value        = f.field_type;
    document.getElementById('fPlaceholder').value = f.placeholder ?? '';
    document.getElementById('fDefault').value     = f.default_value ?? '';
    document.getElementById('fSortOrder').value   = f.sort_order;
    document.getElementById('fRequired').checked  = f.is_required;
    document.getElementById('fActive').checked    = f.is_active;
    document.getElementById('fShowOnLeadCreate').checked = !!f.show_on_lead_create;
    document.getElementById('fIsCalc').checked    = f.is_calculation;
    document.getElementById('fFormula').value     = f.calculation_formula ?? '';
    document.getElementById('fCalcLabel').value   = f.calculation_label ?? '';

    const vr = f.validation_rules ?? {};
    const fMin = document.getElementById('fMin');
    const fMax = document.getElementById('fMax');
    if (fMin) fMin.value = vr.min ?? '';
    if (fMax) fMax.value = vr.max ?? '';

    onTypeChange();

    // Populate options
    document.getElementById('optionsList').innerHTML = '';
    if (f.options) {
        f.options.forEach(opt => addOption(opt.label, opt.value));
    }

    if (f.is_calculation) {
        document.getElementById('calcSection').classList.remove('hidden');
    }

    document.getElementById('fieldModal').classList.add('open');
}

function closeModal() {
    document.getElementById('fieldModal').classList.remove('open');
}

function backdropClose(e) {
    if (e.target === document.getElementById('fieldModal')) closeModal();
}

// ================================================================
//  Type change → show/hide sections
// ================================================================
function onTypeChange() {
    const type = document.getElementById('fType').value;
    document.getElementById('optionsSection').style.display  = ['select','radio'].includes(type) ? '' : 'none';
    document.getElementById('numberSection').style.display   = type === 'number' ? '' : 'none';
}

function toggleCalcSection() {
    const show = document.getElementById('fIsCalc').checked;
    document.getElementById('calcSection').classList.toggle('hidden', !show);
}

// ================================================================
//  Options builder
// ================================================================
function addOption(label = '', value = '') {
    const list = document.getElementById('optionsList');
    const idx  = list.children.length;
    const row  = document.createElement('div');
    row.className = 'option-row';
    row.innerHTML = `
        <input class="form-control opt-label" type="text" placeholder="Label (shown to user)" value="${escHtml(label)}">
        <input class="form-control opt-value" type="text" placeholder="Value (stored)" value="${escHtml(value)}">
        <div class="option-remove" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></div>
    `;
    // Auto-fill value from label
    row.querySelector('.opt-label').addEventListener('input', function() {
        const valInput = row.querySelector('.opt-value');
        if (!valInput._manual) {
            valInput.value = this.value.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
        }
    });
    row.querySelector('.opt-value').addEventListener('input', function() {
        this._manual = true;
    });
    list.appendChild(row);
}

function collectOptions() {
    const rows = document.querySelectorAll('#optionsList .option-row');
    return Array.from(rows).map(row => ({
        label: row.querySelector('.opt-label').value.trim(),
        value: row.querySelector('.opt-value').value.trim(),
    })).filter(o => o.label && o.value);
}

// ================================================================
//  Save (create / update)
// ================================================================
async function saveField() {
    const id      = document.getElementById('editFieldId').value;
    const type    = document.getElementById('fType').value;
    const isCalc  = document.getElementById('fIsCalc').checked;
    const compEl  = document.getElementById('fCompanyId');

    const payload = {
        company_id:     compEl && compEl.value ? parseInt(compEl.value) : null,
        label:          document.getElementById('fLabel').value.trim(),
        field_type:     type,
        placeholder:    document.getElementById('fPlaceholder').value.trim() || null,
        default_value:  document.getElementById('fDefault').value.trim() || null,
        is_required:    document.getElementById('fRequired').checked,
        is_active:      document.getElementById('fActive').checked,
        show_on_lead_create: document.getElementById('fShowOnLeadCreate').checked,
        sort_order:     parseInt(document.getElementById('fSortOrder').value) || 0,
        is_calculation: isCalc,
    };

    if (['select','radio'].includes(type)) {
        payload.options = collectOptions();
    }

    if (type === 'number') {
        const min = document.getElementById('fMin').value;
        const max = document.getElementById('fMax').value;
        if (min !== '' || max !== '') {
            payload.validation_rules = {};
            if (min !== '') payload.validation_rules.min = parseFloat(min);
            if (max !== '') payload.validation_rules.max = parseFloat(max);
        }
        if (isCalc) {
            payload.calculation_formula = document.getElementById('fFormula').value.trim();
            payload.calculation_label   = document.getElementById('fCalcLabel').value.trim() || null;
        }
    }

    if (!payload.label) { showToast('Label is required', 'error'); return; }

    const url    = id ? `${API_BASE}/${id}` : API_BASE;
    const method = id ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, {
            method,
            headers: { ...apiHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message, 'success');
            closeModal();
            await fetchFields();
        } else {
            const errors = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Error');
            showToast(errors, 'error');
        }
    } catch (e) {
        showToast('Network error', 'error');
    }
}

// ================================================================
//  Toggle active
// ================================================================
async function toggleActive(id) {
    try {
        const res  = await fetch(`${API_BASE}/${id}/toggle`, { method: 'PATCH', headers: apiHeaders() });
        const json = await res.json();
        if (json.success) {
            const f = allFields.find(x => x.id === id);
            if (f) f.is_active = json.is_active;
            showToast('Status updated', 'success');
        }
    } catch(e) { showToast('Error', 'error'); }
}

// ================================================================
//  Delete
// ================================================================
async function deleteField(id, label) {
    if (!confirm(`Delete field "${label}"? This cannot be undone.`)) return;
    try {
        const res  = await fetch(`${API_BASE}/${id}`, { method: 'DELETE', headers: apiHeaders() });
        const json = await res.json();
        if (json.success) {
            showToast('Field deleted', 'success');
            await fetchFields();
        }
    } catch(e) { showToast('Error', 'error'); }
}

// ================================================================
//  Helpers
// ================================================================
function apiHeaders() {
    return {
        'Accept':       'application/json',
        'X-CSRF-TOKEN': CSRF,
    };
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `toast show ${type}`;
    setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
@endpush
@endsection