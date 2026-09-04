@extends('layouts.app')

@section('title', 'Create Product — myAgenci.ai')

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
            <span class="crumb-item active">Create</span>
        </div>
        <div class="header-actions">
            <a href="{{ route('products.index') }}" class="pm-btn pm-btn--ghost">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                Back
            </a>
        </div>
    </header>

    <div class="pm-page-body">

        {{-- Validation Errors Summary --}}
        @if($errors->any())
            <div class="pm-alert pm-alert--error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
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
                <div class="pm-card-header__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                </div>
                <div>
                    <h2 class="pm-card-title">New Product</h2>
                    <p class="pm-card-subtitle">Fill in the details and add dynamic attributes for this package.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('products.store') }}" id="productForm" novalidate>
                @csrf

                @include('pages.products.partials.form', [
                    'product'    => new \App\Models\Product(),
                    'categories' => $categories,
                    'departments' => $departments,
                    'users'      => $users,
                ])


                <div class="pm-form-actions">
                    <a href="{{ route('products.index') }}" class="pm-btn pm-btn--ghost">Cancel</a>
                    {{--  <button type="submit" name="_action" value="save_and_new" class="pm-btn pm-btn--outline">
                        Save &amp; Create Another
                    </button>  --}}
                    <button type="submit" class="pm-btn pm-btn--primary" id="btnSubmit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Create Product
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('js/product.js') }}"></script>
@endpush
