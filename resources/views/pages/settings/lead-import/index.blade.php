@extends('layouts.app')

@section('title', 'Lead Import Settings - myAgenci.ai')

@push('styles')
<style>
.lead-import-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 30%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
}
.import-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding: 24px 28px;
    border: 1px solid #e6e8ee;
    border-radius: 20px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 60%, #f7f9fc 100%);
    box-shadow: 0 12px 36px rgba(15, 23, 42, 0.04);
}
.import-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
    margin-bottom: 10px;
}
.import-title {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: #111827;
}
.import-subtitle {
    margin: 6px 0 0;
    font-size: 14px;
    color: #6b7280;
}
.import-card {
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    padding: 28px;
    margin-bottom: 24px;
}
.section-heading {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f3f4f6;
}
.section-heading-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #fff7ed;
    color: #ea580c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.section-heading-title {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #1f2937;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.form-group label {
    font-size: 13px;
    font-weight: 700;
    color: #374151;
}
.form-control, .form-select {
    width: 100%;
    padding: 10px 14px;
    font-size: 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    background-color: #f9fafb;
    color: #111827;
    transition: all 0.2s ease;
}
.form-control:focus, .form-select:focus {
    outline: none;
    border-color: #ea580c;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15);
}
.file-upload-zone {
    border: 2px dashed #fed7aa;
    border-radius: 16px;
    padding: 32px;
    text-align: center;
    background: #fffaf5;
    cursor: pointer;
    transition: all 0.2s ease;
}
.file-upload-zone:hover {
    border-color: #ea580c;
    background: #fff7ed;
}
.file-upload-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 12px;
    color: #ea580c;
}
.btn-primary-agency {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 24px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 14px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(234, 88, 12, 0.3);
    transition: all 0.2s ease;
    text-decoration: none;
}
.btn-primary-agency:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
    color: #ffffff;
}
.btn-secondary-agency {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    background: #ffffff;
    color: #374151;
    font-weight: 700;
    font-size: 14px;
    border: 1px solid #d1d5db;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-secondary-agency:hover {
    background: #f3f4f6;
    color: #111827;
}
.batch-alloc-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
}
.preview-table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 24px;
}
.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.preview-table th {
    background: #f9fafb;
    padding: 10px 14px;
    text-align: left;
    font-weight: 700;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}
.preview-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f3f4f6;
    color: #4b5563;
    white-space: nowrap;
}
.mapping-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 16px;
}
.mapping-row {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 14px 16px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    transition: border-color 0.2s;
}
.mapping-row:focus-within {
    border-color: #ea580c;
}
.mapping-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.mapping-label {
    font-weight: 700;
    font-size: 13px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 6px;
}
.required-star {
    color: #dc2626;
}
.sample-preview-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 6px;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
    display: inline-block;
    word-break: break-all;
}
.approval-banner {
    padding: 24px;
    border-radius: 16px;
    background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
    border: 2px solid #fed7aa;
    margin-top: 24px;
}
</style>
@endpush

@section('content')
<div class="lead-import-page">

    {{-- Hero Section --}}
    <div class="import-hero">
        <div>
            <div class="import-kicker">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6" />
                </svg>
                Lead Management
            </div>
            <h2 class="import-title">Lead Import & Bulk Allocation</h2>
            <p class="import-subtitle">Upload Excel/CSV sheets, map custom fields, and assign leads directly to Sales & Presales executives.</p>
        </div>

        <div>
            <a href="{{ route('settings.lead-import.sample') }}" class="btn-secondary-agency">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Sample Sheet
            </a>
        </div>
    </div>

    {{-- Success / Error Alerts --}}
    @if(session('success'))
        <div style="padding: 16px 20px; border-radius: 14px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="padding: 16px 20px; border-radius: 14px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if(!isset($step) || $step === 1)
    {{-- STEP 1: Upload File & Batch Configuration --}}
    <form action="{{ route('settings.lead-import.parse') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Card 1: Batch Configuration Options --}}
        <div class="import-card">
            <div class="section-heading">
                <div class="section-heading-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="section-heading-title">Batch Allocation & Setup Options</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #6b7280;">Select product & team members to automatically allocate this lead batch upon import.</p>
                </div>
            </div>

            <div class="form-grid">
                {{-- Target Company Select --}}
                <div class="form-group">
                    <label for="company_id">Target Company</label>
                    <select name="company_id" id="company_id" class="form-select">
                        <option value="">-- Default User Company --</option>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}" {{ (auth()->user()?->company_id == $comp->id) ? 'selected' : '' }}>
                                {{ $comp->company_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Product Select --}}
                <div class="form-group">
                    <label for="product_id">Target Product</label>
                    <select name="product_id" id="product_id" class="form-select">
                        <option value="">-- No Product Selected --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">
                                {{ $prod->package_name ?: $prod->product_name }} 
                                @if($prod->final_price) (₹{{ number_format($prod->final_price, 2) }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Sales Assigned Person --}}
                <div class="form-group">
                    <label for="assigned_to">Sales Assigned Person (Sales Executive)</label>
                    <select name="assigned_to" id="assigned_to" class="form-select">
                        <option value="">-- No Sales Executive Assigned --</option>
                        @foreach($salesUsers as $sUser)
                            <option value="{{ $sUser->id }}">{{ $sUser->name }} ({{ $sUser->email }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Presales Assigned Person --}}
                <div class="form-group">
                    <label for="pre_sale_executive_id">Presales Assigned Person (Presales Executive)</label>
                    <select name="pre_sale_executive_id" id="pre_sale_executive_id" class="form-select">
                        <option value="">-- No Presales Executive Assigned --</option>
                        @foreach($preSaleUsers as $pUser)
                            <option value="{{ $pUser->id }}">{{ $pUser->name }} ({{ $pUser->email }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Default Lead Source --}}
                <div class="form-group">
                    <label for="lead_source_id">Default Lead Source</label>
                    <select name="lead_source_id" id="lead_source_id" class="form-select">
                        <option value="">-- Select Source --</option>
                        @foreach($leadSources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Default Lead Status --}}
                <div class="form-group">
                    <label for="lead_status_id">Default Lead Status</label>
                    <select name="lead_status_id" id="lead_status_id" class="form-select">
                        @foreach($leadStatuses as $status)
                            <option value="{{ $status->id }}" {{ strtolower($status->name) === 'new' ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Card 2: File Upload Zone --}}
        <div class="import-card">
            <div class="section-heading">
                <div class="section-heading-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <div>
                    <h3 class="section-heading-title">Upload Excel / CSV File</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #6b7280;">Supported formats: .xlsx, .xls, .csv (Max 10MB)</p>
                </div>
            </div>

            <div class="file-upload-zone" onclick="document.getElementById('import_file').click();">
                <svg class="file-upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h4 style="margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #111827;">Click to select file or drag & drop here</h4>
                <p style="margin: 0; font-size: 13px; color: #6b7280;" id="file-name-display">No file selected yet</p>
                <input type="file" name="import_file" id="import_file" accept=".xlsx,.xls,.csv" style="display: none;" required onchange="document.getElementById('file-name-display').innerText = this.files[0] ? this.files[0].name : 'No file selected';">
            </div>

            <div style="margin-top: 24px; text-align: right;">
                <button type="submit" class="btn-primary-agency">
                    Upload & Preview Field Mapping
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </div>
    </form>
    @else

    {{-- STEP 2: Interactive Field Mapping, Live Preview & Approval --}}
    <form action="{{ route('settings.lead-import.process') }}" method="POST" id="approval-form">
        @csrf
        <input type="hidden" name="temp_path" value="{{ $tempPath }}">
        <input type="hidden" name="company_id" value="{{ $company_id }}">
        <input type="hidden" name="product_id" value="{{ $product_id }}">
        <input type="hidden" name="assigned_to" value="{{ $assigned_to }}">
        <input type="hidden" name="pre_sale_executive_id" value="{{ $pre_sale_executive_id }}">
        <input type="hidden" name="lead_source_id" value="{{ $lead_source_id }}">
        <input type="hidden" name="lead_status_id" value="{{ $lead_status_id }}">

        {{-- Selected Allocations Summary Bar --}}
        <div class="import-card" style="padding: 20px 24px; background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-color: #bbf7d0;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
                <div>
                    <h4 style="margin: 0 0 4px; font-size: 15px; font-weight: 800; color: #166534;">Batch Allocations Summary</h4>
                    <p style="margin: 0; font-size: 13px; color: #15803d;">Total Rows Found in Sheet: <strong>{{ $totalRows }}</strong></p>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @if(isset($targetCompany) && $targetCompany)
                        <span class="batch-alloc-pill" style="background: #eef2ff; border-color: #c7d2fe; color: #3730a3;">
                            🏢 Company: {{ $targetCompany->company_name }}
                        </span>
                    @endif

                    @if($product)
                        <span class="batch-alloc-pill" style="background: #fdf4ff; border-color: #f5d0fe; color: #86198f;">
                            📦 Product: {{ $product->package_name ?: $product->product_name }}
                        </span>
                    @endif

                    @if($salesUser)
                        <span class="batch-alloc-pill" style="background: #fff7ed; border-color: #fed7aa; color: #c2410c;">
                            👤 Sales Executive: {{ $salesUser->name }}
                        </span>
                    @endif

                    @if($preSaleUser)
                        <span class="batch-alloc-pill" style="background: #f0fdfa; border-color: #99f6e4; color: #0f766e;">
                            🎧 Presales Executive: {{ $preSaleUser->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Field Mapping Setup Card (Interactive & Changeable) --}}
        <div class="import-card">
            <div class="section-heading">
                <div class="section-heading-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div>
                    <h3 class="section-heading-title">Configure & Change Field Mappings</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #6b7280;">Select or adjust how each CRM field maps to your Excel columns. Real-time preview updates automatically below.</p>
                </div>
            </div>

            <div class="mapping-grid">

                {{-- Contact Name --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Client / Contact Name <span class="required-star">*</span></span>
                    </div>
                    <select name="mappings[contact_name]" class="form-select mapping-select" data-field="contact_name" required>
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['contact_name']) && $autoMappings['contact_name'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-contact_name">Sample: None</div>
                </div>

                {{-- Company Name --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Company Name</span>
                    </div>
                    <select name="mappings[company_name]" class="form-select mapping-select" data-field="company_name">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['company_name']) && $autoMappings['company_name'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-company_name">Sample: None</div>
                </div>

                {{-- Mobile Number --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Mobile / Phone Number</span>
                    </div>
                    <select name="mappings[mobile_number]" class="form-select mapping-select" data-field="mobile_number">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['mobile_number']) && $autoMappings['mobile_number'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-mobile_number">Sample: None</div>
                </div>

                {{-- Email --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Email Address</span>
                    </div>
                    <select name="mappings[email]" class="form-select mapping-select" data-field="email">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['email']) && $autoMappings['email'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-email">Sample: None</div>
                </div>

                {{-- Lead Date --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Lead Date</span>
                    </div>
                    <select name="mappings[lead_date]" class="form-select mapping-select" data-field="lead_date">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['lead_date']) && $autoMappings['lead_date'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-lead_date">Sample: None</div>
                </div>

                {{-- Deal Value --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Deal Value / Amount</span>
                    </div>
                    <select name="mappings[deal_value]" class="form-select mapping-select" data-field="deal_value">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['deal_value']) && $autoMappings['deal_value'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-deal_value">Sample: None</div>
                </div>

                {{-- Priority --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Priority (Low/Medium/High)</span>
                    </div>
                    <select name="mappings[priority]" class="form-select mapping-select" data-field="priority">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['priority']) && $autoMappings['priority'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-priority">Sample: None</div>
                </div>

                {{-- Remarks --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Remarks / Notes</span>
                    </div>
                    <select name="mappings[remarks]" class="form-select mapping-select" data-field="remarks">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['remarks']) && $autoMappings['remarks'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-remarks">Sample: None</div>
                </div>

                {{-- Lead Source --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Lead Source</span>
                    </div>
                    <select name="mappings[lead_source]" class="form-select mapping-select" data-field="lead_source">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['lead_source']) && $autoMappings['lead_source'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-lead_source">Sample: None</div>
                </div>

                {{-- Lead Status --}}
                <div class="mapping-row">
                    <div class="mapping-header">
                        <span class="mapping-label">Lead Status</span>
                    </div>
                    <select name="mappings[lead_status]" class="form-select mapping-select" data-field="lead_status">
                        <option value="">-- Don't Map --</option>
                        @foreach($headers as $idx => $header)
                            <option value="{{ $idx }}" {{ (isset($autoMappings['lead_status']) && $autoMappings['lead_status'] == (string)$idx) ? 'selected' : '' }}>
                                Col {{ $idx + 1 }}: {{ $header }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sample-preview-badge" id="preview-badge-lead_status">Sample: None</div>
                </div>

            </div>
        </div>

        {{-- Live Mapped CRM Lead Result Preview Table --}}
        <div class="import-card">
            <div class="section-heading">
                <div class="section-heading-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="section-heading-title">Live Mapped Lead Result Preview (How CRM Leads Will Look)</h3>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #6b7280;">This table shows exact sample lead records built from your chosen column mappings above.</p>
                </div>
            </div>

            <div class="preview-table-wrapper">
                <table class="preview-table" id="live-mapped-table">
                    <thead>
                        <tr style="background: #fff7ed;">
                            <th>Contact Name</th>
                            <th>Company</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Lead Date</th>
                            <th>Deal Value</th>
                            <th>Priority</th>
                            <th>Allocated Product</th>
                            <th>Sales Executive</th>
                            <th>Presales Executive</th>
                        </tr>
                    </thead>
                    <tbody id="live-mapped-tbody">
                        {{-- Populated via JS below --}}
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Approval & Confirmation Bar --}}
        <div class="approval-banner">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px;">
                <div>
                    <h4 style="margin: 0 0 4px; font-size: 17px; font-weight: 800; color: #9a3412;">Approve & Import Confirmation</h4>
                    <p style="margin: 0; font-size: 14px; color: #44403c;">
                        Review your mapping & allocations above. Clicking <strong>"Approve & Execute Import"</strong> will import all <strong>{{ $totalRows }}</strong> leads.
                    </p>
                </div>

                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="{{ route('settings.lead-import.index') }}" class="btn-secondary-agency">
                        Cancel / Re-upload File
                    </a>

                    <button type="submit" class="btn-primary-agency" style="font-size: 15px; padding: 12px 28px;">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Approve & Execute Lead Import
                    </button>
                </div>
            </div>
        </div>
    </form>
    @endif

</div>
@endsection

@push('scripts')
@if(isset($step) && $step === 2)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rawPreviewData = @json($previewRows);
    const productName = @json($product ? ($product->package_name ?: $product->product_name) : 'None');
    const salesName = @json($salesUser ? $salesUser->name : 'None');
    const preSaleName = @json($preSaleUser ? $preSaleUser->name : 'None');

    const mappingSelects = document.querySelectorAll('.mapping-select');

    function updateLivePreview() {
        const mappings = {};
        mappingSelects.forEach(select => {
            const field = select.getAttribute('data-field');
            const val = select.value;
            mappings[field] = val !== '' ? parseInt(val) : null;

            // Update sample preview badge for field
            const badge = document.getElementById('preview-badge-' + field);
            if (badge) {
                if (val !== '' && rawPreviewData.length > 0 && rawPreviewData[0][val] !== undefined) {
                    const sampleVal = rawPreviewData[0][val];
                    badge.innerText = 'Preview: "' + (sampleVal ? sampleVal : 'Empty') + '"';
                    badge.style.background = '#f0fdf4';
                    badge.style.color = '#166534';
                    badge.style.borderColor = '#bbf7d0';
                } else {
                    badge.innerText = 'Sample: None';
                    badge.style.background = '#f9fafb';
                    badge.style.color = '#9ca3af';
                    badge.style.borderColor = '#e5e7eb';
                }
            }
        });

        // Build live mapped lead table rows
        const tbody = document.getElementById('live-mapped-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        rawPreviewData.forEach((row, idx) => {
            const tr = document.createElement('tr');

            const contact = mappings.contact_name !== null && row[mappings.contact_name] ? row[mappings.contact_name] : '(Not Mapped)';
            const company = mappings.company_name !== null && row[mappings.company_name] ? row[mappings.company_name] : '-';
            const mobile = mappings.mobile_number !== null && row[mappings.mobile_number] ? row[mappings.mobile_number] : '-';
            const email = mappings.email !== null && row[mappings.email] ? row[mappings.email] : '-';
            const date = mappings.lead_date !== null && row[mappings.lead_date] ? row[mappings.lead_date] : 'Today';
            const val = mappings.deal_value !== null && row[mappings.deal_value] ? row[mappings.deal_value] : '-';
            const priority = mappings.priority !== null && row[mappings.priority] ? row[mappings.priority] : 'medium';

            tr.innerHTML = `
                <td style="font-weight: 700; color: #111827;">${escapeHtml(contact)}</td>
                <td>${escapeHtml(company)}</td>
                <td>${escapeHtml(mobile)}</td>
                <td>${escapeHtml(email)}</td>
                <td>${escapeHtml(date)}</td>
                <td><span style="font-weight: 700; color: #15803d;">${escapeHtml(val)}</span></td>
                <td><span style="text-transform: capitalize; padding: 2px 8px; border-radius: 6px; background: #fff7ed; color: #c2410c; font-size: 11px; font-weight: 700;">${escapeHtml(priority)}</span></td>
                <td>${escapeHtml(productName)}</td>
                <td><strong>${escapeHtml(salesName)}</strong></td>
                <td><strong>${escapeHtml(preSaleName)}</strong></td>
            `;

            tbody.appendChild(tr);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    mappingSelects.forEach(select => {
        select.addEventListener('change', updateLivePreview);
    });

    updateLivePreview();
});
</script>
@endif
@endpush
