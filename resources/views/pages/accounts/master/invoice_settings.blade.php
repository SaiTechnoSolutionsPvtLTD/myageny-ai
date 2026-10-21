@extends('layouts.app')

@section('title', 'Invoice Settings - Accounts Master')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-breadcrumbs .sep { color:#ccc; }
.inv-breadcrumbs .current { color:#424242; font-weight:600; }
.inv-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .inv-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .inv-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.acm-btn[disabled], .acm-btn.disabled { opacity:0.65 !important; cursor:not-allowed !important; pointer-events:none !important; transform:none !important; box-shadow:none !important; }
@keyframes acmSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.acm-spin { display:inline-block; animation:acmSpin .8s linear infinite; }

/* Settings Cards */
.inv-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,.02); overflow:hidden; }
.inv-card-header { padding:18px 24px; border-bottom:1px solid #f1f5f9; background:#fff; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.inv-card-title { font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.inv-card-sub { font-size:12px; color:#64748b; margin-top:2px; }
.inv-card-body { padding:24px; display:flex; flex-direction:column; gap:20px; }

/* Grid Layouts */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.grid-3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; }
.grid-4 { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; }
@media (max-width: 900px) {
    .grid-2, .grid-3, .grid-4 { grid-template-columns:1fr; }
}

.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; box-sizing:border-box; width:100%; transition:border-color .15s, box-shadow .15s; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.form-hint { font-size:11px; color:#94a3b8; }

/* Live Preview Card */
.preview-box { background:linear-gradient(135deg, #0f172a, #1e293b); color:#fff; border-radius:14px; padding:20px 24px; display:flex; flex-direction:column; justify-content:center; align-items:flex-start; gap:8px; box-shadow:0 10px 25px rgba(15,23,42,.15); }
.preview-tag { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#38bdf8; display:flex; align-items:center; gap:6px; }
.preview-number { font-size:26px; font-weight:800; letter-spacing:.03em; color:#f8fafc; font-family:'Courier New', monospace; background:rgba(255,255,255,.08); padding:6px 14px; border-radius:8px; border:1px dashed rgba(255,255,255,.2); word-break:break-all; }
.preview-desc { font-size:12px; color:#94a3b8; line-height:1.5; }

/* Upload Dropzone Box */
.upload-card { border:2px dashed #cbd5e1; border-radius:12px; padding:20px; text-align:center; background:#f8fafc; transition:all .2s; position:relative; cursor:pointer; }
.upload-card:hover { border-color:#fe5f04; background:#fff7ed; }
.upload-card input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; z-index:2; }
.preview-image-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px; display:inline-flex; align-items:center; justify-content:center; max-height:120px; max-width:240px; margin-bottom:10px; overflow:hidden; }
.preview-image-wrap img { max-height:100px; max-width:100%; object-fit:contain; }

.section-divider { border-bottom:1px solid #f1f5f9; margin:4px 0; }
.tox-tinymce { border-radius:12px !important; border-color:#cbd5e1 !important; box-shadow:0 1px 3px rgba(0,0,0,.02) !important; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Standard Uniform Topbar --}}
    <div class="inv-topbar">
        <div class="inv-topbar-left">
            <div class="inv-title">
                <i class="bi bi-receipt-cutoff" style="color:#fe5f04;"></i>
                <span>Invoice Settings</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span class="sep">/</span>
                <span class="current">Invoice Settings</span>
            </div>
        </div>
        <div class="inv-topbar-actions">
            <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Master
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="triggerSaveSettings()">
                <i class="bi bi-check-lg"></i> Save Settings
            </button>
        </div>
    </div>

    <div class="inv-body">

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:14px 20px; border-radius:12px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:14px 20px; border-radius:12px; font-size:13px; font-weight:600;">
            ✕ {{ session('error') }}
        </div>
    @endif
    @if(isset($errors) && $errors->any())
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:14px 20px; border-radius:12px; font-size:13px; font-weight:600;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Settings Form --}}
    <form method="POST" action="{{ route('accounts.master.invoice-settings.update') }}" enctype="multipart/form-data" id="invoiceSettingsForm">
        @csrf

        <div style="display:flex; flex-direction:column; gap:22px;">

            {{-- Card 1: Invoice Numbering & Live Preview --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div>
                        <div class="inv-card-title">
                            <i class="bi bi-hash" style="color:#fe5f04; font-size:18px;"></i>
                            <span>Invoice Numbering & Format</span>
                        </div>
                        <div class="inv-card-sub">Define how your invoice sequential identifiers are structured with live preview.</div>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div style="display:grid; grid-template-columns:1.4fr 1fr; gap:24px; align-items:start;">

                        {{-- Controls --}}
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Invoice Prefix <span style="color:#dc2626;">*</span></label>
                                    <input type="text" name="invoice_prefix" id="inputPrefix" class="form-input"
                                           value="{{ old('invoice_prefix', $setting->invoice_prefix ?? 'INV/') }}"
                                           placeholder="e.g. INV/ or INV- or AG/" required oninput="updateInvoicePreview()">
                                    <span class="form-hint">Appears at the beginning of the invoice number</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Date Format Inclusion <span style="color:#dc2626;">*</span></label>
                                    <select name="date_format" id="inputDateFormat" class="form-input" onchange="updateInvoicePreview()">
                                        <option value="financial_year" {{ old('date_format', $setting->date_format ?? 'financial_year') === 'financial_year' ? 'selected' : '' }}>
                                            Financial Year (e.g. {{ date('m') >= 4 ? date('Y') . '-' . substr(date('Y') + 1, -2) : (date('Y') - 1) . '-' . substr(date('Y'), -2) }})
                                        </option>
                                        <option value="calendar_year" {{ old('date_format', $setting->date_format) === 'calendar_year' ? 'selected' : '' }}>
                                            Calendar Year (e.g. {{ date('Y') }})
                                        </option>
                                        <option value="year_month" {{ old('date_format', $setting->date_format) === 'year_month' ? 'selected' : '' }}>
                                            Year & Month (e.g. {{ date('Y-m') }})
                                        </option>
                                        <option value="none" {{ old('date_format', $setting->date_format) === 'none' ? 'selected' : '' }}>
                                            None (No date tag)
                                        </option>
                                    </select>
                                    <span class="form-hint">Appended after prefix</span>
                                </div>
                            </div>

                            <div class="grid-3">
                                <div class="form-group">
                                    <label class="form-label">Next Invoice Number <span style="color:#dc2626;">*</span></label>
                                    <input type="number" name="next_number" id="inputNextNumber" class="form-input" min="1"
                                           value="{{ old('next_number', $setting->next_number ?? 1) }}" required oninput="updateInvoicePreview()">
                                    <span class="form-hint">Number for next invoice</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Number Digits (Padding) <span style="color:#dc2626;">*</span></label>
                                    <select name="number_padding" id="inputNumberPadding" class="form-input" onchange="updateInvoicePreview()">
                                        <option value="3" {{ old('number_padding', $setting->number_padding) == 3 ? 'selected' : '' }}>3 digits (e.g. 001)</option>
                                        <option value="4" {{ old('number_padding', $setting->number_padding ?? 4) == 4 ? 'selected' : '' }}>4 digits (e.g. 0001)</option>
                                        <option value="5" {{ old('number_padding', $setting->number_padding) == 5 ? 'selected' : '' }}>5 digits (e.g. 00001)</option>
                                        <option value="6" {{ old('number_padding', $setting->number_padding) == 6 ? 'selected' : '' }}>6 digits (e.g. 000001)</option>
                                    </select>
                                    <span class="form-hint">Zero-padded width</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Invoice Suffix (Optional)</label>
                                    <input type="text" name="invoice_suffix" id="inputSuffix" class="form-input"
                                           value="{{ old('invoice_suffix', $setting->invoice_suffix) }}"
                                           placeholder="e.g. DEL or TN" oninput="updateInvoicePreview()">
                                    <span class="form-hint">Appended at end (optional)</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Number Reset Frequency</label>
                                <select name="reset_frequency" class="form-input">
                                    <option value="financial_year" {{ old('reset_frequency', $setting->reset_frequency ?? 'financial_year') === 'financial_year' ? 'selected' : '' }}>
                                        Every Financial Year (Resets to 1 on April 1st)
                                    </option>
                                    <option value="yearly" {{ old('reset_frequency', $setting->reset_frequency) === 'yearly' ? 'selected' : '' }}>
                                        Every Calendar Year (Resets to 1 on Jan 1st)
                                    </option>
                                    <option value="monthly" {{ old('reset_frequency', $setting->reset_frequency) === 'monthly' ? 'selected' : '' }}>
                                        Every Month (Resets to 1 on 1st of each month)
                                    </option>
                                    <option value="never" {{ old('reset_frequency', $setting->reset_frequency) === 'never' ? 'selected' : '' }}>
                                        Never (Continuous sequential numbers)
                                    </option>
                                </select>
                            </div>
                        </div>

                        {{-- Live Preview Box --}}
                        <div class="preview-box">
                            <div class="preview-tag">
                                <i class="bi bi-eye"></i> Live Number Preview
                            </div>
                            <div class="preview-number" id="previewInvoiceNum">
                                {{ $setting->formatInvoiceNumber() }}
                            </div>
                            <div class="preview-desc">
                                This is exactly how your next generated invoice will be numbered. It updates dynamically as you modify prefix, date format, padding, or suffix.
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card 2: Company Profile & Logo Upload --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div>
                        <div class="inv-card-title">
                            <i class="bi bi-building" style="color:#2563eb; font-size:18px;"></i>
                            <span>Company Profile & Branding Logo</span>
                        </div>
                        <div class="inv-card-sub">Company details, addresses, and logo printed on the invoice header.</div>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:24px; align-items:start;">

                        {{-- Left: Company Fields --}}
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <div class="form-group">
                                <label class="form-label">Company Legal / Brand Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="company_name" class="form-input"
                                       value="{{ old('company_name', $setting->company_name) }}"
                                       placeholder="e.g. Acme Media Solutions Pvt Ltd" required>
                            </div>

                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">Company Phone Number</label>
                                    <input type="text" name="company_phone" class="form-input"
                                           value="{{ old('company_phone', $setting->company_phone) }}"
                                           placeholder="e.g. +91 98765 43210">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Company Email</label>
                                    <input type="email" name="company_email" class="form-input"
                                           value="{{ old('company_email', $setting->company_email) }}"
                                           placeholder="e.g. billing@company.com">
                                </div>
                            </div>

                            <div class="grid-2">
                                <div class="form-group">
                                    <label class="form-label">GST Number (GSTIN)</label>
                                    <input type="text" name="company_gstin" class="form-input" style="text-transform:uppercase;"
                                           value="{{ old('company_gstin', $setting->company_gstin) }}"
                                           placeholder="e.g. 33AAAAA0000A1Z5">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">PAN Number</label>
                                    <input type="text" name="company_pan" class="form-input" style="text-transform:uppercase;"
                                           value="{{ old('company_pan', $setting->company_pan) }}"
                                           placeholder="e.g. ABCDE1234F">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Company Address (Street / Suite / Building) <span style="color:#dc2626;">*</span></label>
                                <textarea name="company_address" class="form-input" rows="3"
                                          placeholder="e.g. No. 42, 3rd Floor, Commercial Complex, Gandhi Road" required>{{ old('company_address', $setting->company_address) }}</textarea>
                            </div>

                            <div class="grid-3">
                                <div class="form-group">
                                    <label class="form-label">City</label>
                                    <input type="text" name="company_city" class="form-input"
                                           value="{{ old('company_city', $setting->company_city) }}"
                                           placeholder="e.g. Chennai">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">State</label>
                                    <input type="text" name="company_state" class="form-input"
                                           value="{{ old('company_state', $setting->company_state ?? 'Tamil Nadu') }}"
                                           placeholder="e.g. Tamil Nadu">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Pin Code</label>
                                    <input type="text" name="company_pincode" class="form-input"
                                           value="{{ old('company_pincode', $setting->company_pincode) }}"
                                           placeholder="e.g. 600040">
                                </div>
                            </div>
                        </div>

                        {{-- Right: Logo Upload & Preview --}}
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:14px;">
                            <div style="font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:6px;">
                                <i class="bi bi-image" style="color:#2563eb;"></i> Company Logo
                            </div>
                            <div style="font-size:12px; color:#64748b; line-height:1.5;">
                                Upload your organization logo to appear on top of each invoice. Transparent PNG or SVG recommended. Max 3MB.
                            </div>

                            {{-- Current Logo Display --}}
                            @if($setting->logo_url)
                                <div style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:12px; background:#fff; border:1px solid #e2e8f0; border-radius:10px;">
                                    <div class="preview-image-wrap">
                                        <img src="{{ $setting->logo_url }}" alt="Company Logo" id="currentLogoImg">
                                    </div>
                                    <button type="button" class="acm-btn acm-btn-danger" style="font-size:11px; padding:4px 10px;" onclick="confirmRemoveFile('logo')">
                                        <i class="bi bi-trash"></i> Remove Current Logo
                                    </button>
                                </div>
                            @endif

                            {{-- New Logo Upload Input --}}
                            <div class="upload-card">
                                <input type="file" name="logo" id="logoFileInput" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" onchange="previewUploadImage(this, 'newLogoPreviewWrap', 'newLogoPreviewImg')">
                                <div id="newLogoPreviewWrap" style="display:none; margin-bottom:8px;">
                                    <div class="preview-image-wrap">
                                        <img id="newLogoPreviewImg" src="" alt="New Logo Preview">
                                    </div>
                                </div>
                                <i class="bi bi-cloud-arrow-up" style="font-size:32px; color:#fe5f04; display:block; margin-bottom:4px;"></i>
                                <div style="font-size:13px; font-weight:700; color:#0f172a;">{{ $setting->logo_url ? 'Click to change logo' : 'Choose Logo file or drag here' }}</div>
                                <div style="font-size:11px; color:#94a3b8; margin-top:2px;">PNG, JPG, SVG, WebP up to 3MB</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card 3: Bank Details & UPI ID --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div>
                        <div class="inv-card-title">
                            <i class="bi bi-bank" style="color:#059669; font-size:18px;"></i>
                            <span>Bank & Payment Settlement Details</span>
                        </div>
                        <div class="inv-card-sub">Wire transfer and digital UPI payment details displayed on the invoice footer for clients.</div>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div class="grid-3">
                        <div class="form-group">
                            <label class="form-label">Bank Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="bank_name" class="form-input"
                                   value="{{ old('bank_name', $setting->bank_name) }}"
                                   placeholder="e.g. HDFC Bank Ltd" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Holder / Beneficiary Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="account_name" class="form-input"
                                   value="{{ old('account_name', $setting->account_name) }}"
                                   placeholder="e.g. Acme Media Solutions Pvt Ltd" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="account_number" class="form-input"
                                   value="{{ old('account_number', $setting->account_number) }}"
                                   placeholder="e.g. 50200012345678" required>
                        </div>
                    </div>

                    <div class="grid-4">
                        <div class="form-group">
                            <label class="form-label">IFSC Code <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="ifsc_code" class="form-input" style="text-transform:uppercase;"
                                   value="{{ old('ifsc_code', $setting->ifsc_code) }}"
                                   placeholder="e.g. HDFC0001234" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch Name</label>
                            <input type="text" name="branch" class="form-input"
                                   value="{{ old('branch', $setting->branch) }}"
                                   placeholder="e.g. Anna Nagar, Chennai">
                        </div>
                        <div class="form-group">
                            <label class="form-label">UPI ID</label>
                            <input type="text" name="upi_id" class="form-input"
                                   value="{{ old('upi_id', $setting->upi_id) }}"
                                   placeholder="e.g. acmemedia@okaxis">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Swift / BIC Code (Optional)</label>
                            <input type="text" name="swift_code" class="form-input" style="text-transform:uppercase;"
                                   value="{{ old('swift_code', $setting->swift_code) }}"
                                   placeholder="e.g. HDFCINBBXXX">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 4: Terms & Conditions and Customer Notes --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div>
                        <div class="inv-card-title">
                            <i class="bi bi-file-earmark-text" style="color:#7c3aed; font-size:18px;"></i>
                            <span>Terms & Conditions and Notes</span>
                        </div>
                        <div class="inv-card-sub">Standard terms, conditions, payment rules, and declarations printed on invoices. Terms & Conditions automatically generate as the default dedicated Page 2.</div>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <div class="form-group">
                            <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                                <span>Terms & Conditions (One per line or formatted clauses)</span>
                                <span style="font-size:11px; font-weight:700; color:#7c3aed; background:#f5f3ff; border:1px solid #ddd6fe; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                    <i class="bi bi-pencil-square"></i> Rich Text Editor
                                </span>
                            </label>
                            <textarea id="termsConditionsEditor" name="terms_conditions" class="form-input" rows="8" style="font-size:13px; line-height:1.6;"
                                      placeholder="1. Payment is due within agreed terms&#10;2. Make all payments via NEFT/RTGS/UPI&#10;3. Interest charged on overdue invoices">{{ old('terms_conditions', $setting->terms_conditions ?? \App\Models\InvoiceSetting::defaultTerms()) }}</textarea>
                            <span class="form-hint">These Terms & Conditions appear automatically on the default <strong>dedicated 2nd Page</strong> of every invoice PDF and printout. Use lists, bold styling, headings, and formatting as needed.</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Declaration / Customer Notes (Optional)</label>
                            <textarea name="notes" class="form-input" rows="3" style="font-size:12px; line-height:1.6;"
                                      placeholder="Certified that the particulars given above are true and correct. Thank you for your business!">{{ old('notes', $setting->notes) }}</textarea>
                            <span class="form-hint">Closing declaration or thank-you note printed on Page 1 below totals.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 5: Authorized Signature --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div>
                        <div class="inv-card-title">
                            <i class="bi bi-pen" style="color:#d97706; font-size:18px;"></i>
                            <span>Authorized Signatory & Signature</span>
                        </div>
                        <div class="inv-card-sub">Digital signature and designation displayed on the bottom-right of the invoice.</div>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:24px; align-items:start;">

                        <div class="form-group">
                            <label class="form-label">Signatory Designation / Label <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="signatory_name" class="form-input"
                                   value="{{ old('signatory_name', $setting->signatory_name ?? 'Authorized Signatory') }}"
                                   placeholder="e.g. Authorized Signatory or Managing Director" required>
                            <span class="form-hint">Printed under the signature block on the invoice</span>
                        </div>

                        {{-- Signature Upload & Preview --}}
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:14px;">
                            <div style="font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:6px;">
                                <i class="bi bi-pen" style="color:#d97706;"></i> Signature Image
                            </div>

                            @if($setting->signature_url)
                                <div style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:12px; background:#fff; border:1px solid #e2e8f0; border-radius:10px;">
                                    <div class="preview-image-wrap">
                                        <img src="{{ $setting->signature_url }}" alt="Authorized Signature" id="currentSigImg">
                                    </div>
                                    <button type="button" class="acm-btn acm-btn-danger" style="font-size:11px; padding:4px 10px;" onclick="confirmRemoveFile('signature')">
                                        <i class="bi bi-trash"></i> Remove Current Signature
                                    </button>
                                </div>
                            @endif

                            <div class="upload-card">
                                <input type="file" name="signature" id="sigFileInput" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" onchange="previewUploadImage(this, 'newSigPreviewWrap', 'newSigPreviewImg')">
                                <div id="newSigPreviewWrap" style="display:none; margin-bottom:8px;">
                                    <div class="preview-image-wrap">
                                        <img id="newSigPreviewImg" src="" alt="New Signature Preview">
                                    </div>
                                </div>
                                <i class="bi bi-cloud-arrow-up" style="font-size:32px; color:#d97706; display:block; margin-bottom:4px;"></i>
                                <div style="font-size:13px; font-weight:700; color:#0f172a;">{{ $setting->signature_url ? 'Click to change signature' : 'Choose Signature file or drag here' }}</div>
                                <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Transparent PNG recommended (Max 2MB)</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Bottom Action Bar --}}
            <div style="display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); margin-top:4px;">
                <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancel & Return
                </a>
                <button type="submit" id="saveInvoiceSettingsBtn" class="acm-btn acm-btn-primary" style="padding:10px 24px; font-size:14px;">
                    <i class="bi bi-check-lg"></i> Save Invoice Settings
                </button>
            </div>

        </div>
    </form>

    {{-- Hidden File Removal Form --}}
    <form id="removeFileForm" method="POST" action="" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
let isInvoiceSettingsSubmitting = false;

function triggerSaveSettings() {
    if (window.tinymce) {
        window.tinymce.triggerSave();
    }
    const form = document.getElementById('invoiceSettingsForm');
    if (form) {
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }
}

// Live Number Preview Calculator
function updateInvoicePreview() {
    const prefix = document.getElementById('inputPrefix').value || '';
    const dateFormat = document.getElementById('inputDateFormat').value;
    const nextNum = parseInt(document.getElementById('inputNextNumber').value, 10) || 1;
    const padding = parseInt(document.getElementById('inputNumberPadding').value, 10) || 4;
    const suffix = document.getElementById('inputSuffix').value || '';

    // Calculate padded number
    const padded = String(nextNum).padStart(padding, '0');

    // Calculate date tag
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth() + 1; // 1-12
    let dateTag = '';

    if (dateFormat === 'financial_year') {
        if (currentMonth >= 4) {
            const nextYrShort = String(currentYear + 1).slice(-2);
            dateTag = `${currentYear}-${nextYrShort}`;
        } else {
            const curYrShort = String(currentYear).slice(-2);
            dateTag = `${currentYear - 1}-${curYrShort}`;
        }
    } else if (dateFormat === 'calendar_year') {
        dateTag = String(currentYear);
    } else if (dateFormat === 'year_month') {
        const monthPad = String(currentMonth).padStart(2, '0');
        dateTag = `${currentYear}-${monthPad}`;
    }

    const parts = [];
    if (prefix) parts.push(prefix.replace(/[\/\-]+$/, ''));
    if (dateTag) parts.push(dateTag);
    parts.push(padded);
    if (suffix) parts.push(suffix.replace(/^[\/\-]+/, ''));

    const sep = prefix.endsWith('-') ? '-' : '/';
    const finalNumber = parts.join(sep);

    document.getElementById('previewInvoiceNum').textContent = finalNumber;
}

// Preview uploaded images before form submit
function previewUploadImage(input, wrapId, imgId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById(imgId);
            const wrap = document.getElementById(wrapId);
            if (img && wrap) {
                img.src = e.target.result;
                wrap.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Remove File Handler (Logo / Signature)
function confirmRemoveFile(type) {
    const label = type === 'logo' ? 'Company Logo' : 'Authorized Signature';
    if (!confirm(`Are you sure you want to remove the current ${label}?`)) {
        return;
    }

    const removeForm = document.getElementById('removeFileForm');
    removeForm.action = "{{ url('accounts/master/invoice-settings/remove-file') }}/" + type;
    removeForm.submit();
}

// Form Submit Handler with Loading State and Double-Click Prevention
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TinyMCE for Terms & Conditions Editor
    if (window.tinymce && document.getElementById('termsConditionsEditor')) {
        window.tinymce.remove('#termsConditionsEditor');
        window.tinymce.init({
            selector: 'textarea#termsConditionsEditor',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
            menubar: 'edit view insert format tools table',
            height: 360,
            plugins: 'lists link table code preview wordcount',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | bullist numlist | alignleft aligncenter alignright alignjustify | link table | removeformat code',
            content_style: 'body { font-family: Inter, sans-serif; font-size: 13px; line-height: 1.6; color: #1e293b; padding: 12px; } ol, ul { padding-left: 20px; } p { margin: 0 0 8px 0; }',
            setup: function (editor) {
                editor.on('change keyup NodeChange', function () {
                    window.tinymce.triggerSave();
                });
            },
        });
    }

    const form = document.getElementById('invoiceSettingsForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (window.tinymce) {
                window.tinymce.triggerSave();
            }

            if (isInvoiceSettingsSubmitting) {
                e.preventDefault();
                return false;
            }

            if (!this.checkValidity()) {
                return;
            }

            isInvoiceSettingsSubmitting = true;
            const btn = document.getElementById('saveInvoiceSettingsBtn');
            if (btn) {
                btn.classList.add('disabled');
                btn.style.pointerEvents = 'none';
                btn.innerHTML = '<i class="bi bi-arrow-repeat acm-spin"></i> Saving Settings...';
                setTimeout(() => {
                    btn.disabled = true;
                }, 20);
            }
        });
    }

    // Initialize live preview
    updateInvoicePreview();
});

// Reset on pageshow (bfcache)
window.addEventListener('pageshow', function() {
    isInvoiceSettingsSubmitting = false;
    const btn = document.getElementById('saveInvoiceSettingsBtn');
    if (btn) {
        btn.disabled = false;
        btn.classList.remove('disabled');
        btn.style.pointerEvents = 'auto';
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Invoice Settings';
    }
});
</script>
@endpush
@endsection
