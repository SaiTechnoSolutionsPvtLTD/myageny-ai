@php
    $modules = [
        [
            'key' => 'crm',
            'title' => 'CRM',
            'description' => 'Leads, quotations, products, settings, and customer workflows.',
            'icon' => 'bi-briefcase-fill',
            'permission' => 'modules_menu.crm',
            'url' => route('dashboard'),
        ],
        [
            'key' => 'hrms',
            'title' => 'HRMS',
            'description' => 'Employees, attendance, payroll, and team workflows.',
            'icon' => 'bi-people-fill',
            'permission' => 'modules_menu.hrms',
            'url' => route('hrms.dashboard'),
        ],
        [
            'key' => 'projects',
            'title' => 'Production',
            'description' => 'Projects, tasks, time tracking, and project workflows.',
            'icon' => 'bi-diagram-3-fill',
            'permission' => 'modules_menu.projects',
            'url' => route('projects.dashboard'),
        ],
        [
            'key' => 'cst',
            'title' => 'CST',
            'description' => 'Customer Success Team dashboard, metrics, and renewals.',
            'icon' => 'bi-chat-left-heart-fill',
            'accessor' => 'canAccessCstModule',
            'url' => route('dashboard.customer-success'),
        ],
    ];
@endphp

@can('modules_menu.view')
<button
    type="button"
    class="module-fab"
    id="moduleFab"
    aria-label="Open modules sidebar"
    aria-controls="modulePanel"
    aria-expanded="false"
>
    <span class="module-fab__pulse" aria-hidden="true"></span>
    <i class="bi bi-grid-1x2-fill module-fab__icon" aria-hidden="true"></i>
</button>
@endcan

<div class="module-overlay" id="moduleOverlay" aria-hidden="true"></div>

<aside class="module-panel" id="modulePanel" aria-label="Modules sidebar" role="dialog" aria-modal="true">
    <div class="module-panel__header">
        <div>
            <div class="module-panel__eyebrow">
                <i class="bi bi-stars" aria-hidden="true"></i>
                Modules
            </div>
            <div class="module-panel__title">Quick module access</div>
            <div class="module-panel__text">Open your main workspace areas from one floating shortcut on every page.</div>
        </div>

        <button type="button" class="module-panel__close" id="moduleClose" aria-label="Close modules sidebar">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="module-grid">
        @foreach($modules as $module)
            @php
                $canAccessModule = isset($module['accessor'])
                    ? (bool) auth()->user()?->{$module['accessor']}()
                    : auth()->user()?->can($module['permission'] ?? '');
            @endphp

            @if($canAccessModule && !empty($module['url']))
                <a href="{{ $module['url'] }}" class="module-card module-card--{{ $module['key'] }}">
                    <span class="module-card__icon" aria-hidden="true">
                        <i class="bi {{ $module['icon'] }}"></i>
                    </span>
                    <div class="module-card__title">{{ $module['title'] }}</div>
                    <div class="module-card__desc">{{ $module['description'] }}</div>
                </a>
            @endif
        @endforeach
    </div>
</aside>
