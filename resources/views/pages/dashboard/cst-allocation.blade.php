@extends('layouts.app')

@section('title', 'CST Lead Allocation – myAgenci.ai')

@push('styles')
<style>
/* ============================================================
   CST ALLOCATION WRAPPERS & STYLES
   ============================================================ */
:root {
    --cst-bg:         #f3f4f6;
    --cst-card-bg:    #ffffff;
    --cst-border:     #e5e7eb;
    --cst-text:       #111827;
    --cst-muted:      #6b7280;
    --cst-orange:     #fe5f04;
    --cst-purple:     #6d28d9;
    --cst-teal:       #0d9488;
    --cst-emerald:    #059669;
    --cst-rose:       #be123c;
    --cst-blue:       #1d4ed8;
}

.cst-wrap          { display:flex; flex-direction:column; flex-grow:1; overflow:hidden; font-family:'Inter', sans-serif; background:var(--cst-bg); }
.cst-header        { display:flex; justify-content:space-between; align-items:center;
                     padding:20px 32px; border-bottom:1px solid var(--cst-border);
                     background:var(--cst-card-bg); position:sticky; top:0; z-index:20; }
.cst-body          { flex-grow:1; overflow-y:auto; padding:28px 32px; display:flex; flex-direction:column; gap:24px; }

/* Alert Messages */
.alert-box         { padding:14px 20px; border-radius:12px; font-size:13px; font-weight:600; margin-bottom:8px; display:flex; align-items:center; gap:10px; }
.alert-success     { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
.alert-error       { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

/* Tab Navigation */
.tabs-nav          { display:flex; gap:8px; border-bottom:2px solid var(--cst-border); padding-bottom:1px; margin-bottom:4px; }
.tab-btn           { padding:12px 24px; font-size:14px; font-weight:800; color:var(--cst-muted); background:none;
                     border:none; cursor:pointer; position:relative; transition:color .2s; outline:none; }
.tab-btn.active    { color:var(--cst-orange); }
.tab-btn.active::after { content:''; position:absolute; bottom:-3px; left:0; right:0; height:3px; background:var(--cst-orange); border-radius:3px; }

/* Panel Containers */
.tab-panel         { display:none; }
.tab-panel.active  { display:block; }

.allocation-panel  { background:var(--cst-card-bg); border:1px solid var(--cst-border); border-radius:16px;
                     padding:24px; display:flex; flex-direction:column; gap:16px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.03); }
.allocation-panel h4 { font-size:16px; font-weight:800; color:var(--cst-text); margin:0; }

/* Progress Bar */
.prog-bar-container{ width:100%; max-width:180px; background:#e5e7eb; height:8px; border-radius:4px; overflow:hidden; position:relative; }
.prog-bar-fill     { height:100%; border-radius:4px; background:linear-gradient(90deg, #3b82f6 0%, #10b981 100%); }
.prog-bar-label    { font-size:11px; font-weight:700; color:var(--cst-muted); margin-top:4px; }

/* Tables */
.table-wrap        { overflow-x:auto; width:100%; }
.cs-table          { width:100%; border-collapse:collapse; text-align:left; font-size:13px; }
.cs-table th       { background:#f9fafb; padding:12px 16px; font-size:11px; font-weight:700;
                     color:var(--cst-muted); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--cst-border); }
.cs-table td       { padding:14px 16px; border-bottom:1px solid var(--cst-border); color:var(--cst-text); vertical-align:middle; }
.cs-table tr:hover td { background:#faf5ff; }

/* Buttons */
.btn-action        { padding:7px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer;
                     border:none; display:inline-flex; align-items:center; gap:6px; transition:transform .1s, opacity .2s; text-decoration:none; }
.btn-primary       { background:linear-gradient(135deg, var(--cst-orange) 0%, #ff8c42 100%); color:#fff; }
.btn-primary:hover { opacity:.9; transform:translateY(-1px); }
.btn-secondary     { background:#e5e7eb; color:var(--cst-text); border:1px solid var(--cst-border); }
.btn-secondary:hover{ background:#d1d5db; }
.btn-success       { background:linear-gradient(135deg, var(--cst-emerald) 0%, #34d399 100%); color:#fff; }
.btn-success:hover { opacity:.9; transform:translateY(-1px); }

/* Badges */
.cst-badge          { display:inline-flex; align-items:center; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:700; }
.badge-assigned     { background:#ecfdf5; color:#047857; }
.badge-unassigned   { background:#fffbeb; color:#b45309; }

/* Modal Custom Layer */
.modal-layer       { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999;
                     align-items:center; justify-content:center; backdrop-filter:blur(4px); padding:16px; }
.modal-layer.open  { display:flex; }
.modal-box         { background:#ffffff; border-radius:16px; max-width:440px; width:100%; padding:24px;
                     box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);
                     display:flex; flex-direction:column; gap:16px; animation:scaleUp 0.2s cubic-bezier(0.4,0,0.2,1); }
@keyframes scaleUp  { from { transform:scale(.95); opacity:0; } to { transform:scale(1); opacity:1; } }
.modal-title       { font-size:16px; font-weight:800; color:var(--cst-text); display:flex; justify-content:space-between; align-items:center; }
.modal-close       { border:none; background:none; font-size:20px; font-weight:700; cursor:pointer; color:var(--cst-muted); }
.modal-body        { display:flex; flex-direction:column; gap:12px; }
.modal-body label  { font-size:11px; font-weight:700; color:var(--cst-muted); text-transform:uppercase; }
.modal-body select  { padding:10px 12px; border:1px solid var(--cst-border); border-radius:10px; font-size:13px; outline:none; background:#fafafa; }
.modal-body select:focus { border-color:var(--cst-orange); background:#fff; }
.modal-footer      { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }

/* Custom Pagination styles */
.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 20px 0 0 0;
    gap: 6px;
    align-items: center;
    justify-content: center;
}
.page-item {
    display: inline;
}
.page-item a,
.page-item span,
.page-item .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    border: 1px solid var(--cst-border);
    border-radius: 10px;
    text-decoration: none;
    color: var(--cst-text);
    font-weight: 600;
    font-size: 13px;
    background: #ffffff;
    transition: all 0.2s ease;
}
.page-item a:hover {
    border-color: var(--cst-orange);
    color: var(--cst-orange);
    background: #fff8f3;
}
.page-item.active span,
.page-item.active .page-link,
.page-item.active a {
    background: var(--cst-orange) !important;
    color: #ffffff !important;
    border-color: var(--cst-orange) !important;
}
.page-item.disabled span,
.page-item.disabled .page-link,
.page-item.disabled a {
    color: var(--cst-muted) !important;
    background: #f9fafb !important;
    border-color: var(--cst-border) !important;
    cursor: not-allowed;
}
</style>
@endpush

@section('content')
<div class="cst-wrap">
    {{-- Header --}}
    <header class="cst-header">
        <div class="breadcrumbs">
            <span class="crumb-item">Dashboard</span>
            <span class="crumb-item active" style="color:var(--cst-text);font-weight:700;">CST Lead Allocation</span>
        </div>
        <div style="font-size:12px;color:var(--cst-muted);font-weight:500;">
            📊 Converted & Payment Completed (>= 40%)
        </div>
    </header>

    <div class="cst-body">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert-box alert-success">
                <span>✅</span> {!! session('success') !!}
            </div>
        @endif
        @if(session('error'))
            <div class="alert-box alert-error">
                <span>⚠️</span> {!! session('error') !!}
            </div>
        @endif

        {{-- Filter Bar --}}
        <div style="background:var(--cst-card-bg); border:1px solid var(--cst-border); border-radius:14px; padding:18px 24px; display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; box-shadow:0 4px 6px -1px rgba(0,0,0,0.04);">
            <form method="GET" action="{{ route('cst-allocation.index') }}" style="display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; width:100%;">
                <input type="hidden" name="tab" id="active_tab_field" value="{{ request('tab', 'pending') }}">

                <div style="display:flex; flex-direction:column; gap:5px; flex:1; min-width:180px;">
                    <label style="font-size:10px; font-weight:800; color:var(--cst-muted); text-transform:uppercase; letter-spacing:.5px;">Branch</label>
                    <select name="branch_id" style="padding:9px 12px; border:1px solid var(--cst-border); border-radius:10px; font-size:13px; background:#fafafa;">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:5px; flex:1; min-width:180px;">
                    <label style="font-size:10px; font-weight:800; color:var(--cst-muted); text-transform:uppercase; letter-spacing:.5px;">Product</label>
                    <select name="product_id" style="padding:9px 12px; border:1px solid var(--cst-border); border-radius:10px; font-size:13px; background:#fafafa;">
                        <option value="">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->product_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:5px; flex:1; min-width:180px;">
                    <label style="font-size:10px; font-weight:800; color:var(--cst-muted); text-transform:uppercase; letter-spacing:.5px;">Support TL</label>
                    <select name="tl_id" style="padding:9px 12px; border:1px solid var(--cst-border); border-radius:10px; font-size:13px; background:#fafafa;">
                        <option value="">All Support TLs</option>
                        @foreach($supportTls as $tl)
                            <option value="{{ $tl->id }}" {{ request('tl_id') == $tl->id ? 'selected' : '' }}>{{ $tl->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn-action btn-primary" style="padding:10px 20px;">Apply Filters</button>
                    <a href="{{ route('cst-allocation.index') }}" class="btn-action btn-secondary" style="padding:10px 20px; line-height:20px; text-align:center;">Reset</a>
                </div>
            </form>
        </div>

        {{-- Tabbed Controls --}}
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab(this, 'panelPending')">
                Allocation Pending ({{ $pendingLeads->total() }})
            </button>
            <button class="tab-btn" onclick="switchTab(this, 'panelCompleted')">
                Allocation Completed ({{ $completedLeads->total() }})
            </button>
        </div>

        {{-- Tab 1: Allocation Pending --}}
        <div id="panelPending" class="tab-panel active">
            <div class="allocation-panel">
                <h4>📋 Leads Awaiting Support TL Assignment</h4>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Lead Account</th>
                                <th>Sales Representative</th>
                                <th>Converted Products</th>
                                <th>Payment Collected</th>
                                <th style="text-align:center;">Payment Progress</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingLeads as $lead)
                                <tr>
                                    <td>
                                        <strong><a href="/leads/{{ $lead->id }}" style="color:var(--cst-orange);text-decoration:none;">{{ $lead->company_name ?: ($lead->contact_name ?: 'N/A') }}</a></strong>
                                    </td>
                                    <td>{{ $lead->assignedToUser?->name ?: 'Unassigned' }}</td>
                                    <td>
                                        <div class="products-list-container" style="cursor:pointer;" onclick="toggleAllProducts(this)">
                                            @foreach($lead->products->take(3) as $lp)
                                                <span style="font-weight:500; display:block; margin-bottom:2px;">🔹 {{ $lp->product_name }}</span>
                                            @endforeach
                                            @if($lead->products->count() > 3)
                                                <span class="more-products-indicator" style="font-size:11px; color:var(--cst-orange); font-weight:700; margin-top:4px; display:block;">
                                                    ➕ View All (+{{ $lead->products->count() - 3 }})
                                                </span>
                                                <div class="hidden-products" style="display:none; margin-top:4px;">
                                                    @foreach($lead->products->slice(3) as $lp)
                                                        <span style="font-weight:500; display:block; margin-bottom:2px; color:var(--cst-purple);">🔹 {{ $lp->product_name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight:700;">₹{{ number_format($lead->payment_amount_paid, 2) }}</span>
                                        <div style="font-size:11px;color:var(--cst-muted);">out of ₹{{ number_format($lead->payment_total_price, 2) }}</div>
                                    </td>
                                    <td style="vertical-align:middle;align-items:center;">
                                        <div style="display:flex;flex-direction:column;align-items:center;">
                                            <div class="prog-bar-container">
                                                <div class="prog-bar-fill" style="width: {{ min(100, $lead->payment_progress_pct) }}%"></div>
                                            </div>
                                            <div class="prog-bar-label">{{ $lead->payment_progress_pct }}% Completed</div>
                                        </div>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                                            @if($isSupportTl)
                                                <button class="btn-action btn-primary" onclick="openAllocateExecModal({{ $lead->id }}, '{{ addslashes($lead->company_name ?: $lead->contact_name) }}', '', '{{ $lead->customer_support_tl_id ?: auth()->id() }}')">
                                                    👥 Assign Executive
                                                </button>
                                            @endif
                                            @if($isAdmin)
                                                <button class="btn-action btn-primary" onclick="openAllocateTlModal({{ $lead->id }}, '{{ addslashes($lead->company_name ?: $lead->contact_name) }}')">
                                                    👤 Allocate TL
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--cst-muted);padding:32px 0;">
                                        No leads are currently pending customer support allocation.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                <div style="display:flex; justify-content:center; margin-top:20px;">
                    {{ $pendingLeads->appends(request()->except('page_pending'))->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>

        {{-- Tab 2: Allocation Completed --}}
        <div id="panelCompleted" class="tab-panel">
            <div class="allocation-panel">
                <h4>📋 Allocated Customer Support Team Leads</h4>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Lead Account</th>
                                <th>Assigned Support TL</th>
                                <th>Assigned CS Executive</th>
                                <th>Converted Products</th>
                                <th>Progress</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($completedLeads as $lead)
                                <tr>
                                    <td>
                                        <strong><a href="/leads/{{ $lead->id }}" style="color:var(--cst-purple);text-decoration:none;">{{ $lead->company_name ?: ($lead->contact_name ?: 'N/A') }}</a></strong>
                                    </td>
                                    <td>
                                        <span class="cst-badge badge-assigned">👑 {{ $lead->customerSupportTl?->name }}</span>
                                        <div style="font-size:10px;color:var(--cst-muted);margin-top:2px;">
                                            Allocated: {{ $lead->customer_support_allocated_at ? \Carbon\Carbon::parse($lead->customer_support_allocated_at)->format('d M Y') : '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($lead->customerSupportExecutive)
                                            <span class="cst-badge badge-assigned">👤 {{ $lead->customerSupportExecutive->name }}</span>
                                        @else
                                            <span class="cst-badge badge-unassigned">⏳ Pending Assignment</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="products-list-container" style="cursor:pointer;" onclick="toggleAllProducts(this)">
                                            @foreach($lead->products->take(3) as $lp)
                                                <span style="font-weight:500; display:block; margin-bottom:2px;">🔹 {{ $lp->product_name }}</span>
                                            @endforeach
                                            @if($lead->products->count() > 3)
                                                <span class="more-products-indicator" style="font-size:11px; color:var(--cst-orange); font-weight:700; margin-top:4px; display:block;">
                                                    ➕ View All (+{{ $lead->products->count() - 3 }})
                                                </span>
                                                <div class="hidden-products" style="display:none; margin-top:4px;">
                                                    @foreach($lead->products->slice(3) as $lp)
                                                        <span style="font-weight:500; display:block; margin-bottom:2px; color:var(--cst-purple);">🔹 {{ $lp->product_name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-direction:column;">
                                            <div class="prog-bar-container">
                                                <div class="prog-bar-fill" style="width: {{ min(100, $lead->payment_progress_pct) }}%"></div>
                                            </div>
                                            <div class="prog-bar-label">{{ $lead->payment_progress_pct }}% Completed</div>
                                        </div>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                                            @if($isAdmin || ($isSupportTl && $lead->customer_support_tl_id === auth()->id()))
                                                <button class="btn-action btn-primary" onclick="openAllocateExecModal({{ $lead->id }}, '{{ addslashes($lead->company_name ?: $lead->contact_name) }}', '{{ $lead->customer_support_executive_id }}', '{{ $lead->customer_support_tl_id }}')">
                                                    👥 Assign Executive
                                                </button>
                                            @endif
                                            @if($isAdmin)
                                                <button class="btn-action btn-secondary" onclick="openAllocateTlModal({{ $lead->id }}, '{{ addslashes($lead->company_name ?: $lead->contact_name) }}', '{{ $lead->customer_support_tl_id }}')">
                                                    Re-allocate TL
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--cst-muted);padding:32px 0;">
                                        No allocated leads found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                <div style="display:flex; justify-content:center; margin-top:20px;">
                    {{ $completedLeads->appends(request()->except('page_completed'))->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 1: ALLOCATE TL --}}
<div id="modalAllocateTl" class="modal-layer">
    <div class="modal-box">
        <div class="modal-title">
            <span>Allocate Customer Support TL</span>
            <button class="modal-close" onclick="closeModal('modalAllocateTl')">&times;</button>
        </div>
        <form id="formAllocateTl" method="POST" action="">
            @csrf
            <div class="modal-body">
                <div style="font-size:13px;color:var(--cst-muted);margin-bottom:8px;">
                    Assigning TL for Account: <strong id="tlModalLeadName" style="color:var(--cst-text);"></strong>
                </div>
                <label for="modal_tl_id">Select Support TL</label>
                <select name="customer_support_tl_id" id="modal_tl_id" required>
                    <option value="">-- Choose Support Team Lead --</option>
                    @foreach($supportTls as $tl)
                        <option value="{{ $tl->id }}">{{ $tl->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-secondary" onclick="closeModal('modalAllocateTl')">Cancel</button>
                <button type="submit" class="btn-action btn-primary">Allocate Lead</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: ALLOCATE EXECUTIVE --}}
<div id="modalAllocateExec" class="modal-layer">
    <div class="modal-box">
        <div class="modal-title">
            <span>Assign Support Executive</span>
            <button class="modal-close" onclick="closeModal('modalAllocateExec')">&times;</button>
        </div>
        <form id="formAllocateExec" method="POST" action="">
            @csrf
            <div class="modal-body">
                <div style="font-size:13px;color:var(--cst-muted);margin-bottom:8px;">
                    Account: <strong id="execModalLeadName" style="color:var(--cst-text);"></strong>
                </div>
                <label for="modal_exec_tl_id">Assigned Support TL</label>
                <select id="modal_exec_tl_id" disabled style="padding:10px 12px; border:1px solid var(--cst-border); border-radius:10px; font-size:13px; outline:none; background:#e5e7eb; cursor:not-allowed; margin-bottom:12px; width:100%;">
                    @foreach($supportTls as $tl)
                        <option value="{{ $tl->id }}">{{ $tl->name }}</option>
                    @endforeach
                </select>
                <label for="modal_exec_id">Select Support Agent</label>
                <select name="customer_support_executive_id" id="modal_exec_id" required>
                    <option value="">-- Choose Support Agent --</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-secondary" onclick="closeModal('modalAllocateExec')">Cancel</button>
                <button type="submit" class="btn-action btn-primary">Assign Executive</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// TL to Executives mappings
const tlMappedExecutives = {
    @foreach($supportTls as $tl)
        "{{ $tl->id }}": [
            @foreach(\App\Models\UserMapping::where('manager_id', $tl->id)->pluck('user_id') as $mId)
                "{{ (int) $mId }}",
            @endforeach
        ],
    @endforeach
};

const supportAgentsMaster = {
    tl: [
        @foreach($supportTls as $tl)
            { id: "{{ $tl->id }}", name: "{{ addslashes($tl->name) }}" },
        @endforeach
    ],
    executives: [
        @foreach($supportExecutives as $exec)
            { id: "{{ $exec->id }}", name: "{{ addslashes($exec->name) }}" },
        @endforeach
    ]
};

function switchTab(btn, panelId) {
    // Tabs UI
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Panels UI
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById(panelId).classList.add('active');

    // Set active tab value in form
    const tabName = panelId === 'panelPending' ? 'pending' : 'completed';
    document.getElementById('active_tab_field').value = tabName;
}

function openAllocateTlModal(leadId, leadName, currentTlId = '') {
    const modal = document.getElementById('modalAllocateTl');
    const form = document.getElementById('formAllocateTl');
    const nameSpan = document.getElementById('tlModalLeadName');
    const select = document.getElementById('modal_tl_id');

    form.action = `{{ url('/') }}/cst-allocation/${leadId}/allocate-tl`;
    nameSpan.textContent = leadName;
    select.value = currentTlId;

    modal.classList.add('open');
}

function openAllocateExecModal(leadId, leadName, currentExecId = '', tlId = '') {
    const modal = document.getElementById('modalAllocateExec');
    const form = document.getElementById('formAllocateExec');
    const nameSpan = document.getElementById('execModalLeadName');
    const selectTl = document.getElementById('modal_exec_tl_id');
    const selectExec = document.getElementById('modal_exec_id');

    form.action = `{{ url('/') }}/cst-allocation/${leadId}/allocate-executive`;
    nameSpan.textContent = leadName;
    selectTl.value = tlId || '{{ auth()->id() }}';

    // Clear dropdown and rebuild dynamically
    selectExec.innerHTML = '';
    const activeTlId = tlId || '{{ auth()->id() }}';

    // 1. Add default option
    const defOpt = document.createElement('option');
    defOpt.value = '';
    defOpt.textContent = '-- Choose Support Agent --';
    selectExec.appendChild(defOpt);

    // 2. Add TL option (Self-Allocation option)
    const tlObj = supportAgentsMaster.tl.find(t => String(t.id) === String(activeTlId));
    if (tlObj) {
        const tlGroup = document.createElement('optgroup');
        tlGroup.label = 'Support Team Lead (Self-Allocation)';
        const tlOpt = document.createElement('option');
        tlOpt.value = tlObj.id;
        tlOpt.textContent = `${tlObj.name} (TL)`;
        tlGroup.appendChild(tlOpt);
        selectExec.appendChild(tlGroup);
    }

    // 3. Add mapped Executives
    const mappedIds = tlMappedExecutives[activeTlId] || [];
    const filteredExecs = supportAgentsMaster.executives.filter(e => mappedIds.map(String).includes(String(e.id)));
    if (filteredExecs.length > 0) {
        const execGroup = document.createElement('optgroup');
        execGroup.label = 'Support Executives';
        filteredExecs.forEach(exec => {
            const execOpt = document.createElement('option');
            execOpt.value = exec.id;
            execOpt.textContent = exec.name;
            execGroup.appendChild(execOpt);
        });
        selectExec.appendChild(execGroup);
    }

    selectExec.value = currentExecId;
    modal.classList.add('open');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

function toggleAllProducts(container) {
    const hiddenDiv = container.querySelector('.hidden-products');
    const indicator = container.querySelector('.more-products-indicator');
    if (hiddenDiv) {
        if (hiddenDiv.style.display === 'none') {
            hiddenDiv.style.display = 'block';
            indicator.textContent = '➖ Show Less';
        } else {
            hiddenDiv.style.display = 'none';
            const totalCount = container.querySelectorAll('span').length;
            const remaining = totalCount - 4; // First 3 + indicator + remaining
            indicator.textContent = `➕ View All (+${remaining})`;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Preserve active tab state from GET parameters on load
    const activeTab = "{{ request('tab', 'pending') }}";
    if (activeTab === 'completed') {
        const btn = document.querySelector("button[onclick*='panelCompleted']");
        if (btn) switchTab(btn, 'panelCompleted');
    }
});

// Close modals when clicking overlay
document.querySelectorAll('.modal-layer').forEach(layer => {
    layer.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
        }
    });
});
</script>
@endpush
