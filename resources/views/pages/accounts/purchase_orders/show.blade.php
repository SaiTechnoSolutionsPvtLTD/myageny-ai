@extends('layouts.app')

@section('title', 'PO #' . $purchaseOrder->po_number . ' - Purchase Order Details')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.show-layout { display:grid; grid-template-columns:1fr 320px; gap:22px; align-items:start; }
@media (max-width: 1024px) {
    .show-layout { grid-template-columns:1fr; }
}

.po-sheet { background:#fff; border-radius:18px; border:1px solid #e2e8f0; box-shadow:0 4px 16px rgba(0,0,0,.03); padding:32px; }
.po-header-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; padding-bottom:24px; border-bottom:1px solid #f1f5f9; margin-bottom:24px; }

.info-label { font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:4px; }
.info-val { font-size:14px; color:#0f172a; font-weight:700; }
.info-val-sub { font-size:12px; color:#64748b; margin-top:2px; line-height:1.5; }

.po-items-table { width:100%; border-collapse:collapse; margin-top:16px; }
.po-items-table th { background:#f8fafc; padding:12px 14px; font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.04em; border-bottom:2px solid #e2e8f0; text-align:left; }
.po-items-table td { padding:14px; border-bottom:1px solid #f1f5f9; vertical-align:top; font-size:13px; color:#1e293b; }

.po-totals-grid { display:flex; justify-content:flex-end; margin-top:24px; }
.po-totals-box { width:340px; max-width:100%; display:flex; flex-direction:column; gap:10px; background:#fafbfc; border-radius:12px; padding:18px 20px; border:1px solid #e2e8f0; }
.po-tot-row { display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#475569; font-weight:600; }
.po-tot-row.grand { border-top:2px solid #e2e8f0; padding-top:10px; margin-top:4px; font-size:16px; font-weight:900; color:#fe5f04; }

.side-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.02); margin-bottom:18px; }
.side-card-title { font-size:13px; font-weight:800; color:#0f172a; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-cart-check-fill" style="color:#fe5f04;"></i>
                <span>Purchase Order #{{ $purchaseOrder->po_number }}</span>
                {!! $purchaseOrder->status_badge !!}
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}"><i class="bi bi-house-door"></i> Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.purchase-orders.index') }}">Purchase Orders</a>
                <span>/</span>
                <span>#{{ $purchaseOrder->po_number }}</span>
            </div>
        </div>

        <div class="inv-actions">
            <a href="{{ route('accounts.purchase-orders.print', $purchaseOrder->id) }}" target="_blank" class="acm-btn acm-btn-primary">
                <i class="bi bi-printer"></i>
                <span>Print PO</span>
            </a>
            <a href="{{ route('accounts.purchase-orders.edit', $purchaseOrder->id) }}" class="acm-btn acm-btn-outline">
                <i class="bi bi-pencil-square"></i>
                <span>Edit</span>
            </a>
            <form method="POST" action="{{ route('accounts.purchase-orders.destroy', $purchaseOrder->id) }}" onsubmit="return confirm('Are you sure you want to delete Purchase Order #{{ $purchaseOrder->po_number }}?')" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="acm-btn acm-btn-danger" title="Delete PO">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>

    <div class="inv-body">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @php
            $logoUrl = $setting?->logo_url ?: asset('images/LOGO_STS.png');
        @endphp

        <div class="show-layout">

            {{-- Main PO Sheet --}}
            <div class="po-sheet">

                {{-- Header with Company & PO Info --}}
                <div class="po-header-grid">
                    <div style="display:flex; align-items:center; gap:16px;">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Company Logo" style="height:60px; max-width:130px; object-fit:contain;">
                        @endif
                        <div>
                            <div style="font-size:18px; font-weight:800; color:#0f172a; line-height:1.2; margin-bottom:4px;">
                                {{ $setting?->company_name ?: 'Sai Techno Solutions Private Limited' }}
                            </div>
                            <div class="info-val-sub">
                                @if($setting?->company_address)
                                    {{ $setting->company_address }}<br>
                                @endif
                                @php
                                    $cityStatePin = [];
                                    if ($setting?->company_city) $cityStatePin[] = $setting->company_city;
                                    if ($setting?->company_pincode) {
                                        if (!empty($cityStatePin)) {
                                            $cityStatePin[count($cityStatePin)-1] .= ' ' . $setting->company_pincode;
                                        } else {
                                            $cityStatePin[] = $setting->company_pincode;
                                        }
                                    }
                                    if ($setting?->company_state) $cityStatePin[] = $setting->company_state;
                                @endphp
                                @if(!empty($cityStatePin))
                                    {{ implode(', ', $cityStatePin) }}<br>
                                @endif
                                @if($setting?->company_gstin)
                                    <strong>GSTIN:</strong> {{ $setting->company_gstin }}<br>
                                @endif
                                @if($setting?->company_phone || $setting?->company_email)
                                    {{ $setting->company_phone }} {{ $setting->company_email ? '• ' . $setting->company_email : '' }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <div style="font-size:20px; font-weight:900; color:#fe5f04; text-transform:uppercase; letter-spacing:0.04em;">PURCHASE ORDER</div>
                        <div style="font-size:16px; font-weight:800; color:#0f172a; margin-top:4px; font-family:'JetBrains Mono',monospace;">#{{ $purchaseOrder->po_number }}</div>

                        <div style="margin-top:12px; font-size:13px; color:#475569;">
                            <div><strong>PO Date:</strong> {{ $purchaseOrder->po_date?->format('d M Y') }}</div>
                            @if($purchaseOrder->delivery_date)
                                <div><strong>Delivery Due:</strong> {{ $purchaseOrder->delivery_date->format('d M Y') }}</div>
                            @endif
                            @if($purchaseOrder->payment_terms)
                                <div><strong>Payment Terms:</strong> {{ $purchaseOrder->payment_terms }}</div>
                            @endif
                            @if($purchaseOrder->place_of_supply)
                                <div><strong>Place of Supply:</strong> {{ $purchaseOrder->place_of_supply }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Supplier & Shipping Addresses --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; padding-bottom:24px; border-bottom:1px solid #f1f5f9; margin-bottom:24px;">
                    <div>
                        <div class="info-label">Vendor / Supplier Details</div>
                        <div class="info-val">{{ $purchaseOrder->supplier_name }}</div>
                        @if($purchaseOrder->company_name)
                            <div style="font-weight:700; color:#475569; font-size:13px;">{{ $purchaseOrder->company_name }}</div>
                        @endif
                        <div class="info-val-sub">
                            @if($purchaseOrder->supplier_address)
                                {{ $purchaseOrder->supplier_address }}<br>
                            @endif
                            @if($purchaseOrder->supplier_city || $purchaseOrder->supplier_state)
                                {{ $purchaseOrder->supplier_city }}, {{ $purchaseOrder->supplier_state }} {{ $purchaseOrder->supplier_pincode ? '- ' . $purchaseOrder->supplier_pincode : '' }}<br>
                            @endif
                            @if($purchaseOrder->supplier_gstin)
                                <strong>GSTIN:</strong> {{ $purchaseOrder->supplier_gstin }}<br>
                            @endif
                            @if($purchaseOrder->supplier_pan)
                                <strong>PAN:</strong> {{ $purchaseOrder->supplier_pan }}<br>
                            @endif
                            @if($purchaseOrder->supplier_phone || $purchaseOrder->supplier_email)
                                {{ $purchaseOrder->supplier_phone }} {{ $purchaseOrder->supplier_email ? '• ' . $purchaseOrder->supplier_email : '' }}
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="info-label">Delivery / Shipping Destination</div>
                        <div class="info-val">{{ $purchaseOrder->shipping_attention ?: ($setting?->company_name ?: config('app.name')) }}</div>
                        <div class="info-val-sub">
                            @if($purchaseOrder->shipping_address)
                                {{ $purchaseOrder->shipping_address }}<br>
                            @endif
                            @if($purchaseOrder->shipping_city || $purchaseOrder->shipping_state)
                                {{ $purchaseOrder->shipping_city }}, {{ $purchaseOrder->shipping_state }} {{ $purchaseOrder->shipping_pincode ? '- ' . $purchaseOrder->shipping_pincode : '' }}<br>
                            @endif
                            @if($purchaseOrder->delivery_terms)
                                <strong>Terms:</strong> {{ $purchaseOrder->delivery_terms }}
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="info-label">Order Items &amp; Services</div>
                <div style="overflow-x:auto;">
                    <table class="po-items-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Item Description</th>
                                <th style="width:110px;">HSN / SAC</th>
                                <th style="width:90px; text-align:right;">Qty</th>
                                <th style="width:120px; text-align:right;">Rate (₹)</th>
                                <th style="width:140px; text-align:right;">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->items as $i => $item)
                            <tr>
                                <td style="font-weight:700; color:#64748b;">{{ $i + 1 }}</td>
                                <td>
                                    <div style="font-weight:800; color:#0f172a;">{{ $item->item_name }}</div>
                                    @if($item->description)
                                        <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $item->description }}</div>
                                    @endif
                                </td>
                                <td><span style="font-family:'JetBrains Mono',monospace; font-size:12px; color:#475569;">{{ $item->hsn_sac_code ?: '-' }}</span></td>
                                <td style="text-align:right; font-weight:700;">{{ number_format((float) $item->qty, 2) }}</td>
                                <td style="text-align:right; font-weight:700;">₹{{ number_format((float) $item->rate, 2) }}</td>
                                <td style="text-align:right; font-weight:800; color:#0f172a;">₹{{ number_format((float) $item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Financial Summary --}}
                <div class="po-totals-grid">
                    <div class="po-totals-box">
                        <div class="po-tot-row">
                            <span>Sub Total:</span>
                            <span style="font-weight:800; color:#0f172a;">₹{{ number_format((float) $purchaseOrder->subtotal, 2) }}</span>
                        </div>
                        @if($purchaseOrder->total_cgst > 0)
                            <div class="po-tot-row">
                                <span>CGST:</span>
                                <span>₹{{ number_format((float) $purchaseOrder->total_cgst, 2) }}</span>
                            </div>
                        @endif
                        @if($purchaseOrder->total_sgst > 0)
                            <div class="po-tot-row">
                                <span>SGST:</span>
                                <span>₹{{ number_format((float) $purchaseOrder->total_sgst, 2) }}</span>
                            </div>
                        @endif
                        @if($purchaseOrder->total_igst > 0)
                            <div class="po-tot-row">
                                <span>IGST:</span>
                                <span>₹{{ number_format((float) $purchaseOrder->total_igst, 2) }}</span>
                            </div>
                        @endif
                        @if((float) $purchaseOrder->round_off != 0)
                            <div class="po-tot-row">
                                <span>Round Off:</span>
                                <span>{{ $purchaseOrder->round_off > 0 ? '+' : '' }}₹{{ number_format((float) $purchaseOrder->round_off, 2) }}</span>
                            </div>
                        @endif
                        <div class="po-tot-row grand">
                            <span>Total Amount:</span>
                            <span>₹{{ number_format((float) $purchaseOrder->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Terms & Notes --}}
                @if($purchaseOrder->terms_conditions)
                    <div style="margin-top:28px; padding-top:20px; border-top:1px solid #f1f5f9;">
                        <div class="info-label">Terms &amp; Conditions</div>
                        <div style="font-size:12px; color:#475569; line-height:1.6;">
                            {!! $purchaseOrder->terms_conditions !!}
                        </div>
                    </div>
                @endif

                @if($purchaseOrder->notes)
                    <div style="margin-top:18px; padding:14px; background:#fffbeb; border:1px solid #fef3c7; border-radius:10px;">
                        <div class="info-label" style="color:#b45309;">Special Notes</div>
                        <div style="font-size:13px; color:#78350f;">{{ $purchaseOrder->notes }}</div>
                    </div>
                @endif

            </div>

            {{-- Sidebar Cards --}}
            <div>

                {{-- Status Change Card --}}
                <div class="side-card">
                    <div class="side-card-title">
                        <i class="bi bi-flag-fill" style="color:#fe5f04;"></i>
                        <span>Order Status</span>
                    </div>

                    <form method="POST" action="{{ route('accounts.purchase-orders.update-status', $purchaseOrder->id) }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group" style="margin-bottom:12px;">
                            <select name="status" class="form-input" style="font-weight:700;">
                                <option value="draft" {{ $purchaseOrder->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="issued" {{ $purchaseOrder->status === 'issued' ? 'selected' : '' }}>Issued (Sent to Supplier)</option>
                                <option value="approved" {{ $purchaseOrder->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="received" {{ $purchaseOrder->status === 'received' ? 'selected' : '' }}>Received (Goods / Services)</option>
                                <option value="cancelled" {{ $purchaseOrder->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" class="acm-btn acm-btn-secondary" style="width:100%; justify-content:center;">
                            <i class="bi bi-arrow-repeat"></i> Update Status
                        </button>
                    </form>
                </div>

                {{-- Actions Card --}}
                <div class="side-card">
                    <div class="side-card-title">
                        <i class="bi bi-lightning-charge-fill" style="color:#eab308;"></i>
                        <span>Quick Actions</span>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <a href="{{ route('accounts.purchase-orders.print', $purchaseOrder->id) }}" target="_blank" class="acm-btn acm-btn-primary" style="justify-content:center;">
                            <i class="bi bi-printer"></i> Print Purchase Order
                        </a>
                        <a href="{{ route('accounts.purchase-orders.edit', $purchaseOrder->id) }}" class="acm-btn acm-btn-outline" style="justify-content:center;">
                            <i class="bi bi-pencil-square"></i> Edit Purchase Order
                        </a>
                        <a href="{{ route('accounts.purchase-orders.create') }}" class="acm-btn acm-btn-secondary" style="justify-content:center;">
                            <i class="bi bi-plus-lg"></i> Create Another PO
                        </a>
                    </div>
                </div>

                {{-- Order Meta Info Card --}}
                <div class="side-card">
                    <div class="side-card-title">
                        <i class="bi bi-info-circle-fill" style="color:#64748b;"></i>
                        <span>Audit Information</span>
                    </div>

                    <div style="font-size:12px; color:#475569; display:flex; flex-direction:column; gap:8px;">
                        <div>
                            <strong>Created By:</strong> {{ $purchaseOrder->creator?->name ?: 'System Admin' }}
                        </div>
                        <div>
                            <strong>Created At:</strong> {{ $purchaseOrder->created_at?->format('d M Y, h:i A') }}
                        </div>
                        @if($purchaseOrder->updater)
                            <div>
                                <strong>Updated By:</strong> {{ $purchaseOrder->updater->name }}
                            </div>
                        @endif
                        @if($purchaseOrder->updated_at)
                            <div>
                                <strong>Last Updated:</strong> {{ $purchaseOrder->updated_at->format('d M Y, h:i A') }}
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection
