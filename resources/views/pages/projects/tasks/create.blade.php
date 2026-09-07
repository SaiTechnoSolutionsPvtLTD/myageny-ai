@extends('layouts.app')

@section('title', 'Task Creation')

@push('styles')
<style>
.pts-page { min-height:100%; background:linear-gradient(180deg,#f7fbff 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pts-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e6edf5; }
.pts-title { font-size:24px; font-weight:900; color:#111827; }
.pts-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; }
.pts-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pts-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 16px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; transition:all 0.2s; }
.pts-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.pts-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.pts-btn-outline { border-color:#ea580c; color:#ea580c; background:#fff; }
.pts-btn-outline:hover { background:#fff7ed; }
.pts-btn-danger { background:#ef4444; border-color:#ef4444; color:#fff; }
.pts-btn-danger:hover { background:#dc2626; border-color:#dc2626; color:#fff; }
.pts-body { padding:24px 28px 40px; display:grid; gap:20px; max-width:1400px; margin:0 auto; }
.pts-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.04); }
.pts-card-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:18px 24px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-card-title { font-size:16px; font-weight:900; color:#111827; display:flex; align-items:center; gap:8px; }
.pts-card-sub { font-size:12px; color:#64748b; margin-top:3px; }
.pts-card-body { padding:24px; }
.pts-flash { padding:14px 18px; border-radius:10px; font-size:13px; font-weight:700; margin-bottom:16px; }
.pts-flash.success { background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; }
.pts-flash.error { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
.pts-label { display:block; margin-bottom:8px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#475569; }
.pts-label .req { color:#ef4444; margin-left:2px; font-size:13px; }
.pts-input, .pts-select, .pts-textarea { width:100%; border:1px solid #dbe2ea; border-radius:10px; background:#fff; font-size:14px; color:#111827; transition:border-color 0.2s, box-shadow 0.2s; }
.pts-input, .pts-select { min-height:44px; padding:10px 14px; }
.pts-textarea { min-height:90px; padding:12px 14px; resize:vertical; line-height:1.55; }
.pts-input:focus, .pts-select:focus, .pts-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.pts-help { margin-top:6px; font-size:12px; color:#64748b; }
.pts-error { margin-top:6px; font-size:12px; color:#b91c1c; font-weight:700; }

.task-item-row { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:16px; position:relative; transition:all 0.2s; }
.task-item-row:hover { border-color:#cbd5e1; box-shadow:0 6px 18px rgba(15,23,42,.03); }
.task-row-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #e2e8f0; }
.task-row-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; background:#fff7ed; color:#ea580c; border:1px solid #ffedd5; border-radius:20px; font-size:12px; font-weight:800; }
.task-remove-btn { width:36px; height:36px; border-radius:8px; border:1px solid #fecaca; background:#fff; color:#ef4444; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; }
.task-remove-btn:hover:not(:disabled) { background:#ef4444; color:#fff; }
.task-remove-btn:disabled { opacity:0.4; cursor:not-allowed; }

.select2-container--default .select2-selection--single.pts-select2-selection { height:44px; border:1px solid #dbe2ea; border-radius:10px; background:#fff; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__rendered { line-height:42px; padding-left:14px; padding-right:34px; font-size:14px; color:#111827; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__arrow { height:42px; right:10px; }
.select2-container--default.select2-container--focus .select2-selection--single.pts-select2-selection,
.select2-container--default.select2-container--open .select2-selection--single.pts-select2-selection { border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.select2-dropdown { border:1px solid #dbe2ea; border-radius:10px; overflow:hidden; box-shadow:0 16px 36px rgba(15,23,42,.12); z-index:9999; }
.select2-search--dropdown { padding:10px; }
.select2-search--dropdown .select2-search__field { border:1px solid #dbe2ea; border-radius:8px; padding:8px 10px; font-size:13px; }
.select2-results__option { font-size:13px; padding:10px 12px; }
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background:#ea580c; color:#fff; }

.pts-form-grid { display:grid; grid-template-columns: repeat(12, 1fr); gap:18px; align-items:start; }
.pts-grid-col-12 { grid-column: span 12; }
.pts-grid-col-6 { grid-column: span 6; }
.pts-grid-col-4 { grid-column: span 4; }

@media (max-width: 768px) {
    .pts-topbar { padding:18px 16px; flex-direction:column; }
    .pts-body { padding:16px 12px 30px; }
    .pts-card-head { flex-direction:column; align-items:flex-start; }
    .pts-form-grid { grid-template-columns: 1fr; gap:14px; }
    .pts-grid-col-12, .pts-grid-col-6, .pts-grid-col-4 { grid-column: span 1 !important; }
}
</style>
@endpush

@section('content')
<div class="pts-page">
    <div class="pts-topbar">
        <div>
            <div class="pts-title">Task Creation</div>
            <div class="pts-breadcrumb">Projects &gt; Tasks &gt; Create Task</div>
        </div>
        <div class="pts-actions">
            <a href="{{ route('projects.tasks.index') }}" class="pts-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Tasks</span>
            </a>
        </div>
    </div>

    <div class="pts-body">
        @if(session('success'))
            <div class="pts-flash success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="pts-flash error">
                <div style="margin-bottom:6px; font-weight:800;">Please check the errors below:</div>
                <ul style="margin:0; padding-left:20px; font-weight:600;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('projects.tasks.store') }}" id="taskCreationForm">
            @csrf

            <!-- Assignment Info Section -->
            <div class="pts-card" style="margin-bottom:20px;">
                <div class="pts-card-head">
                    <div>
                        <div class="pts-card-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            General Task Info
                        </div>
                        <div class="pts-card-sub">Select task date and the team member to assign.</div>
                    </div>
                </div>
                <div class="pts-card-body">
                    <div class="pts-form-grid">
                        <!-- Date (Column 1) -->
                        <div class="pts-grid-col-6">
                            <label class="pts-label">Date <span class="req">*</span></label>
                            <input type="date" name="task_date" id="taskDateInput" value="{{ old('task_date', $today) }}" class="pts-input" required>
                            @error('task_date')
                                <div class="pts-error">{{ $message }}</div>
                            @enderror
                            <div class="pts-help">Select the date on which this task should be executed.</div>
                        </div>

                        <!-- Mapped Team Member (Column 2) -->
                        <div class="pts-grid-col-6">
                            <label class="pts-label">Mapped Team Member <span class="req">*</span></label>
                            <select name="assigned_to_user_id" id="assignedUserSelect" class="pts-select select2" data-placeholder="Select Team Member" required>
                                <option value="">Select Team Member</option>
                                @foreach($mappedTeamMembers as $member)
                                    <option value="{{ $member->id }}" @selected((string) old('assigned_to_user_id') === (string) $member->id)>
                                        {{ $member->name }} ({{ $member->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to_user_id')
                                <div class="pts-error">{{ $message }}</div>
                            @enderror
                            <div class="pts-help">Select the team member assigned to execute these tasks.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Repeater Section -->
            <div class="pts-card">
                <div class="pts-card-head">
                    <div>
                        <div class="pts-card-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            Task Details (Products &amp; Descriptions)
                        </div>
                        <div class="pts-card-sub">Select Lead &amp; Product on a single row, write task description, and use the plus (+) button to add more.</div>
                    </div>
                    <button type="button" class="pts-btn pts-btn-primary" id="addRowBtn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Add Product / Project</span>
                    </button>
                </div>
                <div class="pts-card-body">
                    <div id="tasksContainer">
                        @php
                            $oldTasks = old('tasks', [
                                ['lead_id' => '', 'production_initiation_id' => '', 'task_description' => '']
                            ]);
                        @endphp

                        @foreach($oldTasks as $index => $row)
                            <div class="task-item-row" data-row-index="{{ $index }}">
                                <div class="task-row-header">
                                    <div class="task-row-badge">
                                        <i class="bi bi-layers-fill"></i>
                                        <span>Task Item #<span class="row-num">{{ $index + 1 }}</span></span>
                                    </div>
                                    <button type="button" class="task-remove-btn" title="Remove this task row" {{ count($oldTasks) === 1 ? 'disabled' : '' }}>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>

                                <div class="pts-form-grid">
                                    <!-- Lead Dropdown (Single row left) -->
                                    <div class="pts-grid-col-6">
                                        <label class="pts-label">Lead (Client) <span class="req">*</span></label>
                                        <select name="tasks[{{ $index }}][lead_id]" class="pts-select task-lead-select select2" data-placeholder="Select Lead" required>
                                            <option value="">Select Lead</option>
                                            @foreach($uniqueLeads as $lead)
                                                <option value="{{ $lead['lead_id'] }}" @selected((string) ($row['lead_id'] ?? '') === (string) $lead['lead_id'])>
                                                    LD-{{ str_pad($lead['lead_id'], 4, '0', STR_PAD_LEFT) }} | {{ $lead['company_name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("tasks.{$index}.lead_id")
                                            <div class="pts-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Product / Project Dropdown (Single row right) -->
                                    <div class="pts-grid-col-6">
                                        <label class="pts-label">Product / Project <span class="req">*</span></label>
                                        <select name="tasks[{{ $index }}][production_initiation_id]" class="pts-select task-project-select select2" data-placeholder="Select product / project" data-selected="{{ $row['production_initiation_id'] ?? '' }}" required disabled>
                                            <option value="">Select product / project</option>
                                        </select>
                                        @error("tasks.{$index}.production_initiation_id")
                                            <div class="pts-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Task Description (Full width below) -->
                                    <div class="pts-grid-col-12">
                                        <label class="pts-label">Task Description <span class="req">*</span></label>
                                        <textarea name="tasks[{{ $index }}][task_description]" class="pts-textarea" placeholder="Enter detailed task instructions, deliverables, requirements..." required>{{ $row['task_description'] ?? '' }}</textarea>
                                        @error("tasks.{$index}.task_description")
                                            <div class="pts-error">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:24px; padding-top:20px; border-top:1px solid #edf2f7; flex-wrap:wrap;">
                        <button type="button" class="pts-btn pts-btn-outline" id="addRowBtnBottom">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Add Another Product / Project</span>
                        </button>

                        <div style="display:flex; align-items:center; gap:12px;">
                            <a href="{{ route('projects.tasks.index') }}" class="pts-btn">Cancel</a>
                            <button type="submit" class="pts-btn pts-btn-primary" style="padding:10px 24px; font-size:14px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                <span>Save &amp; Assign Tasks</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const assignedProjects = @json($assignedProjects);
    const uniqueLeads = @json($uniqueLeads);
    const container = document.getElementById('tasksContainer');
    const addRowBtn = document.getElementById('addRowBtn');
    const addRowBtnBottom = document.getElementById('addRowBtnBottom');

    function initSelect2ForElement($elem, placeholder) {
        if (window.jQuery && window.jQuery.fn.select2) {
            if ($elem.hasClass('select2-hidden-accessible')) {
                $elem.select2('destroy');
            }
            $elem.select2({
                width: '100%',
                placeholder: placeholder || 'Select an option',
                allowClear: false
            });
            $elem.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        }
    }

    // Init assigned user select2
    const $assignedUser = window.jQuery ? window.jQuery('#assignedUserSelect') : null;
    if ($assignedUser && $assignedUser.length) {
        initSelect2ForElement($assignedUser, 'Select Team Member');
    }

    function populateProjectsForLead(leadId, projectSelect, selectedProjId) {
        projectSelect.innerHTML = '<option value="">Select product / project</option>';
        if (!leadId) {
            projectSelect.disabled = true;
            if (window.jQuery && window.jQuery.fn.select2) {
                const $proj = window.jQuery(projectSelect);
                if ($proj.hasClass('select2-hidden-accessible')) {
                    $proj.select2('destroy');
                }
                projectSelect.disabled = true;
                $proj.select2({
                    width: '100%',
                    placeholder: 'Select product / project',
                    allowClear: false
                });
                $proj.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
            }
            return;
        }

        const filtered = assignedProjects.filter(p => String(p.lead_id) === String(leadId));
        filtered.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = (p.product_name || 'Product') + (p.timesheet_delivery_date ? ' (Delivery: ' + p.timesheet_delivery_date + ')' : '');
            if (selectedProjId && String(p.id) === String(selectedProjId)) {
                opt.selected = true;
            }
            projectSelect.appendChild(opt);
        });

        projectSelect.disabled = false;
        if (window.jQuery && window.jQuery.fn.select2) {
            const $proj = window.jQuery(projectSelect);
            if ($proj.hasClass('select2-hidden-accessible')) {
                $proj.select2('destroy');
            }
            projectSelect.disabled = false;
            $proj.select2({
                width: '100%',
                placeholder: 'Select product / project',
                allowClear: false
            });
            $proj.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
            if (selectedProjId) {
                $proj.val(selectedProjId).trigger('change');
            }
        }
    }

    function setupRowEvents(row) {
        const leadSelect = row.querySelector('.task-lead-select');
        const projectSelect = row.querySelector('.task-project-select');
        const removeBtn = row.querySelector('.task-remove-btn');

        if (window.jQuery) {
            const $lead = window.jQuery(leadSelect);
            const $proj = window.jQuery(projectSelect);
            initSelect2ForElement($lead, 'Select Lead');
            initSelect2ForElement($proj, 'Select product / project');

            $lead.off('change select2:select select2:clear').on('change select2:select select2:clear', function () {
                populateProjectsForLead(this.value, projectSelect, null);
            });
        } else {
            leadSelect.addEventListener('change', function () {
                populateProjectsForLead(this.value, projectSelect, null);
            });
        }

        // Check if initial lead is selected (e.g. from old input)
        const initialLeadId = leadSelect.value;
        const initialSelectedProjId = projectSelect.getAttribute('data-selected');
        if (initialLeadId) {
            populateProjectsForLead(initialLeadId, projectSelect, initialSelectedProjId);
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const totalRows = container.querySelectorAll('.task-item-row').length;
                if (totalRows > 1) {
                    row.remove();
                    updateRowNumbersAndIndices();
                }
            });
        }
    }

    function updateRowNumbersAndIndices() {
        const rows = container.querySelectorAll('.task-item-row');
        rows.forEach((row, idx) => {
            row.setAttribute('data-row-index', idx);
            const numElem = row.querySelector('.row-num');
            if (numElem) numElem.textContent = idx + 1;

            const leadSelect = row.querySelector('.task-lead-select');
            const projectSelect = row.querySelector('.task-project-select');
            const textarea = row.querySelector('.pts-textarea');
            const removeBtn = row.querySelector('.task-remove-btn');

            if (leadSelect) leadSelect.name = `tasks[${idx}][lead_id]`;
            if (projectSelect) projectSelect.name = `tasks[${idx}][production_initiation_id]`;
            if (textarea) textarea.name = `tasks[${idx}][task_description]`;

            if (removeBtn) {
                removeBtn.disabled = (rows.length === 1);
            }
        });
    }

    function addNewTaskRow() {
        const currentRows = container.querySelectorAll('.task-item-row');
        const newIndex = currentRows.length;

        let leadsOptionsHtml = '<option value="">Select Lead</option>';
        uniqueLeads.forEach(lead => {
            const leadNum = String(lead.lead_id).padStart(4, '0');
            leadsOptionsHtml += `<option value="${lead.lead_id}">LD-${leadNum} | ${lead.company_name}</option>`;
        });

        const newRowHtml = `
            <div class="task-item-row" data-row-index="${newIndex}">
                <div class="task-row-header">
                    <div class="task-row-badge">
                        <i class="bi bi-layers-fill"></i>
                        <span>Task Item #<span class="row-num">${newIndex + 1}</span></span>
                    </div>
                    <button type="button" class="task-remove-btn" title="Remove this task row">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
                <div class="pts-form-grid">
                    <div class="pts-grid-col-6">
                        <label class="pts-label">Lead (Client) <span class="req">*</span></label>
                        <select name="tasks[${newIndex}][lead_id]" class="pts-select task-lead-select select2" data-placeholder="Select Lead" required>
                            ${leadsOptionsHtml}
                        </select>
                    </div>
                    <div class="pts-grid-col-6">
                        <label class="pts-label">Product / Project <span class="req">*</span></label>
                        <select name="tasks[${newIndex}][production_initiation_id]" class="pts-select task-project-select select2" data-placeholder="Select product / project" required disabled>
                            <option value="">Select product / project</option>
                        </select>
                    </div>
                    <div class="pts-grid-col-12">
                        <label class="pts-label">Task Description <span class="req">*</span></label>
                        <textarea name="tasks[${newIndex}][task_description]" class="pts-textarea" placeholder="Enter detailed task instructions, deliverables, requirements..." required></textarea>
                    </div>
                </div>
            </div>
        `;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newRowHtml.trim();
        const newRowElem = tempDiv.firstElementChild;
        container.appendChild(newRowElem);

        setupRowEvents(newRowElem);
        updateRowNumbersAndIndices();
    }

    // Init existing rows
    container.querySelectorAll('.task-item-row').forEach(row => {
        setupRowEvents(row);
    });

    if (addRowBtn) addRowBtn.addEventListener('click', addNewTaskRow);
    if (addRowBtnBottom) addRowBtnBottom.addEventListener('click', addNewTaskRow);
});
</script>
@endpush
