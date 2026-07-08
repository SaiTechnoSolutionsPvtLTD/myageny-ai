@extends('layouts.app')

@section('title', 'Lead Reallocation - Settings')

@push('styles')
<style>
    .reallocate-page {
        min-height: 100%;
        padding: 28px;
        background:
            radial-gradient(circle at top left, rgba(254, 95, 4, 0.10), transparent 30%),
            linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    }

    .reallocate-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .reallocate-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid #e6e8ee;
        color: #4b5563;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: all .18s ease;
    }

    .reallocate-back:hover {
        background: rgba(255, 255, 255, 0.95);
        border-color: #fed7aa;
    }

    .reallocate-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
        margin-bottom: 28px;
        padding: 28px;
        border: 1px solid #e6e8ee;
        border-radius: 22px;
        background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
        box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
    }

    .reallocate-hero-content h1 {
        margin: 0 0 10px;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.1;
        color: #111827;
    }

    .reallocate-hero-content p {
        margin: 0;
        max-width: 540px;
        font-size: 14px;
        line-height: 1.6;
        color: #6b7280;
    }

    .reallocate-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        background: #fff1e8;
        color: #c2410c;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .7px;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .reallocate-kicker svg {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
    }

    .reallocate-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 28px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
        margin-bottom: 24px;
    }

    .reallocate-section {
        margin-bottom: 28px;
    }

    .reallocate-section:last-child {
        margin-bottom: 0;
    }

    .reallocate-label {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .reallocate-label-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 6px;
        background: #fe5f04;
        color: white;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .reallocate-label-text {
        font-size: 15px;
        font-weight: 800;
        color: #111827;
    }

    .reallocate-select {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #e5e7eb;
        border-radius: 14px;
        font-size: 14px;
        background-color: #ffffff;
        transition: all .18s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%234b5563' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    .reallocate-select:hover {
        border-color: #fed7aa;
    }

    .reallocate-select:focus {
        outline: none;
        border-color: #fe5f04;
        box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.1);
    }

    .reallocate-select option {
        padding: 8px;
    }

    .reallocate-info {
        margin-top: 8px;
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }

    .reallocate-divider {
        display: flex;
        align-items: center;
        gap: 16px;
        margin: 28px 0;
        opacity: 0.5;
    }

    .reallocate-divider-line {
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }

    .reallocate-divider-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fe5f04;
    }

    .reallocate-divider-icon svg {
        width: 20px;
        height: 20px;
    }

    .reallocate-details {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border: 1px solid #fed7aa;
        border-radius: 14px;
        padding: 20px;
        margin: 28px 0;
    }

    .reallocate-details-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        font-size: 14px;
        font-weight: 800;
        color: #92400e;
    }

    .reallocate-details-title svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }

    .reallocate-details-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .reallocate-details-list li {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 12px;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 10px;
        border: 1px solid rgba(254, 95, 4, 0.15);
        text-align: center;
    }

    .reallocate-details-list .emoji {
        font-size: 24px;
        margin-bottom: 6px;
    }

    .reallocate-details-list .text {
        font-size: 12px;
        font-weight: 700;
        color: #92400e;
    }

    .reallocate-alert {
        border-radius: 14px;
        padding: 18px;
        margin: 28px 0;
        border-left: 4px solid;
    }

    .reallocate-alert h3 {
        margin: 0 0 10px;
        font-size: 14px;
        font-weight: 800;
    }

    .reallocate-alert p, .reallocate-alert li {
        margin: 0;
        font-size: 13px;
        line-height: 1.6;
    }

    .reallocate-alert ul {
        margin: 0;
        padding: 0 0 0 18px;
    }

    .reallocate-alert li {
        margin-bottom: 6px;
    }

    .reallocate-alert.error {
        background: #fef2f2;
        border-color: #f87171;
        color: #991b1b;
    }

    .reallocate-alert.error h3 {
        color: #991b1b;
    }

    .reallocate-alert.success {
        background: #f0fdf4;
        border-color: #86efac;
        color: #166534;
    }

    .reallocate-alert.success h3 {
        color: #166534;
    }

    .reallocate-alert.warning {
        background: #fffbeb;
        border-color: #fcd34d;
        color: #92400e;
    }

    .reallocate-alert.warning h3 {
        color: #92400e;
    }

    .reallocate-buttons {
        display: flex;
        gap: 12px;
        margin-top: 28px;
    }

    .reallocate-btn {
        flex: 1;
        padding: 14px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        border: none;
        cursor: pointer;
        transition: all .18s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }

    .reallocate-btn-submit {
        background: #fe5f04;
        color: white;
    }

    .reallocate-btn-submit:hover {
        background: #ea4f0c;
        box-shadow: 0 8px 20px rgba(254, 95, 4, 0.25);
        transform: translateY(-2px);
    }

    .reallocate-btn-cancel {
        background: #f3f4f6;
        color: #4b5563;
        border: 1px solid #e5e7eb;
    }

    .reallocate-btn-cancel:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
    }

    @media (max-width: 768px) {
        .reallocate-page {
            padding: 18px;
        }

        .reallocate-hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 22px;
        }

        .reallocate-hero-content h1 {
            font-size: 24px;
        }

        .reallocate-details-list {
            grid-template-columns: 1fr;
        }

        .reallocate-buttons {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div class="reallocate-page">
    <div class="reallocate-header">
        <a href="{{ route('settings.index') }}" class="reallocate-back">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Settings
        </a>
    </div>

    @if ($errors->any())
        <div class="reallocate-alert error">
            <h3>⚠ Validation Errors</h3>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="reallocate-alert success">
            <h3>✓ Success</h3>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="reallocate-alert error">
            <h3>⚠ Error</h3>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="reallocate-hero">
        <div class="reallocate-hero-content">
            <div class="reallocate-kicker">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Lead Management
            </div>
            <h1>Lead Reallocation</h1>
            <p>Transfer leads and products from one team member to another. All associated data, payment records, and historical information will be preserved with updated ownership.</p>
        </div>
    </div>

    <div class="reallocate-card">
        <form action="{{ route('settings.lead-reallocation.reallocate') }}" method="POST">
            @csrf

            <!-- Step 1: From User -->
            <div class="reallocate-section">
                <div class="reallocate-label">
                    <span class="reallocate-label-badge">📋 Step 1</span>
                    <span class="reallocate-label-text">Select Source User</span>
                </div>

                <label for="from_user_id" style="display: block; font-size: 13px; color: #6b7280; margin-bottom: 8px;">
                    Whose leads are being reallocated?
                </label>

                <select
                    id="from_user_id"
                    name="from_user_id"
                    required
                    class="reallocate-select @error('from_user_id') border-red-500 @enderror"
                >
                    <option value="">👤 Select the user whose leads you want to reallocate...</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('from_user_id') == $user->id)>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>

                @error('from_user_id')
                    <p style="color: #dc2626; font-size: 13px; margin-top: 6px;">{{ $message }}</p>
                @enderror

                <p class="reallocate-info">
                    💡 All leads and products assigned to this user will be transferred to your selected destination user.
                </p>
            </div>

            <!-- Divider -->
            <div class="reallocate-divider">
                <div class="reallocate-divider-line"></div>
                <div class="reallocate-divider-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
                <div class="reallocate-divider-line"></div>
            </div>

            <!-- Step 2: To User -->
            <div class="reallocate-section">
                <div class="reallocate-label">
                    <span class="reallocate-label-badge" style="background: #10b981;">✓ Step 2</span>
                    <span class="reallocate-label-text">Select Destination User</span>
                </div>

                <label for="to_user_id" style="display: block; font-size: 13px; color: #6b7280; margin-bottom: 8px;">
                    Who will receive these leads?
                </label>

                <select
                    id="to_user_id"
                    name="to_user_id"
                    required
                    class="reallocate-select @error('to_user_id') border-red-500 @enderror"
                >
                    <option value="">👤 Select the user who will receive the leads...</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('to_user_id') == $user->id)>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>

                @error('to_user_id')
                    <p style="color: #dc2626; font-size: 13px; margin-top: 6px;">{{ $message }}</p>
                @enderror

                <p class="reallocate-info">
                    💡 This user becomes the new owner of all leads, products, and associated payment records.
                </p>
            </div>

            <!-- What Will Be Reallocated -->
            <div class="reallocate-details">
                <h3 class="reallocate-details-title">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"></path>
                    </svg>
                    What Gets Transferred
                </h3>

                <ul class="reallocate-details-list">
                    <li>
                        <div class="emoji">📋</div>
                        <div class="text">All Leads</div>
                    </li>
                    <li>
                        <div class="emoji">📦</div>
                        <div class="text">Lead Products</div>
                    </li>
                    <li>
                        <div class="emoji">💳</div>
                        <div class="text">Payment Records</div>
                    </li>
                </ul>
            </div>

            <!-- Warning Alert -->
            <div class="reallocate-alert warning">
                <h3>⚠ Important Reminders</h3>
                <ul>
                    <li>This action cannot be undone — ensure you've selected the correct users</li>
                    <li>All timestamps and audit trails remain intact for compliance tracking</li>
                    <li>Historical records will be preserved with updated ownership information</li>
                    <li>Team members will see the reallocated leads under their new owner immediately</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="reallocate-buttons">
                <button
                    type="submit"
                    class="reallocate-btn reallocate-btn-submit"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Reallocate Leads Now
                </button>
                <a
                    href="{{ route('settings.index') }}"
                    class="reallocate-btn reallocate-btn-cancel"
                >
                    ✕ Cancel
                </a>
            </div>
        </form>
    </div>

    <p style="text-align: center; color: #9ca3af; font-size: 12px; margin-top: 20px;">
        Need help? Contact your administrator for assistance with lead reallocation.
    </p>
</div>
@endsection
