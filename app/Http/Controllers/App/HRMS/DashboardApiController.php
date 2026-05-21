<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\HolidayCalendar;
use App\Models\InternJoiningForm;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    public function index(): JsonResponse
    {
        $today = Carbon::today();

        // ── Basic counts ──────────────────────────────────────────────────────
        $employees_total    = EmployeeOnboarding::count();
        $employees_pending  = EmployeeOnboarding::where('status', 'pending')->count();
        $employees_verified = EmployeeOnboarding::where('status', 'verified')->count();
        $interns_total      = InternJoiningForm::count();

        // ── Today's attendance stats ──────────────────────────────────────────
        $today_attendance = DailyAttendance::where('attendance_date', $today)->get();
        $today_present    = $today_attendance->where('attendance_status', 'present')->count();
        $today_late       = $today_attendance->where('attendance_status', 'late')->count();
        $today_absent     = $employees_total - $today_present - $today_late;

        // ── Department-wise employee count and salary ─────────────────────────
        $department_stats = Department::select('departments.id', 'departments.name', 'departments.description')
            ->selectRaw('COUNT(eo.id) as employee_count')
            ->selectRaw('COALESCE(SUM(eo.gross_salary), 0) as total_salary')
            ->leftJoin('employee_onboardings as eo', function ($join) {
                $join->on('eo.department_id', '=', 'departments.id')
                     ->where('eo.status', 'verified');

                if (auth()->user()?->company_id !== null) {
                    $join->where('eo.company_id', auth()->user()->company_id);
                }
            })
            ->whereNull('departments.deleted_at')
            ->groupBy('departments.id', 'departments.name', 'departments.description')
            ->get()
            ->map(fn($d) => [
                'id'             => $d->id,
                'name'           => $d->name,
                'description'    => $d->description,
                'employee_count' => (int) $d->employee_count,
                'total_salary'   => (float) $d->total_salary,
            ]);

        // ── Today's birthdays ─────────────────────────────────────────────────
        $today_birthdays = EmployeeOnboarding::whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->where('status', 'verified')
            ->with('role')
            ->get()
            ->map(fn($emp) => [
                'id'         => $emp->id,
                'name'       => $emp->name,
                'role'       => optional($emp->role)->name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        // ── Upcoming holidays (next 7 days) ───────────────────────────────────
        $upcoming_holidays = HolidayCalendar::where('holiday_date', '>=', $today)
            ->where('holiday_date', '<=', $today->copy()->addDays(7))
            ->orderBy('holiday_date')
            ->get()
            ->map(fn($h) => [
                'id'           => $h->id,
                'name'         => $h->holiday_name,
                'date'         => $h->holiday_date->format('Y-m-d'),
                'month_short'  => $h->holiday_date->format('M'),
                'day'          => $h->holiday_date->format('d'),
                'day_full'     => $h->holiday_date->format('l, F j, Y'),
            ]);

        // ── Payroll info ──────────────────────────────────────────────────────
        $salary_day_1_employees  = EmployeeOnboarding::where('status', 'verified')
            ->where('salary_payment_mode', 'monthly_1st')
            ->count();

        $salary_day_10_employees = EmployeeOnboarding::where('status', 'verified')
            ->where('salary_payment_mode', 'monthly_10th')
            ->count();

        // ── Monthly leave data (last 6 months) ────────────────────────────────
        $monthly_leave_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month  = $today->copy()->subMonths($i);
            $leaves = DailyAttendance::whereYear('attendance_date', $month->year)
                ->whereMonth('attendance_date', $month->month)
                ->where('attendance_status', 'leave')
                ->count();

            $monthly_leave_data[] = [
                'month'  => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'leaves' => $leaves,
            ];
        }

        // ── Announcements (static / DB-driven if you have a model) ───────────
        $announcements = [
            [
                'title'    => 'New HR Policy Update',
                'message'  => 'Please review the updated leave policy effective from next month.',
                'priority' => 'high',
                'date'     => $today->format('Y-m-d'),
                'date_formatted' => $today->format('M j, Y'),
            ],
            [
                'title'    => 'Team Building Event',
                'message'  => 'Join us for the quarterly team building event on Friday.',
                'priority' => 'medium',
                'date'     => $today->copy()->addDays(2)->format('Y-m-d'),
                'date_formatted' => $today->copy()->addDays(2)->format('M j, Y'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                // Key metrics
                'employees_total'    => $employees_total,
                'employees_pending'  => $employees_pending,
                'employees_verified' => $employees_verified,
                'interns_total'      => $interns_total,

                // Today attendance
                'attendance' => [
                    'present' => $today_present,
                    'late'    => $today_late,
                    'absent'  => max(0, $today_absent),
                    'total'   => $employees_total,
                ],

                // Department stats
                'department_stats'   => $department_stats,

                // Birthdays
                'today_birthdays'    => $today_birthdays,

                // Holidays
                'upcoming_holidays'  => $upcoming_holidays,

                // Payroll
                'payroll' => [
                    'day_1_employees'  => $salary_day_1_employees,
                    'day_10_employees' => $salary_day_10_employees,
                ],

                // Monthly leave trend
                'monthly_leave_data' => $monthly_leave_data,

                // Announcements
                'announcements'      => $announcements,
            ],
        ]);
    }
}
