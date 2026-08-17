@extends('layouts.app')

@section('title', 'Reject Expense Request - myAgenci.ai HRMS')

@push('styles')
<style>
.exp-reject-page {
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
.exp-reject-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 22px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
    width: 580px;
    max-width: 100%;
    overflow: hidden;
}
.exp-reject-head {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    padding: 24px 28px;
    color: #ffffff;
}
.exp-reject-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
}
.exp-reject-sub {
    margin: 6px 0 0;
    font-size: 13px;
    opacity: 0.9;
}
.exp-reject-body {
    padding: 28px;
}
.exp-summary-box {
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 24px;
}
.exp-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 13px;
}
.exp-summary-label {
    color: #6b7280;
    font-weight: 700;
}
.exp-summary-val {
    color: #111827;
    font-weight: 800;
}
.exp-textarea {
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
.exp-textarea:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}
.exp-btn-submit {
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
.exp-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.38);
}
</style>
@endpush

@section('content')
<div class="exp-reject-page">
    <div class="exp-reject-card">
        <div class="exp-reject-head">
            <h3 class="exp-reject-title">Reject Expense Request</h3>
            <p class="exp-reject-sub">Please provide detailed remarks for rejecting this expense request.</p>
        </div>

        <div class="exp-reject-body">
            <div class="exp-summary-box">
                <div class="exp-summary-row">
                    <span class="exp-summary-label">Applicant Name:</span>
                    <span class="exp-summary-val">{{ $expenseRequest->user?->name }}</span>
                </div>
                <div class="exp-summary-row">
                    <span class="exp-summary-label">Expense Category:</span>
                    <span class="exp-summary-val">{{ $expenseRequest->category?->name }}</span>
                </div>
                <div class="exp-summary-row">
                    <span class="exp-summary-label">Requested Amount:</span>
                    <span class="exp-summary-val" style="color:#dc2626; font-size:16px;">₹{{ number_format($expenseRequest->amount, 2) }}</span>
                </div>
                <div style="margin-top:10px; padding-top:10px; border-top:1px solid #e5e7eb; font-size:13px; color:#4b5563;">
                    <strong>Description:</strong> {{ $expenseRequest->description }}
                </div>
            </div>

            <form method="POST" action="{{ route('hrms.expense-requests.reject', $expenseRequest) }}">
                @csrf
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:12px; font-weight:800; text-transform:uppercase; color:#374151; margin-bottom:8px;">
                        Rejection Reason / Remarks <span style="color:#dc2626;">*</span>
                    </label>
                    <textarea name="rejection_reason" rows="4" class="exp-textarea" required placeholder="Enter clear remarks detailing why this expense request is being rejected..."></textarea>
                </div>

                <div style="display:flex; gap:12px; align-items:center;">
                    <a href="{{ route('hrms.expense-requests.index') }}" style="display:inline-flex; align-items:center; justify-content:center; padding:12px 20px; border-radius:12px; border:1px solid #d1d5db; background:#fff; color:#374151; font-weight:700; font-size:14px; text-decoration:none; width:40%; text-align:center;">
                        Cancel
                    </a>
                    <button type="submit" class="exp-btn-submit" style="width:60%;">
                        Submit Rejection Remarks
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
