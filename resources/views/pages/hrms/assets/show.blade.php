@extends('layouts.app')

@section('title', 'Asset Entry - ' . $asset->asset_name)

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Asset Profile</div>
            <div class="eob-breadcrumb">HRMS > Assets > {{ $asset->asset_name }}</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('assets.edit', $asset) }}" class="eob-btn eob-btn-primary">Edit</a>
            <a href="{{ route('assets.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif

        <div class="eob-show-layout">
            {{-- Left Profile Sticky Card --}}
            <div class="eob-profile-sticky">
                <div class="eob-profile">
                    <div class="eob-profile-banner"></div>
                    <div class="eob-profile-body">
                        <div class="eob-avatar" style="background:#fe5f04; color:#fff; font-weight:800;">
                            {{ strtoupper(substr($asset->asset_category ?: $asset->asset_name, 0, 2)) }}
                        </div>
                        <div class="eob-profile-name">{{ $asset->asset_name }}</div>
                        <div class="eob-profile-mail">{{ $asset->asset_code }}</div>
                        <div style="margin-top:14px;">
                            <span class="eob-chip eob-chip-{{ $asset->asset_status === 'available' ? 'verified' : ($asset->asset_status === 'damaged' ? 'rejected' : 'pending') }}">
                                {{ ucwords(str_replace('_', ' ', $asset->asset_status)) }}
                            </span>
                        </div>

                        <div class="eob-empid-card" style="margin-top:16px;">
                            <div class="eob-empid-label">Asset Code</div>
                            <div class="eob-empid-value">{{ $asset->asset_code }}</div>
                            <div class="eob-empid-sub">System-generated inventory code (Default).</div>
                        </div>

                        <div class="eob-side-list" style="margin-top:16px;">
                            <div class="eob-side-item">
                                <div class="eob-side-label">Category</div>
                                <div class="eob-side-value" style="font-weight:700; color:#fe5f04;">{{ $asset->asset_category ?: 'N/A' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Created By</div>
                                <div class="eob-side-value">{{ $asset->creator?->name ?? 'System' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Created Date</div>
                                <div class="eob-side-value">{{ $asset->created_at?->format('d M Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Main Content --}}
            <div class="eob-section-stack">

                @php
                    $customFields = $category?->custom_fields ?? [];
                    if (is_string($customFields)) {
                        $customFields = json_decode($customFields, true) ?: [];
                    }
                    $customValues = $asset->custom_field_values ?? [];
                    if (is_string($customValues)) {
                        $customValues = json_decode($customValues, true) ?: [];
                    }
                @endphp

                {{-- PRIMARY CARD: Category Form Fields & Asset Specifications --}}
                <div class="eob-show-card" style="border:1.5px solid #fed7aa; box-shadow:0 4px 14px rgba(254,95,4,0.06);">
                    <div class="eob-card-head" style="background:#fffaf5; border-bottom:1.5px solid #fed7aa;">
                        <div>
                            <div class="eob-card-title" style="display:flex; align-items:center; gap:8px;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:8px; background:#fe5f04; color:#fff;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                </span>
                                <span>{{ $asset->asset_category ?: 'Asset' }} Specifications</span>
                            </div>
                            <div class="eob-card-sub">Dynamic custom fields configured for this asset category.</div>
                        </div>
                        <span style="background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; padding:4px 14px; border-radius:999px; font-size:12px; font-weight:800;">
                            {{ $asset->asset_category }}
                        </span>
                    </div>

                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            {{-- Core Defaults --}}
                            <div class="eob-show-item">
                                <div class="eob-show-label">Asset Code</div>
                                <div class="eob-show-value" style="font-weight:800; color:#0f172a;">{{ $asset->asset_code }}</div>
                            </div>

                            <div class="eob-show-item">
                                <div class="eob-show-label">Asset Category</div>
                                <div class="eob-show-value" style="font-weight:700; color:#fe5f04;">{{ $asset->asset_category ?: 'N/A' }}</div>
                            </div>

                            {{-- DYNAMIC CATEGORY FIELDS --}}
                            @if(!empty($customFields))
                                @foreach($customFields as $cf)
                                    @php
                                        $cfName = $cf['name'] ?? '';
                                        $cfVal = $customValues[$cfName] ?? null;
                                        $cfType = $cf['type'] ?? 'text';
                                    @endphp
                                    <div class="eob-show-item" style="background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0;">
                                        <div class="eob-show-label" style="color:#475569; font-weight:700;">{{ $cf['label'] ?? ucwords(str_replace('_', ' ', $cfName)) }}</div>
                                        <div class="eob-show-value" style="font-size:14px; color:#0f172a; margin-top:4px;">
                                            @if($cfType === 'file' && !empty($cfVal))
                                                <a href="{{ asset($cfVal) }}" target="_blank" class="eob-btn eob-btn-ghost" style="padding:4px 10px; font-size:12px; display:inline-flex; align-items:center; gap:6px; text-decoration:none; background:#fff; border:1px solid #cbd5e1; border-radius:6px; color:#0284c7; font-weight:600;">
                                                    📎 <span>View Attached File</span>
                                                </a>
                                            @elseif($cfType === 'checkbox')
                                                @if(is_array($cfVal))
                                                    <span style="font-weight:600;">{{ implode(', ', $cfVal) }}</span>
                                                @elseif(!empty($cfVal) && $cfVal != '0')
                                                    <span style="color:#16a34a; font-weight:700;">✓ Yes</span>
                                                @else
                                                    <span style="color:#64748b;">No</span>
                                                @endif
                                            @elseif($cfType === 'date' && !empty($cfVal))
                                                <span style="font-weight:600;">{{ \Carbon\Carbon::parse($cfVal)->format('d M Y') }}</span>
                                            @elseif(!empty($cfVal))
                                                <span style="font-weight:600;">{{ is_array($cfVal) ? implode(', ', $cfVal) : $cfVal }}</span>
                                            @else
                                                <span style="color:#94a3b8; font-style:italic;">—</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @elseif(!empty($customValues))
                                {{-- Fallback if fields definition is not found but values exist --}}
                                @foreach($customValues as $k => $v)
                                    <div class="eob-show-item" style="background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0;">
                                        <div class="eob-show-label" style="color:#475569; font-weight:700;">{{ ucwords(str_replace('_', ' ', $k)) }}</div>
                                        <div class="eob-show-value" style="font-size:14px; color:#0f172a; margin-top:4px;">
                                            @if(is_string($v) && (str_starts_with($v, 'uploads/') || str_starts_with($v, '/uploads/')))
                                                <a href="{{ asset($v) }}" target="_blank" class="eob-btn eob-btn-ghost" style="padding:4px 10px; font-size:12px; color:#0284c7;">📎 View Attached File</a>
                                            @else
                                                <span style="font-weight:600;">{{ is_array($v) ? implode(', ', $v) : ($v ?: '—') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            {{-- Default Assigned Employee --}}
                            <div class="eob-show-item">
                                <div class="eob-show-label">Assigned Employee</div>
                                <div class="eob-show-value">
                                    @if($asset->assignedEmployee)
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:34px; height:34px; border-radius:50%; background:#fe5f04; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700;">
                                                {{ strtoupper(substr($asset->assignedEmployee->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div style="font-weight:700; color:#0f172a; font-size:14px;">{{ $asset->assignedEmployee->name }}</div>
                                                <div style="font-size:12px; color:#64748b;">Emp ID: {{ $asset->assignedEmployee->employee_id }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span style="color:#64748b;">Not assigned</span>
                                    @endif
                                </div>
                            </div>

                            @if($asset->assigned_date)
                            <div class="eob-show-item">
                                <div class="eob-show-label">Assigned Date</div>
                                <div class="eob-show-value">{{ optional($asset->assigned_date)->format('d M Y') }}</div>
                            </div>
                            @endif

                            <div class="eob-show-item">
                                <div class="eob-show-label">Status</div>
                                <div class="eob-show-value">
                                    <span class="eob-chip eob-chip-{{ $asset->asset_status === 'available' ? 'verified' : ($asset->asset_status === 'damaged' ? 'rejected' : 'pending') }}">
                                        {{ ucwords(str_replace('_', ' ', $asset->asset_status)) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Default Description --}}
                            <div class="eob-show-item full" style="grid-column: 1 / -1;">
                                <div class="eob-show-label">Description</div>
                                <div class="eob-show-value" style="line-height:1.6;">{{ $asset->description ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECONDARY CARD: Legacy / Additional details ONLY IF they actually contain data --}}
                @php
                    $hasAdditionalData = !empty($asset->brand)
                        || !empty($asset->model_name)
                        || !empty($asset->serial_number)
                        || !empty($asset->location)
                        || !empty($asset->purchase_date)
                        || !empty($asset->purchase_cost)
                        || !empty($asset->vendor_name)
                        || !empty($asset->invoice_number)
                        || !empty($asset->warranty_expiry_date)
                        || !empty($asset->condition_notes);
                @endphp

                @if($hasAdditionalData)
                <div class="eob-show-card" style="margin-top:20px;">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Purchase &amp; Warranty Details</div>
                            <div class="eob-card-sub">Vendor, invoice, and warranty information.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            @if($asset->brand)
                                <div class="eob-show-item"><div class="eob-show-label">Brand</div><div class="eob-show-value">{{ $asset->brand }}</div></div>
                            @endif
                            @if($asset->model_name)
                                <div class="eob-show-item"><div class="eob-show-label">Model</div><div class="eob-show-value">{{ $asset->model_name }}</div></div>
                            @endif
                            @if($asset->serial_number)
                                <div class="eob-show-item"><div class="eob-show-label">Serial Number</div><div class="eob-show-value">{{ $asset->serial_number }}</div></div>
                            @endif
                            @if($asset->location)
                                <div class="eob-show-item"><div class="eob-show-label">Location</div><div class="eob-show-value">{{ $asset->location }}</div></div>
                            @endif
                            @if($asset->purchase_date)
                                <div class="eob-show-item"><div class="eob-show-label">Purchase Date</div><div class="eob-show-value">{{ optional($asset->purchase_date)->format('d M Y') }}</div></div>
                            @endif
                            @if($asset->purchase_cost !== null)
                                <div class="eob-show-item"><div class="eob-show-label">Purchase Cost</div><div class="eob-show-value">₹{{ number_format((float) $asset->purchase_cost, 2) }}</div></div>
                            @endif
                            @if($asset->vendor_name)
                                <div class="eob-show-item"><div class="eob-show-label">Vendor Name</div><div class="eob-show-value">{{ $asset->vendor_name }}</div></div>
                            @endif
                            @if($asset->invoice_number)
                                <div class="eob-show-item"><div class="eob-show-label">Invoice Number</div><div class="eob-show-value">{{ $asset->invoice_number }}</div></div>
                            @endif
                            @if($asset->warranty_expiry_date)
                                <div class="eob-show-item"><div class="eob-show-label">Warranty Expiry</div><div class="eob-show-value">{{ optional($asset->warranty_expiry_date)->format('d M Y') }}</div></div>
                            @endif
                            @if($asset->condition_notes)
                                <div class="eob-show-item full" style="grid-column: 1 / -1;"><div class="eob-show-label">Condition Notes</div><div class="eob-show-value">{{ $asset->condition_notes }}</div></div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
