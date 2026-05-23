@extends('layouts.app')

@section('title', 'User Mapping')

@push('styles')
<style>
.umap-page { min-height:100%; padding:28px; background:#f4f5f7; }
.umap-topbar { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:18px; }
.umap-title { margin:0; font-size:22px; font-weight:800; color:#121212; }
.umap-subtitle { margin-top:4px; font-size:13px; color:#7c7c7c; }
.umap-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.umap-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:38px; padding:9px 15px; border-radius:10px; border:1px solid #e1dee3; background:#fff; color:#121212; text-decoration:none; font-size:13px; font-weight:700; cursor:pointer; transition:all .16s ease; }
.umap-btn:hover { border-color:#fdba74; color:#ea580c; }
.umap-btn-primary { border-color:#fe5f04; background:#fe5f04; color:#fff; }
.umap-btn-primary:hover { color:#fff; background:#ea580c; border-color:#ea580c; }
.umap-alert { margin-bottom:14px; padding:12px 14px; border-radius:10px; font-size:13px; }
.umap-alert.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.umap-alert.error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.umap-stack { display:flex; flex-direction:column; gap:18px; }
.umap-card { background:#fff; border:1px solid #e1dee3; border-radius:14px; overflow:hidden; box-shadow:0 10px 26px rgba(18,18,18,.04); }
.umap-chart-card:fullscreen { width:100vw; height:100vh; border-radius:0; border:0; background:#fff; display:flex; flex-direction:column; }
.umap-chart-card.is-chart-fullscreen { position:fixed; inset:0; z-index:4500; width:100vw; height:100vh; border-radius:0; border:0; background:#fff; display:flex; flex-direction:column; }
.umap-chart-card:fullscreen .umap-card-head,
.umap-chart-card.is-chart-fullscreen .umap-card-head { flex:0 0 auto; }
.umap-chart-card:fullscreen .umap-chart-body,
.umap-chart-card.is-chart-fullscreen .umap-chart-body { flex:1 1 auto; min-height:0; padding:12px; display:flex; flex-direction:column; }
.umap-chart-card:fullscreen .umap-tree-shell,
.umap-chart-card.is-chart-fullscreen .umap-tree-shell { flex:1 1 auto; min-width:0; min-height:0; display:flex; flex-direction:column; border-radius:12px; }
.umap-chart-card:fullscreen .umap-tree-toolbar,
.umap-chart-card.is-chart-fullscreen .umap-tree-toolbar { flex:0 0 auto; }
.umap-chart-card:fullscreen .umap-tree-viewport,
.umap-chart-card.is-chart-fullscreen .umap-tree-viewport { flex:1 1 auto; min-height:0; max-height:none; }
.umap-card-head { padding:16px 18px; border-bottom:1px solid #f1eff3; display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
.umap-card-title { font-size:15px; font-weight:800; color:#121212; }
.umap-card-sub { margin-top:3px; font-size:12px; color:#7c7c7c; line-height:1.5; }
.umap-card-meta { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
.umap-count { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; background:#eef4ff; color:#3355aa; font-size:11px; font-weight:800; white-space:nowrap; }
.umap-count.orange { background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; }
.umap-chart-body { padding:18px; background:#f6f7f9; }
.umap-tree-shell { min-width:1040px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; overflow:hidden; }
.umap-tree-toolbar { min-height:56px; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #eceff3; background:#fff; }
.umap-tree-legend { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.umap-legend-chip { display:inline-flex; align-items:center; gap:7px; padding:6px 9px; border-radius:999px; background:#f8fafc; border:1px solid #e5e7eb; font-size:11px; font-weight:800; color:#334155; }
.umap-legend-dot { width:9px; height:9px; border-radius:999px; flex:0 0 auto; }
.umap-legend-dot.root { background:#0f766e; }
.umap-legend-dot.manager { background:#15803d; }
.umap-legend-dot.lead { background:#0284c7; }
.umap-legend-dot.individual { background:#d97706; }
.umap-tree-actions { display:flex; gap:8px; flex-wrap:wrap; }
.umap-icon-btn { width:36px; height:36px; border:1px solid #e5e7eb; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:#fff; color:#475569; cursor:pointer; transition:all .16s ease; }
.umap-icon-btn:hover { border-color:#fdba74; color:#ea580c; background:#fff7ed; }
.umap-zoom-label { display:inline-flex; align-items:center; justify-content:center; min-width:64px; height:36px; padding:0 10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; color:#475569; font-size:12px; font-weight:800; }
.umap-tree-viewport { position:relative; min-height:640px; max-height:74vh; overflow:auto; background:#edf6f5; scrollbar-gutter:stable; }
.umap-tree-viewport.is-panning { cursor:grabbing; }
.umap-tree-stage { position:relative; min-width:1160px; min-height:720px; background:#edf6f5; background-image:linear-gradient(rgba(148,163,184,.18) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,.18) 1px, transparent 1px); background-size:34px 34px; }
.umap-tree-svg { position:absolute; inset:0; overflow:visible; pointer-events:none; }
.umap-tree-nodes { position:absolute; inset:0; pointer-events:none; }
.umap-tree-link { fill:none; stroke:#94a3b8; stroke-width:1.7px; stroke-linecap:round; }
.umap-tree-card { position:absolute; width:300px; min-height:116px; padding:15px 16px 15px 52px; border-radius:12px; border:1px solid #d7dde6; background:#fff; color:#172033; box-shadow:0 16px 34px rgba(15,23,42,.12); pointer-events:auto; cursor:grab; user-select:none; touch-action:none; transition:box-shadow .16s ease, transform .16s ease, border-color .16s ease; }
.umap-tree-card:hover { transform:translateY(-1px); box-shadow:0 20px 42px rgba(15,23,42,.16); border-color:#f59e0b; }
.umap-tree-card:active { cursor:grabbing; }
.umap-tree-card::before { content:''; position:absolute; left:16px; top:16px; bottom:16px; width:10px; border-radius:999px; background:var(--node-accent, #f59e0b); opacity:.95; }
.umap-tree-card::after { content:'::'; position:absolute; left:31px; top:17px; color:#8a95a5; font-size:13px; font-weight:900; line-height:1; letter-spacing:-3px; writing-mode:vertical-rl; }
.umap-tree-card.is-root { width:320px; min-height:104px; padding:18px 20px; text-align:center; cursor:default; border-color:#8fd3cb; background:#0f766e; color:#fff; }
.umap-tree-card.is-root::before,
.umap-tree-card.is-root::after { display:none; }
.umap-tree-card.is-manager { --node-accent:#15803d; background:#dcfce7; border-color:#86efac; color:#122817; }
.umap-tree-card.is-lead { --node-accent:#0284c7; background:#e0f2fe; border-color:#7dd3fc; color:#102636; }
.umap-tree-card.is-individual { --node-accent:#d97706; background:#fffbeb; border-color:#fde68a; color:#2a1b02; }
.umap-tree-card.is-drop-target { outline:4px solid rgba(254,95,4,.28); outline-offset:5px; border-color:#fe5f04; }
.umap-tree-card.is-drag-source { opacity:.42; }
.umap-tree-name { display:block; font-size:16px; font-weight:900; line-height:1.25; color:inherit; overflow-wrap:anywhere; }
.umap-tree-role { display:block; margin-top:7px; font-size:12px; font-weight:900; line-height:1.2; color:inherit; opacity:.92; }
.umap-tree-meta { display:block; margin-top:8px; font-size:11px; font-weight:800; line-height:1.2; color:inherit; opacity:.72; }
.umap-tree-email { display:block; margin-top:4px; font-size:11px; color:inherit; opacity:.76; overflow-wrap:anywhere; }
.umap-drag-ghost { position:fixed; z-index:5000; pointer-events:none; transform:translate(-50%, -50%) rotate(.5deg); opacity:.94; }
.umap-drag-ghost .umap-tree-card { position:relative; left:auto !important; top:auto !important; cursor:grabbing; box-shadow:0 24px 48px rgba(15,23,42,.24); }
.umap-chart-status { display:none; margin-top:10px; padding:10px 12px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; font-weight:800; }
.umap-chart-status.is-visible { display:block; }
.umap-form-grid { display:grid; grid-template-columns:minmax(0, 1fr) 360px; gap:18px; align-items:start; }
.umap-table-wrap { overflow-x:auto; }
.umap-table { width:100%; border-collapse:collapse; min-width:980px; }
.umap-table th, .umap-table td { padding:14px 16px; border-bottom:1px solid #f1eff3; text-align:left; font-size:13px; vertical-align:middle; }
.umap-table th { background:#fafafa; color:#8a8a8a; font-size:11px; font-weight:800; letter-spacing:.6px; text-transform:uppercase; }
.umap-table tr:last-child td { border-bottom:none; }
.umap-user-name { font-weight:800; color:#121212; }
.umap-muted { color:#7c7c7c; font-size:12px; line-height:1.45; }
.umap-badge { display:inline-flex; padding:4px 9px; border-radius:999px; background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; font-size:11px; font-weight:800; white-space:nowrap; }
.umap-select { width:100%; min-width:190px; height:38px; padding:0 11px; border:1px solid #e1dee3; border-radius:10px; background:#fff; font-size:13px; color:#121212; }
.umap-select:focus { outline:none; border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.select2-container--default .select2-selection--single.umap-select2-selection { height:38px; border:1px solid #e1dee3; border-radius:10px; background:#fff; }
.select2-container--default .select2-selection--single.umap-select2-selection .select2-selection__rendered { line-height:36px; padding-left:11px; padding-right:34px; font-size:13px; color:#121212; }
.select2-container--default .select2-selection--single.umap-select2-selection .select2-selection__arrow { height:36px; right:8px; }
.select2-container--default.select2-container--focus .select2-selection--single.umap-select2-selection,
.select2-container--default.select2-container--open .select2-selection--single.umap-select2-selection { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.select2-dropdown { border:1px solid #e1dee3; border-radius:10px; overflow:hidden; box-shadow:0 12px 28px rgba(18,18,18,.08); }
.select2-search--dropdown { padding:10px; }
.select2-search--dropdown .select2-search__field { border:1px solid #e1dee3; border-radius:8px; padding:8px 10px; font-size:13px; }
.select2-results__option { font-size:13px; padding:9px 11px; }
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background:#fe5f04; color:#fff; }
.select2-container--open { z-index: 4600; }
.umap-summary-list { display:grid; gap:12px; }
.umap-summary-item { padding:12px 14px; border:1px solid #eef0f3; border-radius:12px; background:#fafafa; }
.umap-summary-item strong { display:block; color:#121212; font-size:13px; }
.umap-empty-box { padding:32px 18px; text-align:center; color:#8a8a8a; font-size:13px; }
.umap-delete { border:none; background:#fef2f2; color:#dc2626; border-radius:9px; width:32px; height:32px; cursor:pointer; font-weight:800; }
@media (max-width: 1180px) {
    .umap-page { padding:18px; }
    .umap-topbar { flex-direction:column; align-items:flex-start; }
    .umap-card-head { flex-direction:column; }
    .umap-card-meta { justify-content:flex-start; }
    .umap-chart-body { padding:12px; overflow-x:auto; }
    .umap-tree-shell { min-width:920px; }
    .umap-tree-viewport { min-height:560px; max-height:70vh; }
    .umap-form-grid { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
<div class="umap-page">
    <div class="umap-topbar">
        <div>
            <h2 class="umap-title">User Mapping</h2>
            <div class="umap-subtitle">D3 flextree layout with draggable user hierarchy mapping.</div>
        </div>
        <div class="umap-actions">
            <a href="{{ route('auth.index') }}" class="umap-btn">Back</a>
            <button type="submit" form="userMappingForm" class="umap-btn umap-btn-primary">Save Mapping</button>
        </div>
    </div>

    @if(session('success'))
        <div class="umap-alert success">{!! session('success') !!}</div>
    @endif
    @if(session('error'))
        <div class="umap-alert error">{!! session('error') !!}</div>
    @endif

    <div class="umap-stack">
        <div id="userChartCard" class="umap-card umap-chart-card">
            <div class="umap-card-head">
                <div>
                    <div class="umap-card-title">User Organization Chart</div>
                    <div class="umap-card-sub">Drag a user card and drop it on another user to remap reporting hierarchy instantly.</div>
                </div>
                <div class="umap-card-meta">
                    <span class="umap-count orange">{{ $userChart['mappedCount'] }} user link{{ $userChart['mappedCount'] === 1 ? '' : 's' }}</span>
                    <span class="umap-count">{{ $userChart['parentCount'] }} people manager{{ $userChart['parentCount'] === 1 ? '' : 's' }}</span>
                </div>
            </div>

            <div class="umap-chart-body">
                @if($users->isEmpty())
                    <div class="umap-empty-box">No active users found.</div>
                @else
                    <div class="umap-tree-shell">
                        <div class="umap-tree-toolbar">
                            <div class="umap-tree-legend" aria-label="User hierarchy legend">
                                <span class="umap-legend-chip"><span class="umap-legend-dot root"></span>Top level</span>
                                <span class="umap-legend-chip"><span class="umap-legend-dot manager"></span>Manager</span>
                                <span class="umap-legend-chip"><span class="umap-legend-dot lead"></span>Lead</span>
                                <span class="umap-legend-chip"><span class="umap-legend-dot individual"></span>Individual</span>
                            </div>
                            <div class="umap-tree-actions">
                                <button type="button" class="umap-icon-btn" data-chart-zoom-out title="Zoom out" aria-label="Zoom out">
                                    <i class="bi bi-zoom-out"></i>
                                </button>
                                <div class="umap-zoom-label" data-chart-zoom-label>100%</div>
                                <button type="button" class="umap-icon-btn" data-chart-zoom-in title="Zoom in" aria-label="Zoom in">
                                    <i class="bi bi-zoom-in"></i>
                                </button>
                                <button type="button" class="umap-icon-btn" data-chart-zoom-reset title="Reset zoom" aria-label="Reset zoom">
                                    <i class="bi bi-aspect-ratio"></i>
                                </button>
                                <button type="button" class="umap-icon-btn" data-chart-center title="Center chart" aria-label="Center chart">
                                    <i class="bi bi-bullseye"></i>
                                </button>
                                <button type="button" class="umap-icon-btn" data-chart-refresh title="Refresh chart" aria-label="Refresh chart">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button type="button" class="umap-icon-btn" data-chart-fullscreen title="Full screen" aria-label="Full screen">
                                    <i class="bi bi-arrows-fullscreen" data-chart-fullscreen-icon></i>
                                </button>
                            </div>
                        </div>
                        <div id="userTreeViewport" class="umap-tree-viewport">
                            <div id="userHierarchyChart" class="umap-tree-stage">
                                <svg id="userTreeSvg" class="umap-tree-svg" aria-hidden="true"></svg>
                                <div id="userTreeNodes" class="umap-tree-nodes"></div>
                            </div>
                        </div>
                    </div>
                    <div id="userChartStatus" class="umap-chart-status" aria-live="polite"></div>
                @endif
            </div>
        </div>

        <form id="userMappingForm" method="POST" action="{{ route('auth.user-mappings.update') }}">
            @csrf

            <div class="umap-form-grid">
                <div class="umap-card">
                    <div class="umap-card-head">
                        <div>
                            <div class="umap-card-title">User Hierarchy Mapping</div>
                            <div class="umap-card-sub">You can drag on the chart or choose a reporting manager manually below.</div>
                        </div>
                        <span class="umap-count">{{ $users->count() }} user{{ $users->count() === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="umap-table-wrap">
                        <table class="umap-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Direct Reports</th>
                                    <th>Reports To</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    @php
                                        $managerId = old("parents.{$user->id}", $parentMap[$user->id] ?? null);
                                        $userNode = $userChart['nodes']->firstWhere('userId', (int) $user->id);
                                        $directReports = $userNode['childCount'] ?? 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="umap-user-name">{{ $user->name }}</div>
                                        </td>
                                        <td class="umap-muted">{{ $user->email }}</td>
                                        <td><span class="umap-badge">{{ $user->roles->first()?->display_name ?? $user->roles->first()?->name ?? 'No Role' }}</span></td>
                                        <td data-report-count="{{ $user->id }}">{{ $directReports }}</td>
                                        <td>
                                            <select class="umap-select select2 umap-parent-select" name="parents[{{ $user->id }}]" data-parent-select data-user-id="{{ $user->id }}" data-placeholder="Search manager">
                                                <option value="">Top Level</option>
                                                @foreach($users as $manager)
                                                    @continue($manager->id === $user->id)
                                                    <option value="{{ $manager->id }}" @selected((int) $managerId === (int) $manager->id)>
                                                        {{ $manager->name }} - {{ $manager->roles->first()?->display_name ?? $manager->roles->first()?->name ?? 'No Role' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("parents.{$user->id}")<div class="umap-muted">{{ $message }}</div>@enderror
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="umap-muted">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="umap-card">
                    <div class="umap-card-head">
                        <div>
                            <div class="umap-card-title">Quick Summary</div>
                            <div class="umap-card-sub">Current mapped user pairs and reporting lines.</div>
                        </div>
                    </div>

                    <div class="umap-chart-body" style="background:#fff;">
                        <div class="umap-summary-list">
                            @forelse($mappings as $mapping)
                                <div class="umap-summary-item">
                                    <strong>{{ $mapping->user?->name }}</strong>
                                    <div class="umap-muted">{{ $mapping->user?->roles->first()?->display_name ?? $mapping->user?->roles->first()?->name ?? 'No Role' }}</div>
                                    <div class="umap-muted">Reports to {{ $mapping->manager?->name ?? 'Top Level' }}</div>
                                </div>
                            @empty
                                <div class="umap-empty-box">No user mappings found yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="umap-card">
            <div class="umap-card-head">
                <div>
                    <div class="umap-card-title">Current Mapping</div>
                    <div class="umap-card-sub">{{ $mappings->total() }} mapped user{{ $mappings->total() === 1 ? '' : 's' }}</div>
                </div>
            </div>

            <div class="umap-table-wrap">
                <table class="umap-table">
                    <thead>
                        <tr>
                            <th>Manager / TL</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings as $mapping)
                            <tr>
                                <td>
                                    <strong>{{ $mapping->manager?->name }}</strong>
                                    <div class="umap-muted">{{ $mapping->manager?->email }}</div>
                                </td>
                                <td>
                                    <strong>{{ $mapping->user?->name }}</strong>
                                    <div class="umap-muted">{{ $mapping->user?->email }}</div>
                                </td>
                                <td class="umap-muted">{{ $mapping->user?->roles->first()?->display_name ?? $mapping->user?->roles->first()?->name ?? 'No Role' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('auth.user-mappings.destroy', $mapping) }}" onsubmit="return confirm('Remove this mapping?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="umap-delete" type="submit" title="Remove">x</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="umap-muted">No user mappings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($mappings->hasPages())
                @include('partials.table-pagination', ['paginator' => $mappings])
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/d3-flextree@2.1.2/build/d3-flextree.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function initializeManagerSelects() {
        if (!window.jQuery || !window.jQuery.fn.select2) {
            return;
        }

        const form = window.jQuery('#userMappingForm');

        window.jQuery('.umap-parent-select').each(function () {
            const select = window.jQuery(this);

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                width: '100%',
                dropdownParent: form,
                placeholder: select.data('placeholder') || 'Search manager',
                allowClear: true,
            });

            select.next('.select2-container').find('.select2-selection--single').addClass('umap-select2-selection');
        });
    }

    initializeManagerSelects();
    window.setTimeout(initializeManagerSelects, 0);
    window.addEventListener('load', initializeManagerSelects, { once: true });

    const viewport = document.getElementById('userTreeViewport');
    const stage = document.getElementById('userHierarchyChart');
    const svg = document.getElementById('userTreeSvg');
    const nodeLayer = document.getElementById('userTreeNodes');
    const statusElement = document.getElementById('userChartStatus');
    const chartCard = document.getElementById('userChartCard');
    const fullscreenButton = document.querySelector('[data-chart-fullscreen]');
    const fullscreenIcon = document.querySelector('[data-chart-fullscreen-icon]');
    const zoomLabel = document.querySelector('[data-chart-zoom-label]');
    const state = {
        users: @json($userChart['nodes']),
        centered: false,
        zoom: 1,
    };

    const sizes = {
        node: [300, 116],
        root: [320, 104],
        xGap: 90,
        yGap: 124,
        paddingX: 90,
        paddingY: 70,
    };

    let dragState = null;
    let activeDropCard = null;
    let panState = null;

    if (!viewport || !stage || !svg || !nodeLayer || !Array.isArray(state.users) || state.users.length === 0) {
        return;
    }

    if (!window.d3 || typeof d3.flextree !== 'function') {
        showStatus('D3 flextree library could not be loaded.');
        return;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function categoryClass(category) {
        return ['manager', 'lead', 'individual'].includes(category) ? category : 'individual';
    }

    function showStatus(message) {
        if (!statusElement) {
            return;
        }

        statusElement.textContent = message;
        statusElement.classList.add('is-visible');

        window.clearTimeout(showStatus.timer);
        showStatus.timer = window.setTimeout(function () {
            statusElement.classList.remove('is-visible');
        }, 2600);
    }

    function clampZoom(value) {
        return Math.min(1.8, Math.max(0.6, value));
    }

    function updateZoomUi() {
        if (zoomLabel) {
            zoomLabel.textContent = Math.round(state.zoom * 100) + '%';
        }
    }

    function applyZoom() {
        stage.style.zoom = String(state.zoom);
        updateZoomUi();
    }

    function setZoom(value) {
        state.zoom = clampZoom(value);
        applyZoom();
    }

    function setZoomFromWheel(nextZoom, clientX, clientY) {
        const boundedZoom = clampZoom(nextZoom);

        if (Math.abs(boundedZoom - state.zoom) < 0.001) {
            return;
        }

        const rect = viewport.getBoundingClientRect();
        const offsetX = clientX - rect.left;
        const offsetY = clientY - rect.top;
        const contentX = (viewport.scrollLeft + offsetX) / state.zoom;
        const contentY = (viewport.scrollTop + offsetY) / state.zoom;

        state.zoom = boundedZoom;
        applyZoom();

        viewport.scrollLeft = Math.max(0, (contentX * state.zoom) - offsetX);
        viewport.scrollTop = Math.max(0, (contentY * state.zoom) - offsetY);
    }

    function userById(userId) {
        const numericUserId = Number(userId);

        return state.users.find(function (user) {
            return Number(user.userId) === numericUserId;
        });
    }

    function syncSelectValue(select, value) {
        if (!select) {
            return;
        }

        const normalizedValue = value ? String(value) : '';
        select.value = normalizedValue;

        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(select).val(normalizedValue).trigger('change.select2');
        }
    }

    function refreshCategories() {
        state.users.forEach(function (user) {
            user.childCount = 0;
        });

        state.users.forEach(function (user) {
            if (!user.parentUserId) {
                return;
            }

            const parentUser = userById(user.parentUserId);

            if (parentUser) {
                parentUser.childCount += 1;
            }
        });

        state.users.forEach(function (user) {
            user.category = user.childCount > 0
                ? (user.parentUserId ? 'lead' : 'manager')
                : 'individual';
            user.meta = user.childCount > 0
                ? user.childCount + ' direct report' + (user.childCount === 1 ? '' : 's')
                : 'Individual contributor';
        });
    }

    function syncStateFromInputs() {
        document.querySelectorAll('[data-parent-select]').forEach(function (select) {
            const user = userById(select.getAttribute('data-user-id'));

            if (user) {
                user.parentUserId = select.value ? Number(select.value) : null;
            }
        });

        refreshCategories();
        updateDirectReportCells();
    }

    function updateDirectReportCells() {
        state.users.forEach(function (user) {
            const cell = document.querySelector('[data-report-count="' + user.userId + '"]');
            if (cell) {
                cell.textContent = String(user.childCount);
            }
        });
    }

    function wouldCreateLoop(childUserId, parentUserId) {
        const visited = new Set([Number(childUserId)]);
        let currentParentId = Number(parentUserId);

        while (currentParentId) {
            if (visited.has(currentParentId)) {
                return true;
            }

            visited.add(currentParentId);
            const parentUser = userById(currentParentId);
            currentParentId = parentUser && parentUser.parentUserId ? Number(parentUser.parentUserId) : null;
        }

        return false;
    }

    function sortNodes(a, b) {
        return String(a.name).localeCompare(String(b.name));
    }

    function buildTreeData() {
        const userNodes = new Map();
        const root = {
            id: 'user-root',
            name: 'Top Level Users',
            role: 'User Mapping',
            meta: 'Users without reporting manager',
            email: '',
            isRoot: true,
            size: sizes.root,
            children: [],
        };

        state.users.forEach(function (user) {
            userNodes.set(Number(user.userId), {
                ...user,
                id: 'user-' + user.userId,
                size: sizes.node,
                children: [],
            });
        });

        state.users.forEach(function (user) {
            const node = userNodes.get(Number(user.userId));
            const parentId = user.parentUserId ? Number(user.parentUserId) : null;
            const parentNode = parentId ? userNodes.get(parentId) : null;

            if (parentNode && parentId !== Number(user.userId) && !wouldCreateLoop(user.userId, parentId)) {
                parentNode.children.push(node);
            } else {
                root.children.push(node);
            }
        });

        root.children = root.children.sort(sortNodes);
        userNodes.forEach(function (node) {
            node.children = node.children.sort(sortNodes);
        });

        return root;
    }

    function nodeBox(node, extents) {
        const width = node.data.size[0];
        const height = node.data.size[1];
        const left = (Number.isFinite(node.left) ? node.left : node.x - (width / 2)) - extents.minX + sizes.paddingX;
        const top = (Number.isFinite(node.top) ? node.top : node.y - (height / 2)) - extents.minY + sizes.paddingY;

        return {
            left: left,
            top: top,
            width: width,
            height: height,
            centerX: left + (width / 2),
            centerY: top + (height / 2),
            bottom: top + height,
        };
    }

    function linkPath(link, extents) {
        const source = nodeBox(link.source, extents);
        const target = nodeBox(link.target, extents);
        const startX = source.centerX;
        const startY = source.bottom;
        const endX = target.centerX;
        const endY = target.top;
        const midY = startY + ((endY - startY) / 2);

        return 'M' + startX + ',' + startY +
            ' C' + startX + ',' + midY +
            ' ' + endX + ',' + midY +
            ' ' + endX + ',' + endY;
    }

    function cardHtml(data) {
        return '<span class="umap-tree-name">' + escapeHtml(data.name) + '</span>' +
            '<span class="umap-tree-role">' + escapeHtml(data.role) + '</span>' +
            (data.email ? '<span class="umap-tree-email">' + escapeHtml(data.email) + '</span>' : '') +
            '<span class="umap-tree-meta">' + escapeHtml(data.meta) + '</span>';
    }

    function renderChart(keepScroll) {
        syncStateFromInputs();

        const previousScrollLeft = viewport.scrollLeft;
        const previousScrollTop = viewport.scrollTop;
        const layout = d3.flextree({
            children: function (data) {
                return data.children && data.children.length ? data.children : null;
            },
            nodeSize: function (node) {
                return [
                    node.data.size[0] + sizes.xGap,
                    node.data.size[1] + sizes.yGap,
                ];
            },
            spacing: function (nodeA, nodeB) {
                return nodeA.parent === nodeB.parent ? 30 : 48;
            },
        });

        const root = layout.hierarchy(buildTreeData());
        layout(root);

        const nodes = root.descendants();
        const extents = {
            minX: d3.min(nodes, function (node) { return Number.isFinite(node.left) ? node.left : node.x - (node.data.size[0] / 2); }) ?? 0,
            maxX: d3.max(nodes, function (node) { return Number.isFinite(node.right) ? node.right : node.x + (node.data.size[0] / 2); }) ?? 0,
            minY: d3.min(nodes, function (node) { return Number.isFinite(node.top) ? node.top : node.y - (node.data.size[1] / 2); }) ?? 0,
            maxY: d3.max(nodes, function (node) { return Number.isFinite(node.bottom) ? node.bottom : node.y + (node.data.size[1] / 2); }) ?? 0,
        };
        const stageWidth = Math.max(1120, extents.maxX - extents.minX + (sizes.paddingX * 2));
        const stageHeight = Math.max(680, extents.maxY - extents.minY + (sizes.paddingY * 2));

        stage.style.width = stageWidth + 'px';
        stage.style.height = stageHeight + 'px';
        svg.setAttribute('width', stageWidth);
        svg.setAttribute('height', stageHeight);
        nodeLayer.style.width = stageWidth + 'px';
        nodeLayer.style.height = stageHeight + 'px';

        d3.select(svg)
            .selectAll('path.umap-tree-link')
            .data(root.links(), function (link) {
                return link.source.data.id + '-' + link.target.data.id;
            })
            .join('path')
            .attr('class', 'umap-tree-link')
            .attr('d', function (link) {
                return linkPath(link, extents);
            });

        const cards = d3.select(nodeLayer)
            .selectAll('div.umap-tree-card')
            .data(nodes, function (node) {
                return node.data.id;
            });

        cards.exit().remove();

        const enteredCards = cards.enter()
            .append('div')
            .on('pointerdown', function (event, node) {
                if (!node.data.isRoot) {
                    startPointerDrag(event, this, node.data.userId);
                }
            });

        enteredCards.merge(cards)
            .attr('class', function (node) {
                return 'umap-tree-card ' + (node.data.isRoot ? 'is-root' : 'is-' + categoryClass(node.data.category));
            })
            .attr('data-root-node', function (node) {
                return node.data.isRoot ? '1' : null;
            })
            .attr('data-user-id', function (node) {
                return node.data.isRoot ? null : node.data.userId;
            })
            .style('width', function (node) {
                return node.data.size[0] + 'px';
            })
            .style('min-height', function (node) {
                return node.data.size[1] + 'px';
            })
            .style('left', function (node) {
                return nodeBox(node, extents).left + 'px';
            })
            .style('top', function (node) {
                return nodeBox(node, extents).top + 'px';
            })
            .html(function (node) {
                return cardHtml(node.data);
            });

        if (keepScroll) {
            viewport.scrollLeft = previousScrollLeft;
            viewport.scrollTop = previousScrollTop;
        } else if (!state.centered) {
            centerChart();
            state.centered = true;
        }
    }

    function targetUserIdForCard(card) {
        if (!card) {
            return null;
        }

        if (card.hasAttribute('data-root-node')) {
            return null;
        }

        return Number(card.getAttribute('data-user-id'));
    }

    function canDropOnCard(card) {
        if (!dragState || !card) {
            return false;
        }

        const targetUserId = targetUserIdForCard(card);

        if (targetUserId && Number(dragState.userId) === Number(targetUserId)) {
            return false;
        }

        if (targetUserId && wouldCreateLoop(dragState.userId, targetUserId)) {
            return false;
        }

        return true;
    }

    function setActiveDropCard(card) {
        if (activeDropCard === card) {
            return;
        }

        if (activeDropCard) {
            activeDropCard.classList.remove('is-drop-target');
        }

        activeDropCard = card;

        if (activeDropCard) {
            activeDropCard.classList.add('is-drop-target');
        }
    }

    function moveDragGhost(clientX, clientY) {
        if (!dragState || !dragState.ghost) {
            return;
        }

        dragState.ghost.style.left = clientX + 'px';
        dragState.ghost.style.top = clientY + 'px';
    }

    function handlePointerMove(event) {
        if (!dragState) {
            return;
        }

        event.preventDefault();
        moveDragGhost(event.clientX, event.clientY);

        const card = document
            .elementFromPoint(event.clientX, event.clientY)
            ?.closest('.umap-tree-card[data-user-id], .umap-tree-card[data-root-node]');

        setActiveDropCard(canDropOnCard(card) ? card : null);
    }

    function cleanupPointerDrag() {
        if (activeDropCard) {
            activeDropCard.classList.remove('is-drop-target');
            activeDropCard = null;
        }

        if (dragState && dragState.sourceCard) {
            dragState.sourceCard.classList.remove('is-drag-source');
        }

        if (dragState && dragState.ghost) {
            dragState.ghost.remove();
        }

        dragState = null;
        document.removeEventListener('pointermove', handlePointerMove);
        document.removeEventListener('pointerup', endPointerDrag);
        document.removeEventListener('pointercancel', cleanupPointerDrag);
    }

    function cleanupPan() {
        if (!panState) {
            return;
        }

        viewport.classList.remove('is-panning');
        panState = null;
        document.removeEventListener('pointermove', handlePanMove);
        document.removeEventListener('pointerup', endPan);
        document.removeEventListener('pointercancel', cleanupPan);
    }

    function handlePanMove(event) {
        if (!panState) {
            return;
        }

        event.preventDefault();
        const deltaX = event.clientX - panState.startX;
        const deltaY = event.clientY - panState.startY;
        viewport.scrollLeft = panState.scrollLeft - deltaX;
        viewport.scrollTop = panState.scrollTop - deltaY;
    }

    function endPan() {
        cleanupPan();
    }

    function startPan(event) {
        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        if (event.target.closest('.umap-tree-card')) {
            return;
        }

        panState = {
            startX: event.clientX,
            startY: event.clientY,
            scrollLeft: viewport.scrollLeft,
            scrollTop: viewport.scrollTop,
        };

        viewport.classList.add('is-panning');
        document.addEventListener('pointermove', handlePanMove);
        document.addEventListener('pointerup', endPan);
        document.addEventListener('pointercancel', cleanupPan);
    }

    function endPointerDrag(event) {
        if (!dragState) {
            return;
        }

        event.preventDefault();
        const dropCard = activeDropCard;
        const userId = dragState.userId;
        cleanupPointerDrag();

        if (!dropCard) {
            showStatus('Drop cancelled.');
            return;
        }

        applyParentUser(userId, targetUserIdForCard(dropCard));
    }

    function startPointerDrag(event, card, userId) {
        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const ghost = document.createElement('div');
        ghost.className = 'umap-drag-ghost';
        ghost.innerHTML = card.outerHTML;
        document.body.appendChild(ghost);

        card.classList.add('is-drag-source');
        dragState = {
            userId: Number(userId),
            sourceCard: card,
            ghost: ghost,
        };

        moveDragGhost(event.clientX, event.clientY);
        document.addEventListener('pointermove', handlePointerMove);
        document.addEventListener('pointerup', endPointerDrag);
        document.addEventListener('pointercancel', cleanupPointerDrag);
    }

    function applyParentUser(childUserId, parentUserId) {
        const childUser = userById(childUserId);

        if (!childUser) {
            return;
        }

        if (parentUserId && Number(childUserId) === Number(parentUserId)) {
            showStatus('A user cannot be mapped under themselves.');
            return;
        }

        if (parentUserId && wouldCreateLoop(childUserId, parentUserId)) {
            showStatus('This mapping would create a reporting loop.');
            return;
        }

        const select = document.querySelector('[data-parent-select][data-user-id="' + childUserId + '"]');

        syncSelectValue(select, parentUserId);

        childUser.parentUserId = parentUserId ? Number(parentUserId) : null;
        renderChart(true);
        showStatus(parentUserId ? 'Reporting manager updated on chart.' : 'User moved to top level.');
    }

    function centerChart() {
        viewport.scrollLeft = Math.max(0, (stage.scrollWidth - viewport.clientWidth) / 2);
        viewport.scrollTop = 0;
    }

    function isChartFullscreen() {
        return document.fullscreenElement === chartCard || chartCard.classList.contains('is-chart-fullscreen');
    }

    function updateFullscreenButton() {
        if (!fullscreenButton || !fullscreenIcon) {
            return;
        }

        const fullscreen = isChartFullscreen();
        fullscreenButton.setAttribute('title', fullscreen ? 'Exit full screen' : 'Full screen');
        fullscreenButton.setAttribute('aria-label', fullscreen ? 'Exit full screen' : 'Full screen');
        fullscreenIcon.className = fullscreen ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
    }

    async function toggleFullscreen() {
        if (!chartCard) {
            return;
        }

        if (document.fullscreenElement !== chartCard && chartCard.classList.contains('is-chart-fullscreen')) {
            chartCard.classList.remove('is-chart-fullscreen');
            document.body.style.overflow = '';
            updateFullscreenButton();
            renderChart(true);
            return;
        }

        try {
            if (document.fullscreenElement === chartCard) {
                await document.exitFullscreen();
            } else if (chartCard.requestFullscreen) {
                await chartCard.requestFullscreen();
            } else {
                chartCard.classList.toggle('is-chart-fullscreen');
                document.body.style.overflow = chartCard.classList.contains('is-chart-fullscreen') ? 'hidden' : '';
                updateFullscreenButton();
                renderChart(true);
            }
        } catch (error) {
            chartCard.classList.toggle('is-chart-fullscreen');
            document.body.style.overflow = chartCard.classList.contains('is-chart-fullscreen') ? 'hidden' : '';
            updateFullscreenButton();
            renderChart(true);
        }
    }

    document.querySelectorAll('[data-parent-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            const userId = Number(select.getAttribute('data-user-id'));
            const managerId = select.value ? Number(select.value) : null;

            if (managerId && wouldCreateLoop(userId, managerId)) {
                showStatus('This mapping would create a reporting loop.');
                syncSelectValue(select, userById(userId)?.parentUserId ?? null);
                return;
            }

            renderChart(true);
        });
    });

    document.querySelector('[data-chart-refresh]')?.addEventListener('click', function () {
        renderChart(true);
        showStatus('Chart refreshed.');
    });

    viewport.addEventListener('pointerdown', function (event) {
        startPan(event);
    });

    viewport.addEventListener('wheel', function (event) {
        event.preventDefault();
        const delta = event.deltaY < 0 ? 0.1 : -0.1;
        setZoomFromWheel(state.zoom + delta, event.clientX, event.clientY);
    }, { passive: false });

    document.querySelector('[data-chart-zoom-in]')?.addEventListener('click', function () {
        setZoom(state.zoom + 0.1);
        showStatus('Chart zoomed in.');
    });

    document.querySelector('[data-chart-zoom-out]')?.addEventListener('click', function () {
        setZoom(state.zoom - 0.1);
        showStatus('Chart zoomed out.');
    });

    document.querySelector('[data-chart-zoom-reset]')?.addEventListener('click', function () {
        setZoom(1);
        showStatus('Chart zoom reset.');
    });

    fullscreenButton?.addEventListener('click', function () {
        toggleFullscreen();
    });

    document.addEventListener('fullscreenchange', function () {
        if (chartCard) {
            chartCard.classList.toggle('is-chart-fullscreen', document.fullscreenElement === chartCard);
        }
        document.body.style.overflow = document.fullscreenElement === chartCard ? 'hidden' : '';
        updateFullscreenButton();
        renderChart(true);
    });

    document.querySelector('[data-chart-center]')?.addEventListener('click', function () {
        centerChart();
        showStatus('Chart centered.');
    });

    applyZoom();
    renderChart(false);
});
</script>
@endpush
