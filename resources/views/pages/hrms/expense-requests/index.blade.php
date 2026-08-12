@extends('layouts.app')

@section('title', 'Expense Requests - myAgenci.ai HRMS')

@push('styles')
<style>
.exp-req-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.exp-req-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
}
.exp-req-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.exp-req-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.exp-req-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.6;
    color: #6b7280;
}
.exp-req-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    border: none;
    transition: all .2s ease;
    text-decoration: none;
    font-family: inherit;
}
.exp-req-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    box-shadow: 0 6px 20px rgba(254, 95, 4, 0.28);
}
.exp-req-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(254, 95, 4, 0.38);
    color: #fff;
}
.exp-req-btn-outline {
    background: #fff;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.exp-req-btn-outline:hover {
    border-color: #fe5f04;
    color: #fe5f04;
}

/* Stats */
.exp-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.exp-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 18px 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 14px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    gap: 14px;
}
.exp-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.exp-stat-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; }
.exp-stat-val { font-size: 22px; font-weight: 800; color: #111827; margin-top: 2px; }

/* Table Card */
.exp-req-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}
.exp-req-filter-bar {
    padding: 18px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    background: #fafafa;
}
.exp-search-input {
    padding: 8px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 13px;
    outline: none;
    background: #fff;
}
.exp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.exp-table th {
    background: #f9fafb;
    padding: 14px 20px;
    text-align: left;
    font-weight: 800;
    color: #4b5563;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .6px;
    border-bottom: 1px solid #e5e7eb;
}
.exp-table td {
    padding: 14px 20px;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
    vertical-align: middle;
}
.exp-table tr:hover { background: #fffdfb; }

/* Badges */
.exp-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.eb-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.eb-approved { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.eb-rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Modal */
.exp-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}
.exp-modal {
    background: #ffffff;
    border-radius: 20px;
    width: 90%;
    max-width: 540px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    animation: popIn .2s ease;
    overflow: hidden;
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.exp-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}
.exp-modal-title { font-size: 18px; font-weight: 800; color: #111827; }
.exp-modal-close {
    background: none; border: none; font-size: 22px; color: #9ca3af; cursor: pointer;
}
.exp-modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.exp-form-group { display: flex; flex-direction: column; gap: 6px; }
.exp-form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #4b5563; }
.exp-input, .exp-select, .exp-textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
}
.exp-input:focus, .exp-select:focus, .exp-textarea:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.exp-modal-foot {
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
</style>
@endpush

@section('content')
<div class="exp-req-page">

    {{-- Hero --}}
    <div class="exp-req-hero">
        <div>
            <div class="exp-req-kicker">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Expense Reimbursements
            </div>
            <h2 class="exp-req-title">Expense Requests</h2>
            <p class="exp-req-subtitle">Submit expense requests to trigger multi-stage hierarchy email approvals (e.g. Sales Executive ➔ Sales TL ➔ Sales Manager ➔ COO).</p>
        </div>
        <div>
            <button class="exp-req-btn exp-req-btn-primary" onclick="openSendModal()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Send Expense Request
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div style="margin-bottom:20px; padding:14px 18px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats Cards --}}
    @php
        $totalCount    = $requests->total();
        $pendingCount  = \App\Models\ExpenseRequest::where('status', 'pending')->count();
        $approvedCount = \App\Models\ExpenseRequest::where('status', 'approved')->count();
        $rejectedCount = \App\Models\ExpenseRequest::where('status', 'rejected')->count();
    @endphp
    <div class="exp-stats-row">
        <div class="exp-stat-card">
            <div class="exp-stat-icon" style="background:#fff1e8; color:#fe5f04;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Total Requests</div>
                <div class="exp-stat-val">{{ $totalCount }}</div>
            </div>
        </div>
        <div class="exp-stat-card">
            <div class="exp-stat-icon" style="background:#fffbeb; color:#b45309;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Pending Approval</div>
                <div class="exp-stat-val" style="color:#b45309;">{{ $pendingCount }}</div>
            </div>
        </div>
        <div class="exp-stat-card">
            <div class="exp-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Approved</div>
                <div class="exp-stat-val" style="color:#16a34a;">{{ $approvedCount }}</div>
            </div>
        </div>
        <div class="exp-stat-card">
            <div class="exp-stat-icon" style="background:#fef2f2; color:#dc2626;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Rejected</div>
                <div class="exp-stat-val" style="color:#dc2626;">{{ $rejectedCount }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="exp-req-card">
        <form method="GET" action="{{ route('hrms.expense-requests.index') }}">
            <div class="exp-req-filter-bar">
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <input type="text" name="search" class="exp-search-input" style="width:220px;" placeholder="Search description, applicant…" value="{{ request('search') }}">
                    
                    <select name="expense_category_id" class="exp-search-input" style="width:170px;" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('expense_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>

                    <select name="status" class="exp-search-input" style="width:130px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    <button type="submit" class="exp-req-btn exp-req-btn-primary" style="padding:7px 16px; font-size:12px; height:34px; border-radius:9px;">Filter</button>

                    @if(request()->hasAny(['search', 'status', 'expense_category_id']))
                    <a href="{{ route('hrms.expense-requests.index') }}" style="font-size:12px; color:#6b7280; text-decoration:none; font-weight:600;">Reset</a>
                    @endif
                </div>

                <div style="font-size:12px; font-weight:700; color:#6b7280;">
                    Showing {{ $requests->total() }} Requests
                </div>
            </div>
        </form>

        <table class="exp-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Applicant</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Current Approver Stage</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $index => $req)
                @php
                    $applicantRole = $req->user?->roles->first()?->display_name ?? ucfirst(str_replace('_',' ', $req->user?->roles->first()?->name ?? 'User'));
                    $canAction = false;
                    if ($req->status === 'pending') {
                        if (auth()->user()?->isCompanyAdmin() || auth()->user()?->isSystemAdmin()) {
                            $canAction = true;
                        } elseif ($req->current_approver_role_id && in_array($req->current_approver_role_id, $userRoleIds)) {
                            $canAction = true;
                        }
                    }
                @endphp
                <tr>
                    <td style="color:#9ca3af; font-weight:600;">
                        {{ $requests->firstItem() + $index }}
                    </td>
                    <td>
                        <strong style="color:#111827; font-size:14px; display:block;">{{ $req->user?->name ?? 'User #'.$req->user_id }}</strong>
                        <span style="font-size:11px; color:#6b7280;">{{ $applicantRole }} • {{ $req->user?->branch?->name ?? 'Main Branch' }}</span>
                    </td>
                    <td>
                        <span style="display:inline-block; padding:3px 10px; border-radius:6px; background:#fff7ed; color:#ea580c; font-weight:700; font-size:12px;">
                            {{ $req->category?->name ?? 'General' }}
                        </span>
                    </td>
                    <td>
                        <strong style="color:#111827; font-size:15px;">₹{{ number_format($req->amount, 2) }}</strong>
                    </td>
                    <td style="color:#4b5563; max-width:240px;">
                        {{ Str::limit($req->description, 75) }}
                        @if($req->attachment)
                            <div style="margin-top:4px;">
                                <a href="{{ asset('storage/' . $req->attachment) }}" target="_blank" style="font-size:11px; color:#fe5f04; text-decoration:underline; font-weight:700;">
                                    📎 View Receipt / Bill
                                </a>
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'pending')
                            <span style="font-size:12px; font-weight:700; color:#374151;">
                                Stage {{ $req->current_step }}: {{ $req->currentApproverRole?->display_name ?? ucfirst(str_replace('_',' ', $req->currentApproverRole?->name ?? 'Pending Role')) }}
                            </span>
                        @elseif($req->status === 'approved')
                            <span style="font-size:11px; color:#166534; font-weight:700;">
                                Approved by {{ $req->approver?->name ?? 'Approver' }}
                            </span>
                        @else
                            <span style="font-size:11px; color:#991b1b; font-weight:700;" title="{{ $req->rejection_reason }}">
                                Rejected by {{ $req->approver?->name ?? 'Approver' }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'pending')
                            <span class="exp-badge eb-pending">⏳ Pending</span>
                        @elseif($req->status === 'approved')
                            <span class="exp-badge eb-approved">✓ Approved</span>
                        @else
                            <span class="exp-badge eb-rejected">✕ Rejected</span>
                        @endif
                    </td>
                    <td style="color:#9ca3af; font-size:12px;">
                        {{ $req->created_at ? $req->created_at->format('d M Y, h:i A') : '-' }}
                    </td>
                    <td style="text-align:right;">
                        @if($canAction)
                            <div style="display:inline-flex; gap:6px;">
                                <form method="POST" action="{{ route('hrms.expense-requests.approve', $req) }}">
                                    @csrf
                                    <button type="submit" class="exp-req-btn exp-req-btn-primary" style="padding:5px 12px; font-size:11px; background:#16a34a; box-shadow:none;">
                                        Approve
                                    </button>
                                </form>

                                <button type="button" class="exp-req-btn exp-req-btn-outline" style="padding:5px 10px; font-size:11px; color:#dc2626; border-color:#fecaca;"
                                    onclick="openRejectModal({{ $req->id }})">
                                    Reject
                                </button>
                            </div>
                        @else
                            <span style="font-size:12px; color:#9ca3af;">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:50px 20px; color:#6b7280;">
                        <div style="font-size:36px; margin-bottom:8px;">💸</div>
                        <div style="font-weight:700; color:#374151; font-size:15px;">No Expense Requests Found</div>
                        <div style="font-size:13px; margin-top:4px;">Click "Send Expense Request" to submit an expense reimbursement.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests->hasPages())
        <div style="padding:16px 24px; border-top:1px solid #f3f4f6;">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Send Expense Request Modal --}}
<div class="exp-modal-overlay" id="sendModal">
    <div class="exp-modal">
        <div class="exp-modal-head">
            <div class="exp-modal-title">Send Expense Request</div>
            <button class="exp-modal-close" onclick="closeSendModal()">✕</button>
        </div>

        <form method="POST" action="{{ route('hrms.expense-requests.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="exp-modal-body">
                
                <div class="exp-form-group">
                    <label class="exp-form-label">Expense Category <span style="color:#dc2626;">*</span></label>
                    <select name="expense_category_id" class="exp-select" required>
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Amount (₹) <span style="color:#dc2626;">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="exp-input" required placeholder="0.00">
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Description & Reason <span style="color:#dc2626;">*</span></label>
                    <textarea name="description" rows="4" class="exp-textarea" required placeholder="Detail the expense purpose, location, date, and items purchased..."></textarea>
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Attach Receipt / Invoice (Optional)</label>
                    <input type="file" name="attachment" class="exp-input" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                    <span style="font-size:11px; color:#6b7280; margin-top:2px;">PDF, PNG, JPG up to 5MB</span>
                </div>

            </div>

            <div class="exp-modal-foot">
                <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeSendModal()">Cancel</button>
                <button type="submit" class="exp-req-btn exp-req-btn-primary">Send Request</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Reason Modal --}}
<div class="exp-modal-overlay" id="rejectModal">
    <div class="exp-modal">
        <div class="exp-modal-head">
            <div class="exp-modal-title">Reject Expense Request</div>
            <button class="exp-modal-close" onclick="closeRejectModal()">✕</button>
        </div>

        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="exp-modal-body">
                <div class="exp-form-group">
                    <label class="exp-form-label">Rejection Reason</label>
                    <textarea name="rejection_reason" rows="3" class="exp-textarea" placeholder="Provide reason for rejecting this expense request..."></textarea>
                </div>
            </div>

            <div class="exp-modal-foot">
                <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="exp-req-btn exp-req-btn-primary" style="background:#dc2626;">Reject Request</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openSendModal() {
    document.getElementById('sendModal').style.display = 'flex';
}
function closeSendModal() {
    document.getElementById('sendModal').style.display = 'none';
}

function openRejectModal(requestId) {
    document.getElementById('rejectForm').action = `/hrms/expense-requests/${requestId}/reject`;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

document.getElementById('sendModal').addEventListener('click', function(e) {
    if (e.target === this) closeSendModal();
});
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endpush
