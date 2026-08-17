<?php

namespace App\Http\Controllers;

use App\Models\HouseKeepingAttendance;
use App\Models\HouseKeepingEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HouseKeepingAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->date)->format('Y-m-d')
            : date('Y-m-d');

        // Fetch ONLY Active Housekeeping employees as requested
        $activeEmployees = HouseKeepingEmployee::active()
            ->orderBy('name')
            ->get();

        // Fetch existing attendance records for the selected date
        $existingAttendances = HouseKeepingAttendance::where('attendance_date', $selectedDate)
            ->get()
            ->keyBy('house_keeping_employee_id');

        // Fetch overall attendance history logs for table/view with optional filters
        $historyQuery = HouseKeepingAttendance::with('employee')
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('filter_employee_id')) {
            $historyQuery->where('house_keeping_employee_id', $request->filter_employee_id);
        }

        if ($request->filled('filter_month')) {
            $month = $request->filter_month; // YYYY-MM
            $historyQuery->where('attendance_date', 'like', "{$month}%");
        }

        $allEmployees = HouseKeepingEmployee::orderBy('name')->get();
        $attendanceLogs = $historyQuery->paginate(20, ['*'], 'page')->withQueryString();

        return view('pages.hrms.house-keeping.attendances.index', compact(
            'selectedDate',
            'activeEmployees',
            'existingAttendances',
            'allEmployees',
            'attendanceLogs'
        ));
    }

    public function storeOrUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.login_time' => 'nullable|string',
            'attendances.*.logout_time' => 'nullable|string',
            'attendances.*.status' => 'nullable|string',
            'attendances.*.remarks' => 'nullable|string',
        ]);

        $date = Carbon::parse($request->attendance_date)->format('Y-m-d');
        $attendances = $request->input('attendances', []);

        foreach ($attendances as $employeeId => $data) {
            // Check if employee exists and is active
            $employee = HouseKeepingEmployee::find($employeeId);
            if (! $employee) {
                continue;
            }

            $loginTime = !empty($data['login_time']) ? $data['login_time'] : null;
            $logoutTime = !empty($data['logout_time']) ? $data['logout_time'] : null;
            $status = !empty($data['status']) ? $data['status'] : 'Present';
            $remarks = !empty($data['remarks']) ? $data['remarks'] : null;

            // Save or update attendance record
            HouseKeepingAttendance::updateOrCreate(
                [
                    'house_keeping_employee_id' => $employeeId,
                    'attendance_date' => $date,
                ],
                [
                    'login_time'  => $loginTime,
                    'logout_time' => $logoutTime,
                    'status'      => $status,
                    'remarks'     => $remarks,
                ]
            );
        }

        return redirect()
            ->route('house-keeping.attendances.index', ['date' => $date])
            ->with('success', "Housekeeping attendance for {$date} saved successfully.");
    }

    public function destroy(HouseKeepingAttendance $attendance): RedirectResponse
    {
        $date = $attendance->attendance_date ? $attendance->attendance_date->format('Y-m-d') : date('Y-m-d');
        $attendance->delete();

        return redirect()
            ->route('house-keeping.attendances.index', ['date' => $date])
            ->with('success', "Attendance record deleted successfully.");
    }
}
