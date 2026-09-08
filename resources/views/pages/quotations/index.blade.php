@extends('layouts.app')

@section('title', 'Quotations')

@push('styles')
<style>
.page-wrapper { padding: 32px; }
.page-header {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 24px;
}
.page-title { font-size: 22px; font-weight: 700; color: #121212; }
.btn-primary {
    display: inline-flex; align-items: center; gap: 6px;
    background-color: #fe5f04; color: #fff;
    padding: 8px 18px; border-radius: 20px;
    font-size: 14px; font-weight: 600; border: none; cursor: pointer;
    text-decoration: none;
}
.btn-primary:hover { background-color: #e05400; }
.btn-sm {
    padding: 4px 12px; font-size: 12px; border-radius: 12px;
}
.card {
    background: #fff; border: 1px solid #e1dee3;
    border-radius: 14px; overflow: hidden;
}
.filter-card {
    background: #fff; border: 1px solid #e1dee3; border-radius: 14px;
    padding: 18px; margin-bottom: 20px;
}
.filter-form {
    display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)) auto auto;
    gap: 12px; align-items: end;
}
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-label { font-size: 12px; font-weight: 600; color: #666; }
.filter-input, .filter-select {
    width: 100%; padding: 10px 12px; border: 1px solid #e1dee3; border-radius: 10px;
    font-size: 13px; color: #121212; background: #fff; outline: none;
}
.filter-input:focus, .filter-select:focus {
    border-color: #fe5f04; box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.10);
}
.btn-filter-reset {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600;
    border: 1px solid #e1dee3; color: #444; text-decoration: none; background: #fff;
}
.btn-filter-reset:hover { border-color: #cfc9d2; background: #fafafa; }
.table-responsive { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8f8f8; }
th {
    padding: 12px 16px; font-size: 12px; font-weight: 600;
    color: #7c7c7c; text-align: left; border-bottom: 1px solid #e1dee3;
}
td {
    padding: 13px 16px; font-size: 13px; color: #121212;
    border-bottom: 1px solid #f1f1f1; vertical-align: middle;
}
tbody tr:hover { background: #fdf8f5; }
tbody tr:last-child td { border-bottom: none; }
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.badge-approved  { background: #edfaf3; color: #1a7a52; border: 1px solid #b6ead0; }
.badge-pending   { background: #fff8ec; color: #9a6200; border: 1px solid #ffd98a; }
.badge-agree     { background: #edfaf3; color: #1a7a52; border: 1px solid #b6ead0; }
.badge-disagree  { background: #fef2f2; color: #b42318; border: 1px solid #fecaca; }
.action-btns { display: flex; gap: 6px; }
.btn-outline-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #e1dee3;
    background: #fff; cursor: pointer; text-decoration: none; color: #444;
}
.btn-outline-sm:hover { border-color: #fe5f04; color: #fe5f04; }
.btn-danger-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #ffc4c4;
    background: #fff5f5; cursor: pointer; color: #c00;
    text-decoration: none;
}
.btn-send-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #b6ead0;
    background: #edfaf3; cursor: pointer; color: #1a7a52;
    text-decoration: none;
}
.btn-send-sm:hover { border-color: #1a7a52; color: #11583a; }
.btn-send-sm:disabled {
    opacity: .55; cursor: not-allowed; border-color: #e1dee3;
    background: #f8f8f8; color: #9e9e9e;
}
.alert-success {
    background: #edfaf3; border: 1px solid #b6ead0; color: #1a7a52;
    padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
}
.alert-error {
    background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
    padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
}
.empty-state { text-align: center; padding: 60px 20px; color: #9e9e9e; }
.empty-state i { font-size: 42px; margin-bottom: 12px; display: block; }
.qt-modal-backdrop {
    position: fixed; inset: 0; z-index: 1400; display: none;
    align-items: center; justify-content: center; padding: 20px;
    background: rgba(18, 18, 18, .42);
}
.qt-modal-backdrop.is-open { display: flex; }
.qt-modal {
    width: min(420px, 100%); background: #fff; border-radius: 14px;
    border: 1px solid #e1dee3; box-shadow: 0 24px 70px rgba(18, 18, 18, .22);
    padding: 22px;
}
.qt-modal-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; margin-bottom: 14px;
}
.qt-modal-title { font-size: 18px; font-weight: 700; color: #121212; }
.qt-modal-close {
    width: 34px; height: 34px; border-radius: 10px; border: 1px solid #e1dee3;
    color: #444; background: #fff; display: inline-flex; align-items: center; justify-content: center;
}
.qt-modal-close:hover { border-color: #fe5f04; color: #fe5f04; }
.qt-modal-copy { font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 16px; }
.qt-modal-note {
    padding: 10px 12px; border-radius: 10px; background: #fff8ec;
    border: 1px solid #ffd98a; color: #7a4c00; font-size: 13px; margin-bottom: 18px;
}
.qt-modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
.qt-modal-actions .btn-primary,
.qt-modal-actions .btn-filter-reset { border-radius: 10px; }
.qt-modal-actions .btn-primary:disabled { opacity: .7; cursor: wait; }
@media (max-width: 1200px) {
    .filter-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .filter-form { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Header --}}
    <div class="page-header">
        <div class="page-title">Quotations</div>

    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <div class="filter-card">
        <form method="GET" action="{{ route('quotations.index') }}" class="filter-form">
            @if(request('lead_id'))
                <input type="hidden" name="lead_id" value="{{ request('lead_id') }}">
            @endif

            <div class="filter-group">
                <label class="filter-label" for="quotation_no">Quotation No</label>
                <input
                    type="text"
                    id="quotation_no"
                    name="quotation_no"
                    class="filter-input"
                    value="{{ request('quotation_no') }}"
                    placeholder="Search quotation no">
            </div>

            <div class="filter-group">
                <label class="filter-label" for="start_date">Start Date</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    class="filter-input"
                    value="{{ request('start_date') }}">
            </div>

            <div class="filter-group">
                <label class="filter-label" for="end_date">End Date</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    class="filter-input"
                    value="{{ request('end_date') }}">
            </div>

            <div class="filter-group">
                <label class="filter-label" for="status">Status</label>
                <select id="status" name="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label" for="approved_by">Approved By</label>
                <select id="approved_by" name="approved_by" class="filter-select">
                    <option value="">All Users</option>
                    @foreach($approvers as $approver)
                        <option value="{{ $approver->id }}" @selected((string) request('approved_by') === (string) $approver->id)>
                            {{ $approver->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary">Apply Filter</button>
            <a href="{{ route('quotations.index', request()->filled('lead_id') ? ['lead_id' => request('lead_id')] : []) }}" class="btn-filter-reset">Reset</a>
        </form>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Quotation No</th>
                        <th>Lead</th>
                        <th>Date</th>
                        <th>Valid Until</th>
                        <th>Total Amount</th>
                        <th>Customer Status</th>
                        {{--  <th>Status</th>  --}}
                        {{--  <th>Approved By</th>  --}}
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $q)
                    @php
                        $leadEmail = $q->lead?->email;
                        $response = $q->customer_response ?: 'pending';
                        $responseBadge = match ($response) {
                            'agree' => 'badge-agree',
                            'disagree' => 'badge-disagree',
                            default => 'badge-pending',
                        };
                        $responseIcon = match ($response) {
                            'agree' => 'bi-check-circle-fill',
                            'disagree' => 'bi-x-circle-fill',
                            default => 'bi-clock-fill',
                        };
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration + ($quotations->firstItem() - 1) }}</td>
                        <td><strong>{{ $q->quotation_no }}</strong></td>
                        <td>{{ $q->lead?->contact_name ?? '—' }}<br>
                            <small style="color:#9e9e9e">{{ $q->lead->company_name ?? '' }}</small>
                        </td>
                        <td>{{ $q->quotation_date->format('d M Y') }}</td>
                        <td>{{ $q->valid_until->format('d M Y') }}</td>
                        <td><strong>₹{{ number_format($q->total_amount, 2) }}</strong></td>
                        <td>
                            <span class="badge {{ $responseBadge }}">
                                <i class="bi {{ $responseIcon }}"></i> {{ $q->customer_response_label }}
                            </span>
                            @if($q->customer_responded_at)
                                <br>
                                <small style="color:#9e9e9e">{{ $q->customer_responded_at->format('d M Y, h:i A') }}</small>
                            @endif
                        </td>
                        {{--  <td>
                            @if($q->is_approved)
                                <span class="badge badge-approved"><i class="bi bi-check-circle-fill"></i> Approved</span>
                            @else
                                <span class="badge badge-pending"><i class="bi bi-clock-fill"></i> Pending</span>
                            @endif
                        </td>  --}}
                        {{--  <td>{{ $q->approver->name ?? '—' }}</td>  --}}
                        <td>
                            <div class="action-btns">
                                <a href="/quotations/{{ $q->id }}/pdf" target="_blank" class="btn-outline-sm">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <button
                                    type="button"
                                    class="btn-send-sm js-send-quotation"
                                    data-action="{{ route('quotations.send-email', $q) }}"
                                    data-quotation="{{ $q->quotation_no }}"
                                    data-email="{{ $leadEmail ?? '' }}"
                                    data-lead="{{ $q->lead?->contact_name ?? $q->lead?->company_name ?? 'Lead' }}"
                                    @if(empty($leadEmail)) disabled title="Lead email not available" @endif>
                                    <i class="bi bi-send"></i> Send Quotation
                                </button>
                                @if(!$q->is_approved)
                                {{--  <form method="POST" action="{{ route('quotations.approve', $q) }}" style="display:inline">
                                    @csrf @method('PATCH')
                                    <button class="btn-outline-sm" style="border-color:#4caf50;color:#2e7d32">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>  --}}
                                @endif
                                <form method="POST" action="{{ route('quotations.destroy', $q) }}"
                                      onsubmit="return confirm('Delete this quotation?')" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                No quotations found.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        @if($quotations->hasPages())
        <div style="padding: 0;">
            @include('partials.table-pagination', ['paginator' => $quotations])
        </div>
        @endif
    </div>

    <div class="qt-modal-backdrop" id="sendQuotationModal" aria-hidden="true">
        <div class="qt-modal" role="dialog" aria-modal="true" aria-labelledby="sendQuotationTitle">
            <div class="qt-modal-header">
                <div>
                    <div class="qt-modal-title" id="sendQuotationTitle">Send Quotation</div>
                </div>
                <button type="button" class="qt-modal-close" id="sendQuotationClose" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="qt-modal-copy">
                Quotation <strong id="sendQuotationNo"></strong> will be sent to
                <strong id="sendQuotationEmail"></strong>.
            </div>
            <div class="qt-modal-note">
                The quotation PDF will be attached in the email.
            </div>

            <form method="POST" id="sendQuotationForm">
                @csrf
                <div class="qt-modal-actions">
                    <button type="button" class="btn-filter-reset" id="sendQuotationCancel">Cancel</button>
                    <button type="submit" class="btn-primary" id="sendQuotationSubmit">
                        <i class="bi bi-send"></i> Send Mail
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('sendQuotationModal');
    const form = document.getElementById('sendQuotationForm');
    const closeButton = document.getElementById('sendQuotationClose');
    const cancelButton = document.getElementById('sendQuotationCancel');
    const submitButton = document.getElementById('sendQuotationSubmit');
    const quotationNo = document.getElementById('sendQuotationNo');
    const quotationEmail = document.getElementById('sendQuotationEmail');
    const submitText = submitButton ? submitButton.innerHTML : '';

    if (!modal || !form || !submitButton || !quotationNo || !quotationEmail) {
        return;
    }

    function openModal(button) {
        if (button.disabled) {
            return;
        }

        form.action = button.dataset.action || '';
        quotationNo.textContent = button.dataset.quotation || '';
        quotationEmail.textContent = button.dataset.email || '';
        submitButton.disabled = false;
        submitButton.innerHTML = submitText;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        submitButton.focus();
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        form.removeAttribute('action');
        submitButton.disabled = false;
        submitButton.innerHTML = submitText;
    }

    document.querySelectorAll('.js-send-quotation').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    closeButton?.addEventListener('click', closeModal);
    cancelButton?.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    form.addEventListener('submit', function () {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
    });
})();
</script>
@endpush
