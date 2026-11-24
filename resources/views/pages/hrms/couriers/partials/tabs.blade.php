<div class="cr-tabs">
    <a href="{{ route('hrms.couriers.index') }}"
       class="cr-tab {{ request()->routeIs('hrms.couriers.index') || request()->routeIs('hrms.couriers.create') || request()->routeIs('hrms.couriers.show') || request()->routeIs('hrms.couriers.edit') ? 'active' : '' }}">
        <i class="bi bi-box-seam"></i>
        <span>Courier Entries</span>
    </a>
    <a href="{{ route('hrms.couriers.forms.index') }}"
       class="cr-tab {{ request()->routeIs('hrms.couriers.forms.*') ? 'active' : '' }}">
        <i class="bi bi-ui-checks-grid"></i>
        <span>Dynamic Courier Form</span>
    </a>
</div>
