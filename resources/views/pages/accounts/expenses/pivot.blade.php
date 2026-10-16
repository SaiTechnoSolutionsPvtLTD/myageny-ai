@extends('layouts.app')

@section('title', 'Expense Pivot Report - Accounts')

@push('styles')
<style>
.acc-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }

/* Quick View Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:94%; max-width:720px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:18px; }
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns:1fr; } }
.detail-item { background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0; }
.detail-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.detail-val { font-size:13px; font-weight:700; color:#0f172a; word-break:break-word; }
.type-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
</style>
@endpush

@section('content')
<div class="acc-page">
    @include('pages.accounts.expenses.pivot_content', ['isModal' => false])
</div>

{{-- Quick View Modal for Individual Expenses in Pivot --}}
<div class="modal-overlay" id="viewExpenseModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-receipt" style="color:#fe5f04;"></i>
                <span id="mvExpenseNumber">Expense Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <div style="text-align:center; padding:30px; color:#64748b;">
                <i class="bi bi-arrow-repeat spin" style="font-size:24px;"></i>
                <div style="margin-top:6px;">Loading expense details...</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function viewExpense(id) {
    const modal = document.getElementById('viewExpenseModal');
    const body = document.getElementById('viewModalBody');
    const title = document.getElementById('mvExpenseNumber');
    if (!modal) return;

    modal.classList.add('active');
    body.innerHTML = `
        <div style="text-align:center; padding:30px; color:#64748b;">
            <i class="bi bi-arrow-repeat spin" style="font-size:24px;"></i>
            <div style="margin-top:6px;">Loading expense details...</div>
        </div>
    `;

    fetch(`{{ url('accounts/expenses') }}/${id}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.status) throw new Error('Failed to load');
        const exp = data.expense;
        const atts = data.attachment_list || [];

        title.textContent = `${exp.expense_number} Details`;

        let attachmentsHtml = '<div style="color:#94a3b8; font-size:12px;">No attachments uploaded</div>';
        if (atts.length > 0) {
            attachmentsHtml = '<div style="display:flex; flex-direction:column; gap:8px;">';
            atts.forEach(att => {
                attachmentsHtml += `
                    <div style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px;">
                        <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
                            <i class="bi ${att.is_image ? 'bi-image' : (att.is_pdf ? 'bi-file-earmark-pdf' : 'bi-paperclip')}" style="color:#2563eb; font-size:18px;"></i>
                            <div>
                                <div style="font-weight:600; font-size:12px; color:#1e293b;">${att.name}</div>
                                <div style="font-size:11px; color:#64748b;">${att.formatted_size || ''}</div>
                            </div>
                        </div>
                        <a href="${att.url}" target="_blank" class="acc-btn acc-btn-outline" style="padding:4px 10px; font-size:11px;">
                            <i class="bi bi-box-arrow-up-right"></i> View / Download
                        </a>
                    </div>
                `;
            });
            attachmentsHtml += '</div>';
        }

        body.innerHTML = `
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Expense Number</div>
                    <div class="detail-val" style="color:#fe5f04;">${exp.expense_number}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Expense Date</div>
                    <div class="detail-val">${exp.expense_date ? new Date(exp.expense_date).toLocaleDateString() : '—'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Expense Amount</div>
                    <div class="detail-val" style="font-size:16px; color:#059669;">₹${parseFloat(exp.amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Expense Type</div>
                    <div class="detail-val">
                        <span class="type-badge ${exp.expense_type === 'Fixed' ? 'type-fixed-badge' : 'type-variable-badge'}">
                            ${exp.expense_type}
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Invoice / Bill No</div>
                    <div class="detail-val">${exp.invoice_no || '—'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Recurring Setting</div>
                    <div class="detail-val">
                        ${exp.is_recurring 
                            ? `<span style="color:#15803d; font-weight:800;"><i class="bi bi-arrow-repeat"></i> Auto-generates Monthly (Day ${exp.recurring_day_of_month || '1'})</span>` 
                            : (exp.auto_generated 
                                ? `<span style="color:#6d28d9; font-weight:700;"><i class="bi bi-robot"></i> Auto-generated Monthly Record</span>` 
                                : '<span style="color:#64748b; font-weight:normal;">One-off / Non-recurring</span>')}
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Category / Subcategory</div>
                    <div class="detail-val">
                        ${exp.category ? exp.category.name : '—'}
                        ${exp.subcategory ? '<br><span style="color:#64748b; font-size:11px;">↳ ' + exp.subcategory.name + '</span>' : ''}
                    </div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label">Vendor Details</div>
                    <div class="detail-val">
                        ${exp.vendor ? `<strong>${exp.vendor.vendor_name}</strong> ${exp.vendor.company_name ? '— ' + exp.vendor.company_name : ''} ${exp.vendor.mobile_number ? ' | 📞 ' + exp.vendor.mobile_number : ''} ${exp.vendor.gst_number ? ' | GST: ' + exp.vendor.gst_number : ''}` : '<span style="color:#94a3b8;">No vendor associated</span>'}
                    </div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label">Remarks / Description</div>
                    <div class="detail-val" style="font-weight:normal; line-height:1.5;">${exp.remarks || '<span style="color:#94a3b8;">No remarks provided</span>'}</div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label" style="margin-bottom:8px;">Attachments (${atts.length})</div>
                    ${attachmentsHtml}
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                <a href="{{ url('accounts/expenses') }}/${exp.id}/edit" class="acc-btn acc-btn-primary" style="padding:6px 14px; font-size:12px;">
                    <i class="bi bi-pencil-square"></i> Edit Expense
                </a>
            </div>
        `;
    })
    .catch(err => {
        body.innerHTML = `
            <div style="text-align:center; padding:30px; color:#b91c1c;">
                <i class="bi bi-exclamation-triangle" style="font-size:24px;"></i>
                <div style="margin-top:6px;">Failed to load expense details.</div>
            </div>
        `;
    });
}

function closeViewModal() {
    document.getElementById('viewExpenseModal')?.classList.remove('active');
}

// Close on backdrop or escape
document.getElementById('viewExpenseModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeViewModal();
});
</script>
@endpush
@endsection
