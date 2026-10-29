@extends('layouts.app')

@section('title', 'Hosting Details - ' . $hosting->hosting_name)

@push('styles')
<style>
.dh-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.dh-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.dh-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.dh-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.dh-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.dh-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.dh-breadcrumbs a:hover { color:#fe5f04; }
.dh-breadcrumbs .sep { color:#ccc; }
.dh-breadcrumbs .current { color:#424242; font-weight:600; }
.dh-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.dh-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .dh-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .dh-body { padding:16px 20px 24px; }
}

.dh-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.dh-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.dh-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.dh-btn-secondary { background:#f1f5f9; color:#334155; }
.dh-btn-secondary:hover { background:#e2e8f0; }
.dh-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.dh-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.dh-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.dh-btn-danger:hover { background:#fca5a5; }

.dh-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-expired { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.status-pending { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }

.dh-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
.dh-stat-card { background:#fff; border-radius:14px; padding:18px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.dh-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.dh-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.dh-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.2; }

.dh-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.dh-card-header { padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.dh-card-title { font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.dh-card-body { padding:24px; }

.detail-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px; }
.detail-item { display:flex; flex-direction:column; gap:4px; }
.detail-label { font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.03em; }
.detail-val { font-size:14px; font-weight:600; color:#0f172a; }

.dh-tbl { width:100%; border-collapse:collapse; }
.dh-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.dh-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:92%; max-width:540px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:16px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; width:100%; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Mapped Lead Box */
.lead-card-box { border-radius:12px; border:1px solid #e2e8f0; padding:18px 20px; background:#f8fafc; display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:16px; }
.lead-avatar { width:46px; height:46px; border-radius:50%; background:linear-gradient(135deg,#0284c7,#38bdf8); color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:800; flex-shrink:0; }
</style>
@endpush

@section('content')
<div class="dh-page">

    {{-- Standard Uniform Topbar --}}
    <div class="dh-topbar">
        <div class="dh-topbar-left">
            <div class="dh-title">
                <i class="bi bi-hdd-network" style="color:#fe5f04;"></i>
                <span>{{ $hosting->hosting_name }}</span>
                @php
                    $days = $hosting->days_until_renewal;
                    $isExpiring = ($days !== null && $days >= 0 && $days <= 30);
                    $isExpired = ($days !== null && $days < 0);
                @endphp
                @if($isExpired)
                    <span class="dh-badge status-expired">EXPIRED</span>
                @elseif($isExpiring)
                    <span class="dh-badge status-pending">RENEWAL DUE</span>
                @elseif($hosting->status === 'ACTIVE')
                    <span class="dh-badge status-active">ACTIVE</span>
                @else
                    <span class="dh-badge status-pending">{{ $hosting->status }}</span>
                @endif
                <span style="font-size:11px; padding:2px 8px; background:#eff6ff; color:#2563eb; border-radius:6px; font-weight:700;">{{ $hosting->plan_type }}</span>
            </div>
            <div class="dh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.domains-hosting.index', ['tab' => 'hostings']) }}">Domains & Hosting</a>
                <span class="sep">/</span>
                <span class="current">{{ $hosting->hosting_name }}</span>
            </div>
        </div>
        <div class="dh-topbar-actions">
            <a href="{{ route('accounts.domains-hosting.index', ['tab' => 'hostings']) }}" class="dh-btn dh-btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button type="button" class="dh-btn" style="background:linear-gradient(135deg,#7c3aed,#9333ea); color:#fff;" onclick="openMapDomainsModal()">
                <i class="bi bi-globe"></i> Map Domains
            </button>
            <button type="button" class="dh-btn dh-btn-outline" onclick="openMigrateModal()">
                <i class="bi bi-arrow-left-right" style="color:#0284c7;"></i> Migrate Hosting
            </button>
            <button type="button" class="dh-btn dh-btn-primary" onclick="openAddRenewalModal()">
                <i class="bi bi-plus-circle"></i> Add Renewal Entry
            </button>
            <button type="button" class="dh-btn dh-btn-secondary" onclick="openEditHostingModal()">
                <i class="bi bi-pencil-square"></i> Edit Hosting
            </button>
        </div>
    </div>

    <div class="dh-body">

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ⚠️ {{ session('error') }}
        </div>
    @endif
    @if(isset($errors) && $errors->any())
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="dh-stats-grid">
        <div class="dh-stat-card">
            <div class="dh-stat-icon" style="background:#eef2ff; color:#4f46e5;">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div>
                <div class="dh-stat-label">Total Renewals</div>
                <div class="dh-stat-val">{{ $renewalsCount }} {{ Str::plural('Time', $renewalsCount) }}</div>
            </div>
        </div>

        <div class="dh-stat-card">
            <div class="dh-stat-icon" style="background:#{{ $isExpired ? 'fee2e2' : ($isExpiring ? 'fef3c7' : 'f0fdf4') }}; color:#{{ $isExpired ? 'dc2626' : ($isExpiring ? 'd97706' : '16a34a') }};">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div>
                <div class="dh-stat-label">Current Renewal Date</div>
                <div class="dh-stat-val" style="font-size:18px;">
                    {{ $hosting->renewal_date ? $hosting->renewal_date->format('d M, Y') : 'Not Set' }}
                </div>
                @if($hosting->renewal_date)
                    <div style="font-size:11px; font-weight:700; color:#{{ $isExpired ? 'dc2626' : ($isExpiring ? 'd97706' : '16a34a') }}; margin-top:3px;">
                        @if($isExpired)
                            Expired {{ abs($days) }} days ago
                        @elseif($days !== null)
                            {{ $days }} days remaining
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="dh-stat-card">
            <div class="dh-stat-icon" style="background:#ecfdf5; color:#059669;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="dh-stat-label">Total Renewal Spend</div>
                <div class="dh-stat-val">₹{{ number_format($totalRenewalSpend > 0 ? $totalRenewalSpend : ($hosting->renewal_amount ?? 0), 2) }}</div>
            </div>
        </div>

        <div class="dh-stat-card">
            <div class="dh-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-globe"></i>
            </div>
            <div>
                <div class="dh-stat-label">Mapped Domains</div>
                <div class="dh-stat-val" style="font-size:18px; font-weight:800; color:#1d4ed8;">
                    {{ $hosting->domains->count() }} {{ Str::plural('Domain', $hosting->domains->count()) }}
                </div>
                <div style="font-size:11px; color:#64748b; margin-top:3px;">
                    Hosted on this server
                </div>
            </div>
        </div>

        <div class="dh-stat-card">
            <div class="dh-stat-icon" style="background:#f0f9ff; color:#0284c7;">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
                <div class="dh-stat-label">Mapped CRM Lead</div>
                <div class="dh-stat-val" style="font-size:15px; font-weight:700; color:#0369a1;">
                    {{ $hosting->lead ? ($hosting->lead->contact_name ?: $hosting->lead->company_name) : ($hosting->client_name ?: 'Not Mapped') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Hosting Overview & Lead Mapping Row --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:20px;">

        {{-- Hosting Overview Card --}}
        <div class="dh-card">
            <div class="dh-card-header">
                <div class="dh-card-title">
                    <i class="bi bi-info-circle-fill" style="color:#fe5f04;"></i> Hosting Overview
                </div>
                <button type="button" class="dh-btn dh-btn-secondary" style="padding:4px 10px; font-size:11px;" onclick="openEditHostingModal()">
                    ✏️ Edit Details
                </button>
            </div>
            <div class="dh-card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Hosting Name</span>
                        <span class="detail-val" style="font-size:16px; font-weight:800; color:#0f172a;">{{ $hosting->hosting_name }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Provider</span>
                        <span class="detail-val">{{ $hosting->provider }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Plan Type</span>
                        <span class="detail-val">{{ $hosting->plan_type }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Allocated Storage</span>
                        <span class="detail-val">
                            @if($hosting->allocated_storage)
                                <code style="background:#f8fafc; border:1px solid #e2e8f0; padding:2px 8px; border-radius:6px; font-size:13px; font-weight:700; color:#0f172a;">
                                    <i class="bi bi-hdd" style="color:#0284c7;"></i> {{ $hosting->allocated_storage }}
                                </code>
                            @else
                                <span style="color:#94a3b8;">Not specified</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Server IP Address</span>
                        <span class="detail-val">
                            <code style="background:#f1f5f9; padding:3px 8px; border-radius:6px; font-size:13px;">{{ $hosting->ip_address ?: 'Not assigned' }}</code>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-val">
                            @if($isExpired)
                                <span class="dh-badge status-expired">EXPIRED</span>
                            @elseif($isExpiring)
                                <span class="dh-badge status-pending">RENEWAL DUE</span>
                            @elseif($hosting->status === 'ACTIVE')
                                <span class="dh-badge status-active">ACTIVE</span>
                            @else
                                <span class="dh-badge status-pending">{{ $hosting->status }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Next Renewal Date</span>
                        <span class="detail-val" style="font-weight:700; color:#0f172a;">
                            {{ $hosting->renewal_date ? $hosting->renewal_date->format('d M, Y') : '—' }}
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Renewal Amount</span>
                        <span class="detail-val" style="font-weight:800; color:#15803d;">
                            {{ $hosting->renewal_amount ? '₹'.number_format($hosting->renewal_amount, 2) : '—' }}
                        </span>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <span class="detail-label">Internal Notes / Remarks</span>
                        <span class="detail-val" style="color:#475569; font-weight:500;">
                            {{ $hosting->notes ?: 'No notes added for this hosting.' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mapped Lead Card --}}
        <div class="dh-card">
            <div class="dh-card-header">
                <div class="dh-card-title">
                    <i class="bi bi-people-fill" style="color:#0284c7;"></i> Mapped Client / Lead
                </div>
                <button type="button" class="dh-btn dh-btn-primary" style="padding:5px 12px; font-size:12px;" onclick="openMigrateModal()">
                    <i class="bi bi-arrow-left-right"></i> Migrate Hosting
                </button>
            </div>
            <div class="dh-card-body">
                @if($hosting->lead)
                    <div class="lead-card-box">
                        <div style="display:flex; align-items:flex-start; gap:14px;">
                            <div class="lead-avatar">
                                {{ strtoupper(substr($hosting->lead->contact_name ?: ($hosting->lead->company_name ?: 'L'), 0, 1)) }}
                            </div>
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <div style="font-size:16px; font-weight:800; color:#0f172a;">
                                    {{ $hosting->lead->contact_name ?: 'Unnamed Contact' }}
                                </div>
                                @if($hosting->lead->company_name)
                                    <div style="font-size:13px; font-weight:600; color:#475569;">
                                        🏢 {{ $hosting->lead->company_name }}
                                    </div>
                                @endif
                                <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:4px; font-size:12px; color:#64748b;">
                                    @if($hosting->lead->mobile_number)
                                        <a href="tel:{{ $hosting->lead->mobile_number }}" style="color:#0284c7; text-decoration:none; font-weight:600;">
                                            📞 {{ $hosting->lead->mobile_number }}
                                        </a>
                                    @endif
                                    @if($hosting->lead->email)
                                        <a href="mailto:{{ $hosting->lead->email }}" style="color:#0284c7; text-decoration:none; font-weight:600;">
                                            ✉️ {{ $hosting->lead->email }}
                                        </a>
                                    @endif
                                </div>
                                <div style="margin-top:6px;">
                                    <span style="font-size:11px; padding:2px 8px; border-radius:999px; background:#e0f2fe; color:#0369a1; font-weight:700;">
                                        Lead #{{ $hosting->lead->id }} • {{ ucfirst($hosting->lead->lead_status ?? 'Active') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <button type="button" class="dh-btn dh-btn-outline" style="font-size:11px; padding:6px 12px;" onclick="openMigrateModal()">
                                Change / Re-migrate
                            </button>
                            <form method="POST" action="{{ route('accounts.domains-hosting.hostings.unlink-lead', $hosting->id) }}" onsubmit="return confirm('Are you sure you want to unlink this lead from this hosting?')" style="margin:0;">
                                @csrf
                                <button type="submit" class="dh-btn dh-btn-danger" style="font-size:11px; padding:6px 12px; width:100%;">
                                    Unlink Lead
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div style="text-align:center; padding:28px 20px; border:2px dashed #cbd5e1; border-radius:12px; background:#f8fafc;">
                        <i class="bi bi-person-x" style="font-size:36px; color:#94a3b8; display:block; margin-bottom:8px;"></i>
                        <div style="font-size:14px; font-weight:700; color:#334155; margin-bottom:4px;">No CRM Lead Mapped Yet</div>
                        <div style="font-size:12px; color:#64748b; margin-bottom:16px;">
                            Client Name fallback: <strong>{{ $hosting->client_name ?: 'None' }}</strong>
                        </div>
                        <button type="button" class="dh-btn dh-btn-primary" onclick="openMigrateModal()">
                            <i class="bi bi-arrow-left-right"></i> Migrate Hosting to Lead
                        </button>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Mapped Domains Card --}}
    <div class="dh-card">
        <div class="dh-card-header">
            <div>
                <div class="dh-card-title">
                    <i class="bi bi-globe" style="color:#0284c7;"></i> Mapped Domains Hosted on this Server
                    <span style="font-size:12px; font-weight:700; padding:2px 8px; border-radius:999px; background:#eff6ff; color:#1d4ed8; margin-left:6px; border:1px solid #bfdbfe;">
                        {{ $hosting->domains->count() }} {{ Str::plural('Domain', $hosting->domains->count()) }}
                    </span>
                </div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    All customer websites and domains hosted on this hosting account and its server
                </div>
            </div>
            <button type="button" class="dh-btn dh-btn-primary" style="background:linear-gradient(135deg,#7c3aed,#9333ea);" onclick="openMapDomainsModal()">
                <i class="bi bi-plus-lg"></i> Map Domains to this Hosting
            </button>
        </div>

        @if($hosting->domains->isEmpty())
            <div style="text-align:center; padding:45px 20px; color:#94a3b8;">
                <i class="bi bi-globe" style="font-size:42px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                <div style="font-size:15px; font-weight:700; color:#475569; margin-bottom:4px;">No Domains Mapped to this Hosting Yet</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:16px;">
                    Map one or multiple domains to <strong>{{ $hosting->hosting_name }}</strong> so you can track which websites are running on this server.
                </div>
                <button type="button" class="dh-btn dh-btn-primary" style="background:linear-gradient(135deg,#7c3aed,#9333ea);" onclick="openMapDomainsModal()">
                    <i class="bi bi-plus-lg"></i> Map Domains Now
                </button>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="dh-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Domain Name</th>
                            <th>Registrar</th>
                            <th>Status</th>
                            <th>Expiration Date</th>
                            <th>Client / Project</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hosting->domains as $domainItem)
                        @php
                            $dDays = $domainItem->days_until_expiration;
                            $dExpiring = ($dDays !== null && $dDays >= 0 && $dDays <= 30);
                            $dExpired = ($dDays !== null && $dDays < 0);
                        @endphp
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">#{{ $domainItem->id }}</td>
                            <td>
                                <a href="{{ route('accounts.domains-hosting.domains.show', $domainItem->id) }}" style="font-size:14px; font-weight:800; color:#0f172a; text-decoration:none;" onmouseover="this.style.color='#fe5f04'" onmouseout="this.style.color='#0f172a'">
                                    <i class="bi bi-globe" style="color:#0284c7; margin-right:4px;"></i> {{ $domainItem->domain_name }}
                                </a>
                            </td>
                            <td><span style="font-weight:600; color:#475569;">{{ $domainItem->registrar }}</span></td>
                            <td>
                                @if($dExpired)
                                    <span class="dh-badge status-expired">EXPIRED</span>
                                @elseif($dExpiring)
                                    <span class="dh-badge status-pending">EXPIRING SOON</span>
                                @elseif($domainItem->status === 'ACTIVE')
                                    <span class="dh-badge status-active">ACTIVE</span>
                                @else
                                    <span class="dh-badge status-pending">{{ $domainItem->status }}</span>
                                @endif
                            </td>
                            <td style="font-weight:700; white-space:nowrap;">
                                @if($domainItem->expires_at)
                                    <div>{{ $domainItem->expires_at->format('d M, Y') }}</div>
                                    @if($dExpired)
                                        <span style="font-size:11px; color:#dc2626;">({{ abs($dDays) }} days ago)</span>
                                    @elseif($dDays !== null)
                                        <span style="font-size:11px; color:{{ $dExpiring ? '#d97706' : '#16a34a' }};">({{ $dDays }} days left)</span>
                                    @endif
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($domainItem->lead)
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700;">
                                        <i class="bi bi-person-check-fill"></i> {{ $domainItem->lead->contact_name ?: $domainItem->lead->company_name }}
                                    </span>
                                @else
                                    {{ $domainItem->client_name ?: '—' }}
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a href="{{ route('accounts.domains-hosting.domains.show', $domainItem->id) }}" class="dh-btn dh-btn-outline" style="padding:4px 10px; font-size:11px;" title="View Domain Details">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <form method="POST" action="{{ route('accounts.domains-hosting.hostings.unlink-domain', [$hosting->id, $domainItem->id]) }}" onsubmit="return confirm('Are you sure you want to unlink domain {{ $domainItem->domain_name }} from this hosting?')" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="dh-btn dh-btn-danger" style="padding:4px 8px; font-size:11px;" title="Unlink from this Hosting">
                                            Unlink
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Hosting Renewals History Card --}}
    <div class="dh-card">
        <div class="dh-card-header">
            <div>
                <div class="dh-card-title">
                    <i class="bi bi-clock-history" style="color:#fe5f04;"></i> Hosting Renewal History
                    <span style="font-size:12px; font-weight:700; padding:2px 8px; border-radius:999px; background:#eef2ff; color:#4f46e5; margin-left:6px;">
                        {{ $renewalsCount }} {{ Str::plural('Entry', $renewalsCount) }}
                    </span>
                </div>
                <div class="dh-sub" style="margin-top:2px;">
                    Track each time this hosting plan was renewed along with renewal dates, next renewal dates, and renewal fees
                </div>
            </div>
            <button type="button" class="dh-btn dh-btn-primary" onclick="openAddRenewalModal()">
                <i class="bi bi-plus-lg"></i> Add Renewal Entry
            </button>
        </div>

        @if($hosting->renewals->isEmpty())
            <div style="text-align:center; padding:45px 20px; color:#94a3b8;">
                <i class="bi bi-calendar-x" style="font-size:40px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                <div style="font-size:15px; font-weight:700; color:#475569; margin-bottom:4px;">No Renewal History Recorded Yet</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:16px;">
                    Click the button below to log your first renewal for <strong>{{ $hosting->hosting_name }}</strong>.
                </div>
                <button type="button" class="dh-btn dh-btn-primary" onclick="openAddRenewalModal()">
                    <i class="bi bi-plus-lg"></i> Record Renewal Now
                </button>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="dh-tbl">
                    <thead>
                        <tr>
                            <th># Renewal</th>
                            <th>Renewal Date</th>
                            <th>Next Renewal Date</th>
                            <th>Renewal Amount</th>
                            <th>Notes / Remarks</th>
                            <th>Recorded By</th>
                            <th>Recorded On</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hosting->renewals as $index => $renewal)
                        <tr>
                            <td>
                                <span style="font-weight:800; color:#4f46e5; background:#eef2ff; padding:3px 8px; border-radius:6px; font-size:12px;">
                                    #{{ $renewalsCount - $index }}
                                </span>
                            </td>
                            <td style="font-weight:700; color:#0f172a; white-space:nowrap;">
                                {{ $renewal->renewal_date ? $renewal->renewal_date->format('d M, Y') : '—' }}
                            </td>
                            <td style="font-weight:700; color:#15803d; white-space:nowrap;">
                                {{ $renewal->expires_at ? $renewal->expires_at->format('d M, Y') : '—' }}
                            </td>
                            <td style="font-weight:800; color:#0f172a; white-space:nowrap;">
                                {{ $renewal->amount ? '₹'.number_format($renewal->amount, 2) : '—' }}
                            </td>
                            <td style="color:#475569; max-width:260px;">
                                {{ $renewal->notes ?: '—' }}
                            </td>
                            <td style="font-size:12px; color:#64748b; white-space:nowrap;">
                                {{ $renewal->creator?->name ?? 'Admin' }}
                            </td>
                            <td style="font-size:12px; color:#94a3b8; white-space:nowrap;">
                                {{ $renewal->created_at->format('d M, Y h:i A') }}
                            </td>
                            <td style="text-align:right;">
                                <form method="POST" action="{{ route('accounts.domains-hosting.hostings.renewals.destroy', [$hosting->id, $renewal->id]) }}" onsubmit="return confirm('Are you sure you want to delete this renewal record?')" style="margin:0; display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dh-btn dh-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Renewal">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </div>
</div>

{{-- MODAL 1: Migrate Hosting Modal --}}
<div class="modal-overlay" id="migrateHostingModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-arrow-left-right" style="color:#0284c7; margin-right:6px;"></i> Migrate Hosting to Lead
            </div>
            <button type="button" class="modal-close" onclick="closeMigrateModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.domains-hosting.hostings.migrate-lead', $hosting->id) }}">
            @csrf
            <div class="modal-body">
                <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:12px 14px; font-size:13px; color:#0369a1;">
                    Map <strong>{{ $hosting->hosting_name }}</strong> to a Lead client in your CRM. Search the lead below by name or mobile number.
                </div>

                <div class="form-group">
                    <label class="form-label">Search & Select Lead <span style="color:#dc2626;">*</span></label>
                    <select id="lead_select" name="lead_id" class="form-input no-select2" data-no-select2="true" style="width:100%;" required>
                        @if($hosting->lead)
                            <option value="{{ $hosting->lead->id }}" selected>
                                #{{ $hosting->lead->id }} - {{ $hosting->lead->contact_name ?: $hosting->lead->company_name }} • 📞 {{ $hosting->lead->mobile_number }}
                            </option>
                        @else
                            <option value=""></option>
                        @endif
                    </select>
                    <small style="color:#64748b; font-size:11px;">
                        Search by Contact Name, Company Name, Mobile Number, or Lead ID.
                    </small>
                </div>

                @if($hosting->lead)
                    <div style="font-size:12px; color:#475569; background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #e2e8f0;">
                        Currently mapped to: <strong>{{ $hosting->lead->contact_name ?: $hosting->lead->company_name }}</strong> ({{ $hosting->lead->mobile_number }})
                    </div>
                @endif
            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="dh-btn dh-btn-secondary" onclick="closeMigrateModal()">Cancel</button>
                <button type="submit" class="dh-btn dh-btn-primary">
                    <i class="bi bi-check-lg"></i> Confirm Migration
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Add Renewal Modal --}}
<div class="modal-overlay" id="addRenewalModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-plus-circle-fill" style="color:#fe5f04; margin-right:6px;"></i> Add Renewal Entry
            </div>
            <button type="button" class="modal-close" onclick="closeAddRenewalModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.domains-hosting.hostings.renewals.store', $hosting->id) }}">
            @csrf
            <div class="modal-body">
                <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; padding:12px 14px; font-size:13px; color:#9a3412;">
                    Recording a new renewal will update the current renewal date of <strong>{{ $hosting->hosting_name }}</strong> to the new renewal date entered below.
                </div>

                <div class="form-group">
                    <label class="form-label">Renewal Date <span style="color:#dc2626;">*</span></label>
                    <input type="date" name="renewal_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                    <small style="color:#64748b; font-size:11px;">The date when this hosting renewal transaction occurred.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Next Renewal / Expiration Date <span style="color:#dc2626;">*</span></label>
                    @php
                        $suggestedRenewalDate = $hosting->renewal_date
                            ? ($hosting->renewal_date->isPast() ? now()->addYear()->format('Y-m-d') : $hosting->renewal_date->addYear()->format('Y-m-d'))
                            : now()->addYear()->format('Y-m-d');
                    @endphp
                    <input type="date" name="expires_at" class="form-input" value="{{ $suggestedRenewalDate }}" required>
                    <small style="color:#64748b; font-size:11px;">The next renewal date after this renewal.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Renewal Amount / Cost (₹)</label>
                    <input type="number" step="0.01" name="amount" class="form-input" value="{{ $hosting->renewal_amount }}" placeholder="e.g. 5499.00">
                </div>

                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="e.g. Renewed Cloud VPS for 1 year via Bank Transfer, Invoice #HOST-992"></textarea>
                </div>
            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="dh-btn dh-btn-secondary" onclick="closeAddRenewalModal()">Cancel</button>
                <button type="submit" class="dh-btn dh-btn-primary">
                    <i class="bi bi-save"></i> Save Renewal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 3: Edit Hosting Modal --}}
<div class="modal-overlay" id="editHostingModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#fe5f04; margin-right:6px;"></i> Edit Hosting Details
            </div>
            <button type="button" class="modal-close" onclick="closeEditHostingModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.domains-hosting.hostings.update', $hosting->id) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="from_show" value="1">
            <div class="modal-body">
                @if(isset($servers) && $servers->count() > 0)
                <div class="form-group" style="background:#f5f3ff; padding:12px 14px; border-radius:10px; border:1px solid #ddd6fe;">
                    <label class="form-label" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                        <span style="display:flex; align-items:center; gap:6px; color:#5b21b6; font-weight:800;">
                            <i class="bi bi-hdd-network" style="color:#7c3aed;"></i>
                            <span>Server Master Link</span>
                        </span>
                        <span style="font-size:11px; font-weight:700; color:#7c3aed; background:#ede9fe; padding:2px 8px; border-radius:4px; border:1px solid #c4b5fd;">
                            ⚡ Auto-Fetch
                        </span>
                    </label>
                    <select id="show_server_select" name="server_id" class="form-input" onchange="autoFillShowHostingServer(this)" style="background:#fff;">
                        <option value="">-- Choose Server or keep manual --</option>
                        @foreach($servers as $srv)
                            <option value="{{ $srv->id }}"
                                {{ (old('server_id', $hosting->server_id) == $srv->id) ? 'selected' : '' }}
                                data-name="{{ $srv->server_name }}"
                                data-provider="{{ $srv->provider_name }}"
                                data-ip="{{ $srv->ip_address }}"
                                data-plan="{{ $srv->plan_type }}"
                                data-disk="{{ $srv->disk_space }}">
                                {{ $srv->server_name }} @if($srv->ip_address) ({{ $srv->ip_address }}) @endif @if($srv->provider_name) • {{ $srv->provider_name }} @endif @if($srv->disk_space) [{{ $srv->disk_space }}] @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Hosting / Server Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="show_h_name" name="hosting_name" class="form-input" value="{{ $hosting->hosting_name }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Provider <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="show_h_provider" name="provider" class="form-input" value="{{ $hosting->provider }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">IP Address</label>
                    <input type="text" id="show_h_ip" name="ip_address" class="form-input" value="{{ $hosting->ip_address }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Plan Type <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="show_h_plan" name="plan_type" class="form-input" value="{{ $hosting->plan_type }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Allocated Storage</label>
                    <input type="text" id="show_h_storage" name="allocated_storage" class="form-input" value="{{ $hosting->allocated_storage }}" placeholder="e.g. 50 GB NVMe SSD / 20 GB">
                </div>

                <div class="form-group">
                    <label class="form-label">Status <span style="color:#dc2626;">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="ACTIVE" {{ $hosting->status === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="EXPIRED" {{ $hosting->status === 'EXPIRED' ? 'selected' : '' }}>EXPIRED</option>
                        <option value="PENDING_RENEWAL" {{ $hosting->status === 'PENDING_RENEWAL' ? 'selected' : '' }}>PENDING_RENEWAL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Renewal Date</label>
                    <input type="date" name="renewal_date" class="form-input" value="{{ $hosting->renewal_date ? $hosting->renewal_date->format('Y-m-d') : '' }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Renewal Amount (₹)</label>
                    <input type="number" step="0.01" name="renewal_amount" class="form-input" value="{{ $hosting->renewal_amount }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="notes" class="form-input" rows="3">{{ $hosting->notes }}</textarea>
                </div>
            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="dh-btn dh-btn-secondary" onclick="closeEditHostingModal()">Cancel</button>
                <button type="submit" class="dh-btn dh-btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 4: Map Multiple Domains to this Hosting Modal --}}
<div class="modal-overlay" id="mapDomainsModal">
    <div class="modal-box" style="max-width:620px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-globe" style="color:#7c3aed; margin-right:6px;"></i> Map Domains to {{ $hosting->hosting_name }}
            </div>
            <button type="button" class="modal-close" onclick="closeMapDomainsModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.domains-hosting.hostings.map-domains', $hosting->id) }}">
            @csrf
            <div class="modal-body">
                <div style="background:#f5f3ff; border:1px solid #ddd6fe; border-radius:10px; padding:12px 14px; font-size:13px; color:#5b21b6;">
                    Select the domain(s) you wish to map and host on <strong>{{ $hosting->hosting_name }}</strong> @if($hosting->server) [Server: {{ $hosting->server->server_name }}] @endif.
                </div>

                {{-- Search & Select All controls --}}
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:4px;">
                    <input type="text" id="domainFilterInput" class="form-input" style="padding:6px 10px; font-size:12px;" placeholder="Filter available domains..." onkeyup="filterAvailableDomains(this.value)">
                    <label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:#334155; cursor:pointer; white-space:nowrap;">
                        <input type="checkbox" id="selectAllAvailable" onchange="toggleSelectAllAvailable(this)"> Select All
                    </label>
                </div>

                <div style="max-height:280px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:10px; padding:8px; background:#fff; display:flex; flex-direction:column; gap:4px;" id="availableDomainsList">
                    @forelse($availableDomains as $availDomain)
                        <label class="available-domain-row" style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-radius:8px; border:1px solid #f1f5f9; background:#f8fafc; cursor:pointer; transition:all .15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="checkbox" name="domain_ids[]" value="{{ $availDomain->id }}" class="available-domain-cb" onchange="updateSelectedDomainsCount()">
                                <div>
                                    <div style="font-weight:700; color:#0f172a; font-size:13px;" class="domain-text-name">
                                        {{ $availDomain->domain_name }}
                                    </div>
                                    <div style="font-size:11px; color:#64748b;">
                                        {{ $availDomain->registrar }} • Expiry: {{ $availDomain->expires_at ? $availDomain->expires_at->format('d M, Y') : 'N/A' }}
                                        @if($availDomain->lead)
                                            • <span style="color:#0284c7;">{{ $availDomain->lead->contact_name ?: $availDomain->lead->company_name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div>
                                @if($availDomain->hosting)
                                    <span style="font-size:10px; background:#fff1f2; color:#be123c; padding:2px 6px; border-radius:4px; font-weight:700;" title="Will be moved to this hosting">
                                        On: {{ Str::limit($availDomain->hosting->hosting_name, 14) }}
                                    </span>
                                @else
                                    <span style="font-size:10px; background:#f1f5f9; color:#64748b; padding:2px 6px; border-radius:4px; font-weight:700;">
                                        Unmapped
                                    </span>
                                @endif
                            </div>
                        </label>
                    @empty
                        <div style="text-align:center; padding:24px; color:#94a3b8; font-size:13px;">
                            No available domains to map.
                        </div>
                    @endforelse
                </div>

                <div style="font-size:12px; font-weight:700; color:#475569;" id="selectedDomainSummary">
                    0 domain(s) selected
                </div>
            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="dh-btn dh-btn-secondary" onclick="closeMapDomainsModal()">Cancel</button>
                <button type="submit" class="dh-btn dh-btn-primary" style="background:linear-gradient(135deg,#7c3aed,#9333ea);">
                    <i class="bi bi-check-lg"></i> Map Selected Domains
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function escapeLeadHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatLeadOption(lead) {
    if (lead.loading) {
        return lead.text;
    }
    if (!lead.id) {
        return lead.text;
    }
    var primary = lead.contact_name || lead.company_name || ('Lead #' + lead.id);
    var company = (lead.company_name && lead.company_name !== primary) ? lead.company_name : '';
    var phone = lead.mobile_number || '';

    var html = '<div style="padding: 4px 2px;">';
    html += '<div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">';
    html += '<span style="font-weight:700; color:#0f172a; font-size:13px;">';
    html += '<span style="display:inline-block; background:#fff7ed; color:#ea580c; border:1px solid #ffedd5; font-size:11px; font-weight:800; padding:1px 6px; border-radius:4px; margin-right:6px;">#' + lead.id + '</span>';
    html += escapeLeadHtml(primary);
    html += '</span>';
    if (phone) {
        html += '<span style="font-size:12px; font-weight:600; color:#0284c7; white-space:nowrap;">📞 ' + escapeLeadHtml(phone) + '</span>';
    }
    html += '</div>';
    if (company) {
        html += '<div style="font-size:12px; color:#64748b; margin-top:3px; font-weight:500;">🏢 ' + escapeLeadHtml(company) + '</div>';
    }
    html += '</div>';

    return html;
}

function formatLeadSelection(lead) {
    if (!lead.id) {
        return lead.text || 'Search lead by contact name, company or mobile number...';
    }
    return lead.text || ('#' + lead.id + ' - ' + (lead.contact_name || lead.company_name || ''));
}

function initMigrateLeadSelect() {
    var $select = $('#lead_select');
    if ($select.length === 0) return;

    if ($select.hasClass('select2-hidden-accessible')) {
        return;
    }

    $select.select2({
        dropdownParent: $('#migrateHostingModal'),
        placeholder: 'Search lead by contact name, company or mobile number...',
        allowClear: true,
        width: '100%',
        ajax: {
            url: '{{ route('accounts.domains-hosting.leads.search', [], false) }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || ''
                };
            },
            processResults: function (data) {
                return {
                    results: data.results || []
                };
            },
            cache: true
        },
        minimumInputLength: 0,
        escapeMarkup: function (markup) { return markup; },
        templateResult: formatLeadOption,
        templateSelection: formatLeadSelection
    });

    $select.on('select2:open', function() {
        setTimeout(function() {
            var searchField = document.querySelector('.select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 50);
    });
}

function openMigrateModal() {
    $('#migrateHostingModal').addClass('active');
    initMigrateLeadSelect();
}

function closeMigrateModal() {
    $('#migrateHostingModal').removeClass('active');
}

function openAddRenewalModal() {
    $('#addRenewalModal').addClass('active');
}

function closeAddRenewalModal() {
    $('#addRenewalModal').removeClass('active');
}

function openEditHostingModal() {
    $('#editHostingModal').addClass('active');
}

function closeEditHostingModal() {
    $('#editHostingModal').removeClass('active');
}

function openMapDomainsModal() {
    $('#mapDomainsModal').addClass('active');
}

function closeMapDomainsModal() {
    $('#mapDomainsModal').removeClass('active');
}

function toggleSelectAllAvailable(master) {
    const rows = document.querySelectorAll('.available-domain-row');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.available-domain-cb');
            if (cb) cb.checked = master.checked;
        }
    });
    updateSelectedDomainsCount();
}

function updateSelectedDomainsCount() {
    const checked = document.querySelectorAll('.available-domain-cb:checked').length;
    document.getElementById('selectedDomainSummary').textContent = checked + ' domain' + (checked === 1 ? '' : 's') + ' selected';
}

function filterAvailableDomains(val) {
    const filter = (val || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.available-domain-row');
    rows.forEach(row => {
        const text = row.querySelector('.domain-text-name')?.textContent.toLowerCase() || '';
        if (text.includes(filter)) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });
}

function autoFillShowHostingServer(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) return;

    const name = opt.getAttribute('data-name');
    const provider = opt.getAttribute('data-provider');
    const ip = opt.getAttribute('data-ip');
    const plan = opt.getAttribute('data-plan');
    const disk = opt.getAttribute('data-disk');

    if (name) document.getElementById('show_h_name').value = name;
    if (provider) document.getElementById('show_h_provider').value = provider;
    if (ip) document.getElementById('show_h_ip').value = ip;
    if (plan) document.getElementById('show_h_plan').value = plan;
    if (disk) document.getElementById('show_h_storage').value = disk;
}

// Close modals on clicking overlay backdrop
$(document).on('click', '.modal-overlay', function(e) {
    if (e.target === this) {
        $(this).removeClass('active');
    }
});
</script>
@endpush
