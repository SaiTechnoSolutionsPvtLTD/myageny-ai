@extends('layouts.app')

@section('title', 'Activity Logs — Settings — myAgenci.ai')

@push('styles')
<style>
.al-page {
    min-height: 100%;
    padding: 24px 28px 40px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}
.al-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
}
.al-title-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.al-breadcrumb {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}
.al-breadcrumb a {
    color: #64748b;
    text-decoration: none;
    transition: color .15s;
}
.al-breadcrumb a:hover {
    color: #0f172a;
}
.al-title {
    font-size: 24px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.al-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}
.al-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all .16s ease;
    border: 1px solid transparent;
}
.al-btn-primary {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: #fff;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
}
.al-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
    color: #fff;
}
.al-btn-secondary {
    background: #fff;
    border-color: #e2e8f0;
    color: #334155;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
}
.al-btn-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}

/* Stats Cards */
.al-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.al-stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
    transition: transform .15s, box-shadow .15s;
}
.al-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
.al-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.al-stat-icon.blue { background: #e0f2fe; color: #0284c7; }
.al-stat-icon.green { background: #dcfce7; color: #16a34a; }
.al-stat-icon.purple { background: #f3e8ff; color: #9333ea; }
.al-stat-icon.orange { background: #ffedd5; color: #ea580c; }
.al-stat-info { display: flex; flex-direction: column; }
.al-stat-value { font-size: 24px; font-weight: 900; color: #0f172a; line-height: 1.1; }
.al-stat-label { font-size: 12px; font-weight: 700; color: #64748b; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em; }

/* Filter Section */
.al-filter-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
}
.al-filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 14px;
    align-items: flex-end;
}
.al-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.al-filter-label {
    font-size: 11px;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.al-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13px;
    color: #0f172a;
    background: #fff;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.al-input:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}

/* Table Card */
.al-table-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.04);
}
.al-table-head {
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}
.al-table-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}
.al-table-count {
    padding: 3px 10px;
    border-radius: 999px;
    background: #e2e8f0;
    color: #334155;
    font-size: 11px;
    font-weight: 800;
}
.al-table-wrap {
    overflow-x: auto;
}
.al-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.al-table th {
    padding: 13px 18px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}
.al-table td {
    padding: 14px 18px;
    font-size: 13px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.al-table tbody tr:hover td {
    background: #f8fafc;
}

/* User avatar */
.al-user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.al-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #4338ca;
    font-weight: 800;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.al-user-name {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.al-user-email {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

/* Badges */
.al-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 9px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}
.badge-login { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-logout { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.badge-create { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.badge-update { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-delete { background: #ffe4e6; color: #e11d48; border: 1px solid #fecdd3; }
.badge-status { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
.badge-approve { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.badge-reject { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.badge-general { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

.al-module-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
}
.al-method-pill {
    font-family: monospace;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    background: #e2e8f0;
    color: #334155;
}
.al-lead-link {
    font-weight: 700;
    color: #0284c7;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.al-lead-link:hover {
    text-decoration: underline;
}
.al-action-btn {
    padding: 6px 12px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
}
.al-action-btn:hover {
    background: #0284c7;
    border-color: #0284c7;
    color: #fff;
}

/* Modal */
.al-modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 16px;
}
.al-modal.is-open {
    display: flex;
}
.al-modal-card {
    width: min(100%, 650px);
    max-height: 90vh;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
    display: flex;
    flex-direction: column;
}
.al-modal-head {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
}
.al-modal-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
}
.al-modal-close {
    border: none;
    background: #e2e8f0;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
}
.al-modal-body {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.al-prop-box {
    background: #0f172a;
    color: #38bdf8;
    padding: 16px;
    border-radius: 12px;
    font-family: monospace;
    font-size: 12px;
    overflow-x: auto;
    white-space: pre-wrap;
    line-height: 1.5;
}
.al-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.al-meta-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
}
.al-meta-label {
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
}
.al-meta-val {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    margin-top: 4px;
    word-break: break-all;
}

@media (max-width: 1024px) {
    .al-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .al-stats-grid { grid-template-columns: 1fr; }
    .al-topbar { flex-direction: column; align-items: flex-start; }
    .al-meta-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="al-page">

    {{-- Topbar --}}
    <div class="al-topbar">
        <div class="al-title-wrap">
            <div class="al-breadcrumb">
                <a href="{{ route('settings.index') }}">Settings</a>
                <span>/</span>
                <span>Activity Logs</span>
            </div>
            <h1 class="al-title">
                <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                System &amp; User Activity Logs
            </h1>
        </div>

        <div class="al-header-actions">
            <a href="{{ route('settings.activity-logs.export', request()->query()) }}" class="al-btn al-btn-secondary" title="Export current filtered logs to CSV">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </a>
            <a href="{{ route('settings.index') }}" class="al-btn al-btn-secondary">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Settings
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="al-stats-grid">
        <div class="al-stat-card">
            <div class="al-stat-icon blue">📊</div>
            <div class="al-stat-info">
                <span class="al-stat-value">{{ number_format($totalActivities) }}</span>
                <span class="al-stat-label">Total Activities</span>
            </div>
        </div>
        <div class="al-stat-card">
            <div class="al-stat-icon green">👥</div>
            <div class="al-stat-info">
                <span class="al-stat-value">{{ number_format($activeUsersCount) }}</span>
                <span class="al-stat-label">Active Users Today</span>
            </div>
        </div>
        <div class="al-stat-card">
            <div class="al-stat-icon purple">🎯</div>
            <div class="al-stat-info">
                <span class="al-stat-value">{{ number_format($leadActivitiesCount) }}</span>
                <span class="al-stat-label">Lead Activities</span>
            </div>
        </div>
        <div class="al-stat-card">
            <div class="al-stat-icon orange">⚡</div>
            <div class="al-stat-info">
                <span class="al-stat-value">{{ number_format($todayActionsCount) }}</span>
                <span class="al-stat-label">Today's Actions</span>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="al-filter-card">
        <form method="GET" action="{{ route('settings.activity-logs.index') }}" class="al-filter-form">
            <div class="al-filter-group">
                <label class="al-filter-label">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="al-input">
            </div>

            <div class="al-filter-group">
                <label class="al-filter-label">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="al-input">
            </div>

            <div class="al-filter-group">
                <label class="al-filter-label">User</label>
                <select name="user_id" class="al-input select2">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="al-filter-group">
                <label class="al-filter-label">Lead ID / Company</label>
                <input type="text" name="lead_id" value="{{ request('lead_id') }}" placeholder="e.g. 30988" class="al-input">
            </div>

            <div class="al-filter-group">
                <label class="al-filter-label">Module</label>
                <select name="module" class="al-input">
                    <option value="">All Modules</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" {{ request('module') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="al-filter-group">
                <label class="al-filter-label">Action</label>
                <select name="action" class="al-input">
                    <option value="">All Actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="al-filter-group" style="grid-column: span 2;">
                <label class="al-filter-label">Search Keyword</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description, IP, user name..." class="al-input">
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: flex-end; margin-top: 4px;">
                <button type="submit" class="al-btn al-btn-primary" style="padding: 9px 18px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Filter
                </button>
                <a href="{{ route('settings.activity-logs.index') }}" class="al-btn al-btn-secondary" style="padding: 9px 14px;">Reset</a>
            </div>
        </form>
    </div>

    {{-- Activity Table --}}
    <div class="al-table-card">
        <div class="al-table-head">
            <div class="al-table-title">
                <span>Recent Activity Trail</span>
                <span class="al-table-count">{{ $logs->total() }} Records</span>
            </div>
            <div style="font-size: 12px; color: #64748b; font-weight: 600;">
                Showing Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
            </div>
        </div>

        <div class="al-table-wrap">
            <table class="al-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>User</th>
                        <th>Lead / Account</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP / Method</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $initials = collect(explode(' ', $log->user_name ?: ($log->user?->name ?: 'SYS')))
                                ->map(fn($part) => substr($part, 0, 1))
                                ->take(2)
                                ->implode('');
                            $badgeClass = $log->getActionBadgeClass();
                        @endphp
                        <tr>
                            <td style="white-space: nowrap;">
                                <div style="font-weight: 700; color: #0f172a;">{{ $log->created_at->format('d M Y') }}</div>
                                <div style="font-size: 11px; color: #64748b;">{{ $log->created_at->format('h:i:s A') }}</div>
                            </td>
                            <td>
                                <div class="al-user-cell">
                                    <div class="al-avatar">{{ strtoupper($initials) }}</div>
                                    <div>
                                        <div class="al-user-name">{{ $log->user_name ?: ($log->user?->name ?: 'System') }}</div>
                                        <div class="al-user-email">{{ $log->user_email ?: ($log->user?->email ?: '') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($log->lead_id)
                                    <div>
                                        <a href="{{ route('leads.show', $log->lead_id) }}" target="_blank" class="al-lead-link">
                                            LD-{{ str_pad($log->lead_id, 4, '0', STR_PAD_LEFT) }}
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $log->lead_title ?: ($log->lead?->company_name ?: '') }}</div>
                                    </div>
                                @else
                                    <span style="color: #94a3b8; font-size: 12px;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="al-module-pill">{{ $log->module }}</span>
                            </td>
                            <td>
                                <span class="al-badge {{ $badgeClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #1e293b; max-width: 320px; line-height: 1.4;">
                                    {{ $log->description }}
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 12px; font-weight: 700; color: #475569;">{{ $log->ip_address ?: '—' }}</div>
                                <div style="margin-top: 2px;">
                                    <span class="al-method-pill">{{ $log->method ?: 'GET' }}</span>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="al-action-btn" onclick="openLogDetails({{ $log->id }})">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 48px; color: #64748b;">
                                <div style="font-size: 32px; margin-bottom: 8px;">📋</div>
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a;">No activity logs found</div>
                                <div style="font-size: 13px; margin-top: 4px;">Try adjusting your search or date filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #fff;">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Activity Details Modal --}}
<div class="al-modal" id="alDetailsModal">
    <div class="al-modal-card">
        <div class="al-modal-head">
            <div class="al-modal-title">Activity Details #<span id="mdlLogId"></span></div>
            <button type="button" class="al-modal-close" onclick="closeLogModal()">&times;</button>
        </div>
        <div class="al-modal-body">
            <div class="al-meta-grid">
                <div class="al-meta-item">
                    <div class="al-meta-label">User</div>
                    <div class="al-meta-val" id="mdlUser">—</div>
                </div>
                <div class="al-meta-item">
                    <div class="al-meta-label">Time &amp; Date</div>
                    <div class="al-meta-val" id="mdlTime">—</div>
                </div>
                <div class="al-meta-item">
                    <div class="al-meta-label">Module &amp; Action</div>
                    <div class="al-meta-val" id="mdlModuleAction">—</div>
                </div>
                <div class="al-meta-item">
                    <div class="al-meta-label">IP Address</div>
                    <div class="al-meta-val" id="mdlIp">—</div>
                </div>
            </div>

            <div class="al-meta-item">
                <div class="al-meta-label">Description</div>
                <div class="al-meta-val" id="mdlDesc" style="font-size: 14px; margin-top: 6px;">—</div>
            </div>

            <div class="al-meta-item">
                <div class="al-meta-label">Request URL &amp; Method</div>
                <div class="al-meta-val" id="mdlUrl" style="font-family: monospace; font-size: 12px; color: #0284c7;">—</div>
            </div>

            <div class="al-meta-item">
                <div class="al-meta-label">User Agent (Browser / Device)</div>
                <div class="al-meta-val" id="mdlUserAgent" style="font-size: 12px; color: #64748b; font-weight: normal;">—</div>
            </div>

            <div id="mdlPropertiesWrap" style="display: none;">
                <div class="al-meta-label" style="margin-bottom: 8px;">Action Properties &amp; Payload</div>
                <pre class="al-prop-box" id="mdlProperties"></pre>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openLogDetails(id) {
    fetch('{{ url("settings/activity-logs") }}/' + id, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.status) return;
        const log = data.log;

        document.getElementById('mdlLogId').innerText = log.id;
        document.getElementById('mdlUser').innerText = log.user_name + ' (' + (log.user_email || 'No email') + ')';
        document.getElementById('mdlTime').innerText = log.created_at + ' (' + log.time_ago + ')';
        document.getElementById('mdlModuleAction').innerText = log.module + ' — ' + log.action;
        document.getElementById('mdlIp').innerText = log.ip_address || '—';
        document.getElementById('mdlDesc').innerText = log.description;
        document.getElementById('mdlUrl').innerText = '[' + log.method + '] ' + (log.url || '—');
        document.getElementById('mdlUserAgent').innerText = log.user_agent || '—';

        const propWrap = document.getElementById('mdlPropertiesWrap');
        const propBox = document.getElementById('mdlProperties');
        if (log.properties && Object.keys(log.properties).length > 0) {
            propBox.innerText = JSON.stringify(log.properties, null, 2);
            propWrap.style.display = 'block';
        } else {
            propWrap.style.display = 'none';
        }

        document.getElementById('alDetailsModal').classList.add('is-open');
    })
    .catch(err => {
        console.error('Error fetching log details:', err);
    });
}

function closeLogModal() {
    document.getElementById('alDetailsModal').classList.remove('is-open');
}

// Close on backdrop click
document.getElementById('alDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogModal();
    }
});
</script>
@endpush
@endsection
