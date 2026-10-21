@extends('layouts.app')

@section('title', 'Bank Master - Accounts')

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
.acm-btn-star { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.acm-btn-star:hover { background:#fde68a; color:#92400e; }

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

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-inactive { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.primary-badge { background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:10px; font-weight:800; padding:2px 8px; border-radius:6px; letter-spacing:.5px; text-transform:uppercase; display:inline-flex; align-items:center; gap:3px; }

.code-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#0f172a; font-family:monospace; font-size:12px; font-weight:700; border:1px solid #e2e8f0; }
.upi-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#f5f3ff; color:#6d28d9; font-size:11px; font-weight:700; border:1px solid #ddd6fe; }

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
.form-section-card { background:#fff7ed; border:1px solid #ffedd5; border-radius:12px; padding:12px 16px; display:flex; align-items:flex-start; gap:10px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-label span.req { color:#ef4444; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Detail View */
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns:1fr; } }
.detail-item { background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0; }
.detail-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.detail-val { font-size:13px; font-weight:700; color:#0f172a; word-break:break-word; }

.copy-btn { border:none; background:none; color:#64748b; cursor:pointer; padding:2px 4px; border-radius:4px; font-size:12px; }
.copy-btn:hover { color:#fe5f04; background:#fff; }
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Topbar --}}
    <div class="acm-topbar">
        <div class="acm-topbar-left">
            <div class="acm-title">
                <i class="bi bi-bank" style="color:#fe5f04;"></i>
                <span>Bank Master</span>
            </div>
            <div class="acm-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span class="sep">/</span>
                <span class="current">Bank Master</span>
            </div>
        </div>
        <div class="acm-topbar-actions">
            <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Master Hub
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddBankModal()">
                <i class="bi bi-plus-lg"></i> Add Bank Account
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
                <div class="acm-stat-icon" style="background:#fff7ed; color:#ea580c;">
                    <i class="bi bi-bank2"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total Bank Accounts</div>
                    <div class="acm-stat-val">{{ $stats['total'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Active Accounts</div>
                    <div class="acm-stat-val">{{ $stats['active'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fef2f2; color:#dc2626;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Inactive Accounts</div>
                    <div class="acm-stat-val">{{ $stats['inactive'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fef3c7; color:#d97706;">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Primary Settlement A/C</div>
                    <div class="acm-stat-val" style="font-size:15px; font-weight:700; color:#0f172a; margin-top:2px;">
                        @if($stats['primary'])
                            <span title="{{ $stats['primary']->bank_name }} - {{ $stats['primary']->account_number }}">
                                {{ \Illuminate\Support\Str::limit($stats['primary']->bank_name, 16) }}
                                <code style="font-size:11px; color:#64748b; font-weight:600;">(...{{ substr($stats['primary']->account_number, -4) }})</code>
                            </span>
                        @else
                            <span style="color:#94a3b8; font-weight:600; font-size:13px;">Not Set</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="acm-card">
            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('accounts.master.banks.index') }}" class="acm-filter-bar">
                <div class="acm-search-wrap">
                    <i class="bi bi-search acm-search-ico"></i>
                    <input type="text" name="search" class="acm-search-input"
                           placeholder="Search by Bank Name, Account Holder, Account Number, IFSC, UPI..."
                           value="{{ request('search') }}">
                </div>
                <select name="status" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
                <button type="submit" class="acm-btn acm-btn-primary" style="padding:8px 14px;">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('accounts.master.banks.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px;">
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
                            <th>Bank & Branch</th>
                            <th>Account Holder / Beneficiary</th>
                            <th>Account Number</th>
                            <th>IFSC Code</th>
                            <th>UPI & SWIFT</th>
                            <th style="text-align:center;">Primary</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banks as $bank)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $banks->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <div style="font-weight:800; color:#0f172a; font-size:14px;">{{ $bank->bank_name }}</div>
                                    @if($bank->is_primary)
                                        <span class="primary-badge"><i class="bi bi-star-fill"></i> Primary</span>
                                    @endif
                                </div>
                                @if($bank->branch_name)
                                    <div style="font-size:12px; color:#64748b; font-weight:600; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                        <i class="bi bi-geo-alt"></i> {{ $bank->branch_name }}
                                    </div>
                                @else
                                    <span style="font-size:11px; color:#94a3b8;">Branch not specified</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:700; color:#0f172a; font-size:13px;">
                                    {{ $bank->account_name }}
                                </div>
                            </td>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <span class="code-chip">{{ $bank->account_number }}</span>
                                    <button type="button" class="copy-btn" onclick="copyText('{{ $bank->account_number }}', this)" title="Copy Account Number">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <span class="code-chip" style="background:#ecfdf5; color:#047857; border-color:#a7f3d0;">
                                        {{ $bank->ifsc_code }}
                                    </span>
                                    <button type="button" class="copy-btn" onclick="copyText('{{ $bank->ifsc_code }}', this)" title="Copy IFSC Code">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:3px;">
                                    @if($bank->upi_id)
                                        <div style="display:inline-flex; align-items:center; gap:4px;">
                                            <span class="upi-chip">
                                                <i class="bi bi-qr-code"></i> {{ $bank->upi_id }}
                                            </span>
                                            <button type="button" class="copy-btn" onclick="copyText('{{ $bank->upi_id }}', this)" title="Copy UPI ID">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    @endif
                                    @if($bank->swift_code)
                                        <div style="font-size:11px; color:#64748b;">
                                            SWIFT: <code style="background:#f1f5f9; padding:1px 4px; border-radius:4px; font-weight:700; color:#334155;">{{ $bank->swift_code }}</code>
                                        </div>
                                    @endif
                                    @if(!$bank->upi_id && !$bank->swift_code)
                                        <span style="color:#94a3b8; font-size:12px;">—</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if($bank->is_primary)
                                    <span class="acm-badge status-active" style="font-size:11px;">
                                        <i class="bi bi-check-lg"></i> Default A/C
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('accounts.master.banks.set-primary', $bank->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-star" style="padding:4px 10px; font-size:11px;" title="Set as primary settlement bank">
                                            <i class="bi bi-star"></i> Set Primary
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                <span class="acm-badge status-{{ $bank->status }}">{{ ucfirst($bank->status) }}</span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    {{-- View Details --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openViewBankModal(@json($bank))'
                                            title="View Bank Details">
                                        <i class="bi bi-eye"></i> View
                                    </button>

                                    {{-- Edit --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openEditBankModal(@json($bank))'
                                            title="Edit Bank Details">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    {{-- Toggle Status --}}
                                    <form method="POST" action="{{ route('accounts.master.banks.toggle-status', $bank->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active/Inactive">
                                            {{ $bank->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('accounts.master.banks.destroy', $bank->id) }}" onsubmit="return confirm('Are you sure you want to delete bank account: {{ addslashes($bank->bank_name) }} ({{ addslashes($bank->account_number) }})?')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Bank Account">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:48px 20px; color:#64748b;">
                                <i class="bi bi-bank" style="font-size:42px; color:#cbd5e1; display:block; margin-bottom:10px;"></i>
                                <div style="font-size:15px; font-weight:700; color:#0f172a;">No bank accounts found</div>
                                <div style="font-size:13px; color:#94a3b8; margin-top:4px;">Get started by adding your organization's bank settlement details.</div>
                                <button type="button" class="acm-btn acm-btn-primary" style="margin-top:14px;" onclick="openAddBankModal()">
                                    <i class="bi bi-plus-lg"></i> Add Bank Account
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($banks->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                    {{ $banks->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

{{-- ======================================================== --}}
{{-- MODAL: Add Bank Account                                  --}}
{{-- ======================================================== --}}
<div id="addBankModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-bank" style="color:#fe5f04;"></i>
                <span>Add Bank Account</span>
            </div>
            <button type="button" class="modal-close" onclick="closeAddBankModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.banks.store') }}">
            @csrf
            <div class="modal-body">

                {{-- Screenshot Header Banner --}}
                <div class="form-section-card">
                    <i class="bi bi-shield-check" style="color:#ea580c; font-size:20px; flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:13px; font-weight:800; color:#9a3412;">Bank & Payment Settlement Details</div>
                        <div style="font-size:12px; color:#c2410c; margin-top:2px;">
                            This bank account will be printed on the invoice for wire transfer & UPI payments.
                        </div>
                    </div>
                </div>

                {{-- Bank Name & Branch Name --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Bank Name <span class="req">*</span></label>
                        <input type="text" name="bank_name" class="form-input" placeholder="e.g. HDFC Bank" required value="{{ old('bank_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Name</label>
                        <input type="text" name="branch_name" class="form-input" placeholder="e.g. T Nagar Branch" value="{{ old('branch_name') }}">
                    </div>
                </div>

                {{-- Account Holder / Beneficiary Name --}}
                <div class="form-group">
                    <label class="form-label">Account Holder / Beneficiary Name <span class="req">*</span></label>
                    <input type="text" name="account_name" class="form-input" placeholder="e.g. MY AGENCY DIGITAL TECH PVT LTD" required value="{{ old('account_name') }}">
                </div>

                {{-- Account Number & IFSC Code --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Account Number <span class="req">*</span></label>
                        <input type="text" name="account_number" class="form-input" placeholder="e.g. 50200012345678" required value="{{ old('account_number') }}" style="font-family:monospace; letter-spacing:.5px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IFSC Code <span class="req">*</span></label>
                        <input type="text" name="ifsc_code" class="form-input" placeholder="e.g. HDFC0001234" required value="{{ old('ifsc_code') }}" style="text-transform:uppercase; font-family:monospace;">
                    </div>
                </div>

                {{-- UPI ID & Swift / BIC Code --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">UPI ID</label>
                        <input type="text" name="upi_id" class="form-input" placeholder="e.g. myagency@hdfcbank" value="{{ old('upi_id') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Swift / BIC Code (Optional)</label>
                        <input type="text" name="swift_code" class="form-input" placeholder="e.g. HDFCINBBXXX" value="{{ old('swift_code') }}" style="text-transform:uppercase; font-family:monospace;">
                    </div>
                </div>

                {{-- Primary Settlement Checkbox --}}
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" name="is_primary" id="add_is_primary" value="1" {{ $stats['total'] === 0 ? 'checked' : '' }} style="width:16px; height:16px; accent-color:#fe5f04;">
                    <label for="add_is_primary" style="font-size:13px; font-weight:700; color:#0f172a; cursor:pointer; margin:0;">
                        Set as Primary Settlement Bank
                        <span style="display:block; font-size:11px; font-weight:500; color:#64748b;">Invoices will automatically use this account for payment transfers.</span>
                    </label>
                </div>

                {{-- Remarks --}}
                <div class="form-group">
                    <label class="form-label">Internal Remarks (Optional)</label>
                    <textarea name="remarks" class="form-input" rows="2" placeholder="Notes for internal accounts team...">{{ old('remarks') }}</textarea>
                </div>

            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeAddBankModal()">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Save Bank Account
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- MODAL: Edit Bank Account                                 --}}
{{-- ======================================================== --}}
<div id="editBankModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#fe5f04;"></i>
                <span>Edit Bank Account</span>
            </div>
            <button type="button" class="modal-close" onclick="closeEditBankModal()">&times;</button>
        </div>
        <form id="editBankForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">

                {{-- Screenshot Header Banner --}}
                <div class="form-section-card">
                    <i class="bi bi-shield-check" style="color:#ea580c; font-size:20px; flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:13px; font-weight:800; color:#9a3412;">Bank & Payment Settlement Details</div>
                        <div style="font-size:12px; color:#c2410c; margin-top:2px;">
                            Update bank account credentials used on invoices and client settlements.
                        </div>
                    </div>
                </div>

                {{-- Bank Name & Branch Name --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Bank Name <span class="req">*</span></label>
                        <input type="text" name="bank_name" id="edit_bank_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Name</label>
                        <input type="text" name="branch_name" id="edit_branch_name" class="form-input">
                    </div>
                </div>

                {{-- Account Holder / Beneficiary Name --}}
                <div class="form-group">
                    <label class="form-label">Account Holder / Beneficiary Name <span class="req">*</span></label>
                    <input type="text" name="account_name" id="edit_account_name" class="form-input" required>
                </div>

                {{-- Account Number & IFSC Code --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Account Number <span class="req">*</span></label>
                        <input type="text" name="account_number" id="edit_account_number" class="form-input" required style="font-family:monospace; letter-spacing:.5px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IFSC Code <span class="req">*</span></label>
                        <input type="text" name="ifsc_code" id="edit_ifsc_code" class="form-input" required style="text-transform:uppercase; font-family:monospace;">
                    </div>
                </div>

                {{-- UPI ID & Swift / BIC Code --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">UPI ID</label>
                        <input type="text" name="upi_id" id="edit_upi_id" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Swift / BIC Code (Optional)</label>
                        <input type="text" name="swift_code" id="edit_swift_code" class="form-input" style="text-transform:uppercase; font-family:monospace;">
                    </div>
                </div>

                {{-- Status & Primary Settings --}}
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Account Status <span class="req">*</span></label>
                        <select name="status" id="edit_status" class="form-input" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div style="display:flex; align-items:center; padding-top:22px;">
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#0f172a; cursor:pointer; margin:0;">
                            <input type="checkbox" name="is_primary" id="edit_is_primary" value="1" style="width:16px; height:16px; accent-color:#fe5f04;">
                            Set as Primary Settlement Bank
                        </label>
                    </div>
                </div>

                {{-- Remarks --}}
                <div class="form-group">
                    <label class="form-label">Internal Remarks (Optional)</label>
                    <textarea name="remarks" id="edit_remarks" class="form-input" rows="2"></textarea>
                </div>

            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeEditBankModal()">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Update Bank Account
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- MODAL: View Bank Account Details                         --}}
{{-- ======================================================== --}}
<div id="viewBankModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-bank" style="color:#fe5f04;"></i>
                <span id="view_modal_title">Bank Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeViewBankModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Bank Name</div>
                    <div class="detail-val" id="view_bank_name"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Branch Name</div>
                    <div class="detail-val" id="view_branch_name"></div>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Account Holder / Beneficiary Name</div>
                    <div class="detail-val" id="view_account_name"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Account Number</div>
                    <div class="detail-val" style="font-family:monospace; display:flex; align-items:center; gap:6px;">
                        <span id="view_account_number"></span>
                        <button type="button" class="copy-btn" id="view_copy_ac" title="Copy Account Number">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">IFSC Code</div>
                    <div class="detail-val" style="font-family:monospace; display:flex; align-items:center; gap:6px;">
                        <span id="view_ifsc_code"></span>
                        <button type="button" class="copy-btn" id="view_copy_ifsc" title="Copy IFSC">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">UPI ID</div>
                    <div class="detail-val" id="view_upi_id"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Swift / BIC Code</div>
                    <div class="detail-val" id="view_swift_code"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Primary Settlement Status</div>
                    <div class="detail-val" id="view_is_primary"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-val" id="view_status"></div>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Remarks</div>
                    <div class="detail-val" id="view_remarks" style="font-weight:500; color:#475569;"></div>
                </div>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc;">
            <button type="button" class="acm-btn acm-btn-secondary" onclick="closeViewBankModal()">Close</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Modal Controls
function openAddBankModal() {
    document.getElementById('addBankModal').classList.add('active');
}
function closeAddBankModal() {
    document.getElementById('addBankModal').classList.remove('active');
}

function openEditBankModal(bank) {
    const form = document.getElementById('editBankForm');
    form.action = `/accounts/master/banks/${bank.id}`;

    document.getElementById('edit_bank_name').value = bank.bank_name || '';
    document.getElementById('edit_branch_name').value = bank.branch_name || '';
    document.getElementById('edit_account_name').value = bank.account_name || '';
    document.getElementById('edit_account_number').value = bank.account_number || '';
    document.getElementById('edit_ifsc_code').value = bank.ifsc_code || '';
    document.getElementById('edit_upi_id').value = bank.upi_id || '';
    document.getElementById('edit_swift_code').value = bank.swift_code || '';
    document.getElementById('edit_status').value = bank.status || 'active';
    document.getElementById('edit_is_primary').checked = !!bank.is_primary;
    document.getElementById('edit_remarks').value = bank.remarks || '';

    document.getElementById('editBankModal').classList.add('active');
}
function closeEditBankModal() {
    document.getElementById('editBankModal').classList.remove('active');
}

function openViewBankModal(bank) {
    document.getElementById('view_modal_title').innerText = bank.bank_name + ' Details';
    document.getElementById('view_bank_name').innerText = bank.bank_name || '—';
    document.getElementById('view_branch_name').innerText = bank.branch_name || '—';
    document.getElementById('view_account_name').innerText = bank.account_name || '—';
    document.getElementById('view_account_number').innerText = bank.account_number || '—';
    document.getElementById('view_ifsc_code').innerText = bank.ifsc_code || '—';
    document.getElementById('view_upi_id').innerText = bank.upi_id || '—';
    document.getElementById('view_swift_code').innerText = bank.swift_code || '—';
    document.getElementById('view_remarks').innerText = bank.remarks || 'None';

    const isPrimaryBadge = bank.is_primary
        ? '<span class="primary-badge"><i class="bi bi-star-fill"></i> Primary Settlement Account</span>'
        : '<span style="color:#64748b; font-weight:600;">Secondary Account</span>';
    document.getElementById('view_is_primary').innerHTML = isPrimaryBadge;

    const statusBadge = bank.status === 'active'
        ? '<span class="acm-badge status-active">Active</span>'
        : '<span class="acm-badge status-inactive">Inactive</span>';
    document.getElementById('view_status').innerHTML = statusBadge;

    // Bind copy triggers
    document.getElementById('view_copy_ac').onclick = function() { copyText(bank.account_number, this); };
    document.getElementById('view_copy_ifsc').onclick = function() { copyText(bank.ifsc_code, this); };

    document.getElementById('viewBankModal').classList.add('active');
}
function closeViewBankModal() {
    document.getElementById('viewBankModal').classList.remove('active');
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
    ['addBankModal', 'editBankModal', 'viewBankModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
</script>
@endpush
