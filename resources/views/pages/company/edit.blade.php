@extends('layouts.app')

@section('title', 'Edit Company - ' . $company->company_name)

@push('styles')
<style>
.cform-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.cform-topbar { display:flex; justify-content:space-between; align-items:center; padding:0 28px; height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.cform-title { font-size:18px; font-weight:800; color:#121212; }
.cform-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.cform-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:700; text-decoration:none; border:none; cursor:pointer; font-family:inherit; }
.cform-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; }
.cform-btn-ghost { background:#fff; color:#121212; border:1px solid #e1dee3; }
.cform-body { padding:24px 28px 36px; }
.cform-card { max-width:980px; margin:0 auto; background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.cform-card-head { padding:18px 22px; border-bottom:1px solid #f0eef2; }
.cform-card-title { font-size:16px; font-weight:700; color:#121212; }
.cform-card-sub { font-size:12px; color:#9e9e9e; margin-top:4px; }
.cform-card-body { padding:22px; display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
.cform-group { display:flex; flex-direction:column; gap:6px; }
.cform-group.full { grid-column:1 / -1; }
.cform-label { font-size:13px; font-weight:700; color:#444; }
.cform-input, .cform-select, .cform-textarea { width:100%; padding:11px 12px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; font-family:inherit; outline:none; background:#fff; }
.cform-textarea { min-height:110px; resize:vertical; }
.cform-input:focus, .cform-select:focus, .cform-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.cform-error { font-size:12px; color:#dc2626; }
.cform-foot { padding:18px 22px; border-top:1px solid #f0eef2; display:flex; justify-content:flex-end; gap:10px; }
@media (max-width: 768px) {
    .cform-topbar { height:auto; padding:16px 20px; flex-direction:column; align-items:flex-start; gap:10px; }
    .cform-body { padding:16px 20px 24px; }
    .cform-card-body { grid-template-columns:1fr; padding:18px; }
    .cform-foot { padding:16px 18px; flex-direction:column; }
}
</style>
@endpush

@section('content')
<div class="cform-page">
    <div class="cform-topbar">
        <div>
            <div class="cform-title">Edit Company</div>
            <div class="cform-breadcrumb">Companies > {{ $company->company_name }} > Edit</div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('companies.show', $company) }}" class="cform-btn cform-btn-ghost">View</a>
            <a href="{{ route('companies.index') }}" class="cform-btn cform-btn-ghost">Back</a>
        </div>
    </div>

    <div class="cform-body">
        <form method="POST" action="{{ route('companies.update', $company) }}">
            @csrf
            @method('PUT')
            <div class="cform-card">
                <div class="cform-card-head">
                    <div class="cform-card-title">Company Details</div>
                    <div class="cform-card-sub">Update company profile and Facebook app credentials</div>
                </div>

                <div class="cform-card-body">
                    <div class="cform-group">
                        <label class="cform-label">Company Name</label>
                        <input type="text" name="company_name" class="cform-input" value="{{ old('company_name', $company->company_name) }}" required>
                        @error('company_name')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group">
                        <label class="cform-label">Email</label>
                        <input type="email" name="email" class="cform-input" value="{{ old('email', $company->email) }}" required>
                        @error('email')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group">
                        <label class="cform-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="cform-input" value="{{ old('mobile_number', $company->mobile_number) }}" required>
                        @error('mobile_number')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group">
                        <label class="cform-label">Number of Accounts</label>
                        <input type="number" min="1" name="number_of_accounts" class="cform-input" value="{{ old('number_of_accounts', $company->number_of_accounts) }}" required>
                        @error('number_of_accounts')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group">
                        <label class="cform-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="cform-input" value="{{ old('expiry_date', optional($company->expiry_date)->format('Y-m-d')) }}" required>
                        @error('expiry_date')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group">
                        <label class="cform-label">Company Status</label>
                        <select name="company_status" class="cform-select" required>
                            <option value="active" @selected(old('company_status', $company->company_status) === 'active')>Activate</option>
                            <option value="inactive" @selected(old('company_status', $company->company_status) === 'inactive')>Deactivate</option>
                        </select>
                        @error('company_status')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group full">
                        <label class="cform-label">Address</label>
                        <textarea name="address" class="cform-textarea" required>{{ old('address', $company->address) }}</textarea>
                        @error('address')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group">
                        <label class="cform-label">Facebook Client ID</label>
                        <input type="text" name="facebook_client_id" class="cform-input" value="{{ old('facebook_client_id', $company->facebook_client_id) }}" required>
                        @error('facebook_client_id')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group">
                        <label class="cform-label">Facebook Client Secret</label>
                        <input type="text" name="facebook_client_secret" class="cform-input" value="{{ old('facebook_client_secret', $company->facebook_client_secret) }}" required>
                        @error('facebook_client_secret')<div class="cform-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="cform-group full" style="margin-top:12px; padding-top:16px; border-top:1px dashed #e1dee3;">
                        <label class="cform-label" style="font-size:14px;color:#121212;">Company Feature Settings</label>
                        <div style="font-size:12px;color:#9e9e9e;">Select which features/sections should be enabled and visible for this company.</div>
                    </div>

                    <div class="cform-group full" style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; background:#fafafa; padding:16px; border-radius:12px; border:1px solid #f0eef2;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; font-weight:600; color:#333;">
                            <input type="checkbox" name="show_price_request" value="1" {{ old('show_price_request', $company->allowsPriceRequests()) ? 'checked' : '' }} style="width:18px; height:18px; accent-color:#fe5f04;">
                            Price Request Option
                        </label>

                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; font-weight:600; color:#333;">
                            <input type="checkbox" name="show_production_update" value="1" {{ old('show_production_update', $company->allowsProductionUpdates()) ? 'checked' : '' }} style="width:18px; height:18px; accent-color:#fe5f04;">
                            Production Update
                        </label>

                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; font-weight:600; color:#333;">
                            <input type="checkbox" name="show_approval_history" value="1" {{ old('show_approval_history', $company->allowsApprovalHistory()) ? 'checked' : '' }} style="width:18px; height:18px; accent-color:#fe5f04;">
                            Approval History
                        </label>

                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; font-weight:600; color:#333;">
                            <input type="checkbox" name="show_cst_updates" value="1" {{ old('show_cst_updates', $company->allowsCstUpdates()) ? 'checked' : '' }} style="width:18px; height:18px; accent-color:#fe5f04;">
                            CST Updates
                        </label>
                    </div>
                </div>

                <div class="cform-foot">
                    <a href="{{ route('companies.show', $company) }}" class="cform-btn cform-btn-ghost">Cancel</a>
                    <button type="submit" class="cform-btn cform-btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
