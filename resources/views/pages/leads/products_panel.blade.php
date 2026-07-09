{{-- ================================================================
     FILE: resources/views/leads/partials/_products_panel.blade.php

     ROOT CAUSE FIX:
     The previous version used DOMContentLoaded inside an IIFE.
     Because this partial is rendered INSIDE the page body, the
     DOMContentLoaded event has already fired by the time the script
     runs — so the callback never executed and buttons had no listeners.

     FIX: Wire buttons directly with onclick attributes that call
     named functions. The IIFE defines all functions on window.*
     BEFORE any button can be clicked, so they are always available.
================================================================ --}}

@php
    $totalValue   = $lead->products->sum('total_price');
    $totalPaid    = $lead->products->sum(fn($p) => $p->total_paid);


    $totalPending = $totalValue - $totalPaid;
    $prodCount    = $lead->products->count();
@endphp

{{-- ═══════════════ STYLES ═══════════════ --}}
<style>
.pp-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.pp-sum-card{background:#fff;border:1px solid #e1dee3;border-radius:12px;padding:14px 16px;position:relative;overflow:hidden}
.pp-sum-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.pp-sum-card.pp-total::before{background:#fe5f04}
.pp-sum-card.pp-paid::before{background:#16a34a}
.pp-sum-card.pp-pending::before{background:#dc2626}
.pp-sum-card.pp-count::before{background:#7c3aed}
.pp-sum-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#9e9e9e;margin-bottom:6px}
.pp-sum-value{font-size:22px;font-weight:800;color:#121212;line-height:1}
.pp-sum-sub{font-size:11px;color:#9e9e9e;margin-top:4px}

.pp-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.pp-toolbar-title{font-size:15px;font-weight:700;color:#121212}

.pp-prod-grid{display:flex;flex-direction:column;gap:14px}
.pp-prod-card{background:#fff;border:1px solid #e1dee3;border-radius:14px;overflow:hidden;transition:box-shadow .2s}
.pp-prod-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.07)}

.pp-prod-header{display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #f7f6f9}
.pp-prod-icon{width:40px;height:40px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:18px}
.pp-prod-name{font-size:15px;font-weight:700;color:#121212}
.pp-prod-desc{font-size:11px;color:#9e9e9e;margin-top:2px}
.pp-prod-right{margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}

.pp-status-select-wrap{position:relative;display:inline-flex}
.pp-status-select{appearance:none;-webkit-appearance:none;padding:5px 26px 5px 10px;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;border:1px solid transparent;font-family:inherit;outline:none;transition:all .15s}
.pp-status-caret{position:absolute;right:8px;top:50%;transform:translateY(-50%);pointer-events:none;width:10px;height:10px}

.pp-amounts-row{display:grid;grid-template-columns:repeat(4,1fr)}
.pp-amt-item{padding:12px 18px;border-right:1px solid #f7f6f9}
.pp-amt-item:last-child{border-right:none}
.pp-amt-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#9e9e9e;margin-bottom:4px}
.pp-amt-value{font-size:16px;font-weight:800;color:#121212}

.pp-progress-wrap{padding:0 18px 12px}
.pp-progress-bar-outer{height:6px;background:#f0eef2;border-radius:3px;overflow:hidden}
.pp-progress-bar-inner{height:100%;border-radius:3px;transition:width .6s ease}
.pp-progress-label{display:flex;justify-content:space-between;font-size:10px;color:#9e9e9e;margin-top:4px}

.pp-prod-footer{display:flex;align-items:center;gap:8px;padding:10px 18px;background:#fafafa;border-top:1px solid #f7f6f9}
.pp-footer-actions{display:flex;align-items:center;gap:6px;margin-left:auto}
.pp-act-btn{display:flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;transition:all .15s;border:1px solid transparent}
.pp-btn-pay{background:#fff0e6;color:#fe5f04;border-color:#fed7aa}
.pp-btn-pay:hover{background:#fed7aa}
.pp-btn-edit{background:#f0fdf4;color:#15803d;border-color:#bbf7d0}
.pp-btn-edit:hover{background:#dcfce7}
.pp-btn-hist{background:#eef2ff;color:#4f46e5;border-color:#c7d2fe}
.pp-btn-hist:hover{background:#c7d2fe}
.pp-btn-del{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.pp-btn-del:hover{background:#fecaca}

/* ── Modal Overlay ── */
.pp-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(14,10,20,.55);backdrop-filter:blur(6px);align-items:center;justify-content:center}
.pp-overlay.pp-show{display:flex}
.pp-modal-box{background:#fff;border-radius:18px;width:90%;max-width:580px;max-height:90vh;overflow-y:auto;box-shadow:0 32px 80px rgba(0,0,0,.22);animation:ppIn .22s cubic-bezier(.4,0,.2,1)}
.pp-modal-box--wide{max-width:860px}
@keyframes ppIn{from{opacity:0;transform:scale(.95) translateY(16px)}to{opacity:1;transform:scale(1) translateY(0)}}
.pp-mhd{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f0eef2;position:sticky;top:0;background:#fff;z-index:1}
.pp-mtitle{font-size:16px;font-weight:800;color:#121212;display:flex;align-items:center;gap:8px}
.pp-mclose{width:32px;height:32px;border-radius:9px;background:#f5f4f6;border:none;cursor:pointer;color:#7c7c7c;display:flex;align-items:center;justify-content:center;font-size:18px;line-height:1;transition:all .15s}
.pp-mclose:hover{background:#e1dee3;color:#121212}
.pp-mbody{padding:20px 22px}
.pp-mmeta{padding:12px 22px;background:#fafafa;border-bottom:1px solid #f0eef2}
.pp-mmeta-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.pp-mmeta-lbl{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:#9e9e9e;margin-bottom:3px}
.pp-mmeta-val{font-size:15px;font-weight:800}
.pp-mfoot{display:flex;gap:10px;padding:14px 22px;border-top:1px solid #f0eef2;background:#fafafa;position:sticky;bottom:0}

/* ── Form ── */
.ppf-grp{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.ppf-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#3d3d3d}
.ppf-req{color:#dc2626}
.ppf-rel{position:relative}
.ppf-ico{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9e9e9e;pointer-events:none;width:13px;height:13px}
.ppf-inp,.ppf-sel,.ppf-ta{width:100%;padding:9px 12px 9px 32px;border:1px solid #e1dee3;border-radius:9px;font-size:13px;font-family:inherit;color:#121212;background:#fafafa;outline:none;transition:all .15s}
.ppf-inp::placeholder,.ppf-ta::placeholder{color:#b8b3aa}
.ppf-inp:focus,.ppf-sel:focus,.ppf-ta:focus{border-color:#fe5f04;background:#fff;box-shadow:0 0 0 3px rgba(254,95,4,.1)}
.ppf-inp.ni,.ppf-sel.ni{padding-left:12px}
.ppf-sel{appearance:none;-webkit-appearance:none;cursor:pointer}
.ppf-caret{position:absolute;right:9px;top:50%;transform:translateY(-50%);pointer-events:none;color:#9e9e9e;width:11px;height:11px}
.ppf-ta{resize:vertical;min-height:70px;padding:8px 12px}
.ppf-r2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.ppf-r3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}

.ppf-total-box{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;margin-bottom:14px}
.ppf-total-lbl{font-size:12px;font-weight:700;color:#16a34a}
.ppf-total-val{font-size:18px;font-weight:800;color:#16a34a}

/* Mode tiles */
.ppf-mode-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin-bottom:14px}
.ppf-mode-tile{display:flex;flex-direction:column;align-items:center;gap:4px;padding:9px 6px;border-radius:10px;border:1.5px solid #e1dee3;cursor:pointer;transition:all .15s;font-size:10px;font-weight:700;color:#7c7c7c;user-select:none}
.ppf-mode-tile:hover,.ppf-mode-tile.pp-sel{border-color:#fe5f04;background:#fff0e6;color:#fe5f04}
.ppf-mode-ico{font-size:18px}

/* Status tiles */
.ppf-status-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin-bottom:14px}
.ppf-status-tile{display:flex;flex-direction:column;align-items:center;gap:4px;padding:9px 4px;border-radius:10px;border:1.5px solid #e1dee3;cursor:pointer;transition:all .15s;text-align:center;user-select:none}
.ppf-status-ico{font-size:18px}
.ppf-status-lbl{font-size:10px;font-weight:700;color:#7c7c7c}

/* Buttons */
.ppf-btn{display:flex;align-items:center;gap:6px;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;border:none}
.ppf-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);color:#fff;box-shadow:0 4px 12px rgba(254,95,4,.25);flex:1;justify-content:center}
.ppf-btn-primary:hover{transform:translateY(-1px)}
.ppf-btn-green{background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;box-shadow:0 4px 12px rgba(22,163,74,.25);flex:1;justify-content:center}
.ppf-btn-green:hover{transform:translateY(-1px)}
.ppf-btn-sec{background:#f5f4f6;color:#7c7c7c;border:1px solid #e1dee3}
.ppf-btn-sec:hover{background:#e1dee3}

/* History */
.pp-hist-totals{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;padding:12px 14px;background:linear-gradient(135deg,#f8f9fe,#f0fdf4);border-radius:12px;margin-bottom:14px}
.pp-htl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#9e9e9e;margin-bottom:3px}
.pp-htv{font-size:16px;font-weight:800}
.pp-hist-prog{margin-bottom:14px}
.pp-hist-pbar{height:6px;background:#f0eef2;border-radius:3px;overflow:hidden;margin-bottom:4px}
.pp-hist-pfill{height:100%;border-radius:3px;transition:width .5s ease}
.pp-hist-plabels{display:flex;justify-content:space-between;font-size:10px;color:#9e9e9e}
.pp-hist-list{display:flex;flex-direction:column;gap:10px;max-height:450px;overflow-y:auto;padding-right:6px}
.pp-hist-item{display:flex;align-items:center;gap:12px;padding:12px 14px;background:#fff;border:1px solid #e1dee3;border-radius:11px}
.pp-hist-item:hover{border-color:#d4cfd8}
.pp-hist-mode-wrap{width:38px;height:38px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:17px}
.pp-hist-info{flex:1;min-width:0}
.pp-hist-mname{font-size:13px;font-weight:700;color:#121212}
.pp-hist-date{font-size:11px;color:#9e9e9e;margin-top:2px}
.pp-hist-ref{font-family:monospace;font-size:10px;color:#9e9e9e;margin-top:1px}
.pp-hist-note{font-size:11px;color:#7c7c7c;margin-top:2px}
.pp-hist-right{text-align:right;flex-shrink:0}
.pp-hist-amt{font-size:16px;font-weight:800;color:#16a34a}
.pp-hist-run{font-size:10px;color:#9e9e9e;margin-top:2px}
.pp-hist-del{background:none;border:none;cursor:pointer;color:#9e9e9e;padding:5px;border-radius:6px;transition:color .15s;flex-shrink:0}
.pp-hist-del:hover{color:#dc2626}
.pp-hist-empty{text-align:center;padding:32px 20px;color:#9e9e9e}
.pp-hist-empty-ico{font-size:36px;margin-bottom:8px}
</style>

{{-- ═══════════════ SUMMARY BAR ═══════════════ --}}
<div class="pp-summary">
    <div class="pp-sum-card pp-total">
        <div class="pp-sum-label">Total Products Value</div>
        <div class="pp-sum-value">₹{{ number_format($totalValue,2) }}</div>
        <div class="pp-sum-sub">{{ $prodCount }} product(s)</div>
    </div>
    <div class="pp-sum-card pp-paid">
        <div class="pp-sum-label">Amount Received</div>
        <div class="pp-sum-value" style="color:#16a34a">₹{{ number_format($totalPaid,2) }}</div>
        <div class="pp-sum-sub" style="color:#16a34a">Collected so far</div>
    </div>
    <div class="pp-sum-card pp-pending">
        <div class="pp-sum-label">Amount Pending</div>
        <div class="pp-sum-value" style="color:{{ $totalPending>0?'#dc2626':'#16a34a' }}">₹{{ number_format($totalPending,2) }}</div>
        <div class="pp-sum-sub" style="color:{{ $totalPending>0?'#dc2626':'#16a34a' }}">{{ $totalPending>0?'Outstanding':'Fully Settled ✓' }}</div>
    </div>
    <div class="pp-sum-card pp-count">
        <div class="pp-sum-label">Converted</div>
        <div class="pp-sum-value" style="color:#7c3aed">{{ $lead->products->where('product_status','converted')->count() }}</div>
        <div class="pp-sum-sub">of {{ $prodCount }} total</div>
    </div>
</div>

{{-- ═══════════════ TOOLBAR ═══════════════ --}}
<div class="pp-toolbar">
    <div class="pp-toolbar-title">📦 Products</div>
    {{-- onclick directly calls the global function — always works --}}
    <button type="button" class="pp-act-btn pp-btn-pay" onclick="ppShowAddProduct()">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Product
    </button>
</div>

{{-- ═══════════════ PRODUCT CARDS ═══════════════ --}}
@if($lead->products->isEmpty())
<div style="background:#fff;border:1px solid #e1dee3;border-radius:14px;padding:50px 20px;text-align:center;color:#9e9e9e">
    <div style="font-size:40px;margin-bottom:10px">📦</div>
    <div style="font-size:15px;font-weight:700;color:#7c7c7c;margin-bottom:5px">No products added yet</div>
    <div style="font-size:13px">Click "Add Product" to get started</div>
</div>
@else
<div class="pp-prod-grid">
@foreach($lead->products as $prod)
@php

    $psc      = $prod->product_status_config;
    $pysc     = $prod->payment_status_color;
    $progress = $prod->payment_progress;
    $prgColor = $progress >= 100 ? '#16a34a' : ($progress > 0 ? '#fe5f04' : '#e1dee3');
@endphp
<div class="pp-prod-card">

    {{-- Header --}}
    <div class="pp-prod-header">
        <div class="pp-prod-icon" style="background:{{ $psc['bg'] }}">{{ $psc['icon'] }}</div>
        <div style="min-width:0;flex:1">
            <div class="pp-prod-name">{{ $prod->product_name }}</div>
            @if($prod->description)<div class="pp-prod-desc">{{ $prod->description }}</div>@endif
        </div>
        <div class="pp-prod-right">
            {{-- Status select (inline form, auto-submit) --}}
            <form method="POST" action="{{ route('leads.products.update-status',[$lead,$prod]) }}">
                @csrf @method('PATCH')
                <div class="pp-status-select-wrap">
                    <select name="product_status" class="pp-status-select"
                            style="background:{{ $psc['bg'] }};color:{{ $psc['text'] }};border-color:{{ $psc['border'] }}"
                            onchange="this.form.submit()">
                        @foreach(\App\Models\LeadProduct::PRODUCT_STATUSES as $sk => $sl)
                        @php $scfg = \App\Models\LeadProduct::PRODUCT_STATUS_CONFIG[$sk]; @endphp
                        <option value="{{ $sk }}" {{ $prod->product_status===$sk?'selected':'' }}>
                            {{ $scfg['icon'] }} {{ $sl }}
                        </option>
                        @endforeach
                    </select>
                    <svg class="pp-status-caret" style="color:{{ $psc['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </form>
            {{-- Payment pill --}}
            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $pysc['bg'] }};color:{{ $pysc['text'] }};border:1px solid {{ $pysc['border'] }}">{{ ucfirst($prod->payment_status) }}</span>
        </div>
    </div>

    {{-- Amounts --}}
    <div class="pp-amounts-row">
        <div class="pp-amt-item"><div class="pp-amt-label">Unit Price</div><div class="pp-amt-value">₹{{ number_format($prod->unit_price,2) }}</div></div>
        <div class="pp-amt-item"><div class="pp-amt-label">Qty × Disc</div><div class="pp-amt-value">{{ $prod->quantity }} × {{ $prod->discount_percent }}%</div></div>
        <div class="pp-amt-item"><div class="pp-amt-label">Total Value</div><div class="pp-amt-value">{{ $prod->formatted_total }}</div></div>
        <div class="pp-amt-item">
            <div class="pp-amt-label"><span style="color:#16a34a">Paid</span> / <span style="color:{{ $prod->amount_pending>0?'#dc2626':'#16a34a' }}">Pending</span></div>
            <div class="pp-amt-value">
                <span style="color:#16a34a">{{ $prod->formatted_paid }}</span>
                <span style="color:#9e9e9e;font-size:12px"> / </span>
                <span style="color:{{ $prod->amount_pending>0?'#dc2626':'#16a34a' }};font-size:14px">{{ $prod->formatted_pending }}</span>
            </div>
        </div>
    </div>

    {{-- Progress --}}
    <div class="pp-progress-wrap">
        <div class="pp-progress-bar-outer">
            <div class="pp-progress-bar-inner" style="width:{{ $progress }}%;background:{{ $prgColor }}"></div>
        </div>
        <div class="pp-progress-label">
            <span>{{ $prod->payments->count() }} payment(s)</span>
            <span style="color:{{ $prgColor }};font-weight:700">{{ $progress }}% collected</span>
        </div>
    </div>

    {{-- Footer --}}
    <div class="pp-prod-footer">
        <span style="font-size:11px;color:#9e9e9e">Created {{ $prod->created_at->format('d M Y') }}</span>
        <div class="pp-footer-actions">

            {{-- ▼ KEY FIX: direct onclick with product id passed inline ▼ --}}
            <button type="button"
                    class="pp-act-btn pp-btn-pay"
                    onclick="ppShowPayment({{ $prod->id }})">

                Add Payment
            </button>

            <button type="button"
                    class="pp-act-btn pp-btn-edit"
                    onclick="ppShowEditProduct({{ $prod->id }})">
                Edit
            </button>

            <button type="button"
                    class="pp-act-btn pp-btn-hist"
                    onclick="ppShowHistory({{ $prod->id }})">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="12 8 12 12 14 14"/><circle cx="12" cy="12" r="10"/></svg>
                History ({{ $prod->payments->count() }})
            </button>

            <form method="POST" action="{{ route('leads.products.destroy',[$lead,$prod]) }}"
                  style="display:inline"
                  onsubmit="return confirm('Remove {{ addslashes($prod->product_name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="pp-act-btn pp-btn-del">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                </button>
            </form>
        </div>
    </div>

</div>
@endforeach
</div>
@endif


{{-- ═══════════════ MODAL 1: ADD PRODUCT ═══════════════ --}}
<div class="pp-overlay" id="pp-modal-add-product">
    <div class="pp-modal-box">
        <div class="pp-mhd">
            <div class="pp-mtitle">📦 Add Product to Lead</div>
            <button type="button" class="pp-mclose" onclick="ppHideModal('pp-modal-add-product')">✕</button>
        </div>
        <form method="POST" action="{{ route('leads.products.store',$lead) }}">
            @csrf
            <div class="pp-mbody">

                <div class="ppf-grp">
                    <label class="ppf-lbl">Product Name <span class="ppf-req">*</span></label>
                    <div class="ppf-rel">
                        <svg class="ppf-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8"/></svg>

                        <select class="form-select select2" name="product_id" id="pp-product-select" required>
                            @php
                                $allProducts = App\Models\Product::where('status', 'active')->get();
                            @endphp
                            <option value="" selected>-- Choose Products --</option>
                            @if($allProducts)
                                @foreach ($allProducts as $allProduct)
                                    @php
                                        $discountPercent = $allProduct->discount_type === 'percentage'
                                            ? (float) $allProduct->discount_value
                                            : ((float) $allProduct->base_price > 0
                                                ? round(((float) $allProduct->discount_value / (float) $allProduct->base_price) * 100, 2)
                                                : 0);
                                    @endphp
                                    <option
                                        value="{{ $allProduct->id }}"
                                        data-description="{{ $allProduct->description ?? '' }}"
                                        data-unit-price="{{ (float) ($allProduct->final_price ?? $allProduct->base_price ?? 0) }}"
                                        data-discount-percent="{{ $discountPercent }}"
                                    >{{ $allProduct->category?->name }} | {{ $allProduct->package_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="ppf-grp">
                    <label class="ppf-lbl">Description</label>
                    <textarea name="description" class="ppf-ta" placeholder="Optional details…" rows="2"></textarea>
                </div>

                {{-- Status tiles --}}
                <div class="ppf-grp">
                    <label class="ppf-lbl">Product Status <span class="ppf-req">*</span></label>
                    <div class="ppf-status-grid">
                        @foreach(\App\Models\LeadProduct::PRODUCT_STATUS_CONFIG as $sk => $scfg)
                        <div class="ppf-status-tile {{ $sk==='new'?'pp-sel':'' }}"
                             style="{{ $sk==='new'?'border-color:'.$scfg['border'].';background:'.$scfg['bg']:'' }}"
                             data-val="{{ $sk }}"
                             data-bg="{{ $scfg['bg'] }}"
                             data-text="{{ $scfg['text'] }}"
                             data-border="{{ $scfg['border'] }}"
                             onclick="ppPickStatus(this)">
                            <div class="ppf-status-ico">{{ $scfg['icon'] }}</div>
                            <div class="ppf-status-lbl" style="{{ $sk==='new'?'color:'.$scfg['text']:'' }}">
                                {{ \App\Models\LeadProduct::PRODUCT_STATUSES[$sk] }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="product_status" id="pp-status-val" value="new">
                </div>

                {{-- Pricing --}}
                <div class="ppf-r3" style="margin-bottom:14px">
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Unit Price ₹ <span class="ppf-req">*</span></label>
                        <input type="number" name="unit_price" id="pp-price" class="ppf-inp ni" placeholder="0.00" step="0.01" min="0" required oninput="ppCalc()">
                    </div>
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Quantity <span class="ppf-req">*</span></label>
                        <input type="number" name="quantity" id="pp-qty" class="ppf-inp ni" value="1" min="1" required oninput="ppCalc()">
                    </div>
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Discount %</label>
                        <input type="number" name="discount_percent" id="pp-disc" class="ppf-inp ni" value="0" min="0" max="100" oninput="ppCalc()">
                    </div>
                </div>

                <div class="ppf-total-box">
                    <span class="ppf-total-lbl">Computed Total</span>
                    <span class="ppf-total-val" id="pp-total-preview">₹0.00</span>
                </div>

            </div>
            <div class="pp-mfoot">
                <button type="submit" class="ppf-btn ppf-btn-primary">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Add Product
                </button>
                <button type="button" class="ppf-btn ppf-btn-sec" onclick="ppHideModal('pp-modal-add-product')">Cancel</button>
            </div>
        </form>
    </div>
</div>


{{-- ═══════════════ MODAL 2: ADD PAYMENT ═══════════════ --}}
<div class="pp-overlay" id="pp-modal-edit-product">
    <div class="pp-modal-box">
        <div class="pp-mhd">
            <div class="pp-mtitle">Edit Product — <span id="pp-edit-name" style="color:#15803d"></span></div>
            <button type="button" class="pp-mclose" onclick="ppHideModal('pp-modal-edit-product')">✕</button>
        </div>
        <form id="pp-edit-form" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="pp-mbody">
                <div class="ppf-grp">
                    <label class="ppf-lbl">Product Name <span class="ppf-req">*</span></label>
                    <select class="form-select select2" name="product_id" id="pp-edit-product-select" required>
                        <option value="">-- Choose Products --</option>
                        @foreach($allProducts as $allProduct)
                            @php
                                $discountPercent = $allProduct->discount_type === 'percentage'
                                    ? (float) $allProduct->discount_value
                                    : ((float) $allProduct->base_price > 0
                                        ? round(((float) $allProduct->discount_value / (float) $allProduct->base_price) * 100, 2)
                                        : 0);
                            @endphp
                            <option
                                value="{{ $allProduct->id }}"
                                data-description="{{ $allProduct->description ?? '' }}"
                                data-unit-price="{{ (float) ($allProduct->final_price ?? $allProduct->base_price ?? 0) }}"
                                data-discount-percent="{{ $discountPercent }}"
                            >{{ $allProduct->category?->name }} | {{ $allProduct->package_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ppf-grp">
                    <label class="ppf-lbl">Description</label>
                    <textarea name="description" id="pp-edit-description" class="ppf-ta" placeholder="Optional details…" rows="2"></textarea>
                </div>

                <div class="ppf-grp">
                    <label class="ppf-lbl">Product Status <span class="ppf-req">*</span></label>
                    <select name="product_status" id="pp-edit-status" class="ppf-inp" required>
                        @foreach(\App\Models\LeadProduct::PRODUCT_STATUSES as $sk => $sl)
                            <option value="{{ $sk }}">{{ $sl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ppf-r3" style="margin-bottom:14px">
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Unit Price ₹ <span class="ppf-req">*</span></label>
                        <input type="number" name="unit_price" id="pp-edit-price" class="ppf-inp ni" step="0.01" min="0" required oninput="ppEditCalc()">
                    </div>
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Quantity <span class="ppf-req">*</span></label>
                        <input type="number" name="quantity" id="pp-edit-qty" class="ppf-inp ni" min="1" required oninput="ppEditCalc()">
                    </div>
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Discount %</label>
                        <input type="number" name="discount_percent" id="pp-edit-disc" class="ppf-inp ni" min="0" max="100" oninput="ppEditCalc()">
                    </div>
                </div>

                <div class="ppf-total-box">
                    <span class="ppf-total-lbl">Computed Total</span>
                    <span class="ppf-total-val" id="pp-edit-total-preview">₹0.00</span>
                </div>
            </div>
            <div class="pp-mfoot">
                <button type="submit" class="ppf-btn ppf-btn-green">Save Changes</button>
                <button type="button" class="ppf-btn ppf-btn-sec" onclick="ppHideModal('pp-modal-edit-product')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="pp-overlay" id="pp-modal-payment">
    <div class="pp-modal-box">
        <div class="pp-mhd">
            <div class="pp-mtitle">💳 Record Payment — <span id="pp-pay-name" style="color:#fe5f04"></span></div>
            <button type="button" class="pp-mclose" onclick="ppHideModal('pp-modal-payment')">✕</button>
        </div>
        <div class="pp-mmeta">
            <div class="pp-mmeta-grid">
                <div><div class="pp-mmeta-lbl">Total Value</div><div class="pp-mmeta-val" id="pp-pay-total">₹0.00</div></div>
                <div><div class="pp-mmeta-lbl">Already Paid</div><div class="pp-mmeta-val" style="color:#16a34a" id="pp-pay-paid">₹0.00</div></div>
                <div><div class="pp-mmeta-lbl">Balance Due</div><div class="pp-mmeta-val" style="color:#dc2626" id="pp-pay-balance">₹0.00</div></div>
            </div>
        </div>
        <form id="pp-pay-form" method="POST" action="">
            @csrf
            <div class="pp-mbody">

                <div class="ppf-grp">
                    <label class="ppf-lbl">Amount Received ₹ <span class="ppf-req">*</span></label>
                    <div class="ppf-rel">
                        <input type="number" name="amount" id="pp-pay-amount" class="ppf-inp" placeholder="0.00" step="0.01" min="0.01" required>
                    </div>
                </div>

                <div class="ppf-grp">
                    <label class="ppf-lbl">Payment Mode <span class="ppf-req">*</span></label>
                    <div class="ppf-mode-grid">
                        @foreach(['cash'=>['💵','Cash'],'bank_transfer'=>['🏦','Bank Transfer'],'cheque'=>['📝','Cheque'],'upi'=>['📱','UPI'],'card'=>['💳','Card']] as $mk=>$mv)
                        <div class="ppf-mode-tile {{ $mk==='upi'?'pp-sel':'' }}" data-val="{{ $mk }}" onclick="ppPickMode(this)">
                            <div class="ppf-mode-ico">{{ $mv[0] }}</div>
                            {{ $mv[1] }}
                        </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="payment_mode" id="pp-mode-val" value="upi">
                </div>

                <div class="ppf-r2">
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Payment Date <span class="ppf-req">*</span></label>
                        <input type="date" name="payment_date" class="ppf-inp ni" value="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="ppf-grp" style="margin-bottom:0">
                        <label class="ppf-lbl">Reference / UTR No.</label>
                        <div class="ppf-rel">
                            <svg class="ppf-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                            <input type="text" name="reference_number" class="ppf-inp" placeholder="Txn ID / Cheque No.">
                        </div>
                    </div>
                </div>

                <div class="ppf-grp" style="margin-top:14px">
                    <label class="ppf-lbl">Notes</label>
                    <textarea name="notes" class="ppf-ta" placeholder="Optional note…" rows="2"></textarea>
                </div>

            </div>
            <div class="pp-mfoot">
                <button type="submit" class="ppf-btn ppf-btn-green">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Save Payment
                </button>
                <button type="button" class="ppf-btn ppf-btn-sec" onclick="ppHideModal('pp-modal-payment')">Cancel</button>
            </div>
        </form>
    </div>
</div>


{{-- ═══════════════ MODAL 3: PAYMENT HISTORY ═══════════════ --}}
<div class="pp-overlay" id="pp-modal-history">
    <div class="pp-modal-box pp-modal-box--wide">
        <div class="pp-mhd">
            <div class="pp-mtitle">📊 Payment History — <span id="pp-hist-name" style="color:#7c3aed"></span></div>
            <button type="button" class="pp-mclose" onclick="ppHideModal('pp-modal-history')">✕</button>
        </div>
        <div class="pp-mbody" id="pp-hist-body"></div>
        <div class="pp-mfoot">
            <button type="button" id="pp-hist-add-btn" class="ppf-btn ppf-btn-primary">

                Add Payment
            </button>
            <button type="button" class="ppf-btn ppf-btn-sec" onclick="ppHideModal('pp-modal-history')">Close</button>
        </div>
    </div>
</div>


{{-- ═══════════════ JAVASCRIPT ═══════════════
     IIFE runs immediately when the <script> tag
     is parsed — all functions are registered on
     window.* before any button click can happen.
     No DOMContentLoaded needed.
═══════════════════════════════════════════ --}}
<script>
(function () {

    /* ── 1. Build ppData from Blade ─────────────────────────────── */
    var ppData = {};

    @foreach($lead->products as $prod)
    ppData[{{ $prod->id }}] = {
        id       : {{ $prod->id }},
        name     : {!! json_encode($prod->product_name) !!},
        productId: {{ (int) $prod->product_id }},
        description: {!! json_encode($prod->description ?? '') !!},
        unitPrice: {{ (float) $prod->unit_price }},
        quantity : {{ (int) $prod->quantity }},
        discountPercent: {{ (float) $prod->discount_percent }},
        status   : {!! json_encode($prod->product_status) !!},
        total    : {{ (float) $prod->total_price }},
        paid     : {{ (float) $prod->total_paid }},
        pending  : {{ (float) $prod->amount_pending }},
        progress : {{ (int)   $prod->payment_progress }},
        updateUrl: {!! json_encode(route('leads.products.update', [$lead, $prod])) !!},
        payUrl   : {!! json_encode(route('leads.products.payments.store', [$lead, $prod])) !!},
        payments : [
            @foreach($prod->payments as $pmt)
            {
                id        : {{ $pmt->id }},
                amount    : {{ (float) $pmt->amount }},
                mode      : {!! json_encode($pmt->payment_mode) !!},
                modeLabel : {!! json_encode($pmt->mode_label) !!},
                modeIcon  : {!! json_encode($pmt->mode_icon) !!},
                modeColor : {!! json_encode($pmt->mode_color) !!},
                date      : {!! json_encode($pmt->payment_date->format('d M Y')) !!},
                ref       : {!! json_encode($pmt->reference_number ?? '') !!},
                notes     : {!! json_encode($pmt->notes ?? '') !!},
                by        : {!! json_encode($pmt->recordedBy?->name ?? 'System') !!},
                delUrl    : {!! json_encode(route('leads.products.payments.destroy', [$lead, $prod, $pmt])) !!}
            },
            @endforeach
        ]
    };
    @endforeach

    var csrf = {!! json_encode(csrf_token()) !!};

    /* ── 2. Modal open / close ──────────────────────────────────── */
    window.ppHideModal = function (id) {
        var el = document.getElementById(id);
        if (el) { el.classList.remove('pp-show'); document.body.style.overflow = ''; }
    };

    function ppShow (id) {
        var el = document.getElementById(id);
        if (el) { el.classList.add('pp-show'); document.body.style.overflow = 'hidden'; }
    }

    /* Close on backdrop click */
    ['pp-modal-add-product','pp-modal-edit-product','pp-modal-payment','pp-modal-history'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('click', function (e) { if (e.target === el) ppHideModal(id); });
    });

    /* Close on Escape */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            ['pp-modal-add-product','pp-modal-edit-product','pp-modal-payment','pp-modal-history'].forEach(ppHideModal);
        }
    });

    /* ── 3. Add Product modal ───────────────────────────────────── */
    window.ppShowAddProduct = function () { ppShow('pp-modal-add-product'); };

    /* Status tile picker */
    window.ppPickStatus = function (tile) {
        document.querySelectorAll('.ppf-status-tile').forEach(function (t) {
            t.classList.remove('pp-sel');
            t.style.borderColor = '#e1dee3';
            t.style.background  = '';
            var lbl = t.querySelector('.ppf-status-lbl');
            if (lbl) lbl.style.color = '#7c7c7c';
        });
        tile.classList.add('pp-sel');
        tile.style.borderColor = tile.dataset.border;
        tile.style.background  = tile.dataset.bg;
        var lbl = tile.querySelector('.ppf-status-lbl');
        if (lbl) lbl.style.color = tile.dataset.text;
        var inp = document.getElementById('pp-status-val');
        if (inp) inp.value = tile.dataset.val;
    };

    /* Live total calc */
    window.ppCalc = function () {
        var price = parseFloat(document.getElementById('pp-price').value)  || 0;
        var qty   = parseFloat(document.getElementById('pp-qty').value)    || 1;
        var disc  = parseFloat(document.getElementById('pp-disc').value)   || 0;
        var total = price * qty * (1 - disc / 100);
        var el    = document.getElementById('pp-total-preview');
        if (el) el.textContent = '₹' + total.toFixed(2);
    };

    /* ── 4. Payment mode tile picker ────────────────────────────── */
    window.ppEditCalc = function () {
        var price = parseFloat(document.getElementById('pp-edit-price').value)  || 0;
        var qty   = parseFloat(document.getElementById('pp-edit-qty').value)    || 1;
        var disc  = parseFloat(document.getElementById('pp-edit-disc').value)   || 0;
        var total = price * qty * (1 - disc / 100);
        var el    = document.getElementById('pp-edit-total-preview');
        if (el) el.textContent = '₹' + total.toFixed(2);
    };

    function ppApplySelectedProduct() {
        var select = document.getElementById('pp-product-select');
        if (!select) return;

        var option = select.options[select.selectedIndex];
        var priceInput = document.getElementById('pp-price');
        var discInput = document.getElementById('pp-disc');
        var descInput = document.querySelector('textarea[name="description"]');

        if (!option || !option.value) {
            if (priceInput) priceInput.value = '';
            if (discInput) discInput.value = 0;
            if (descInput) descInput.value = '';
            window.ppCalc();
            return;
        }

        if (priceInput) {
            priceInput.value = parseFloat(option.dataset.unitPrice || 0).toFixed(2);
        }
        if (discInput) {
            discInput.value = parseFloat(option.dataset.discountPercent || 0).toFixed(2);
        }
        if (descInput && !descInput.value.trim()) {
            descInput.value = option.dataset.description || '';
        }

        window.ppCalc();
    }

    var ppProductSelect = document.getElementById('pp-product-select');
    if (ppProductSelect) {
        ppProductSelect.addEventListener('change', ppApplySelectedProduct);
    }

    function ppApplySelectedEditProduct() {
        var select = document.getElementById('pp-edit-product-select');
        if (!select) return;

        var option = select.options[select.selectedIndex];
        var priceInput = document.getElementById('pp-edit-price');
        var discInput = document.getElementById('pp-edit-disc');
        var descInput = document.getElementById('pp-edit-description');

        if (!option || !option.value) return;

        if (priceInput) priceInput.value = parseFloat(option.dataset.unitPrice || 0).toFixed(2);
        if (discInput) discInput.value = parseFloat(option.dataset.discountPercent || 0).toFixed(2);
        if (descInput) descInput.value = option.dataset.description || '';

        window.ppEditCalc();
    }

    var ppEditProductSelect = document.getElementById('pp-edit-product-select');
    if (ppEditProductSelect) {
        ppEditProductSelect.addEventListener('change', ppApplySelectedEditProduct);
    }

    window.ppShowEditProduct = function (prodId) {
        var d = ppData[prodId];
        if (!d) { alert('Product data not found. Please refresh.'); return; }

        document.getElementById('pp-edit-name').textContent = d.name;
        document.getElementById('pp-edit-form').action = d.updateUrl;
        document.getElementById('pp-edit-product-select').value = d.productId;
        document.getElementById('pp-edit-description').value = d.description || '';
        document.getElementById('pp-edit-status').value = d.status || 'new';
        document.getElementById('pp-edit-price').value = Number(d.unitPrice || 0).toFixed(2);
        document.getElementById('pp-edit-qty').value = d.quantity || 1;
        document.getElementById('pp-edit-disc').value = Number(d.discountPercent || 0).toFixed(2);
        window.ppEditCalc();

        if (window.jQuery && jQuery.fn.select2) {
            jQuery('#pp-edit-product-select').trigger('change.select2');
        }

        ppShow('pp-modal-edit-product');
    };

    window.ppPickMode = function (tile) {
        document.querySelectorAll('.ppf-mode-tile').forEach(function (t) { t.classList.remove('pp-sel'); });
        tile.classList.add('pp-sel');
        var inp = document.getElementById('pp-mode-val');
        if (inp) inp.value = tile.dataset.val;
    };

    /* ── 5. Open Payment modal ──────────────────────────────────── */
    window.ppShowPayment = function (prodId) {
        var d = ppData[prodId];
        if (!d) { alert('Product data not found. Please refresh.'); return; }

        document.getElementById('pp-pay-name').textContent    = d.name;
        document.getElementById('pp-pay-total').textContent   = '₹' + d.total.toFixed(2);
        document.getElementById('pp-pay-paid').textContent    = '₹' + d.paid.toFixed(2);
        document.getElementById('pp-pay-balance').textContent = '₹' + d.pending.toFixed(2);

        var amt = document.getElementById('pp-pay-amount');
        if (amt) amt.value = d.pending > 0 ? d.pending.toFixed(2) : '';

        var frm = document.getElementById('pp-pay-form');
        if (frm) frm.action = d.payUrl;

        ppShow('pp-modal-payment');
    };

    /* ── 6. Open History modal ──────────────────────────────────── */
    window.ppShowHistory = function (prodId) {
        var d = ppData[prodId];
        if (!d) { alert('Product data not found. Please refresh.'); return; }

        document.getElementById('pp-hist-name').textContent = d.name;

        /* Wire the Add Payment button */
        var addBtn = document.getElementById('pp-hist-add-btn');
        if (addBtn) {
            addBtn.onclick = function () {
                ppHideModal('pp-modal-history');
                ppShowPayment(prodId);
            };
        }

        var progColor = d.progress >= 100 ? '#16a34a' : (d.progress > 0 ? '#fe5f04' : '#e1dee3');
        var html = '';

        /* Totals row */
        html += '<div class="pp-hist-totals">';
        html += '<div><div class="pp-htl">Total Value</div><div class="pp-htv">₹' + d.total.toFixed(2) + '</div></div>';
        html += '<div><div class="pp-htl">Collected</div><div class="pp-htv" style="color:#16a34a">₹' + d.paid.toFixed(2) + '</div></div>';
        html += '<div><div class="pp-htl">Pending</div><div class="pp-htv" style="color:' + (d.pending > 0 ? '#dc2626' : '#16a34a') + '">₹' + d.pending.toFixed(2) + '</div></div>';
        html += '</div>';

        /* Progress bar */
        html += '<div class="pp-hist-prog">';
        html += '<div class="pp-hist-pbar"><div class="pp-hist-pfill" style="width:' + d.progress + '%;background:' + progColor + '"></div></div>';
        html += '<div class="pp-hist-plabels"><span>' + d.payments.length + ' payment(s)</span>';
        html += '<span style="font-weight:700;color:' + progColor + '">' + d.progress + '% collected</span></div></div>';

        /* Payment list */
        if (d.payments.length === 0) {
            html += '<div class="pp-hist-empty"><div class="pp-hist-empty-ico">💸</div>';
            html += '<div style="font-size:14px;font-weight:700;color:#7c7c7c">No payments recorded yet</div>';
            html += '<div style="font-size:12px;color:#9e9e9e;margin-top:4px">Use "Add Payment" below.</div></div>';
        } else {
            var running = 0;
            html += '<div class="pp-hist-list">';
            d.payments.forEach(function (p) {
                running += p.amount;
                html += '<div class="pp-hist-item">';
                html += '<div class="pp-hist-mode-wrap" style="background:' + p.modeColor + '20">' + p.modeIcon + '</div>';
                html += '<div class="pp-hist-info">';
                html += '<div class="pp-hist-mname">'  + p.modeLabel + '</div>';
                html += '<div class="pp-hist-date">'   + p.date + ' · By ' + p.by + '</div>';
                if (p.ref)   html += '<div class="pp-hist-ref">Ref: '   + p.ref   + '</div>';
                if (p.notes) html += '<div class="pp-hist-note">'        + p.notes + '</div>';
                html += '</div>';
                html += '<div class="pp-hist-right">';
                html += '<div class="pp-hist-amt">₹' + p.amount.toFixed(2) + '</div>';
                html += '<div class="pp-hist-run">Cumulative: ₹' + running.toFixed(2) + '</div></div>';
                /* Delete mini-form */
                html += '<form method="POST" action="' + p.delUrl + '" style="display:inline" onsubmit="return confirm(\'Remove this payment?\')">';
                html += '<input type="hidden" name="_token" value="' + csrf + '">';
                html += '<input type="hidden" name="_method" value="DELETE">';
                html += '<button type="submit" class="pp-hist-del" title="Remove">';
                html += '<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>';
                html += '</button></form>';
                html += '</div>';
            });
            html += '</div>';
        }

        document.getElementById('pp-hist-body').innerHTML = html;
        ppShow('pp-modal-history');
    };

}()); /* end IIFE */
</script>
