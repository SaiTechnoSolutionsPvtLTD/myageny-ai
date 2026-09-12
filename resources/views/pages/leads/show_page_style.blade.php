<style>
/* ════════════════════════════════════════════════════════════
   ENHANCED LEAD SHOW PAGE
   Sections: Info · Call Updates · Reminders · Products · Quotations
   Theme: Clean white + orange system, tab-driven layout
════════════════════════════════════════════════════════════ */
:root {
    --orange:  #fe5f04;
    --orange2: #ff7c30;
    --green:   #16a34a;
    --red:     #dc2626;
    --blue:    #2563eb;
    --purple:  #7c3aed;
    --amber:   #b45309;
    --border:  #e1dee3;
    --bg:      #f4f5f7;
    --white:   #ffffff;
    --text:    #121212;
    --muted:   #9e9e9e;
    --soft:    #fafafa;
}

.lsp { display:flex; flex-direction:column; min-height:100vh; flex:1; background:var(--bg); font-family:'Inter',sans-serif; color:var(--text); }

/* ── Topbar ── */
.lsp-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; flex-shrink:0; background:#fff; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:50; }
.lsp-title  { font-size:17px; font-weight:800; color:var(--text); }
.lsp-crumb  { font-size:12px; color:var(--muted); margin-top:2px; }
.lsp-crumb a { color:var(--orange); text-decoration:none; font-weight:600; }
.lsp-topbar-right { display:flex; align-items:center; gap:8px; }
.lsp-btn { display:flex; align-items:center; gap:6px; padding:7px 16px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; transition:all .15s; border:none; }
.lsp-btn-primary  { background:linear-gradient(135deg,var(--orange),var(--orange2)); color:#fff; box-shadow:0 4px 12px rgba(254,95,4,.25); }
.lsp-btn-primary:hover { transform:translateY(-1px); }
.lsp-btn-outline  { background:#fff; color:var(--text); border:1px solid var(--border); }
.lsp-btn-outline:hover { border-color:var(--muted); }
.lsp-btn-danger   { background:#fef2f2; color:var(--red); border:1px solid #fecaca; }
.lsp-btn-danger:hover { background:#fee2e2; }
.lsp-btn-green    { background:#f0fdf4; color:var(--green); border:1px solid #bbf7d0; }
.lsp-btn-green:hover { background:#dcfce7; }
.lsp-btn-sm { padding:5px 12px; font-size:12px; border-radius:7px; }

/* ── Hero Strip ── */
.lsp-hero { display:flex; align-items:center; gap:16px; padding:16px 28px; background:#fff; border-bottom:1px solid var(--border); flex-shrink:0; }
.lsp-hero-logo { width:48px; height:48px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:17px; font-weight:900; color:#fff; flex-shrink:0; }
.lsp-hero-name { font-size:18px; font-weight:800; color:var(--text); }
.lsp-hero-sub  { font-size:12px; color:var(--muted); margin-top:2px; display:flex; align-items:center; gap:8px; }
.lsp-hero-right { margin-left:auto; display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }

/* ── Tabs ── */
.lsp-tabs-wrap { background:#fff; border-bottom:1px solid var(--border); flex-shrink:0; position:sticky; top:60px; z-index:40; }
.lsp-tabs { display:flex; align-items:center; padding:0 28px; gap:0; overflow-x:auto; }
.lsp-tab {
    display:flex; align-items:center; gap:7px;
    padding:12px 18px; font-size:13px; font-weight:600;
    color:var(--muted); cursor:pointer; border:none;
    background:none; font-family:inherit; white-space:nowrap;
    border-bottom:2px solid transparent; transition:all .15s;
    text-decoration:none;
}
.lsp-tab:hover { color:var(--text); }
.lsp-tab.active { color:var(--orange); border-bottom-color:var(--orange); font-weight:700; }
.lsp-tab-count {
    background:var(--bg); color:var(--muted);
    font-size:10px; font-weight:800; padding:1px 6px;
    border-radius:20px; min-width:18px; text-align:center;
}
.lsp-tab.active .lsp-tab-count { background:rgba(254,95,4,.12); color:var(--orange); }
.lsp-tab-count.urgent { background:#fef2f2; color:var(--red); }

/* ── Body ── */
.lsp-body { flex:1; display:flex; flex-direction:column; overflow-y:auto; }
.lsp-body::-webkit-scrollbar { width:5px; }
.lsp-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }

/* ── Tab panels ── */
.lsp-panel { display:none; padding:20px 28px 24px; flex:1; }
.lsp-panel.active { display:block; }

@keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
.lsp-panel.active { animation:fadeUp .25s ease; }

/* ── Layout helpers ── */
.lsp-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.lsp-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
.lsp-stack { display:flex; flex-direction:column; gap:14px; }

/* ── Cards ── */
.lsp-card {
    background:linear-gradient(180deg,#ffffff 0%, #fffdfb 100%);
    border:1px solid var(--border);
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(18,18,18,.04);
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.lsp-card:hover { transform:translateY(-2px); box-shadow:0 16px 36px rgba(18,18,18,.07); border-color:#d9d2d8; }
.lsp-card-head {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 18px;
    border-bottom:1px solid #f3edf1;
    background:linear-gradient(180deg,#fffaf7 0%, #ffffff 100%);
}
.lsp-card-title { font-size:14px; font-weight:800; color:var(--text); display:flex; align-items:center; gap:7px; }
.lsp-card-body  { padding:18px; }

/* ── Forms ── */
.lsp-form-row { display:grid; gap:12px; }
.lsp-form-row-2 { grid-template-columns:1fr 1fr; }
.lsp-form-row-3 { grid-template-columns:1fr 1fr 1fr; }
.lsp-group { display:flex; flex-direction:column; gap:5px; }
.lsp-label { font-size:11px; font-weight:700; color:#3d3d3d; text-transform:uppercase; letter-spacing:.4px; }
.lsp-req   { color:var(--red); }
.lsp-fw    { position:relative; }
.lsp-ico   { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none; width:13px; height:13px; }
.lsp-inp, .lsp-sel, .lsp-ta {
    width:100%; padding:8px 11px 8px 30px;
    border:1px solid var(--border); border-radius:8px;
    font-size:13px; font-family:inherit; color:var(--text);
    background:var(--soft); outline:none; transition:all .15s;
}
.lsp-inp::placeholder, .lsp-ta::placeholder { color:#c0bbb7; }
.lsp-inp:focus, .lsp-sel:focus, .lsp-ta:focus { border-color:var(--orange); background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.lsp-sel {
    background:
        linear-gradient(180deg,#fff7f1 0%, #fff2e8 100%);
    border-color:#f7c9ac;
    color:#c2410c;
}
.lsp-sel:hover { border-color:var(--orange2); }
.lsp-inp[type="date"] {
    background:linear-gradient(180deg,#fff7f1 0%, #fff2e8 100%);
    border-color:#f7c9ac;
    color:#c2410c;
}
.lsp-inp[type="date"]::-webkit-calendar-picker-indicator {
    cursor:pointer;
    filter: sepia(1) saturate(8) hue-rotate(345deg) brightness(.95);
    opacity:.9;
}
.lsp-inp.no-ico, .lsp-sel.no-ico { padding-left:11px; }
.lsp-sel { appearance:none; -webkit-appearance:none; cursor:pointer; }
.lsp-sel-caret { position:absolute; right:9px; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--muted); width:11px; height:11px; }
.lsp-ta { resize:vertical; min-height:72px; padding:8px 11px; }
.lsp-err { font-size:11px; color:var(--red); margin-top:2px; }

/* ── Badges ── */
.lsp-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; border:1px solid transparent; }
.lsp-dot   { width:6px; height:6px; border-radius:50%; }

/* ── Empty state ── */
.lsp-empty { text-align:center; padding:44px 20px; color:var(--muted); }
.lsp-empty-ico { font-size:40px; margin-bottom:10px; }
.lsp-empty-title { font-size:15px; font-weight:700; color:#7c7c7c; margin-bottom:5px; }
.lsp-empty-sub { font-size:12px; }

/* ── Alert ── */
.lsp-alert { padding:11px 15px; border-radius:9px; font-size:13px; display:flex; align-items:center; gap:9px; margin-bottom:14px; }
.lsp-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.lsp-alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }

/* ══════════════════════════════════════════
   PANEL 1 — Lead Info (2-col)
══════════════════════════════════════════ */
.lsp-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.lsp-info-item {
    padding:13px 14px;
    border:1px solid #f0e9ee;
    border-radius:14px;
    background:linear-gradient(180deg,#ffffff 0%, #fbfbfc 100%);
}
.lsp-info-item .lsp-il { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); margin-bottom:6px; }
.lsp-info-item .lsp-iv { font-size:13px; font-weight:700; color:var(--text); line-height:1.45; }
.lsp-info-item .lsp-iv a { color:var(--blue); text-decoration:none; }
.lsp-info-item .lsp-iv a:hover { text-decoration:underline; }
.lsp-remarks-box {
    background:linear-gradient(180deg,#fffaf7 0%, #ffffff 100%);
    border:1px solid #f1e7de;
    border-radius:14px;
    padding:14px 15px;
    font-size:13px;
    color:#2e2e2e;
    line-height:1.75;
}
.lsp-deal-big { font-size:28px; font-weight:900; color:var(--text); line-height:1; letter-spacing:-.02em; }
.lsp-status-select { appearance:none; -webkit-appearance:none; width:100%; padding:8px 28px 8px 11px; border:1px solid var(--border); border-radius:9px; font-size:13px; font-family:inherit; color:var(--text); background:var(--soft); outline:none; cursor:pointer; }
.lsp-status-select { background:linear-gradient(180deg,#fff7f1 0%, #fff2e8 100%); border-color:#f7c9ac; color:#c2410c; }
.lsp-status-select:focus { border-color:var(--orange); background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.lsp-stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; }
.lsp-stat-box {
    position:relative;
    overflow:hidden;
    padding:15px 16px;
    border-radius:16px;
    border:1px solid #efe7ed;
    background:linear-gradient(135deg,#ffffff 0%, #fff7f1 100%);
}
.lsp-stat-box::before {
    content:'';
    position:absolute;
    inset:0 auto 0 0;
    width:4px;
    background:linear-gradient(180deg,var(--orange),var(--orange2));
}
.lsp-stat-box.blue::before { background:linear-gradient(180deg,#2563eb,#60a5fa); }
.lsp-stat-box .lsp-il { margin-bottom:8px; }
.lsp-meta-line {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:12px 14px;
    border:1px solid #f0e9ee;
    border-radius:14px;
    background:#fff;
}
.lsp-meta-value { font-size:13px; font-weight:700; color:var(--text); }
.lsp-age-pill {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:999px;
    background:#fff4ea;
    color:#c2410c;
    border:1px solid #fed7aa;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.lsp-danger-note {
    margin-top:0;
    margin-bottom:12px;
    font-size:12px;
    line-height:1.6;
    color:#7c7c7c;
}

/* ══════════════════════════════════════════
   PANEL 2 — Call Updates
══════════════════════════════════════════ */
.lsp-call-list { display:flex; flex-direction:column; gap:10px; }
.lsp-call-card {
    background:#fff; border:1px solid var(--border); border-radius:12px; padding:14px 16px;
    border-left:3px solid var(--border);
}
.lsp-call-card.type-outgoing { border-left-color:var(--blue); }
.lsp-call-card.type-incoming { border-left-color:var(--green); }
.lsp-call-card.type-missed   { border-left-color:var(--red); }
.lsp-call-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
.lsp-call-meta { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.lsp-call-type { font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; }
.ct-outgoing { background:#eff6ff; color:var(--blue); }
.ct-incoming { background:#f0fdf4; color:var(--green); }
.ct-missed   { background:#fef2f2; color:var(--red); }
.lsp-call-when { font-size:11px; color:var(--muted); }
.lsp-call-dur  { font-size:11px; color:var(--muted); }
.lsp-call-notes { font-size:13px; color:#2e2e2e; line-height:1.5; margin-bottom:8px; }
.lsp-call-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.lsp-call-outcome { font-size:11px; font-weight:700; padding:3px 9px; border-radius:20px; }
.lsp-call-followup { font-size:11px; color:var(--muted); display:flex; align-items:center; gap:4px; }
.lsp-call-by { font-size:11px; color:var(--muted); }
.lsp-call-del { background:none; border:none; cursor:pointer; color:var(--muted); padding:3px; border-radius:5px; transition:color .15s; }
.lsp-call-del:hover { color:var(--red); }

/* ══════════════════════════════════════════
   PANEL 3 — Reminders
══════════════════════════════════════════ */
.lsp-rem-list { display:flex; flex-direction:column; gap:10px; }
.lsp-rem-card {
    background:#fff; border:1px solid var(--border); border-radius:12px; padding:14px 16px;
    display:flex; gap:12px; align-items:flex-start;
    transition:box-shadow .2s;
}
.lsp-rem-card:hover { box-shadow:0 3px 14px rgba(0,0,0,.06); }
.lsp-rem-card.overdue { border-color:#fecaca; background:#fffafa; }
.lsp-rem-card.done    { opacity:.55; }
.lsp-rem-ico { width:36px; height:36px; border-radius:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:16px; }
.lsp-rem-body { flex:1; min-width:0; }
.lsp-rem-title { font-size:14px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:7px; }
.lsp-rem-title.done { text-decoration:line-through; color:var(--muted); }
.lsp-rem-desc  { font-size:12px; color:var(--muted); margin-top:2px; line-height:1.5; }
.lsp-rem-meta  { display:flex; align-items:center; gap:8px; margin-top:7px; flex-wrap:wrap; }
.lsp-rem-time  { font-size:11px; font-weight:700; color:var(--muted); display:flex; align-items:center; gap:4px; }
.lsp-rem-time.overdue { color:var(--red); }
.lsp-rem-actions { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.lsp-rem-done-btn { background:var(--soft); border:1px solid var(--border); border-radius:7px; padding:5px 10px; font-size:11px; font-weight:700; cursor:pointer; font-family:inherit; color:var(--green); transition:all .15s; }
.lsp-rem-done-btn:hover { background:#dcfce7; border-color:#86efac; }
.lsp-rem-del-btn { background:none; border:none; cursor:pointer; color:var(--muted); padding:3px; border-radius:5px; transition:color .15s; }
.lsp-rem-del-btn:hover { color:var(--red); }

/* ══════════════════════════════════════════
   PANEL 4 — Products & Payments
══════════════════════════════════════════ */
/* Payment summary bar */
.lsp-pay-summary { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:16px; }
.lsp-pay-card { background:#fff; border:1px solid var(--border); border-radius:12px; padding:14px 16px; }
.lsp-pay-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); margin-bottom:6px; }
.lsp-pay-value { font-size:22px; font-weight:800; color:var(--text); }
.lsp-pay-sub   { font-size:11px; color:var(--muted); margin-top:3px; }

.lsp-prod-list { display:flex; flex-direction:column; gap:12px; }
.lsp-prod-card { background:#fff; border:1px solid var(--border); border-radius:12px; overflow:hidden; }
.lsp-prod-head { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #f7f6f9; }
.lsp-prod-name { font-size:14px; font-weight:700; color:var(--text); }
.lsp-prod-desc { font-size:11px; color:var(--muted); margin-top:2px; }
.lsp-prod-body { padding:12px 16px; }
.lsp-prod-amounts { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:12px; }
.lsp-prod-amt-item .lsp-pal { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--muted); margin-bottom:3px; }
.lsp-prod-amt-item .lsp-pav { font-size:14px; font-weight:800; color:var(--text); }
.lsp-prod-pay-form { background:var(--soft); border:1px solid #f0eef2; border-radius:9px; padding:12px; }
.lsp-prod-pay-title { font-size:12px; font-weight:700; color:var(--text); margin-bottom:10px; }

/* ══════════════════════════════════════════
   PANEL 5 — Quotations
══════════════════════════════════════════ */
.lsp-qt-list { display:flex; flex-direction:column; gap:12px; margin-bottom:20px; }
.lsp-qt-card { background:#fff; border:1px solid var(--border); border-radius:13px; overflow:hidden; }
.lsp-qt-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #f7f6f9; }
.lsp-qt-num { font-family:monospace; font-size:14px; font-weight:700; color:var(--text); }
.lsp-qt-date { font-size:12px; color:var(--muted); margin-top:2px; }
.lsp-qt-actions { display:flex; align-items:center; gap:8px; }
.lsp-qt-body { padding:14px 18px; }
.lsp-qt-items-tbl { width:100%; border-collapse:collapse; margin-bottom:12px; }
.lsp-qt-items-tbl th { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); padding:7px 10px; text-align:left; border-bottom:1px solid #f0eef2; background:var(--soft); }
.lsp-qt-items-tbl td { padding:9px 10px; font-size:13px; border-bottom:1px solid #f7f6f9; }
.lsp-qt-items-tbl tr:last-child td { border-bottom:none; }
.lsp-qt-totals { display:flex; flex-direction:column; align-items:flex-end; gap:5px; }
.lsp-qt-total-row { display:flex; gap:40px; font-size:12px; }
.lsp-qt-total-row.grand { font-size:15px; font-weight:800; color:var(--text); border-top:1px solid var(--border); padding-top:7px; margin-top:3px; }
.lsp-qt-total-label { color:var(--muted); }
.lsp-qt-total-value { font-weight:700; text-align:right; min-width:80px; }

/* Add quotation form */
.lsp-qt-form-wrap { background:#fff; border:1px solid var(--border); border-radius:13px; overflow:hidden; }
.lsp-qt-form-head { padding:14px 18px; border-bottom:1px solid #f0eef2; font-size:14px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; cursor:pointer; }
.lsp-qt-form-body { padding:18px; display:none; }
.lsp-qt-form-body.open { display:block; }

/* Line items dynamic table */
.lsp-items-table { width:100%; border-collapse:collapse; margin-bottom:10px; }
.lsp-items-table th { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); padding:7px 8px; text-align:left; background:var(--soft); border-bottom:1px solid var(--border); }
.lsp-items-table td { padding:6px 8px; vertical-align:middle; }
.lsp-item-inp { width:100%; padding:7px 9px; border:1px solid var(--border); border-radius:7px; font-size:12px; font-family:inherit; outline:none; transition:border-color .15s; background:var(--soft); }
.lsp-item-inp:focus { border-color:var(--orange); background:#fff; }
.lsp-item-total { font-size:13px; font-weight:700; color:var(--text); text-align:right; padding:7px 9px; }
.lsp-add-row-btn { display:flex; align-items:center; gap:5px; padding:7px 14px; border-radius:8px; background:none; border:1px dashed var(--border); font-size:12px; font-weight:600; color:var(--muted); cursor:pointer; font-family:inherit; transition:all .15s; }
.lsp-add-row-btn:hover { border-color:var(--orange); color:var(--orange); }
.lsp-remove-row { background:none; border:none; cursor:pointer; color:var(--muted); padding:4px; border-radius:5px; transition:color .15s; }
.lsp-remove-row:hover { color:var(--red); }
.lsp-qt-totals-preview { display:flex; flex-direction:column; align-items:flex-end; gap:4px; padding:12px 0; border-top:1px solid var(--border); margin-top:8px; }
.lsp-qt-pr { display:flex; gap:32px; font-size:12px; color:var(--muted); }
.lsp-qt-pr.grand { font-size:14px; font-weight:800; color:var(--text); }
.lsp-qt-pr span:last-child { min-width:80px; text-align:right; font-weight:600; }

@media (max-width: 980px) {
    .lsp-grid-2 { grid-template-columns:1fr; }
    .lsp-info-grid { grid-template-columns:1fr; }
    .lsp-stat-grid { grid-template-columns:1fr; }
    .lsp-hero { align-items:flex-start; flex-wrap:wrap; }
    .lsp-hero-right { margin-left:0; justify-content:flex-start; }
}
</style>
