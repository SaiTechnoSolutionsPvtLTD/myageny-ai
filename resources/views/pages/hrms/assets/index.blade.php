@extends('layouts.app')

@section('title', 'Asset Entries')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        /* Modern Navigation Tabs */
        .asset-nav-tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 10px 14px;
            border-radius: 16px;
            border: 1px solid #e1dee3;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .asset-nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid transparent;
            background: transparent;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all .18s ease;
            white-space: nowrap;
            text-decoration: none;
            font-family: inherit;
        }
        .asset-nav-tab:hover {
            background: #fff7ed;
            color: #fe5f04;
        }
        .asset-nav-tab.active {
            background: linear-gradient(135deg, #fe5f04, #ff7c30);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(254,95,4,0.22);
        }
        .asset-tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            background: #f1f5f9;
            color: #475569;
            transition: all .18s;
        }
        .asset-nav-tab.active .asset-tab-badge {
            background: rgba(255,255,255,0.28);
            color: #ffffff;
        }

        /* Tab Content Panes */
        .asset-tab-pane {
            display: none;
            flex-direction: column;
            gap: 16px;
        }
        .asset-tab-pane.active {
            display: flex;
        }

        /* Pivot Styles */
        .pivot-card {
            background: #fff;
            border: 1px solid #e1dee3;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .pivot-card-head {
            padding: 18px 22px;
            border-bottom: 1px solid #f0eef2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .pivot-card-title {
            font-size: 16px;
            font-weight: 800;
            color: #121212;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pivot-card-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 3px;
        }
        .pivot-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .pivot-search-box {
            position: relative;
            min-width: 260px;
        }
        .pivot-search-box input {
            width: 100%;
            padding: 9px 12px 9px 34px;
            border-radius: 10px;
            border: 1px solid #e1dee3;
            font-size: 13px;
            background: #fff;
            outline: none;
            transition: all .15s;
        }
        .pivot-search-box input:focus {
            border-color: #fe5f04;
            box-shadow: 0 0 0 3px rgba(254,95,4,0.1);
        }
        .pivot-search-box i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }
        .pivot-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s;
        }
        .pivot-btn-ghost:hover {
            background: #fff7ed;
            color: #fe5f04;
            border-color: #fdba74;
        }

        /* Pivot Table */
        .pivot-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pivot-table th {
            padding: 12px 18px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #64748b;
            background: #fafafc;
            border-bottom: 1px solid #f0eef2;
            text-align: left;
        }
        .pivot-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #121212;
            vertical-align: middle;
        }
        .pivot-table tbody tr.pivot-summary-row:hover td {
            background: #fffaf7;
        }

        /* Subtable for Expanded Assets */
        .pivot-subtable-container {
            padding: 16px 20px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .pivot-subtable {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .pivot-subtable th {
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #475569;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .pivot-subtable td {
            padding: 11px 14px;
            font-size: 12px;
            color: #1e293b;
            border-bottom: 1px solid #edf2f7;
            vertical-align: middle;
        }
        .pivot-subtable tbody tr:last-child td {
            border-bottom: none;
        }

        /* Category pills & badges */
        .pivot-cat-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            margin: 2px 4px 2px 0;
            white-space: nowrap;
        }
        .pivot-status-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin: 2px 3px 2px 0;
            white-space: nowrap;
        }
        .pivot-status-available { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
        .pivot-status-assigned { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
        .pivot-status-in_service { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
        .pivot-status-damaged { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
        .pivot-status-retired { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }

        .pivot-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 13px;
            border-radius: 9px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            transition: all .15s;
        }
        .pivot-toggle-btn:hover {
            background: #fff7ed;
            border-color: #fdba74;
            color: #fe5f04;
        }
        .pivot-toggle-btn.is-active {
            background: #ffedd5;
            border-color: #fdba74;
            color: #c2410c;
        }

        .pivot-expandable-row {
            display: none;
        }
        .pivot-expandable-row.is-open {
            display: table-row;
        }

        /* Avatar badge */
        .emp-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #fe5f04, #ff8c42);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Asset Management</div>
            <div class="eob-breadcrumb">HRMS > Assets & Inventory</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <a href="{{ route('assets.create') }}" class="eob-btn eob-btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Asset
            </a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        {{-- Top Summary KPI Cards --}}
        <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px;">
            <div class="eob-show-item">
                <div class="eob-show-label">Total Assets</div>
                <div class="eob-show-value">{{ $stats['total'] }}</div>
            </div>
            <div class="eob-show-item">
                <div class="eob-show-label">Assigned</div>
                <div class="eob-show-value" style="color:#1d4ed8;">{{ $stats['assigned'] }}</div>
            </div>
            <div class="eob-show-item">
                <div class="eob-show-label">Available</div>
                <div class="eob-show-value" style="color:#15803d;">{{ $stats['available'] }}</div>
            </div>
            <div class="eob-show-item">
                <div class="eob-show-label">In Service</div>
                <div class="eob-show-value" style="color:#c2410c;">{{ $stats['in_service'] }}</div>
            </div>
            <div class="eob-show-item">
                <div class="eob-show-label">Total Asset Value</div>
                <div class="eob-show-value" style="color:#0f172a;">₹{{ number_format($stats['total_value'] ?? 0, 2) }}</div>
            </div>
        </div>

        {{-- Modern Navigation Tabs --}}
        <div class="asset-nav-tabs">
            <button type="button" class="asset-nav-tab active" data-tab-target="#tab-registry">
                <i class="bi bi-table"></i>
                <span>All Assets Registry</span>
                <span class="asset-tab-badge">{{ $stats['total'] }}</span>
            </button>
            <button type="button" class="asset-nav-tab" data-tab-target="#tab-employee-pivot">
                <i class="bi bi-people-fill"></i>
                <span>Employee-wise Pivot</span>
                <span class="asset-tab-badge">{{ count($employeePivot) }} {{ Str::plural('Employee', count($employeePivot)) }}</span>
            </button>
            <button type="button" class="asset-nav-tab" data-tab-target="#tab-category-pivot">
                <i class="bi bi-tags-fill"></i>
                <span>Category-wise Pivot</span>
                <span class="asset-tab-badge">{{ count($categoryPivot) }} {{ Str::plural('Category', count($categoryPivot)) }}</span>
            </button>
        </div>

        <div class="asset-tab-content">

            {{-- ══════════════════════════════════════════════════════════════════ --}}
            {{-- TAB 1: ALL ASSETS REGISTRY (MASTER TABLE)                           --}}
            {{-- ══════════════════════════════════════════════════════════════════ --}}
            <div class="asset-tab-pane active" id="tab-registry">
                <div class="eob-filter-card">
                    <form method="GET" action="{{ route('assets.index') }}" class="eob-filter-form">
                        <div class="eob-field">
                            <label class="eob-label">Search</label>
                            <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Asset code, name, serial, vendor, location">
                        </div>
                        <div class="eob-field" style="max-width:220px;">
                            <label class="eob-label">Category</label>
                            <select name="asset_category" class="eob-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(request('asset_category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="eob-field" style="max-width:220px;">
                            <label class="eob-label">Status</label>
                            <select name="asset_status" class="eob-select">
                                <option value="">All Status</option>
                                @foreach(['available' => 'Available', 'assigned' => 'Assigned', 'in_service' => 'In Service', 'damaged' => 'Damaged', 'retired' => 'Retired'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('asset_status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="eob-field" style="max-width:280px;">
                            <label class="eob-label">Assigned Employee</label>
                            <select name="assigned_employee_id" class="eob-select">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected((string) request('assigned_employee_id') === (string) $employee->id)>{{ $employee->employee_id }} - {{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="eob-actions">
                            <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                            @if(request()->hasAny(['search', 'asset_category', 'asset_status', 'assigned_employee_id']))
                                <a href="{{ route('assets.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="eob-table-card">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Asset Registry</div>
                            <div class="eob-card-sub">Manage organization assets, ownership, and availability from one HRMS screen.</div>
                        </div>
                        <div class="eob-results">{{ $assets->total() }} asset(s)</div>
                    </div>

                    @if($assets->isEmpty())
                        <div class="eob-empty">No asset entries found.</div>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="eob-list-table">
                                <thead>
                                    <tr>
                                        <th>Asset</th>
                                        <th>Category / Brand</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Purchase</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assets as $asset)
                                        <tr>
                                            <td>
                                                <div class="eob-cell-title">{{ $asset->asset_name }}</div>
                                                <div class="eob-cell-sub">{{ $asset->asset_code }}{{ $asset->serial_number ? ' • ' . $asset->serial_number : '' }}</div>
                                            </td>
                                            <td>
                                                <div class="eob-cell-title">{{ $asset->asset_category ?: 'No category' }}</div>
                                                <div class="eob-cell-sub">{{ $asset->brand ?: 'No brand' }}{{ $asset->model_name ? ' • ' . $asset->model_name : '' }}</div>
                                            </td>
                                            <td>
                                                <span class="eob-chip eob-chip-{{ $asset->asset_status === 'available' ? 'verified' : ($asset->asset_status === 'damaged' ? 'rejected' : 'pending') }}">
                                                    {{ ucwords(str_replace('_', ' ', $asset->asset_status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="eob-cell-title">{{ $asset->assignedEmployee?->name ?: 'Not assigned' }}</div>
                                                <div class="eob-cell-sub">{{ $asset->assignedEmployee?->employee_id ?: '—' }}</div>
                                            </td>
                                            <td>
                                                <div class="eob-cell-title">{{ optional($asset->purchase_date)->format('d M Y') ?: 'N/A' }}</div>
                                                <div class="eob-cell-sub">{{ $asset->purchase_cost !== null ? '₹' . number_format((float) $asset->purchase_cost, 2) : 'Cost not added' }}</div>
                                            </td>
                                            <td>{{ $asset->location ?: 'N/A' }}</td>
                                            <td>
                                                <details class="eob-table-dropdown">
                                                    <summary class="eob-table-dropdown-trigger">Actions</summary>
                                                    <div class="eob-table-dropdown-menu">
                                                        <a href="{{ route('assets.show', $asset) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                        <a href="{{ route('assets.edit', $asset) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                                        <button
                                                            type="button"
                                                            class="eob-table-dropdown-item danger"
                                                            data-delete-trigger
                                                            data-name="{{ $asset->asset_name }}"
                                                            data-action="{{ route('assets.destroy', $asset) }}"
                                                        ><i class="bi bi-trash"></i> Delete</button>
                                                    </div>
                                                </details>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($assets->hasPages())
                            @include('partials.table-pagination', ['paginator' => $assets])
                        @endif
                    @endif
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════ --}}
            {{-- TAB 2: EMPLOYEE-WISE PIVOT PANE                                    --}}
            {{-- ══════════════════════════════════════════════════════════════════ --}}
            <div class="asset-tab-pane" id="tab-employee-pivot">
                {{-- Quick Stats for Employee Pivot --}}
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;">
                    <div class="eob-show-item">
                        <div class="eob-show-label">Employees With Assets</div>
                        <div class="eob-show-value">{{ count($employeePivot) }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">Total Assigned Assets</div>
                        <div class="eob-show-value" style="color:#1d4ed8;">{{ $stats['assigned'] }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">Total Value in Custody</div>
                        <div class="eob-show-value" style="color:#0f172a;">₹{{ number_format(collect($employeePivot)->sum('total_cost'), 2) }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">Unassigned / In Inventory</div>
                        <div class="eob-show-value" style="color:#15803d;">{{ $unassignedPivot['total_count'] }} <span style="font-size:12px;font-weight:600;color:#64748b;">(₹{{ number_format($unassignedPivot['total_cost'], 2) }})</span></div>
                    </div>
                </div>

                {{-- Employee Pivot Card --}}
                <div class="pivot-card">
                    <div class="pivot-card-head">
                        <div>
                            <div class="pivot-card-title">
                                <i class="bi bi-person-badge" style="color:#fe5f04;"></i>
                                Employee Asset Allocations
                            </div>
                            <div class="pivot-card-sub">Grouped summary of all assets currently issued to employees across departments.</div>
                        </div>
                        <div class="pivot-toolbar">
                            <div class="pivot-search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" id="employeePivotSearch" placeholder="Search employee, ID, department..." autocomplete="off">
                            </div>
                            <button type="button" class="pivot-btn-ghost" id="expandAllEmployeeRows">
                                <i class="bi bi-chevron-expand"></i> Expand All
                            </button>
                            <button type="button" class="pivot-btn-ghost" id="collapseAllEmployeeRows">
                                <i class="bi bi-chevron-contract"></i> Collapse All
                            </button>
                        </div>
                    </div>

                    @if(empty($employeePivot))
                        <div class="eob-empty">No employees currently have assets assigned.</div>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="pivot-table" id="employeePivotTable">
                                <thead>
                                    <tr>
                                        <th style="width:28%;">Employee</th>
                                        <th style="width:18%;">Department & Role</th>
                                        <th style="width:12%;">Total Assets</th>
                                        <th style="width:22%;">Categories Issued</th>
                                        <th style="width:12%;">Total Value</th>
                                        <th style="width:8%;text-align:right;">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employeePivot as $empId => $data)
                                        @php
                                            $emp = $data['employee'];
                                            $initials = collect(explode(' ', $emp->name))->map(fn($part) => substr($part, 0, 1))->take(2)->implode('');
                                            $searchString = strtolower($emp->name . ' ' . $emp->employee_id . ' ' . ($emp->department?->name ?? '') . ' ' . ($emp->role?->name ?? ''));
                                        @endphp
                                        <tr class="pivot-summary-row" data-search-target="{{ $searchString }}" data-row-id="emp-row-{{ $empId }}">
                                            <td>
                                                <div style="display:flex;align-items:center;gap:12px;">
                                                    <div class="emp-avatar">{{ strtoupper($initials ?: 'E') }}</div>
                                                    <div>
                                                        <div style="font-weight:700;font-size:14px;color:#0f172a;">{{ $emp->name }}</div>
                                                        <div style="font-size:12px;color:#64748b;margin-top:1px;">
                                                            <span style="font-family:monospace;font-weight:700;background:#f1f5f9;padding:1px 6px;border-radius:4px;">{{ $emp->employee_id }}</span>
                                                            @if($emp->official_email || $emp->email)
                                                                &middot; {{ $emp->official_email ?: $emp->email }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600;color:#1e293b;">{{ $emp->department?->name ?: 'General' }}</div>
                                                <div style="font-size:11px;color:#64748b;margin-top:1px;">{{ $emp->role?->display_name ?: ($emp->role?->name ?: 'Staff') }}</div>
                                            </td>
                                            <td>
                                                <span style="display:inline-flex;align-items:center;gap:6px;font-weight:800;font-size:13px;background:#fff7ed;color:#ea580c;border:1px solid #fed7aa;padding:3px 10px;border-radius:999px;">
                                                    <i class="bi bi-box-seam-fill"></i> {{ $data['total_count'] }} {{ Str::plural('Item', $data['total_count']) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display:flex;flex-wrap:wrap;gap:2px;">
                                                    @foreach($data['categories'] as $catName => $count)
                                                        <span class="pivot-cat-tag">
                                                            <i class="bi bi-tag-fill" style="color:#fe5f04;"></i>
                                                            {{ $catName }} <strong style="color:#0f172a;">({{ $count }})</strong>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="font-size:14px;color:#0f172a;">₹{{ number_format($data['total_cost'], 2) }}</strong>
                                            </td>
                                            <td style="text-align:right;">
                                                <button type="button" class="pivot-toggle-btn js-toggle-pivot" data-target="#emp-assets-{{ $empId }}">
                                                    <span>Assets ({{ $data['total_count'] }})</span>
                                                    <i class="bi bi-chevron-down"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        {{-- Collapsible Assets Subtable Row --}}
                                        <tr class="pivot-expandable-row" id="emp-assets-{{ $empId }}">
                                            <td colspan="6" style="padding:0;">
                                                <div class="pivot-subtable-container">
                                                    <div style="font-size:12px;font-weight:700;color:#475569;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;">
                                                        <span><i class="bi bi-list-check" style="color:#fe5f04;"></i> Assets Allocated to {{ $emp->name }}:</span>
                                                        <span style="font-size:11px;color:#64748b;">{{ $data['total_count'] }} items &middot; Total Value: ₹{{ number_format($data['total_cost'], 2) }}</span>
                                                    </div>
                                                    <table class="pivot-subtable">
                                                        <thead>
                                                            <tr>
                                                                <th>Asset Code & Name</th>
                                                                <th>Category</th>
                                                                <th>Brand / Model</th>
                                                                <th>Serial Number</th>
                                                                <th>Assigned Date</th>
                                                                <th>Location</th>
                                                                <th>Cost</th>
                                                                <th>Status</th>
                                                                <th style="text-align:right;">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($data['assets'] as $assetItem)
                                                                <tr>
                                                                    <td>
                                                                        <div style="font-weight:700;color:#0f172a;">{{ $assetItem->asset_name }}</div>
                                                                        <div style="font-size:11px;color:#64748b;font-family:monospace;font-weight:700;">{{ $assetItem->asset_code }}</div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="pivot-cat-tag">{{ $assetItem->asset_category ?: 'N/A' }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <div>{{ $assetItem->brand ?: '—' }}</div>
                                                                        <div style="font-size:11px;color:#64748b;">{{ $assetItem->model_name ?: '—' }}</div>
                                                                    </td>
                                                                    <td>
                                                                        <span style="font-family:monospace;font-size:11px;color:#475569;">{{ $assetItem->serial_number ?: '—' }}</span>
                                                                    </td>
                                                                    <td>
                                                                        {{ optional($assetItem->assigned_date)->format('d M Y') ?: (optional($assetItem->created_at)->format('d M Y') ?: '—') }}
                                                                    </td>
                                                                    <td>{{ $assetItem->location ?: '—' }}</td>
                                                                    <td>
                                                                        <strong style="color:#0f172a;">{{ $assetItem->purchase_cost !== null ? '₹' . number_format((float) $assetItem->purchase_cost, 2) : '—' }}</strong>
                                                                    </td>
                                                                    <td>
                                                                        <span class="pivot-status-tag pivot-status-{{ $assetItem->asset_status }}">
                                                                            {{ ucwords(str_replace('_', ' ', $assetItem->asset_status)) }}
                                                                        </span>
                                                                    </td>
                                                                    <td style="text-align:right;">
                                                                        <div style="display:inline-flex;gap:6px;">
                                                                            <a href="{{ route('assets.show', $assetItem) }}" class="eob-icon-btn" title="View Asset"><i class="bi bi-eye"></i></a>
                                                                            <a href="{{ route('assets.edit', $assetItem) }}" class="eob-icon-btn" title="Edit Asset"><i class="bi bi-pencil"></i></a>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Unassigned / In Stock Section --}}
                @if($unassignedPivot['total_count'] > 0)
                    <div class="pivot-card" style="margin-top:10px;border-color:#bbf7d0;">
                        <div class="pivot-card-head" style="background:#f0fdf4;">
                            <div>
                                <div class="pivot-card-title" style="color:#15803d;">
                                    <i class="bi bi-box2-heart-fill"></i>
                                    Unassigned Assets in Stock / Inventory
                                </div>
                                <div class="pivot-card-sub" style="color:#166534;">
                                    {{ $unassignedPivot['total_count'] }} assets currently in stock and available for assignment. Total inventory valuation: <strong>₹{{ number_format($unassignedPivot['total_cost'], 2) }}</strong>.
                                </div>
                            </div>
                            <div>
                                <button type="button" class="pivot-toggle-btn js-toggle-pivot" data-target="#unassigned-assets-row" style="background:#fff;border-color:#86efac;color:#15803d;">
                                    <span>View Unassigned Assets ({{ $unassignedPivot['total_count'] }})</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="pivot-expandable-row" id="unassigned-assets-row" style="display:none;width:100%;">
                            <div class="pivot-subtable-container" style="background:#ffffff;">
                                <table class="pivot-subtable">
                                    <thead>
                                        <tr>
                                            <th>Asset Code & Name</th>
                                            <th>Category</th>
                                            <th>Brand / Model</th>
                                            <th>Serial Number</th>
                                            <th>Location</th>
                                            <th>Purchase Cost</th>
                                            <th>Status</th>
                                            <th style="text-align:right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unassignedPivot['assets'] as $unassignedItem)
                                            <tr>
                                                <td>
                                                    <div style="font-weight:700;color:#0f172a;">{{ $unassignedItem->asset_name }}</div>
                                                    <div style="font-size:11px;color:#64748b;font-family:monospace;font-weight:700;">{{ $unassignedItem->asset_code }}</div>
                                                </td>
                                                <td><span class="pivot-cat-tag">{{ $unassignedItem->asset_category ?: 'N/A' }}</span></td>
                                                <td>
                                                    <div>{{ $unassignedItem->brand ?: '—' }}</div>
                                                    <div style="font-size:11px;color:#64748b;">{{ $unassignedItem->model_name ?: '—' }}</div>
                                                </td>
                                                <td><span style="font-family:monospace;font-size:11px;">{{ $unassignedItem->serial_number ?: '—' }}</span></td>
                                                <td>{{ $unassignedItem->location ?: '—' }}</td>
                                                <td><strong>{{ $unassignedItem->purchase_cost !== null ? '₹' . number_format((float) $unassignedItem->purchase_cost, 2) : '—' }}</strong></td>
                                                <td>
                                                    <span class="pivot-status-tag pivot-status-{{ $unassignedItem->asset_status }}">
                                                        {{ ucwords(str_replace('_', ' ', $unassignedItem->asset_status)) }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div style="display:inline-flex;gap:6px;">
                                                        <a href="{{ route('assets.show', $unassignedItem) }}" class="eob-icon-btn" title="View"><i class="bi bi-eye"></i></a>
                                                        <a href="{{ route('assets.edit', $unassignedItem) }}" class="eob-btn eob-btn-sm eob-btn-primary" style="padding:4px 10px;font-size:11px;">Assign Now</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ══════════════════════════════════════════════════════════════════ --}}
            {{-- TAB 3: CATEGORY-WISE PIVOT PANE                                   --}}
            {{-- ══════════════════════════════════════════════════════════════════ --}}
            <div class="asset-tab-pane" id="tab-category-pivot">
                {{-- Quick Stats for Category Pivot --}}
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;">
                    <div class="eob-show-item">
                        <div class="eob-show-label">Total Categories</div>
                        <div class="eob-show-value">{{ count($categoryPivot) }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">Total Category Assets</div>
                        <div class="eob-show-value" style="color:#0f172a;">{{ $stats['total'] }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">Total Category Valuation</div>
                        <div class="eob-show-value" style="color:#0f172a;">₹{{ number_format(collect($categoryPivot)->sum('total_cost'), 2) }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">In-Stock / Available</div>
                        <div class="eob-show-value" style="color:#15803d;">{{ $stats['available'] }}</div>
                    </div>
                </div>

                {{-- Category Pivot Card --}}
                <div class="pivot-card">
                    <div class="pivot-card-head">
                        <div>
                            <div class="pivot-card-title">
                                <i class="bi bi-tags-fill" style="color:#fe5f04;"></i>
                                Asset Category Distribution & Inventory
                            </div>
                            <div class="pivot-card-sub">Categorical distribution, operational status, valuation, and assigned employee metrics.</div>
                        </div>
                        <div class="pivot-toolbar">
                            <div class="pivot-search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" id="categoryPivotSearch" placeholder="Search category..." autocomplete="off">
                            </div>
                            <button type="button" class="pivot-btn-ghost" id="expandAllCategoryRows">
                                <i class="bi bi-chevron-expand"></i> Expand All
                            </button>
                            <button type="button" class="pivot-btn-ghost" id="collapseAllCategoryRows">
                                <i class="bi bi-chevron-contract"></i> Collapse All
                            </button>
                        </div>
                    </div>

                    @if(empty($categoryPivot))
                        <div class="eob-empty">No asset categories found.</div>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="pivot-table" id="categoryPivotTable">
                                <thead>
                                    <tr>
                                        <th style="width:26%;">Category Name</th>
                                        <th style="width:12%;">Total Assets</th>
                                        <th style="width:28%;">Status Breakdown</th>
                                        <th style="width:14%;">Employees Holding</th>
                                        <th style="width:12%;">Total Value</th>
                                        <th style="width:8%;text-align:right;">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryPivot as $catKey => $catData)
                                        @php
                                            $searchCatString = strtolower($catData['category_name'] . ' ' . ($catData['category_model']?->description ?? ''));
                                        @endphp
                                        <tr class="pivot-summary-row" data-search-target="{{ $searchCatString }}" data-row-id="cat-row-{{ Str::slug($catKey) }}">
                                            <td>
                                                <div style="display:flex;align-items:center;gap:12px;">
                                                    <div style="width:38px;height:38px;border-radius:10px;background:#fff7ed;color:#fe5f04;border:1px solid #fed7aa;display:inline-flex;align-items:center;justify-content:center;font-size:18px;">
                                                        <i class="bi bi-tag-fill"></i>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:700;font-size:14px;color:#0f172a;">{{ $catData['category_name'] }}</div>
                                                        <div style="font-size:12px;color:#64748b;margin-top:1px;">
                                                            {{ $catData['category_model']?->description ?: 'No description' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="display:inline-flex;align-items:center;gap:6px;font-weight:800;font-size:13px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:3px 10px;border-radius:999px;">
                                                    {{ $catData['total_count'] }} {{ Str::plural('Item', $catData['total_count']) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                                    @if($catData['available_count'] > 0)
                                                        <span class="pivot-status-tag pivot-status-available">
                                                            <i class="bi bi-check-circle-fill"></i> Available: {{ $catData['available_count'] }}
                                                        </span>
                                                    @endif
                                                    @if($catData['assigned_count'] > 0)
                                                        <span class="pivot-status-tag pivot-status-assigned">
                                                            <i class="bi bi-person-check-fill"></i> Assigned: {{ $catData['assigned_count'] }}
                                                        </span>
                                                    @endif
                                                    @if($catData['in_service_count'] > 0)
                                                        <span class="pivot-status-tag pivot-status-in_service">
                                                            <i class="bi bi-wrench-adjustable-circle"></i> Service: {{ $catData['in_service_count'] }}
                                                        </span>
                                                    @endif
                                                    @if($catData['damaged_count'] > 0)
                                                        <span class="pivot-status-tag pivot-status-damaged">
                                                            <i class="bi bi-exclamation-triangle-fill"></i> Damaged: {{ $catData['damaged_count'] }}
                                                        </span>
                                                    @endif
                                                    @if($catData['retired_count'] > 0)
                                                        <span class="pivot-status-tag pivot-status-retired">
                                                            <i class="bi bi-archive-fill"></i> Retired: {{ $catData['retired_count'] }}
                                                        </span>
                                                    @endif
                                                    @if($catData['total_count'] === 0)
                                                        <span style="font-size:12px;color:#94a3b8;font-style:italic;">No assets registered</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:#1e293b;">
                                                    <i class="bi bi-people" style="color:#64748b;"></i>
                                                    {{ $catData['employees_count'] }} {{ Str::plural('Employee', $catData['employees_count']) }}
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="font-size:14px;color:#0f172a;">₹{{ number_format($catData['total_cost'], 2) }}</strong>
                                            </td>
                                            <td style="text-align:right;">
                                                @if($catData['total_count'] > 0)
                                                    <button type="button" class="pivot-toggle-btn js-toggle-pivot" data-target="#cat-assets-{{ Str::slug($catKey) }}">
                                                        <span>Assets ({{ $catData['total_count'] }})</span>
                                                        <i class="bi bi-chevron-down"></i>
                                                    </button>
                                                @else
                                                    <span style="font-size:12px;color:#94a3b8;">Empty</span>
                                                @endif
                                            </td>
                                        </tr>
                                        {{-- Collapsible Category Assets Subtable Row --}}
                                        @if($catData['total_count'] > 0)
                                            <tr class="pivot-expandable-row" id="cat-assets-{{ Str::slug($catKey) }}">
                                                <td colspan="6" style="padding:0;">
                                                    <div class="pivot-subtable-container">
                                                        <div style="font-size:12px;font-weight:700;color:#475569;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;">
                                                            <span><i class="bi bi-tags" style="color:#fe5f04;"></i> All {{ $catData['category_name'] }} Assets:</span>
                                                            <span style="font-size:11px;color:#64748b;">{{ $catData['total_count'] }} assets registered &middot; Total Value: ₹{{ number_format($catData['total_cost'], 2) }}</span>
                                                        </div>
                                                        <table class="pivot-subtable">
                                                            <thead>
                                                                <tr>
                                                                    <th>Asset Code & Name</th>
                                                                    <th>Brand / Model</th>
                                                                    <th>Serial Number</th>
                                                                    <th>Assigned Custodian</th>
                                                                    <th>Location</th>
                                                                    <th>Purchase Date & Cost</th>
                                                                    <th>Status</th>
                                                                    <th style="text-align:right;">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($catData['assets'] as $catAsset)
                                                                    <tr>
                                                                        <td>
                                                                            <div style="font-weight:700;color:#0f172a;">{{ $catAsset->asset_name }}</div>
                                                                            <div style="font-size:11px;color:#64748b;font-family:monospace;font-weight:700;">{{ $catAsset->asset_code }}</div>
                                                                        </td>
                                                                        <td>
                                                                            <div>{{ $catAsset->brand ?: '—' }}</div>
                                                                            <div style="font-size:11px;color:#64748b;">{{ $catAsset->model_name ?: '—' }}</div>
                                                                        </td>
                                                                        <td>
                                                                            <span style="font-family:monospace;font-size:11px;color:#475569;">{{ $catAsset->serial_number ?: '—' }}</span>
                                                                        </td>
                                                                        <td>
                                                                            @if($catAsset->assignedEmployee)
                                                                                <div style="font-weight:700;color:#1e293b;display:flex;align-items:center;gap:6px;">
                                                                                    <i class="bi bi-person-fill" style="color:#fe5f04;"></i>
                                                                                    {{ $catAsset->assignedEmployee->name }}
                                                                                </div>
                                                                                <div style="font-size:11px;color:#64748b;margin-left:18px;">
                                                                                    {{ $catAsset->assignedEmployee->employee_id }}
                                                                                </div>
                                                                            @else
                                                                                <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;background:#f0fdf4;color:#15803d;font-size:11px;font-weight:700;">
                                                                                    <i class="bi bi-check-circle"></i> In Stock (Unassigned)
                                                                                </span>
                                                                            @endif
                                                                        </td>
                                                                        <td>{{ $catAsset->location ?: '—' }}</td>
                                                                        <td>
                                                                            <div>{{ optional($catAsset->purchase_date)->format('d M Y') ?: 'N/A' }}</div>
                                                                            <div style="font-size:11px;font-weight:700;color:#0f172a;">
                                                                                {{ $catAsset->purchase_cost !== null ? '₹' . number_format((float) $catAsset->purchase_cost, 2) : '—' }}
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <span class="pivot-status-tag pivot-status-{{ $catAsset->asset_status }}">
                                                                                {{ ucwords(str_replace('_', ' ', $catAsset->asset_status)) }}
                                                                            </span>
                                                                        </td>
                                                                        <td style="text-align:right;">
                                                                            <div style="display:inline-flex;gap:6px;">
                                                                                <a href="{{ route('assets.show', $catAsset) }}" class="eob-icon-btn" title="View Asset"><i class="bi bi-eye"></i></a>
                                                                                <a href="{{ route('assets.edit', $catAsset) }}" class="eob-icon-btn" title="Edit Asset"><i class="bi bi-pencil"></i></a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div> {{-- End .asset-tab-content --}}
    </div>
</div>

{{-- Delete Modal --}}
<div class="eob-modal" id="deleteModal">
    <div class="eob-modal-card">
        <div class="eob-modal-head">
            <div class="eob-modal-title">Delete asset entry?</div>
            <div class="eob-modal-copy">This will remove the asset record for <strong id="deleteAssetName">this asset</strong>.</div>
        </div>
        <div class="eob-modal-foot">
            <button type="button" class="eob-btn eob-btn-ghost" id="deleteModalCancel">Cancel</button>
            <form method="POST" id="deleteModalForm">
                @csrf
                @method('DELETE')
                <button type="submit" class="eob-btn eob-btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Navigation Tab Switching with URL Hash Preservation
    const tabButtons = document.querySelectorAll('.asset-nav-tab');
    const tabPanes = document.querySelectorAll('.asset-tab-pane');

    function activateTab(targetSelector, updateHash = true) {
        const targetPane = document.querySelector(targetSelector);
        if (!targetPane) return;

        tabButtons.forEach(btn => {
            const isMatch = btn.getAttribute('data-tab-target') === targetSelector;
            btn.classList.toggle('active', isMatch);
        });

        tabPanes.forEach(pane => {
            pane.classList.toggle('active', pane === targetPane);
        });

        if (updateHash && history.replaceState) {
            history.replaceState(null, null, targetSelector);
        }
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetSelector = this.getAttribute('data-tab-target');
            activateTab(targetSelector);
        });
    });

    // Check URL Hash on load
    if (window.location.hash) {
        activateTab(window.location.hash, false);
    }

    // 2. Expand / Collapse Individual Subtable Rows
    document.querySelectorAll('.js-toggle-pivot').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetRow = document.querySelector(targetId);
            if (!targetRow) return;

            const isOpen = targetRow.classList.toggle('is-open');
            this.classList.toggle('is-active', isOpen);

            const icon = this.querySelector('i');
            if (icon) {
                icon.className = isOpen ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
            }
        });
    });

    // 3. Employee Pivot Expand All / Collapse All
    const expandAllEmpBtn = document.getElementById('expandAllEmployeeRows');
    const collapseAllEmpBtn = document.getElementById('collapseAllEmployeeRows');

    if (expandAllEmpBtn) {
        expandAllEmpBtn.addEventListener('click', function () {
            document.querySelectorAll('#tab-employee-pivot .pivot-expandable-row').forEach(row => {
                row.classList.add('is-open');
            });
            document.querySelectorAll('#tab-employee-pivot .js-toggle-pivot').forEach(btn => {
                btn.classList.add('is-active');
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'bi bi-chevron-up';
            });
        });
    }

    if (collapseAllEmpBtn) {
        collapseAllEmpBtn.addEventListener('click', function () {
            document.querySelectorAll('#tab-employee-pivot .pivot-expandable-row').forEach(row => {
                row.classList.remove('is-open');
            });
            document.querySelectorAll('#tab-employee-pivot .js-toggle-pivot').forEach(btn => {
                btn.classList.remove('is-active');
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'bi bi-chevron-down';
            });
        });
    }

    // 4. Category Pivot Expand All / Collapse All
    const expandAllCatBtn = document.getElementById('expandAllCategoryRows');
    const collapseAllCatBtn = document.getElementById('collapseAllCategoryRows');

    if (expandAllCatBtn) {
        expandAllCatBtn.addEventListener('click', function () {
            document.querySelectorAll('#tab-category-pivot .pivot-expandable-row').forEach(row => {
                row.classList.add('is-open');
            });
            document.querySelectorAll('#tab-category-pivot .js-toggle-pivot').forEach(btn => {
                btn.classList.add('is-active');
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'bi bi-chevron-up';
            });
        });
    }

    if (collapseAllCatBtn) {
        collapseAllCatBtn.addEventListener('click', function () {
            document.querySelectorAll('#tab-category-pivot .pivot-expandable-row').forEach(row => {
                row.classList.remove('is-open');
            });
            document.querySelectorAll('#tab-category-pivot .js-toggle-pivot').forEach(btn => {
                btn.classList.remove('is-active');
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'bi bi-chevron-down';
            });
        });
    }

    // 5. Real-Time Search Filtering for Employee Pivot Table
    const empSearchInput = document.getElementById('employeePivotSearch');
    if (empSearchInput) {
        empSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#employeePivotTable tbody tr.pivot-summary-row');

            rows.forEach(row => {
                const searchData = (row.getAttribute('data-search-target') || '').toLowerCase();
                const targetRowId = row.querySelector('.js-toggle-pivot')?.getAttribute('data-target');
                const subRow = targetRowId ? document.querySelector(targetRowId) : null;

                if (query === '' || searchData.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                    if (subRow) subRow.classList.remove('is-open');
                }
            });
        });
    }

    // 6. Real-Time Search Filtering for Category Pivot Table
    const catSearchInput = document.getElementById('categoryPivotSearch');
    if (catSearchInput) {
        catSearchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#categoryPivotTable tbody tr.pivot-summary-row');

            rows.forEach(row => {
                const searchData = (row.getAttribute('data-search-target') || '').toLowerCase();
                const targetRowId = row.querySelector('.js-toggle-pivot')?.getAttribute('data-target');
                const subRow = targetRowId ? document.querySelector(targetRowId) : null;

                if (query === '' || searchData.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                    if (subRow) subRow.classList.remove('is-open');
                }
            });
        });
    }

    // 7. Delete Modal Handling
    const modal = document.getElementById('deleteModal');
    const modalForm = document.getElementById('deleteModalForm');
    const modalName = document.getElementById('deleteAssetName');
    const cancelButton = document.getElementById('deleteModalCancel');

    document.querySelectorAll('[data-delete-trigger]').forEach(function (button) {
        button.addEventListener('click', function () {
            modalForm.setAttribute('action', button.getAttribute('data-action'));
            modalName.textContent = button.getAttribute('data-name');
            modal.classList.add('is-open');
        });
    });

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            modal.classList.remove('is-open');
        });
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.classList.remove('is-open');
            }
        });
    }
});
</script>
@endpush
