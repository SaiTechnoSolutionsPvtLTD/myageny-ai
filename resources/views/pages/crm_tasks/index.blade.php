@extends('layouts.app')

@section('title', 'Tasks & Reminders')

@push('styles')
<style>
.task-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; }
.task-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.task-title { font-size:18px; font-weight:800; color:#121212; }
.task-crumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.task-crumb a { color:#fe5f04; text-decoration:none; font-weight:700; }
.task-body { padding:18px 28px 28px; display:flex; flex-direction:column; gap:14px; }
.task-filter-card, .task-table-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; box-shadow:0 10px 24px rgba(18,18,18,.04); overflow:hidden; }
.task-filter-head, .task-table-head { padding:14px 18px; border-bottom:1px solid #f1eef2; background:linear-gradient(180deg,#fffaf7 0%, #fff 100%); }
.task-head-title { font-size:14px; font-weight:800; color:#121212; }
.task-head-sub { font-size:11px; color:#9e9e9e; margin-top:3px; }
.task-filter-body { padding:18px; }
.task-row { display:grid; grid-template-columns:1.5fr 1fr 1fr auto; gap:12px; align-items:end; }
.task-group { display:flex; flex-direction:column; gap:6px; }
.task-label { font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.4px; }
.task-input, .task-select {
    width:100%; padding:10px 12px; border:1px solid #e1dee3; border-radius:10px; background:#faf7f4; color:#121212;
    font-size:13px; font-family:inherit; outline:none; transition:all .15s;
}
.task-input:focus, .task-select:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
.task-actions { display:flex; align-items:center; gap:8px; }
.task-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 14px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; border:none; cursor:pointer; font-family:inherit; transition:all .15s; white-space:nowrap; }
.task-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 6px 16px rgba(254,95,4,.25); }
.task-btn-primary:hover { transform:translateY(-1px); }
.task-btn-ghost { background:#fff; color:#7c7c7c; border:1px solid #e1dee3; }
.task-btn-ghost:hover { border-color:#fe5f04; color:#fe5f04; }

/* Tabs */
.task-tabs-row { display:flex; align-items:center; gap:10px; padding:12px 18px; background:#fcfbfe; border-bottom:1px solid #e1dee3; }
.task-tab { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:12px; font-size:13px; font-weight:700; color:#7c7c7c; text-decoration:none; background:transparent; border:1px solid transparent; transition:all .15s; cursor:pointer; }
.task-tab:hover { background:#f5f2f7; color:#121212; }
.task-tab.active-today { background:#fe5f04; color:#fff; border-color:#fe5f04; box-shadow:0 4px 12px rgba(254,95,4,.2); }
.task-tab.active-overdue { background:#dc2626; color:#fff; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }
.task-tab.active-completed { background:#16a34a; color:#fff; border-color:#16a34a; box-shadow:0 4px 12px rgba(22,163,74,.2); }
.task-tab-badge { display:inline-flex; align-items:center; justify-content:center; padding:2px 7px; border-radius:999px; font-size:11px; font-weight:800; background:rgba(0,0,0,.08); }
.task-tab.active-today .task-tab-badge,
.task-tab.active-overdue .task-tab-badge,
.task-tab.active-completed .task-tab-badge { background:rgba(255,255,255,.25); color:#fff; }

/* Table */
.task-table-wrap { overflow-x:auto; }
.task-table { width:100%; border-collapse:collapse; }
.task-table th { padding:11px 14px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:#9e9e9e; text-align:left; background:#fafafa; border-bottom:1px solid #ece7eb; }
.task-table td { padding:12px 14px; font-size:13px; color:#121212; border-bottom:1px solid #f5f2f5; vertical-align:middle; }
.task-table tr:hover td { background:#fffcf9; }
.task-lead-link { font-weight:700; color:#fe5f04; text-decoration:none; }
.task-lead-link:hover { text-decoration:underline; }
.task-title-text { font-weight:700; color:#121212; font-size:13px; }
.task-desc-text { font-size:12px; color:#7c7c7c; margin-top:2px; }
.task-done-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; background:#16a34a; color:#fff; font-size:12px; font-weight:700; border:none; cursor:pointer; text-decoration:none; transition:all .15s; }
.task-done-btn:hover { background:#15803d; transform:translateY(-1px); }
.task-reopen-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; background:#f3f4f6; color:#4b5563; font-size:12px; font-weight:700; border:1px solid #d1d5db; cursor:pointer; text-decoration:none; transition:all .15s; }
.task-reopen-btn:hover { background:#e5e7eb; color:#111827; }
.task-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.task-badge-high { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.task-badge-medium { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.task-badge-low { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
</style>
@endpush

@section('content')
<div class="task-page">
    <div class="task-topbar">
        <div>
            <div class="task-title">Tasks & Reminders</div>
            <div class="task-crumb">
                <a href="{{ url('/') }}">Dashboard</a> &nbsp;/&nbsp; <span>Tasks</span>
            </div>
        </div>
    </div>

    <div class="task-body">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-0" role="alert" style="border-radius:12px; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Filter Card --}}
        <div class="task-filter-card">
            <div class="task-filter-head">
                <div class="task-head-title">Filter Tasks & Reminders</div>
                <div class="task-head-sub">Filter tasks by search keywords, assigned team members, or branch.</div>
            </div>
            <div class="task-filter-body">
                <form method="GET" action="{{ route('tasks.index') }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="task-row">
                        <div class="task-group">
                            <label class="task-label">Search</label>
                            <input type="text" name="search" class="task-input" value="{{ request('search') }}" placeholder="Search title, remarks, lead name, phone...">
                        </div>
                        <div class="task-group">
                            <label class="task-label">Assigned User</label>
                            <select name="user_id" class="task-select">
                                <option value="">All Team Members</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="task-group">
                            <label class="task-label">Branch</label>
                            <select name="branch_id" class="task-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="task-actions">
                            <button type="submit" class="task-btn task-btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            @if(request()->anyFilled(['search', 'user_id', 'branch_id']))
                                <a href="{{ route('tasks.index', ['tab' => $activeTab]) }}" class="task-btn task-btn-ghost">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="task-table-card">
            {{-- Tabs --}}
            <div class="task-tabs-row">
                <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['tab' => 'today'])) }}"
                   class="task-tab {{ $activeTab === 'today' ? 'active-today' : '' }}">
                    <span>📅 Today Planned</span>
                    <span class="task-tab-badge">{{ $todayCount }}</span>
                </a>
                <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['tab' => 'overdue'])) }}"
                   class="task-tab {{ $activeTab === 'overdue' ? 'active-overdue' : '' }}">
                    <span>⚠️ Overdue</span>
                    <span class="task-tab-badge">{{ $overdueCount }}</span>
                </a>
                <a href="{{ route('tasks.index', array_merge(request()->except('page'), ['tab' => 'completed'])) }}"
                   class="task-tab {{ $activeTab === 'completed' ? 'active-completed' : '' }}">
                    <span>✅ Completed</span>
                    <span class="task-tab-badge">{{ $completedCount }}</span>
                </a>
            </div>

            {{-- Table --}}
            <div class="task-table-wrap">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">Type</th>
                            <th>Task / Reminder Title</th>
                            <th>Lead Details</th>
                            <th>{{ $activeTab === 'completed' ? 'Completed & Scheduled Date' : 'Scheduled Date & Time' }}</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $task)
                            @php
                                $isOverdue = $task->is_overdue;
                                $priorityClass = [
                                    'high'   => 'task-badge-high',
                                    'medium' => 'task-badge-medium',
                                    'low'    => 'task-badge-low',
                                ][$task->priority] ?? 'task-badge-medium';
                            @endphp
                            <tr>
                                <td>
                                    <span style="font-size:18px;">{{ $task->type_icon }}</span>
                                </td>
                                <td>
                                    <div class="task-title-text">{{ $task->title }}</div>
                                    @if($task->description)
                                        <div class="task-desc-text">{{ Str::limit($task->description, 90) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($task->lead)
                                        <a href="{{ route('leads.show', $task->lead) }}" class="task-lead-link">
                                            {{ $task->lead->company_name ?: $task->lead->contact_name }}
                                        </a>
                                        <div style="font-size:11px; color:#7c7c7c;">
                                            {{ $task->lead->contact_name }} ({{ $task->lead->mobile_number }})
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($activeTab === 'completed' || $task->is_completed)
                                        <div style="font-weight:700; font-size:12px; color:#16a34a;">
                                            ✅ {{ $task->completed_at ? $task->completed_at->format('d M Y, h:i A') : ($task->updated_at ? $task->updated_at->format('d M Y, h:i A') : 'Completed') }}
                                        </div>
                                        <div style="font-size:11px; color:#7c7c7c; margin-top:2px;">
                                            📅 Scheduled: {{ $task->remind_at ? $task->remind_at->format('d M Y') : 'N/A' }} {{ $task->remainder_time ? $task->remainder_time->format('h:i A') : '' }}
                                        </div>
                                    @else
                                        <div style="font-weight:700; font-size:12px; color:{{ $isOverdue ? '#dc2626' : '#121212' }}">
                                            📅 {{ $task->remind_at ? $task->remind_at->format('d M Y') : 'N/A' }}
                                        </div>
                                        <div style="font-size:11px; color:#7c7c7c;">
                                            ⏰ {{ $task->remainder_time ? $task->remainder_time->format('h:i A') : '10:00 AM' }}
                                            @if($isOverdue)
                                                <span class="badge bg-danger ms-1" style="font-size:9px;">Overdue</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="task-badge {{ $priorityClass }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:600; font-size:12px;">{{ $task->user?->name ?? '—' }}</div>
                                </td>
                                <td style="text-align:right;">
                                    @if(!$task->is_completed)
                                        <form method="POST" action="{{ route('tasks.complete', $task) }}" onsubmit="return confirm('Are you sure you want to mark this task as completed?');" style="display:inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="task-done-btn">
                                                <i class="fas fa-check"></i> Done
                                            </button>
                                        </form>
                                    @else
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <span class="badge bg-success" style="font-size:11px; padding:5px 8px;">Completed</span>
                                            <form method="POST" action="{{ route('tasks.incomplete', $task) }}" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="task-reopen-btn" title="Reopen Task">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px 14px; color:#7c7c7c;">
                                    <div style="font-size:32px; margin-bottom:8px;">
                                        @if($activeTab === 'overdue')
                                            🎉
                                        @elseif($activeTab === 'completed')
                                            📋
                                        @else
                                            ✅
                                        @endif
                                    </div>
                                    <div style="font-weight:700; font-size:14px; color:#121212;">
                                        @if($activeTab === 'overdue')
                                            No Overdue Tasks!
                                        @elseif($activeTab === 'completed')
                                            No Completed Tasks Found
                                        @else
                                            No Tasks Planned For Today
                                        @endif
                                    </div>
                                    <div style="font-size:12px; color:#9e9e9e; margin-top:4px;">
                                        All caught up or try adjusting your search filters.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tasks->hasPages())
                <div style="padding:14px 18px; border-top:1px solid #ece7eb;">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
