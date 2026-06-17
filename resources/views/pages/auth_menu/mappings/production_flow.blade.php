@extends('layouts.app')

@section('title', 'Production Workflow Mapping')

@push('styles')
<style>
.pf-page{min-height:100%;padding:28px;background:linear-gradient(180deg,#f6f8fb 0%,#f2f5f8 100%)}
.pf-topbar{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:20px}
.pf-title{margin:0;font-size:28px;font-weight:900;color:#111827}
.pf-subtitle{margin-top:8px;max-width:860px;font-size:14px;line-height:1.7;color:#6b7280}
.pf-actions{display:flex;gap:10px;flex-wrap:wrap}
.pf-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:10px 16px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#111827;text-decoration:none;font-size:13px;font-weight:800;transition:all .16s ease}
.pf-btn:hover{border-color:#fdba74;color:#c2410c}
.pf-btn-primary{background:#111827;border-color:#111827;color:#fff}
.pf-btn-primary:hover{background:#1f2937;border-color:#1f2937;color:#fff}
.pf-shell{display:grid;gap:18px}
.pf-alert{padding:13px 15px;border-radius:14px;font-size:13px;font-weight:700}
.pf-alert.success{background:#ecfdf5;border:1px solid #bbf7d0;color:#166534}
.pf-alert.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.pf-card{background:#fff;border:1px solid #e5e7eb;border-radius:22px;box-shadow:0 14px 34px rgba(15,23,42,.05)}
.pf-hero{display:grid;grid-template-columns:1.1fr .9fr;gap:18px}
.pf-hero-main{padding:24px;background:linear-gradient(135deg,#fffdf8 0%,#ffffff 55%,#eefbf5 100%);position:relative;overflow:hidden}
.pf-hero-main::after{content:'';position:absolute;right:-50px;top:-50px;width:170px;height:170px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.18),transparent 70%)}
.pf-kicker{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;background:#ecfdf5;color:#047857;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.pf-hero-title{margin:16px 0 0;font-size:26px;font-weight:900;line-height:1.15;color:#111827}
.pf-hero-copy{margin:12px 0 0;font-size:14px;line-height:1.7;color:#6b7280;max-width:680px}
.pf-badges{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
.pf-badge{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:14px;background:#fff;border:1px solid #e5e7eb;color:#475569;font-size:12px;font-weight:800}
.pf-side-panel{padding:20px}
.pf-side-title{font-size:16px;font-weight:900;color:#111827}
.pf-side-copy{margin-top:6px;font-size:13px;line-height:1.7;color:#6b7280}
.pf-products{display:flex;flex-wrap:wrap;gap:9px;margin-top:16px}
.pf-product-pill{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:14px;background:linear-gradient(135deg,#f0fdf4,#ffffff);border:1px solid #ccefd8;font-size:12px;font-weight:800;color:#065f46}
.pf-product-price{font-size:11px;font-weight:700;color:#0f766e}
.pf-empty{margin-top:16px;padding:18px;border:1px dashed #dbe2ea;border-radius:16px;background:#fafafa;color:#6b7280;font-size:13px;line-height:1.6}
.pf-note{padding:14px 15px;border-radius:16px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:12px;line-height:1.6}
.pf-form-card{padding:22px}
.pf-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:18px}
.pf-section-title{margin:0;font-size:18px;font-weight:900;color:#111827}
.pf-section-copy{margin-top:6px;font-size:13px;line-height:1.6;color:#6b7280}
.pf-count{display:inline-flex;align-items:center;justify-content:center;min-width:44px;height:44px;padding:0 14px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:13px;font-weight:900}
.pf-stage-grid{display:grid;gap:16px}
.pf-stage{position:relative;padding:20px;border-radius:22px;border:1px solid #e5e7eb;background:linear-gradient(135deg,#ffffff 0%,#fbfdff 100%)}
.pf-stage::before{content:'';position:absolute;left:0;top:0;bottom:0;width:6px;border-radius:22px 0 0 22px;background:var(--stage-accent,#10b981)}
.pf-stage-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}
.pf-stage-step{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.9);border:1px solid #e5e7eb;font-size:13px;font-weight:900;color:#111827}
.pf-stage-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#6b7280}
.pf-stage-title{margin-top:6px;font-size:20px;font-weight:900;color:#111827}
.pf-stage-copy{margin-top:8px;font-size:13px;line-height:1.7;color:#6b7280}
.pf-stage-body{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:16px}
.pf-field{display:grid;gap:8px}
.pf-field--full{grid-column:1/-1}
.pf-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#6b7280}
.pf-select,.pf-input{width:100%;min-height:42px;padding:10px 12px;border:1px solid #dbe2ea;border-radius:12px;background:#fff;font-size:13px;color:#111827}
.pf-select:focus,.pf-input:focus{outline:none;border-color:#10b981;box-shadow:0 0 0 4px rgba(16,185,129,.12)}
.pf-help{font-size:12px;color:#6b7280;line-height:1.5}
.pf-error{font-size:12px;color:#b91c1c}
.pf-form-actions{display:flex;justify-content:flex-end;margin-top:18px}
.pf-chart-card{overflow:hidden}
.pf-chart-head{padding:20px 22px;border-bottom:1px solid #eef2f7;display:flex;align-items:flex-end;justify-content:space-between;gap:16px}
.pf-chart-title{font-size:18px;font-weight:900;color:#111827}
.pf-chart-copy{margin-top:6px;font-size:13px;line-height:1.6;color:#6b7280}
.pf-chart-meta{display:flex;gap:10px;flex-wrap:wrap}
.pf-chart-pill{display:inline-flex;align-items:center;padding:8px 12px;border-radius:999px;background:#f8fafc;border:1px solid #e5e7eb;color:#475569;font-size:12px;font-weight:800}
.pf-chart-shell{padding:18px;background:#f8fafc}
.pf-chart-viewport{position:relative;overflow:auto;border:1px solid #e5e7eb;border-radius:18px;background:linear-gradient(180deg,#fffef8 0%,#f9fbff 100%);min-height:520px}
.pf-chart-stage{position:relative;min-width:980px;min-height:520px;background-image:linear-gradient(rgba(148,163,184,.12) 1px, transparent 1px),linear-gradient(90deg, rgba(148,163,184,.12) 1px, transparent 1px);background-size:32px 32px}
.pf-chart-svg{position:absolute;inset:0;overflow:visible;pointer-events:none}
.pf-chart-nodes{position:absolute;inset:0}
.pf-chart-link{fill:none;stroke:#9ca3af;stroke-width:2px;stroke-linecap:round}
.pf-node{position:absolute;width:280px;min-height:120px;padding:18px;border-radius:20px;border:1px solid #dbe2ea;background:#fff;box-shadow:0 16px 32px rgba(15,23,42,.08)}
.pf-node-step{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#6b7280}
.pf-node-title{margin-top:6px;font-size:18px;font-weight:900;color:#111827}
.pf-node-role-chip{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;font-size:11px;font-weight:800;margin:6px 6px 0 0}
.pf-node-group{margin-top:12px}
.pf-node-group-label{font-size:11px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.1em}
.pf-node-note{margin-top:10px;font-size:12px;line-height:1.6;color:#6b7280}
.pf-node-empty{margin-top:12px;font-size:12px;color:#94a3b8;font-weight:700}
.select2-container--default .select2-selection--multiple.pf-select2-multi{min-height:42px;border:1px solid #dbe2ea;border-radius:12px;padding:4px 6px;background:#fff}
.select2-container--default .select2-selection--multiple.pf-select2-multi .select2-selection__choice{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700}
.select2-container--default.select2-container--focus .select2-selection--multiple.pf-select2-multi{border-color:#10b981;box-shadow:0 0 0 4px rgba(16,185,129,.12)}
@media (max-width: 1100px){
    .pf-page{padding:18px}
    .pf-topbar{flex-direction:column;align-items:flex-start}
    .pf-hero{grid-template-columns:1fr}
    .pf-stage-body{grid-template-columns:1fr}
    .pf-chart-head{flex-direction:column;align-items:flex-start}
}
</style>
@endpush

@section('content')
@php
    $roleLabel = static fn ($role) => $role->display_name ?: str($role->name)->after('__')->replace('_', ' ')->title()->value();
    $valueFor = static function (string $stageKey, string $fieldKey, $default = null) use ($workflowData) {
        return old("workflow.$stageKey.$fieldKey", $workflowData[$stageKey][$fieldKey] ?? $default);
    };
@endphp
<div class="pf-page">
    <div class="pf-topbar">
        <div>
            <h2 class="pf-title">{{ $department->name }} Production Mapping</h2>
            <div class="pf-subtitle">Save development production workflow roles for this department. The saved mapping will appear below as a stage-by-stage D3 flow chart.</div>
        </div>
        <div class="pf-actions">
            <a href="{{ route('auth.production-mappings.index') }}" class="pf-btn">Back to Departments</a>
            <button type="submit" form="productionWorkflowForm" class="pf-btn pf-btn-primary">Save Workflow</button>
        </div>
    </div>

    <div class="pf-shell">
        @if(session('success'))
            <div class="pf-alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="pf-alert error">{{ session('error') }}</div>
        @endif

        <div class="pf-hero">
            <div class="pf-card pf-hero-main">
                <div class="pf-kicker">
                    <i class="bi bi-kanban"></i>
                    Workflow Blueprint
                </div>
                <div class="pf-hero-title">{{ $department->name }} Department Production Lifecycle</div>
                <div class="pf-hero-copy">Configure the role-based workflow from Production Initiation through TL and Developer allocation. Once saved, the same mapping will appear below as a D3 flow chart with role summaries.</div>
                <div class="pf-badges">
                    <span class="pf-badge"><i class="bi bi-diagram-3"></i> {{ count($stageDefinitions) }} stages</span>
                    <span class="pf-badge"><i class="bi bi-people"></i> Role-only mapping</span>
                    <span class="pf-badge"><i class="bi bi-save"></i> Department-wise saved</span>
                </div>
            </div>

            <div class="pf-card pf-side-panel">
                <div class="pf-side-title">Mapped Products</div>
                <div class="pf-side-copy">These products are already mapped to this department in the product master and are shown here for context.</div>

                @if($department->products->isEmpty())
                    <div class="pf-empty">No mapped products found for this department yet.</div>
                @else
                    <div class="pf-products">
                        @foreach($department->products as $product)
                            <span class="pf-product-pill">
                                {{ $product->package_name ?: $product->product_name }}
                                <span class="pf-product-price">Rs. {{ number_format((float) $product->final_price, 2) }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="pf-note" style="margin-top:16px;">
                    The saved role mapping can later be used for notifications, routing, and access checks.
                </div>
            </div>
        </div>

        <div class="pf-card pf-chart-card">
            <div class="pf-chart-head">
                <div>
                    <div class="pf-chart-title">Saved Workflow D3 Flow</div>
                    <div class="pf-chart-copy">This chart shows the current production mapping for the department. Every saved stage assignment is reflected here.</div>
                </div>
                <div class="pf-chart-meta">
                    <span class="pf-chart-pill">{{ $workflowChart['mappedStageCount'] }} / {{ $workflowChart['totalStageCount'] }} stages mapped</span>
                    <span class="pf-chart-pill">{{ collect($workflowChart['nodes'])->sum('assignedRoleCount') }} roles assigned</span>
                </div>
            </div>
            <div class="pf-chart-shell">
                <div class="pf-chart-viewport">
                    <div id="pfWorkflowChart" class="pf-chart-stage">
                        <svg id="pfWorkflowSvg" class="pf-chart-svg" aria-hidden="true"></svg>
                        <div id="pfWorkflowNodes" class="pf-chart-nodes"></div>
                    </div>
                </div>
            </div>
        </div>

        <form id="productionWorkflowForm" method="POST" action="{{ route('auth.production-mappings.update', $department) }}">
            @csrf
            @method('PUT')

            <div class="pf-card pf-form-card">
                <div class="pf-section-head">
                    <div>
                        <h3 class="pf-section-title">Workflow Configuration</h3>
                        <div class="pf-section-copy">Select the roles for each stage and save the workflow. You can choose multiple roles wherever needed.</div>
                    </div>
                    <span class="pf-count">{{ count($stageDefinitions) }}</span>
                </div>

                <div class="pf-stage-grid">
                    @foreach($stageDefinitions as $stageKey => $stage)
                        <div class="pf-stage" style="--stage-accent:{{ $stage['accent'] }}">
                            <div class="pf-stage-head">
                                <div>
                                    <div class="pf-stage-label">{{ $stage['step'] }}</div>
                                    <div class="pf-stage-title">{{ $stage['title'] }}</div>
                                    <div class="pf-stage-copy">{{ $stage['description'] }}</div>
                                </div>
                                <span class="pf-stage-step">{{ $loop->iteration }}</span>
                            </div>

                            <div class="pf-stage-body">
                                @foreach($stage['role_fields'] as $fieldKey => $field)
                                    @php
                                        $selectedRoleIds = $valueFor($stageKey, $fieldKey, []);
                                        $selectedRoleIds = is_array($selectedRoleIds) ? $selectedRoleIds : [];
                                    @endphp
                                    <div class="pf-field">
                                        <label class="pf-label" for="{{ $stageKey }}_{{ $fieldKey }}">{{ $field['label'] }}</label>
                                        <select id="{{ $stageKey }}_{{ $fieldKey }}"
                                                name="workflow[{{ $stageKey }}][{{ $fieldKey }}][]"
                                                class="pf-select select2 pf-multi-select"
                                                data-placeholder="{{ $field['placeholder'] }}"
                                                multiple>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @selected(in_array((int) $role->id, array_map('intval', $selectedRoleIds), true))>{{ $roleLabel($role) }}</option>
                                            @endforeach
                                        </select>
                                        @error("workflow.$stageKey.$fieldKey")<div class="pf-error">{{ $message }}</div>@enderror
                                        @error("workflow.$stageKey.$fieldKey.*")<div class="pf-error">{{ $message }}</div>@enderror
                                    </div>
                                @endforeach

                                @foreach($stage['text_fields'] as $fieldKey => $field)
                                    <div class="pf-field">
                                        <label class="pf-label" for="{{ $stageKey }}_{{ $fieldKey }}">{{ $field['label'] }}</label>
                                        <input id="{{ $stageKey }}_{{ $fieldKey }}"
                                               name="workflow[{{ $stageKey }}][{{ $fieldKey }}]"
                                               class="pf-input"
                                               type="text"
                                               value="{{ $valueFor($stageKey, $fieldKey, '') }}"
                                               placeholder="{{ $field['placeholder'] }}">
                                        @error("workflow.$stageKey.$fieldKey")<div class="pf-error">{{ $message }}</div>@enderror
                                    </div>
                                @endforeach

                                @if($stageKey === 'ovp_team_review')
                                    <div class="pf-field pf-field--full">
                                        <div class="pf-note">In the OVP review stage, passed items move to the approval team, while failed or clarification cases return to the business team roles.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pf-form-actions">
                    <button type="submit" class="pf-btn pf-btn-primary">Save Workflow</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && window.jQuery.fn.select2) {
        window.jQuery('.pf-multi-select').each(function () {
            const $select = window.jQuery(this);
            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || 'Choose roles',
            });

            $select.next('.select2-container').find('.select2-selection').addClass('pf-select2-multi');
        });
    }

    if (!window.d3) {
        return;
    }

    const chartData = @json($workflowChart);
    const stage = document.getElementById('pfWorkflowChart');
    const svg = document.getElementById('pfWorkflowSvg');
    const nodeLayer = document.getElementById('pfWorkflowNodes');

    if (!stage || !svg || !nodeLayer || !Array.isArray(chartData.nodes) || !chartData.nodes.length) {
        return;
    }

    function buildChain(index) {
        const node = chartData.nodes[index];

        if (!node) {
            return null;
        }

        const next = buildChain(index + 1);

        return {
            ...node,
            children: next ? [next] : [],
        };
    }

    const rootData = {
        id: 'root',
        title: '{{ addslashes($department->name) }} Production Flow',
        children: chartData.nodes.length ? [buildChain(0)] : [],
    };

    const layout = d3.tree().nodeSize([340, 200]);
    const root = d3.hierarchy(rootData, function (node) {
        return node.children && node.children.length ? node.children : null;
    });

    layout(root);

    const nodes = root.descendants().filter(function (node) {
        return node.depth > 0;
    });

    const links = root.links().filter(function (link) {
        return link.source.depth > 0 || link.target.depth > 0;
    });

    const minX = d3.min(nodes, function (node) { return node.x; }) ?? 0;
    const maxX = d3.max(nodes, function (node) { return node.x; }) ?? 0;
    const minY = d3.min(nodes, function (node) { return node.y; }) ?? 0;
    const maxY = d3.max(nodes, function (node) { return node.y; }) ?? 0;
    const paddingX = 180;
    const paddingY = 70;
    const stageWidth = Math.max(980, (maxX - minX) + 560);
    const stageHeight = Math.max(520, (maxY - minY) + 240);

    stage.style.width = stageWidth + 'px';
    stage.style.height = stageHeight + 'px';
    svg.setAttribute('width', stageWidth);
    svg.setAttribute('height', stageHeight);
    nodeLayer.style.width = stageWidth + 'px';
    nodeLayer.style.height = stageHeight + 'px';

    function nodeBox(node) {
        return {
            left: (node.x - minX) + paddingX,
            top: (node.y - minY) + paddingY,
        };
    }

    function linkPath(link) {
        const source = nodeBox(link.source);
        const target = nodeBox(link.target);
        const startX = source.left + 140;
        const startY = source.top + 120;
        const endX = target.left + 140;
        const endY = target.top;
        const midY = startY + ((endY - startY) / 2);

        return 'M' + startX + ',' + startY +
            ' C' + startX + ',' + midY +
            ' ' + endX + ',' + midY +
            ' ' + endX + ',' + endY;
    }

    d3.select(svg)
        .selectAll('path.pf-chart-link')
        .data(links)
        .join('path')
        .attr('class', 'pf-chart-link')
        .attr('d', linkPath);

    d3.select(nodeLayer)
        .selectAll('div.pf-node')
        .data(nodes, function (node) { return node.data.id; })
        .join('div')
        .attr('class', 'pf-node')
        .style('left', function (node) { return nodeBox(node).left + 'px'; })
        .style('top', function (node) { return nodeBox(node).top + 'px'; })
        .style('border-top', function (node) { return '6px solid ' + (node.data.accent || '#10b981'); })
        .html(function (node) {
            const groups = (node.data.roleGroups || []).map(function (group) {
                const roles = Array.isArray(group.roles) && group.roles.length
                    ? group.roles.map(function (role) {
                        return '<span class="pf-node-role-chip">' + escapeHtml(role) + '</span>';
                    }).join('')
                    : '<div class="pf-node-empty">No roles selected</div>';

                return '<div class="pf-node-group">' +
                    '<div class="pf-node-group-label">' + escapeHtml(group.label) + '</div>' +
                    '<div>' + roles + '</div>' +
                '</div>';
            }).join('');

            const notes = (node.data.notes || []).map(function (note) {
                return '<div class="pf-node-note">' + escapeHtml(note) + '</div>';
            }).join('');

            return '<div class="pf-node-step">' + escapeHtml(node.data.step || '') + '</div>' +
                '<div class="pf-node-title">' + escapeHtml(node.data.title || '') + '</div>' +
                groups +
                notes;
        });

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
