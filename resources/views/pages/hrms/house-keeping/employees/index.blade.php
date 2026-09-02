@extends('layouts.app')

@section('title', 'Housekeeping Employee Entry')

@push('styles')
<style>
.hk-page { min-height: 100%; padding: 28px; background: linear-gradient(180deg, #f7f3ee 0%, #f3f5f8 100%); }
.hk-shell { display: flex; flex-direction: column; gap: 20px; max-width: 1500px; margin: 0 auto; }
.hk-hero, .hk-card { background: #fff; border: 1px solid #e7e2dc; border-radius: 24px; box-shadow: 0 18px 40px rgba(18,18,18,.05); }
.hk-hero { padding: 26px 28px; background: linear-gradient(135deg, #fff8f1 0%, #ffffff 62%, #f4fbff 100%); }
.hk-kicker { display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; background: #fff1e8; color: #c2410c; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.hk-title { margin: 14px 0 8px; font-size: 30px; font-weight: 800; color: #111827; }
.hk-subtitle { margin: 0; max-width: 760px; font-size: 14px; line-height: 1.7; color: #6b7280; }
.hk-nav-tabs { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; border-bottom: 2px solid #e7e2dc; padding-bottom: 12px; }
.hk-nav-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; border: 1px solid #e5ddd6; background: #fff; color: #374151; transition: all .2s; }
.hk-nav-btn:hover { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.hk-nav-btn.active { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; border-color: transparent; box-shadow: 0 4px 14px rgba(254, 95, 4, 0.35); }

.hk-card { padding: 24px; }
.hk-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.hk-search-wrap { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.hk-input, .hk-select, .hk-textarea { width: 100%; padding: 10px 14px; border-radius: 12px; border: 1px solid #d1d5db; background: #fff; color: #111827; font-size: 13px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
.hk-input:focus, .hk-select:focus, .hk-textarea:focus { border-color: #fe5f04; box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.15); }
.hk-input-inline { min-height: 42px; width: auto; min-width: 220px; }

.hk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; border: 1px solid transparent; cursor: pointer; transition: all .2s; }
.hk-btn-primary { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; box-shadow: 0 4px 12px rgba(254, 95, 4, 0.3); }
.hk-btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
.hk-btn-ghost { background: #fff; color: #374151; border-color: #e5ddd6; }
.hk-btn-ghost:hover { background: #f9fafb; border-color: #d1d5db; }
.hk-btn-danger { background: #ef4444; color: #fff; }
.hk-btn-danger:hover { background: #dc2626; }
.hk-btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 8px; }

.hk-table-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 16px; background: #fff; }
.hk-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
.hk-table th { background: #f9fafb; padding: 14px 16px; font-weight: 700; color: #374151; border-bottom: 1px solid #e5e7eb; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; }
.hk-table td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
.hk-table tr:last-child td { border-bottom: none; }
.hk-table tr:hover td { background: #fffdfa; }

.badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
.badge-active { background: #dcfce7; color: #15803d; }
.badge-inactive { background: #fee2e2; color: #b91c1c; }

.hk-pagination-wrap { margin-top: 16px; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; background: #fff; box-shadow: 0 2px 6px rgba(18, 18, 18, 0.02); }
.hk-pagination-wrap .app-pagination { border-top: none; }

.hk-modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
.hk-modal-overlay.active { display: flex; }
.hk-modal { background: #fff; width: 100%; max-width: 580px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; animation: modalIn 0.2s ease-out; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.hk-modal-header { padding: 20px 24px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
.hk-modal-title { font-size: 18px; font-weight: 800; color: #111827; margin: 0; }
.hk-modal-close { background: none; border: none; font-size: 24px; color: #9ca3af; cursor: pointer; padding: 0; line-height: 1; }
.hk-modal-close:hover { color: #111827; }
.hk-modal-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
.hk-form-group { display: flex; flex-direction: column; gap: 6px; }
.hk-form-label { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.03em; }
.hk-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.hk-modal-footer { padding: 16px 24px; background: #f9fafb; border-top: 1px solid #f3f4f6; display: flex; justify-content: flex-end; gap: 12px; }

.alert-success { padding: 14px 18px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; border-radius: 12px; margin-bottom: 16px; font-weight: 600; font-size: 13px; }
.alert-danger { padding: 14px 18px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 12px; margin-bottom: 16px; font-weight: 600; font-size: 13px; }
</style>
@endpush

@section('content')
<main class="hk-page">
    <div class="hk-shell">
        <section class="hk-hero">
            <div class="hk-kicker">HRMS House Keeping</div>
            <h1 class="hk-title">Housekeeping Employee Entry</h1>
            <p class="hk-subtitle">Manage Housekeeping staff details, salaries, employment status, and contact information.</p>

            <div class="hk-nav-tabs">
                <a href="{{ route('house-keeping.index') }}" class="hk-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"></path><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>
                    Cleaning Sheet
                </a>
                <a href="{{ route('house-keeping.employees.index') }}" class="hk-nav-btn active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Employee Entry
                </a>
                <a href="{{ route('house-keeping.attendances.index') }}" class="hk-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Attendance
                </a>
                <a href="{{ route('hrms.dashboard') }}" class="hk-nav-btn">Back to HRMS</a>
            </div>
        </section>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-danger">
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
                    <select name="status" class="hk-select hk-input-inline" style="min-width: 140px;">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
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
                            <th>Name</th>
                            <th>Mobile Number</th>
                            <th>Address</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th style="width: 130px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $index => $employee)
                            <tr>
                                <td>{{ $employees->firstItem() + $index }}</td>
                                <td><strong>{{ $employee->name }}</strong></td>
                                <td>{{ $employee->mobile_number ?: '-' }}</td>
                                <td>{{ $employee->address ?: '-' }}</td>
                                <td>₹{{ number_format($employee->salary, 2) }}</td>
                                <td>
                                    <span class="badge {{ $employee->status === 'Active' ? 'badge-active' : 'badge-inactive' }}">
                                        {{ $employee->status }}
                                    </span>
                                </td>
                                <td>{{ $employee->remarks ?: '-' }}</td>
                                <td style="text-align: right;">
                                    <button type="button" class="hk-btn hk-btn-ghost hk-btn-sm" 
                                            onclick='openEditModal(@json($employee))'>
                                        Edit
                                    </button>
                                    <form action="{{ route('house-keeping.employees.destroy', $employee) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this housekeeping employee?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="hk-btn hk-btn-danger hk-btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: #9ca3af; padding: 36px;">
                                    No housekeeping employees found. Click "Add Employee" to register one.
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
<div class="hk-modal-overlay" id="employeeModal">
    <div class="hk-modal">
        <div class="hk-modal-header">
            <h3 class="hk-modal-title" id="modalTitle">Add Housekeeping Employee</h3>
            <button type="button" class="hk-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="employeeForm" method="POST" action="{{ route('house-keeping.employees.store') }}">
            @csrf
            <div id="methodContainer"></div>
            
            <div class="hk-modal-body">
                <div class="hk-form-group">
                    <label class="hk-form-label">Employee Name <span style="color: red;">*</span></label>
                    <input type="text" name="name" id="inp_name" required placeholder="Enter employee full name" class="hk-input">
                </div>

                <div class="hk-form-row">
                    <div class="hk-form-group">
                        <label class="hk-form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" id="inp_mobile_number" placeholder="Enter mobile number" class="hk-input">
                    </div>
                    <div class="hk-form-group">
                        <label class="hk-form-label">Salary (₹)</label>
                        <input type="number" step="0.01" min="0" name="salary" id="inp_salary" placeholder="0.00" class="hk-input">
                    </div>
                </div>

                <div class="hk-form-group">
                    <label class="hk-form-label">Status <span style="color: red;">*</span></label>
                    <select name="status" id="inp_status" required class="hk-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="hk-form-group">
                    <label class="hk-form-label">Address</label>
                    <textarea name="address" id="inp_address" rows="2" placeholder="Enter complete address..." class="hk-textarea"></textarea>
                </div>

                <div class="hk-form-group">
                    <label class="hk-form-label">Remarks</label>
                    <textarea name="remarks" id="inp_remarks" rows="2" placeholder="Enter additional remarks or notes..." class="hk-textarea"></textarea>
                </div>
            </div>

            <div class="hk-modal-footer">
                <button type="button" class="hk-btn hk-btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="hk-btn hk-btn-primary" id="modalSubmitBtn">Save Employee</button>
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

    document.getElementById('employeeModal').classList.add('active');
}

function openEditModal(emp) {
    document.getElementById('modalTitle').innerText = 'Edit Housekeeping Employee';
    document.getElementById('employeeForm').action = "/house-keeping-employees/" + emp.id;
    document.getElementById('methodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    document.getElementById('inp_name').value = emp.name || '';
    document.getElementById('inp_mobile_number').value = emp.mobile_number || '';
    document.getElementById('inp_salary').value = emp.salary || '0';
    document.getElementById('inp_status').value = emp.status || 'Active';
    document.getElementById('inp_address').value = emp.address || '';
    document.getElementById('inp_remarks').value = emp.remarks || '';
    document.getElementById('modalSubmitBtn').innerText = 'Update Employee';

    document.getElementById('employeeModal').classList.add('active');
}

function closeModal() {
    document.getElementById('employeeModal').classList.remove('active');
}
</script>
@endpush
@endsection
