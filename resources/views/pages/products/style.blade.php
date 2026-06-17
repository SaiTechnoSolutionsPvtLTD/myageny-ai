<style>
/* ================================================================
   PRODUCT MASTER — product.css
   Matches myAgenci.ai design system (Inter, #fcfcfc bg, #fe5f04 accent)
   ================================================================ */

/* ── Page shell ────────────────────────────────────────────────── */
.pm-page-body {
    padding: 24px 32px 40px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── Breadcrumb / header (re-uses .top-header from app.blade) ──── */
.top-header { border-bottom: 1px solid #e1dee3;display:flex;justify-content:space-between;padding:10px }
.crumb-sep { color: #c8c8c8; margin: 0 4px; font-size: 13px; }
.crumb-item { color: #9e9e9e; font-size: 14px; text-decoration: none; }
.crumb-item.active { color: #121212; font-weight: 600; }
.crumb-item:hover:not(.active) { color: #fe5f04; }

/* ── Alerts ────────────────────────────────────────────────────── */
.pm-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 14px;
    line-height: 1.5;
}
.pm-alert--success {
    background: #f1fdf6;
    border: 1px solid #c3edd6;
    color: #1a6b45;
}
.pm-alert--error {
    background: #fff5f5;
    border: 1px solid #ffc0c0;
    color: #b00020;
}
.pm-alert svg { flex-shrink: 0; margin-top: 2px; }
.pm-error-list { margin: 4px 0 0 0; padding-left: 18px; }
.pm-error-list li { margin-bottom: 2px; }

/* ── Buttons ───────────────────────────────────────────────────── */
.pm-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.18s ease;
    white-space: nowrap;
}
.pm-btn--primary {
    background: #fe5f04;
    color: #fff;
    border: 1.5px solid #fe5f04;
}
.pm-btn--primary:hover { background: #e55203; border-color: #e55203; }
.pm-btn--outline {
    background: #fff;
    color: #fe5f04;
    border: 1.5px solid #fe5f04;
}
.pm-btn--outline:hover { background: #fff4ee; }
.pm-btn--ghost {
    background: transparent;
    color: #7c7c7c;
    border: 1.5px solid #e1dee3;
}
.pm-btn--ghost:hover { background: #f8f8f8; color: #121212; }
.pm-btn--danger {
    background: #fff5f5;
    color: #e53935;
    border: 1.5px solid #ffcdd2;
}
.pm-btn--danger:hover { background: #ffebee; border-color: #e53935; }
.pm-btn--sm { padding: 5px 12px; font-size: 13px; }
.pm-btn:disabled,
.pm-btn[disabled] { opacity: 0.45; cursor: not-allowed; pointer-events: none; }

/* ── Icon buttons ──────────────────────────────────────────────── */
.pm-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: #f4f4f4;
    border: 1px solid #e8e8e8;
    color: #7c7c7c;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
}
.pm-icon-btn:hover { background: #ebebeb; color: #121212; }
.pm-icon-btn--edit:hover { background: #fff4ee; border-color: #ffd5b8; color: #fe5f04; }
.pm-icon-btn--delete { background: transparent; border: none; }
.pm-icon-btn--delete:hover { background: #fff5f5; color: #e53935; }

/* ── Cards ─────────────────────────────────────────────────────── */
.pm-card {
    background: #fff;
    border: 1px solid #e1dee3;
    border-radius: 14px;
    overflow: hidden;
}
.pm-card--form { padding: 28px 32px; }
.pm-card--detail { padding: 20px 24px; margin-bottom: 0; }
.pm-card--meta { background: #fafafa; }

.pm-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 24px;
    margin-bottom: 24px;
    border-bottom: 1px solid #f0f0f0;
}
.pm-card-header__icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #fff4ee;
    border: 1px solid #ffd5b8;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fe5f04;
    flex-shrink: 0;
}
.pm-card-header__icon--edit {
    background: #f0f4ff;
    border-color: #c5d2ff;
    color: #3b5bdb;
}
.pm-card-title { font-size: 18px; font-weight: 700; color: #121212; margin-bottom: 2px; }
.pm-card-subtitle { font-size: 13px; color: #9e9e9e; }

/* ── Filter bar ────────────────────────────────────────────────── */
.pm-filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.pm-filter-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1; }
.pm-filter-group { display: flex; }
.pm-result-count { font-size: 13px; color: #9e9e9e; margin-left: auto; }

/* ── Form inputs ───────────────────────────────────────────────── */
.pm-input,
.pm-select,
.pm-textarea {
    display: block;
    width: 100%;
    padding: 9px 13px;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    color: #121212;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    appearance: none;
    -webkit-appearance: none;
}
.pm-input:focus,
.pm-select:focus,
.pm-textarea:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254,95,4,0.10);
}
.pm-input.is-invalid,
.pm-select.is-invalid { border-color: #e53935; }
.pm-input--sm { width: 220px; }
.pm-select--sm { width: 180px; }
.pm-select--lg { width: 100%; }
.pm-select--type { width: 110px; flex-shrink: 0; border-radius: 10px 0 0 10px; border-right: none; }
.pm-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239e9e9e' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 32px;
}
.pm-textarea { resize: vertical; min-height: 100px; }
.pm-input-prefix { position: relative; display: flex; align-items: stretch; }
.pm-prefix {
    display: flex;
    align-items: center;
    padding: 0 10px;
    background: #f8f8f8;
    border: 1px solid #ddd;
    border-right: none;
    border-radius: 10px 0 0 10px;
    font-size: 14px;
    color: #7c7c7c;
    font-weight: 600;
}
.pm-input--prefixed { border-radius: 0 10px 10px 0; }
.pm-combo-row { display: flex; }
.pm-combo-row .pm-input {
    border-radius: 0 10px 10px 0;
    flex: 1;
}

/* ── Form layout ───────────────────────────────────────────────── */
.pm-form-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 32px;
    margin-bottom: 28px;
}
.pm-form-col { display: flex; flex-direction: column; gap: 18px; }
.pm-field { display: flex; flex-direction: column; gap: 6px; }
.pm-field--sm { max-width: 140px; }
.pm-label {
    font-size: 13px;
    font-weight: 600;
    color: #3c3c3c;
}
.pm-label--required::after {
    content: ' *';
    color: #fe5f04;
}
.pm-error { font-size: 12px; color: #e53935; margin-top: 2px; }
.pm-help { font-size: 12px; color: #8a8a8a; margin-top: 2px; }

.select2-container--default .select2-selection--multiple.pm-select2-selection {
    min-height: 44px;
    border: 1px solid #ddd;
    border-radius: 10px;
    background: #fff;
    padding: 4px 38px 4px 8px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.select2-container--default.select2-container--focus .select2-selection--multiple.pm-select2-selection,
.select2-container--default.select2-container--open .select2-selection--multiple.pm-select2-selection {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254,95,4,0.10);
}
.select2-container--default .select2-selection--multiple.pm-select2-selection .select2-selection__rendered {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    padding: 0;
    margin: 0;
}
.select2-container--default .select2-selection--multiple.pm-select2-selection .select2-selection__choice {
    margin-top: 0;
    border: 1px solid #ffd5b8;
    background: #fff4ee;
    color: #c44e11;
    border-radius: 999px;
    padding: 4px 10px 4px 8px;
    font-size: 12px;
    font-weight: 600;
}
.select2-container--default .select2-selection--multiple.pm-select2-selection .select2-selection__choice__remove {
    border-right: none;
    color: #c44e11;
    margin-right: 6px;
    padding-right: 0;
}
.select2-container--default .select2-selection--multiple.pm-select2-selection .select2-search--inline .select2-search__field {
    margin-top: 0;
    font-size: 14px;
    font-family: inherit;
}
.select2-container--default .select2-selection--multiple.pm-select2-selection .select2-selection__placeholder {
    color: #9e9e9e;
}
.select2-dropdown {
    border: 1px solid #e1dee3;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(18,18,18,.08);
}
.select2-search--dropdown {
    padding: 10px;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #e1dee3;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
}
.select2-results__option {
    font-size: 13px;
    padding: 9px 11px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #fe5f04;
    color: #fff;
}
.select2-container--open {
    z-index: 9999;
}
.select2-results__options {
    max-height: 260px !important;
}
.select2-container--default .select2-results__option--selectable {
    color: #121212;
    background: #fff;
}

/* ── Pricing box ───────────────────────────────────────────────── */
.pm-pricing-box {
    background: #fafafa;
    border: 1px solid #ebebeb;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.pm-pricing-box__title {
    font-size: 15px;
    font-weight: 700;
    color: #121212;
    margin-bottom: 4px;
}
.pm-final-price-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #fff4ee 0%, #fff 100%);
    border: 1.5px solid #ffd5b8;
    border-radius: 10px;
    padding: 14px 16px;
    margin-top: 4px;
}
.pm-final-price-label { font-size: 14px; font-weight: 600; color: #7c7c7c; }
.pm-final-price-value {
    font-size: 22px;
    font-weight: 700;
    color: #fe5f04;
    font-variant-numeric: tabular-nums;
}

/* ── Section heading ───────────────────────────────────────────── */
.pm-section { margin-top: 8px; }
.pm-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}
.pm-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 700;
    color: #121212;
}
.pm-section-hint { font-size: 13px; color: #b0b0b0; flex: 1; }

/* ── Attribute rows ────────────────────────────────────────────── */
.pm-attr-rows { display: flex; flex-direction: column; gap: 8px; }
.pm-attr-row {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fafafa;
    border: 1px solid #ebebeb;
    border-radius: 10px;
    padding: 10px 12px;
    transition: border-color 0.15s, box-shadow 0.15s;
    animation: rowAppear 0.2s ease;
}
@keyframes rowAppear {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.pm-attr-row:hover { border-color: #e0d0ff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.pm-attr-row__drag {
    color: #c8c8c8;
    cursor: grab;
    flex-shrink: 0;
    padding: 0 4px;
}
.pm-attr-row__select { flex: 0 0 240px; }
.pm-attr-row__value  { flex: 1; }
.pm-attr-row__unit   {
    min-width: 60px;
    font-size: 12px;
    color: #9e9e9e;
    font-weight: 600;
    flex-shrink: 0;
}
.pm-no-attrs {
    font-size: 14px;
    color: #b0b0b0;
    text-align: center;
    padding: 20px 0;
    border: 1.5px dashed #e1dee3;
    border-radius: 10px;
}

/* ── Form actions footer ───────────────────────────────────────── */
.pm-form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 24px;
    margin-top: 8px;
    border-top: 1px solid #f0f0f0;
}

/* ── Index table ───────────────────────────────────────────────── */
.pm-table-wrap { overflow-x: auto; }
.pm-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 6px;
    font-size: 13.5px;
}
.pm-table thead th {
    background: #f8f8f8;
    padding: 10px 14px;
    font-size: 12px;
    color: #7c7c7c;
    font-weight: 600;
    text-align: left;
    border-top: 1px solid #f1f1f1;
    border-bottom: 1px solid #f1f1f1;
    white-space: nowrap;
}
.pm-table thead th:first-child { border-left: 1px solid #f1f1f1; border-radius: 8px 0 0 8px; }
.pm-table thead th:last-child  { border-right: 1px solid #f1f1f1; border-radius: 0 8px 8px 0; }
.pm-table tbody tr td {
    background: #fff;
    padding: 11px 14px;
    border-top: 1px solid #f5f5f5;
    border-bottom: 1px solid #f5f5f5;
    vertical-align: middle;
}
.pm-table tbody tr td:first-child { border-left: 1px solid #f5f5f5; border-radius: 8px 0 0 8px; }
.pm-table tbody tr td:last-child  { border-right: 1px solid #f5f5f5; border-radius: 0 8px 8px 0; }
.pm-table tbody tr:hover td { background: #fff8f4; border-color: #ffe0cc; }
.pm-table--attrs { border-spacing: 0; }
.pm-table--attrs thead th,
.pm-table--attrs tbody td {
    border: none;
    border-bottom: 1px solid #f0f0f0;
    border-radius: 0;
}
.pm-table--attrs tbody tr:last-child td { border-bottom: none; }

.pm-product-name {
    font-weight: 600;
    color: #121212;
    text-decoration: none;
}
.pm-product-name:hover { color: #fe5f04; }
.pm-sku {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: 12px;
    background: #f4f4f4;
    padding: 2px 7px;
    border-radius: 6px;
    color: #555;
    white-space: nowrap;
}
.pm-price { font-variant-numeric: tabular-nums; color: #121212; font-weight: 500; }
.pm-price--final { color: #fe5f04; font-weight: 700; }
.pm-muted { color: #9e9e9e; }
.pm-muted--italic { font-style: italic; }
.pm-category-chip {
    display: inline-block;
    background: #f0ecfa;
    color: #60308c;
    border: 1px solid #e0d4f5;
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}
.pm-actions { display: flex; align-items: center; gap: 4px; }

/* ── Status badges ─────────────────────────────────────────────── */
.pm-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.pm-badge--active   { background: #f1fdf6; color: #1a6b45; border: 1px solid #c3edd6; }
.pm-badge--inactive { background: #fff5f5; color: #b00020; border: 1px solid #ffc0c0; }
.pm-badge--draft    { background: #fffbf0; color: #7a5c00; border: 1px solid #ffe49a; }

/* ── Pagination ────────────────────────────────────────────────── */
.pm-pagination { padding: 16px 20px; display: flex; justify-content: flex-end; }
.pm-pagination nav { display: flex; gap: 4px; }

/* ── Empty state ───────────────────────────────────────────────── */
.pm-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    gap: 10px;
    text-align: center;
}
.pm-empty__icon { font-size: 48px; margin-bottom: 6px; }
.pm-empty h3 { font-size: 18px; font-weight: 700; color: #121212; }
.pm-empty p  { font-size: 14px; color: #9e9e9e; margin-bottom: 8px; }

/* ── Show page layout ──────────────────────────────────────────── */
.pm-show-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 24px;
    align-items: start;
}

/* ── Package Summary Card ──────────────────────────────────────── */
.pm-summary-card {
    background: #fff;
    border: 1px solid #e1dee3;
    border-radius: 18px;
    overflow: hidden;
    position: sticky;
    top: 88px;
}
.pm-summary-ribbon {
    background: linear-gradient(90deg, #60308c 0%, #a060d8 100%);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    text-align: center;
    padding: 8px 16px;
}
.pm-summary-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px 20px 16px;
    gap: 10px;
}
.pm-tier-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.pm-tier-icon--gold     { background: linear-gradient(135deg, #fff9e6 0%, #ffe49a 100%); color: #c99411; border: 2px solid #f0cc5a; }
.pm-tier-icon--silver   { background: linear-gradient(135deg, #f4f4f4 0%, #ddd 100%);    color: #707070; border: 2px solid #c8c8c8; }
.pm-tier-icon--platinum { background: linear-gradient(135deg, #e8f4ff 0%, #b8d8f8 100%); color: #2060a8; border: 2px solid #90c0e8; }
.pm-tier-icon--default  { background: #f0ecfa; color: #60308c; border: 2px solid #ddd4f5; }
.pm-summary-package { font-size: 20px; font-weight: 800; color: #121212; text-align: center; }

.pm-summary-price-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 20px 16px;
    gap: 4px;
}
.pm-summary-price {
    font-size: 32px;
    font-weight: 800;
    color: #fe5f04;
    font-variant-numeric: tabular-nums;
    line-height: 1;
}
.pm-summary-original {
    font-size: 16px;
    color: #b0b0b0;
    text-decoration: line-through;
}
.pm-summary-savings {
    font-size: 12px;
    font-weight: 700;
    color: #1a6b45;
    background: #f1fdf6;
    border: 1px solid #c3edd6;
    padding: 2px 10px;
    border-radius: 20px;
}

.pm-summary-breakdown {
    margin: 0 16px 16px;
    background: #fafafa;
    border: 1px solid #ebebeb;
    border-radius: 10px;
    overflow: hidden;
}
.pm-breakdown-row {
    display: flex;
    justify-content: space-between;
    padding: 9px 14px;
    font-size: 13px;
    border-bottom: 1px solid #f0f0f0;
    color: #5c5c5c;
}
.pm-breakdown-row:last-child { border-bottom: none; }
.pm-breakdown-row--discount { color: #1a6b45; }
.pm-breakdown-row--tax      { color: #7a5c00; }
.pm-breakdown-row--total    {
    font-weight: 700;
    font-size: 14px;
    color: #121212;
    background: #fff4ee;
}

.pm-summary-meta {
    margin: 0 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pm-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}
.pm-meta-label { color: #9e9e9e; font-weight: 500; }

.pm-summary-features {
    border-top: 1px solid #f0f0f0;
    padding: 16px;
}
.pm-features-title {
    font-size: 13px;
    font-weight: 700;
    color: #7c7c7c;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}
.pm-features-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 7px; }
.pm-feature-item {
    display: flex;
    align-items: baseline;
    gap: 8px;
    font-size: 13px;
}
.pm-feature-check { color: #1a6b45; flex-shrink: 0; margin-top: 1px; }
.pm-feature-name  { color: #5c5c5c; min-width: 90px; }
.pm-feature-value { color: #121212; font-weight: 600; margin-left: auto; text-align: right; }
.pm-feature-unit  { color: #9e9e9e; font-weight: 400; font-size: 11px; }

/* ── Detail panel ──────────────────────────────────────────────── */
.pm-detail-panel { display: flex; flex-direction: column; gap: 16px; }
.pm-detail-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #121212;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}
.pm-description {
    font-size: 14px;
    color: #4c4c4c;
    line-height: 1.7;
    white-space: pre-wrap;
}
.pm-sys-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.pm-sys-item { display: flex; flex-direction: column; gap: 3px; }
.pm-sys-label { font-size: 12px; color: #9e9e9e; font-weight: 500; }
.pm-sys-value { font-size: 13px; color: #121212; font-weight: 600; }

/* ── Loading spinner for AJAX ──────────────────────────────────── */
.pm-spinner {
    display: inline-block;
    width: 18px; height: 18px;
    border: 2px solid #f0f0f0;
    border-top-color: #fe5f04;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ────────────────────────────────────────────────── */
@media (max-width: 1100px) {
    .pm-form-grid  { grid-template-columns: 1fr; }
    .pm-show-grid  { grid-template-columns: 1fr; }
    .pm-summary-card { position: static; }
}
@media (max-width: 768px) {
    .pm-page-body { padding: 16px; }
    .pm-card--form { padding: 18px; }
    .pm-filter-bar { flex-direction: column; align-items: stretch; }
    .pm-filter-form { flex-direction: column; }
    .pm-input--sm, .pm-select--sm { width: 100%; }
}
</style>
