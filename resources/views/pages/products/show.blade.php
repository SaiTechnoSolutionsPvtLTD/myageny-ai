@extends('layouts.app')

@section('title', $product->package_name . ' — Product Detail')

@include('pages.products.style')

@section('content')
<main class="main-content">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <header class="top-header">
        <div class="breadcrumbs">
            <span class="crumb-item">Home</span>
            <span class="crumb-sep">/</span>
            <a href="{{ route('products.index') }}" class="crumb-item">Product Master</a>
            <span class="crumb-sep">/</span>
            <span class="crumb-item active">{{ $product->package_name }}</span>
        </div>
        <div class="header-actions">
            <a href="{{ route('products.index') }}" class="pm-btn pm-btn--ghost">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                Back
            </a>
            <a href="{{ route('products.edit', $product) }}" class="pm-btn pm-btn--outline">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit
            </a>
            @can('products.edit')
                <a href="{{ route('products.ovp-form.builder', $product) }}" class="pm-btn pm-btn--primary">
                    OVP Form
                </a>
            @endcan
            <form method="POST" action="{{ route('products.destroy', $product) }}"
                  class="pm-delete-form" data-name="{{ $product->package_name }}">
                @csrf @method('DELETE')
                <button type="submit" class="pm-btn pm-btn--danger">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                        <path d="M9 6V4h6v2"/>
                    </svg>
                    Delete
                </button>
            </form>
        </div>
    </header>

    <div class="pm-page-body">

        @if(session('success'))
            <div class="pm-alert pm-alert--success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="pm-show-grid">

            {{-- ═══ LEFT — Package Summary Card ══════════════════════ --}}
            <div class="pm-summary-card">

                {{-- Category ribbon --}}
                <div class="pm-summary-ribbon">{{ $product->category?->name ?? 'Uncategorized' }}</div>

                {{-- Package badge --}}
                <div class="pm-summary-badge">
                    @php
                        $tier = strtolower($product->package_name);
                        $tierClass = str_contains($tier, 'gold') ? 'gold'
                            : (str_contains($tier, 'silver') ? 'silver'
                            : (str_contains($tier, 'platinum') ? 'platinum' : 'default'));
                    @endphp
                    <div class="pm-tier-icon pm-tier-icon--{{ $tierClass }}">
                        @if(str_contains($tier, 'gold'))
                            <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        @elseif(str_contains($tier, 'silver'))
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        @elseif(str_contains($tier, 'platinum'))
                            <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><polygon points="12,2 15.5,8.5 22,9.5 17,14.5 18.2,21 12,17.8 5.8,21 7,14.5 2,9.5 8.5,8.5"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28" height="28"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        @endif
                    </div>
                    <h3 class="pm-summary-package">{{ $product->package_name }}</h3>
                </div>

                {{-- Price block --}}
                <div class="pm-summary-price-block">
                    <span class="pm-summary-price">₹{{ number_format($product->final_price, 2) }}</span>
                    @if($product->discount_value > 0)
                        <span class="pm-summary-original">₹{{ number_format($product->base_price, 2) }}</span>
                        <span class="pm-summary-savings">
                            Save {{ $product->discount_type === 'percentage'
                                ? $product->discount_value . '%'
                                : '₹' . number_format($product->discount_value, 2) }}
                        </span>
                    @endif
                </div>

                {{-- Pricing breakdown --}}
                <div class="pm-summary-breakdown">
                    <div class="pm-breakdown-row">
                        <span>Base Price</span>
                        <span>₹{{ number_format($product->base_price, 2) }}</span>
                    </div>
                    @if($product->discount_value > 0)
                    <div class="pm-breakdown-row pm-breakdown-row--discount">
                        <span>
                            Discount
                            ({{ $product->discount_type === 'percentage' ? $product->discount_value . '%' : 'Fixed' }})
                        </span>
                        <span>
                            − ₹{{ $product->discount_type === 'percentage'
                                ? number_format($product->base_price * $product->discount_value / 100, 2)
                                : number_format($product->discount_value, 2) }}
                        </span>
                    </div>
                    @endif
                    @if($product->tax_value > 0)
                    <div class="pm-breakdown-row pm-breakdown-row--tax">
                        <span>
                            Tax
                            ({{ $product->tax_type === 'percentage' ? $product->tax_value . '%' : 'Fixed' }})
                        </span>
                        @php
                            $discounted = $product->discount_type === 'percentage'
                                ? $product->base_price * (1 - $product->discount_value / 100)
                                : $product->base_price - $product->discount_value;
                            $taxAmount = $product->tax_type === 'percentage'
                                ? $discounted * $product->tax_value / 100
                                : $product->tax_value;
                        @endphp
                        <span>+ ₹{{ number_format($taxAmount, 2) }}</span>
                    </div>
                    @endif
                    <div class="pm-breakdown-row pm-breakdown-row--total">
                        <span>Final Price</span>
                        <span>₹{{ number_format($product->final_price, 2) }}</span>
                    </div>
                </div>

                {{-- Status & SKU --}}
                <div class="pm-summary-meta">
                    <div class="pm-meta-row">
                        <span class="pm-meta-label">Status</span>
                        {!! $product->statusBadge !!}
                    </div>
                    <div class="pm-meta-row">
                        <span class="pm-meta-label">SKU</span>
                        <span class="pm-sku">{{ $product->sku }}</span>
                    </div>
                    <div class="pm-meta-row">
                        <span class="pm-meta-label">Sort Order</span>
                        <span>{{ $product->sort_order }}</span>
                    </div>
                    <div class="pm-meta-row">
                        <span class="pm-meta-label">Renewal Product</span>
                        <span>{{ $product->is_this_renewal_product ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="pm-meta-row">
                        <span class="pm-meta-label">Budget Approval Needed</span>
                        <span>{{ $product->is_budget_approval_needed ? 'Yes' : 'No' }}</span>
                    </div>
                </div>

                {{-- Feature list from attributes --}}
                @if($product->attributeValues->isNotEmpty())
                <div class="pm-summary-features">
                    <h4 class="pm-features-title">What's Included</h4>
                    <ul class="pm-features-list">
                        @foreach($product->attributeValues as $pav)
                            @if(!empty($pav->value))
                            <li class="pm-feature-item">
                                <svg class="pm-feature-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <span class="pm-feature-name">{{ $pav->attribute->name }}</span>
                                <span class="pm-feature-value">
                                    {{ $pav->value }}
                                    @if($pav->attribute->unit)
                                        <span class="pm-feature-unit">{{ $pav->attribute->unit }}</span>
                                    @endif
                                </span>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                @endif

            </div>

            {{-- ═══ RIGHT — Detail Panel ══════════════════════════════ --}}
            <div class="pm-detail-panel">

                {{-- Overview --}}
                <div class="pm-card pm-card--detail">
                    <h3 class="pm-detail-section-title">Overview</h3>
                    @if($product->description)
                        <div class="pm-description">{{ $product->description }}</div>
                    @else
                        <p class="pm-muted pm-muted--italic">No description provided.</p>
                    @endif
                </div>

                {{-- Attributes Table --}}
                @if($product->attributeValues->isNotEmpty())
                <div class="pm-card pm-card--detail">
                    <h3 class="pm-detail-section-title">Attribute Details</h3>
                    <table class="pm-table pm-table--attrs">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Attribute</th>
                                <th>Value</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->attributeValues as $i => $pav)
                            <tr>
                                <td class="pm-muted">{{ $i + 1 }}</td>
                                <td><strong>{{ $pav->attribute->name }}</strong></td>
                                <td>{{ $pav->value ?? '—' }}</td>
                                <td class="pm-muted">{{ $pav->attribute->unit ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="pm-card pm-card--detail">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:14px;">
                        <h3 class="pm-detail-section-title" style="margin:0;">OVP Form Fields</h3>
                        @can('products.edit')
                            <a href="{{ route('products.ovp-form.builder', $product) }}" class="pm-btn pm-btn--outline pm-btn--sm">Manage Form</a>
                        @endcan
                    </div>

                    @if($product->ovpFormFields->isEmpty())
                        <p class="pm-muted pm-muted--italic">No OVP customization fields configured for this product.</p>
                    @else
                        <table class="pm-table pm-table--attrs">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Label</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->ovpFormFields as $field)
                                    <tr>
                                        <td class="pm-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $field->label }}</strong>
                                            @if($field->help_text)
                                                <div class="pm-muted" style="margin-top:4px;">{{ $field->help_text }}</div>
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($field->field_type) }}</td>
                                        <td>{{ $field->is_required ? 'Yes' : 'No' }}</td>
                                        <td>{{ $field->is_active ? 'Active' : 'Inactive' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="pm-card pm-card--detail">
                    <h3 class="pm-detail-section-title">Recent Production Customization Data</h3>
                    @php
                        $recentInitiations = $product->productionInitiations->take(5);
                    @endphp

                    @if($recentInitiations->isEmpty())
                        <p class="pm-muted pm-muted--italic">No production initiation records for this product yet.</p>
                    @else
                        <div style="display:grid; gap:14px;">
                            @foreach($recentInitiations as $initiation)
                                <div style="border:1px solid #f0eef2; border-radius:14px; padding:16px; background:#fcfcfc;">
                                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:12px;">
                                        <div>
                                            <div style="font-size:14px; font-weight:800; color:#121212;">
                                                {{ $initiation->lead?->name ?? 'Lead #' . $initiation->lead_id }}
                                            </div>
                                            <div class="pm-muted" style="margin-top:4px;">
                                                Department: {{ $initiation->department?->name ?? 'N/A' }} | {{ optional($initiation->created_at)->format('d M Y h:i A') }}
                                            </div>
                                        </div>
                                        <span class="pm-category-chip">{{ str_replace('_', ' ', ucfirst((string) $initiation->status)) }}</span>
                                    </div>

                                    @include('pages.products.partials.ovp-submission-values', ['entries' => $initiation->custom_form_data])
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- System Info --}}
                <div class="pm-card pm-card--detail pm-card--meta">
                    <h3 class="pm-detail-section-title">System Information</h3>
                    <div class="pm-sys-grid">
                        <div class="pm-sys-item">
                            <span class="pm-sys-label">Created</span>
                            <span class="pm-sys-value">{{ $product->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="pm-sys-item">
                            <span class="pm-sys-label">Last Updated</span>
                            <span class="pm-sys-value">{{ $product->updated_at->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="pm-sys-item">
                            <span class="pm-sys-label">Category ID</span>
                            <span class="pm-sys-value">#{{ $product->product_category_id }}</span>
                        </div>
                        <div class="pm-sys-item">
                            <span class="pm-sys-label">Product ID</span>
                            <span class="pm-sys-value">#{{ $product->id }}</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>{{-- end pm-show-grid --}}
    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/product.js') }}"></script>
<script>
document.querySelectorAll('.pm-delete-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var name = this.dataset.name;
        if (!confirm('Delete product "' + name + '"?\nThis action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
