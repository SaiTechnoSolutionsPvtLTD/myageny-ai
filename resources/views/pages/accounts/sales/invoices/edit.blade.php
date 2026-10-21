@extends('layouts.app')

@section('title', 'Edit Invoice #' . $invoice->invoice_number . ' - Accounts Sales')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .inv-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .inv-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.acm-btn[disabled], .acm-btn.disabled { opacity:0.65 !important; cursor:not-allowed !important; pointer-events:none !important; transform:none !important; box-shadow:none !important; }
@keyframes acmSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.acm-spin { display:inline-block; animation:acmSpin .8s linear infinite; }

.inv-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,.02); overflow:hidden; }
.inv-card-header { padding:18px 24px; border-bottom:1px solid #f1f5f9; background:#fff; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.inv-card-title { font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.inv-card-sub { font-size:12px; color:#64748b; margin-top:2px; }
.inv-card-body { padding:24px; display:flex; flex-direction:column; gap:22px; }

.form-grid-3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; }
.form-grid-4 { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
@media (max-width: 900px) {
    .form-grid-3, .form-grid-4, .form-grid-2 { grid-template-columns:1fr; }
}

.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; box-sizing:border-box; width:100%; transition:border-color .15s, box-shadow .15s; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

.address-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; }
@media (max-width: 768px) { .address-grid { grid-template-columns:1fr; } }
.address-col-header { display:flex; align-items:center; justify-content:space-between; font-size:13px; font-weight:800; color:#0f172a; margin-bottom:12px; }
.copy-btn { background:none; border:none; color:#2563eb; font-size:12px; font-weight:700; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:4px; }
.copy-btn:hover { text-decoration:underline; }

.items-table { width:100%; border-collapse:collapse; margin-top:8px; }
.items-table th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:10px 12px; border-bottom:2px solid #e2e8f0; background:#f8fafc; text-align:left; }
.items-table td { padding:10px 12px; vertical-align:top; border-bottom:1px solid #f1f5f9; }

.summary-container { display:flex; justify-content:flex-end; margin-top:14px; }
.summary-box { width:100%; max-width:440px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:12px; }
.summary-row { display:flex; align-items:center; justify-content:space-between; font-size:13px; color:#475569; }
.summary-row.total-row { font-size:16px; font-weight:800; color:#0f172a; border-top:2px solid #e2e8f0; padding-top:10px; margin-top:4px; }
.summary-row.balance-row { font-size:18px; font-weight:900; color:#dc2626; border-top:1px dashed #cbd5e1; padding-top:10px; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-pencil-square" style="color:#fe5f04;"></i>
                <span>Edit Invoice #{{ $invoice->invoice_number }}</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.invoices.index') }}">Sales</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Edit #{{ $invoice->invoice_number }}</span>
            </div>
        </div>
        <div class="inv-actions">
            <a href="{{ route('accounts.sales.invoices.show', $invoice->id) }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> View Invoice
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="submitInvoiceForm()">
                <i class="bi bi-check-lg"></i> Update Invoice
            </button>
        </div>
    </div>

    <div class="inv-body">

    {{-- Alerts --}}
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✕ {{ session('error') }}
        </div>
    @endif

    {{-- Edit Form --}}
    <form method="POST" action="{{ route('accounts.sales.invoices.update', $invoice->id) }}" id="invoiceForm">
        @csrf
        @method('PUT')

        <div style="display:flex; flex-direction:column; gap:22px;">

            {{-- Section 1: Customer & Invoice Meta Details --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div class="inv-card-title">
                        <i class="bi bi-person-badge" style="color:#2563eb;"></i>
                        <span>Customer & Invoice Information</span>
                    </div>
                </div>
                <div class="inv-card-body">

                    <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:16px 20px;">
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label class="form-label" style="color:#9a3412;">
                                    <i class="bi bi-person-check-fill"></i> Select Customer (Customer Master)
                                </label>
                                <select name="customer_id" id="customerSelect" class="form-input" style="border-color:#fdba74;" onchange="onCustomerSelected(this.value)">
                                    <option value="">-- Choose Existing Customer --</option>
                                    @foreach($customers as $cust)
                                        <option value="{{ $cust->id }}" {{ old('customer_id', $invoice->customer_id) == $cust->id ? 'selected' : '' }}>
                                            {{ $cust->customer_name }} {{ $cust->company_name ? "({$cust->company_name})" : '' }} {{ $cust->mobile_number ? "- {$cust->mobile_number}" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Customer Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="customer_name" id="customerName" class="form-input"
                                       value="{{ old('customer_name', $invoice->customer_name) }}" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="companyName" class="form-input"
                                       value="{{ old('company_name', $invoice->company_name) }}">
                            </div>
                        </div>

                        <div class="form-grid-4" style="margin-top:12px;">
                            <div class="form-group">
                                <label class="form-label">Phone / Mobile</label>
                                <input type="text" name="customer_phone" id="customerPhone" class="form-input"
                                       value="{{ old('customer_phone', $invoice->customer_phone) }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="customer_email" id="customerEmail" class="form-input"
                                       value="{{ old('customer_email', $invoice->customer_email) }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">GST Number (GSTIN)</label>
                                <input type="text" name="customer_gstin" id="customerGstin" class="form-input" style="text-transform:uppercase;"
                                       value="{{ old('customer_gstin', $invoice->customer_gstin) }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">PAN Number</label>
                                <input type="text" name="customer_pan" id="customerPan" class="form-input" style="text-transform:uppercase;"
                                       value="{{ old('customer_pan', $invoice->customer_pan) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-grid-4">
                        <div class="form-group">
                            <label class="form-label" style="display:flex; align-items:center; justify-content:space-between;">
                                <span>Invoice Number <span style="color:#dc2626;">*</span></span>
                                <span style="font-size:10.5px; font-weight:700; color:#64748b; background:#f1f5f9; padding:1px 7px; border-radius:4px;">
                                    <i class="bi bi-lock-fill"></i> Read-only
                                </span>
                            </label>
                            <input type="text" name="invoice_number" id="invoiceNumber" class="form-input"
                                   value="{{ old('invoice_number', $invoice->invoice_number) }}" readonly
                                   style="font-weight:700; background:#f1f5f9; cursor:not-allowed; color:#0f172a;" required>
                            <span style="font-size:11px; color:#64748b;">Invoice number cannot be changed</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Invoice Date <span style="color:#dc2626;">*</span></label>
                            <input type="date" name="invoice_date" id="invoiceDate" class="form-input"
                                   value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select name="payment_terms" id="paymentTerms" class="form-input">
                                <option value="Due on Receipt" {{ old('payment_terms', $invoice->payment_terms) === 'Due on Receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                <option value="Net 15" {{ old('payment_terms', $invoice->payment_terms) === 'Net 15' ? 'selected' : '' }}>Net 15</option>
                                <option value="Net 30" {{ old('payment_terms', $invoice->payment_terms) === 'Net 30' ? 'selected' : '' }}>Net 30</option>
                                <option value="Net 45" {{ old('payment_terms', $invoice->payment_terms) === 'Net 45' ? 'selected' : '' }}>Net 45</option>
                                <option value="Net 60" {{ old('payment_terms', $invoice->payment_terms) === 'Net 60' ? 'selected' : '' }}>Net 60</option>
                                <option value="Due End of Month" {{ old('payment_terms', $invoice->payment_terms) === 'Due End of Month' ? 'selected' : '' }}>Due End of Month</option>
                                <option value="Due End of Next Month" {{ old('payment_terms', $invoice->payment_terms) === 'Due End of Next Month' ? 'selected' : '' }}>Due End of Next Month</option>
                                <option value="Custom" {{ old('payment_terms', $invoice->payment_terms) === 'Custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="dueDate" class="form-input"
                                   value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Place of Supply <span style="color:#dc2626;">*</span></label>
                            <select name="place_of_supply" id="placeOfSupply" class="form-input" required onchange="onPlaceOfSupplyChanged()">
                                @foreach($states as $codeKey => $stateName)
                                    <option value="{{ $codeKey }}" {{ old('place_of_supply', $invoice->place_of_supply) === $codeKey ? 'selected' : '' }}>
                                        {{ $codeKey }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Sales Person</label>
                            <select name="sales_person_id" id="salesPersonId" class="form-input">
                                <option value="">-- Choose Sales / CST Rep --</option>
                                @foreach($salesUsers as $u)
                                    <option value="{{ $u->id }}" {{ old('sales_person_id', $invoice->sales_person_id) == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="draft" {{ old('status', $invoice->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ old('status', $invoice->status) === 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="partially_paid" {{ old('status', $invoice->status) === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                                <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="overdue" {{ old('status', $invoice->status) === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="cancelled" {{ old('status', $invoice->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    {{-- Addresses --}}
                    <div class="address-grid">
                        <div>
                            <div class="address-col-header">
                                <span><i class="bi bi-receipt" style="color:#2563eb;"></i> Billing Address (Bill To)</span>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:10px;">
                                <div class="form-group">
                                    <label class="form-label">Attention</label>
                                    <input type="text" name="billing_attention" id="billingAttention" class="form-input"
                                           value="{{ old('billing_attention', $invoice->billing_attention) }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GST Number (GSTIN)</label>
                                    <input type="text" name="billing_gstin" id="billingGstin" class="form-input" style="text-transform:uppercase;"
                                           value="{{ old('billing_gstin', $invoice->billing_gstin ?: $invoice->customer_gstin) }}" placeholder="e.g. 33AAAAA0000A1Z5">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Address</label>
                                    <textarea name="billing_address" id="billingAddress" class="form-input" rows="2">{{ old('billing_address', $invoice->billing_address) }}</textarea>
                                </div>
                                <div class="form-grid-3">
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" name="billing_city" id="billingCity" class="form-input"
                                               value="{{ old('billing_city', $invoice->billing_city) }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">State</label>
                                        <input type="text" name="billing_state" id="billingState" class="form-input"
                                               value="{{ old('billing_state', $invoice->billing_state) }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Pin Code</label>
                                        <input type="text" name="billing_pincode" id="billingPincode" class="form-input"
                                               value="{{ old('billing_pincode', $invoice->billing_pincode) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="address-col-header">
                                <span><i class="bi bi-truck" style="color:#059669;"></i> Shipping Address (Ship To)</span>
                                <button type="button" class="copy-btn" onclick="copyBillingToShipping()">
                                    <i class="bi bi-arrow-down-short"></i> Copy billing address
                                </button>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:10px;">
                                <div class="form-group">
                                    <label class="form-label">Attention</label>
                                    <input type="text" name="shipping_attention" id="shippingAttention" class="form-input"
                                           value="{{ old('shipping_attention', $invoice->shipping_attention) }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GST Number (GSTIN)</label>
                                    <input type="text" name="shipping_gstin" id="shippingGstin" class="form-input" style="text-transform:uppercase;"
                                           value="{{ old('shipping_gstin', $invoice->shipping_gstin ?: $invoice->customer_gstin) }}" placeholder="e.g. 33AAAAA0000A1Z5">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Address</label>
                                    <textarea name="shipping_address" id="shippingAddress" class="form-input" rows="2">{{ old('shipping_address', $invoice->shipping_address) }}</textarea>
                                </div>
                                <div class="form-grid-3">
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" name="shipping_city" id="shippingCity" class="form-input"
                                               value="{{ old('shipping_city', $invoice->shipping_city) }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">State</label>
                                        <input type="text" name="shipping_state" id="shippingState" class="form-input"
                                               value="{{ old('shipping_state', $invoice->shipping_state) }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Pin Code</label>
                                        <input type="text" name="shipping_pincode" id="shippingPincode" class="form-input"
                                               value="{{ old('shipping_pincode', $invoice->shipping_pincode) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Section 2: Items Table --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div>
                        <div class="inv-card-title">
                            <i class="bi bi-box-seam" style="color:#fe5f04;"></i>
                            <span>Products / Line Items</span>
                        </div>
                        <div class="inv-card-sub">Edit quantities, rates, descriptions, and tax percentages.</div>
                    </div>
                    <button type="button" class="acm-btn acm-btn-outline" onclick="addInvoiceItemRow()">
                        <i class="bi bi-plus-lg"></i> Add Line Item
                    </button>
                </div>
                <div class="inv-card-body" style="padding-top:10px;">

                    <div style="overflow-x:auto;">
                        <table class="items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="min-width:240px;">Item / Service</th>
                                    <th style="min-width:280px;">Description (Editable)</th>
                                    <th style="width:130px;">HSN / SAC</th>
                                    <th style="width:100px; text-align:right;">Qty</th>
                                    <th style="width:130px; text-align:right;">Rate (₹)</th>
                                    <th style="width:140px; text-align:right;">Amount (₹)</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                {{-- Initialized via JS from existing items --}}
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:10px;">
                        <button type="button" class="acm-btn acm-btn-secondary" onclick="addInvoiceItemRow()">
                            <i class="bi bi-plus-circle"></i> Add Another Item
                        </button>
                    </div>

                    @php
                        $isTN = !empty($invoice->place_of_supply) ? (str_contains(strtolower($invoice->place_of_supply), 'tamil nadu') || str_contains($invoice->place_of_supply, '33')) : true;
                        $initialSubtotal = (float) $invoice->subtotal;
                        $initialCgstPct = 9.00;
                        $initialSgstPct = 9.00;
                        $initialIgstPct = 18.00;

                        if ($initialSubtotal > 0) {
                            if ($invoice->total_cgst > 0) {
                                $initialCgstPct = round(($invoice->total_cgst / $initialSubtotal) * 100, 2);
                            }
                            if ($invoice->total_sgst > 0) {
                                $initialSgstPct = round(($invoice->total_sgst / $initialSubtotal) * 100, 2);
                            }
                            if ($invoice->total_igst > 0) {
                                $initialIgstPct = round(($invoice->total_igst / $initialSubtotal) * 100, 2);
                            }
                        }
                    @endphp

                    {{-- Calculations Summary Box --}}
                    <div class="summary-container">
                        <div class="summary-box">
                            <div class="summary-row">
                                <span>Sub Total</span>
                                <span style="font-weight:700;" id="sumSubtotal">₹{{ number_format((float) $invoice->subtotal, 2) }}</span>
                                <input type="hidden" name="subtotal" id="inputSubtotal" value="{{ $invoice->subtotal }}">
                            </div>

                            {{-- Tax Section Header --}}
                            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; border-top:1px dashed #e2e8f0; padding-top:10px; display:flex; align-items:center; justify-content:space-between;">
                                <span id="taxTypeLabel"><i class="bi bi-geo-alt"></i> GST ({{ $isTN ? 'Intra-State: Tamil Nadu' : 'Inter-State: ' . $invoice->place_of_supply }})</span>
                                <span id="stateBadge" style="font-size:10px; padding:2px 8px; border-radius:999px; background:{{ $isTN ? '#e0f2fe' : '#fef3c7' }}; color:{{ $isTN ? '#0369a1' : '#b45309' }}; font-weight:700;">{{ $isTN ? 'CGST + SGST' : 'IGST' }}</span>
                            </div>

                            {{-- Tamil Nadu: CGST & SGST Rows (Editable Percentages) --}}
                            <div id="tnGstRows" style="display:{{ $isTN ? 'flex' : 'none' }}; flex-direction:column; gap:8px;">
                                <div class="summary-row" style="align-items:center;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span>CGST (</span>
                                        <input type="number" step="0.01" min="0" max="50" id="inputCgstPercent" class="form-input" style="width:70px; padding:4px 8px; font-size:12px; font-weight:700; text-align:right;" value="{{ number_format($initialCgstPct, 2, '.', '') }}" oninput="onGstPercentChange('cgst')">
                                        <span>%)</span>
                                    </div>
                                    <span style="font-weight:700;" id="sumCgst">₹{{ number_format((float) $invoice->total_cgst, 2) }}</span>
                                    <input type="hidden" name="total_cgst" id="inputTotalCgst" value="{{ $invoice->total_cgst }}">
                                </div>

                                <div class="summary-row" style="align-items:center;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span>SGST (</span>
                                        <input type="number" step="0.01" min="0" max="50" id="inputSgstPercent" class="form-input" style="width:70px; padding:4px 8px; font-size:12px; font-weight:700; text-align:right;" value="{{ number_format($initialSgstPct, 2, '.', '') }}" oninput="onGstPercentChange('sgst')">
                                        <span>%)</span>
                                    </div>
                                    <span style="font-weight:700;" id="sumSgst">₹{{ number_format((float) $invoice->total_sgst, 2) }}</span>
                                    <input type="hidden" name="total_sgst" id="inputTotalSgst" value="{{ $invoice->total_sgst }}">
                                </div>
                            </div>

                            {{-- Other States: IGST Row (Editable Percentage) --}}
                            <div id="interStateGstRow" style="display:{{ $isTN ? 'none' : 'flex' }}; flex-direction:column; gap:8px;">
                                <div class="summary-row" style="align-items:center;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span>IGST (</span>
                                        <input type="number" step="0.01" min="0" max="100" id="inputIgstPercent" class="form-input" style="width:70px; padding:4px 8px; font-size:12px; font-weight:700; text-align:right;" value="{{ number_format($initialIgstPct, 2, '.', '') }}" oninput="onGstPercentChange('igst')">
                                        <span>%)</span>
                                    </div>
                                    <span style="font-weight:700;" id="sumIgst">₹{{ number_format((float) $invoice->total_igst, 2) }}</span>
                                    <input type="hidden" name="total_igst" id="inputTotalIgst" value="{{ $invoice->total_igst }}">
                                </div>
                            </div>

                            <div class="summary-row" style="font-size:12px; color:#64748b;">
                                <span>Total Tax Amount</span>
                                <span style="font-weight:700;" id="sumTotalTax">₹{{ number_format((float) $invoice->total_tax, 2) }}</span>
                                <input type="hidden" name="total_tax" id="inputTotalTax" value="{{ $invoice->total_tax }}">
                            </div>

                            <div class="summary-row" style="align-items:center;">
                                <span>Round Off (+ / -)</span>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <input type="number" step="0.01" name="round_off" id="inputRoundOff" class="form-input" style="width:95px; padding:4px 8px; text-align:right; font-weight:700;" value="{{ $invoice->round_off }}" oninput="onRoundOffManualChange()">
                                </div>
                            </div>

                            <div class="summary-row total-row">
                                <span>Grand Total</span>
                                <span id="sumGrandTotal">₹{{ number_format((float) $invoice->total_amount, 2) }}</span>
                                <input type="hidden" name="total_amount" id="inputTotalAmount" value="{{ $invoice->total_amount }}">
                            </div>

                            <div class="summary-row" style="align-items:center; background:#fff; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0;">
                                <span style="font-weight:700; color:#15803d;">Payment Made (₹)</span>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="inputPaidAmount" class="form-input" style="width:130px; text-align:right; font-weight:800; color:#15803d;" value="{{ $invoice->paid_amount }}" oninput="calculateInvoiceTotals()">
                            </div>

                            <div class="summary-row balance-row">
                                <span>Balance Due</span>
                                <span id="sumBalanceDue">₹{{ number_format((float) $invoice->balance_due, 2) }}</span>
                                <input type="hidden" name="balance_due" id="inputBalanceDue" value="{{ $invoice->balance_due }}">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Section 3: Notes & Terms --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div class="inv-card-title">
                        <i class="bi bi-card-text" style="color:#7c3aed;"></i>
                        <span>Terms & Conditions and Customer Notes</span>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div class="form-group">
                            <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                                <span>Terms &amp; Conditions (Printed on Dedicated Page 2)</span>
                                <span style="font-size:11px; font-weight:700; color:#7c3aed; background:#f5f3ff; border:1px solid #ddd6fe; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                    <i class="bi bi-pencil-square"></i> Rich Text Editor
                                </span>
                            </label>
                            <textarea id="invoiceTermsEditor" name="terms_conditions" class="form-input" rows="6" style="font-size:13px; line-height:1.6;">{{ old('terms_conditions', $invoice->terms_conditions) }}</textarea>
                            <span style="font-size:11px; color:#94a3b8; margin-top:2px;">Appears automatically on the dedicated 2nd page of the invoice.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Customer Notes (Page 1)</label>
                            <textarea name="customer_notes" class="form-input" rows="3" style="font-size:12px; line-height:1.6;">{{ old('customer_notes', $invoice->customer_notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom Actions Bar --}}
            <div style="display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02);">
                <a href="{{ route('accounts.sales.invoices.show', $invoice->id) }}" class="acm-btn acm-btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
                <button type="submit" id="saveInvoiceBtn" class="acm-btn acm-btn-primary" style="padding:10px 24px; font-size:14px;">
                    <i class="bi bi-check-lg"></i> Update Invoice
                </button>
            </div>

        </div>
    </form>

    </div>{{-- /inv-body --}}
</div>{{-- /inv-page --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
const catalogItems = @json($items);
const initialItems = @json($invoice->items);
let isInvoiceSubmitting = false;

function submitInvoiceForm() {
    const form = document.getElementById('invoiceForm');
    if (form) {
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }
}

function onCustomerSelected(customerId) {
    if (!customerId) return;
    fetch("{{ url('accounts/sales/invoices/customer-details') }}/" + customerId)
        .then(res => res.json())
        .then(data => {
            if (!data) return;
            document.getElementById('customerName').value = data.customer_name || '';
            document.getElementById('companyName').value = data.company_name || '';
            document.getElementById('customerPhone').value = data.mobile_number || '';
            document.getElementById('customerEmail').value = data.email || '';
            document.getElementById('customerGstin').value = data.gst_number || '';
            document.getElementById('customerPan').value = data.pan_number || '';

            // Sales Person dynamic auto-select
            if (data.sales_person_id) {
                const spSelect = document.getElementById('salesPersonId');
                if (spSelect) {
                    let optFound = false;
                    for (let i = 0; i < spSelect.options.length; i++) {
                        if (spSelect.options[i].value == data.sales_person_id) {
                            spSelect.selectedIndex = i;
                            optFound = true;
                            break;
                        }
                    }
                    if (!optFound && data.sales_person_name) {
                        const newOpt = document.createElement('option');
                        newOpt.value = data.sales_person_id;
                        newOpt.textContent = data.sales_person_name + (data.sales_person_email ? ' (' + data.sales_person_email + ')' : '');
                        newOpt.selected = true;
                        spSelect.appendChild(newOpt);
                    }
                }
            }

            if (data.payment_terms) {
                document.getElementById('paymentTerms').value = data.payment_terms;
            }

            document.getElementById('billingAttention').value = data.billing_attention || data.customer_name || '';
            const bGstin = document.getElementById('billingGstin');
            if (bGstin) bGstin.value = data.gst_number || '';
            document.getElementById('billingAddress').value = data.billing_address || '';
            document.getElementById('billingCity').value = data.billing_city || '';
            document.getElementById('billingState').value = data.billing_state || '';
            document.getElementById('billingPincode').value = data.billing_pincode || '';

            document.getElementById('shippingAttention').value = data.shipping_attention || data.billing_attention || data.customer_name || '';
            const sGstin = document.getElementById('shippingGstin');
            if (sGstin) sGstin.value = data.gst_number || '';
            document.getElementById('shippingAddress').value = data.shipping_address || data.billing_address || '';
            document.getElementById('shippingCity').value = data.shipping_city || data.billing_city || '';
            document.getElementById('shippingState').value = data.shipping_state || data.billing_state || '';
            document.getElementById('shippingPincode').value = data.shipping_pincode || data.billing_pincode || '';
        });
}

function copyBillingToShipping() {
    document.getElementById('shippingAttention').value = document.getElementById('billingAttention').value;
    const bGstin = document.getElementById('billingGstin');
    const sGstin = document.getElementById('shippingGstin');
    if (bGstin && sGstin) sGstin.value = bGstin.value;
    document.getElementById('shippingAddress').value = document.getElementById('billingAddress').value;
    document.getElementById('shippingCity').value = document.getElementById('billingCity').value;
    document.getElementById('shippingState').value = document.getElementById('billingState').value;
    document.getElementById('shippingPincode').value = document.getElementById('billingPincode').value;
}

// Place of supply changed (Intra-state TN vs Inter-state IGST)
function onPlaceOfSupplyChanged() {
    calculateInvoiceTotals();
}

let hasUserManuallyEditedGstPercent = true; // In edit mode, preserve saved rates unless changed

function onGstPercentChange(type) {
    hasUserManuallyEditedGstPercent = true;
    if (type === 'cgst') {
        const cgstVal = parseFloat(document.getElementById('inputCgstPercent').value) || 0;
        document.getElementById('inputSgstPercent').value = cgstVal.toFixed(2);
        document.getElementById('inputIgstPercent').value = (cgstVal * 2).toFixed(2);
    } else if (type === 'sgst') {
        const cgstVal = parseFloat(document.getElementById('inputCgstPercent').value) || 0;
        const sgstVal = parseFloat(document.getElementById('inputSgstPercent').value) || 0;
        document.getElementById('inputIgstPercent').value = (cgstVal + sgstVal).toFixed(2);
    } else if (type === 'igst') {
        const igstVal = parseFloat(document.getElementById('inputIgstPercent').value) || 0;
        document.getElementById('inputCgstPercent').value = (igstVal / 2).toFixed(2);
        document.getElementById('inputSgstPercent').value = (igstVal / 2).toFixed(2);
    }
    calculateInvoiceTotals();
}

function isTamilNaduPlaceOfSupply() {
    const posElem = document.getElementById('placeOfSupply');
    if (!posElem) return true;
    const val = (posElem.value || '').trim().toLowerCase();
    if (!val) return true;
    return val.includes('tamil nadu') || val.includes('[33]') || val === '33';
}

let itemRowIndex = 0;

function initRowSelect2(rowIndex) {
    if (!window.jQuery) return;
    const $select = $(`#rowSelect_${rowIndex}`);
    if (!$select.length) return;

    if (typeof window.initSelect2 === 'function') {
        window.initSelect2($select);
    } else if ($.fn.select2) {
        $select.select2({
            width: '100%',
            allowClear: true,
            placeholder: $select.find('option[value=""]').first().text() || '-- Choose Item from Master --'
        });
    }

    $select.off('change.rowItemSelect').on('change.rowItemSelect', function() {
        onRowItemSelect(this, rowIndex);
    });
}

function addInvoiceItemRow(data = {}) {
    const tbody = document.getElementById('itemsTableBody');
    const tr = document.createElement('tr');
    const currentRowIndex = itemRowIndex;
    tr.id = 'itemRow_' + currentRowIndex;

    let itemsOptions = '<option value="">-- Choose Item from Master --</option>';
    catalogItems.forEach(it => {
        const isSelected = data.item_id && data.item_id == it.id ? 'selected' : '';
        itemsOptions += `<option value="${it.id}" ${isSelected}>${escapeHtml(it.item_name)} (₹${parseFloat(it.rate || 0).toFixed(2)})</option>`;
    });

    const itemName = data.item_name || '';
    const desc = data.description || '';
    const hsn = data.hsn_sac_code || '';
    const qty = data.qty || 1;
    const rate = data.rate !== undefined ? data.rate : 0;
    const amount = (qty * rate).toFixed(2);

    tr.innerHTML = `
        <td>
            <select name="items[${currentRowIndex}][item_id]" id="rowSelect_${currentRowIndex}" class="form-input select2" style="font-size:12px; padding:6px 8px; margin-bottom:4px;">
                ${itemsOptions}
            </select>
            <input type="text" name="items[${currentRowIndex}][item_name]" id="rowItemName_${currentRowIndex}" class="form-input" style="font-size:12px; font-weight:700; margin-top:6px;" value="${escapeHtml(itemName)}" required>
        </td>
        <td>
            <textarea name="items[${itemRowIndex}][description]" id="rowDesc_${itemRowIndex}" class="form-input" rows="2" style="font-size:11px; line-height:1.4;">${escapeHtml(desc)}</textarea>
        </td>
        <td>
            <input type="text" name="items[${itemRowIndex}][hsn_sac_code]" id="rowHsn_${itemRowIndex}" class="form-input" style="font-size:12px; padding:6px 8px;" value="${escapeHtml(hsn)}">
        </td>
        <td>
            <input type="number" step="any" min="0.01" name="items[${itemRowIndex}][qty]" id="rowQty_${itemRowIndex}" class="form-input" style="font-size:12px; padding:6px 8px; text-align:right;" value="${qty}" required oninput="calculateRow(${itemRowIndex})">
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="items[${itemRowIndex}][rate]" id="rowRate_${itemRowIndex}" class="form-input" style="font-size:12px; padding:6px 8px; text-align:right;" value="${rate}" required oninput="calculateRow(${itemRowIndex})">
        </td>
        <td style="text-align:right; font-weight:700; padding-top:16px;">
            <span id="rowAmountSpan_${itemRowIndex}">₹${parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            <input type="hidden" name="items[${itemRowIndex}][amount]" id="rowAmount_${itemRowIndex}" value="${amount}">
            <input type="hidden" name="items[${itemRowIndex}][total_amount]" id="rowTotal_${itemRowIndex}" value="${amount}">
            <input type="hidden" name="items[${itemRowIndex}][cgst_percent]" id="rowCgst_${itemRowIndex}" value="0.00">
            <input type="hidden" name="items[${itemRowIndex}][cgst_amount]" id="rowCgstAmt_${itemRowIndex}" value="0.00">
            <input type="hidden" name="items[${itemRowIndex}][sgst_percent]" id="rowSgst_${itemRowIndex}" value="0.00">
            <input type="hidden" name="items[${itemRowIndex}][sgst_amount]" id="rowSgstAmt_${itemRowIndex}" value="0.00">
            <input type="hidden" name="items[${itemRowIndex}][igst_percent]" id="rowIgst_${itemRowIndex}" value="0.00">
            <input type="hidden" name="items[${itemRowIndex}][igst_amount]" id="rowIgstAmt_${itemRowIndex}" value="0.00">
        </td>
        <td style="text-align:center; padding-top:12px;">
            <button type="button" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" onclick="removeInvoiceItemRow(${itemRowIndex})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);

    // Initialize Select2 on the new row's select
    initRowSelect2(currentRowIndex);

    calculateRow(currentRowIndex);
    itemRowIndex++;
}

function onRowItemSelect(selectElem, rowId) {
    const itemId = selectElem.value;
    if (!itemId) return;

    const found = catalogItems.find(it => it.id == itemId);
    if (found) {
        document.getElementById('rowItemName_' + rowId).value = found.item_name || '';
        document.getElementById('rowDesc_' + rowId).value = found.description || '';
        document.getElementById('rowHsn_' + rowId).value = found.hsn_sac_code || '';
        document.getElementById('rowRate_' + rowId).value = parseFloat(found.rate || 0).toFixed(2);

        calculateRow(rowId);
    }
}

function removeInvoiceItemRow(rowId) {
    const tr = document.getElementById('itemRow_' + rowId);
    if (tr) {
        if (window.jQuery) {
            const $s = $(tr).find('select.select2');
            if ($s.length && $s.data('select2')) {
                $s.select2('destroy');
            }
        }
        tr.remove();
        calculateInvoiceTotals();
    }
}

function calculateRow(rowId) {
    const qtyElem = document.getElementById('rowQty_' + rowId);
    const rateElem = document.getElementById('rowRate_' + rowId);
    if (!qtyElem || !rateElem) return;

    const qty = parseFloat(qtyElem.value) || 0;
    const rate = parseFloat(rateElem.value) || 0;
    const amount = qty * rate;

    document.getElementById('rowAmount_' + rowId).value = amount.toFixed(2);
    document.getElementById('rowAmountSpan_' + rowId).textContent = '₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('rowTotal_' + rowId).value = amount.toFixed(2);

    calculateInvoiceTotals();
}

let isManualRoundOff = false;
function onRoundOffManualChange() {
    isManualRoundOff = true;
    calculateInvoiceTotals();
}

function calculateInvoiceTotals() {
    let subtotal = 0;

    const rows = document.querySelectorAll('#itemsTableBody tr');
    rows.forEach(tr => {
        const id = tr.id.replace('itemRow_', '');
        const amtElem = document.getElementById('rowAmount_' + id);
        if (amtElem) {
            subtotal += parseFloat(amtElem.value) || 0;
        }
    });

    const isTN = isTamilNaduPlaceOfSupply();
    const tnGstRows = document.getElementById('tnGstRows');
    const interStateGstRow = document.getElementById('interStateGstRow');
    const taxTypeLabel = document.getElementById('taxTypeLabel');
    const stateBadge = document.getElementById('stateBadge');

    let totalCgst = 0;
    let totalSgst = 0;
    let totalIgst = 0;
    let totalTax = 0;

    const cgstInput = document.getElementById('inputCgstPercent');
    const sgstInput = document.getElementById('inputSgstPercent');
    const igstInput = document.getElementById('inputIgstPercent');

    if (isTN) {
        if (tnGstRows) tnGstRows.style.display = 'flex';
        if (interStateGstRow) interStateGstRow.style.display = 'none';

        if (taxTypeLabel) taxTypeLabel.innerHTML = '<i class="bi bi-geo-alt"></i> GST (Intra-State: Tamil Nadu)';
        if (stateBadge) {
            stateBadge.textContent = 'CGST + SGST';
            stateBadge.style.background = '#e0f2fe';
            stateBadge.style.color = '#0369a1';
        }

        const cgstPct = cgstInput ? (parseFloat(cgstInput.value) || 0) : 9;
        const sgstPct = sgstInput ? (parseFloat(sgstInput.value) || 0) : 9;

        totalCgst = (subtotal * cgstPct) / 100;
        totalSgst = (subtotal * sgstPct) / 100;
        totalIgst = 0;
        totalTax = totalCgst + totalSgst;
    } else {
        if (tnGstRows) tnGstRows.style.display = 'none';
        if (interStateGstRow) interStateGstRow.style.display = 'flex';

        const posElem = document.getElementById('placeOfSupply');
        const stateName = (posElem && posElem.selectedIndex >= 0 && posElem.options[posElem.selectedIndex].text)
            ? posElem.options[posElem.selectedIndex].text
            : (posElem?.value || 'Other State');

        if (taxTypeLabel) taxTypeLabel.innerHTML = '<i class="bi bi-geo-alt"></i> GST (Inter-State: ' + escapeHtml(stateName) + ')';
        if (stateBadge) {
            stateBadge.textContent = 'IGST';
            stateBadge.style.background = '#fef3c7';
            stateBadge.style.color = '#b45309';
        }

        const igstPct = igstInput ? (parseFloat(igstInput.value) || 0) : 18;

        totalIgst = (subtotal * igstPct) / 100;
        totalCgst = 0;
        totalSgst = 0;
        totalTax = totalIgst;
    }

    const rawTotal = subtotal + totalTax;

    let roundOff = 0;
    const roundOffInput = document.getElementById('inputRoundOff');
    if (!isManualRoundOff) {
        const rounded = Math.round(rawTotal);
        roundOff = rounded - rawTotal;
        if (roundOffInput) roundOffInput.value = roundOff.toFixed(2);
    } else {
        roundOff = parseFloat(roundOffInput ? roundOffInput.value : 0) || 0;
    }

    const grandTotal = rawTotal + roundOff;
    const paidAmount = parseFloat(document.getElementById('inputPaidAmount')?.value) || 0;
    const balanceDue = Math.max(0, grandTotal - paidAmount);

    // Update displays & hidden inputs
    const sumSubtotal = document.getElementById('sumSubtotal');
    if (sumSubtotal) sumSubtotal.textContent = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const inputSubtotal = document.getElementById('inputSubtotal');
    if (inputSubtotal) inputSubtotal.value = subtotal.toFixed(2);

    const sumCgst = document.getElementById('sumCgst');
    if (sumCgst) sumCgst.textContent = '₹' + totalCgst.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const inputTotalCgst = document.getElementById('inputTotalCgst');
    if (inputTotalCgst) inputTotalCgst.value = totalCgst.toFixed(2);

    const sumSgst = document.getElementById('sumSgst');
    if (sumSgst) sumSgst.textContent = '₹' + totalSgst.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const inputTotalSgst = document.getElementById('inputTotalSgst');
    if (inputTotalSgst) inputTotalSgst.value = totalSgst.toFixed(2);

    const sumIgst = document.getElementById('sumIgst');
    if (sumIgst) sumIgst.textContent = '₹' + totalIgst.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const inputTotalIgst = document.getElementById('inputTotalIgst');
    if (inputTotalIgst) inputTotalIgst.value = totalIgst.toFixed(2);

    const sumTotalTax = document.getElementById('sumTotalTax');
    if (sumTotalTax) sumTotalTax.textContent = '₹' + totalTax.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const inputTotalTax = document.getElementById('inputTotalTax');
    if (inputTotalTax) inputTotalTax.value = totalTax.toFixed(2);

    const sumGrandTotal = document.getElementById('sumGrandTotal');
    if (sumGrandTotal) sumGrandTotal.textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const inputTotalAmount = document.getElementById('inputTotalAmount');
    if (inputTotalAmount) inputTotalAmount.value = grandTotal.toFixed(2);

    const sumBalanceDue = document.getElementById('sumBalanceDue');
    if (sumBalanceDue) {
        sumBalanceDue.textContent = '₹' + balanceDue.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        sumBalanceDue.style.color = balanceDue === 0 ? '#15803d' : '#dc2626';
    }
    const inputBalanceDue = document.getElementById('inputBalanceDue');
    if (inputBalanceDue) inputBalanceDue.value = balanceDue.toFixed(2);
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize TinyMCE on Invoice Terms Editor
    if (window.tinymce && document.getElementById('invoiceTermsEditor')) {
        window.tinymce.init({
            selector: 'textarea#invoiceTermsEditor',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
            menubar: 'edit view insert format tools table',
            height: 280,
            plugins: 'lists link table code preview wordcount',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | removeformat code',
            content_style: 'body { font-family: Inter, sans-serif; font-size: 13px; line-height: 1.6; color: #1e293b; padding: 10px; } ol, ul { padding-left: 20px; } p { margin: 0 0 8px 0; }',
            setup: function (editor) {
                editor.on('change keyup NodeChange', function () {
                    window.tinymce.triggerSave();
                });
            },
        });
    }

    if (initialItems && initialItems.length) {
        initialItems.forEach(it => addInvoiceItemRow(it));
    } else {
        addInvoiceItemRow();
    }

    const form = document.getElementById('invoiceForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (window.tinymce) {
                window.tinymce.triggerSave();
            }

            if (isInvoiceSubmitting) {
                e.preventDefault();
                return false;
            }
            if (!this.checkValidity()) return;

            isInvoiceSubmitting = true;
            const btn = document.getElementById('saveInvoiceBtn');
            if (btn) {
                btn.classList.add('disabled');
                btn.style.pointerEvents = 'none';
                btn.innerHTML = '<i class="bi bi-arrow-repeat acm-spin"></i> Updating Invoice...';
                setTimeout(() => { btn.disabled = true; }, 20);
            }
        });
    }
});

window.addEventListener('pageshow', function() {
    isInvoiceSubmitting = false;
    const btn = document.getElementById('saveInvoiceBtn');
    if (btn) {
        btn.disabled = false;
        btn.classList.remove('disabled');
        btn.style.pointerEvents = 'auto';
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Update Invoice';
    }
});
</script>
@endpush
@endsection
