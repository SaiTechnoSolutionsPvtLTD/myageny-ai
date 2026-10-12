@extends('layouts.app')

@section('title', 'Quotation ' . $quotation->quotation_no)

@push('styles')
<style>
.page-wrapper { padding: 32px; max-width: 960px; }
.page-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.page-title   { font-size:22px; font-weight:700; color:#121212; }
.back-link    { color:#9e9e9e; font-size:13px; text-decoration:none; }
.back-link:hover{ color:#fe5f04; }

.qt-card {
    background:#fff; border:1px solid #e1dee3;
    border-radius:14px; overflow:hidden; margin-bottom:20px;
}
.qt-card-header{
    display:flex; justify-content:space-between; align-items:center;
    padding:18px 24px; background:#fafafa;
    border-bottom:1px solid #e1dee3;
}
.qt-card-body { padding:24px; }
.qt-no  { font-size:18px; font-weight:700; color:#121212; }
.badge-approved { background:#edfaf3; color:#1a7a52; border:1px solid #b6ead0; }
.badge-pending  { background:#fff8ec; color:#9a6200; border:1px solid #ffd98a; }
.badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;
}

.info-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
.info-item label { font-size:11px; font-weight:600; color:#9e9e9e; display:block; margin-bottom:4px; }
.info-item span  { font-size:14px; font-weight:500; color:#121212; }

.qt-table{ width:100%; border-collapse:collapse; }
.qt-table th{
    padding:10px 14px; font-size:11px; font-weight:600;
    color:#7c7c7c; background:#f8f8f8; border-bottom:1px solid #e1dee3;
    text-align:left;
}
.qt-table td{
    padding:12px 14px; font-size:13px; color:#121212;
    border-bottom:1px solid #f4f4f4; vertical-align:middle;
}
.qt-table tbody tr:last-child td { border-bottom:none; }

.totals-section{ display:flex; justify-content:flex-end; padding-top:18px; border-top:1px solid #f0f0f0; }
.totals-box{ min-width:260px; display:flex; flex-direction:column; gap:10px; }
.totals-line { display:flex; justify-content:space-between; font-size:13px; color:#444; }
.totals-line.grand { font-size:16px; font-weight:700; color:#121212; padding-top:10px; border-top:1px solid #e1dee3; }

.action-row{ display:flex; gap:10px; flex-wrap:wrap; }
.btn-approve{
    display:inline-flex; align-items:center; gap:6px;
    background:#1a7a52; color:#fff; border:none;
    padding:9px 22px; border-radius:20px;
    font-size:13px; font-weight:600; cursor:pointer; font-family:inherit;
}
.btn-approve:hover{ background:#155f3f; }
.btn-back{
    display:inline-flex; align-items:center; gap:6px;
    background:#f4f4f4; color:#555; border:none;
    padding:9px 22px; border-radius:20px;
    font-size:13px; font-weight:500; cursor:pointer;
    text-decoration:none; font-family:inherit;
}
.alert-success{
    background:#edfaf3; border:1px solid #b6ead0; color:#1a7a52;
    padding:12px 18px; border-radius:10px; margin-bottom:20px; font-size:14px;
}
</style>
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <a href="{{ route('quotations.index') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Quotations
            </a>
            <div class="page-title" style="margin-top:4px">{{ $quotation->quotation_no }}</div>
        </div>
        <div class="action-row">
            @if(!$quotation->is_approved)
            <form method="POST" action="{{ route('quotations.approve', $quotation) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-approve">
                    <i class="bi bi-check-circle-fill"></i> Approve
                </button>
            </form>
            @endif
            <a href="{{ route('quotations.edit', $quotation) }}" class="btn-back">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('quotations.index') }}" class="btn-back">
                <i class="bi bi-list-ul"></i> All Quotations
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="qt-card">
        <div class="qt-card-header">
            <span class="qt-no">{{ $quotation->quotation_no }}</span>
            @if($quotation->is_approved)
                <span class="badge badge-approved"><i class="bi bi-check-circle-fill"></i> Approved</span>
            @else
                <span class="badge badge-pending"><i class="bi bi-clock-fill"></i> Pending Approval</span>
            @endif
        </div>
        <div class="qt-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Lead</label>
                    <span>{{ $quotation->lead->contact_name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <label>Company</label>
                    <span>{{ $quotation->lead->company_name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <label>Quotation Date</label>
                    <span>{{ $quotation->quotation_date->format('d M Y') }}</span>
                </div>
                <div class="info-item">
                    <label>Valid Until</label>
                    <span>{{ $quotation->valid_until->format('d M Y') }}</span>
                </div>
                <div class="info-item">
                    <label>Tax Type</label>
                    <span>{{ $quotation->tax_type === 'igst' ? 'IGST' : 'CGST + SGST' }}</span>
                </div>
                <div class="info-item">
                    <label>Client GST</label>
                    <span>{{ $quotation->gst_number ?: '-' }}</span>
                </div>
                <div class="info-item">
                    <label>Customer State</label>
                    <span>{{ $quotation->customer_state ?: '-' }}</span>
                </div>
                <div class="info-item">
                    <label>Approved By</label>
                    <span>{{ $quotation->approver->name ?? '-' }}</span>
                </div>
                @if($quotation->bill_to_address)
                <div class="info-item" style="grid-column: span 3">
                    <label>Bill To Address</label>
                    <span style="line-height: 1.35; display: block;">{!! nl2br(e($quotation->bill_to_address)) !!}</span>
                </div>
                @endif
                @if($quotation->ship_to_address && $quotation->ship_to_address !== $quotation->bill_to_address)
                <div class="info-item" style="grid-column: span 3">
                    <label>Ship To Address</label>
                    <span style="line-height: 1.35; display: block;">{!! nl2br(e($quotation->ship_to_address)) !!}</span>
                </div>
                @endif
            </div>

            <table class="qt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Description</th>
                        <th style="text-align:center">Qty</th>
                        <th style="text-align:right">Unit Price</th>
                        <th style="text-align:right">Discount</th>
                        <th style="text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><strong>{{ $item->product->product_name ?? '-' }}</strong></td>
                        <td style="color:#6e6e6e">{{ $item->description ?: '-' }}</td>
                        <td style="text-align:center">{{ $item->qty }}</td>
                        <td style="text-align:right">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align:right">₹{{ number_format($item->discount, 2) }}</td>
                        <td style="text-align:right"><strong>₹{{ number_format($item->total, 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals-section">
                <div class="totals-box">
                    <div class="totals-line">
                        <span>Subtotal</span>
                        <span>₹{{ number_format($quotation->subtotal, 2) }}</span>
                    </div>
                    @if($quotation->igst_amount > 0)
                    <div class="totals-line">
                        <span>IGST ({{ number_format($quotation->igst_rate, 2) }}%)</span>
                        <span>₹{{ number_format($quotation->igst_amount, 2) }}</span>
                    </div>
                    @else
                    <div class="totals-line">
                        <span>CGST ({{ number_format($quotation->cgst_rate, 2) }}%)</span>
                        <span>₹{{ number_format($quotation->cgst_amount, 2) }}</span>
                    </div>
                    <div class="totals-line">
                        <span>SGST ({{ number_format($quotation->sgst_rate, 2) }}%)</span>
                        <span>₹{{ number_format($quotation->sgst_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="totals-line grand">
                        <span>Grand Total</span>
                        <span>₹{{ number_format($quotation->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($quotation->notes)
            <div style="margin-top:20px;padding-top:18px;border-top:1px solid #f0f0f0">
                <div style="font-size:11px;font-weight:600;color:#9e9e9e;margin-bottom:6px">NOTES</div>
                <p style="font-size:13px;color:#444;line-height:1.6;margin:0">{{ $quotation->notes }}</p>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
