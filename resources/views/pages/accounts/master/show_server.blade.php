@extends('layouts.app')

@section('title', 'Server Details - ' . $server->server_name)

@push('styles')
<style>
.acm-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.acm-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.acm-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.acm-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.acm-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.acm-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.acm-breadcrumbs a:hover { color:#7c3aed; }
.acm-breadcrumbs .sep { color:#ccc; }
.acm-breadcrumbs .current { color:#424242; font-weight:600; }
.acm-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.acm-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .acm-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .acm-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#7c3aed,#9333ea); color:#fff; box-shadow:0 4px 14px rgba(124,58,237,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(124,58,237,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#7c3aed; color:#7c3aed; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:capitalize; }
.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-maintenance { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.status-inactive { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.acm-card-header { padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.acm-card-title { font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.acm-card-body { padding:22px 24px; }

.detail-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; }
.detail-item { background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0; }
.detail-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.detail-val { font-size:13.5px; font-weight:700; color:#0f172a; word-break:break-word; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acm-tbl tbody tr:hover td { background:#faf5ff; }

.code-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#0f172a; font-family:monospace; font-size:12px; font-weight:700; border:1px solid #e2e8f0; }
.spec-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f8fafc; color:#475569; font-size:11px; font-weight:700; border:1px solid #e2e8f0; }
.provider-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f5f3ff; color:#6d28d9; font-size:11px; font-weight:700; border:1px solid #ddd6fe; }
.plan-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:700; border:1px solid #bfdbfe; }

.copy-btn { border:none; background:none; color:#64748b; cursor:pointer; padding:2px 4px; border-radius:4px; font-size:12px; }
.copy-btn:hover { color:#7c3aed; background:#fff; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:94%; max-width:640px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:16px; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .form-grid-2 { grid-template-columns:1fr; } }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-label span.req { color:#ef4444; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1); }
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Topbar --}}
    <div class="acm-topbar">
        <div class="acm-topbar-left">
            <div class="acm-title">
                <i class="bi bi-hdd-network-fill" style="color:#7c3aed;"></i>
                <span>{{ $server->server_name }}</span>
                <span class="acm-badge status-{{ $server->status }}" style="margin-left:6px;">
                    @if($server->status === 'active')
                        <i class="bi bi-check-circle"></i> Active
                    @elseif($server->status === 'maintenance')
                        <i class="bi bi-wrench-adjustable"></i> Maintenance
                    @else
                        <i class="bi bi-x-circle"></i> Inactive
                    @endif
                </span>
            </div>
            <div class="acm-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.servers.index') }}">Server Master</a>
                <span class="sep">/</span>
                <span class="current">{{ $server->server_name }}</span>
            </div>
        </div>

        <div class="acm-topbar-actions">
            <a href="{{ route('accounts.master.servers.index') }}" class="acm-btn acm-btn-secondary" title="Back to Servers List">
                <i class="bi bi-arrow-left"></i> Servers List
            </a>
            <a href="{{ route('accounts.domains-hosting.index') }}" class="acm-btn acm-btn-outline" title="Open Domains & Hosting Module">
                <i class="bi bi-globe"></i> Domains & Hosting
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openEditServerModal()">
                <i class="bi bi-pencil-square"></i> Edit Server
            </button>
        </div>
    </div>

    {{-- Body --}}
    <div class="acm-body">

        {{-- Alerts --}}
        @if(session('success'))
            <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Stats Grid --}}
        <div class="acm-stats-grid">
            {{-- Mapped Domains Stat --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#eff6ff; color:#2563eb;">
                    <i class="bi bi-globe2"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Mapped Domains</div>
                    <div class="acm-stat-val" style="color:#2563eb;">{{ $stats['total_domains'] }}</div>
                    <div style="font-size:11px; color:#64748b; font-weight:600; margin-top:2px;">
                        {{ $stats['active_domains'] }} active &bull; {{ $stats['expiring_soon'] }} expiring soon
                    </div>
                </div>
            </div>

            {{-- Active Domains Stat --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#dcfce7; color:#16a34a;">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Active Domains</div>
                    <div class="acm-stat-val" style="color:#16a34a;">{{ $stats['active_domains'] }}</div>
                    <div style="font-size:11px; color:#64748b; font-weight:600; margin-top:2px;">
                        {{ $stats['expiring_soon'] }} expiring within 30 days
                    </div>
                </div>
            </div>

            {{-- Server Specs Stat --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                    <i class="bi bi-cpu"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Hardware Specs</div>
                    <div class="acm-stat-val" style="font-size:16px; color:#0f172a;">
                        {{ $server->cpu ?: 'CPU N/A' }}
                    </div>
                    <div style="font-size:11px; color:#64748b; font-weight:600; margin-top:2px;">
                        Disk: {{ $server->disk_space ?: 'Disk space N/A' }}
                    </div>
                </div>
            </div>

            {{-- Provider & IP Stat --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fff7ed; color:#fe5f04;">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Provider & IP</div>
                    <div class="acm-stat-val" style="font-size:15px; font-family:monospace; color:#0f172a;">
                        {{ $server->ip_address ?: 'No IP configured' }}
                    </div>
                    <div style="font-size:11px; color:#64748b; font-weight:600; margin-top:2px;">
                        Provider: {{ $server->provider_name ?: 'Self / Custom' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Server Specifications Card --}}
        <div class="acm-card">
            <div class="acm-card-header">
                <div class="acm-card-title">
                    <i class="bi bi-info-circle-fill" style="color:#7c3aed;"></i>
                    <span>Server Information & Configuration</span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    @if($server->ip_address)
                        <button type="button" class="acm-btn acm-btn-outline" style="padding:4px 10px; font-size:11px;" onclick="copyText('{{ $server->ip_address }}', this)">
                            <i class="bi bi-clipboard"></i> Copy IP
                        </button>
                    @endif
                </div>
            </div>
            <div class="acm-card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Server Name</div>
                        <div class="detail-val" style="color:#7c3aed;">{{ $server->server_name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Provider Name</div>
                        <div class="detail-val">
                            @if($server->provider_name)
                                <span class="provider-badge"><i class="bi bi-cloud"></i> {{ $server->provider_name }}</span>
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">IP Address</div>
                        <div class="detail-val" style="font-family:monospace;">
                            @if($server->ip_address)
                                <span class="code-chip" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                                    {{ $server->ip_address }}
                                </span>
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Plan Type</div>
                        <div class="detail-val">
                            @if($server->plan_type)
                                <span class="plan-badge"><i class="bi bi-layers"></i> {{ $server->plan_type }}</span>
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">CPU Specs</div>
                        <div class="detail-val">
                            @if($server->cpu)
                                <span class="spec-chip"><i class="bi bi-cpu" style="color:#7c3aed;"></i> {{ $server->cpu }}</span>
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Disk Space</div>
                        <div class="detail-val">
                            @if($server->disk_space)
                                <span class="spec-chip"><i class="bi bi-hdd" style="color:#0284c7;"></i> {{ $server->disk_space }}</span>
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Created At</div>
                        <div class="detail-val" style="color:#475569; font-weight:600;">
                            {{ $server->created_at ? $server->created_at->format('M d, Y h:i A') : '—' }}
                            @if($server->creator)
                                <span style="font-size:11px; color:#94a3b8; font-weight:400;">({{ $server->creator->name }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Remarks / Notes</div>
                        <div class="detail-val" style="color:#475569; font-weight:500;">
                            {{ $server->remarks ?: 'No notes or remarks recorded.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAPPED DOMAINS SECTION --}}
        <div class="acm-card">
            <div class="acm-card-header">
                <div>
                    <div class="acm-card-title">
                        <i class="bi bi-globe2" style="color:#2563eb;"></i>
                        <span>Domains Hosted on this Server ({{ $domains->count() }})</span>
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                        All domains hosted on <strong>{{ $server->server_name }}</strong> via associated hosting accounts
                    </div>
                </div>

                @if($domains->isNotEmpty())
                    <div style="position:relative; width:260px;">
                        <input type="text" id="domainSearchInput" onkeyup="filterDomainsTable()" placeholder="Search mapped domains..." class="form-input" style="padding-left:32px; font-size:12px; height:34px;">
                        <i class="bi bi-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                    </div>
                @endif
            </div>

            <div style="overflow-x:auto;">
                <table class="acm-tbl" id="mappedDomainsTable">
                    <thead>
                        <tr>
                            <th style="width:44px;" class="text-center">#</th>
                            <th>Domain Name</th>
                            <th>Mapped Hosting Account</th>
                            <th>Client / Lead</th>
                            <th>Registrar</th>
                            <th>Expiration Date</th>
                            <th>Status</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($domains as $index => $dom)
                            <tr>
                                <td class="text-center" style="color:#94a3b8; font-weight:700;">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('accounts.domains-hosting.domains.show', $dom->id) }}" style="color:#0f172a; text-decoration:none; font-weight:800; font-size:13.5px;" onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#0f172a'" title="View Domain Details">
                                        {{ $dom->domain_name }}
                                    </a>
                                </td>
                                <td>
                                    @if($dom->hosting)
                                        <a href="{{ route('accounts.domains-hosting.hostings.show', $dom->hosting->id) }}" class="code-chip" style="text-decoration:none; background:#f5f3ff; color:#6d28d9; border-color:#ddd6fe;" title="View Hosting Account">
                                            <i class="bi bi-hdd-network"></i> {{ $dom->hosting->hosting_name }}
                                        </a>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($dom->lead)
                                        <div style="font-weight:700; color:#0f172a;">{{ $dom->lead->name }}</div>
                                        @if($dom->lead->mobile_number)
                                            <div style="font-size:11px; color:#64748b;">{{ $dom->lead->mobile_number }}</div>
                                        @endif
                                    @elseif($dom->client_name)
                                        <span style="font-weight:600; color:#475569;">{{ $dom->client_name }}</span>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="color:#475569; font-weight:600;">{{ $dom->registrar ?: '—' }}</span>
                                </td>
                                <td>
                                    @if($dom->expires_at)
                                        <div style="font-weight:700; color:#0f172a;">
                                            {{ $dom->expires_at->format('d M, Y') }}
                                        </div>
                                        @php
                                            $dRemaining = $dom->days_until_expiration;
                                        @endphp
                                        @if($dRemaining !== null)
                                            @if($dRemaining < 0)
                                                <span style="font-size:11px; font-weight:700; color:#dc2626;">Expired {{ abs($dRemaining) }}d ago</span>
                                            @elseif($dRemaining <= 30)
                                                <span style="font-size:11px; font-weight:700; color:#d97706;">Expires in {{ $dRemaining }}d</span>
                                            @else
                                                <span style="font-size:11px; color:#16a34a; font-weight:600;">In {{ $dRemaining }} days</span>
                                            @endif
                                        @endif
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $st = strtoupper($dom->status ?? 'ACTIVE');
                                    @endphp
                                    @if($st === 'ACTIVE')
                                        <span class="acm-badge status-active">Active</span>
                                    @elseif($st === 'EXPIRED')
                                        <span class="acm-badge status-inactive">Expired</span>
                                    @elseif($st === 'PENDING')
                                        <span class="acm-badge status-maintenance">Pending</span>
                                    @else
                                        <span class="acm-badge" style="background:#f1f5f9; color:#475569;">{{ $st }}</span>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    <a href="{{ route('accounts.domains-hosting.domains.show', $dom->id) }}" class="acm-btn acm-btn-secondary" style="padding:4px 10px; font-size:11px;" title="View Domain Details">
                                        <i class="bi bi-eye"></i> View Domain
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:44px 20px;">
                                    <div style="font-size:36px; color:#cbd5e1; margin-bottom:8px;"><i class="bi bi-globe"></i></div>
                                    <div style="font-size:15px; font-weight:700; color:#334155;">No domains mapped to this server yet</div>
                                    <div style="font-size:13px; color:#94a3b8; margin-top:4px;">
                                        Domains mapped to hosting accounts hosted on <strong>{{ $server->server_name }}</strong> will be listed here.
                                    </div>
                                    <a href="{{ route('accounts.domains-hosting.index') }}" class="acm-btn acm-btn-outline" style="margin-top:14px;">
                                        <i class="bi bi-link-45deg"></i> Go to Domains & Hosting to Map Domains
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- MODAL: Edit Server Details --}}
<div id="editServerModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#7c3aed;"></i>
                <span>Edit Server Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeEditServerModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.servers.update', $server->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Server Name <span class="req">*</span></label>
                        <input type="text" name="server_name" value="{{ $server->server_name }}" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Provider Name</label>
                        <input type="text" name="provider_name" value="{{ $server->provider_name }}" class="form-input">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Plan Type</label>
                        <input type="text" name="plan_type" value="{{ $server->plan_type }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" value="{{ $server->ip_address }}" class="form-input" style="font-family:monospace;">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">CPU</label>
                        <input type="text" name="cpu" value="{{ $server->cpu }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Disk Space</label>
                        <input type="text" name="disk_space" value="{{ $server->disk_space }}" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status <span class="req">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="active" {{ $server->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="maintenance" {{ $server->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="inactive" {{ $server->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-input" rows="2">{{ $server->remarks }}</textarea>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeEditServerModal()">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditServerModal() {
    document.getElementById('editServerModal').classList.add('active');
}
function closeEditServerModal() {
    document.getElementById('editServerModal').classList.remove('active');
}

function copyText(text, btnElement) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        const original = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="bi bi-check2" style="color:#16a34a;"></i> Copied!';
        setTimeout(() => { btnElement.innerHTML = original; }, 1500);
    });
}

function filterDomainsTable() {
    const input = document.getElementById('domainSearchInput');
    const filter = input ? input.value.toLowerCase() : '';
    const table = document.getElementById('mappedDomainsTable');
    if (!table) return;
    const trs = table.getElementsByTagName('tr');

    for (let i = 1; i < trs.length; i++) {
        const rowText = trs[i].textContent.toLowerCase();
        trs[i].style.display = rowText.includes(filter) ? '' : 'none';
    }
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('editServerModal');
    if (modal && e.target === modal) {
        modal.classList.remove('active');
    }
});
</script>
@endpush
