@extends('layouts.app')

@section('title', 'House Keeping Management')

@push('styles')
<style>
.hk-page{min-height:100%;padding:28px;background:linear-gradient(180deg,#f7f3ee 0%,#f3f5f8 100%)}
.hk-shell{display:flex;flex-direction:column;gap:20px;max-width:1500px;margin:0 auto}
.hk-hero,.hk-card{background:#fff;border:1px solid #e7e2dc;border-radius:24px;box-shadow:0 18px 40px rgba(18,18,18,.05)}
.hk-hero{padding:26px 28px;background:linear-gradient(135deg,#fff8f1 0%,#ffffff 62%,#f4fbff 100%)}
.hk-kicker{display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:#fff1e8;color:#c2410c;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.hk-title{margin:14px 0 8px;font-size:30px;font-weight:800;color:#111827}
.hk-subtitle{margin:0;max-width:760px;font-size:14px;line-height:1.7;color:#6b7280}
.hk-actions{margin-top:20px;display:flex;gap:12px;flex-wrap:wrap}
.hk-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 18px;border-radius:14px;text-decoration:none;font-size:13px;font-weight:700;border:1px solid transparent}
.hk-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);color:#fff}
.hk-btn-ghost{background:#fff;color:#111827;border-color:#e5ddd6}
.hk-toolbar{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.hk-card{padding:22px}
.hk-card-title{font-size:18px;font-weight:800;color:#111827}
.hk-card-sub{margin-top:5px;font-size:13px;color:#7b7b7b}
.hk-filter{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.hk-input{min-height:42px;padding:10px 12px;border-radius:12px;border:1px solid #e5ddd6;background:#fff;color:#111827;font-size:13px}
.hk-sheet-wrap{overflow-x:auto;margin-top:18px}
.hk-sheet{width:max-content;min-width:100%;border-collapse:collapse;background:#fff}
.hk-sheet th,.hk-sheet td{border:1px solid #1f2937;padding:0;text-align:center}
.hk-sheet thead th{background:#fe8a3d;color:#111827;font-size:11px;font-weight:800}
.hk-sheet .hk-left-head{min-width:56px}
.hk-sheet .hk-point-head{min-width:330px}
.hk-sheet .hk-day{width:38px;min-width:38px}
.hk-sheet .hk-total-head{min-width:56px}
.hk-sheet .hk-daynum{display:block;padding-top:4px;font-size:11px}
.hk-sheet .hk-dayname{display:block;padding:2px 0 4px;font-size:10px;border-top:1px solid rgba(17,24,39,.25)}
.hk-sheet .is-weekend{background:#d9d9d9}
.hk-sheet .hk-category-row td{padding:8px 10px;background:#fff;font-size:13px;font-weight:800;text-align:left}
.hk-sheet .hk-category-row td:not(.hk-category-label){background:#fff}
.hk-sheet .hk-category-label{text-align:center}
.hk-sheet .hk-sn{width:56px;font-size:12px}
.hk-sheet .hk-work-name{padding:10px 12px;text-align:left;font-size:12px;line-height:1.45}
.hk-sheet .hk-cell{height:30px;background:#fff}
.hk-sheet .hk-cell.is-weekend{background:#d9d9d9}
.hk-sheet .hk-total-cell{width:56px;min-width:56px;font-size:12px;font-weight:800;background:#fff7ed;color:#9a3412}
.hk-check-wrap{display:flex;align-items:center;justify-content:center;width:100%;height:30px}
.hk-check{width:14px;height:14px;accent-color:#fe5f04;cursor:pointer}
.hk-empty{padding:36px 18px;text-align:center;color:#9ca3af;font-size:14px}
@media (max-width: 768px){
    .hk-page{padding:18px}
    .hk-hero,.hk-card{padding:20px}
    .hk-title{font-size:24px}
}
</style>
@endpush

@section('content')
<main class="hk-page">
    <div class="hk-shell">
        <section class="hk-hero">
            <div class="hk-kicker">HRMS House Keeping</div>
            <h1 class="hk-title">House Keeping Management</h1>
            <p class="hk-subtitle">Manage cleaning categories, maintain work items under each category, and review the monthly cleaning sheet in the format you shared.</p>

            <div class="hk-actions">
                <a href="{{ route('settings.house-keeping-categories.index') }}" class="hk-btn hk-btn-primary">House Keeping Category</a>
                <a href="{{ route('settings.house-keeping-works.index') }}" class="hk-btn hk-btn-ghost">House Keeping Works</a>
                <a href="{{ route('hrms.dashboard') }}" class="hk-btn hk-btn-ghost">Back to HRMS</a>
            </div>
        </section>

        <section class="hk-card">
            <div class="hk-toolbar">
                <div>
                    <div class="hk-card-title">Cleaning Sheet - {{ $selectedMonth->format('F Y') }}</div>
                </div>

                <form method="GET" action="{{ route('house-keeping.index') }}" class="hk-filter">
                    <input type="month" name="month" value="{{ $selectedMonth->format('Y-m') }}" class="hk-input">
                    <button type="submit" class="hk-btn hk-btn-primary">Apply Month</button>
                    @if(request()->filled('month'))
                        <a href="{{ route('house-keeping.index') }}" class="hk-btn hk-btn-ghost">Reset</a>
                    @endif
                </form>
            </div>

            @if($categories->isEmpty())
                <div class="hk-empty">No house keeping categories or works found yet.</div>
            @else
                <div class="hk-sheet-wrap">
                    <table class="hk-sheet">
                        <thead>
                            <tr>
                                <th rowspan="2" class="hk-left-head">S.No.</th>
                                <th rowspan="2" class="hk-point-head">Cleaning Points</th>
                                @foreach($days as $day)
                                    <th class="hk-day {{ $day->isWeekend() ? 'is-weekend' : '' }}">
                                        <span class="hk-daynum">{{ $day->day }}</span>
                                    </th>
                                @endforeach
                                <th rowspan="2" class="hk-total-head">Total</th>
                            </tr>
                            <tr>
                                @foreach($days as $day)
                                    <th class="hk-day {{ $day->isWeekend() ? 'is-weekend' : '' }}">
                                        <span class="hk-dayname">{{ $day->format('D') }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php($serial = 1)
                            @foreach($categories as $category)
                                <tr class="hk-category-row">
                                    <td colspan="2" class="hk-category-label">{{ $category->name }}</td>
                                    @foreach($days as $day)
                                        <td class="{{ $day->isWeekend() ? 'is-weekend' : '' }}"></td>
                                    @endforeach
                                    <td></td>
                                </tr>

                                @foreach($category->works as $work)
                                    <tr>
                                        <td class="hk-sn">{{ $serial++ }}</td>
                                        <td class="hk-work-name">{{ $work->work_name }}</td>
                                        @foreach($days as $day)
                                            <td class="hk-cell {{ $day->isWeekend() ? 'is-weekend' : '' }}">
                                                <label class="hk-check-wrap">
                                                    <input
                                                        type="checkbox"
                                                        class="hk-check"
                                                        data-work-id="{{ $work->id }}"
                                                        data-work="{{ $work->work_name }}"
                                                        data-category="{{ $category->name }}"
                                                        data-date="{{ $day->format('d-m-Y') }}"
                                                        data-save-date="{{ $day->format('Y-m-d') }}"
                                                        @checked($completedKeys->has($work->id . '|' . $day->format('Y-m-d')))
                                                    >
                                                </label>
                                            </td>
                                        @endforeach
                                        <td class="hk-total-cell">{{ $completionTotals[$work->id] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = @json(csrf_token());
    const saveUrl = @json(route('house-keeping.completions.update'));

    document.querySelectorAll('.hk-check').forEach(function (checkbox) {
        checkbox.addEventListener('change', async function (event) {
            const workName = checkbox.getAttribute('data-work') || 'this work';
            const categoryName = checkbox.getAttribute('data-category') || 'this category';
            const selectedDate = checkbox.getAttribute('data-date') || 'this date';
            const saveDate = checkbox.getAttribute('data-save-date');
            const workId = checkbox.getAttribute('data-work-id');
            const actionText = checkbox.checked ? 'check' : 'uncheck';
            const confirmation = window.confirm('Do you want to ' + actionText + ' "' + workName + '" under "' + categoryName + '" for ' + selectedDate + '?');

            if (!confirmation) {
                event.preventDefault();
                checkbox.checked = !checkbox.checked;
                return;
            }

            checkbox.disabled = true;

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        house_keeping_work_id: workId,
                        completed_date: saveDate,
                        completed: checkbox.checked,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Unable to save');
                }
            } catch (error) {
                checkbox.checked = !checkbox.checked;
                window.alert('Unable to save this checkbox right now. Please try again.');
            } finally {
                checkbox.disabled = false;
            }
        });
    });
});
</script>
@endpush
