@extends('layouts.app')

@section('title', 'Permissions')

@push('styles')
<style>
.perm-page { padding: 28px; background: #f4f5f7; min-height: 100%; }
.perm-top { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; }
.perm-title { font-size:22px; font-weight:800; color:#121212; }
.perm-sub { font-size:13px; color:#7c7c7c; margin-top:4px; }
.perm-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:10px; text-decoration:none; border:1px solid #e1dee3; font-size:13px; font-weight:700; background:#fff; color:#222; }
.perm-btn.primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; border:none; }
.perm-flash { margin-bottom:16px; padding:12px 14px; border-radius:10px; font-size:13px; }
.perm-flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.perm-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.perm-filter { display:flex; gap:12px; flex-wrap:wrap; align-items:end; padding:18px 20px; border-bottom:1px solid #f1eff3; background:#fcfcfc; }
.perm-field { display:flex; flex-direction:column; gap:6px; min-width:180px; }
.perm-label { font-size:11px; font-weight:800; color:#8a8a8a; text-transform:uppercase; letter-spacing:.6px; }
.perm-input, .perm-select { min-height:42px; padding:10px 12px; border:1px solid #e1dee3; border-radius:10px; background:#fff; font-size:13px; color:#222; }
.perm-input { min-width:280px; }
.perm-section { border-bottom:1px solid #f1eff3; }
.perm-section:last-child { border-bottom:none; }
.perm-head { padding:16px 20px; background:#fafafa; font-size:12px; font-weight:800; color:#8a8a8a; letter-spacing:.8px; }
.perm-table { width:100%; border-collapse:collapse; }
.perm-table th, .perm-table td { padding:14px 16px; border-bottom:1px solid #f1eff3; text-align:left; font-size:13px; }
.perm-table th { font-size:11px; text-transform:uppercase; letter-spacing:.6px; color:#8a8a8a; }
.perm-table tr:last-child td { border-bottom:none; }
.perm-key { display:inline-flex; padding:4px 10px; border-radius:999px; background:#eff6ff; color:#2563eb; font-size:11px; font-weight:700; }
.perm-actions { display:flex; gap:8px; }
.perm-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:10px; border:1px solid #e1dee3; background:#fff; color:#555; text-decoration:none; }
.perm-empty { padding:60px 24px; text-align:center; color:#8a8a8a; }
</style>
@endpush

@section('content')
<div class="perm-page">
    <div class="perm-top">
        <div>
            <div class="perm-title">Permissions</div>
            <div class="perm-sub">Define reusable actions, then assign them to roles and users.</div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('auth.index') }}" class="perm-btn">Back</a>
            <a href="{{ route('auth.permissions.create') }}" class="perm-btn primary">Add Permission</a>
        </div>
    </div>

    @if(session('success'))
        <div class="perm-flash success">{!! session('success') !!}</div>
    @endif

    <div class="perm-card">
        <form method="GET" action="{{ route('auth.permissions.index') }}" class="perm-filter">
            <div class="perm-field">
                <label class="perm-label">Category</label>
                <select name="module" class="perm-select">
                    <option value="">All Categories</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected($selectedModule === $module)>{{ strtoupper($module) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="perm-field">
                <label class="perm-label">Search</label>
                <input type="text" name="search" class="perm-input" value="{{ $search }}" placeholder="Search permission name, key, description...">
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="perm-btn primary">Apply</button>
                @if($selectedModule !== '' || $search !== '')
                    <a href="{{ route('auth.permissions.index') }}" class="perm-btn">Reset</a>
                @endif
            </div>
        </form>

        @if($permissionPages->isEmpty())
            <div class="perm-empty">No permissions found yet. Create permissions first so roles can inherit them.</div>
        @else
        @foreach($permissions as $module => $items)
            <div class="perm-section">
                <div class="perm-head">{{ strtoupper($module) }}</div>
                <table class="perm-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Key</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $permission)
                            <tr>
                                <td>{{ $permission->display_name ?: ucfirst(str_replace(['.', '_'], ' ', $permission->name)) }}</td>
                                <td><span class="perm-key">{{ $permission->name }}</span></td>
                                <td>{{ $permission->description ?: 'No description added' }}</td>
                                <td>
                                    <div class="perm-actions">
                                        <a href="{{ route('auth.permissions.edit', $permission) }}" class="perm-icon-btn" title="Edit">
                                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('auth.permissions.destroy', $permission) }}" onsubmit="return confirm('Delete this permission?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="perm-icon-btn" style="color:#dc2626;" title="Delete">
                                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        @if($permissionPages->hasPages())
            @include('partials.table-pagination', ['paginator' => $permissionPages])
        @endif
        @endif
    </div>
</div>
@endsection
