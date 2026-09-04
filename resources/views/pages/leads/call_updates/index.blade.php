@extends('layouts.app')

@section('title', 'Call Updates')

@push('styles')
<style>
.cu-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; }
.cu-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.cu-title { font-size:18px; font-weight:800; color:#121212; }
.cu-crumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.cu-crumb a { color:#fe5f04; text-decoration:none; font-weight:700; }
.cu-body { padding:18px 28px 28px; display:flex; flex-direction:column; gap:14px; }
.cu-filter-card, .cu-table-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; box-shadow:0 10px 24px rgba(18,18,18,.04); overflow:hidden; }
.cu-filter-head, .cu-table-head { padding:14px 18px; border-bottom:1px solid #f1eef2; background:linear-gradient(180deg,#fffaf7 0%, #fff 100%); }
.cu-head-title { font-size:14px; font-weight:800; color:#121212; }
.cu-head-sub { font-size:11px; color:#9e9e9e; margin-top:3px; }
.cu-filter-body { padding:18px; }
.cu-row { display:grid; grid-template-columns:1.2fr 1fr 1fr 1fr 1fr auto; gap:12px; align-items:end; }
.cu-group { display:flex; flex-direction:column; gap:6px; }
.cu-label { font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.4px; }
.cu-input, .cu-select {
    width:100%; padding:10px 12px; border:1px solid #e1dee3; border-radius:10px; background:#faf7f4; color:#121212;
    font-size:13px; font-family:inherit; outline:none; transition:all .15s; box-sizing:border-box;
}
.cu-input:focus, .cu-select:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
.cu-select { background:linear-gradient(180deg,#fff7f1 0%, #fff2e8 100%); border-color:#f7c9ac; color:#c2410c; }
.cu-input[type="date"] { background:#fff; border-color:#e1dee3; color:#121212; cursor:pointer; }
.cu-input[type="date"]:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
.cu-actions { display:flex; align-items:center; gap:8px; }
.cu-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 14px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; border:none; cursor:pointer; font-family:inherit; transition:all .15s; white-space:nowrap; }
.cu-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 6px 16px rgba(254,95,4,.25); }
.cu-btn-primary:hover { transform:translateY(-1px); }
.cu-btn-ghost { background:#fff; color:#7c7c7c; border:1px solid #e1dee3; }
.cu-btn-ghost:hover { border-color:#fe5f04; color:#fe5f04; }
.cu-table-wrap { overflow-x:auto; }
.cu-table { width:100%; border-collapse:collapse; }
.cu-table th { padding:11px 14px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:#9e9e9e; text-align:left; background:#fafafa; border-bottom:1px solid #ece7eb; }
.cu-table td { padding:14px; font-size:13px; color:#121212; border-bottom:1px solid #f4f1f3; vertical-align:top; }
.cu-table tbody tr { cursor:pointer; }
.cu-table tbody tr:hover td { background:#fffaf7; }
.cu-lead-id { font-family:monospace; color:#7c7c7c; font-size:12px; }
.cu-client { font-weight:700; color:#121212; }
.cu-company { font-size:12px; color:#7c7c7c; margin-top:2px; }
.cu-contact a { color:#2563eb; text-decoration:none; }
.cu-contact a:hover { color:#fe5f04; text-decoration:underline; }
.cu-update-note { color:#2e2e2e; line-height:1.5; }
.cu-meta { margin-top:6px; display:flex; flex-wrap:wrap; gap:6px; }
.cu-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 8px; border-radius:999px; font-size:10px; font-weight:800; }
.cu-pill-time { background:#eff6ff; color:#2563eb; }
.cu-pill-user { background:#f0fdf4; color:#15803d; }
.cu-empty { padding:50px 20px; text-align:center; color:#9e9e9e; }
.cu-empty-title { font-size:15px; font-weight:800; color:#7c7c7c; margin-bottom:6px; }
.cu-results { font-size:12px; color:#9e9e9e; }
.cu-results strong { color:#121212; }
.cu-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.cu-qbtn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 8px; border: 1.5px solid #e1dee3; background: #faf8fb; color: #7c7c7c; font-size: 11px; font-weight: 800; cursor: pointer; transition: all .15s ease; text-transform: uppercase; letter-spacing: .02em; }
.cu-qbtn:hover { border-color: #fe5f04; background: #fffaf7; color: #fe5f04; }
.cu-qbtn.is-active { border-color: #fe5f04; background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; box-shadow: 0 4px 10px rgba(254,95,4,.2); }

/* Premium Table Revamp Styles */
.cu-table-wrap { overflow-x:auto; }
.cu-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.cu-table th { padding: 14px 18px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #7c7c7c; text-align: left; background: #fbfafc; border-bottom: 1.5px solid #eae6eb; }
.cu-table td { padding: 16px 18px; font-size: 13px; color: #2e2e2e; border-bottom: 1px solid #f3eff2; vertical-align: middle; transition: all 0.15s ease; }
.cu-table tbody tr { transition: transform 0.1s ease, box-shadow 0.1s ease; }
.cu-table tbody tr:hover td { background: #fffaf7; }
.cu-table tbody tr:last-child td { border-bottom: none; }

.cu-lead-id { font-family: monospace; color: #fe5f04; font-size: 12px; font-weight: 800; background: #fff2ea; padding: 4px 8px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.15s; }
.cu-lead-id:hover { background: #fe5f04; color: #fff; transform: translateY(-1px); }

.cu-client { font-weight: 750; color: #121212; font-size: 13.5px; }
.cu-company { font-size: 11.5px; color: #7c7c7c; margin-top: 3px; font-weight: 500; }

.cu-contact-item { display: flex; align-items: center; gap: 6px; font-size: 12.5px; margin-bottom: 4px; }
.cu-contact-item svg { color: #fe5f04; }
.cu-contact-item a { color: #475569; text-decoration: none; font-weight: 600; transition: color 0.15s; }
.cu-contact-item a:hover { color: #fe5f04; }

/* Truncated note element styling */
.cu-note-bubble {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 10px;
    background: #faf8fb;
    border: 1.5px solid #e1dee3;
    font-size: 12.5px;
    color: #374151;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    max-width: 250px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cu-note-bubble:hover {
    border-color: #fe5f04;
    background: #fff7f2;
    color: #c2410c;
    box-shadow: 0 4px 12px rgba(254,95,4,0.08);
    transform: translateY(-1px);
}

/* Premium Modal Styles */
.cu-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; display: flex; align-items: center; justify-content: center; }
.cu-modal-overlay { position: absolute; width: 100%; height: 100%; background: rgba(18, 18, 18, 0.4); backdrop-filter: blur(4px); animation: fadeIn 0.2s ease-out; }
.cu-modal-card { position: relative; width: 90%; max-width: 500px; background: #fff; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1); border: 1px solid #e1dee3; }
.cu-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1eef2; background: #faf8fb; }
.cu-modal-title { font-size: 15px; font-weight: 800; color: #121212; }
.cu-modal-close { background: none; border: none; font-size: 18px; color: #7c7c7c; cursor: pointer; transition: color 0.15s; }
.cu-modal-close:hover { color: #fe5f04; }
.cu-modal-body { padding: 20px; }
.cu-modal-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.cu-modal-meta-item { display: flex; flex-direction: column; gap: 4px; }
.cu-modal-meta-label { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #9e9e9e; letter-spacing: 0.5px; }
.cu-modal-meta-value { font-size: 13px; font-weight: 700; color: #121212; }
.cu-modal-divider { height: 1px; background: #f1eef2; margin: 16px 0; }
.cu-modal-note-section { background: #faf8fb; border-radius: 12px; padding: 14px; border: 1px dashed #e1dee3; }
.cu-modal-note-content { font-size: 13px; color: #2e2e2e; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
.cu-modal-foot { padding: 12px 20px; border-top: 1px solid #f1eef2; background: #faf8fb; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

@media (max-width: 1180px) {
    .cu-row { grid-template-columns:1fr 1fr; }
    .cu-actions { grid-column:1 / -1; }
}
@media (max-width: 720px) {
    .cu-topbar { padding:0 18px; }
    .cu-body { padding:14px 18px 22px; }
    .cu-row { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
<div class="cu-page">
    <div class="cu-topbar">
        <div>
            <div class="cu-title">Call Updates</div>
            <div class="cu-crumb"><a href="{{ route('leads.index') }}">Leads</a> › Call Updates</div>
        </div>
    </div>

    <div class="cu-body">
        <form method="GET" action="{{ route('leads.calls.index') }}" class="cu-filter-card" id="filterForm">
            <div class="cu-filter-head">
                <div class="cu-head-title">Filter Call Updates</div>
                <div class="cu-head-sub">Showing current month by default. Change the filters below to view other dates or users.</div>
            </div>
            <div class="cu-filter-body">
                <div class="cu-quick-filters">
                    <span style="font-size:11px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#9e9e9e; margin-right:4px;">Quick:</span>
                    <button type="button" class="cu-qbtn" data-preset="today">Today</button>
                    <button type="button" class="cu-qbtn" data-preset="week">This Week</button>
                    <button type="button" class="cu-qbtn" data-preset="month">This Month</button>
                    <button type="button" class="cu-qbtn" data-preset="quarter">This Quarter</button>
                    <button type="button" class="cu-qbtn" data-preset="year">This Year</button>
                    <button type="button" class="cu-qbtn" data-preset="all">Show All</button>
                </div>
                <div class="cu-row">
                    <div class="cu-group">
                        <label class="cu-label">Search</label>
                        <input type="text" name="search" class="cu-input" value="{{ request('search') }}" placeholder="Lead ID, client, company, mobile, user, notes">
                    </div>
                    <div class="cu-group">
                        <label class="cu-label">User</label>
                        <select name="user_id" class="cu-select">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cu-group">
                        <label class="cu-label">Branch</label>
                        <select name="branch_id" class="cu-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cu-group">
                        <label class="cu-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="cu-input" value="{{ $dateFrom }}">
                    </div>
                    <div class="cu-group">
                        <label class="cu-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="cu-input" value="{{ $dateTo }}">
                    </div>
                    <div class="cu-actions">
                        <button type="submit" class="cu-btn cu-btn-primary">Apply</button>
                        <a href="{{ route('leads.calls.index') }}" class="cu-btn cu-btn-ghost">Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="cu-table-card">
            <div class="cu-table-head">
                <div class="cu-head-title">Call Update List</div>
                <div class="cu-results">
                    Showing <strong>{{ $callUpdates->firstItem() ?? 0 }}–{{ $callUpdates->lastItem() ?? 0 }}</strong>
                    of <strong>{{ $callUpdates->total() }}</strong> records
                </div>
            </div>

            @if($callUpdates->isEmpty())
            <div class="cu-empty">
                <div class="cu-empty-title">No call updates found</div>
                <div>Try changing the date or filter options.</div>
            </div>
            @else
            <div class="cu-table-wrap">
                <table class="cu-table">
                    <thead>
                        <tr>
                            <th>Lead ID</th>
                            <th>Client Name</th>
                            <th>Company Name</th>
                            <th>Mobile / Email</th>
                            <th>Call Updates</th>
                            <th>Username</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($callUpdates as $call)
                        <tr onclick="window.location='{{ route('leads.show', $call->lead_id) }}'">
                            <td>
                                <a href="{{ route('leads.show', $call->lead_id) }}" class="cu-lead-id" onclick="event.stopPropagation()">LD-{{ str_pad($call->lead_id, 4, '0', STR_PAD_LEFT) }}</a>
                            </td>
                            <td>
                                <div class="cu-client">{{ $call->lead?->contact_name ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="cu-client">{{ $call->lead?->company_name ?? '—' }}</div>
                            </td>
                            <td class="cu-contact">
                                @if($call->lead?->mobile_number)
                                <div class="cu-contact-item">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    <a href="tel:{{ $call->lead->mobile_number }}" onclick="event.stopPropagation()">{{ $call->lead->mobile_number }}</a>
                                </div>
                                @endif
                                @if($call->lead?->email)
                                <div class="cu-contact-item">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    <a href="mailto:{{ $call->lead->email }}" onclick="event.stopPropagation()">{{ $call->lead->email }}</a>
                                </div>
                                @endif
                            </td>
                             <td>
                                 <div class="cu-note-bubble" 
                                      data-lead="LD-{{ str_pad($call->lead_id, 4, '0', STR_PAD_LEFT) }}"
                                      data-client="{{ $call->lead?->contact_name ?? '—' }} ({{ $call->lead?->company_name ?? '—' }})"
                                      data-user="{{ $call->user?->name ?? 'System' }}"
                                      data-called="{{ $call->called_at?->format('d M Y, h:i A') ?? '—' }}"
                                      data-outcome="{{ $call->outCome?->name ?? '' }}"
                                      data-outcomesub="{{ $call->outComeSubCategory?->name ?? '' }}"
                                      data-notes="{{ $call->notes }}"
                                      onclick="event.stopPropagation(); showCallDetails(this)">
                                     {{ Str::limit($call->notes, 25, '...') }}
                                 </div>
                                 <div class="cu-meta" style="margin-top: 8px;">
                                     <span class="cu-pill cu-pill-time">{{ $call->called_at?->format('d M Y, h:i A') ?? '—' }}</span>
                                     @if($call->outCome?->name)
                                     <span class="cu-pill" style="background:#f5f3ff;color:#6d28d9">{{ $call->outCome->name }}</span>
                                     @endif
                                     @if($call->outComeSubCategory?->name)
                                     <span class="cu-pill" style="background:#eef2ff;color:#4338ca">{{ $call->outComeSubCategory->name }}</span>
                                     @endif
                                 </div>
                             </td>
                            <td>
                                <span class="cu-pill cu-pill-user">{{ $call->user?->name ?? 'System' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($callUpdates->hasPages())
                @include('partials.table-pagination', ['paginator' => $callUpdates])
            @endif
            @endif
        </div>
    </div>
</div>

<!-- Elegant Call Update Details Modal -->
<div id="cuDetailsModal" class="cu-modal" style="display:none;">
    <div class="cu-modal-overlay" onclick="closeCuModal()"></div>
    <div class="cu-modal-card">
        <div class="cu-modal-head">
            <div class="cu-modal-title">📞 Call Update Details</div>
            <button class="cu-modal-close" onclick="closeCuModal()">✕</button>
        </div>
        <div class="cu-modal-body">
            <div class="cu-modal-meta-grid">
                <div class="cu-modal-meta-item">
                    <span class="cu-modal-meta-label">Lead ID</span>
                    <span class="cu-modal-meta-value" id="modalLeadId">—</span>
                </div>
                <div class="cu-modal-meta-item">
                    <span class="cu-modal-meta-label">Client / Company</span>
                    <span class="cu-modal-meta-value" id="modalClientCompany">—</span>
                </div>
                <div class="cu-modal-meta-item">
                    <span class="cu-modal-meta-label">Username</span>
                    <span class="cu-modal-meta-value" id="modalUser">—</span>
                </div>
                <div class="cu-modal-meta-item">
                    <span class="cu-modal-meta-label">Called At</span>
                    <span class="cu-modal-meta-value" id="modalCalledAt">—</span>
                </div>
            </div>
            
            <div style="margin-top:16px;" id="modalBadgesContainer">
                <!-- Outcomes will go here -->
            </div>

            <div class="cu-modal-divider"></div>

            <div class="cu-modal-note-section">
                <span class="cu-modal-meta-label" style="margin-bottom:8px; display:block;">Call Notes / Updates</span>
                <div class="cu-modal-note-content" id="modalNotesText">—</div>
            </div>
        </div>
        <div class="cu-modal-foot">
            <button type="button" class="cu-btn cu-btn-ghost" onclick="closeCuModal()" style="width:100%;">Close</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showCallDetails(el) {
    const leadId = el.getAttribute('data-lead');
    const clientCompany = el.getAttribute('data-client');
    const user = el.getAttribute('data-user');
    const calledAt = el.getAttribute('data-called');
    const outcome = el.getAttribute('data-outcome');
    const outcomeSub = el.getAttribute('data-outcomesub');
    const notes = el.getAttribute('data-notes');

    openCuModal(leadId, clientCompany, user, calledAt, notes, outcome, outcomeSub);
}

function openCuModal(leadId, clientCompany, user, calledAt, notes, outcome, outcomeSub) {
    document.getElementById('modalLeadId').textContent = leadId;
    document.getElementById('modalClientCompany').textContent = clientCompany;
    document.getElementById('modalUser').textContent = user;
    document.getElementById('modalCalledAt').textContent = calledAt;
    document.getElementById('modalNotesText').textContent = notes || 'No notes added.';

    const badgesContainer = document.getElementById('modalBadgesContainer');
    badgesContainer.innerHTML = '';
    if (outcome) {
        badgesContainer.innerHTML += `<span class="cu-pill" style="background:#f5f3ff;color:#6d28d9;margin-right:6px;padding:5px 10px;font-size:11px;">${outcome}</span>`;
    }
    if (outcomeSub) {
        badgesContainer.innerHTML += `<span class="cu-pill" style="background:#eef2ff;color:#4338ca;padding:5px 10px;font-size:11px;">${outcomeSub}</span>`;
    }

    document.getElementById('cuDetailsModal').style.display = 'flex';
}

function closeCuModal() {
    document.getElementById('cuDetailsModal').style.display = 'none';
}

(() => {
    const fmtDate = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    const today = new Date();
    const y = today.getFullYear(), m = today.getMonth(), q = Math.floor(m / 3);
    const day = today.getDay();
    const diffToMon = (day + 6) % 7;
    const mon = new Date(y, m, today.getDate() - diffToMon);
    const sun = new Date(y, m, today.getDate() - diffToMon + 6);

    const presets = {
        today:   { from: fmtDate(today), to: fmtDate(today) },
        week:    { from: fmtDate(mon), to: fmtDate(sun) },
        month:   { from: fmtDate(new Date(y, m, 1)), to: fmtDate(new Date(y, m + 1, 0)) },
        quarter: { from: fmtDate(new Date(y, q * 3, 1)), to: fmtDate(new Date(y, q * 3 + 3, 0)) },
        year:    { from: fmtDate(new Date(y, 0, 1)), to: fmtDate(new Date(y, 11, 31)) },
        all:     { from: '', to: '' },
    };
    const urlParams = new URLSearchParams(window.location.search);
    const hasDateParam = urlParams.has('date_from') || urlParams.has('date_to');
    const currentFrom = urlParams.get('date_from') ?? '';
    const currentTo   = urlParams.get('date_to')   ?? '';
    const fromInput = document.getElementById('date_from');
    const toInput   = document.getElementById('date_to');
    const form      = document.getElementById('filterForm');

    const defaultFrom = '{{ now()->startOfMonth()->toDateString() }}';
    const defaultTo   = '{{ now()->endOfMonth()->toDateString() }}';
    const activeFrom  = hasDateParam ? currentFrom : defaultFrom;
    const activeTo    = hasDateParam ? currentTo : defaultTo;

    document.querySelectorAll('.cu-qbtn').forEach(btn => {
        const preset = presets[btn.dataset.preset];
        if (btn.dataset.preset === 'all') {
            if (hasDateParam && !currentFrom && !currentTo) {
                btn.classList.add('is-active');
            }
        } else if (preset && activeFrom === preset.from && activeTo === preset.to) {
            btn.classList.add('is-active');
        }
        btn.addEventListener('click', () => {
            const p = presets[btn.dataset.preset];
            if (!p) return;
            fromInput.value = p.from;
            toInput.value   = p.to;
            form.submit();
        });
    });

    [fromInput, toInput].forEach(inp => {
        if (!inp) return;
        inp.addEventListener('input', () => {
            document.querySelectorAll('.cu-qbtn').forEach(b => b.classList.remove('is-active'));
        });
        inp.addEventListener('change', () => {
            document.querySelectorAll('.cu-qbtn').forEach(b => b.classList.remove('is-active'));
        });
    });
})();
</script>
@endpush

@endsection
