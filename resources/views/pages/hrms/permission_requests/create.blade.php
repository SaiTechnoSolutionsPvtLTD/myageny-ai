@extends('layouts.app')

@section('title', 'Create Permission Request')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .pr-create-layout { display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:18px; align-items:start; }
        .pr-flow { display:flex; flex-direction:column; gap:10px; }
        .pr-flow-step { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .pr-flow-title { font-size:13px; font-weight:800; color:#121212; }
        .pr-flow-sub { margin-top:4px; font-size:12px; color:#7c7c7c; line-height:1.5; }
        .pr-note { padding:12px 14px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) { .pr-create-layout { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Create Permission Request</div>
            <div class="eob-breadcrumb">HRMS > Permission Requests > Create</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('permission-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Permission date, time, and reason are mandatory.</div>
        @endif

        <div class="pr-create-layout">
            <form method="POST" action="{{ route('permission-requests.store') }}" class="eob-card">
                @csrf
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Permission Details</div>
                        <div class="eob-card-sub">Date, time, and reason are required before submitting.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group full">
                            <label class="eob-label">Permission Date <span class="eob-label-required">*</span></label>
                            <input type="date" name="permission_date" class="eob-input" value="{{ old('permission_date') }}" required>
                            @error('permission_date')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">From Time <span class="eob-label-required">*</span></label>
                            <input type="time" name="from_time" class="eob-input" value="{{ old('from_time') }}" required>
                            @error('from_time')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">To Time <span class="eob-label-required">*</span></label>
                            <input type="time" name="to_time" class="eob-input" value="{{ old('to_time') }}" required>
                            @error('to_time')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group full">
                            <label class="eob-label">Reason <span class="eob-label-required">*</span></label>
                            <textarea name="reason" class="eob-textarea" placeholder="Explain why you need permission" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="eob-foot">
                    <a href="{{ route('permission-requests.index') }}" class="eob-btn eob-btn-ghost">Cancel</a>
                    <button type="submit" class="eob-btn eob-btn-primary">Submit Request</button>
                </div>
            </form>

            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Approval Hierarchy</div>
                        <div class="eob-card-sub">Approval follows your mapped manager first, then continues through the configured role hierarchy.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    @if($approvalChain->isEmpty())
                        <div class="pr-note">No approval hierarchy could be resolved for your user. Please verify the first manager mapping and the role parent mappings before submitting.</div>
                    @else
                        <div class="pr-flow">
                            @foreach($approvalChain as $approver)
                                <div class="pr-flow-step">
                                    <div class="pr-flow-title">{{ $loop->iteration }}. {{ $approver->name }}</div>
                                    <div class="pr-flow-sub">{{ $approver->roles->first()?->display_name ?? $approver->roles->first()?->name ?? 'No Role' }} · {{ $approver->email }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(! $employee)
                        <div class="pr-note" style="margin-top:14px;">No employee onboarding record was found for your login email. The request can still be submitted under your user account.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
