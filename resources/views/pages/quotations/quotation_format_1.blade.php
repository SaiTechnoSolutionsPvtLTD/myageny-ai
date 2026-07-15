<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #0f1923; background: #fff; }
    .header { background: {{ $quoteSetting['theme_color'] }}; color: {{ $quoteSetting['header_text_color'] ?? '#ffffff' }}; padding: 20px 28px; overflow: hidden; }
    .header-left  { float: left; width: 60%; }
    .header-right { float: right; width: 38%; text-align: right; }
    .clearfix::after { content: ''; display: table; clear: both; }
    .brand-sub { font-size: 9px; color: {{ $quoteSetting['header_text_color'] ?? '#ffffff' }}; line-height: 1.6; }
    .gstin-badge { display: inline-block; background: {{ $quoteSetting['secondary_color'] }}; color: {{ $quoteSetting['header_text_color'] ?? '#ffffff' }}; font-size: 9px; padding: 2px 6px; border-radius: 3px; margin-top: 5px; }
    .estimate-word { font-size: 30px; color: {{ $quoteSetting['secondary_color'] }}; font-weight: bold; }
    .est-meta { font-size: 10px; color: {{ $quoteSetting['header_text_color'] ?? '#ffffff' }}; line-height: 1.8; margin-top: 6px; }
    .est-meta strong { color: {{ $quoteSetting['header_text_color'] ?? '#ffffff' }}; }
    .addr-row { overflow: hidden; border-bottom: 1px solid #d8dde5; }
    .addr-cell { float: left; width: 50%; padding: 14px 28px; }
    .addr-cell + .addr-cell { border-left: 1px solid #d8dde5; }
    .addr-label { font-size: 8px; font-weight: bold; letter-spacing: .1em; text-transform: uppercase; color: #5a6472; margin-bottom: 6px; }
    .addr-name { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
    .addr-text { font-size: 10px; color: #5a6472; line-height: 1.6; }
    .gstin-pill { display: inline-block; background: #fdf4e7; color: #c8973a; font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 3px; margin-top: 5px; }
    .table-wrap { padding: 0 28px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10px; }
    thead tr { background: {{ $quoteSetting['secondary_color'] ?: $quoteSetting['theme_color'] }}; color: #fff; }
    thead th { padding: 8px; text-align: left; font-size: 8.5px; font-weight: bold; letter-spacing: .06em; text-transform: uppercase; }
    thead th.r, tbody td.r { text-align: right; }
    thead th.c, tbody td.c { text-align: center; }
    tbody tr { border-bottom: 1px solid #e5e8ed; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td { padding: 8px; vertical-align: top; }
    .item-title { font-weight: bold; }
    .item-sub { font-size: 9px; color: #5a6472; margin-top: 2px; line-height: 1.5; }
    .words-bar { background: #fdf4e7; border-top: 1px solid #f0e0bc; padding: 8px 28px; font-size: 10px; color: {{ $quoteSetting['secondary_color'] }}; font-weight: bold; }
    .words-bar span { color: #5a6472; font-weight: normal; margin-right: 4px; }
    .totals-outer { overflow: hidden; padding: 16px 28px; border-top: 2px solid #d8dde5; }
    .totals-box { float: right; width: 300px; }
    .t-row { overflow: hidden; padding: 5px 0; border-bottom: 1px solid #eee; font-size: 11px; }
    .t-val { float: right; font-weight: 600; }
    .t-grand { background: {{ $quoteSetting['theme_color'] }}; color: #fff; padding: 10px 12px; overflow: hidden; border-radius: 4px; margin-top: 6px; }
    .t-grand .t-val { color: {{ $quoteSetting['secondary_color'] }}; font-weight: bold; font-size: 16px; }
    .footer-row { overflow: hidden; border-top: 2px solid #d8dde5; }
    .foot-cell { float: left; width: 50%; padding: 16px 28px; }
    .foot-cell + .foot-cell { border-left: 1px solid #d8dde5; }
    .foot-label { font-size: 8px; font-weight: bold; letter-spacing: .1em; text-transform: uppercase; color: #5a6472; margin-bottom: 8px; }
    .bk-row { padding: 4px 0; border-bottom: 1px dashed #eee; font-size: 10px; }
    .upi { display: inline-block; background: #fdf4e7; color: #c8973a; font-size: 9px; font-weight: bold; padding: 3px 8px; border-radius: 3px; margin-top: 8px; }
    .note { font-size: 10px; color: #5a6472; margin-top: 10px; }
    .sig-space { height: 48px; }
    .sig-line { border-top: 1px solid #0f1923; padding-top: 4px; font-size: 9px; font-weight: bold; letter-spacing: .05em; text-transform: uppercase; color: #5a6472; }
    .preview { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 60px; font-weight: bold; color: rgba(0, 0, 0, 0.08); text-align: center; width: 80%; white-space: normal; overflow-wrap: break-word; pointer-events: none; }
    .footer-final { position: absolute; bottom:0; background: #fdf4e7; padding:10px; width:100%; text-align:center; }
    .page-break { page-break-before: always; }
    .terms-page { padding: 34px 38px 42px; }
    .terms-header { border-bottom: 3px solid {{ $quoteSetting['theme_color'] }}; padding-bottom: 14px; margin-bottom: 20px; }
    .terms-title { font-size: 24px; font-weight: bold; color: {{ $quoteSetting['theme_color'] }}; }
    .terms-subtitle { margin-top: 6px; font-size: 11px; color: #5a6472; }
    .terms-section { margin-bottom: 22px; }
    .terms-section h3 { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; color: {{ $quoteSetting['secondary_color'] }}; margin-bottom: 10px; }
    .terms-box { background: #f9fafb; border: 1px solid #e5e8ed; border-radius: 6px; padding: 14px 16px; }
    .terms-box p, .terms-box li { font-size: 11px; line-height: 1.8; color: #334155; }
    .terms-box ul { margin: 0; padding-left: 18px; }
    .terms-box p + p { margin-top: 8px; }
    .terms-footer-note { margin-top: 30px; padding-top: 12px; border-top: 1px solid #d8dde5; font-size: 10px; color: #64748b; }
  </style>
</head>
<body>
@php
  $logoBase64 = null;
  if (!empty($quoteSetting['logo']) && file_exists(public_path($quoteSetting['logo']))) {
      $path = public_path($quoteSetting['logo']);
      $type = pathinfo($path, PATHINFO_EXTENSION);
      $data = file_get_contents($path);
      $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
  }

  $signatureBase64 = null;
  if (!empty($quoteSetting['signature']) && file_exists(public_path($quoteSetting['signature']))) {
      $path = public_path($quoteSetting['signature']);
      $type = pathinfo($path, PATHINFO_EXTENSION);
      $data = file_get_contents($path);
      $signatureBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
  }

  $defaultTerms = '
    <ul>
      <li>This quotation is valid for 7 days from the quotation date unless otherwise stated.</li>
      <li>Prices quoted are exclusive of additional scope changes unless explicitly mentioned.</li>
      <li>Work will begin only after written confirmation or purchase order approval.</li>
      <li>Any changes in scope, quantity, or delivery requirements may affect pricing and timeline.</li>
      <li>Taxes, levies, and statutory charges are applicable as per current regulations.</li>
    </ul>
  ';

  $termsContent = trim((string) ($quoteSetting['terms'] ?? '')) !== ''
      ? $quoteSetting['terms']
      : $defaultTerms;
@endphp

<div class="header clearfix">
  <div class="header-left">
    @if($logoBase64)
    <div style="margin-bottom:10px;width:180px;background-color:#fff;padding:10px;border-radius:10px">
      <img src="{{ $logoBase64 }}" style="width:175px">
    </div>
    @endif
    <div class="brand-sub">
      {{ $quoteSetting['company_name'] }}<br>
      {{ $quoteSetting['company_address'] }}
    </div>
    <span class="gstin-badge">GSTIN: {{ $quoteSetting['company_gstin'] }}</span>
  </div>
  <div class="header-right">
    <div class="estimate-word">Quotation</div>
    <div class="est-meta">
      <strong>{{ $quotation->quotation_no }}</strong><br>
      Date: <strong>{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d/m/Y') }}</strong><br>
      Place of Supply: <strong>{{ $quotation->customer_state ?: ($quotation->seller_state ?: 'Tamil Nadu') }}</strong><br>
      Sales Person: <strong>{{ $quotation->createdBy?->name }}</strong>
    </div>
  </div>
</div>

<div class="addr-row clearfix">
  <div class="addr-cell">
    <div class="addr-label">Bill To</div>
    <div class="addr-name" style="text-transform: uppercase">{{ $quotation->lead->company_name }}</div>
    <div class="addr-text">{{ $quotation->bill_to_address }}</div>
    @if($quotation->gst_number)
      <span class="gstin-pill">GSTIN: {{ $quotation->gst_number }}</span>
    @endif
  </div>
  <div class="addr-cell">
    <div class="addr-label">Ship To</div>
    <div class="addr-name" style="text-transform: uppercase">{{ $quotation->lead->company_name }}</div>
    <div class="addr-text">{{ $quotation->ship_to_address }}</div>
    @if($quotation->gst_number)
      <span class="gstin-pill">GSTIN: {{ $quotation->gst_number }}</span>
    @endif
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th style="width:24px;">#</th>
        <th>Item &amp; Description</th>
        <th class="c" style="width:36px;">Qty</th>
        <th class="r" style="width:80px;">Rate (₹)</th>
        <th class="r" style="width:80px;">Discount (₹)</th>
        <th class="r" style="width:80px;">Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($quotation->items as $i => $item)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>
          <div class="item-title">{{ $item->product?->product_name }}</div>
          @if($item->description)
            <div class="item-sub">{!! nl2br(e($item->description)) !!}</div>
          @endif
        </td>
        <td class="c">{{ number_format($item->qty, 2) }}</td>
        <td class="r">{{ number_format($item->unit_price, 2) }}</td>
        <td class="r">{{ number_format($item->discount, 2) }}</td>
        <td class="r">{{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

<div class="words-bar">
  <span>Amount in Words:</span>{{ amount_in_words($quotation->total_amount) }}
</div>

<div class="totals-outer clearfix">
  <div class="totals-box">
    <div class="t-row clearfix"><span>Sub Total</span><span class="t-val">₹{{ number_format($quotation->subtotal, 2) }}</span></div>
    @if($quotation->igst_amount > 0)
    <div class="t-row clearfix"><span>IGST {{ number_format($quotation->igst_rate, 2) }}%</span><span class="t-val">₹{{ number_format($quotation->igst_amount, 2) }}</span></div>
    @else
    <div class="t-row clearfix"><span>CGST {{ number_format($quotation->cgst_rate, 2) }}%</span><span class="t-val">₹{{ number_format($quotation->cgst_amount, 2) }}</span></div>
    <div class="t-row clearfix"><span>SGST {{ number_format($quotation->sgst_rate, 2) }}%</span><span class="t-val">₹{{ number_format($quotation->sgst_amount, 2) }}</span></div>
    @endif
    <div class="t-grand clearfix">
      <span>Total</span>
      <span class="t-val">₹{{ number_format($quotation->total_amount, 2) }}</span>
    </div>
  </div>
</div>

<div class="footer-row clearfix">
  <div class="foot-cell">
    <div class="foot-label">Bank Details</div>
    <div class="bk-row">Bank Name : {{ $quoteSetting['bank_name'] }}</div>
    <div class="bk-row">Account Name : {{ $quoteSetting['account_name'] }}</div>
    <div class="bk-row">Account Number : {{ $quoteSetting['bank_account'] }}</div>
    <div class="bk-row">IFSC Code : {{ $quoteSetting['bank_ifsc'] }}</div>
    <div class="bk-row">Branch : {{ $quoteSetting['bank_branch'] }}</div>
    <span class="upi">UPI: {{ $quoteSetting['bank_upi'] }}</span>
    <p class="note">Looking forward for your business.</p>
  </div>
  <div class="foot-cell">
    <div class="foot-label">Authorized Signatory</div>
    @if($signatureBase64)
    <div class="sig-space" style="margin-left:25px;margin-bottom:30px;margin-top:25px">
      <img src="{{ $signatureBase64 }}" width="150">
    </div>
    @else
    <div class="sig-space"></div>
    @endif
    <div class="sig-line">{{ $quoteSetting['company_name'] }}</div>
  </div>
</div>

<div class="preview">
  <h1>{{ $quoteSetting['watermark_text'] }}</h1>
</div>

<div class="footer-final">
  <a style="text-decoration:none;color:#0f1923" href="http://myagenci.ai/" target="_blank">Powered By Myagenci.ai</a>
</div>

<div class="page-break"></div>

<div class="terms-page">
  <div class="terms-header">
    <div class="terms-title">Terms &amp; Conditions</div>
    <div class="terms-subtitle">This page forms an integral part of Quotation {{ $quotation->quotation_no }}.</div>
  </div>

  <div class="terms-section">
    <h3>Terms &amp; Conditions</h3>
    <div class="terms-box">
      {!! $termsContent !!}
    </div>
  </div>

  <div class="terms-footer-note">
    For any clarification regarding pricing, delivery, support, or commercial terms, please contact {{ $quoteSetting['company_name'] }} before confirmation.
  </div>
</div>

</body>
</html>
