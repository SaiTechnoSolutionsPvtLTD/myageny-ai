@extends('layouts.app')

@section('title', 'Target Allocation')

@push('styles')
<style>
.st-page {
    min-height: 100%;
    padding: 28px;
    background: linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.st-shell {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.st-card {
    background: #ffffff;
    border: 1px solid #e1dee3;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
}
.st-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
}
.st-title {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #111827;
}
.st-sub {
    margin: 6px 0 0;
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
    max-width: 640px;
}
.st-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.st-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s ease;
}
.st-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    border-color: #fe5f04;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.25);
}
.st-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(254, 95, 4, 0.35);
}
.st-btn-ghost {
    background: #ffffff;
    border-color: #e1dee3;
    color: #374151;
}
.st-btn-ghost:hover {
    border-color: #94a3b8;
    color: #111827;
}
.st-section-title {
    font-size: 15px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 4px;
}
.st-section-sub {
    font-size: 12px;
    color: #9e9e9e;
    margin: 0 0 20px;
    line-height: 1.6;
}
.st-table-wrap {
    overflow-x: auto;
}
.st-table {
    width: 100%;
    border-collapse: collapse;
}
.st-table th {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: #9e9e9e;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #f0eef2;
    background: #fafafa;
}
.st-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f7f6f9;
    font-size: 13.5px;
    color: #374151;
    vertical-align: middle;
}
.st-table tbody tr:last-child td {
    border-bottom: none;
}
.st-table tbody tr:hover td {
    background: #fffaf7;
}
.st-user-cell {
    display: flex;
    align-items: center;
    gap: 11px;
}
.st-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 800;
    color: #ffffff;
    flex-shrink: 0;
}
.st-user-name {
    font-size: 13px;
    font-weight: 700;
    color: #111827;
}
.st-user-role {
    font-size: 11px;
    color: #9e9e9e;
    margin-top: 1px;
}
.st-target-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.st-currency-prefix {
    font-size: 14px;
    font-weight: 800;
    color: #fe5f04;
}
.st-target-input {
    width: 150px;
    height: 38px;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    padding: 0 12px;
    font-size: 13.5px;
    font-weight: 700;
    color: #111827;
    background: #fafafa;
    font-family: inherit;
    outline: none;
    transition: all 0.15s ease;
}
.st-target-input:focus {
    border-color: #fe5f04;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.1);
}
.st-target-input:hover {
    border-color: #fed7aa;
}
.st-empty {
    text-align: center;
    padding: 48px 20px;
    color: #9e9e9e;
}
.st-empty-icon {
    font-size: 42px;
    margin-bottom: 12px;
}
.st-empty-title {
    font-size: 16px;
    font-weight: 700;
    color: #7c7c7c;
    margin-bottom: 6px;
}
.st-empty-sub {
    font-size: 13px;
}
.st-alert {
    padding: 13px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}
.st-alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
</style>
@endpush

@section('content')
@php
    $avatarColors = ['#fe5f04', '#7c3aed', '#2563eb', '#16a34a', '#be123c', '#0284c7', '#b45309', '#0891b2'];
@endphp

<div class="st-page">
    <div class="st-shell">

        {{-- Flash message --}}
        @if(session('success'))
        <div class="st-alert ds-alert-success">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:6px;"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Header card --}}
        <div class="st-card">
            <div class="st-head">
                <div>
                    <h2 class="st-title">Target Allocation</h2>
                    <p class="st-sub">
                        Configure target revenue amounts for members of the Sales department.
                        These targets will be displayed on their dashboards and compared against their actual closed deal collection.
                    </p>
                </div>
                <div class="st-actions">
                    <a href="{{ route('settings.index') }}" class="st-btn st-btn-ghost">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        Back to Settings
                    </a>
                </div>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="st-card">
            <form method="GET" action="{{ route('settings.sales-targets.index') }}" id="filterForm" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
                <div style="display:flex; flex-direction:column; gap:6px; flex:1; min-width:200px;">
                    <label style="font-size:11px; font-weight:800; color:#9e9e9e; text-transform:uppercase; letter-spacing:0.5px;">Select Month</label>
                    <input type="month" name="month" value="{{ $selectedMonth }}" class="st-target-input" style="width:100%;" onchange="document.getElementById('filterForm').submit()">
                </div>
                <div style="display:flex; flex-direction:column; gap:6px; flex:1; min-width:200px;">
                    <label style="font-size:11px; font-weight:800; color:#9e9e9e; text-transform:uppercase; letter-spacing:0.5px;">Select Branch</label>
                    <select name="branch_id" class="st-target-input" style="width:100%;" onchange="document.getElementById('filterForm').submit()">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branch->id == $selectedBranchId)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="st-btn st-btn-ghost" style="height:38px;">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- Allocation Form Card --}}
        <div class="st-card">
            <form method="POST" action="{{ route('settings.sales-targets.store') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">

                <div class="st-head" style="margin-bottom:20px;">
                    <div>
                        <div class="st-section-title">Sales Target Amounts for {{ \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}</div>
                        <p class="st-section-sub" style="margin-bottom:0;">
                            Specify target amount values for each representative in the selected branch. Set to 0.00 for no target.
                        </p>
                    </div>
                    <div class="st-actions">
                        <button type="submit" class="st-btn st-btn-primary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Target Allocations
                        </button>
                    </div>
                </div>

                @if($users->isEmpty())
                    <div class="st-empty">
                        <div class="st-empty-icon">💼</div>
                        <div class="st-empty-title">No Sales Department users found</div>
                        <div class="st-empty-sub">
                            Make sure users have roles that belong to the <strong>"Sales"</strong> department.
                        </div>
                    </div>
                @else
                    <div class="st-table-wrap">
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Sales Representative</th>
                                    <th style="width:250px;">Target Revenue Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $i => $user)
                                @php
                                    $avatarColor = $avatarColors[$user->id % count($avatarColors)];
                                    $initials    = strtoupper(substr($user->name, 0, 1));
                                    $roleName    = $user->roles->first()?->display_name
                                                    ?? ucwords(str_replace('_', ' ', $user->roles->first()?->name ?? 'Staff'));
                                    $targetValue = $targets[$user->id] ?? 0.00;
                                @endphp
                                <tr>
                                    <td style="color:#9e9e9e;font-size:12px;font-family:monospace;">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="st-user-cell">
                                            <div class="st-avatar" style="background:{{ $avatarColor }}">{{ $initials }}</div>
                                            <div>
                                                <div class="st-user-name">{{ $user->name }}</div>
                                                <div class="st-user-role">{{ $roleName }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="st-target-input-group">
                                            <span class="st-currency-prefix">₹</span>
                                            <input type="number" name="targets[{{ $user->id }}]" class="st-target-input" 
                                                   value="{{ $targetValue }}" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </form>
        </div>

    </div>
</div>
@endsection
