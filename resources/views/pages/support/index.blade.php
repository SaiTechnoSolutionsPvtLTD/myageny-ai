@extends('layouts.app')

@section('title', 'Support - myAgenci.ai')

@push('styles')
<style>
/* ── Corporate Page Layout & Topbar ── */
.sup-page {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background: #f4f5f7;
    font-family: 'Inter', sans-serif;
}
.sup-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 0 28px;
    min-height: 60px;
    background: #fff;
    border-bottom: 1px solid #e1dee3;
    position: sticky;
    top: 0;
    z-index: 30;
}
.sup-topbar-left {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.sup-title {
    font-size: 18px;
    font-weight: 800;
    color: #121212;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.2;
}
.sup-breadcrumbs {
    font-size: 12px;
    color: #9e9e9e;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}
.sup-breadcrumbs a {
    color: #9e9e9e;
    text-decoration: none;
    transition: color .15s;
}
.sup-breadcrumbs a:hover {
    color: #fe5f04;
}
.sup-breadcrumbs .sep {
    color: #ccc;
}
.sup-breadcrumbs .current {
    color: #424242;
    font-weight: 600;
}
.sup-topbar-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.sup-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
    transition: all .15s;
    border: none;
}
.sup-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.25);
}
.sup-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(254, 95, 4, 0.35);
    color: #fff;
}
.sup-btn-outline {
    background: #fff;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.sup-btn-outline:hover {
    border-color: #fe5f04;
    color: #fe5f04;
}
.sup-body {
    padding: 22px 28px 34px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    flex: 1;
}
@media (max-width: 900px) {
    .sup-topbar {
        padding: 14px 20px;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        min-height: auto;
    }
    .sup-body {
        padding: 16px 20px 24px;
    }
}
.sup-alert {
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sup-alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}
.sup-alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.support-page {
    min-height: 100%;
}
.btn-create-ticket {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 13px;
    padding: 8px 18px;
    border-radius: 9px;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.25);
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-create-ticket:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(254, 95, 4, 0.35);
    color: #ffffff;
}

/* Tabs */
.tabs-container {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 24px;
    gap: 28px;
}
.tab-btn {
    padding: 12px 6px;
    font-size: 15px;
    font-weight: 700;
    color: #6b7280;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    transition: all 0.25s ease;
}
.tab-btn:hover {
    color: #fe5f04;
}
.tab-btn.active {
    color: #fe5f04;
    border-bottom-color: #fe5f04;
}

/* Filter Card & Form */
.support-filter-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 14px 18px;
    margin-bottom: 20px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
}
.support-filter-form {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    width: 100%;
}
.support-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.support-filter-label {
    font-size: 12px;
    font-weight: 800;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.support-filter-select {
    padding: 9px 14px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
    background: #ffffff;
    outline: none;
    min-width: 180px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.support-filter-select:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.support-filter-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 8px 14px;
    flex: 1;
    min-width: 200px;
    transition: all 0.2s ease;
}
.support-filter-search:focus-within {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.support-filter-search input {
    border: none;
    outline: none;
    font-size: 13px;
    font-weight: 600;
    color: #111827;
    width: 100%;
    background: transparent;
}
.btn-filter-apply {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    background: #fe5f04;
    color: #ffffff;
    border: none;
    cursor: pointer;
    transition: all 0.18s ease;
}
.btn-filter-apply:hover {
    background: #ea580c;
    box-shadow: 0 4px 10px rgba(254, 95, 4, 0.2);
}
.btn-filter-reset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    background: #f3f4f6;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.18s ease;
}
.btn-filter-reset:hover {
    background: #e5e7eb;
    color: #111827;
}
@media (max-width: 768px) {
    .support-filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    .support-filter-group {
        width: 100%;
    }
    .support-filter-select {
        width: 100%;
        min-width: 100%;
    }
    .support-filter-search {
        width: 100%;
    }
}
.tab-panel {
    display: none;
}
.tab-panel.active {
    display: block;
}

/* Table Design */
.ticket-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.02);
    overflow: hidden;
}
.ticket-table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.ticket-table-responsive::-webkit-scrollbar {
    height: 7px;
}
.ticket-table-responsive::-webkit-scrollbar-track {
    background: #f8fafc;
}
.ticket-table-responsive::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}
.ticket-table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.support-table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
    text-align: left;
}
.support-kpi-card {
    background: #ffffff;
    border: 1.5px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    position: relative;
}
.support-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}
.support-kpi-card.active-filter {
    border-color: #fe5f04 !important;
    background: #fffaf5 !important;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.15), 0 8px 20px rgba(254, 95, 4, 0.08) !important;
}
.support-kpi-card.active-filter::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 20px;
    right: 20px;
    height: 3px;
    background: #fe5f04;
    border-radius: 3px 3px 0 0;
}
.support-table th {
    background: #f9fafb;
    padding: 16px 20px;
    font-size: 12px;
    font-weight: 700;
    color: #4b5563;
    border-bottom: 1px solid #e5e7eb;
    text-transform: uppercase;
}
.support-table td {
    padding: 18px 20px;
    font-size: 14px;
    color: #1f2937;
    border-bottom: 1px solid #e5e7eb;
}
.support-table tr:last-child td {
    border-bottom: none;
}
.support-table tr:hover td {
    background: #fafbfe;
}

/* Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}
.status-badge.pending {
    background-color: #fff1e8;
    color: #c2410c;
}
.status-badge.onprocess {
    background-color: #eff6ff;
    color: #1d4ed8;
}
.status-badge.resolved {
    background-color: #ecfdf5;
    color: #047857;
}
.status-badge.closed {
    background-color: #f3f4f6;
    color: #374151;
}

.btn-update-status {
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 14px;
    border-radius: 10px;
    transition: all 0.15s ease;
    cursor: pointer;
}
.btn-update-status:hover {
    background-color: #fe5f04;
    border-color: #fe5f04;
    color: #ffffff;
}

.ticket-message-preview {
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #6b7280;
    font-size: 13px;
}

.ticket-subject-link:hover {
    color: #fe5f04 !important;
    text-decoration: underline;
}

/* Modals */
.support-modal {
    position: fixed;
    inset: 0;
    background: rgba(18, 24, 38, 0.45);
    backdrop-filter: blur(4px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.support-modal.show {
    display: flex;
    opacity: 1;
}
.support-modal-content {
    background: #ffffff;
    border-radius: 20px;
    width: 650px;
    max-width: calc(100vw - 32px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transform: translateY(20px);
    transition: transform 0.25s ease;
}
.support-modal.show .support-modal-content {
    transform: translateY(0);
}
.support-modal-header {
    background: #fafbfe;
    border-bottom: 1px solid #e5e7eb;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.support-modal-title {
    font-size: 18px;
    font-weight: 800;
    color: #111827;
    margin: 0;
}
.support-modal-close {
    color: #9ca3af;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    transition: color 0.15s ease;
}
.support-modal-close:hover {
    color: #374151;
}
.support-modal-body {
    padding: 24px;
    max-height: calc(85vh - 120px);
    overflow-y: auto;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 8px;
}
.form-control {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    color: #1f2937;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.form-control:focus {
    outline: none;
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.support-modal-footer {
    border-top: 1px solid #e5e7eb;
    padding: 16px 24px;
    background: #fafbfe;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
.btn-secondary {
    background: #ffffff;
    border: 1px solid #d1d5db;
    color: #374151;
    font-weight: 700;
    padding: 10px 18px;
    border-radius: 10px;
    cursor: pointer;
}
.btn-secondary:hover {
    background: #f9fafb;
}
.btn-submit {
    background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%);
    color: #ffffff;
    font-weight: 700;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.15);
}
.btn-submit:hover {
    box-shadow: 0 6px 16px rgba(254, 95, 4, 0.22);
}

.tox-tinymce {
    border-radius: 12px !important;
    border-color: #d1d5db !important;
}

/* Select2 overrides inside modal */
.select2-container--default .select2-selection--single {
    border-radius: 10px !important;
    border-color: #d1d5db !important;
    height: 42px !important;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
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

/* Process / Progress Bar Overlay */
.support-process-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    animation: fadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}

.support-process-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 40px 36px;
    width: 460px;
    max-width: calc(100vw - 32px);
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
    text-align: center;
    transform: scale(0.95);
    animation: scaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes scaleInCard {
    to { transform: scale(1); }
}

.support-process-icon-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.support-process-spinner {
    position: absolute;
    inset: 0;
    border: 3.5px solid #ffe6d5;
    border-top-color: #fe5f04;
    border-radius: 50%;
    animation: spinOverlay 0.9s linear infinite;
}

@keyframes spinOverlay {
    to { transform: rotate(360deg); }
}

.support-process-icon {
    font-size: 32px;
    color: #fe5f04;
    animation: pulseIcon 1.5s ease-in-out infinite alternate;
}

@keyframes pulseIcon {
    from { transform: scale(0.88); opacity: 0.85; }
    to { transform: scale(1.12); opacity: 1; }
}

.support-process-title {
    font-size: 19px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 6px;
}

.support-process-subtitle {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 24px;
    line-height: 1.5;
}

.support-progress-wrapper {
    width: 100%;
}

.support-progress-bar {
    width: 100%;
    height: 10px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
}

.support-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 50%, #fe5f04 100%);
    background-size: 200% 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
    animation: gradientMove 2s linear infinite;
}

@keyframes gradientMove {
    0% { background-position: 0% 0%; }
    100% { background-position: 200% 0%; }
}

.support-progress-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    font-size: 12px;
    font-weight: 700;
    color: #4b5563;
}

/* Custom Pagination Styles */
.pagination {
    display: flex !important;
    list-style: none !important;
    padding: 0 !important;
    margin: 0 !important;
    gap: 6px !important;
    align-items: center !important;
}
.page-item {
    display: inline-block !important;
    margin: 0 !important;
}
.page-item a,
.page-item span,
.page-link {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 36px !important;
    height: 36px !important;
    padding: 0 12px !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 10px !important;
    text-decoration: none !important;
    color: #4b5563 !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    background: #ffffff !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03) !important;
    transition: all 0.2s ease !important;
    line-height: 1 !important;
}
.page-item a:hover,
.page-link:hover {
    border-color: #fe5f04 !important;
    color: #fe5f04 !important;
    background: #fff1e8 !important;
}
.page-item.active span,
.page-item.active .page-link {
    background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%) !important;
    color: #ffffff !important;
    border-color: #fe5f04 !important;
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25) !important;
}
.page-item.disabled span,
.page-item.disabled .page-link {
    color: #cbd5e1 !important;
    background: #f8fafc !important;
    border-color: #f1f5f9 !important;
    cursor: not-allowed !important;
    opacity: 0.7 !important;
}
</style>
@endpush

@section('content')
<div class="sup-page">
    {{-- Sticky Corporate Topbar --}}
    <div class="sup-topbar">
        <div class="sup-topbar-left">
            <div class="sup-title">
                <i class="bi bi-headset" style="color: #fe5f04;"></i>
                <span>Support Tickets</span>
            </div>
            <div class="sup-breadcrumbs">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span class="sep">/</span>
                <span class="current">Support &amp; Helpdesk</span>
            </div>
        </div>
        <div class="sup-topbar-actions">
            @if(request()->hasAny(['category_id', 'subcategory_id', 'search']))
                <a href="{{ route('support.index') }}" class="sup-btn sup-btn-outline" title="Reset all active filters">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                </a>
            @endif
            <a href="{{ route('support.create') }}" class="sup-btn sup-btn-primary">
                <i class="bi bi-plus-lg"></i> Create Support Ticket
            </a>
        </div>
    </div>

    <div class="sup-body">
        @if(session('success'))
            <div class="sup-alert sup-alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="sup-alert sup-alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

    {{-- Active Tab Resolution --}}
    @php
        $activeTab = 'received';
        if ($isCompanyAdmin) {
            $activeTab = 'all_tickets';
            if (request()->has('received_page')) {
                $activeTab = 'received';
            } elseif (request()->has('created_page')) {
                $activeTab = 'created';
            } elseif (request()->has('all_page')) {
                $activeTab = 'all_tickets';
            }
        } else {
            if (request()->has('created_page')) {
                $activeTab = 'created';
            } elseif (request()->has('received_page')) {
                $activeTab = 'received';
            }
        }
    @endphp

    {{-- KPI Summary Stats --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="support-kpi-card active-filter" data-status-filter="all" onclick="filterByCardStatus('all')" title="Click to view all tickets">
            <div>
                <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">{{ $isCompanyAdmin ? 'All Company Tickets' : 'All Tickets' }}</div>
                <div style="font-size: 24px; font-weight: 900; color: #111827; margin-top: 4px;">{{ $statTotal }}</div>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #fff1e8; border: 1px solid #fed7aa; color: #c2410c; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="bi bi-collection-fill"></i>
            </div>
        </div>

        <div class="support-kpi-card" data-status-filter="pending" onclick="filterByCardStatus('pending')" title="Click to filter pending tickets">
            <div>
                <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Pending Tickets</div>
                <div style="font-size: 24px; font-weight: 900; color: #c2410c; margin-top: 4px;">{{ $statPending }}</div>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>

        <div class="support-kpi-card" data-status-filter="onprocess" onclick="filterByCardStatus('onprocess')" title="Click to filter on process tickets">
            <div>
                <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">On Process</div>
                <div style="font-size: 24px; font-weight: 900; color: #1d4ed8; margin-top: 4px;">{{ $statOnProcess }}</div>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="bi bi-gear-wide-connected"></i>
            </div>
        </div>

        <div class="support-kpi-card" data-status-filter="resolved_closed" onclick="filterByCardStatus('resolved_closed')" title="Click to filter resolved & closed tickets">
            <div>
                <div style="font-size: 11px; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Resolved / Closed</div>
                <div style="font-size: 24px; font-weight: 900; color: #15803d; margin-top: 4px;">{{ $statResolved }}</div>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
        </div>
    </div>

    {{-- Filter Bar: Category & Subcategory Filter --}}
    <div class="support-filter-card">
        <form method="GET" action="{{ route('support.index') }}" class="support-filter-form" id="supportFilterForm">
            <div class="support-filter-group">
                <i class="bi bi-funnel-fill" style="color: #fe5f04; font-size: 15px;"></i>
                <span class="support-filter-label">Filter:</span>
            </div>

            <div class="support-filter-group" style="flex: 1; min-width: 190px;">
                <select name="category_id" id="filterCategoryId" class="support-filter-select no-select2" data-no-select2="true" style="width: 100%;">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }} {{ $cat->code ? "({$cat->code})" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="support-filter-group" style="flex: 1; min-width: 190px;">
                <select name="subcategory_id" id="filterSubcategoryId" class="support-filter-select no-select2" data-no-select2="true" style="width: 100%;">
                    <option value="">All Subcategories</option>
                    @if(request('category_id'))
                        @php
                            $currentCat = $categories->firstWhere('id', request('category_id'));
                        @endphp
                        @if($currentCat)
                            @foreach($currentCat->activeSubcategories as $subcat)
                                <option value="{{ $subcat->id }}" data-category-id="{{ $currentCat->id }}" {{ (string) request('subcategory_id') === (string) $subcat->id ? 'selected' : '' }}>
                                    {{ $subcat->name }} {{ $subcat->code ? "({$subcat->code})" : '' }}
                                </option>
                            @endforeach
                        @endif
                    @else
                        @foreach($categories as $cat)
                            @if($cat->activeSubcategories && $cat->activeSubcategories->count() > 0)
                                <optgroup label="{{ $cat->name }}">
                                    @foreach($cat->activeSubcategories as $subcat)
                                        <option value="{{ $subcat->id }}" data-category-id="{{ $cat->id }}" {{ (string) request('subcategory_id') === (string) $subcat->id ? 'selected' : '' }}>
                                            {{ $subcat->name }} {{ $subcat->code ? "({$subcat->code})" : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="support-filter-search">
                <i class="bi bi-search" style="color: #9ca3af; font-size: 13px;"></i>
                <input type="text" name="search" id="ticketSearchInput" value="{{ request('search') }}" placeholder="Search tickets, subject, remark, user..." onkeyup="filterSupportTickets(this.value)">
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="submit" class="btn-filter-apply">
                    <i class="bi bi-funnel"></i> Apply
                </button>
                @if(request()->hasAny(['category_id', 'subcategory_id', 'search']))
                    <a href="{{ route('support.index') }}" class="btn-filter-reset">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabs Toolbar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 22px;">
        <div class="tabs-container" style="margin-bottom: 0;">
            @if($isCompanyAdmin)
                <button type="button" class="tab-btn {{ $activeTab === 'all_tickets' ? 'active' : '' }}" onclick="switchTab(event, 'all_tickets')">
                    <i class="bi bi-collection-fill"></i> All Tickets ({{ $allTickets->total() }})
                </button>
                <button type="button" class="tab-btn {{ $activeTab === 'received' ? 'active' : '' }}" onclick="switchTab(event, 'received')">
                    <i class="bi bi-inbox-fill"></i> Received Tickets ({{ $receivedTickets->total() }})
                </button>
                <button type="button" class="tab-btn {{ $activeTab === 'created' ? 'active' : '' }}" onclick="switchTab(event, 'created')">
                    <i class="bi bi-send-fill"></i> Created Tickets ({{ $createdTickets->total() }})
                </button>
            @else
                <button type="button" class="tab-btn {{ $activeTab === 'received' ? 'active' : '' }}" onclick="switchTab(event, 'received')">
                    <i class="bi bi-inbox-fill"></i> Received Tickets ({{ $receivedTickets->total() }})
                </button>
                <button type="button" class="tab-btn {{ $activeTab === 'created' ? 'active' : '' }}" onclick="switchTab(event, 'created')">
                    <i class="bi bi-send-fill"></i> Created Tickets ({{ $createdTickets->total() }})
                </button>
            @endif
        </div>
    </div>

    @if($isCompanyAdmin)
        {{-- ALL TICKETS PANEL (FOR COMPANY ADMIN) --}}
        <div id="all_tickets" class="tab-panel {{ $activeTab === 'all_tickets' ? 'active' : '' }}">
            <div class="ticket-card">
                <div class="ticket-table-responsive">
                    <table class="support-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Ticket ID</th>
                                <th>Subject</th>
                                <th style="width: 175px;">From User</th>
                                <th style="width: 175px;">To User</th>
                                <th style="width: 150px;">Created At</th>
                                <th style="width: 110px;">Status</th>
                                <th>Remark</th>
                                <th style="width: 110px;">Attachment</th>
                                <th style="width: 140px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allTickets as $ticket)
                                <tr data-status="{{ strtolower(trim($ticket->status)) }}"
                                    data-category-id="{{ $ticket->account_support_category_id ?? '' }}"
                                    data-subcategory-id="{{ $ticket->account_support_subcategory_id ?? '' }}">
                                    <td><strong>#{{ $ticket->id }}</strong></td>
                                <td>
                                    <div>
                                        <a href="javascript:void(0)" class="ticket-subject-link"
                                           data-ticket-id="{{ $ticket->id }}"
                                           data-subject="{{ $ticket->subject }}"
                                           data-creator="{{ $ticket->creator?->name ?? '-' }}"
                                           data-assigned="{{ $ticket->assignedTo?->name ?? '-' }}"
                                           data-created-at="{{ $ticket->created_at?->format('d M Y, h:i A') ?? '-' }}"
                                           data-status="{{ $ticket->status }}"
                                           data-remark="{{ $ticket->remark ?? '' }}"
                                           data-attachment="{{ $ticket->attachment_url ?? '' }}"
                                           data-category="{{ $ticket->category?->name ?? '' }}"
                                           data-subcategory="{{ $ticket->subcategory?->name ?? '' }}"
                                           data-is-expense="{{ $ticket->subcategory?->is_expense_add ? '1' : '0' }}"
                                           data-payment-amount="{{ $ticket->payment_amount ? number_format((float)$ticket->payment_amount, 2, '.', '') : '' }}"
                                           data-resolution-attachment="{{ $ticket->resolution_attachment_url ?? '' }}"
                                           style="color: #111827; text-decoration: none; font-weight: 800; transition: color 0.15s ease;">
                                            {{ $ticket->subject }}
                                        </a>
                                    </div>
                                    <div class="ticket-message-preview">{!! strip_tags($ticket->message) !!}</div>
                                    @if($ticket->category || $ticket->subcategory || $ticket->payment_amount)
                                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 5px; flex-wrap: wrap;">
                                            @if($ticket->category)
                                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                                    <i class="bi bi-folder2-open" style="font-size: 10px;"></i> {{ $ticket->category->name }}
                                                </span>
                                            @endif
                                            @if($ticket->subcategory)
                                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #fff1e8; color: #c2410c; border: 1px solid #fed7aa;">
                                                    <i class="bi bi-tag-fill" style="font-size: 10px;"></i> {{ $ticket->subcategory->name }}
                                                </span>
                                            @endif
                                            @if($ticket->subcategory?->is_expense_add)
                                                <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;" title="Expense Settlement Required on Close/Resolve">
                                                    <i class="bi bi-cash-coin" style="font-size: 10px;"></i> Expense
                                                </span>
                                            @endif
                                            @if($ticket->payment_amount !== null && (float)$ticket->payment_amount > 0)
                                                <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #fef3c7; color: #b45309; border: 1px solid #fde68a;" title="Settled Payment Amount">
                                                    <i class="bi bi-currency-rupee" style="font-size: 10px;"></i> {{ number_format((float)$ticket->payment_amount, 2) }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    <div id="ticket-msg-{{ $ticket->id }}" style="display:none;">{!! $ticket->message !!}</div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #fff1e8; border: 1px solid #fed7aa; color: #c2410c; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            {{ strtoupper(substr($ticket->creator?->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 700; color: #111827; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->creator?->name ?? '-' }}">
                                                {{ $ticket->creator?->name ?? 'Unknown' }}
                                            </div>
                                            @if($ticket->creator?->email)
                                                <div style="font-size: 11px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->creator->email }}">
                                                    {{ $ticket->creator->email }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            {{ strtoupper(substr($ticket->assignedTo?->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-weight: 700; color: #111827; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->assignedTo?->name ?? '-' }}">
                                                {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
                                            </div>
                                            @if($ticket->assignedTo?->email)
                                                <div style="font-size: 11px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->assignedTo->email }}">
                                                    {{ $ticket->assignedTo->email }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                                <td>
                                    <span class="status-badge {{ $ticket->status }}">
                                        {{ $ticket->status === 'onprocess' ? 'On Process' : ucfirst($ticket->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($ticket->remark)
                                        <em class="text-gray">{{ $ticket->remark }}</em>
                                    @else
                                        <span class="text-gray" style="font-size: 12px; opacity: 0.65;">No remarks yet</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ticket->attachment_path)
                                        <a href="{{ $ticket->attachment_url }}" target="_blank" style="color: #fe5f04; font-weight: 700;">
                                            <i class="bi bi-file-earmark-arrow-down"></i> View File
                                        </a>
                                    @else
                                        <span class="text-gray">-</span>
                                    @endif
                                    @if($ticket->resolution_attachment_path)
                                        <div style="margin-top: 4px;">
                                            <a href="{{ $ticket->resolution_attachment_url }}" target="_blank" style="color: #059669; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;" title="Payment Receipt / Resolution Invoice">
                                                <i class="bi bi-receipt"></i> Receipt
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($ticket->status === 'closed')
                                        <button type="button" class="btn-update-status" disabled style="opacity: 0.55; cursor: not-allowed; background-color: #e5e7eb; border-color: #d1d5db; color: #6b7280;" title="Ticket is closed and cannot be updated.">
                                            <i class="bi bi-lock-fill"></i> Closed
                                        </button>
                                    @else
                                        <button type="button" class="btn-update-status" 
                                                onclick="openUpdateModal({{ $ticket->id }}, '{{ $ticket->status }}', '{{ addslashes($ticket->remark ?? '') }}', {{ $ticket->subcategory?->is_expense_add ? 'true' : 'false' }}, '{{ $ticket->payment_amount ?? '' }}', '{{ addslashes($ticket->subcategory?->name ?? '') }}', '{{ $ticket->resolution_attachment_url ?? '' }}')">
                                            <i class="bi bi-pencil-square"></i> Update Status
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 44px 20px; color: #8e8e8e;">
                                    <i class="bi bi-inbox" style="font-size: 36px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 15px;">No support tickets found</div>
                                    <div style="font-size: 13px; color: #64748b; margin-top: 4px;">There are currently no support tickets in your company.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                @if($allTickets->hasPages())
                    <div style="padding: 16px 24px; border-top: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #ffffff;">
                        <div style="font-size: 13px; font-weight: 600; color: #6b7280;">
                            Showing <span style="font-weight: 800; color: #111827;">{{ $allTickets->firstItem() }}</span> to <span style="font-weight: 800; color: #111827;">{{ $allTickets->lastItem() }}</span> of <span style="font-weight: 800; color: #111827;">{{ $allTickets->total() }}</span> tickets
                        </div>
                        <div>
                            {{ $allTickets->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- RECEIVED TICKETS PANEL --}}
    <div id="received" class="tab-panel {{ $activeTab === 'received' ? 'active' : '' }}">
        <div class="ticket-card">
            <div class="ticket-table-responsive">
                <table class="support-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Ticket ID</th>
                            <th>Subject</th>
                            <th style="width: 175px;">From User</th>
                            <th style="width: 175px;">To User</th>
                            <th style="width: 150px;">Created At</th>
                            <th style="width: 110px;">Status</th>
                            <th>Remark</th>
                            <th style="width: 110px;">Attachment</th>
                            <th style="width: 140px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivedTickets as $ticket)
                            <tr data-status="{{ strtolower(trim($ticket->status)) }}"
                                data-category-id="{{ $ticket->account_support_category_id ?? '' }}"
                                data-subcategory-id="{{ $ticket->account_support_subcategory_id ?? '' }}">
                                <td><strong>#{{ $ticket->id }}</strong></td>
                            <td>
                                <div>
                                    <a href="javascript:void(0)" class="ticket-subject-link"
                                       data-ticket-id="{{ $ticket->id }}"
                                       data-subject="{{ $ticket->subject }}"
                                       data-creator="{{ $ticket->creator?->name ?? '-' }}"
                                       data-assigned="{{ $ticket->assignedTo?->name ?? '-' }}"
                                       data-created-at="{{ $ticket->created_at?->format('d M Y, h:i A') ?? '-' }}"
                                       data-status="{{ $ticket->status }}"
                                       data-remark="{{ $ticket->remark ?? '' }}"
                                       data-attachment="{{ $ticket->attachment_url ?? '' }}"
                                       data-category="{{ $ticket->category?->name ?? '' }}"
                                       data-subcategory="{{ $ticket->subcategory?->name ?? '' }}"
                                       data-is-expense="{{ $ticket->subcategory?->is_expense_add ? '1' : '0' }}"
                                       data-payment-amount="{{ $ticket->payment_amount ? number_format((float)$ticket->payment_amount, 2, '.', '') : '' }}"
                                       data-resolution-attachment="{{ $ticket->resolution_attachment_url ?? '' }}"
                                       style="color: #111827; text-decoration: none; font-weight: 800; transition: color 0.15s ease;">
                                        {{ $ticket->subject }}
                                    </a>
                                </div>
                                <div class="ticket-message-preview">{!! strip_tags($ticket->message) !!}</div>
                                @if($ticket->category || $ticket->subcategory || $ticket->payment_amount)
                                    <div style="display: flex; align-items: center; gap: 6px; margin-top: 5px; flex-wrap: wrap;">
                                        @if($ticket->category)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                                <i class="bi bi-folder2-open" style="font-size: 10px;"></i> {{ $ticket->category->name }}
                                            </span>
                                        @endif
                                        @if($ticket->subcategory)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #fff1e8; color: #c2410c; border: 1px solid #fed7aa;">
                                                <i class="bi bi-tag-fill" style="font-size: 10px;"></i> {{ $ticket->subcategory->name }}
                                            </span>
                                        @endif
                                        @if($ticket->subcategory?->is_expense_add)
                                            <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;" title="Expense Settlement Required on Close/Resolve">
                                                <i class="bi bi-cash-coin" style="font-size: 10px;"></i> Expense
                                            </span>
                                        @endif
                                        @if($ticket->payment_amount !== null && (float)$ticket->payment_amount > 0)
                                            <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #fef3c7; color: #b45309; border: 1px solid #fde68a;" title="Settled Payment Amount">
                                                <i class="bi bi-currency-rupee" style="font-size: 10px;"></i> {{ number_format((float)$ticket->payment_amount, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <div id="ticket-msg-{{ $ticket->id }}" style="display:none;">{!! $ticket->message !!}</div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #fff1e8; border: 1px solid #fed7aa; color: #c2410c; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        {{ strtoupper(substr($ticket->creator?->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 700; color: #111827; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->creator?->name ?? '-' }}">
                                            {{ $ticket->creator?->name ?? 'Unknown' }}
                                        </div>
                                        @if($ticket->creator?->email)
                                            <div style="font-size: 11px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->creator->email }}">
                                                {{ $ticket->creator->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        {{ strtoupper(substr($ticket->assignedTo?->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 700; color: #111827; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->assignedTo?->name ?? '-' }}">
                                            {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
                                        </div>
                                        @if($ticket->assignedTo?->email)
                                            <div style="font-size: 11px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->assignedTo->email }}">
                                                {{ $ticket->assignedTo->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="status-badge {{ $ticket->status }}">
                                    {{ $ticket->status === 'onprocess' ? 'On Process' : ucfirst($ticket->status) }}
                                </span>
                            </td>
                            <td>
                                @if($ticket->remark)
                                    <em class="text-gray">{{ $ticket->remark }}</em>
                                @else
                                    <span class="text-gray" style="font-size: 12px; opacity: 0.65;">No remarks yet</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->attachment_path)
                                    <a href="{{ $ticket->attachment_url }}" target="_blank" style="color: #fe5f04; font-weight: 700;">
                                        <i class="bi bi-file-earmark-arrow-down"></i> View File
                                    </a>
                                @else
                                    <span class="text-gray">-</span>
                                @endif
                                @if($ticket->resolution_attachment_path)
                                    <div style="margin-top: 4px;">
                                        <a href="{{ $ticket->resolution_attachment_url }}" target="_blank" style="color: #059669; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;" title="Payment Receipt / Resolution Invoice">
                                            <i class="bi bi-receipt"></i> Receipt
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($ticket->status === 'closed')
                                    <button type="button" class="btn-update-status" disabled style="opacity: 0.55; cursor: not-allowed; background-color: #e5e7eb; border-color: #d1d5db; color: #6b7280;" title="Ticket is closed and cannot be updated.">
                                        <i class="bi bi-lock-fill"></i> Closed
                                    </button>
                                @else
                                    <button type="button" class="btn-update-status" 
                                            onclick="openUpdateModal({{ $ticket->id }}, '{{ $ticket->status }}', '{{ addslashes($ticket->remark ?? '') }}', {{ $ticket->subcategory?->is_expense_add ? 'true' : 'false' }}, '{{ $ticket->payment_amount ?? '' }}', '{{ addslashes($ticket->subcategory?->name ?? '') }}', '{{ $ticket->resolution_attachment_url ?? '' }}')">
                                        <i class="bi bi-pencil-square"></i> Update Status
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #8e8e8e;">
                                <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                                No received tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            @if($receivedTickets->hasPages())
                <div style="padding: 16px 24px; border-top: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #ffffff;">
                    <div style="font-size: 13px; font-weight: 600; color: #6b7280;">
                        Showing <span style="font-weight: 800; color: #111827;">{{ $receivedTickets->firstItem() }}</span> to <span style="font-weight: 800; color: #111827;">{{ $receivedTickets->lastItem() }}</span> of <span style="font-weight: 800; color: #111827;">{{ $receivedTickets->total() }}</span> tickets
                    </div>
                    <div>
                        {{ $receivedTickets->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- CREATED TICKETS PANEL --}}
    <div id="created" class="tab-panel {{ $activeTab === 'created' ? 'active' : '' }}">
        <div class="ticket-card">
            <div class="ticket-table-responsive">
                <table class="support-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Ticket ID</th>
                            <th>Subject</th>
                            <th style="width: 175px;">From User</th>
                            <th style="width: 175px;">To User</th>
                            <th style="width: 150px;">Created At</th>
                            <th style="width: 110px;">Status</th>
                            <th>Remark</th>
                            <th style="width: 110px;">Attachment</th>
                            <th style="width: 140px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($createdTickets as $ticket)
                            <tr data-status="{{ strtolower(trim($ticket->status)) }}"
                                data-category-id="{{ $ticket->account_support_category_id ?? '' }}"
                                data-subcategory-id="{{ $ticket->account_support_subcategory_id ?? '' }}">
                                <td><strong>#{{ $ticket->id }}</strong></td>
                            <td>
                                <div>
                                    <a href="javascript:void(0)" class="ticket-subject-link"
                                       data-ticket-id="{{ $ticket->id }}"
                                       data-subject="{{ $ticket->subject }}"
                                       data-creator="{{ $ticket->creator?->name ?? '-' }}"
                                       data-assigned="{{ $ticket->assignedTo?->name ?? '-' }}"
                                       data-created-at="{{ $ticket->created_at?->format('d M Y, h:i A') ?? '-' }}"
                                       data-status="{{ $ticket->status }}"
                                       data-remark="{{ $ticket->remark ?? '' }}"
                                       data-attachment="{{ $ticket->attachment_url ?? '' }}"
                                       data-category="{{ $ticket->category?->name ?? '' }}"
                                       data-subcategory="{{ $ticket->subcategory?->name ?? '' }}"
                                       data-is-expense="{{ $ticket->subcategory?->is_expense_add ? '1' : '0' }}"
                                       data-payment-amount="{{ $ticket->payment_amount ? number_format((float)$ticket->payment_amount, 2, '.', '') : '' }}"
                                       data-resolution-attachment="{{ $ticket->resolution_attachment_url ?? '' }}"
                                       style="color: #111827; text-decoration: none; font-weight: 800; transition: color 0.15s ease;">
                                        {{ $ticket->subject }}
                                    </a>
                                </div>
                                <div class="ticket-message-preview">{!! strip_tags($ticket->message) !!}</div>
                                @if($ticket->category || $ticket->subcategory || $ticket->payment_amount)
                                    <div style="display: flex; align-items: center; gap: 6px; margin-top: 5px; flex-wrap: wrap;">
                                        @if($ticket->category)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                                <i class="bi bi-folder2-open" style="font-size: 10px;"></i> {{ $ticket->category->name }}
                                            </span>
                                        @endif
                                        @if($ticket->subcategory)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #fff1e8; color: #c2410c; border: 1px solid #fed7aa;">
                                                <i class="bi bi-tag-fill" style="font-size: 10px;"></i> {{ $ticket->subcategory->name }}
                                            </span>
                                        @endif
                                        @if($ticket->subcategory?->is_expense_add)
                                            <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;" title="Expense Settlement Required on Close/Resolve">
                                                <i class="bi bi-cash-coin" style="font-size: 10px;"></i> Expense
                                            </span>
                                        @endif
                                        @if($ticket->payment_amount !== null && (float)$ticket->payment_amount > 0)
                                            <span style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #fef3c7; color: #b45309; border: 1px solid #fde68a;" title="Settled Payment Amount">
                                                <i class="bi bi-currency-rupee" style="font-size: 10px;"></i> {{ number_format((float)$ticket->payment_amount, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <div id="ticket-msg-{{ $ticket->id }}" style="display:none;">{!! $ticket->message !!}</div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #fff1e8; border: 1px solid #fed7aa; color: #c2410c; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        {{ strtoupper(substr($ticket->creator?->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 700; color: #111827; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->creator?->name ?? '-' }}">
                                            {{ $ticket->creator?->name ?? 'Unknown' }}
                                        </div>
                                        @if($ticket->creator?->email)
                                            <div style="font-size: 11px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->creator->email }}">
                                                {{ $ticket->creator->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        {{ strtoupper(substr($ticket->assignedTo?->name ?? 'U', 0, 2)) }}
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 700; color: #111827; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->assignedTo?->name ?? '-' }}">
                                            {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
                                        </div>
                                        @if($ticket->assignedTo?->email)
                                            <div style="font-size: 11px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $ticket->assignedTo->email }}">
                                                {{ $ticket->assignedTo->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="status-badge {{ $ticket->status }}">
                                    {{ $ticket->status === 'onprocess' ? 'On Process' : ucfirst($ticket->status) }}
                                </span>
                            </td>
                            <td>
                                @if($ticket->remark)
                                    <em class="text-gray">{{ $ticket->remark }}</em>
                                @else
                                    <span class="text-gray" style="font-size: 12px; opacity: 0.65;">Pending review</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->attachment_path)
                                    <a href="{{ $ticket->attachment_url }}" target="_blank" style="color: #fe5f04; font-weight: 700;">
                                        <i class="bi bi-file-earmark-arrow-down"></i> View File
                                    </a>
                                @else
                                    <span class="text-gray">-</span>
                                @endif
                                @if($ticket->resolution_attachment_path)
                                    <div style="margin-top: 4px;">
                                        <a href="{{ $ticket->resolution_attachment_url }}" target="_blank" style="color: #059669; font-weight: 700; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;" title="Payment Receipt / Resolution Invoice">
                                            <i class="bi bi-receipt"></i> Receipt
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($isCompanyAdmin)
                                    @if($ticket->status === 'closed')
                                        <button type="button" class="btn-update-status" disabled style="opacity: 0.55; cursor: not-allowed; background-color: #e5e7eb; border-color: #d1d5db; color: #6b7280;" title="Ticket is closed and cannot be updated.">
                                            <i class="bi bi-lock-fill"></i> Closed
                                        </button>
                                    @else
                                        <button type="button" class="btn-update-status" 
                                                onclick="openUpdateModal({{ $ticket->id }}, '{{ $ticket->status }}', '{{ addslashes($ticket->remark ?? '') }}', {{ $ticket->subcategory?->is_expense_add ? 'true' : 'false' }}, '{{ $ticket->payment_amount ?? '' }}', '{{ addslashes($ticket->subcategory?->name ?? '') }}', '{{ $ticket->resolution_attachment_url ?? '' }}')">
                                            <i class="bi bi-pencil-square"></i> Update Status
                                        </button>
                                    @endif
                                @else
                                    <span class="text-gray" style="font-size: 12px; opacity: 0.7;">Sent</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #8e8e8e;">
                                <i class="bi bi-send" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                                You haven't created any support tickets yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            @if($createdTickets->hasPages())
                <div style="padding: 16px 24px; border-top: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #ffffff;">
                    <div style="font-size: 13px; font-weight: 600; color: #6b7280;">
                        Showing <span style="font-weight: 800; color: #111827;">{{ $createdTickets->firstItem() }}</span> to <span style="font-weight: 800; color: #111827;">{{ $createdTickets->lastItem() }}</span> of <span style="font-weight: 800; color: #111827;">{{ $createdTickets->total() }}</span> tickets
                    </div>
                    <div>
                        {{ $createdTickets->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
    </div>
</div>

<!-- CREATE TICKET MODAL -->
<div id="createTicketModal" class="support-modal">
    <div class="support-modal-content" style="width: 750px;">
        <form id="createTicketForm" method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="support-modal-header">
                <h3 class="support-modal-title">Create Support Ticket</h3>
                <button type="button" class="support-modal-close" onclick="closeCreateModal()">&times;</button>
            </div>
            <div class="support-modal-body">
                <div class="form-group">
                    <label for="to_user_id">Submit To (Person)</label>
                    <select name="to_user_id" id="to_user_id" class="form-control select2" required style="width: 100%;">
                        <option value="">Select a user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" placeholder="Brief summary of the issue" required>
                </div>

                <div class="form-group">
                    <label for="ticketMessage">Message</label>
                    <textarea name="message" id="ticketMessage" class="form-control" rows="8"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="attachment">Attachment</label>
                    <input type="file" name="attachment" id="attachment" class="form-control" style="padding: 6px 12px;">
                    <small style="color: #8e8e8e; margin-top: 4px; display: block;">Supported files: PDF, Word, Excel, Images, Zip, Txt (Max: 10MB)</small>
                </div>
            </div>
            <div class="support-modal-footer">
                <button type="button" class="btn-secondary" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="btn-submit">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- UPDATE STATUS MODAL -->
<div id="updateStatusModal" class="support-modal">
    <div class="support-modal-content">
        <form id="updateStatusForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            <div class="support-modal-header">
                <h3 class="support-modal-title">Update Ticket Status & Remarks</h3>
                <button type="button" class="support-modal-close" onclick="closeUpdateModal()">&times;</button>
            </div>
            <div class="support-modal-body">
                <!-- Subcategory Expense Notice (Dynamic) -->
                <div id="ticket_is_expense_notice" style="display: none; margin-bottom: 16px; padding: 12px 16px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #065f46;">
                        <i class="bi bi-wallet2" style="font-size: 16px; color: #059669;"></i>
                        <span>Expense Subcategory: <span id="ticket_notice_subcategory"></span></span>
                    </div>
                    <div style="font-size: 12px; color: #047857; margin-top: 4px; line-height: 1.4;">
                        Resolving or closing this ticket requires Payment Amount, Remarks, and an optional/required receipt attachment. It will automatically synchronize into Accounts Expenses.
                    </div>
                </div>

                <div class="form-group">
                    <label for="ticket_status">Status <span class="required-star">*</span></label>
                    <select name="status" id="ticket_status" class="form-control" required onchange="handleStatusChange()">
                        <option value="pending">Pending</option>
                        <option value="onprocess">On Process</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <!-- Dynamic Expense Fields (shown when is_expense_add is true AND status is resolved or closed) -->
                <div id="expenseFieldsGroup" style="display: none; background: #fafafa; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 18px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                        <i class="bi bi-cash-stack" style="color: #fe5f04;"></i> Expense Settlement Details
                    </div>

                    <div class="form-group">
                        <label for="ticket_payment_amount">Payment Amount (₹) <span class="required-star">*</span></label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #6b7280;">₹</span>
                            <input type="number" step="0.01" min="0" name="payment_amount" id="ticket_payment_amount" class="form-control" style="padding-left: 32px;" placeholder="0.00">
                        </div>
                        <small class="form-text text-muted" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">Enter the total amount spent/paid for this ticket (e.g. Domain purchase price).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="ticket_resolution_attachment">Attachments (Invoice / Receipt / Bill)</label>
                        <input type="file" name="resolution_attachment" id="ticket_resolution_attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        <div id="ticket_existing_attachment_wrap" style="display: none; margin-top: 6px; font-size: 12px;">
                            <span style="color: #4b5563;">Current Attachment: </span>
                            <a id="ticket_existing_attachment_link" href="#" target="_blank" style="color: #fe5f04; font-weight: 700; text-decoration: none;">
                                <i class="bi bi-file-earmark-check"></i> View Existing Receipt
                            </a>
                        </div>
                        <small class="form-text text-muted" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">Upload invoice, payment slip, or domain purchase receipt (PDF, JPG, PNG up to 25MB).</small>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="ticket_remark">Remark / Update Message <span id="remark_required_star" class="required-star" style="display: none;">*</span></label>
                    <textarea name="remark" id="ticket_remark" class="form-control" rows="4" placeholder="Enter details about status resolution, notes, or messages for the creator..."></textarea>
                </div>
            </div>
            <div class="support-modal-footer">
                <button type="button" class="btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                <button type="button" class="btn-submit" onclick="showConfirmModal()">Update Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW TICKET MODAL -->
<div id="viewTicketModal" class="support-modal">
    <div class="support-modal-content" style="width: 700px;">
        <div class="support-modal-header">
            <h3 class="support-modal-title" id="vt_subject_title">Ticket Details</h3>
            <button type="button" class="support-modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="support-modal-body" style="padding: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 16px;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Ticket ID</div>
                    <div id="vt_id" style="font-size: 16px; font-weight: 800; color: #fe5f04; margin-top: 4px;">#123</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Status</div>
                    <div style="margin-top: 4px;">
                        <span class="status-badge" id="vt_status">Pending</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;" id="vt_category_container">
                <span id="vt_category_badge" style="display: none; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                    <i class="bi bi-folder2-open"></i> <span id="vt_category_text"></span>
                </span>
                <span id="vt_subcategory_badge" style="display: none; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #fff1e8; color: #c2410c; border: 1px solid #fed7aa;">
                    <i class="bi bi-tag-fill"></i> <span id="vt_subcategory_text"></span>
                </span>
                <span id="vt_expense_badge" style="display: none; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                    <i class="bi bi-cash-coin"></i> Expense Ticket
                </span>
            </div>

            <div id="vt_payment_section" style="display: none; margin-bottom: 20px; background: #ecfdf5; border-radius: 12px; padding: 14px 18px; border: 1px solid #a7f3d0;">
                <div style="font-size: 11px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">Settled Payment Amount</div>
                <div id="vt_payment_amount" style="font-size: 20px; font-weight: 800; color: #065f46; margin-top: 3px;">₹0.00</div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">From</div>
                    <div id="vt_from" style="font-size: 14px; font-weight: 700; color: #111827; margin-top: 4px;">John Doe</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">To</div>
                    <div id="vt_to" style="font-size: 14px; font-weight: 700; color: #111827; margin-top: 4px;">Jane Smith</div>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Date Created</div>
                <div id="vt_date" style="font-size: 14px; color: #1f2937; margin-top: 4px;">17 Jul 2026, 12:00 PM</div>
            </div>

            <div style="margin-bottom: 24px; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0;">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">Message</div>
                <div id="vt_message" style="font-size: 14px; color: #334155; line-height: 1.6; word-break: break-word;"></div>
            </div>

            <div style="margin-bottom: 24px;" id="vt_attachment_section">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Ticket Attachment</div>
                <div style="margin-top: 6px;">
                    <a id="vt_attachment_link" href="#" target="_blank" style="color: #fe5f04; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="bi bi-file-earmark-arrow-down" style="font-size: 18px;"></i> View Attached File
                    </a>
                    <span id="vt_no_attachment" class="text-gray" style="font-size: 14px; color: #9ca3af;">No attachment provided</span>
                </div>
                <div id="vt_resolution_attachment_section" style="display: none; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #e2e8f0;">
                    <div style="font-size: 11px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">Payment / Resolution Attachment</div>
                    <div style="margin-top: 6px;">
                        <a id="vt_resolution_link" href="#" target="_blank" style="color: #059669; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-receipt" style="font-size: 18px;"></i> View Payment Receipt / Invoice
                        </a>
                    </div>
                </div>
            </div>

            <div style="padding-top: 20px; border-top: 1px dashed #e5e7eb;" id="vt_remark_section">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Resolution Remarks</div>
                <div id="vt_remark" style="font-size: 14px; color: #475569; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 14px 18px; line-height: 1.5; font-style: italic; display: block;">
                    No remarks yet.
                </div>
            </div>
        </div>
        <div class="support-modal-footer">
            <button type="button" class="btn-secondary" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<!-- CONFIRMATION MODAL -->
<div id="confirmUpdateModal" class="support-modal" style="z-index: 2100;">
    <div class="support-modal-content" style="width: 450px;">
        <div class="support-modal-header" style="background: #fffbeb; border-bottom: 1px solid #fef3c7;">
            <h3 class="support-modal-title" style="color: #b45309; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-exclamation-triangle-fill"></i> Confirm Update
            </h3>
            <button type="button" class="support-modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="support-modal-body" style="padding: 24px; text-align: center;">
            <p style="font-size: 15px; font-weight: 700; color: #1f2937; margin: 0 0 10px;">Are you sure you want to update this ticket?</p>
            <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.5;">An email notification will be sent to the creator with the updated status and remarks.</p>
        </div>
        <div class="support-modal-footer">
            <button type="button" class="btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn-submit" id="btn-confirm-submit" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);">Yes, Update</button>
        </div>
    </div>
</div>

<!-- PROCESS OVERLAY / PROGRESS BAR MODAL -->
<div id="supportProcessOverlay" class="support-process-overlay" style="display: none;">
    <div class="support-process-card">
        <div class="support-process-icon-wrap">
            <div class="support-process-spinner"></div>
            <i class="bi bi-envelope-paper-fill support-process-icon"></i>
        </div>
        <h4 id="processOverlayTitle" class="support-process-title">Sending Email & Processing...</h4>
        <p id="processOverlaySubtitle" class="support-process-subtitle">Please wait while the email notification is being sent...</p>

        <div class="support-progress-wrapper">
            <div class="support-progress-bar">
                <div id="supportProgressFill" class="support-progress-fill"></div>
            </div>
            <div class="support-progress-status">
                <span id="supportProgressText">Preparing email notification...</span>
                <span id="supportProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- TinyMCE self-hosted CDN build --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    // Tab switching logic
    function switchTab(evt, tabName) {
        var i, tabcontent, tablinks;

        tabcontent = document.getElementsByClassName("tab-panel");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }

        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        const targetPanel = document.getElementById(tabName);
        if (targetPanel) {
            targetPanel.classList.add("active");
        }
        evt.currentTarget.classList.add("active");

        // Reapply active filters to the new tab
        applySupportFilters();
    }

    // Card status filter state
    let currentCardStatusFilter = 'all';

    function filterByCardStatus(status) {
        if (currentCardStatusFilter === status && status !== 'all') {
            currentCardStatusFilter = 'all'; // toggle back to all
        } else {
            currentCardStatusFilter = status;
        }

        // Update active class on KPI cards
        document.querySelectorAll('.support-kpi-card').forEach(card => {
            if (card.getAttribute('data-status-filter') === currentCardStatusFilter) {
                card.classList.add('active-filter');
            } else {
                card.classList.remove('active-filter');
            }
        });

        applySupportFilters();
    }

    // Filter tickets in active tab (search input)
    function filterSupportTickets(val) {
        applySupportFilters();
    }

    // Combined filter function for Search + Category + Subcategory + KPI Card Status
    function applySupportFilters() {
        const searchInput = document.getElementById('ticketSearchInput');
        const q = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const categorySelect = document.getElementById('filterCategoryId');
        const subcategorySelect = document.getElementById('filterSubcategoryId');
        const selectedCatId = categorySelect ? categorySelect.value.trim() : '';
        const selectedSubcatId = subcategorySelect ? subcategorySelect.value.trim() : '';

        const activePanel = document.querySelector('.tab-panel.active');
        if (!activePanel) return;

        const rows = activePanel.querySelectorAll('tbody tr:not(.dynamic-filter-empty-row)');
        let visibleCount = 0;

        rows.forEach(row => {
            // Skip blade empty state rows if present
            if (row.querySelector('td[colspan]')) return;

            const rowStatus = (row.getAttribute('data-status') || '').toLowerCase().trim();
            const rowCatId = (row.getAttribute('data-category-id') || '').trim();
            const rowSubcatId = (row.getAttribute('data-subcategory-id') || '').trim();
            const rowText = row.textContent.toLowerCase();

            // Status filter check
            let matchesStatus = false;
            if (currentCardStatusFilter === 'all') {
                matchesStatus = true;
            } else if (currentCardStatusFilter === 'resolved_closed') {
                matchesStatus = (rowStatus === 'resolved' || rowStatus === 'closed');
            } else {
                matchesStatus = (rowStatus === currentCardStatusFilter);
            }

            // Category filter check
            let matchesCategory = (!selectedCatId || rowCatId === selectedCatId);

            // Subcategory filter check
            let matchesSubcategory = (!selectedSubcatId || rowSubcatId === selectedSubcatId);

            // Search query check
            let matchesSearch = (!q || rowText.includes(q));

            if (matchesStatus && matchesCategory && matchesSubcategory && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Dynamic empty state message if no rows match filter
        let dynamicEmptyRow = activePanel.querySelector('.dynamic-filter-empty-row');
        const tbody = activePanel.querySelector('tbody');
        const hasBaseEmptyRow = tbody ? tbody.querySelector('td[colspan]:not(.dynamic-filter-empty-row td)') : null;

        if (tbody && !hasBaseEmptyRow && rows.length > 0) {
            if (visibleCount === 0) {
                if (!dynamicEmptyRow) {
                    dynamicEmptyRow = document.createElement('tr');
                    dynamicEmptyRow.className = 'dynamic-filter-empty-row';
                    dynamicEmptyRow.innerHTML = `
                        <td colspan="9" style="text-align: center; padding: 40px 20px; color: #6b7280;">
                            <i class="bi bi-funnel" style="font-size: 32px; display: block; margin-bottom: 8px; color: #9ca3af;"></i>
                            <span style="font-weight: 700; font-size: 14px; color: #374151;">No tickets match the selected filter.</span>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Try adjusting category, subcategory, or search filters.</div>
                        </td>
                    `;
                    tbody.appendChild(dynamicEmptyRow);
                }
                dynamicEmptyRow.style.display = '';
            } else if (dynamicEmptyRow) {
                dynamicEmptyRow.style.display = 'none';
            }
        }
    }

    // Dynamic Subcategory population and filter trigger
    const allCategoriesData = @json($categories ?? []);
    const catSelect = document.getElementById('filterCategoryId');
    const subcatSelect = document.getElementById('filterSubcategoryId');

    function populateSubcategories(selectedCatId, preselectedSubcatId) {
        if (!subcatSelect) return;
        subcatSelect.innerHTML = '<option value="">All Subcategories</option>';

        if (selectedCatId) {
            const foundCat = allCategoriesData.find(c => String(c.id) === String(selectedCatId));
            if (foundCat && foundCat.active_subcategories && foundCat.active_subcategories.length > 0) {
                foundCat.active_subcategories.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name + (sub.code ? ` (${sub.code})` : '');
                    opt.setAttribute('data-category-id', foundCat.id);
                    if (preselectedSubcatId && String(sub.id) === String(preselectedSubcatId)) {
                        opt.selected = true;
                    }
                    subcatSelect.appendChild(opt);
                });
            }
        } else {
            // When all categories selected, show all subcategories grouped by category
            allCategoriesData.forEach(cat => {
                if (cat.active_subcategories && cat.active_subcategories.length > 0) {
                    const grp = document.createElement('optgroup');
                    grp.label = cat.name + (cat.code ? ` (${cat.code})` : '');
                    cat.active_subcategories.forEach(sub => {
                        const opt = document.createElement('option');
                        opt.value = sub.id;
                        opt.textContent = sub.name + (sub.code ? ` (${sub.code})` : '');
                        opt.setAttribute('data-category-id', cat.id);
                        if (preselectedSubcatId && String(sub.id) === String(preselectedSubcatId)) {
                            opt.selected = true;
                        }
                        grp.appendChild(opt);
                    });
                    subcatSelect.appendChild(grp);
                }
            });
        }
    }

    if (catSelect && subcatSelect) {
        catSelect.addEventListener('change', function () {
            populateSubcategories(this.value);
            applySupportFilters();
        });

        subcatSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            const parentCatId = selectedOpt ? selectedOpt.getAttribute('data-category-id') : null;
            if (parentCatId && !catSelect.value) {
                catSelect.value = parentCatId;
                populateSubcategories(parentCatId, this.value);
            }
            applySupportFilters();
        });
    }

    // Modal Control Logic
    function openCreateModal() {
        const modal = document.getElementById('createTicketModal');
        modal.classList.add('show');

        // TinyMCE Init
        if (window.tinymce && !window.tinymce.get('ticketMessage')) {
            window.tinymce.init({
                selector: 'textarea#ticketMessage',
                base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
                height: 280,
                menubar: false,
                plugins: 'lists link code wordcount',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link code',
                setup: function (editor) {
                    editor.on('change keyup', function () {
                        window.tinymce.triggerSave();
                    });
                }
            });
        }

        // Select2 re-initialization if needed
        if (window.jQuery && jQuery().select2) {
            $('#to_user_id').select2({
                dropdownParent: $('#createTicketModal')
            });
        }
    }

    function closeCreateModal() {
        const modal = document.getElementById('createTicketModal');
        modal.classList.remove('show');
    }

    let currentTicketIsExpense = false;

    function openUpdateModal(ticketId, currentStatus, currentRemark, isExpense = false, paymentAmount = '', subcategoryName = '', resolutionAttachmentUrl = '') {
        const modal = document.getElementById('updateStatusModal');
        const form = document.getElementById('updateStatusForm');
        
        // Dynamically set action URL
        form.action = `/support/${ticketId}/update-status`;
        
        // Pre-fill form fields
        document.getElementById('ticket_status').value = currentStatus;
        document.getElementById('ticket_remark').value = currentRemark || '';
        
        currentTicketIsExpense = Boolean(isExpense);

        // Notice section
        const noticeEl = document.getElementById('ticket_is_expense_notice');
        const noticeSubcat = document.getElementById('ticket_notice_subcategory');
        if (currentTicketIsExpense) {
            noticeEl.style.display = 'block';
            noticeSubcat.innerText = subcategoryName || 'Expense Applicable';
        } else {
            noticeEl.style.display = 'none';
        }

        // Payment Amount
        const amountInput = document.getElementById('ticket_payment_amount');
        amountInput.value = paymentAmount ? parseFloat(paymentAmount) : '';

        // File / Attachment
        const fileInput = document.getElementById('ticket_resolution_attachment');
        if (fileInput) fileInput.value = '';
        const existingAttachWrap = document.getElementById('ticket_existing_attachment_wrap');
        const existingAttachLink = document.getElementById('ticket_existing_attachment_link');
        if (resolutionAttachmentUrl) {
            existingAttachWrap.style.display = 'block';
            existingAttachLink.href = resolutionAttachmentUrl;
        } else {
            existingAttachWrap.style.display = 'none';
        }

        handleStatusChange();
        
        modal.classList.add('show');
    }

    function handleStatusChange() {
        const status = document.getElementById('ticket_status').value;
        const isClosingOrResolving = (status === 'resolved' || status === 'closed');
        const expenseGroup = document.getElementById('expenseFieldsGroup');
        const amountInput = document.getElementById('ticket_payment_amount');
        const remarkInput = document.getElementById('ticket_remark');
        const remarkStar = document.getElementById('remark_required_star');

        if (currentTicketIsExpense && isClosingOrResolving) {
            if (expenseGroup) expenseGroup.style.display = 'block';
            if (amountInput) amountInput.required = true;
            if (remarkInput) remarkInput.required = true;
            if (remarkStar) remarkStar.style.display = 'inline';
        } else {
            if (expenseGroup) expenseGroup.style.display = 'none';
            if (amountInput) amountInput.required = false;
            if (remarkInput) remarkInput.required = false;
            if (remarkStar) remarkStar.style.display = 'none';
        }
    }

    function closeUpdateModal() {
        const modal = document.getElementById('updateStatusModal');
        modal.classList.remove('show');
    }

    function openViewModal(ticket) {
        document.getElementById('vt_id').innerText = '#' + ticket.id;
        document.getElementById('vt_subject_title').innerText = ticket.subject;
        document.getElementById('vt_from').innerText = ticket.creator_name;
        document.getElementById('vt_to').innerText = ticket.assigned_to_name;
        document.getElementById('vt_date').innerText = ticket.created_at;
        document.getElementById('vt_message').innerHTML = ticket.message;

        // Status Badge
        const statusBadge = document.getElementById('vt_status');
        statusBadge.innerText = ticket.status === 'onprocess' ? 'On Process' : (ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1));
        statusBadge.className = 'status-badge ' + ticket.status;

        // Category & Subcategory Badges
        const catBadge = document.getElementById('vt_category_badge');
        const subBadge = document.getElementById('vt_subcategory_badge');
        const expenseBadge = document.getElementById('vt_expense_badge');
        if (ticket.category) {
            document.getElementById('vt_category_text').innerText = ticket.category;
            catBadge.style.display = 'inline-flex';
        } else {
            catBadge.style.display = 'none';
        }
        if (ticket.subcategory) {
            document.getElementById('vt_subcategory_text').innerText = ticket.subcategory;
            subBadge.style.display = 'inline-flex';
        } else {
            subBadge.style.display = 'none';
        }
        if (ticket.is_expense && ticket.is_expense !== '0') {
            expenseBadge.style.display = 'inline-flex';
        } else {
            expenseBadge.style.display = 'none';
        }

        // Settled Payment Amount
        const paymentSection = document.getElementById('vt_payment_section');
        if (ticket.payment_amount && parseFloat(ticket.payment_amount) > 0) {
            document.getElementById('vt_payment_amount').innerText = '₹' + parseFloat(ticket.payment_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            paymentSection.style.display = 'block';
        } else {
            paymentSection.style.display = 'none';
        }

        // Attachment section
        const attachmentLink = document.getElementById('vt_attachment_link');
        const noAttachment = document.getElementById('vt_no_attachment');
        if (ticket.attachment_path) {
            attachmentLink.href = ticket.attachment_path;
            attachmentLink.style.display = 'inline-flex';
            noAttachment.style.display = 'none';
        } else {
            attachmentLink.style.display = 'none';
            noAttachment.style.display = 'inline';
        }

        // Resolution attachment section
        const resolutionSection = document.getElementById('vt_resolution_attachment_section');
        const resolutionLink = document.getElementById('vt_resolution_link');
        if (ticket.resolution_attachment) {
            resolutionLink.href = ticket.resolution_attachment;
            resolutionSection.style.display = 'block';
        } else {
            resolutionSection.style.display = 'none';
        }

        // Remarks section
        const remarkEl = document.getElementById('vt_remark');
        if (ticket.remark) {
            remarkEl.innerText = ticket.remark;
        } else {
            remarkEl.innerText = 'No remarks yet';
        }

        const modal = document.getElementById('viewTicketModal');
        modal.classList.add('show');
    }

    function closeViewModal() {
        const modal = document.getElementById('viewTicketModal');
        modal.classList.remove('show');
    }

    function showConfirmModal() {
        // Validate form fields are filled (status is required, and payment amount/remark if expense ticket resolving/closing)
        const updateForm = document.getElementById('updateStatusForm');
        if (updateForm && !updateForm.checkValidity()) {
            updateForm.reportValidity();
            return;
        }
        
        const modal = document.getElementById('confirmUpdateModal');
        modal.classList.add('show');
    }

    function closeConfirmModal() {
        const modal = document.getElementById('confirmUpdateModal');
        modal.classList.remove('show');
    }

    // Close modals on clicking background wrapper
    window.addEventListener('click', function(e) {
        const createModal = document.getElementById('createTicketModal');
        const updateModal = document.getElementById('updateStatusModal');
        const viewModal = document.getElementById('viewTicketModal');
        const confirmModal = document.getElementById('confirmUpdateModal');
        if (e.target === createModal) {
            closeCreateModal();
        }
        if (e.target === updateModal) {
            closeUpdateModal();
        }
        if (e.target === viewModal) {
            closeViewModal();
        }
        if (e.target === confirmModal) {
            closeConfirmModal();
        }
    });

    // Progress bar overlay logic
    let processProgressInterval = null;

    function showProcessOverlay(title, subtitle) {
        if (title) document.getElementById('processOverlayTitle').innerText = title;
        if (subtitle) document.getElementById('processOverlaySubtitle').innerText = subtitle;

        const overlay = document.getElementById('supportProcessOverlay');
        const fill = document.getElementById('supportProgressFill');
        const percentText = document.getElementById('supportProgressPercent');
        const statusText = document.getElementById('supportProgressText');

        overlay.style.display = 'flex';

        let currentProgress = 5;
        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
        statusText.innerText = 'Connecting to server...';

        if (processProgressInterval) clearInterval(processProgressInterval);

        processProgressInterval = setInterval(function() {
            if (currentProgress < 30) {
                currentProgress += Math.floor(Math.random() * 8) + 4;
                statusText.innerText = 'Building email notification...';
            } else if (currentProgress < 70) {
                currentProgress += Math.floor(Math.random() * 6) + 3;
                statusText.innerText = 'Sending email via SMTP...';
            } else if (currentProgress < 92) {
                currentProgress += Math.floor(Math.random() * 3) + 1;
                statusText.innerText = 'Finalizing support ticket process...';
            }

            if (currentProgress > 94) {
                currentProgress = 94;
            }

            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
        }, 250);
    }

    // Add confirmation click listener for Status Update
    document.addEventListener('DOMContentLoaded', function() {
        const btnConfirmSubmit = document.getElementById('btn-confirm-submit');
        if (btnConfirmSubmit) {
            btnConfirmSubmit.addEventListener('click', function() {
                const updateForm = document.getElementById('updateStatusForm');
                if (updateForm && !updateForm.checkValidity()) {
                    updateForm.reportValidity();
                    return;
                }

                closeConfirmModal();
                closeUpdateModal();
                showProcessOverlay(
                    "Sending Email & Updating Ticket Status...",
                    "Please wait while the ticket status is updated and email notification is sent..."
                );

                HTMLFormElement.prototype.submit.call(updateForm);
            });
        }
    });

    // Add click listeners to subject links once DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.ticket-subject-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const ticketId = this.getAttribute('data-ticket-id');
                const subject = this.getAttribute('data-subject');
                const creator = this.getAttribute('data-creator');
                const assigned = this.getAttribute('data-assigned');
                const createdAt = this.getAttribute('data-created-at');
                const status = this.getAttribute('data-status');
                const remark = this.getAttribute('data-remark');
                const attachment = this.getAttribute('data-attachment');
                const category = this.getAttribute('data-category');
                const subcategory = this.getAttribute('data-subcategory');
                const isExpense = this.getAttribute('data-is-expense');
                const paymentAmount = this.getAttribute('data-payment-amount');
                const resolutionAttachment = this.getAttribute('data-resolution-attachment');
                const messageHtml = document.getElementById('ticket-msg-' + ticketId).innerHTML;

                openViewModal({
                    id: ticketId,
                    subject: subject,
                    creator_name: creator,
                    assigned_to_name: assigned,
                    created_at: createdAt,
                    status: status,
                    remark: remark,
                    attachment_path: attachment,
                    category: category,
                    subcategory: subcategory,
                    is_expense: isExpense,
                    payment_amount: paymentAmount,
                    resolution_attachment: resolutionAttachment,
                    message: messageHtml
                });
            });
        });
    });

    // Form submission validation & progress bar trigger for Ticket Create
    document.getElementById('createTicketForm').addEventListener('submit', function(e) {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }

        const toUser = document.getElementById('to_user_id')?.value;
        const subject = document.getElementById('subject')?.value;
        const message = document.getElementById('ticketMessage')?.value;

        if (!toUser) {
            alert('Please select a person to submit the ticket to.');
            e.preventDefault();
            return;
        }

        if (!subject || !subject.trim()) {
            alert('Please enter a ticket subject.');
            e.preventDefault();
            return;
        }

        if (!message || !message.trim() || message === '<p></p>') {
            alert('Please enter a ticket message.');
            e.preventDefault();
            return;
        }

        closeCreateModal();
        showProcessOverlay(
            "Sending Email & Creating Support Ticket...",
            "Please wait while your support ticket is created and email notification is sent..."
        );
    });
</script>
@endpush
