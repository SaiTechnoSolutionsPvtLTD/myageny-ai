@extends('layouts.app')

@section('title', 'Hosting Details - ' . $hosting->hosting_name)

@push('styles')
<style>
.dh-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.dh-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); flex-wrap:wrap; gap:12px; }
.dh-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; display:flex; align-items:center; gap:10px; }
.dh-sub { font-size:13px; color:#64748b; }

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

    {{-- Topbar --}}
    <div class="dh-topbar">
        <div>
            <div style="margin-bottom:6px;">
                <a href="{{ route('accounts.domains-hosting.index', ['tab' => 'hostings']) }}" class="dh-btn dh-btn-secondary" style="padding:4px 10px; font-size:12px;">
                    <i class="bi bi-arrow-left"></i> Back to Domains & Hosting
                </a>
            </div>
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
            <div class="dh-sub">
                Provider: <strong>{{ $hosting->provider }}</strong> • IP Address: <strong>{{ $hosting->ip_address ?: 'Not assigned' }}</strong>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
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
                <div class="form-group">
                    <label class="form-label">Hosting / Server Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="hosting_name" class="form-input" value="{{ $hosting->hosting_name }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Provider <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="provider" class="form-input" value="{{ $hosting->provider }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">IP Address</label>
                    <input type="text" name="ip_address" class="form-input" value="{{ $hosting->ip_address }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Plan Type <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="plan_type" class="form-input" value="{{ $hosting->plan_type }}" required>
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
                    <label class="form-label">Client / Project Name</label>
                    <input type="text" name="client_name" class="form-input" value="{{ $hosting->client_name }}">
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

// Close modals on clicking overlay backdrop
$(document).on('click', '.modal-overlay', function(e) {
    if (e.target === this) {
        $(this).removeClass('active');
    }
});
</script>
@endpush
