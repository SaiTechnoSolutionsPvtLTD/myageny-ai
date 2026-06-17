@extends('layouts.app')

@section('title', 'Product Master — myAgenci.ai')

@include('pages.products.style')

@section('content')
<main class="main-content">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <header class="top-header">
        <div class="breadcrumbs">
            <span class="crumb-item">Home</span>
            <span class="crumb-sep">/</span>
            <span class="crumb-item active">Product Master</span>
        </div>
        @can('products.create')
        <div class="header-actions">
            <a href="{{ route('products.create') }}" class="pm-btn pm-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Product
            </a>
        </div>
        @endcan
    </header>

    <div class="pm-page-body">

        {{-- Flash --}}
        @if(session('success'))
            <div class="pm-alert pm-alert--success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- ── Filters ───────────────────────────────────────────── --}}
        <div class="pm-filter-bar">
            <form method="GET" action="{{ route('products.index') }}" class="pm-filter-form">
                <div class="pm-filter-group">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search package name, SKU…" class="pm-input pm-input--sm">
                </div>
                <div class="pm-filter-group">
                    <select name="category" class="pm-select pm-select--sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pm-filter-group">
                    <select name="status" class="pm-select pm-select--sm">
                        <option value="">All Status</option>
                        <option value="active"   @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        <option value="draft"    @selected(request('status') === 'draft')>Draft</option>
                    </select>
                </div>
                <button type="submit" class="pm-btn pm-btn--outline pm-btn--sm">Filter</button>
                @if(request()->hasAny(['search','category','status']))
                    <a href="{{ route('products.index') }}" class="pm-btn pm-btn--ghost pm-btn--sm">Clear</a>
                @endif
            </form>
            <span class="pm-result-count">{{ $products->total() }} product(s)</span>
        </div>

        {{-- ── Table ────────────────────────────────────────────── --}}
        <div class="pm-card">
            @if($products->isEmpty())
                <div class="pm-empty">
                    <div class="pm-empty__icon">📦</div>
                    <h3>No products yet</h3>
                    <p>Create your first product to get started.</p>
                    <a href="{{ route('products.create') }}" class="pm-btn pm-btn--primary">Create Product</a>
                </div>
            @else
                <div class="pm-table-wrap">
                    <table class="pm-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Package Name</th>
                                <th>Category</th>
                                <th>Base Price</th>
                                <th>Tax</th>
                                <th>Discount</th>
                                <th>Final Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td><span class="pm-sku">{{ $product->sku }}</span></td>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="pm-product-name">
                                        {{ $product->package_name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="pm-category-chip">{{ $product->category->name }}</span>
                                </td>
                                <td class="pm-price">₹{{ number_format($product->base_price, 2) }}</td>
                                <td class="pm-muted">
                                    {{ $product->tax_value > 0
                                        ? $product->tax_value . ($product->tax_type === 'percentage' ? '%' : ' ₹')
                                        : '—' }}
                                </td>
                                <td class="pm-muted">
                                    {{ $product->discount_value > 0
                                        ? $product->discount_value . ($product->discount_type === 'percentage' ? '%' : ' ₹')
                                        : '—' }}
                                </td>
                                <td class="pm-price pm-price--final">₹{{ number_format($product->final_price, 2) }}</td>
                                <td>{!! $product->statusBadge !!}</td>
                                <td>
                                    <div class="pm-actions">
                                        @can('products.view')
                                        <a href="{{ route('products.show', $product) }}"
                                           class="pm-icon-btn" title="View">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        @endcan

                                        @can('products.edit')
                                        <a href="{{ route('products.ovp-form.builder', $product) }}"
                                           class="pm-icon-btn" title="OVP Form">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg>
                                        </a>
                                        @endcan

                                         @can('products.edit')
                                        <a href="{{ route('products.edit', $product) }}"
                                           class="pm-icon-btn pm-icon-btn--edit" title="Edit">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        @endcan

                                         @can('products.delete')
                                        <form method="POST" action="{{ route('products.destroy', $product) }}"
                                              class="pm-delete-form" data-name="{{ $product->package_name }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="pm-icon-btn pm-icon-btn--delete" title="Delete">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($products->hasPages())
                <div class="pm-pagination">
                    @include('partials.table-pagination', ['paginator' => $products])
                </div>
                @endif
            @endif
        </div>

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
