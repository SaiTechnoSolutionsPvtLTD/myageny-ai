@extends('layouts.app')

@section('title', 'Create Leave Request')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .lr-create-layout { display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:18px; align-items:start; }
        .lr-flow { display:flex; flex-direction:column; gap:10px; }
        .lr-flow-step { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .lr-flow-title { font-size:13px; font-weight:800; color:#121212; }
        .lr-flow-sub { margin-top:4px; font-size:12px; color:#7c7c7c; line-height:1.5; }
        .lr-note { padding:12px 14px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) { .lr-create-layout { grid-template-columns:1fr; } }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form[action="{{ route('leave-requests.store') }}"]');

            if (! form) {
                return;
            }

            form.addEventListener('submit', function () {
                const submitButton = form.querySelector('button[type="submit"]');

                if (! submitButton || submitButton.disabled) {
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';
            });
        });
    </script>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Create Leave Request</div>
            <div class="eob-breadcrumb">HRMS > Leave Requests > Create</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('leave-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please fix the highlighted fields and submit again.</div>
        @endif

        <div class="lr-create-layout">
            <form method="POST" action="{{ route('leave-requests.store') }}" class="eob-card">
                @csrf
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Leave Details</div>
                        <div class="eob-card-sub">Submit your leave request through your mapped approval hierarchy.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group full">
                            <label class="eob-label">Leave Type <span class="eob-label-required">*</span></label>
                            <select name="leave_type_id" class="eob-select" required>
                                <option value="">Select leave type</option>
                                @foreach($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                                @endforeach
                            </select>
                            @error('leave_type_id')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">Start Date <span class="eob-label-required">*</span></label>
                            <input type="date" name="start_date" class="eob-input" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">End Date <span class="eob-label-required">*</span></label>
                            <input type="date" name="end_date" class="eob-input" value="{{ old('end_date') }}" required>
                            @error('end_date')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group full">
                            <label class="eob-label">Reason <span class="eob-label-required">*</span></label>
                            <textarea name="reason" class="eob-textarea" placeholder="Explain why you need leave" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="eob-foot">
                    <a href="{{ route('leave-requests.index') }}" class="eob-btn eob-btn-ghost">Cancel</a>
                    <button type="submit" class="eob-btn eob-btn-primary">Submit Request</button>
                </div>
            </form>

            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Approval Hierarchy</div>
                        <div class="eob-card-sub">Approval follows User Mapping from manager to manager.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    @if($approvalChain->isEmpty())
                        <div class="lr-note">No manager hierarchy is mapped for your user. Please map this user under a manager before submitting.</div>
                    @else
                        <div class="lr-flow">
                            @foreach($approvalChain as $approver)
                                <div class="lr-flow-step">
                                    <div class="lr-flow-title">{{ $loop->iteration }}. {{ $approver->name }}</div>
                                    <div class="lr-flow-sub">{{ $approver->roles->first()?->display_name ?? $approver->roles->first()?->name ?? 'No Role' }} · {{ $approver->email }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(! $employee)
                        <div class="lr-note" style="margin-top:14px;">
                            No employee onboarding record was found for your login email. The request can still be submitted under your user account.
                        </div>
                    @endif

                    @if($leaveTypes->isEmpty())
                        <div class="lr-note" style="margin-top:14px;">
                            Create at least one Leave Type from Masters before submitting leave requests.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
