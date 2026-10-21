@extends('layouts.app')

@section('title', 'Create Purchase Order - PO Orders')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }

.inv-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); margin-bottom:20px; }
.inv-card-header { padding:14px 22px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; }
.inv-card-title { font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.inv-card-body { padding:22px; }

.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-grid-3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; }
.form-grid-4 { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; }

@media (max-width: 900px) {
    .form-grid-2, .form-grid-3, .form-grid-4 { grid-template-columns:1fr; }
}

.form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-label .req { color:#dc2626; margin-left:2px; }
.form-input { width:100%; height:38px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; color:#0f172a; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
textarea.form-input { height:auto; min-height:76px; resize:vertical; }

/* Items Table */
.items-table { width:100%; border-collapse:collapse; margin-top:8px; }
.items-table th { background:#f8fafc; padding:10px 12px; font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.04em; border-bottom:2px solid #e2e8f0; text-align:left; }
.items-table td { padding:10px 8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.items-table tr:hover td { background:#fafbfc; }

.btn-remove-row { width:32px; height:32px; border-radius:6px; border:1px solid #fecaca; background:#fff; color:#dc2626; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; }
.btn-remove-row:hover { background:#fee2e2; }

/* Totals Summary Box */
.summary-container { display:flex; justify-content:flex-end; margin-top:16px; }
.summary-box { width:380px; max-width:100%; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; padding:18px 20px; display:flex; flex-direction:column; gap:10px; }
.summary-row { display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#475569; font-weight:600; }
.summary-row.total-row { border-top:2px solid #e2e8f0; padding-top:12px; margin-top:4px; font-size:16px; font-weight:900; color:#0f172a; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-cart-plus-fill" style="color:#fe5f04;"></i>
                <span>Create Purchase Order</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}"><i class="bi bi-house-door"></i> Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.purchase-orders.index') }}">Purchase Orders</a>
                <span>/</span>
                <span>New</span>
            </div>
        </div>

        <div>
            <a href="{{ route('accounts.purchase-orders.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancel
            </a>
        </div>
    </div>

    <div class="inv-body">

        @if(isset($errors) && $errors->any())
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:14px 18px; border-radius:10px; font-size:13px; font-weight:600;">
                <div style="font-weight:800; margin-bottom:4px;">Please correct the errors below:</div>
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="purchaseOrderForm" method="POST" action="{{ route('accounts.purchase-orders.store') }}">
            @csrf

            {{-- Card 1: Supplier & PO Meta --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div class="inv-card-title">
                        <i class="bi bi-building" style="color:#2563eb;"></i>
                        <span>Supplier / Vendor &amp; PO Details</span>
                    </div>
                    <div>
                        <span style="font-size:12px; color:#64748b; font-weight:600;">Fields marked with <span style="color:#dc2626;">*</span> are required</span>
                    </div>
                </div>
                <div class="inv-card-body">

                    {{-- Supplier Selection --}}
                    <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:14px 18px; margin-bottom:18px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">
                                <i class="bi bi-magic" style="color:#fe5f04;"></i>
                                <span>Quick Auto-Fill: Select from Registered Suppliers / Vendors</span>
                            </label>
                            <select id="supplierSelect" class="form-input" onchange="onSupplierSelected(this)">
                                <option value="">-- Choose a Supplier or Vendor to auto-populate details --</option>
                                <optgroup label="Suppliers (Supplier Master)">
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}"
                                                data-type="supplier"
                                                data-name="{{ $sup->supplier_name }}"
                                                data-company="{{ $sup->company_name }}"
                                                data-phone="{{ $sup->mobile_number }}"
                                                data-email="{{ $sup->email }}"
                                                data-gstin="{{ $sup->gst_number }}"
                                                data-address="{{ $sup->address }}">
                                            {{ $sup->supplier_name }} {{ $sup->company_name ? "({$sup->company_name})" : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @if($vendors->isNotEmpty())
                                    <optgroup label="Vendors (Vendor Master)">
                                        @foreach($vendors as $v)
                                            <option value="{{ $v->id }}"
                                                    data-type="vendor"
                                                    data-name="{{ $v->vendor_name }}"
                                                    data-company="{{ $v->company_name }}"
                                                    data-phone="{{ $v->mobile_number }}"
                                                    data-email="{{ $v->email }}"
                                                    data-gstin="{{ $v->gst_number }}"
                                                    data-address="{{ $v->address }}">
                                                {{ $v->vendor_name }} {{ $v->company_name ? "({$v->company_name})" : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="supplier_id" id="selectedSupplierId" value="{{ old('supplier_id') }}">

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Supplier / Vendor Name <span class="req">*</span></label>
                            <input type="text" name="supplier_name" id="supplierName" class="form-input" value="{{ old('supplier_name') }}" placeholder="e.g. Acme Cloud Solutions" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" id="supplierCompany" class="form-input" value="{{ old('company_name') }}" placeholder="e.g. Acme Tech Pvt Ltd">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier Mobile / Phone</label>
                            <input type="text" name="supplier_phone" id="supplierPhone" class="form-input" value="{{ old('supplier_phone') }}" placeholder="+91 98765 43210">
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Supplier Email</label>
                            <input type="email" name="supplier_email" id="supplierEmail" class="form-input" value="{{ old('supplier_email') }}" placeholder="billing@supplier.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier GSTIN</label>
                            <input type="text" name="supplier_gstin" id="supplierGstin" class="form-input" value="{{ old('supplier_gstin') }}" placeholder="33AAAAA0000A1Z5" style="text-transform:uppercase;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier PAN</label>
                            <input type="text" name="supplier_pan" id="supplierPan" class="form-input" value="{{ old('supplier_pan') }}" placeholder="AAAAA0000A" style="text-transform:uppercase;">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Supplier Address</label>
                            <textarea name="supplier_address" id="supplierAddress" class="form-input" rows="2" placeholder="Street, Building, Area...">{{ old('supplier_address') }}</textarea>
                        </div>

                        <div class="form-grid-3">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="supplier_city" id="supplierCity" class="form-input" value="{{ old('supplier_city') }}" placeholder="Chennai">
                            </div>
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <select name="supplier_state" id="supplierState" class="form-input">
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    @foreach($states as $code => $st)
                                        @if($st !== 'Tamil Nadu')
                                            <option value="{{ $st }}" {{ old('supplier_state') === $st ? 'selected' : '' }}>{{ $st }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="supplier_pincode" id="supplierPincode" class="form-input" value="{{ old('supplier_pincode') }}" placeholder="600001">
                            </div>
                        </div>
                    </div>

                    <hr style="border:0; border-top:1px solid #f1f5f9; margin:16px 0;">

                    {{-- PO Metadata --}}
                    <div class="form-grid-4">
                        <div class="form-group">
                            <label class="form-label">PO Number <span class="req">*</span></label>
                            <input type="text" name="po_number" class="form-input" value="{{ old('po_number', $poNumber) }}" style="font-weight:700; font-family:'JetBrains Mono',monospace;" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">PO Date <span class="req">*</span></label>
                            <input type="date" name="po_date" class="form-input" value="{{ old('po_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="delivery_date" class="form-input" value="{{ old('delivery_date') }}">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select name="payment_terms" class="form-input">
                                <option value="Due on Receipt" {{ old('payment_terms') === 'Due on Receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                <option value="Net 15" {{ old('payment_terms') === 'Net 15' ? 'selected' : '' }}>Net 15 (15 Days)</option>
                                <option value="Net 30" {{ old('payment_terms', 'Net 30') === 'Net 30' ? 'selected' : '' }}>Net 30 (30 Days)</option>
                                <option value="Net 45" {{ old('payment_terms') === 'Net 45' ? 'selected' : '' }}>Net 45 (45 Days)</option>
                                <option value="Net 60" {{ old('payment_terms') === 'Net 60' ? 'selected' : '' }}>Net 60 (60 Days)</option>
                                <option value="Advance 100%" {{ old('payment_terms') === 'Advance 100%' ? 'selected' : '' }}>100% Advance Payment</option>
                                <option value="50% Advance, 50% on Delivery" {{ old('payment_terms') === '50% Advance, 50% on Delivery' ? 'selected' : '' }}>50% Advance, 50% on Delivery</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Place of Supply (GST State) <span class="req">*</span></label>
                            <select name="place_of_supply" id="placeOfSupply" class="form-input" onchange="calculatePoTotals()" required>
                                @foreach($states as $code => $st)
                                    <option value="{{ $code }}" {{ str_contains($code, '33') ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Delivery Terms</label>
                            <input type="text" name="delivery_terms" class="form-input" value="{{ old('delivery_terms', 'Free Door Delivery at Company Site') }}" placeholder="e.g. Free Door Delivery, Ex-works">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Initial PO Status</label>
                            <select name="status" class="form-input">
                                <option value="issued" {{ old('status', 'issued') === 'issued' ? 'selected' : '' }}>Issued (Directly Issue to Supplier)</option>
                                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Pending Approval)</option>
                                <option value="approved" {{ old('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Card 2: Shipping / Delivery Address --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div class="inv-card-title">
                        <i class="bi bi-geo-alt" style="color:#059669;"></i>
                        <span>Delivery / Shipping Address (Where Goods / Services Should be Delivered)</span>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Attention / Contact Person</label>
                            <input type="text" name="shipping_attention" class="form-input" value="{{ old('shipping_attention', $setting?->company_name ?: config('app.name')) }}">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Delivery Address</label>
                            <input type="text" name="shipping_address" class="form-input" value="{{ old('shipping_address', $setting?->company_address) }}" placeholder="Office / Warehouse Address">
                        </div>
                    </div>
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="shipping_city" class="form-input" value="{{ old('shipping_city', $setting?->company_city ?: 'Chennai') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" name="shipping_state" class="form-input" value="{{ old('shipping_state', $setting?->company_state ?: 'Tamil Nadu') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="shipping_pincode" class="form-input" value="{{ old('shipping_pincode', $setting?->company_pincode) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 3: Line Items --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div class="inv-card-title">
                        <i class="bi bi-list-check" style="color:#fe5f04;"></i>
                        <span>Order Items &amp; Services</span>
                    </div>
                    <div>
                        <button type="button" class="acm-btn acm-btn-secondary" onclick="addNewItemRow()" style="font-size:12px; padding:6px 12px;">
                            <i class="bi bi-plus-circle"></i> Add Item Row
                        </button>
                    </div>
                </div>
                <div class="inv-card-body" style="padding:14px 22px;">

                    {{-- Quick Catalog Picker --}}
                    @if($items->isNotEmpty())
                        <div style="background:#fff7ed; border:1px solid #ffedd5; border-radius:10px; padding:10px 14px; margin-bottom:14px; display:flex; align-items:center; gap:12px;">
                            <i class="bi bi-box-arrow-in-down" style="color:#fe5f04; font-size:18px;"></i>
                            <span style="font-size:12px; font-weight:700; color:#c2410c;">Quick Catalog Item:</span>
                            <select id="catalogItemSelect" class="form-input" style="flex:1; height:34px; font-size:12px;" onchange="onCatalogItemSelected(this)">
                                <option value="">-- Choose item to add into order rows --</option>
                                @foreach($items as $catItem)
                                    <option value="{{ $catItem->id }}"
                                            data-name="{{ $catItem->item_name }}"
                                            data-desc="{{ $catItem->description }}"
                                            data-hsn="{{ $catItem->hsn_sac_code }}"
                                            data-rate="{{ $catItem->rate }}"
                                            data-tax="{{ $catItem->tax_percentage ?? 18 }}">
                                        {{ $catItem->item_name }} (₹{{ number_format((float) $catItem->rate, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div style="overflow-x:auto;">
                        <table class="items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="min-width:240px;">Item Name &amp; Description <span class="req">*</span></th>
                                    <th style="width:130px;">HSN / SAC</th>
                                    <th style="width:100px; text-align:right;">Qty <span class="req">*</span></th>
                                    <th style="width:130px; text-align:right;">Rate (₹) <span class="req">*</span></th>
                                    <th style="width:140px; text-align:right;">Amount (₹)</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                {{-- Initial Row --}}
                                <tr id="itemRow_1">
                                    <td>
                                        <input type="hidden" name="items[1][item_id]" id="itemId_1" value="">
                                        <input type="text" name="items[1][item_name]" id="itemName_1" class="form-input" style="font-weight:700;" placeholder="Item / Service Name" required>
                                        <textarea name="items[1][description]" id="itemDesc_1" class="form-input" rows="1" style="margin-top:6px; font-size:12px; min-height:40px;" placeholder="Specifications, scope, or item notes..."></textarea>
                                    </td>
                                    <td>
                                        <input type="text" name="items[1][hsn_sac_code]" id="itemHsn_1" class="form-input" placeholder="e.g. 998313">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[1][qty]" id="itemQty_1" class="form-input" style="text-align:right; font-weight:700;" value="1" oninput="calculateRowAmount(1)" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[1][rate]" id="itemRate_1" class="form-input" style="text-align:right; font-weight:700;" value="0.00" oninput="calculateRowAmount(1)" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="items[1][amount]" id="itemAmount_1" class="form-input" style="text-align:right; font-weight:800; background:#f8fafc;" value="0.00" readonly>
                                        <input type="hidden" name="items[1][total_amount]" id="itemTotal_1" value="0.00">
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-remove-row" onclick="removeRow(1)" title="Remove item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:14px;">
                        <button type="button" class="acm-btn acm-btn-secondary" onclick="addNewItemRow()" style="font-size:12px;">
                            <i class="bi bi-plus-lg"></i> Add Another Item
                        </button>
                    </div>

                    {{-- Totals Summary Section --}}
                    <div class="summary-container">
                        <div class="summary-box">
                            <div class="summary-row">
                                <span>Sub Total</span>
                                <span id="dispSubtotal" style="font-weight:800; color:#0f172a;">₹0.00</span>
                                <input type="hidden" name="subtotal" id="inputSubtotal" value="0.00">
                            </div>

                            {{-- Intra-State GST (CGST + SGST) --}}
                            <div id="cgstRow" class="summary-row">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>CGST</span>
                                    <input type="number" step="0.5" id="cgstPercent" class="form-input" style="width:65px; height:28px; padding:2px 6px; text-align:right; font-weight:700;" value="9" oninput="calculatePoTotals()">
                                    <span>%</span>
                                </div>
                                <span id="dispCgst">₹0.00</span>
                                <input type="hidden" name="total_cgst" id="inputCgst" value="0.00">
                            </div>

                            <div id="sgstRow" class="summary-row">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>SGST</span>
                                    <input type="number" step="0.5" id="sgstPercent" class="form-input" style="width:65px; height:28px; padding:2px 6px; text-align:right; font-weight:700;" value="9" oninput="calculatePoTotals()">
                                    <span>%</span>
                                </div>
                                <span id="dispSgst">₹0.00</span>
                                <input type="hidden" name="total_sgst" id="inputSgst" value="0.00">
                            </div>

                            {{-- Inter-State GST (IGST) --}}
                            <div id="igstRow" class="summary-row" style="display:none;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span>IGST</span>
                                    <input type="number" step="0.5" id="igstPercent" class="form-input" style="width:65px; height:28px; padding:2px 6px; text-align:right; font-weight:700;" value="18" oninput="calculatePoTotals()">
                                    <span>%</span>
                                </div>
                                <span id="dispIgst">₹0.00</span>
                                <input type="hidden" name="total_igst" id="inputIgst" value="0.00">
                            </div>

                            <input type="hidden" name="total_tax" id="inputTotalTax" value="0.00">

                            <div class="summary-row" style="align-items:center;">
                                <span>Round Off (+ / -)</span>
                                <input type="number" step="0.01" name="round_off" id="inputRoundOff" class="form-input" style="width:90px; height:28px; padding:2px 6px; text-align:right; font-weight:700;" value="0.00" oninput="onManualRoundOffChange()">
                            </div>

                            <div class="summary-row total-row">
                                <span>Total PO Amount</span>
                                <span id="dispGrandTotal" style="color:#fe5f04;">₹0.00</span>
                                <input type="hidden" name="total_amount" id="inputGrandTotal" value="0.00">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Card 4: Terms & Conditions and Notes --}}
            <div class="inv-card">
                <div class="inv-card-header">
                    <div class="inv-card-title">
                        <i class="bi bi-journal-text" style="color:#7c3aed;"></i>
                        <span>Terms &amp; Conditions and Notes</span>
                    </div>
                </div>
                <div class="inv-card-body">
                    <div class="form-group">
                        <label class="form-label">Terms &amp; Conditions (Printed on Purchase Order)</label>
                        <textarea id="poTermsEditor" name="terms_conditions" class="form-input" rows="6">{{ old('terms_conditions', $defaultTerms) }}</textarea>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Special Notes / Internal Instructions</label>
                        <textarea name="notes" class="form-input" rows="3" placeholder="Additional instructions for warehouse, receiving team or supplier...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Footer Action Bar --}}
            <div style="background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:16px 24px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 6px rgba(0,0,0,.02);">
                <a href="{{ route('accounts.purchase-orders.index') }}" class="acm-btn acm-btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="acm-btn acm-btn-primary" id="btnSubmitPo">
                    <i class="bi bi-check2-circle"></i> Save &amp; Issue Purchase Order
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

@push('scripts')
{{-- TinyMCE --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>

<script>
let rowCounter = 1;
let isManualRoundOff = false;

document.addEventListener('DOMContentLoaded', function() {
    if (window.tinymce) {
        window.tinymce.init({
            selector: '#poTermsEditor',
            height: 220,
            menubar: false,
            plugins: 'lists link code',
            toolbar: 'undo redo | bold italic underline | bullist numlist | removeformat',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 13px; color: #1e293b; line-height: 1.6; }'
        });
    }

    calculateRowAmount(1);
});

// Auto-fill Supplier from dropdown
function onSupplierSelected(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    document.getElementById('selectedSupplierId').value = opt.value;
    document.getElementById('supplierName').value = opt.getAttribute('data-name') || '';
    document.getElementById('supplierCompany').value = opt.getAttribute('data-company') || '';
    document.getElementById('supplierPhone').value = opt.getAttribute('data-phone') || '';
    document.getElementById('supplierEmail').value = opt.getAttribute('data-email') || '';
    document.getElementById('supplierGstin').value = opt.getAttribute('data-gstin') || '';
    document.getElementById('supplierAddress').value = opt.getAttribute('data-address') || '';

    // Auto-detect PAN from GSTIN if 15 chars
    const gstin = opt.getAttribute('data-gstin') || '';
    if (gstin.length >= 12) {
        document.getElementById('supplierPan').value = gstin.substring(2, 12);
    }
}

// Add catalog item directly into a new row
function onCatalogItemSelected(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    rowCounter++;
    const tbody = document.getElementById('itemsTableBody');
    const tr = document.createElement('tr');
    tr.id = 'itemRow_' + rowCounter;
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items[${rowCounter}][item_id]" id="itemId_${rowCounter}" value="${opt.value}">
            <input type="text" name="items[${rowCounter}][item_name]" id="itemName_${rowCounter}" class="form-input" style="font-weight:700;" value="${opt.getAttribute('data-name') || ''}" required>
            <textarea name="items[${rowCounter}][description]" id="itemDesc_${rowCounter}" class="form-input" rows="1" style="margin-top:6px; font-size:12px; min-height:40px;">${opt.getAttribute('data-desc') || ''}</textarea>
        </td>
        <td>
            <input type="text" name="items[${rowCounter}][hsn_sac_code]" id="itemHsn_${rowCounter}" class="form-input" value="${opt.getAttribute('data-hsn') || ''}">
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="items[${rowCounter}][qty]" id="itemQty_${rowCounter}" class="form-input" style="text-align:right; font-weight:700;" value="1" oninput="calculateRowAmount(${rowCounter})" required>
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="items[${rowCounter}][rate]" id="itemRate_${rowCounter}" class="form-input" style="text-align:right; font-weight:700;" value="${opt.getAttribute('data-rate') || '0.00'}" oninput="calculateRowAmount(${rowCounter})" required>
        </td>
        <td>
            <input type="number" step="0.01" name="items[${rowCounter}][amount]" id="itemAmount_${rowCounter}" class="form-input" style="text-align:right; font-weight:800; background:#f8fafc;" value="0.00" readonly>
            <input type="hidden" name="items[${rowCounter}][total_amount]" id="itemTotal_${rowCounter}" value="0.00">
        </td>
        <td style="text-align:center;">
            <button type="button" class="btn-remove-row" onclick="removeRow(${rowCounter})" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);

    calculateRowAmount(rowCounter);
    sel.value = '';
}

// Add blank row
function addNewItemRow() {
    rowCounter++;
    const tbody = document.getElementById('itemsTableBody');
    const tr = document.createElement('tr');
    tr.id = 'itemRow_' + rowCounter;
    tr.innerHTML = `
        <td>
            <input type="hidden" name="items[${rowCounter}][item_id]" id="itemId_${rowCounter}" value="">
            <input type="text" name="items[${rowCounter}][item_name]" id="itemName_${rowCounter}" class="form-input" style="font-weight:700;" placeholder="Item / Service Name" required>
            <textarea name="items[${rowCounter}][description]" id="itemDesc_${rowCounter}" class="form-input" rows="1" style="margin-top:6px; font-size:12px; min-height:40px;" placeholder="Specifications or scope..."></textarea>
        </td>
        <td>
            <input type="text" name="items[${rowCounter}][hsn_sac_code]" id="itemHsn_${rowCounter}" class="form-input" placeholder="HSN/SAC">
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="items[${rowCounter}][qty]" id="itemQty_${rowCounter}" class="form-input" style="text-align:right; font-weight:700;" value="1" oninput="calculateRowAmount(${rowCounter})" required>
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="items[${rowCounter}][rate]" id="itemRate_${rowCounter}" class="form-input" style="text-align:right; font-weight:700;" value="0.00" oninput="calculateRowAmount(${rowCounter})" required>
        </td>
        <td>
            <input type="number" step="0.01" name="items[${rowCounter}][amount]" id="itemAmount_${rowCounter}" class="form-input" style="text-align:right; font-weight:800; background:#f8fafc;" value="0.00" readonly>
            <input type="hidden" name="items[${rowCounter}][total_amount]" id="itemTotal_${rowCounter}" value="0.00">
        </td>
        <td style="text-align:center;">
            <button type="button" class="btn-remove-row" onclick="removeRow(${rowCounter})" title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    calculateRowAmount(rowCounter);
}

// Remove row
function removeRow(id) {
    const rows = document.querySelectorAll('#itemsTableBody tr');
    if (rows.length <= 1) {
        alert('Purchase Order must have at least one line item.');
        return;
    }
    const tr = document.getElementById('itemRow_' + id);
    if (tr) tr.remove();
    calculatePoTotals();
}

// Calculate individual row amount (qty * rate)
function calculateRowAmount(id) {
    const qty = parseFloat(document.getElementById('itemQty_' + id)?.value) || 0;
    const rate = parseFloat(document.getElementById('itemRate_' + id)?.value) || 0;
    const amt = Math.round(qty * rate * 100) / 100;

    const amtField = document.getElementById('itemAmount_' + id);
    const totField = document.getElementById('itemTotal_' + id);
    if (amtField) amtField.value = amt.toFixed(2);
    if (totField) totField.value = amt.toFixed(2);

    calculatePoTotals();
}

function onManualRoundOffChange() {
    isManualRoundOff = true;
    calculatePoTotals();
}

// Calculate whole PO summary
function calculatePoTotals() {
    let subtotal = 0;
    document.querySelectorAll('#itemsTableBody tr').forEach(tr => {
        const id = tr.id.replace('itemRow_', '');
        const amt = parseFloat(document.getElementById('itemAmount_' + id)?.value) || 0;
        subtotal += amt;
    });

    const posSelect = document.getElementById('placeOfSupply');
    const isTN = (posSelect?.value || '').includes('33');

    const cgstRow = document.getElementById('cgstRow');
    const sgstRow = document.getElementById('sgstRow');
    const igstRow = document.getElementById('igstRow');

    let totalCgst = 0;
    let totalSgst = 0;
    let totalIgst = 0;

    if (isTN) {
        cgstRow.style.display = 'flex';
        sgstRow.style.display = 'flex';
        igstRow.style.display = 'none';

        const cgstPct = parseFloat(document.getElementById('cgstPercent')?.value) || 0;
        const sgstPct = parseFloat(document.getElementById('sgstPercent')?.value) || 0;

        totalCgst = Math.round((subtotal * cgstPct / 100) * 100) / 100;
        totalSgst = Math.round((subtotal * sgstPct / 100) * 100) / 100;
        totalIgst = 0;
    } else {
        cgstRow.style.display = 'none';
        sgstRow.style.display = 'none';
        igstRow.style.display = 'flex';

        const igstPct = parseFloat(document.getElementById('igstPercent')?.value) || 0;
        totalIgst = Math.round((subtotal * igstPct / 100) * 100) / 100;
        totalCgst = 0;
        totalSgst = 0;
    }

    const totalTax = totalCgst + totalSgst + totalIgst;
    const rawTotal = subtotal + totalTax;

    let roundOff = 0;
    const roundOffElem = document.getElementById('inputRoundOff');
    if (!isManualRoundOff) {
        const rounded = Math.round(rawTotal);
        roundOff = Math.round((rounded - rawTotal) * 100) / 100;
        if (roundOffElem) roundOffElem.value = roundOff.toFixed(2);
    } else {
        roundOff = parseFloat(roundOffElem?.value) || 0;
    }

    const grandTotal = Math.max(0, rawTotal + roundOff);

    // Update UI
    document.getElementById('dispSubtotal').textContent = '₹' + subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('inputSubtotal').value = subtotal.toFixed(2);

    document.getElementById('dispCgst').textContent = '₹' + totalCgst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('inputCgst').value = totalCgst.toFixed(2);

    document.getElementById('dispSgst').textContent = '₹' + totalSgst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('inputSgst').value = totalSgst.toFixed(2);

    document.getElementById('dispIgst').textContent = '₹' + totalIgst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('inputIgst').value = totalIgst.toFixed(2);

    document.getElementById('inputTotalTax').value = totalTax.toFixed(2);

    document.getElementById('dispGrandTotal').textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('inputGrandTotal').value = grandTotal.toFixed(2);
}

// Form submit validation & spinner
document.getElementById('purchaseOrderForm').addEventListener('submit', function(e) {
    if (window.tinymce) {
        window.tinymce.triggerSave();
    }

    const submitBtn = document.getElementById('btnSubmitPo');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Creating Purchase Order...';
    }
});
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
