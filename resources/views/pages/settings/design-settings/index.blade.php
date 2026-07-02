@extends('layouts.app')

@section('title', 'Design Settings')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════
   DESIGN SETTINGS PAGE
   Palette: White cards · Purple accent · Orange CTA
═══════════════════════════════════════════════════════ */
.ds-page {
    min-height: 100%;
    padding: 28px;
    background: linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.ds-shell {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── Card ── */
.ds-card {
    background: #ffffff;
    border: 1px solid #e1dee3;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
}

/* ── Page header ── */
.ds-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
}
.ds-title {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #111827;
}
.ds-sub {
    margin: 6px 0 0;
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
    max-width: 640px;
}
.ds-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.ds-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s ease;
}
.ds-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    border-color: #fe5f04;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.25);
}
.ds-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(254, 95, 4, 0.35);
}
.ds-btn-ghost {
    background: #ffffff;
    border-color: #e1dee3;
    color: #374151;
}
.ds-btn-ghost:hover {
    border-color: #94a3b8;
    color: #111827;
}

/* ── Section heading ── */
.ds-section-title {
    font-size: 15px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 4px;
}
.ds-section-sub {
    font-size: 12px;
    color: #9e9e9e;
    margin: 0 0 20px;
    line-height: 1.6;
}

/* ── Product type manager ── */
.ds-type-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
    min-height: 40px;
}
.ds-type-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 9999px;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    color: #5b21b6;
    font-size: 12px;
    font-weight: 700;
}
.ds-type-chip-remove {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #ddd6fe;
    border: none;
    color: #7c3aed;
    cursor: pointer;
    font-size: 12px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: background 0.15s ease;
}
.ds-type-chip-remove:hover {
    background: #c4b5fd;
}
.ds-add-type-row {
    display: flex;
    gap: 8px;
    align-items: center;
}
.ds-add-input {
    height: 38px;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    padding: 0 12px;
    font-size: 13px;
    font-family: inherit;
    color: #111827;
    background: #fafafa;
    outline: none;
    transition: border-color 0.15s ease;
    width: 200px;
}
.ds-add-input:focus {
    border-color: #7c3aed;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}
.ds-btn-add-type {
    height: 38px;
    padding: 0 14px;
    border-radius: 10px;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    color: #5b21b6;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
}
.ds-btn-add-type:hover {
    background: #ede9fe;
    border-color: #c4b5fd;
}

/* ── Targets table ── */
.ds-table-wrap {
    overflow-x: auto;
}
.ds-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}
.ds-table th {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: #9e9e9e;
    padding: 10px 14px;
    text-align: left;
    border-bottom: 1px solid #f0eef2;
    background: #fafafa;
    white-space: nowrap;
}
.ds-table th.ds-th-type {
    text-align: center;
    min-width: 110px;
}
.ds-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f7f6f9;
    font-size: 13px;
    color: #374151;
    vertical-align: middle;
}
.ds-table tbody tr:last-child td {
    border-bottom: none;
}
.ds-table tbody tr:hover td {
    background: #fdf9f6;
}

/* ── User cell ── */
.ds-user-cell {
    display: flex;
    align-items: center;
    gap: 11px;
}
.ds-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 800;
    color: #ffffff;
    flex-shrink: 0;
}
.ds-user-name {
    font-size: 13px;
    font-weight: 700;
    color: #111827;
}
.ds-user-role {
    font-size: 11px;
    color: #9e9e9e;
    margin-top: 1px;
}

/* ── Target input ── */
.ds-target-td {
    text-align: center;
}
.ds-target-input {
    width: 82px;
    height: 36px;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    padding: 0 10px;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    color: #111827;
    background: #fafafa;
    font-family: inherit;
    outline: none;
    transition: all 0.15s ease;
    margin: 0 auto;
    display: block;
}
.ds-target-input:focus {
    border-color: #7c3aed;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}
.ds-target-input:hover {
    border-color: #a78bfa;
}

/* ── Empty state ── */
.ds-empty {
    text-align: center;
    padding: 48px 20px;
    color: #9e9e9e;
}
.ds-empty-icon {
    font-size: 42px;
    margin-bottom: 12px;
}
.ds-empty-title {
    font-size: 16px;
    font-weight: 700;
    color: #7c7c7c;
    margin-bottom: 6px;
}
.ds-empty-sub {
    font-size: 13px;
}

/* ── Alert ── */
.ds-alert {
    padding: 13px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}
.ds-alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .ds-page { padding: 16px; }
    .ds-head  { flex-direction: column; align-items: stretch; }
    .ds-actions { justify-content: flex-start; }
}
</style>
@endpush

@section('content')
@php
    $avatarColors = ['#fe5f04', '#7c3aed', '#2563eb', '#16a34a', '#be123c', '#0284c7', '#b45309', '#0891b2'];
@endphp

<div class="ds-page">
    <div class="ds-shell">

        {{-- Flash message --}}
        @if(session('success'))
        <div class="ds-alert ds-alert-success">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:inline;vertical-align:middle;margin-right:6px;"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Header card --}}
        <div class="ds-card">
            <div class="ds-head">
                <div>
                    <h2 class="ds-title">Design Settings</h2>
                    <p class="ds-sub">
                        Configure daily production targets for each member of the Designing department.
                        Targets are set per product type (Poster, Video, Logo, Flyer, etc.) and apply per working day.
                    </p>
                </div>
                <div class="ds-actions">
                    <a href="{{ route('settings.index') }}" class="ds-btn ds-btn-ghost">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        Back to Settings
                    </a>
                </div>
            </div>
        </div>

        {{-- Product types manager card --}}
        <div class="ds-card" id="typesCard">
            <div class="ds-section-title">Product Types</div>
            <p class="ds-section-sub">
                These are the design deliverable categories used as target columns. Add or remove types as needed — changes are reflected in the targets table below instantly.
            </p>

            <div class="ds-type-list" id="typeList">
                @foreach($productTypes as $type)
                <span class="ds-type-chip" data-type="{{ $type }}">
                    {{ $type }}
                    <button type="button" class="ds-type-chip-remove" onclick="removeType('{{ addslashes($type) }}')" title="Remove {{ $type }}">✕</button>
                </span>
                @endforeach
            </div>

            <div class="ds-add-type-row">
                <input type="text" id="newTypeInput" class="ds-add-input" placeholder="e.g. Brochure" maxlength="60"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();addType();}">
                <button type="button" class="ds-btn-add-type" onclick="addType()">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Type
                </button>
            </div>
        </div>

        {{-- Targets table card --}}
        <div class="ds-card">
            <form method="POST" action="{{ route('settings.design-settings.store') }}" id="targetsForm">
                @csrf

                <div class="ds-head" style="margin-bottom:20px;">
                    <div>
                        <div class="ds-section-title">Daily Targets per User</div>
                        <p class="ds-section-sub" style="margin-bottom:0;">
                            Set how many items each designer should produce per day for each product type.
                            Enter 0 to mark as no target set.
                        </p>
                    </div>
                    <div class="ds-actions">
                        <button type="submit" class="ds-btn ds-btn-primary">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Save Targets
                        </button>
                    </div>
                </div>

                @if($users->isEmpty())
                    <div class="ds-empty">
                        <div class="ds-empty-icon">🎨</div>
                        <div class="ds-empty-title">No Designing department users found</div>
                        <div class="ds-empty-sub">
                            Make sure users have a role that belongs to a department named <strong>"Designing"</strong> (or similar).<br>
                            Go to <a href="{{ route('settings.departments.index') }}" style="color:#7c3aed;">Settings → Departments</a> to configure departments.
                        </div>
                    </div>
                @else
                    <div class="ds-table-wrap">
                        <table class="ds-table" id="targetsTable">
                            <thead id="targetsHead">
                                <tr>
                                    <th>#</th>
                                    <th>Designer</th>
                                    {{-- Type columns rendered by JS --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $i => $user)
                                @php
                                    $avatarColor = $avatarColors[$user->id % count($avatarColors)];
                                    $initials    = strtoupper(substr($user->name, 0, 1));
                                    $roleName    = $user->roles->first()?->display_name
                                                    ?? ucwords(str_replace('_', ' ', $user->roles->first()?->name ?? 'Staff'));
                                @endphp
                                <tr data-user-id="{{ $user->id }}">
                                    <td style="color:#9e9e9e;font-size:12px;font-family:monospace;width:36px;">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="ds-user-cell">
                                            <div class="ds-avatar" style="background:{{ $avatarColor }}">{{ $initials }}</div>
                                            <div>
                                                <div class="ds-user-name">{{ $user->name }}</div>
                                                <div class="ds-user-role">{{ $roleName }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    {{-- Target inputs rendered by JS per product type --}}
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Hidden: pass existing targets as JSON for JS to seed inputs --}}
                <script id="existingTargetsJson" type="application/json">
                    @json($targets->map(fn($typeMap) => $typeMap->map(fn($t) => $t->daily_target)))
                </script>
                <script id="initialTypesJson" type="application/json">
                    @json($productTypes)
                </script>

            </form>
        </div>

    </div>{{-- /ds-shell --}}
</div>{{-- /ds-page --}}
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── State ────────────────────────────────────────────────────────────────
    let productTypes = JSON.parse(document.getElementById('initialTypesJson').textContent || '[]');
    const existingTargets = JSON.parse(document.getElementById('existingTargetsJson').textContent || '{}');

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const head       = document.getElementById('targetsHead');
    const table      = document.getElementById('targetsTable');
    const typeList   = document.getElementById('typeList');

    // ── Render helpers ────────────────────────────────────────────────────────
    function renderHeaders() {
        if (!head) return;
        const tr = head.querySelector('tr');
        // Remove old type headers (all beyond first 2 fixed cols)
        while (tr.children.length > 2) tr.removeChild(tr.lastChild);
        productTypes.forEach(type => {
            const th = document.createElement('th');
            th.className = 'ds-th-type';
            th.textContent = type;
            tr.appendChild(th);
        });
    }

    function renderBodyInputs() {
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr[data-user-id]');
        rows.forEach(row => {
            const userId = row.dataset.userId;
            // Remove old type cells (beyond first 2 fixed cols)
            while (row.children.length > 2) row.removeChild(row.lastChild);
            productTypes.forEach(type => {
                const td = document.createElement('td');
                td.className = 'ds-target-td';
                const val = existingTargets?.[userId]?.[type] ?? 0;
                const inp = document.createElement('input');
                inp.type  = 'number';
                inp.min   = '0';
                inp.max   = '9999';
                inp.className = 'ds-target-input';
                inp.name  = `targets[${userId}][${type}]`;
                inp.value = val;
                inp.placeholder = '0';
                td.appendChild(inp);
                row.appendChild(td);
            });
        });
    }

    function renderTypeChips() {
        if (!typeList) return;
        typeList.innerHTML = '';
        productTypes.forEach(type => {
            const span = document.createElement('span');
            span.className = 'ds-type-chip';
            span.dataset.type = type;
            span.innerHTML = `${escHtml(type)} <button type="button" class="ds-type-chip-remove" onclick="removeType('${escJs(type)}')" title="Remove ${escHtml(type)}">✕</button>`;
            typeList.appendChild(span);
        });
    }

    function refreshAll() {
        renderTypeChips();
        renderHeaders();
        renderBodyInputs();
    }

    // ── Public functions (called from inline handlers) ─────────────────────
    window.addType = function () {
        const inp = document.getElementById('newTypeInput');
        const val = inp.value.trim();
        if (!val) { inp.focus(); return; }
        if (productTypes.map(t => t.toLowerCase()).includes(val.toLowerCase())) {
            inp.value = '';
            inp.focus();
            return;
        }
        productTypes.push(val);
        productTypes.sort();
        inp.value = '';
        inp.focus();
        refreshAll();
    };

    window.removeType = function (type) {
        productTypes = productTypes.filter(t => t !== type);
        refreshAll();
    };

    // ── Escape helpers ────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function escJs(str) {
        return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    refreshAll();
})();
</script>
@endpush
