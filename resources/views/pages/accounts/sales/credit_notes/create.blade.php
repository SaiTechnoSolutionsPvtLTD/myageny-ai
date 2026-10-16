@extends('layouts.app')

@section('title', 'Create Credit Note - Accounts Sales')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; max-width:1000px; margin:0 auto; width:100%; }
@media (max-width: 900px) {
    .inv-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .inv-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-control { width:100%; height:40px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; color:#0f172a; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
.form-control:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-file-earmark-minus" style="color:#dc2626;"></i>
                <span>Create Credit Note (CN)</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.invoices.index') }}">Sales</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.credit-notes.index') }}">Credit Notes</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">New CN</span>
            </div>
        </div>
        <div class="inv-actions">
            <a href="{{ route('accounts.sales.credit-notes.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancel
            </a>
        </div>
    </div>

    <div class="inv-body">

        @if(session('error'))
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('accounts.sales.credit-notes.store') }}" enctype="multipart/form-data" id="cnForm">
            @csrf

            <div class="acm-card" style="padding:24px;">
                <div style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-file-earmark-text" style="color:#fe5f04;"></i>
                        <span>Credit Note Details</span>
                    </div>
                    <div style="font-size:12px; font-family:monospace; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:3px 10px; border-radius:6px; font-weight:800;">
                        Auto-assigned: #{{ $nextCnNumber }}
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
                    {{-- Invoice Selector --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">
                            Select Invoice to Credit <span style="color:#dc2626;">*</span>
                        </label>
                        <select name="invoice_id" id="invoiceSelect" class="form-control" required onchange="onInvoiceSelected()">
                            <option value="">-- Choose an Invoice --</option>
                            @foreach($invoices as $inv)
                                @php
                                    $existingCn = (float) $inv->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
                                    $maxCreditable = max(0, (float) $inv->total_amount - $existingCn);
                                @endphp
                                <option value="{{ $inv->id }}" 
                                    data-total="{{ (float) $inv->total_amount }}" 
                                    data-balance="{{ (float) $inv->balance_due }}"
                                    data-max-credit="{{ $maxCreditable }}"
                                    data-customer="{{ $inv->customer_name }}"
                                    data-date="{{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '' }}"
                                    {{ (old('invoice_id', $selectedInvoice?->id) == $inv->id) ? 'selected' : '' }}>
                                    Invoice #{{ $inv->invoice_number }} — {{ $inv->customer_name }} (Total: ₹{{ number_format((float)$inv->total_amount, 2) }}, Balance: ₹{{ number_format((float)$inv->balance_due, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Invoice Info Banner --}}
                    <div id="invInfoBanner" style="grid-column: 1 / -1; display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px;">
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Customer</div>
                                <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:2px;" id="dispCustomer">—</div>
                            </div>
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Invoice Date</div>
                                <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:2px;" id="dispDate">—</div>
                            </div>
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Invoice Total</div>
                                <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:2px;" id="dispTotal">₹0.00</div>
                            </div>
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Current Balance Due</div>
                                <div style="font-size:14px; font-weight:800; color:#ea580c; margin-top:2px;" id="dispBalDue">₹0.00</div>
                            </div>
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Max Allowable Credit</div>
                                <div style="font-size:14px; font-weight:800; color:#15803d; margin-top:2px;" id="dispMaxCredit">₹0.00</div>
                            </div>
                        </div>
                    </div>

                    {{-- Credit Note Date --}}
                    <div class="form-group">
                        <label class="form-label">
                            Credit Note Date <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="date" name="credit_note_date" class="form-control" required value="{{ old('credit_note_date', date('Y-m-d')) }}">
                    </div>

                    {{-- Reason --}}
                    <div class="form-group">
                        <label class="form-label">
                            Reason for Credit Note <span style="color:#dc2626;">*</span>
                        </label>
                        <select name="reason" class="form-control" required>
                            @foreach($reasons as $rKey => $rLabel)
                                <option value="{{ $rKey }}" {{ old('reason') === $rKey ? 'selected' : '' }}>{{ $rLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Credit Amount --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2px;">
                            <label class="form-label" style="margin:0;">
                                Amount to Credit (₹) <span style="color:#dc2626;">*</span>
                            </label>
                            <div style="display:flex; gap:8px;">
                                <button type="button" onclick="fillBalanceDue()" style="font-size:11.5px; font-weight:700; color:#2563eb; background:none; border:none; cursor:pointer; padding:0;">
                                    Fill Current Balance Due
                                </button>
                                <span style="color:#cbd5e1;">•</span>
                                <button type="button" onclick="fillMaxCreditable()" style="font-size:11.5px; font-weight:700; color:#16a34a; background:none; border:none; cursor:pointer; padding:0;">
                                    Fill Full Invoice Total
                                </button>
                            </div>
                        </div>
                        <input type="number" step="0.01" min="0.01" name="total_amount" id="inputCreditAmount" class="form-control" required placeholder="0.00" value="{{ old('total_amount') }}" style="font-size:16px; font-weight:800; color:#dc2626;">
                        <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                            💡 This credit amount will be applied against the invoice, reducing its balance due immediately upon saving.
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Internal Notes / Reason Details (Optional)</label>
                        <textarea name="notes" rows="2" class="form-control" style="height:auto;" placeholder="e.g. Scope reduction approved via email by client, 10% goodwill discount on revised milestone...">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Attachment --}}
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Proof / Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" style="padding-top:6px;">
                        <div style="font-size:11px; color:#94a3b8;">Accepted: PDF, Images, Word Docs (Max 10MB)</div>
                    </div>
                </div>

                <div style="margin-top:24px; padding-top:16px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:10px;">
                    <a href="{{ route('accounts.sales.credit-notes.index') }}" class="acm-btn acm-btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="acm-btn acm-btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg"></i> Issue Credit Note
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
let currentInvoiceData = null;

function onInvoiceSelected() {
    const sel = document.getElementById('invoiceSelect');
    const opt = sel.options[sel.selectedIndex];
    const banner = document.getElementById('invInfoBanner');

    if (!opt || !opt.value) {
        banner.style.display = 'none';
        currentInvoiceData = null;
        return;
    }

    currentInvoiceData = {
        total: parseFloat(opt.dataset.total) || 0,
        balance: parseFloat(opt.dataset.balance) || 0,
        maxCredit: parseFloat(opt.dataset.maxCredit) || 0,
        customer: opt.dataset.customer || '',
        date: opt.dataset.date || ''
    };

    document.getElementById('dispCustomer').textContent = currentInvoiceData.customer;
    document.getElementById('dispDate').textContent = currentInvoiceData.date;
    document.getElementById('dispTotal').textContent = '₹' + currentInvoiceData.total.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('dispBalDue').textContent = '₹' + currentInvoiceData.balance.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('dispMaxCredit').textContent = '₹' + currentInvoiceData.maxCredit.toLocaleString('en-IN', {minimumFractionDigits: 2});

    banner.style.display = 'block';

    const amtInput = document.getElementById('inputCreditAmount');
    if (!amtInput.value || parseFloat(amtInput.value) === 0) {
        amtInput.value = currentInvoiceData.balance > 0 ? currentInvoiceData.balance.toFixed(2) : currentInvoiceData.maxCredit.toFixed(2);
    }
}

function fillBalanceDue() {
    if (!currentInvoiceData) return;
    document.getElementById('inputCreditAmount').value = currentInvoiceData.balance.toFixed(2);
}

function fillMaxCreditable() {
    if (!currentInvoiceData) return;
    document.getElementById('inputCreditAmount').value = currentInvoiceData.maxCredit.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('invoiceSelect');
    if (sel && sel.value) {
        onInvoiceSelected();
    }
});
</script>
@endpush
@endsection
