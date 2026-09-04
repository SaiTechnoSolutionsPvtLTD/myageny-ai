<style>
/* ── Shared CRM settings page styles ── */
.crm-page-body      { padding: 32px; }
.crm-page-header    { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; }
.crm-title          { font-size:20px; font-weight:700; margin-bottom:4px; }
.crm-subtitle       { font-size:13px; color:#9e9e9e; }
.crm-header-actions { display:flex; gap:10px; }

/* Buttons */
.crm-btn            { padding:8px 18px; border-radius:20px; font-size:14px; font-weight:600; cursor:pointer; border:none; }
.crm-btn-primary    { background:#fe5f04; color:#fff; }
.crm-btn-primary:hover { background:#e55500; }
.crm-btn-ghost      { background:#fff; color:#121212; border:1px solid #e1dee3; }
.crm-btn-ghost:hover { background:#f8f8f8; }

/* Table */
.crm-table-wrap     { background:#fff; border:1px solid #e1dee3; border-radius:12px; overflow-x:auto; -webkit-overflow-scrolling:touch; }
.crm-table          { width:100%; border-collapse:collapse; font-size:14px; }
.crm-table thead tr { background:#f8f8f8; }
.crm-table th       { padding:12px 16px; text-align:left; font-size:12px; color:#9e9e9e; font-weight:600; border-bottom:1px solid #f1f1f1; }
.crm-table td       { padding:14px 16px; border-bottom:1px solid #f8f8f8; color:#121212; }
.crm-table tbody tr:last-child td { border-bottom:none; }
.crm-table tbody tr:hover td { background:#fdfbff; }
.text-right         { text-align:right; }
.crm-empty          { text-align:center; color:#9e9e9e; padding:32px !important; }

/* Pagination */
.crm-pagination     { display:flex; justify-content:space-between; align-items:center; padding:14px 16px; border-top:1px solid #f1f1f1; gap:12px; flex-wrap:wrap; }
.crm-page-info      { font-size:12px; color:#9e9e9e; }
.crm-page-links     { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.crm-page-link      { display:inline-flex; align-items:center; justify-content:center; min-width:36px; padding:7px 11px; border-radius:8px; font-size:12px; font-weight:600; text-decoration:none; color:#666; border:1px solid #e1dee3; background:#fff; transition:all .15s ease; }
.crm-page-link:hover { background:#fe5f04; color:#fff; border-color:#fe5f04; }
.crm-page-link.active { background:#fe5f04; color:#fff; border-color:#fe5f04; }
.crm-page-link.disabled { opacity:.45; cursor:default; pointer-events:none; }

/* Badges */
.crm-badge          { background:#f0eadb; color:#6b4c1e; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.crm-badge-blue     { background:#eef4ff; color:#3355aa; }
.crm-badge-purple   { background:#f5eeff; color:#60308c; }
.crm-count-badge    { background:#ede6f4; color:#3f1b5f; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }

/* Icon action buttons */
.crm-icon-btn       { background:none; border:none; cursor:pointer; font-size:16px; padding:4px 6px; border-radius:6px; }
.crm-icon-btn:hover { background:#f3f3f3; }
.crm-icon-btn.danger:hover { background:#fff0f0; }
.crm-table-dropdown { position:relative; display:inline-block; }
.crm-table-dropdown summary { list-style:none; }
.crm-table-dropdown summary::-webkit-details-marker { display:none; }
.crm-table-dropdown-trigger { min-width:42px; height:36px; padding:0 12px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border-radius:10px; border:1px solid #e1dee3; background:#fff; color:#121212; cursor:pointer; font-size:12px; font-weight:700; transition:all .15s ease; user-select:none; }
.crm-table-dropdown[open] .crm-table-dropdown-trigger,
.crm-table-dropdown-trigger:hover { background:#fff7ed; color:#fe5f04; border-color:#fdba74; }
.crm-table-dropdown-menu { position:absolute; right:0; top:calc(100% + 8px); min-width:140px; padding:6px; border-radius:12px; border:1px solid #ece7ec; background:#fff; box-shadow:0 16px 40px rgba(18,18,18,.12); z-index:20; display:flex; flex-direction:column; gap:4px; text-align:left; }
.crm-table-dropdown-item { width:100%; display:flex; align-items:center; gap:9px; padding:8px 12px; border:1px solid transparent; border-radius:8px; background:#fff; color:#121212; text-decoration:none; font-size:13px; font-weight:600; cursor:pointer; text-align:left; transition:all .15s ease; box-sizing:border-box; }
.crm-table-dropdown-item i { font-size:14px; line-height:1; }
.crm-table-dropdown-item svg { flex-shrink:0; }
.crm-table-dropdown-item:hover { background:#fff7ed; color:#fe5f04; border-color:#fed7aa; }
.crm-table-dropdown-item.danger { color:#dc2626; }
.crm-table-dropdown-item.danger:hover { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.crm-table-dropdown-menu form { margin:0; width:100%; }

/* Modal */
.crm-modal-overlay  {
    position:fixed; inset:0; background:rgba(0,0,0,.4);
    display:flex; align-items:center; justify-content:center;
    z-index:1000; animation:fadeIn .15s ease;
}
.crm-modal          { background:#fff; border-radius:16px; width:420px; max-width:95vw; box-shadow:0 8px 40px rgba(0,0,0,.18); }
.crm-modal-header   { display:flex; justify-content:space-between; align-items:center; padding:20px 24px 0; }
.crm-modal-header h3 { font-size:16px; font-weight:700; }
.crm-modal-header button { background:none; border:none; font-size:18px; cursor:pointer; color:#9e9e9e; }
.crm-modal-body     { padding:20px 24px; }
.crm-modal-footer   { display:flex; justify-content:flex-end; gap:10px; padding:0 24px 20px; }

/* Form */
.crm-label          { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:#444; }
.crm-input          { width:100%; padding:10px 14px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; outline:none; font-family:inherit; }
.crm-input:focus    { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.req                { color:#fe5f04; }

@keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
</style>
