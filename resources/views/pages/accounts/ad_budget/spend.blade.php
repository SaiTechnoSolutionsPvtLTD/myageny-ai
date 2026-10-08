@extends('layouts.app')

@section('title', 'Ad Spent Management')

@push('styles')
<style>
.adb-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.adb-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; }
.adb-sub { font-size:13px; color:#64748b; }

.adb-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
.adb-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.adb-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.adb-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.adb-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.adb-nav-tabs { display:flex; align-items:center; gap:4px; padding:12px 20px 0 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; overflow-x:auto; }
.adb-tab-item { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px 10px 0 0; font-size:13px; font-weight:700; color:#64748b; text-decoration:none; border:1px solid transparent; border-bottom:none; background:transparent; transition:all .15s ease; white-space:nowrap; }
.adb-tab-item:hover { color:#fe5f04; background:#ffffff; }
.adb-tab-item.active { color:#fe5f04; background:#ffffff; border-color:#e2e8f0; border-bottom:2px solid #fe5f04; margin-bottom:-1px; }

.adb-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:nowrap; overflow-x:auto; white-space:nowrap; }
.adb-search-wrap { position:relative; flex:1; min-width:180px; max-width:280px; flex-shrink:0; }
.adb-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.adb-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.adb-select { padding:8px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.adb-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.adb-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.adb-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.adb-btn-secondary { background:#f1f5f9; color:#334155; }
.adb-btn-secondary:hover { background:#e2e8f0; }
.adb-btn-danger { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; }
.adb-btn-danger:hover { background:#fca5a5; color:#991b1b; }

.adb-tbl { width:100%; border-collapse:collapse; }
.adb-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; }
.adb-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:92%; max-width:520px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:16px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 14px; border:1px solid #cbd5e1; border-radius:99px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
textarea.form-input { border-radius:12px; }
</style>
@endpush

@section('content')
<div class="adb-page">

    {{-- Topbar --}}
    <div class="adb-topbar">
        <div>
            <div class="adb-title">Ad Spent Management</div>
            <div class="adb-sub">Record, track, and audit actual daily advertisement spend across ad accounts</div>
        </div>
        <button type="button" class="adb-btn adb-btn-primary" onclick="openAddSpendModal()">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Spend Amount
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="adb-stats-grid">
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Spend Recorded</div>
                <div class="adb-stat-val">₹{{ number_format($totalSpend, 2) }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-calendar2-check"></i>
            </div>
            <div>
                <div class="adb-stat-label">This Month Spend</div>
                <div class="adb-stat-val">₹{{ number_format($thisMonthSpend, 2) }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-card-checklist"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Spend Entries</div>
                <div class="adb-stat-val">{{ $totalEntries }}</div>
            </div>
        </div>
    </div>

    {{-- Main Content Card --}}
    <div class="adb-card">
        {{-- Navigation Tabs --}}
        <div class="adb-nav-tabs">
            <a href="{{ route('accounts.ad-budget.clients') }}" class="adb-tab-item">
                <span>For Clients</span>
            </a>
            <a href="{{ route('accounts.ad-budget.partners') }}" class="adb-tab-item">
                <span>For Partners</span>
            </a>
            <a href="{{ route('accounts.ad-budget.spend') }}" class="adb-tab-item active">
                <span>Ad Spent</span>
            </a>
            <a href="{{ route('accounts.ad-budget.allocation-vs-spent') }}" class="adb-tab-item">
                <span>Allocation Vs Spent</span>
            </a>
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('accounts.ad-budget.spend') }}">
            <div class="adb-filter-bar">
                <div class="adb-search-wrap">
                    <i class="bi bi-search adb-search-ico"></i>
                    <input type="text" name="search" class="adb-search-input" placeholder="Search account, user, remarks…" value="{{ request('search') }}">
                </div>
                
                <select name="ad_account_id" class="adb-select" style="min-width:170px; flex-shrink:0;" onchange="this.form.submit()">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('ad_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->account_name }} ({{ $acc->platform }})
                        </option>
                    @endforeach
                </select>

                <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                    <span style="font-size:12px; color:#64748b; font-weight:700;">From:</span>
                    <input type="date" name="date_from" class="adb-select" value="{{ request('date_from') }}" onchange="this.form.submit()">
                </div>

                <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                    <span style="font-size:12px; color:#64748b; font-weight:700;">To:</span>
                    <input type="date" name="date_to" class="adb-select" value="{{ request('date_to') }}" onchange="this.form.submit()">
                </div>

                <button type="submit" class="adb-btn adb-btn-secondary" style="padding:8px 14px; font-size:12px; flex-shrink:0;">Filter</button>
                @if(request()->hasAny(['search','ad_account_id','date_from','date_to']))
                    <a href="{{ route('accounts.ad-budget.spend') }}" class="adb-btn adb-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1; flex-shrink:0;">Reset</a>
                @endif
            </div>
        </form>

        {{-- Data Table --}}
        @if($query->isEmpty())
            <div style="text-align:center; padding:40px; color:#94a3b8;">
                <i class="bi bi-receipt" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                No Ad Spend entries found. Click "Add Spend Amount" to create one.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="adb-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Spend Date</th>
                            <th>Ad Account</th>
                            <th>Spend Amount</th>
                            <th>Remarks</th>
                            <th>Entered By (User)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($query as $item)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">#{{ $item->id }}</td>
                            <td>
                                <strong>{{ $item->spend_date->format('d M Y') }}</strong>
                            </td>
                            <td>
                                <strong>{{ $item->adAccount?->account_name ?? '—' }}</strong>
                                <div style="font-size:11px; color:#64748b;">
                                    {{ $item->adAccount?->platform }} {{ $item->adAccount?->account_id ? '('.$item->adAccount->account_id.')' : '' }}
                                </div>
                            </td>
                            <td style="font-weight:800; color:#16a34a; font-size:14px;">
                                ₹{{ number_format($item->amount, 2) }}
                            </td>
                            <td>
                                <span style="color:{{ $item->remarks ? '#334155' : '#94a3b8' }};">
                                    {{ $item->remarks ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <div><strong>👤 {{ $item->creator?->name ?? '—' }}</strong></div>
                                <div style="font-size:11px; color:#94a3b8;">{{ $item->created_at->format('d M Y, h:i A') }}</div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <button type="button" class="adb-btn adb-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                            onclick='openEditSpendModal(@json($item))'>
                                        ✏️ Edit
                                    </button>
                                    <button type="button" class="adb-btn adb-btn-danger" style="padding:4px 10px; font-size:11px;"
                                            onclick='openDeleteSpendModal(@json($item))'>
                                        🗑️ Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($query->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                    {{ $query->links() }}
                </div>
            @endif
        @endif

    </div>

</div>

{{-- 1. Add Spend Amount Modal --}}
<div class="modal-overlay" id="addSpendModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Add Spend Amount</div>
            <button type="button" class="modal-close" onclick="closeAddSpendModal()">×</button>
        </div>
        <form method="POST" action="{{ route('accounts.ad-budget.spend.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Spend Date <span style="color:#dc2626;">*</span></label>
                    <input type="date" name="spend_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Ad Account <span style="color:#dc2626;">*</span></label>
                    <select name="ad_account_id" class="form-input" required>
                        <option value="">-- Select Ad Account from Master --</option>
                        @foreach($adAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->account_name }} ({{ $acc->platform }}) {{ $acc->account_id ? '- '.$acc->account_id : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Spend Amount (₹) <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="amount" class="form-input" step="0.01" min="0.01" placeholder="e.g. 2500.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Remarks / Note</label>
                    <textarea name="remarks" class="form-input" rows="3" placeholder="Enter optional notes (e.g. Daily Meta Ad spend for Client X)…"></textarea>
                </div>
            </div>
            <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="adb-btn adb-btn-secondary" onclick="closeAddSpendModal()">Cancel</button>
                <button type="submit" class="adb-btn adb-btn-primary">Save Spend Amount</button>
            </div>
        </form>
    </div>
</div>

{{-- 2. Edit Spend Entry Modal --}}
<div class="modal-overlay" id="editSpendModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Edit Spend Amount Entry</div>
            <button type="button" class="modal-close" onclick="closeEditSpendModal()">×</button>
        </div>
        <form id="editSpendForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Spend Date <span style="color:#dc2626;">*</span></label>
                    <input type="date" name="spend_date" id="edit_spend_date" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Ad Account <span style="color:#dc2626;">*</span></label>
                    <select name="ad_account_id" id="edit_ad_account_id" class="form-input" required>
                        @foreach($adAccounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->account_name }} ({{ $acc->platform }}) {{ $acc->account_id ? '- '.$acc->account_id : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Spend Amount (₹) <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="amount" id="edit_amount" class="form-input" step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Remarks / Note</label>
                    <textarea name="remarks" id="edit_remarks" class="form-input" rows="3"></textarea>
                </div>
            </div>
            <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="adb-btn adb-btn-secondary" onclick="closeEditSpendModal()">Cancel</button>
                <button type="submit" class="adb-btn adb-btn-primary">Update Entry</button>
            </div>
        </form>
    </div>
</div>

{{-- 3. Delete Confirmation Modal --}}
<div class="modal-overlay" id="deleteSpendModal">
    <div class="modal-box" style="max-width:440px;">
        <div class="modal-header">
            <div class="modal-title" style="color:#dc2626;">Confirm Delete</div>
            <button type="button" class="modal-close" onclick="closeDeleteSpendModal()">×</button>
        </div>
        <form id="deleteSpendForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <div style="text-align:center; padding:10px 0;">
                    <div style="font-size:40px; margin-bottom:10px;">⚠️</div>
                    <div style="font-size:15px; font-weight:800; color:#0f172a;">Delete Ad Spend Entry <span id="del_req_id"></span>?</div>
                    <div style="font-size:13px; color:#64748b; margin-top:6px;">
                        This item will be soft-deleted. You can restore it if needed.
                    </div>
                </div>
            </div>
            <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="adb-btn adb-btn-secondary" onclick="closeDeleteSpendModal()">Cancel</button>
                <button type="submit" class="adb-btn adb-btn-danger">Yes, Delete Entry</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAddSpendModal() {
    document.getElementById('addSpendModal').classList.add('active');
}
function closeAddSpendModal() {
    document.getElementById('addSpendModal').classList.remove('active');
}

function openEditSpendModal(item) {
    document.getElementById('editSpendForm').action = '/accounts/ad-budget/spend/' + item.id;
    
    // Format spend date to YYYY-MM-DD
    let formattedDate = '';
    if (item.spend_date) {
        formattedDate = item.spend_date.substring(0, 10);
    }
    document.getElementById('edit_spend_date').value = formattedDate;
    document.getElementById('edit_ad_account_id').value = item.ad_account_id || '';
    document.getElementById('edit_amount').value = item.amount || '';
    document.getElementById('edit_remarks').value = item.remarks || '';

    document.getElementById('editSpendModal').classList.add('active');
}
function closeEditSpendModal() {
    document.getElementById('editSpendModal').classList.remove('active');
}

function openDeleteSpendModal(item) {
    document.getElementById('deleteSpendForm').action = '/accounts/ad-budget/spend/' + item.id;
    document.getElementById('del_req_id').innerText = '#' + item.id;
    document.getElementById('deleteSpendModal').classList.add('active');
}
function closeDeleteSpendModal() {
    document.getElementById('deleteSpendModal').classList.remove('active');
}
</script>
@endpush
@endsection
