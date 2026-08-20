<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\HolidayCalendar;
use App\Models\HrmsTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HrmsCalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $currentMonth = $request->filled('calendar_month')
            ? Carbon::parse($request->calendar_month)
            : Carbon::today();

        $startOfMonth = $currentMonth->copy()->startOfMonth()->subDays(7);
        $endOfMonth   = $currentMonth->copy()->endOfMonth()->addDays(7);

        $showAllUsers = ($request->get('user_id') === 'all');
        $selectedUserId = $request->filled('user_id') && !$showAllUsers
            ? (int) $request->user_id
            : ($showAllUsers ? null : $user->id);

        // Fetch HRMS tasks filtered by user & company
        $taskQuery = HrmsTask::with(['user:id,name', 'creator:id,name'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('task_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);

        if ($selectedUserId) {
            $taskQuery->where('user_id', $selectedUserId);
        }

        $hrmsTasks = $taskQuery->get();

        $holidays = HolidayCalendar::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('holiday_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $assignableUsers = User::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'hrms_tasks'        => $hrmsTasks,
            'calendar_holidays' => $holidays,
            'assignable_users'  => $assignableUsers,
            'calendar_month'    => $currentMonth,
            'selected_user_id'  => $selectedUserId,
            'show_all_users'    => $showAllUsers,
        ];

        return view('pages.hrms.calendar.index', compact('stats'));
    }
}
