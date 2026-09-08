{{-- resources/views/quotations/pdf.blade.php --}}
{{-- Used by DomPDF / barryvdh/laravel-dompdf --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <style>
    /* DomPDF works best with basic CSS – no Google Fonts, no flex/grid */
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 11px;
      color: #0d3b2e;
      background: #fff;
    }

    /* ── Header ── */
    .header {
      background: #0d3b2e;
      color: #fff;
      padding: 20px 28px;
      overflow: hidden;
    }
    .header-left  { float: left; width: 60%; }
    .header-right { float: right; width: 38%; text-align: right; }
    .clearfix::after { content: ''; display: table; clear: both; }

    .brand-name {
      font-size: 15px;
      font-weight: bold;
      color: #fff;
      margin-bottom: 4px;
    }
    .brand-sub { font-size: 9px; color: #fff; line-height: 1.6; }
    .gstin-badge {
      display: inline-block;
      background: #2ecc71;
      color: #fff;
      font-size: 9px;
      padding: 2px 6px;
      border-radius: 3px;
      margin-top: 5px;
    }

    .estimate-word { font-size: 30px; color: #2ecc71; font-weight: bold; }
    .est-meta { font-size: 10px; color: rgba(255,255,255,.8); line-height: 1.8; margin-top: 6px; }
    .est-meta strong { color: #fff; }

    /* ── Addresses ── */
    .addr-row { overflow: hidden; border-bottom: 1px solid #c8e6d8; }
    .addr-cell { float: left; width: 50%; padding: 14px 28px; }
    .addr-cell + .addr-cell { border-left: 1px solid #c8e6d8; }
    .addr-label {
      font-size: 8px;
      font-weight: bold;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: #5a6472;
      margin-bottom: 6px;
    }
    .addr-name { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
    .addr-text { font-size: 10px; color: #5a6472; line-height: 1.6; }
    .gstin-pill {
      display: inline-block;
      background: #e8f8f0;
      color: #2ecc71;
      font-size: 9px;
      font-weight: bold;
      padding: 2px 6px;
      border-radius: 3px;
      margin-top: 5px;
    }

    /* ── Table ── */
    .table-wrap { padding: 0 28px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10px; }
    thead tr { background: #0d3b2e; color: #fff; }
    thead th {
      padding: 8px 8px;
      text-align: left;
      font-size: 8.5px;
      font-weight: bold;
      letter-spacing: .06em;
      text-transform: uppercase;
    }
    thead th.r { text-align: right; }
    thead th.c { text-align: center; }

    tbody tr { border-bottom: 1px solid #e5e8ed; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 8px 8px; vertical-align: top; }
    tbody td.c { text-align: center; }
    tbody td.r { text-align: right; }

    .item-title { font-weight: bold; }
    .item-sub { font-size: 9px; color: #5a6472; margin-top: 2px; line-height: 1.5; }
    .hsn { background: #edf0f4; color: #5a6472; font-size: 9px; padding: 1px 5px; border-radius: 3px; }
    .zero { color: #bbb; }

    /* ── Totals ── */
    .totals-outer { overflow: hidden; padding: 16px 28px; border-top: 2px solid #c8e6d8; }
    .totals-box { float: right; width: 280px; }
    .t-row { overflow: hidden; padding: 5px 0; border-bottom: 1px solid #eee; font-size: 11px; }
    .t-row:last-child { border-bottom: none; }
    .t-lbl { float: left; color: #5a6472; }
    .t-val { float: right; font-weight: 600; }
    .t-grand {
      background: #0d3b2e;
      color: #fff;
      padding: 10px 12px;
      overflow: hidden;
      border-radius: 4px;
      margin-top: 6px;
    }
    .t-grand .t-lbl { color: #fff; font-weight: bold; font-size: 12px; }
    .t-grand .t-val { color: #2ecc71; font-weight: bold; font-size: 16px; }

    /* ── Words bar ── */
    .words-bar {
      background: #e8f8f0;
      border-top: 1px solid #f0e0bc;
      padding: 8px 28px;
      font-size: 10px;
      color: #2ecc71;
      font-weight: bold;
    }
    .words-bar span { color: #5a6472; font-weight: normal; margin-right: 4px; }

    /* ── Footer ── */
    .footer-row { overflow: hidden; border-top: 2px solid #c8e6d8; }
    .foot-cell { float: left; width: 50%; padding: 16px 28px; }
    .foot-cell + .foot-cell { border-left: 1px solid #c8e6d8; }
    .foot-label {
      font-size: 8px;
      font-weight: bold;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: #5a6472;
      margin-bottom: 8px;
    }
    .bk-row { overflow: hidden; padding: 4px 0; border-bottom: 1px dashed #eee; font-size: 10px; }
    .bk-row:last-child { border-bottom: none; }
    .bk-k { float: left; color: #5a6472; }
    .bk-v { float: right; font-weight: 600; }
    .upi {
      display: inline-block;
      background: #e8f8f0;
      color: #2ecc71;
      font-size: 9px;
      font-weight: bold;
      padding: 3px 8px;
      border-radius: 3px;
      margin-top: 8px;
    }
    .note { font-size: 10px; color: #5a6472; margin-top: 10px; }
    .sig-space { height: 48px; }
    .sig-line { border-top: 1px solid #0d3b2e; padding-top: 4px; font-size: 9px; font-weight: bold; letter-spacing: .05em; text-transform: uppercase; color: #5a6472; }
    .preview {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);

    font-size: 60px;
    font-weight: bold;
    color: rgba(0, 0, 0, 0.08); /* light transparent */

    text-align: center;
    width: 80%;

    white-space: normal;
    overflow-wrap: break-word;
    pointer-events: none; /* click-through */
  }

    @page {
      margin-bottom: 45px;
    }

    .page-number-footer {
      position: fixed;
      bottom: -30px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 9.5px;
      color: #5a6472;
      font-weight: bold;
    }
    .page-number-footer::before {
      content: "Page No : " counter(page);
    }
  </style>
</head>
<body>

<div class="page-number-footer"></div>

{{-- HEADER --}}
<div class="header clearfix">
  <div class="header-left">
     @php
    $path = public_path('images/LOGO_STS.png');
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
@endphp

<div style="margin-bottom:10px;width:120px;background-color:#fff;padding:10px;border-radius:10px">
    <img src="{{ $base64 }}" style="width:105px">
</div>

    <div class="brand-name">Sai Techno Solutions Private Limited</div>
    <div class="brand-sub">
      No: 6, 1st Floor, KCP Garden, Kaapi Kadai Stop,<br>
      Saravanampatti, Coimbatore – Tamil Nadu 641035, India
    </div>
    <span class="gstin-badge">GSTIN: 33ABPCS7671C1Z3</span>
  </div>
  <div class="header-right">
    <div class="estimate-word">Estimate</div>
    <div class="est-meta">
      <strong>{{ $quotation->estimate_number }}</strong><br>
      Date: <strong>{{ \Carbon\Carbon::parse($quotation->estimate_date)->format('d/m/Y') }}</strong><br>
      Place of Supply: <strong>Tamil Nadu (33)</strong><br>
      Sales Person: <strong>{{ $quotation->createdBy?->name }}</strong>
    </div>
  </div>
</div>

{{-- ADDRESSES --}}
<div class="addr-row clearfix">
  <div class="addr-cell">
    <div class="addr-label">Bill To</div>
    <div class="addr-name" style="text-transform: uppercase">{{ $quotation->lead->company_name }}</div>
    <div class="addr-text">{{ $quotation->bill_to_address }}</div>
    @if(isset($quotation->client_gstin))
        <span class="gstin-pill">GSTIN: {{ $quotation->client_gstin }}</span>
    @endif
  </div>
  <div class="addr-cell">
    <div class="addr-label">Ship To</div>
    <div class="addr-name" style="text-transform: uppercase">{{ $quotation->lead->company_name }}</div>
    <div class="addr-text">{{ $quotation->ship_to_address }}</div>
    @if(isset($quotation->client_gstin))
        <span class="gstin-pill">GSTIN: {{ $quotation->client_gstin }}</span>
    @endif

  </div>
</div>

{{-- ITEMS TABLE --}}
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th style="width:24px;">#</th>
        <th>Item &amp; Description</th>
        <th class="c" style="width:54px;">HSN/SAC</th>
        <th class="c" style="width:36px;">Qty</th>
        <th class="r" style="width:80px;">Rate (₹)</th>
        <th class="r" style="width:80px;">Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($quotation->items as $i => $item)
      <tr>
        <td style="color:#5a6472;">{{ $i + 1 }}</td>
        <td>
          <div class="item-title">{{ $item->product?->product_name }}</div>
          @if($item->description)
            <div class="item-sub">{!! nl2br(e($item->description)) !!}</div>
          @endif
        </td>
        <td class="c"><span class="hsn">{{ $item->hsn_sac }}</span></td>
        <td class="c">{{ number_format($item->qty, 2) }}</td>
        <td class="r {{ $item->unit_price == 0 ? 'zero' : '' }}">{{ number_format($item->unit_price, 2) }}</td>
        <td class="r {{ $item->total == 0 ? 'zero' : '' }}">{{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

{{-- WORDS BAR --}}
<div class="words-bar">
  <span>Amount in Words:</span>{{ amount_in_words($quotation->total_amount) }}
</div>

{{-- TOTALS --}}
<div class="totals-outer clearfix">
  <div class="totals-box">
    @if($quotation->tax_type === 'igst' || $quotation->igst_amount > 0)
    <div class="t-row clearfix"><span>IGST {{ number_format($quotation->igst_rate ?: 18, 2) }}%</span><span class="t-val">₹{{ number_format($quotation->igst_amount ?: $quotation->tax_amount, 2) }}</span></div>
    @else
    <div class="t-row clearfix"><span>CGST {{ number_format($quotation->cgst_rate ?: 9, 2) }}%</span><span class="t-val">₹{{ number_format($quotation->cgst_amount ?: ($quotation->tax_amount / 2), 2) }}</span></div>
    <div class="t-row clearfix"><span>SGST {{ number_format($quotation->sgst_rate ?: 9, 2) }}%</span><span class="t-val">₹{{ number_format($quotation->sgst_amount ?: ($quotation->tax_amount / 2), 2) }}</span></div>
    @endif
    <div class="t-row clearfix"><span >Rounding</span><span class="t-val">₹{{ number_format($quotation->rounding, 2) }}</span></div>
    <div class="t-grand clearfix">
      <span >Total</span>
      <span class="t-val">₹{{ number_format($quotation->total_amount, 2) }}</span>
    </div>
  </div>
</div>

{{-- FOOTER --}}
<div class="footer-row clearfix">
  <div class="foot-cell">
    <div class="foot-label">Bank Details</div>
    <div class="bk-row clearfix"><span >Bank Name : </span><span >DHANLAXMI BANK</span></div>
    <div class="bk-row clearfix">Account Name : SAI TECHNO SOLUTIONS PVT LTD</div>
    <div class="bk-row clearfix">Account Number : 012706700003085</div>
    <div class="bk-row clearfix">IFSC Code : DLXB0000127</div>
    <div class="bk-row clearfix">Branch : PEELAMEDU</div>
    <span class="upi">UPI: saitechno3085@dlb</span>
    <p class="note">Looking forward for your business.</p>
  </div>
  <div class="foot-cell">
    <div class="foot-label">Authorized Signatory</div>
    @php
    $path = public_path('images/sign.jpeg');
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
@endphp

<div class="sig-space" style="margin-left:25px;margin-bottom:30px;margin-top:25px">
    <img src="{{ $base64 }}" width="150">
</div>
    <div class="sig-line"> Sai Techno Solutions Pvt Ltd</div>
  </div>
</div>

<div class="preview">
    <h1>Preview Only</h1>
</div>

</body>
</html>
