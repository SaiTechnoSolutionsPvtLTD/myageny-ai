{{-- ================================================================
     FILE: resources/views/leads/partials/_products_panel.blade.php

     ENHANCED VERSION — API-based, accordion deals, multi-product
     modal, payment history, status updates via Fetch API.
     All window.PP.* functions registered immediately via IIFE
     so onclick= attributes always resolve.
================================================================ --}}

@php
    $totalValue   = $lead->products->sum('total_price');
    $totalPaid    = $lead->products->sum(fn($p) => $p->amount_paid);
    $totalPending = $totalValue - $totalPaid;
    $prodCount    = $lead->products->count();
    $leadStatusCompanyId = $lead->company_id ?? auth()->user()?->company_id;
    $leadProductStatuses = \App\Models\LeadStatus::query()
        ->when(
            $leadStatusCompanyId,
            fn ($query) => $query->where(fn ($statusQuery) => $statusQuery
                ->where('company_id', $leadStatusCompanyId)
                ->orWhereNull('company_id')),
            fn ($query) => $query->whereNull('company_id')
        )
        ->orderBy('name')
        ->get(['id', 'name']);
    $convertedStatusIds = $leadProductStatuses
        ->filter(fn ($status) => \App\Models\LeadProduct::statusKey($status->name) === 'converted')
        ->pluck('id')
        ->all();
    $converted    = $lead->products->filter(fn ($product) => in_array($product->lead_status_id, $convertedStatusIds, true) || \App\Models\LeadProduct::statusKey($product->product_status) === 'converted')->count();
    $canApproveLeadPriceRequests = auth()->user()?->hasAnyRole(['super_admin', 'Super Admin', 'admin']);
@endphp

{{-- ══════════════════════════════════════════════════════
     STYLES
══════════════════════════════════════════════════════ --}}
<style>
/* ── Summary bar ─────────────────────────────────────── */
.pp-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.pp-sum-card{background:#fff;border:1px solid #e1dee3;border-radius:12px;padding:14px 16px;position:relative;overflow:hidden}
.pp-sum-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.pp-sum-card.pp-total::before{background:#fe5f04}
.pp-sum-card.pp-paid::before{background:#16a34a}
.pp-sum-card.pp-pending::before{background:#dc2626}
.pp-sum-card.pp-count::before{background:#7c3aed}
.pp-sum-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#9e9e9e;margin-bottom:6px}
.pp-sum-value{font-size:22px;font-weight:800;color:#121212;line-height:1}
.pp-sum-sub{font-size:11px;color:#9e9e9e;margin-top:4px}

/* ── Toolbar ─────────────────────────────────────────── */
.pp-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:10px}
.pp-toolbar-title{font-size:15px;font-weight:700;color:#121212}
.pp-toolbar-right{display:flex;align-items:center;gap:8px}

/* ── Deal accordion ──────────────────────────────────── */
.pp-deals-stack{display:flex;flex-direction:column;gap:12px}
.pp-deal-accordion{background:#fff;border:1px solid #e1dee3;border-radius:14px;overflow:hidden;transition:box-shadow .2s}
.pp-deal-accordion:hover{box-shadow:0 4px 20px rgba(0,0,0,.07)}

.pp-deal-header{display:flex;align-items:center;gap:12px;padding:14px 18px;cursor:pointer;user-select:none;border-bottom:1px solid transparent;transition:background .15s}
.pp-deal-header:hover{background:#fafafa}
.pp-deal-chevron{font-size:10px;color:#9e9e9e;transition:transform .2s;flex-shrink:0}
.pp-deal-icon{width:38px;height:38px;border-radius:10px;background:#fff4ee;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.pp-deal-info{flex:1;min-width:0}
.pp-deal-name{font-size:15px;font-weight:700;color:#121212;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pp-deal-meta{font-size:11px;color:#9e9e9e;margin-top:2px}
.pp-deal-right{display:flex;align-items:center;gap:8px;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end}
.pp-deal-amounts{display:flex;gap:6px;align-items:center}

.pp-pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.pp-pill-green{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.pp-pill-red{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}

.pp-deal-progress{padding:0 18px 4px;background:#fff}
.pp-progress-bar-outer{height:5px;background:#f0eef2;border-radius:3px;overflow:hidden}
.pp-progress-bar-inner{height:100%;border-radius:3px;transition:width .6s ease}
.pp-progress-label{display:flex;justify-content:space-between;font-size:10px;color:#9e9e9e;margin-top:3px}
.pp-progress-wrap{padding:0 18px 10px}

.pp-deal-body{border-top:1px solid #f5f4f7}

/* ── Product sub-card ────────────────────────────────── */
.pp-prod-card--sub{border:none;border-bottom:1px solid #f5f4f7;border-radius:0}
.pp-prod-card--sub:last-child{border-bottom:none}
.pp-prod-inner{padding:14px 18px}
.pp-prod-name-row{margin-bottom:10px}
.pp-prod-name{font-size:14px;font-weight:700;color:#121212}
.pp-prod-desc-sub{font-size:11px;color:#9e9e9e;margin-top:2px}

.pp-amounts-row{display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:10px}
.pp-amt-item{padding:6px 10px 6px 0}
.pp-amt-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#9e9e9e;margin-bottom:3px}
.pp-amt-value{font-size:15px;font-weight:800;color:#121212}

/* ── Status select ───────────────────────────────────── */
.pp-status-select-wrap{position:relative;display:inline-flex}
.pp-status-select{appearance:none;-webkit-appearance:none;padding:5px 26px 5px 10px;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;border:1px solid transparent;font-family:inherit;outline:none;transition:all .15s}
.pp-status-caret{position:absolute;right:8px;top:50%;transform:translateY(-50%);pointer-events:none;width:10px;height:10px}

/* ── Prod footer ─────────────────────────────────────── */
.pp-prod-footer{display:flex;align-items:center;justify-content:flex-end;gap:6px;padding:8px 14px;background:#fafafa;border-top:1px solid #f5f4f7;flex-wrap:wrap}
.pp-footer-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.pp-act-btn{display:flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;transition:all .15s;border:1px solid transparent}
.pp-btn-pay{background:#fff0e6;color:#fe5f04;border-color:#fed7aa}
.pp-btn-pay:hover{background:#fed7aa}
.pp-btn-hist{background:#eef2ff;color:#4f46e5;border-color:#c7d2fe}
.pp-btn-hist:hover{background:#c7d2fe}
.pp-btn-prod{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.pp-btn-prod:hover{background:#d1fae5}
.pp-btn-edit{background:#eff6ff;color:#2563eb;border-color:#bfdbfe}
.pp-btn-edit:hover{background:#dbeafe}
.pp-btn-del{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.pp-btn-del:hover{background:#fecaca}
.pp-remove-row{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:6px;padding:4px 7px;cursor:pointer;display:flex;align-items:center;transition:all .15s}
.pp-remove-row:hover{background:#fecaca}

/* ── Loading / empty states ──────────────────────────── */
.pp-loading-wrap{display:none;flex-direction:column;align-items:center;justify-content:center;padding:48px 20px;gap:12px;color:#9e9e9e}
.pp-spinner{width:28px;height:28px;border:3px solid #f0eef2;border-top-color:#fe5f04;border-radius:50%;animation:ppSpin .7s linear infinite}
@keyframes ppSpin{to{transform:rotate(360deg)}}

/* ── Payment history download icon ───────────────────── */
.pp-hist-ref a{color:#1f2937;text-decoration:underline}
.pp-hist-download{display:inline-flex;align-items:center;justify-content:center;margin-left:8px;width:30px;height:30px;border-radius:6px;background:#f8fafc;border:1px solid #e6edf3;color:#0f172a;text-decoration:none}
.pp-hist-download svg{width:14px;height:14px}
.pp-hist-download:hover{background:#eef2ff}
.pp-empty-state{background:#fff;border:1px solid #e1dee3;border-radius:14px;padding:50px 20px;text-align:center;color:#9e9e9e}
.pp-empty-icon{font-size:40px;margin-bottom:10px}
.pp-empty-title{font-size:15px;font-weight:700;color:#7c7c7c;margin-bottom:5px}
.pp-empty-sub{font-size:13px}

/* ── Modal overlay ───────────────────────────────────── */
.pp-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(14,10,20,.55);backdrop-filter:blur(6px);align-items:center;justify-content:center}
.pp-overlay.pp-show{display:flex}
.pp-modal-box{background:#fff;border-radius:18px;width:90%;max-width:660px;max-height:92vh;overflow-y:auto;box-shadow:0 32px 80px rgba(0,0,0,.22);animation:ppIn .22s cubic-bezier(.4,0,.2,1)}
.pp-modal-box--wide{max-width:860px}
@keyframes ppIn{from{opacity:0;transform:scale(.95) translateY(16px)}to{opacity:1;transform:scale(1) translateY(0)}}
.pp-mhd{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f0eef2;position:sticky;top:0;background:#fff;z-index:1}
.pp-mtitle{font-size:16px;font-weight:800;color:#121212;display:flex;align-items:center;gap:8px}
.pp-mclose{width:32px;height:32px;border-radius:9px;background:#f5f4f6;border:none;cursor:pointer;color:#7c7c7c;display:flex;align-items:center;justify-content:center;font-size:18px;line-height:1;transition:all .15s}
.pp-mclose:hover{background:#e1dee3;color:#121212}
.pp-mbody{padding:20px 22px}
.pp-mmeta{padding:12px 22px;background:#fafafa;border-bottom:1px solid #f0eef2}
.pp-mmeta-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.pp-mmeta-lbl{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:#9e9e9e;margin-bottom:3px}
.pp-mmeta-val{font-size:15px;font-weight:800}
.pp-mfoot{display:flex;gap:10px;padding:14px 22px;border-top:1px solid #f0eef2;background:#fafafa;position:sticky;bottom:0}
.pp-confirm-modal{max-width:460px;overflow:hidden}
.pp-confirm-head{display:flex;align-items:center;gap:14px}
.pp-confirm-icon{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#fff0e6,#ffe2cf);color:#fe5f04;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:inset 0 0 0 1px rgba(254,95,4,.08)}
.pp-confirm-copy{display:flex;flex-direction:column;gap:4px}
.pp-confirm-kicker{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:#fe5f04}
.pp-confirm-title{font-size:18px;font-weight:800;color:#121212;line-height:1.2}
.pp-confirm-text{margin:16px 0 0;color:#6b7280;font-size:13px;line-height:1.6}
.pp-confirm-status{display:inline-flex;align-items:center;margin-top:14px;padding:8px 12px;border-radius:999px;background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;font-size:12px;font-weight:800}
.pp-confirm-actions{display:flex;gap:10px;width:100%}
.pp-confirm-actions .ppf-btn{flex:1}

/* ── Form elements ───────────────────────────────────── */
.ppf-grp{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.ppf-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#3d3d3d}
.ppf-req{color:#dc2626}
.ppf-rel{position:relative}
.ppf-ico{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9e9e9e;pointer-events:none;width:13px;height:13px}
.ppf-inp,.ppf-sel,.ppf-ta{width:100%;padding:9px 12px 9px 32px;border:1px solid #e1dee3;border-radius:9px;font-size:13px;font-family:inherit;color:#121212;background:#fafafa;outline:none;transition:all .15s}
.ppf-inp::placeholder,.ppf-ta::placeholder{color:#b8b3aa}
.ppf-inp:focus,.ppf-sel:focus,.ppf-ta:focus{border-color:#fe5f04;background:#fff;box-shadow:0 0 0 3px rgba(254,95,4,.1)}
.ppf-inp.ni,.ppf-sel.ni{padding-left:12px}
.ppf-sel{appearance:none;-webkit-appearance:none;cursor:pointer}
.ppf-caret{position:absolute;right:9px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9e9e9e;width:11px;height:11px}
.ppf-ta{resize:vertical;min-height:70px;padding:8px 12px}
.ppf-r2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.ppf-r3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.ppf-err{font-size:11px;color:#dc2626;margin-top:2px;display:none}
.pp-production-dynamic-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.pp-production-dynamic-field{margin-bottom:0}
.pp-production-choice-group{display:flex;flex-direction:column;gap:8px;padding:10px 12px;border:1px solid #e1dee3;border-radius:9px;background:#fafafa}
.pp-production-choice{display:flex;align-items:center;gap:8px;font-size:13px;color:#3a3a3a}
.pp-production-choice input{accent-color:#fe5f04}
.pp-production-field-help,.pp-production-file-note{font-size:11px;color:#9e9e9e;margin-top:4px}

/* ── Multi-select ────────────────────────────────────── */
.pp-multi-select{width:100%;border:1px solid #e1dee3;border-radius:9px;font-size:13px;font-family:inherit;color:#121212;background:#fafafa;outline:none;padding:4px;min-height:100px;transition:all .15s}
.pp-multi-select:focus{border-color:#fe5f04;box-shadow:0 0 0 3px rgba(254,95,4,.1)}
.pp-multi-select option{padding:6px 8px;border-radius:5px;margin-bottom:2px}
.pp-multi-select option:checked{background:#fff0e6;color:#fe5f04;font-weight:700}

/* ── Selected products table ─────────────────────────── */
.pp-sel-table-wrap{margin-top:14px;border:1px solid #e1dee3;border-radius:10px;overflow:hidden;display:none}
.pp-sel-table{width:100%;border-collapse:collapse;font-size:12px}
.pp-sel-table thead th{background:#f8f7fa;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#7c7c7c;text-align:left;border-bottom:1px solid #e1dee3}
.pp-sel-table tbody tr{border-bottom:1px solid #f5f4f7}
.pp-sel-table tbody tr:last-child{border-bottom:none}
.pp-sel-table tbody tr:hover td{background:#fafafa}
.pp-sel-table td{padding:9px 10px;vertical-align:middle;color:#121212}
.pp-td-name{min-width:140px}
.pp-td-desc{font-size:11px;color:#9e9e9e;margin-top:2px}
.pp-td-price{font-weight:700;white-space:nowrap}
.pp-td-total{font-weight:700;color:#fe5f04;white-space:nowrap}
.pp-td-qty .ppf-inp,.pp-td-disc .ppf-inp{width:70px;padding:5px 8px;text-align:center}
.pp-td-remarks .ppf-inp{width:140px;padding:5px 8px}

/* ── Payment mode tiles ──────────────────────────────── */
.ppf-mode-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin-bottom:14px}
.ppf-mode-tile{display:flex;flex-direction:column;align-items:center;gap:4px;padding:9px 6px;border-radius:10px;border:1.5px solid #e1dee3;cursor:pointer;transition:all .15s;font-size:10px;font-weight:700;color:#7c7c7c;user-select:none}
.ppf-mode-tile:hover,.ppf-mode-tile.pp-sel{border-color:#fe5f04;background:#fff0e6;color:#fe5f04}
.ppf-mode-ico{font-size:18px}

/* ── Buttons ─────────────────────────────────────────── */
.ppf-btn{display:flex;align-items:center;gap:6px;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;border:none;justify-content:center}
.ppf-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);color:#fff;box-shadow:0 4px 12px rgba(254,95,4,.25);flex:1}
.ppf-btn-primary:hover{transform:translateY(-1px)}
.ppf-btn-primary:disabled{opacity:.55;transform:none;cursor:not-allowed}
.ppf-btn-green{background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;box-shadow:0 4px 12px rgba(22,163,74,.25);flex:1}
.ppf-btn-green:hover{transform:translateY(-1px)}
.ppf-btn-green:disabled{opacity:.55;transform:none;cursor:not-allowed}
.ppf-btn-sec{background:#f5f4f6;color:#7c7c7c;border:1px solid #e1dee3}
.ppf-btn-sec:hover{background:#e1dee3}

/* ── Payment history ─────────────────────────────────── */
.pp-hist-totals{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;padding:12px 14px;background:linear-gradient(135deg,#f8f9fe,#f0fdf4);border-radius:12px;margin-bottom:14px}
.pp-htl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#9e9e9e;margin-bottom:3px}
.pp-htv{font-size:16px;font-weight:800}
.pp-hist-prog{margin-bottom:14px}
.pp-hist-pbar{height:6px;background:#f0eef2;border-radius:3px;overflow:hidden;margin-bottom:4px}
.pp-hist-pfill{height:100%;border-radius:3px;transition:width .5s ease}
.pp-hist-plabels{display:flex;justify-content:space-between;font-size:10px;color:#9e9e9e}
.pp-hist-section-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#7c7c7c;margin:0 0 10px 0}
.pp-hist-list{display:flex;flex-direction:column;gap:8px;max-height:450px;overflow-y:auto;padding-right:6px}
.pp-hist-item{display:flex;align-items:center;gap:12px;padding:12px 14px;background:#fff;border:1px solid #e1dee3;border-radius:11px;transition:border-color .15s}
.pp-hist-item:hover{border-color:#d4cfd8}
.pp-hist-mode-wrap{width:38px;height:38px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:17px}
.pp-hist-info{flex:1;min-width:0}
.pp-hist-mname{font-size:13px;font-weight:700;color:#121212}
.pp-hist-date{font-size:11px;color:#9e9e9e;margin-top:2px}
.pp-hist-ref{font-family:monospace;font-size:10px;color:#9e9e9e;margin-top:1px}
.pp-hist-note{font-size:11px;color:#7c7c7c;margin-top:2px}
.pp-hist-right{text-align:right;flex-shrink:0}
.pp-hist-amt{font-size:16px;font-weight:800;color:#16a34a}
.pp-hist-run{font-size:10px;color:#9e9e9e;margin-top:2px}
.pp-hist-del{background:none;border:none;cursor:pointer;color:#9e9e9e;padding:5px;border-radius:6px;transition:color .15s;flex-shrink:0}
.pp-hist-del:hover{color:#dc2626}
.pp-hist-empty{text-align:center;padding:32px 20px;color:#9e9e9e}
.pp-hist-empty-ico{font-size:36px;margin-bottom:8px}
.pp-prod-meta-card{background:linear-gradient(135deg,#f8fafc,#f0fdf4);border:1px solid #dcfce7;border-radius:14px;padding:14px 16px;margin-bottom:14px}
.pp-prod-meta-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:#6b7280;margin-bottom:4px}
.pp-prod-meta-value{font-size:16px;font-weight:800;color:#121212}
.pp-prod-meta-note{font-size:12px;color:#6b7280;margin-top:6px}
.pp-dept-list{display:flex;flex-wrap:wrap;gap:8px}
.pp-dept-pill{display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:#fff;color:#065f46;border:1px solid #a7f3d0;font-size:12px;font-weight:700}
.pp-prod-empty{padding:18px;border:1px dashed #d1d5db;border-radius:12px;background:#fafafa;color:#6b7280;font-size:13px;line-height:1.5}
.pp-prod-helper{margin-top:14px;padding:12px 14px;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:12px;line-height:1.5}
.pp-production-modal{max-width:760px;overflow:hidden;display:flex;flex-direction:column}
.pp-production-head{display:flex;align-items:flex-start;gap:14px;min-width:0;flex:1}
.pp-production-icon{width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#047857,#10b981);color:#fff;box-shadow:0 12px 30px rgba(4,120,87,.24);flex-shrink:0}
.pp-production-title-wrap{min-width:0;flex:1}
.pp-production-kicker{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#6b7280;margin-bottom:4px}
.pp-production-name{font-size:16px;font-weight:800;line-height:1.15;color:#064e3b;word-break:break-word}
.pp-production-sub{font-size:13px;color:#6b7280;margin-top:8px}
.pp-production-shell{display:grid;gap:16px}
.pp-production-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:14px}
.pp-production-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:16px 18px;box-shadow:0 12px 35px rgba(15,23,42,.05)}
.pp-production-card--hero{background:linear-gradient(135deg,#f5fffb,#ecfeff);border-color:#c7f9e8}
.pp-production-card--soft{background:linear-gradient(135deg,#fffaf2,#fff7ed);border-color:#fed7aa}
.pp-production-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#6b7280;margin-bottom:8px}
.pp-production-value{font-size:16px;font-weight:800;color:#111827}
.pp-production-note{font-size:13px;line-height:1.55;color:#6b7280;margin-top:8px}
.pp-production-dept-wrap{display:grid;gap:12px}
.pp-production-dept-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.pp-production-count{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:999px;background:#ecfdf5;color:#047857;font-size:13px;font-weight:800;border:1px solid #a7f3d0}
.pp-dept-pill{padding:10px 14px;background:linear-gradient(135deg,#ffffff,#f0fdf4);font-size:13px;border-width:1px;box-shadow:0 8px 20px rgba(4,120,87,.08)}
.pp-dept-pill::before{content:'•';margin-right:8px;color:#10b981}
.pp-production-empty{display:grid;gap:10px;padding:18px;border:1px dashed #d1d5db;border-radius:16px;background:#fafafa}
.pp-production-empty-title{font-size:14px;font-weight:800;color:#374151}
.pp-production-empty-copy{font-size:13px;line-height:1.55;color:#6b7280}
.pp-production-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%}
.pp-production-footer-note{font-size:12px;color:#6b7280}
.pp-production-form{display:grid;gap:16px}
.pp-production-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.pp-production-form-full{grid-column:1/-1}
.pp-production-file{display:block;width:100%;padding:10px 12px;border:1px solid #e1dee3;border-radius:9px;background:#fafafa;font-size:13px}
.pp-production-file:focus{outline:none;border-color:#fe5f04;background:#fff;box-shadow:0 0 0 3px rgba(254,95,4,.1)}
.pp-production-workflow{display:grid;gap:12px}
.pp-production-workflow-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.pp-production-workflow-title{font-size:14px;font-weight:800;color:#111827}
.pp-production-workflow-copy{font-size:12px;line-height:1.5;color:#6b7280}
.pp-production-stage-list{display:grid;gap:12px}
.pp-production-stage{padding:14px 16px;border-radius:16px;border:1px solid #e5e7eb;background:#fff}
.pp-production-stage-step{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#6b7280}
.pp-production-stage-title{margin-top:4px;font-size:15px;font-weight:800;color:#111827}
.pp-production-stage-desc{margin-top:6px;font-size:12px;line-height:1.55;color:#6b7280}
.pp-production-role-group{margin-top:10px}
.pp-production-role-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#475569}
.pp-production-role-pills{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
.pp-production-role-pill{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;font-size:11px;font-weight:700}
.pp-production-note-item{margin-top:8px;font-size:12px;line-height:1.5;color:#6b7280}
.pp-production-status{padding:14px 16px;border-radius:14px;border:1px solid #bbf7d0;background:#f0fdf4;color:#166534;font-size:13px;line-height:1.55}
.pp-production-hidden{display:none!important}
.pp-modal-production-body{flex:1;overflow-y:auto;min-height:0}
.pp-modal-production-foot{flex-shrink:0}
@media (max-width: 720px){
    .pp-production-grid{grid-template-columns:1fr}
    .pp-production-dynamic-grid{grid-template-columns:1fr}
    .pp-production-name{font-size:20px}
    .pp-production-head{gap:12px}
    .pp-production-icon{width:46px;height:46px;border-radius:14px}
    .pp-production-footer{flex-direction:column;align-items:stretch}
    .pp-production-form-grid{grid-template-columns:1fr}
}
.pp-hist-loading{display:flex;justify-content:center;padding:40px 20px}

/* ── Toast notifications ─────────────────────────────── */
#pp-toast-wrap{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.pp-toast{padding:11px 18px;border-radius:10px;font-size:13px;font-weight:700;box-shadow:0 8px 28px rgba(0,0,0,.18);animation:ppToastIn .25s ease;pointer-events:none;max-width:320px}
.pp-toast--success{background:#16a34a;color:#fff}
.pp-toast--error{background:#dc2626;color:#fff}
.pp-toast--out{animation:ppToastOut .3s ease forwards}
@keyframes ppToastIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes ppToastOut{to{opacity:0;transform:translateY(16px)}}
</style>

{{-- ══════════════════════════════════════════════════════
     TOAST CONTAINER
══════════════════════════════════════════════════════ --}}
<div id="pp-toast-wrap"></div>

{{-- ══════════════════════════════════════════════════════
     SUMMARY BAR  (hydrated via JS after API call)
══════════════════════════════════════════════════════ --}}
<div class="pp-summary">
    <div class="pp-sum-card pp-total">
        <div class="pp-sum-label">Total Products Value</div>
        <div class="pp-sum-value" id="pp-sum-total">₹{{ number_format($totalValue,2) }}</div>
        <div class="pp-sum-sub" id="pp-sum-count">{{ $prodCount }} product(s)</div>
    </div>
    <div class="pp-sum-card pp-paid">
        <div class="pp-sum-label">Amount Received </div>
        <div class="pp-sum-value"  style="color:#16a34a">₹{{ number_format($totalPaid,2) }}</div>
        <div class="pp-sum-sub" style="color:#16a34a">Collected so far</div>
    </div>
    <div class="pp-sum-card pp-pending">
        <div class="pp-sum-label">Amount Pending</div>
        <div class="pp-sum-value" id="pp-sum-pending" style="color:{{ $totalPending>0?'#dc2626':'#16a34a' }}">
            ₹{{ number_format($totalPending,2) }}
        </div>
        <div class="pp-sum-sub">{{ $totalPending>0 ? 'Outstanding' : 'Fully Settled ✓' }}</div>
    </div>
    <div class="pp-sum-card pp-count">
        <div class="pp-sum-label">Converted</div>
        <div class="pp-sum-value" id="pp-sum-converted" style="color:#7c3aed">{{ $converted }}</div>
        <div class="pp-sum-sub">of {{ $prodCount }} total</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     TOOLBAR
══════════════════════════════════════════════════════ --}}
<div class="pp-toolbar">
    <div class="pp-toolbar-title">🤝 Deals &amp; Products</div>
    <div class="pp-toolbar-right">
        @if($canApproveLeadPriceRequests)
        <a href="{{ route('lead-price-requests.index', ['lead_id' => $lead->id, 'status' => 'pending']) }}"
           class="pp-act-btn pp-btn-hist">
            Pending Requests
            @if(!empty($pendingPriceRequestCount))
                ({{ $pendingPriceRequestCount }})
            @endif
        </a>
        @endif
        {{-- Loading indicator for AJAX refresh --}}
        <div id="pp-deals-loading" class="pp-loading-wrap" style="display:none;padding:0">
            <div class="pp-spinner" style="width:18px;height:18px;border-width:2px"></div>
            <span style="font-size:12px;color:#9e9e9e">Loading…</span>
        </div>
        <button type="button" class="pp-act-btn pp-btn-pay" onclick="PP.ppShowAddProduct()">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Product
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     DEALS CONTAINER  (JS renders accordion here)
══════════════════════════════════════════════════════ --}}
<div id="pp-deals-container" class="pp-deals-stack">
    {{-- Initial server-side render (replaced by JS after API load) --}}
    @if($lead->products->isEmpty())
    <div class="pp-empty-state">
        <div class="pp-empty-icon">📦</div>
        <div class="pp-empty-title">No deals yet</div>
        <div class="pp-empty-sub">Click "Add Product" to create your first deal</div>
    </div>
    @else
    <div class="pp-loading-wrap" style="display:flex">
        <div class="pp-spinner"></div>
        <span style="font-size:13px;color:#9e9e9e">Loading deals…</span>
    </div>
    @endif
</div>


{{-- ══════════════════════════════════════════════════════
     MODAL 1 — ADD PRODUCT (with multi-select + table)
══════════════════════════════════════════════════════ --}}
<div class="pp-overlay" id="pp-modal-add-product">
    <div class="pp-modal-box pp-modal-box--wide">
        <div class="pp-mhd">
            <div class="pp-mtitle" id="pp-add-product-modal-title">📦 Add Product to Lead</div>
            <button type="button" class="pp-mclose" onclick="PP.ppHideModal('pp-modal-add-product')">✕</button>
        </div>
        <div class="pp-mbody">

            {{-- Deal Name --}}
            <div class="ppf-grp">
                <label class="ppf-lbl">Deal Name <span class="ppf-req">*</span></label>
                <div class="ppf-rel">
                    <svg class="ppf-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    <input type="text" id="pp-deal-name" class="ppf-inp"
                           placeholder="e.g. Q1 Digital Campaign, Website Package…" >
                </div>
            </div>

            {{-- Product multi-select --}}
            <div class="ppf-grp">
                <label class="ppf-lbl">Select Products <span class="ppf-req">*</span>
                    <span style="font-weight:400;text-transform:none;color:#9e9e9e;margin-left:6px">Hold Ctrl/Cmd to select multiple</span>
                </label>
                <div id="pp-product-loading" class="pp-loading-wrap" style="display:none;padding:12px 0">
                    <div class="pp-spinner"></div>
                    <span style="font-size:12px;color:#9e9e9e">Loading products…</span>
                </div>
                <select id="pp-product-multi-select" class="pp-multi-select"
                        multiple onchange="PP.ppOnProductSelect()">
                    <option disabled>Loading products…</option>
                </select>
            </div>

            {{-- Dynamic selected products table --}}
            <div id="pp-selected-products-wrap" class="pp-sel-table-wrap">
                <table class="pp-sel-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Disc %</th>
                            <th>Total</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="pp-selected-tbody"></tbody>
                </table>
            </div>

        </div>
        <div class="pp-mfoot">
            <button type="button" id="pp-submit-deal-btn" class="ppf-btn ppf-btn-primary"
                    onclick="PP.ppSubmitDeal()">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Create Deal
            </button>
            <button type="button" id="pp-submit-price-request-btn" class="ppf-btn pp-btn-hist"
                    style="justify-content:center"
                    onclick="PP.ppSubmitPriceRequest()">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v12"></path>
                    <path d="m8 11 4 4 4-4"></path>
                    <path d="M5 21h14"></path>
                </svg>
                Send Price Request
            </button>
            <button type="button" class="ppf-btn ppf-btn-sec"
                    onclick="PP.ppHideModal('pp-modal-add-product')">Cancel</button>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════
     MODAL 2 — ADD PAYMENT
══════════════════════════════════════════════════════ --}}
<div class="pp-overlay" id="pp-modal-payment">
    <div class="pp-modal-box">
        <div class="pp-mhd">
            <div class="pp-mtitle">
                💳 Record Payment —
                <span id="pp-pay-name" style="color:#fe5f04"></span>
            </div>
            <button type="button" class="pp-mclose" onclick="PP.ppHideModal('pp-modal-payment')">✕</button>
        </div>
        <div class="pp-mmeta">
            <div class="pp-mmeta-grid">
                <div>
                    <div class="pp-mmeta-lbl">Total Value</div>
                    <div class="pp-mmeta-val" id="pp-pay-total">₹0.00</div>
                </div>
                <div>
                    <div class="pp-mmeta-lbl">Already Paid</div>
                    <div class="pp-mmeta-val" id="pp-pay-paid" style="color:#16a34a">₹0.00</div>
                </div>
                <div>
                    <div class="pp-mmeta-lbl">Balance Due</div>
                    <div class="pp-mmeta-val" id="pp-pay-balance" style="color:#dc2626">₹0.00</div>
                </div>
            </div>
        </div>
        <div class="pp-mbody">

            <div class="ppf-grp">
                <label class="ppf-lbl">Amount Received ₹ <span class="ppf-req">*</span></label>
                <div class="ppf-rel">
                    <svg class="ppf-ico" viewBox="0 0 24 24" aria-hidden="true">
                        <text x="12" y="16" text-anchor="middle" font-size="14" font-weight="700" fill="currentColor">₹</text>
                    </svg>
                    <input type="number" id="pp-pay-amount" class="ppf-inp"
                           placeholder="0.00" step="0.01" min="0.01">
                </div>
            </div>

            <div class="ppf-grp">
                <label class="ppf-lbl">Payment Mode <span class="ppf-req">*</span></label>
                <div class="ppf-mode-grid">
                    @foreach(['cash'=>['💵','Cash'],'bank_transfer'=>['🏦','Bank Transfer'],'cheque'=>['📝','Cheque'],'upi'=>['📱','UPI'],'card'=>['💳','Card']] as $mk=>$mv)
                    <div class="ppf-mode-tile {{ $mk==='upi'?'pp-sel':'' }}"
                         data-val="{{ $mk }}" onclick="PP.ppPickMode(this)">
                        <div class="ppf-mode-ico">{{ $mv[0] }}</div>
                        {{ $mv[1] }}
                    </div>
                    @endforeach
                </div>
                <input type="hidden" id="pp-mode-val" value="upi">
            </div>

            <div class="ppf-r2">
                <div class="ppf-grp" style="margin-bottom:0">
                    <label class="ppf-lbl">Payment Date <span class="ppf-req">*</span></label>
                    <input type="date" id="pp-pay-date" class="ppf-inp ni"
                           value="{{ today()->toDateString() }}" readonly>
                </div>
                <div class="ppf-grp" style="margin-bottom:0">
                    <label class="ppf-lbl">Reference / UTR No.</label>
                    <div class="ppf-rel">
                        <svg class="ppf-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                        </svg>
                        <input type="text" id="pp-pay-ref" class="ppf-inp"
                               placeholder="Txn ID / Cheque No.">
                    </div>
                </div>
            </div>

            <div class="ppf-grp" style="margin-top:14px">
                <label class="ppf-lbl">Notes</label>
                <textarea id="pp-pay-notes" class="ppf-ta"
                          placeholder="Optional note…" rows="2"></textarea>
            </div>

            <div class="ppf-grp" style="margin-top:12px">
                <label class="ppf-lbl">Attachment</label>
                <div class="ppf-rel">
                    <input type="file" id="pp-pay-attachment" name="attachment" class="ppf-inp" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                </div>
                <div style="font-size:12px;color:#9e9e9e;margin-top:6px">Accepted: PDF, images, Word, Excel. Max 10MB.</div>
            </div>

        </div>
        <div class="pp-mfoot">
            <button type="button" id="pp-submit-pay-btn" class="ppf-btn ppf-btn-green"
                    onclick="PP.ppSubmitPayment()">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Save Payment
            </button>
            <button type="button" class="ppf-btn ppf-btn-sec"
                    onclick="PP.ppHideModal('pp-modal-payment')">Cancel</button>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════
     MODAL 3 — PAYMENT HISTORY
══════════════════════════════════════════════════════ --}}
<div class="pp-overlay" id="pp-modal-history">
    <div class="pp-modal-box pp-modal-box--wide">
        <div class="pp-mhd">
            <div class="pp-mtitle">
                📊 Payment History —
                <span id="pp-hist-name" style="color:#7c3aed"></span>
            </div>
            <button type="button" class="pp-mclose"
                    onclick="PP.ppHideModal('pp-modal-history')">✕</button>
        </div>
        <div class="pp-mbody" id="pp-hist-body"></div>
        <div class="pp-mfoot">
            <button type="button" id="pp-hist-add-btn" class="ppf-btn ppf-btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" aria-hidden="true">
                    <text x="12" y="16" text-anchor="middle" font-size="14" font-weight="700" fill="currentColor">₹</text>
                </svg>
                Add Payment
            </button>
            <button type="button" class="ppf-btn ppf-btn-sec"
                    onclick="PP.ppHideModal('pp-modal-history')">Close</button>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════
     CONFIG INJECTION + JS
══════════════════════════════════════════════════════ --}}

<div class="pp-overlay" id="pp-modal-status-confirm">
    <div class="pp-modal-box pp-confirm-modal">
        <div class="pp-mbody">
            <div class="pp-confirm-head">
                <div class="pp-confirm-icon">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v4"/>
                        <path d="M12 17h.01"/>
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    </svg>
                </div>
                <div class="pp-confirm-copy">
                    <div class="pp-confirm-kicker">Confirm Status Change</div>
                    <div class="pp-confirm-title">Are you sure?</div>
                </div>
            </div>
            <p class="pp-confirm-text">This product status will be updated for the selected lead product.</p>
            <div class="pp-confirm-status" id="pp-status-confirm-label"></div>
        </div>
        <div class="pp-mfoot">
            <div class="pp-confirm-actions">
                <button type="button" class="ppf-btn ppf-btn-sec" onclick="PP.ppResolveStatusConfirm(false)">No, Keep Current</button>
                <button type="button" class="ppf-btn ppf-btn-primary" onclick="PP.ppResolveStatusConfirm(true)">Yes, Change Status</button>
            </div>
        </div>
    </div>
</div>

<div class="pp-overlay" id="pp-modal-production">
    <div class="pp-modal-box pp-production-modal">
        <div class="pp-mhd">
            <div class="pp-production-head">
                <div class="pp-production-icon">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 7h18"/>
                        <path d="M6 12h12"/>
                        <path d="M9 17h6"/>
                    </svg>
                </div>
                <div class="pp-production-title-wrap">
                    <div class="pp-production-kicker">Production Move</div>
                    <div class="pp-production-name" id="pp-production-name"></div>
                </div>
            </div>
            <button type="button" class="pp-mclose"
                    onclick="PP.ppHideModal('pp-modal-production')">✕</button>
        </div>
        <div class="pp-mbody pp-modal-production-body">
            <div class="pp-production-shell">
                <div id="pp-production-body"></div>
            </div>
        </div>
        <div class="pp-mfoot pp-modal-production-foot">
            <div class="pp-production-footer">
                <button type="button" id="pp-production-submit-btn" class="ppf-btn ppf-btn-primary"
                        onclick="PP.ppSubmitProductionInitiation()">
                    Submit to Production
                </button>
                <button type="button" class="ppf-btn ppf-btn-sec"
                        onclick="PP.ppHideModal('pp-modal-production')">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* Inject server-side config for products-panel.js */
window.PP_CONFIG = {
    leadId  : {{ $lead->id }},
    apiBase : '{{ rtrim(env("APP_URL"), "/") }}/api',
    csrf    : {!! json_encode(csrf_token()) !!},
    isAdmin : {{ $canApproveLeadPriceRequests ? 'true' : 'false' }},
    statusOptions: @json($leadProductStatuses->map(fn ($status) => ['id' => $status->id, 'name' => $status->name])->values()),
};
window.PP = window.PP || {};
</script>
<script src="{{ asset('js/products-panel.js') }}"></script>

@endpush
