@extends('layouts.app')

@section('title', 'Recruitment Reminders')

@push('styles')
<style>
.rec-rem-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; }
.rec-rem-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.rec-rem-title { font-size:18px; font-weight:800; color:#121212; }
.rec-rem-crumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.rec-rem-crumb a { color:#fe5f04; text-decoration:none; font-weight:700; }
.rec-rem-body { padding:18px 28px 28px; display:flex; flex-direction:column; gap:14px; }

/* Filter & Table Cards */
.rec-rem-filter-card, .rec-rem-table-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; box-shadow:0 10px 24px rgba(18,18,18,.04); overflow:hidden; }
.rec-rem-filter-head, .rec-rem-table-head { padding:14px 18px; border-bottom:1px solid #f1eef2; background:linear-gradient(180deg,#fffaf7 0%, #fff 100%); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.rec-rem-head-title { font-size:14px; font-weight:800; color:#121212; }
.rec-rem-head-sub { font-size:11px; color:#9e9e9e; margin-top:3px; }
.rec-rem-filter-body { padding:18px; }
.rec-rem-row { display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr auto; gap:12px; align-items:end; }
.rec-rem-group { display:flex; flex-direction:column; gap:6px; }
.rec-rem-label { font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.4px; }
.rec-rem-input, .rec-rem-select {
    width:100%; padding:9px 12px; border:1px solid #e1dee3; border-radius:10px; background:#faf7f4; color:#121212;
    font-size:13px; font-family:inherit; outline:none; transition:all .15s; box-sizing:border-box;
}
.rec-rem-input:focus, .rec-rem-select:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
.rec-rem-select { background:linear-gradient(180deg,#fff7f1 0%, #fff2e8 100%); border-color:#f7c9ac; color:#c2410c; }
.rec-rem-actions { display:flex; align-items:center; gap:8px; }
.rec-rem-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 14px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; border:none; cursor:pointer; font-family:inherit; transition:all .15s; white-space:nowrap; }
.rec-rem-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 6px 16px rgba(254,95,4,.25); }
.rec-rem-btn-primary:hover { transform:translateY(-1px); }
.rec-rem-btn-ghost { background:#fff; color:#7c7c7c; border:1px solid #e1dee3; }
.rec-rem-btn-ghost:hover { border-color:#fe5f04; color:#fe5f04; }

/* Tabs */
.rec-rem-tabs-row { display:flex; align-items:center; gap:10px; padding:12px 18px; background:#fcfbfe; border-bottom:1px solid #e1dee3; flex-wrap:wrap; }
.rec-rem-tab { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:12px; font-size:13px; font-weight:700; color:#7c7c7c; text-decoration:none; background:transparent; border:1px solid transparent; transition:all .15s; cursor:pointer; }
.rec-rem-tab:hover { background:#f5f2f7; color:#121212; }
.rec-rem-tab.active-today { background:linear-gradient(135deg, #fe5f04, #ff7c30); color:#fff; border-color:#fe5f04; box-shadow:0 4px 12px rgba(254,95,4,.2); }
.rec-rem-tab.active-overdue { background:linear-gradient(135deg, #dc2626, #ef4444); color:#fff; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }
.rec-rem-tab.active-completed { background:linear-gradient(135deg, #16a34a, #22c55e); color:#fff; border-color:#16a34a; box-shadow:0 4px 12px rgba(22,163,74,.2); }
.rec-rem-tab-badge { display:inline-flex; align-items:center; justify-content:center; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:800; background:rgba(0,0,0,.08); }
.rec-rem-tab.active-today .rec-rem-tab-badge,
.rec-rem-tab.active-overdue .rec-rem-tab-badge,
.rec-rem-tab.active-completed .rec-rem-tab-badge { background:rgba(255,255,255,.28); color:#fff; }

/* Table */
.rec-rem-table-wrap { overflow-x:auto; }
.rec-rem-table { width:100%; border-collapse:separate; border-spacing:0; }
.rec-rem-table th { padding:13px 16px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#7c7c7c; text-align:left; background:#fbfafc; border-bottom:1.5px solid #eae6eb; }
.rec-rem-table td { padding:14px 16px; font-size:13px; color:#2e2e2e; border-bottom:1px solid #f3eff2; vertical-align:middle; }
.rec-rem-table tbody tr:hover td { background:#fffaf7; }
.rec-rem-table tbody tr:last-child td { border-bottom:none; }

.rec-rem-cand-id { font-family:monospace; color:#fe5f04; font-size:12px; font-weight:800; background:#fff2ea; padding:3px 8px; border-radius:6px; text-decoration:none; display:inline-block; transition:all 0.15s; }
.rec-rem-cand-id:hover { background:#fe5f04; color:#fff; transform:translateY(-1px); }

.rec-rem-cand-name { font-weight:750; color:#121212; font-size:13.5px; }
.rec-rem-cand-job { font-size:11.5px; color:#7c7c7c; margin-top:2px; font-weight:500; }
.rec-rem-contact-item { display:flex; align-items:center; gap:6px; font-size:12px; margin-bottom:3px; }
.rec-rem-contact-item a { color:#475569; text-decoration:none; font-weight:600; }
.rec-rem-contact-item a:hover { color:#fe5f04; }

.rec-rem-title-text { font-weight:750; color:#121212; font-size:13px; }
.rec-rem-desc-text { font-size:12px; color:#64748b; margin-top:3px; max-width:280px; line-height:1.4; }

/* Action Buttons */
.rec-rem-done-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; background:#16a34a; color:#fff; font-size:12px; font-weight:750; border:none; cursor:pointer; text-decoration:none; transition:all .15s; box-shadow:0 3px 8px rgba(22,163,74,.2); }
.rec-rem-done-btn:hover { background:#15803d; transform:translateY(-1px); color:#fff; }
.rec-rem-reopen-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; background:#f3f4f6; color:#4b5563; font-size:12px; font-weight:700; border:1px solid #d1d5db; cursor:pointer; text-decoration:none; transition:all .15s; }
.rec-rem-reopen-btn:hover { background:#e5e7eb; color:#111827; }

/* Badges */
.rec-rem-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:750; }
.rec-rem-badge-high { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.rec-rem-badge-medium { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.rec-rem-badge-low { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

.rec-rem-type-pill { display:inline-flex; align-items:center; gap:4px; padding:4px 9px; border-radius:999px; font-size:11px; font-weight:750; background:#f1f5f9; color:#475569; }
.rec-rem-type-follow_up { background:#fff7ed; color:#c2410c; }
.rec-rem-type-interview { background:#f5f3ff; color:#6d28d9; }
.rec-rem-type-call { background:#eff6ff; color:#1d4ed8; }

/* Pagination */
.rec-rem-pagination-wrap { display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; padding:16px 20px; border-top:1px solid #f1eef2; background:#fff; }
.rec-rem-pagination-info-badge { display:inline-flex; align-items:center; padding:5px 12px; border-radius:10px; background:#faf7f4; border:1px solid #ece7eb; font-size:12px; color:#64748b; }
.rec-rem-pagination-links { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.rec-rem-page-link, .rec-rem-page-ellipsis { display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 12px; border-radius:10px; border:1px solid #e1dee3; background:#fff; font-size:13px; font-weight:700; color:#4b5563; text-decoration:none; transition:all .15s ease; }
.rec-rem-page-link:hover { border-color:#fe5f04; color:#fe5f04; background:#fffaf7; transform:translateY(-1px); }
.rec-rem-page-link.is-active { background:linear-gradient(135deg,#fe5f04,#ff7c30); border-color:#fe5f04; color:#fff; box-shadow:0 4px 12px rgba(254,95,4,.25); }
.rec-rem-page-link.is-disabled { opacity:.45; background:#faf7f4; border-color:#e5e7eb; color:#9ca3af; cursor:not-allowed; pointer-events:none; }
.rec-rem-page-ellipsis { border-color:transparent; background:transparent; color:#9ca3af; min-width:auto; padding:0 4px; }

@media (max-width: 1200px) {
    .rec-rem-row { grid-template-columns: 1fr 1fr; }
    .rec-rem-actions { grid-column: 1 / -1; }
}
@media (max-width: 720px) {
    .rec-rem-topbar { padding:0 18px; }
    .rec-rem-body { padding:14px 18px 22px; }
    .rec-rem-row { grid-template-columns:1fr; }
    .rec-rem-pagination-wrap { flex-direction:column; align-items:center; gap:12px; }
}
</style>
@endpush

@section('content')
<div class="rec-rem-page">
    <div class="rec-rem-topbar">
        <div>
            <div class="rec-rem-title">Recruitment Reminders & Follow-ups</div>
            <div class="rec-rem-crumb"><a href="{{ route('hrms.dashboard') }}">HRMS</a> › <a href="{{ route('recruitment.index') }}">Recruitment</a> › Reminders</div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <a href="{{ route('recruitment.calls.index') }}" class="rec-rem-btn rec-rem-btn-ghost">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <span>Call Updates</span>
            </a>
            <a href="{{ route('recruitment.index') }}" class="rec-rem-btn rec-rem-btn-ghost">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Candidate Pipeline</span>
            </a>
        </div>
    </div>

    <div class="rec-rem-body">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div style="padding:12px 16px; border-radius:12px; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Filter Card --}}
        <div class="rec-rem-filter-card">
            <div class="rec-rem-filter-head">
                <div>
                    <div class="rec-rem-head-title">Filter Reminders</div>
                    <div class="rec-rem-head-sub">Filter candidate reminders by keywords, assigned HR, type, or priority.</div>
                </div>
            </div>
            <div class="rec-rem-filter-body">
                <form method="GET" action="{{ route('recruitment.reminders.index') }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="rec-rem-row">
                        <div class="rec-rem-group">
                            <label class="rec-rem-label">Search</label>
                            <input type="text" name="search" class="rec-rem-input" value="{{ request('search') }}" placeholder="Candidate name, ID, mobile, title, notes...">
                        </div>
                        <div class="rec-rem-group">
                            <label class="rec-rem-label">Assigned HR</label>
                            <select name="user_id" class="rec-rem-select">
                                <option value="">All HR Members</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rec-rem-group">
                            <label class="rec-rem-label">Type</label>
                            <select name="type" class="rec-rem-select">
                                <option value="">All Types</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rec-rem-group">
                            <label class="rec-rem-label">Priority</label>
                            <select name="priority" class="rec-rem-select">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $key => $label)
                                    <option value="{{ $key }}" @selected(request('priority') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rec-rem-actions">
                            <button type="submit" class="rec-rem-btn rec-rem-btn-primary">Filter</button>
                            @if(request()->anyFilled(['search', 'user_id', 'type', 'priority', 'candidate_status']))
                                <a href="{{ route('recruitment.reminders.index', ['tab' => $activeTab]) }}" class="rec-rem-btn rec-rem-btn-ghost">Reset</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table Card with Tabs --}}
        <div class="rec-rem-table-card">
            {{-- Tabs --}}
            <div class="rec-rem-tabs-row">
                <a href="{{ route('recruitment.reminders.index', array_merge(request()->except('page'), ['tab' => 'today'])) }}"
                   class="rec-rem-tab {{ $activeTab === 'today' ? 'active-today' : '' }}">
                    <span>📅 Today Planned</span>
                    <span class="rec-rem-tab-badge">{{ $todayCount }}</span>
                </a>
                <a href="{{ route('recruitment.reminders.index', array_merge(request()->except('page'), ['tab' => 'overdue'])) }}"
                   class="rec-rem-tab {{ $activeTab === 'overdue' ? 'active-overdue' : '' }}">
                    <span>⚠️ Overdue</span>
                    <span class="rec-rem-tab-badge">{{ $overdueCount }}</span>
                </a>
                <a href="{{ route('recruitment.reminders.index', array_merge(request()->except('page'), ['tab' => 'completed'])) }}"
                   class="rec-rem-tab {{ $activeTab === 'completed' ? 'active-completed' : '' }}">
                    <span>✅ Completed</span>
                    <span class="rec-rem-tab-badge">{{ $completedCount }}</span>
                </a>
            </div>

            {{-- Table --}}
            <div class="rec-rem-table-wrap">
                <table class="rec-rem-table">
                    <thead>
                        <tr>
                            <th style="width:110px;">Type</th>
                            <th>Reminder / Follow-up Title</th>
                            <th>Candidate Details</th>
                            <th>{{ $activeTab === 'completed' ? 'Completed & Scheduled Date' : 'Scheduled Date & Time' }}</th>
                            <th>Priority</th>
                            <th>Assigned HR</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reminders as $item)
                            @php
                                $isOverdue = $item->is_overdue;
                                $priorityClass = [
                                    'high'   => 'rec-rem-badge-high',
                                    'medium' => 'rec-rem-badge-medium',
                                    'low'    => 'rec-rem-badge-low',
                                ][$item->priority] ?? 'rec-rem-badge-medium';
                                
                                $typeClass = 'rec-rem-type-' . $item->type;
                            @endphp
                            <tr>
                                <td>
                                    <span class="rec-rem-type-pill {{ $typeClass }}">
                                        <span>{{ $item->type_icon }}</span>
                                        <span>{{ $item->type_label }}</span>
                                    </span>
                                </td>
                                <td>
                                    <div class="rec-rem-title-text">{{ $item->title }}</div>
                                    @if($item->description)
                                        <div class="rec-rem-desc-text">{{ Str::limit($item->description, 100) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($item->candidate)
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:3px;">
                                            <a href="{{ route('recruitment.show', $item->candidate) }}" class="rec-rem-cand-id">
                                                #{{ $item->candidate->candidate_no ?: $item->candidate->id }}
                                            </a>
                                            <a href="{{ route('recruitment.show', $item->candidate) }}" style="text-decoration:none;" class="rec-rem-cand-name">
                                                {{ $item->candidate->name }}
                                            </a>
                                        </div>
                                        <div class="rec-rem-contact-item">
                                            <span>📞</span>
                                            <a href="tel:{{ $item->candidate->mobile_number }}">{{ $item->candidate->mobile_number }}</a>
                                            @if($item->candidate->job_title)
                                                <span style="color:#cbd5e1;">•</span>
                                                <span class="rec-rem-cand-job">{{ $item->candidate->job_title }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color:#9e9e9e;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($activeTab === 'completed' || $item->is_completed)
                                        <div style="font-weight:750; font-size:12px; color:#16a34a;">
                                            ✅ {{ $item->completed_at ? $item->completed_at->format('d M Y, h:i A') : 'Completed' }}
                                        </div>
                                        <div style="font-size:11px; color:#7c7c7c; margin-top:2px;">
                                            📅 Scheduled: {{ $item->remind_at ? $item->remind_at->format('d M Y, h:i A') : 'N/A' }}
                                        </div>
                                    @else
                                        <div style="font-weight:750; font-size:12.5px; color:{{ $isOverdue ? '#dc2626' : '#121212' }}">
                                            📅 {{ $item->remind_at ? $item->remind_at->format('d M Y') : 'N/A' }}
                                        </div>
                                        <div style="font-size:11px; color:{{ $isOverdue ? '#dc2626' : '#7c7c7c' }}; font-weight:{{ $isOverdue ? '700' : '500' }}; margin-top:2px;">
                                            ⏰ {{ $item->remind_at ? $item->remind_at->format('h:i A') : 'N/A' }}
                                            @if($isOverdue)
                                                <span class="badge" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:9.5px; padding:2px 5px; border-radius:4px; margin-left:4px;">Overdue</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="rec-rem-badge {{ $priorityClass }}">
                                        {{ ucfirst($item->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700; font-size:12.5px; color:#121212;">{{ $item->user?->name ?? '—' }}</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                        @if(!$item->is_completed)
                                            <form method="POST" action="{{ route('recruitment.reminders.complete', $item) }}" onsubmit="return confirm('Mark this reminder as completed?');" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rec-rem-done-btn">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                                    <span>Done</span>
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-size:11px; font-weight:700; color:#16a34a; background:#f0fdf4; border:1px solid #bbf7d0; padding:4px 8px; border-radius:6px;">Done</span>
                                            <form method="POST" action="{{ route('recruitment.reminders.incomplete', $item) }}" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rec-rem-reopen-btn" title="Reopen reminder">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                                                </button>
                                            </form>
                                        @endif

                                        @if($item->candidate)
                                            <a href="{{ route('recruitment.show', $item->candidate) }}" class="rec-rem-btn rec-rem-btn-ghost" style="padding:6px 10px; font-size:11px;" title="View Candidate Profile">
                                                Profile
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:50px 14px; color:#7c7c7c;">
                                    <div style="font-size:36px; margin-bottom:8px;">
                                        @if($activeTab === 'overdue')
                                            🎉
                                        @elseif($activeTab === 'completed')
                                            📋
                                        @else
                                            ✅
                                        @endif
                                    </div>
                                    <div style="font-weight:800; font-size:15px; color:#121212;">
                                        @if($activeTab === 'overdue')
                                            No Overdue Follow-ups!
                                        @elseif($activeTab === 'completed')
                                            No Completed Reminders Found
                                        @else
                                            No Follow-ups Planned For Today
                                        @endif
                                    </div>
                                    <div style="font-size:12px; color:#9e9e9e; margin-top:4px;">
                                        All caught up! Next follow-up dates given during calls will automatically show here.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($reminders->total() > 0)
                @php
                    $startPage = max(1, $reminders->currentPage() - 2);
                    $endPage   = min($reminders->lastPage(), $reminders->currentPage() + 2);
                    $pageName  = $reminders->getPageName();
                    
                    $allParams = collect(request()->all())
                        ->except(['_token', '_method'])
                        ->map(fn($val) => $val ?? '')
                        ->toArray();

                    $getPageUrl = function($page) use ($allParams, $pageName) {
                        $params = array_merge($allParams, [$pageName => $page]);
                        return url()->current() . '?' . http_build_query($params);
                    };
                @endphp

                <div class="rec-rem-pagination-wrap">
                    <div class="rec-rem-pagination-info-badge">
                        Showing &nbsp;<strong>{{ $reminders->firstItem() ?? 0 }} - {{ $reminders->lastItem() ?? 0 }}</strong>&nbsp; of &nbsp;<strong>{{ $reminders->total() }}</strong>&nbsp; reminders
                    </div>

                    @if($reminders->hasPages())
                        <div class="rec-rem-pagination-links">
                            @if($reminders->onFirstPage())
                                <span class="rec-rem-page-link is-disabled">Prev</span>
                            @else
                                <a href="{{ $getPageUrl($reminders->currentPage() - 1) }}" class="rec-rem-page-link">Prev</a>
                            @endif

                            @if($startPage > 1)
                                <a href="{{ $getPageUrl(1) }}" class="rec-rem-page-link">1</a>
                                @if($startPage > 2)
                                    <span class="rec-rem-page-ellipsis">...</span>
                                @endif
                            @endif

                            @foreach(range($startPage, $endPage) as $pg)
                                <a href="{{ $getPageUrl($pg) }}"
                                   class="rec-rem-page-link {{ $pg === $reminders->currentPage() ? 'is-active' : '' }}">
                                    {{ $pg }}
                                </a>
                            @endforeach

                            @if($endPage < $reminders->lastPage())
                                @if($endPage < $reminders->lastPage() - 1)
                                    <span class="rec-rem-page-ellipsis">...</span>
                                @endif
                                <a href="{{ $getPageUrl($reminders->lastPage()) }}" class="rec-rem-page-link">{{ $reminders->lastPage() }}</a>
                            @endif

                            @if($reminders->hasMorePages())
                                <a href="{{ $getPageUrl($reminders->currentPage() + 1) }}" class="rec-rem-page-link">Next</a>
                            @else
                                <span class="rec-rem-page-link is-disabled">Next</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
