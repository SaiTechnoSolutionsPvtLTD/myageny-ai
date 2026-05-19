<style>
.intern-page {
    font-family: var(--font-family);
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background:
        radial-gradient(circle at top left, rgba(254,95,4,.08), transparent 28%),
        linear-gradient(180deg, #fff7f1 0%, #f7f3ef 38%, #f4f5f7 100%);
}
.intern-shell {
    width: 100%;
    max-width: 1440px;
    margin: 0 auto;
}
.intern-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 24px 28px 22px;
    margin: 22px 28px 0;
    min-height: 60px;
    background: rgba(255,255,255,.88);
    border: 1px solid rgba(225,222,227,.8);
    border-radius: 24px;
    backdrop-filter: blur(10px);
    box-shadow: 0 18px 45px rgba(18,18,18,.06);
}
.intern-topbar-copy {
    max-width: 760px;
}
.intern-eyebrow {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    margin-bottom: 10px;
    background: #fff1e8;
    color: #c25513;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.intern-title {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-primary, #121212);
    line-height: 1.1;
}
.intern-subtitle {
    margin-top: 8px;
    color: #6f6f6f;
    font-size: 14px;
    line-height: 1.6;
}
.intern-body {
    padding: 22px 28px 34px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.intern-card {
    border: 1px solid var(--border-color, #e1dee3);
    border-radius: 22px;
    background: rgba(255,255,255,.94);
    overflow: hidden;
    box-shadow: 0 18px 34px rgba(18,18,18,.04);
}
.intern-overview-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}
.intern-overview-card {
    padding: 18px 20px;
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(255,255,255,.96) 0%, rgba(255,247,241,.92) 100%);
    border: 1px solid rgba(236,225,217,.92);
    box-shadow: 0 14px 28px rgba(18,18,18,.04);
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.intern-overview-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #b56b3c;
}
.intern-overview-value {
    font-size: 28px;
    font-weight: 800;
    color: #121212;
    line-height: 1;
}
.intern-overview-meta {
    font-size: 12px;
    line-height: 1.5;
    color: #7c7c7c;
}
.intern-list-card {
    border-radius: 24px;
}
.intern-card-head {
    padding: 22px 24px 18px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    border-bottom: 1px solid #f1ece8;
}
.intern-card-title {
    font-size: 18px;
    font-weight: 800;
    color: #121212;
}
.intern-card-subtitle {
    margin-top: 6px;
    color: #7c7c7c;
    font-size: 13px;
    line-height: 1.6;
    max-width: 720px;
}
.intern-results-chip {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: #fff4eb;
    color: #c55d1c;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}
.intern-filter-wrap {
    padding: 18px 24px 0;
}
.intern-filter-form {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.intern-filter-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 280px;
    flex: 1;
}
.intern-filter-label {
    font-size: 12px;
    font-weight: 800;
    color: #8a7466;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.intern-filter-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.intern-list-table-wrap {
    margin: 18px 24px 24px;
    border-radius: 18px !important;
    background: #fff;
}
.intern-list-table {
    width: 100%;
    min-width: 100% !important;
}
.intern-list-table thead th {
    padding: 14px 16px !important;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8f8f8f !important;
    background: #fbfaf9 !important;
}
.intern-list-table tbody td {
    padding: 16px !important;
}
.intern-row-index {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff4eb;
    color: #c55d1c;
    font-size: 12px;
    font-weight: 800;
}
.intern-person-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 220px;
}
.intern-person-avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #fe5f04, #ff9a52);
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 10px 20px rgba(254,95,4,.16);
}
.intern-person-name,
.intern-contact-main,
.intern-date-main {
    font-size: 14px;
    font-weight: 700;
    color: #121212;
}
.intern-person-sub,
.intern-contact-sub,
.intern-date-sub {
    margin-top: 4px;
    font-size: 12px;
    color: #8a8a8a;
    line-height: 1.5;
}
.intern-action-group {
    display: inline-flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.intern-empty-state {
    display: inline-flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
}
.intern-empty-state strong {
    font-size: 16px;
    color: #121212;
}
.intern-empty-state span {
    font-size: 13px;
    color: #8a8a8a;
}
.intern-page *, .intern-page *::before, .intern-page *::after {
    box-sizing: border-box;
}
.form-grid,
.review-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.review-grid {
    gap: 18px;
}
.grid-half,
.grid-full {
    min-width: 0;
}
.grid-full {
    grid-column: 1 / -1;
}
.intern-page .d-flex { display: flex; }
.intern-page .d-inline-flex { display: inline-flex; }
.intern-page .justify-content-between { justify-content: space-between; }
.intern-page .justify-content-center { justify-content: center; }
.intern-page .align-items-center { align-items: center; }
.intern-page .gap-2 { gap: 8px; }
.intern-page .mb-0 { margin-bottom: 0; }
.intern-page .mb-2 { margin-bottom: 8px; }
.intern-page .mb-3 { margin-bottom: 16px; }
.intern-page .mb-4 { margin-bottom: 18px; }
.intern-page .mt-1 { margin-top: 4px; }
.intern-page .mt-2 { margin-top: 8px; }
.intern-page .pt-3 { padding-top: 16px; }
.intern-page .p-3 { padding: 18px; }
.intern-page .p-4 { padding: 22px; }
.intern-page .py-5 { padding-top: 56px; padding-bottom: 56px; }
.intern-page .h-100 { height: 100%; }
.intern-page .border { border: 1px solid #f0eef2; }
.intern-page .border-top { border-top: 1px solid #f0eef2; }
.intern-page .rounded-4 { border-radius: 16px; }
.intern-page .bg-light { background: #fafafa; }
.intern-page .small { font-size: 12px; }
.intern-page .text-center { text-align: center; }
.intern-page .text-end { text-align: right; }
.intern-page .text-uppercase { text-transform: uppercase; }
.intern-page .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid transparent;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: all .18s ease;
}
.intern-page .btn-sm {
    padding: 7px 12px;
    font-size: 12px;
    border-radius: 9px;
}
.intern-page .btn-success {
    background: linear-gradient(135deg,#fe5f04,#ff7c30);
    color: #fff;
    border-color: transparent;
}
.intern-page .btn-success:hover,
.intern-page .btn-success:focus {
    box-shadow: 0 10px 18px rgba(254,95,4,.16);
    transform: translateY(-1px);
}
.intern-page .d-none { display: none !important; }
.intern-page .fw-bold,
.intern-page .fw-semibold { font-weight: 700; }
.intern-page .fs-4 { font-size: 22px; }
.intern-page .fs-5 { font-size: 18px; }
.intern-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #444;
    margin-bottom: 8px;
}
.intern-label-required {
    color: #dc2626;
    margin-left: 4px;
    font-weight: 800;
}
.intern-required-note {
    margin-top: 10px;
    font-size: 12px;
    color: #8a8a8a;
}
.field-help {
    margin: -3px 0 10px;
    font-size: 12px;
    color: #8a8a8a;
    line-height: 1.45;
}
.field-surface {
    padding: 14px;
    border-radius: 16px;
    background: linear-gradient(180deg, #fffaf7 0%, #ffffff 100%);
    border: 1px dashed #efd6c4;
}
.upload-surface {
    min-height: 100%;
}
.intern-textarea {
    min-height: 110px;
    resize: vertical;
}
.intern-choice {
    display: flex;
    align-items: center;
    gap: 8px;
}
.intern-choice-inline {
    display: inline-flex;
    margin-right: 18px;
}
.intern-choice-label {
    font-size: 13px;
    color: #444;
    font-weight: 600;
}
.intern-choice-input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: #fe5f04;
    flex-shrink: 0;
}
.intern-check-card {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.intern-page .table-hover tbody tr:hover td {
    background: #fdf9f6;
}
.intern-page .align-middle td,
.intern-page .align-middle th {
    vertical-align: middle;
}
.intern-page .table-bordered {
    border-collapse: collapse;
}
.intern-page hr {
    border: 0;
    border-top: 1px solid #f0eef2;
    margin: 16px 0;
}
.intern-alert {
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 13px;
    border: 1px solid transparent;
}
.intern-alert-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
}
.intern-alert-success {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}
.wizard-validation-summary {
    display: none;
    padding: 14px 16px;
    border: 1px solid #fecaca;
    border-radius: 14px;
    background: #fef2f2;
    color: #b91c1c;
}
.wizard-validation-summary.is-visible {
    display: block;
}
.wizard-validation-summary strong {
    display: block;
    margin-bottom: 8px;
}
.wizard-validation-summary ul {
    margin: 0;
    padding-left: 18px;
}
.wizard-validation-summary li + li {
    margin-top: 4px;
}
.wizard-layout {
    display: grid;
    grid-template-columns: 320px minmax(0, 1fr);
    gap: 22px;
    align-items: start;
    padding: 18px;
}
.wizard-sidebar {
    padding: 24px;
    position: sticky;
    top: 18px;
    align-self: start;
    border: 1px solid #eadfd8;
    border-radius: 20px;
    background:
        linear-gradient(180deg, #fff7f1 0%, #ffffff 100%);
    box-shadow: 0 16px 38px rgba(18,18,18,.05);
}
.wizard-sidebar-note {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 14px 16px;
    margin-bottom: 18px;
    border-radius: 16px;
    background: rgba(255,255,255,.78);
    border: 1px solid #f0e2d7;
}
.wizard-sidebar-note strong {
    font-size: 12px;
    color: #121212;
}
.wizard-sidebar-note span {
    font-size: 12px;
    line-height: 1.5;
    color: #7a7a7a;
}
.wizard-main {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-width: 0;
}
.wizard-header-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 20px 22px;
    background: rgba(255,255,255,.92);
    border: 1px solid #e1dee3;
    border-radius: 20px;
}
.wizard-header-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 190px;
}
.wizard-header-meta span {
    font-size: 12px;
    font-weight: 700;
    color: #7c7c7c;
    text-align: right;
}
.wizard-step-btn {
    width: 100%;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border: 1px solid #ece5e0;
    border-radius: 14px;
    background: #fff;
    text-align: left;
    margin-bottom: 12px;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.wizard-step-btn.is-active {
    border-color: #fe5f04;
    box-shadow: 0 10px 24px rgba(254,95,4,.12);
    background: #fff8f4;
}
.wizard-step-btn.is-complete {
    border-color: #ece5e0;
    background: #fff;
}
.wizard-step-btn:hover {
    transform: translateY(-1px);
    border-color: #ffcfb0;
}
.wizard-step-no {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 1px solid #ece5e0;
    background: #faf7f4;
    color: #8a7466;
    font-size: 12px;
    font-weight: 800;
}
.wizard-step-btn.is-active .wizard-step-no {
    background: #fe5f04;
    color: #fff;
    border-color: #fe5f04;
}
.wizard-step-btn.is-complete .wizard-step-no {
    background: #fe5f04;
    color: #fff;
    border-color: #fe5f04;
}
.wizard-step-copy {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}
.wizard-step-copy strong {
    font-size: 13px;
    font-weight: 700;
    color: #121212;
}
.wizard-step-copy small {
    font-size: 11px;
    line-height: 1.45;
    color: #8a8a8a;
}
.wizard-panel {
    display: none;
}
.wizard-panel.is-active {
    display: block;
    animation: internWizardFade .22s ease;
}
.panel-intro {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid #f2ece8;
}
.panel-eyebrow {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #c25513;
    margin-bottom: 8px;
}
.panel-title {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #121212;
}
.panel-subtitle {
    margin: 8px 0 0;
    max-width: 720px;
    color: #777;
    font-size: 14px;
    line-height: 1.6;
}
.wizard-progress {
    height: 8px;
    border-radius: 999px;
    background: #f1ece8;
    overflow: hidden;
}
.wizard-progress > span {
    display: block;
    height: 100%;
    background: linear-gradient(135deg,#fe5f04,#ff9a52);
    transition: width .24s ease;
}
.intern-doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}
.intern-doc-card {
    border: 1px solid #f0eef2;
    border-radius: 18px;
    padding: 16px;
    background: linear-gradient(180deg, #fffaf7 0%, #fafafa 100%);
}
.signature-pad-wrap {
    border: 1px dashed #f0d8c6;
    border-radius: 16px;
    background: linear-gradient(180deg,#fffaf6 0%,#ffffff 100%);
    padding: 12px;
}
.signature-pad {
    width: 100%;
    height: 180px;
    border: 1px solid var(--border-color, #e1dee3);
    border-radius: 12px;
    background: #fff;
    touch-action: none;
}
.review-list dt {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9e9e9e;
    margin-bottom: 4px;
}
.review-list dd {
    font-size: 14px;
    color: #121212;
    margin-bottom: 14px;
}
.intern-avatar-preview {
    width: 96px;
    height: 96px;
    border-radius: 20px;
    object-fit: cover;
    border: 1px solid var(--border-color, #e1dee3);
}
.intern-page .btn-primary {
    background: linear-gradient(135deg,#fe5f04,#ff7c30);
    border-color: transparent;
}
.intern-page .btn-primary:hover,
.intern-page .btn-primary:focus {
    background: linear-gradient(135deg,#fe5f04,#ff7c30);
    box-shadow: 0 10px 18px rgba(254,95,4,.16);
    transform: translateY(-1px);
}
.intern-page .btn-outline-primary {
    color: #fe5f04;
    border-color: #fdba74;
    background: #fff;
}
.intern-page .btn-outline-primary:hover,
.intern-page .btn-outline-primary:focus {
    background: #fff7ed;
    color: #fe5f04;
    border-color: #fdba74;
}
.intern-page .btn-outline-secondary {
    color: #121212;
    background: #fff;
    border-color: #e1dee3;
}
.intern-page .btn-outline-secondary:hover,
.intern-page .btn-outline-secondary:focus {
    background: #fafafa;
    color: #121212;
    border-color: #e1dee3;
}
.intern-page .btn-outline-danger {
    color: #be123c;
    background: #fff1f2;
    border-color: #fecdd3;
}
.intern-page .btn-outline-danger:hover,
.intern-page .btn-outline-danger:focus {
    color: #be123c;
    background: #ffe4e6;
    border-color: #fda4af;
}
.intern-page .table-light,
.intern-page .table-light > th,
.intern-page .table-light > td {
    background: #fafafa !important;
    color: #9e9e9e;
}
.intern-input,
.intern-select {
    display: block;
    width: 100%;
    max-width: 100%;
    border-color: var(--border-color, #e1dee3);
    border-radius: 12px;
    color: #121212;
    padding: 11px 12px;
    font-size: 14px;
    background: #fff;
    border: 1px solid var(--border-color, #e1dee3);
    font-family: inherit;
    line-height: 1.4;
    transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
}
.intern-input,
.intern-select {
    width: 100%;
    max-width: 100%;
}
.intern-file-input {
    padding: 10px 12px;
    min-height: 46px;
    cursor: pointer;
}
.intern-textarea {
    width: 100%;
    max-width: 100%;
}
.intern-input:focus,
.intern-select:focus,
.intern-choice-input:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254,95,4,.1);
    outline: none;
}
.intern-input[readonly] {
    background: #f8f5f2;
    color: #6f6f6f;
}
.intern-input.is-invalid,
.intern-select.is-invalid,
.intern-choice-input.is-invalid,
.signature-pad-wrap.is-invalid {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(220,38,38,.08);
}
.intern-page .table {
    margin-bottom: 0;
    min-width: 760px;
}
.intern-page .table > :not(caption) > * > * {
    padding: 11px 12px;
    border-bottom-color: #f0eef2;
}
.intern-page .table tbody tr:last-child td {
    border-bottom: 0;
}
.intern-page .table-responsive {
    border: 1px solid #f0eef2;
    border-radius: 14px;
}
.intern-page .text-secondary {
    color: #7c7c7c !important;
}
.intern-page .text-dark,
.intern-page .fw-bold,
.intern-page .fw-semibold {
    color: #121212;
}
.wizard-footer {
    position: sticky;
    bottom: 14px;
    z-index: 5;
    backdrop-filter: blur(12px);
}
.intern-topbar-action {
    flex-shrink: 0;
}
.intern-show-layout {
    align-items: start;
}
.intern-show-sidebar {
    overflow: visible;
}
.intern-show-main {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-width: 0;
}
.intern-show-avatar {
    width: 92px;
    height: 92px;
    border-radius: 24px;
}
.intern-show-avatar span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}
.intern-section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 22px 24px 18px;
    border-bottom: 1px solid #f1ece8;
}
.intern-section-title {
    font-size: 18px;
    font-weight: 800;
    color: #121212;
}
.intern-section-subtitle {
    margin-top: 6px;
    font-size: 13px;
    line-height: 1.6;
    color: #7c7c7c;
}
.intern-detail-grid .eob-show-item {
    min-height: 100%;
    background: linear-gradient(180deg, #fcfbfa 0%, #ffffff 100%);
}
.intern-show-table-wrap {
    border-radius: 18px;
    background: #fff;
}
.intern-show-table {
    min-width: 780px !important;
}
.intern-show-table thead th {
    padding: 14px 16px !important;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8f8f8f !important;
    background: #fbfaf9 !important;
}
.intern-show-table tbody td {
    padding: 15px 16px !important;
    vertical-align: middle;
}
.intern-doc-list {
    gap: 14px;
}
.intern-status-pill {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: #fff4eb;
    color: #c55d1c;
}
.intern-status-pill.is-muted {
    background: #f5f5f5;
    color: #8a8a8a;
}
@media (max-width: 960px) {
    .intern-topbar {
        margin: 16px 20px 0;
        padding: 18px 20px;
    }
    .intern-body {
        padding: 16px 20px 24px;
    }
    .intern-overview-grid {
        grid-template-columns: 1fr;
    }
    .intern-card-head,
    .intern-filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    .intern-list-table-wrap {
        margin: 16px 20px 20px;
    }
    .wizard-layout {
        grid-template-columns: 1fr;
        padding: 14px;
    }
    .wizard-sidebar {
        position: static;
    }
    .wizard-header-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .wizard-header-meta {
        min-width: 100%;
    }
    .wizard-header-meta span {
        text-align: left;
    }
    .intern-section-head {
        padding: 18px 20px 16px;
    }
}
@media (max-width: 992px) {
    .wizard-sidebar {
        border-bottom: 1px solid #e1dee3;
    }
}
@media (max-width: 768px) {
    .form-grid,
    .review-grid {
        grid-template-columns: 1fr;
    }
    .intern-filter-wrap {
        padding: 16px 20px 0;
    }
    .intern-filter-field {
        min-width: 100%;
    }
    .intern-action-group,
    .intern-filter-actions {
        width: 100%;
    }
    .intern-action-group .btn,
    .intern-filter-actions .btn {
        flex: 1;
    }
    .intern-topbar {
        flex-direction: column;
        align-items: flex-start;
    }
    .intern-title {
        font-size: 24px;
    }
    .panel-title {
        font-size: 20px;
    }
    .wizard-footer {
        flex-direction: column;
        align-items: stretch;
        position: static;
    }
    .intern-show-main {
        gap: 16px;
    }
    .wizard-footer-actions {
        width: 100%;
    }
    .wizard-footer-actions .btn {
        flex: 1;
    }
}
@keyframes internWizardFade {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
