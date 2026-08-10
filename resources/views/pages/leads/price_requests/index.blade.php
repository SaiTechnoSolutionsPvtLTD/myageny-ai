@extends('layouts.app')

@section('title', 'Lead Price Requests')

@push('styles')
<style>
.page-wrap{padding:32px}
.page-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px}
.page-title{font-size:22px;font-weight:700;color:#121212}
.filter-card,.table-card{background:#fff;border:1px solid #e1dee3;border-radius:14px}
.filter-card{padding:18px;margin-bottom:20px}
.filter-form{display:grid;grid-template-columns:repeat(5,minmax(0,1fr)) auto auto;gap:12px;align-items:end}
.filter-group{display:flex;flex-direction:column;gap:6px}
.filter-label{font-size:12px;font-weight:600;color:#666}
.filter-input,.filter-select,.table-input{width:100%;padding:10px 12px;border:1px solid #e1dee3;border-radius:10px;font-size:13px;background:#fff}
.filter-input:focus,.filter-select:focus,.table-input:focus{border-color:#fe5f04;box-shadow:0 0 0 3px rgba(254,95,4,.1);outline:none}
.btn-primary,.btn-reset,.btn-approve,.btn-reject{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;border:1px solid transparent;text-decoration:none;cursor:pointer}
.btn-primary{background:#fe5f04;color:#fff}
.btn-reset{background:#fff;border-color:#e1dee3;color:#444}
.btn-approve{background:#f0fdf4;border-color:#bbf7d0;color:#166534;transition:all 0.15s ease;}
.btn-approve:hover{background:#dcfce7;border-color:#86efac;}
.btn-reject{background:#fef2f2;border-color:#fecaca;color:#b91c1c;transition:all 0.15s ease;}
.btn-reject:hover{background:#fee2e2;border-color:#fca5a5;}
.table-responsive{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:14px 16px;border-bottom:1px solid #f1f1f1;text-align:left;vertical-align:top}
th{font-size:12px;font-weight:700;color:#7c7c7c;background:#fafafa}
td{font-size:13px;color:#121212}
.badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700}
.badge-pending{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.badge-approved{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.badge-rejected{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.meta{font-size:11px;color:#8a8a8a;margin-top:4px}
.money-old{font-size:12px;color:#8a8a8a}
.money-new{font-size:15px;font-weight:700;color:#121212}
.money-diff-up{font-size:12px;font-weight:700;color:#dc2626}
.money-diff-down{font-size:12px;font-weight:700;color:#15803d}
.actions{display:flex;flex-direction:column;gap:8px;min-width:180px}
.empty{padding:56px 20px;text-align:center;color:#9e9e9e}
@media (max-width:1200px){.filter-form{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:768px){.page-head{flex-direction:column;align-items:flex-start}.filter-form{grid-template-columns:1fr}}

/* Modals & Progress Overlay */
.pr-modal-overlay {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px);
    display: flex; align-items: center; justify-content: center; z-index: 9999;
}
.pr-modal-box {
    background: #fff; border-radius: 18px; width: 90%; max-width: 520px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); overflow: hidden; animation: prIn 0.2s ease-out;
}
@keyframes prIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.pr-modal-head {
    padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; font-weight: 700; font-size: 16px;
}
.pr-modal-head.approve { background: #f0fdf4; border-bottom: 1px solid #bbf7d0; color: #166534; }
.pr-modal-head.reject { background: #fef2f2; border-bottom: 1px solid #fecaca; color: #991b1b; }
.pr-modal-body { padding: 22px; font-size: 14px; color: #374151; }
.pr-modal-foot { padding: 14px 20px; background: #fafafa; border-top: 1px solid #f1f1f1; display: flex; justify-content: flex-end; gap: 10px; }

.support-process-overlay {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px);
    display: flex; justify-content: center; align-items: center; z-index: 99999;
}
.support-process-card {
    background: #ffffff; border-radius: 24px; padding: 36px 40px; width: 440px; max-width: 90vw; text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.2);
}
.support-process-icon-wrap {
    position: relative; width: 72px; height: 72px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;
}
.support-process-spinner {
    position: absolute; inset: 0; border: 3px solid #f1f5f9; border-top-color: #fe5f04; border-radius: 50%; animation: processSpin 1s linear infinite;
}
@keyframes processSpin { to { transform: rotate(360deg); } }
.support-process-icon { font-size: 32px; color: #fe5f04; animation: pulseIcon 1.5s ease-in-out infinite alternate; }
@keyframes pulseIcon { from { transform: scale(0.88); opacity: 0.85; } to { transform: scale(1.12); opacity: 1; } }
.support-process-title { font-size: 19px; font-weight: 800; color: #111827; margin: 0 0 6px; }
.support-process-subtitle { font-size: 13px; color: #6b7280; margin: 0 0 24px; line-height: 1.5; }
.support-progress-wrapper { width: 100%; }
.support-progress-bar { width: 100%; height: 10px; background: #e2e8f0; border-radius: 999px; overflow: hidden; position: relative; }
.support-progress-fill {
    height: 100%; width: 0%; background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 50%, #fe5f04 100%);
    background-size: 200% 100%; border-radius: 999px; transition: width 0.3s ease; animation: gradientMove 2s linear infinite;
}
@keyframes gradientMove { 0% { background-position: 0% 0%; } 100% { background-position: 200% 0%; } }
.support-progress-status { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 12px; font-weight: 700; color: #4b5563; }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <div class="page-head">
        <div class="page-title">Lead Price Change Requests</div>
    </div>

    @if(session('success'))
        <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c">
            {{ session('error') }}
        </div>
    @endif

    <div class="filter-card">
        <form method="GET" action="{{ route('lead-price-requests.index') }}" class="filter-form">
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\LeadProductPriceRequest::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Requested By</label>
                <select name="requested_by" class="filter-select">
                    <option value="">All Users</option>
                    @foreach($requesters as $user)
                        <option value="{{ $user->id }}" {{ request('requested_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Lead ID</label>
                <input type="text" name="lead_id" value="{{ request('lead_id') }}" placeholder="Lead ID" class="filter-input">
            </div>

            <div class="filter-group">
                <label class="filter-label">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-input">
            </div>

            <div class="filter-group">
                <label class="filter-label">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-input">
            </div>

            <button type="submit" class="btn-primary">Filter</button>
            <a href="{{ route('lead-price-requests.index') }}" class="btn-reset">Reset</a>
        </form>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Product & Deal</th>
                        <th>Requested By</th>
                        <th>Original Unit Price</th>
                        <th>Requested Unit Price</th>
                        <th>Qty & Disc</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $priceRequest)
                        @php
                            $difference = $priceRequest->price_difference;
                            $badgeClass = $priceRequest->status === 'approved' ? 'badge-approved' : ($priceRequest->status === 'rejected' ? 'badge-rejected' : 'badge-pending');
                            
                            $allocatedUser = $priceRequest->lead?->assignedTo ?? $priceRequest->requestedBy;
                            $recipientName = $allocatedUser?->name ?? 'Allocated Person';
                            $recipientEmail = $allocatedUser?->email ?? '';
                        @endphp
                        <tr>
                            <td>
                                <strong>Lead #{{ $priceRequest->lead_id }}</strong>
                                <div class="meta">{{ $priceRequest->lead?->company_name ?? $priceRequest->lead?->contact_name ?? 'Lead' }}</div>
                                <div class="meta">Deal: {{ $priceRequest->deal_name }}</div>
                            </td>
                            <td>
                                <strong>{{ $priceRequest->product_name }}</strong>
                                @if($priceRequest->remarks)
                                    <div class="meta">Remarks: {{ $priceRequest->remarks }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $priceRequest->requestedBy?->name ?? 'User' }}
                                <div class="meta">{{ $priceRequest->created_at->format('d M Y h:i A') }}</div>
                            </td>
                            <td>
                                <div class="money-old">₹{{ number_format($priceRequest->original_unit_price, 2) }}</div>
                            </td>
                            <td>
                                <div class="money-new">₹{{ number_format($priceRequest->requested_unit_price, 2) }}</div>
                                @if($difference > 0)
                                    <div class="money-diff-up">+₹{{ number_format($difference, 2) }}</div>
                                @elseif($difference < 0)
                                    <div class="money-diff-down">-₹{{ number_format(abs($difference), 2) }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $priceRequest->quantity }}</strong> qty
                                <div class="meta">{{ number_format($priceRequest->discount_percent, 2) }}% discount</div>
                                <div class="meta">Total: ₹{{ number_format($priceRequest->requested_total, 2) }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($priceRequest->status) }}</span>
                                @if($priceRequest->approvedBy)
                                    <div class="meta">By {{ $priceRequest->approvedBy->name }}</div>
                                @endif
                                @if($priceRequest->rejection_reason)
                                    <div class="meta">Reason: {{ $priceRequest->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    @if($priceRequest->status === 'pending')
                                        <form id="approve-form-{{ $priceRequest->id }}" method="POST" action="{{ route('lead-price-requests.approve', $priceRequest) }}" style="display:none;">
                                            @csrf
                                            @method('PATCH')
                                        </form>

                                        <form id="reject-form-{{ $priceRequest->id }}" method="POST" action="{{ route('lead-price-requests.reject', $priceRequest) }}" style="display:none;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="rejection_reason" id="reject-reason-input-{{ $priceRequest->id }}">
                                        </form>

                                        <button type="button" class="btn-approve" style="width:100%"
                                                onclick="confirmApprove('{{ $priceRequest->id }}', '{{ $priceRequest->lead_id }}', '{{ addslashes($priceRequest->deal_name) }}', '{{ addslashes($priceRequest->product_name) }}', '₹{{ number_format($priceRequest->requested_unit_price, 2) }}', '{{ addslashes($recipientName) }}', '{{ $recipientEmail }}')">
                                            Approve
                                        </button>
                                        <button type="button" class="btn-reject" style="width:100%;margin-top:4px"
                                                onclick="confirmReject('{{ $priceRequest->id }}', '{{ $priceRequest->lead_id }}', '{{ addslashes($priceRequest->deal_name) }}', '{{ addslashes($priceRequest->product_name) }}', '₹{{ number_format($priceRequest->requested_unit_price, 2) }}', '{{ addslashes($recipientName) }}', '{{ $recipientEmail }}')">
                                            Reject
                                        </button>
                                    @else
                                        <a href="{{ route('leads.show', $priceRequest->lead_id) }}" class="btn-reset">View Lead</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty">No price change requests found.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            @include('partials.table-pagination', ['paginator' => $requests])
        @endif
    </div>
</div>

{{-- APPROVE CONFIRMATION MODAL --}}
<div id="approveConfirmModal" class="pr-modal-overlay" style="display: none;">
    <div class="pr-modal-box">
        <div class="pr-modal-head approve">
            <span>✓ Confirm Price Request Approval</span>
            <button type="button" style="background:none;border:none;font-size:18px;cursor:pointer;color:#166534;" onclick="closeApproveModal()">&times;</button>
        </div>
        <div class="pr-modal-body">
            <p style="margin: 0 0 14px; font-weight:700; font-size:15px; color:#111827;">Are you sure you want to APPROVE this price change request?</p>
            
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:16px; font-size:13px; line-height:1.6;">
                <div><strong>Lead ID:</strong> <span id="approve_lead_info"></span></div>
                <div><strong>Deal Name:</strong> <span id="approve_deal_name"></span></div>
                <div><strong>Product:</strong> <span id="approve_product_name"></span></div>
                <div><strong>Approved Price:</strong> <span id="approve_asked_price" style="color:#16a34a; font-weight:800;"></span></div>
            </div>

            <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.5;">
                An email notification will be sent to the allocated person: <strong id="approve_recipient_info" style="color:#111827;"></strong>.
            </p>
        </div>
        <div class="pr-modal-foot">
            <button type="button" class="btn-reset" onclick="closeApproveModal()">Cancel</button>
            <button type="button" class="btn-approve" onclick="executeApprove()">Yes, Approve Request</button>
        </div>
    </div>
</div>

{{-- REJECT CONFIRMATION MODAL --}}
<div id="rejectConfirmModal" class="pr-modal-overlay" style="display: none;">
    <div class="pr-modal-box">
        <div class="pr-modal-head reject">
            <span>✕ Confirm Price Request Rejection</span>
            <button type="button" style="background:none;border:none;font-size:18px;cursor:pointer;color:#991b1b;" onclick="closeRejectModal()">&times;</button>
        </div>
        <div class="pr-modal-body">
            <input type="hidden" id="reject_form_id_holder">
            <p style="margin: 0 0 14px; font-weight:700; font-size:15px; color:#111827;">Are you sure you want to REJECT this price change request?</p>
            
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:16px; font-size:13px; line-height:1.6;">
                <div><strong>Lead ID:</strong> <span id="reject_lead_info"></span></div>
                <div><strong>Deal Name:</strong> <span id="reject_deal_name"></span></div>
                <div><strong>Product:</strong> <span id="reject_product_name"></span></div>
                <div><strong>Requested Price:</strong> <span id="reject_asked_price" style="color:#dc2626; font-weight:800;"></span></div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:#374151;">Rejection Reason (Optional)</label>
                <input type="text" id="modal_rejection_reason" class="filter-input" placeholder="Enter reason for rejecting this price request...">
            </div>

            <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.5;">
                A rejection email notification will be sent to the allocated person: <strong id="reject_recipient_info" style="color:#111827;"></strong>.
            </p>
        </div>
        <div class="pr-modal-foot">
            <button type="button" class="btn-reset" onclick="closeRejectModal()">Cancel</button>
            <button type="button" class="btn-reject" onclick="executeReject()">Yes, Reject Request</button>
        </div>
    </div>
</div>

{{-- MAIL PROCESS OVERLAY LOADER --}}
<div id="priceRequestStatusOverlay" class="support-process-overlay" style="display: none;">
    <div class="support-process-card">
        <div class="support-process-icon-wrap">
            <div class="support-process-spinner"></div>
            <i class="bi bi-envelope-paper-fill support-process-icon"></i>
        </div>
        <h4 id="prStatusOverlayTitle" class="support-process-title">Processing Price Request...</h4>
        <p id="prStatusOverlaySubtitle" class="support-process-subtitle">Please wait while the status update email notification is sent...</p>

        <div class="support-progress-wrapper">
            <div class="support-progress-bar">
                <div id="prStatusProgressFill" class="support-progress-fill"></div>
            </div>
            <div class="support-progress-status">
                <span id="prStatusProgressText">Preparing email notification...</span>
                <span id="prStatusProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentSubmitFormId = null;
let progressInterval = null;

function confirmApprove(id, leadId, dealName, productName, askedPrice, recipientName, recipientEmail) {
    document.getElementById('approve_lead_info').innerText = '#' + leadId;
    document.getElementById('approve_deal_name').innerText = dealName;
    document.getElementById('approve_product_name').innerText = productName;
    document.getElementById('approve_asked_price').innerText = askedPrice;
    document.getElementById('approve_recipient_info').innerText = recipientName + (recipientEmail ? ' (' + recipientEmail + ')' : '');
    
    currentSubmitFormId = 'approve-form-' + id;
    document.getElementById('approveConfirmModal').style.display = 'flex';
}

function confirmReject(id, leadId, dealName, productName, askedPrice, recipientName, recipientEmail) {
    document.getElementById('reject_lead_info').innerText = '#' + leadId;
    document.getElementById('reject_deal_name').innerText = dealName;
    document.getElementById('reject_product_name').innerText = productName;
    document.getElementById('reject_asked_price').innerText = askedPrice;
    document.getElementById('reject_recipient_info').innerText = recipientName + (recipientEmail ? ' (' + recipientEmail + ')' : '');
    document.getElementById('modal_rejection_reason').value = '';
    
    currentSubmitFormId = 'reject-form-' + id;
    document.getElementById('reject_form_id_holder').value = id;
    document.getElementById('rejectConfirmModal').style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approveConfirmModal').style.display = 'none';
}

function closeRejectModal() {
    document.getElementById('rejectConfirmModal').style.display = 'none';
}

function executeApprove() {
    closeApproveModal();
    startProgressAndSubmit('Approving Price Request...', 'Sending confirmation email to allocated team member...');
}

function executeReject() {
    const reqId = document.getElementById('reject_form_id_holder').value;
    const reason = document.getElementById('modal_rejection_reason').value;
    if (reqId && document.getElementById('reject-reason-input-' + reqId)) {
        document.getElementById('reject-reason-input-' + reqId).value = reason;
    }
    closeRejectModal();
    startProgressAndSubmit('Rejecting Price Request...', 'Sending rejection notification email to allocated team member...');
}

function startProgressAndSubmit(title, subtitle) {
    const overlay = document.getElementById('priceRequestStatusOverlay');
    const fill = document.getElementById('prStatusProgressFill');
    const percentText = document.getElementById('prStatusProgressPercent');
    const statusText = document.getElementById('prStatusProgressText');
    const titleText = document.getElementById('prStatusOverlayTitle');
    const subText = document.getElementById('prStatusOverlaySubtitle');

    if (titleText) titleText.innerText = title;
    if (subText) subText.innerText = subtitle;

    overlay.style.display = 'flex';
    let currentProgress = 10;
    fill.style.width = currentProgress + '%';
    percentText.innerText = currentProgress + '%';
    statusText.innerText = 'Preparing email notification...';

    progressInterval = setInterval(function() {
        if (currentProgress < 40) {
            currentProgress += Math.floor(Math.random() * 8) + 5;
            statusText.innerText = 'Building status update email...';
        } else if (currentProgress < 85) {
            currentProgress += Math.floor(Math.random() * 6) + 3;
            statusText.innerText = 'Sending email notification to allocated user...';
        } else if (currentProgress < 95) {
            currentProgress += 1;
            statusText.innerText = 'Updating price request record in database...';
        }

        if (currentProgress > 95) {
            currentProgress = 95;
        }

        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
    }, 180);

    setTimeout(function() {
        if (currentSubmitFormId && document.getElementById(currentSubmitFormId)) {
            document.getElementById(currentSubmitFormId).submit();
        }
    }, 1000);
}
</script>
@endpush
