@extends('layouts.app')
@section('title', 'Holiday Calendar')

@push('styles')
@include('pages.hrms.employee_onboarding.styles')
@include('pages.settings.partials.table-styles')
<style>
.holiday-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:var(--font-family, 'Inter', sans-serif); }
.holiday-layout { display:grid; grid-template-columns: minmax(0, 1.45fr) minmax(360px, .95fr); gap:18px; align-items:start; }
.holiday-card { background:#fff; border:1px solid #e1dee3; border-radius:18px; overflow:hidden; box-shadow:0 10px 24px rgba(15,23,42,.04); }
.holiday-card-head { padding:18px 22px; border-bottom:1px solid #f1eff3; display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
.holiday-card-body { padding:22px; }
.holiday-kpi { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px; background:#fff1f2; color:#be123c; font-size:12px; font-weight:800; }
.holiday-filter-grid { display:grid; grid-template-columns: minmax(0,1fr) 180px auto; gap:10px; }
.holiday-import-form { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.holiday-import-note { font-size:12px; color:#7c7c7c; }
.holiday-calendar-head { display:grid; grid-template-columns:repeat(7,1fr); background:#fafafa; border-bottom:1px solid #f1eff3; }
.holiday-calendar-head div { padding:12px 10px; font-size:11px; font-weight:800; letter-spacing:.5px; text-transform:uppercase; color:#8a8a8a; text-align:center; }
.holiday-calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); }
.holiday-day { min-height:126px; border-right:1px solid #f1eff3; border-bottom:1px solid #f1eff3; padding:10px; background:#fff; }
.holiday-day:nth-child(7n) { border-right:none; }
.holiday-day.is-muted { background:#fafafa; color:#a1a1aa; }
.holiday-day.is-today { background:linear-gradient(180deg, #fff7ed, #ffffff); }
.holiday-day-number { font-size:13px; font-weight:800; color:#111827; margin-bottom:10px; }
.holiday-pill { display:block; padding:7px 9px; border-radius:12px; background:#fff1f2; color:#9f1239; font-size:11px; line-height:1.4; font-weight:700; margin-bottom:8px; }
.holiday-table-meta { font-size:12px; color:#7c7c7c; }
.holiday-actions { display:flex; gap:8px; justify-content:flex-end; }
.holiday-empty-mini { color:#9ca3af; font-size:12px; }
@media (max-width: 1100px) {
    .holiday-layout { grid-template-columns:1fr; }
}
@media (max-width: 700px) {
    .holiday-filter-grid { grid-template-columns:1fr; }
    .holiday-calendar-head, .holiday-calendar-grid { min-width:760px; }
}
</style>
@endpush

@section('content')
<div class="holiday-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Holiday Calendar</div>
            <div class="eob-breadcrumb">HRMS > Holiday Calendar</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('settings.holiday-calendars.create') }}" class="eob-btn eob-btn-primary">Add Holiday</a>
        </div>
    </div>

    <div class="eob-body">


        @include('pages.settings.partials.alert')

        <div class="holiday-layout">
            <section class="holiday-card">
                <div class="holiday-card-head">
                    <div>
                        <h3 style="margin:0;font-size:16px;font-weight:800;">{{ $calendarMonth->format('F Y') }}</h3>
                        <p style="margin:6px 0 0;color:#7c7c7c;font-size:13px;">Monthly holiday planner with all saved dates highlighted.</p>
                    </div>
                    <span class="holiday-kpi">{{ $calendarHolidays->flatten()->count() }} holiday(s)</span>
                </div>
                <div class="holiday-card-body">
                    <form method="GET" action="{{ route('settings.holiday-calendars.index') }}" class="holiday-filter-grid" style="margin-bottom:18px;">
                        <input type="text" name="search" class="crm-input" value="{{ request('search') }}" placeholder="Search holiday reason">
                        <input type="month" name="month" class="crm-input" value="{{ request('month', $calendarMonth->format('Y-m')) }}">
                        <div style="display:flex;gap:10px;">
                            <button type="submit" class="crm-btn crm-btn-primary">Apply</button>
                            @if(request()->hasAny(['search', 'month']))
                                <a href="{{ route('settings.holiday-calendars.index') }}" class="crm-btn crm-btn-ghost">Reset</a>
                            @endif
                        </div>
                    </form>

                    <div style="overflow-x:auto;">
                        <div class="holiday-calendar-head">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>
                        <div class="holiday-calendar-grid">
                            @foreach($calendarDays as $day)
                                @php
                                    $dayKey = $day->format('Y-m-d');
                                    $dayHolidays = $calendarHolidays->get($dayKey, collect());
                                @endphp
                                <div class="holiday-day {{ $day->month !== $calendarMonth->month ? 'is-muted' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
                                    <div class="holiday-day-number">{{ $day->format('d') }}</div>
                                    @forelse($dayHolidays as $holiday)
                                        <span class="holiday-pill">{{ $holiday->reason }}</span>
                                    @empty
                                        <div class="holiday-empty-mini">{{ $day->month === $calendarMonth->month ? 'No holiday' : '' }}</div>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="holiday-card">
                <div class="holiday-card-head">
                    <div>
                        <h3 style="margin:0;font-size:16px;font-weight:800;">Manage Holidays</h3>
                        <p style="margin:6px 0 0;color:#7c7c7c;font-size:13px;">Add one holiday at a time or bulk import an annual sheet.</p>
                    </div>
                </div>
                <div class="holiday-card-body">
                    <form method="POST" action="{{ route('settings.holiday-calendars.import') }}" enctype="multipart/form-data" class="holiday-import-form" style="margin-bottom:18px;">
                        @csrf
                        <input type="file" name="import_file" class="crm-input" accept=".csv,.xlsx" style="max-width:220px;" required>
                        <button type="submit" class="crm-btn crm-btn-primary">Import File</button>
                    </form>
                    <div class="holiday-import-note" style="margin-bottom:18px;">Supported headers: `holiday_date` and `reason` or `Holiday Date` and `Reason for Holiday`. Supported formats: CSV and XLSX.</div>

                    <div class="crm-table-wrap">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Holiday Date</th>
                                    <th>Reason for Holiday</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($holidays as $holiday)
                                <tr>
                                    <td>{{ ($holidays->firstItem() ?? 1) + $loop->index }}</td>
                                    <td>
                                        <strong>{{ $holiday->holiday_date->format('d M Y') }}</strong>
                                        <div class="holiday-table-meta">{{ $holiday->holiday_date->format('l') }}</div>
                                    </td>
                                    <td>{{ $holiday->reason }}</td>
                                    <td class="text-right">
                                        <details class="crm-table-dropdown">
                                            <summary class="crm-table-dropdown-trigger">Actions</summary>
                                            <div class="crm-table-dropdown-menu">
                                                <div class="holiday-actions">
                                            <a href="{{ route('settings.holiday-calendars.edit', $holiday) }}" class="crm-icon-btn" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <form action="{{ route('settings.holiday-calendars.destroy', $holiday) }}" method="POST" onsubmit="return confirm('Delete this holiday?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="crm-icon-btn danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                                </div>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="crm-empty">No holidays created yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>

                        @if($holidays->hasPages())
                            @include('partials.table-pagination', ['paginator' => $holidays])
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
