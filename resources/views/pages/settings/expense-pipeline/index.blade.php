@extends('layouts.app')

@section('title', 'Expense Approval Pipeline Settings - myAgenci.ai')

@push('styles')
<style>
.exp-pipe-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.exp-pipe-hero {
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
.exp-pipe-kicker {
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
.exp-pipe-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.exp-pipe-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.6;
    color: #6b7280;
}
.exp-pipe-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    border: none;
    transition: all .2s ease;
    text-decoration: none;
    font-family: inherit;
}
.exp-pipe-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    box-shadow: 0 6px 20px rgba(254, 95, 4, 0.28);
}
.exp-pipe-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(254, 95, 4, 0.38);
    color: #fff;
}
.exp-pipe-btn-outline {
    background: #fff;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.exp-pipe-btn-outline:hover {
    border-color: #fe5f04;
    color: #fe5f04;
}

/* Card list */
.exp-pipe-grid {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.exp-pipe-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    transition: transform .18s ease, box-shadow .18s ease;
}
.exp-pipe-card:hover {
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.07);
}
.exp-pipe-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}
.exp-pipe-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 10px;
    background: #fff7ed;
    color: #ea580c;
    border: 1px solid #ffedd5;
    font-size: 14px;
    font-weight: 800;
}
.exp-pipe-flow {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 16px 20px;
    background: #f9fafb;
    border-radius: 14px;
    border: 1px solid #f3f4f6;
}
.exp-pipe-step-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    font-size: 13px;
    font-weight: 700;
    color: #1f2937;
}
.exp-pipe-step-num {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fe5f04;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.exp-pipe-arrow {
    color: #9ca3af;
    font-size: 16px;
    font-weight: 800;
}

/* Modal */
.exp-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}
.exp-modal {
    background: #ffffff;
    border-radius: 20px;
    width: 90%;
    max-width: 680px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    animation: popIn .2s ease;
    overflow: hidden;
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.exp-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}
.exp-modal-title { font-size: 18px; font-weight: 800; color: #111827; }
.exp-modal-close {
    background: none; border: none; font-size: 22px; color: #9ca3af; cursor: pointer;
}
.exp-modal-body {
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.exp-form-group { display: flex; flex-direction: column; gap: 8px; }
.exp-form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #4b5563; }
.exp-select, .exp-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
    transition: all .15s ease;
}
.exp-select:focus, .exp-input:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.exp-step-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    margin-bottom: 10px;
}
.exp-step-badge {
    padding: 4px 10px;
    border-radius: 8px;
    background: #fe5f04;
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    flex-shrink: 0;
}
.exp-step-actions { display: flex; gap: 4px; }
.exp-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: all .15s ease;
}
.exp-icon-btn:hover { border-color: #fe5f04; color: #fe5f04; }
.exp-icon-btn.danger:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

.exp-preview-box {
    padding: 16px;
    border-radius: 14px;
    background: linear-gradient(135deg, #fff7ed, #fff1e8);
    border: 1px solid #fed7aa;
}
.exp-preview-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #c2410c; margin-bottom: 8px; }
.exp-preview-flow { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.exp-modal-foot {
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
</style>
@endpush

@section('content')
<div class="exp-pipe-page">

    {{-- Hero --}}
    <div class="exp-pipe-hero">
        <div>
            <div class="exp-pipe-kicker">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Approval Workflows
            </div>
            <h2 class="exp-pipe-title">Expense Approval Pipeline</h2>
            <p class="exp-pipe-subtitle">Configure multi-level dynamic role hierarchies for expense approvals. E.g. <code>Sales Executive ➔ Sales TL ➔ Sales Manager ➔ COO ➔ Company Admin ➔ Accounts</code>.</p>
        </div>
        <div>
            <button class="exp-pipe-btn exp-pipe-btn-primary" onclick="openCreateModal()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add New Pipeline
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div style="margin-bottom:20px; padding:14px 18px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Pipeline List --}}
    <div class="exp-pipe-grid">
        @forelse($pipelines as $pipeline)
        @php
            $roleMap = $roles->keyBy('id');
            $sourceRoleName = $pipeline->role?->display_name ?? ucfirst(str_replace('_',' ', $pipeline->role?->name ?? 'Role #'.$pipeline->role_id));
        @endphp
        <div class="exp-pipe-card">
            <div class="exp-pipe-card-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="exp-pipe-role-badge">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                        Target Role: {{ $sourceRoleName }}
                    </div>
                    <span style="font-size:12px; color:#6b7280; font-weight:600;">
                        {{ count($pipeline->approval_chain ?? []) }} Approval Stage(s)
                    </span>
                </div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <form method="POST" action="{{ route('settings.expense-pipeline.toggle-status', $pipeline) }}">
                        @csrf @method('PATCH')
                        <button type="submit" style="background:none; border:none; cursor:pointer;" title="Click to toggle status">
                            <span class="usr-badge {{ $pipeline->is_active ? 'ub-active' : 'ub-inactive' }}">
                                {{ $pipeline->is_active ? 'Active Pipeline' : 'Inactive' }}
                            </span>
                        </button>
                    </form>

                    <button class="exp-pipe-btn exp-pipe-btn-outline" style="padding:6px 14px; font-size:12px;"
                        onclick='openEditModal(@json($pipeline))'>
                        Edit
                    </button>

                    <form method="POST" action="{{ route('settings.expense-pipeline.destroy', $pipeline) }}" onsubmit="return confirm('Delete this expense approval pipeline?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="exp-pipe-btn exp-pipe-btn-outline" style="padding:6px 12px; font-size:12px; color:#dc2626; border-color:#fecaca;">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Flow Steps --}}
            <div class="exp-pipe-flow">
                <div class="exp-pipe-step-pill" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c;">
                    <span class="exp-pipe-step-num" style="background:#ea580c;">★</span>
                    {{ $sourceRoleName }} (Applicant)
                </div>

                @foreach($pipeline->approval_chain ?? [] as $idx => $stepRoleId)
                @php
                    $stepRole = $roleMap->get($stepRoleId);
                    $stepRoleName = $stepRole?->display_name ?? ucfirst(str_replace('_',' ', $stepRole?->name ?? 'Role #'.$stepRoleId));
                @endphp
                <span class="exp-pipe-arrow">➔</span>
                <div class="exp-pipe-step-pill">
                    <span class="exp-pipe-step-num">{{ $idx + 1 }}</span>
                    {{ $stepRoleName }}
                </div>
                @endforeach
            </div>

            @if($pipeline->notes)
            <div style="margin-top:14px; font-size:12px; color:#6b7280; font-style:italic;">
                Note: {{ $pipeline->notes }}
            </div>
            @endif
        </div>
        @empty
        <div style="text-align:center; padding:60px 20px; background:#fff; border-radius:18px; border:1px dashed #d1d5db;">
            <div style="font-size:44px; margin-bottom:12px;">🔄</div>
            <div style="font-size:16px; font-weight:800; color:#374151; margin-bottom:6px;">No Expense Pipelines Created Yet</div>
            <div style="font-size:13px; color:#6b7280; max-width:440px; margin:0 auto 20px;">
                Create dynamic approval hierarchies for roles like Sales Executive ➔ Sales TL ➔ Sales Manager ➔ COO ➔ Company Admin ➔ Accounts.
            </div>
            <button class="exp-pipe-btn exp-pipe-btn-primary" onclick="openCreateModal()">
                + Configure First Pipeline
            </button>
        </div>
        @endforelse
    </div>

</div>

{{-- Dynamic Pipeline Builder Modal --}}
<div class="exp-modal-overlay" id="pipelineModal">
    <div class="exp-modal">
        <div class="exp-modal-head">
            <div class="exp-modal-title" id="modalTitle">Configure Expense Approval Pipeline</div>
            <button class="exp-modal-close" onclick="closeModal()">✕</button>
        </div>

        <form id="pipelineForm" method="POST" action="{{ route('settings.expense-pipeline.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="exp-modal-body">

                {{-- Target Role --}}
                <div class="exp-form-group">
                    <label class="exp-form-label">Applicant Role (Source Role)</label>
                    <select name="role_id" id="roleSelect" class="exp-select" required onchange="renderPreview()">
                        <option value="">-- Select Source Role --</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}">
                            {{ $role->display_name ?? ucfirst(str_replace('_',' ',$role->name)) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Approval Chain Steps --}}
                <div class="exp-form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <label class="exp-form-label">Approval Role Hierarchy Steps</label>
                        <button type="button" class="exp-pipe-btn exp-pipe-btn-outline" style="padding:4px 12px; font-size:12px;" onclick="addStep()">
                            + Add Approval Step
                        </button>
                    </div>

                    <div id="stepsContainer" style="margin-top:8px;">
                        <!-- Dynamic steps rendered via JS -->
                    </div>
                </div>

                {{-- Live Preview Box --}}
                <div class="exp-preview-box">
                    <div class="exp-preview-title">Live Pipeline Flow Preview</div>
                    <div class="exp-preview-flow" id="previewFlow">
                        <span style="font-size:12px; color:#9a3412;">Select a role and add approval steps to view live pipeline workflow...</span>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="exp-form-group">
                    <label class="exp-form-label">Pipeline Notes / Description (Optional)</label>
                    <input type="text" name="notes" id="notesInput" class="exp-input" placeholder="E.g., Approval hierarchy for sales team expenses above ₹5,000">
                </div>

            </div>

            <div class="exp-modal-foot">
                <button type="button" class="exp-pipe-btn exp-pipe-btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="exp-pipe-btn exp-pipe-btn-primary">Save Approval Pipeline</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const availableRoles = @json($roles);
let steps = [];

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Configure Expense Approval Pipeline';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('pipelineForm').action = "{{ route('settings.expense-pipeline.store') }}";
    document.getElementById('roleSelect').value = '';
    document.getElementById('notesInput').value = '';
    steps = [];
    addStep(); // add first default step
    renderSteps();
    document.getElementById('pipelineModal').style.display = 'flex';
}

function openEditModal(pipeline) {
    document.getElementById('modalTitle').textContent = 'Edit Expense Approval Pipeline';
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('pipelineForm').action = `/settings/expense-pipeline/${pipeline.id}`;
    document.getElementById('roleSelect').value = pipeline.role_id;
    document.getElementById('notesInput').value = pipeline.notes || '';
    
    steps = Array.isArray(pipeline.approval_chain) ? [...pipeline.approval_chain] : [];
    if (!steps.length) steps = [''];
    
    renderSteps();
    document.getElementById('pipelineModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('pipelineModal').style.display = 'none';
}

document.getElementById('pipelineModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function addStep(roleId = '') {
    steps.push(roleId);
    renderSteps();
}

function removeStep(index) {
    if (steps.length <= 1) {
        alert('At least 1 approval step is required in the pipeline.');
        return;
    }
    steps.splice(index, 1);
    renderSteps();
}

function moveStepUp(index) {
    if (index === 0) return;
    const temp = steps[index];
    steps[index] = steps[index - 1];
    steps[index - 1] = temp;
    renderSteps();
}

function moveStepDown(index) {
    if (index === steps.length - 1) return;
    const temp = steps[index];
    steps[index] = steps[index + 1];
    steps[index + 1] = temp;
    renderSteps();
}

function updateStepRole(index, value) {
    steps[index] = value;
    renderPreview();
}

function renderSteps() {
    const container = document.getElementById('stepsContainer');
    container.innerHTML = '';

    steps.forEach((stepRoleId, idx) => {
        const item = document.createElement('div');
        item.className = 'exp-step-item';

        let roleOptionsHtml = '<option value="">-- Select Approver Role --</option>';
        availableRoles.forEach(r => {
            const roleName = r.display_name || r.name.replace('_', ' ');
            const selected = String(r.id) === String(stepRoleId) ? 'selected' : '';
            roleOptionsHtml += `<option value="${r.id}" ${selected}>${roleName}</option>`;
        });

        item.innerHTML = `
            <div class="exp-step-badge">Step ${idx + 1}</div>
            <select name="approval_chain[]" class="exp-select" required onchange="updateStepRole(${idx}, this.value)">
                ${roleOptionsHtml}
            </select>
            <div class="exp-step-actions">
                <button type="button" class="exp-icon-btn" title="Move Up" onclick="moveStepUp(${idx})" ${idx === 0 ? 'disabled style="opacity:.4"' : ''}>▲</button>
                <button type="button" class="exp-icon-btn" title="Move Down" onclick="moveStepDown(${idx})" ${idx === steps.length - 1 ? 'disabled style="opacity:.4"' : ''}>▼</button>
                <button type="button" class="exp-icon-btn danger" title="Remove Step" onclick="removeStep(${idx})">🗑️</button>
            </div>
        `;
        container.appendChild(item);
    });

    renderPreview();
}

function renderPreview() {
    const previewContainer = document.getElementById('previewFlow');
    const roleSelect = document.getElementById('roleSelect');
    const sourceRoleId = roleSelect.value;
    
    let sourceRoleName = 'Source Role';
    if (sourceRoleId) {
        const found = availableRoles.find(r => String(r.id) === String(sourceRoleId));
        if (found) sourceRoleName = found.display_name || found.name.replace('_', ' ');
    }

    let html = `
        <div class="exp-pipe-step-pill" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c;">
            <span class="exp-pipe-step-num" style="background:#ea580c;">★</span>
            ${sourceRoleName}
        </div>
    `;

    let validStepsCount = 0;
    steps.forEach((stepRoleId, idx) => {
        if (!stepRoleId) return;
        validStepsCount++;
        const found = availableRoles.find(r => String(r.id) === String(stepRoleId));
        const stepRoleName = found ? (found.display_name || found.name.replace('_', ' ')) : `Role #${stepRoleId}`;
        
        html += `
            <span class="exp-pipe-arrow">➔</span>
            <div class="exp-pipe-step-pill">
                <span class="exp-pipe-step-num">${validStepsCount}</span>
                ${stepRoleName}
            </div>
        `;
    });

    if (validStepsCount === 0) {
        html += `<span style="font-size:12px; color:#9a3412; margin-left:8px;">(Add steps to see approval chain)</span>`;
    }

    previewContainer.innerHTML = html;
}
</script>
@endpush
