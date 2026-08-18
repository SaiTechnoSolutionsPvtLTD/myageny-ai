@extends('layouts.app')

@section('title', 'Edit Product — ' . $product->package_name)

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
            <a href="{{ route('products.show', $product) }}" class="crumb-item">{{ $product->package_name }}</a>
            <span class="crumb-sep">/</span>
            <span class="crumb-item active">Edit</span>
        </div>
        <div class="header-actions">
            <a href="{{ route('products.show', $product) }}" class="pm-btn pm-btn--ghost">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                Cancel
            </a>
        </div>
    </header>

    <div class="pm-page-body">

        @if($errors->any())
            <div class="pm-alert pm-alert--error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul class="pm-error-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="pm-card pm-card--form">
            <div class="pm-card-header">
                <div class="pm-card-header__icon pm-card-header__icon--edit">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="pm-card-title">Edit — {{ $product->package_name }}</h2>
                    <p class="pm-card-subtitle">
                        <span class="pm-sku">{{ $product->sku }}</span>
                        &bull; {{ $product->category?->name ?? 'Uncategorized' }}
                        &bull; Created {{ $product->created_at->format('d M Y') }}
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('products.update', $product) }}" id="productForm" novalidate>
                @csrf
                @method('PUT')

                @include('pages.products.partials.form', [
                    'product'        => $product,
                    'categories'     => $categories,
                    'departments'    => $departments,
                    'existingValues' => $existingValues,
                    'users'          => $users,
                ])

                <div class="pm-form-actions">
                    <a href="{{ route('products.show', $product) }}" class="pm-btn pm-btn--ghost">Cancel</a>
                    <button type="submit" class="pm-btn pm-btn--primary" id="btnSubmit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Update Product
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/product.js') }}"></script>
@endpush
