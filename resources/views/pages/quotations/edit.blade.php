@extends('layouts.app')

@section('title', 'Edit Quotation - ' . $quotation->quotation_no)

@push('styles')
{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
/* ── Layout ─────────────────────────────────────────────── */
.page-wrapper   { padding: 28px 32px; max-width: 100%; }
.page-header    { display:flex; justify-space-between; align-items:center; margin-bottom:24px; }
.page-title     { font-size:22px; font-weight:700; color:#121212; }
.back-link      { color:#9e9e9e; font-size:13px; text-decoration:none; }
.back-link:hover{ color:#fe5f04; }

/* ── Card ────────────────────────────────────────────────── */
.form-card {
    background:#fff; border:1px solid #e1dee3;
    border-radius:14px; padding:28px; margin-bottom:20px;
}
.form-card-title{
    font-size:15px; font-weight:600; color:#121212;
    margin-bottom:20px; padding-bottom:10px;
    border-bottom:1px solid #f0f0f0;
    display:flex; align-items:center; gap:8px;
}
.form-card-title i{ color:#fe5f04; }

/* ── Grid ────────────────────────────────────────────────── */
.grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
.grid-4{ display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:16px; }
.address-grid{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; align-items:stretch; }
.address-grid.same-address-mode{ grid-template-columns:minmax(0,1fr); }

/* ── Inputs ──────────────────────────────────────────────── */
.form-group       { display:flex; flex-direction:column; gap:6px; }
.form-label       { font-size:12px; font-weight:600; color:#6e6e6e; }
.form-control {
    border:1px solid #e1dee3; border-radius:10px;
    padding:9px 14px; font-size:13px; color:#121212;
    font-family:inherit; background:#fcfcfc;
    transition:border-color .2s;
    width:100%;
}
.form-control:focus{ outline:none; border-color:#fe5f04; background:#fff; }
textarea.form-control{ resize:vertical; min-height:80px; }
.input-readonly{ background:#f4f4f4; color:#888; cursor:not-allowed; }
.address-card{ padding:24px; margin-bottom:0; }
.address-card .form-card-title{ margin-bottom:16px; }
.address-card textarea.form-control{ min-height:132px; background:#fff; }
.address-meta{ margin-top:-4px; margin-bottom:10px; font-size:12px; color:#8a8a8a; line-height:1.5; }
.address-toggle-card{
    display:flex; flex-direction:column; gap:10px; margin-top:14px; padding:12px 14px;
    border:1px dashed #ffd0b3; border-radius:14px; background:linear-gradient(180deg,#fff9f5 0%,#fff 100%);
}
.address-toggle-title{
    font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#a46a47;
}
.address-toggle-row{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
}
.address-toggle-copy{
    font-size:12px; line-height:1.5; color:#7b6a5e;
}
.address-switch{
    position:relative; width:42px; height:24px; flex-shrink:0;
}
.address-switch input{ position:absolute; inset:0; opacity:0; cursor:pointer; }
.address-slider{
    position:absolute; inset:0; border-radius:999px; background:#fed7aa; transition:background .2s ease;
}
.address-slider::after{
    content:''; position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%;
    background:#fff; box-shadow:0 4px 12px rgba(15,23,42,.16); transition:transform .2s ease;
}
.address-switch input:checked + .address-slider{ background:#fb923c; }
.address-switch input:checked + .address-slider::after{ transform:translateX(18px); }
.ship-address-section.is-hidden{ display:none; }

/* ── Products Table ──────────────────────────────────────── */
.products-table-wrapper{ overflow-x:auto; }
table.products-table{ width:100%; border-collapse:collapse; }
table.products-table th{
    padding:10px 12px; font-size:11px; font-weight:600;
    color:#7c7c7c; background:#f8f8f8;
    border-bottom:1px solid #e1dee3; text-align:left;
}
table.products-table td{
    padding:8px 8px; vertical-align:middle;
    border-bottom:1px solid #f4f4f4;
}
table.products-table tbody tr:last-child td{ border-bottom:none; }
.td-product  { min-width:220px; }
.td-desc     { min-width:180px; }
.td-qty      { width:90px; }
.td-price    { width:120px; }
.td-disc     { width:110px; }
.td-total    { width:120px; text-align:right; }
.td-del      { width:44px; text-align:center; }

.row-total-val{ font-weight:600; color:#121212; font-size:13px; }

/* ── Totals ──────────────────────────────────────────────── */
.totals-row{ display:flex; justify-content:flex-end; margin-top:18px; }
.totals-box{
    border:1px solid #e1dee3; border-radius:12px;
    padding:16px 24px; min-width:280px;
    display:flex; flex-direction:column; gap:10px;
}
.totals-line{ display:flex; justify-content:space-between; font-size:13px; color:#444; }
.totals-line.grand{
    font-size:16px; font-weight:700; color:#121212;
    padding-top:10px; border-top:1px solid #e1dee3;
}

/* ── Buttons ─────────────────────────────────────────────── */
.btn-add-row{
    display:inline-flex; align-items:center; gap:6px;
    background:#fff8f4; color:#fe5f04;
    border:1px dashed #ffc19e; border-radius:10px;
    padding:7px 16px; font-size:13px; font-weight:600;
    cursor:pointer; margin-top:14px;
}
.btn-add-row:hover{ background:#ffe8d6; }
.btn-del-row{
    background:none; border:none; color:#ccc;
    cursor:pointer; font-size:18px; line-height:1;
    padding:4px;
}
.btn-del-row:hover{ color:#e53935; }
.btn-submit{
    display:inline-flex; align-items:center; gap:8px;
    background:#fe5f04; color:#fff; border:none;
    padding:10px 28px; border-radius:20px;
    font-size:14px; font-weight:600; cursor:pointer;
    font-family:inherit;
}
.btn-submit:hover{ background:#e05400; }
.form-footer{
    display:flex; justify-content:flex-end;
    gap:12px; margin-top:24px;
}
.btn-cancel{
    display:inline-flex; align-items:center; gap:8px;
    background:#f4f4f4; color:#555; border:none;
    padding:10px 24px; border-radius:20px;
    font-size:14px; font-weight:500; cursor:pointer;
    text-decoration:none; font-family:inherit;
}

/* ── Select2 overrides ───────────────────────────────────── */
.select2-container--default .select2-selection--single{
    border:1px solid #e1dee3; border-radius:10px;
    height:38px; background:#fcfcfc;
    display:flex; align-items:center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:36px; padding-left:14px; color:#121212; font-size:13px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow{ height:36px; }
.select2-dropdown{ border:1px solid #e1dee3; border-radius:10px; box-shadow:0 4px 16px rgba(0,0,0,.08); }
.select2-container--default .select2-results__option--highlighted[aria-selected]{
    background-color:#fe5f04;
}

/* ── Validation errors ───────────────────────────────────── */
.is-invalid{ border-color:#e53935 !important; }
.error-text{ color:#e53935; font-size:11px; margin-top:2px; }
@media (max-width: 992px){
    .address-grid{ grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <a href="{{ route('quotations.show', $quotation) }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Quotation
            </a>
            <div class="page-title" style="margin-top:4px">Edit Quotation - {{ $quotation->quotation_no }}</div>
        </div>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
    <div style="background:#fff5f5;border:1px solid #ffc4c4;border-radius:10px;padding:14px 18px;margin-bottom:20px;color:#c00;font-size:13px;">
        <strong>Please fix the following errors:</strong>
        <ul style="margin:8px 0 0 16px; padding:0">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('quotations.update', $quotation) }}" id="quotationForm">
        @csrf
        @method('PUT')

        {{-- ── Section 1: Quotation Info ── --}}
        <div class="form-card">
            <div class="form-card-title">
                <i class="bi bi-file-earmark-text-fill"></i> Quotation Details
            </div>

            <div class="grid-4" style="margin-bottom:16px">
                {{-- Quotation No --}}
                <div class="form-group">
                    <label class="form-label">Quotation Number</label>
                    <input type="text" class="form-control input-readonly"
                           value="{{ $quotation->quotation_no }}" readonly>
                </div>

                {{-- Date --}}
                <div class="form-group">
                    <label class="form-label">Quotation Date <span style="color:#e53935">*</span></label>
                    <input type="date" name="quotation_date" class="form-control @error('quotation_date') is-invalid @enderror"
                           value="{{ old('quotation_date', $quotation->quotation_date->format('Y-m-d')) }}" required>
                    @error('quotation_date')<div class="error-text">{{ $message }}</div>@enderror
                </div>

                {{-- Valid Until --}}
                <div class="form-group">
                    <label class="form-label">Valid Until <span style="color:#e53935">*</span></label>
                    <input type="date" name="valid_until" class="form-control @error('valid_until') is-invalid @enderror"
                           value="{{ old('valid_until', $quotation->valid_until->format('Y-m-d')) }}" required>
                    @error('valid_until')<div class="error-text">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Client GST Number</label>
                    <input type="text" name="gst_number" id="gstNumberInput" class="form-control @error('gst_number') is-invalid @enderror"
                           value="{{ old('gst_number', $quotation->gst_number) }}" placeholder="33ABCDE1234F1Z5" maxlength="20">
                    @error('gst_number')<div class="error-text">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Customer State <span style="color:#e53935">*</span></label>
                    <select name="customer_state" id="customerStateInput" class="form-control @error('customer_state') is-invalid @enderror" required>
                        @foreach(\App\Models\Quotation::INDIAN_STATES as $state)
                            <option value="{{ $state }}" {{ old('customer_state', $quotation->customer_state ?: 'Tamil Nadu') === $state ? 'selected' : '' }}>{{ $state }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" id="sellerStateInput" value="{{ $quotation->seller_state ?: 'Tamil Nadu' }}">
                    @error('customer_state')<div class="error-text">{{ $message }}</div>@enderror
                </div>
            </div>

            @php
                $isSameAddress = empty($quotation->ship_to_address) || $quotation->ship_to_address === $quotation->bill_to_address;
            @endphp

            <div class="address-grid {{ $isSameAddress ? 'same-address-mode' : '' }}" id="addressGrid" style="margin-bottom:16px">
                <div class="form-card address-card">
                    <div class="form-card-title">
                        <i class="bi bi-sticky-fill"></i> Bill To Address
                    </div>
                    <div class="form-group">
                        <div class="address-meta">Enter the billing address exactly as it should appear on the quotation.</div>
                        <textarea name="bill_to_address" id="billToAddress" class="form-control" placeholder="Address">{{ old('bill_to_address', $quotation->bill_to_address) }}</textarea>
                    </div>
                    <div class="address-toggle-card">
                        <div class="address-toggle-title">Shipping Preference</div>
                        <div class="address-toggle-row">
                            <div class="address-toggle-copy">Same as billing address</div>
                            <label class="address-switch" for="sameAsBillingToggle">
                                <input type="checkbox" id="sameAsBillingToggle" name="same_as_billing" value="1" {{ old('same_as_billing', $isSameAddress ? '1' : '0') == '1' ? 'checked' : '' }}>
                                <span class="address-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-card address-card ship-address-section {{ $isSameAddress ? 'is-hidden' : '' }}" id="shipAddressSection">
                    <div class="form-card-title">
                        <i class="bi bi-sticky-fill"></i> Ship To Address
                    </div>
                    <div class="form-group">
                        <div class="address-meta">Use a separate shipping destination only when delivery differs from billing.</div>
                        <textarea name="ship_to_address" id="shipToAddress" class="form-control" placeholder="Address">{{ old('ship_to_address', $quotation->ship_to_address) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Section 2: Products ── --}}
        <div class="form-card">
            <div class="form-card-title">
                <i class="bi bi-box-seam-fill"></i> Products / Services
            </div>

            {{-- Embed products as JS data --}}
            <script id="productsData" type="application/json">
                {!! json_encode($products->map(fn($p) => [
                    'id'          => $p->id,
                    'text'        => $p->product_name,
                    'description' => $p->description ?? '',
                    'price'       => $p->base_price,
                    'discount_type'  => $p->discount_type ?? 'fixed',
                    'discount_value' => (float) ($p->discount_value ?? 0),
                ])) !!}
            </script>

            {{-- Embed existing items as JS data --}}
            <script id="existingItemsData" type="application/json">
                {!! json_encode($quotation->items->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'description' => $item->description ?? '',
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'discount' => (float) $item->discount,
                ])) !!}
            </script>

            <div class="products-table-wrapper">
                <table class="products-table" id="productsTable">
                    <thead>
                        <tr>
                            <th class="td-product">Product</th>
                            <th class="td-desc">Description</th>
                            <th class="td-qty">Qty</th>
                            <th class="td-price">Unit Price (₹)</th>
                            <th class="td-disc">Discount (₹)</th>
                            <th class="td-total">Total (₹)</th>
                            <th class="td-del"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        {{-- rows injected by JS --}}
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn-add-row" id="addRowBtn">
                <i class="bi bi-plus-circle"></i> Add Product
            </button>

            {{-- Totals --}}
            <div class="totals-row">
                <div class="totals-box">
                    <div class="totals-line">
                        <span>Subtotal</span>
                        <span id="displaySubtotal">₹0.00</span>
                    </div>
                    <div class="totals-line" id="cgstLine">
                        <span>CGST (<span id="displayTaxPctCgst">0</span>%)</span>
                        <span id="displayTaxAmtCgst">₹0.00</span>
                    </div>
                    <div class="totals-line" id="sgstLine">
                        <span>SGST (<span id="displayTaxPctSgst">0</span>%)</span>
                        <span id="displayTaxAmtSgst">₹0.00</span>
                    </div>
                    <div class="totals-line" id="igstLine" style="display:none;">
                        <span>IGST (<span id="displayTaxPctIgst">0</span>%)</span>
                        <span id="displayTaxAmtIgst">₹0.00</span>
                    </div>
                    <div class="totals-line grand">
                        <span>Grand Total</span>
                        <span id="displayTotal">₹0.00</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Section 3: Notes ── --}}
        <div class="grid-2" style="margin-bottom:16px">
            <div class="form-card">
                <div class="form-card-title">
                    <i class="bi bi-sticky-fill"></i> Notes / Terms
                </div>
                <div class="form-group">
                    <textarea name="notes" class="form-control" placeholder="Optional notes or payment terms…">{{ old('notes', $quotation->notes) }}</textarea>
                    <input type="hidden" name="lead_id" value="{{ $quotation->lead_id }}">
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="form-footer">
            <a href="{{ route('quotations.show', $quotation) }}" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="bi bi-save2-fill"></i> Update Quotation
            </button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
    const PRODUCTS = JSON.parse(document.getElementById('productsData').textContent);
    const EXISTING_ITEMS = JSON.parse(document.getElementById('existingItemsData').textContent || '[]');
    const GST_RATE = 18;
    const productMap = {};
    PRODUCTS.forEach(p => { productMap[p.id] = p; });

    const fmt  = n => '₹' + parseFloat(n || 0).toFixed(2);
    const fnum = n => parseFloat(n || 0);

    function getRow(rowOrElement) {
        return $(rowOrElement).closest('tr');
    }

    function getDiscountAmount(row) {
        const qty = fnum(row.find('.item-qty').val());
        const price = fnum(row.find('.item-price').val());
        const enteredDiscount = fnum(row.find('.item-disc').val());
        const discountMode = row.data('discount-mode');
        const discountType = row.data('product-discount-type');
        const discountValue = fnum(row.data('product-discount-value'));

        if (discountMode === 'product') {
            if (discountType === 'percentage') {
                return (qty * price) * (discountValue / 100);
            }
            return discountValue;
        }

        return enteredDiscount;
    }

    function syncDiscountInput(row) {
        if (row.data('discount-mode') !== 'product') {
            return;
        }
        row.find('.item-disc').val(getDiscountAmount(row).toFixed(2));
    }

    let isAddingRow = false;

    function addRow(data = {}) {
        if (isAddingRow) return;
        isAddingRow = true;

        const idx = Date.now() + Math.floor(Math.random() * 1000);

        const row = $(`
            <tr id="item-row-${idx}">
                <td class="td-product">
                    <select name="items[${idx}][product_id]"
                            class="product-select"
                            data-row="${idx}" required>
                        <option value="">— Select —</option>
                        ${PRODUCTS.map(p => `<option value="${p.id}"
                            ${data.product_id == p.id ? 'selected' : ''}
                            data-desc="${p.description || ''}"
                            data-price="${p.price}">${p.text}</option>`).join('')}
                    </select>
                </td>
                <td class="td-desc">
                    <input type="text" name="items[${idx}][description]"
                           class="form-control item-desc" style="min-width:160px"
                           value="${data.description || ''}" placeholder="Description">
                </td>
                <td class="td-qty">
                    <input type="number" name="items[${idx}][qty]"
                           class="form-control item-qty" min="0.01" step="any"
                           value="${data.qty || 1}" required>
                </td>
                <td class="td-price">
                    <input type="number" name="items[${idx}][unit_price]"
                           class="form-control item-price input-readonly" min="0" step="any"
                           value="${data.unit_price !== undefined ? data.unit_price : ''}" placeholder="0.00" required readonly>
                </td>
                <td class="td-disc">
                    <input type="number" name="items[${idx}][discount]"
                           class="form-control item-disc" min="0" step="any"
                           value="${data.discount !== undefined ? data.discount : 0}">
                </td>
                <td class="td-total" style="text-align:right">
                    <span class="row-total-val" id="row-total-${idx}">₹0.00</span>
                </td>
                <td class="td-del">
                    <button type="button" class="btn-del-row" data-row="${idx}"
                            title="Remove row">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </td>
            </tr>
        `);

        $('#itemsBody').append(row);
        row.data('discount-mode', data.discount_mode || 'manual');
        row.data('product-discount-type', data.product_discount_type || 'fixed');
        row.data('product-discount-value', fnum(data.product_discount_value || 0));

        setTimeout(() => {
            row.find('.product-select').select2({
                placeholder: '— Select product —',
                allowClear: true,
                width: '100%',
            });
            isAddingRow = false;
        }, 0);

        if (data.product_id) recalcRow(row);

        return idx;
    }

    function recalcRow(el) {
        const row = getRow(el);
        const qty   = fnum(row.find('.item-qty').val());
        const price = fnum(row.find('.item-price').val());
        const disc  = getDiscountAmount(row);

        syncDiscountInput(row);

        const total = Math.max((qty * price) - disc, 0);

        row.find('.row-total-val').text(fmt(total));
        recalcTotals();
    }

    function isTamilNaduState(stateStr) {
        if (!stateStr) return false;
        const clean = stateStr.toString().trim().toLowerCase().replace(/[\s\-_]/g, '');
        return clean === 'tamilnadu' || clean === 'tn';
    }

    function recalcTotals(el = null) {
        let container = el
            ? $(el).closest('.products-table-wrapper')
            : $('.products-table-wrapper');

        let subtotal = 0;

        container.find('.row-total-val').each(function () {
            subtotal += parseFloat($(this).text().replace('₹', '') || 0);
        });

        const customerState = ($('#customerStateInput').val() || '').trim();
        const isTamilNaduSale = isTamilNaduState(customerState);
        const cgstRate = isTamilNaduSale ? (GST_RATE / 2) : 0;
        const sgstRate = isTamilNaduSale ? (GST_RATE / 2) : 0;
        const igstRate = isTamilNaduSale ? 0 : GST_RATE;
        const cgstAmount = subtotal * (cgstRate / 100);
        const sgstAmount = subtotal * (sgstRate / 100);
        const igstAmount = subtotal * (igstRate / 100);
        const grand = subtotal + cgstAmount + sgstAmount + igstAmount;

        $('#displaySubtotal').text(fmt(subtotal));
        $('#displayTaxPctCgst').text(cgstRate.toFixed(2));
        $('#displayTaxPctSgst').text(sgstRate.toFixed(2));
        $('#displayTaxPctIgst').text(igstRate.toFixed(2));
        $('#displayTaxAmtCgst').text(fmt(cgstAmount));
        $('#displayTaxAmtSgst').text(fmt(sgstAmount));
        $('#displayTaxAmtIgst').text(fmt(igstAmount));
        $('#cgstLine').toggle(isTamilNaduSale);
        $('#sgstLine').toggle(isTamilNaduSale);
        $('#igstLine').toggle(!isTamilNaduSale);
        $('#displayTotal').text(fmt(grand));
    }

    $(document).on('change', '.product-select', function () {
        const row = $(this).closest('tr');
        const pid  = $(this).val();
        const prod = productMap[pid];

        if (prod) {
            if (!row.find('.item-desc').val()) {
                row.find('.item-desc').val(prod.description);
            }
            row.find('.item-price').val(parseFloat(prod.price).toFixed(2));
            row.data('discount-mode', 'product');
            row.data('product-discount-type', prod.discount_type || 'fixed');
            row.data('product-discount-value', fnum(prod.discount_value || 0));
            syncDiscountInput(row);
        } else {
            row.find('.item-desc').val('');
            row.find('.item-price').val('');
            row.find('.item-disc').val('0.00');
            row.data('discount-mode', 'manual');
            row.data('product-discount-type', 'fixed');
            row.data('product-discount-value', 0);
        }

        row.find('.item-price').trigger('change');
        recalcRow(row);
    });

    $(document).on('input change', '.item-qty, .item-price, .item-disc', function () {
        const row = $(this).closest('tr');
        if ($(this).hasClass('item-disc')) {
            row.data('discount-mode', 'manual');
        }
        recalcRow(this);
    });

    $('#customerStateInput').on('change', recalcTotals);
    $('#gstNumberInput').on('input', function () {
        const gstin = ($(this).val() || '').replace(/[^0-9A-Za-z]/g, '').toUpperCase();
        $(this).val(gstin);

        const stateMap = {
            '01': 'Jammu and Kashmir', '02': 'Himachal Pradesh', '03': 'Punjab', '04': 'Chandigarh',
            '05': 'Uttarakhand', '06': 'Haryana', '07': 'Delhi', '08': 'Rajasthan',
            '09': 'Uttar Pradesh', '10': 'Bihar', '11': 'Sikkim', '12': 'Arunachal Pradesh',
            '13': 'Nagaland', '14': 'Manipur', '15': 'Mizoram', '16': 'Tripura',
            '17': 'Meghalaya', '18': 'Assam', '19': 'West Bengal', '20': 'Jharkhand',
            '21': 'Odisha', '22': 'Chhattisgarh', '23': 'Madhya Pradesh', '24': 'Gujarat',
            '26': 'Dadra and Nagar Haveli and Daman and Diu', '27': 'Maharashtra', '29': 'Karnataka',
            '30': 'Goa', '31': 'Lakshadweep', '32': 'Kerala', '33': 'Tamil Nadu',
            '34': 'Puducherry', '36': 'Telangana', '37': 'Andhra Pradesh', '38': 'Ladakh'
        };

        if (gstin.length >= 2 && stateMap[gstin.slice(0, 2)]) {
            $('#customerStateInput').val(stateMap[gstin.slice(0, 2)]);
            recalcTotals();
        }
    });

    $(document).off('click', '#addRowBtn').on('click', '#addRowBtn', function () {
        addRow();
    });

    $(document).on('click', '.btn-del-row', function () {
        const idx = $(this).data('row');
        $(`#item-row-${idx}`).remove();
        recalcTotals();
    });

    const sameAsBillingToggle = document.getElementById('sameAsBillingToggle');
    const addressGrid = document.getElementById('addressGrid');
    const shipAddressSection = document.getElementById('shipAddressSection');
    const billToAddress = document.getElementById('billToAddress');
    const shipToAddress = document.getElementById('shipToAddress');

    function syncAddressVisibility() {
        const sameAddress = sameAsBillingToggle ? sameAsBillingToggle.checked : true;

        if (addressGrid) {
            addressGrid.classList.toggle('same-address-mode', sameAddress);
        }

        if (shipAddressSection) {
            shipAddressSection.classList.toggle('is-hidden', sameAddress);
        }

        if (sameAddress && billToAddress && shipToAddress) {
            shipToAddress.value = billToAddress.value;
        }
    }

    if (sameAsBillingToggle && billToAddress && shipToAddress) {
        sameAsBillingToggle.addEventListener('change', syncAddressVisibility);
        billToAddress.addEventListener('input', function () {
            if (sameAsBillingToggle.checked) {
                shipToAddress.value = billToAddress.value;
            }
        });

        syncAddressVisibility();
    }

    // Populate existing items
    if (EXISTING_ITEMS && EXISTING_ITEMS.length > 0) {
        EXISTING_ITEMS.forEach(item => {
            addRow(item);
        });
    } else {
        addRow();
    }

    recalcTotals();

    $('#quotationForm').on('submit', function () {
        if ($('#itemsBody tr').length === 0) {
            alert('Please add at least one product.');
            return false;
        }
        if (sameAsBillingToggle && sameAsBillingToggle.checked && billToAddress && shipToAddress) {
            shipToAddress.value = billToAddress.value;
        }
        $('#submitBtn').prop('disabled', true).html(
            '<i class="bi bi-hourglass-split"></i> Updating…'
        );
    });

});
</script>
@endpush
