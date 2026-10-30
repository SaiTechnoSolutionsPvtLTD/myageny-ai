@extends('layouts.app')

@section('title', 'Zoho Quotation #' . $quotation->estimate_number . ' - Accounts')

@push('styles')
<style>
.zh-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.zh-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:64px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.zh-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.zh-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.zh-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.zh-breadcrumbs a:hover { color:#fe5f04; }
.zh-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.zh-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; max-width:1100px; margin:0 auto; width:100%; }

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

.zh-card { background:#fff; border-radius:12px; border:1px solid #e2e8f0; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,0.03); }
.zh-table { width:100%; border-collapse:collapse; font-size:13px; text-align:left; }
.zh-table th { background:#f8fafc; padding:12px 14px; font-size:11.5px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #e2e8f0; }
.zh-table td { padding:14px; border-bottom:1px solid #f1f5f9; color:#334155; }
</style>
@endpush

@section('content')
<div class="zh-page">
    <div class="zh-topbar">
        <div>
            <div class="zh-title">
                <i class="bi bi-file-earmark-text-fill" style="color:#fe5f04;"></i>
                Zoho Estimate #{{ $quotation->estimate_number }}
            </div>
            <div class="zh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.index') }}">Zoho Books</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.quotations') }}">Quotations</a>
                <span>/</span>
                <span>{{ $quotation->estimate_number }}</span>
            </div>
        </div>
        <div class="zh-actions">
            <a href="{{ route('accounts.zoho.quotations') }}" class="acm-btn acm-btn-secondary">
                &larr; Back to Quotations
            </a>
            @if($quotation->is_migrated_to_native && $quotation->migratedQuotation)
                <a href="{{ route('quotations.show', $quotation->migrated_native_quotation_id) }}" target="_blank" class="acm-btn acm-btn-outline" style="color:#15803d; border-color:#86efac; background:#f0fdf4;">
                    ✓ View in CRM (#{{ $quotation->migratedQuotation->quotation_no }})
                </a>
                @if($quotation->migratedQuotation->lead)
                    <a href="{{ route('leads.show', $quotation->migratedQuotation->lead->id) }}" target="_blank" class="acm-btn acm-btn-outline" style="color:#0284c7; border-color:#bae6fd; background:#f0f9ff;">
                        <i class="bi bi-person-check-fill"></i> View Mapped Lead ({{ $quotation->migratedQuotation->lead->contact_name ?: $quotation->migratedQuotation->lead->company_name }})
                    </a>
                @else
                    <button type="button" onclick="openMapLeadModal()" class="acm-btn acm-btn-outline" style="color:#d97706; border-color:#fde68a; background:#fefce8;" title="Map this quotation to a Lead">
                        <i class="bi bi-link-45deg"></i> Map to Lead
                    </button>
                @endif
            @else
                <form action="{{ route('accounts.zoho.quotations.migrate', $quotation->id) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="acm-btn acm-btn-primary">
                        Migrate to CRM Quotation
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="zh-body">
        @if(session('success'))
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Main Estimate Card --}}
        <div class="zh-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #e2e8f0; padding-bottom:18px; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
                <div>
                    <div style="font-size:20px; font-weight:800; color:#0f172a;">{{ $quotation->estimate_number }}</div>
                    <div style="font-size:13px; color:#64748b; margin-top:2px;">
                        Zoho Estimate ID: <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;">{{ $quotation->zoho_estimate_id }}</code>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-block; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700; text-transform:uppercase; background:{{ in_array($quotation->status, ['accepted', 'invoiced']) ? '#dcfce7' : ($quotation->status === 'sent' ? '#e0f2fe' : '#f1f5f9') }}; color:{{ in_array($quotation->status, ['accepted', 'invoiced']) ? '#15803d' : ($quotation->status === 'sent' ? '#0369a1' : '#475569') }};">
                        {{ $quotation->status }}
                    </span>
                    @if($quotation->is_migrated_to_native)
                        <span style="display:inline-block; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:700; background:#dcfce7; color:#15803d;">
                            ✓ Migrated to CRM
                        </span>
                    @endif
                </div>
            </div>

            {{-- Info Grid --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:24px;">
                <div>
                    <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; color:#64748b;">Customer</div>
                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-top:3px;">{{ $quotation->customer_name }}</div>
                    @if($quotation->company_name && $quotation->company_name !== $quotation->customer_name)
                        <div style="font-size:12px; color:#64748b;">{{ $quotation->company_name }}</div>
                    @endif
                </div>
                <div>
                    <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; color:#64748b;">Estimate Date</div>
                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-top:3px;">
                        {{ $quotation->estimate_date ? $quotation->estimate_date->format('d M, Y') : '—' }}
                    </div>
                </div>
                <div>
                    <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; color:#64748b;">Valid Until (Due Date)</div>
                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-top:3px;">
                        @if($quotation->expiry_date)
                            {{ $quotation->expiry_date->format('d M, Y') }}
                        @elseif($quotation->migratedQuotation && $quotation->migratedQuotation->valid_until)
                            {{ $quotation->migratedQuotation->valid_until->format('d M, Y') }}
                        @elseif($quotation->estimate_date)
                            {{ $quotation->estimate_date->copy()->addDays(7)->format('d M, Y') }}
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div>
                    <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; color:#64748b;">Reference #</div>
                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-top:3px;">
                        {{ $quotation->reference_number ?: '—' }}
                    </div>
                </div>
            </div>

            {{-- Line Items Table --}}
            <div style="margin-bottom:24px;">
                <div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:10px; text-transform:uppercase; letter-spacing:0.04em;">
                    Line Items
                </div>
                <div style="overflow-x:auto; border:1px solid #e2e8f0; border-radius:8px;">
                    <table class="zh-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Item & Description</th>
                                <th style="text-align:right; width:80px;">Qty</th>
                                <th style="text-align:right; width:120px;">Rate</th>
                                <th style="text-align:right; width:100px;">Discount</th>
                                <th style="text-align:right; width:140px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $items = $quotation->line_items ?? ($quotation->raw_data['line_items'] ?? []);
                            @endphp
                            @forelse($items as $idx => $it)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <div style="font-weight:700; color:#0f172a;">{{ $it['name'] ?? ($it['description'] ?? 'Item') }}</div>
                                        @if(!empty($it['description']) && ($it['name'] ?? '') !== $it['description'])
                                            <div style="font-size:11.5px; color:#64748b;">{{ $it['description'] }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">{{ (float)($it['quantity'] ?? 1) }}</td>
                                    <td style="text-align:right;">₹{{ number_format((float)($it['rate'] ?? 0), 2) }}</td>
                                    <td style="text-align:right;">{{ (float)($it['discount'] ?? 0) }}%</td>
                                    <td style="text-align:right; font-weight:700;">₹{{ number_format((float)($it['item_total'] ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:20px; color:#94a3b8;">
                                        No itemized line items recorded. (Subtotal: ₹{{ number_format((float)$quotation->sub_total, 2) }})
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr style="background:#f8fafc; font-weight:700;">
                                <td colspan="5" style="text-align:right; padding:10px 14px;">Sub Total:</td>
                                <td style="text-align:right; padding:10px 14px;">₹{{ number_format((float)$quotation->sub_total, 2) }}</td>
                            </tr>
                            <tr style="background:#f8fafc; font-weight:700;">
                                <td colspan="5" style="text-align:right; padding:10px 14px;">Tax:</td>
                                <td style="text-align:right; padding:10px 14px;">₹{{ number_format((float)$quotation->tax_total, 2) }}</td>
                            </tr>
                            <tr style="background:#fff7ed; font-weight:800; font-size:14px; color:#fe5f04;">
                                <td colspan="5" style="text-align:right; padding:12px 14px;">Total Amount:</td>
                                <td style="text-align:right; padding:12px 14px;">₹{{ number_format((float)$quotation->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($quotation->is_migrated_to_native && $quotation->migratedQuotation)
{{-- MODAL: Map Quotation to Lead --}}
<div id="mapLeadModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:94%; max-width:520px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.25);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
            <div style="font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-person-check-fill" style="color:#2563eb;"></i>
                Map Quotation #{{ $quotation->migratedQuotation->quotation_no }} to CRM Lead
            </div>
            <button type="button" onclick="closeMapLeadModal()" style="border:none; background:none; font-size:22px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('quotations.map-lead', $quotation->migrated_native_quotation_id) }}" style="margin:0;">
            @csrf
            @method('PATCH')
            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="font-size:12px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">Search Lead by Name, Company, Phone, or ID:</label>
                    <input type="text" id="quoteLeadSearchBox" placeholder="Type name, company, or phone..." onkeyup="searchLeadsForQuoteModal(this.value)" autocomplete="off" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:12px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">Select Matching Lead: <span style="color:#ef4444;">*</span></label>
                    <select name="lead_id" id="modalQuoteLeadSelect" required size="5" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; box-sizing:border-box; min-height:120px;">
                        <option value="" disabled selected>— Type in the search box above to find leads —</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:14px 20px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                <button type="button" onclick="closeMapLeadModal()" class="acm-btn acm-btn-secondary" style="padding:8px 16px; font-size:13px;">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary" style="padding:8px 18px; font-size:13px; font-weight:700;">Save Mapping</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMapLeadModal() {
    document.getElementById('mapLeadModal').style.display = 'flex';
    document.getElementById('quoteLeadSearchBox').focus();
}
function closeMapLeadModal() {
    document.getElementById('mapLeadModal').style.display = 'none';
}
let quoteLeadSearchTimeout = null;
function searchLeadsForQuoteModal(q) {
    clearTimeout(quoteLeadSearchTimeout);
    quoteLeadSearchTimeout = setTimeout(() => {
        if (!q || q.length < 2) return;
        fetch('{{ route('api.leads.search') }}?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('modalQuoteLeadSelect');
                sel.innerHTML = '';
                if (!data.results || !data.results.length) {
                    sel.innerHTML = '<option value="" disabled>No leads found matching "' + q + '"</option>';
                    return;
                }
                data.results.forEach((item, idx) => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.text = item.text;
                    if (idx === 0) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
    }, 250);
}
</script>
@endif
@endsection
