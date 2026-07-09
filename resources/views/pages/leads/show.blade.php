@extends('layouts.app')

@section('title', 'Lead — ' . $lead->company_name)

@include('pages.leads.show_page_style')

<style>
.page-wrapper { padding: 32px; }
.page-header {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 24px;
}
.page-title { font-size: 22px; font-weight: 700; color: #121212; }
.btn-primary {
    display: inline-flex; align-items: center; gap: 6px;
    background-color: #fe5f04; color: #fff;
    padding: 8px 18px; border-radius: 20px;
    font-size: 14px; font-weight: 600; border: none; cursor: pointer;
    text-decoration: none;
}
.btn-primary:hover { background-color: #e05400; }
.btn-sm {
    padding: 4px 12px; font-size: 12px; border-radius: 12px;
}
.card {
    background: #fff; border: 1px solid #e1dee3;
    border-radius: 14px; overflow: hidden;
}
.table-responsive { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8f8f8; }
th {
    padding: 12px 16px; font-size: 12px; font-weight: 600;
    color: #7c7c7c; text-align: left; border-bottom: 1px solid #e1dee3;
}
td {
    padding: 13px 16px; font-size: 13px; color: #121212;
    border-bottom: 1px solid #f1f1f1; vertical-align: middle;
}
tbody tr:hover { background: #fdf8f5; }
tbody tr:last-child td { border-bottom: none; }
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.badge-approved  { background: #edfaf3; color: #1a7a52; border: 1px solid #b6ead0; }
.badge-pending   { background: #fff8ec; color: #9a6200; border: 1px solid #ffd98a; }
.action-btns { display: flex; gap: 6px; }
.btn-outline-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #e1dee3;
    background: #fff; cursor: pointer; text-decoration: none; color: #444;
}
.btn-outline-sm:hover { border-color: #fe5f04; color: #fe5f04; }
.btn-danger-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #ffc4c4;
    background: #fff5f5; cursor: pointer; color: #c00;
    text-decoration: none;
}
.alert-success {
    background: #edfaf3; border: 1px solid #b6ead0; color: #1a7a52;
    padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
}
.empty-state { text-align: center; padding: 60px 20px; color: #9e9e9e; }
.empty-state i { font-size: 42px; margin-bottom: 12px; display: block; }

   #summaryPreview {
        background: #ffffff;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #f3f4f6;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        margin-top: 15px;
    }

    #summaryPreview hr {
        margin: 10px 0 15px;
    }

    /* Title */
    #summaryPreview .title {
        font-size: 14px;
        font-weight: 600;
        color: #fe5f04; /* ORANGE */
        display: flex;
        align-items: center;
    }

    /* Labels */
    #summaryPreview .label {
        font-size: 12px;
        margin-bottom: 5px;
        display: block;
    }

    /* Boxes */
    .preview-box {
        max-height: 130px;
        overflow-y: auto;
        white-space: pre-wrap;
        font-size: 13px;
        border-radius: 8px;
        padding: 10px;
    }

    /* Original */
    #originalTextPreview {
        background: #fff7ed; /* light orange bg */
        border: 1px solid #fdba74;
        color: #7c2d12;
    }

    /* Summary */
    #summarizedTextPreview {
        background: #fff7ed;
        border: 1px solid #fe5f04;
        color: #9a3412;
        font-weight: 500;
    }

    /* Buttons */
    .btn-use {
        background: #fe5f04;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 14px;
    }

    .btn-use:hover {
        background: #e55303;
    }

    .btn-keep {
        border: 1px solid #fe5f04;
        color: #fe5f04;
        background: transparent;
        border-radius: 6px;
        padding: 6px 14px;
    }

    .btn-keep:hover {
        background: #fe5f04;
        color: #fff;
    }

.actions {
        margin-top: 12px;
        display: flex;
        gap: 10px;
    }
.lsp-prod-update-shell{display:grid;gap:18px}
.lsp-prod-update-toolbar{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;flex-wrap:wrap}
.lsp-prod-update-copy{font-size:13px;line-height:1.6;color:#6b7280;max-width:720px}
.lsp-prod-update-filters{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.lsp-prod-update-filter{display:flex;flex-direction:column;gap:6px}
.lsp-prod-update-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280}
.lsp-prod-update-input{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;font-size:13px;color:#111827}
.lsp-prod-update-list{display:grid;gap:14px}
.lsp-prod-update-item{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;display:grid;gap:12px}
.lsp-prod-update-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}
.lsp-prod-update-product{font-size:16px;font-weight:800;color:#111827}
.lsp-prod-update-meta{font-size:12px;line-height:1.6;color:#6b7280;text-align:right}
.lsp-prod-update-type{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;border:1px solid transparent}
.lsp-prod-update-title{margin-top:8px;font-size:13px;font-weight:700;color:#374151}
.lsp-prod-update-content{font-size:13px;line-height:1.7;color:#1f2937}
.lsp-prod-update-content p:last-child{margin-bottom:0}
.lsp-prod-update-empty{padding:40px 18px;border:1px dashed #d1d5db;border-radius:16px;background:#fafafa;text-align:center;color:#6b7280;font-size:14px}
.lsp-ah-stack{display:grid;gap:14px}
.lsp-ah-accordion{border:1px solid #e5e7eb;border-radius:18px;background:#fff;overflow:hidden}
.lsp-ah-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;cursor:pointer;background:linear-gradient(180deg,#fff,#fafafa)}
.lsp-ah-head-left{min-width:0}
.lsp-ah-product{font-size:15px;font-weight:800;color:#111827}
.lsp-ah-sub{margin-top:4px;font-size:12px;color:#6b7280}
.lsp-ah-head-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.lsp-ah-status{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:11px;font-weight:800}
.lsp-ah-chevron{width:28px;height:28px;border-radius:999px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#6b7280;transition:transform .18s ease}
.lsp-ah-accordion.open .lsp-ah-chevron{transform:rotate(180deg)}
.lsp-ah-body{display:none;padding:0 18px 18px;border-top:1px solid #eef2f7}
.lsp-ah-accordion.open .lsp-ah-body{display:block}
.lsp-ah-timeline{display:grid;gap:14px;padding-top:18px}
.lsp-ah-item{position:relative;padding:16px 16px 16px 18px;border:1px solid #eef2f7;border-radius:18px;background:#fff}
.lsp-ah-item::before{content:'';position:absolute;left:0;top:16px;bottom:16px;width:4px;border-radius:999px;background:var(--timeline-color,#2563eb)}
.lsp-ah-title{font-size:14px;font-weight:900;color:#111827}
.lsp-ah-subline{margin-top:4px;font-size:12px;color:#6b7280}
.lsp-ah-meta{margin-top:10px;font-size:13px;color:#334155;line-height:1.7}
.lsp-ah-empty{padding:18px;border:1px dashed #d1d5db;border-radius:14px;background:#fafafa;color:#6b7280;font-size:13px}
@media (max-width: 992px){
    .lsp-prod-update-filters{grid-template-columns:1fr 1fr}
}
@media (max-width: 640px){
    .lsp-prod-update-filters{grid-template-columns:1fr}
    .lsp-prod-update-meta{text-align:left}
    .lsp-ah-head{align-items:flex-start}
    .lsp-ah-head-right{justify-content:flex-start}
}
</style>
@section('content')
@php
    $colors  = ['#fe5f04','#7c3aed','#2563eb','#16a34a','#be123c','#0284c7','#b45309','#0f766e'];
    $heroColor = $colors[$lead->id % count($colors)];
    $sc = $lead->status_color;
    $pc = $lead->priority_color;
    $priClass = ['low'=>'','medium'=>'','high'=>''][$lead->priority] ?? '';

    $incomingCallCount     = $lead->callUpdates->where('call_type', 'incoming')->count();
    $outGoingcallCount     = $lead->callUpdates->where('call_type', 'outgoing')->count();
    $remCount      = $lead->reminders->where('is_completed', false)->count();
    $overdueRem    = $lead->reminders->where('is_completed', false)->filter(fn($r) => $r->remind_at->isPast())->count();
    $prodCount     = $lead->products->count();
    $qtCount       = $lead->quotations->count();
    $customFieldValues = $lead->customFieldValues
        ->filter(fn ($fieldValue) => $fieldValue->field && $fieldValue->field->is_active)
        ->sortBy(fn ($fieldValue) => [$fieldValue->field->sort_order ?? 9999, strtolower($fieldValue->field->label ?? '')]);
    $productionUpdateTypeMeta = [
        'production_update' => ['label' => 'Production Update', 'title' => 'Execution Progress', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1d4ed8'],
        'meeting_update' => ['label' => 'Meeting Update', 'title' => 'Discussion Notes', 'bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#c2410c'],
        'weekly_update' => ['label' => 'Weekly Update', 'title' => 'Weekly Summary', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#15803d'],
    ];
    $productionUpdates = $lead->products
        ->flatMap(function ($product) {
            $updates = $product->latestProductionInitiation?->projectUpdates ?? collect();

            return $updates->map(function ($update) use ($product) {
                $update->lead_product_name = $product->product_name;

                return $update;
            });
        })
        ->sortByDesc('created_at')
        ->values();
    $productionUpdateProductOptions = $productionUpdates
        ->pluck('lead_product_name')
        ->filter()
        ->unique()
        ->sort()
        ->values();
    $productionUpdateCount = $productionUpdates->count();
    $approvalHistoryProducts = $lead->products
        ->filter(fn ($product) => $product->latestProductionInitiation)
        ->values();
    $approvalHistoryCount = $approvalHistoryProducts->count();

    $totalValue    = $lead->products->sum('total_price');
    $totalPaid     = $lead->products->sum('amount_paid');
    $totalPending  = $totalValue - $totalPaid;
@endphp

<div class="lsp">

    {{-- ── Topbar ── --}}
    <div class="lsp-topbar">
        <div>
            <div class="lsp-title">{{ $lead->company_name }}</div>
            <div class="lsp-crumb">
                <a href="{{ route('leads.index') }}">Leads</a> ›
                LD-{{ str_pad($lead->id,4,'0',STR_PAD_LEFT) }}
            </div>
        </div>
        <div class="lsp-topbar-right">
            <a href="{{ route('leads.edit', $lead) }}" class="lsp-btn lsp-btn-primary">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Lead
            </a>
            <a href="{{ route('leads.index') }}" class="lsp-btn lsp-btn-outline">← Back</a>
        </div>
    </div>

    {{-- ── Hero Strip ── --}}
    <div class="lsp-hero">
        <div class="lsp-hero-logo" style="background:{{ $heroColor }}">{{ strtoupper(substr($lead->company_name,0,2)) }}</div>
        <div>
            <div class="lsp-hero-name">{{ $lead->company_name }}</div>
            <div class="lsp-hero-sub">
                <span>{{ $lead->contact_name }}</span>
                <span style="color:#ddd">·</span>
                <span>{{ $lead->mobile_number }}</span>
                @if($lead->email)
                <span style="color:#ddd">·</span>
                <span>{{ $lead->email }}</span>
                @endif
                <span style="color:#ddd">·</span>
                <span>{{ $lead->lead_date->format('d M Y') }}</span>
            </div>
        </div>
        <div class="lsp-hero-right">
            <span class="lsp-badge" style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};border-color:{{ $sc['border'] }}">
                <span class="lsp-dot" style="background:{{ $sc['text'] }}"></span>
                {{ $lead->status_label }}
            </span>
            <span class="lsp-badge" style="background:{{ $pc['bg'] }};color:{{ $pc['text'] }};border-color:{{ $pc['border'] }}">
                {{ $lead->priority_label }} Priority
            </span>
            @if($lead->deal_value)
            <span style="font-size:15px;font-weight:800;color:var(--text);">{{ $lead->formatted_deal_value }}</span>
            @endif
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="lsp-tabs-wrap">
        <div class="lsp-tabs">
            <button class="lsp-tab active" onclick="switchTab('info', this)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Lead Info
            </button>
            <button class="lsp-tab" onclick="switchTab('calls', this)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.71 19a19.5 19.5 0 0 1-5.52-5.51A19.79 19.79 0 0 1 3.08 4.18 2 2 0 0 1 5.06 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L9.91 9.91a16 16 0 0 0 5.71 5.71l.63-.63a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Call Updates
                <span class="lsp-tab-count">{{ $outGoingcallCount + $incomingCallCount }}</span>
            </button>
            <button class="lsp-tab" onclick="switchTab('reminders', this)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Reminders
                <span class="lsp-tab-count {{ $overdueRem ? 'urgent' : '' }}">{{ $remCount }}</span>
            </button>
            <button class="lsp-tab" onclick="switchTab('products', this)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Products & Payments
                <span class="lsp-tab-count">{{ $prodCount }}</span>
            </button>
            <button class="lsp-tab" onclick="switchTab('quotations', this)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Quotations
                <span class="lsp-tab-count">{{ $qtCount }}</span>
            </button>
            <button class="lsp-tab" onclick="switchTab('production-updates', this)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="m18 20-6-6-6 6"/><path d="M6 4h12"/></svg>
                Production Update
                <span class="lsp-tab-count">{{ $productionUpdateCount }}</span>
            </button>
            <button class="lsp-tab" onclick="switchTab('approval-history', this)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Approval History
                <span class="lsp-tab-count">{{ $approvalHistoryCount }}</span>
            </button>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="lsp-body">

        {{-- Flash messages --}}
        @if(session('success') || session('error'))
        <div style="padding:14px 28px 0;">
            @if(session('success'))
            <div class="lsp-alert lsp-alert-success">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                {!! session('success') !!}
            </div>
            @endif
            @if(session('error'))
            <div class="lsp-alert lsp-alert-error">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
                {!! session('error') !!}
            </div>
            @endif
        </div>
        @endif

        {{-- ════════════════════════════════════════
             TAB 1 — LEAD INFO
        ════════════════════════════════════════ --}}
        <div class="lsp-panel active" id="panel-info">
            <div class="lsp-grid-2" style="gap:18px;">

                {{-- Left --}}
                <div class="lsp-stack">

                    <div class="lsp-card">
                        <div class="lsp-card-head"><div class="lsp-card-title">📋 Contact Information</div></div>
                        <div class="lsp-card-body">
                            <div class="lsp-info-grid">
                                <div class="lsp-info-item"><div class="lsp-il">Company</div><div class="lsp-iv">{{ $lead->company_name }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Contact Person</div><div class="lsp-iv">{{ $lead->contact_name }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Mobile</div><div class="lsp-iv"><a href="tel:{{ $lead->mobile_number }}">{{ $lead->mobile_number }}</a></div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Email</div><div class="lsp-iv">{!! $lead->email ? '<a href="mailto:'.$lead->email.'">'.$lead->email.'</a>' : '—' !!}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Lead Date</div><div class="lsp-iv">{{ $lead->lead_date->format('d M Y') }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Product</div><div class="lsp-iv">{{ $lead->product_name ?? '—' }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Source</div><div class="lsp-iv">{{ $lead->source_label }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Branch</div><div class="lsp-iv">{{ $lead->branch?->name ?? '—' }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Assigned To</div><div class="lsp-iv">{{ $lead->assignedTo?->name ?? 'Unassigned' }}</div></div>
                                <div class="lsp-info-item"><div class="lsp-il">Created By</div><div class="lsp-iv">{{ $lead->createdBy?->name ?? 'System' }}</div></div>
                            </div>
                        </div>
                    </div>

                    @if($customFieldValues->isNotEmpty())
                    <div class="lsp-card">
                        <div class="lsp-card-head"><div class="lsp-card-title">🧩 Custom Fields</div></div>
                        <div class="lsp-card-body">
                            <div class="lsp-info-grid">
                                @foreach($customFieldValues as $fieldValue)
                                <div class="lsp-info-item">
                                    <div class="lsp-il">{{ $fieldValue->field->label }}</div>
                                    <div class="lsp-iv">
                                        @php
                                            $displayValue = $fieldValue->value;
                                            $decodedValue = json_decode((string) $fieldValue->value, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedValue)) {
                                                $displayValue = implode(', ', array_filter($decodedValue, fn ($value) => $value !== null && $value !== ''));
                                            }
                                        @endphp
                                        {{ $displayValue !== '' ? $displayValue : '—' }}
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="lsp-card">
                        <div class="lsp-card-head"><div class="lsp-card-title">💬 Remarks</div></div>
                        <div class="lsp-card-body">
                            <div class="lsp-remarks-box">{{ $lead->remarks ?? 'No remarks added.' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Right --}}
                <div class="lsp-stack">


                    <div class="lsp-card">
                        <div class="lsp-card-head"><div class="lsp-card-title">💰 Deal & Status</div></div>
                        <div class="lsp-card-body">
                            <div class="lsp-stat-grid">
                                <div class="lsp-stat-box">
                                    <div class="lsp-il">Deal Value</div>
                                    <div class="lsp-deal-big">₹{{ number_format($lead->products->sum('total_price'), 2) }}</div>
                                </div>
                                <div class="lsp-stat-box blue">
                                    <div class="lsp-il">Number Of Products</div>
                                    <div class="lsp-deal-big">{{ $lead->products->count() ?? 0 }}</div>
                                </div>
                            </div>
                            {{--  <div style="margin-bottom:6px;font-size:11px;font-weight:700;color:#3d3d3d;text-transform:uppercase;letter-spacing:.4px;">Quick Status Change</div>
                            <form method="POST" action="{{ route('leads.update-status', $lead) }}">
                                @csrf @method('PATCH')
                                <div style="position:relative;">
                                    <select name="lead_status" class="lsp-status-select" onchange="this.form.submit()">
                                        @foreach(\App\Models\Lead::statusOptions() as $k => $v)
                                        <option value="{{ $k }}" {{ $lead->lead_status===$k?'selected':'' }}>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                    <svg style="position:absolute;right:9px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9e9e9e;width:11px;height:11px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </div>
                            </form>  --}}

                            <div class="lsp-meta-line">
                                <div>
                                    <div class="lsp-il">Current Status</div>
                                    <div class="lsp-meta-value">{{ $lead->status_label }}</div>
                                </div>
                                <span class="lsp-age-pill">{{ $lead->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="lsp-card">
                        <div class="lsp-card-head"><div class="lsp-card-title">⚠️ Danger Zone</div></div>
                        <div class="lsp-card-body">
                            <p class="lsp-danger-note">Delete this lead only if you are sure. This action removes the lead record and cannot be undone.</p>
                            <form method="POST" action="{{ route('leads.destroy', $lead) }}"
                                  onsubmit="return confirm('Permanently delete this lead?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="lsp-btn lsp-btn-danger" style="width:100%;justify-content:center;">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    Delete This Lead
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════
             TAB 2 — CALL UPDATES
        ════════════════════════════════════════ --}}
        <div class="lsp-panel" id="panel-calls">
            <div class="lsp-grid-2" style="gap:18px;align-items:start;">

                {{-- Left: Log list --}}
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <h3 style="font-size:15px;font-weight:700;color:var(--text);">📞 Call History</h3>
                        <div>

<span style="font-size:12px;color:var(--muted);"> | </span>
                        <span style="font-size:12px;color:var(--orange);">{{ $outGoingcallCount }} Outgoing Call records</span>
                        </div>

                    </div>

                    @if($lead->callUpdates->isEmpty())
                    <div class="lsp-empty">
                        <div class="lsp-empty-ico">📞</div>
                        <div class="lsp-empty-title">No calls logged yet</div>
                        <div class="lsp-empty-sub">Log your first call using the form →</div>
                    </div>
                    @else
                    <div class="lsp-call-list">
                        @foreach($lead->callUpdates->sortByDesc('called_at') as $call)
                        @php $oc = $call->outcome_color; @endphp
                        <div class="lsp-call-card type-{{ $call->call_type }}">
                            <div class="lsp-call-top">
                                <div class="lsp-call-meta">
                                    <span class="lsp-call-type ct-{{ $call->call_type }}">
                                        {{ $call->call_type_label }}
                                    </span>
                                    <span class="lsp-call-when">{{ $call->called_at->format('d M Y, h:i A') }}</span>
                                    @if($call->duration_minutes)
                                    <span class="lsp-call-dur">⏱ {{ $call->duration_minutes }} min</span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('leads.calls.destroy', [$lead, $call]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="lsp-call-del" title="Remove"
                                            onclick="return confirm('Remove this call record?')">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </form>
                            </div>
                            @if($call->notes)
                            <div class="lsp-call-notes">{{ $call->notes }}</div>
                            @endif
                            <div class="lsp-call-footer">
                                <span class="lsp-call-outcome" style="background:#f0fdf4;color:#16a34a">
                                    {{ $call?->outCome?->name }} - {{ $call?->outComeSubCategory?->name }}
                                </span>
                                @if($call->next_follow_up)
                                <span class="lsp-call-followup">
                                    📅 Follow-up: {{ $call->next_follow_up->format('d M Y') }}
                                </span>
                                @endif
                                <span class="lsp-call-by">By {{ $call->user?->name }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Right: Add call form --}}
                <div>
                    <div class="lsp-card">
                        <div class="lsp-card-head">
                            <div class="lsp-card-title">➕ Log New Call</div>
                        </div>
                        <div class="lsp-card-body">
                            <form method="POST" action="{{ route('leads.calls.store', $lead) }}">
                                @csrf
                                <div style="display:flex;flex-direction:column;gap:12px;">

                                    <div class="lsp-form-row lsp-form-row-2">


                                    </div>

                                    <div class="lsp-form-row lsp-form-row-2">
                                        {{--  <div class="lsp-group">
                                            <label class="lsp-label">Duration (minutes)</label>
                                            <div class="lsp-fw">
                                                <svg class="lsp-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                <input type="number" name="duration_minutes" class="lsp-inp" placeholder="0" min="0">
                                            </div>
                                        </div>  --}}
                                        <div class="lsp-group">
                                            <label class="lsp-label">Outcome <span class="lsp-req">*</span></label>
                                            <div class="lsp-fw">
                                                <svg class="lsp-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                <select name="outcome" id="outcome_category" class="lsp-sel" required>
                                                    <option value="">— Select Outcome —</option>
                                                     @foreach($outcomes as $v)
                                                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                                                        @endforeach
                                                </select>
                                                <svg class="lsp-sel-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                            </div>
                                        </div>

                                        <div class="lsp-group">
                                            <label class="lsp-label">Outcome Subcategory<span class="lsp-req">*</span></label>
                                            <div class="lsp-fw">
                                                <svg class="lsp-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                                <select name="outcome_sub_category_id" id="outcome_sub_category" class="lsp-sel" required>
                                                    <option value="">— Select sub category —</option>
                                                </select>
                                                <svg class="lsp-sel-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lsp-group">
                                        <label class="lsp-label">Call Notes <span class="lsp-req">*</span></label>
                                        <textarea name="notes" class="lsp-ta" id="addNoteText" placeholder="What was discussed? Any key points…" rows="3" required></textarea>
                                    </div>



                                    <div class="d-flex justify-content-end mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="summarizeBtn"
                                                onclick="summarizeNote()">
                                                <i class="fas fa-magic me-1"></i> AI Summarize
                                            </button>
                                        </div>

                                       <div id="summaryPreview" style="display:none;">
    <hr style="border:none; border-top:0.5px solid #e1dee3; margin:0 0 1.25rem;">

    <p style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; color:#7c7c7c; margin:0 0 1rem;">
        <i class="fas fa-robot" style="font-size:14px; color:#9e9e9e;"></i>
        AI Summary Preview
    </p>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:1.25rem;">
        <div>
            <p style="font-size:12px; font-weight:500; color:#7c7c7c; margin:0 0 8px;">Original</p>
            <div id="originalTextPreview"
                style="background:#f8f8f8; border:0.5px solid #e1dee3; border-radius:8px;
                       padding:12px 14px; font-size:12px; line-height:1.75; color:#7c7c7c;
                       min-height:90px; max-height:140px; overflow-y:auto; white-space:pre-wrap;">
            </div>
        </div>
        <div>
            <p style="font-size:12px; font-weight:500; color:#c45f04; margin:0 0 8px;">Summarized</p>
            <div id="summarizedTextPreview"
                style="background:#fff8f3; border:0.5px solid #f5d3b8; border-radius:8px;
                       padding:12px 14px; font-size:12px; line-height:1.75; color:#7a3b0d;
                       min-height:90px; max-height:140px; overflow-y:auto; white-space:pre-wrap;">
            </div>
        </div>
    </div>

    <div style="display:flex; gap:10px;">
        <button type="button" onclick="acceptSummary()"
            style="display:inline-flex; align-items:center; gap:7px; padding:8px 18px;
                   border-radius:20px; background:#fe5f04; border:none; color:#fff;
                   font-size:13px; font-weight:500; cursor:pointer;">
            <i class="fas fa-check" style="font-size:12px;"></i> Use Summary
        </button>
        <button type="button" onclick="rejectSummary()"
            style="display:inline-flex; align-items:center; gap:7px; padding:8px 18px;
                   border-radius:20px; background:transparent; border:0.5px solid #c8c4cc;
                   color:#7c7c7c; font-size:13px; font-weight:500; cursor:pointer;">
            <i class="fas fa-times" style="font-size:12px;"></i> Keep Original
        </button>
    </div>
</div>

                        <div id="summarizeSpinner" style="display:none;" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary me-2"  role="status"></div>
                            <span class="text-muted small" style="font-size:12px;color:green">Generating summary...</span>
                        </div>

                        {{-- Error --}}
                        <div id="summarizeError" class="alert alert-danger alert-sm py-2 mt-2 d-none" style="color:red;font-size:12px" role="alert">

                            <span id="summarizeErrorMsg"></span>
                        </div>

                                    <div class="lsp-form-row lsp-form-row-2">
                                         <div class="lsp-group">
                                            <label class="lsp-label">Next Follow-up Date <span class="lsp-req">*</span></label>
                                            <div class="lsp-fw">
                                                <svg class="lsp-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                <input type="date" name="next_follow_up" class="lsp-inp" min="{{ today()->toDateString() }}" required>
                                            </div>
                                         </div>

                                         <div class="lsp-group">
                                            <label class="lsp-label">Next Follow-up Time <span class="lsp-req">*</span></label>
                                            <div class="lsp-fw">
                                                <svg class="lsp-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                <input type="time" name="followup_time" class="lsp-inp" required>
                                            </div>
                                         </div>

                                    </div>




                                    <button type="submit" class="lsp-btn lsp-btn-primary" style="justify-content:center;">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Save Call Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════
             TAB 3 — REMINDERS
        ════════════════════════════════════════ --}}
        <div class="lsp-panel" id="panel-reminders">
            <div class="lsp-grid-2" style="gap:18px;align-items:start;">

                {{-- Left: Reminder list --}}
                <div>
                    @php
                        $pending   = $lead->reminders->where('is_completed', false)->sortBy('remind_at');
                        $completed = $lead->reminders->where('is_completed', true)->sortByDesc('completed_at');
                    @endphp

                    @if($pending->isNotEmpty())
                    <h3 style="font-size:14px;font-weight:700;margin-bottom:10px;color:var(--text);">🔔 Pending ({{ $pending->count() }})</h3>
                    <div class="lsp-rem-list" style="margin-bottom:18px;">
                        @foreach($pending as $rem)
                        @php $isOverdue = $rem->is_overdue; @endphp
                        <div class="lsp-rem-card {{ $isOverdue ? 'overdue' : '' }}">
                            <div class="lsp-rem-ico" style="background:{{ $isOverdue ? '#fef2f2' : '#f5f4f6' }}">{{ $rem->type_icon }}</div>
                            <div class="lsp-rem-body">
                                <div class="lsp-rem-title">
                                    {{ $rem->title }}
                                    @if($isOverdue)
                                    <span class="lsp-badge" style="background:#fef2f2;color:#dc2626;border-color:#fecaca;font-size:10px;">Overdue</span>
                                    @endif
                                </div>
                                @if($rem->description)
                                <div class="lsp-rem-desc">{{ $rem->description }}</div>
                                @endif
                                <div class="lsp-rem-meta">
                                    <span class="lsp-rem-time {{ $isOverdue ? 'overdue' : '' }}">
                                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        {{ $rem->remind_at->format('d M Y') }}  {{ $rem->remainder_time->format('H:i:s') }}
                                    </span>
                                    <span class="lsp-badge" style="background:#f5f4f6;color:#555;border-color:#e1dee3;font-size:10px;">{{ $rem->type_label }}</span>
                                    <span class="lsp-badge" style="font-size:10px;background:{{ ['low'=>'#f0fdf4','medium'=>'#fffbeb','high'=>'#fef2f2'][$rem->priority] ?? '#f5f4f6' }};color:{{ ['low'=>'#16a34a','medium'=>'#b45309','high'=>'#dc2626'][$rem->priority] ?? '#7c7c7c' }};border:none;">{{ ucfirst($rem->priority) }}</span>
                                </div>
                            </div>
                            <div class="lsp-rem-actions">
                                <form method="POST" action="{{ route('leads.reminders.complete', [$lead, $rem]) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="lsp-rem-done-btn">✓ Done</button>
                                </form>
                                {{--  <form method="POST" action="{{ route('leads.reminders.destroy', [$lead, $rem]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="lsp-rem-del-btn" onclick="return confirm('Remove reminder?')">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </form>  --}}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if($completed->isNotEmpty())
                    <h3 style="font-size:13px;font-weight:700;margin-bottom:8px;color:var(--muted);">✅ Completed ({{ $completed->count() }})</h3>
                    <div class="lsp-rem-list">
                        @foreach($completed->take(5) as $rem)
                        <div class="lsp-rem-card done">
                            <div class="lsp-rem-ico" style="background:#f0fdf4">✅</div>
                            <div class="lsp-rem-body">
                                <div class="lsp-rem-title done">{{ $rem->title }}</div>
                                <div class="lsp-rem-meta">
                                    <span style="font-size:11px;color:var(--muted);">Completed {{ $rem->completed_at?->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if($lead->reminders->isEmpty())
                    <div class="lsp-empty">
                        <div class="lsp-empty-ico">🔔</div>
                        <div class="lsp-empty-title">No reminders set</div>
                        <div class="lsp-empty-sub">Set a reminder using the form →</div>
                    </div>
                    @endif
                </div>

                {{-- Right: Add reminder form --}}
                <div>
                    <div class="lsp-card">
                        <div class="lsp-card-head">
                            <div class="lsp-card-title">➕ Set Reminder</div>
                        </div>
                        <div class="lsp-card-body">
                            <!-- FORM START -->
<form method="POST" action="{{ route('leads.reminders.store', $lead) }}">
@csrf

<div id="step-form">

    <!-- STEP 1 -->
    <div class="lsp-group step">
        <label class="lsp-label">Title <span class="lsp-req">*</span></label>
        <div class="lsp-fw">
            <input type="text" name="title" class="lsp-inp" required>
        </div>
    </div>

    <!-- STEP 2 -->
    <div class="lsp-form-row lsp-form-row-2 step">

        <div class="lsp-group">
            <label class="lsp-label">Remind Date <span class="lsp-req">*</span></label>
            <div class="lsp-fw">
                <input type="date" name="remind_at" class="lsp-inp" required>
            </div>
        </div>

        <div class="lsp-group">
            <label class="lsp-label">Remind Time <span class="lsp-req">*</span></label>
            <div class="lsp-fw">
                <input type="time" name="remainder_time" class="lsp-inp" required>
            </div>
        </div>



    </div>
    <div class="lsp-group step">
     <div class="lsp-group">
            <label class="lsp-label">Type <span class="lsp-req">*</span></label>
            <div class="lsp-fw">
                <select name="type" class="lsp-sel" required>
                    @foreach(\App\Models\LeadReminder::TYPES as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- STEP 3 -->
    <div class="lsp-group step">
        <label class="lsp-label">Priority <span class="lsp-req">*</span></label>
        <div style="display:flex;gap:8px;">
            @foreach(['low'=>['🟢','Low'],'medium'=>['🟡','Medium'],'high'=>['🔴','High']] as $pk => $pv)
            <label class="rem-pri-opt">
                <input type="radio" name="priority" value="{{ $pk }}" {{ $pk==='medium'?'checked':'' }}>
                {{ $pv[0] }} {{ $pv[1] }}
            </label>
            @endforeach
        </div>
    </div>

    <!-- STEP 4 -->
    <div class="lsp-group step">
        <label class="lsp-label">Description <span class="lsp-req">*</span></label>
        <textarea name="description" class="lsp-ta" required></textarea>
    </div>

    <!-- BUTTONS -->
    <div id="step-buttons" style="margin-top:15px;display:flex;gap:10px;">
        <button type="button" id="prevBtn" class="lsp-btn">Back</button>
        <button type="button" id="nextBtn" class="lsp-btn lsp-btn-primary">Next</button>
        <button type="submit" id="submitBtn" class="lsp-btn lsp-btn-primary">Submit</button>
    </div>

</div>
</form>
<!-- FORM END -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════
             TAB 4 — PRODUCTS & PAYMENTS
        ════════════════════════════════════════ --}}
       <div class="lsp-panel" id="panel-products">
         @include('pages.leads.partials._products_panel')
        </div>

        <div class="lsp-panel" id="panel-production-updates">
            <div class="lsp-card">
                <div class="lsp-card-head">
                    <div class="lsp-card-title">Production Updates</div>
                </div>
                <div class="lsp-card-body">
                    <div class="lsp-prod-update-shell">


                        <div class="lsp-prod-update-filters">
                            <div class="lsp-prod-update-filter">
                                <label class="lsp-prod-update-label" for="pu-product-filter">Product Wise</label>
                                <select id="pu-product-filter" class="lsp-prod-update-input" onchange="filterProductionUpdates()">
                                    <option value="">All Products</option>
                                    @foreach($productionUpdateProductOptions as $productName)
                                        <option value="{{ $productName }}">{{ $productName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lsp-prod-update-filter">
                                <label class="lsp-prod-update-label" for="pu-type-filter">Type Wise</label>
                                <select id="pu-type-filter" class="lsp-prod-update-input" onchange="filterProductionUpdates()">
                                    <option value="">All Types</option>
                                    @foreach($productionUpdateTypeMeta as $typeKey => $typeMeta)
                                        <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lsp-prod-update-filter">
                                <label class="lsp-prod-update-label" for="pu-date-filter">Date Wise</label>
                                <input type="date" id="pu-date-filter" class="lsp-prod-update-input" onchange="filterProductionUpdates()">
                            </div>
                            <div class="lsp-prod-update-filter" style="justify-content:flex-end;">
                                <label class="lsp-prod-update-label" style="visibility:hidden;">Reset</label>
                                <button type="button" class="lsp-btn lsp-btn-outline" onclick="resetProductionUpdateFilters()">Reset Filter</button>
                            </div>
                        </div>

                        @if($productionUpdates->isNotEmpty())
                            <div class="lsp-prod-update-list" id="production-update-list">
                                @foreach($productionUpdates as $update)
                                    @php
                                        $typeMeta = $productionUpdateTypeMeta[$update->type] ?? ['label' => ucwords(str_replace('_', ' ', (string) $update->type)), 'title' => 'Project Update', 'bg' => '#f3f4f6', 'border' => '#d1d5db', 'text' => '#374151'];
                                    @endphp
                                    <article
                                        class="lsp-prod-update-item"
                                        data-update-item
                                        data-product="{{ strtolower($update->lead_product_name ?? '') }}"
                                        data-type="{{ strtolower($update->type ?? '') }}"
                                        data-date="{{ optional($update->created_at)->format('Y-m-d') }}"
                                    >
                                        <div class="lsp-prod-update-head">
                                            <div>
                                                <span class="lsp-prod-update-type" style="background:{{ $typeMeta['bg'] }};border-color:{{ $typeMeta['border'] }};color:{{ $typeMeta['text'] }};">
                                                    {{ $typeMeta['label'] }}
                                                </span>
                                                <div class="lsp-prod-update-title">{{ $typeMeta['title'] }}</div>
                                                <div class="lsp-prod-update-product">{{ $update->lead_product_name ?: 'Product' }}</div>
                                            </div>
                                            <div class="lsp-prod-update-meta">
                                                Added By: {{ $update->createdBy?->name ?: 'Unknown user' }}<br>
                                                Added On: {{ optional($update->created_at)->format('d M Y h:i A') }}
                                            </div>
                                        </div>
                                        <div class="lsp-prod-update-content">{!! $update->content !!}</div>
                                    </article>
                                @endforeach
                            </div>
                            <div class="lsp-prod-update-empty" id="production-update-empty" style="display:none;">
                                No production updates found for the selected filters.
                            </div>
                        @else
                            <div class="lsp-prod-update-empty">
                                No production updates have been added for this lead yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════
             TAB 5 — QUOTATIONS
        ════════════════════════════════════════ --}}
        <div class="lsp-panel" id="panel-approval-history">
            <div class="lsp-card">
                <div class="lsp-card-head">
                    <div class="lsp-card-title">Approval History</div>
                </div>
                <div class="lsp-card-body">
                    @if($approvalHistoryProducts->isNotEmpty())
                        <div class="lsp-ah-stack">
                            @foreach($approvalHistoryProducts as $product)
                                @php
                                    $history = $product->latestProductionInitiation;
                                    $teamCount = collect(\Illuminate\Support\Arr::wrap($history?->project_allocated_employee_user_ids))->filter()->count();
                                    $currentApprovalState = $history?->employee_allocation_status ?: $history?->project_allocation_status ?: $history?->production_approval_status ?: $history?->status ?: 'pending';
                                @endphp
                                <div class="lsp-ah-accordion">
                                    <div class="lsp-ah-head" onclick="toggleApprovalHistory(this)">
                                        <div class="lsp-ah-head-left">
                                            <div class="lsp-ah-product">{{ $product->product_name }}</div>
                                            <div class="lsp-ah-sub">
                                                {{ $history?->department?->name ?: 'Production initiation available' }}
                                                @if($history?->created_at)
                                                    | Initiated {{ optional($history->created_at)->format('d M Y h:i A') }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="lsp-ah-head-right">
                                            <span class="lsp-ah-status">{{ ucwords(str_replace('_', ' ', (string) $currentApprovalState)) }}</span>
                                            <span class="lsp-ah-chevron">
                                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="lsp-ah-body">
                                        <div class="lsp-ah-timeline">
                                            <div class="lsp-ah-item" style="--timeline-color:#2563eb;">
                                                <div class="lsp-ah-title">Project Initiated</div>
                                                <div class="lsp-ah-subline">Initial project request creation</div>
                                                <div class="lsp-ah-meta">
                                                    Initiated By: {{ $history?->initiatedBy?->name ?: 'Not available' }}<br>
                                                    Initiated On: {{ optional($history?->created_at)->format('d M Y h:i A') ?: 'Not available' }}
                                                </div>
                                            </div>
                                            <div class="lsp-ah-item" style="--timeline-color:#0f766e;">
                                                <div class="lsp-ah-title">OVP Approval</div>
                                                <div class="lsp-ah-subline">OVP review and confirmation</div>
                                                <div class="lsp-ah-meta">
                                                    OVP Approved By: {{ $history?->reviewedBy?->name ?: 'Not available' }}<br>
                                                    OVP Approved On: {{ optional($history?->reviewed_at)->format('d M Y h:i A') ?: 'Not available' }}
                                                </div>
                                            </div>
                                            <div class="lsp-ah-item" style="--timeline-color:#7c3aed;">
                                                <div class="lsp-ah-title">Project Approval</div>
                                                <div class="lsp-ah-subline">Production approval stage action</div>
                                                <div class="lsp-ah-meta">
                                                    Approved By: {{ $history?->productionApprovalReviewedBy?->name ?: 'Not available' }}<br>
                                                    Approved On: {{ optional($history?->production_approval_reviewed_at)->format('d M Y h:i A') ?: 'Not available' }}
                                                </div>
                                            </div>
                                            <div class="lsp-ah-item" style="--timeline-color:#166534;">
                                                <div class="lsp-ah-title">TL Allocation</div>
                                                <div class="lsp-ah-subline">Latest TL allocation ownership status</div>
                                                <div class="lsp-ah-meta">
                                                    Current Status: {{ ucwords(str_replace('_', ' ', (string) ($history?->project_allocation_status ?: 'pending'))) }}<br>
                                                    Allocated By: {{ $history?->projectAllocatedBy?->name ?: 'Pending' }}<br>
                                                    Allocated On: {{ optional($history?->project_allocated_at)->format('d M Y h:i A') ?: 'Pending' }}
                                                </div>
                                            </div>
                                            <div class="lsp-ah-item" style="--timeline-color:#ea580c;">
                                                <div class="lsp-ah-title">Employee Allocation</div>
                                                <div class="lsp-ah-subline">Mapped employee assignment for execution</div>
                                                <div class="lsp-ah-meta">
                                                    Employee Allocation Status: {{ ucwords(str_replace('_', ' ', (string) ($history?->employee_allocation_status ?: 'pending'))) }}<br>
                                                    Allocated By: {{ $history?->employeeAllocatedBy?->name ?: 'Pending' }}<br>
                                                    Allocated On: {{ optional($history?->employee_allocated_at)->format('d M Y h:i A') ?: 'Pending' }}<br>
                                                    Team Members: {{ $teamCount ? $teamCount . ' member(s) allocated' : 'Pending' }}
                                                </div>
                                            </div>
                                            @if($history)
                                                @foreach($history->projectUpdates()->where('type', 'schedule_history')->orderBy('created_at')->get() as $historyUpdate)
                                                    <div class="lsp-ah-item" style="--timeline-color:#b91c1c;">
                                                        <div class="lsp-ah-title">Schedule & Status Changed</div>
                                                        <div class="lsp-ah-subline">Project delivery date and status update history</div>
                                                        <div class="lsp-ah-meta">
                                                            Changed By: {{ $historyUpdate->createdBy?->name ?: 'System' }}<br>
                                                            Changed On: {{ optional($historyUpdate->created_at)->format('d M Y h:i A') }}<br>
                                                            <div style="margin-top: 8px; padding: 10px; background: #fff5f5; border: 1px solid #fecaca; border-radius: 8px; font-size: 13px; color: #7f1d1d; line-height: 1.5; font-family: sans-serif;">
                                                                {!! $historyUpdate->content !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="lsp-ah-empty">
                            No product approval history is available yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lsp-panel" id="panel-quotations">

            <div class="page-header">
        <div class="page-title"></div>
        <a href="/quotations/create/{{ $lead->id }}" class="btn-primary">
            <i class="bi bi-plus-lg"></i> New Quotation
        </a>
    </div>

            {{-- Create new quotation form --}}
            <div class="lsp-qt-form-wrap">

                <div class="card">
        <div class="table-responsive">

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Quotation No</th>
                        <th>Lead</th>
                        <th>Date</th>
                        <th>Valid Until</th>
                        <th>Total Amount</th>
                        {{--  <th>Status</th>  --}}
                        {{--  <th>Approved By</th>  --}}
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $i=1;
                    @endphp
                    @forelse($lead->quotations as $q)
                    <tr>
                        {{--  <td>{{ $loop->iteration + ($quotations->firstItem() - 1) }}</td>  --}}
                        <td>{{ $i++ }}</td>
                        <td><strong>{{ $q->quotation_no }}</strong></td>
                        <td>{{ $q->lead?->contact_name ?? '—' }}<br>
                            <small style="color:#9e9e9e">{{ $q->lead->company_name ?? '' }}</small>
                        </td>
                        <td>{{ $q->quotation_date->format('d M Y') }}</td>
                        <td>{{ $q->valid_until->format('d M Y') }}</td>
                        <td><strong>₹{{ number_format($q->total_amount, 2) }}</strong></td>
                        {{--  <td>
                            @if($q->is_approved)
                                <span class="badge badge-approved"><i class="bi bi-check-circle-fill"></i> Approved</span>
                            @else
                                <span class="badge badge-pending"><i class="bi bi-clock-fill"></i> Pending</span>
                            @endif
                        </td>
                        <td>{{ $q->approver->name ?? '—' }}</td>  --}}
                        <td>
                            <div class="action-btns">
                                <a href="/quotations/{{ $q->id }}/pdf" class="btn-outline-sm">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                @if(!$q->is_approved)
                                <form method="POST" action="{{ route('quotations.approve', $q) }}" style="display:inline">
                                    @csrf @method('PATCH')
                                    <button class="btn-outline-sm" style="border-color:#4caf50;color:#2e7d32">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('quotations.destroy', $q) }}"
                                      onsubmit="return confirm('Delete this quotation?')" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                No quotations found.
                                <br><br>
                                <a href="/quotations/create/{{ $lead->id }}" class="btn-primary" style="display:inline-flex">
                                    Create First Quotation
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>



    </div>
                {{--  <div class="lsp-qt-form-head" onclick="toggleQtForm()">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create New Quotation
                    <svg id="qtArrow" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-left:auto;transition:transform .2s"><polyline points="6 9 12 15 18 9"/></svg>
                </div>  --}}
                {{--  <div class="lsp-qt-form-body {{ $lead->quotations->isEmpty() ? 'open' : '' }}" id="qtFormBody">
                    <form method="POST" action="{{ route('leads.quotations.store', $lead) }}" id="qtForm">
                        @csrf
                        <div style="display:flex;flex-direction:column;gap:14px;">

                            <div class="lsp-form-row lsp-form-row-3">
                                <div class="lsp-group">
                                    <label class="lsp-label">Quotation Date <span class="lsp-req">*</span></label>
                                    <input type="date" name="quotation_date" class="lsp-inp no-ico" value="{{ today()->toDateString() }}" required>
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Valid Until</label>
                                    <input type="date" name="valid_until" class="lsp-inp no-ico" value="{{ today()->addDays(30)->toDateString() }}">
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Tax %</label>
                                    <input type="number" name="tax_percent" class="lsp-inp no-ico" placeholder="0" min="0" max="100" value="18" oninput="recalcQt()">
                                </div>
                            </div>


                            <div>
                                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:8px;">Line Items</div>
                                <div style="overflow-x:auto;">
                                <table class="lsp-items-table" id="qtItemsTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width:160px;">Product Name *</th>
                                            <th style="min-width:140px;">Description</th>
                                            <th style="min-width:60px;">Qty *</th>
                                            <th style="min-width:60px;">Unit</th>
                                            <th style="min-width:100px;">Unit Price *</th>
                                            <th style="min-width:70px;">Disc %</th>
                                            <th style="min-width:90px;text-align:right;">Total</th>
                                            <th style="width:30px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="qtItemsBody">
                                        <tr class="qt-item-row">
                                            <td><input type="text" name="items[0][product_name]" class="lsp-item-inp" placeholder="Product name" required></td>
                                            <td><input type="text" name="items[0][description]" class="lsp-item-inp" placeholder="Optional"></td>
                                            <td><input type="number" name="items[0][quantity]" class="lsp-item-inp" value="1" min="1" onchange="recalcQt()" style="width:55px;"></td>
                                            <td><input type="text" name="items[0][unit]" class="lsp-item-inp" value="Nos" style="width:55px;"></td>
                                            <td><input type="number" name="items[0][unit_price]" class="lsp-item-inp" placeholder="0.00" step="0.01" min="0" onchange="recalcQt()"></td>
                                            <td><input type="number" name="items[0][discount_percent]" class="lsp-item-inp" value="0" min="0" max="100" onchange="recalcQt()" style="width:65px;"></td>
                                            <td class="lsp-item-total">₹0.00</td>
                                            <td><button type="button" class="lsp-remove-row" onclick="removeQtRow(this)" style="display:none">✕</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                                <button type="button" class="lsp-add-row-btn" onclick="addQtRow()">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Add Line Item
                                </button>


                                <div class="lsp-qt-totals-preview">
                                    <div class="lsp-qt-pr"><span>Subtotal</span><span id="qtSubtotal">₹0.00</span></div>
                                    <div class="lsp-qt-pr"><span>Discount</span><span>-<input type="number" name="discount_amount" id="qtDiscInput" value="0" min="0" style="width:70px;border:1px solid var(--border);border-radius:5px;padding:3px 5px;font-size:12px;font-family:inherit;" onchange="recalcQt()"></span></div>
                                    <div class="lsp-qt-pr"><span>Tax</span><span id="qtTaxAmt">₹0.00</span></div>
                                    <div class="lsp-qt-pr grand"><span>Grand Total</span><span id="qtGrandTotal">₹0.00</span></div>
                                </div>
                            </div>

                            <div class="lsp-form-row lsp-form-row-2">
                                <div class="lsp-group">
                                    <label class="lsp-label">Terms & Conditions</label>
                                    <textarea name="terms_conditions" class="lsp-ta" rows="3" placeholder="Payment terms, delivery, etc."></textarea>
                                </div>
                                <div class="lsp-group">
                                    <label class="lsp-label">Internal Notes</label>
                                    <textarea name="notes" class="lsp-ta" rows="3" placeholder="Notes visible to team only…"></textarea>
                                </div>
                            </div>

                            <button type="submit" class="lsp-btn lsp-btn-primary" style="justify-content:center;width:100%;">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Create Quotation
                            </button>
                        </div>
                    </form>
                </div>  --}}
            </div>
        </div>

    </div>{{-- /lsp-body --}}
</div>{{-- /lsp --}}



@endsection

@push('scripts')
<script>
// ── Tab switching ─────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.lsp-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.lsp-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-'+name).classList.add('active');
}

function toggleApprovalHistory(trigger) {
    const card = trigger.closest('.lsp-ah-accordion');
    if (!card) return;
    card.classList.toggle('open');
}

function filterProductionUpdates() {
    const product = (document.getElementById('pu-product-filter')?.value || '').toLowerCase();
    const type = (document.getElementById('pu-type-filter')?.value || '').toLowerCase();
    const date = document.getElementById('pu-date-filter')?.value || '';
    const items = document.querySelectorAll('[data-update-item]');
    let visibleCount = 0;

    items.forEach(item => {
        const matchesProduct = !product || item.dataset.product === product;
        const matchesType = !type || item.dataset.type === type;
        const matchesDate = !date || item.dataset.date === date;
        const visible = matchesProduct && matchesType && matchesDate;

        item.style.display = visible ? '' : 'none';

        if (visible) {
            visibleCount += 1;
        }
    });

    const empty = document.getElementById('production-update-empty');
    if (empty) empty.style.display = visibleCount ? 'none' : '';
}

function resetProductionUpdateFilters() {
    const product = document.getElementById('pu-product-filter');
    const type = document.getElementById('pu-type-filter');
    const date = document.getElementById('pu-date-filter');

    if (product) product.value = '';
    if (type) type.value = '';
    if (date) date.value = '';

    filterProductionUpdates();
}

// ── Toggle quotation form ─────────────────────────────────────────
function toggleQtForm() {
    const body  = document.getElementById('qtFormBody');
    const arrow = document.getElementById('qtArrow');
    const open  = body.classList.toggle('open');
    arrow.style.transform = open ? 'rotate(180deg)' : '';
}

// ── Quotation line items ──────────────────────────────────────────
let qtRowIndex = 1;

function addQtRow() {
    const body = document.getElementById('qtItemsBody');
    const idx  = qtRowIndex++;
    const tr   = document.createElement('tr');
    tr.className = 'qt-item-row';
    tr.innerHTML = `
        <td><input type="text" name="items[${idx}][product_name]" class="lsp-item-inp" placeholder="Product name" required></td>
        <td><input type="text" name="items[${idx}][description]" class="lsp-item-inp" placeholder="Optional"></td>
        <td><input type="number" name="items[${idx}][quantity]" class="lsp-item-inp" value="1" min="1" onchange="recalcQt()" style="width:55px;"></td>
        <td><input type="text" name="items[${idx}][unit]" class="lsp-item-inp" value="Nos" style="width:55px;"></td>
        <td><input type="number" name="items[${idx}][unit_price]" class="lsp-item-inp" placeholder="0.00" step="0.01" min="0" onchange="recalcQt()"></td>
        <td><input type="number" name="items[${idx}][discount_percent]" class="lsp-item-inp" value="0" min="0" max="100" onchange="recalcQt()" style="width:65px;"></td>
        <td class="lsp-item-total">₹0.00</td>
        <td><button type="button" class="lsp-remove-row" onclick="removeQtRow(this)">✕</button></td>
    `;
    body.appendChild(tr);
    updateRemoveBtns();
}

function removeQtRow(btn) {
    btn.closest('tr').remove();
    updateRemoveBtns();
    recalcQt();
}

function updateRemoveBtns() {
    const rows = document.querySelectorAll('.qt-item-row');
    rows.forEach((r, i) => {
        const btn = r.querySelector('.lsp-remove-row');
        if (btn) btn.style.display = rows.length > 1 ? '' : 'none';
    });
}

function recalcQt() {
    let subtotal = 0;
    document.querySelectorAll('.qt-item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('[name*="[quantity]"]')?.value) || 0;
        const price = parseFloat(row.querySelector('[name*="[unit_price]"]')?.value) || 0;
        const disc  = parseFloat(row.querySelector('[name*="[discount_percent]"]')?.value) || 0;
        const gross = qty * price;
        const total = gross - (gross * disc / 100);
        const totEl = row.querySelector('.lsp-item-total');
        if (totEl) totEl.textContent = '₹' + total.toFixed(2);
        subtotal += total;
    });

    const discAmt  = parseFloat(document.getElementById('qtDiscInput')?.value) || 0;
    const taxPct   = parseFloat(document.querySelector('[name="tax_percent"]')?.value) || 0;
    const taxable  = subtotal - discAmt;
    const taxAmt   = taxable * (taxPct / 100);
    const grand    = taxable + taxAmt;

    const fmt = v => '₹' + Math.max(0,v).toFixed(2);
    document.getElementById('qtSubtotal').textContent  = fmt(subtotal);
    document.getElementById('qtTaxAmt').textContent    = fmt(taxAmt);
    document.getElementById('qtGrandTotal').textContent= fmt(grand);
}

// ── Product total live calc ────────────────────────────────────────
function calcTotal() {
    const price = parseFloat(document.querySelector('[name="unit_price"]')?.value) || 0;
    const qty   = parseFloat(document.querySelector('[name="quantity"]')?.value) || 1;
    const disc  = parseFloat(document.querySelector('[name="discount_percent"]')?.value) || 0;
    const gross = price * qty;
    const total = gross - (gross * disc / 100);
    const el    = document.getElementById('prodTotal');
    if (el) el.textContent = '₹' + total.toFixed(2);
}

// ── Reminder priority selector ─────────────────────────────────────
document.querySelectorAll('.rem-pri-opt').forEach(el => {
    el.addEventListener('click', () => {
        const val = el.dataset.val;
        const colors = { low:['#16a34a','#f0fdf4','#bbf7d0'], medium:['#b45309','#fffbeb','#fde68a'], high:['#dc2626','#fef2f2','#fecaca'] };
        document.querySelectorAll('.rem-pri-opt').forEach(o => {
            o.style.borderColor = 'var(--border)';
            o.style.background  = '';
        });
        const c = colors[val];
        if (c) { el.style.borderColor = c[2]; el.style.background = c[1]; }
        el.querySelector('input').checked = true;
    });
});

// Init medium selected
const medOpt = document.querySelector('.rem-pri-opt[data-val="medium"]');
if (medOpt) { medOpt.style.borderColor = '#fde68a'; medOpt.style.background = '#fffbeb'; }


function summarizeNote() {
            const noteText = $('#addNoteText').val().trim();


     $('#summaryPreview').hide();
            $('#summarizeError').addClass('d-none');

            if (!noteText || noteText.length < 30) {
                showSummarizeError('Please enter at least 30 characters to summarize.');
                return;
            }

            $('#summarizeSpinner').show();
            $('#summarizeBtn').prop('disabled', true);


            $.ajax({
                url: '{{ route("ai.summarize") }}',
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
                data: JSON.stringify({ text: noteText }),

                success: function (response) {
                    if (response.summary) {
                        $('#originalTextPreview').text(noteText);
                        $('#summarizedTextPreview').text(response.summary);
                        $('#summaryPreview').show();
                    } else {
                        showSummarizeError('No summary returned. Please try again.');
                    }
                },

                error: function (xhr) {
                    const json = xhr.responseJSON;
                    const status = xhr.status;

                    let msg;
                    if (status === 503) {
                        msg = 'AI model is warming up. Please try again in 20 seconds.';
                    } else if (status === 401) {
                        msg = 'API key error. Please contact your administrator.';
                    } else {
                        msg = json?.error ?? 'Failed to summarize. Please try again.';
                    }

                    showSummarizeError(msg);
                },

                complete: function () {
                    $('#summarizeSpinner').hide();
                    $('#summarizeBtn').prop('disabled', false);
                }
            });
        }

        function acceptSummary() {
            $('#addNoteText').val($('#summarizedTextPreview').text());
            $('#summaryPreview').hide();
        }

        function rejectSummary() {
            $('#summaryPreview').hide();
        }

        function showSummarizeError(msg) {
            $('#summarizeErrorMsg').text(msg);
            $('#summarizeError').removeClass('d-none');
        }

        // Reset modal state on close
        $('#addNoteModal').on('hidden.bs.modal', function () {
            $('#summaryPreview').hide();
            $('#summarizeError').addClass('d-none');
            $('#summarizeSpinner').hide();
        });

        window.summarizeNote = summarizeNote;
        window.acceptSummary = acceptSummary;
        window.rejectSummary = rejectSummary;


</script>

<script>
$(document).ready(function() {

    $('#outcome_category').change(function() {

        let category_id = $(this).val();

        $('#outcome_sub_category').html('<option>Loading...</option>');

        if(category_id != '') {

            $.ajax({
                url: '/get-subcategories/' + category_id,
                type: 'GET',
                success: function(response) {

                    let options = '<option value="">— Select sub category —</option>';

                    response.forEach(function(item) {
                        options += `<option value="${item.id}">${item.name}</option>`;
                    });

                    $('#outcome_sub_category').html(options);
                }
            });

        } else {
            $('#outcome_sub_category').html('<option value="">— Select sub category —</option>');
        }

    });

});
</script>

<script>
$(document).ready(function(){

    let currentStep = 0;
    let steps = $('.step');

    function showStep(index) {
        steps.removeClass('active').hide();
        $(steps[index]).addClass('active').show();

        // Button control
        $('#prevBtn').toggle(index > 0);
        $('#nextBtn').toggle(index < steps.length - 1);
        $('#submitBtn').toggle(index === steps.length - 1);
    }

    showStep(currentStep);

    // NEXT
    $('#nextBtn').click(function(){

        let currentInputs = $(steps[currentStep]).find('input, select, textarea');

        let valid = true;

        currentInputs.each(function(){
            if ($(this).prop('required') && !$(this).val()) {
                valid = false;
            }
        });

        if (!valid) {
            alert('Please fill all required fields');
            return;
        }

        currentStep++;
        showStep(currentStep);
    });

    // BACK
    $('#prevBtn').click(function(){
        currentStep--;
        showStep(currentStep);
    });

});

$(document).ready(function() {
    // 1. Handle standard form submissions (Call Updates, Reminders, etc.)
    $('form').on('submit', function() {
        var $form = $(this);
        
        // Skip if browser validity check fails
        if (this.checkValidity && !this.checkValidity()) {
            return;
        }

        // Find the submit button(s)
        var $btn = $form.find('button[type="submit"], input[type="submit"]');
        if ($btn.length) {
            // Disable button to prevent double submit
            $btn.prop('disabled', true);
            $btn.css({
                'opacity': '0.7',
                'cursor': 'not-allowed'
            });
            // Show loading text/spinner
            $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="width: 12px; height: 12px; border-width: 2px; display: inline-block;"></span> Loading...');
        }
    });

    // 2. Prevent double clicks on buttons generally
    $(document).on('click', 'button, .lsp-btn, .ppf-btn, .pp-act-btn', function(e) {
        var $btn = $(this);
        
        // Skip if already disabled
        if ($btn.prop('disabled') || $btn.hasClass('disabled') || $btn.hasClass('clicked-disabled')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        
        // Temporary disable pointer events to prevent rapid multiple clicks
        $btn.addClass('clicked-disabled');
        setTimeout(function() {
            $btn.removeClass('clicked-disabled');
        }, 1500);
    });

    // Dynamic style injection for disabled clicks
    $('<style>')
        .prop('type', 'text/css')
        .html('.clicked-disabled { pointer-events: none !important; opacity: 0.8; }')
        .appendTo('head');
});
</script>

@endpush
