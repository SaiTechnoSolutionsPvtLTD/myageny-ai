@extends('layouts.app')

@section('title', 'Petty Cash Account Report & Rani - myAgenci.ai')

@push('styles')
<style>
.petty-cash-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.petty-cash-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding: 24px 28px;
    border: 1px solid #e6e8ee;
    border-radius: 20px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.04);
}
.petty-cash-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.petty-cash-title {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    color: #111827;
}
.petty-cash-subtitle {
    margin: 6px 0 0;
    font-size: 14px;
    color: #6b7280;
}
.alert-success {
    background-color: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 600;
}

/* 12-Column Grid Layout */
.petty-cash-grid-container {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 24px;
    align-items: start;
}
.petty-cash-col-8 {
    grid-column: span 8;
}
.petty-cash-col-4 {
    grid-column: span 4;
}
.petty-cash-col-8 > div.hrms-card {
    grid-column: auto !important;
    width: 100%;
}
@media (max-width: 1024px) {
    .petty-cash-col-8,
    .petty-cash-col-4 {
        grid-column: span 12;
    }
}

/* Modal Styling */
.rani-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.rani-modal {
    background: #ffffff;
    border-radius: 20px;
    width: 90%;
    max-width: 440px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    animation: raniPopIn .2s ease;
    overflow: hidden;
}
@keyframes raniPopIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.rani-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
}
.rani-modal-title { font-size: 17px; font-weight: 800; color: #0f172a; }
.rani-modal-close {
    background: none; border: none; font-size: 22px; color: #94a3b8; cursor: pointer;
}
.rani-modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.rani-form-group { display: flex; flex-direction: column; gap: 6px; }
.rani-form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #475569; }
.rani-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
}
.rani-input:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.rani-modal-foot {
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.rani-btn {
    padding: 9px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    font-family: inherit;
}
.rani-btn-primary {
    background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25);
}
.rani-btn-secondary {
    background: #fff;
    color: #475569;
    border: 1px solid #cbd5e1;
}
</style>
@endpush

@section('content')
<div class="petty-cash-page">
    @if(session('success'))
        <div class="alert-success">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif

    <div class="petty-cash-hero">
        <div>
            <div class="petty-cash-kicker">
                <i class="bi bi-wallet2"></i> Finance Management
            </div>
            <h2 class="petty-cash-title">Petty Cash & Rani Accounts</h2>
            <p class="petty-cash-subtitle">Branch-wise Debit & Credit transaction ledger statement alongside Rani petty cash entries.</p>
        </div>
    </div>

    <!-- Main 12-Column Single Row Layout -->
    <div class="petty-cash-grid-container">
        
        <!-- 8 Column: Petty Cash Account Report Partial -->
        <div class="petty-cash-col-8">
            @include('pages.hrms.dashboard.partials._petty_cash_report')
        </div>

        <!-- 4 Column: Rani Petty Cash Card -->
        <div class="petty-cash-col-4">
            <div class="hrms-card hrms-panel" style="background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                <!-- Card Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 40px; height: 40px; border-radius: 12px; background: #fff7ed; color: #fe5f04; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #ffedd5; font-weight: 800;">
                            👑
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a;">Rani</h3>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Rani petty cash account ledger.</p>
                        </div>
                    </div>

                    <div>
                        <button type="button" onclick="openAddRaniModal()" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%); color: #fff; font-weight: 700; font-size: 12px; padding: 8px 14px; border-radius: 9px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(254,95,4,0.25);">
                            <i class="bi bi-plus-circle-fill"></i> + Add
                        </button>
                    </div>
                </div>

                <!-- Table showing ONLY Date and Amount -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 10px 12px; text-align: left; font-weight: 800; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Date</th>
                                <th style="padding: 10px 12px; text-align: left; font-weight: 800; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Amount</th>
                                <th style="padding: 10px 12px; text-align: right; font-weight: 800; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($raniEntries ?? [] as $rani)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px 12px; font-weight: 700; color: #0f172a; white-space: nowrap;">
                                    📅 {{ $rani->entry_date ? $rani->entry_date->format('d M Y') : '-' }}
                                </td>
                                <td style="padding: 12px 12px; font-weight: 800; color: #16a34a; font-size: 13px; white-space: nowrap;">
                                    ₹ {{ number_format($rani->amount, 2) }}
                                </td>
                                <td style="padding: 12px 12px; text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" onclick='openEditRaniModal(@json($rani))' style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 700; cursor: pointer;">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('hrms.petty-cash.rani.destroy', $rani) }}" onsubmit="return confirm('Delete this Rani entry?');" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 700; cursor: pointer;">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 36px 12px; color: #94a3b8;">
                                    <div style="font-size: 28px; margin-bottom: 4px;">👑</div>
                                    <div style="font-weight: 700; color: #475569; font-size: 13px;">No Rani Entries Found</div>
                                    <div style="font-size: 11px; margin-top: 2px;">Click "+ Add" above to add an entry.</div>
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

<!-- Add / Edit Rani Modal Popup -->
<div class="rani-modal-overlay" id="raniModal">
    <div class="rani-modal">
        <div class="rani-modal-head">
            <div class="rani-modal-title" id="raniModalTitle">Add Rani Entry</div>
            <button class="rani-modal-close" onclick="closeRaniModal()">✕</button>
        </div>

        <form id="raniForm" method="POST" action="{{ route('hrms.petty-cash.rani.store') }}">
            @csrf
            <input type="hidden" name="_method" id="raniFormMethod" value="POST">

            <div class="rani-modal-body">
                <div class="rani-form-group">
                    <label class="rani-form-label">Date <span style="color:#dc2626;">*</span></label>
                    <input type="date" name="entry_date" id="raniDateInput" class="rani-input" required value="{{ date('Y-m-d') }}">
                </div>

                <div class="rani-form-group">
                    <label class="rani-form-label">Amount (₹) <span style="color:#dc2626;">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="raniAmountInput" class="rani-input" required placeholder="0.00">
                </div>
            </div>

            <div class="rani-modal-foot">
                <button type="button" class="rani-btn rani-btn-secondary" onclick="closeRaniModal()">Cancel</button>
                <button type="submit" class="rani-btn rani-btn-primary">Save Entry</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAddRaniModal() {
    document.getElementById('raniModalTitle').textContent = 'Add Rani Entry';
    document.getElementById('raniFormMethod').value = 'POST';
    document.getElementById('raniForm').action = "{{ route('hrms.petty-cash.rani.store') }}";
    document.getElementById('raniDateInput').value = "{{ date('Y-m-d') }}";
    document.getElementById('raniAmountInput').value = '';
    document.getElementById('raniModal').style.display = 'flex';
}

function openEditRaniModal(entry) {
    document.getElementById('raniModalTitle').textContent = 'Edit Rani Entry';
    document.getElementById('raniFormMethod').value = 'PUT';
    document.getElementById('raniForm').action = `/hrms/petty-cash/rani/${entry.id}`;
    
    let formattedDate = entry.entry_date ? entry.entry_date.substring(0, 10) : '';
    document.getElementById('raniDateInput').value = formattedDate;
    document.getElementById('raniAmountInput').value = entry.amount || '';
    document.getElementById('raniModal').style.display = 'flex';
}

function closeRaniModal() {
    document.getElementById('raniModal').style.display = 'none';
}

document.getElementById('raniModal').addEventListener('click', function(e) {
    if (e.target === this) closeRaniModal();
});
</script>
@endpush
