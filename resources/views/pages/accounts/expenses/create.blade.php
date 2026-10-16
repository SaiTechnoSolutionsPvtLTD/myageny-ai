@extends('layouts.app')

@section('title', 'Add Expense - Accounts')

@push('styles')
<style>
.acc-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.acc-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.acc-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.acc-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.acc-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.acc-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.acc-breadcrumbs a:hover { color:#fe5f04; }
.acc-breadcrumbs .sep { color:#ccc; }
.acc-breadcrumbs .current { color:#424242; font-weight:600; }
.acc-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.acc-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .acc-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .acc-body { padding:16px 20px 24px; }
}

.acc-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acc-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acc-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acc-btn-secondary { background:#f1f5f9; color:#334155; }
.acc-btn-secondary:hover { background:#e2e8f0; }

.acc-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:28px; box-shadow:0 2px 8px rgba(0,0,0,.02); max-width:960px; margin:0 auto; width:100%; }
.acc-card-header { border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:24px; }
.acc-card-title { font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.acc-card-subtitle { font-size:13px; color:#64748b; margin-top:4px; }

.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media (max-width: 768px) { .form-grid-2 { grid-template-columns:1fr; } }

.form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:18px; }
.form-label { font-size:12px; font-weight:700; color:#334155; display:flex; align-items:center; gap:4px; }
.form-label .req { color:#ef4444; font-size:14px; }
.form-input, .form-select, .form-textarea { padding:10px 14px; border:1px solid #cbd5e1; border-radius:10px; font-size:13px; font-family:inherit; outline:none; transition:all .2s; background:#fff; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.form-textarea { resize:vertical; min-height:80px; }

/* Radio Type Cards */
.type-radio-group { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.type-radio-card { position:relative; border:2px solid #e2e8f0; border-radius:10px; padding:12px 16px; cursor:pointer; display:flex; align-items:center; gap:12px; transition:all .2s; background:#f8fafc; }
.type-radio-card:hover { border-color:#cbd5e1; }
.type-radio-card.active { border-color:#fe5f04; background:#fff7ed; }
.type-radio-card input { position:absolute; opacity:0; }
.type-badge-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; }
.type-fixed .type-badge-icon { background:#e0e7ff; color:#4338ca; }
.type-variable .type-badge-icon { background:#fef3c7; color:#b45309; }

/* Multi-upload styling */
.dropzone-box { border:2px dashed #cbd5e1; border-radius:12px; padding:24px 20px; text-align:center; background:#fafafa; cursor:pointer; transition:all .2s; }
.dropzone-box:hover, .dropzone-box.dragover { border-color:#fe5f04; background:#fff7ed; }
.file-list { display:flex; flex-direction:column; gap:8px; margin-top:12px; }
.file-item { display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px; font-size:12px; }
.file-item-left { display:flex; align-items:center; gap:8px; overflow:hidden; }
.file-item-name { font-weight:600; color:#1e293b; max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.file-item-size { color:#64748b; font-size:11px; }
.file-remove-btn { background:none; border:none; color:#ef4444; cursor:pointer; font-size:16px; padding:2px 6px; }

.amount-input-wrap { position:relative; display:flex; align-items:center; }
.amount-prefix { position:absolute; left:14px; font-weight:800; color:#64748b; font-size:15px; }
.amount-input-wrap .form-input { padding-left:32px; font-weight:700; font-size:15px; }

.text-danger { color:#ef4444; font-size:11px; font-weight:600; margin-top:2px; }
</style>
@endpush

@section('content')
<div class="acc-page">

    {{-- Standard Uniform Topbar --}}
    <div class="acc-topbar">
        <div class="acc-topbar-left">
            <div class="acc-title">
                <i class="bi bi-receipt" style="color:#fe5f04;"></i>
                <span>Add Expense</span>
            </div>
            <div class="acc-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.expenses.index') }}">Expenses</a>
                <span class="sep">/</span>
                <span class="current">Add Expense</span>
            </div>
        </div>
        <div class="acc-topbar-actions">
            <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancel
            </a>
            <button type="button" onclick="document.getElementById('expenseForm').requestSubmit ? document.getElementById('expenseForm').requestSubmit() : document.getElementById('expenseForm').submit()" class="acc-btn acc-btn-primary">
                <i class="bi bi-check-circle-fill"></i> Save Expense
            </button>
        </div>
    </div>

    <div class="acc-body">

    {{-- Form Card --}}
    <div class="acc-card">
        <div class="acc-card-header">
            <div class="acc-card-title">
                <i class="bi bi-plus-circle" style="color:#fe5f04;"></i>
                <span>Expense Details</span>
            </div>
            <div class="acc-card-subtitle">Fill in the fields below. Category and Vendor values are fetched directly from Accounts Master.</div>
        </div>

        @if($errors->any())
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:20px;">
                <div style="font-weight:800; margin-bottom:4px;">Please correct the errors below:</div>
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('accounts.expenses.store') }}" method="POST" enctype="multipart/form-data" id="expenseForm">
            @csrf

            <div class="form-grid-2">
                {{-- Expense Date --}}
                <div class="form-group">
                    <label class="form-label" for="expense_date">
                        <span>Expense Date</span>
                        <span class="req">*</span>
                    </label>
                    <input type="date"
                           name="expense_date"
                           id="expense_date"
                           class="form-input"
                           value="{{ old('expense_date', date('Y-m-d')) }}"
                           required>
                    @error('expense_date') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                {{-- Invoice No --}}
                <div class="form-group">
                    <label class="form-label" for="invoice_no">
                        <span>Invoice No / Bill No</span>
                    </label>
                    <input type="text"
                           name="invoice_no"
                           id="invoice_no"
                           class="form-input"
                           placeholder="e.g. INV-2026-0042"
                           value="{{ old('invoice_no') }}">
                    @error('invoice_no') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-grid-2">
                {{-- Expense Category --}}
                <div class="form-group">
                    <label class="form-label" for="account_expense_category_id">
                        <span>Expense Category (Master)</span>
                        <span class="req">*</span>
                    </label>
                    <select name="account_expense_category_id"
                            id="account_expense_category_id"
                            class="form-select"
                            required
                            onchange="loadSubcategories(this.value)">
                        <option value="">-- Select Expense Category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('account_expense_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }} {{ $category->code ? '(' . $category->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('account_expense_category_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                {{-- Expense Subcategory --}}
                <div class="form-group">
                    <label class="form-label" for="account_expense_subcategory_id">
                        <span>Expense Subcategory (Master)</span>
                        <span id="subcatLoader" style="display:none; color:#fe5f04; font-size:11px; margin-left:6px;">
                            <i class="bi bi-arrow-repeat spin"></i> Loading...
                        </span>
                    </label>
                    <select name="account_expense_subcategory_id"
                            id="account_expense_subcategory_id"
                            class="form-select">
                        <option value="">-- Select Subcategory (Optional) --</option>
                    </select>
                    @error('account_expense_subcategory_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-grid-2">
                {{-- Vendor Master --}}
                <div class="form-group">
                    <label class="form-label" for="vendor_id">
                        <span>Vendor (Vendor Master)</span>
                    </label>
                    <select name="vendor_id" id="vendor_id" class="form-select">
                        <option value="">-- Select Vendor / Supplier (Optional) --</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }} {{ $vendor->company_name ? '— ' . $vendor->company_name : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                {{-- Expense Amount --}}
                <div class="form-group">
                    <label class="form-label" for="amount">
                        <span>Expense Amount</span>
                        <span class="req">*</span>
                    </label>
                    <div class="amount-input-wrap">
                        <span class="amount-prefix">₹</span>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               name="amount"
                               id="amount"
                               class="form-input"
                               placeholder="0.00"
                               value="{{ old('amount') }}"
                               required>
                    </div>
                    @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Expense Type (Fixed vs Variable) --}}
            <div class="form-group">
                <label class="form-label">
                    <span>Expense Type</span>
                    <span class="req">*</span>
                </label>
                <div class="type-radio-group">
                    <label class="type-radio-card type-fixed {{ old('expense_type', 'Fixed') === 'Fixed' ? 'active' : '' }}" onclick="selectType(this)">
                        <input type="radio" name="expense_type" value="Fixed" {{ old('expense_type', 'Fixed') === 'Fixed' ? 'checked' : '' }} required>
                        <div class="type-badge-icon">
                            <i class="bi bi-pin-angle-fill"></i>
                        </div>
                        <div>
                            <div style="font-weight:800; font-size:14px; color:#1e293b;">Fixed Expense</div>
                            <div style="font-size:11px; color:#64748b;">Recurring & constant (e.g. Rent, Subscriptions, Salaries)</div>
                        </div>
                    </label>

                    <label class="type-radio-card type-variable {{ old('expense_type') === 'Variable' ? 'active' : '' }}" onclick="selectType(this)">
                        <input type="radio" name="expense_type" value="Variable" {{ old('expense_type') === 'Variable' ? 'checked' : '' }} required>
                        <div class="type-badge-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <div style="font-weight:800; font-size:14px; color:#1e293b;">Variable Expense</div>
                            <div style="font-size:11px; color:#64748b;">Fluctuating / one-off (e.g. Supplies, Maintenance, Travel)</div>
                        </div>
                    </label>
                </div>
                @error('expense_type') <span class="text-danger">{{ $message }}</span> @enderror

                {{-- Recurring Configuration (for Fixed expenses) --}}
                <div id="recurringSettingsSection" style="display: {{ old('expense_type', 'Fixed') === 'Fixed' ? 'block' : 'none' }}; margin-top: 14px; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 18px 20px;">
                    <div style="display:flex; align-items:flex-start; gap:12px;">
                        <input type="checkbox" name="is_recurring" value="1" id="is_recurring" {{ old('is_recurring', '1') == '1' ? 'checked' : '' }} style="width:18px; height:18px; margin-top:2px; accent-color:#16a34a; cursor:pointer;" onchange="toggleRecurringFields(this.checked)">
                        <div style="flex:1;">
                            <label for="is_recurring" style="font-weight:800; font-size:13px; color:#14532d; cursor:pointer; display:flex; align-items:center; gap:6px;">
                                <i class="bi bi-arrow-repeat" style="font-size:16px;"></i> Auto-Generate Every Month (Recurring Fixed Expense)
                            </label>
                            <div style="font-size:12px; color:#15803d; margin-top:2px;">
                                Automatically generates this fixed expense each month on the scheduled day, and calculates into next month's forecast and budget pivots.
                            </div>

                            <div id="recurringDetails" style="display: {{ old('is_recurring', '1') == '1' ? 'grid' : 'none' }}; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #bbf7d0;">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" for="recurring_day_of_month" style="color:#14532d;">
                                        <span>Recurring Day of Month (1 - 31)</span>
                                    </label>
                                    <input type="number" name="recurring_day_of_month" id="recurring_day_of_month" min="1" max="31" class="form-input" placeholder="e.g. 5" value="{{ old('recurring_day_of_month') }}" style="background:#fff;">
                                    <span style="font-size:11px; color:#166534;">Defaults to day of selected Expense Date if empty.</span>
                                    @error('recurring_day_of_month') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label" for="recurring_end_date" style="color:#14532d;">
                                        <span>Repeat Until (Optional End Date)</span>
                                    </label>
                                    <input type="date" name="recurring_end_date" id="recurring_end_date" class="form-input" value="{{ old('recurring_end_date') }}" style="background:#fff;">
                                    <span style="font-size:11px; color:#166534;">Leave blank to repeat indefinitely each month.</span>
                                    @error('recurring_end_date') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Remarks --}}
            <div class="form-group">
                <label class="form-label" for="remarks">
                    <span>Remarks / Notes</span>
                </label>
                <textarea name="remarks"
                          id="remarks"
                          class="form-textarea"
                          rows="3"
                          placeholder="Provide any additional context, payment method, bill details, etc.">{{ old('remarks') }}</textarea>
                @error('remarks') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            {{-- Multi Attachments --}}
            <div class="form-group">
                <label class="form-label">
                    <span>Attachments (Multi Attachments)</span>
                    <span style="font-size:11px; color:#64748b; font-weight:normal;">(Invoices, receipts, payment proofs - PDF, Images, Docs up to 10MB each)</span>
                </label>
                <div class="dropzone-box" id="dropzoneBox" onclick="document.getElementById('attachmentsInput').click()">
                    <i class="bi bi-cloud-arrow-up" style="font-size:36px; color:#fe5f04;"></i>
                    <div style="font-size:14px; font-weight:700; color:#1e293b; margin-top:6px;">Click or drag & drop files here</div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Supports multiple files (PDF, PNG, JPG, DOCX, XLSX)</div>
                    <input type="file"
                           name="attachments[]"
                           id="attachmentsInput"
                           multiple
                           style="display:none;"
                           onchange="handleFilesSelected(this)">
                </div>

                {{-- Selected Files Preview --}}
                <div class="file-list" id="selectedFileList"></div>
                @error('attachments') <span class="text-danger">{{ $message }}</span> @enderror
                @error('attachments.*') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            {{-- Form Actions --}}
            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px; border-top:1px solid #e2e8f0; padding-top:20px;">
                <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="acc-btn acc-btn-primary" style="padding:10px 24px; font-size:14px;">
                    <i class="bi bi-check-circle-fill"></i> Save Expense
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

@push('scripts')
<script>
function selectType(card) {
    document.querySelectorAll('.type-radio-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
    const radio = card.querySelector('input[type="radio"]');
    if (radio) {
        radio.checked = true;
        const recurringSection = document.getElementById('recurringSettingsSection');
        if (recurringSection) {
            recurringSection.style.display = radio.value === 'Fixed' ? 'block' : 'none';
        }
    }
}

function toggleRecurringFields(checked) {
    const details = document.getElementById('recurringDetails');
    if (details) {
        details.style.display = checked ? 'grid' : 'none';
    }
}

// Dynamic Subcategory Loading based on Category Selection
const subcategoryUrlTemplate = "{{ route('accounts.master.expense-categories.subcategories', ['category' => ':id']) }}";
const oldSubcatId = "{{ old('account_expense_subcategory_id') }}";

function loadSubcategories(categoryId, preselectId = null) {
    const subcatSelect = document.getElementById('account_expense_subcategory_id');
    const loader = document.getElementById('subcatLoader');

    subcatSelect.innerHTML = '<option value="">-- Select Subcategory (Optional) --</option>';

    if (!categoryId) return;

    if (loader) loader.style.display = 'inline-block';

    const url = subcategoryUrlTemplate.replace(':id', categoryId);

    fetch(url, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (loader) loader.style.display = 'none';
        if (data.status && Array.isArray(data.data)) {
            data.data.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                if (preselectId && String(sub.id) === String(preselectId)) {
                    opt.selected = true;
                }
                subcatSelect.appendChild(opt);
            });
        }
    })
    .catch(err => {
        if (loader) loader.style.display = 'none';
        console.error('Error loading subcategories:', err);
    });
}

// On page load, if old category exists, reload subcategories
document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('account_expense_category_id');
    if (catSelect && catSelect.value) {
        loadSubcategories(catSelect.value, oldSubcatId);
    }
});

// File uploader preview handling
const dropzone = document.getElementById('dropzoneBox');
const fileInput = document.getElementById('attachmentsInput');
const fileList = document.getElementById('selectedFileList');

if (dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFilesSelected(fileInput);
        }
    });
}

function handleFilesSelected(input) {
    fileList.innerHTML = '';
    if (!input.files || input.files.length === 0) return;

    Array.from(input.files).forEach((file, idx) => {
        const item = document.createElement('div');
        item.className = 'file-item';

        const sizeFormatted = file.size > 1048576
            ? (file.size / 1048576).toFixed(2) + ' MB'
            : (file.size / 1024).toFixed(1) + ' KB';

        item.innerHTML = `
            <div class="file-item-left">
                <i class="bi bi-file-earmark-check" style="color:#059669; font-size:16px;"></i>
                <div>
                    <div class="file-item-name" title="${file.name}">${file.name}</div>
                    <div class="file-item-size">${sizeFormatted}</div>
                </div>
            </div>
            <span style="color:#64748b; font-size:11px;">Ready to upload</span>
        `;
        fileList.appendChild(item);
    });
}
</script>
@endpush
@endsection
