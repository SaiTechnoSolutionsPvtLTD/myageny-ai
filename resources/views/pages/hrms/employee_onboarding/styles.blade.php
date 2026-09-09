<style>
.eob-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:var(--font-family, 'Inter', sans-serif); }
.eob-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.eob-title { font-size:18px; font-weight:800; color:#121212; }
.eob-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.eob-actions, .eob-inline-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.eob-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 16px; border-radius:10px; font-size:13px; font-weight:700; border:1px solid transparent; text-decoration:none; cursor:pointer; font-family:inherit; transition:all .18s ease; }
.eob-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; }
.eob-btn-primary:hover { transform:translateY(-1px); box-shadow:0 10px 18px rgba(254,95,4,.16); }
.eob-btn-ghost { background:#fff; color:#121212; border-color:#e1dee3; }
.eob-btn-danger { background:#fff1f2; color:#be123c; border-color:#fecdd3; }
.eob-btn-sm { padding:7px 12px; font-size:12px; border-radius:9px; }
.eob-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:16px; }
.eob-alert { padding:12px 16px; border-radius:12px; font-size:13px; border:1px solid transparent; }
.eob-alert-success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
.eob-alert-error { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
.eob-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.eob-card-head { padding:18px 22px; border-bottom:1px solid #f0eef2; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
.eob-card-title { font-size:16px; font-weight:700; color:#121212; }
.eob-card-sub { font-size:12px; color:#9e9e9e; margin-top:4px; }
.eob-card-body { padding:22px; }
.eob-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
.eob-group { display:flex; flex-direction:column; gap:6px; }
.eob-group.full { grid-column:1 / -1; }
.eob-label { font-size:13px; font-weight:700; color:#444; }
.eob-label-required { color:#dc2626; margin-left:4px; font-weight:800; }
.eob-help { font-size:11px; color:#9e9e9e; }
.eob-required-note { margin-top:8px; font-size:12px; color:#8a8a8a; }
.eob-input, .eob-select, .eob-textarea { width:100%; padding:11px 12px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; font-family:inherit; outline:none; background:#fff; color:#121212; }
.eob-textarea { min-height:110px; resize:vertical; }
.eob-input:focus, .eob-select:focus, .eob-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.eob-error { font-size:12px; color:#dc2626; }
.eob-input.is-invalid, .eob-select.is-invalid, .eob-textarea.is-invalid { border-color:#dc2626; box-shadow:0 0 0 3px rgba(220,38,38,.10); background:#fff8f8; }
.eob-foot { padding:18px 22px; border-top:1px solid #f0eef2; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
.eob-radio-group { display:flex; align-items:center; gap:18px; min-height:44px; flex-wrap:wrap; }
.eob-radio { display:inline-flex; align-items:center; gap:8px; font-size:13px; color:#444; font-weight:600; }
.eob-table-wrap { overflow-x:auto; border:1px solid #f0eef2; border-radius:14px; }
.eob-table { width:100%; border-collapse:collapse; min-width:760px; }
.eob-table th { padding:11px 12px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#9e9e9e; text-align:left; background:#fafafa; border-bottom:1px solid #f0eef2; }
.eob-table td { padding:10px 12px; border-bottom:1px solid #f7f6f9; vertical-align:top; }
.eob-table tr:last-child td { border-bottom:none; }
.eob-table .eob-input { min-width:140px; }
.eob-table .eob-textarea { min-height:88px; }
.eob-file-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
.eob-file-card { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; display:flex; flex-direction:column; gap:10px; }
.eob-file-meta { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.eob-file-name { font-size:12px; color:#121212; font-weight:700; }
.eob-file-links { display:flex; gap:8px; flex-wrap:wrap; }
.eob-chip { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.eob-chip-active { background:#f0fdf4; color:#15803d; }
.eob-chip-inactive { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.eob-chip-resigned { background:#fef2f2; color:#b91c1c; }
.eob-chip-pending { background:#fff7ed; color:#c2410c; }
.eob-chip-verified { background:#f0fdf4; color:#15803d; }
.eob-chip-rejected { background:#fef2f2; color:#b91c1c; }
.eob-chip-billable { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.eob-chip-non-billable { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.eob-filter-card, .eob-table-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.eob-filter-form { display:flex; gap:12px; flex-wrap:wrap; padding:16px; align-items:flex-end; }
.eob-field { display:flex; flex-direction:column; gap:6px; min-width:220px; flex:1; }
.eob-results { font-size:12px; color:#9e9e9e; }
.eob-list-table { width:100%; border-collapse:collapse; }
.eob-list-table th { padding:11px 16px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#9e9e9e; text-align:left; background:#fafafa; border-bottom:1px solid #f0eef2; }
.eob-list-table td { padding:14px 16px; border-bottom:1px solid #f7f6f9; font-size:13px; color:#121212; vertical-align:middle; }
.eob-list-table tbody tr:hover td { background:#fdf9f6; }
.eob-cell-title { font-size:13px; font-weight:700; color:#121212; }
.eob-cell-sub { font-size:11px; color:#9e9e9e; margin-top:2px; }
.eob-icon-btn { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:9px; border:1px solid #e1dee3; background:#fafafa; color:#666; cursor:pointer; }
.eob-icon-btn:hover { background:#fff7ed; color:#fe5f04; border-color:#fdba74; }
.eob-icon-btn.danger:hover { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.eob-table-dropdown { position:relative; display:inline-block; }
.eob-table-dropdown[open] { z-index:60; }
.eob-table-dropdown summary { list-style:none; }
.eob-table-dropdown summary::-webkit-details-marker { display:none; }
.eob-table-dropdown-trigger { min-width:42px; height:36px; padding:0 12px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border-radius:10px; border:1px solid #e1dee3; background:#fff; color:#121212; cursor:pointer; font-size:12px; font-weight:800; }
.eob-table-dropdown[open] .eob-table-dropdown-trigger,
.eob-table-dropdown-trigger:hover { background:#fff7ed; color:#fe5f04; border-color:#fdba74; }
.eob-table-dropdown-menu { position:absolute; right:0; top:calc(100% + 8px); min-width:170px; padding:8px; border-radius:14px; border:1px solid #ece7ec; background:#fff; box-shadow:0 16px 40px rgba(18,18,18,.15), 0 4px 12px rgba(0,0,0,.08); z-index:999; display:flex; flex-direction:column; gap:6px; }
.eob-table-dropdown.dropup .eob-table-dropdown-menu { top:auto !important; bottom:calc(100% + 8px) !important; box-shadow:0 -16px 40px rgba(18,18,18,.15), 0 -4px 12px rgba(0,0,0,.08) !important; }
.eob-table-dropdown-item { width:100%; display:flex; align-items:center; gap:8px; padding:9px 10px; border:1px solid transparent; border-radius:10px; background:#fff; color:#121212; text-decoration:none; font-size:13px; font-weight:700; cursor:pointer; text-align:left; }
.eob-table-dropdown-item:hover { background:#fff7ed; color:#fe5f04; border-color:#fed7aa; }
.eob-table-dropdown-item.danger:hover { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.eob-table-dropdown-menu form { margin:0; }
.eob-empty { text-align:center; padding:56px 20px; color:#9e9e9e; }
.eob-modal { position:fixed; inset:0; background:rgba(18,18,18,.42); display:none; align-items:center; justify-content:center; z-index:1400; padding:20px; }
.eob-modal.is-open { display:flex; }
.eob-modal-card { width:min(100%, 420px); background:#fff; border-radius:18px; border:1px solid #ece7ec; box-shadow:0 24px 60px rgba(18,18,18,.18); overflow:hidden; }
.eob-modal-head { padding:20px 22px 12px; }
.eob-modal-title { font-size:18px; font-weight:800; color:#121212; }
.eob-modal-copy { margin-top:8px; font-size:13px; line-height:1.6; color:#666; }
.eob-modal-foot { padding:16px 22px 22px; display:flex; justify-content:flex-end; gap:10px; }
.eob-show-layout { display:grid; grid-template-columns:320px minmax(0, 1fr); gap:18px; align-items:start; }
.eob-profile, .eob-show-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.eob-profile-sticky { position:sticky; top:18px; align-self:start; }
.eob-profile-banner { height:96px; background:linear-gradient(135deg,#fe5f04,#ff7c30); }
.eob-profile-body { padding:0 22px 22px; }
.eob-avatar { width:78px; height:78px; border-radius:20px; border:4px solid #fff; background:#fff3ec; margin-top:-38px; overflow:hidden; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:800; color:#fe5f04; box-shadow:0 6px 20px rgba(0,0,0,.08); }
.eob-avatar img { width:100%; height:100%; object-fit:cover; }
.eob-profile-name { font-size:20px; font-weight:800; color:#121212; margin-top:14px; }
.eob-profile-mail { font-size:13px; color:#7c7c7c; margin-top:4px; word-break:break-all; }
.eob-empid-card { margin-top:16px; padding:14px 16px; border-radius:16px; background:linear-gradient(135deg,#fff4eb 0%,#fff 100%); border:1px solid #ffd9c2; box-shadow:0 10px 28px rgba(254,95,4,.10); }
.eob-empid-label { font-size:10px; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#c96a2b; }
.eob-empid-value { margin-top:8px; font-size:22px; font-weight:800; color:#121212; letter-spacing:.04em; }
.eob-empid-sub { margin-top:6px; font-size:12px; color:#8d6e5a; }
.eob-side-list { display:flex; flex-direction:column; gap:12px; margin-top:18px; }
.eob-side-item, .eob-show-item { padding:14px; border:1px solid #f0eef2; border-radius:12px; background:#fafafa; }
.eob-side-label, .eob-show-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:#9e9e9e; }
.eob-side-value, .eob-show-value { font-size:14px; font-weight:700; color:#121212; margin-top:5px; word-break:break-word; }
.eob-show-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
.eob-doc-list { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
.eob-doc-card { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
.eob-doc-title { font-size:13px; font-weight:700; color:#121212; }
.eob-doc-sub { font-size:11px; color:#9e9e9e; margin-top:4px; word-break:break-all; }
.eob-doc-actions { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
.eob-muted { color:#9e9e9e; }
.eob-wizard-shell { display:grid; grid-template-columns:300px minmax(0, 1fr); gap:18px; align-items:start; }
.eob-wizard-nav { position:sticky; top:18px; align-self:start; background:linear-gradient(180deg,#fff7f1 0%,#ffffff 100%); border:1px solid #eadfd8; border-radius:20px; padding:20px; box-shadow:0 16px 38px rgba(18,18,18,.05); }
.eob-wizard-nav-head { padding-bottom:16px; border-bottom:1px solid #f1e7e1; }
.eob-wizard-kicker { font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#fe5f04; }
.eob-wizard-title { margin-top:8px; font-size:18px; font-weight:800; color:#121212; }
.eob-wizard-copy { margin-top:6px; font-size:12px; line-height:1.6; color:#7c7c7c; }
.eob-wizard-steps { display:flex; flex-direction:column; gap:10px; margin-top:18px; }
.eob-wizard-step-btn { width:100%; display:flex; align-items:flex-start; gap:12px; padding:12px; border:1px solid #ece5e0; border-radius:14px; background:#fff; text-align:left; transition:border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
.eob-wizard-step-btn:hover { transform:translateY(-1px); border-color:#ffcfb0; }
.eob-wizard-step-btn.is-active { border-color:#fe5f04; box-shadow:0 10px 24px rgba(254,95,4,.12); background:#fff8f4; }
.eob-wizard-step-btn.is-complete .eob-wizard-step-no { background:#fe5f04; color:#fff; border-color:#fe5f04; }
.eob-wizard-step-btn.has-errors { border-color:#fca5a5; background:#fff7f7; }
.eob-wizard-step-btn.has-errors .eob-wizard-step-no { border-color:#fca5a5; color:#b91c1c; background:#fff1f2; }
.eob-wizard-step-no { width:38px; height:38px; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; border:1px solid #ece5e0; background:#faf7f4; color:#8a7466; font-size:12px; font-weight:800; }
.eob-wizard-step-text { display:flex; flex-direction:column; gap:3px; min-width:0; }
.eob-wizard-step-title { font-size:13px; font-weight:700; color:#121212; }
.eob-wizard-step-sub { font-size:11px; line-height:1.45; color:#8a8a8a; }
.eob-wizard-main { display:flex; flex-direction:column; gap:16px; min-width:0; }
.eob-wizard-progress-card { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:18px 20px; background:#fff; border:1px solid #e1dee3; border-radius:16px; }
.eob-wizard-validation-summary { display:none; padding:14px 16px; border-radius:14px; border:1px solid #fecaca; background:#fef2f2; color:#b91c1c; font-size:13px; line-height:1.6; }
.eob-wizard-validation-summary.is-visible { display:block; }
.eob-wizard-validation-summary strong { display:block; margin-bottom:6px; font-size:13px; }
.eob-wizard-validation-summary ul { margin:0; padding-left:18px; }
.eob-wizard-validation-summary li + li { margin-top:2px; }
.eob-wizard-progress-meta { display:flex; flex-direction:column; gap:8px; min-width:190px; }
.eob-wizard-progress-meta span { font-size:12px; font-weight:700; color:#7c7c7c; text-align:right; }
.eob-wizard-progress-track { width:100%; height:8px; border-radius:999px; background:#f1ece8; overflow:hidden; }
.eob-wizard-progress-track span { display:block; height:100%; width:0; border-radius:999px; background:linear-gradient(135deg,#fe5f04,#ff9a52); transition:width .24s ease; }
.eob-wizard-panels { display:flex; flex-direction:column; gap:16px; }
.eob-wizard-panel { display:none; animation:eobWizardFade .22s ease; }
.eob-wizard-panel.is-active { display:block; }
.eob-wizard-footer { justify-content:space-between; }
.eob-wizard-footer-actions { display:flex; gap:10px; flex-wrap:wrap; }
@media (max-width: 960px) {
    .eob-topbar { padding:16px 20px; }
    .eob-body { padding:16px 20px 24px; }
    .eob-show-layout { grid-template-columns:1fr; }
    .eob-show-grid, .eob-file-grid, .eob-doc-list { grid-template-columns:1fr; }
    .eob-profile-sticky { position:static; }
    .eob-wizard-shell { grid-template-columns:1fr; }
    .eob-wizard-nav { position:static; }
    .eob-wizard-progress-card { flex-direction:column; align-items:flex-start; }
    .eob-wizard-progress-meta { min-width:100%; }
    .eob-wizard-progress-meta span { text-align:left; }
}
@media (max-width: 768px) {
    .eob-topbar { flex-direction:column; align-items:flex-start; }
    .eob-form-grid { grid-template-columns:1fr; }
    .eob-card-body { padding:18px; }
    .eob-foot { padding:16px 18px; }
.eob-wizard-progress-meta span { font-size:12px; font-weight:700; color:#7c7c7c; text-align:right; }
.eob-wizard-progress-track { width:100%; height:8px; border-radius:999px; background:#f1ece8; overflow:hidden; }
.eob-wizard-progress-track span { display:block; height:100%; width:0; border-radius:999px; background:linear-gradient(135deg,#fe5f04,#ff9a52); transition:width .24s ease; }
.eob-wizard-panels { display:flex; flex-direction:column; gap:16px; }
.eob-wizard-panel { display:none; animation:eobWizardFade .22s ease; }
.eob-wizard-panel.is-active { display:block; }
.eob-wizard-footer { justify-content:space-between; }
.eob-wizard-footer-actions { display:flex; gap:10px; flex-wrap:wrap; }
@media (max-width: 960px) {
    .eob-topbar { padding:16px 20px; }
    .eob-body { padding:16px 20px 24px; }
    .eob-show-layout { grid-template-columns:1fr; }
    .eob-show-grid, .eob-file-grid, .eob-doc-list { grid-template-columns:1fr; }
    .eob-profile-sticky { position:static; }
    .eob-wizard-shell { grid-template-columns:1fr; }
    .eob-wizard-nav { position:static; }
    .eob-wizard-progress-card { flex-direction:column; align-items:flex-start; }
    .eob-wizard-progress-meta { min-width:100%; }
    .eob-wizard-progress-meta span { text-align:left; }
}
@media (max-width: 768px) {
    .eob-topbar { flex-direction:column; align-items:flex-start; }
    .eob-form-grid { grid-template-columns:1fr; }
    .eob-card-body { padding:18px; }
    .eob-foot { padding:16px 18px; }
    .eob-filter-form { flex-direction:column; align-items:stretch; }
    .eob-field { min-width:100%; }
    .eob-wizard-footer { flex-direction:column; align-items:stretch; }
    .eob-wizard-footer-actions { width:100%; }
    .eob-wizard-footer-actions .eob-btn { flex:1; }
}
@keyframes eobWizardFade {
    from { opacity:0; transform:translateY(8px); }
    to { opacity:1; transform:translateY(0); }
}
.eob-progress-wrap { display:flex; flex-direction:column; gap:4px; min-width:115px; }
.eob-progress-info { display:flex; justify-content:space-between; align-items:center; font-size:11px; font-weight:800; color:#475569; }
.eob-progress-bar-bg { width:100%; height:7px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
.eob-progress-bar-fill { height:100%; border-radius:999px; transition:width .3s ease; }
.eob-progress-high { background:linear-gradient(90deg, #16a34a, #22c55e); }
.eob-progress-mid { background:linear-gradient(90deg, #d97706, #f59e0b); }
.eob-progress-low { background:linear-gradient(90deg, #dc2626, #ef4444); }
</style>
