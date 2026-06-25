@extends('layouts.app')

@section('title', 'Project Timesheets')

@push('styles')
<style>
.pts-page { min-height:100%; background:linear-gradient(180deg,#f7fbff 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pts-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e6edf5; }
.pts-title { font-size:24px; font-weight:900; color:#111827; }
.pts-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; }
.pts-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pts-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; }
.pts-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.pts-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pts-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; overflow:hidden; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pts-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-card-title { font-size:16px; font-weight:900; color:#111827; }
.pts-card-sub { margin-top:4px; font-size:12px; color:#64748b; }
.pts-card-body { padding:20px; }
.pts-flash { padding:12px 14px; border-radius:10px; font-size:13px; font-weight:700; }
.pts-flash.success { background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; }
.pts-flash.error { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
.pts-empty { padding:40px 20px; text-align:center; color:#64748b; font-size:13px; }
.pts-table-wrap { overflow-x:auto; }
.pts-table { width:100%; border-collapse:collapse; min-width:860px; }
.pts-table th { padding:12px 14px; text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; background:#f8fafc; border-bottom:1px solid #edf2f7; }
.pts-table td { padding:14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#111827; vertical-align:top; }
.pts-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pts-project { font-weight:900; color:#0f172a; }
.pts-update-text { white-space:pre-wrap; line-height:1.65; color:#334155; max-width:520px; }
.pts-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid #fed7aa; color:#c2410c; background:#fff7ed; }
.pts-status { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; }
.pts-status.completed { color:#c2410c; background:#fff7ed; border-color:#fed7aa; }
.pts-status.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.pts-filter-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; padding:16px; box-shadow:0 10px 28px rgba(15,23,42,.04); }
.pts-filter-form { display:grid; grid-template-columns:190px 1fr 190px auto; gap:12px; align-items:end; }
.pts-filter-group { display:grid; gap:7px; }
.pts-filter-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.pts-reset-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:13px; font-weight:800; text-decoration:none; }
.pts-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.42); z-index:1200; display:none; }
.pts-modal-overlay.is-open { display:block; }
.pts-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(840px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.22); z-index:1210; display:none; }
.pts-modal.is-open { display:block; }
.pts-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-modal-close { width:40px; height:40px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:16px; cursor:pointer; }
.pts-modal-body { padding:20px; }
.pts-form-grid { display:grid; grid-template-columns:1fr 190px 190px; gap:14px; align-items:start; }
.pts-form-full { grid-column:1/-1; }
.pts-label { display:block; margin-bottom:8px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
.pts-input, .pts-select, .pts-textarea { width:100%; border:1px solid #dbe2ea; border-radius:10px; background:#fff; font-size:14px; color:#111827; }
.pts-input, .pts-select { min-height:44px; padding:10px 12px; }
.pts-textarea { min-height:170px; padding:12px; resize:vertical; line-height:1.55; }
.pts-input:focus, .pts-select:focus, .pts-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.pts-input[readonly] { background:#f8fafc; color:#475569; cursor:not-allowed; }
.pts-help { margin-top:7px; font-size:12px; color:#64748b; line-height:1.55; }
.pts-error { margin-top:7px; font-size:12px; color:#b91c1c; font-weight:700; }
.select2-container--default .select2-selection--single.pts-select2-selection { height:44px; border:1px solid #dbe2ea; border-radius:10px; background:#fff; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__rendered { line-height:42px; padding-left:12px; padding-right:34px; font-size:14px; color:#111827; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__arrow { height:42px; right:8px; }
.select2-container--default.select2-container--focus .select2-selection--single.pts-select2-selection,
.select2-container--default.select2-container--open .select2-selection--single.pts-select2-selection { border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.select2-dropdown { border:1px solid #dbe2ea; border-radius:10px; overflow:hidden; box-shadow:0 16px 36px rgba(15,23,42,.12); }
.select2-search--dropdown { padding:10px; }
.select2-search--dropdown .select2-search__field { border:1px solid #dbe2ea; border-radius:8px; padding:8px 10px; font-size:13px; }
.select2-results__option { font-size:13px; padding:10px 12px; }
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background:#ea580c; color:#fff; }
.select2-container--open { z-index:1220; }
@media (max-width: 900px) {
    .pts-form-grid { grid-template-columns:1fr; }
    .pts-filter-form { grid-template-columns:1fr 1fr; }
}
@media (max-width: 768px) {
    .pts-topbar { padding:18px 16px; flex-direction:column; }
    .pts-body { padding:18px 16px 24px; }
    .pts-card-head { flex-direction:column; }
    .pts-modal-body, .pts-modal-head { padding:16px; }
    .pts-filter-form { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
@php
    $hasTimesheetErrors = $errors->any();
    $selectedProjectId = (int) old('production_initiation_id', 0);
@endphp
<div class="pts-page">
    <div class="pts-topbar">
        <div>
            <div class="pts-title">Project Timesheets</div>
            <div class="pts-breadcrumb">Projects > Timesheets</div>
        </div>
        <div class="pts-actions">
            <button type="button" class="pts-btn pts-btn-primary" data-open-timesheet-modal>Add Timesheet</button>
        </div>
    </div>

    <div class="pts-body">
        @if(session('success'))
            <div class="pts-flash success">{{ session('success') }}</div>
        @endif

        @if($hasTimesheetErrors)
            <div class="pts-flash error">Please check the timesheet form and try again.</div>
        @endif

        <section class="pts-filter-card">
            <form method="GET" action="{{ route('projects.timesheets') }}" class="pts-filter-form">
                <div class="pts-filter-group">
                    <label class="pts-label">Date</label>
                    <input type="date" name="filter_date" value="{{ $timesheetFilters['filter_date'] ?? '' }}" class="pts-input">
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Project</label>
                    <select name="filter_project_id" class="pts-select select2 pts-filter-project-select" data-placeholder="All projects">
                        <option value="">All Projects</option>
                        @foreach($assignedProjects as $project)
                            <option value="{{ $project->id }}" @selected(($timesheetFilters['filter_project_id'] ?? '') === (string) $project->id)>
                                {{ $project->product_name }} | {{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Status</label>
                    <select name="filter_status" class="pts-select">
                        <option value="">All Status</option>
                        <option value="pending" @selected(($timesheetFilters['filter_status'] ?? '') === 'pending')>Pending</option>
                        <option value="completed" @selected(($timesheetFilters['filter_status'] ?? '') === 'completed')>Completed</option>
                    </select>
                </div>

                <div class="pts-filter-actions">
                    <button type="submit" class="pts-btn pts-btn-primary">Filter</button>
                    <a href="{{ route('projects.timesheets') }}" class="pts-reset-btn">Reset</a>
                </div>
            </form>
        </section>

        <section class="pts-card">
            <div class="pts-card-head">
                <div>
                    <div class="pts-card-title">Saved Timesheets</div>
                    <div class="pts-card-sub">Your submitted day closing updates are listed here.</div>
                </div>
                <span class="pts-pill">{{ $timesheets->count() }} Entries</span>
            </div>
            <div class="pts-card-body" style="padding:0;">
                @if($timesheets->isNotEmpty())
                    <div class="pts-table-wrap">
                        <table class="pts-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Project</th>
                                    <th>Delivery Date</th>
                                    <th>Status</th>
                                    <th>Posters</th>
                                    <th>Videos</th>
                                    <th>Day Closing Update</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timesheets as $timesheet)
                                    @php
                                        $isCompleted = $timesheet->project_delivery_date && $timesheet->project_delivery_date->lte(\Illuminate\Support\Carbon::today());
                                        $statusClass = $isCompleted ? 'completed' : 'pending';
                                        $statusLabel = $isCompleted ? 'Completed' : 'Pending';
                                    @endphp
                                    <tr>
                                        <td>{{ optional($timesheet->timesheet_date)->format('d M Y') ?: 'No date' }}</td>
                                        <td>
                                            <div class="pts-project">{{ $timesheet->project?->product_name ?: 'Project removed' }}</div>
                                            <div class="pts-meta">{{ $timesheet->project?->company_name ?: ($timesheet->project?->lead?->company_name ?: 'No company') }}</div>
                                        </td>
                                        <td>{{ optional($timesheet->project_delivery_date)->format('d M Y') ?: 'Not available' }}</td>
                                        <td><span class="pts-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                        <td>{{ (int) $timesheet->poster_count }}</td>
                                        <td>{{ (int) $timesheet->video_count }}</td>
                                        <td><div class="pts-update-text">{{ $timesheet->day_closing_update }}</div></td>
                                        <td>
                                            {{ optional($timesheet->created_at)->format('d M Y h:i A') ?: 'Not available' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pts-empty">No timesheets submitted yet. Use Add Timesheet to enter today&apos;s day closing update.</div>
                @endif
            </div>
        </section>
    </div>
</div>

<div class="pts-modal-overlay {{ $hasTimesheetErrors ? 'is-open' : '' }}" data-timesheet-modal-overlay></div>
<div class="pts-modal {{ $hasTimesheetErrors ? 'is-open' : '' }}" data-timesheet-modal>
    <div class="pts-modal-head">
        <div>
            <div class="pts-card-title">Add Timesheet</div>
            <div class="pts-card-sub">Select one allocated project and add your day closing tasks.</div>
        </div>
        <button type="button" class="pts-modal-close" data-close-timesheet-modal aria-label="Close timesheet modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="pts-modal-body">
        <form method="POST" action="{{ route('projects.timesheets.store') }}">
            @csrf
            <div class="pts-form-grid">
                <div>
                    <label class="pts-label">Allocated Project</label>
                    <select name="production_initiation_id" class="pts-select select2 pts-project-select" data-placeholder="Search allocated project" required>
                        <option value="">Select project</option>
                        @foreach($assignedProjects as $project)
                            <option
                                value="{{ $project->id }}"
                                data-delivery-date="{{ $project->timesheet_delivery_date }}"
                                @selected($selectedProjectId === (int) $project->id)
                            >
                                {{ $project->product_name }} | {{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}
                            </option>
                        @endforeach
                    </select>
                    @if($assignedProjects->isEmpty())
                        <div class="pts-help">No allocated projects are available for your account right now.</div>
                    @else
                        <div class="pts-help">Only projects allocated to you are shown here. One project can be submitted once per date.</div>
                    @endif
                    @error('production_initiation_id')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="pts-label">Date</label>
                    <input type="date" name="timesheet_date" value="{{ old('timesheet_date', $today) }}" class="pts-input" required>
                    @error('timesheet_date')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="pts-label">Project Delivery Date</label>
                    <input type="date" class="pts-input" data-project-delivery-date readonly>
                </div>

                <div>
                    <label class="pts-label">Poster Completed Count</label>
                    <input type="number" name="poster_count" value="{{ old('poster_count', 0) }}" class="pts-input" min="0" step="1">
                    @error('poster_count')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="pts-label">Video Completed Count</label>
                    <input type="number" name="video_count" value="{{ old('video_count', 0) }}" class="pts-input" min="0" step="1">
                    @error('video_count')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pts-form-full">
                    <label class="pts-label">Day Closing Update</label>
                    <textarea name="day_closing_update" class="pts-textarea" rows="7" required placeholder="Task 1&#10;Task 2&#10;Task 3&#10;Task 4&#10;Task 5">{{ old('day_closing_update') }}</textarea>
                    <div class="pts-help">Add at least 5 task lines before saving.</div>
                    @error('day_closing_update')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="pts-actions" style="margin-top:18px; justify-content:flex-end;">
                <button type="button" class="pts-btn" data-close-timesheet-modal>Cancel</button>
                <button type="submit" class="pts-btn pts-btn-primary" @disabled($assignedProjects->isEmpty())>Save Timesheet</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-timesheet-modal]');
    const overlay = document.querySelector('[data-timesheet-modal-overlay]');
    const openButtons = document.querySelectorAll('[data-open-timesheet-modal]');
    const closeButtons = document.querySelectorAll('[data-close-timesheet-modal]');
    const projectSelect = document.querySelector('.pts-project-select');
    const filterProjectSelect = document.querySelector('.pts-filter-project-select');
    const deliveryDateInput = document.querySelector('[data-project-delivery-date]');

    function setModalState(isOpen) {
        if (!modal || !overlay) {
            return;
        }

        modal.classList.toggle('is-open', isOpen);
        overlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function syncDeliveryDate() {
        if (!projectSelect || !deliveryDateInput) {
            return;
        }

        const selectedOption = projectSelect.options[projectSelect.selectedIndex];
        deliveryDateInput.value = selectedOption ? (selectedOption.dataset.deliveryDate || '') : '';
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalState(true);
            window.setTimeout(syncDeliveryDate, 0);
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalState(false);
        });
    });

    if (overlay) {
        overlay.addEventListener('click', function () {
            setModalState(false);
        });
    }

    if (projectSelect) {
        projectSelect.addEventListener('change', syncDeliveryDate);
    }

    if (window.jQuery && window.jQuery.fn.select2 && projectSelect) {
        const $select = window.jQuery(projectSelect);

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            placeholder: $select.data('placeholder') || 'Search allocated project',
            dropdownParent: window.jQuery(modal),
        });

        $select.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        $select.on('change select2:select', syncDeliveryDate);
    }

    if (window.jQuery && window.jQuery.fn.select2 && filterProjectSelect) {
        const $filterSelect = window.jQuery(filterProjectSelect);

        if ($filterSelect.hasClass('select2-hidden-accessible')) {
            $filterSelect.select2('destroy');
        }

        $filterSelect.select2({
            width: '100%',
            placeholder: $filterSelect.data('placeholder') || 'All projects',
            allowClear: true,
        });

        $filterSelect.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
    }

    window.setTimeout(syncDeliveryDate, 0);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setModalState(false);
        }
    });
});
</script>
@endpush
