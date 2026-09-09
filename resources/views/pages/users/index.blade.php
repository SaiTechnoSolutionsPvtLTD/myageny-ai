@extends('layouts.app')

@section('title', 'User Management')

@push('styles')
<style>
/* ══════════════════════════════════════════════
   USER INDEX — Clean management table
   Theme: White cards, structured data, orange CTA
══════════════════════════════════════════════ */
.usr-page { display:flex; flex-direction:column; height:100%; overflow:hidden; background:#f4f5f7; font-family:'Inter',sans-serif; }

/* ── Topbar ── */
.usr-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; flex-shrink:0; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.usr-page-title { font-size:18px; font-weight:800; color:#121212; }
.usr-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.usr-breadcrumb span { color:#121212; font-weight:600; }
.usr-topbar-right { display:flex; align-items:center; gap:10px; }
.usr-btn { display:flex; align-items:center; gap:6px; padding:8px 18px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; transition:all .15s; border:none; }
.usr-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.usr-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); }
.usr-btn-outline { background:#fff; color:#121212; border:1px solid #e1dee3; }
.usr-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

/* ── Filter Bar ── */
.usr-filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 28px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:60px; z-index:25; }
.usr-search-wrap { position:relative; flex:1; min-width:220px; max-width:320px; }
.usr-search-ico { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#9e9e9e; width:14px; height:14px; pointer-events:none; }
.usr-search-input { width:100%; padding:8px 12px 8px 34px; border:1px solid #e1dee3; border-radius:9px; font-size:13px; font-family:inherit; outline:none; background:#f8f8f8; color:#121212; transition:all .15s; }
.usr-search-input:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.usr-filter-wrap { position:relative; }
.usr-filter-ico { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#9e9e9e; pointer-events:none; width:12px; height:12px; }
.usr-filter-select { appearance:none; -webkit-appearance:none; padding:7px 28px 7px 28px; background:#f8f8f8; border:1px solid #e1dee3; border-radius:9px; font-size:12px; font-weight:600; color:#2e2e2e; cursor:pointer; outline:none; transition:all .15s; font-family:inherit; min-width:130px; }
.usr-filter-select:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.usr-filter-caret { position:absolute; right:8px; top:50%; transform:translateY(-50%); pointer-events:none; color:#9e9e9e; width:11px; height:11px; }
.usr-filter-reset { display:flex; align-items:center; gap:5px; padding:7px 12px; border-radius:9px; background:none; border:1px solid #e1dee3; font-size:12px; font-weight:600; color:#9e9e9e; cursor:pointer; font-family:inherit; transition:all .15s; }
.usr-filter-reset:hover { border-color:#dc2626; color:#dc2626; }

/* ── Body ── */
.usr-body { flex:1; overflow-y:auto; padding:20px 28px 32px; display:flex; flex-direction:column; gap:16px; }
.usr-body::-webkit-scrollbar { width:5px; }
.usr-body::-webkit-scrollbar-thumb { background:#e1dee3; border-radius:3px; }

@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* ── Stats Row ── */
.usr-stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; animation:fadeUp .35s ease both; }
.usr-stat { background:#fff; border:1px solid #e1dee3; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; transition:box-shadow .2s; }
.usr-stat:hover { box-shadow:0 4px 18px rgba(0,0,0,.06); }
.usr-stat-icon { width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.usr-stat-label { font-size:11px; font-weight:700; color:#9e9e9e; text-transform:uppercase; letter-spacing:.5px; }
.usr-stat-value { font-size:24px; font-weight:800; color:#121212; line-height:1; margin-top:4px; }

/* ── Table Card ── */
.usr-table-card { background:#fff; border:1px solid #e1dee3; border-radius:14px; overflow:hidden; animation:fadeUp .35s .1s ease both; }
.usr-table-head-row { display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-bottom:1px solid #f0eef2; }
.usr-table-title { font-size:15px; font-weight:700; color:#121212; }
.usr-table-sub { font-size:11px; color:#9e9e9e; margin-top:2px; }
.usr-results-count { font-size:12px; color:#9e9e9e; }
.usr-results-count strong { color:#121212; font-weight:700; }

/* Table */
.usr-tbl { width:100%; border-collapse:collapse; }
.usr-tbl th { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.7px; color:#9e9e9e; padding:10px 16px; text-align:left; border-bottom:1px solid #f0eef2; background:#fafafa; white-space:nowrap; }
.usr-tbl td { padding:13px 16px; font-size:13px; color:#121212; border-bottom:1px solid #f7f6f9; vertical-align:middle; }
.usr-tbl tbody tr:last-child td { border-bottom:none; }
.usr-tbl tbody tr:hover td { background:#fdf9f6; }

/* User cell */
.usr-cell { display:flex; align-items:center; gap:11px; }
.usr-avatar { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:800; color:#fff; flex-shrink:0; }
.usr-avatar img { width:100%; height:100%; object-fit:cover; border-radius:10px; }
.usr-name { font-size:13px; font-weight:700; color:#121212; }
.usr-email { font-size:11px; color:#9e9e9e; margin-top:1px; }
.usr-phone { font-size:12px; color:#7c7c7c; }

/* Badges */
.usr-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.ub-active   { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.ub-inactive { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.ub-admin    { background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; }
.ub-super    { background:linear-gradient(135deg,#fef3c7,#fff7ed); color:#92400e; border:1px solid #fde68a; }
.ub-bde      { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.ub-tl       { background:#f0fdf4; color:#15803d; border:1px solid #86efac; }
.ub-hr       { background:#faf5ff; color:#7c3aed; border:1px solid #e9d5ff; }
.ub-acc      { background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4; }
.ub-default  { background:#f5f4f6; color:#7c7c7c; border:1px solid #e1dee3; }

/* Branch pill */
.usr-branch { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; color:#7c7c7c; }
.usr-branch-dot { width:6px; height:6px; border-radius:50%; background:#e1dee3; flex-shrink:0; }

/* Actions */
.usr-actions { display:flex; align-items:center; gap:6px; }
.usr-action-btn { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; border:1px solid #e1dee3; background:#fafafa; cursor:pointer; color:#9e9e9e; transition:all .15s; text-decoration:none; }
.usr-action-btn:hover { border-color:#2563eb; color:#2563eb; background:#eff6ff; }
.usr-action-btn.edit:hover { border-color:#fe5f04; color:#fe5f04; background:#fff7ed; }
.usr-action-btn.del:hover  { border-color:#dc2626; color:#dc2626; background:#fef2f2; }
.usr-action-btn.toggle:hover { border-color:#16a34a; color:#16a34a; background:#f0fdf4; }

/* Toggle switch in table — large, labeled, highlighted */
.usr-toggle-wrap { display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none; }
.usr-toggle { position:relative; width:52px; height:28px; flex-shrink:0; }
.usr-toggle input { display:none; }
.usr-toggle-slider {
    position:absolute; inset:0;
    border-radius:999px;
    background:#e1dee3;
    cursor:pointer;
    transition:background .25s, box-shadow .25s;
    border:2px solid transparent;
}
.usr-toggle-slider::before {
    content:'';
    position:absolute;
    width:20px; height:20px;
    border-radius:50%;
    background:#fff;
    top:2px; left:2px;
    transition:transform .25s cubic-bezier(.4,0,.2,1);
    box-shadow:0 2px 6px rgba(0,0,0,.22);
}
.usr-toggle input:checked + .usr-toggle-slider {
    background:#16a34a;
    border-color:#15803d;
    box-shadow:0 0 0 3px rgba(22,163,74,.18), 0 2px 8px rgba(22,163,74,.22);
}
.usr-toggle input:not(:checked) + .usr-toggle-slider {
    background:#f1f1f4;
    border-color:#d1d0d5;
}
.usr-toggle input:checked + .usr-toggle-slider::before { transform:translateX(24px); }
.usr-toggle-label {
    font-size:12px;
    font-weight:700;
    min-width:46px;
}
.usr-toggle-label.is-active { color:#16a34a; }
.usr-toggle-label.is-inactive { color:#dc2626; }

/* Pagination */
.usr-pagination { display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-top:1px solid #f0eef2; }
.usr-page-info { font-size:12px; color:#9e9e9e; }
.usr-page-links { display:flex; gap:4px; }
.usr-page-link { padding:5px 10px; border-radius:7px; font-size:12px; font-weight:600; text-decoration:none; color:#7c7c7c; border:1px solid #e1dee3; transition:all .15s; }
.usr-page-link:hover, .usr-page-link.active { background:#fe5f04; color:#fff; border-color:#fe5f04; }

/* Alert */
.usr-alert { padding:12px 16px; border-radius:10px; font-size:13px; display:flex; align-items:center; gap:10px; animation:fadeUp .3s ease; }
.usr-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.usr-alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }

/* Empty state */
.usr-empty { text-align:center; padding:60px 20px; color:#9e9e9e; }
.usr-empty-icon { font-size:48px; margin-bottom:12px; }
.usr-empty-title { font-size:16px; font-weight:700; color:#7c7c7c; margin-bottom:6px; }
.usr-empty-sub { font-size:13px; }

/* Delete modal */
.usr-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:999; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.usr-modal { background:#fff; border-radius:16px; padding:28px; max-width:380px; width:90%; box-shadow:0 24px 60px rgba(0,0,0,.18); animation:popIn .2s ease; }
@keyframes popIn { from { opacity:0; transform:scale(.94) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
.usr-modal-icon { width:52px; height:52px; border-radius:14px; background:#fef2f2; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
.usr-modal-title { font-size:17px; font-weight:800; color:#121212; text-align:center; margin-bottom:8px; }
.usr-modal-sub { font-size:13px; color:#7c7c7c; text-align:center; line-height:1.6; margin-bottom:22px; }
.usr-modal-btns { display:flex; gap:10px; }
.usr-modal-btn { flex:1; padding:10px; border-radius:9px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; border:none; transition:all .15s; }
.usr-modal-cancel { background:#f5f4f6; color:#7c7c7c; border:1px solid #e1dee3; }
.usr-modal-cancel:hover { background:#e1dee3; }
.usr-modal-delete { background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; box-shadow:0 4px 12px rgba(220,38,38,.3); }
.usr-modal-delete:hover { transform:translateY(-1px); }
</style>
@endpush

@section('content')

<div class="usr-page">

    {{-- Topbar --}}
    <div class="usr-topbar">
        <div>
            <div class="usr-page-title">User Management</div>
            <div class="usr-breadcrumb">Admin › <span>Users</span></div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('users.index') }}" id="filterForm">
    <div class="usr-filter-bar">
        <div class="usr-search-wrap">
            <svg class="usr-search-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" class="usr-search-input" placeholder="Search name, email, phone…"
                   value="{{ request('search') }}">
        </div>

        <div class="usr-filter-wrap">
            <svg class="usr-filter-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <select name="branch_id" class="usr-filter-select">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
                @endforeach
            </select>
            <svg class="usr-filter-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <div class="usr-filter-wrap">
            <svg class="usr-filter-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
            <select name="role" class="usr-filter-select">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                    {{ $role->display_name ?? ucfirst(str_replace('_',' ',$role->name)) }}
                </option>
                @endforeach
            </select>
            <svg class="usr-filter-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <div class="usr-filter-wrap">
            <svg class="usr-filter-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 14 14"/></svg>
            <select name="status" class="usr-filter-select">
                <option value="">All Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
            <svg class="usr-filter-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <div class="usr-filter-wrap">
            <svg class="usr-filter-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 16 14"/></svg>
            <select name="activity" class="usr-filter-select">
                <option value="">All Activity</option>
                <option value="last_seen" {{ request('activity') === 'last_seen' ? 'selected' : '' }}>🕒 Last Seen (Recent First)</option>
                <option value="recent_login" {{ request('activity') === 'recent_login' ? 'selected' : '' }}>⚡ Recent Login (24h)</option>
                <option value="inactive_3days" {{ request('activity') === 'inactive_3days' ? 'selected' : '' }}>⚠️ Inactive (3+ Days)</option>
            </select>
            <svg class="usr-filter-caret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>

        <button type="submit" class="usr-btn usr-btn-primary" style="padding:7px 16px; font-size:12px; height:34px; border-radius:9px;">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Apply
        </button>

        @if(request()->hasAny(['search','branch_id','role','status','activity']))
        <a href="{{ route('users.index') }}" class="usr-filter-reset">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
            Reset
        </a>
        @endif
    </div>
    </form>

    {{-- Body --}}
    <div class="usr-body">

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="usr-alert usr-alert-success">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            {!! session('success') !!}
        </div>
        @endif
        @if(session('error'))
        <div class="usr-alert usr-alert-error">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {!! session('error') !!}
        </div>
        @endif

        {{-- Stats --}}
        <div class="usr-stats-row">
            @php
                $totalUsers    = \App\Models\User::count();
                $activeUsers   = \App\Models\User::where('is_active', true)->count();
                $recentLogins  = \App\Models\User::where('last_login_at', '>=', now()->subHours(24))->count();
                $inactive3Days = \App\Models\User::where(function($q) {
                    $q->whereNull('last_login_at')->orWhere('last_login_at', '<=', now()->subDays(3));
                })->count();
            @endphp
            <div class="usr-stat">
                <div class="usr-stat-icon" style="background:#fff0e6">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#fe5f04" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <div class="usr-stat-label">Total Users</div>
                    <div class="usr-stat-value">{{ $totalUsers }}</div>
                </div>
            </div>
            <div class="usr-stat">
                <div class="usr-stat-icon" style="background:#f0fdf4">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                    <div class="usr-stat-label">Active Users</div>
                    <div class="usr-stat-value" style="color:#16a34a">{{ $activeUsers }}</div>
                </div>
            </div>
            <div class="usr-stat" style="cursor:pointer;" onclick="location.href='{{ route('users.index', ['activity' => 'recent_login']) }}'">
                <div class="usr-stat-icon" style="background:#eff6ff">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <div>
                    <div class="usr-stat-label">Recent Login (24h)</div>
                    <div class="usr-stat-value" style="color:#2563eb">{{ $recentLogins }}</div>
                </div>
            </div>
            <div class="usr-stat" style="cursor:pointer;" onclick="location.href='{{ route('users.index', ['activity' => 'inactive_3days']) }}'">
                <div class="usr-stat-icon" style="background:#fef2f2">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <div class="usr-stat-label">Inactive (3+ Days)</div>
                    <div class="usr-stat-value" style="color:#dc2626">{{ $inactive3Days }}</div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="usr-table-card">
            <div class="usr-table-head-row">
                <div>
                    <div class="usr-table-title">All Users</div>
                    <div class="usr-table-sub">Manage team members, roles & access</div>
                </div>
                <div class="usr-results-count">
                    Showing <strong>{{ $query->firstItem() ?? 0 }}–{{ $query->lastItem() ?? 0 }}</strong>
                    of <strong>{{ $query->total() }}</strong> users
                </div>
            </div>

            @if($query->isEmpty())
            <div class="usr-empty">
                <div class="usr-empty-icon">👥</div>
                <div class="usr-empty-title">No users found</div>
                <div class="usr-empty-sub">Try adjusting your filters or add a new user.</div>
            </div>
            @else
            <div style="overflow-x:auto;">
                <table class="usr-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Phone</th>
                            <th>Branch</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Seen / Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $roleClasses = [
                                'super_admin'      => 'ub-super',
                                'company_admin'    => 'ub-super',
                                'admin'            => 'ub-admin',
                                'team_leader'      => 'ub-tl',
                                'bde'              => 'ub-bde',
                                'hr_manager'       => 'ub-hr',
                                'accounts_manager' => 'ub-acc',
                            ];
                            $colors = ['#fe5f04','#7c3aed','#2563eb','#16a34a','#be123c','#0284c7','#b45309'];
                        @endphp
                        @foreach($query as $i => $user)
                        @php
                            $roleName = $user->roles->first()?->name ?? 'staff';
                            $roleKey = \Illuminate\Support\Str::contains($roleName, '__')
                                ? \Illuminate\Support\Str::afterLast($roleName, '__')
                                : $roleName;
                            $roleDisplay = $user->roles->first()?->display_name ?? ucfirst(str_replace('_',' ',$roleKey));
                            $roleClass = $roleClasses[$roleKey] ?? 'ub-default';
                            $avatarColor = $colors[$user->id % count($colors)];
                        @endphp
                        <tr>
                            <td style="color:#9e9e9e;font-size:12px;font-family:monospace;">
                                {{ $query->firstItem() + $loop->index }}
                            </td>
                            <td>
                                <div class="usr-cell">
                                    <div class="usr-avatar" style="background:{{ $avatarColor }}">
                                        @if($user->avatar)
                                            <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}">
                                        @else
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="usr-name">{{ $user->name }}</div>
                                        <div class="usr-email">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="usr-phone">{{ $user->phone ?? '—' }}</span></td>
                            <td>
                                @if($user->branch)
                                <div class="usr-branch">
                                    <div class="usr-branch-dot"></div>
                                    <strong style="color:#1e293b;">{{ $user->branch->name }}</strong>
                                </div>
                                @endif

                                @if($user->branches && $user->branches->isNotEmpty())
                                    @php
                                        $addBranches = $user->branches->reject(fn($b) => $user->branch_id && $b->id == $user->branch_id);
                                    @endphp
                                    @if($addBranches->isNotEmpty())
                                        <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px;">
                                            @foreach($addBranches as $addBranch)
                                                <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 6px; background: #eff6ff; color: #1d4ed8; font-size: 10px; font-weight: 700; border: 1px solid #bfdbfe;" title="Additional Branch">
                                                    + {{ $addBranch->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                @if(!$user->branch && ($user->branches->isEmpty()))
                                <span style="color:#9e9e9e;font-size:12px;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="usr-badge {{ $roleClass }}">{{ $roleDisplay }}</span>
                            </td>
                            <td>
                                @can('users.manage')
                                <form method="POST" action="{{ route('users.toggle-status', $user) }}" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <label class="usr-toggle-wrap" title="{{ $user->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                                        <span class="usr-toggle">
                                            <input type="checkbox" {{ $user->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                            <div class="usr-toggle-slider"></div>
                                        </span>
                                        <span class="usr-toggle-label {{ $user->is_active ? 'is-active' : 'is-inactive' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </label>
                                </form>
                                @else
                                <span class="usr-badge {{ $user->is_active ? 'ub-active' : 'ub-inactive' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @endcan
                            </td>
                            <td>
                                @if(!$user->last_login_at)
                                    <span class="usr-badge ub-inactive" style="font-size:11px;">Never</span>
                                @elseif($user->last_login_at->gte(now()->subHours(24)))
                                    <span class="usr-badge ub-active" style="font-size:11px;" title="{{ $user->last_login_at->format('d M Y, h:i A') }}">
                                        ⚡ {{ $user->last_login_at->diffForHumans() }}
                                    </span>
                                @elseif($user->last_login_at->lte(now()->subDays(3)))
                                    <span class="usr-badge ub-inactive" style="font-size:11px;" title="{{ $user->last_login_at->format('d M Y, h:i A') }}">
                                        ⚠️ {{ $user->last_login_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="usr-badge ub-default" style="font-size:11px;" title="{{ $user->last_login_at->format('d M Y, h:i A') }}">
                                        🕒 {{ $user->last_login_at->diffForHumans() }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="usr-actions">
                                    <a href="{{ route('users.show', $user) }}" class="usr-action-btn" title="View">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($query->hasPages())
                @include('partials.table-pagination', ['paginator' => $query])
            @endif
            @if(false && $query->hasPages())
            <div class="usr-pagination">
                <div class="usr-page-info">
                    Page {{ $query->currentPage() }} of {{ $query->lastPage() }}
                </div>
                <div class="usr-page-links">
                    @if($query->onFirstPage())
                        <span class="usr-page-link" style="opacity:.4;">‹ Prev</span>
                    @else
                        <a href="{{ $query->previousPageUrl() }}" class="usr-page-link">‹ Prev</a>
                    @endif

                    @foreach($query->getUrlRange(max(1, $query->currentPage()-2), min($query->lastPage(), $query->currentPage()+2)) as $page => $url)
                        <a href="{{ $url }}" class="usr-page-link {{ $page == $query->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach

                    @if($query->hasMorePages())
                        <a href="{{ $query->nextPageUrl() }}" class="usr-page-link">Next ›</a>
                    @else
                        <span class="usr-page-link" style="opacity:.4;">Next ›</span>
                    @endif
                </div>
            </div>
            @endif

            @endif
        </div>

    </div>{{-- /usr-body --}}
</div>{{-- /usr-page --}}
@endsection

@push('scripts')
<script>
// ── Debounced search ─────────────────────────────────────────────
let searchTimer;
function delaySubmit() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
}
</script>
@endpush
