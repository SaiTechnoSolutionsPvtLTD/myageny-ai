@extends('layouts.app')

@section('title', 'Pre Sales Leads Workspace')

@push('styles')
<style>
/* Page Layout */
.ps-page { display:flex; flex-direction:column; min-height:100vh; background:#f8fafc; color:#0f172a; font-family: inherit; }

/* Hero Topbar */
.ps-topbar {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-bottom: 1px solid #334155;
    padding: 24px 32px;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
}
.ps-topbar::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(249, 115, 22, 0.15) 0%, transparent 70%);
    pointer-events: none;
}
.ps-title { font-size: 22px; font-weight: 800; color: #ffffff; margin: 0 0 4px; letter-spacing: -0.02em; display:flex; align-items:center; gap:10px; }
.ps-crumb { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

.ps-topbar-pill {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    color: #fdba74;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.ps-body { padding: 28px 32px 48px; display: flex; flex-direction: column; gap: 24px; }

/* Stats Bar */
.ps-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.ps-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
}
.ps-stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 3.5px;
    background: var(--card-accent, #ea580c);
}
.ps-stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07); }
.ps-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.ps-stat-val { font-size: 24px; font-weight: 900; color: #0f172a; line-height: 1.1; margin-top: 2px; }
.ps-stat-lbl { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }

/* Alert */
.ps-alert { padding: 14px 20px; border-radius: 14px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
.ps-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.ps-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

/* Filter & Bulk Bar */
.ps-action-bar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
}
.ps-bulk-controls { display: flex; align-items: center; gap: 12px; }

.ps-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
    text-decoration: none;
}
.ps-btn-primary {
    background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
    color: #ffffff;
    box-shadow: 0 4px 16px rgba(234, 88, 12, 0.3);
}
.ps-btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
}
.ps-btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.ps-btn-secondary:hover { background: #e2e8f0; color: #0f172a; }
.ps-btn:disabled { opacity: 0.55; pointer-events: none; cursor: not-allowed; box-shadow: none; }

.ps-filters { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.ps-search-wrap { position: relative; display: flex; align-items: center; }
.ps-search-ico { position: absolute; left: 12px; color: #94a3b8; font-size: 14px; pointer-events: none; }
.ps-input-search {
    padding-left: 36px !important;
    min-width: 260px;
}
.ps-input {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 9px 14px;
    font-size: 13px;
    color: #0f172a;
    font-family: inherit;
    transition: all 0.2s ease;
}
.ps-input:focus { outline: none; border-color: #ea580c; background: #ffffff; box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12); }

/* Table Card */
.ps-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.04);
}
.ps-tbl { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
.ps-tbl th {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 18px;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.06em;
}
.ps-tbl td { border-bottom: 1px solid #f1f5f9; padding: 16px 18px; vertical-align: middle; color: #334155; }
.ps-tbl tbody tr { transition: background 0.15s ease; }
.ps-tbl tbody tr:hover { background: #faf5f0; }

/* Badges & Cell Components */
.ps-lead-id {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 8px;
    background: #fff7ed;
    color: #c2410c;
    font-weight: 800;
    font-size: 12px;
    border: 1px solid #ffedd5;
    text-decoration: none;
}
.ps-lead-id:hover { background: #ffedd5; color: #9a3412; }

.ps-co-avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 900;
    color: #ffffff;
    flex-shrink: 0;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.3);
}

.ps-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.ps-badge-branch { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.ps-badge-owner { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
.ps-badge-unassigned { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.ps-badge-pre { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

.ps-move-btn {
    background: #ffffff;
    border: 1px solid #ea580c;
    color: #ea580c;
    padding: 7px 14px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.ps-move-btn:hover {
    background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
}

/* Modal */
.ps-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    justify-content: center;
    align-items: center;
}
.ps-modal-overlay.is-open { display: flex; animation: fadeInOverlay 0.25s ease; }
@keyframes fadeInOverlay { from { opacity: 0; } to { opacity: 1; } }

.ps-modal-card {
    background: #ffffff;
    border-radius: 24px;
    width: min(100%, 520px);
    padding: 32px;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
    animation: scaleInCard 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}
@keyframes scaleInCard { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.ps-modal-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 5px;
    background: linear-gradient(90deg, #ea580c 0%, #f97316 100%);
}

.ps-modal-title { font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 6px; display: flex; align-items: center; gap: 10px; }
.ps-modal-sub { font-size: 13px; color: #64748b; margin: 0 0 20px; line-height: 1.5; }
.ps-modal-field { margin-bottom: 20px; }
.ps-modal-label { display: block; font-size: 11px; font-weight: 800; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
.ps-modal-select {
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1.5px solid #cbd5e1;
    font-size: 14px;
    background: #f8fafc;
    font-family: inherit;
    color: #0f172a;
    font-weight: 600;
}
.ps-modal-select:focus { border-color: #ea580c; background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.12); }
.ps-modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; }
</style>
@endpush

@section('content')
<div class="ps-page">
    {{-- Hero Topbar --}}
    <div class="ps-topbar">
        <div>
            <div class="ps-crumb">Sales Management &rsaquo; Pre Sales Workspace</div>
            <h1 class="ps-title">
                <i class="bi bi-headset" style="color: #f97316;"></i>
                Pre Sales Leads
            </h1>
        </div>
        <div>
            <div class="ps-topbar-pill">
                <i class="bi bi-lightning-charge-fill" style="color:#f97316;"></i>
                <span>Workspace Leads: {{ $totalCount }}</span>
            </div>
        </div>
    </div>

    <div class="ps-body">
        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="ps-alert ps-alert-success">
            <i class="bi bi-check-circle-fill" style="font-size:18px;"></i>
            <div>{!! session('success') !!}</div>
        </div>
        @endif
        @if(session('error'))
        <div class="ps-alert ps-alert-error">
            <i class="bi bi-exclamation-triangle-fill" style="font-size:18px;"></i>
            <div>{!! session('error') !!}</div>
        </div>
        @endif

        {{-- Action Bar & Filters --}}
        <form method="GET" action="{{ route('pre-sales.index') }}" id="psFilterForm">
            <div class="ps-action-bar" style="justify-content: flex-end;">
                <div class="ps-filters">
                    <div class="ps-search-wrap">
                        <i class="bi bi-search ps-search-ico"></i>
                        <input type="text" name="search" class="ps-input ps-input-search" placeholder="Search company, contact, phone..." value="{{ request('search') }}" onchange="this.form.submit()">
                    </div>
                    @if(request()->filled('search'))
                    <a href="{{ route('pre-sales.index') }}" class="ps-btn ps-btn-secondary" style="padding:8px 14px; font-size:12px;">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Leads Table Card --}}
        <div class="ps-card">
            @if($leads->isEmpty())
            <div style="text-align:center; padding:64px 24px; color:#94a3b8;">
                <i class="bi bi-clipboard2-x" style="font-size:48px; display:block; margin-bottom:14px; color:#cbd5e1;"></i>
                <h4 style="font-size:17px; font-weight:800; color:#334155; margin:0 0 6px;">No Pre-Sales Leads Found</h4>
                <p style="font-size:13px; margin:0; color:#64748b;">There are currently no leads allocated to you in Pre-Sales matching the selected criteria.</p>
            </div>
            @else
            <div style="overflow-x:auto;">
                <table class="ps-tbl">
                    <thead>
                        <tr>
                            <th>Lead Ref</th>
                            <th>Company &amp; Contact</th>
                            <th>Contact Info</th>
                            <th>Assigned Sales Person</th>
                            <th>Pre-Sales Executive</th>
                            <th>Date</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $avatarColors = ['#ea580c', '#2563eb', '#059669', '#7c3aed', '#be123c', '#0284c7', '#d97706'];
                        @endphp
                        @foreach($leads as $lead)
                        @php
                            $coColor = $avatarColors[$lead->id % count($avatarColors)];
                        @endphp
                        <tr style="cursor:pointer;" onclick="window.location='{{ route('leads.show', $lead) }}'">
                            <td>
                                <a href="{{ route('leads.show', $lead) }}" class="ps-lead-id" onclick="event.stopPropagation()">
                                    LD-{{ str_pad($lead->id, 4, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="ps-co-avatar" style="background:{{ $coColor }}">
                                        {{ strtoupper(substr($lead->company_name ?: 'LD', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:800; color:#0f172a; font-size:14px; line-height:1.3;">{{ $lead->company_name }}</div>
                                        <div style="font-size:12px; color:#64748b; font-weight:600; margin-top:2px;">{{ $lead->contact_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:#1e293b; font-size:13px;">
                                    <i class="bi bi-telephone-fill" style="font-size:11px; margin-right:4px; color:#ea580c;"></i>{{ $lead->mobile_number }}
                                </div>
                                @if($lead->email)
                                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                    <i class="bi bi-envelope-fill" style="font-size:11px; margin-right:4px; color:#64748b;"></i>{{ $lead->email }}
                                </div>
                                @endif
                            </td>
                            <td>
                                @if($lead->assignedTo)
                                <span class="ps-badge ps-badge-owner">
                                    <i class="bi bi-person-fill"></i> {{ $lead->assignedTo->name }}
                                </span>
                                @else
                                <span class="ps-badge ps-badge-unassigned">
                                    <i class="bi bi-exclamation-circle"></i> Unassigned
                                </span>
                                @endif
                            </td>
                            <td>
                                @if($lead->preSaleExecutive)
                                <span class="ps-badge ps-badge-pre">
                                    <i class="bi bi-headset"></i> {{ $lead->preSaleExecutive->name }}
                                </span>
                                @else
                                <span style="color:#94a3b8; font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="color:#64748b; font-size:12px; font-weight:600;">
                                {{ $lead->lead_date ? $lead->lead_date->format('d M Y') : '—' }}
                            </td>
                            <td style="text-align:right;" onclick="event.stopPropagation()">
                                <a href="{{ route('leads.show', $lead) }}" class="ps-btn ps-btn-secondary" style="padding:6px 10px; font-size:12px; border-radius:10px;" title="View Lead Details" onclick="event.stopPropagation()">
                                    <i class="bi bi-eye-fill" style="color:#2563eb; font-size:14px;"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($leads->hasPages())
                @include('partials.table-pagination', ['paginator' => $leads])
            @endif
            @endif
        </div>
    </div>
</div>

{{-- Allocation Modal --}}
<div class="ps-modal-overlay" id="allocateModal">
    <div class="ps-modal-card">
        <form method="POST" action="{{ route('pre-sales.allocate') }}" id="allocateForm">
            @csrf
            <h3 class="ps-modal-title">
                <i class="bi bi-person-up" style="color:#ea580c;"></i>
                Move / Allocate Lead(s)
            </h3>
            <p class="ps-modal-sub">Select the Sales Executive to whom you want to assign these lead(s).</p>

            <div id="selectedLeadsContainer"></div>

            <div class="ps-modal-field">
                <label class="ps-modal-label" for="sales_person_id">Target Sales Person (Sales Executive)</label>
                <select name="sales_person_id" id="sales_person_id" class="ps-modal-select" required>
                    <option value="">-- Choose Sales Person --</option>
                    @foreach($salesExecutives as $se)
                    <option value="{{ $se->id }}">{{ $se->name }} ({{ $se->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="ps-modal-actions">
                <button type="button" class="ps-btn ps-btn-secondary" onclick="closeAllocateModal()">Cancel</button>
                <button type="submit" class="ps-btn ps-btn-primary">
                    <i class="bi bi-check2-circle"></i>
                    Confirm &amp; Move Lead(s)
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllCheckbox');
    const leadCheckboxes = document.querySelectorAll('.lead-checkbox');
    const btnBulk = document.getElementById('btnBulkAllocate');
    const selectedCountText = document.getElementById('selectedCountText');
    const modal = document.getElementById('allocateModal');
    const container = document.getElementById('selectedLeadsContainer');

    function updateSelection() {
        let selected = Array.from(leadCheckboxes).filter(cb => cb.checked);
        if (selectedCountText) selectedCountText.innerText = selected.length;
        if (btnBulk) btnBulk.disabled = selected.length === 0;
        if (selectAll) selectAll.checked = selected.length > 0 && selected.length === leadCheckboxes.length;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            leadCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateSelection();
        });
    }

    leadCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });

    window.openAllocateModal = function () {
        let selected = Array.from(leadCheckboxes).filter(cb => cb.checked);
        if (selected.length === 0) return;

        container.innerHTML = '';
        let listHtml = '<div style="background:#fff7ed; border:1px solid #ffedd5; border-radius:12px; padding:12px 16px; margin-bottom:18px; font-size:12px; color:#c2410c; max-height:130px; overflow-y:auto;">';
        listHtml += '<strong style="display:block; margin-bottom:6px; font-size:13px;">Selected Leads (' + selected.length + '):</strong><ul style="margin:0 0 0 16px; padding:0;">';
        
        selected.forEach(cb => {
            listHtml += '<li>' + escapeHtml(cb.dataset.name) + '</li>';
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'lead_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        listHtml += '</ul></div>';
        container.innerHTML += listHtml;

        modal.classList.add('is-open');
    };

    window.openSingleAllocateModal = function (leadId, leadName) {
        container.innerHTML = '';
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'lead_ids[]';
        input.value = leadId;
        container.appendChild(input);

        container.innerHTML += '<div style="background:#fff7ed; border:1px solid #ffedd5; border-radius:12px; padding:12px 16px; margin-bottom:18px; font-size:13px; color:#c2410c;">' +
            '<strong>Selected Lead:</strong> ' + escapeHtml(leadName) +
        '</div>';

        modal.classList.add('is-open');
    };

    window.closeAllocateModal = function () {
        modal.classList.remove('is-open');
    };

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>
@endpush
