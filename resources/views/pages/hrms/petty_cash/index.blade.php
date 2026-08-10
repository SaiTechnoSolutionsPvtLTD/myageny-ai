@extends('layouts.app')

@section('title', 'Petty Cash Account Report - myAgenci.ai')

@push('styles')
<style>
.petty-cash-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
}
.petty-cash-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding: 24px 28px;
    border: 1px solid #e6e8ee;
    border-radius: 20px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.04);
}
.petty-cash-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.petty-cash-title {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: #111827;
}
.petty-cash-subtitle {
    margin: 6px 0 0;
    font-size: 14px;
    color: #6b7280;
}
.alert-success {
    background-color: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="petty-cash-page">
    @if(session('success'))
        <div class="alert-success">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    <div class="petty-cash-hero">
        <div>
            <div class="petty-cash-kicker">
                <i class="bi bi-wallet2"></i> Finance Management
            </div>
            <h2 class="petty-cash-title">Petty Cash Account Report (DR & CR)</h2>
            <p class="petty-cash-subtitle">Branch-wise Debit & Credit transaction ledger statement with opening & closing balances.</p>
        </div>
    </div>

    <!-- Include Petty Cash Report Widget Partial -->
    @include('pages.hrms.dashboard.partials._petty_cash_report')
</div>
@endsection
