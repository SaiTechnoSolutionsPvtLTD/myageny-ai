@extends('layouts.app')

@section('title', 'Edit Announcement')

@push('styles')
<style>
.hac-page { min-height: 100%; padding: 28px; background: linear-gradient(180deg, #fff8f3 0%, #f4f5f7 100%); }
.hac-shell { max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.hac-card { background: #fff; border: 1px solid #e7e5e4; border-radius: 22px; padding: 24px; box-shadow: 0 16px 36px rgba(18, 18, 18, .05); }
.hac-title { margin: 0; font-size: 24px; font-weight: 800; color: #121212; }
.hac-sub { margin-top: 8px; font-size: 14px; line-height: 1.7; color: #7a7a7a; }
.hac-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.hac-field { display: flex; flex-direction: column; gap: 8px; }
.hac-field.full { grid-column: 1 / -1; }
.hac-label { font-size: 13px; font-weight: 700; color: #374151; }
.hac-label .req { color: #fe5f04; }
.hac-input, .hac-select, .hac-textarea { border: 1px solid #e5ddd6; border-radius: 14px; background: #fff; color: #121212; font-size: 14px; outline: none; font-family: inherit; transition: border-color .15s ease, box-shadow .15s ease; }
.hac-input:focus, .hac-select:focus, .hac-textarea:focus { border-color: #fe5f04; box-shadow: 0 0 0 3px rgba(254, 95, 4, .1); }
.hac-input, .hac-select { height: 46px; padding: 0 14px; }
.hac-textarea { min-height: 140px; padding: 14px; resize: vertical; }
.hac-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 18px; border-radius: 14px; border: 1px solid transparent; text-decoration: none; font-size: 13px; font-weight: 700; cursor: pointer; transition: all .15s ease; }
.hac-btn-primary { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; box-shadow: 0 4px 14px rgba(254, 95, 4, .25); }
.hac-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(254, 95, 4, .35); }
.hac-btn-ghost { background: #fff; color: #121212; border-color: #e5ddd6; }
.hac-btn-ghost:hover { background: #f9f9f9; }
.hac-error { font-size: 12px; color: #b91c1c; }

/* Target Branch Radio & Box */
.hac-target-options { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 4px; }
.hac-target-card { display: flex; align-items: center; gap: 10px; padding: 12px 18px; border: 1px solid #e5ddd6; border-radius: 14px; background: #fafafa; cursor: pointer; font-size: 13px; font-weight: 600; color: #374151; transition: all .15s ease; user-select: none; }
.hac-target-card.active, .hac-target-card:has(input:checked) { border-color: #fe5f04; background: #fff8f3; color: #fe5f04; font-weight: 700; box-shadow: 0 0 0 1px #fe5f04; }
.hac-target-card input[type="radio"] { accent-color: #fe5f04; width: 16px; height: 16px; cursor: pointer; }

.hac-branch-box { border: 1px solid #e5ddd6; border-radius: 14px; padding: 14px; background: #fafafa; display: flex; flex-direction: column; gap: 10px; max-height: 220px; overflow-y: auto; }
.hac-branch-item { display: flex; align-items: center; gap: 10px; padding: 6px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; transition: background .12s ease; user-select: none; }
.hac-branch-item:hover { background: #f0edea; }
.hac-branch-item input[type="checkbox"] { accent-color: #fe5f04; width: 16px; height: 16px; cursor: pointer; }

/* Modal Confirmation */
.hac-modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .45); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 9999; animation: fadeIn .15s ease; }
.hac-modal { background: #fff; border-radius: 20px; width: 480px; max-width: 95vw; box-shadow: 0 20px 50px rgba(0,0,0,.2); overflow: hidden; }
.hac-modal-head { padding: 20px 24px; border-bottom: 1px solid #f1eff3; display: flex; justify-content: space-between; align-items: center; }
.hac-modal-head h3 { margin: 0; font-size: 18px; font-weight: 800; color: #121212; display: flex; align-items: center; gap: 8px; }
.hac-modal-body { padding: 22px 24px; font-size: 14px; line-height: 1.6; color: #4b5563; }
.hac-summary-box { background: #f9f8f6; border: 1px solid #ede8e3; border-radius: 12px; padding: 14px; margin-top: 14px; display: flex; flex-direction: column; gap: 8px; font-size: 13px; }
.hac-summary-row { display: flex; justify-content: space-between; gap: 12px; }
.hac-summary-label { color: #8a8a8a; font-weight: 600; }
.hac-summary-val { color: #121212; font-weight: 700; text-align: right; }
.hac-modal-footer { padding: 16px 24px; background: #faf8f6; border-top: 1px solid #f1eff3; display: flex; justify-content: flex-end; gap: 10px; }

@keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }
@media (max-width: 768px) { .hac-page { padding: 18px; } .hac-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="hac-page">
    <div class="hac-shell">
        <div class="hac-card">
            <h2 class="hac-title">Edit Announcement</h2>
            <div class="hac-sub">Update announcement details, priority, active status, or target branches.</div>
        </div>

        @php
            $savedBranchIds = (array) ($announcement->branch_ids ?? []);
            $initialTargetType = old('target_type', empty($savedBranchIds) ? 'all' : 'specific');
        @endphp

        <form id="announcementForm" method="POST" action="{{ route('hrms-announcements.update', $announcement) }}" class="hac-card">
            @csrf
            @method('PUT')
            <div class="hac-grid">
                <div class="hac-field full">
                    <label class="hac-label">Title <span class="req">*</span></label>
                    <input type="text" id="annTitle" name="title" class="hac-input" value="{{ old('title', $announcement->title) }}" required>
                    @error('title')<div class="hac-error">{{ $message }}</div>@enderror
                </div>

                <div class="hac-field">
                    <label class="hac-label">Priority <span class="req">*</span></label>
                    <select id="annPriority" name="priority" class="hac-select" required>
                        <option value="high" @selected(old('priority', $announcement->priority) === 'high')>🔴 High Priority</option>
                        <option value="medium" @selected(old('priority', $announcement->priority) === 'medium')>🟠 Medium Priority</option>
                        <option value="low" @selected(old('priority', $announcement->priority) === 'low')>🔵 Low Priority</option>
                    </select>
                    @error('priority')<div class="hac-error">{{ $message }}</div>@enderror
                </div>

                <div class="hac-field">
                    <label class="hac-label">Announcement Date <span class="req">*</span></label>
                    <input type="date" id="annDate" name="announcement_date" class="hac-input" value="{{ old('announcement_date', optional($announcement->announcement_date)->toDateString()) }}" required>
                    @error('announcement_date')<div class="hac-error">{{ $message }}</div>@enderror
                </div>

                {{-- Target Branches --}}
                <div class="hac-field full">
                    <label class="hac-label">Target Audience / Branches <span class="req">*</span></label>
                    <div class="hac-target-options">
                        <label class="hac-target-card" id="cardAllBranches">
                            <input type="radio" name="target_type" value="all" @checked($initialTargetType === 'all') onchange="toggleBranchSelection()">
                            <span>🌐 All Branches</span>
                        </label>
                        <label class="hac-target-card" id="cardSpecificBranches">
                            <input type="radio" name="target_type" value="specific" @checked($initialTargetType === 'specific') onchange="toggleBranchSelection()">
                            <span>🏢 Select Specific Branches</span>
                        </label>
                    </div>

                    <div id="branchSelectionBox" style="display: {{ $initialTargetType === 'specific' ? 'block' : 'none' }}; margin-top: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 12px; color: #6b7280; font-weight: 600;">Choose one or more branches:</span>
                            <button type="button" onclick="toggleSelectAllBranches()" class="hac-btn" style="padding: 4px 10px; font-size: 12px; background: #f3f4f6; color: #374151; border-radius: 8px;">
                                Select / Deselect All
                            </button>
                        </div>
                        <div class="hac-branch-box">
                            @forelse($branches as $branch)
                                @php
                                    $isChecked = is_array(old('branch_ids')) 
                                        ? in_array($branch->id, old('branch_ids')) 
                                        : in_array($branch->id, $savedBranchIds);
                                @endphp
                                <label class="hac-branch-item">
                                    <input type="checkbox" name="branch_ids[]" class="branch-checkbox" value="{{ $branch->id }}" data-branch-name="{{ $branch->name }}" @checked($isChecked)>
                                    <span>{{ $branch->name }}</span>
                                    @if($branch->city)<span style="font-size: 11px; color: #9ca3af; font-weight: 400;">({{ $branch->city }})</span>@endif
                                </label>
                            @empty
                                <div style="font-size: 13px; color: #9ca3af; padding: 10px;">No branches available.</div>
                            @endforelse
                        </div>
                    </div>
                    @error('branch_ids')<div class="hac-error">{{ $message }}</div>@enderror
                </div>

                <div class="hac-field">
                    <label class="hac-label">Status <span class="req">*</span></label>
                    <select id="annStatus" name="is_active" class="hac-select">
                        <option value="1" @selected(old('is_active', $announcement->is_active ? '1' : '0') === '1')>Active (Visible)</option>
                        <option value="0" @selected(old('is_active', $announcement->is_active ? '1' : '0') === '0')>Inactive (Hidden)</option>
                    </select>
                    @error('is_active')<div class="hac-error">{{ $message }}</div>@enderror
                </div>

                <div class="hac-field full">
                    <label class="hac-label">Message Content <span class="req">*</span></label>
                    <textarea id="annMessage" name="message" class="hac-textarea" required>{{ old('message', $announcement->message) }}</textarea>
                    @error('message')<div class="hac-error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; flex-wrap: wrap;">
                <a href="{{ route('hrms-announcements.index') }}" class="hac-btn hac-btn-ghost">Cancel</a>
                <button type="button" class="hac-btn hac-btn-primary" onclick="showUpdateConfirmation()">Update Announcement</button>
            </div>
        </form>
    </div>
</div>

{{-- CONFIRMATION MODAL --}}
<div id="confirmModal" class="hac-modal-overlay" style="display: none;">
    <div class="hac-modal">
        <div class="hac-modal-head">
            <h3>📢 Confirm Update</h3>
            <button type="button" onclick="closeConfirmModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #9ca3af;">✕</button>
        </div>
        <div class="hac-modal-body">
            <p style="margin: 0;">Are you sure you want to save changes to this announcement?</p>
            <div class="hac-summary-box">
                <div class="hac-summary-row">
                    <span class="hac-summary-label">Title:</span>
                    <span class="hac-summary-val" id="sumTitle">—</span>
                </div>
                <div class="hac-summary-row">
                    <span class="hac-summary-label">Date:</span>
                    <span class="hac-summary-val" id="sumDate">—</span>
                </div>
                <div class="hac-summary-row">
                    <span class="hac-summary-label">Priority:</span>
                    <span class="hac-summary-val" id="sumPriority">—</span>
                </div>
                <div class="hac-summary-row">
                    <span class="hac-summary-label">Target:</span>
                    <span class="hac-summary-val" id="sumTarget">—</span>
                </div>
                <div class="hac-summary-row">
                    <span class="hac-summary-label">Status:</span>
                    <span class="hac-summary-val" id="sumStatus">—</span>
                </div>
            </div>
        </div>
        <div class="hac-modal-footer">
            <button type="button" class="hac-btn hac-btn-ghost" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="hac-btn hac-btn-primary" onclick="submitForm()">Yes, Update Announcement</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleBranchSelection() {
    const isSpecific = document.querySelector('input[name="target_type"]:checked')?.value === 'specific';
    const box = document.getElementById('branchSelectionBox');
    box.style.display = isSpecific ? 'block' : 'none';
}

function toggleSelectAllBranches() {
    const checkboxes = document.querySelectorAll('.branch-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}

function showUpdateConfirmation() {
    const title = document.getElementById('annTitle').value.trim();
    const message = document.getElementById('annMessage').value.trim();
    const date = document.getElementById('annDate').value;
    const prioritySelect = document.getElementById('annPriority');
    const priorityText = prioritySelect.options[prioritySelect.selectedIndex]?.text || '';
    const statusSelect = document.getElementById('annStatus');
    const statusText = statusSelect.options[statusSelect.selectedIndex]?.text || '';

    if (!title) {
        alert('Please enter a title for the announcement.');
        document.getElementById('annTitle').focus();
        return;
    }
    if (!message) {
        alert('Please enter the announcement message content.');
        document.getElementById('annMessage').focus();
        return;
    }

    const targetType = document.querySelector('input[name="target_type"]:checked')?.value;
    let targetLabel = 'All Branches';

    if (targetType === 'specific') {
        const selected = Array.from(document.querySelectorAll('.branch-checkbox:checked'))
            .map(cb => cb.getAttribute('data-branch-name'));
        if (selected.length === 0) {
            alert('Please select at least one branch or choose "All Branches".');
            return;
        }
        targetLabel = selected.join(', ');
    }

    document.getElementById('sumTitle').textContent = title;
    document.getElementById('sumDate').textContent = date;
    document.getElementById('sumPriority').textContent = priorityText;
    document.getElementById('sumTarget').textContent = targetLabel;
    document.getElementById('sumStatus').textContent = statusText;

    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function submitForm() {
    document.getElementById('announcementForm').submit();
}
</script>
@endpush
