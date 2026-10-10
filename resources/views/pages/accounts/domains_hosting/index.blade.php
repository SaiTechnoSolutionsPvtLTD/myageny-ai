@extends('layouts.app')

@section('title', 'Domains & Hosting')

@push('styles')
<style>
.dh-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.dh-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.dh-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; }
.dh-sub { font-size:13px; color:#64748b; }

.dh-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.dh-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.dh-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.dh-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.dh-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.dh-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

.dh-nav-tabs { display:flex; align-items:center; gap:8px; padding:14px 20px 0 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.dh-tab-item { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px 10px 0 0; font-size:13px; font-weight:700; color:#64748b; text-decoration:none; border:1px solid transparent; border-bottom:none; background:transparent; transition:all .15s ease; cursor:pointer; }
.dh-tab-item:hover { color:#fe5f04; background:#ffffff; }
.dh-tab-item.active { color:#fe5f04; background:#ffffff; border-color:#e2e8f0; border-bottom:2px solid #fe5f04; margin-bottom:-1px; }

.dh-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:nowrap; overflow-x:auto; }
.dh-search-wrap { position:relative; flex:1; min-width:180px; }
.dh-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.dh-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.dh-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.dh-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.dh-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.dh-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.dh-btn-secondary { background:#f1f5f9; color:#334155; }
.dh-btn-secondary:hover { background:#e2e8f0; }
.dh-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.dh-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.dh-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.dh-btn-danger:hover { background:#fca5a5; }

.dh-tbl { width:100%; border-collapse:collapse; }
.dh-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.dh-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.dh-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }

.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-expired { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.status-pending { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }

.api-banner { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600; }
.api-banner-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
.api-banner-warning { background:#fffbeb; border:1px solid #fde68a; color:#b45309; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:92%; max-width:540px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; sticky:top; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:14px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
</style>
@endpush

@section('content')
<div class="dh-page">

    {{-- Topbar --}}
    <div class="dh-topbar">
        <div>
            <div class="dh-title">Domains & Hosting</div>
            <div class="dh-sub">Manage domain renewals, GoDaddy integration, and hosting server renewals</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            @if($activeTab === 'domains')
                <form method="POST" action="{{ route('accounts.domains-hosting.domains.sync-godaddy') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="dh-btn dh-btn-outline" title="Fetch live domains from GoDaddy API">
                        <i class="bi bi-arrow-repeat"></i> Sync GoDaddy API
                    </button>
                </form>
                <button type="button" class="dh-btn dh-btn-primary" onclick="openAddDomainModal()">
                    <i class="bi bi-plus-lg"></i> Add Domain
                </button>
            @else
                <button type="button" class="dh-btn dh-btn-primary" onclick="openAddHostingModal()">
                    <i class="bi bi-plus-lg"></i> Add Hosting
                </button>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- GoDaddy API Banner --}}
    @if($activeTab === 'domains')
        @if($isGodaddyConfigured)
            <div class="api-banner api-banner-success">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="bi bi-check-circle-fill" style="font-size:18px;"></i>
                    <span>GoDaddy API Connection Configured (<code>GET /v1/domains</code>)</span>
                </div>
                <form method="POST" action="{{ route('accounts.domains-hosting.domains.sync-godaddy') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="dh-btn" style="background:#16a34a; color:#fff; padding:5px 12px; font-size:12px;">
                        Sync Domains Now
                    </button>
                </form>
            </div>
        @else
            <div class="api-banner api-banner-warning">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:18px;"></i>
                    <span>GoDaddy API is not configured. Add <code>GODADDY_API_KEY</code> and <code>GODADDY_API_SECRET</code> to <code>.env</code> to auto-sync GoDaddy domains.</span>
                </div>
            </div>
        @endif
    @endif

    {{-- Main Container Card --}}
    <div class="dh-card">

        {{-- Nav Tabs --}}
        <div class="dh-nav-tabs">
            <a href="{{ route('accounts.domains-hosting.index', ['tab' => 'domains']) }}" class="dh-tab-item {{ $activeTab === 'domains' ? 'active' : '' }}">
                <i class="bi bi-globe"></i> Domains
                <span style="padding:2px 8px; border-radius:999px; font-size:11px; background:{{ $activeTab === 'domains' ? '#fff3eb' : '#e2e8f0' }}; color:{{ $activeTab === 'domains' ? '#fe5f04' : '#475569' }};">
                    {{ $domainStats['total'] }}
                </span>
            </a>
            <a href="{{ route('accounts.domains-hosting.index', ['tab' => 'hostings']) }}" class="dh-tab-item {{ $activeTab === 'hostings' ? 'active' : '' }}">
                <i class="bi bi-hdd-network"></i> Hosting
                <span style="padding:2px 8px; border-radius:999px; font-size:11px; background:{{ $activeTab === 'hostings' ? '#fff3eb' : '#e2e8f0' }}; color:{{ $activeTab === 'hostings' ? '#fe5f04' : '#475569' }};">
                    {{ $hostingStats['total'] }}
                </span>
            </a>
        </div>

        {{-- DOMAINS TAB --}}
        @if($activeTab === 'domains')

            {{-- Domain Stats Cards --}}
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#fafafa;">
                <div class="dh-stats-grid">
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#eff6ff; color:#2563eb;">
                            <i class="bi bi-globe"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Total Domains</div>
                            <div class="dh-stat-val">{{ $domainStats['total'] }}</div>
                        </div>
                    </div>
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Active Domains</div>
                            <div class="dh-stat-val">{{ $domainStats['active'] }}</div>
                        </div>
                    </div>
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#fef3c7; color:#d97706;">
                            <i class="bi bi-exclamation-circle-fill"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Expiring Soon (30 Days)</div>
                            <div class="dh-stat-val">{{ $domainStats['expiring_soon'] }}</div>
                        </div>
                    </div>
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#fee2e2; color:#dc2626;">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Expired Domains</div>
                            <div class="dh-stat-val">{{ $domainStats['expired'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Single-Row Filter Bar --}}
            <form method="GET" action="{{ route('accounts.domains-hosting.index') }}">
                <input type="hidden" name="tab" value="domains">
                <div class="dh-filter-bar">
                    <div class="dh-search-wrap">
                        <i class="bi bi-search dh-search-ico"></i>
                        <input type="text" name="search" class="dh-search-input" placeholder="Search domain name, registrar, client..." value="{{ request('search') }}">
                    </div>

                    <select name="status" class="dh-select" style="min-width:160px; flex-shrink:0;">
                        <option value="">All Status</option>
                        <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (30 Days)</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="PENDING_RENEWAL" {{ request('status') === 'PENDING_RENEWAL' ? 'selected' : '' }}>Pending Renewal</option>
                        <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                    </select>

                    <button type="submit" class="dh-btn dh-btn-primary" style="padding:8px 16px; font-size:12px; flex-shrink:0;">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    @if(request()->hasAny(['search','status']))
                        <a href="{{ route('accounts.domains-hosting.index', ['tab' => 'domains']) }}" class="dh-btn dh-btn-secondary" style="padding:8px 14px; font-size:12px; flex-shrink:0;">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            @if($domains->isEmpty())
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="bi bi-globe" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    No domain records found. Click "Add Domain" or "Sync GoDaddy API" to add domains.
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
                                <th>Auto Renew</th>
                                <th>Privacy</th>
                                <th>Client / Project</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($domains as $item)
                            @php
                                $days = $item->days_until_expiration;
                                $isExpiring = ($days !== null && $days >= 0 && $days <= 30);
                                $isExpired = ($days !== null && $days < 0);
                            @endphp
                            <tr>
                                <td style="color:#94a3b8; font-weight:700;">#{{ $item->id }}</td>
                                <td>
                                    <a href="{{ route('accounts.domains-hosting.domains.show', $item->id) }}" style="color:#0f172a; text-decoration:none; font-weight:800;" onmouseover="this.style.color='#fe5f04'" onmouseout="this.style.color='#0f172a'" title="Click to view domain details, lead mapping & renewals">
                                        {{ $item->domain_name }}
                                    </a>
                                    @if($item->godaddy_domain_id)
                                        <span style="font-size:10px; padding:1px 5px; background:#eff6ff; color:#2563eb; border-radius:4px; margin-left:4px;">GoDaddy API</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-weight:600; color:#475569;">{{ $item->registrar }}</span>
                                </td>
                                <td>
                                    @if($isExpired)
                                        <span class="dh-badge status-expired">EXPIRED</span>
                                    @elseif($isExpiring)
                                        <span class="dh-badge status-pending">EXPIRING SOON</span>
                                    @elseif($item->status === 'ACTIVE')
                                        <span class="dh-badge status-active">ACTIVE</span>
                                    @else
                                        <span class="dh-badge status-pending">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td style="font-weight:700; white-space:nowrap;">
                                    @if($item->expires_at)
                                        <div>{{ $item->expires_at->format('d M, Y') }}</div>
                                        @if($isExpired)
                                            <span style="font-size:11px; color:#dc2626;">({{ abs($days) }} days ago)</span>
                                        @elseif($days !== null)
                                            <span style="font-size:11px; color:{{ $isExpiring ? '#d97706' : '#16a34a' }};">({{ $days }} days left)</span>
                                        @endif
                                        @if($item->renewals_count > 0)
                                            <div style="margin-top:2px;">
                                                <span style="font-size:10px; font-weight:700; color:#4f46e5; background:#eef2ff; padding:2px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;">
                                                    <i class="bi bi-arrow-repeat"></i> {{ $item->renewals_count }} {{ Str::plural('renewal', $item->renewals_count) }}
                                                </span>
                                            </div>
                                        @endif
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size:11px; font-weight:700; color:{{ $item->auto_renew ? '#16a34a' : '#94a3b8' }};">
                                        {{ $item->auto_renew ? '✓ Enabled' : '✕ Disabled' }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size:11px; font-weight:700; color:{{ $item->privacy ? '#16a34a' : '#94a3b8' }};">
                                        {{ $item->privacy ? '✓ Yes' : '✕ No' }}
                                    </span>
                                </td>
                                <td>
                                    @if($item->lead)
                                        <a href="{{ route('accounts.domains-hosting.domains.show', $item->id) }}" style="text-decoration:none;">
                                            <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px;" title="Mapped CRM Lead">
                                                <i class="bi bi-person-check-fill"></i> {{ $item->lead->contact_name ?: $item->lead->company_name }}
                                            </span>
                                        </a>
                                    @else
                                        {{ $item->client_name ?: '—' }}
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="{{ route('accounts.domains-hosting.domains.show', $item->id) }}" class="dh-btn dh-btn-outline" style="padding:4px 10px; font-size:11px;" title="View Details, Lead Mapping & Renewals">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <button type="button" class="dh-btn dh-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                                data-item="{{ json_encode([
                                                    'id' => $item->id,
                                                    'domain_name' => $item->domain_name,
                                                    'registrar' => $item->registrar,
                                                    'status' => $item->status,
                                                    'expires_at' => $item->expires_at ? $item->expires_at->format('Y-m-d') : '',
                                                    'auto_renew' => $item->auto_renew,
                                                    'privacy' => $item->privacy,
                                                    'client_name' => $item->client_name ?: '',
                                                    'notes' => $item->notes ?: '',
                                                ]) }}"
                                                onclick='openEditDomainModal(JSON.parse(this.getAttribute("data-item")))'>
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" action="{{ route('accounts.domains-hosting.domains.destroy', $item->id) }}" onsubmit="return confirm('Are you sure you want to delete this domain record?')" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dh-btn dh-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Domain">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($domains->hasPages())
                    @include('partials.table-pagination', ['paginator' => $domains])
                @endif
            @endif

        {{-- HOSTINGS TAB --}}
        @else

            {{-- Hosting Stats Cards --}}
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#fafafa;">
                <div class="dh-stats-grid">
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#eff6ff; color:#2563eb;">
                            <i class="bi bi-hdd-network"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Total Hosting Plans</div>
                            <div class="dh-stat-val">{{ $hostingStats['total'] }}</div>
                        </div>
                    </div>
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Active Hosting</div>
                            <div class="dh-stat-val">{{ $hostingStats['active'] }}</div>
                        </div>
                    </div>
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#fef3c7; color:#d97706;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Renewal Due Soon (30 Days)</div>
                            <div class="dh-stat-val">{{ $hostingStats['renewal_due'] }}</div>
                        </div>
                    </div>
                    <div class="dh-stat-card">
                        <div class="dh-stat-icon" style="background:#fff7ed; color:#ea580c;">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <div class="dh-stat-label">Total Renewal Amount</div>
                            <div class="dh-stat-val">₹{{ number_format($hostingStats['total_amount'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Single-Row Filter Bar --}}
            <form method="GET" action="{{ route('accounts.domains-hosting.index') }}">
                <input type="hidden" name="tab" value="hostings">
                <div class="dh-filter-bar">
                    <div class="dh-search-wrap">
                        <i class="bi bi-search dh-search-ico"></i>
                        <input type="text" name="search" class="dh-search-input" placeholder="Search hosting name, provider, IP, client..." value="{{ request('search') }}">
                    </div>

                    <select name="status" class="dh-select" style="min-width:160px; flex-shrink:0;">
                        <option value="">All Status</option>
                        <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Renewal Due (30 Days)</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="PENDING_RENEWAL" {{ request('status') === 'PENDING_RENEWAL' ? 'selected' : '' }}>Pending Renewal</option>
                    </select>

                    <button type="submit" class="dh-btn dh-btn-primary" style="padding:8px 16px; font-size:12px; flex-shrink:0;">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    @if(request()->hasAny(['search','status']))
                        <a href="{{ route('accounts.domains-hosting.index', ['tab' => 'hostings']) }}" class="dh-btn dh-btn-secondary" style="padding:8px 14px; font-size:12px; flex-shrink:0;">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            @if($hostings->isEmpty())
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="bi bi-hdd-network" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    No hosting records found. Click "Add Hosting" to create one.
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="dh-tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Hosting Name</th>
                                <th>Provider</th>
                                <th>IP Address</th>
                                <th>Plan Type</th>
                                <th>Status</th>
                                <th>Renewal Date</th>
                                <th>Renewal Amount</th>
                                <th>Client / Project</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hostings as $item)
                            @php
                                $days = $item->days_until_renewal;
                                $isExpiring = ($days !== null && $days >= 0 && $days <= 30);
                                $isExpired = ($days !== null && $days < 0);
                            @endphp
                            <tr>
                                <td style="color:#94a3b8; font-weight:700;">#{{ $item->id }}</td>
                                <td>
                                    <a href="{{ route('accounts.domains-hosting.hostings.show', $item->id) }}" style="color:#0f172a; text-decoration:none; font-weight:800;" onmouseover="this.style.color='#fe5f04'" onmouseout="this.style.color='#0f172a'" title="Click to view hosting details, lead mapping & renewals">
                                        {{ $item->hosting_name }}
                                    </a>
                                </td>
                                <td><span style="font-weight:600; color:#475569;">{{ $item->provider }}</span></td>
                                <td><code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:12px;">{{ $item->ip_address ?: '—' }}</code></td>
                                <td><span style="font-size:11px; padding:2px 8px; background:#eff6ff; color:#2563eb; border-radius:4px; font-weight:700;">{{ $item->plan_type }}</span></td>
                                <td>
                                    @if($isExpired)
                                        <span class="dh-badge status-expired">EXPIRED</span>
                                    @elseif($isExpiring)
                                        <span class="dh-badge status-pending">RENEWAL DUE</span>
                                    @elseif($item->status === 'ACTIVE')
                                        <span class="dh-badge status-active">ACTIVE</span>
                                    @else
                                        <span class="dh-badge status-pending">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td style="font-weight:700; white-space:nowrap;">
                                    @if($item->renewal_date)
                                        <div>{{ $item->renewal_date->format('d M, Y') }}</div>
                                        @if($isExpired)
                                            <span style="font-size:11px; color:#dc2626;">({{ abs($days) }} days ago)</span>
                                        @elseif($days !== null)
                                            <span style="font-size:11px; color:{{ $isExpiring ? '#d97706' : '#16a34a' }};">({{ $days }} days left)</span>
                                        @endif
                                        @if($item->renewals_count > 0)
                                            <div style="margin-top:2px;">
                                                <span style="font-size:10px; font-weight:700; color:#4f46e5; background:#eef2ff; padding:2px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;">
                                                    <i class="bi bi-arrow-repeat"></i> {{ $item->renewals_count }} {{ Str::plural('renewal', $item->renewals_count) }}
                                                </span>
                                            </div>
                                        @endif
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="font-weight:800; color:#15803d; white-space:nowrap;">
                                    {{ $item->renewal_amount ? '₹'.number_format($item->renewal_amount, 2) : '—' }}
                                </td>
                                <td>
                                    @if($item->lead)
                                        <a href="{{ route('accounts.domains-hosting.hostings.show', $item->id) }}" style="text-decoration:none;">
                                            <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px;" title="Mapped CRM Lead">
                                                <i class="bi bi-person-check-fill"></i> {{ $item->lead->contact_name ?: $item->lead->company_name }}
                                            </span>
                                        </a>
                                    @else
                                        {{ $item->client_name ?: '—' }}
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="{{ route('accounts.domains-hosting.hostings.show', $item->id) }}" class="dh-btn dh-btn-outline" style="padding:4px 10px; font-size:11px;" title="View Details, Lead Mapping & Renewals">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <button type="button" class="dh-btn dh-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                                data-item="{{ json_encode([
                                                    'id' => $item->id,
                                                    'hosting_name' => $item->hosting_name,
                                                    'provider' => $item->provider,
                                                    'ip_address' => $item->ip_address ?: '',
                                                    'plan_type' => $item->plan_type,
                                                    'status' => $item->status,
                                                    'renewal_date' => $item->renewal_date ? $item->renewal_date->format('Y-m-d') : '',
                                                    'renewal_amount' => $item->renewal_amount,
                                                    'client_name' => $item->client_name ?: '',
                                                    'notes' => $item->notes ?: '',
                                                ]) }}"
                                                onclick='openEditHostingModal(JSON.parse(this.getAttribute("data-item")))'>
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" action="{{ route('accounts.domains-hosting.hostings.destroy', $item->id) }}" onsubmit="return confirm('Are you sure you want to delete this hosting record?')" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dh-btn dh-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Hosting">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($hostings->hasPages())
                    @include('partials.table-pagination', ['paginator' => $hostings])
                @endif
            @endif

        @endif

    </div>

</div>

{{-- Add Domain Modal --}}
<div class="modal-overlay" id="addDomainModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Add New Domain</div>
            <button type="button" class="modal-close" onclick="closeModal('addDomainModal')">×</button>
        </div>
        <form method="POST" action="{{ route('accounts.domains-hosting.domains.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Domain Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="domain_name" class="form-input" placeholder="e.g. saitechnosolutions.com" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Registrar <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="registrar" class="form-input" value="GoDaddy" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span style="color:#ef4444;">*</span></label>
                        <select name="status" class="form-input" required>
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="PENDING_RENEWAL">PENDING_RENEWAL</option>
                            <option value="EXPIRED">EXPIRED</option>
                            <option value="CANCELLED">CANCELLED</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Expiration Date</label>
                        <input type="date" name="expires_at" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client / Project Name</label>
                        <input type="text" name="client_name" class="form-input" placeholder="e.g. Acme Corp">
                    </div>
                </div>
                <div style="display:flex; gap:20px; margin-top:4px;">
                    <label style="font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="auto_renew" value="1" checked> Auto Renew
                    </label>
                    <label style="font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="privacy" value="1"> Privacy Protection
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Additional notes..."></textarea>
                </div>
                <button type="submit" class="dh-btn dh-btn-primary" style="justify-content:center; padding:10px;">Save Domain</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Domain Modal --}}
<div class="modal-overlay" id="editDomainModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Edit Domain Record</div>
            <button type="button" class="modal-close" onclick="closeModal('editDomainModal')">×</button>
        </div>
        <form id="editDomainForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Domain Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="edit-d-domain_name" name="domain_name" class="form-input" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Registrar <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="edit-d-registrar" name="registrar" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span style="color:#ef4444;">*</span></label>
                        <select id="edit-d-status" name="status" class="form-input" required>
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="PENDING_RENEWAL">PENDING_RENEWAL</option>
                            <option value="EXPIRED">EXPIRED</option>
                            <option value="CANCELLED">CANCELLED</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Expiration Date</label>
                        <input type="date" id="edit-d-expires_at" name="expires_at" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client / Project Name</label>
                        <input type="text" id="edit-d-client_name" name="client_name" class="form-input">
                    </div>
                </div>
                <div style="display:flex; gap:20px; margin-top:4px;">
                    <label style="font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" id="edit-d-auto_renew" name="auto_renew" value="1"> Auto Renew
                    </label>
                    <label style="font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" id="edit-d-privacy" name="privacy" value="1"> Privacy Protection
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea id="edit-d-notes" name="notes" class="form-input" rows="2"></textarea>
                </div>
                <button type="submit" class="dh-btn dh-btn-primary" style="justify-content:center; padding:10px;">Update Domain</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Hosting Modal --}}
<div class="modal-overlay" id="addHostingModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Add New Hosting Record</div>
            <button type="button" class="modal-close" onclick="closeModal('addHostingModal')">×</button>
        </div>
        <form method="POST" action="{{ route('accounts.domains-hosting.hostings.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Hosting Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="hosting_name" class="form-input" placeholder="e.g. Cloud VPS Server 01" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Provider <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="provider" class="form-input" value="Hostinger" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-input" placeholder="e.g. 192.168.1.1">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Plan Type <span style="color:#ef4444;">*</span></label>
                        <select name="plan_type" class="form-input" required>
                            <option value="Cloud / VPS">Cloud / VPS</option>
                            <option value="Shared Hosting">Shared Hosting</option>
                            <option value="Dedicated Server">Dedicated Server</option>
                            <option value="cPanel">cPanel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span style="color:#ef4444;">*</span></label>
                        <select name="status" class="form-input" required>
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="PENDING_RENEWAL">PENDING_RENEWAL</option>
                            <option value="EXPIRED">EXPIRED</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Renewal Date</label>
                        <input type="date" name="renewal_date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Renewal Amount (₹)</label>
                        <input type="number" name="renewal_amount" class="form-input" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Client / Project Name</label>
                    <input type="text" name="client_name" class="form-input" placeholder="e.g. Acme Corp">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Server credentials or details..."></textarea>
                </div>
                <button type="submit" class="dh-btn dh-btn-primary" style="justify-content:center; padding:10px;">Save Hosting</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Hosting Modal --}}
<div class="modal-overlay" id="editHostingModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Edit Hosting Record</div>
            <button type="button" class="modal-close" onclick="closeModal('editHostingModal')">×</button>
        </div>
        <form id="editHostingForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Hosting Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="edit-h-hosting_name" name="hosting_name" class="form-input" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Provider <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="edit-h-provider" name="provider" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">IP Address</label>
                        <input type="text" id="edit-h-ip_address" name="ip_address" class="form-input">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Plan Type <span style="color:#ef4444;">*</span></label>
                        <select id="edit-h-plan_type" name="plan_type" class="form-input" required>
                            <option value="Cloud / VPS">Cloud / VPS</option>
                            <option value="Shared Hosting">Shared Hosting</option>
                            <option value="Dedicated Server">Dedicated Server</option>
                            <option value="cPanel">cPanel</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span style="color:#ef4444;">*</span></label>
                        <select id="edit-h-status" name="status" class="form-input" required>
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="PENDING_RENEWAL">PENDING_RENEWAL</option>
                            <option value="EXPIRED">EXPIRED</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Renewal Date</label>
                        <input type="date" id="edit-h-renewal_date" name="renewal_date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Renewal Amount (₹)</label>
                        <input type="number" id="edit-h-renewal_amount" name="renewal_amount" class="form-input" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Client / Project Name</label>
                    <input type="text" id="edit-h-client_name" name="client_name" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea id="edit-h-notes" name="notes" class="form-input" rows="2"></textarea>
                </div>
                <button type="submit" class="dh-btn dh-btn-primary" style="justify-content:center; padding:10px;">Update Hosting</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAddDomainModal() {
    document.getElementById('addDomainModal').classList.add('active');
}

function openEditDomainModal(item) {
    document.getElementById('editDomainForm').action = '/accounts/domains-hosting/domains/' + item.id;
    document.getElementById('edit-d-domain_name').value = item.domain_name;
    document.getElementById('edit-d-registrar').value = item.registrar;
    document.getElementById('edit-d-status').value = item.status;
    document.getElementById('edit-d-expires_at').value = item.expires_at;
    document.getElementById('edit-d-client_name').value = item.client_name;
    document.getElementById('edit-d-auto_renew').checked = !!item.auto_renew;
    document.getElementById('edit-d-privacy').checked = !!item.privacy;
    document.getElementById('edit-d-notes').value = item.notes;
    document.getElementById('editDomainModal').classList.add('active');
}

function openAddHostingModal() {
    document.getElementById('addHostingModal').classList.add('active');
}

function openEditHostingModal(item) {
    document.getElementById('editHostingForm').action = '/accounts/domains-hosting/hostings/' + item.id;
    document.getElementById('edit-h-hosting_name').value = item.hosting_name;
    document.getElementById('edit-h-provider').value = item.provider;
    document.getElementById('edit-h-ip_address').value = item.ip_address;
    document.getElementById('edit-h-plan_type').value = item.plan_type;
    document.getElementById('edit-h-status').value = item.status;
    document.getElementById('edit-h-renewal_date').value = item.renewal_date;
    document.getElementById('edit-h-renewal_amount').value = item.renewal_amount;
    document.getElementById('edit-h-client_name').value = item.client_name;
    document.getElementById('edit-h-notes').value = item.notes;
    document.getElementById('editHostingModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

window.addEventListener('click', function(e) {
    ['addDomainModal', 'editDomainModal', 'addHostingModal', 'editHostingModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            closeModal(id);
        }
    });
});
</script>
@endpush
@endsection
