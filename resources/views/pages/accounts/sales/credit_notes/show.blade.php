@extends('layouts.app')

@section('title', 'Credit Note #' . $creditNote->credit_note_number . ' - Accounts Sales')

@push('styles')
<style>
.cn-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.cn-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.cn-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.cn-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.cn-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.cn-breadcrumbs a:hover { color:#fe5f04; }
.cn-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.cn-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; max-width:960px; margin:0 auto; width:100%; }
@media (max-width: 900px) {
    .cn-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .cn-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

.cn-sheet {
    background: #ffffff;
    border: 1px solid #000000;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
    position: relative;
    font-size: 11px;
    line-height: 1.45;
    color: #000000;
}
.cn-sheet-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 18px 20px 14px;
    border-bottom: 1px solid #000000;
}
.cn-badge-title {
    font-size: 20px;
    font-weight: 900;
    letter-spacing: 0.08em;
    color: #b91c1c;
    text-transform: uppercase;
}
</style>
@endpush

@section('content')
<div class="cn-page">

    {{-- Topbar --}}
    <div class="cn-topbar">
        <div>
            <div class="cn-title">
                <i class="bi bi-file-earmark-minus" style="color:#dc2626;"></i>
                <span>Credit Note #{{ $creditNote->credit_note_number }}</span>
                @if($creditNote->status === 'void')
                    <span style="font-size:11px; background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:2px 8px; border-radius:999px; font-weight:800;">VOID</span>
                @else
                    <span style="font-size:11px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px; font-weight:800;">ACTIVE</span>
                @endif
            </div>
            <div class="cn-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.invoices.index') }}">Sales</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.credit-notes.index') }}">Credit Notes</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">{{ $creditNote->credit_note_number }}</span>
            </div>
        </div>
        <div class="cn-actions">
            @if($creditNote->invoice)
                <a href="{{ route('accounts.sales.invoices.show', $creditNote->invoice_id) }}" class="acm-btn acm-btn-secondary" title="View Related Invoice">
                    <i class="bi bi-receipt"></i> View Invoice #{{ $creditNote->invoice->invoice_number }}
                </a>
            @endif
            <a href="{{ route('accounts.sales.credit-notes.print', $creditNote->id) }}" target="_blank" class="acm-btn acm-btn-primary">
                <i class="bi bi-printer"></i> Print / PDF
            </a>
            <form method="POST" action="{{ route('accounts.sales.credit-notes.void', $creditNote->id) }}" onsubmit="return confirm('Are you sure you want to {{ $creditNote->status === 'void' ? 'restore' : 'void' }} this Credit Note?')" style="margin:0; display:inline-block;">
                @csrf
                <button type="submit" class="acm-btn acm-btn-outline" style="color:{{ $creditNote->status === 'void' ? '#16a34a' : '#ea580c' }};">
                    <i class="bi {{ $creditNote->status === 'void' ? 'bi-arrow-counterclockwise' : 'bi-slash-circle' }}"></i>
                    <span>{{ $creditNote->status === 'void' ? 'Restore Note' : 'Void Note' }}</span>
                </button>
            </form>
            <form method="POST" action="{{ route('accounts.sales.credit-notes.destroy', $creditNote->id) }}" onsubmit="return confirm('Permanently delete this Credit Note? The invoice balance will be adjusted back.')" style="margin:0; display:inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="acm-btn acm-btn-outline" style="color:#dc2626;">
                    <i class="bi bi-trash3"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <div class="cn-body">

        {{-- Flash Alerts --}}
        @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- A4 Printable Sheet Preview --}}
        <div class="cn-sheet">
            {{-- Header --}}
            <div class="cn-sheet-header">
                <div>
                    @if($setting?->logo_url)
                        <img src="{{ $setting->logo_url }}" alt="Company Logo" style="max-height:55px; max-width:180px; object-fit:contain; margin-bottom:8px;">
                    @endif
                    <div style="font-size:15px; font-weight:800; color:#000;">
                        {{ $setting?->company_name ?: 'Sai Techno Solutions Private Limited' }}
                    </div>
                    <div style="font-size:11px; color:#333; max-width:380px; line-height:1.4;">
                        {{ $setting?->full_address ?: 'Plot No 8, Sai Nagar, Thoraipakkam, Chennai - 600097' }}
                    </div>
                    @if($setting?->company_gstin)
                        <div style="font-size:11px; font-weight:700; color:#000; margin-top:3px;">
                            GSTIN: {{ $setting->company_gstin }}
                        </div>
                    @endif
                </div>

                <div style="text-align:right;">
                    <div class="cn-badge-title">CREDIT NOTE</div>
                    <div style="font-size:11px; color:#555; margin-top:2px;">(Original for Recipient)</div>

                    <table style="margin-top:10px; font-size:11.5px; border-collapse:collapse; margin-left:auto;">
                        <tr>
                            <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Credit Note #</td>
                            <td style="font-weight:700; padding:2px 4px;">:</td>
                            <td style="font-weight:800; font-family:monospace; color:#b91c1c; text-align:left;">{{ $creditNote->credit_note_number }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Credit Note Date</td>
                            <td style="font-weight:700; padding:2px 4px;">:</td>
                            <td style="font-weight:700; text-align:left;">{{ $creditNote->credit_note_date ? $creditNote->credit_note_date->format('d/m/Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Against Invoice #</td>
                            <td style="font-weight:700; padding:2px 4px;">:</td>
                            <td style="font-weight:800; font-family:monospace; color:#0f172a; text-align:left;">{{ $creditNote->invoice?->invoice_number ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Original Inv. Date</td>
                            <td style="font-weight:700; padding:2px 4px;">:</td>
                            <td style="text-align:left;">{{ $creditNote->invoice?->invoice_date ? $creditNote->invoice->invoice_date->format('d/m/Y') : '—' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Client & Reason Banner --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; border-bottom:1px solid #000;">
                <div style="padding:12px 18px; border-right:1px solid #000;">
                    <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#555; margin-bottom:4px; letter-spacing:0.04em;">Customer Details (Credited To)</div>
                    <div style="font-size:13px; font-weight:800; color:#000;">{{ $creditNote->customer?->customer_name ?: ($creditNote->invoice?->customer_name ?: 'Valued Customer') }}</div>
                    @if($creditNote->customer?->company_name)
                        <div style="font-size:11.5px; font-weight:700; color:#333;">{{ $creditNote->customer->company_name }}</div>
                    @endif
                    @if($creditNote->invoice?->billing_address)
                        <div style="font-size:11px; color:#444; margin-top:2px;">{{ $creditNote->invoice->billing_address }}</div>
                    @endif
                    @if($creditNote->invoice?->billing_gstin || $creditNote->customer?->gst_number)
                        <div style="font-size:11px; font-weight:700; color:#000; margin-top:2px;">
                            GSTIN: {{ $creditNote->invoice?->billing_gstin ?: $creditNote->customer?->gst_number }}
                        </div>
                    @endif
                </div>

                <div style="padding:12px 18px; background:#fafafa;">
                    <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#555; margin-bottom:4px; letter-spacing:0.04em;">Reason for Issue & Reference</div>
                    <div style="font-size:12.5px; font-weight:800; color:#b91c1c;">
                        {{ \App\Models\CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason }}
                    </div>
                    @if($creditNote->notes)
                        <div style="font-size:11px; color:#444; margin-top:4px;">
                            <strong>Remarks:</strong> {{ $creditNote->notes }}
                        </div>
                    @endif
                    @if($creditNote->attachment_url)
                        <div style="margin-top:8px;">
                            <a href="{{ $creditNote->attachment_url }}" target="_blank" style="display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; color:#2563eb; text-decoration:none; background:#eff6ff; border:1px solid #bfdbfe; padding:2px 8px; border-radius:4px;">
                                <i class="bi bi-paperclip"></i> View Proof Attachment
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Line Items Table --}}
            <table style="width:100%; border-collapse:collapse; font-size:11px;">
                <thead>
                    <tr style="background:#f3f4f6; border-bottom:1px solid #000; text-align:left;">
                        <th style="padding:8px 12px; width:5%; text-align:center; border-right:1px solid #000;">#</th>
                        <th style="padding:8px 12px; width:55%; border-right:1px solid #000;">Description of Service / Adjustment</th>
                        <th style="padding:8px 12px; width:15%; text-align:center; border-right:1px solid #000;">Original Inv. Total</th>
                        <th style="padding:8px 12px; width:25%; text-align:right;">Credit Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @if($creditNote->items->count() > 0)
                        @foreach($creditNote->items as $idx => $ci)
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <td style="padding:10px 12px; text-align:center; border-right:1px solid #000;">{{ $idx + 1 }}</td>
                                <td style="padding:10px 12px; border-right:1px solid #000;">
                                    <div style="font-weight:700; color:#000;">{{ $ci->item_name }}</div>
                                    @if($ci->description)
                                        <div style="font-size:10.5px; color:#555;">{{ $ci->description }}</div>
                                    @endif
                                </td>
                                <td style="padding:10px 12px; text-align:center; border-right:1px solid #000; color:#555;">
                                    ₹{{ number_format((float) ($creditNote->invoice?->total_amount ?? 0), 2) }}
                                </td>
                                <td style="padding:10px 12px; text-align:right; font-weight:700; color:#b91c1c;">
                                    ₹{{ number_format((float) $ci->total_amount, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr style="border-bottom:1px solid #000;">
                            <td style="padding:16px 12px; text-align:center; border-right:1px solid #000;">1</td>
                            <td style="padding:16px 12px; border-right:1px solid #000;">
                                <div style="font-weight:700; color:#000;">
                                    Credit adjustment against Invoice #{{ $creditNote->invoice?->invoice_number }}
                                </div>
                                <div style="font-size:10.5px; color:#555; margin-top:2px;">
                                    Reason: {{ \App\Models\CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason }}
                                    @if($creditNote->notes) • {{ $creditNote->notes }} @endif
                                </div>
                            </td>
                            <td style="padding:16px 12px; text-align:center; border-right:1px solid #000; color:#555;">
                                ₹{{ number_format((float) ($creditNote->invoice?->total_amount ?? 0), 2) }}
                            </td>
                            <td style="padding:16px 12px; text-align:right; font-weight:800; font-size:12px; color:#b91c1c;">
                                ₹{{ number_format((float) $creditNote->total_amount, 2) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Summary & Signatory Section --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; border-top:1px solid #000;">
                <div style="padding:16px 18px; border-right:1px solid #000; display:flex; flex-direction:column; justify-content:space-between;">
                    <div>
                        <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#555;">Declaration</div>
                        <div style="font-size:10.5px; color:#333; margin-top:4px; line-height:1.45;">
                            This credit note is issued under Section 34 of the CGST/SGST Act, 2017. The tax liability of the supplier and input tax credit of the recipient shall be adjusted accordingly.
                        </div>
                    </div>
                    <div style="font-size:10.5px; color:#666; margin-top:20px;">
                        Created by: <strong>{{ $creditNote->creator?->name ?? 'System' }}</strong> on {{ $creditNote->created_at?->format('d/m/Y H:i') }}
                    </div>
                </div>

                <div style="padding:16px 18px;">
                    <table style="width:100%; font-size:11.5px; border-collapse:collapse;">
                        @if($creditNote->subtotal > 0 && $creditNote->total_tax > 0)
                            <tr>
                                <td style="padding:3px 0; color:#555;">Credited Subtotal</td>
                                <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->subtotal, 2) }}</td>
                            </tr>
                            @if($creditNote->cgst_amount > 0)
                                <tr>
                                    <td style="padding:3px 0; color:#555;">CGST Adjustment</td>
                                    <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->cgst_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($creditNote->sgst_amount > 0)
                                <tr>
                                    <td style="padding:3px 0; color:#555;">SGST Adjustment</td>
                                    <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->sgst_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($creditNote->igst_amount > 0)
                                <tr>
                                    <td style="padding:3px 0; color:#555;">IGST Adjustment</td>
                                    <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->igst_amount, 2) }}</td>
                                </tr>
                            @endif
                        @endif
                        <tr style="border-top:1px solid #000; border-bottom:1px solid #000;">
                            <td style="padding:8px 0; font-size:13px; font-weight:800; color:#000;">Total Credited Value</td>
                            <td style="padding:8px 0; font-size:15px; font-weight:900; text-align:right; color:#b91c1c;">₹{{ number_format((float) $creditNote->total_amount, 2) }}</td>
                        </tr>
                    </table>

                    <div style="margin-top:24px; text-align:right;">
                        <div style="font-size:10.5px; font-weight:700; color:#000;">{{ $setting?->signatory_name ?: ('FOR ' . strtoupper($setting?->company_name ?: 'Sai Techno Solutions Private Limited')) }}</div>
                        @if($setting?->signature_url)
                            <img src="{{ $setting->signature_url }}" alt="Signature" style="max-height:45px; margin:6px 0;">
                        @else
                            <div style="height:45px;"></div>
                        @endif
                        <div style="font-size:10px; color:#555;">Authorized Signatory</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
