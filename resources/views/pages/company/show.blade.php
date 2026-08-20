@extends('layouts.app')

@section('title', 'Company - ' . $company->company_name)

@push('styles')
<style>
.cshow-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.cshow-topbar { display:flex; justify-content:space-between; align-items:center; padding:0 28px; height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.cshow-title { font-size:18px; font-weight:800; color:#121212; }
.cshow-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.cshow-actions { display:flex; gap:10px; }
.cshow-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:700; text-decoration:none; border:none; cursor:pointer; font-family:inherit; }
.cshow-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; }
.cshow-btn-ghost { background:#fff; color:#121212; border:1px solid #e1dee3; }
.cshow-body { padding:24px 28px 36px; }
.cshow-alert { max-width:1040px; margin:0 auto 16px; padding:12px 16px; border-radius:10px; font-size:13px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.cshow-layout { max-width:1040px; margin:0 auto; display:grid; grid-template-columns:320px 1fr; gap:18px; }
.cshow-profile, .cshow-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.cshow-banner { height:92px; background:linear-gradient(135deg,#fe5f04,#ff7c30); }
.cshow-profile-body { padding:0 22px 22px; }
.cshow-avatar { width:74px; height:74px; border-radius:18px; background:#fff; border:4px solid #fff; box-shadow:0 6px 18px rgba(0,0,0,.08); margin-top:-36px; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:800; color:#fe5f04; }
.cshow-name { font-size:20px; font-weight:800; color:#121212; margin-top:14px; }
.cshow-email { font-size:13px; color:#7c7c7c; margin-top:4px; word-break:break-all; }
.cshow-badge { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; border:1px solid transparent; margin-top:14px; }
.cshow-active { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
.cshow-inactive { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.cshow-side-list { display:flex; flex-direction:column; gap:12px; margin-top:18px; }
.cshow-side-item { padding:12px; border:1px solid #f0eef2; border-radius:12px; background:#fafafa; }
.cshow-side-label { font-size:10px; font-weight:800; text-transform:uppercase; color:#9e9e9e; letter-spacing:.5px; }
.cshow-side-value { font-size:14px; font-weight:700; color:#121212; margin-top:4px; }
.cshow-card-head { padding:16px 20px; border-bottom:1px solid #f0eef2; }
.cshow-card-title { font-size:15px; font-weight:700; color:#121212; }
.cshow-card-sub { font-size:12px; color:#9e9e9e; margin-top:4px; }
.cshow-card-body { padding:20px; }
.cshow-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:18px; }
.cshow-item { padding:14px; border:1px solid #f0eef2; border-radius:12px; background:#fafafa; }
.cshow-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:#9e9e9e; margin-bottom:6px; }
.cshow-value { font-size:14px; font-weight:700; color:#121212; word-break:break-word; }
.cshow-value.muted { font-weight:500; color:#666; }
.feat-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.feat-enabled { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.feat-disabled { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
@media (max-width: 900px) {
    .cshow-topbar { height:auto; padding:16px 20px; flex-direction:column; align-items:flex-start; gap:10px; }
    .cshow-body { padding:16px 20px 24px; }
    .cshow-layout { grid-template-columns:1fr; }
    .cshow-grid { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
<div class="cshow-page">
    <div class="cshow-topbar">
        <div>
            <div class="cshow-title">Company Profile</div>
            <div class="cshow-breadcrumb">Companies > {{ $company->company_name }}</div>
        </div>
        <div class="cshow-actions">
            @if(auth()->user()->isSystemAdmin())
            <a href="{{ route('companies.edit', $company) }}" class="cshow-btn cshow-btn-primary">Edit Company</a>
            @endif
            <a href="{{ route('companies.index') }}" class="cshow-btn cshow-btn-ghost">Back</a>
        </div>
    </div>

    <div class="cshow-body">
        @if(session('success'))
            <div class="cshow-alert">{{ strip_tags(session('success')) }}</div>
        @endif

        <div class="cshow-layout">
            <div class="cshow-profile">
                <div class="cshow-banner"></div>
                <div class="cshow-profile-body">
                    <div class="cshow-avatar">{{ strtoupper(substr($company->company_name, 0, 2)) }}</div>
                    <div class="cshow-name">{{ $company->company_name }}</div>
                    <div class="cshow-email">{{ $company->email }}</div>

                    <span class="cshow-badge {{ $company->company_status === 'active' ? 'cshow-active' : 'cshow-inactive' }}">
                        {{ $company->status_label }}
                    </span>

                    <div class="cshow-side-list">
                        <div class="cshow-side-item">
                            <div class="cshow-side-label">Mobile Number</div>
                            <div class="cshow-side-value">{{ $company->mobile_number }}</div>
                        </div>
                        <div class="cshow-side-item">
                            <div class="cshow-side-label">Number of Accounts</div>
                            <div class="cshow-side-value">{{ $company->number_of_accounts }}</div>
                        </div>
                        <div class="cshow-side-item">
                            <div class="cshow-side-label">Expiry Date</div>
                            <div class="cshow-side-value">{{ $company->expiry_date ? $company->expiry_date->format('d M Y') : 'N/A' }}</div>
                        </div>
                        <div class="cshow-side-item">
                            <div class="cshow-side-label">Created On</div>
                            <div class="cshow-side-value">{{ $company->created_at->format('d M Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:18px;">
                <div class="cshow-card">
                    <div class="cshow-card-head">
                        <div class="cshow-card-title">Company Details</div>
                        <div class="cshow-card-sub">Stored business and Facebook app configuration</div>
                    </div>
                    <div class="cshow-card-body">
                        <div class="cshow-grid">
                            <div class="cshow-item">
                                <div class="cshow-label">Company Name</div>
                                <div class="cshow-value">{{ $company->company_name }}</div>
                            </div>
                            <div class="cshow-item">
                                <div class="cshow-label">Email</div>
                                <div class="cshow-value">{{ $company->email }}</div>
                            </div>
                            <div class="cshow-item">
                                <div class="cshow-label">Mobile Number</div>
                                <div class="cshow-value">{{ $company->mobile_number }}</div>
                            </div>
                            <div class="cshow-item">
                                <div class="cshow-label">Company Status</div>
                                <div class="cshow-value">{{ $company->status_label }}</div>
                            </div>
                            <div class="cshow-item">
                                <div class="cshow-label">Expiry Date</div>
                                <div class="cshow-value">{{ $company->expiry_date ? $company->expiry_date->format('d M Y') : 'N/A' }}</div>
                            </div>
                            <div class="cshow-item">
                                <div class="cshow-label">Expiry Status</div>
                                <div class="cshow-value" style="color:{{ $company->isExpired() ? '#dc2626' : '#16a34a' }}">{{ $company->expiry_status }}</div>
                            </div>
                            <div class="cshow-item full">
                                <div class="cshow-label">Address</div>
                                <div class="cshow-value muted">{{ $company->address }}</div>
                            </div>
                            <div class="cshow-item">
                                <div class="cshow-label">Facebook Client ID</div>
                                <div class="cshow-value">{{ $company->facebook_client_id }}</div>
                            </div>
                            <div class="cshow-item">
                                <div class="cshow-label">Facebook Client Secret</div>
                                <div class="cshow-value">{{ $company->facebook_client_secret }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cshow-card">
                    <div class="cshow-card-head">
                        <div class="cshow-card-title">Feature Settings</div>
                        <div class="cshow-card-sub">Enabled feature options for this company</div>
                    </div>
                    <div class="cshow-card-body">
                        <div class="cshow-grid">
                            <div class="cshow-item">
                                <div class="cshow-label">Price Request Option</div>
                                <div class="cshow-value">
                                    <span class="feat-pill {{ $company->allowsPriceRequests() ? 'feat-enabled' : 'feat-disabled' }}">
                                        {{ $company->allowsPriceRequests() ? '✓ Enabled' : '✕ Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <div class="cshow-item">
                                <div class="cshow-label">Move to Production Option</div>
                                <div class="cshow-value">
                                    <span class="feat-pill {{ $company->allowsProductionUpdates() ? 'feat-enabled' : 'feat-disabled' }}">
                                        {{ $company->allowsProductionUpdates() ? '✓ Enabled' : '✕ Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <div class="cshow-item">
                                <div class="cshow-label">Approval History</div>
                                <div class="cshow-value">
                                    <span class="feat-pill {{ $company->allowsApprovalHistory() ? 'feat-enabled' : 'feat-disabled' }}">
                                        {{ $company->allowsApprovalHistory() ? '✓ Enabled' : '✕ Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <div class="cshow-item">
                                <div class="cshow-label">CST Updates</div>
                                <div class="cshow-value">
                                    <span class="feat-pill {{ $company->allowsCstUpdates() ? 'feat-enabled' : 'feat-disabled' }}">
                                        {{ $company->allowsCstUpdates() ? '✓ Enabled' : '✕ Disabled' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
