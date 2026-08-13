@extends('layouts.app')

@section('title', 'Leave Hierarchy - Settings')

@push('styles')
<style>
.lh-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.lh-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
}
.lh-kicker {
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
.lh-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.lh-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.6;
    color: #6b7280;
}
.lh-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    border: none;
    transition: all .2s ease;
    text-decoration: none;
    font-family: inherit;
}
.lh-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    box-shadow: 0 6px 20px rgba(254, 95, 4, 0.28);
}
.lh-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(254, 95, 4, 0.38);
    color: #fff;
}
.lh-btn-ghost {
    background: #fff;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.lh-btn-ghost:hover {
    border-color: #fe5f04;
    color: #fe5f04;
}

/* Card & Table */
.lh-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}
.lh-card-head {
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
}
.lh-card-title { font-size: 16px; font-weight: 800; color: #111827; }
.lh-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.lh-table th {
    background: #f9fafb;
    padding: 14px 20px;
    text-align: left;
    font-weight: 800;
    color: #4b5563;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .6px;
    border-bottom: 1px solid #e5e7eb;
}
.lh-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
    vertical-align: middle;
}
.lh-table tr:hover { background: #fffdfb; }

/* Chain Flow View */
.lh-flow-chain {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.lh-role-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.lh-role-pill.applicant {
    background: #fff7ed;
    color: #c2410c;
    border-color: #ffedd5;
}
.lh-role-pill.approver {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #dbeafe;
}
.lh-arrow {
    color: #fe5f04;
    font-weight: 800;
    font-size: 14px;
}

/* Status Badge */
.lh-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}
.badge-active { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.badge-inactive { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Modal */
.lh-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}
.lh-modal {
    background: #ffffff;
    border-radius: 20px;
    width: 90%;
    max-width: 640px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    animation: popIn .2s ease;
    max-height: 90vh;
    overflow: hidden;
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.lh-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}
.lh-modal-title { font-size: 18px; font-weight: 800; color: #111827; }
.lh-modal-close {
    background: none; border: none; font-size: 22px; color: #9ca3af; cursor: pointer;
}
.lh-modal-body {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.lh-form-group { display: flex; flex-direction: column; gap: 6px; }
.lh-form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #4b5563; }
.lh-input, .lh-select {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
}
.lh-input:focus, .lh-select:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}

/* Steps Builder */
.lh-step-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 8px;
}
.lh-step-num {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #fe5f04;
    color: #fff;
    font-weight: 800;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lh-step-btn {
    background: #fff;
    border: 1px solid #d1d5db;
    color: #4b5563;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
}
.lh-step-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

.lh-modal-foot {
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Select2 Custom Styling */
.select2-container--default .select2-selection--single {
    height: 42px;
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background-color: #ffffff;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #111827;
    font-size: 14px;
    padding-left: 0;
    line-height: normal;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
    right: 10px;
}
.select2-dropdown {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    z-index: 99999;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 13px;
    outline: none;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: #fe5f04;
}
.select2-results__option {
    font-size: 13px;
    padding: 8px 12px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #fe5f04;
    color: #fff;
}
.select2-container--open {
    z-index: 99999 !important;
}
</style>
@endpush

@section('content')
<div class="lh-page">

    {{-- Hero --}}
    <div class="lh-hero">
        <div>
            <div class="lh-kicker">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/></svg>
                Leave Workflow Management
            </div>
            <h2 class="lh-title">Leave Hierarchy Settings</h2>
            <p class="lh-subtitle">Define multi-stage role approval flows for leave requests (e.g. Sales Executive ➔ Sales TL ➔ Sales Manager ➔ COO ➔ HR).</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="{{ route('settings.index') }}" class="lh-btn lh-btn-ghost">Back to Settings</a>
            <button class="lh-btn lh-btn-primary" onclick="openCreateModal()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create Leave Hierarchy
            </button>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
    <div style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 10px;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Main Card --}}
    <div class="lh-card">
        <div class="lh-card-head">
            <div class="lh-card-title">Configured Leave Approval Hierarchies</div>
            <div style="font-size: 12px; font-weight: 700; color: #6b7280;">
                Total Hierarchies: {{ $hierarchies->count() }}
            </div>
        </div>

        <table class="lh-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 200px;">Applicant Role</th>
                    <th>Leave Approval Hierarchy Chain</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hierarchies as $index => $hierarchy)
                @php
                    $roleMap = $roles->keyBy('id');
                    $chainRoleIds = $hierarchy->approval_chain ?? [];
                @endphp
                <tr>
                    <td style="color: #9ca3af; font-weight: 600;">{{ $index + 1 }}</td>
                    <td>
                        <strong style="color: #111827; font-size: 14px;">
                            {{ $hierarchy->role?->display_name ?? ucfirst(str_replace('_',' ', $hierarchy->role?->name ?? 'Role')) }}
                        </strong>
                    </td>
                    <td>
                        <div class="lh-flow-chain">
                            <span class="lh-role-pill applicant">
                                👤 {{ $hierarchy->role?->display_name ?? $hierarchy->role?->name }}
                            </span>

                            @foreach($chainRoleIds as $stepIdx => $roleId)
                                <span class="lh-arrow">➔</span>
                                @php
                                    $stepRole = $roleMap->get($roleId);
                                    $stepName = $stepRole?->display_name ?? ucfirst(str_replace('_',' ', $stepRole?->name ?? "Role #{$roleId}"));
                                @endphp
                                <span class="lh-role-pill approver">
                                    <span style="font-size: 10px; opacity: .7;">Step {{ $stepIdx + 1 }}:</span> {{ $stepName }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('settings.leave-hierarchy.toggle-status', $hierarchy) }}">
                            @csrf @method('PATCH')
                            <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer;">
                                @if($hierarchy->is_active)
                                    <span class="lh-badge badge-active">✓ Active</span>
                                @else
                                    <span class="lh-badge badge-inactive">✕ Inactive</span>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 8px;">
                            <button type="button" class="lh-btn lh-btn-ghost" style="padding: 5px 12px; font-size: 12px;"
                                data-hierarchy="{{ json_encode($hierarchy) }}" onclick="openEditModalFromData(this)">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('settings.leave-hierarchy.destroy', $hierarchy) }}" onsubmit="return confirm('Delete this leave approval hierarchy?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="lh-btn lh-btn-ghost" style="padding: 5px 12px; font-size: 12px; color: #dc2626; border-color: #fecaca;">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 48px 20px; color: #6b7280;">
                        <div style="font-size: 32px; margin-bottom: 8px;">🌴</div>
                        <div style="font-weight: 700; color: #374151; font-size: 15px;">No Leave Hierarchies Configured</div>
                        <div style="font-size: 13px; margin-top: 4px;">Click "Create Leave Hierarchy" to map role approval steps for leave requests.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- Create / Edit Modal --}}
<div class="lh-modal-overlay" id="hierarchyModal">
    <div class="lh-modal">
        <div class="lh-modal-head">
            <div class="lh-modal-title" id="modalTitle">Create Leave Approval Hierarchy</div>
            <button class="lh-modal-close" onclick="closeHierarchyModal()">✕</button>
        </div>

        <form id="hierarchyForm" method="POST" action="{{ route('settings.leave-hierarchy.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="lh-modal-body">
                
                {{-- Role Selection --}}
                <div class="lh-form-group">
                    <label class="lh-form-label">Target Role (Applicant) <span style="color:#dc2626;">*</span></label>
                    <select name="role_id" id="role_id" class="lh-select select2" required style="width:100%;" onchange="renderPreview()">
                        <option value="">-- Select Role --</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-name="{{ $role->display_name ?? ucfirst(str_replace('_',' ', $role->name)) }}">
                            {{ $role->display_name ?? ucfirst(str_replace('_',' ', $role->name)) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Approval Chain Builder --}}
                <div class="lh-form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label class="lh-form-label" style="margin:0;">Approval Chain Steps (Order of Approval) <span style="color:#dc2626;">*</span></label>
                        <button type="button" onclick="addStep()" class="lh-btn lh-btn-primary" style="padding: 4px 10px; font-size: 11px; border-radius: 8px;">
                            + Add Approval Step
                        </button>
                    </div>

                    <div id="stepsContainer">
                        <!-- Dynamic Steps Rendered via JS -->
                    </div>
                </div>

                {{-- Live Chain Preview --}}
                <div class="lh-form-group">
                    <label class="lh-form-label">Live Visual Approval Flow Preview</label>
                    <div id="chainPreview" style="padding: 14px; background: #fff9f3; border: 1px solid #ffedd5; border-radius: 12px;">
                        <span style="font-size: 12px; color: #9ca3af;">Select a role and add steps to preview the approval chain...</span>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="lh-form-group">
                    <label class="lh-form-label">Notes / Description (Optional)</label>
                    <input type="text" name="notes" id="notes" class="lh-input" placeholder="e.g., Leave requests submitted by Sales Executive require Sales TL ➔ Sales Manager ➔ COO approval.">
                </div>

            </div>

            <div class="lh-modal-foot">
                <button type="button" class="lh-btn lh-btn-ghost" onclick="closeHierarchyModal()">Cancel</button>
                <button type="submit" class="lh-btn lh-btn-primary">Save Leave Hierarchy</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const availableRoles = @json($formattedRoles);
let currentSteps = [];

$(document).ready(function() {
    if (window.jQuery && window.jQuery.fn.select2) {
        $('#role_id').select2({
            placeholder: '-- Select Role --',
            allowClear: true,
            dropdownParent: $('#hierarchyModal'),
            width: '100%'
        });

        $('#role_id').on('change', function() {
            renderPreview();
        });
    }
});

function openEditModalFromData(btn) {
    const hierarchy = JSON.parse(btn.getAttribute('data-hierarchy'));
    openEditModal(hierarchy);
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Leave Approval Hierarchy';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('hierarchyForm').action = "{{ route('settings.leave-hierarchy.store') }}";
    document.getElementById('role_id').value = '';
    if (window.jQuery && window.jQuery.fn.select2) {
        $('#role_id').val('').trigger('change.select2');
    }
    document.getElementById('notes').value = '';
    currentSteps = [availableRoles[0]?.id || ''];
    renderSteps();
    renderPreview();
    document.getElementById('hierarchyModal').style.display = 'flex';
}

function openEditModal(hierarchy) {
    document.getElementById('modalTitle').textContent = 'Edit Leave Approval Hierarchy';
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('hierarchyForm').action = `/settings/leave-hierarchy/${hierarchy.id}`;
    document.getElementById('role_id').value = hierarchy.role_id;
    if (window.jQuery && window.jQuery.fn.select2) {
        $('#role_id').val(hierarchy.role_id).trigger('change.select2');
    }
    document.getElementById('notes').value = hierarchy.notes || '';
    currentSteps = Array.isArray(hierarchy.approval_chain) ? [...hierarchy.approval_chain] : [];
    if (currentSteps.length === 0) currentSteps.push(availableRoles[0]?.id || '');
    renderSteps();
    renderPreview();
    document.getElementById('hierarchyModal').style.display = 'flex';
}

function closeHierarchyModal() {
    document.getElementById('hierarchyModal').style.display = 'none';
}

function addStep() {
    currentSteps.push(availableRoles[0]?.id || '');
    renderSteps();
    renderPreview();
}

function removeStep(idx) {
    if (currentSteps.length <= 1) {
        alert('Hierarchy must have at least 1 approval step.');
        return;
    }
    currentSteps.splice(idx, 1);
    renderSteps();
    renderPreview();
}

function updateStepValue(idx, val) {
    currentSteps[idx] = parseInt(val, 10);
    renderPreview();
}

function moveStepUp(idx) {
    if (idx === 0) return;
    const temp = currentSteps[idx];
    currentSteps[idx] = currentSteps[idx - 1];
    currentSteps[idx - 1] = temp;
    renderSteps();
    renderPreview();
}

function moveStepDown(idx) {
    if (idx === currentSteps.length - 1) return;
    const temp = currentSteps[idx];
    currentSteps[idx] = currentSteps[idx + 1];
    currentSteps[idx + 1] = temp;
    renderSteps();
    renderPreview();
}

function renderSteps() {
    const container = document.getElementById('stepsContainer');
    container.innerHTML = '';

    currentSteps.forEach((roleId, idx) => {
        const stepDiv = document.createElement('div');
        stepDiv.className = 'lh-step-item';

        let optionsHtml = '';
        availableRoles.forEach(r => {
            const selected = (parseInt(r.id, 10) === parseInt(roleId, 10)) ? 'selected' : '';
            optionsHtml += `<option value="${r.id}" ${selected}>${r.name}</option>`;
        });

        stepDiv.innerHTML = `
            <div class="lh-step-num">${idx + 1}</div>
            <select name="approval_chain[]" class="lh-select" style="flex:1;" required onchange="updateStepValue(${idx}, this.value)">
                ${optionsHtml}
            </select>
            <button type="button" class="lh-step-btn" onclick="moveStepUp(${idx})" ${idx === 0 ? 'disabled style="opacity:.4;"' : ''}>▲</button>
            <button type="button" class="lh-step-btn" onclick="moveStepDown(${idx})" ${idx === currentSteps.length - 1 ? 'disabled style="opacity:.4;"' : ''}>▼</button>
            <button type="button" class="lh-step-btn" style="color:#dc2626;" onclick="removeStep(${idx})">✕</button>
        `;
        container.appendChild(stepDiv);
    });
}

function renderPreview() {
    const preview = document.getElementById('chainPreview');
    const roleSelect = document.getElementById('role_id');
    const selectedOption = roleSelect.options[roleSelect.selectedIndex];
    const applicantName = selectedOption && selectedOption.value ? selectedOption.getAttribute('data-name') : null;

    if (!applicantName) {
        preview.innerHTML = '<span style="font-size: 12px; color: #9ca3af;">Select a target role to preview the leave approval chain...</span>';
        return;
    }

    let html = `<div class="lh-flow-chain"><span class="lh-role-pill applicant">👤 ${applicantName}</span>`;

    const roleMap = {};
    availableRoles.forEach(r => roleMap[r.id] = r.name);

    currentSteps.forEach((rId, idx) => {
        const name = roleMap[rId] || `Role #${rId}`;
        html += `<span class="lh-arrow">➔</span><span class="lh-role-pill approver"><span style="font-size: 10px; opacity: .7;">Step ${idx + 1}:</span> ${name}</span>`;
    });

    html += `</div>`;
    preview.innerHTML = html;
}

document.getElementById('hierarchyModal').addEventListener('click', function(e) {
    if (e.target === this) closeHierarchyModal();
});
</script>
@endpush
