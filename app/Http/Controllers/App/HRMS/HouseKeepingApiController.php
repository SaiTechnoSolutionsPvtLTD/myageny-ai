<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\HouseKeepingAttendance;
use App\Models\HouseKeepingCategory;
use App\Models\HouseKeepingCompletion;
use App\Models\HouseKeepingEmployee;
use App\Models\HouseKeepingSalary;
use App\Models\HouseKeepingWork;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Mobile API Controller for HRMS House Keeping Module.
 *
 * Provides dedicated endpoints for:
 * 1. Cleaning Sheet (Monthly matrix & Daily task checklist)
 * 2. Housekeeping Staff/Employee Directory (CRUD)
 * 3. Daily Attendance Marking & History
 * 4. Monthly Salary Payout Sheet & History
 * 5. Cleaning Categories & Work Points (Masters)
 *
 * Strict Isolation: ZERO web controllers or web blade templates are altered.
 */
class HouseKeepingApiController extends Controller
{
    /**
     * Ensure the authenticated user has permission to view/manage housekeeping.
     */
    protected function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->isSuperAdmin() || $user->isSystemAdmin()) {
            return;
        }

        if (! $user->can('house_keeping.menuview')) {
            abort(403, 'Unauthorized access to House Keeping.');
        }
    }

    // =========================================================================
    // 1. CLEANING SHEET / DAILY CHECKLIST
    // =========================================================================

    /**
     * GET /api/mobile/hrms/house-keeping/cleaning-sheet
     * Query params:
     *   - month (YYYY-MM, optional, defaults to current month)
     *   - date  (YYYY-MM-DD, optional, defaults to today)
     */
    public function cleaningSheet(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $selectedMonthStr = $request->filled('month')
            ? (string) $request->month
            : now()->format('Y-m');

        try {
            $selectedMonth = Carbon::createFromFormat('Y-m', $selectedMonthStr)->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->startOfMonth();
        }

        $selectedDateStr = $request->filled('date')
            ? (string) $request->date
            : now()->format('Y-m-d');

        try {
            $selectedDate = Carbon::parse($selectedDateStr);
        } catch (\Throwable $e) {
            $selectedDate = now();
        }

        $startOfMonth = $selectedMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $selectedMonth->copy()->endOfMonth()->toDateString();
        $targetDateStr = $selectedDate->toDateString();

        // 1. Fetch categories with their work items
        $categories = HouseKeepingCategory::query()
            ->with(['works' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // 2. Fetch all completions in the selected month
        $monthCompletions = HouseKeepingCompletion::query()
            ->whereBetween('completed_date', [$startOfMonth, $endOfMonth])
            ->get(['house_keeping_work_id', 'completed_date']);

        // Group completions by work_id -> list of completed dates
        $completionsByWork = [];
        foreach ($monthCompletions as $comp) {
            $workId = $comp->house_keeping_work_id;
            $dStr = $comp->completed_date ? $comp->completed_date->format('Y-m-d') : null;
            if ($dStr) {
                $completionsByWork[$workId][] = $dStr;
            }
        }

        // 3. Build response structure
        $totalWorksCount = 0;
        $completedTodayCount = 0;

        $categoriesData = $categories->map(function ($cat) use ($completionsByWork, $targetDateStr, &$totalWorksCount, &$completedTodayCount) {
            $worksData = $cat->works->map(function ($work) use ($completionsByWork, $targetDateStr, &$totalWorksCount, &$completedTodayCount) {
                $totalWorksCount++;
                $completedDates = $completionsByWork[$work->id] ?? [];
                $isCompletedToday = in_array($targetDateStr, $completedDates, true);

                if ($isCompletedToday) {
                    $completedTodayCount++;
                }

                return [
                    'id'               => $work->id,
                    'category_id'      => $work->house_keeping_category_id,
                    'work_name'        => $work->work_name,
                    'notes'            => $work->notes,
                    'sort_order'       => $work->sort_order,
                    'completed_today'  => $isCompletedToday,
                    'month_total'      => count($completedDates),
                    'completed_dates'  => $completedDates,
                ];
            });

            return [
                'id'          => $cat->id,
                'name'        => $cat->name,
                'description' => $cat->description,
                'sort_order'  => $cat->sort_order,
                'works_count' => $worksData->count(),
                'works'       => $worksData,
            ];
        });

        $pendingTodayCount = max(0, $totalWorksCount - $completedTodayCount);
        $completionPercent = $totalWorksCount > 0
            ? round(($completedTodayCount / $totalWorksCount) * 100, 1)
            : 0.0;

        return response()->json([
            'success' => true,
            'data'    => [
                'selected_month'   => $selectedMonth->format('Y-m'),
                'selected_date'    => $targetDateStr,
                'days_in_month'    => $selectedMonth->daysInMonth,
                'summary'          => [
                    'total_works'             => $totalWorksCount,
                    'completed_today_count'   => $completedTodayCount,
                    'pending_today_count'     => $pendingTodayCount,
                    'today_completion_percent'=> $completionPercent,
                ],
                'categories'       => $categoriesData,
            ],
        ]);
    }

    /**
     * POST /api/mobile/hrms/house-keeping/cleaning-sheet/toggle
     * Body: { house_keeping_work_id, completed_date, completed }
     */
    public function toggleCompletion(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'house_keeping_work_id' => ['required', 'exists:house_keeping_works,id'],
            'completed_date'        => ['required', 'date'],
            'completed'             => ['required', 'boolean'],
        ]);

        $dateStr = Carbon::parse($validated['completed_date'])->format('Y-m-d');
        $workId = $validated['house_keeping_work_id'];
        $completed = (bool) $validated['completed'];

        if ($completed) {
            HouseKeepingCompletion::updateOrCreate(
                [
                    'house_keeping_work_id' => $workId,
                    'completed_date'        => $dateStr,
                ],
                [
                    'completed_by' => auth()->id(),
                ]
            );
        } else {
            HouseKeepingCompletion::query()
                ->where('house_keeping_work_id', $workId)
                ->whereDate('completed_date', $dateStr)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => $completed ? 'Task marked as completed.' : 'Task marked as pending.',
            'data'    => [
                'house_keeping_work_id' => $workId,
                'completed_date'        => $dateStr,
                'completed'             => $completed,
            ],
        ]);
    }

    // =========================================================================
    // 2. HOUSEKEEPING EMPLOYEES (CRUD)
    // =========================================================================

    /**
     * GET /api/mobile/hrms/house-keeping/employees
     * Query params: search, status ('Active', 'Inactive'), page, per_page
     */
    public function employees(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $query = HouseKeepingEmployee::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min(max((int) $request->query('per_page', 20), 5), 100);
        $paginator = $query->orderBy('name')->paginate($perPage);

        $totalCount = HouseKeepingEmployee::query()->count();
        $activeCount = HouseKeepingEmployee::query()->where('status', 'Active')->count();
        $inactiveCount = HouseKeepingEmployee::query()->where('status', 'Inactive')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'employees' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
                'summary' => [
                    'total'    => $totalCount,
                    'active'   => $activeCount,
                    'inactive' => $inactiveCount,
                ],
            ],
        ]);
    }

    /**
     * POST /api/mobile/hrms/house-keeping/employees
     */
    public function storeEmployee(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'mobile_number' => 'nullable|string|max:50',
            'address'       => 'nullable|string',
            'salary'        => 'nullable|numeric|min:0',
            'status'        => 'required|in:Active,Inactive',
            'remarks'       => 'nullable|string',
        ]);

        $employee = HouseKeepingEmployee::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Housekeeping employee '{$employee->name}' added successfully.",
            'data'    => $employee,
        ], 201);
    }

    /**
     * PUT /api/mobile/hrms/house-keeping/employees/{id}
     */
    public function updateEmployee(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $employee = HouseKeepingEmployee::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'mobile_number' => 'nullable|string|max:50',
            'address'       => 'nullable|string',
            'salary'        => 'nullable|numeric|min:0',
            'status'        => 'required|in:Active,Inactive',
            'remarks'       => 'nullable|string',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Housekeeping employee '{$employee->name}' updated successfully.",
            'data'    => $employee,
        ]);
    }

    /**
     * DELETE /api/mobile/hrms/house-keeping/employees/{id}
     */
    public function destroyEmployee(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $employee = HouseKeepingEmployee::findOrFail($id);
        $name = $employee->name;
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => "Housekeeping employee '{$name}' deleted successfully.",
        ]);
    }

    // =========================================================================
    // 3. HOUSEKEEPING ATTENDANCE
    // =========================================================================

    /**
     * GET /api/mobile/hrms/house-keeping/attendances
     * Query param: date (YYYY-MM-DD, defaults to today)
     */
    public function attendances(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->date)->format('Y-m-d')
            : date('Y-m-d');

        // Fetch only active housekeeping staff
        $activeEmployees = HouseKeepingEmployee::active()
            ->orderBy('name')
            ->get();

        // Existing records for the selected date
        $existing = HouseKeepingAttendance::where('attendance_date', $selectedDate)
            ->get()
            ->keyBy('house_keeping_employee_id');

        $presentCount = 0;
        $absentCount = 0;
        $halfDayCount = 0;

        $staffAttendance = $activeEmployees->map(function ($emp) use ($existing, &$presentCount, &$absentCount, &$halfDayCount) {
            $att = $existing->get($emp->id);
            $status = $att ? $att->status : 'Present';

            if ($status === 'Present') {
                $presentCount++;
            } elseif ($status === 'Absent') {
                $absentCount++;
            } elseif (str_contains(strtolower($status), 'half')) {
                $halfDayCount++;
            }

            return [
                'employee_id'   => $emp->id,
                'name'          => $emp->name,
                'mobile_number' => $emp->mobile_number,
                'salary'        => (float) ($emp->salary ?? 0),
                'attendance_id' => $att?->id,
                'status'        => $status,
                'login_time'    => $att?->login_time ?: '',
                'logout_time'   => $att?->logout_time ?: '',
                'remarks'       => $att?->remarks ?: '',
                'is_recorded'   => $att !== null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'selected_date' => $selectedDate,
                'summary'       => [
                    'total_staff'    => $activeEmployees->count(),
                    'present_count'  => $presentCount,
                    'absent_count'   => $absentCount,
                    'half_day_count' => $halfDayCount,
                ],
                'records'       => $staffAttendance,
            ],
        ]);
    }

    /**
     * POST /api/mobile/hrms/house-keeping/attendances
     * Body: {
     *   attendance_date: "YYYY-MM-DD",
     *   attendances: [
     *     { house_keeping_employee_id, status, login_time, logout_time, remarks }
     *   ]
     * }
     */
    public function saveAttendances(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $request->validate([
            'attendance_date'                  => 'required|date',
            'attendances'                      => 'required|array',
            'attendances.*.house_keeping_employee_id' => 'required|exists:house_keeping_employees,id',
            'attendances.*.status'             => 'nullable|string',
            'attendances.*.login_time'         => 'nullable|string',
            'attendances.*.logout_time'        => 'nullable|string',
            'attendances.*.remarks'            => 'nullable|string',
        ]);

        $date = Carbon::parse($request->attendance_date)->format('Y-m-d');
        $attendances = $request->input('attendances', []);

        DB::beginTransaction();
        try {
            foreach ($attendances as $row) {
                $employeeId = $row['house_keeping_employee_id'];
                $status = !empty($row['status']) ? $row['status'] : 'Present';
                $loginTime = !empty($row['login_time']) ? $row['login_time'] : null;
                $logoutTime = !empty($row['logout_time']) ? $row['logout_time'] : null;
                $remarks = !empty($row['remarks']) ? $row['remarks'] : null;

                HouseKeepingAttendance::updateOrCreate(
                    [
                        'house_keeping_employee_id' => $employeeId,
                        'attendance_date'           => $date,
                    ],
                    [
                        'login_time'  => $loginTime,
                        'logout_time' => $logoutTime,
                        'status'      => $status,
                        'remarks'     => $remarks,
                    ]
                );
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save attendance: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Housekeeping attendance for {$date} saved successfully.",
        ]);
    }

    /**
     * GET /api/mobile/hrms/house-keeping/attendances/history
     * Query params: filter_employee_id, filter_month (YYYY-MM), page, per_page
     */
    public function attendanceHistory(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $query = HouseKeepingAttendance::with('employee')
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('filter_employee_id')) {
            $query->where('house_keeping_employee_id', $request->filter_employee_id);
        }

        if ($request->filled('filter_month')) {
            $month = $request->filter_month;
            $query->where('attendance_date', 'like', "{$month}%");
        }

        $perPage = min(max((int) $request->query('per_page', 20), 5), 100);
        $paginator = $query->paginate($perPage);

        $allEmployees = HouseKeepingEmployee::orderBy('name')->get(['id', 'name', 'status']);

        return response()->json([
            'success' => true,
            'data'    => [
                'logs'       => $paginator->items(),
                'employees'  => $allEmployees,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ],
        ]);
    }

    /**
     * DELETE /api/mobile/hrms/house-keeping/attendances/{id}
     */
    public function destroyAttendance(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $attendance = HouseKeepingAttendance::findOrFail($id);
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance record deleted successfully.',
        ]);
    }

    // =========================================================================
    // 4. HOUSEKEEPING SALARIES / PAYOUTS
    // =========================================================================

    /**
     * GET /api/mobile/hrms/house-keeping/salaries
     * Query param: month (YYYY-MM, defaults to current month)
     */
    public function salaries(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $selectedMonth = $request->filled('month')
            ? Carbon::parse($request->month)->format('Y-m')
            : date('Y-m');

        $carbonMonth = Carbon::parse($selectedMonth . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;
        $startOfMonth = $carbonMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $carbonMonth->copy()->endOfMonth()->toDateString();

        // 1. Fetch active employees
        $employees = HouseKeepingEmployee::active()
            ->orderBy('name')
            ->get();

        // 2. Fetch existing salary records for the selected month
        $salaries = HouseKeepingSalary::where('salary_month', $selectedMonth)
            ->get()
            ->keyBy('house_keeping_employee_id');

        // 3. Fetch monthly attendance summary
        $attendances = HouseKeepingAttendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->whereIn('house_keeping_employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('house_keeping_employee_id');

        // 4. Build monthly summary data
        $employeeData = $employees->map(function ($emp) use ($salaries, $attendances, $daysInMonth, $selectedMonth) {
            $empAttendances = $attendances->get($emp->id, collect());

            $presentCount = 0;
            $halfDayCount = 0;
            $absentCount = 0;

            foreach ($empAttendances as $att) {
                $status = strtolower(trim($att->status ?? ''));
                if (str_contains($status, 'present')) {
                    $presentCount++;
                } elseif (str_contains($status, 'half')) {
                    $halfDayCount++;
                } elseif (str_contains($status, 'absent')) {
                    $absentCount++;
                }
            }

            $effectivePresent = $presentCount + ($halfDayCount * 0.5);
            $effectiveAbsent = $absentCount + ($halfDayCount * 0.5);

            $baseSalary = (float) ($emp->salary ?? 0);
            $perDayRate = $daysInMonth > 0 ? ($baseSalary / $daysInMonth) : 0;
            $calculatedSalary = round($perDayRate * $effectivePresent, 2);

            $existingSalary = $salaries->get($emp->id);

            return [
                'employee'          => $emp,
                'salary_month'      => $selectedMonth,
                'base_salary'       => $baseSalary,
                'working_days'      => $daysInMonth,
                'present_days'      => $effectivePresent,
                'absent_days'       => $effectiveAbsent,
                'calculated_salary' => $calculatedSalary,
                'salary_record'     => $existingSalary,
                'is_paid'           => $existingSalary && ($existingSalary->payment_status === 'Paid'),
            ];
        });

        // Summary stats
        $totalStaff = $employees->count();
        $totalPaidCount = $salaries->where('payment_status', 'Paid')->count();
        $totalDisbursed = (float) $salaries->sum('paid_amount');
        $totalBasePay = (float) $employees->sum('salary');

        return response()->json([
            'success' => true,
            'data'    => [
                'selected_month' => $selectedMonth,
                'days_in_month'  => $daysInMonth,
                'summary'        => [
                    'total_staff'      => $totalStaff,
                    'total_base_pay'   => $totalBasePay,
                    'total_disbursed'  => $totalDisbursed,
                    'total_paid_count' => $totalPaidCount,
                ],
                'employee_data'  => $employeeData,
            ],
        ]);
    }

    /**
     * POST /api/mobile/hrms/house-keeping/salaries
     */
    public function saveSalary(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'house_keeping_employee_id' => 'required|exists:house_keeping_employees,id',
            'salary_month'              => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'base_salary'               => 'required|numeric|min:0',
            'working_days'              => 'required|numeric|min:0',
            'present_days'              => 'required|numeric|min:0',
            'absent_days'               => 'nullable|numeric|min:0',
            'calculated_salary'         => 'required|numeric|min:0',
            'bonus'                     => 'nullable|numeric|min:0',
            'deductions'                => 'nullable|numeric|min:0',
            'paid_amount'               => 'required|numeric|min:0',
            'payment_date'              => 'required|date',
            'payment_mode'              => 'required|string|in:Cash,Bank Transfer,UPI,Cheque,Other',
            'payment_status'            => 'required|string|in:Paid,Pending,Partial',
            'notes'                     => 'nullable|string|max:1000',
        ]);

        $validated['bonus'] = $validated['bonus'] ?? 0;
        $validated['deductions'] = $validated['deductions'] ?? 0;
        $validated['absent_days'] = $validated['absent_days'] ?? 0;
        $validated['created_by'] = auth()->id();

        $record = HouseKeepingSalary::updateOrCreate(
            [
                'house_keeping_employee_id' => $validated['house_keeping_employee_id'],
                'salary_month'              => $validated['salary_month'],
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping salary payout record saved successfully.',
            'data'    => $record,
        ]);
    }

    /**
     * GET /api/mobile/hrms/house-keeping/salaries/history
     * Query params: filter_employee_id, filter_month (YYYY-MM), page, per_page
     */
    public function salaryHistory(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $query = HouseKeepingSalary::with('employee')
            ->orderBy('salary_month', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('filter_employee_id')) {
            $query->where('house_keeping_employee_id', $request->filter_employee_id);
        }

        if ($request->filled('filter_month')) {
            $query->where('salary_month', $request->filter_month);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 5), 100);
        $paginator = $query->paginate($perPage);

        $allEmployees = HouseKeepingEmployee::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => [
                'logs'       => $paginator->items(),
                'employees'  => $allEmployees,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ],
        ]);
    }

    /**
     * DELETE /api/mobile/hrms/house-keeping/salaries/{id}
     */
    public function destroySalary(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $salary = HouseKeepingSalary::findOrFail($id);
        $salary->delete();

        return response()->json([
            'success' => true,
            'message' => 'Housekeeping salary record deleted successfully.',
        ]);
    }

    // =========================================================================
    // 5. HOUSEKEEPING CATEGORIES & WORKS (MASTERS)
    // =========================================================================

    /**
     * GET /api/mobile/hrms/house-keeping/categories
     */
    public function categories(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $categories = HouseKeepingCategory::query()
            ->withCount('works')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * POST /api/mobile/hrms/house-keeping/categories
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $category = HouseKeepingCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => "House keeping category '{$category->name}' created successfully.",
            'data'    => $category,
        ], 201);
    }

    /**
     * PUT /api/mobile/hrms/house-keeping/categories/{id}
     */
    public function updateCategory(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $category = HouseKeepingCategory::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => "House keeping category '{$category->name}' updated successfully.",
            'data'    => $category,
        ]);
    }

    /**
     * DELETE /api/mobile/hrms/house-keeping/categories/{id}
     */
    public function destroyCategory(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $category = HouseKeepingCategory::findOrFail($id);
        $name = $category->name;
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "House keeping category '{$name}' deleted successfully.",
        ]);
    }

    /**
     * GET /api/mobile/hrms/house-keeping/works
     * Query param: category_id, search
     */
    public function works(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $query = HouseKeepingWork::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('house_keeping_category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('work_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $works = $query->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => $works,
        ]);
    }

    /**
     * POST /api/mobile/hrms/house-keeping/works
     */
    public function storeWork(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'house_keeping_category_id' => 'required|exists:house_keeping_categories,id',
            'work_name'                 => 'required|string|max:255',
            'notes'                     => 'nullable|string|max:1000',
            'sort_order'                => 'nullable|integer|min:0',
        ]);

        $work = HouseKeepingWork::create($validated);

        return response()->json([
            'success' => true,
            'message' => "House keeping work '{$work->work_name}' created successfully.",
            'data'    => $work->load('category'),
        ], 201);
    }

    /**
     * PUT /api/mobile/hrms/house-keeping/works/{id}
     */
    public function updateWork(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $work = HouseKeepingWork::findOrFail($id);

        $validated = $request->validate([
            'house_keeping_category_id' => 'required|exists:house_keeping_categories,id',
            'work_name'                 => 'required|string|max:255',
            'notes'                     => 'nullable|string|max:1000',
            'sort_order'                => 'nullable|integer|min:0',
        ]);

        $work->update($validated);

        return response()->json([
            'success' => true,
            'message' => "House keeping work '{$work->work_name}' updated successfully.",
            'data'    => $work->load('category'),
        ]);
    }

    /**
     * DELETE /api/mobile/hrms/house-keeping/works/{id}
     */
    public function destroyWork(Request $request, $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $work = HouseKeepingWork::findOrFail($id);
        $name = $work->work_name;
        $work->delete();

        return response()->json([
            'success' => true,
            'message' => "House keeping work '{$name}' deleted successfully.",
        ]);
    }
}
