@extends('layouts.app')

@section('title', 'HRMS Announcements')

@push('styles')
<style>
.han-page{min-height:100%;padding:28px;background:linear-gradient(180deg,#fff8f3 0%,#f4f5f7 100%)}
.han-shell{max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:18px}
.han-card{background:#fff;border:1px solid #e7e5e4;border-radius:22px;padding:22px;box-shadow:0 16px 36px rgba(18,18,18,.05)}
.han-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.han-title{margin:0;font-size:24px;font-weight:800;color:#121212}
.han-sub{margin-top:8px;font-size:14px;line-height:1.7;color:#7a7a7a}
.han-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 16px;border-radius:14px;border:1px solid transparent;text-decoration:none;font-size:13px;font-weight:700}
.han-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);color:#fff}
.han-btn-ghost{background:#fff;color:#121212;border-color:#e5ddd6}
.han-list{display:grid;gap:12px}
.han-item{padding:18px;border-radius:18px;background:#faf7f4;border:1px solid #efe7e0}
.han-meta{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
.han-item-title{font-size:16px;font-weight:800;color:#121212}
.han-item-message{margin-top:10px;font-size:13px;line-height:1.7;color:#6b7280}
.han-pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.han-pill-high{background:#fef2f2;color:#b91c1c}
.han-pill-medium{background:#fff7ed;color:#c2410c}
.han-pill-low{background:#eff6ff;color:#1d4ed8}
.han-status{font-size:12px;color:#8a8a8a}
@media (max-width:768px){.han-page{padding:18px}.han-head{flex-direction:column;align-items:flex-start}}
</style>
@endpush

@section('content')
<div class="han-page">
    <div class="han-shell">
        @if(session('success'))
            <div class="han-card" style="border-color:#bbf7d0;background:#f0fdf4;color:#166534;font-size:13px;font-weight:700;">
                {{ session('success') }}
            </div>
        @endif

        <div class="han-card">
            <div class="han-head">
                <div>
                    <h2 class="han-title">Announcements</h2>
                    <div class="han-sub">Create and review announcements that should appear on every employee dashboard.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="{{ route('hrms.dashboard') }}" class="han-btn han-btn-ghost">Back</a>
                    <a href="{{ route('hrms-announcements.create') }}" class="han-btn han-btn-primary">Create Announcement</a>
                </div>
            </div>
        </div>

        <div class="han-list">
            @forelse($announcements as $announcement)
                <div class="han-card han-item">
                    <div class="han-meta">
                        <div class="han-item-title">{{ $announcement->title }}</div>
                        <span class="han-pill han-pill-{{ $announcement->priority }}">{{ strtoupper($announcement->priority) }}</span>
                    </div>
                    <div class="han-item-message">{{ $announcement->message }}</div>
                    <div class="han-meta" style="margin-top:12px;">
                        <div class="han-status">{{ optional($announcement->announcement_date)->format('d M Y') ?: 'N/A' }}</div>
                        <div class="han-status">{{ $announcement->is_active ? 'Active' : 'Inactive' }}</div>
                    </div>
                </div>
            @empty
                <div class="han-card" style="text-align:center;color:#9ca3af;">No announcements created yet.</div>
            @endforelse
        </div>

        <div>
            {{ $announcements->links() }}
        </div>
    </div>
</div>
@endsection
