@extends('layouts.app')

@section('title', 'Project Testing Details')

@push('styles')
<style>
.ptd-page { min-height: 100vh; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); font-family: 'Inter', system-ui, -apple-system, sans-serif; }
.ptd-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px 32px; background: #ffffff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); flex-wrap: wrap; }
.ptd-back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 12px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #cbd5e1; transition: all 0.22s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
.ptd-back-btn:hover { background: #f1f5f9; color: #0f172a; transform: translateX(-3px); border-color: #94a3b8; }

.ptd-title-icon { width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 8px 16px rgba(2,132,199,0.25); flex-shrink: 0; }
.ptd-title { font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em; }
.ptd-body { padding: 28px 32px 48px; display: grid; gap: 24px; max-width: 1280px; margin: 0 auto; }

.ptd-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 24px rgba(15,23,42,0.04); }
.ptd-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px 26px; border-bottom: 1px solid #e2e8f0; background: #ffffff; flex-wrap: wrap; }
.ptd-card-title { font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }
.ptd-card-body { padding: 26px; }

/* Info Grid Cards */
.ptd-grid-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; }
.ptd-info-box { background: #f8fafc; padding: 18px 20px; border-radius: 16px; border: 1px solid #e2e8f0; transition: all 0.2s ease; }
.ptd-info-box:hover { background: #ffffff; border-color: #cbd5e1; box-shadow: 0 6px 16px rgba(15,23,42,0.04); }
.ptd-info-label { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: .06em; }
.ptd-info-val { font-size: 14.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }

/* Action Buttons & Status Selector */
.ptd-status-selector { display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 6px 14px 6px 16px; border-radius: 14px; border: 1.5px solid #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
.ptd-status-select { border: none; background: transparent; outline: none; font-weight: 800; font-size: 13.5px; color: #0f172a; cursor: pointer; padding: 4px 0; }

.ptd-btn-bug { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; border: none; font-weight: 800; padding: 11px 24px; border-radius: 14px; box-shadow: 0 4px 16px rgba(220,38,38,0.3); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; transition: all 0.22s ease; text-decoration: none; }
.ptd-btn-bug:hover { background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(220,38,38,0.4); color: #ffffff; }

/* Credentials & Notes Blocks */
.ptd-credentials-box { background: #0f172a; color: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #1e293b; box-shadow: 0 6px 18px rgba(15,23,42,0.12); }
.ptd-notes-box { background: linear-gradient(135deg, #fffdfb 0%, #fff8f3 100%); padding: 20px; border-radius: 16px; border: 1px solid #ffd8bf; box-shadow: 0 4px 14px rgba(194,65,12,0.04); }

/* Bugs Table Styles */
.ptd-bugs-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.ptd-bugs-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 14px 18px; border-bottom: 1.5px solid #e2e8f0; }
.ptd-bugs-table td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; background: #ffffff; }
.ptd-bugs-table tr:hover td { background: #f8fafc; }
.ptd-bugs-table tr:last-child td { border-bottom: none; }

/* Modal Floating Overlay & Backdrop CSS */
.ps-modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 1200; display: none; opacity: 0; transition: opacity 0.25s ease; }
.ps-modal-overlay.is-open { display: block; opacity: 1; }

.ps-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.95); width: min(580px, calc(100vw - 32px)); max-height: calc(100vh - 48px); overflow: auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.28); z-index: 1210; display: none; opacity: 0; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.ps-modal.is-open { display: block; opacity: 1; transform: translate(-50%, -50%) scale(1); }

.ps-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 22px 26px; border-bottom: 1px solid #e2e8f0; background: #ffffff; }
.ps-modal-close { width: 38px; height: 38px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
.ps-modal-close:hover { background: #f1f5f9; color: #0f172a; }
.ps-modal-body { padding: 24px 26px; }

.pjd-select, .cc-sheet-input { width: 100%; min-height: 44px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 14px; outline: none; transition: all 0.2s ease; }
.pjd-select:focus, .cc-sheet-input:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12); }
.ps-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 10px 20px; border-radius: 12px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; border: none; }
</style>
@endpush

@section('content')
<div class="ptd-page">
    {{-- Topbar --}}
    <div class="ptd-topbar">
        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing']) }}" class="ptd-back-btn">
                <i class="bi bi-arrow-left"></i> Back to Testing Dashboard
            </a>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="ptd-title-icon">🧪</div>
                <div>
                    @php
                        $projectName = $projectItem->leadProduct?->name 
                            ?? $projectItem->product?->name 
                            ?? ($projectItem->lead?->company_name ? $projectItem->lead->company_name . ' Project' : 'Project #' . $projectItem->id);
                    @endphp
                    <div class="ptd-title">
                        {{ $projectName }}
                    </div>
                    @if($projectItem->lead?->company_name)
                        <div style="font-size: 13px; color: #64748b; margin-top: 2px; font-weight: 600;">🏢 Company / Client: <strong>{{ $projectItem->lead->company_name }}</strong></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Testing Status Selector --}}
        <div style="display: flex; align-items: center; gap: 12px;">
            <form method="POST" action="{{ route('projects.testing-details.update-status', $projectItem) }}" class="ptd-status-selector">
                @csrf
                <i class="bi bi-sliders" style="color: #0284c7; font-size: 15px;"></i>
                <span style="font-size: 11.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: .05em;">QA Status:</span>
                <select name="status" onchange="this.form.submit()" class="ptd-status-select">
                    @php $currStatus = $handover?->status ?? 'open'; @endphp
                    <option value="open" {{ in_array($currStatus, ['open', 'moved_to_testing']) ? 'selected' : '' }}>🟧 Open (New Handover)</option>
                    <option value="ongoing" {{ $currStatus === 'ongoing' ? 'selected' : '' }}>🟦 Ongoing Testing</option>
                    <option value="retesting" {{ $currStatus === 'retesting' ? 'selected' : '' }}>🟪 Retesting Phase</option>
                    <option value="completed" {{ $currStatus === 'completed' ? 'selected' : '' }}>🟩 Completed (QA Passed)</option>
                </select>
            </form>
        </div>
    </div>

    <div class="ptd-body">
        {{-- Overview Card --}}
        <div class="ptd-card">
            <div class="ptd-card-head">
                <div class="ptd-card-title">
                    <i class="bi bi-shield-check" style="color: #0284c7; font-size: 20px;"></i> Testing Handover Overview
                </div>
                <button type="button" class="ptd-btn-bug" onclick="openAddBugModal()">
                    <i class="bi bi-bug-fill"></i> + Add Bug
                </button>
            </div>
            <div class="ptd-card-body">
                <div class="ptd-grid-info">
                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Testing Handover Date</div>
                        <div class="ptd-info-val" style="color: #0284c7;">
                            <i class="bi bi-calendar-check-fill"></i>
                            {{ $handover?->created_at ? $handover->created_at->format('d M Y, h:i A') : 'N/A' }}
                        </div>
                    </div>

                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Developer Name</div>
                        <div class="ptd-info-val">
                            <div style="width: 26px; height: 26px; border-radius: 50%; background: #0284c7; color: #fff; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                                {{ strtoupper(substr($handover?->movedBy?->name ?? 'D', 0, 1)) }}
                            </div>
                            {{ $handover?->movedBy?->name ?? 'Dev Team' }}
                        </div>
                    </div>

                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Testing TL</div>
                        <div class="ptd-info-val">
                            <i class="bi bi-person-badge-fill" style="color: #0284c7;"></i>
                            {{ $handover?->testingTl?->name ?? 'Assigned QA TL' }}
                        </div>
                    </div>

                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Target Delivery Date</div>
                        <div class="ptd-info-val" style="color: #dc2626;">
                            <i class="bi bi-alarm-fill"></i>
                            {{ $projectItem->project_delivery_date ? \Carbon\Carbon::parse($projectItem->project_delivery_date)->format('d M Y') : 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Access Credentials & Developer Remarks --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; margin-top: 22px;">
                    @if($handover?->credentials)
                        <div class="ptd-credentials-box">
                            <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #38bdf8; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; letter-spacing: .05em;">
                                <i class="bi bi-key-fill"></i> Testing Credentials &amp; Access Details
                            </div>
                            <div style="font-size: 13.5px; color: #f8fafc; font-family: monospace; white-space: pre-line; line-height: 1.6;">{!! e($handover->credentials) !!}</div>
                        </div>
                    @endif

                    @if($handover?->notes)
                        <div class="ptd-notes-box">
                            <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #c2410c; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; letter-spacing: .05em;">
                                <i class="bi bi-chat-left-text-fill"></i> Developer Remarks &amp; Instructions
                            </div>
                            <div style="font-size: 13.5px; color: #334155; line-height: 1.6; white-space: pre-line; font-weight: 500;">{!! e($handover->notes) !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Reported Bugs Table Card --}}
        <div class="ptd-card">
            <div class="ptd-card-head">
                <div class="ptd-card-title">
                    <i class="bi bi-bug-fill" style="color: #dc2626; font-size: 20px;"></i> Reported Bugs &amp; Issues
                    <span style="font-size: 12px; font-weight: 800; background: #fee2e2; color: #991b1b; padding: 3px 12px; border-radius: 20px; border: 1px solid #fecaca;">
                        {{ $bugs->count() }} Reported
                    </span>
                </div>
                <button type="button" class="ptd-btn-bug" onclick="openAddBugModal()">
                    <i class="bi bi-plus-circle-fill"></i> Add New Bug
                </button>
            </div>
            <div style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table class="ptd-bugs-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th style="width: 150px;">Priority</th>
                                <th>Bug Description</th>
                                <th style="width: 200px;">Reported By &amp; Date</th>
                                <th style="width: 170px; text-align: center;">Attachment</th>
                                <th style="width: 150px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bugs as $index => $bug)
                                @php
                                    $priorityBadge = match($bug->priority) {
                                        'High' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'icon' => 'bi-exclamation-triangle-fill'],
                                        'Low' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd', 'icon' => 'bi-info-circle-fill'],
                                        default => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa', 'icon' => 'bi-exclamation-circle-fill'],
                                    };
                                @endphp
                                <tr>
                                    <td style="text-align: center; font-weight: 800; color: #64748b;">
                                        {{ $index + 1 }}
                                    </td>
                                    <td>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; background: {{ $priorityBadge['bg'] }}; color: {{ $priorityBadge['color'] }}; border: 1px solid {{ $priorityBadge['border'] }}; font-size: 12px; font-weight: 800;">
                                            <i class="bi {{ $priorityBadge['icon'] }}"></i> {{ $bug->priority }} Priority
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 13.5px; color: #0f172a; line-height: 1.6; white-space: pre-line; font-weight: 500; max-width: 520px;">{!! e($bug->description) !!}</div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #1e293b;">
                                                <div style="width: 24px; height: 24px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    {{ strtoupper(substr($bug->createdBy?->name ?? 'Q', 0, 1)) }}
                                                </div>
                                                <span>{{ $bug->createdBy?->name ?? 'QA Tester' }}</span>
                                            </div>
                                            <div style="font-size: 11.5px; color: #64748b; font-weight: 600;">
                                                <i class="bi bi-clock-history"></i> {{ $bug->created_at?->format('d M Y, h:i A') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        @if($bug->attachment_path)
                                            <a href="{{ asset($bug->attachment_path) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 6px 14px; border-radius: 10px; border: 1px solid #bae6fd; text-decoration: none; transition: all 0.2s ease;">
                                                <i class="bi bi-paperclip" style="font-size: 15px;"></i> View File
                                            </a>
                                        @else
                                            <span style="color: #94a3b8; font-size: 12px; font-weight: 600;">No File</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <form method="POST" action="{{ route('projects.bugs.update-status', $bug) }}" style="display: inline-block;">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()" style="font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 20px; border: 1.5px solid #cbd5e1; outline: none; cursor: pointer; background: #ffffff; color: #0f172a;">
                                                <option value="open" {{ $bug->status === 'open' ? 'selected' : '' }}>🟧 Open</option>
                                                <option value="fixed" {{ in_array($bug->status, ['fixed', 'closed', 'resolved']) ? 'selected' : '' }}>🟩 Fixed</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 54px 20px; background: #ffffff;">
                                        <div style="width: 64px; height: 64px; border-radius: 20px; background: #f0f9ff; color: #0284c7; font-size: 32px; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; box-shadow: 0 4px 14px rgba(2,132,199,0.15);">
                                            🎉
                                        </div>
                                        <div style="font-size: 17px; font-weight: 800; color: #0f172a;">No Bugs Reported Yet</div>
                                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Click <strong>+ Add Bug</strong> above to report any QA issues or bugs found during testing.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Bug Modal Popup --}}
<div class="ps-modal-overlay" id="add-bug-modal-overlay" onclick="closeAddBugModal()"></div>
<div class="ps-modal" id="add-bug-modal" style="max-width: 580px; overflow: hidden;">
    <div class="ps-modal-head">
        <div>
            <div class="ps-card-title" style="display:flex; align-items:center; gap:8px; color:#dc2626;">
                🐞 Report Project Bug
            </div>
            <div class="ps-card-sub">Log bug description, set priority level, and upload screenshot or attachment.</div>
        </div>
        <button type="button" class="ps-modal-close" onclick="closeAddBugModal()" aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="ps-modal-body">
        <form method="POST" action="{{ route('projects.bugs.store', $projectItem) }}" enctype="multipart/form-data" onsubmit="handleAddBugSubmit(event, this)">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Priority Level <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="priority" class="pjd-select" style="width: 100%; min-height: 42px; padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1;" required>
                        <option value="High">🔴 High Priority</option>
                        <option value="Medium" selected>🟠 Medium Priority</option>
                        <option value="Low">🔵 Low Priority</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Bug Description <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="description" rows="4" class="cc-sheet-input" style="width: 100%; min-height: 110px; padding: 10px; border-radius: 10px;" placeholder="Describe the issue, step-by-step reproduction, or expected vs actual behavior..." required></textarea>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Attachment / File Upload (Screenshot, PDF, Log File)
                    </label>
                    <input type="file" name="attachment" class="cc-sheet-input" style="width: 100%; padding: 8px 10px;" accept="image/*,.pdf,.doc,.docx,.zip">
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Files will be stored in public directory (`uploads/project-bugs/`) and viewable by team.</div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                    <button type="button" class="ps-btn" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" onclick="closeAddBugModal()">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitBug" class="ps-btn" style="background: #dc2626; color: #ffffff; border: none; font-weight: 700; padding: 10px 22px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                        <span id="btnSubmitBugIcon">🐞</span>
                        <span id="btnSubmitBugText">Submit Bug Report</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openAddBugModal() {
    const btn = document.getElementById('btnSubmitBug');
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        const icon = document.getElementById('btnSubmitBugIcon');
        const text = document.getElementById('btnSubmitBugText');
        if (icon) icon.innerHTML = '🐞';
        if (text) text.textContent = 'Submit Bug Report';
    }
    document.getElementById('add-bug-modal-overlay').classList.add('is-open');
    document.getElementById('add-bug-modal').classList.add('is-open');
}
function closeAddBugModal() {
    document.getElementById('add-bug-modal-overlay').classList.remove('is-open');
    document.getElementById('add-bug-modal').classList.remove('is-open');
}
function handleAddBugSubmit(event, form) {
    const btn = document.getElementById('btnSubmitBug');
    if (btn) {
        if (btn.disabled) {
            event.preventDefault();
            return false;
        }
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'wait';
        const icon = document.getElementById('btnSubmitBugIcon');
        const text = document.getElementById('btnSubmitBugText');
        if (icon) icon.innerHTML = '<i class="bi bi-arrow-repeat ps-spin-icon"></i>';
        if (text) text.textContent = ' Submitting Bug Report...';
    }
}
</script>
@endsection
