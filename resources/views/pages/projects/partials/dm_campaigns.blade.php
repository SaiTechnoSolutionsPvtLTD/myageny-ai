{{-- Digital Marketing Campaigns Component (Current Month Renewals & Expired Campaigns) --}}
@php
    $dmc = $dmCampaignsData ?? null;
    $renewals = $dmc['renewalCampaigns'] ?? null;
    $expired = $dmc['expiredCampaigns'] ?? null;
    $summary = $dmc['summary'] ?? [
        'total_renewals' => 0,
        'due_renewals' => 0,
        'completed_renewals' => 0,
        'total_expired' => 0,
        'unrenewed_expired' => 0,
    ];
    $monthLabel = $dmc['current_month_label'] ?? \Illuminate\Support\Carbon::today()->format('F Y');
@endphp

@if($dmc)
<style>
.dmc-card { background:#fff; border:1px solid #eee7df; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.03); margin-top:18px; }
.dmc-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid #f2ede8; background:#fffdfb; flex-wrap:wrap; }
.dmc-card-title { font-size:16px; font-weight:900; color:#111827; display:flex; align-items:center; gap:8px; }
.dmc-card-sub { margin-top:3px; font-size:12px; color:#7c7c7c; }



.dmc-table-wrap { overflow-x:auto; }
.dmc-table { width:100%; border-collapse:collapse; min-width:960px; }
.dmc-table th { padding:12px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; background:#fafaf9; border-bottom:1px solid #f2ede8; white-space:nowrap; }
.dmc-table td { padding:14px 16px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.dmc-table tbody tr:hover td { background:#fffaf5; }

.dmc-platform-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:10.5px; font-weight:800; text-transform:uppercase; }
.dmc-platform-meta { background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; }
.dmc-platform-google { background:#fef2f2; color:#dc2626; border:1px solid #fee2e2; }
.dmc-platform-instagram { background:#fdf2f8; color:#be185d; border:1px solid #fce7f3; }
.dmc-platform-other { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

.dmc-renewal-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; white-space:nowrap; }
.dmc-renewal-badge--due { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.dmc-renewal-badge--renewed { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.dmc-renewal-badge--expired { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

.dmc-status-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; text-transform:capitalize; }
.dmc-status-pill--active { background:#ecfdf5; color:#047857; }
.dmc-status-pill--paused { background:#fffbeb; color:#b45309; }
.dmc-status-pill--expired { background:#fef2f2; color:#b91c1c; }
.dmc-status-pill--stopped { background:#f1f5f9; color:#64748b; }

.dmc-ext-badge { display:inline-flex; align-items:center; gap:3px; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:800; background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; margin-left:4px; }
.dmc-days-left { display:inline-flex; align-items:center; gap:3px; padding:2px 7px; border-radius:6px; font-size:10.5px; font-weight:700; }
.dmc-days-left--safe { background:#f0fdf4; color:#15803d; border:1px solid #dcfce7; }
.dmc-days-left--urgent { background:#fff7ed; color:#c2410c; border:1px solid #ffedd5; }
.dmc-days-left--overdue { background:#fef2f2; color:#b91c1c; border:1px solid #fee2e2; }

.dmc-action-btn { display:inline-flex; align-items:center; justify-content:center; gap:5px; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; text-decoration:none; transition:all .15s ease; white-space:nowrap; }
.dmc-action-btn--primary { background:#ea580c; color:#fff; border:1px solid #ea580c; }
.dmc-action-btn--primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.dmc-action-btn--outline { background:#fff; color:#475569; border:1px solid #cbd5e1; }
.dmc-action-btn--outline:hover { background:#f8fafc; color:#0f172a; border-color:#94a3b8; }
</style>



{{-- 2. Section: Current Month Renewal Campaigns --}}
<section class="dmc-card" id="dm-renewal-campaigns-section">
    <div class="dmc-card-head">
        <div>
            <div class="dmc-card-title">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:#ea580c;">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Current Month Renewal Campaigns ({{ $monthLabel }})</span>
            </div>
            <div class="dmc-card-sub">Campaigns ending or renewed in {{ $monthLabel }}. Monitor expiring campaigns to ensure continuous ad delivery.</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span class="pjd-highlight" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c;">
                {{ $renewals ? $renewals->total() : 0 }} Campaigns
            </span>
            <a href="{{ route('projects.campaigns.index', ['renewal_filter' => 'cm_not_renewed']) }}" class="dmc-action-btn dmc-action-btn--outline">
                <span>View in Campaigns</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <div class="dmc-card-body" style="padding:0;">
        @if($renewals && $renewals->isNotEmpty())
            <div class="dmc-table-wrap">
                <table class="dmc-table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Company / Client</th>
                            <th>Tenure &amp; End Date</th>
                            <th>Budget</th>
                            <th>Renewal Status</th>
                            <th>Status</th>
                            <th>Assigned By</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($renewals as $item)
                            <tr>
                                {{-- Campaign Column --}}
                                <td>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        @php
                                            $plat = strtolower($item['platform'] ?? '');
                                            $platClass = match(true) {
                                                str_contains($plat, 'meta') || str_contains($plat, 'face') => 'dmc-platform-meta',
                                                str_contains($plat, 'google') => 'dmc-platform-google',
                                                str_contains($plat, 'insta') => 'dmc-platform-instagram',
                                                default => 'dmc-platform-other'
                                            };
                                        @endphp
                                        <span class="dmc-platform-badge {{ $platClass }}">{{ $item['platform'] }}</span>
                                        @if($item['is_extended'])
                                            <span class="dmc-ext-badge">Extended</span>
                                        @endif
                                    </div>
                                    <div style="font-weight:800; font-size:13.5px; color:#0f172a; margin-top:4px;">
                                        {{ $item['campaign_name'] }}
                                    </div>
                                </td>

                                {{-- Company / Client Column --}}
                                <td>
                                    <div style="font-weight:800; font-size:13px; color:#1e293b;">
                                        {{ $item['company_name'] }}
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                        {{ $item['contact_name'] }} &bull; {{ $item['mobile_number'] }}
                                    </div>
                                </td>

                                {{-- Tenure & End Date Column --}}
                                <td>
                                    <div style="font-size:12.5px; font-weight:700; color:#1e293b;">
                                        {{ $item['start_date'] }} &ndash; {{ $item['end_date'] }}
                                    </div>
                                    <div style="margin-top:4px;">
                                        @if($item['days_remaining'] !== null)
                                            @if($item['is_overdue'])
                                                <span class="dmc-days-left dmc-days-left--overdue">{{ $item['days_remaining_text'] }}</span>
                                            @elseif($item['days_remaining'] <= 3)
                                                <span class="dmc-days-left dmc-days-left--urgent">{{ $item['days_remaining_text'] }}</span>
                                            @else
                                                <span class="dmc-days-left dmc-days-left--safe">{{ $item['days_remaining_text'] }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                {{-- Budget Column --}}
                                <td>
                                    <div style="font-weight:800; font-size:13px; color:#0f172a;">
                                        Rs {{ number_format($item['budget_amount'], 2) }}
                                    </div>
                                    <div style="font-size:11px; color:#64748b;">
                                        {{ $item['budget_type'] }}
                                    </div>
                                </td>

                                {{-- Renewal Status Column --}}
                                <td>
                                    @if($item['is_renewed'])
                                        <span class="dmc-renewal-badge dmc-renewal-badge--renewed">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            <span>Renewed</span>
                                        </span>
                                    @else
                                        <span class="dmc-renewal-badge dmc-renewal-badge--due">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            <span>Due for Renewal</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Campaign Status Column --}}
                                <td>
                                    @php
                                        $cStatus = $item['campaign_status'];
                                        $cStatusClass = match($cStatus) {
                                            'active' => 'dmc-status-pill--active',
                                            'paused' => 'dmc-status-pill--paused',
                                            'expired' => 'dmc-status-pill--expired',
                                            default => 'dmc-status-pill--stopped',
                                        };
                                    @endphp
                                    <span class="dmc-status-pill {{ $cStatusClass }}">{{ $cStatus }}</span>
                                </td>

                                {{-- Assigned By Column --}}
                                <td>
                                    <div style="font-weight:600; font-size:12px; color:#475569;">
                                        {{ $item['created_by_name'] }}
                                    </div>
                                </td>

                                {{-- Action Column --}}
                                <td style="text-align:right;">
                                    @if($item['lead_url'])
                                        <a href="{{ $item['lead_url'] }}" class="dmc-action-btn dmc-action-btn--primary">
                                            <span>View / Extend</span>
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                        </a>
                                    @else
                                        <span style="font-size:11px; color:#94a3b8;">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer for Renewal Campaigns --}}
            @if($renewals->hasPages())
                <div class="pjd-pagination-footer">
                    <div class="pjd-pagination-info">
                        {{ $renewals->firstItem() }}-{{ $renewals->lastItem() }} of {{ $renewals->total() }} campaigns
                    </div>
                    <nav class="pjd-pagination-nav">
                        {{-- Prev Page Link --}}
                        @if($renewals->onFirstPage())
                            <span class="pjd-page-btn is-disabled" title="Previous Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </span>
                        @else
                            <a href="{{ $renewals->previousPageUrl() }}" class="pjd-page-btn" title="Previous Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </a>
                        @endif

                        @php
                            $rCur = $renewals->currentPage();
                            $rLast = $renewals->lastPage();
                            $rStart = max(1, $rCur - 2);
                            $rEnd = min($rLast, $rCur + 2);
                        @endphp

                        @if($rStart > 1)
                            <a href="{{ $renewals->url(1) }}" class="pjd-page-btn {{ $rCur == 1 ? 'is-active' : '' }}">1</a>
                            @if($rStart > 2)
                                <span class="pjd-page-ellipsis">...</span>
                            @endif
                        @endif

                        @for($i = $rStart; $i <= $rEnd; $i++)
                            <a href="{{ $renewals->url($i) }}" class="pjd-page-btn {{ $rCur == $i ? 'is-active' : '' }}">{{ $i }}</a>
                        @endfor

                        @if($rEnd < $rLast)
                            @if($rEnd < $rLast - 1)
                                <span class="pjd-page-ellipsis">...</span>
                            @endif
                            <a href="{{ $renewals->url($rLast) }}" class="pjd-page-btn {{ $rCur == $rLast ? 'is-active' : '' }}">{{ $rLast }}</a>
                        @endif

                        {{-- Next Page Link --}}
                        @if($renewals->hasMorePages())
                            <a href="{{ $renewals->nextPageUrl() }}" class="pjd-page-btn" title="Next Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        @else
                            <span class="pjd-page-btn is-disabled" title="Next Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </span>
                        @endif
                    </nav>
                </div>
            @endif
        @else
            <div class="pjd-empty" style="padding:32px 20px; text-align:center; color:#64748b;">
                <div style="font-size:28px; margin-bottom:8px;">🔄</div>
                <div style="font-weight:700; font-size:14px; color:#1e293b;">No Renewal Campaigns Found for {{ $monthLabel }}</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:4px;">When campaigns reach their renewal window this month, they will appear here.</div>
            </div>
        @endif
    </div>
</section>

{{-- 3. Section: Current Expired Campaigns --}}
<section class="dmc-card" id="dm-expired-campaigns-section">
    <div class="dmc-card-head">
        <div>
            <div class="dmc-card-title">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:#dc2626;">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span>Current Expired Campaigns</span>
            </div>
            <div class="dmc-card-sub">Campaigns that have passed their end date or are marked expired. Review to renew, extend, or settle accounts.</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span class="pjd-highlight" style="background:#fef2f2; border-color:#fecaca; color:#dc2626;">
                {{ $expired ? $expired->total() : 0 }} Expired
            </span>
            <a href="{{ route('projects.campaigns.index', ['renewal_filter' => 'expired']) }}" class="dmc-action-btn dmc-action-btn--outline">
                <span>View All Expired</span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <div class="dmc-card-body" style="padding:0;">
        @if($expired && $expired->isNotEmpty())
            <div class="dmc-table-wrap">
                <table class="dmc-table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Company / Client</th>
                            <th>Ended On</th>
                            <th>Last Active Budget</th>
                            <th>Campaign Status</th>
                            <th>Renewal Status</th>
                            <th>Assigned By</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expired as $item)
                            <tr>
                                {{-- Campaign Column --}}
                                <td>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        @php
                                            $plat = strtolower($item['platform'] ?? '');
                                            $platClass = match(true) {
                                                str_contains($plat, 'meta') || str_contains($plat, 'face') => 'dmc-platform-meta',
                                                str_contains($plat, 'google') => 'dmc-platform-google',
                                                str_contains($plat, 'insta') => 'dmc-platform-instagram',
                                                default => 'dmc-platform-other'
                                            };
                                        @endphp
                                        <span class="dmc-platform-badge {{ $platClass }}">{{ $item['platform'] }}</span>
                                    </div>
                                    <div style="font-weight:800; font-size:13.5px; color:#0f172a; margin-top:4px;">
                                        {{ $item['campaign_name'] }}
                                    </div>
                                </td>

                                {{-- Company / Client Column --}}
                                <td>
                                    <div style="font-weight:800; font-size:13px; color:#1e293b;">
                                        {{ $item['company_name'] }}
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                        {{ $item['contact_name'] }} &bull; {{ $item['mobile_number'] }}
                                    </div>
                                </td>

                                {{-- Ended On Column --}}
                                <td>
                                    <div style="font-size:12.5px; font-weight:700; color:#1e293b;">
                                        {{ $item['end_date'] }}
                                    </div>
                                    <div style="margin-top:3px;">
                                        <span class="dmc-days-left dmc-days-left--overdue">{{ $item['overdue_text'] }}</span>
                                    </div>
                                </td>

                                {{-- Budget Column --}}
                                <td>
                                    <div style="font-weight:800; font-size:13px; color:#0f172a;">
                                        Rs {{ number_format($item['budget_amount'], 2) }}
                                    </div>
                                    <div style="font-size:11px; color:#64748b;">
                                        {{ $item['budget_type'] }}
                                    </div>
                                </td>

                                {{-- Campaign Status Column --}}
                                <td>
                                    <span class="dmc-status-pill dmc-status-pill--expired">Expired</span>
                                </td>

                                {{-- Renewal Status Column --}}
                                <td>
                                    @if($item['is_renewed'])
                                        <span class="dmc-renewal-badge dmc-renewal-badge--renewed">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            <span>Renewed</span>
                                        </span>
                                    @else
                                        <span class="dmc-renewal-badge dmc-renewal-badge--expired">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            <span>Pending Renewal</span>
                                        </span>
                                    @endif
                                </td>

                                {{-- Assigned By Column --}}
                                <td>
                                    <div style="font-weight:600; font-size:12px; color:#475569;">
                                        {{ $item['created_by_name'] }}
                                    </div>
                                </td>

                                {{-- Action Column --}}
                                <td style="text-align:right;">
                                    @if($item['lead_url'])
                                        <a href="{{ $item['lead_url'] }}" class="dmc-action-btn dmc-action-btn--primary">
                                            <span>View / Extend</span>
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                                        </a>
                                    @else
                                        <span style="font-size:11px; color:#94a3b8;">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer for Expired Campaigns --}}
            @if($expired->hasPages())
                <div class="pjd-pagination-footer">
                    <div class="pjd-pagination-info">
                        {{ $expired->firstItem() }}-{{ $expired->lastItem() }} of {{ $expired->total() }} campaigns
                    </div>
                    <nav class="pjd-pagination-nav">
                        {{-- Prev Page Link --}}
                        @if($expired->onFirstPage())
                            <span class="pjd-page-btn is-disabled" title="Previous Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </span>
                        @else
                            <a href="{{ $expired->previousPageUrl() }}" class="pjd-page-btn" title="Previous Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </a>
                        @endif

                        @php
                            $eCur = $expired->currentPage();
                            $eLast = $expired->lastPage();
                            $eStart = max(1, $eCur - 2);
                            $eEnd = min($eLast, $eCur + 2);
                        @endphp

                        @if($eStart > 1)
                            <a href="{{ $expired->url(1) }}" class="pjd-page-btn {{ $eCur == 1 ? 'is-active' : '' }}">1</a>
                            @if($eStart > 2)
                                <span class="pjd-page-ellipsis">...</span>
                            @endif
                        @endif

                        @for($i = $eStart; $i <= $eEnd; $i++)
                            <a href="{{ $expired->url($i) }}" class="pjd-page-btn {{ $eCur == $i ? 'is-active' : '' }}">{{ $i }}</a>
                        @endfor

                        @if($eEnd < $eLast)
                            @if($eEnd < $eLast - 1)
                                <span class="pjd-page-ellipsis">...</span>
                            @endif
                            <a href="{{ $expired->url($eLast) }}" class="pjd-page-btn {{ $eCur == $eLast ? 'is-active' : '' }}">{{ $eLast }}</a>
                        @endif

                        {{-- Next Page Link --}}
                        @if($expired->hasMorePages())
                            <a href="{{ $expired->nextPageUrl() }}" class="pjd-page-btn" title="Next Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        @else
                            <span class="pjd-page-btn is-disabled" title="Next Page">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </span>
                        @endif
                    </nav>
                </div>
            @endif
        @else
            <div class="pjd-empty" style="padding:32px 20px; text-align:center; color:#64748b;">
                <div style="font-size:28px; margin-bottom:8px;">🎉</div>
                <div style="font-weight:700; font-size:14px; color:#1e293b;">No Expired Campaigns</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:4px;">All digital marketing campaigns are currently running within their active schedule.</div>
            </div>
        @endif
    </div>
</section>
@endif