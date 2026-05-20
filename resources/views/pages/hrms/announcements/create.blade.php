@extends('layouts.app')

@section('title', 'Create Announcement')

@push('styles')
<style>
.hac-page{min-height:100%;padding:28px;background:linear-gradient(180deg,#fff8f3 0%,#f4f5f7 100%)}
.hac-shell{max-width:860px;margin:0 auto;display:flex;flex-direction:column;gap:18px}
.hac-card{background:#fff;border:1px solid #e7e5e4;border-radius:22px;padding:22px;box-shadow:0 16px 36px rgba(18,18,18,.05)}
.hac-title{margin:0;font-size:24px;font-weight:800;color:#121212}
.hac-sub{margin-top:8px;font-size:14px;line-height:1.7;color:#7a7a7a}
.hac-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.hac-field{display:flex;flex-direction:column;gap:8px}
.hac-field.full{grid-column:1/-1}
.hac-label{font-size:13px;font-weight:700;color:#374151}
.hac-input,.hac-select,.hac-textarea{border:1px solid #e5ddd6;border-radius:14px;background:#fff;color:#121212;font-size:14px}
.hac-input,.hac-select{height:46px;padding:0 14px}
.hac-textarea{min-height:140px;padding:14px;resize:vertical}
.hac-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 16px;border-radius:14px;border:1px solid transparent;text-decoration:none;font-size:13px;font-weight:700}
.hac-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);color:#fff}
.hac-btn-ghost{background:#fff;color:#121212;border-color:#e5ddd6}
.hac-error{font-size:12px;color:#b91c1c}
@media (max-width:768px){.hac-page{padding:18px}.hac-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="hac-page">
    <div class="hac-shell">
        <div class="hac-card">
            <h2 class="hac-title">Create Announcement</h2>
            <div class="hac-sub">This announcement will be shown in the HRMS dashboard announcement section for all logins.</div>
        </div>

        <form method="POST" action="{{ route('hrms-announcements.store') }}" class="hac-card">
            @csrf
            <div class="hac-grid">
                <div class="hac-field">
                    <label class="hac-label">Title</label>
                    <input type="text" name="title" class="hac-input" value="{{ old('title') }}" required>
                    @error('title')<div class="hac-error">{{ $message }}</div>@enderror
                </div>
                <div class="hac-field">
                    <label class="hac-label">Priority</label>
                    <select name="priority" class="hac-select" required>
                        <option value="high" @selected(old('priority') === 'high')>High</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                        <option value="low" @selected(old('priority') === 'low')>Low</option>
                    </select>
                    @error('priority')<div class="hac-error">{{ $message }}</div>@enderror
                </div>
                <div class="hac-field">
                    <label class="hac-label">Announcement Date</label>
                    <input type="date" name="announcement_date" class="hac-input" value="{{ old('announcement_date', now()->toDateString()) }}" required>
                    @error('announcement_date')<div class="hac-error">{{ $message }}</div>@enderror
                </div>
                <div class="hac-field">
                    <label class="hac-label">Status</label>
                    <select name="is_active" class="hac-select">
                        <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                    </select>
                    @error('is_active')<div class="hac-error">{{ $message }}</div>@enderror
                </div>
                <div class="hac-field full">
                    <label class="hac-label">Message</label>
                    <textarea name="message" class="hac-textarea" required>{{ old('message') }}</textarea>
                    @error('message')<div class="hac-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;flex-wrap:wrap;">
                <a href="{{ route('hrms-announcements.index') }}" class="hac-btn hac-btn-ghost">Cancel</a>
                <button type="submit" class="hac-btn hac-btn-primary">Create Announcement</button>
            </div>
        </form>
    </div>
</div>
@endsection
