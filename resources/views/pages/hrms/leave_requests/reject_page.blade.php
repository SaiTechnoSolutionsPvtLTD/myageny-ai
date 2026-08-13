@extends('layouts.app')

@section('title', 'Reject Leave Request - myAgenci.ai HRMS')

@push('styles')
<style>
.lr-reject-page {
    min-height: 100%;
    padding: 32px;
    background:
        radial-gradient(circle at top left, rgba(239, 68, 68, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
}
.lr-reject-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 22px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
    width: 580px;
    max-width: 100%;
    overflow: hidden;
}
.lr-reject-head {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    padding: 24px 28px;
    color: #ffffff;
}
.lr-reject-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
}
.lr-reject-sub {
    margin: 6px 0 0;
    font-size: 13px;
    opacity: 0.9;
}
.lr-reject-body {
    padding: 28px;
}
.lr-summary-box {
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 24px;
}
.lr-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 13px;
}
.lr-summary-label {
    color: #6b7280;
    font-weight: 700;
}
.lr-summary-val {
    color: #111827;
    font-weight: 800;
}
.lr-textarea {
    width: 100%;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
    resize: vertical;
}
.lr-textarea:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}
.lr-btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    border: none;
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #ffffff;
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.28);
    transition: all 0.2s ease;
}
</style>
@endpush

@section('content')
<div class="lr-reject-page">
    <div class="lr-reject-card">
        <div class="lr-reject-head">
            <h3 class="lr-reject-title">Reject Leave Request</h3>
            <p class="lr-reject-sub">Please provide detailed remarks for rejecting this leave request.</p>
        </div>

        <div class="lr-reject-body">
            <div class="lr-summary-box">
                <div class="lr-summary-row">
                    <span class="lr-summary-label">Applicant Name:</span>
                    <span class="lr-summary-val">{{ $leaveRequest->user?->name }}</span>
                </div>
                <div class="lr-summary-row">
                    <span class="lr-summary-label">Leave Type:</span>
                    <span class="lr-summary-val">{{ $leaveRequest->leaveType?->name }}</span>
                </div>
                <div class="lr-summary-row">
                    <span class="lr-summary-label">Leave Dates:</span>
                    <span class="lr-summary-val" style="color:#ea580c;">{{ $leaveRequest->start_date?->format('d M Y') }} to {{ $leaveRequest->end_date?->format('d M Y') }} ({{ $leaveRequest->total_days }} Days)</span>
                </div>
                <div style="margin-top:10px; padding-top:10px; border-top:1px solid #e5e7eb; font-size:13px; color:#4b5563;">
                    <strong>Reason:</strong> {{ $leaveRequest->reason }}
                </div>
            </div>

            <form method="POST" action="{{ route('leave-requests.reject', [$leaveRequest, $approval]) }}">
                @csrf
                @method('PATCH')
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:12px; font-weight:800; text-transform:uppercase; color:#374151; margin-bottom:8px;">
                        Rejection Reason / Remarks <span style="color:#dc2626;">*</span>
                    </label>
                    <textarea name="remarks" rows="4" class="lr-textarea" required placeholder="Enter clear remarks detailing why this leave request is being rejected..."></textarea>
                </div>

                <div style="display:flex; gap:12px; align-items:center;">
                    <a href="{{ route('leave-requests.show', $leaveRequest) }}" style="display:inline-flex; align-items:center; justify-content:center; padding:12px 20px; border-radius:12px; border:1px solid #d1d5db; background:#fff; color:#374151; font-weight:700; font-size:14px; text-decoration:none; width:40%; text-align:center;">
                        Cancel
                    </a>
                    <button type="submit" class="lr-btn-submit" style="width:60%;">
                        Submit Rejection Remarks
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
