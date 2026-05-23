@extends('layouts.app')

@section('title', 'Role Mapping')

@push('styles')
<style>
.map-page { min-height:100%; padding:28px; background:#f4f5f7; }
.map-topbar { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:18px; }
.map-title { margin:0; font-size:22px; font-weight:800; color:#121212; }
.map-subtitle { margin-top:4px; font-size:13px; color:#7c7c7c; }
.map-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.map-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:38px; padding:9px 15px; border-radius:10px; border:1px solid #e1dee3; background:#fff; color:#121212; text-decoration:none; font-size:13px; font-weight:700; cursor:pointer; transition:all .16s ease; }
.map-btn:hover { border-color:#fdba74; color:#ea580c; }
.map-btn-primary { border-color:#fe5f04; background:#fe5f04; color:#fff; }
.map-btn-primary:hover { color:#fff; background:#ea580c; border-color:#ea580c; }
.map-alert { margin-bottom:14px; padding:12px 14px; border-radius:10px; font-size:13px; }
.map-alert.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.map-alert.error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.map-section-stack { display:flex; flex-direction:column; gap:18px; }
.map-card { background:#fff; border:1px solid #e1dee3; border-radius:14px; overflow:hidden; box-shadow:0 10px 26px rgba(18,18,18,.04); }
.map-chart-card:fullscreen { width:100vw; height:100vh; border-radius:0; border:0; background:#fff; display:flex; flex-direction:column; }
.map-chart-card.is-chart-fullscreen { position:fixed; inset:0; z-index:4500; width:100vw; height:100vh; border-radius:0; border:0; background:#fff; display:flex; flex-direction:column; }
.map-chart-card:fullscreen .map-card-head,
.map-chart-card.is-chart-fullscreen .map-card-head { flex:0 0 auto; }
.map-chart-card:fullscreen .map-chart-body,
.map-chart-card.is-chart-fullscreen .map-chart-body { flex:1 1 auto; min-height:0; padding:12px; display:flex; flex-direction:column; }
.map-chart-card:fullscreen .map-tree-shell,
.map-chart-card.is-chart-fullscreen .map-tree-shell { flex:1 1 auto; min-width:0; min-height:0; display:flex; flex-direction:column; border-radius:12px; }
.map-chart-card:fullscreen .map-tree-toolbar,
.map-chart-card.is-chart-fullscreen .map-tree-toolbar { flex:0 0 auto; }
.map-chart-card:fullscreen .map-tree-viewport,
.map-chart-card.is-chart-fullscreen .map-tree-viewport { flex:1 1 auto; min-height:0; max-height:none; }
.map-chart-card:fullscreen .map-chart-status,
.map-chart-card.is-chart-fullscreen .map-chart-status { flex:0 0 auto; }
.map-card-head { padding:16px 18px; border-bottom:1px solid #f1eff3; display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
.map-card-title { font-size:15px; font-weight:800; color:#121212; }
.map-card-sub { margin-top:3px; font-size:12px; color:#7c7c7c; line-height:1.5; }
.map-card-meta { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
.map-role-count { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; background:#eef4ff; color:#3355aa; font-size:11px; font-weight:800; white-space:nowrap; }
.map-role-count.orange { background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; }

.map-chart-body { padding:18px; background:#f6f7f9; }
.map-tree-shell { min-width:1040px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; overflow:hidden; }
.map-tree-toolbar { min-height:56px; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #eceff3; background:#fff; }
.map-tree-legend { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.map-legend-chip { display:inline-flex; align-items:center; gap:7px; padding:6px 9px; border-radius:999px; background:#f8fafc; border:1px solid #e5e7eb; font-size:11px; font-weight:800; color:#334155; }
.map-legend-dot { width:9px; height:9px; border-radius:999px; flex:0 0 auto; }
.map-legend-dot.company { background:#087f3b; }
.map-legend-dot.team { background:#3aa76d; }
.map-legend-dot.tl { background:#2588b8; }
.map-legend-dot.self { background:#d49513; }
.map-tree-actions { display:flex; gap:8px; flex-wrap:wrap; }
.map-icon-btn { width:36px; height:36px; border:1px solid #e5e7eb; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:#fff; color:#475569; cursor:pointer; transition:all .16s ease; }
.map-icon-btn:hover { border-color:#fdba74; color:#ea580c; background:#fff7ed; }
.map-zoom-label { display:inline-flex; align-items:center; justify-content:center; min-width:64px; height:36px; padding:0 10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; color:#475569; font-size:12px; font-weight:800; }
.map-tree-viewport { position:relative; min-height:640px; max-height:74vh; overflow:auto; background:#f8efcf; scrollbar-gutter:stable; }
.map-tree-viewport.is-panning { cursor:grabbing; }
.map-tree-stage { position:relative; min-width:1160px; min-height:720px; background:#f8efcf; background-image:linear-gradient(rgba(148,163,184,.18) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,.18) 1px, transparent 1px); background-size:34px 34px; }
.map-tree-svg { position:absolute; inset:0; overflow:visible; pointer-events:none; }
.map-tree-nodes { position:absolute; inset:0; pointer-events:none; }
.map-tree-link { fill:none; stroke:#a2a8b2; stroke-width:1.7px; stroke-linecap:round; }
.map-tree-card { position:absolute; width:288px; min-height:108px; padding:15px 16px 15px 50px; border-radius:10px; border:1px solid #d7dde6; background:#fff; color:#172033; box-shadow:0 16px 34px rgba(15,23,42,.12); pointer-events:auto; cursor:grab; user-select:none; touch-action:none; transition:box-shadow .16s ease, transform .16s ease, border-color .16s ease; }
.map-tree-card:hover { transform:translateY(-1px); box-shadow:0 20px 42px rgba(15,23,42,.16); border-color:#f59e0b; }
.map-tree-card:active { cursor:grabbing; }
.map-tree-card::before { content:''; position:absolute; left:16px; top:16px; bottom:16px; width:9px; border-radius:999px; background:var(--node-accent, #fe5f04); opacity:.95; }
.map-tree-card::after { content:'::'; position:absolute; left:30px; top:17px; color:#8a95a5; font-size:13px; font-weight:900; line-height:1; letter-spacing:-3px; writing-mode:vertical-rl; }
.map-tree-card.is-root { width:320px; min-height:104px; padding:18px 20px; text-align:center; cursor:default; border-color:#9aa9c1; background:#fff; }
.map-tree-card.is-root::before,
.map-tree-card.is-root::after { display:none; }
.map-tree-card.is-company { --node-accent:#087f3b; background:#087f3b; border-color:#087f3b; color:#fff; }
.map-tree-card.is-company::after { color:rgba(255,255,255,.58); }
.map-tree-card.is-team { --node-accent:#2f9e62; background:#dcf5e4; border-color:#79c79a; color:#102018; }
.map-tree-card.is-tl { --node-accent:#2588b8; background:#dff2fa; border-color:#7fc6e5; color:#102636; }
.map-tree-card.is-self { --node-accent:#d49513; background:#fff0bd; border-color:#f1c94f; color:#2a1b02; }
.map-tree-card.is-drop-target { outline:4px solid rgba(254,95,4,.28); outline-offset:5px; border-color:#fe5f04; }
.map-tree-card.is-drag-source { opacity:.42; }
.map-tree-name { display:block; font-size:16px; font-weight:900; line-height:1.25; color:inherit; overflow-wrap:anywhere; }
.map-tree-access { display:block; margin-top:7px; font-size:12px; font-weight:900; line-height:1.2; color:inherit; opacity:.92; }
.map-tree-meta { display:block; margin-top:8px; font-size:11px; font-weight:800; line-height:1.2; color:inherit; opacity:.72; }
.map-drag-ghost { position:fixed; z-index:5000; pointer-events:none; transform:translate(-50%, -50%) rotate(.5deg); opacity:.94; }
.map-drag-ghost .map-tree-card { position:relative; left:auto !important; top:auto !important; cursor:grabbing; box-shadow:0 24px 48px rgba(15,23,42,.24); }
.map-chart-status { display:none; margin-top:10px; padding:10px 12px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; font-weight:800; }
.map-chart-status.is-visible { display:block; }

.map-table-wrap { overflow-x:auto; }
.map-table { width:100%; border-collapse:collapse; min-width:980px; }
.map-table th, .map-table td { padding:14px 16px; border-bottom:1px solid #f1eff3; text-align:left; font-size:13px; vertical-align:middle; }
.map-table th { background:#fafafa; color:#8a8a8a; font-size:11px; font-weight:800; letter-spacing:.6px; text-transform:uppercase; }
.map-table tr:last-child td { border-bottom:none; }
.map-role { font-weight:800; color:#121212; }
.map-muted { color:#7c7c7c; font-size:12px; line-height:1.45; }
.map-badge { display:inline-flex; padding:4px 9px; border-radius:999px; background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; font-size:11px; font-weight:800; white-space:nowrap; }
.map-select { width:100%; min-width:190px; height:38px; padding:0 11px; border:1px solid #e1dee3; border-radius:10px; background:#fff; font-size:13px; color:#121212; }
.map-select:focus { outline:none; border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.map-access-chip { display:inline-flex; padding:5px 9px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; white-space:nowrap; }
.map-access-chip.company { background:#dcfce7; color:#166534; border-color:#bbf7d0; }
.map-access-chip.team { background:#e8fff1; color:#047857; border-color:#bbf7d0; }
.map-access-chip.tl { background:#e0f2fe; color:#0369a1; border-color:#bae6fd; }
.map-access-chip.self { background:#fffbeb; color:#92400e; border-color:#fde68a; }
.map-empty-box { padding:32px 18px; text-align:center; color:#8a8a8a; font-size:13px; }
@media (max-width: 1000px) {
    .map-page { padding:18px; }
    .map-topbar { flex-direction:column; align-items:flex-start; }
    .map-card-head { flex-direction:column; }
    .map-card-meta { justify-content:flex-start; }
    .map-chart-body { padding:12px; overflow-x:auto; }
    .map-tree-shell { min-width:920px; }
    .map-tree-viewport { min-height:560px; max-height:70vh; }
}
</style>
@endpush

@section('content')
<div class="map-page">
    <div class="map-topbar">
        <div>
            <h2 class="map-title">Role Mapping</h2>
            <div class="map-subtitle">Access levels and parent-role hierarchy for CRM visibility.</div>
        </div>
        <div class="map-actions">
            <a href="{{ route('auth.index') }}" class="map-btn">Back</a>
            <button type="submit" form="roleMappingForm" class="map-btn map-btn-primary">Save Mapping</button>
        </div>
    </div>

    @if(session('success'))
        <div class="map-alert success">{!! session('success') !!}</div>
    @endif
    @if(session('error'))
        <div class="map-alert error">{!! session('error') !!}</div>
    @endif

    <div class="map-section-stack">
        <div id="roleChartCard" class="map-card map-chart-card">
            <div class="map-card-head">
                <div>
                    <div class="map-card-title">Role Organization Chart</div>
                    <div class="map-card-sub">D3 flextree layout with draggable role hierarchy mapping.</div>
                </div>
                <div class="map-card-meta">
                    <span class="map-role-count orange">{{ $roleChart['mappedCount'] }} role link{{ $roleChart['mappedCount'] === 1 ? '' : 's' }}</span>
                    <span class="map-role-count">{{ $roleChart['parentCount'] }} parent role{{ $roleChart['parentCount'] === 1 ? '' : 's' }}</span>
                </div>
            </div>

            <div class="map-chart-body">
                @if($roles->isEmpty())
                    <div class="map-empty-box">No roles found.</div>
                @else
                    <div class="map-tree-shell">
                        <div class="map-tree-toolbar">
                            <div class="map-tree-legend" aria-label="Access level legend">
                                <span class="map-legend-chip"><span class="map-legend-dot company"></span>Company</span>
                                <span class="map-legend-chip"><span class="map-legend-dot team"></span>Team</span>
                                <span class="map-legend-chip"><span class="map-legend-dot tl"></span>TL</span>
                                <span class="map-legend-chip"><span class="map-legend-dot self"></span>Self</span>
                            </div>
                            <div class="map-tree-actions">
                                <button type="button" class="map-icon-btn" data-chart-zoom-out title="Zoom out" aria-label="Zoom out">
                                    <i class="bi bi-zoom-out"></i>
                                </button>
                                <div class="map-zoom-label" data-chart-zoom-label>100%</div>
                                <button type="button" class="map-icon-btn" data-chart-zoom-in title="Zoom in" aria-label="Zoom in">
                                    <i class="bi bi-zoom-in"></i>
                                </button>
                                <button type="button" class="map-icon-btn" data-chart-zoom-reset title="Reset zoom" aria-label="Reset zoom">
                                    <i class="bi bi-aspect-ratio"></i>
                                </button>
                                <button type="button" class="map-icon-btn" data-chart-center title="Center chart" aria-label="Center chart">
                                    <i class="bi bi-bullseye"></i>
                                </button>
                                <button type="button" class="map-icon-btn" data-chart-refresh title="Refresh chart" aria-label="Refresh chart">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button type="button" class="map-icon-btn" data-chart-fullscreen title="Full screen" aria-label="Full screen">
                                    <i class="bi bi-arrows-fullscreen" data-chart-fullscreen-icon></i>
                                </button>
                            </div>
                        </div>
                        <div id="roleTreeViewport" class="map-tree-viewport">
                            <div id="roleHierarchyChart" class="map-tree-stage">
                                <svg id="roleTreeSvg" class="map-tree-svg" aria-hidden="true"></svg>
                                <div id="roleTreeNodes" class="map-tree-nodes"></div>
                            </div>
                        </div>
                    </div>
                    <div id="roleChartStatus" class="map-chart-status" aria-live="polite"></div>
                @endif
            </div>
        </div>

        <form id="roleMappingForm" method="POST" action="{{ route('auth.role-mappings.update') }}">
            @csrf
            @method('PUT')

            <div class="map-card">
                <div class="map-card-head">
                    <div>
                        <div class="map-card-title">Access Level and Role Mapping</div>
                        <div class="map-card-sub">Choose access level and set which parent role each role belongs under.</div>
                    </div>
                    <span class="map-role-count">{{ $roles->count() }} role{{ $roles->count() === 1 ? '' : 's' }}</span>
                </div>

                <div class="map-table-wrap">
                    <table class="map-table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Role Key</th>
                                <th>Users</th>
                                <th>Current Access</th>
                                <th>Access Level</th>
                                <th>Parent Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                @php
                                    $roleName = $role->display_name ?: str($role->name)->after('__')->replace('_', ' ')->title()->value();
                                    $inferred = app(\App\Services\DataVisibilityService::class)->defaultAccessLevelForRole($role);
                                    $selected = old("mappings.{$role->id}", $role->roleMapping?->access_level ?? $inferred);
                                    $selectedParent = old("parents.{$role->id}", $role->roleParentMapping?->parent_role_id);
                                    $selectedParent = $selectedParent ? (int) $selectedParent : null;
                                    $accessClass = in_array($selected, ['company', 'team', 'tl'], true) ? $selected : 'self';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="map-role">{{ $roleName }}</div>
                                        <div class="map-muted">{{ $role->description ?: 'No description' }}</div>
                                    </td>
                                    <td><span class="map-badge">{{ $role->name }}</span></td>
                                    <td>{{ $role->users->count() }}</td>
                                    <td>
                                        <span class="map-access-chip {{ $accessClass }}" data-current-access="{{ $role->id }}">
                                            {{ \App\Models\RoleMapping::labelFor($selected) }}
                                        </span>
                                    </td>
                                    <td>
                                        <select class="map-select" name="mappings[{{ $role->id }}]" data-access-select data-role-id="{{ $role->id }}">
                                            @foreach($accessLevels as $value => $label)
                                                <option value="{{ $value }}" @selected($selected === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("mappings.{$role->id}")<div class="map-muted">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <select class="map-select" name="parents[{{ $role->id }}]" data-parent-select data-role-id="{{ $role->id }}">
                                            <option value="">Top Level</option>
                                            @foreach($roles as $parentRole)
                                                @continue($parentRole->id === $role->id)
                                                @php
                                                    $parentName = $parentRole->display_name ?: str($parentRole->name)->after('__')->replace('_', ' ')->title()->value();
                                                @endphp
                                                <option value="{{ $parentRole->id }}" @selected($selectedParent === (int) $parentRole->id)>{{ $parentName }}</option>
                                            @endforeach
                                        </select>
                                        @error("parents.{$role->id}")<div class="map-muted">{{ $message }}</div>@enderror
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="map-muted">No roles found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/d3-flextree@2.1.2/build/d3-flextree.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewport = document.getElementById('roleTreeViewport');
    const stage = document.getElementById('roleHierarchyChart');
    const svg = document.getElementById('roleTreeSvg');
    const nodeLayer = document.getElementById('roleTreeNodes');
    const statusElement = document.getElementById('roleChartStatus');
    const chartCard = document.getElementById('roleChartCard');
    const fullscreenButton = document.querySelector('[data-chart-fullscreen]');
    const fullscreenIcon = document.querySelector('[data-chart-fullscreen-icon]');
    const zoomLabel = document.querySelector('[data-chart-zoom-label]');
    const state = {
        roles: @json($roleChart['nodes']),
        centered: false,
        zoom: 1,
    };

    const sizes = {
        role: [288, 108],
        root: [320, 104],
        xGap: 78,
        yGap: 112,
        paddingX: 90,
        paddingY: 70,
    };

    let dragState = null;
    let activeDropCard = null;
    let panState = null;

    if (!viewport || !stage || !svg || !nodeLayer || !Array.isArray(state.roles) || state.roles.length === 0) {
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

    function accessClass(accessLevel) {
        return ['company', 'team', 'tl'].includes(accessLevel) ? accessLevel : 'self';
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

    function roleById(roleId) {
        const numericRoleId = Number(roleId);

        return state.roles.find(function (role) {
            return Number(role.roleId) === numericRoleId;
        });
    }

    function syncStateFromInputs() {
        document.querySelectorAll('[data-parent-select]').forEach(function (select) {
            const role = roleById(select.getAttribute('data-role-id'));

            if (role) {
                role.parentRoleId = select.value ? Number(select.value) : null;
            }
        });

        document.querySelectorAll('[data-access-select]').forEach(function (select) {
            const role = roleById(select.getAttribute('data-role-id'));
            const selectedOption = select.options[select.selectedIndex];

            if (!role || !selectedOption) {
                return;
            }

            role.accessLevel = select.value;
            role.accessLabel = selectedOption.textContent.trim();

            const chip = document.querySelector('[data-current-access="' + role.roleId + '"]');
            if (chip) {
                chip.textContent = role.accessLabel;
                chip.className = 'map-access-chip ' + accessClass(role.accessLevel);
            }
        });

        refreshChildCounts();
    }

    function refreshChildCounts() {
        state.roles.forEach(function (role) {
            role.childCount = 0;
        });

        state.roles.forEach(function (role) {
            if (!role.parentRoleId) {
                return;
            }

            const parentRole = roleById(role.parentRoleId);

            if (parentRole) {
                parentRole.childCount += 1;
            }
        });
    }

    function wouldCreateLoop(childRoleId, parentRoleId) {
        const visited = new Set([Number(childRoleId)]);
        let currentParentId = Number(parentRoleId);

        while (currentParentId) {
            if (visited.has(currentParentId)) {
                return true;
            }

            visited.add(currentParentId);
            const parentRole = roleById(currentParentId);
            currentParentId = parentRole && parentRole.parentRoleId ? Number(parentRole.parentRoleId) : null;
        }

        return false;
    }

    function buildTreeData() {
        const roleNodes = new Map();
        const root = {
            id: 'role-root',
            name: 'Top Level Roles',
            accessLabel: 'Role Mapping',
            userCount: 0,
            childCount: 0,
            isRoot: true,
            size: sizes.root,
            children: [],
        };

        state.roles.forEach(function (role) {
            roleNodes.set(Number(role.roleId), {
                ...role,
                id: 'role-' + role.roleId,
                size: sizes.role,
                children: [],
            });
        });

        state.roles.forEach(function (role) {
            const node = roleNodes.get(Number(role.roleId));
            const parentId = role.parentRoleId ? Number(role.parentRoleId) : null;
            const parentNode = parentId ? roleNodes.get(parentId) : null;

            if (parentNode && parentId !== Number(role.roleId) && !wouldCreateLoop(role.roleId, parentId)) {
                parentNode.children.push(node);
            } else {
                root.children.push(node);
            }
        });

        root.children = root.children.sort(sortNodes);
        roleNodes.forEach(function (node) {
            node.children = node.children.sort(sortNodes);
        });
        root.childCount = root.children.length;

        return root;
    }

    function sortNodes(a, b) {
        return String(a.name).localeCompare(String(b.name));
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
        const title = escapeHtml(data.name);
        const access = escapeHtml(data.accessLabel);
        const meta = data.isRoot
            ? data.childCount + ' top level roles'
            : data.userCount + ' users | ' + data.childCount + ' child roles';

        return '<span class="map-tree-name">' + title + '</span>' +
            '<span class="map-tree-access">' + access + '</span>' +
            '<span class="map-tree-meta">' + escapeHtml(meta) + '</span>';
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
                return nodeA.parent === nodeB.parent ? 28 : 46;
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
            .selectAll('path.map-tree-link')
            .data(root.links(), function (link) {
                return link.source.data.id + '-' + link.target.data.id;
            })
            .join('path')
            .attr('class', 'map-tree-link')
            .attr('d', function (link) {
                return linkPath(link, extents);
            });

        const cards = d3.select(nodeLayer)
            .selectAll('div.map-tree-card')
            .data(nodes, function (node) {
                return node.data.id;
            });

        cards.exit().remove();

        const enteredCards = cards.enter()
            .append('div')
            .on('pointerdown', function (event, node) {
                if (!node.data.isRoot) {
                    startPointerDrag(event, this, node.data.roleId);
                }
            });

        enteredCards.merge(cards)
            .attr('class', function (node) {
                return 'map-tree-card ' + (node.data.isRoot ? 'is-root' : 'is-' + accessClass(node.data.accessLevel));
            })
            .attr('data-root-node', function (node) {
                return node.data.isRoot ? '1' : null;
            })
            .attr('data-role-id', function (node) {
                return node.data.isRoot ? null : node.data.roleId;
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

    function targetRoleIdForCard(card) {
        if (!card) {
            return null;
        }

        if (card.hasAttribute('data-root-node')) {
            return null;
        }

        return Number(card.getAttribute('data-role-id'));
    }

    function canDropOnCard(card) {
        if (!dragState || !card) {
            return false;
        }

        const targetRoleId = targetRoleIdForCard(card);

        if (targetRoleId && Number(dragState.roleId) === Number(targetRoleId)) {
            return false;
        }

        if (targetRoleId && wouldCreateLoop(dragState.roleId, targetRoleId)) {
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
        if (!dragState?.ghost) {
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
            ?.closest('.map-tree-card[data-role-id], .map-tree-card[data-root-node]');

        setActiveDropCard(canDropOnCard(card) ? card : null);
    }

    function cleanupPointerDrag() {
        if (activeDropCard) {
            activeDropCard.classList.remove('is-drop-target');
            activeDropCard = null;
        }

        if (dragState?.sourceCard) {
            dragState.sourceCard.classList.remove('is-drag-source');
        }

        if (dragState?.ghost) {
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

        if (event.target.closest('.map-tree-card')) {
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
        const roleId = dragState.roleId;
        cleanupPointerDrag();

        if (!dropCard) {
            showStatus('Drop cancelled.');
            return;
        }

        applyParentRole(roleId, targetRoleIdForCard(dropCard));
    }

    function startPointerDrag(event, card, roleId) {
        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const ghost = document.createElement('div');
        ghost.className = 'map-drag-ghost';
        ghost.innerHTML = card.outerHTML;
        document.body.appendChild(ghost);

        card.classList.add('is-drag-source');
        dragState = {
            roleId: Number(roleId),
            sourceCard: card,
            ghost: ghost,
        };

        moveDragGhost(event.clientX, event.clientY);
        document.addEventListener('pointermove', handlePointerMove);
        document.addEventListener('pointerup', endPointerDrag);
        document.addEventListener('pointercancel', cleanupPointerDrag);
    }

    function applyParentRole(childRoleId, parentRoleId) {
        const childRole = roleById(childRoleId);

        if (!childRole) {
            return;
        }

        if (parentRoleId && Number(childRoleId) === Number(parentRoleId)) {
            showStatus('A role cannot be mapped under itself.');
            return;
        }

        if (parentRoleId && wouldCreateLoop(childRoleId, parentRoleId)) {
            showStatus('This role mapping would create a hierarchy loop.');
            return;
        }

        const select = document.querySelector('[data-parent-select][data-role-id="' + childRoleId + '"]');

        if (select) {
            select.value = parentRoleId ? String(parentRoleId) : '';
        }

        childRole.parentRoleId = parentRoleId ? Number(parentRoleId) : null;
        renderChart(true);
    }

    function centerChart() {
        viewport.scrollLeft = Math.max(0, (stage.scrollWidth - viewport.clientWidth) / 2);
        viewport.scrollTop = 0;
    }

    function isChartFullscreen() {
        return document.fullscreenElement === chartCard || chartCard?.classList.contains('is-chart-fullscreen');
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

    document.querySelectorAll('[data-parent-select], [data-access-select]').forEach(function (select) {
        select.addEventListener('change', function () {
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
        chartCard?.classList.toggle('is-chart-fullscreen', document.fullscreenElement === chartCard);
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
