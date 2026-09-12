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
.crm-table-wrap     { background:#fff; border:1px solid #e1dee3; border-radius:12px; overflow-x:auto; -webkit-overflow-scrolling:touch; min-height: 480px; }
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
.crm-table-dropdown[open] { z-index:60; }
.crm-table-dropdown summary { list-style:none; }
.crm-table-dropdown summary::-webkit-details-marker { display:none; }
.crm-table-dropdown-trigger { min-width:42px; height:36px; padding:0 12px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border-radius:10px; border:1px solid #e1dee3; background:#fff; color:#121212; cursor:pointer; font-size:12px; font-weight:700; transition:all .15s ease; user-select:none; }
.crm-table-dropdown[open] .crm-table-dropdown-trigger,
.crm-table-dropdown-trigger:hover { background:#fff7ed; color:#fe5f04; border-color:#fdba74; }
.crm-table-dropdown-menu { position:absolute; right:0; top:calc(100% + 8px); min-width:140px; padding:6px; border-radius:12px; border:1px solid #ece7ec; background:#fff; box-shadow:0 16px 40px rgba(18,18,18,.15), 0 4px 12px rgba(0,0,0,.08); z-index:999; display:flex; flex-direction:column; gap:4px; text-align:left; }
.crm-table-dropdown.dropup .crm-table-dropdown-menu { top:auto !important; bottom:calc(100% + 8px) !important; box-shadow:0 -16px 40px rgba(18,18,18,.15), 0 -4px 12px rgba(0,0,0,.08) !important; }
.crm-table-dropdown-item { width:100%; display:flex; align-items:center; gap:9px; padding:8px 12px; border:1px solid transparent; border-radius:8px; background:#fff; color:#121212; text-decoration:none; font-size:13px; font-weight:600; cursor:pointer; text-align:left; transition:all .15s ease; box-sizing:border-box; }
.crm-table-dropdown-item i { font-size:14px; line-height:1; }
.crm-table-dropdown-item svg { flex-shrink:0; }
.crm-table-dropdown-item:hover { background:#fff7ed; color:#fe5f04; border-color:#fed7aa; }
.crm-table-dropdown-item.danger { color:#dc2626; }
.crm-table-dropdown-item.danger:hover { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.crm-table-dropdown-menu form { margin:0; width:100%; }

/* Modal */
.crm-modal-overlay  {
    position: fixed !important;
    inset: 0 !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(5px) !important;
    -webkit-backdrop-filter: blur(5px) !important;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    z-index: 999999 !important;
    animation: crmFadeIn .18s ease forwards;
}
.crm-modal          {
    background: #ffffff;
    border-radius: 18px;
    width: 480px;
    max-width: min(94vw, 500px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.04);
    animation: crmModalIn .2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    overflow: hidden;
    margin: auto;
}
.crm-modal-header   {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
}
.crm-modal-header h3 {
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
}
.crm-modal-header button,
.crm-modal-close {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 14px;
    line-height: 1;
    cursor: pointer;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all .15s ease;
}
.crm-modal-header button:hover,
.crm-modal-close:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #dc2626;
}
.crm-modal-body     {
    padding: 24px;
    background: #ffffff;
}
.crm-modal-footer   {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}

/* Form */
.crm-label          {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #334155;
}
.crm-input          {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    font-family: inherit;
    box-sizing: border-box;
    transition: all .15s ease;
}
select.crm-input    {
    appearance: auto;
    cursor: pointer;
}
.crm-input:focus    {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3.5px rgba(254, 95, 4, 0.12);
}
.req                { color: #fe5f04; font-weight: 700; }

@keyframes crmFadeIn  { from { opacity: 0; } to { opacity: 1; } }
@keyframes crmModalIn { from { opacity: 0; transform: scale(0.95) translateY(-8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>
