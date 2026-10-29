@extends('layouts.app')

@section('title', 'Server Master - Accounts')

@push('styles')
<style>
.acm-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.acm-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.acm-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.acm-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.acm-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.acm-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.acm-breadcrumbs a:hover { color:#fe5f04; }
.acm-breadcrumbs .sep { color:#ccc; }
.acm-breadcrumbs .current { color:#424242; font-weight:600; }
.acm-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.acm-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .acm-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .acm-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.acm-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:wrap; }
.acm-search-wrap { position:relative; flex:1; min-width:240px; }
.acm-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.acm-search-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.acm-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.acm-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:capitalize; }
.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-maintenance { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.status-inactive { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

.code-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#0f172a; font-family:monospace; font-size:12px; font-weight:700; border:1px solid #e2e8f0; }
.spec-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f8fafc; color:#475569; font-size:11px; font-weight:700; border:1px solid #e2e8f0; }
.provider-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f5f3ff; color:#6d28d9; font-size:11px; font-weight:700; border:1px solid #ddd6fe; }
.plan-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:700; border:1px solid #bfdbfe; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:94%; max-width:680px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:18px; }

.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .form-grid-2 { grid-template-columns:1fr; } }
.form-section-card { background:#f5f3ff; border:1px solid #ede9fe; border-radius:12px; padding:12px 16px; display:flex; align-items:flex-start; gap:10px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-label span.req { color:#ef4444; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1); }

/* Detail View */
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns:1fr; } }
.detail-item { background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0; }
.detail-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.detail-val { font-size:13px; font-weight:700; color:#0f172a; word-break:break-word; }

.copy-btn { border:none; background:none; color:#64748b; cursor:pointer; padding:2px 4px; border-radius:4px; font-size:12px; }
.copy-btn:hover { color:#7c3aed; background:#fff; }
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Topbar --}}
    <div class="acm-topbar">
        <div class="acm-topbar-left">
            <div class="acm-title">
                <i class="bi bi-hdd-network" style="color:#7c3aed;"></i>
                <span>Server Master</span>
            </div>
            <div class="acm-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span class="sep">/</span>
                <span class="current">Server Master</span>
            </div>
        </div>
        <div class="acm-topbar-actions">
            <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Master Hub
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddServerModal()">
                <i class="bi bi-plus-lg"></i> Add Server
            </button>
        </div>
    </div>

    <div class="acm-body">

        {{-- Alerts --}}
        @if(session('success'))
            <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
                ⚠ {{ session('error') }}
            </div>
        @endif
        @if(isset($errors) && $errors->any())
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stats Grid --}}
        <div class="acm-stats-grid">
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f5f3ff; color:#7c3aed;">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total Servers</div>
                    <div class="acm-stat-val">{{ $stats['total'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Active Servers</div>
                    <div class="acm-stat-val">{{ $stats['active'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fef3c7; color:#d97706;">
                    <i class="bi bi-wrench-adjustable-circle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">In Maintenance</div>
                    <div class="acm-stat-val">{{ $stats['maintenance'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fef2f2; color:#dc2626;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Inactive Servers</div>
                    <div class="acm-stat-val">{{ $stats['inactive'] }}</div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="acm-card">
            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('accounts.master.servers.index') }}" class="acm-filter-bar">
                <div class="acm-search-wrap">
                    <i class="bi bi-search acm-search-ico"></i>
                    <input type="text" name="search" class="acm-search-input"
                           placeholder="Search by Server Name, Provider, Plan, IP, Specs, Remarks..."
                           value="{{ request('search') }}">
                </div>
                <select name="status" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
                <button type="submit" class="acm-btn acm-btn-primary" style="padding:8px 14px;">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('accounts.master.servers.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px;">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                @endif
            </form>

            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Server Name & Provider</th>
                            <th>IP Address</th>
                            <th>CPU & Disk Specs</th>
                            <th>Plan Type</th>
                            <th class="text-center">Mapped Domains</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servers as $server)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $servers->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <a href="{{ route('accounts.master.servers.show', $server->id) }}" style="font-weight:800; color:#0f172a; font-size:14px; text-decoration:none;" onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#0f172a'" title="Click to view server details & mapped domains">
                                        {{ $server->server_name }}
                                    </a>
                                    @if($server->provider_name)
                                        <span class="provider-badge">
                                            <i class="bi bi-cloud"></i> {{ $server->provider_name }}
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size:11px; color:#94a3b8; margin-top:2px;">
                                    Created {{ $server->created_at ? $server->created_at->format('M d, Y') : '—' }}
                                    @if($server->creator)
                                        by {{ $server->creator->name }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($server->ip_address)
                                    <div style="display:inline-flex; align-items:center; gap:6px;">
                                        <span class="code-chip" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                                            {{ $server->ip_address }}
                                        </span>
                                        <button type="button" class="copy-btn" onclick="copyText('{{ $server->ip_address }}', this)" title="Copy IP Address">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                @else
                                    <span style="color:#94a3b8; font-size:12px;">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                                    @if($server->cpu)
                                        <span class="spec-chip" title="CPU Specification">
                                            <i class="bi bi-cpu" style="color:#7c3aed;"></i> {{ $server->cpu }}
                                        </span>
                                    @endif
                                    @if($server->disk_space)
                                        <span class="spec-chip" title="Disk Space Storage">
                                            <i class="bi bi-hdd" style="color:#0284c7;"></i> {{ $server->disk_space }}
                                        </span>
                                    @endif
                                    @if(!$server->cpu && !$server->disk_space)
                                        <span style="color:#94a3b8; font-size:12px;">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($server->plan_type)
                                    <span class="plan-badge">
                                        <i class="bi bi-layers"></i> {{ $server->plan_type }}
                                    </span>
                                @else
                                    <span style="color:#94a3b8; font-size:12px;">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('accounts.master.servers.show', $server->id) }}" class="code-chip" style="text-decoration:none; background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; font-size:12px; font-weight:700;" title="View all mapped domains for {{ $server->server_name }}">
                                    <i class="bi bi-globe2"></i> {{ $server->domains_count }} {{ \Illuminate\Support\Str::plural('domain', $server->domains_count) }}
                                </a>
                            </td>
                            <td>
                                <span class="acm-badge status-{{ $server->status }}">
                                    @if($server->status === 'active')
                                        <i class="bi bi-check-circle"></i> Active
                                    @elseif($server->status === 'maintenance')
                                        <i class="bi bi-wrench-adjustable"></i> Maintenance
                                    @else
                                        <i class="bi bi-x-circle"></i> Inactive
                                    @endif
                                </span>
                            </td>
                            <td>
                                @if($server->remarks)
                                    <span title="{{ $server->remarks }}" style="color:#475569; font-size:12px; display:inline-block; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $server->remarks }}
                                    </span>
                                @else
                                    <span style="color:#94a3b8; font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    {{-- View Details Page --}}
                                    <a href="{{ route('accounts.master.servers.show', $server->id) }}" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;" title="View Server Details & Mapped Domains">
                                        <i class="bi bi-eye"></i> View
                                    </a>

                                    {{-- Edit --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openEditServerModal(@json($server))'
                                            title="Edit Server Details">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    {{-- Toggle Status --}}
                                    <form method="POST" action="{{ route('accounts.master.servers.toggle-status', $server->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active / Inactive">
                                            {{ $server->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('accounts.master.servers.destroy', $server->id) }}" onsubmit="return confirm('Are you sure you want to delete server: {{ addslashes($server->server_name) }}?')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Server">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:48px 20px; color:#64748b;">
                                <i class="bi bi-hdd-network" style="font-size:42px; color:#cbd5e1; display:block; margin-bottom:10px;"></i>
                                <div style="font-size:15px; font-weight:700; color:#0f172a;">No servers registered yet</div>
                                <div style="font-size:13px; color:#94a3b8; margin-top:4px;">Manage cloud servers, VPS, hosting providers and server specs in one place.</div>
                                <button type="button" class="acm-btn acm-btn-primary" style="margin-top:14px;" onclick="openAddServerModal()">
                                    <i class="bi bi-plus-lg"></i> Add Server
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($servers->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                    {{ $servers->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

{{-- ======================================================== --}}
{{-- MODAL: Add Server                                        --}}
{{-- ======================================================== --}}
<div id="addServerModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-hdd-network" style="color:#7c3aed;"></i>
                <span>Add Server</span>
            </div>
            <button type="button" class="modal-close" onclick="closeAddServerModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.servers.store') }}">
            @csrf
            <div class="modal-body">

                {{-- Banner --}}
                <div class="form-section-card">
                    <i class="bi bi-cloud-check" style="color:#7c3aed; font-size:20px; flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:13px; font-weight:800; color:#5b21b6;">Server & Hosting Specification</div>
                        <div style="font-size:12px; color:#6d28d9; margin-top:2px;">
                            Configure server node details, provider specs, and IP assignment for domain hosting tracking.
                        </div>
                    </div>
                </div>

                {{-- Server Name & Provider Name --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Server Name <span class="req">*</span></label>
                        <input type="text" name="server_name" class="form-input" placeholder="e.g. AWS Prod Web-01 / Hetzner CP" required value="{{ old('server_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Provider Name</label>
                        <input type="text" name="provider_name" class="form-input" placeholder="e.g. AWS, DigitalOcean, Hetzner, Hostinger" value="{{ old('provider_name') }}">
                    </div>
                </div>

                {{-- Plan Type & IP Address --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Plan Type</label>
                        <input type="text" name="plan_type" class="form-input" placeholder="e.g. Cloud VPS, Dedicated, Shared, Standard Droplet" value="{{ old('plan_type') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-input" placeholder="e.g. 192.168.1.100 or 203.0.113.195" value="{{ old('ip_address') }}" style="font-family:monospace;">
                    </div>
                </div>

                {{-- CPU & Disk Space --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">CPU</label>
                        <input type="text" name="cpu" class="form-input" placeholder="e.g. 4 vCPU / 8 Cores AMD EPYC" value="{{ old('cpu') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Disk Space</label>
                        <input type="text" name="disk_space" class="form-input" placeholder="e.g. 160 GB NVMe SSD / 1 TB HDD" value="{{ old('disk_space') }}">
                    </div>
                </div>

                {{-- Status --}}
                <div class="form-group">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                {{-- Remarks --}}
                <div class="form-group">
                    <label class="form-label">Remarks (Optional)</label>
                    <textarea name="remarks" class="form-input" rows="2" placeholder="e.g. Production web application host with automated weekly backups...">{{ old('remarks') }}</textarea>
                </div>

            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeAddServerModal()">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Save Server
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- MODAL: Edit Server                                       --}}
{{-- ======================================================== --}}
<div id="editServerModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#7c3aed;"></i>
                <span>Edit Server</span>
            </div>
            <button type="button" class="modal-close" onclick="closeEditServerModal()">&times;</button>
        </div>
        <form id="editServerForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">

                {{-- Banner --}}
                <div class="form-section-card">
                    <i class="bi bi-cloud-check" style="color:#7c3aed; font-size:20px; flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:13px; font-weight:800; color:#5b21b6;">Update Server Configuration</div>
                        <div style="font-size:12px; color:#6d28d9; margin-top:2px;">
                            Modify specs, plan, IP, or operational status for this server entry.
                        </div>
                    </div>
                </div>

                {{-- Server Name & Provider Name --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Server Name <span class="req">*</span></label>
                        <input type="text" name="server_name" id="edit_server_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Provider Name</label>
                        <input type="text" name="provider_name" id="edit_provider_name" class="form-input">
                    </div>
                </div>

                {{-- Plan Type & IP Address --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Plan Type</label>
                        <input type="text" name="plan_type" id="edit_plan_type" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" id="edit_ip_address" class="form-input" style="font-family:monospace;">
                    </div>
                </div>

                {{-- CPU & Disk Space --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">CPU</label>
                        <input type="text" name="cpu" id="edit_cpu" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Disk Space</label>
                        <input type="text" name="disk_space" id="edit_disk_space" class="form-input">
                    </div>
                </div>

                {{-- Status --}}
                <div class="form-group">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select name="status" id="edit_status" class="form-input" required>
                        <option value="active">Active</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                {{-- Remarks --}}
                <div class="form-group">
                    <label class="form-label">Remarks (Optional)</label>
                    <textarea name="remarks" id="edit_remarks" class="form-input" rows="2"></textarea>
                </div>

            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeEditServerModal()">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Update Server
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- MODAL: View Server Details                               --}}
{{-- ======================================================== --}}
<div id="viewServerModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-hdd-network" style="color:#7c3aed;"></i>
                <span id="view_modal_title">Server Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeViewServerModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Server Name</div>
                    <div class="detail-val" id="view_server_name"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Provider Name</div>
                    <div class="detail-val" id="view_provider_name"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">IP Address</div>
                    <div class="detail-val" style="font-family:monospace; display:flex; align-items:center; gap:6px;">
                        <span id="view_ip_address"></span>
                        <button type="button" class="copy-btn" id="view_copy_ip" title="Copy IP Address">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Plan Type</div>
                    <div class="detail-val" id="view_plan_type"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">CPU</div>
                    <div class="detail-val" id="view_cpu"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Disk Space</div>
                    <div class="detail-val" id="view_disk_space"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-val" id="view_status"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Created At</div>
                    <div class="detail-val" id="view_created_at" style="color:#475569;"></div>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Remarks</div>
                    <div class="detail-val" id="view_remarks" style="font-weight:500; color:#475569;"></div>
                </div>
            </div>

            {{-- Mapped Domains in Modal --}}
            <div style="border-top:1px solid #e2e8f0; padding-top:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                    <div style="font-size:13px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <i class="bi bi-globe2" style="color:#2563eb;"></i>
                        <span>Mapped Domains</span>
                        <span id="view_domains_count_badge" class="code-chip" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; font-size:11px;">0</span>
                    </div>
                    <a id="view_full_page_link" href="#" class="acm-btn acm-btn-outline" style="padding:4px 10px; font-size:11px;">
                        <i class="bi bi-box-arrow-up-right"></i> Open Full Server Page
                    </a>
                </div>
                <div id="view_domains_container" style="max-height:220px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; background:#fff;">
                    <!-- Dynamically populated via JS -->
                </div>
            </div>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc;">
            <a id="view_full_page_btn" href="#" class="acm-btn acm-btn-primary" style="padding:6px 14px; font-size:12px;">
                <i class="bi bi-eye"></i> View Full Server Page
            </a>
            <button type="button" class="acm-btn acm-btn-secondary" onclick="closeViewServerModal()">Close</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Modal Controls
function openAddServerModal() {
    document.getElementById('addServerModal').classList.add('active');
}
function closeAddServerModal() {
    document.getElementById('addServerModal').classList.remove('active');
}

function openEditServerModal(server) {
    const form = document.getElementById('editServerForm');
    form.action = `/accounts/master/servers/${server.id}`;

    document.getElementById('edit_server_name').value = server.server_name || '';
    document.getElementById('edit_provider_name').value = server.provider_name || '';
    document.getElementById('edit_plan_type').value = server.plan_type || '';
    document.getElementById('edit_ip_address').value = server.ip_address || '';
    document.getElementById('edit_cpu').value = server.cpu || '';
    document.getElementById('edit_disk_space').value = server.disk_space || '';
    document.getElementById('edit_status').value = server.status || 'active';
    document.getElementById('edit_remarks').value = server.remarks || '';

    document.getElementById('editServerModal').classList.add('active');
}
function closeEditServerModal() {
    document.getElementById('editServerModal').classList.remove('active');
}

function openViewServerModal(server) {
    document.getElementById('view_modal_title').innerText = (server.server_name || 'Server') + ' Details';
    document.getElementById('view_server_name').innerText = server.server_name || '—';
    document.getElementById('view_provider_name').innerText = server.provider_name || '—';
    document.getElementById('view_ip_address').innerText = server.ip_address || '—';
    document.getElementById('view_plan_type').innerText = server.plan_type || '—';
    document.getElementById('view_cpu').innerText = server.cpu || '—';
    document.getElementById('view_disk_space').innerText = server.disk_space || '—';
    document.getElementById('view_remarks').innerText = server.remarks || 'None';

    if (server.created_at) {
        try {
            const d = new Date(server.created_at);
            document.getElementById('view_created_at').innerText = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        } catch(e) {
            document.getElementById('view_created_at').innerText = server.created_at;
        }
    } else {
        document.getElementById('view_created_at').innerText = '—';
    }

    let statusBadge = '<span class="acm-badge status-active">Active</span>';
    if (server.status === 'maintenance') {
        statusBadge = '<span class="acm-badge status-maintenance">Maintenance</span>';
    } else if (server.status === 'inactive') {
        statusBadge = '<span class="acm-badge status-inactive">Inactive</span>';
    }
    document.getElementById('view_status').innerHTML = statusBadge;

    // Bind copy triggers
    const copyBtn = document.getElementById('view_copy_ip');
    if (server.ip_address) {
        copyBtn.style.display = 'inline-block';
        copyBtn.onclick = function() { copyText(server.ip_address, this); };
    } else {
        copyBtn.style.display = 'none';
    }

    // Full page link
    const fullPageUrl = `/accounts/master/servers/${server.id}`;
    const linkEl = document.getElementById('view_full_page_link');
    const btnEl = document.getElementById('view_full_page_btn');
    if (linkEl) linkEl.href = fullPageUrl;
    if (btnEl) btnEl.href = fullPageUrl;

    // Render Mapped Domains
    const domainsContainer = document.getElementById('view_domains_container');
    const badgeEl = document.getElementById('view_domains_count_badge');
    const hostings = server.hostings || [];
    let domains = [];
    hostings.forEach(h => {
        (h.domains || []).forEach(d => {
            domains.push({
                ...d,
                hosting_name: h.hosting_name
            });
        });
    });

    if (badgeEl) badgeEl.innerText = domains.length;

    if (domainsContainer) {
        if (domains.length === 0) {
            domainsContainer.innerHTML = `
                <div style="padding:22px 16px; text-align:center; color:#94a3b8; font-size:12px;">
                    <i class="bi bi-globe" style="font-size:24px; display:block; margin-bottom:6px; color:#cbd5e1;"></i>
                    No domains currently mapped to hostings on this server.
                </div>
            `;
        } else {
            let html = `
                <table style="width:100%; border-collapse:collapse; font-size:12px;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:10.5px;">
                            <th style="padding:8px 12px; text-align:left;">Domain Name</th>
                            <th style="padding:8px 12px; text-align:left;">Hosting</th>
                            <th style="padding:8px 12px; text-align:left;">Status</th>
                            <th style="padding:8px 12px; text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            domains.forEach(d => {
                const statusBadge = (d.status && d.status.toUpperCase() === 'ACTIVE') 
                    ? '<span class="acm-badge status-active" style="font-size:10px; padding:1px 6px;">Active</span>' 
                    : `<span class="acm-badge" style="background:#f1f5f9; color:#475569; font-size:10px; padding:1px 6px;">${d.status || 'Active'}</span>`;

                html += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 12px; font-weight:700; color:#0f172a;">
                            ${d.domain_name}
                        </td>
                        <td style="padding:8px 12px; color:#6d28d9; font-weight:600;">
                            ${d.hosting_name || '—'}
                        </td>
                        <td style="padding:8px 12px;">
                            ${statusBadge}
                        </td>
                        <td style="padding:8px 12px; text-align:right;">
                            <a href="/accounts/domains-hosting/domains/${d.id}" class="acm-btn acm-btn-secondary" style="padding:3px 8px; font-size:10.5px; text-decoration:none;" title="View Domain Details">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </td>
                    </tr>
                `;
            });
            html += `</tbody></table>`;
            domainsContainer.innerHTML = html;
        }
    }

    document.getElementById('viewServerModal').classList.add('active');
}
function closeViewServerModal() {
    document.getElementById('viewServerModal').classList.remove('active');
}

// Copy to clipboard helper
function copyText(text, btnElement) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        const original = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="bi bi-check2" style="color:#16a34a;"></i>';
        setTimeout(() => { btnElement.innerHTML = original; }, 1500);
    }).catch(function(err) {
        console.error('Copy failed', err);
    });
}

// Close modals when clicking backdrop
window.addEventListener('click', function(e) {
    ['addServerModal', 'editServerModal', 'viewServerModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
</script>
@endpush
