@extends('layouts.app')

@section('title', 'Recruitment Call Updates')

@push('styles')
<style>
.rcu-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; }
.rcu-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.rcu-title { font-size:18px; font-weight:800; color:#121212; }
.rcu-crumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.rcu-crumb a { color:#fe5f04; text-decoration:none; font-weight:700; }
.rcu-body { padding:18px 28px 28px; display:flex; flex-direction:column; gap:14px; }

/* Filter & Table Cards */
.rcu-filter-card, .rcu-table-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; box-shadow:0 10px 24px rgba(18,18,18,.04); overflow:hidden; }
.rcu-filter-head, .rcu-table-head { padding:14px 18px; border-bottom:1px solid #f1eef2; background:linear-gradient(180deg,#fffaf7 0%, #fff 100%); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.rcu-head-title { font-size:14px; font-weight:800; color:#121212; }
.rcu-head-sub { font-size:11px; color:#9e9e9e; margin-top:3px; }
.rcu-filter-body { padding:18px; }
.rcu-row { display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr 1fr auto; gap:12px; align-items:end; }
.rcu-group { display:flex; flex-direction:column; gap:6px; }
.rcu-label { font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.4px; }
.rcu-input, .rcu-select {
    width:100%; padding:9px 12px; border:1px solid #e1dee3; border-radius:10px; background:#faf7f4; color:#121212;
    font-size:13px; font-family:inherit; outline:none; transition:all .15s; box-sizing:border-box;
}
.rcu-input:focus, .rcu-select:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
.rcu-select { background:linear-gradient(180deg,#fff7f1 0%, #fff2e8 100%); border-color:#f7c9ac; color:#c2410c; }
.rcu-input[type="date"] { background:#fff; border-color:#e1dee3; color:#121212; cursor:pointer; }
.rcu-input[type="date"]:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
.rcu-actions { display:flex; align-items:center; gap:8px; }
.rcu-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 14px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; border:none; cursor:pointer; font-family:inherit; transition:all .15s; white-space:nowrap; }
.rcu-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 6px 16px rgba(254,95,4,.25); }
.rcu-btn-primary:hover { transform:translateY(-1px); }
.rcu-btn-ghost { background:#fff; color:#7c7c7c; border:1px solid #e1dee3; }
.rcu-btn-ghost:hover { border-color:#fe5f04; color:#fe5f04; }

/* Quick Filters */
.rcu-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
.rcu-qbtn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 8px; border: 1.5px solid #e1dee3; background: #faf8fb; color: #7c7c7c; font-size: 11px; font-weight: 800; cursor: pointer; transition: all .15s ease; text-transform: uppercase; letter-spacing: .02em; }
.rcu-qbtn:hover { border-color: #fe5f04; background: #fffaf7; color: #fe5f04; }
.rcu-qbtn.is-active { border-color: #fe5f04; background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; box-shadow: 0 4px 10px rgba(254,95,4,.2); }

/* Table Styles */
.rcu-table-wrap { overflow-x:auto; }
.rcu-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.rcu-table th { padding: 13px 16px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #7c7c7c; text-align: left; background: #fbfafc; border-bottom: 1.5px solid #eae6eb; }
.rcu-table td { padding: 14px 16px; font-size: 13px; color: #2e2e2e; border-bottom: 1px solid #f3eff2; vertical-align: middle; }
.rcu-table tbody tr:hover td { background: #fffaf7; }
.rcu-table tbody tr:last-child td { border-bottom: none; }

.rcu-cand-id { font-family: monospace; color: #fe5f04; font-size: 12px; font-weight: 800; background: #fff2ea; padding: 3px 8px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.15s; }
.rcu-cand-id:hover { background: #fe5f04; color: #fff; transform: translateY(-1px); }

.rcu-candidate-name { font-weight: 750; color: #121212; font-size: 13.5px; }
.rcu-candidate-job { font-size: 11.5px; color: #7c7c7c; margin-top: 2px; font-weight: 500; }
.rcu-type-badge { display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700; background:#f1f5f9; color:#475569; margin-top:3px; }

.rcu-contact-item { display: flex; align-items: center; gap: 6px; font-size: 12px; margin-bottom: 3px; }
.rcu-contact-item a { color: #475569; text-decoration: none; font-weight: 600; }
.rcu-contact-item a:hover { color: #fe5f04; }

/* Truncated note bubble */
.rcu-note-bubble {
    display: inline-block; padding: 6px 10px; border-radius: 8px; background: #faf8fb; border: 1px solid #e1dee3;
    font-size: 12px; color: #374151; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
    max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rcu-note-bubble:hover {
    border-color: #fe5f04; background: #fff7f2; color: #c2410c; box-shadow: 0 4px 10px rgba(254,95,4,0.08); transform: translateY(-1px);
}

/* Outcome & Call Type Pills */
.rcu-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 750; }
.rcu-pill-screening { background: #eff6ff; color: #1d4ed8; }
.rcu-pill-interested { background: #f0fdf4; color: #15803d; }
.rcu-pill-not_interested { background: #fef2f2; color: #b91c1c; }
.rcu-pill-interview_planned { background: #f5f3ff; color: #6d28d9; }
.rcu-pill-no_answer { background: #fffbeb; color: #b45309; }
.rcu-pill-follow_up { background: #fff7ed; color: #c2410c; }
.rcu-pill-selected { background: #ecfdf5; color: #047857; }
.rcu-pill-rejected { background: #fef2f2; color: #dc2626; }
.rcu-pill-default { background: #f1f5f9; color: #475569; }

.rcu-calltype-pill { display:inline-flex; align-items:center; gap:3px; padding:3px 7px; border-radius:6px; font-size:10px; font-weight:700; text-transform:uppercase; }
.rcu-calltype-outgoing { background:#eff6ff; color:#2563eb; }
.rcu-calltype-incoming { background:#f0fdf4; color:#16a34a; }
.rcu-calltype-missed { background:#fef2f2; color:#dc2626; }

/* Empty state */
.rcu-empty { padding:50px 20px; text-align:center; color:#9e9e9e; }
.rcu-empty-title { font-size:15px; font-weight:800; color:#7c7c7c; margin-bottom:6px; }

/* Detail Modal */
.rcu-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; display: flex; align-items: center; justify-content: center; }
.rcu-modal-overlay { position: absolute; width: 100%; height: 100%; background: rgba(18, 18, 18, 0.45); backdrop-filter: blur(4px); animation: fadeIn 0.2s ease-out; }
.rcu-modal-card { position: relative; width: 90%; max-width: 520px; background: #fff; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); overflow: hidden; animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1); border: 1px solid #e1dee3; }
.rcu-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1eef2; background: #faf8fb; }
.rcu-modal-title { font-size: 15px; font-weight: 800; color: #121212; }
.rcu-modal-close { background: none; border: none; font-size: 20px; color: #7c7c7c; cursor: pointer; transition: color 0.15s; line-height: 1; }
.rcu-modal-close:hover { color: #fe5f04; }
.rcu-modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
.rcu-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.rcu-modal-item { display: flex; flex-direction: column; gap: 3px; }
.rcu-modal-label { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #9e9e9e; letter-spacing: 0.5px; }
.rcu-modal-value { font-size: 13px; font-weight: 700; color: #121212; }
.rcu-modal-divider { height: 1px; background: #f1eef2; margin: 16px 0; }
.rcu-modal-note-section { background: #faf8fb; border-radius: 12px; padding: 14px; border: 1px dashed #e1dee3; }
.rcu-modal-note-content { font-size: 13px; color: #2e2e2e; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
.rcu-modal-foot { padding: 12px 20px; border-top: 1px solid #f1eef2; background: #faf8fb; display: flex; justify-content: flex-end; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

@media (max-width: 1200px) {
    .rcu-row { grid-template-columns: 1fr 1fr; }
    .rcu-actions { grid-column: 1 / -1; }
}
@media (max-width: 720px) {
    .rcu-topbar { padding:0 18px; }
    .rcu-body { padding:14px 18px 22px; }
    .rcu-row { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
<div class="rcu-page">
    <div class="rcu-topbar">
        <div>
            <div class="rcu-title">Recruitment Call Updates</div>
            <div class="rcu-crumb"><a href="{{ route('hrms.dashboard') }}">HRMS</a> › <a href="{{ route('recruitment.index') }}">Recruitment</a> › Call Updates</div>
        </div>
        <div>
            <a href="{{ route('recruitment.index') }}" class="rcu-btn rcu-btn-ghost">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Candidate Pipeline</span>
            </a>
        </div>
    </div>

    <div class="rcu-body">
        @php
            $todayStr = now()->toDateString();
            $yesterdayStr = now()->subDay()->toDateString();
            $startOfWeekStr = now()->startOfWeek()->toDateString();
            $endOfWeekStr = now()->endOfWeek()->toDateString();
            $startOfMonthStr = now()->startOfMonth()->toDateString();
            $endOfMonthStr = now()->endOfMonth()->toDateString();

            $reqFrom = request('date_from', $dateFrom);
            $reqTo = request('date_to', $dateTo);
            $quick = request('quick_range');

            $isToday = $quick === 'today' || ($reqFrom === $todayStr && $reqTo === $todayStr);
            $isYesterday = $quick === 'yesterday' || ($reqFrom === $yesterdayStr && $reqTo === $yesterdayStr);
            $isThisWeek = $quick === 'this_week' || ($reqFrom === $startOfWeekStr && ($reqTo === $todayStr || $reqTo === $endOfWeekStr));
            $isThisMonth = $quick === 'this_month' || ($reqFrom === $startOfMonthStr && $reqTo === $endOfMonthStr);
            $isAllTime = $quick === 'all_time' || (request()->has('date_from') && empty(request('date_from')) && empty(request('date_to')));
        @endphp

        {{-- Filter Card --}}
        <form method="GET" action="{{ route('recruitment.calls.index') }}" class="rcu-filter-card" id="recruitmentFilterForm">
            <input type="hidden" name="quick_range" id="quick_range" value="{{ request('quick_range') }}">
            <div class="rcu-filter-head">
                <div>
                    <div class="rcu-head-title">Filter Recruitment Calls</div>
                    <div class="rcu-head-sub">Search candidate conversations, outcomes, and follow-ups.</div>
                </div>
            </div>

            <div class="rcu-filter-body">
                {{-- Quick Date Range Buttons --}}
                <div class="rcu-quick-filters">
                    <button type="button" class="rcu-qbtn {{ $isToday ? 'is-active' : '' }}" onclick="setDateRange('today')">Today</button>
                    <button type="button" class="rcu-qbtn {{ $isYesterday ? 'is-active' : '' }}" onclick="setDateRange('yesterday')">Yesterday</button>
                    <button type="button" class="rcu-qbtn {{ $isThisWeek ? 'is-active' : '' }}" onclick="setDateRange('this_week')">This Week</button>
                    <button type="button" class="rcu-qbtn {{ $isThisMonth ? 'is-active' : '' }}" onclick="setDateRange('this_month')">This Month</button>
                    <button type="button" class="rcu-qbtn {{ $isAllTime ? 'is-active' : '' }}" onclick="setDateRange('all_time')">All Time</button>
                </div>

                <div class="rcu-row">
                    <div class="rcu-group">
                        <label class="rcu-label">Search</label>
                        <input type="text" name="search" class="rcu-input" placeholder="Name, Mobile, Candidate ID, Job..." value="{{ request('search') }}">
                    </div>

                    <div class="rcu-group">
                        <label class="rcu-label">From Date</label>
                        <input type="date" name="date_from" id="date_from" class="rcu-input" value="{{ $dateFrom }}">
                    </div>

                    <div class="rcu-group">
                        <label class="rcu-label">To Date</label>
                        <input type="date" name="date_to" id="date_to" class="rcu-input" value="{{ $dateTo }}">
                    </div>

                    <div class="rcu-group">
                        <label class="rcu-label">Outcome</label>
                        <select name="outcome" class="rcu-select">
                            <option value="">All Outcomes</option>
                            @foreach($outcomes as $key => $label)
                                <option value="{{ $key }}" @selected(request('outcome') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rcu-group">
                        <label class="rcu-label">Caller / HR</label>
                        <select name="user_id" class="rcu-select">
                            <option value="">All Callers</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rcu-actions">
                        <button type="submit" class="rcu-btn rcu-btn-primary">Filter</button>
                        <a href="{{ route('recruitment.calls.index') }}" class="rcu-btn rcu-btn-ghost">Reset</a>
                    </div>
                </div>
            </div>
        </form>

        {{-- Table Card --}}
        <div class="rcu-table-card">
            <div class="rcu-table-head">
                <div>
                    <div class="rcu-head-title">Call Update Records</div>
                    <div class="rcu-head-sub">Showing {{ $callUpdates->firstItem() ?? 0 }} to {{ $callUpdates->lastItem() ?? 0 }} of {{ $callUpdates->total() }} updates</div>
                </div>
            </div>

            <div class="rcu-table-wrap">
                <table class="rcu-table">
                    <thead>
                        <tr>
                            <th>Candidate ID</th>
                            <th>Candidate Details</th>
                            <th>Contact</th>
                            <th>Call Notes</th>
                            <th>Outcome</th>
                            <th>Call Type</th>
                            <th>Called At</th>
                            <th>Caller</th>
                            <th>Next Follow-up</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($callUpdates as $call)
                            @php
                                $candidate = $call->candidate;
                                $outcomeKey = $call->outcome;
                                $outcomeClass = match($outcomeKey) {
                                    'screening' => 'rcu-pill-screening',
                                    'interested' => 'rcu-pill-interested',
                                    'not_interested' => 'rcu-pill-not_interested',
                                    'interview_planned' => 'rcu-pill-interview_planned',
                                    'no_answer' => 'rcu-pill-no_answer',
                                    'follow_up' => 'rcu-pill-follow_up',
                                    'selected' => 'rcu-pill-selected',
                                    'rejected' => 'rcu-pill-rejected',
                                    default => 'rcu-pill-default'
                                };
                            @endphp
                            <tr>
                                <td>
                                    @if($candidate)
                                        <a href="{{ route('recruitment.show', $candidate) }}" class="rcu-cand-id" title="View Candidate Profile">
                                            {{ $candidate->candidate_no ?: '#' . $candidate->id }}
                                        </a>
                                    @else
                                        <span class="rcu-cand-id">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if($candidate)
                                        <div class="rcu-candidate-name">{{ $candidate->name }}</div>
                                        <div class="rcu-candidate-job">{{ $candidate->job_title ?: 'Candidate' }} @if($candidate->location) • {{ $candidate->location }} @endif</div>
                                        @if($candidate->candidate_type)
                                            <span class="rcu-type-badge">{{ ucfirst($candidate->candidate_type) }}</span>
                                        @endif
                                    @else
                                        <span style="color:#9e9e9e;">Deleted Candidate</span>
                                    @endif
                                </td>

                                <td>
                                    @if($candidate)
                                        @if($candidate->mobile_number)
                                            <div class="rcu-contact-item">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                                <a href="tel:{{ $candidate->mobile_number }}">{{ $candidate->mobile_number }}</a>
                                            </div>
                                        @endif
                                        @if($candidate->email)
                                            <div class="rcu-contact-item">
                                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                                <a href="mailto:{{ $candidate->email }}">{{ $candidate->email }}</a>
                                            </div>
                                        @endif
                                    @else
                                        <span style="color:#9e9e9e;">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if($call->notes)
                                        <div class="rcu-note-bubble"
                                             onclick="openRecruitmentCallModal({{ json_encode([
                                                 'candidate_name' => $candidate?->name ?? 'Candidate',
                                                 'candidate_no' => $candidate?->candidate_no ?? ('#' . ($candidate?->id ?? '')),
                                                 'job_title' => $candidate?->job_title ?? '',
                                                 'caller' => $call->user?->name ?? 'HR Team',
                                                 'called_at' => $call->called_at?->format('d M Y, h:i A') ?? 'N/A',
                                                 'call_type' => $call->call_type_label,
                                                 'duration' => $call->duration_minutes ? ($call->duration_minutes . ' mins') : 'N/A',
                                                 'outcome' => $call->outcome_label,
                                                 'next_follow_up' => $call->next_follow_up_at?->format('d M Y, h:i A') ?? 'None',
                                                 'notes' => $call->notes
                                             ]) }})"
                                             title="Click to view full notes">
                                            {{ $call->notes }}
                                        </div>
                                    @else
                                        <span style="color:#9e9e9e; font-size:12px; font-style:italic;">No notes recorded</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="rcu-pill {{ $outcomeClass }}">{{ $call->outcome_label }}</span>
                                </td>

                                <td>
                                    <span class="rcu-calltype-pill rcu-calltype-{{ $call->call_type }}">{{ $call->call_type_label }}</span>
                                    @if($call->duration_minutes)
                                        <div style="font-size:11px; color:#7c7c7c; margin-top:2px;">{{ $call->duration_minutes }} min</div>
                                    @endif
                                </td>

                                <td>
                                    <div style="font-weight:700; color:#121212;">{{ $call->called_at?->format('d M Y') }}</div>
                                    <div style="font-size:11px; color:#7c7c7c;">{{ $call->called_at?->format('h:i A') }}</div>
                                </td>

                                <td>
                                    <span style="font-weight:600; color:#374151;">{{ $call->user?->name ?? 'HR Team' }}</span>
                                </td>

                                <td>
                                    @if($call->next_follow_up_at)
                                        <div style="font-weight:700; color:#c2410c;">{{ $call->next_follow_up_at->format('d M Y') }}</div>
                                        <div style="font-size:11px; color:#7c7c7c;">{{ $call->next_follow_up_at->format('h:i A') }}</div>
                                    @else
                                        <span style="color:#9e9e9e; font-size:12px;">—</span>
                                    @endif
                                </td>

                                <td style="text-align:right;">
                                    @if($candidate)
                                        <a href="{{ route('recruitment.show', $candidate) }}" class="rcu-btn rcu-btn-ghost" style="padding:6px 10px; font-size:11px;">
                                            <span>Profile</span>
                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="rcu-empty">
                                        <div class="rcu-empty-title">No Recruitment Call Updates Found</div>
                                        <div style="font-size:13px;">There are no candidate call updates matching your filter criteria.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($callUpdates->hasPages())
                <div style="border-top:1px solid #f1eef2;">
                    @include('partials.table-pagination', ['paginator' => $callUpdates])
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div id="recruitmentCallModal" class="rcu-modal" style="display:none;">
    <div class="rcu-modal-overlay" onclick="closeRecruitmentCallModal()"></div>
    <div class="rcu-modal-card">
        <div class="rcu-modal-head">
            <div class="rcu-modal-title">Candidate Call Details</div>
            <button type="button" class="rcu-modal-close" onclick="closeRecruitmentCallModal()">×</button>
        </div>
        <div class="rcu-modal-body">
            <div class="rcu-modal-grid">
                <div class="rcu-modal-item">
                    <div class="rcu-modal-label">Candidate</div>
                    <div class="rcu-modal-value" id="modalCandName"></div>
                </div>
                <div class="rcu-modal-item">
                    <div class="rcu-modal-label">Applied For</div>
                    <div class="rcu-modal-value" id="modalJobTitle"></div>
                </div>
                <div class="rcu-modal-item">
                    <div class="rcu-modal-label">Outcome</div>
                    <div class="rcu-modal-value" id="modalOutcome"></div>
                </div>
                <div class="rcu-modal-item">
                    <div class="rcu-modal-label">Call Type & Duration</div>
                    <div class="rcu-modal-value" id="modalCallType"></div>
                </div>
                <div class="rcu-modal-item">
                    <div class="rcu-modal-label">Called At</div>
                    <div class="rcu-modal-value" id="modalCalledAt"></div>
                </div>
                <div class="rcu-modal-item">
                    <div class="rcu-modal-label">Caller</div>
                    <div class="rcu-modal-value" id="modalCaller"></div>
                </div>
                <div class="rcu-modal-item" style="grid-column:1 / -1;">
                    <div class="rcu-modal-label">Next Follow-Up</div>
                    <div class="rcu-modal-value" id="modalFollowUp"></div>
                </div>
            </div>

            <div class="rcu-modal-divider"></div>

            <div class="rcu-modal-note-section">
                <div class="rcu-modal-label" style="margin-bottom:6px;">Call Notes & Discussion Summary</div>
                <div class="rcu-modal-note-content" id="modalNotes"></div>
            </div>
        </div>
        <div class="rcu-modal-foot">
            <button type="button" class="rcu-btn rcu-btn-ghost" onclick="closeRecruitmentCallModal()">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function setDateRange(range) {
    const today = new Date();
    const fromInput = document.getElementById('date_from');
    const toInput = document.getElementById('date_to');
    const quickRangeInput = document.getElementById('quick_range');

    function formatDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    if (quickRangeInput) {
        quickRangeInput.value = range;
    }

    if (range === 'today') {
        fromInput.value = formatDate(today);
        toInput.value = formatDate(today);
    } else if (range === 'yesterday') {
        const yest = new Date(today);
        yest.setDate(yest.getDate() - 1);
        fromInput.value = formatDate(yest);
        toInput.value = formatDate(yest);
    } else if (range === 'this_week') {
        const firstDay = new Date(today);
        const dayOfWeek = today.getDay() || 7;
        firstDay.setDate(today.getDate() - dayOfWeek + 1);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(today);
    } else if (range === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(lastDay);
    } else if (range === 'all_time') {
        fromInput.value = '';
        toInput.value = '';
    }

    document.getElementById('recruitmentFilterForm').submit();
}

function openRecruitmentCallModal(data) {
    document.getElementById('modalCandName').textContent = (data.candidate_name || 'Candidate') + ' (' + (data.candidate_no || '') + ')';
    document.getElementById('modalJobTitle').textContent = data.job_title || 'N/A';
    document.getElementById('modalOutcome').textContent = data.outcome || 'N/A';
    document.getElementById('modalCallType').textContent = (data.call_type || '') + ' (' + (data.duration || 'N/A') + ')';
    document.getElementById('modalCalledAt').textContent = data.called_at || 'N/A';
    document.getElementById('modalCaller').textContent = data.caller || 'N/A';
    document.getElementById('modalFollowUp').textContent = data.next_follow_up || 'None';
    document.getElementById('modalNotes').textContent = data.notes || 'No notes entered.';

    document.getElementById('recruitmentCallModal').style.display = 'flex';
}

function closeRecruitmentCallModal() {
    document.getElementById('recruitmentCallModal').style.display = 'none';
}
</script>
@endpush
