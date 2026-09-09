@extends('layouts.app')

@section('title', 'Housekeeping Employee Entry')

@push('styles')
<style>
.hk-page {
    min-height: 100%;
    padding: 24px 28px 36px;
    background: #f8fafc;
    font-family: var(--font-family, 'Inter', sans-serif);
}
.hk-shell {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1500px;
    margin: 0 auto;
}
.hk-hero {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 24px 28px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}
.hk-hero-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
}
.hk-kicker {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 9999px;
    background: #fff1e8;
    color: #ea580c;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.hk-title {
    margin: 10px 0 6px;
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.hk-subtitle {
    margin: 0;
    max-width: 760px;
    font-size: 13px;
    line-height: 1.6;
    color: #64748b;
}
.hk-nav-tabs {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    border-top: 1px solid #f1f5f9;
    padding-top: 16px;
}
.hk-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    transition: all 0.2s ease;
}
.hk-nav-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.hk-nav-btn.active {
    background: linear-gradient(135deg, #fe5f04 0%, #ff7c30 100%);
    color: #ffffff;
    border-color: #fe5f04;
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25);
}

.hk-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.hk-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.hk-search-wrap {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    flex: 1;
}
.hk-input, .hk-select, .hk-textarea {
    width: 100%;
    padding: 9px 14px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #0f172a;
    font-size: 13px;
    outline: none;
    transition: all 0.2s ease;
}
.hk-input:hover, .hk-select:hover, .hk-textarea:hover {
    border-color: #94a3b8;
}
.hk-input:focus, .hk-select:focus, .hk-textarea:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.hk-input-inline {
    height: 40px;
    width: auto;
    min-width: 240px;
}
.hk-select-inline {
    height: 40px;
    width: auto;
    min-width: 140px;
}

.hk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.hk-btn-primary {
    background: linear-gradient(135deg, #fe5f04 0%, #ff7c30 100%);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(254, 95, 4, 0.15);
}
.hk-btn-primary:hover {
    background: linear-gradient(135deg, #e05300 0%, #f26f22 100%);
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25);
    transform: translateY(-1px);
}
.hk-btn-ghost {
    background: #ffffff;
    color: #475569;
    border-color: #e2e8f0;
}
.hk-btn-ghost:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.hk-btn-danger {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.15);
}
.hk-btn-danger:hover {
    background: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}
.hk-btn-danger-outline {
    background: #ffffff;
    color: #ef4444;
    border-color: #fecaca;
}
.hk-btn-danger-outline:hover {
    background: #fef2f2;
    border-color: #f87171;
    color: #b91c1c;
}
.hk-btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 8px;
}

.hk-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #ffffff;
}
.hk-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 13px;
}
.hk-table th {
    background: #f8fafc;
    padding: 13px 16px;
    font-weight: 700;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.05em;
}
.hk-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
}
.hk-table tbody tr:last-child td {
    border-bottom: none;
}
.hk-table tbody tr:hover td {
    background: #f8fafc;
}

.hk-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fff1e8 0%, #fed7aa 100%);
    color: #ea580c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    border: 1px solid #ffedd5;
}
.hk-emp-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.hk-emp-name {
    font-weight: 700;
    color: #0f172a;
    font-size: 13px;
}
.hk-emp-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 1px;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.badge-active {
    background: #dcfce7;
    color: #15803d;
}
.badge-active .badge-dot {
    background: #22c55e;
}
.badge-inactive {
    background: #fee2e2;
    color: #b91c1c;
}
.badge-inactive .badge-dot {
    background: #ef4444;
}

.hk-pagination-wrap {
    margin-top: 16px;
}

.hk-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.hk-modal-overlay.active {
    display: flex;
    opacity: 1;
}
.hk-modal {
    background: #ffffff;
    width: 100%;
    max-width: 560px;
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transform: scale(0.96);
    transition: transform 0.2s ease;
}
.hk-modal-overlay.active .hk-modal {
    transform: scale(1);
}
.hk-modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.hk-modal-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}
.hk-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: color 0.15s ease;
}
.hk-modal-close:hover {
    color: #0f172a;
}
.hk-modal-body {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.hk-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.hk-form-label {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
}
.hk-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.hk-modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Delete Confirmation Modal */
.hk-delete-modal {
    max-width: 440px;
    text-align: center;
    padding: 28px 24px;
}
.hk-delete-icon-box {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #fef2f2;
    color: #ef4444;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    border: 1px solid #fee2e2;
}
.hk-delete-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px;
}
.hk-delete-desc {
    font-size: 13px;
    line-height: 1.5;
    color: #64748b;
    margin: 0 0 24px;
}
.hk-delete-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.alert-box {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 16px;
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
.alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
</style>
@endpush

@section('content')
<main class="hk-page">
    <div class="hk-shell">
        <section class="hk-hero">
            <div class="hk-hero-head">
                <div>
                    <div class="hk-kicker">HRMS • House Keeping</div>
                    <h1 class="hk-title">Housekeeping Employee Entry</h1>
                    <p class="hk-subtitle">Manage housekeeping staff directory, salaries, active/inactive employment status, and contact details.</p>
                </div>
            </div>

            <div class="hk-nav-tabs">
                <a href="{{ route('house-keeping.index') }}" class="hk-nav-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"></path><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>
                    Cleaning Sheet
                </a>
                <a href="{{ route('house-keeping.employees.index') }}" class="hk-nav-btn active">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Employee Entry
                </a>
                <a href="{{ route('house-keeping.attendances.index') }}" class="hk-nav-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Attendance
                </a>
                <a href="{{ route('hrms.dashboard') }}" class="hk-nav-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Back to HRMS
                </a>
            </div>
        </section>

        @if(session('success'))
            <div class="alert-box alert-success">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert-box alert-danger">
                <ul style="margin:0; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="hk-card">
            <div class="hk-toolbar">
                <form method="GET" action="{{ route('house-keeping.employees.index') }}" class="hk-search-wrap">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, mobile, address..." class="hk-input hk-input-inline">
                    <select name="status" class="hk-select hk-select-inline">
                        <option value="">All Status</option>
                        <option value="Active" @selected(request('status') === 'Active')>Active</option>
                        <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                    </select>
                    <button type="submit" class="hk-btn hk-btn-primary">Filter</button>
                    @if(request()->filled('search') || request()->filled('status'))
                        <a href="{{ route('house-keeping.employees.index') }}" class="hk-btn hk-btn-ghost">Reset</a>
                    @endif
                </form>

                <button type="button" class="hk-btn hk-btn-primary" onclick="openAddModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add Employee
                </button>
            </div>

            <div class="hk-table-wrap">
                <table class="hk-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">S.No.</th>
                            <th>Employee Details</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th style="width: 150px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $index => $employee)
                            <tr>
                                <td>{{ $employees->firstItem() + $index }}</td>
                                <td>
                                    <div class="hk-emp-cell">
                                        <div class="hk-avatar">{{ strtoupper(substr($employee->name, 0, 1)) }}</div>
                                        <div>
                                            <div class="hk-emp-name">{{ $employee->name }}</div>
                                            <div class="hk-emp-sub">ID: #HK-{{ str_pad($employee->id, 3, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $employee->mobile_number ?: '-' }}</td>
                                <td style="max-width: 220px; white-space: normal;">{{ $employee->address ?: '-' }}</td>
                                <td><strong>₹{{ number_format($employee->salary, 2) }}</strong></td>
                                <td>
                                    <span class="badge {{ $employee->status === 'Active' ? 'badge-active' : 'badge-inactive' }}">
                                        <span class="badge-dot"></span>
                                        {{ $employee->status }}
                                    </span>
                                </td>
                                <td style="max-width: 200px; white-space: normal;">{{ $employee->remarks ?: '-' }}</td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px; justify-content: flex-end;">
                                        <button type="button" class="hk-btn hk-btn-ghost hk-btn-sm" 
                                                onclick='openEditModal(@json($employee))' title="Edit details">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            Edit
                                        </button>
                                        <button type="button" class="hk-btn hk-btn-danger-outline hk-btn-sm" 
                                                onclick="openDeleteModal({{ $employee->id }}, @js($employee->name))" title="Delete employee">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: #94a3b8; padding: 48px 20px;">
                                    <div style="font-size: 14px; font-weight: 600; color: #64748b;">No housekeeping employees found.</div>
                                    <div style="font-size: 12px; margin-top: 4px;">Click "Add Employee" above to register new housekeeping staff.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employees->hasPages())
                <div class="hk-pagination-wrap">
                    @include('partials.table-pagination', ['paginator' => $employees])
                </div>
            @endif
        </section>
    </div>
</main>

<!-- Add / Edit Modal -->
<div class="hk-modal-overlay" id="employeeModal" onclick="handleOverlayClick(event, 'employeeModal')">
    <div class="hk-modal">
        <div class="hk-modal-header">
            <h3 class="hk-modal-title" id="modalTitle">Add Housekeeping Employee</h3>
            <button type="button" class="hk-modal-close" onclick="closeModal('employeeModal')">&times;</button>
        </div>
        <form id="employeeForm" method="POST" action="{{ route('house-keeping.employees.store') }}">
            @csrf
            <div id="methodContainer"></div>
            
            <div class="hk-modal-body">
                <div class="hk-form-group">
                    <label class="hk-form-label">Employee Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" id="inp_name" required placeholder="Enter employee full name" class="hk-input">
                </div>

                <div class="hk-form-row">
                    <div class="hk-form-group">
                        <label class="hk-form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" id="inp_mobile_number" placeholder="e.g. 9876543210" class="hk-input">
                    </div>
                    <div class="hk-form-group">
                        <label class="hk-form-label">Salary (₹)</label>
                        <input type="number" step="0.01" min="0" name="salary" id="inp_salary" placeholder="0.00" class="hk-input">
                    </div>
                </div>

                <div class="hk-form-group">
                    <label class="hk-form-label">Status <span style="color: #ef4444;">*</span></label>
                    <select name="status" id="inp_status" required class="hk-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="hk-form-group">
                    <label class="hk-form-label">Address</label>
                    <textarea name="address" id="inp_address" rows="2" placeholder="Enter residential address..." class="hk-textarea"></textarea>
                </div>

                <div class="hk-form-group">
                    <label class="hk-form-label">Remarks</label>
                    <textarea name="remarks" id="inp_remarks" rows="2" placeholder="Any additional notes or duties..." class="hk-textarea"></textarea>
                </div>
            </div>

            <div class="hk-modal-footer">
                <button type="button" class="hk-btn hk-btn-ghost" onclick="closeModal('employeeModal')">Cancel</button>
                <button type="submit" class="hk-btn hk-btn-primary" id="modalSubmitBtn">Save Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="hk-modal-overlay" id="deleteConfirmModal" onclick="handleOverlayClick(event, 'deleteConfirmModal')">
    <div class="hk-modal hk-delete-modal">
        <div class="hk-delete-icon-box">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3 class="hk-delete-title">Delete Employee</h3>
        <p class="hk-delete-desc">
            Are you sure you want to delete <strong id="deleteEmpName" style="color: #0f172a;"></strong>?<br>
            This will permanently remove this employee record from the housekeeping list.
        </p>
        <form id="deleteForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="hk-delete-actions">
                <button type="button" class="hk-btn hk-btn-ghost" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <button type="submit" class="hk-btn hk-btn-danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Housekeeping Employee';
    document.getElementById('employeeForm').action = "{{ route('house-keeping.employees.store') }}";
    document.getElementById('methodContainer').innerHTML = '';
    
    document.getElementById('inp_name').value = '';
    document.getElementById('inp_mobile_number').value = '';
    document.getElementById('inp_salary').value = '';
    document.getElementById('inp_status').value = 'Active';
    document.getElementById('inp_address').value = '';
    document.getElementById('inp_remarks').value = '';
    document.getElementById('modalSubmitBtn').innerText = 'Save Employee';

    openModal('employeeModal');
}

function openEditModal(emp) {
    document.getElementById('modalTitle').innerText = 'Edit Housekeeping Employee';
    document.getElementById('employeeForm').action = "/house-keeping-employees/" + emp.id;
    document.getElementById('methodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    document.getElementById('inp_name').value = emp.name || '';
    document.getElementById('inp_mobile_number').value = emp.mobile_number || '';
    document.getElementById('inp_salary').value = emp.salary ? parseFloat(emp.salary) : '';
    document.getElementById('inp_status').value = emp.status || 'Active';
    document.getElementById('inp_address').value = emp.address || '';
    document.getElementById('inp_remarks').value = emp.remarks || '';
    document.getElementById('modalSubmitBtn').innerText = 'Update Employee';

    openModal('employeeModal');
}

function openDeleteModal(id, name) {
    document.getElementById('deleteEmpName').innerText = name;
    document.getElementById('deleteForm').action = "/house-keeping-employees/" + id;
    openModal('deleteConfirmModal');
}

function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('active');
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('active');
    }
}

function handleOverlayClick(e, id) {
    if (e.target.id === id) {
        closeModal(id);
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('employeeModal');
        closeModal('deleteConfirmModal');
    }
});
</script>
@endpush
@endsection

