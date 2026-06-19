{{-- FILE: resources/views/settings/quotation.blade.php --}}
@extends('layouts.app')

@section('title', 'Quotation Settings — myAgenci.ai')

@push('styles')
<style>
/* ===== QUOTATION SETTINGS PAGE ===== */

/* TinyMCE editor */
.tox-tinymce {
    border: 1px solid #e1dee3 !important;
    border-radius: 9px !important;
}
.tox .tox-toolbar,
.tox .tox-toolbar__overflow,
.tox .tox-toolbar__primary {
    background: #fcfcfc !important;
}

.settings-container {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 0;
    min-height: calc(100vh - 72px);
}

/* ─── Left Nav ──────────────────────────────────────────── */
.settings-nav {
    border-right: 1px solid #e1dee3;
    padding: 24px 16px;
    background: #fafafa;
    position: sticky;
    top: 72px;
    height: calc(100vh - 72px);
    overflow-y: auto;
}
.settings-nav-title {
    font-size: 11px;
    font-weight: 700;
    color: #9e9e9e;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    padding: 0 12px;
    margin-bottom: 8px;
}
.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #4e4e4e;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
    margin-bottom: 2px;
}
.settings-nav-item:hover { background: #f0f0f0; color: #121212; }
.settings-nav-item.active {
    background: linear-gradient(135deg, #fff0e6, #fde8d8);
    color: #fe5f04;
    font-weight: 600;
    border: 1px solid #fdd5be;
}
.settings-nav-item .nav-icon {
    width: 28px; height: 28px;
    background: #f5f5f5;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.settings-nav-item.active .nav-icon { background: #ffe8d4; }

/* ─── Main Panel ────────────────────────────────────────── */
.settings-panel {
    padding: 32px;
    overflow-y: auto;
    max-height: calc(100vh - 72px);
}

/* ─── Page Header ───────────────────────────────────────── */
.settings-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f0f0f0;
}
.page-title { font-size: 22px; font-weight: 700; color: #121212; margin-bottom: 4px; }
.page-subtitle { font-size: 14px; color: #8e8e8e; }

/* ─── Section Cards ─────────────────────────────────────── */
.settings-section {
    background: #fff;
    border: 1px solid #e8e5eb;
    border-radius: 14px;
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.settings-section:hover { box-shadow: 0 4px 24px rgba(0,0,0,0.06); }

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 24px;
    border-bottom: 1px solid #f5f3f7;
    cursor: pointer;
    user-select: none;
}
.section-icon {
    width: 36px; height: 36px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.icon-brand   { background: #fff3e6; }
.icon-color   { background: #fff8e6; }
.icon-terms   { background: #e8f5e9; }
.icon-sig     { background: #f3e8ff; }
.icon-company { background: #e3f2fd; }
.icon-number  { background: #fce4ec; }
.icon-bank    { background: #e0f7fa; }

.section-title-text {
    flex-grow: 1;
}
.section-title-text h3 { font-size: 15px; font-weight: 600; color: #121212; margin-bottom: 2px; }
.section-title-text p  { font-size: 12px; color: #9e9e9e; }
.section-toggle { font-size: 18px; color: #c0c0c0; transition: transform 0.2s; }
.section-toggle.open { transform: rotate(180deg); }

.section-body { padding: 24px; }

/* ─── Form Elements ─────────────────────────────────────── */
.form-group { margin-bottom: 20px; }
.form-group:last-child { margin-bottom: 0; }
.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #2e2e2e;
    margin-bottom: 7px;
}
.form-label span { color: #fe5f04; }
.form-hint { font-size: 11px; color: #9e9e9e; margin-top: 5px; }

.form-input {
    width: 100%;
    padding: 9px 13px;
    border: 1px solid #e1dee3;
    border-radius: 9px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: #121212;
    background: #fcfcfc;
    transition: border 0.15s, box-shadow 0.15s;
    outline: none;
}
.form-input:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.1);
    background: #fff;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

/* ─── File Upload ───────────────────────────────────────── */
.upload-zone {
    border: 2px dashed #e1dee3;
    border-radius: 12px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #fafafa;
    position: relative;
}
.upload-zone:hover, .upload-zone.drag-over {
    border-color: #fe5f04;
    background: #fff8f4;
}
.upload-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-icon { font-size: 32px; margin-bottom: 8px; }
.upload-label { font-size: 14px; font-weight: 600; color: #2e2e2e; margin-bottom: 4px; }
.upload-sub   { font-size: 12px; color: #9e9e9e; }

.file-preview {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: #f8f6fb;
    border: 1px solid #ede9f2;
    border-radius: 10px;
    margin-top: 12px;
}
.preview-img {
    height: 48px;
    max-width: 120px;
    object-fit: contain;
    border-radius: 6px;
}
.preview-sig {
    height: 40px;
    max-width: 160px;
    object-fit: contain;
}
.preview-actions { margin-left: auto; display: flex; gap: 8px; }
.btn-icon-danger {
    width: 28px; height: 28px;
    background: #fff0f0;
    border: 1px solid #ffcdd2;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: #e53935;
    font-size: 13px;
    transition: all 0.15s;
}
.btn-icon-danger:hover { background: #ffcdd2; }

/* ─── Color Picker ──────────────────────────────────────── */
.color-picker-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
.color-swatch {
    width: 44px; height: 44px;
    border-radius: 10px;
    border: 2px solid #e1dee3;
    cursor: pointer;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
}
.color-swatch input[type="color"] {
    position: absolute; inset: -4px;
    width: calc(100% + 8px); height: calc(100% + 8px);
    border: none; outline: none; cursor: pointer; padding: 0;
}
.color-hex-input {
    width: 120px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    letter-spacing: 1px;
}
.color-presets {
    display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;
}
.preset-color {
    width: 28px; height: 28px;
    border-radius: 6px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.15s;
}
.preset-color:hover, .preset-color.selected { border-color: #121212; transform: scale(1.1); }

/* ─── Quotation Number Preview ──────────────────────────── */
.number-preview {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #f8f8f8;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    font-family: 'Courier New', monospace;
    font-size: 16px;
    font-weight: 700;
    color: #121212;
    margin-top: 12px;
}
.number-preview .preview-label {
    font-size: 11px;
    color: #9e9e9e;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    margin-right: 4px;
}

/* ─── Two Column Grid ───────────────────────────────────── */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

/* ─── Save Bar ──────────────────────────────────────────── */
.save-bar {
    position: fixed;
    bottom: 100px;
    margin: 0 -32px -32px;
    padding: 16px 32px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(8px);
    border-top: 1px solid #e8e5eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 20;
}
.save-status { font-size: 13px; color: #9e9e9e; }

.btn-save {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 24px;
    background: linear-gradient(135deg, #fe5f04, #ff8c42);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(254,95,4,0.3); }
.btn-save:active { transform: translateY(0); }
.btn-save:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

.btn-secondary {
    padding: 10px 20px;
    background: #fff;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #4e4e4e;
    cursor: pointer;
    transition: all 0.15s;
    font-family: 'Inter', sans-serif;
}
.btn-secondary:hover { background: #f8f8f8; }

/* ─── Alert ─────────────────────────────────────────────── */
.alert-success {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px;
    background: #e8f5e9;
    border: 1px solid #c8e6c9;
    border-radius: 10px;
    font-size: 14px;
    color: #2e7d32;
    margin-bottom: 24px;
}

/* ─── Preview Modal ─────────────────────────────────────── */
.preview-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.preview-modal-overlay.show { display: flex; }
.preview-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 700px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
}
.modal-header h3 { font-size: 16px; font-weight: 700; }
.modal-close {
    width: 32px; height: 32px;
    background: #f5f5f5;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}

/* ─── Quotation PDF Preview ─────────────────────────────── */
.quotation-preview {
    padding: 32px;
    font-family: 'Inter', sans-serif;
}
.qp-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    padding-bottom: 20px;
}
.qp-company-name { font-size: 20px; font-weight: 700; }
.qp-title { font-size: 28px; font-weight: 800; text-align: right; }
.qp-divider { height: 4px; border-radius: 2px; margin-bottom: 24px; }
.qp-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.qp-label { font-size: 11px; font-weight: 600; color: #9e9e9e; text-transform: uppercase; margin-bottom: 4px; }
.qp-val { font-size: 13px; color: #121212; }
.qp-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.qp-table th { font-size: 11px; text-transform: uppercase; padding: 10px 12px; text-align: left; }
.qp-table td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #f5f5f5; }
.qp-totals { display: flex; justify-content: flex-end; margin-bottom: 24px; }
.qp-totals-box { width: 240px; }
.qp-total-row { display: flex; justify-content: space-between; font-size: 13px; padding: 5px 0; }
.qp-total-row.grand { font-weight: 700; font-size: 15px; padding-top: 10px; margin-top: 5px; border-top: 2px solid #e0e0e0; }
.qp-footer { display: flex; justify-content: space-between; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f0f0f0; }
.qp-terms h4 { font-size: 12px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; }
.qp-terms p { font-size: 11px; color: #7e7e7e; margin-bottom: 4px; }
.qp-signature { text-align: right; }
.qp-signature img { height: 48px; margin-bottom: 4px; }
.qp-sig-line { font-size: 11px; color: #9e9e9e; }
</style>
@endpush

@section('content')
<main class="main-content">

    {{-- Top Header --}}


    <div class="settings-container">

        {{-- ─── Left Navigation ─────────────────────────────── --}}
        <nav class="settings-nav">
            <div class="settings-nav-title">Quotation</div>

            <a href="#section-branding" class="settings-nav-item active" data-section="branding">
                <div class="nav-icon">🖼️</div> Branding
            </a>
            <a href="#section-appearance" class="settings-nav-item" data-section="appearance">
                <div class="nav-icon">🎨</div> Appearance
            </a>
            <a href="#section-company" class="settings-nav-item" data-section="company">
                <div class="nav-icon">🏢</div> Company Info
            </a>
            <a href="#section-numbering" class="settings-nav-item" data-section="numbering">
                <div class="nav-icon">#️⃣</div> Numbering
            </a>
            <a href="#section-terms" class="settings-nav-item" data-section="terms">
                <div class="nav-icon">📄</div> Terms & Conditions
            </a>
            <a href="#section-signature" class="settings-nav-item" data-section="signature">
                <div class="nav-icon">✍️</div> Signature
            </a>
            <a href="#section-bank" class="settings-nav-item" data-section="bank">
                <div class="nav-icon">🏦</div> Bank Details
            </a>
        </nav>

        {{-- ─── Settings Panel ──────────────────────────────── --}}
        <div class="settings-panel">

            @if(session('success'))
            <div class="alert-success">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
            </div>
            @endif

            <div class="settings-page-header">
                <div>
                    <h1 class="page-title">Quotation Settings</h1>
                    <p class="page-subtitle">Configure how your quotations look and behave across the system.</p>
                </div>
            </div>

            <form action="{{ route('settings.quotation.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
                @csrf
                @method('POST')

                {{-- ══════════════════════════════════════════════
                    SECTION 1: BRANDING (Logo)
                ══════════════════════════════════════════════ --}}
                <div class="settings-section" id="section-branding">
                    <div class="section-header" onclick="toggleSection(this)">
                        <div class="section-icon icon-brand">🖼️</div>
                        <div class="section-title-text">
                            <h3>Company Logo</h3>
                            <p>Upload your logo to display on quotations</p>
                        </div>
                        <span class="section-toggle open bi bi-chevron-down"></span>
                    </div>
                    <div class="section-body">

                        @if(!empty($data['logo']))
                        <div class="file-preview" id="logoPreview">
                            <img src="/{{ $data['logo'] }}" alt="Logo" class="preview-img" id="logoImg">
                            <div>
                                <div style="font-size:13px;font-weight:600;">Current Logo</div>
                                <div style="font-size:12px;color:#9e9e9e;">Click upload to replace</div>
                            </div>
                            <div class="preview-actions">
                                <button type="button" class="btn-icon-danger" onclick="deleteFile('logo')" title="Remove logo">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                        @endif

                        <div class="upload-zone" id="logoZone" style="{{ !empty($data['logo']) ? 'margin-top:12px;' : '' }}"
                             ondragover="handleDragOver(event,this)" ondragleave="handleDragLeave(this)" ondrop="handleDrop(event,'logo')">
                            <input type="file" name="logo" id="logoInput" accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                   onchange="previewFile(this,'logoPreviewNew','logoZone')">
                            <div class="upload-icon">☁️</div>
                            <div class="upload-label">Drop logo here or click to upload</div>
                            <div class="upload-sub">PNG, JPG, SVG — max 2MB · Recommended: 300×80px transparent PNG</div>
                        </div>
                        <div id="logoPreviewNew"></div>
                        @error('logo')<div style="color:#e53935;font-size:12px;margin-top:6px;">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════
                    SECTION 2: APPEARANCE (Theme Color)
                ══════════════════════════════════════════════ --}}
                <div class="settings-section" id="section-appearance">
                    <div class="section-header" onclick="toggleSection(this)">
                        <div class="section-icon icon-color">🎨</div>
                        <div class="section-title-text">
                            <h3>Theme Color</h3>
                            <p>Applied to headers, dividers and highlights in quotations</p>
                        </div>
                        <span class="section-toggle open bi bi-chevron-down"></span>
                    </div>
                    <div class="section-body">
                        <div class="form-group">
                            <label class="form-label">Primary Color</label>
                            <div class="color-picker-row">
                                <div class="color-swatch" id="colorSwatch" style="background:{{ $data['theme_color'] ?? '#fe5f04' }}">
                                    <input type="color" name="theme_color" id="colorPicker"
                                           value="{{ $data['theme_color'] ?? '#fe5f04' }}"
                                           oninput="syncColor(this.value)">
                                </div>
                                <input type="text" class="form-input color-hex-input" id="colorHex"
                                       value="{{ $data['theme_color'] ?? '#fe5f04' }}"
                                       placeholder="#fe5f04" maxlength="7"
                                       oninput="syncColorFromHex(this.value)">
                                <span style="font-size:13px;color:#9e9e9e;">Select or type hex value</span>
                            </div>

                            {{-- Preset palette --}}
                            <div class="color-presets">
                                @foreach(['#fe5f04','#e53935','#8e24aa','#1e88e5','#00897b','#43a047','#f57c00','#546e7a','#121212'] as $clr)
                                <div class="preset-color {{ ($data['theme_color'] ?? '#fe5f04') === $clr ? 'selected' : '' }}"
                                     style="background:{{ $clr }}"
                                     onclick="syncColor('{{ $clr }}')"
                                     title="{{ $clr }}"></div>
                                @endforeach
                            </div>
                            <div class="form-hint">This color will be applied to the quotation header, dividers, and total row background.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Secondary Color</label>
                            <div class="color-picker-row">
                                <div class="color-swatch" id="colorSwatch2" style="background:{{ $data['secondary_color'] ?? '#fe5f04' }}">
                                    <input type="color" name="secondary_color" id="colorPicker2"
                                           value="{{ $data['secondary_color'] ?? '#fe5f04' }}"
                                           oninput="syncColor2(this.value)">
                                </div>
                                <input type="text" class="form-input color-hex-input" id="colorHex2"
                                       value="{{ $data['secondary_color'] ?? '#fe5f04' }}"
                                       placeholder="#fe5f04" maxlength="7"
                                       oninput="syncColorFromHex2(this.value)">
                                <span style="font-size:13px;color:#9e9e9e;">Select or type hex value</span>
                            </div>

                            {{-- Preset palette --}}
                            <div class="color-presets">
                                @foreach(['#fe5f04','#e53935','#8e24aa','#1e88e5','#00897b','#43a047','#f57c00','#546e7a','#121212'] as $clr)
                                <div class="preset-color {{ ($data['secondary_color'] ?? '#fe5f04') === $clr ? 'selected' : '' }}"
                                     style="background:{{ $clr }}"
                                     onclick="syncColor2('{{ $clr }}')"
                                     title="{{ $clr }}"></div>
                                @endforeach
                            </div>
                            <div class="form-hint">This color will be applied to the quotation header, dividers, and total row background.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Header Text Color</label>
                            <div class="color-picker-row">
                                <div class="color-swatch" id="colorSwatch3" style="background:{{ $data['header_text_color'] ?? '#ffffff' }}">
                                    <input type="color" name="header_text_color" id="colorPicker3"
                                           value="{{ $data['header_text_color'] ?? '#ffffff' }}"
                                           oninput="syncColor3(this.value)">
                                </div>
                                <input type="text" class="form-input color-hex-input" id="colorHex3"
                                       value="{{ $data['header_text_color'] ?? '#ffffff' }}"
                                       placeholder="#ffffff" maxlength="7"
                                       oninput="syncColorFromHex3(this.value)">
                                <span style="font-size:13px;color:#9e9e9e;">Select or type hex value</span>
                            </div>

                            <div class="color-presets">
                                @foreach(['#ffffff','#f8fafc','#fef3c7','#dbeafe','#dcfce7','#111827','#1f2937','#4b5563','#7f1d1d'] as $clr)
                                <div class="preset-color"
                                     style="background:{{ $clr }}"
                                     onclick="syncColor3('{{ $clr }}')"
                                     title="{{ $clr }}"></div>
                                @endforeach
                            </div>
                            <div class="form-hint">This color will be used for the company details and quotation info inside the header area.</div>
                        </div>

                        {{-- Watermark --}}
                        <div class="form-group">
                            <label class="form-label">Watermark Text <span style="font-size:11px;color:#9e9e9e;font-weight:400;">(optional)</span></label>
                            <input type="text" name="watermark_text" class="form-input"
                                   value="{{ $data['watermark_text'] ?? '' }}"
                                   placeholder="e.g. DRAFT, CONFIDENTIAL, SAMPLE"
                                   maxlength="50">
                            <div class="form-hint">Displayed diagonally across the quotation as a light watermark.</div>
                        </div>
                        <div class="form-group" style="display:flex;align-items:center;gap:10px;">
                            <label class="form-label" style="margin:0;">Show Watermark on PDFs</label>
                            <label class="toggle-switch" style="margin-left:auto;">
                                <input type="hidden" name="show_watermark" value="0">
                                <input type="checkbox" name="show_watermark" value="1"
                                       {{ ($data['show_watermark'] ?? false) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════
                    SECTION 3: COMPANY INFO
                ══════════════════════════════════════════════ --}}
                <div class="settings-section" id="section-company">
                    <div class="section-header" onclick="toggleSection(this)">
                        <div class="section-icon icon-company">🏢</div>
                        <div class="section-title-text">
                            <h3>Company Information</h3>
                            <p>Appears in the quotation header / sender section</p>
                        </div>
                        <span class="section-toggle open bi bi-chevron-down"></span>
                    </div>
                    <div class="section-body">
                        <div class="form-group">
                            <label class="form-label">Company Name <span>*</span></label>
                            <input type="text" name="company_name" class="form-input"
                                   value="{{ $data['company_name'] ?? '' }}"
                                   placeholder="myAgenci.ai Private Limited" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Address</label>
                            <textarea name="company_address" class="form-input form-textarea"
                                      placeholder="No. 123, Main Street&#10;Chennai - 600001&#10;Tamil Nadu, India">{{ $data['company_address'] ?? '' }}</textarea>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Phone / WhatsApp</label>
                                <input type="text" name="company_phone" class="form-input"
                                       value="{{ $data['company_phone'] ?? '' }}" placeholder="+91 9999999999">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="company_email" class="form-input"
                                       value="{{ $data['company_email'] ?? '' }}" placeholder="hello@company.com">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">GSTIN / Tax ID</label>
                            <input type="text" name="company_gstin" class="form-input"
                                   value="{{ $data['company_gstin'] ?? '' }}" placeholder="29ABCDE1234F1Z5" maxlength="20">
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════
                    SECTION 4: QUOTATION NUMBERING
                ══════════════════════════════════════════════ --}}
                <div class="settings-section" id="section-numbering">
                    <div class="section-header" onclick="toggleSection(this)">
                        <div class="section-icon icon-number">#️⃣</div>
                        <div class="section-title-text">
                            <h3>Quotation Numbering</h3>
                            <p>Configure how quotation numbers are auto-generated</p>
                        </div>
                        <span class="section-toggle open bi bi-chevron-down"></span>
                    </div>
                    <div class="section-body">
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Number Prefix <span>*</span></label>
                                <input type="text" name="prefix" id="prefixInput" class="form-input"
                                       value="{{ $data['prefix'] ?? 'QUO-' }}"
                                       placeholder="QUO-" maxlength="10"
                                       oninput="updateNumberPreview()">
                                <div class="form-hint">Examples: QUO-, EST-, INV-, PRO-</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Number Padding</label>
                                <input type="number" name="number_padding" id="paddingInput" class="form-input"
                                       value="{{ $data['number_padding'] ?? 5 }}"
                                       min="3" max="10" oninput="updateNumberPreview()">
                                <div class="form-hint">Total digits: 5 → QUO-00001</div>
                            </div>
                        </div>

                        {{-- Live Preview --}}
                        <div>
                            <div class="form-label">Preview</div>
                            <div class="number-preview">
                                <span class="preview-label">Next number:</span>
                                <span id="numberPreview">{{ ($data['prefix'] ?? 'QUO-') . str_pad($data['next_number'] ?? 1, $data['number_padding'] ?? 5, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        </div>
                        <div class="form-hint" style="margin-top:8px;">
                            ⚠️ Changing the prefix only affects future quotations. Existing quotation numbers remain unchanged.
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════
                    SECTION 5: TERMS & CONDITIONS
                ══════════════════════════════════════════════ --}}
                <div class="settings-section" id="section-terms">
                    <div class="section-header" onclick="toggleSection(this)">
                        <div class="section-icon icon-terms">📄</div>
                        <div class="section-title-text">
                            <h3>Terms & Conditions</h3>
                            <p>Auto-displayed at the bottom of every quotation</p>
                        </div>
                        <span class="section-toggle open bi bi-chevron-down"></span>
                    </div>
                    <div class="section-body">
                        <div class="form-group">
                            <label class="form-label">Terms Content</label>
                            <textarea id="termsEditor" name="terms" class="form-input form-textarea"
                                      style="min-height:220px;">{{ old('terms', $data['terms'] ?? '') }}</textarea>
                            <div class="form-hint">
                                Uses rich text formatting. You can use numbered lists, bold, links, etc.
                                These terms will be copied to each new quotation (and can be edited per-quotation).
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════
                    SECTION 6: SIGNATURE
                ══════════════════════════════════════════════ --}}
                <div class="settings-section" id="section-signature">
                    <div class="section-header" onclick="toggleSection(this)">
                        <div class="section-icon icon-sig">✍️</div>
                        <div class="section-title-text">
                            <h3>Authorized Signature</h3>
                            <p>Shown in the footer of every quotation</p>
                        </div>
                        <span class="section-toggle open bi bi-chevron-down"></span>
                    </div>
                    <div class="section-body">
                        @if(!empty($data['signature']))
                        <div class="file-preview" id="sigPreview">
                            <img src="/{{ $data['signature'] }}" alt="Signature" class="preview-sig" id="sigImg">
                            <div>
                                <div style="font-size:13px;font-weight:600;">Current Signature</div>
                                <div style="font-size:12px;color:#9e9e9e;">Click upload to replace</div>
                            </div>
                            <div class="preview-actions">
                                <button type="button" class="btn-icon-danger" onclick="deleteFile('signature')">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                        @endif

                        <div class="upload-zone" id="sigZone" style="{{ !empty($data['signature']) ? 'margin-top:12px;' : '' }}"
                             ondragover="handleDragOver(event,this)" ondragleave="handleDragLeave(this)">
                            <input type="file" name="signature" id="sigInput" accept="image/png,image/jpeg"
                                   onchange="previewFile(this,'sigPreviewNew','sigZone')">
                            <div class="upload-icon">✍️</div>
                            <div class="upload-label">Upload signature image</div>
                            <div class="upload-sub">PNG with transparent background recommended · max 1MB</div>
                        </div>
                        <div id="sigPreviewNew"></div>
                        @error('signature')<div style="color:#e53935;font-size:12px;margin-top:6px;">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════
                    SECTION 7: BANK DETAILS
                ══════════════════════════════════════════════ --}}
                <div class="settings-section" id="section-bank">
                    <div class="section-header" onclick="toggleSection(this)">
                        <div class="section-icon icon-bank">🏦</div>
                        <div class="section-title-text">
                            <h3>Bank Details</h3>
                            <p>Shown in quotation footer for payment reference</p>
                        </div>
                        <span class="section-toggle open bi bi-chevron-down"></span>
                    </div>
                    <div class="section-body">
                        <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-input"
                                   value="{{ $data['bank_name'] ?? '' }}" placeholder="HDFC Bank">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Name</label>
                            <input type="text" name="account_name" class="form-input"
                                   value="{{ $data['account_name'] ?? '' }}" placeholder="TTT">
                        </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="bank_account" class="form-input"
                                       value="{{ $data['bank_account'] ?? '' }}" placeholder="XXXXXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" name="bank_ifsc" class="form-input"
                                       value="{{ $data['bank_ifsc'] ?? '' }}" placeholder="HDFC0001234" maxlength="15">
                            </div>
                        </div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">Branch</label>
                                <input type="text" name="bank_branch" class="form-input"
                                       value="{{ $data['bank_branch'] ?? '' }}" placeholder="Chennai">
                            </div>
                            <div class="form-group">
                                <label class="form-label">UPI</label>
                                <input type="text" name="bank_upi" class="form-input"
                                       value="{{ $data['bank_upi'] ?? '' }}" placeholder="test@oksbi" maxlength="15">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Save Bar ─────────────────────────────── --}}
                <div class="save-bar">
                    <span class="save-status" id="saveStatus">All changes will be applied to newly created quotations.</span>
                    <div style="display:flex;gap:10px;">
                        <button type="button" class="btn-secondary" onclick="window.location.reload()">Discard</button>
                        <button type="submit" class="btn-save" id="saveBtn">
                            <i class="bi bi-check2-circle"></i>
                            Save Settings
                        </button>
                    </div>
                </div>

            </form>{{-- end form --}}
        </div>{{-- end settings-panel --}}
    </div>{{-- end settings-container --}}
</main>




{{-- Toggle Switch CSS (inline) --}}
<style>
.toggle-switch { position:relative; display:inline-block; width:44px; height:24px; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider {
    position:absolute; cursor:pointer; inset:0;
    background:#e1dee3; border-radius:24px; transition:.3s;
}
.toggle-slider:before {
    content:''; position:absolute; height:18px; width:18px;
    left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s;
}
input:checked + .toggle-slider { background:#fe5f04; }
input:checked + .toggle-slider:before { transform:translateX(20px); }
</style>
@endsection


@push('scripts')
{{-- TinyMCE self-hosted build via jsDelivr (no Tiny Cloud API key required) --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    // ─── TinyMCE Init ───────────────────────────────────────
    var syncTermsEditor = function() {};

    function initTermsEditor() {
        const termsEditor = document.getElementById('termsEditor');
        if (!termsEditor || !window.tinymce) return;

        tinymce.remove('#termsEditor');
        tinymce.init({
            selector: 'textarea#termsEditor',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
            suffix: '.min',
            plugins: 'lists link table code autoresize',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | alignleft aligncenter alignright | link table | removeformat code',
            menubar: false,
            branding: false,
            promotion: false,
            min_height: 260,
            content_style: "body { font-family: Inter, Arial, sans-serif; font-size: 14px; color: #121212; }",
            setup: function(editor) {
                syncTermsEditor = function() {
                    editor.save();
                };

                editor.on('change keyup input undo redo SetContent', syncTermsEditor);
            },
            init_instance_callback: function(editor) {
                editor.save();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTermsEditor, { once: true });
    } else {
        initTermsEditor();
    }

    // ─── Section Collapse / Expand ────────────────────────────
    function toggleSection(headerEl) {
        const toggle = headerEl.querySelector('.section-toggle');
        const body   = headerEl.nextElementSibling;
        const isOpen = toggle.classList.contains('open');

        if (isOpen) {
            body.style.display = 'none';
            toggle.classList.remove('open');
        } else {
            body.style.display = 'block';
            toggle.classList.add('open');
        }
    }

    // ─── Nav Highlight ────────────────────────────────────────
    document.querySelectorAll('.settings-nav-item').forEach(link => {
        link.addEventListener('click', function(e) {
            document.querySelectorAll('.settings-nav-item').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ─── Color Sync ───────────────────────────────────────────
    function syncColor(hex) {
        document.getElementById('colorPicker').value = hex;
        document.getElementById('colorHex').value    = hex;
        document.getElementById('colorSwatch').style.background = hex;
        document.querySelectorAll('.preset-color').forEach(p => {
            p.classList.toggle('selected', p.style.background === hex || p.getAttribute('style').includes(hex));
        });
        // Live update preview
        updatePreviewColor(hex);
    }

     function syncColor2(hex) {
        document.getElementById('colorPicker2').value = hex;
        document.getElementById('colorHex2').value    = hex;
        document.getElementById('colorSwatch2').style.background = hex;
        document.querySelectorAll('.preset-color2').forEach(p => {
            p.classList.toggle('selected', p.style.background === hex || p.getAttribute('style').includes(hex));
        });
        // Live update preview
        updatePreviewColor2(hex);
    }

    function syncColor3(hex) {
        document.getElementById('colorPicker3').value = hex;
        document.getElementById('colorHex3').value    = hex;
        document.getElementById('colorSwatch3').style.background = hex;
        updatePreviewHeaderTextColor(hex);
    }

    function syncColorFromHex(val) {
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) syncColor(val);
    }

    function syncColorFromHex2(val) {
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) syncColor2(val);
    }

    function syncColorFromHex3(val) {
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) syncColor3(val);
    }

    // ─── Number Preview ───────────────────────────────────────
    function updateNumberPreview() {
        const prefix  = document.getElementById('prefixInput').value || 'QUO-';
        const padding = parseInt(document.getElementById('paddingInput').value) || 5;
        const num     = String({{ $data['next_number'] ?? 1 }}).padStart(padding, '0');
        document.getElementById('numberPreview').textContent = prefix + num;
    }

    // ─── File Preview (New Upload) ────────────────────────────
    function previewFile(input, previewId, zoneId) {
        const preview = document.getElementById(previewId);
        if (!input.files || !input.files[0]) return;

        const file   = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const isLogo = zoneId === 'logoZone';
            preview.innerHTML = `
                <div class="file-preview" style="margin-top:12px;">
                    <img src="${e.target.result}" class="${isLogo ? 'preview-img' : 'preview-sig'}" alt="Preview">
                    <div>
                        <div style="font-size:13px;font-weight:600;">New File Selected</div>
                        <div style="font-size:12px;color:#9e9e9e;">${file.name} (${(file.size/1024).toFixed(1)} KB)</div>
                    </div>
                </div>`;

            // Update live quotation preview
            if (isLogo) updatePreviewLogo(e.target.result);
        };
        reader.readAsDataURL(file);
    }

    // ─── Drag & Drop ─────────────────────────────────────────
    function handleDragOver(e, zone) {
        e.preventDefault();
        zone.classList.add('drag-over');
    }
    function handleDragLeave(zone) {
        zone.classList.remove('drag-over');
    }
    function handleDrop(e, type) {
        e.preventDefault();
        const zone  = document.getElementById(type + 'Zone');
        zone.classList.remove('drag-over');
        const input = document.getElementById(type + 'Input');
        const dt    = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        input.files = dt.files;
        previewFile(input, type + 'PreviewNew', type + 'Zone');
    }

    // ─── Delete File (AJAX) ───────────────────────────────────
    function deleteFile(type) {
        if (!confirm(`Remove the current ${type}?`)) return;

        fetch(`{{ url('settings/quotation/file') }}/${type}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const previewEl = document.getElementById(type === 'logo' ? 'logoPreview' : 'sigPreview');
                if (previewEl) previewEl.remove();
            }
        });
    }

    // ─── Form Save State ──────────────────────────────────────
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function() {
            syncTermsEditor();
            const btn    = document.getElementById('saveBtn');
            const status = document.getElementById('saveStatus');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
            }
            if (status) status.textContent = 'Saving your settings…';
        });
    }

    // ─── Live Quotation Preview ───────────────────────────────
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        previewModal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });
    }

    // Build preview when modal opens
    const previewTrigger = document.querySelector('[onclick*="previewModal"]');
    if (previewTrigger) {
        previewTrigger.addEventListener('click', buildPreview);
    }

    function buildPreview() {
        const livePreview = document.getElementById('livePreview');
        if (!livePreview) return;

        const color           = document.getElementById('colorHex').value || '#fe5f04';
        const headerTextColor = document.getElementById('colorHex3').value || '#ffffff';
        const prefix          = document.getElementById('prefixInput').value || 'QUO-';
        const padding         = parseInt(document.getElementById('paddingInput').value) || 5;
        const qNo             = prefix + String({{ $data['next_number'] ?? 1 }}).padStart(padding, '0');
        const logoEl          = document.getElementById('logoImg');
        const logoSrc         = logoEl ? logoEl.src : '';
        const sigEl           = document.getElementById('sigImg');
        const sigSrc          = sigEl ? sigEl.src : '';
        const company         = document.querySelector('[name="company_name"]')?.value || 'Your Company';
        const address         = document.querySelector('[name="company_address"]')?.value || '';

        livePreview.innerHTML = `
        <div style="background:${color};padding:20px;border-radius:16px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    ${logoSrc ? `<img src="${logoSrc}" style="height:48px;margin-bottom:10px;" alt="Logo">` : `<div style="font-size:20px;font-weight:800;color:${headerTextColor};">${company}</div>`}
                    <div style="font-size:12px;color:${headerTextColor};opacity:.9;white-space:pre-line;">${address}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:28px;font-weight:800;color:${headerTextColor};">QUOTATION</div>
                    <div style="font-size:14px;color:${headerTextColor};opacity:.9;">${qNo}</div>
                    <div style="font-size:12px;color:${headerTextColor};opacity:.8;">Date: ${new Date().toLocaleDateString('en-IN')}</div>
                </div>
            </div>
        </div>

        <div style="height:4px;background:${color};border-radius:2px;margin-bottom:20px;"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div>
                <div style="font-size:10px;font-weight:700;color:#9e9e9e;text-transform:uppercase;margin-bottom:4px;">Bill To</div>
                <div style="font-size:13px;font-weight:600;">Sample Client Pvt Ltd</div>
                <div style="font-size:12px;color:#7e7e7e;">123 Client Street, Chennai</div>
            </div>
            <div>
                <div style="font-size:10px;font-weight:700;color:#9e9e9e;text-transform:uppercase;margin-bottom:4px;">Valid Until</div>
                <div style="font-size:13px;">30 days from date</div>
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            <thead>
                <tr style="background:${color}15;">
                    <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;color:${color};text-align:left;">#</th>
                    <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;color:${color};text-align:left;">Description</th>
                    <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;color:${color};text-align:right;">Qty</th>
                    <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;color:${color};text-align:right;">Unit Price</th>
                    <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;color:${color};text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:10px 12px;font-size:13px;border-bottom:1px solid #f5f5f5;">1</td>
                    <td style="padding:10px 12px;font-size:13px;border-bottom:1px solid #f5f5f5;">Website Design & Development</td>
                    <td style="padding:10px 12px;font-size:13px;border-bottom:1px solid #f5f5f5;text-align:right;">1</td>
                    <td style="padding:10px 12px;font-size:13px;border-bottom:1px solid #f5f5f5;text-align:right;">₹50,000</td>
                    <td style="padding:10px 12px;font-size:13px;border-bottom:1px solid #f5f5f5;text-align:right;">₹50,000</td>
                </tr>
                <tr>
                    <td style="padding:10px 12px;font-size:13px;">2</td>
                    <td style="padding:10px 12px;font-size:13px;">Monthly Maintenance (3 months)</td>
                    <td style="padding:10px 12px;font-size:13px;text-align:right;">3</td>
                    <td style="padding:10px 12px;font-size:13px;text-align:right;">₹5,000</td>
                    <td style="padding:10px 12px;font-size:13px;text-align:right;">₹15,000</td>
                </tr>
            </tbody>
        </table>

        <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
            <div style="width:220px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;">
                    <span style="color:#7e7e7e;">Subtotal</span><span>₹65,000</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;">
                    <span style="color:#7e7e7e;">GST (18%)</span><span>₹11,700</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;padding:10px 0;border-top:2px solid ${color};margin-top:6px;color:${color};">
                    <span>TOTAL</span><span>₹76,700</span>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;padding-top:16px;border-top:1px solid #f0f0f0;">
            <div style="max-width:60%;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;margin-bottom:6px;color:${color};">Terms & Conditions</div>
                <div style="font-size:11px;color:#7e7e7e;">1. This quotation is valid for 30 days from the date of issue.</div>
                <div style="font-size:11px;color:#7e7e7e;">2. Payment: 50% advance, balance on delivery.</div>
            </div>
            <div style="text-align:right;">
                ${sigSrc ? `<img src="${sigSrc}" style="height:48px;margin-bottom:4px;" alt="Signature"><br>` : ''}
                <div style="font-size:11px;color:#9e9e9e;">Authorized Signatory</div>
                <div style="font-size:12px;font-weight:600;">${company}</div>
            </div>
        </div>`;
    }

    function refreshPreviewIfOpen() {
        if (previewModal && previewModal.classList.contains('show')) {
            buildPreview();
        }
    }

    function updatePreviewColor(hex) { refreshPreviewIfOpen(); }
    function updatePreviewColor2(hex) { refreshPreviewIfOpen(); }
    function updatePreviewHeaderTextColor(hex) { refreshPreviewIfOpen(); }
    function updatePreviewLogo(src)  { refreshPreviewIfOpen(); }

    // ─── Smooth scroll for nav items ─────────────────────────
    document.querySelectorAll('.settings-nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (!href || !href.startsWith('#')) return;
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
</script>
@endpush
