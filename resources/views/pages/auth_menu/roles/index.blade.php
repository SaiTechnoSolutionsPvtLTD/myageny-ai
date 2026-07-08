@extends('layouts.app')

@section('title', 'Roles')

@push('styles')
<style>
.auth-page { padding: 28px; background: #f4f5f7; min-height: 100%; }
.auth-topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; }
.auth-title { font-size: 22px; font-weight: 800; color: #121212; }
.auth-subtitle { font-size: 13px; color: #7c7c7c; margin-top: 4px; }
.auth-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; text-decoration: none; border: 1px solid #e1dee3; font-size: 13px; font-weight: 700; }
.auth-btn-primary { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; border: none; }
.auth-card { background: #fff; border: 1px solid #e1dee3; border-radius: 16px; overflow: hidden; }
.auth-table { width: 100%; border-collapse: collapse; }
.auth-table th, .auth-table td { padding: 14px 16px; border-bottom: 1px solid #f1eff3; text-align: left; font-size: 13px; }
.auth-table th { background: #fafafa; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; color: #8a8a8a; }
.auth-table tr:last-child td { border-bottom: none; }
.auth-badge { display: inline-flex; padding: 4px 10px; border-radius: 999px; background: #fff7ed; color: #ea580c; font-size: 11px; font-weight: 700; }
.auth-muted { color: #8a8a8a; }
.auth-actions { display: flex; align-items: center; gap: 8px; }
.auth-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; border: 1px solid #e1dee3; background: #fff; color: #555; text-decoration: none; }
.auth-icon-btn.delete { color: #dc2626; }
.auth-icon-btn.access { color: #2563eb; }
.auth-empty { padding: 60px 24px; text-align: center; color: #8a8a8a; }
.auth-flash { margin-bottom: 16px; padding: 12px 14px; border-radius: 10px; font-size: 13px; }
.auth-flash.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.auth-flash.error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.auth-filter { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; padding: 18px 20px; border-bottom: 1px solid #f1eff3; background: #fcfcfc; }
.auth-field { display: flex; flex-direction: column; gap: 6px; min-width: 180px; }
.auth-label { font-size: 11px; font-weight: 800; color: #8a8a8a; text-transform: uppercase; letter-spacing: .6px; }
.auth-input, .auth-select { min-height: 42px; padding: 10px 12px; border: 1px solid #e1dee3; border-radius: 10px; background: #fff; font-size: 13px; color: #222; }
.auth-input { min-width: 280px; }
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-topbar">
        <div>
            <div class="auth-title">Roles</div>
            <div class="auth-subtitle">Create roles, map permissions, and control access across the CRM.</div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('auth.index') }}" class="auth-btn">Back</a>
            <a href="{{ route('auth.roles.create') }}" class="auth-btn auth-btn-primary">Add Role</a>
        </div>
    </div>

    @if(session('success'))
        <div class="auth-flash success">{!! session('success') !!}</div>
    @endif
    @if(session('error'))
        <div class="auth-flash error">{!! session('error') !!}</div>
    @endif

    <div class="auth-card">
        <form method="GET" action="{{ route('auth.roles.index') }}" class="auth-filter">
            <div class="auth-field">
                <label class="auth-label">Department</label>
                <select name="department_id" class="auth-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="auth-field">
                <label class="auth-label">Search</label>
                <input type="text" name="search" class="auth-input" value="{{ request('search') }}" placeholder="Search role name, key, description...">
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="auth-btn auth-btn-primary">Apply</button>
                @if(request('department_id') || request('search'))
                    <a href="{{ route('auth.roles.index') }}" class="auth-btn">Reset</a>
                @endif
            </div>
        </form>

        @if($roles->isEmpty())
            <div class="auth-empty">
                @if(request('department_id') || request('search'))
                    No roles matched your filters. Try resetting or adjusting them.
                @else
                    No roles found yet. Create your first role to start assigning access.
                @endif
            </div>
        @else
            <table class="auth-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Key</th>
                        <th>Department</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        @php $isProtectedRole = strtolower(str_replace(' ', '_', $role->name)) === 'super_admin'; @endphp
                        <tr>
                            <td>
                                <div style="font-weight:700;">{{ $role->display_name ?: ucfirst(str_replace('_', ' ', $role->name)) }}</div>
                            </td>
                            <td><span class="auth-badge">{{ $role->name }}</span></td>
                            <td class="auth-muted">{{ $role->department?->name ?: 'No department' }}</td>
                            <td class="auth-muted">{{ $role->description ?: 'No description added' }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ $role->permissions->count() }}</td>
                            <td>
                                <div class="auth-actions">
                                    <a href="{{ route('auth.roles.permissions.edit', $role) }}" class="auth-icon-btn access" title="Assign Permissions">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    </a>
                                    <a href="{{ route('auth.roles.edit', $role) }}" class="auth-icon-btn" title="Edit">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    @if(! $isProtectedRole)
                                        <form method="POST" action="{{ route('auth.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="auth-icon-btn delete" title="Delete">
                                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($roles->hasPages())
                @include('partials.table-pagination', ['paginator' => $roles])
            @endif
        @endif
    </div>
</div>
@endsection
