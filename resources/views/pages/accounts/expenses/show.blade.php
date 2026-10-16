@extends('layouts.app')

@section('title', 'Expense Details - ' . $expense->expense_number)

@push('styles')
<style>
.acc-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.acc-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.acc-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.acc-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.acc-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.acc-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.acc-breadcrumbs a:hover { color:#fe5f04; }
.acc-breadcrumbs .sep { color:#ccc; }
.acc-breadcrumbs .current { color:#424242; font-weight:600; }
.acc-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.acc-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .acc-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .acc-body { padding:16px 20px 24px; }
}

.acc-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acc-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acc-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acc-btn-secondary { background:#f1f5f9; color:#334155; }
.acc-btn-secondary:hover { background:#e2e8f0; }
.acc-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acc-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acc-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acc-btn-danger:hover { background:#fca5a5; }

.acc-grid-layout { display:grid; grid-template-columns:2fr 1fr; gap:20px; }
@media (max-width: 900px) { .acc-grid-layout { grid-template-columns:1fr; } }

.acc-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.acc-card-title { font-size:15px; font-weight:800; color:#0f172a; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #f1f5f9; padding-bottom:10px; }

.detail-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc; font-size:13px; }
.detail-row:last-child { border-bottom:none; }
.detail-row .label { color:#64748b; font-weight:600; }
.detail-row .value { color:#0f172a; font-weight:700; text-align:right; }

.type-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:700; }
.type-fixed-badge { background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe; }
.type-variable-badge { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }

.amount-hero { font-size:28px; font-weight:900; color:#059669; line-height:1; }

.att-card { display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:12px 14px; border-radius:10px; transition:all .2s; }
.att-card:hover { border-color:#cbd5e1; background:#f1f5f9; }

/* Image thumbnails */
.att-thumb-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px; margin-top:12px; }
.att-thumb-item { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; background:#fff; text-decoration:none; display:flex; flex-direction:column; transition:transform .2s; }
.att-thumb-item:hover { transform:scale(1.02); box-shadow:0 4px 12px rgba(0,0,0,.08); }
.att-thumb-img { width:100%; height:110px; object-fit:cover; background:#f1f5f9; }
.att-thumb-meta { padding:8px 10px; font-size:11px; color:#475569; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* Modal */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:94%; max-width:440px; padding:24px; box-shadow:0 24px 60px rgba(0,0,0,.25); }
</style>
@endpush

@section('content')
<div class="acc-page">

    {{-- Standard Uniform Topbar --}}
    <div class="acc-topbar">
        <div class="acc-topbar-left">
            <div class="acc-title">
                <i class="bi bi-receipt-cutoff" style="color:#fe5f04;"></i>
                <span>Expense {{ $expense->expense_number }}</span>
                <span class="type-badge {{ $expense->expense_type === 'Fixed' ? 'type-fixed-badge' : 'type-variable-badge' }}">
                    {{ $expense->expense_type }}
                </span>
            </div>
            <div class="acc-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.expenses.index') }}">Expenses</a>
                <span class="sep">/</span>
                <span class="current">{{ $expense->expense_number }}</span>
            </div>
        </div>
        <div class="acc-topbar-actions">
            <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Expenses
            </a>
            <a href="{{ route('accounts.expenses.edit', $expense->id) }}" class="acc-btn acc-btn-primary">
                <i class="bi bi-pencil-square"></i> Edit Expense
            </a>
            <button type="button" class="acc-btn acc-btn-danger" onclick="document.getElementById('deleteModal').classList.add('active')">
                <i class="bi bi-trash"></i> Delete
            </button>
        </div>
    </div>

    <div class="acc-body">

    {{-- Layout Grid --}}
    <div class="acc-grid-layout">

        {{-- Left Main Column --}}
        <div style="display:flex; flex-direction:column; gap:20px;">

            {{-- Financial & Basic Info --}}
            <div class="acc-card">
                <div class="acc-card-title">
                    <span>Financial & Category Breakdown</span>
                    <span class="amount-hero">₹{{ number_format($expense->amount, 2) }}</span>
                </div>

                <div class="detail-row">
                    <span class="label">Expense Number</span>
                    <span class="value" style="color:#fe5f04;">{{ $expense->expense_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Expense Date</span>
                    <span class="value">{{ $expense->expense_date ? $expense->expense_date->format('l, d F Y') : '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Expense Type</span>
                    <span class="value">
                        <span class="type-badge {{ $expense->expense_type === 'Fixed' ? 'type-fixed-badge' : 'type-variable-badge' }}">
                            {{ $expense->expense_type }}
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Invoice / Bill No</span>
                    <span class="value">{{ $expense->invoice_no ?: '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Expense Category</span>
                    <span class="value" style="font-weight:800; color:#1e293b;">
                        {{ $expense->category?->name ?? '—' }}
                        @if($expense->category?->code)
                            <span style="font-size:11px; color:#64748b;">({{ $expense->category->code }})</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Expense Subcategory</span>
                    <span class="value">
                        @if($expense->subcategory)
                            {{ $expense->subcategory->name }}
                            @if($expense->subcategory->code)
                                <span style="font-size:11px; color:#64748b;">({{ $expense->subcategory->code }})</span>
                            @endif
                        @else
                            <span style="color:#94a3b8; font-style:italic;">None</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Recurring Schedule</span>
                    <span class="value">
                        @if($expense->is_recurring)
                            <span style="color:#15803d; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                                <i class="bi bi-arrow-repeat"></i> Monthly on Day {{ $expense->recurring_day_of_month ?? ($expense->expense_date ? $expense->expense_date->day : 1) }}
                            </span>
                            @if($expense->recurring_end_date)
                                <div style="font-size:11px; color:#64748b; font-weight:normal; margin-top:2px;">
                                    Repeats until {{ $expense->recurring_end_date->format('d M Y') }}
                                </div>
                            @else
                                <div style="font-size:11px; color:#166534; font-weight:normal; margin-top:2px;">
                                    Repeats indefinitely every month
                                </div>
                            @endif
                        @elseif($expense->auto_generated)
                            <span style="color:#6d28d9; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                                <i class="bi bi-robot"></i> Auto-Generated Record
                            </span>
                            @if($expense->parent)
                                <div style="font-size:11px; color:#64748b; font-weight:normal; margin-top:2px;">
                                    Generated from <a href="{{ route('accounts.expenses.show', $expense->parent_expense_id) }}" style="color:#6d28d9; font-weight:700;">Base #{{ $expense->parent->expense_number }}</a>
                                </div>
                            @endif
                        @else
                            <span style="color:#94a3b8; font-weight:normal;">Non-recurring (One-off)</span>
                        @endif
                    </span>
                </div>
            </div>

            {{-- Remarks --}}
            <div class="acc-card">
                <div class="acc-card-title">
                    <span>Remarks & Notes</span>
                </div>
                <div style="font-size:13px; color:#334155; line-height:1.6; white-space:pre-wrap;">{{ $expense->remarks ?: 'No remarks provided for this expense.' }}</div>
            </div>

            {{-- Attachments --}}
            @php $attList = $expense->attachment_list; @endphp
            <div class="acc-card">
                <div class="acc-card-title">
                    <span>Uploaded Attachments ({{ count($attList) }})</span>
                </div>

                @if(empty($attList))
                    <div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px;">
                        <i class="bi bi-paperclip" style="font-size:28px; display:block; margin-bottom:6px;"></i>
                        No receipts or documents attached to this expense.
                    </div>
                @else
                    {{-- Image Thumbnails if any --}}
                    @php $imageAtts = collect($attList)->where('is_image', true); @endphp
                    @if($imageAtts->isNotEmpty())
                        <div style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Image Previews</div>
                        <div class="att-thumb-grid">
                            @foreach($imageAtts as $img)
                                <a href="{{ $img['url'] }}" target="_blank" class="att-thumb-item">
                                    <img src="{{ $img['url'] }}" alt="{{ $img['name'] }}" class="att-thumb-img">
                                    <div class="att-thumb-meta" title="{{ $img['name'] }}">{{ $img['name'] }}</div>
                                </a>
                            @endforeach
                        </div>
                        <div style="height:16px;"></div>
                    @endif

                    {{-- All Attachments List --}}
                    <div style="font-size:12px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase;">All Files</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($attList as $att)
                            <div class="att-card">
                                <div style="display:flex; align-items:center; gap:10px; overflow:hidden;">
                                    <i class="bi {{ $att['is_image'] ? 'bi-image' : ($att['is_pdf'] ? 'bi-file-earmark-pdf' : 'bi-file-earmark-text') }}"
                                       style="color:#2563eb; font-size:20px; flex-shrink:0;"></i>
                                    <div>
                                        <div style="font-weight:700; font-size:13px; color:#0f172a;">{{ $att['name'] }}</div>
                                        <div style="font-size:11px; color:#64748b;">{{ $att['formatted_size'] }} • {{ strtoupper($att['extension']) }}</div>
                                    </div>
                                </div>
                                <a href="{{ $att['url'] }}" target="_blank" download class="acc-btn acc-btn-outline" style="padding:5px 12px; font-size:12px;">
                                    <i class="bi bi-box-arrow-up-right"></i> Open / Download
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        {{-- Right Side Column (Vendor & Audit) --}}
        <div style="display:flex; flex-direction:column; gap:20px;">

            {{-- Vendor Details --}}
            <div class="acc-card">
                <div class="acc-card-title">
                    <span>Vendor Details</span>
                </div>

                @if($expense->vendor)
                    <div class="detail-row">
                        <span class="label">Vendor Name</span>
                        <span class="value" style="color:#0f172a;">{{ $expense->vendor->vendor_name }}</span>
                    </div>
                    @if($expense->vendor->company_name)
                    <div class="detail-row">
                        <span class="label">Company Name</span>
                        <span class="value">{{ $expense->vendor->company_name }}</span>
                    </div>
                    @endif
                    @if($expense->vendor->mobile_number)
                    <div class="detail-row">
                        <span class="label">Phone / Mobile</span>
                        <span class="value">{{ $expense->vendor->mobile_number }}</span>
                    </div>
                    @endif
                    @if($expense->vendor->email)
                    <div class="detail-row">
                        <span class="label">Email</span>
                        <span class="value">{{ $expense->vendor->email }}</span>
                    </div>
                    @endif
                    @if($expense->vendor->gst_number)
                    <div class="detail-row">
                        <span class="label">GST Number</span>
                        <span class="value" style="font-family:monospace;">{{ $expense->vendor->gst_number }}</span>
                    </div>
                    @endif
                    @if($expense->vendor->bank_name)
                    <div class="detail-row">
                        <span class="label">Bank Name</span>
                        <span class="value">{{ $expense->vendor->bank_name }}</span>
                    </div>
                    @endif
                    @if($expense->vendor->account_number)
                    <div class="detail-row">
                        <span class="label">Account Number</span>
                        <span class="value" style="font-family:monospace;">{{ $expense->vendor->account_number }}</span>
                    </div>
                    @endif
                    @if($expense->vendor->ifsc_code)
                    <div class="detail-row">
                        <span class="label">IFSC Code</span>
                        <span class="value" style="font-family:monospace;">{{ $expense->vendor->ifsc_code }}</span>
                    </div>
                    @endif
                    @if($expense->vendor->address)
                    <div style="margin-top:10px; font-size:12px; color:#64748b; line-height:1.5;">
                        <span style="font-weight:700;">Address:</span> {{ $expense->vendor->address }}
                    </div>
                    @endif
                @else
                    <div style="text-align:center; padding:20px; color:#94a3b8; font-size:13px;">
                        <i class="bi bi-shop" style="font-size:24px; display:block; margin-bottom:4px;"></i>
                        No vendor linked to this expense.
                    </div>
                @endif
            </div>

            {{-- Audit Details --}}
            <div class="acc-card">
                <div class="acc-card-title">
                    <span>Audit Information</span>
                </div>

                <div class="detail-row">
                    <span class="label">Recorded By</span>
                    <span class="value">{{ $expense->creator?->name ?? 'System' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Created At</span>
                    <span class="value">{{ $expense->created_at ? $expense->created_at->format('d M Y, h:i A') : '—' }}</span>
                </div>
                @if($expense->updated_at && $expense->updated_at != $expense->created_at)
                <div class="detail-row">
                    <span class="label">Last Updated</span>
                    <span class="value">{{ $expense->updated_at->format('d M Y, h:i A') }}</span>
                </div>
                @endif
                @if($expense->updater)
                <div class="detail-row">
                    <span class="label">Updated By</span>
                    <span class="value">{{ $expense->updater->name }}</span>
                </div>
                @endif
            </div>

        </div>

    </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div style="font-size:16px; font-weight:800; color:#b91c1c; margin-bottom:8px;">
            <i class="bi bi-exclamation-triangle-fill"></i> Delete Expense
        </div>
        <p style="font-size:13px; color:#475569; margin:0 0 20px 0;">
            Are you sure you want to delete expense <strong>{{ $expense->expense_number }}</strong>? This record will be soft deleted.
        </p>
        <form method="POST" action="{{ route('accounts.expenses.destroy', $expense->id) }}">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acc-btn acc-btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="acc-btn acc-btn-danger">
                    <i class="bi bi-trash"></i> Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('deleteModal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) e.currentTarget.classList.remove('active');
});
</script>
@endpush
@endsection
