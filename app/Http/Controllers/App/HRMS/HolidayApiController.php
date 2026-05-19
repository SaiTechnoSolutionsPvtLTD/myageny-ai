<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\HolidayCalendar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayApiController extends Controller
{
    // ── GET /api/mobile/hrms/holidays ─────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = HolidayCalendar::query()
            ->when($request->search, function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where('reason', 'like', "%$s%");
            })
            ->when($request->filled('month'), function ($q) use ($request) {
                try {
                    $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
                    $q->whereBetween('holiday_date', [
                        $month->copy()->startOfMonth(),
                        $month->copy()->endOfMonth(),
                    ]);
                } catch (\Throwable) {
                    // invalid month format — ignore filter
                }
            })
            ->orderBy('holiday_date');

        $perPage  = (int) ($request->per_page ?? 50);
        $holidays = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'holidays'   => $holidays->map(fn ($h) => $this->mapHoliday($h)),
                'pagination' => [
                    'current_page' => $holidays->currentPage(),
                    'last_page'    => $holidays->lastPage(),
                    'per_page'     => $holidays->perPage(),
                    'total'        => $holidays->total(),
                    'has_more'     => $holidays->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/holidays/meta ────────────────────────────────────
    public function meta(): JsonResponse
    {
        $totalThisYear = HolidayCalendar::whereYear('holiday_date', now()->year)->count();
        $upcoming      = HolidayCalendar::where('holiday_date', '>=', now()->toDateString())
            ->orderBy('holiday_date')
            ->limit(5)
            ->get()
            ->map(fn ($h) => $this->mapHoliday($h));

        return response()->json([
            'success' => true,
            'data'    => [
                'total_this_year' => $totalThisYear,
                'upcoming'        => $upcoming,
                'current_year'    => now()->year,
            ],
        ]);
    }

    // ── Private mapper ────────────────────────────────────────────────────────
    private function mapHoliday(HolidayCalendar $h): array
    {
        $date = $h->holiday_date instanceof Carbon
            ? $h->holiday_date
            : Carbon::parse($h->holiday_date);

        return [
            'id'           => $h->id,
            'holiday_date' => $date->format('Y-m-d'),
            'day'          => $date->day,
            'month'        => $date->month,
            'year'         => $date->year,
            'month_short'  => $date->format('M'),     // Jan, Feb …
            'month_label'  => $date->format('F Y'),   // January 2025
            'weekday'      => $date->dayOfWeek,        // 0 = Sunday … 6 = Saturday
            'weekday_name' => $date->format('l'),      // Monday …
            'weekday_short'=> $date->format('D'),      // Mon …
            'display_date' => $date->format('d M Y'),  // 01 Jan 2025
            'reason'       => $h->reason ?? '',
        ];
    }
}