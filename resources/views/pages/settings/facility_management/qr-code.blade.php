@extends('layouts.app')

@section('title', 'Facility QR Code')

@push('styles')
@include('pages.settings.partials.table-styles')
<style>
    .fm-qr-layout { display:grid; grid-template-columns:360px minmax(0, 1fr); gap:18px; align-items:start; }
    .fm-qr-box { display:flex; align-items:center; justify-content:center; padding:22px; background:#fff; border:1px solid #e1dee3; border-radius:18px; }
    .fm-qr-box img { width:100%; max-width:320px; height:auto; }
    .fm-link-box { padding:14px; border:1px solid #f0eef2; border-radius:12px; background:#fafafa; word-break:break-all; font-size:13px; color:#121212; }
    @media (max-width: 900px) { .fm-qr-layout { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Facility QR Code</h2>
                <p class="crm-subtitle">Scan this QR to open the facility management form without login.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('facility-management.index') }}" class="crm-btn crm-btn-ghost">Back</a>
            </div>
        </div>

        <div class="fm-qr-layout">
            <div class="fm-qr-box">
                <img src="{{ $qrCodeUrl }}" alt="Facility management QR code">
            </div>

            <div class="crm-table-wrap" style="padding:24px;">
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <h3 style="margin:0; font-size:18px; font-weight:700;">Common Facility Entry QR</h3>
                        <p style="margin:8px 0 0; color:#666; line-height:1.6;">Print or display this QR where staff can scan and submit the facility entry form directly.</p>
                    </div>

                    <div>
                        <div class="crm-label" style="margin-bottom:8px;">Facility Form Link</div>
                        <div class="fm-link-box">{{ $facilityFormUrl }}</div>
                    </div>

                    <div class="crm-header-actions">
                        <a href="{{ $facilityFormUrl }}" target="_blank" rel="noopener" class="crm-btn crm-btn-primary">Open Form</a>
                        <button type="button" class="crm-btn crm-btn-ghost" onclick="window.print()">Print QR</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
