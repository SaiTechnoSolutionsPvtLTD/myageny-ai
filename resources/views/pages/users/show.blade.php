@extends('layouts.app')

@section('title', 'User Profile — ' . $user->name)

@push('styles')
<style>
/* ══════════════════════════════════════════════
   USER SHOW / PROFILE — Detail view
══════════════════════════════════════════════ */
.ushow-page { display:flex; flex-direction:column; height:100%; overflow:hidden; background:#f4f5f7; font-family:'Inter',sans-serif; }

/* Topbar */
.ushow-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; flex-shrink:0; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.ushow-page-title { font-size:18px; font-weight:800; color:#121212; }
.ushow-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.ushow-breadcrumb a { color:#fe5f04; text-decoration:none; font-weight:600; }
.ushow-topbar-right { display:flex; align-items:center; gap:10px; }
.ushow-btn { display:flex; align-items:center; gap:6px; padding:8px 18px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; transition:all .15s; border:none; }
.ushow-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.ushow-btn-primary:hover { transform:translateY(-1px); }
.ushow-btn-outline { background:#fff; color:#121212; border:1px solid #e1dee3; }
.ushow-btn-outline:hover { border-color:#9e9e9e; }
.ushow-btn-danger  { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.ushow-btn-danger:hover { background:#fee2e2; }

/* Body */
.ushow-body { flex:1; overflow-y:auto; padding:24px 28px 40px; }
.ushow-body::-webkit-scrollbar { width:5px; }
.ushow-body::-webkit-scrollbar-thumb { background:#e1dee3; border-radius:3px; }

@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* Layout */
.ushow-layout { display:grid; grid-template-columns:300px 1fr; gap:20px; animation:fadeUp .35s ease both; }

/* Profile Card */
.ushow-profile-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.ushow-profile-banner {
    height:80px;
    background:linear-gradient(135deg,{{ ['#fe5f04','#7c3aed','#2563eb','#16a34a','#be123c','#b45309'][$user->id % 6] }},
               {{ ['#ff7c30','#a855f7','#3b82f6','#22c55e','#dc2626','#d97706'][$user->id % 6] }});
}
.ushow-profile-body { padding:0 20px 20px; }
.ushow-avatar-wrap { margin-top:-36px; margin-bottom:12px; }
.ushow-avatar {
    width:72px; height:72px; border-radius:50%;
    background:{{ ['#fe5f04','#7c3aed','#2563eb','#16a34a','#be123c','#b45309'][$user->id % 6] }};
    display:flex; align-items:center; justify-content:center;
    font-size:26px; font-weight:800; color:#fff;
    border:4px solid #fff; box-shadow:0 4px 16px rgba(0,0,0,.12);
    overflow:hidden;
}
.ushow-avatar img { width:100%; height:100%; object-fit:cover; }
.ushow-name { font-size:18px; font-weight:800; color:#121212; margin-bottom:4px; }
.ushow-email { font-size:13px; color:#9e9e9e; margin-bottom:12px; }
.ushow-role-badge {
    display:inline-flex; align-items:center; gap:6px;
    padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700;
    margin-bottom:16px;
}
.ushow-status-row { display:flex; align-items:center; gap:8px; margin-bottom:16px; }
.ushow-status-dot { width:8px; height:8px; border-radius:50%; }
.ushow-status-text { font-size:12px; font-weight:600; }
.ushow-divider { height:1px; background:#f0eef2; margin:14px 0; }
.ushow-meta-item { display:flex; align-items:flex-start; gap:10px; margin-bottom:12px; }
.ushow-meta-ico { width:28px; height:28px; border-radius:8px; background:#f5f4f6; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#9e9e9e; }
.ushow-meta-label { font-size:10px; font-weight:700; color:#9e9e9e; text-transform:uppercase; letter-spacing:.4px; }
.ushow-meta-value { font-size:13px; font-weight:600; color:#121212; margin-top:2px; }
.ushow-profile-actions { display:flex; flex-direction:column; gap:8px; margin-top:16px; }

/* Right column */
.ushow-right { display:flex; flex-direction:column; gap:16px; }

/* Info cards */
.ushow-card { background:#fff; border:1px solid #e1dee3; border-radius:14px; overflow:hidden; }
.ushow-card-head { display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-bottom:1px solid #f0eef2; }
.ushow-card-title { font-size:14px; font-weight:700; color:#121212; display:flex; align-items:center; gap:8px; }
.ushow-card-body { padding:18px 20px; }

/* Info grid */
.ushow-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.ushow-info-item { }
.ushow-info-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:#9e9e9e; margin-bottom:5px; }
.ushow-info-value { font-size:13px; font-weight:600; color:#121212; }
.ushow-info-value.muted { color:#9e9e9e; font-weight:400; }

/* Permissions grid */
.ushow-perms-grid { display:flex; flex-wrap:wrap; gap:6px; }
.ushow-perm-chip {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600;
    background:#f5f4f6; color:#555; border:1px solid #e1dee3;
}
.ushow-perm-module { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; padding:2px 7px; border-radius:20px; }

/* Reset Password Section */
.ushow-pw-form { display:flex; flex-direction:column; gap:12px; }
.ushow-pw-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ushow-input-wrap { position:relative; }
.ushow-input-ico { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9e9e9e; pointer-events:none; width:13px; height:13px; }
.ushow-input { width:100%; padding:8px 12px 8px 32px; border:1px solid #e1dee3; border-radius:9px; font-size:13px; font-family:inherit; color:#121212; background:#fafafa; outline:none; transition:all .15s; }
.ushow-input:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.ushow-select { width:100%; padding:10px 12px; border:1px solid #e1dee3; border-radius:9px; font-size:13px; font-family:inherit; color:#121212; background:#fafafa; outline:none; }
.ushow-checkbox-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px; }
.ushow-checkbox-card { display:flex; gap:10px; align-items:flex-start; border:1px solid #f0eef2; border-radius:10px; padding:10px 12px; background:#fafafa; }
.ushow-checkbox-card input { margin-top:3px; }
.ushow-checkbox-title { display:block; font-size:12px; font-weight:700; color:#121212; }
.ushow-checkbox-sub { display:block; font-size:11px; color:#8a8a8a; margin-top:2px; }

/* Activity log */
.ushow-activity-list { display:flex; flex-direction:column; gap:0; }
.ushow-activity-item { display:flex; gap:12px; padding:11px 0; border-bottom:1px solid #f7f6f9; }
.ushow-activity-item:last-child { border-bottom:none; }
.ushow-activity-ico { width:30px; height:30px; border-radius:9px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:14px; }
.ushow-activity-text { font-size:12px; color:#2e2e2e; line-height:1.5; }
.ushow-activity-text strong { font-weight:700; color:#fe5f04; }
.ushow-activity-time { font-size:10px; color:#9e9e9e; margin-top:2px; }

/* Alert */
.ushow-alert { padding:12px 16px; border-radius:10px; font-size:13px; display:flex; align-items:center; gap:10px; margin-bottom:16px; }
.ushow-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.ushow-alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
</style>
@endpush

@section('content')
<div class="ushow-page">

    {{-- Topbar --}}
    <div class="ushow-topbar">
        <div>
            <div class="ushow-page-title">User Profile</div>
            <div class="ushow-breadcrumb">
                <a href="{{ route('users.index') }}">Users</a> › {{ $user->name }}
            </div>
        </div>
        <div class="ushow-topbar-right">
            @can('users.manage')
            <a href="{{ route('users.edit', $user) }}" class="ushow-btn ushow-btn-primary">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit User
            </a>
            @endcan
            <a href="{{ route('users.index') }}" class="ushow-btn ushow-btn-outline">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="ushow-body">

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="ushow-alert ushow-alert-success">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            {!! session('success') !!}
        </div>
        @endif
        @if(session('error'))
        <div class="ushow-alert ushow-alert-error">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {!! session('error') !!}
        </div>
        @endif

        <div class="ushow-layout">

            {{-- ── Left: Profile Card ── --}}
            <div>
                <div class="ushow-profile-card">
                    <div class="ushow-profile-banner"></div>
                    <div class="ushow-profile-body">
                        <div class="ushow-avatar-wrap">
                            <div class="ushow-avatar">
                                @if($user->avatar)
                                    <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}">
                                @else
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                @endif
                            </div>
                        </div>

                        <div class="ushow-name">{{ $user->name }}</div>
                        <div class="ushow-email">{{ $user->email }}</div>

                        @php
                            $role = $user->roles->first();
                            $directPermissions = $user->permissions;
                            $roleColors = ['super_admin'=>['bg'=>'#fef9c3','color'=>'#92400e'],'company_admin'=>['bg'=>'#fef2f2','color'=>'#b91c1c'],'admin'=>['bg'=>'#fff7ed','color'=>'#ea580c'],'team_leader'=>['bg'=>'#f0fdf4','color'=>'#15803d'],'bde'=>['bg'=>'#eff6ff','color'=>'#2563eb'],'hr_manager'=>['bg'=>'#faf5ff','color'=>'#7c3aed'],'accounts_manager'=>['bg'=>'#f0fdfa','color'=>'#0f766e'],'staff'=>['bg'=>'#f5f4f6','color'=>'#7c7c7c']];
                            $roleKey = $role && \Illuminate\Support\Str::contains($role->name, '__') ? \Illuminate\Support\Str::afterLast($role->name, '__') : ($role?->name ?? 'staff');
                            $rc = $roleColors[$roleKey] ?? ['bg'=>'#f5f4f6','color'=>'#7c7c7c'];
                        @endphp
                        <div class="ushow-role-badge" style="background:{{ $rc['bg'] }};color:{{ $rc['color'] }}">
                            🛡️ {{ $role?->display_name ?? 'No Role' }}
                        </div>

                        <div class="ushow-status-row">
                            <div class="ushow-status-dot" style="background:{{ $user->is_active ? '#16a34a' : '#dc2626' }}"></div>
                            <div class="ushow-status-text" style="color:{{ $user->is_active ? '#16a34a' : '#dc2626' }}">
                                {{ $user->is_active ? 'Active Account' : 'Inactive Account' }}
                            </div>
                        </div>

                        <div class="ushow-divider"></div>

                        <div class="ushow-meta-item">
                            <div class="ushow-meta-ico">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                            </div>
                            <div>
                                <div class="ushow-meta-label">Primary Branch</div>
                                <div class="ushow-meta-value">{{ $user->branch?->name ?? 'Not Assigned' }}</div>
                                @if($user->branches && $user->branches->isNotEmpty())
                                    @php
                                        $addBranches = $user->branches->reject(fn($b) => $user->branch_id && $b->id == $user->branch_id);
                                    @endphp
                                    @if($addBranches->isNotEmpty())
                                        <div style="margin-top: 6px;">
                                            <div class="ushow-meta-label" style="font-size: 10px; margin-bottom: 3px;">Additional Branches</div>
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                @foreach($addBranches as $addBranch)
                                                    <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 6px; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 700; border: 1px solid #bfdbfe;">
                                                        + {{ $addBranch->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="ushow-meta-item">
                            <div class="ushow-meta-ico">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/></svg>
                            </div>
                            <div>
                                <div class="ushow-meta-label">Phone</div>
                                <div class="ushow-meta-value">{{ $user->phone ?? 'Not set' }}</div>
                            </div>
                        </div>

                        <div class="ushow-meta-item">
                            <div class="ushow-meta-ico">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <div class="ushow-meta-label">Last Login</div>
                                <div class="ushow-meta-value">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</div>
                            </div>
                        </div>

                        <div class="ushow-meta-item">
                            <div class="ushow-meta-ico">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <div>
                                <div class="ushow-meta-label">Member Since</div>
                                <div class="ushow-meta-value">{{ $user->created_at->format('d M Y') }}</div>
                            </div>
                        </div>

                        @if($user->last_login_ip)
                        <div class="ushow-meta-item">
                            <div class="ushow-meta-ico">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <div class="ushow-meta-label">Last IP</div>
                                <div class="ushow-meta-value" style="font-family:monospace;font-size:12px">{{ $user->last_login_ip }}</div>
                            </div>
                        </div>
                        @endif

                        @can('users.manage')
                        <div class="ushow-divider"></div>
                        <div class="ushow-profile-actions">
                            {{-- Toggle Status --}}
                            <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="ushow-btn" style="width:100%;justify-content:center;background:{{ $user->is_active ? '#fef2f2' : '#f0fdf4' }};color:{{ $user->is_active ? '#dc2626' : '#16a34a' }};border:1px solid {{ $user->is_active ? '#fecaca' : '#bbf7d0' }};">
                                    {{ $user->is_active ? '🔒 Deactivate Account' : '✅ Activate Account' }}
                                </button>
                            </form>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- ── Right Column ── --}}
            <div class="ushow-right">

                {{-- Account Details --}}
                <div class="ushow-card">
                    <div class="ushow-card-head">
                        <div class="ushow-card-title">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Account Details
                        </div>
                    </div>
                    <div class="ushow-card-body">
                        <div class="ushow-info-grid">
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Full Name</div>
                                <div class="ushow-info-value">{{ $user->name }}</div>
                            </div>
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Email Address</div>
                                <div class="ushow-info-value">{{ $user->email }}</div>
                            </div>
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Phone</div>
                                <div class="ushow-info-value {{ !$user->phone ? 'muted' : '' }}">{{ $user->phone ?? 'Not set' }}</div>
                            </div>
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Branch</div>
                                <div class="ushow-info-value">{{ $user->branch?->name ?? 'Not assigned' }}</div>
                            </div>
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Role</div>
                                <div class="ushow-info-value">{{ $role?->display_name ?? 'No Role' }}</div>
                            </div>
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Status</div>
                                <div class="ushow-info-value" style="color:{{ $user->is_active ? '#16a34a' : '#dc2626' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </div>
                            </div>
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Created</div>
                                <div class="ushow-info-value">{{ $user->created_at->format('d M Y, h:i A') }}</div>
                            </div>
                            <div class="ushow-info-item">
                                <div class="ushow-info-label">Last Updated</div>
                                <div class="ushow-info-value">{{ $user->updated_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Permissions --}}
                @if($role && $role->permissions->count())
                <div class="ushow-card">
                    <div class="ushow-card-head">
                        <div class="ushow-card-title">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Role Permissions
                        </div>
                        <span style="font-size:11px;color:#9e9e9e;">{{ $role->permissions->count() }} permissions via <strong>{{ $role->display_name }}</strong></span>
                    </div>
                    <div class="ushow-card-body">
                        @php $grouped = $role->permissions->groupBy('module'); @endphp
                        @foreach($grouped as $module => $perms)
                        <div style="margin-bottom:12px;">
                            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:#9e9e9e;margin-bottom:6px;">{{ strtoupper($module) }}</div>
                            <div class="ushow-perms-grid">
                                @foreach($perms as $p)
                                <span class="ushow-perm-chip">
                                    <svg width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ $p->name }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($directPermissions->count())
                <div class="ushow-card">
                    <div class="ushow-card-head">
                        <div class="ushow-card-title">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9S3 16.97 3 12 7.03 3 12 3s9 4.03 9 9z"/></svg>
                            Direct Permissions
                        </div>
                        <span style="font-size:11px;color:#9e9e9e;">{{ $directPermissions->count() }} directly assigned</span>
                    </div>
                    <div class="ushow-card-body">
                        <div class="ushow-perms-grid">
                            @foreach($directPermissions as $permission)
                            <span class="ushow-perm-chip">{{ $permission->display_name ?? $permission->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <div class="ushow-card">
                    <div class="ushow-card-head">
                        <div class="ushow-card-title">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Access Control
                        </div>
                    </div>
                    <div class="ushow-card-body">
                        <form method="POST" action="{{ route('auth.users.assign-role', $user) }}" class="ushow-pw-form">
                            @csrf
                            <div>
                                <div class="ushow-info-label">Role</div>
                                <select name="roles[]" class="ushow-select">
                                    <option value="">No Role</option>
                                    @foreach($roles as $availableRole)
                                    <option value="{{ $availableRole->name }}" {{ $user->roles->contains('name', $availableRole->name) ? 'selected' : '' }}>
                                        {{ $availableRole->display_name ?? ucfirst(str_replace('_', ' ', $availableRole->name)) }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('roles')<div style="font-size:11px;color:#dc2626;margin-top:6px;">{{ $message }}</div>@enderror
                            </div>

                            <div>
                                <div class="ushow-info-label">Direct Permissions</div>
                                <div class="ushow-checkbox-grid">
                                    @foreach($permissions as $module => $permissionGroup)
                                        @foreach($permissionGroup as $permission)
                                        <label class="ushow-checkbox-card">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" {{ $directPermissions->contains('name', $permission->name) ? 'checked' : '' }}>
                                            <span>
                                                <span class="ushow-checkbox-title">{{ $permission->display_name ?? ucfirst(str_replace(['.', '_'], ' ', $permission->name)) }}</span>
                                                <span class="ushow-checkbox-sub">{{ strtoupper($module) }} | {{ $permission->name }}</span>
                                            </span>
                                        </label>
                                        @endforeach
                                    @endforeach
                                </div>
                                @error('permissions')<div style="font-size:11px;color:#dc2626;margin-top:6px;">{{ $message }}</div>@enderror
                                @error('permissions.*')<div style="font-size:11px;color:#dc2626;margin-top:6px;">{{ $message }}</div>@enderror
                            </div>

                            <div>
                                <button type="submit" class="ushow-btn ushow-btn-primary">Update Access</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Reset Password --}}
                @can('users.manage')
                <div class="ushow-card">
                    <div class="ushow-card-head">
                        <div class="ushow-card-title">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Reset Password
                        </div>
                    </div>
                    <div class="ushow-card-body">
                        <form method="POST" action="{{ route('users.reset-password', $user) }}" class="ushow-pw-form">
                            @csrf
                            <div class="ushow-pw-row">
                                <div class="ushow-input-wrap">
                                    <svg class="ushow-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" name="new_password" class="ushow-input" placeholder="New password (min 8)">
                                </div>
                                <div class="ushow-input-wrap">
                                    <svg class="ushow-input-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" name="new_password_confirmation" class="ushow-input" placeholder="Confirm new password">
                                </div>
                            </div>
                            @error('new_password')<div style="font-size:11px;color:#dc2626;">{{ $message }}</div>@enderror
                            <div>
                                <button type="submit" class="ushow-btn ushow-btn-outline" style="font-size:13px;">
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
                                    Reset Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan

                {{-- Recent Activity (placeholder) --}}
                <div class="ushow-card">
                    <div class="ushow-card-head">
                        <div class="ushow-card-title">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            Recent Activity
                        </div>
                    </div>
                    <div class="ushow-card-body">
                        <div class="ushow-activity-list">
                            @php
                                $activities = [
                                    ['ico'=>'🔐','bg'=>'#eef2ff','text'=>'Logged in successfully','time'=>$user->last_login_at?->diffForHumans() ?? 'Never'],
                                    ['ico'=>'✏️','bg'=>'#fff7ed','text'=>'Profile details updated','time'=>$user->updated_at->diffForHumans()],
                                    ['ico'=>'👤','bg'=>'#f0fdf4','text'=>'Account created','time'=>$user->created_at->diffForHumans()],
                                ];
                            @endphp
                            @foreach($activities as $a)
                            <div class="ushow-activity-item">
                                <div class="ushow-activity-ico" style="background:{{ $a['bg'] }}">{{ $a['ico'] }}</div>
                                <div>
                                    <div class="ushow-activity-text">{{ $a['text'] }}</div>
                                    <div class="ushow-activity-time">{{ $a['time'] }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
