@extends('layouts.app')

@section('title', 'Zoho Invoice #' . $invoice->invoice_number . ' - Accounts')

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
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Zoho Invoice #{{ $invoice->invoice_number }}
            </div>
            <div class="zh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.index') }}">Zoho Books</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.invoices') }}">Invoices</a>
                <span>/</span>
                <span>{{ $invoice->invoice_number }}</span>
            </div>
        </div>
        <div class="zh-actions">
            <a href="{{ route('accounts.zoho.invoices') }}" class="acm-btn acm-btn-secondary">
                &larr; Back to Invoices
            </a>
            @if($invoice->is_migrated_to_native && $invoice->migratedInvoice)
                <a href="{{ route('accounts.sales.invoices.show', $invoice->migrated_native_invoice_id) }}" target="_blank" class="acm-btn acm-btn-outline" style="color:#15803d; border-color:#86efac; background:#f0f9ff;">
                    ✓ View in CRM (#{{ $invoice->migratedInvoice->invoice_number }})
                </a>
                @if($invoice->migratedInvoice->lead)
                    <a href="{{ route('leads.show', $invoice->migratedInvoice->lead->id) }}" target="_blank" class="acm-btn acm-btn-outline" style="color:#0284c7; border-color:#bae6fd; background:#f0f9ff;">
                        <i class="bi bi-person-check-fill"></i> View Mapped Lead ({{ $invoice->migratedInvoice->lead->contact_name ?: $invoice->migratedInvoice->lead->company_name }})
                    </a>
                @else
                    <button type="button" onclick="openMapLeadModal()" class="acm-btn acm-btn-outline" style="color:#d97706; border-color:#fde68a; background:#fefce8;" title="Map this invoice to a Lead">
                        <i class="bi bi-link-45deg"></i> Map to Lead
                    </button>
                @endif
            @else
                <form action="{{ route('accounts.zoho.invoices.migrate', $invoice->id) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="acm-btn acm-btn-primary">
                        Migrate to CRM Invoice
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

        <!-- Invoice Header Card -->
        <div class="zh-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
                <div>
                    <div style="font-size:12px; text-transform:uppercase; font-weight:700; color:#fe5f04; letter-spacing:0.5px;">ZOHO BOOKS INVOICE</div>
                    <h2 style="font-size:24px; font-weight:900; color:#0f172a; margin:4px 0 6px;">{{ $invoice->invoice_number }}</h2>
                    <div style="font-size:13px; color:#64748b;">
                        Zoho ID: <code>{{ $invoice->zoho_invoice_id }}</code> &bull; Synced at: {{ $invoice->synced_at ? $invoice->synced_at->format('d M Y, h:i A') : '—' }}
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <span style="display:inline-block; padding:5px 12px; border-radius:8px; font-size:12px; font-weight:800; text-transform:uppercase; background:{{ $invoice->status === 'paid' ? '#dcfce7' : ($invoice->status === 'sent' ? '#e0f2fe' : '#fee2e2') }}; color:{{ $invoice->status === 'paid' ? '#15803d' : ($invoice->status === 'sent' ? '#0369a1' : '#b91c1c') }};">
                        {{ $invoice->status }}
                    </span>

                    @if($invoice->is_migrated_to_native)
                        <span style="display:inline-block; padding:5px 12px; border-radius:8px; font-size:12px; font-weight:800; background:#dcfce7; color:#15803d;">
                            ✓ Migrated to CRM
                        </span>
                    @else
                        <span style="display:inline-block; padding:5px 12px; border-radius:8px; font-size:12px; font-weight:800; background:#f1f5f9; color:#64748b;">
                            Not Migrated
                        </span>
                    @endif
                </div>
            </div>

            <hr style="margin:20px 0; border:0; border-top:1px solid #f1f5f9;">

            <!-- Customer & Address Grid -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                <div>
                    <div style="font-size:11.5px; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:4px;">Customer Info</div>
                    <div style="font-weight:700; font-size:14px; color:#0f172a;">{{ $invoice->customer_name }}</div>
                    @if($invoice->company_name && $invoice->company_name !== $invoice->customer_name)
                        <div style="font-size:12.5px; color:#475569;">{{ $invoice->company_name }}</div>
                    @endif
                    @if(!empty($invoice->raw_data['email']))
                        <div style="font-size:12.5px; color:#64748b; margin-top:2px;">{{ $invoice->raw_data['email'] }}</div>
                    @endif
                    @if(!empty($invoice->raw_data['phone']))
                        <div style="font-size:12.5px; color:#64748b;">{{ $invoice->raw_data['phone'] }}</div>
                    @endif
                </div>

                <div>
                    <div style="font-size:11.5px; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:4px;">Invoice Dates</div>
                    <div style="font-size:13px; color:#334155;"><strong>Date:</strong> {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : '—' }}</div>
                    <div style="font-size:13px; color:#334155; margin-top:3px;"><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</div>
                    @if($invoice->payment_terms)
                        <div style="font-size:12.5px; color:#64748b; margin-top:3px;">Terms: {{ $invoice->payment_terms }}</div>
                    @endif
                </div>

                <div>
                    <div style="font-size:11.5px; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:4px;">Billing Address</div>
                    @php $b = $invoice->billing_address ?? []; @endphp
                    @if(!empty($b))
                        <div style="font-size:12.5px; color:#334155; line-height:1.4;">
                            @if(!empty($b['attention'])) <strong>{{ $b['attention'] }}</strong><br> @endif
                            @if(!empty($b['address'])) {{ $b['address'] }}<br> @endif
                            {{ implode(', ', array_filter([$b['city'] ?? null, $b['state'] ?? null, $b['zip'] ?? null])) }}<br>
                            {{ $b['country'] ?? 'India' }}
                        </div>
                    @else
                        <div style="font-size:12.5px; color:#94a3b8;">No billing address recorded</div>
                    @endif
                </div>

                <div>
                    <div style="font-size:11.5px; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:4px;">Shipping Address</div>
                    @php $s = $invoice->shipping_address ?? []; @endphp
                    @if(!empty($s))
                        <div style="font-size:12.5px; color:#334155; line-height:1.4;">
                            @if(!empty($s['attention'])) <strong>{{ $s['attention'] }}</strong><br> @endif
                            @if(!empty($s['address'])) {{ $s['address'] }}<br> @endif
                            {{ implode(', ', array_filter([$s['city'] ?? null, $s['state'] ?? null, $s['zip'] ?? null])) }}<br>
                            {{ $s['country'] ?? 'India' }}
                        </div>
                    @else
                        <div style="font-size:12.5px; color:#94a3b8;">Same as billing</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items Card -->
        <div class="zh-card" style="padding:0; overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0;">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0;">Itemized Line Items</h3>
            </div>

            <div style="overflow-x:auto;">
                <table class="zh-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Item Name & Description</th>
                            <th>HSN/SAC</th>
                            <th style="text-align:right;">Qty</th>
                            <th style="text-align:right;">Rate</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lineItems = $invoice->line_items ?? []; @endphp
                        @if(empty($lineItems))
                            <tr>
                                <td>1</td>
                                <td>
                                    <div style="font-weight:700; color:#0f172a;">Consulting / Services</div>
                                    <div style="font-size:12px; color:#64748b;">Imported from Zoho Books</div>
                                </td>
                                <td>—</td>
                                <td style="text-align:right;">1</td>
                                <td style="text-align:right;">₹{{ number_format((float)$invoice->sub_total, 2) }}</td>
                                <td style="text-align:right; font-weight:700;">₹{{ number_format((float)$invoice->sub_total, 2) }}</td>
                            </tr>
                        @else
                            @foreach($lineItems as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div style="font-weight:700; color:#0f172a;">{{ $item['name'] ?? 'Item' }}</div>
                                    @if(!empty($item['description']))
                                        <div style="font-size:12px; color:#64748b; margin-top:2px;">{{ $item['description'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $item['hsn_or_sac'] ?? '—' }}</td>
                                <td style="text-align:right;">{{ $item['quantity'] ?? 1 }} {{ $item['unit'] ?? '' }}</td>
                                <td style="text-align:right;">₹{{ number_format((float)($item['rate'] ?? 0), 2) }}</td>
                                <td style="text-align:right; font-weight:700;">₹{{ number_format((float)($item['item_total'] ?? 0), 2) }}</td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Totals Section -->
            <div style="padding:20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <div style="width:280px; font-size:13.5px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; color:#475569;">
                        <span>Subtotal:</span>
                        <strong>₹{{ number_format((float)$invoice->sub_total, 2) }}</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; color:#475569;">
                        <span>Tax Total:</span>
                        <strong>₹{{ number_format((float)$invoice->tax_total, 2) }}</strong>
                    </div>
                    <hr style="border:0; border-top:1px solid #cbd5e1; margin:8px 0;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:16px; color:#0f172a; font-weight:900;">
                        <span>Grand Total:</span>
                        <span style="color:#fe5f04;">₹{{ number_format((float)$invoice->total, 2) }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; color:{{ $invoice->balance > 0 ? '#dc2626' : '#16a34a' }}; font-weight:800;">
                        <span>Balance Due:</span>
                        <span>₹{{ number_format((float)$invoice->balance, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raw Zoho Payload Accordion -->
        <div class="zh-card">
            <details>
                <summary style="font-weight:700; color:#334155; cursor:pointer; font-size:13.5px; user-select:none;">
                    View Raw Zoho API JSON Payload (Audit & Debug)
                </summary>
                <pre style="background:#0f172a; color:#f8fafc; padding:16px; border-radius:8px; font-size:12px; overflow-x:auto; margin-top:14px; max-height:400px;">{{ json_encode($invoice->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        </div>
    </div>
</div>

@if($invoice->is_migrated_to_native && $invoice->migratedInvoice)
{{-- MODAL: Map Invoice to Lead --}}
<div id="mapLeadModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:94%; max-width:520px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.25);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
            <div style="font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-person-check-fill" style="color:#2563eb;"></i>
                Map Invoice #{{ $invoice->migratedInvoice->invoice_number }} to CRM Lead
            </div>
            <button type="button" onclick="closeMapLeadModal()" style="border:none; background:none; font-size:22px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.sales.invoices.map-lead', $invoice->migrated_native_invoice_id) }}" style="margin:0;">
            @csrf
            @method('PATCH')
            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="font-size:12px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">Search Lead by Name, Company, Phone, or ID:</label>
                    <input type="text" id="invLeadSearchBox" placeholder="Type name, company, or phone..." onkeyup="searchLeadsForInvModal(this.value)" autocomplete="off" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:12px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">Select Matching Lead: <span style="color:#ef4444;">*</span></label>
                    <select name="lead_id" id="modalInvLeadSelect" required size="5" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; box-sizing:border-box; min-height:120px;">
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
    document.getElementById('invLeadSearchBox').focus();
}
function closeMapLeadModal() {
    document.getElementById('mapLeadModal').style.display = 'none';
}
let invLeadSearchTimeout = null;
function searchLeadsForInvModal(q) {
    clearTimeout(invLeadSearchTimeout);
    invLeadSearchTimeout = setTimeout(() => {
        if (!q || q.length < 2) return;
        fetch('{{ route('api.leads.search') }}?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('modalInvLeadSelect');
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
