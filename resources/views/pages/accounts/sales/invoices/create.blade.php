@extends('layouts.app')

@section('title', 'Create Invoice - Accounts Sales')

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

/* Address Columns */
.address-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; }
@media (max-width: 768px) { .address-grid { grid-template-columns:1fr; } }
.address-col-header { display:flex; align-items:center; justify-content:space-between; font-size:13px; font-weight:800; color:#0f172a; margin-bottom:12px; }
.copy-btn { background:none; border:none; color:#2563eb; font-size:12px; font-weight:700; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:4px; }
.copy-btn:hover { text-decoration:underline; }

/* Items Table */
.items-table { width:100%; border-collapse:collapse; margin-top:8px; }
.items-table th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:10px 12px; border-bottom:2px solid #e2e8f0; background:#f8fafc; text-align:left; }
.items-table td { padding:10px 12px; vertical-align:top; border-bottom:1px solid #f1f5f9; }

/* Totals Summary */
.summary-container { display:flex; justify-content:flex-end; margin-top:14px; }
.summary-box { width:100%; max-width:440px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:12px; }
.summary-row { display:flex; align-items:center; justify-content:space-between; font-size:13px; color:#475569; }
.summary-row.total-row { font-size:16px; font-weight:800; color:#0f172a; border-top:2px solid #e2e8f0; padding-top:10px; margin-top:4px; }
.summary-row.balance-row { font-size:18px; font-weight:900; color:#dc2626; border-top:1px dashed #cbd5e1; padding-top:10px; }

/* Modal Overlay & Box */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:95%; max-width:920px; max-height:92vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:16.5px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.modal-close { background:none; border:none; font-size:24px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-close:hover { color:#0f172a; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:20px; }
.modal-footer { display:flex; align-items:center; justify-content:flex-end; gap:12px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; position:sticky; bottom:0; z-index:10; }

.form-section-title { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px dashed #e2e8f0; padding-bottom:6px; }
.address-container { display:grid; grid-template-columns:1fr 1fr; gap:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:18px; }
@media (max-width: 768px) { .address-container { grid-template-columns:1fr; } }
.address-col-title { font-size:13px; font-weight:800; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; }
.address-row { display:grid; grid-template-columns:120px 1fr; gap:12px; align-items:start; margin-bottom:10px; }
@media (max-width: 600px) { .address-row { grid-template-columns:1fr; gap:4px; } }
.address-row-label { font-size:12px; font-weight:600; color:#64748b; padding-top:8px; }
.copy-address-btn { background:none; border:none; color:#ea580c; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px; }
.copy-address-btn:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-file-earmark-plus" style="color:#fe5f04;"></i>
                <span>Create Sales Invoice</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.invoices.index') }}">Sales</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">New Invoice</span>
            </div>
        </div>
        <div class="inv-actions">
            <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancel
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openMailPreviewOrSubmit()">
                <i class="bi bi-check-lg"></i> Save & Issue Invoice
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
    @if(isset($errors) && $errors->any())
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Success Toast Alert for Quick Actions --}}
    <div id="quickCustomerSuccessAlert" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700;">
        <i class="bi bi-check-circle-fill" style="color:#10b981; margin-right:6px;"></i>
        <span id="quickCustomerSuccessMsg">Customer created and auto-selected!</span>
    </div>

    {{-- Main Invoice Form --}}
    <form method="POST" action="{{ route('accounts.sales.invoices.store') }}" id="invoiceForm">
        @csrf

        {{-- Hidden email flag: 1 = send email, 0 = no email --}}
        <input type="hidden" name="send_email" id="sendEmailInput" value="0">
        {{-- Hidden invoice status: sent or draft --}}
        <input type="hidden" name="status" id="invoiceStatusInput" value="sent">

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

                    {{-- Customer Selector Bar with + New Customer Button --}}
                    <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:16px 20px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap;">
                            <div class="form-group" style="flex:1; min-width:280px; max-width:550px; margin-bottom:0;">
                                <label class="form-label" style="color:#9a3412; font-size:13px; font-weight:800;">
                                    <i class="bi bi-person-check-fill"></i> Select Customer (Customer Master) <span style="color:#dc2626;">*</span>
                                </label>
                                <select name="customer_id" id="customerSelect" class="form-input select2" style="border-color:#fdba74; font-size:13.5px; padding:10px 14px;" onchange="onCustomerSelected(this.value)" required>
                                    <option value="">-- Choose Existing Customer --</option>
                                    @foreach($customers as $cust)
                                        <option value="{{ $cust->id }}" {{ old('customer_id', request('customer_id', $prefillCustomer?->id)) == $cust->id ? 'selected' : '' }}>
                                            {{ $cust->customer_name }} {{ $cust->company_name ? "({$cust->company_name})" : '' }} {{ $cust->mobile_number ? "- {$cust->mobile_number}" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <button type="button" class="acm-btn" onclick="openQuickCustomerModal()" style="background:#ea580c; color:#fff; border:none; padding:10px 18px; font-size:13px; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:6px; box-shadow:0 3px 10px rgba(234,88,12,0.25); cursor:pointer;">
                                    <i class="bi bi-person-plus-fill"></i> + New Customer
                                </button>
                            </div>
                        </div>

                        {{-- Hidden inputs to preserve customer snapshot data --}}
                        <input type="hidden" name="customer_name" id="customerName" value="{{ old('customer_name') }}">
                        <input type="hidden" name="company_name" id="companyName" value="{{ old('company_name') }}">
                        <input type="hidden" name="customer_phone" id="customerPhone" value="{{ old('customer_phone') }}">
                        <input type="hidden" name="customer_email" id="customerEmail" value="{{ old('customer_email') }}">
                        <input type="hidden" name="customer_gstin" id="customerGstin" value="{{ old('customer_gstin') }}">
                        <input type="hidden" name="customer_pan" id="customerPan" value="{{ old('customer_pan') }}">
                    </div>

                    {{-- Invoice Identification & Dates --}}
                    <div class="form-grid-4">
                        <div class="form-group">
                            <label class="form-label" style="display:flex; align-items:center; justify-content:space-between;">
                                <span>Invoice Number <span style="color:#dc2626;">*</span></span>
                                <span style="font-size:10.5px; font-weight:700; color:#059669; background:#dcfce7; padding:1px 7px; border-radius:4px;">
                                    <i class="bi bi-magic"></i> Auto-generated
                                </span>
                            </label>
                            <input type="text" name="invoice_number" id="invoiceNumber" class="form-input"
                                   value="{{ old('invoice_number', $invoiceNumber) }}" readonly
                                   style="font-weight:700; background:#f1f5f9; cursor:not-allowed; color:#0f172a;" required>

                        </div>

                        <div class="form-group">
                            <label class="form-label">Invoice Date <span style="color:#dc2626;">*</span></label>
                            <input type="date" name="invoice_date" id="invoiceDate" class="form-input"
                                   value="{{ old('invoice_date', date('Y-m-d')) }}" required onchange="calculateDueDate()">

                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select name="payment_terms" id="paymentTerms" class="form-input" onchange="calculateDueDate()">
                                <option value="Due on Receipt" {{ old('payment_terms') === 'Due on Receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                <option value="Net 15" {{ old('payment_terms') === 'Net 15' ? 'selected' : '' }}>Net 15</option>
                                <option value="Net 30" {{ old('payment_terms', 'Net 30') === 'Net 30' ? 'selected' : '' }}>Net 30</option>
                                <option value="Net 45" {{ old('payment_terms') === 'Net 45' ? 'selected' : '' }}>Net 45</option>
                                <option value="Net 60" {{ old('payment_terms') === 'Net 60' ? 'selected' : '' }}>Net 60</option>
                                <option value="Due End of Month" {{ old('payment_terms') === 'Due End of Month' ? 'selected' : '' }}>Due End of Month</option>
                                <option value="Due End of Next Month" {{ old('payment_terms') === 'Due End of Next Month' ? 'selected' : '' }}>Due End of Next Month</option>
                                <option value="Custom" {{ old('payment_terms') === 'Custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="dueDate" class="form-input"
                                   value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Place of Supply (All States & UTs) <span style="color:#dc2626;">*</span></label>
                            <select name="place_of_supply" id="placeOfSupply" class="form-input" required onchange="onPlaceOfSupplyChanged()">
                                <option value="">-- Select State for GST Place of Supply --</option>
                                @foreach($states as $codeKey => $stateName)
                                    <option value="{{ $codeKey }}" {{ old('place_of_supply', $prefillPlaceOfSupply ?? '[33] Tamil Nadu') === $codeKey ? 'selected' : '' }}>
                                        {{ $codeKey }}
                                    </option>
                                @endforeach
                            </select>
                            <span style="font-size:11px; color:#94a3b8;">Determines whether CGST + SGST (Intra-state) or IGST (Inter-state) applies</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Sales Person (Sales & CST Teams)</label>
                            <select name="sales_person_id" id="salesPersonId" class="form-input">
                                <option value="">-- Choose Sales / CST Representative --</option>
                                @foreach($salesUsers as $u)
                                    <option value="{{ $u->id }}" {{ old('sales_person_id', auth()->id()) == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Bill To and Ship To Addresses (Editable) --}}
                    <div>
                        <div class="address-grid">
                            {{-- Billing Address --}}
                            <div>
                                <div class="address-col-header">
                                    <span><i class="bi bi-receipt" style="color:#2563eb;"></i> Billing Address (Bill To)</span>
                                </div>
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    <div class="form-group">
                                        <label class="form-label">Attention</label>
                                        <input type="text" name="billing_attention" id="billingAttention" class="form-input"
                                               value="{{ old('billing_attention') }}" placeholder="Attention / Recipient Name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">GST Number (GSTIN)</label>
                                        <input type="text" name="billing_gstin" id="billingGstin" class="form-input" style="text-transform:uppercase;"
                                               value="{{ old('billing_gstin', old('customer_gstin')) }}" placeholder="e.g. 33AAAAA0000A1Z5" oninput="syncGstin(this.value)">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Address (Street / Building)</label>
                                        <textarea name="billing_address" id="billingAddress" class="form-input" rows="2"
                                                  placeholder="Street address line">{{ old('billing_address') }}</textarea>
                                    </div>
                                    <div class="form-grid-3">
                                        <div class="form-group">
                                            <label class="form-label">City</label>
                                            <input type="text" name="billing_city" id="billingCity" class="form-input"
                                                   value="{{ old('billing_city') }}" placeholder="City">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">State</label>
                                            <input type="text" name="billing_state" id="billingState" class="form-input"
                                                   value="{{ old('billing_state') }}" placeholder="State">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Pin Code</label>
                                            <input type="text" name="billing_pincode" id="billingPincode" class="form-input"
                                                   value="{{ old('billing_pincode') }}" placeholder="Pin Code">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Shipping Address --}}
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
                                               value="{{ old('shipping_attention') }}" placeholder="Attention / Recipient Name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">GST Number (GSTIN)</label>
                                        <input type="text" name="shipping_gstin" id="shippingGstin" class="form-input" style="text-transform:uppercase;"
                                               value="{{ old('shipping_gstin', old('customer_gstin')) }}" placeholder="e.g. 33AAAAA0000A1Z5">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Address (Street / Building)</label>
                                        <textarea name="shipping_address" id="shippingAddress" class="form-input" rows="2"
                                                  placeholder="Street address line">{{ old('shipping_address') }}</textarea>
                                    </div>
                                    <div class="form-grid-3">
                                        <div class="form-group">
                                            <label class="form-label">City</label>
                                            <input type="text" name="shipping_city" id="shippingCity" class="form-input"
                                                   value="{{ old('shipping_city') }}" placeholder="City">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">State</label>
                                            <input type="text" name="shipping_state" id="shippingState" class="form-input"
                                                   value="{{ old('shipping_state') }}" placeholder="State">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Pin Code</label>
                                            <input type="text" name="shipping_pincode" id="shippingPincode" class="form-input"
                                                   value="{{ old('shipping_pincode') }}" placeholder="Pin Code">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Section 2: Items / Products List Table --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div>
                        <div class="inv-card-title">
                            <i class="bi bi-box-seam" style="color:#fe5f04;"></i>
                            <span>Products / Line Items (Item Master)</span>
                        </div>
                        <div class="inv-card-sub">Select products from Item Master catalog. Description, rates, and GST taxes are fully editable.</div>
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
                                {{-- Rows dynamically inserted or initialized via JS --}}
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:10px;">
                        <button type="button" class="acm-btn acm-btn-secondary" onclick="addInvoiceItemRow()">
                            <i class="bi bi-plus-circle"></i> Add Another Item
                        </button>
                    </div>

                    {{-- Calculations Summary Box --}}
                    <div class="summary-container">
                        <div class="summary-box">
                            <div class="summary-row">
                                <span>Sub Total</span>
                                <span style="font-weight:700;" id="sumSubtotal">₹0.00</span>
                                <input type="hidden" name="subtotal" id="inputSubtotal" value="0.00">
                            </div>

                            {{-- Tax Section Header --}}
                            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; border-top:1px dashed #e2e8f0; padding-top:10px; display:flex; align-items:center; justify-content:space-between;">
                                <span id="taxTypeLabel"><i class="bi bi-geo-alt"></i> GST (Intra-State: Tamil Nadu)</span>
                                <span id="stateBadge" style="font-size:10px; padding:2px 8px; border-radius:999px; background:#e0f2fe; color:#0369a1; font-weight:700;">CGST + SGST</span>
                            </div>

                            {{-- Tamil Nadu: CGST & SGST Rows (Editable Percentages) --}}
                            <div id="tnGstRows" style="display:flex; flex-direction:column; gap:8px;">
                                <div class="summary-row" style="align-items:center;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span>CGST (</span>
                                        <input type="number" step="0.01" min="0" max="50" id="inputCgstPercent" class="form-input" style="width:70px; padding:4px 8px; font-size:12px; font-weight:700; text-align:right;" value="{{ old('cgst_percent', !empty($prefillQuotation) && isset($prefillQuotation->cgst_rate) ? number_format((float)$prefillQuotation->cgst_rate, 2, '.', '') : '9.00') }}" oninput="onGstPercentChange('cgst')">
                                        <span>%)</span>
                                    </div>
                                    <span style="font-weight:700;" id="sumCgst">₹0.00</span>
                                    <input type="hidden" name="total_cgst" id="inputTotalCgst" value="0.00">
                                </div>

                                <div class="summary-row" style="align-items:center;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span>SGST (</span>
                                        <input type="number" step="0.01" min="0" max="50" id="inputSgstPercent" class="form-input" style="width:70px; padding:4px 8px; font-size:12px; font-weight:700; text-align:right;" value="{{ old('sgst_percent', !empty($prefillQuotation) && isset($prefillQuotation->sgst_rate) ? number_format((float)$prefillQuotation->sgst_rate, 2, '.', '') : '9.00') }}" oninput="onGstPercentChange('sgst')">
                                        <span>%)</span>
                                    </div>
                                    <span style="font-weight:700;" id="sumSgst">₹0.00</span>
                                    <input type="hidden" name="total_sgst" id="inputTotalSgst" value="0.00">
                                </div>
                            </div>

                            {{-- Other States: IGST Row (Editable Percentage) --}}
                            <div id="interStateGstRow" style="display:none; flex-direction:column; gap:8px;">
                                <div class="summary-row" style="align-items:center;">
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span>IGST (</span>
                                        <input type="number" step="0.01" min="0" max="100" id="inputIgstPercent" class="form-input" style="width:70px; padding:4px 8px; font-size:12px; font-weight:700; text-align:right;" value="{{ old('igst_percent', !empty($prefillQuotation) && isset($prefillQuotation->igst_rate) ? number_format((float)$prefillQuotation->igst_rate, 2, '.', '') : '18.00') }}" oninput="onGstPercentChange('igst')">
                                        <span>%)</span>
                                    </div>
                                    <span style="font-weight:700;" id="sumIgst">₹0.00</span>
                                    <input type="hidden" name="total_igst" id="inputTotalIgst" value="0.00">
                                </div>
                            </div>

                            <div class="summary-row" style="font-size:12px; color:#64748b;">
                                <span>Total Tax Amount</span>
                                <span style="font-weight:700;" id="sumTotalTax">₹0.00</span>
                                <input type="hidden" name="total_tax" id="inputTotalTax" value="0.00">
                            </div>

                            <div class="summary-row" style="align-items:center;">
                                <span>Round Off (+ / -)</span>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <input type="number" step="0.01" name="round_off" id="inputRoundOff" class="form-input" style="width:95px; padding:4px 8px; text-align:right; font-weight:700;" value="0.00" oninput="onRoundOffManualChange()">
                                </div>
                            </div>

                            <div class="summary-row total-row">
                                <span>Grand Total</span>
                                <span id="sumGrandTotal">₹0.00</span>
                                <input type="hidden" name="total_amount" id="inputTotalAmount" value="0.00">
                            </div>

                            <input type="hidden" name="paid_amount" id="inputPaidAmount" value="0.00">

                            <div class="summary-row balance-row">
                                <span>Balance Due</span>
                                <span id="sumBalanceDue">₹0.00</span>
                                <input type="hidden" name="balance_due" id="inputBalanceDue" value="0.00">
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
                            <textarea id="invoiceTermsEditor" name="terms_conditions" class="form-input" rows="6" style="font-size:13px; line-height:1.6;">{{ old('terms_conditions', $defaultTerms) }}</textarea>
                            <span style="font-size:11px; color:#94a3b8; margin-top:2px;">Defaults from Invoice Settings. Appears automatically on the dedicated 2nd page.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Customer Notes / Payment Remarks (Page 1)</label>
                            <textarea name="customer_notes" class="form-input" rows="3" style="font-size:12px; line-height:1.6;" placeholder="Thanks for your business. Please remit payments on or before the due date.">{{ old('customer_notes', $defaultNotes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom Actions Bar --}}
            <div style="display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02);">
                <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" onclick="submitDraftInvoice()" class="acm-btn acm-btn-outline">
                        Save as Draft
                    </button>
                    <button type="button" id="saveInvoiceBtn" class="acm-btn acm-btn-primary" style="padding:10px 24px; font-size:14px;" onclick="openMailPreviewOrSubmit()">
                        <i class="bi bi-check-lg"></i> Create & Issue Invoice
                    </button>
                </div>
            </div>

        </div>
    </form>

    {{-- Modal: Quick Customer Creation --}}
    <div id="quickCustomerModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="bi bi-person-plus-fill" style="color:#ea580c;"></i>
                    <span>Add New Customer</span>
                </div>
                <button type="button" class="modal-close" onclick="closeQuickCustomerModal()">&times;</button>
            </div>
            <form id="quickCustomerForm" onsubmit="submitQuickCustomer(event)">
                @csrf
                <input type="hidden" name="lead_id" id="qcLeadId" value="">

                <div class="modal-body">
                    <div id="quickCustomerErrorAlert" style="display:none; background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:12.5px;"></div>

                    {{-- Converted Leads Auto-Fetch Bar --}}
                    <div class="lead-select-card" style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:14px 18px; display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <i class="bi bi-lightning-charge-fill" style="color:#ea580c; font-size:16px;"></i>
                                <strong style="font-size:13px; color:#9a3412;">Auto-Fetch from Converted CRM Lead (Optional)</strong>
                            </div>
                            <div id="qcLeadSelectedIndicator" style="display:none;">
                                <span class="lead-badge-chip" style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; border-radius:6px; font-size:12px; font-weight:700;">
                                    <i class="bi bi-check-circle-fill"></i> Autofilled from Lead <span id="qcLeadBadgeName"></span>
                                </span>
                                <button type="button" onclick="clearQcLeadSelection()" style="background:none; border:none; color:#b91c1c; font-size:11px; font-weight:700; cursor:pointer; text-decoration:underline; margin-left:6px;">Clear</button>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center;">
                            <div style="position:relative;">
                                <input type="text"
                                       id="qcLeadSearchInput"
                                       class="form-input"
                                       placeholder="Search converted leads by Contact Name, Company Name, or Mobile Number…"
                                       oninput="debounceQcLeadSearch(this.value)">
                                <div id="qcLeadSearchResults" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:50; background:#fff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,.15); max-height:220px; overflow-y:auto; margin-top:4px;"></div>
                            </div>
                            <select id="qcRecentLeadsSelect" class="form-input" style="max-width:280px;" onchange="onQcLeadSelectChanged(this.value)">
                                <option value="">-- Or Pick Recent Converted Lead --</option>
                                @if(isset($recentConvertedLeads))
                                    @foreach($recentConvertedLeads as $rLead)
                                        <option value="{{ $rLead->id }}">
                                            {{ $rLead->contact_name ?: $rLead->company_name }} ({{ $rLead->mobile_number ?: 'No Mobile' }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    {{-- Group 1: General & Contact Info --}}
                    <div>
                        <div class="form-section-title">
                            <span><i class="bi bi-person-badge"></i> Customer Profile & Business Info</span>
                        </div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label class="form-label">Customer Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="customer_name" id="qcCustomerName" class="form-input" placeholder="e.g. Ramesh Kumar" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="qcCompanyName" class="form-input" placeholder="e.g. Acme Technologies Pvt Ltd">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mobile Number</label>
                                <input type="text" name="mobile_number" id="qcMobileNumber" class="form-input" placeholder="e.g. +91 98765 43210">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" id="qcEmail" class="form-input" placeholder="e.g. client@company.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">PAN Number</label>
                                <input type="text" name="pan_number" id="qcPanNumber" class="form-input" placeholder="e.g. ABCDE1234F" style="text-transform:uppercase;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">GST Number</label>
                                <input type="text" name="gst_number" id="qcGstNumber" class="form-input" placeholder="e.g. 33AAAAA0000A1Z5" style="text-transform:uppercase;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" id="qcPaymentTerms" class="form-input">
                                    <option value="">Select Payment Terms</option>
                                    <option value="Due on Receipt" selected>Due on Receipt</option>
                                    <option value="Net 15">Net 15</option>
                                    <option value="Net 30">Net 30</option>
                                    <option value="Net 45">Net 45</option>
                                    <option value="Net 60">Net 60</option>
                                    <option value="Due End of Month">Due End of Month</option>
                                    <option value="Due End of Next Month">Due End of Next Month</option>
                                    <option value="50% Advance, 50% on Delivery">50% Advance, 50% on Delivery</option>
                                    <option value="100% Advance">100% Advance</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Group 2: Address Details --}}
                    <div>
                        <div class="form-section-title">
                            <span><i class="bi bi-geo-alt"></i> Address Details</span>
                        </div>

                        <div class="address-container">
                            {{-- Left Column: Billing Address --}}
                            <div>
                                <div class="address-col-title">
                                    <span>Billing Address</span>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Attention</span>
                                    <input type="text" name="billing_attention" id="qcBillingAttention" class="form-input" placeholder="Name or Dept">
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Country/Region</span>
                                    <select name="billing_country" id="qcBillingCountry" class="form-input">
                                        <option value="India" selected>India</option>
                                        <option value="United States">United States</option>
                                        <option value="United Arab Emirates">United Arab Emirates</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="Malaysia">Malaysia</option>
                                        <option value="Australia">Australia</option>
                                        <option value="Canada">Canada</option>
                                    </select>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Address</span>
                                    <div style="display:flex; flex-direction:column; gap:8px;">
                                        <textarea name="billing_address_street_1" id="qcBillingStreet1" rows="2" class="form-input" placeholder="Street 1"></textarea>
                                        <textarea name="billing_address_street_2" id="qcBillingStreet2" rows="2" class="form-input" placeholder="Street 2"></textarea>
                                    </div>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">City</span>
                                    <input type="text" name="billing_city" id="qcBillingCity" class="form-input">
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">State</span>
                                    <select name="billing_state" id="qcBillingState" class="form-input">
                                        <option value="">Select or type to add</option>
                                        <option value="Tamil Nadu" selected>Tamil Nadu</option>
                                        <option value="Karnataka">Karnataka</option>
                                        <option value="Kerala">Kerala</option>
                                        <option value="Maharashtra">Maharashtra</option>
                                        <option value="Andhra Pradesh">Andhra Pradesh</option>
                                        <option value="Telangana">Telangana</option>
                                        <option value="Delhi">Delhi</option>
                                        <option value="Gujarat">Gujarat</option>
                                        <option value="Haryana">Haryana</option>
                                        <option value="Uttar Pradesh">Uttar Pradesh</option>
                                        <option value="West Bengal">West Bengal</option>
                                        <option value="Rajasthan">Rajasthan</option>
                                        <option value="Punjab">Punjab</option>
                                        <option value="Other">Other State</option>
                                    </select>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Pin Code</span>
                                    <input type="text" name="billing_pin_code" id="qcBillingPinCode" class="form-input">
                                </div>
                            </div>

                            {{-- Right Column: Shipping Address --}}
                            <div>
                                <div class="address-col-title">
                                    <span>Shipping Address</span>
                                    <button type="button" class="copy-address-btn" onclick="copyQuickBillingToShipping()">
                                        <i class="bi bi-arrow-down-short" style="font-size:16px;"></i> Copy billing address
                                    </button>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Attention</span>
                                    <input type="text" name="shipping_attention" id="qcShippingAttention" class="form-input" placeholder="Name or Dept">
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Country/Region</span>
                                    <select name="shipping_country" id="qcShippingCountry" class="form-input">
                                        <option value="India" selected>India</option>
                                        <option value="United States">United States</option>
                                        <option value="United Arab Emirates">United Arab Emirates</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="Malaysia">Malaysia</option>
                                        <option value="Australia">Australia</option>
                                        <option value="Canada">Canada</option>
                                    </select>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Address</span>
                                    <div style="display:flex; flex-direction:column; gap:8px;">
                                        <textarea name="shipping_address_street_1" id="qcShippingStreet1" rows="2" class="form-input" placeholder="Street 1"></textarea>
                                        <textarea name="shipping_address_street_2" id="qcShippingStreet2" rows="2" class="form-input" placeholder="Street 2"></textarea>
                                    </div>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">City</span>
                                    <input type="text" name="shipping_city" id="qcShippingCity" class="form-input">
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">State</span>
                                    <select name="shipping_state" id="qcShippingState" class="form-input">
                                        <option value="">Select or type to add</option>
                                        <option value="Tamil Nadu" selected>Tamil Nadu</option>
                                        <option value="Karnataka">Karnataka</option>
                                        <option value="Kerala">Kerala</option>
                                        <option value="Maharashtra">Maharashtra</option>
                                        <option value="Andhra Pradesh">Andhra Pradesh</option>
                                        <option value="Telangana">Telangana</option>
                                        <option value="Delhi">Delhi</option>
                                        <option value="Gujarat">Gujarat</option>
                                        <option value="Haryana">Haryana</option>
                                        <option value="Uttar Pradesh">Uttar Pradesh</option>
                                        <option value="West Bengal">West Bengal</option>
                                        <option value="Rajasthan">Rajasthan</option>
                                        <option value="Punjab">Punjab</option>
                                        <option value="Other">Other State</option>
                                    </select>
                                </div>

                                <div class="address-row">
                                    <span class="address-row-label">Pin Code</span>
                                    <input type="text" name="shipping_pin_code" id="qcShippingPinCode" class="form-input">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Group 3: Remarks --}}
                    <div class="form-group">
                        <label class="form-label">Remarks / Internal Notes</label>
                        <textarea name="remarks" id="qcRemarks" class="form-input" rows="2" placeholder="Any internal notes regarding this client..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="acm-btn acm-btn-secondary" onclick="closeQuickCustomerModal()">Cancel</button>
                    <button type="submit" id="qcSubmitBtn" class="acm-btn acm-btn-primary" style="background:#ea580c;">
                        <i class="bi bi-check-lg"></i> Create & Select Customer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Mail Preview & Approval --}}
    <div id="mailPreviewModal" class="modal-overlay">
        <div class="modal-box" style="max-width:760px;">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="bi bi-envelope-paper-fill" style="color:#fe5f04;"></i>
                    <span>Invoice Email Preview & Approval</span>
                </div>
                <button type="button" class="modal-close" onclick="closeMailPreviewModal()">&times;</button>
            </div>
            <div class="modal-body" style="gap:16px;">
                <div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; border-radius:10px; padding:12px 16px; font-size:12.5px; display:flex; align-items:flex-start; gap:10px;">
                    <i class="bi bi-info-circle-fill" style="font-size:16px; margin-top:2px;"></i>
                    <div>
                        Review the email notification below before issuing. You can approve and send the email with the invoice PDF attached, or choose to create/issue the invoice without sending an email.
                    </div>
                </div>

                {{-- Recipient & Subject --}}
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 18px; display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label class="form-label" style="font-size:11.5px; text-transform:uppercase; color:#64748b;">Recipient Email (To:)</label>
                        <div style="display:flex; align-items:center; gap:10px; margin-top:4px;">
                            <input type="email" id="previewCustomerEmail" class="form-input" placeholder="e.g. customer@example.com" oninput="syncPreviewEmail(this.value)">
                        </div>
                        <span id="previewEmailWarning" style="display:none; color:#dc2626; font-size:11.5px; font-weight:700; margin-top:4px;">
                            <i class="bi bi-exclamation-triangle-fill"></i> Valid email address is required to send mail. You can type it here or click 'Create Without Email'.
                        </span>
                    </div>

                    <div>
                        <label class="form-label" style="font-size:11.5px; text-transform:uppercase; color:#64748b;">Subject Line</label>
                        <div id="previewEmailSubject" style="background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; font-weight:700; color:#0f172a; margin-top:4px;">
                            Tax Invoice #...
                        </div>
                    </div>
                </div>

                {{-- Email Mockup Box --}}
                <div style="border:1px solid #cbd5e1; border-radius:12px; overflow:hidden; background:#ffffff;">
                    <div style="background:linear-gradient(135deg, #fe5f04, #ff8533); padding:16px 20px; color:#ffffff; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-size:16px; font-weight:800;">{{ $setting->company_name ?: config('app.name') }}</div>
                            <div style="font-size:11px; opacity:0.9;">TAX INVOICE NOTIFICATION</div>
                        </div>
                        <span style="background:rgba(255,255,255,0.25); color:#fff; font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px;">ISSUED</span>
                    </div>
                    <div style="padding:18px 20px; font-size:13px; color:#334155; line-height:1.6;">
                        <p style="margin:0 0 10px;">Dear <strong id="previewCustomerName">Customer</strong>,</p>
                        <p style="margin:0 0 16px;">Thank you for your business! Please find below your invoice summary for <strong id="previewInvoiceNumber">#INV</strong>.</p>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:14px;">
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Invoice Date</span>
                                <span id="previewInvoiceDate" style="font-weight:600; color:#0f172a;">—</span>
                            </div>
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Due Date</span>
                                <span id="previewDueDate" style="font-weight:600; color:#dc2626;">—</span>
                            </div>
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Total Amount</span>
                                <span id="previewTotalAmount" style="font-weight:800; color:#fe5f04; font-size:15px;">₹ 0.00</span>
                            </div>
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Balance Due</span>
                                <span id="previewBalanceDue" style="font-weight:800; color:#ea580c; font-size:15px;">₹ 0.00</span>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:8px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; padding:8px 12px; font-size:12px; color:#334155;">
                            <i class="bi bi-file-earmark-pdf-fill" style="color:#dc2626; font-size:18px;"></i>
                            <span>Attachment: <strong id="previewPdfName">Invoice.pdf</strong> (Official PDF copy)</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeMailPreviewModal()">
                    <i class="bi bi-x-lg"></i> Cancel / Keep Editing
                </button>
                <div style="display:flex; align-items:center; gap:10px;">
                    <button type="button" class="acm-btn acm-btn-outline" onclick="submitInvoiceWithoutMail()" title="Create invoice without sending email">
                        <i class="bi bi-file-earmark-check"></i> Create Without Email
                    </button>
                    <button type="button" class="acm-btn acm-btn-primary" onclick="approveAndSendInvoiceEmail()" id="approveMailBtn" style="background:linear-gradient(135deg, #16a34a, #22c55e);">
                        <i class="bi bi-send-check-fill"></i> Approve & Send Email
                    </button>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- /inv-body --}}
</div>{{-- /inv-page --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// Catalog Items JSON for client-side auto-fill
const catalogItems = @json($items);
let isInvoiceSubmitting = false;
let allowActualSubmit = false;

// ==========================================
// Quick Customer Functions (AJAX Modal)
// ==========================================
let qcLeadSearchTimeout = null;

function debounceQcLeadSearch(query) {
    clearTimeout(qcLeadSearchTimeout);
    const resultsBox = document.getElementById('qcLeadSearchResults');
    if (!resultsBox) return;

    if (!query || query.trim().length < 2) {
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
        return;
    }

    qcLeadSearchTimeout = setTimeout(() => {
        const url = "{{ route('accounts.master.customers.search-converted-leads') }}?q=" + encodeURIComponent(query.trim());
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (!Array.isArray(data) || !data.length) {
                    resultsBox.innerHTML = '<div style="padding:10px 14px; font-size:12px; color:#94a3b8;">No matching converted leads found</div>';
                    resultsBox.style.display = 'block';
                    return;
                }

                let html = '';
                data.forEach(item => {
                    html += `
                        <div onclick="selectQcLeadFromSearch(${item.id}, '${escapeHtml(item.name)}')"
                             style="padding:8px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .15s;"
                             onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                            <div style="font-weight:700; font-size:13px; color:#0f172a;">${escapeHtml(item.name)}</div>
                            <div style="font-size:11px; color:#64748b; display:flex; gap:10px; margin-top:2px;">
                                ${item.company_name ? `<span><i class="bi bi-building"></i> ${escapeHtml(item.company_name)}</span>` : ''}
                                ${item.mobile_number ? `<span><i class="bi bi-telephone"></i> ${escapeHtml(item.mobile_number)}</span>` : ''}
                            </div>
                        </div>
                    `;
                });
                resultsBox.innerHTML = html;
                resultsBox.style.display = 'block';
            })
            .catch(() => {
                resultsBox.style.display = 'none';
            });
    }, 280);
}

function selectQcLeadFromSearch(leadId, leadName) {
    const resBox = document.getElementById('qcLeadSearchResults');
    if (resBox) resBox.style.display = 'none';
    const sInput = document.getElementById('qcLeadSearchInput');
    if (sInput) sInput.value = leadName;
    fetchQcLeadDetailsAndAutoFill(leadId);
}

function onQcLeadSelectChanged(leadId) {
    if (!leadId) return;
    fetchQcLeadDetailsAndAutoFill(leadId);
}

function fetchQcLeadDetailsAndAutoFill(leadId) {
    const url = "{{ url('accounts/master/customers/lead-details') }}/" + leadId;
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data) return;

            const leadIdElem = document.getElementById('qcLeadId');
            if (leadIdElem) leadIdElem.value = data.id || '';

            // Fill General Fields
            if (data.customer_name) document.getElementById('qcCustomerName').value = data.customer_name;
            if (data.company_name) document.getElementById('qcCompanyName').value = data.company_name;
            if (data.mobile_number) document.getElementById('qcMobileNumber').value = data.mobile_number;
            if (data.email) document.getElementById('qcEmail').value = data.email;

            // Attention in Billing / Shipping
            if (data.customer_name) {
                const bAtt = document.getElementById('qcBillingAttention');
                if (bAtt) bAtt.value = data.customer_name;
                const sAtt = document.getElementById('qcShippingAttention');
                if (sAtt) sAtt.value = data.customer_name;
            }

            // City
            if (data.city) {
                const bCity = document.getElementById('qcBillingCity');
                if (bCity) bCity.value = data.city;
                const sCity = document.getElementById('qcShippingCity');
                if (sCity) sCity.value = data.city;
            }

            // State
            if (data.state) {
                const bState = document.getElementById('qcBillingState');
                if (bState) bState.value = data.state;
                const sState = document.getElementById('qcShippingState');
                if (sState) sState.value = data.state;
            }

            // Address / Location
            if (data.address) {
                const bStr = document.getElementById('qcBillingStreet1');
                if (bStr) bStr.value = data.address;
                const sStr = document.getElementById('qcShippingStreet1');
                if (sStr) sStr.value = data.address;
            }

            // Show badge
            const indicator = document.getElementById('qcLeadSelectedIndicator');
            if (indicator) {
                const badgeName = document.getElementById('qcLeadBadgeName');
                if (badgeName) badgeName.textContent = data.customer_name || ('#' + data.id);
                indicator.style.display = 'inline-flex';
            }
        })
        .catch(err => console.error('Error fetching lead details:', err));
}

function clearQcLeadSelection() {
    const leadIdElem = document.getElementById('qcLeadId');
    if (leadIdElem) leadIdElem.value = '';
    const sInput = document.getElementById('qcLeadSearchInput');
    if (sInput) sInput.value = '';
    const rSelect = document.getElementById('qcRecentLeadsSelect');
    if (rSelect) rSelect.value = '';
    const indicator = document.getElementById('qcLeadSelectedIndicator');
    if (indicator) indicator.style.display = 'none';
}

function openQuickCustomerModal() {
    const modal = document.getElementById('quickCustomerModal');
    if (modal) {
        const errBox = document.getElementById('quickCustomerErrorAlert');
        if (errBox) {
            errBox.style.display = 'none';
            errBox.innerHTML = '';
        }
        modal.classList.add('active');
        const nameInput = document.getElementById('qcCustomerName');
        if (nameInput) setTimeout(() => nameInput.focus(), 150);
    }
}

function closeQuickCustomerModal() {
    const modal = document.getElementById('quickCustomerModal');
    if (modal) modal.classList.remove('active');
    resetQcSubmitBtn();
}

function resetQcSubmitBtn() {
    const btn = document.getElementById('qcSubmitBtn');
    if (btn) {
        btn.disabled = false;
        btn.classList.remove('disabled');
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Create & Select Customer';
    }
}

function copyQuickBillingToShipping() {
    document.getElementById('qcShippingAttention').value   = document.getElementById('qcBillingAttention').value;
    document.getElementById('qcShippingCountry').value     = document.getElementById('qcBillingCountry').value;
    document.getElementById('qcShippingStreet1').value     = document.getElementById('qcBillingStreet1').value;
    document.getElementById('qcShippingStreet2').value     = document.getElementById('qcBillingStreet2').value;
    document.getElementById('qcShippingCity').value        = document.getElementById('qcBillingCity').value;
    document.getElementById('qcShippingState').value       = document.getElementById('qcBillingState').value;
    document.getElementById('qcShippingPinCode').value     = document.getElementById('qcBillingPinCode').value;
}

function submitQuickCustomer(e) {
    e.preventDefault();
    const btn = document.getElementById('qcSubmitBtn');
    const errBox = document.getElementById('quickCustomerErrorAlert');
    errBox.style.display = 'none';
    errBox.innerHTML = '';

    const name = document.getElementById('qcCustomerName').value.trim();
    if (!name) {
        errBox.innerHTML = 'Customer Name is required.';
        errBox.style.display = 'block';
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.classList.add('disabled');
        btn.innerHTML = '<i class="bi bi-arrow-repeat acm-spin"></i> Creating...';
    }

    const form = document.getElementById('quickCustomerForm');
    const formData = new FormData(form);

    fetch("{{ route('accounts.master.customers.store') }}", {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async res => {
        const data = await res.json().catch(() => null);
        if (!res.ok) {
            let msg = 'Failed to create customer.';
            if (data && data.errors) {
                msg = Object.values(data.errors).flat().join('<br>');
            } else if (data && data.message) {
                msg = data.message;
            }
            throw new Error(msg);
        }
        return data;
    })
    .then(data => {
        if (!data || !data.customer) {
            throw new Error('Customer could not be verified in response.');
        }

        const customer = data.customer;
        const sub = [];
        if (customer.company_name) sub.push(customer.company_name);
        if (customer.mobile_number) sub.push(customer.mobile_number);
        const label = customer.customer_name + (sub.length ? ' (' + sub.join(' - ') + ')' : '');

        // Append to #customerSelect and select it
        const sel = $('#customerSelect');
        const opt = new Option(label, customer.id, true, true);
        sel.append(opt).trigger('change');

        // Auto populate address snapshot and details
        onCustomerSelected(customer.id);

        // Reset quick customer form & close modal
        form.reset();
        clearQcLeadSelection();
        closeQuickCustomerModal();

        // Show success alert toast
        const toast = document.getElementById('quickCustomerSuccessAlert');
        const msgSpan = document.getElementById('quickCustomerSuccessMsg');
        if (toast && msgSpan) {
            msgSpan.textContent = `Customer "${customer.customer_name}" created & auto-selected!`;
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 5000);
        }
    })
    .catch(err => {
        errBox.innerHTML = err.message || 'An error occurred while creating customer.';
        errBox.style.display = 'block';
    })
    .finally(() => {
        resetQcSubmitBtn();
    });
}

// ==========================================
// Mail Preview & Approval Workflow Functions
// ==========================================
function openMailPreviewOrSubmit() {
    try {
        const custSelect = document.getElementById('customerSelect');
        const custId = custSelect ? custSelect.value : '';
        if (!custId) {
            alert('Please select or create a customer first.');
            if (typeof $ !== 'undefined' && $('#customerSelect').data('select2')) {
                $('#customerSelect').select2('open');
            } else if (custSelect) {
                custSelect.focus();
            }
            return;
        }

        const invNumInput = document.querySelector('input[name="invoice_number"]');
        const invNum = invNumInput ? invNumInput.value.trim() : '';
        if (!invNum) {
            alert('Invoice Number is required.');
            if (invNumInput) invNumInput.focus();
            return;
        }

        const rows = document.querySelectorAll('#itemsTableBody tr');
        if (!rows.length) {
            alert('Please add at least one line item to the invoice.');
            return;
        }

        const form = document.getElementById('invoiceForm');
        if (form && form.checkValidity && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Populate dynamic values into Mail Preview Modal
        const custEmail = (document.getElementById('customerEmail')?.value || '').trim();
        const previewEmailInput = document.getElementById('previewCustomerEmail');
        if (previewEmailInput) {
            previewEmailInput.value = custEmail;
            togglePreviewEmailWarning(custEmail);
        }

        const companyName = @json($setting->company_name ?: config('app.name'));
        const previewSub = document.getElementById('previewEmailSubject');
        if (previewSub) {
            previewSub.innerText = `Tax Invoice #${invNum} from ${companyName}`;
        }

        let custName = (document.getElementById('customerName')?.value || '').trim();
        if (!custName && typeof $ !== 'undefined') {
            const optText = $('#customerSelect option:selected').text();
            if (optText) custName = optText.split('(')[0].trim();
        }
        if (!custName) custName = 'Valued Customer';
        const previewCust = document.getElementById('previewCustomerName');
        if (previewCust) previewCust.innerText = custName;

        const previewInv = document.getElementById('previewInvoiceNumber');
        if (previewInv) previewInv.innerText = '#' + invNum;

        const invDate = document.getElementById('invoiceDate')?.value || '';
        const previewDate = document.getElementById('previewInvoiceDate');
        if (previewDate) previewDate.innerText = invDate || 'Today';

        // NOTE: The due date input in create.blade.php has id="dueDate"
        const invDueDate = (document.getElementById('dueDate')?.value || document.getElementById('invoiceDueDate')?.value || '');
        const previewDue = document.getElementById('previewDueDate');
        if (previewDue) previewDue.innerText = invDueDate || 'Due on Receipt';

        // NOTE: Grand total is in #sumGrandTotal, balance due is in #sumBalanceDue
        const grandTotalText = document.getElementById('sumGrandTotal')?.innerText
            || ('₹ ' + (document.getElementById('inputTotalAmount')?.value || '0.00'));
        const previewTotal = document.getElementById('previewTotalAmount');
        if (previewTotal) previewTotal.innerText = grandTotalText;

        const balanceDueText = document.getElementById('sumBalanceDue')?.innerText
            || ('₹ ' + (document.getElementById('inputBalanceDue')?.value || '0.00'));
        const previewBal = document.getElementById('previewBalanceDue');
        if (previewBal) previewBal.innerText = balanceDueText;

        const safeFilename = 'Invoice_' + invNum.replace(/[\/\\]/g, '_') + '.pdf';
        const previewPdf = document.getElementById('previewPdfName');
        if (previewPdf) previewPdf.innerText = safeFilename;

        // Show modal
        const modal = document.getElementById('mailPreviewModal');
        if (modal) {
            modal.classList.add('active');
        } else {
            submitInvoiceWithoutMail();
        }
    } catch (err) {
        console.error('Error preparing mail preview:', err);
        submitInvoiceWithoutMail();
    }
}

function closeMailPreviewModal() {
    const modal = document.getElementById('mailPreviewModal');
    if (modal) modal.classList.remove('active');
}

function syncPreviewEmail(val) {
    const emailInput = document.getElementById('customerEmail');
    if (emailInput) emailInput.value = val.trim();
    togglePreviewEmailWarning(val);
}

function togglePreviewEmailWarning(email) {
    const warn = document.getElementById('previewEmailWarning');
    const isValid = email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
    if (warn) {
        warn.style.display = isValid ? 'none' : 'block';
    }
}

function approveAndSendInvoiceEmail() {
    const email = (document.getElementById('previewCustomerEmail')?.value || '').trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please provide a valid recipient email address to send the invoice email, or click "Create Without Email".');
        const emailInput = document.getElementById('previewCustomerEmail');
        if (emailInput) emailInput.focus();
        togglePreviewEmailWarning(email);
        return;
    }

    const emailField = document.getElementById('customerEmail');
    if (emailField) emailField.value = email;

    const sendEmailInput = document.getElementById('sendEmailInput');
    if (sendEmailInput) sendEmailInput.value = '1';

    const statusInput = document.getElementById('invoiceStatusInput');
    if (statusInput) statusInput.value = 'sent';

    closeMailPreviewModal();
    submitInvoiceWithLoading('Creating Invoice & Sending Email...');
}

function submitInvoiceWithoutMail() {
    const sendEmailInput = document.getElementById('sendEmailInput');
    if (sendEmailInput) sendEmailInput.value = '0';

    const statusInput = document.getElementById('invoiceStatusInput');
    if (statusInput) statusInput.value = 'created';

    closeMailPreviewModal();
    submitInvoiceWithLoading('Creating Invoice...');
}

function submitDraftInvoice() {
    const sendEmailInput = document.getElementById('sendEmailInput');
    if (sendEmailInput) sendEmailInput.value = '0';

    let statusInput = document.getElementById('invoiceStatusInput');
    if (!statusInput) {
        statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.id = 'invoiceStatusInput';
        document.getElementById('invoiceForm')?.appendChild(statusInput);
    }
    statusInput.value = 'draft';

    submitInvoiceWithLoading('Saving Draft...');
}

function submitInvoiceWithLoading(msg) {
    if (window.tinymce) {
        window.tinymce.triggerSave();
    }
    if (isInvoiceSubmitting) return;
    isInvoiceSubmitting = true;
    allowActualSubmit = true;

    const btn = document.getElementById('saveInvoiceBtn');
    if (btn) {
        btn.classList.add('disabled');
        btn.style.pointerEvents = 'none';
        btn.innerHTML = `<i class="bi bi-arrow-repeat acm-spin"></i> ${msg}`;
    }

    const form = document.getElementById('invoiceForm');
    if (form) {
        form.submit();
    }
}

// When Customer is chosen from Customer Master
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
                const termsSelect = document.getElementById('paymentTerms');
                termsSelect.value = data.payment_terms;
                calculateDueDate();
            }

            // Billing Address
            document.getElementById('billingAttention').value = data.billing_attention || data.customer_name || '';
            const bGstin = document.getElementById('billingGstin');
            if (bGstin) bGstin.value = data.gst_number || '';
            document.getElementById('billingAddress').value = data.billing_address || '';
            document.getElementById('billingCity').value = data.billing_city || '';
            document.getElementById('billingState').value = data.billing_state || '';
            document.getElementById('billingPincode').value = data.billing_pincode || '';

            // Shipping Address
            document.getElementById('shippingAttention').value = data.shipping_attention || data.billing_attention || data.customer_name || '';
            const sGstin = document.getElementById('shippingGstin');
            if (sGstin) sGstin.value = data.gst_number || '';
            document.getElementById('shippingAddress').value = data.shipping_address || data.billing_address || '';
            document.getElementById('shippingCity').value = data.shipping_city || data.billing_city || '';
            document.getElementById('shippingState').value = data.shipping_state || data.billing_state || '';
            document.getElementById('shippingPincode').value = data.shipping_pincode || data.billing_pincode || '';

            // Try to auto-select Place of Supply if matching state
            if (data.billing_state) {
                const posSelect = document.getElementById('placeOfSupply');
                for (let i = 0; i < posSelect.options.length; i++) {
                    if (posSelect.options[i].text.toLowerCase().includes(data.billing_state.toLowerCase())) {
                        posSelect.selectedIndex = i;
                        break;
                    }
                }
            }
            calculateInvoiceTotals();
        });
}

// Copy Billing Address to Shipping Address
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

function syncGstin(val) {
    const cg = document.getElementById('customerGstin');
    if (cg) cg.value = val;
}

// Auto-calculate Due Date based on Payment Terms
function calculateDueDate() {
    const invDateVal = document.getElementById('invoiceDate').value;
    if (!invDateVal) return;

    const terms = document.getElementById('paymentTerms').value;
    const invDate = new Date(invDateVal);

    if (terms === 'Due on Receipt') {
        document.getElementById('dueDate').value = invDateVal;
    } else if (terms === 'Net 15') {
        invDate.setDate(invDate.getDate() + 15);
        document.getElementById('dueDate').value = invDate.toISOString().split('T')[0];
    } else if (terms === 'Net 30') {
        invDate.setDate(invDate.getDate() + 30);
        document.getElementById('dueDate').value = invDate.toISOString().split('T')[0];
    } else if (terms === 'Net 45') {
        invDate.setDate(invDate.getDate() + 45);
        document.getElementById('dueDate').value = invDate.toISOString().split('T')[0];
    } else if (terms === 'Net 60') {
        invDate.setDate(invDate.getDate() + 60);
        document.getElementById('dueDate').value = invDate.toISOString().split('T')[0];
    }
}

// Place of supply changed (Intra-state TN vs Inter-state IGST)
function onPlaceOfSupplyChanged() {
    calculateInvoiceTotals();
}

let hasUserManuallyEditedGstPercent = false;

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

// Line Items Management
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
    let itemMatched = false;
    catalogItems.forEach(it => {
        const isSelected = data.item_id && String(data.item_id) === String(it.id);
        if (isSelected) itemMatched = true;
        itemsOptions += `<option value="${it.id}" ${isSelected ? 'selected' : ''}>${escapeHtml(it.item_name)} (₹${parseFloat(it.rate || 0).toFixed(2)})</option>`;
    });

    if (data.item_id && !itemMatched) {
        itemsOptions += `<option value="${data.item_id}" selected>${escapeHtml(data.item_name || 'Selected Item')} (₹${parseFloat(data.rate || 0).toFixed(2)})</option>`;
    }

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
            <input type="text" name="items[${currentRowIndex}][item_name]" id="rowItemName_${currentRowIndex}" class="form-input" style="font-size:12px; font-weight:700; margin-top:6px;" value="${escapeHtml(itemName)}" placeholder="Item / Service Name" required>
        </td>
        <td>
            <textarea name="items[${currentRowIndex}][description]" id="rowDesc_${currentRowIndex}" class="form-input" rows="2" style="font-size:11px; line-height:1.4;" placeholder="Editable item description…">${escapeHtml(desc)}</textarea>
        </td>
        <td>
            <input type="text" name="items[${currentRowIndex}][hsn_sac_code]" id="rowHsn_${currentRowIndex}" class="form-input" style="font-size:12px; padding:6px 8px;" value="${escapeHtml(hsn)}" placeholder="HSN/SAC">
        </td>
        <td>
            <input type="number" step="any" min="0.01" name="items[${currentRowIndex}][qty]" id="rowQty_${currentRowIndex}" class="form-input" style="font-size:12px; padding:6px 8px; text-align:right;" value="${qty}" required oninput="calculateRow(${currentRowIndex})">
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="items[${currentRowIndex}][rate]" id="rowRate_${currentRowIndex}" class="form-input" style="font-size:12px; padding:6px 8px; text-align:right;" value="${rate}" required oninput="calculateRow(${currentRowIndex})">
        </td>
        <td style="text-align:right; font-weight:700; padding-top:16px;">
            <span id="rowAmountSpan_${currentRowIndex}">₹${parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            <input type="hidden" name="items[${currentRowIndex}][amount]" id="rowAmount_${currentRowIndex}" value="${amount}">
            <input type="hidden" name="items[${currentRowIndex}][total_amount]" id="rowTotal_${currentRowIndex}" value="${amount}">
            <input type="hidden" name="items[${currentRowIndex}][cgst_percent]" id="rowCgst_${currentRowIndex}" value="${data.cgst_percent !== undefined ? data.cgst_percent : '0.00'}">
            <input type="hidden" name="items[${currentRowIndex}][cgst_amount]" id="rowCgstAmt_${currentRowIndex}" value="${data.cgst_amount !== undefined ? data.cgst_amount : '0.00'}">
            <input type="hidden" name="items[${currentRowIndex}][sgst_percent]" id="rowSgst_${currentRowIndex}" value="${data.sgst_percent !== undefined ? data.sgst_percent : '0.00'}">
            <input type="hidden" name="items[${currentRowIndex}][sgst_amount]" id="rowSgstAmt_${currentRowIndex}" value="${data.sgst_amount !== undefined ? data.sgst_amount : '0.00'}">
            <input type="hidden" name="items[${currentRowIndex}][igst_percent]" id="rowIgst_${currentRowIndex}" value="${data.igst_percent !== undefined ? data.igst_percent : '0.00'}">
            <input type="hidden" name="items[${currentRowIndex}][igst_amount]" id="rowIgstAmt_${currentRowIndex}" value="${data.igst_amount !== undefined ? data.igst_amount : '0.00'}">
        </td>
        <td style="text-align:center; padding-top:12px;">
            <button type="button" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" onclick="removeInvoiceItemRow(${currentRowIndex})" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);

    // Initialize Select2 on the newly created row's select
    initRowSelect2(currentRowIndex);

    // Explicitly sync select value with Select2 if prefilled
    if (data.item_id && window.jQuery) {
        const $sel = $(`#rowSelect_${currentRowIndex}`);
        if ($sel.length) {
            $sel.val(String(data.item_id));
            if ($sel.data('select2')) {
                $sel.trigger('change.select2');
            }
        }
    }

    calculateRow(currentRowIndex);
    itemRowIndex++;
}

// When item is selected from Item Master dropdown in a line row
function onRowItemSelect(selectElem, rowId) {
    const itemId = selectElem.value;
    if (!itemId) return;

    const found = catalogItems.find(it => it.id == itemId);
    if (found) {
        document.getElementById('rowItemName_' + rowId).value = found.item_name || '';
        document.getElementById('rowDesc_' + rowId).value = found.description || '';
        document.getElementById('rowHsn_' + rowId).value = found.hsn_sac_code || '';
        document.getElementById('rowRate_' + rowId).value = parseFloat(found.rate || 0).toFixed(2);

        // If user hasn't manually customized the tax percentage, initialize from item master
        if (!hasUserManuallyEditedGstPercent && found.gst !== undefined && found.gst !== null && found.gst !== '') {
            const totalGst = parseFloat(found.gst) || 0;
            const halfGst = (totalGst / 2).toFixed(2);
            const cgstInput = document.getElementById('inputCgstPercent');
            const sgstInput = document.getElementById('inputSgstPercent');
            const igstInput = document.getElementById('inputIgstPercent');
            if (cgstInput) cgstInput.value = halfGst;
            if (sgstInput) sgstInput.value = halfGst;
            if (igstInput) igstInput.value = totalGst.toFixed(2);
        }

        calculateRow(rowId);
    }
}

// Remove row
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

// Calculate individual row
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

// Calculate whole invoice summary (Subtotal, taxes, roundoff, grand total, balance due)
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

// Form Submit Handler with Loading Spinner & Double Click Prevention
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

    // Add default row or restore old items if available
    @if(old('items') && is_array(old('items')) && count(old('items')) > 0)
        const oldItems = @json(old('items'));
        oldItems.forEach(it => addInvoiceItemRow(it));
    @elseif(!empty($prefillItems) && count($prefillItems) > 0)
        const prefillItems = @json($prefillItems);
        prefillItems.forEach(it => addInvoiceItemRow(it));
    @else
        addInvoiceItemRow();
    @endif

    const custSel = document.getElementById('customerSelect');
    @if(!empty($prefillCustomer))
        if (custSel) {
            custSel.value = "{{ $prefillCustomer->id }}";
            if (window.$ && $(custSel).data('select2')) {
                $(custSel).val("{{ $prefillCustomer->id }}").trigger('change');
            } else {
                onCustomerSelected("{{ $prefillCustomer->id }}");
            }
        }
    @else
        if (custSel && custSel.value) {
            onCustomerSelected(custSel.value);
        }
    @endif

    // Initial check for invoice totals
    calculateInvoiceTotals();

    const form = document.getElementById('invoiceForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (allowActualSubmit) {
                return true;
            }

            e.preventDefault();

            if (isInvoiceSubmitting) {
                return false;
            }

            openMailPreviewOrSubmit();
            return false;
        });
    }
});

// Reset on pageshow (bfcache)
window.addEventListener('pageshow', function() {
    isInvoiceSubmitting = false;
    allowActualSubmit = false;
    const btn = document.getElementById('saveInvoiceBtn');
    if (btn) {
        btn.disabled = false;
        btn.classList.remove('disabled');
        btn.style.pointerEvents = 'auto';
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Create & Issue Invoice';
    }
});

// Close modals on outside backdrop click or ESC
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            if (this.id === 'quickCustomerModal') resetQcSubmitBtn();
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
            if (modal.id === 'quickCustomerModal') resetQcSubmitBtn();
        });
    }
});

// Close lead search dropdown on outside click
document.addEventListener('click', function(e) {
    const resBox = document.getElementById('qcLeadSearchResults');
    const input = document.getElementById('qcLeadSearchInput');
    if (resBox && !resBox.contains(e.target) && e.target !== input) {
        resBox.style.display = 'none';
    }
});
</script>
@endpush
@endsection
