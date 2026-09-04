@extends('layouts.app')

@section('title', 'HRMS Announcements')

@push('styles')
<style>
.han-page { min-height: 100%; padding: 28px; background: linear-gradient(180deg, #fff8f3 0%, #f4f5f7 100%); }
.han-shell { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.han-card { background: #fff; border: 1px solid #e7e5e4; border-radius: 22px; padding: 22px; box-shadow: 0 16px 36px rgba(18, 18, 18, .05); }
.han-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
.han-title { margin: 0; font-size: 24px; font-weight: 800; color: #121212; }
.han-sub { margin-top: 8px; font-size: 14px; line-height: 1.7; color: #7a7a7a; }
.han-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 16px; border-radius: 12px; border: 1px solid transparent; text-decoration: none; font-size: 13px; font-weight: 700; cursor: pointer; transition: all .15s ease; }
.han-btn-primary { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; box-shadow: 0 4px 14px rgba(254, 95, 4, .25); }
.han-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(254, 95, 4, .35); }
.han-btn-ghost { background: #fff; color: #121212; border-color: #e5ddd6; }
.han-btn-ghost:hover { background: #f9f9f9; }

.han-list { display: grid; gap: 14px; }
.han-item { padding: 20px; border-radius: 18px; background: #fff; border: 1px solid #efe7e0; box-shadow: 0 4px 16px rgba(0, 0, 0, .03); transition: transform .15s ease, box-shadow .15s ease; }
.han-item:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0, 0, 0, .06); }
.han-item-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.han-item-title-wrap { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.han-item-title { font-size: 17px; font-weight: 800; color: #121212; }
.han-item-message { margin-top: 12px; font-size: 13.5px; line-height: 1.75; color: #4b5563; white-space: pre-line; }

.han-badges-wrap { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.han-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
.han-pill-high { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.han-pill-medium { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.han-pill-low { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.han-branch-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #f5f3ff; color: #60308c; border: 1px solid #ede9fe; }
.han-branch-badge.all { background: #f0fdf4; color: #15803d; border-color: #dcfce7; }

.han-footer { margin-top: 14px; padding-top: 12px; border-top: 1px solid #f3ede7; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 12px; color: #8a8a8a; }
.han-status-pill { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.han-status-active { background: #ecfdf5; color: #047857; }
.han-status-inactive { background: #f3f4f6; color: #6b7280; }

.han-actions { display: flex; align-items: center; gap: 8px; }
.han-act-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
.han-act-edit { background: #fff7ed; color: #fe5f04; border-color: #fed7aa; }
.han-act-edit:hover { background: #fe5f04; color: #fff; border-color: #fe5f04; }
.han-act-delete { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.han-act-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

@media (max-width: 768px) {
    .han-page { padding: 18px; }
    .han-head { flex-direction: column; align-items: flex-start; }
    .han-item-head { flex-direction: column; align-items: flex-start; }
    .han-footer { flex-direction: column; align-items: flex-start; }
}
</style>
@endpush

@section('content')
<div class="han-page">
    <div class="han-shell">
        @if(session('success'))
            <div class="han-card" style="border-color: #bbf7d0; background: #f0fdf4; color: #166534; font-size: 13px; font-weight: 700;">
                ✓ {{ session('success') }}
            </div>
        @endif

        <div class="han-card">
            <div class="han-head">
                <div>
                    <h2 class="han-title">Announcements</h2>
                    <div class="han-sub">
                        @if($canManage ?? false)
                            Create, update, and target announcements by branch to display on employee dashboards.
                        @else
                            Latest organization announcements and important updates for your branch.
                        @endif
                    </div>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ route('hrms.dashboard') }}" class="han-btn han-btn-ghost">← HRMS Dashboard</a>
                    @if($canManage ?? false)
                        <a href="{{ route('hrms-announcements.create') }}" class="han-btn han-btn-primary">+ Create Announcement</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="han-list">
            @forelse($announcements as $announcement)
                <div class="han-card han-item">
                    <div class="han-item-head">
                        <div class="han-item-title-wrap">
                            <span class="han-item-title">{{ $announcement->title }}</span>
                            <div class="han-badges-wrap">
                                <span class="han-pill han-pill-{{ $announcement->priority }}">
                                    @if($announcement->priority === 'high') 🔴 @elseif($announcement->priority === 'medium') 🟠 @else 🔵 @endif
                                    {{ strtoupper($announcement->priority) }}
                                </span>
                                @if($announcement->isForAllBranches())
                                    <span class="han-branch-badge all">🌐 All Branches</span>
                                @else
                                    <span class="han-branch-badge">🏢 {{ $announcement->getTargetBranchesLabel() }}</span>
                                @endif
                            </div>
                        </div>

                        @if($canManage ?? false)
                            <div class="han-actions">
                                <a href="{{ route('hrms-announcements.edit', $announcement) }}" class="han-act-btn han-act-edit" title="Edit Announcement">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('hrms-announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete announcement: &quot;{{ addslashes($announcement->title) }}&quot;?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="han-act-btn han-act-delete" title="Delete Announcement">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="han-item-message">{{ $announcement->message }}</div>

                    <div class="han-footer">
                        <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                            <span>📅 <strong>{{ optional($announcement->announcement_date)->format('d M Y') ?: 'N/A' }}</strong></span>
                            @if($announcement->creator)
                                <span>👤 Posted by <strong>{{ $announcement->creator->name }}</strong></span>
                            @endif
                        </div>

                        @if($canManage ?? false)
                            <div>
                                <span class="han-status-pill {{ $announcement->is_active ? 'han-status-active' : 'han-status-inactive' }}">
                                    {{ $announcement->is_active ? '● Active' : '○ Inactive' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="han-card" style="text-align: center; color: #9ca3af; padding: 40px;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📢</div>
                    <div style="font-size: 15px; font-weight: 700; color: #4b5563;">No announcements created yet.</div>
                    <div style="font-size: 13px; color: #9ca3af; margin-top: 4px;">Announcements published here will appear on employee dashboards.</div>
                </div>
            @endforelse
        </div>

        <div>
            {{ $announcements->links() }}
        </div>
    </div>
</div>
@endsection
