@extends('layouts.app')

@section('title', 'Ad Accounts Master')

@push('styles')
<style>
.adm-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.adm-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.adm-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.adm-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.adm-breadcrumb a { color:#9e9e9e; text-decoration:none; }
.adm-breadcrumb a:hover { color:#fe5f04; }
.adm-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.adm-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .adm-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .adm-body { padding:16px 20px 24px; }
}
.adm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.adm-filter-bar { display:flex; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:wrap; }
.adm-search-wrap { position:relative; flex:1; min-width:240px; }
.adm-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.adm-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.adm-select { padding:8px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.adm-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.adm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.adm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.adm-btn-secondary { background:#f1f5f9; color:#334155; }

.adm-tbl { width:100%; border-collapse:collapse; }
.adm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; }
.adm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.adm-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.badge-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.badge-inactive { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

/* Modal */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:16px; width:90%; max-width:500px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.2); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
.modal-title { font-size:16px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:14px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
</style>
@endpush

@section('content')
<div class="adm-page">

    {{-- Topbar --}}
    <div class="adm-topbar">
        <div>
            <div class="adm-title">
                <i class="bi bi-display" style="color:#fe5f04;"></i>
                <span>Ad Accounts Master</span>
            </div>
            <div class="adm-breadcrumb">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Ad Accounts</span>
            </div>
        </div>
        <div class="adm-actions">
            <a href="{{ route('accounts.master.index') }}" class="adm-btn adm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Master Hub
            </a>
            <button type="button" class="adm-btn adm-btn-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Add New Ad Account
            </button>
        </div>
    </div>

    <div class="adm-body">

    {{-- Alert --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Table Card --}}
    <div class="adm-card">
        <form method="GET" action="{{ route('accounts.ad-accounts-master.index') }}">
            <div class="adm-filter-bar">
                <div class="adm-search-wrap">
                    <i class="bi bi-search adm-search-ico"></i>
                    <input type="text" name="search" class="adm-search-input" placeholder="Search account name, account ID, platform…" value="{{ request('search') }}">
                </div>
                <select name="platform" class="adm-select" onchange="this.form.submit()">
                    <option value="">All Platforms</option>
                    <option value="Meta" {{ request('platform') === 'Meta' ? 'selected' : '' }}>Meta (Facebook / Instagram)</option>
                    <option value="Google" {{ request('platform') === 'Google' ? 'selected' : '' }}>Google Ads</option>
                    <option value="LinkedIn" {{ request('platform') === 'LinkedIn' ? 'selected' : '' }}>LinkedIn</option>
                    <option value="YouTube" {{ request('platform') === 'YouTube' ? 'selected' : '' }}>YouTube</option>
                    <option value="Other" {{ request('platform') === 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                <select name="status" class="adm-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="adm-btn adm-btn-secondary" style="padding:8px 14px; font-size:12px;">Filter</button>
                @if(request()->hasAny(['search','platform','status']))
                    <a href="{{ route('accounts.ad-accounts-master.index') }}" class="adm-btn adm-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                @endif
            </div>
        </form>

        @if($query->isEmpty())
            <div style="text-align:center; padding:40px; color:#94a3b8;">
                <i class="bi bi-wallet2" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                No Ad Accounts found. Click "Add New Ad Account" to create one.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="adm-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Account Name</th>
                            <th>Account ID</th>
                            <th>Platform</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($query as $item)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $query->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->account_name }}</strong>
                                @if($item->notes)
                                    <div style="font-size:11px; color:#64748b;">{{ $item->notes }}</div>
                                @endif
                            </td>
                            <td>
                                <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:12px; color:#475569;">{{ $item->account_id ?? '—' }}</code>
                            </td>
                            <td>
                                <span style="display:inline-flex; align-items:center; gap:5px; font-weight:700; font-size:12px;">
                                    @if($item->platform === 'Meta')
                                        <i class="bi bi-facebook" style="color:#1877f2;"></i> Meta
                                    @elseif($item->platform === 'Google')
                                        <i class="bi bi-google" style="color:#ea4335;"></i> Google Ads
                                    @elseif($item->platform === 'LinkedIn')
                                        <i class="bi bi-linkedin" style="color:#0a66c2;"></i> LinkedIn
                                    @else
                                        <i class="bi bi-globe" style="color:#64748b;"></i> {{ $item->platform }}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="adm-badge badge-{{ $item->status }}">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td>{{ $item->creator?->name ?? '—' }}</td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <button type="button" class="adm-btn adm-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                            onclick='openEditModal(@json($item))'>
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('accounts.ad-accounts-master.toggle-status', $item) }}" style="display:inline;">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="adm-btn adm-btn-secondary" style="padding:4px 10px; font-size:11px;">
                                            {{ $item->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
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

    </div>{{-- /adm-body --}}
</div>{{-- /adm-page --}}

{{-- Create Modal --}}
<div class="modal-overlay" id="createModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Add New Ad Account</div>
            <button type="button" class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <form method="POST" action="{{ route('accounts.ad-accounts-master.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Account Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="account_name" class="form-input" placeholder="e.g. Meta Ads - Coimbatore HO" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Account ID / Number</label>
                    <input type="text" name="account_id" class="form-input" placeholder="e.g. act_1029384756 or 123-456-7890">
                </div>
                <div class="form-group">
                    <label class="form-label">Platform <span style="color:#dc2626;">*</span></label>
                    <select name="platform" class="form-input" required>
                        <option value="Meta">Meta (Facebook / Instagram)</option>
                        <option value="Google">Google Ads</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="YouTube">YouTube</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Optional details…"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                    <button type="button" class="adm-btn adm-btn-secondary" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary">Create Ad Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Edit Ad Account</div>
            <button type="button" class="modal-close" onclick="closeEditModal()">×</button>
        </div>
        <form method="POST" id="editForm">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Account Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="account_name" id="edit_account_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Account ID / Number</label>
                    <input type="text" name="account_id" id="edit_account_id" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Platform <span style="color:#dc2626;">*</span></label>
                    <select name="platform" id="edit_platform" class="form-input" required>
                        <option value="Meta">Meta (Facebook / Instagram)</option>
                        <option value="Google">Google Ads</option>
                        <option value="LinkedIn">LinkedIn</option>
                        <option value="YouTube">YouTube</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-input">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="notes" id="edit_notes" class="form-input" rows="2"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                    <button type="button" class="adm-btn adm-btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary">Update Ad Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('createModal').classList.add('active');
}
function closeCreateModal() {
    document.getElementById('createModal').classList.remove('active');
}
function openEditModal(item) {
    document.getElementById('editForm').action = '/accounts/ad-accounts-master/' + item.id;
    document.getElementById('edit_account_name').value = item.account_name || '';
    document.getElementById('edit_account_id').value = item.account_id || '';
    document.getElementById('edit_platform').value = item.platform || 'Meta';
    document.getElementById('edit_status').value = item.status || 'active';
    document.getElementById('edit_notes').value = item.notes || '';
    document.getElementById('editModal').classList.add('active');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}
</script>
@endpush
@endsection
