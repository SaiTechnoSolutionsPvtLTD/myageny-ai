<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CstDailyClosing;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\HolidayCalendar;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;
use App\Models\PermissionRequest;
use App\Models\ProjectTimesheet;
use App\Models\QuotationSetting;
use App\Models\SalesDailyClosing;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\PayrollController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollApiController extends Controller
{
    private const BASIC_SALARY_RATIO = 0.50;
    private const HRA_RATIO = 0.30;
    private const TRAVEL_ALLOWANCE_RATIO = 0.10;
    private const PF_BASIC_CAP = 15000.00;
    private const PF_GROSS_THRESHOLD = 21000.00;

    /**
     * Check if user is a self-service employee only.
     */
    private function isSelfServiceUser(): bool
    {
        return auth()->user()?->isHrmsAttendanceOnlyUser() ?? false;
    }

    /**
     * Check if current user can manage/run payroll.
     */
    private function canManagePayroll(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($this->isSelfServiceUser()) {
            return false;
        }

        return $user->hasCrmPermission('payroll.menuview')
            || $user->hasCrmPermission('payroll.create')
            || $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->belongsToHrDepartment()
            || $user->hasHrLikeRole();
    }

    /**
     * Authorize access to view payroll records.
     */
    private function authorizeAccess(): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Self-service users can view their own payslips
        if ($this->isSelfServiceUser()) {
            return;
        }

        if (! $this->canManagePayroll()) {
            abort(403, 'Unauthorized access to Payroll.');
        }
    }

    /**
     * Payroll index: list of processed payroll batches.
     * For self-service users, returns batches containing their payslips.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $selfServiceMode = $this->isSelfServiceUser();
        $user = auth()->user();

        $query = Payroll::query()
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->when($selfServiceMode, function ($q) use ($user) {
                $q->whereHas('items.employee', function ($itemQuery) use ($user) {
                    $itemQuery->where('portal_user_id', $user->id);
                });
            })
            ->when($request->filled('month'), function ($q) use ($request) {
                try {
                    $month = Carbon::parse($request->string('month')->value() . '-01')->startOfMonth();
                    $q->whereDate('salary_month', $month->toDateString());
                } catch (\Exception $e) {
                    // ignore invalid date format
                }
            })
            ->when($request->filled('salary_cycle'), function ($q) use ($request) {
                if (in_array($request->salary_cycle, ['26_to_25', '25_to_25'], true)) {
                    $q->whereIn('salary_cycle', ['26_to_25', '25_to_25']);
                } elseif (in_array($request->salary_cycle, ['1_to_30', '10_to_10'], true)) {
                    $q->whereIn('salary_cycle', ['1_to_30', '10_to_10']);
                } else {
                    $q->where('salary_cycle', $request->salary_cycle);
                }
            })
            ->latest('salary_month');

        $perPage = min(max((int) $request->input('per_page', 12), 1), 50);
        $paginator = $query->paginate($perPage);

        // Overall stats calculation
        $latestBatch = Payroll::query()
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->when($selfServiceMode, function ($q) use ($user) {
                $q->whereHas('items.employee', function ($itemQuery) use ($user) {
                    $itemQuery->where('portal_user_id', $user->id);
                });
            })
            ->latest('salary_month')
            ->first();

        $latestNetTotal = 0.0;
        if ($latestBatch) {
            if ($selfServiceMode) {
                $latestItem = $latestBatch->items()
                    ->whereHas('employee', fn ($q) => $q->where('portal_user_id', $user->id))
                    ->latest('id')
                    ->first();
                $latestNetTotal = (float) ($latestItem?->net_salary ?? 0);
            } else {
                $latestNetTotal = (float) ($latestBatch->net_total ?? 0);
            }
        }

        $items = collect($paginator->items())->map(function (Payroll $payroll) use ($selfServiceMode, $user) {
            $userItem = null;
            if ($selfServiceMode) {
                $userItem = $payroll->items()
                    ->whereHas('employee', fn ($q) => $q->where('portal_user_id', $user->id))
                    ->first();
            }

            return [
                'id'                 => $payroll->id,
                'salary_month'       => optional($payroll->salary_month)->format('Y-m'),
                'salary_month_label' => optional($payroll->salary_month)->format('M Y'),
                'salary_cycle'       => $payroll->salary_cycle ?? '26_to_25',
                'salary_date'        => optional($payroll->salary_date)->format('Y-m-d'),
                'total_working_days' => (int) $payroll->total_working_days,
                'employee_count'     => (int) $payroll->employee_count,
                'gross_total'        => (float) ($selfServiceMode && $userItem ? $userItem->earned_gross_salary : $payroll->gross_total),
                'deduction_total'    => (float) ($selfServiceMode && $userItem ? $userItem->total_deductions : $payroll->deduction_total),
                'net_total'          => (float) ($selfServiceMode && $userItem ? $userItem->net_salary : $payroll->net_total),
                'notes'              => $payroll->notes,
                'my_item_id'         => $userItem?->id,
                'created_at'         => optional($payroll->created_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'success'           => true,
            'self_service_mode' => $selfServiceMode,
            'can_run_payroll'   => $this->canManagePayroll(),
            'summary'           => [
                'payroll_runs'     => $paginator->total(),
                'latest_month'     => optional($latestBatch?->salary_month)->format('M Y') ?: 'N/A',
                'latest_net_total' => $latestNetTotal,
            ],
            'data'              => $items,
            'meta'              => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Fast preview shell meta: branches, current month, default working days.
     */
    public function createData(Request $request): JsonResponse
    {
        abort_unless($this->canManagePayroll(), 403, 'Unauthorized to run payroll.');

        $selectedMonth = $request->filled('month')
            ? Carbon::parse($request->string('month')->value() . '-01')->startOfMonth()
            : now()->startOfMonth();

        $selectedCycle = $request->input('salary_cycle', '26_to_25');

        $user = auth()->user();
        $selectedBranchId = $request->filled('branch_id')
            ? $request->integer('branch_id')
            : ($user?->branch_id ?: 1);

        $branches = Branch::query()
            ->where('is_active', true)
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        [$cycleStart, $cycleEnd] = PayrollController::getCycleDateRange($selectedMonth, $selectedCycle);
        $workingDays = count($this->workingDateStringsForPeriod($cycleStart, $cycleEnd));
        $payrollSettings = PayrollSetting::forCompany($user?->company_id);

        $monthLabel = $selectedMonth->format('M Y') . ' (' . $cycleStart->format('d M') . ' - ' . $cycleEnd->format('d M') . ')';

        return response()->json([
            'success'            => true,
            'selected_month'     => $selectedMonth->format('Y-m'),
            'selected_cycle'     => $selectedCycle,
            'cycle_start'        => $cycleStart->toDateString(),
            'cycle_end'          => $cycleEnd->toDateString(),
            'month_label'        => $monthLabel,
            'selected_branch_id' => $selectedBranchId,
            'working_days'       => $workingDays,
            'branches'           => $branches,
            'settings'           => [
                'pf_employee_percentage'  => (float) $payrollSettings->pf_employee_percentage,
                'pf_employer_percentage'  => (float) $payrollSettings->pf_employer_percentage,
                'esi_employee_percentage' => (float) $payrollSettings->esi_employee_percentage,
                'esi_employer_percentage' => (float) $payrollSettings->esi_employer_percentage,
                'esi_salary_limit'        => (float) $payrollSettings->esi_salary_limit,
                'paid_leave_days'         => (float) $payrollSettings->paid_leave_days,
                'grace_login_time'        => (string) ($payrollSettings->grace_login_time ?: '09:30:00'),
            ],
        ]);
    }

    /**
     * Preview rows calculation for Run Payroll screen.
     * Computes real-time salary and deductions based on attendance.
     */
    public function previewRows(Request $request): JsonResponse
    {
        abort_unless($this->canManagePayroll(), 403, 'Unauthorized to run payroll.');

        $selectedMonth = $request->filled('month')
            ? Carbon::parse($request->string('month')->value() . '-01')->startOfMonth()
            : now()->startOfMonth();

        $selectedBranchId = $request->filled('branch_id') ? $request->integer('branch_id') : null;
        $selectedCycle    = $request->filled('salary_cycle') ? $request->string('salary_cycle')->value() : '26_to_25';
        $search = $request->string('search')->trim()->value();
        $user = auth()->user();

        $employeesQuery = EmployeeOnboarding::query()
            ->active()
            ->where(function ($query) {
                $query->whereNull('portal_user_id')
                    ->orWhereHas('portalUser', function ($userQuery) {
                        $userQuery->where('is_active', true);
                    });
            })
            ->with(['role', 'portalUser', 'department'])
            ->when($selectedCycle, function ($query) use ($selectedCycle) {
                $hasCustomCycleEmployees = EmployeeOnboarding::query()->whereIn('salary_cycle', ['1_to_30', '10_to_10'])->exists();
                if ($hasCustomCycleEmployees) {
                    if (in_array($selectedCycle, ['26_to_25', '25_to_25'], true)) {
                        $query->where(function ($q) {
                            $q->whereIn('salary_cycle', ['26_to_25', '25_to_25'])
                              ->orWhereNull('salary_cycle');
                        });
                    } elseif (in_array($selectedCycle, ['1_to_30', '10_to_10'], true)) {
                        $query->whereIn('salary_cycle', ['1_to_30', '10_to_10']);
                    } else {
                        $query->where('salary_cycle', $selectedCycle);
                    }
                }
            })
            ->when($user?->company_id, function ($query) use ($user) {
                $query->whereHas('portalUser', function ($subQuery) use ($user) {
                    $subQuery->where('company_id', $user->company_id);
                });
            })
            ->when($selectedBranchId, function ($query) use ($selectedBranchId) {
                $branch = Branch::withoutGlobalScopes()->find($selectedBranchId);
                $query->where(function ($sub) use ($selectedBranchId, $branch) {
                    $sub->whereHas('portalUser', fn ($q) => $q->where('branch_id', $selectedBranchId));
                    if ($branch && $branch->code) {
                        $sub->orWhere(function ($q2) use ($branch) {
                            $q2->whereNull('portal_user_id')
                               ->where('employee_id', 'like', $branch->code . '%');
                        });
                    }
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $employees = $employeesQuery->get();

        // Working dates and attendance calculation for cycle
        [$cycleStart, $cycleEnd] = PayrollController::getCycleDateRange($selectedMonth, $selectedCycle);
        $workingDates   = $this->workingDateStringsForPeriod($cycleStart, $cycleEnd);
        $workingDays    = count($workingDates);
        $payrollSettings = PayrollSetting::forCompany($user?->company_id);
        $attendanceSummary = $this->attendanceSummaryForPeriod($cycleStart, $cycleEnd, $employees, $workingDays, $workingDates, $payrollSettings);

        $totalNetSalary = 0.0;
        $totalGrossSalary = 0.0;
        $totalDeductions = 0.0;

        $rows = $employees->map(function (EmployeeOnboarding $employee) use ($attendanceSummary, $workingDays, $payrollSettings, &$totalNetSalary, &$totalGrossSalary, &$totalDeductions) {
            $components = $this->salaryComponentsForEmployee($employee);
            $summary    = $attendanceSummary[$employee->id] ?? [
                'days_attended'      => 0,
                'leave_days'         => 0,
                'timesheet_lop_days' => 0,
                'lop_days'           => $workingDays,
                'payable_days'       => 0,
            ];

            $row = $this->calculatePayrollRow([
                'employee_onboarding_id'   => $employee->id,
                'employee_code'            => $employee->employee_id,
                'employee_name'            => $employee->name,
                'branch_name'              => $employee->branch_name,
                'designation'              => $employee->role?->display_name ?: ($employee->role?->name ? Str::of(Str::afterLast($employee->role->name, '__'))->replace('_', ' ')->title()->value() : 'Employee'),
                'date_of_joining'          => optional($employee->joining_date ?: $employee->salary_effective_from)->format('Y-m-d'),
                'uan_no'                   => $employee->uan_no,
                'esi_no'                   => $employee->esi_no,
                'working_days'             => $workingDays,
                'days_attended'            => $summary['days_attended'],
                'leave_days'               => $summary['leave_days'],
                'timesheet_lop_days'       => $summary['timesheet_lop_days'] ?? 0,
                'lop_days'                 => $summary['lop_days'],
                'payable_days'             => $summary['payable_days'],
                'use_pf'                   => (bool) $employee->pf_enabled,
                'use_esi'                  => (bool) $employee->esi_enabled,
                'pf_employee_percentage'   => (float) $payrollSettings->pf_employee_percentage,
                'pf_employer_percentage'   => (float) $payrollSettings->pf_employer_percentage,
                'esi_employee_percentage'  => (float) $payrollSettings->esi_employee_percentage,
                'esi_employer_percentage'  => (float) $payrollSettings->esi_employer_percentage,
                'esi_salary_limit'         => (float) $payrollSettings->esi_salary_limit,
                'gross_salary'             => $components['gross_salary'],
                'basic_salary'             => $components['basic_salary'],
                'hra'                      => $components['hra'],
                'travel_allowance'         => $components['travel_allowance'],
                'other_allowance'          => $components['other_allowance'],
                'professional_tax'         => (float) ($employee->professional_tax ?? 0),
                'tds_amount'               => (float) ($employee->tds_amount ?? 0),
                'loan_deduction'           => (float) ($employee->loan_deduction ?? 0),
                'other_deduction'          => (float) ($employee->other_deduction ?? 0),
            ]);

            $totalNetSalary += $row['net_salary'];
            $totalGrossSalary += $row['earned_gross_salary'];
            $totalDeductions += $row['total_deductions'];

            return $row;
        })->values();

        $monthLabel = $selectedMonth->format('M Y') . ' (' . $cycleStart->format('d M') . ' - ' . $cycleEnd->format('d M') . ')';

        return response()->json([
            'success'        => true,
            'working_days'   => $workingDays,
            'selected_cycle' => $selectedCycle,
            'cycle_start'    => $cycleStart->toDateString(),
            'cycle_end'      => $cycleEnd->toDateString(),
            'month_label'    => $monthLabel,
            'summary'      => [
                'verified_employees' => $rows->count(),
                'total_working_days' => $workingDays,
                'payroll_month'      => $monthLabel,
                'total_net_salary'   => round($totalNetSalary, 2),
                'total_gross_salary' => round($totalGrossSalary, 2),
                'total_deductions'   => round($totalDeductions, 2),
            ],
            'rows'         => $rows,
        ]);
    }

    /**
     * Store or update payroll batch and all item records.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($this->canManagePayroll(), 403, 'Unauthorized to process payroll.');

        if ($request->filled('items_json')) {
            $decoded = json_decode($request->string('items_json')->value(), true);
            if (is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }

        $validated = $request->validate([
            'month'                          => ['required', 'date_format:Y-m'],
            'salary_cycle'                   => ['nullable', 'string', 'in:26_to_25,1_to_30,25_to_25,10_to_10'],
            'salary_date'                    => ['nullable', 'date'],
            'notes'                          => ['nullable', 'string'],
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.employee_onboarding_id' => ['required', 'exists:employee_onboardings,id'],
            'items.*.working_days'           => ['required', 'numeric', 'min:0'],
            'items.*.days_attended'          => ['required', 'numeric', 'min:0'],
            'items.*.leave_days'             => ['required', 'numeric', 'min:0'],
            'items.*.timesheet_lop_days'     => ['nullable', 'numeric', 'min:0'],
            'items.*.lop_days'               => ['required', 'numeric', 'min:0'],
            'items.*.payable_days'           => ['required', 'numeric', 'min:0'],
            'items.*.use_pf'                 => ['nullable'],
            'items.*.use_esi'                => ['nullable'],
            'items.*.pf_employee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.pf_employer_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_employee_percentage'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_employer_percentage'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_salary_limit'       => ['nullable', 'numeric', 'min:0'],
            'items.*.gross_salary'           => ['required', 'numeric', 'min:0'],
            'items.*.basic_salary'           => ['required', 'numeric', 'min:0'],
            'items.*.hra'                    => ['required', 'numeric', 'min:0'],
            'items.*.travel_allowance'       => ['required', 'numeric', 'min:0'],
            'items.*.other_allowance'        => ['required', 'numeric', 'min:0'],
            'items.*.professional_tax'       => ['nullable', 'numeric', 'min:0'],
            'items.*.tds_amount'             => ['nullable', 'numeric', 'min:0'],
            'items.*.loan_deduction'         => ['nullable', 'numeric', 'min:0'],
            'items.*.other_deduction'        => ['nullable', 'numeric', 'min:0'],
        ]);

        $salaryMonth = Carbon::parse($validated['month'] . '-01')->startOfMonth();
        $salaryCycle = $validated['salary_cycle'] ?? '26_to_25';
        $employeeIds = collect($validated['items'])->pluck('employee_onboarding_id')->all();
        $employees = EmployeeOnboarding::query()
            ->with('role')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $payroll = DB::transaction(function () use ($validated, $salaryMonth, $salaryCycle, $employees) {
            $user = auth()->user();
            $payroll = Payroll::updateOrCreate(
                [
                    'company_id'   => $user?->company_id,
                    'salary_month' => $salaryMonth->toDateString(),
                    'salary_cycle' => $salaryCycle,
                ],
                [
                    'salary_date' => $validated['salary_date'] ?? now()->toDateString(),
                    'notes'       => $validated['notes'] ?? null,
                    'created_by'  => $user?->id,
                    'updated_by'  => $user?->id,
                ]
            );

            $payroll->items()->delete();

            $grossTotal = 0;
            $deductionTotal = 0;
            $netTotal = 0;
            $workingDays = 0;

            foreach ($validated['items'] as $item) {
                $employee = $employees->get((int) $item['employee_onboarding_id']);
                if (! $employee) {
                    continue;
                }

                $row = $this->calculatePayrollRow([
                    'employee_onboarding_id'   => $employee->id,
                    'employee_code'            => $employee->employee_id,
                    'employee_name'            => $employee->name,
                    'branch_name'              => $employee->branch_name,
                    'designation'              => $employee->role?->display_name ?: ($employee->role?->name ? Str::of(Str::afterLast($employee->role->name, '__'))->replace('_', ' ')->title()->value() : 'Employee'),
                    'date_of_joining'          => optional($employee->joining_date ?: $employee->salary_effective_from)->format('Y-m-d'),
                    'uan_no'                   => $employee->uan_no,
                    'esi_no'                   => $employee->esi_no,
                    'working_days'             => $item['working_days'],
                    'days_attended'            => $item['days_attended'],
                    'leave_days'               => $item['leave_days'],
                    'timesheet_lop_days'       => $item['timesheet_lop_days'] ?? 0,
                    'lop_days'                 => $item['lop_days'],
                    'payable_days'             => $item['payable_days'],
                    'use_pf'                   => (bool) ($item['use_pf'] ?? false),
                    'use_esi'                  => (bool) ($item['use_esi'] ?? false),
                    'pf_employee_percentage'   => $item['pf_employee_percentage'],
                    'pf_employer_percentage'   => $item['pf_employer_percentage'],
                    'esi_employee_percentage'  => $item['esi_employee_percentage'],
                    'esi_employer_percentage'  => $item['esi_employer_percentage'],
                    'esi_salary_limit'         => $item['esi_salary_limit'],
                    'gross_salary'             => $item['gross_salary'],
                    'basic_salary'             => $item['basic_salary'],
                    'hra'                      => $item['hra'],
                    'travel_allowance'         => $item['travel_allowance'],
                    'other_allowance'          => $item['other_allowance'],
                    'professional_tax'         => $item['professional_tax'],
                    'tds_amount'               => $item['tds_amount'],
                    'loan_deduction'           => $item['loan_deduction'],
                    'other_deduction'          => $item['other_deduction'],
                ]);

                $payroll->items()->create($row);

                $grossTotal += $row['earned_gross_salary'];
                $deductionTotal += $row['total_deductions'];
                $netTotal += $row['net_salary'];
                $workingDays = max($workingDays, (int) $row['working_days']);
            }

            $payroll->update([
                'salary_cycle'       => $salaryCycle,
                'total_working_days' => $workingDays,
                'employee_count'     => $payroll->items()->count(),
                'gross_total'        => round($grossTotal, 2),
                'deduction_total'    => round($deductionTotal, 2),
                'net_total'          => round($netTotal, 2),
                'updated_by'         => $user?->id,
            ]);

            return $payroll;
        });

        return response()->json([
            'success' => true,
            'message' => 'Payroll processed successfully for ' . $salaryMonth->format('F Y') . '.',
            'data'    => [
                'id'                 => $payroll->id,
                'salary_month'       => optional($payroll->salary_month)->format('Y-m'),
                'salary_month_label' => optional($payroll->salary_month)->format('M Y'),
                'salary_cycle'       => $payroll->salary_cycle ?? '26_to_25',
                'total_working_days' => (int) $payroll->total_working_days,
                'employee_count'     => (int) $payroll->employee_count,
                'gross_total'        => (float) $payroll->gross_total,
                'deduction_total'    => (float) $payroll->deduction_total,
                'net_total'          => (float) $payroll->net_total,
            ],
        ]);
    }

    /**
     * Show payroll details for a batch.
     */
    public function show(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorizeAccess();
        $this->authorizePayrollScope($payroll);

        $selfServiceMode = $this->isSelfServiceUser();
        $user = auth()->user();

        $itemsQuery = $payroll->items()
            ->with('employee')
            ->when($selfServiceMode, function ($q) use ($user) {
                $q->whereHas('employee', fn ($eq) => $eq->where('portal_user_id', $user->id));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->trim()->value();
                $q->where(function ($sub) use ($search) {
                    $sub->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            })
            ->orderBy('employee_name');

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $paginator = $itemsQuery->paginate($perPage);

        $allItems = $payroll->items()
            ->when($selfServiceMode, function ($q) use ($user) {
                $q->whereHas('employee', fn ($eq) => $eq->where('portal_user_id', $user->id));
            })
            ->get();

        $summary = [
            'employee_count'  => $allItems->count(),
            'gross_total'     => round((float) $allItems->sum('earned_gross_salary'), 2),
            'deduction_total' => round((float) $allItems->sum('total_deductions'), 2),
            'net_total'       => round((float) $allItems->sum('net_salary'), 2),
        ];

        return response()->json([
            'success'           => true,
            'self_service_mode' => $selfServiceMode,
            'payroll'           => [
                'id'                 => $payroll->id,
                'salary_month'       => optional($payroll->salary_month)->format('Y-m'),
                'salary_month_label' => optional($payroll->salary_month)->format('M Y'),
                'salary_cycle'       => $payroll->salary_cycle ?? '26_to_25',
                'salary_date'        => optional($payroll->salary_date)->format('Y-m-d'),
                'total_working_days' => (int) $payroll->total_working_days,
                'employee_count'     => (int) $payroll->employee_count,
                'gross_total'        => (float) $payroll->gross_total,
                'deduction_total'    => (float) $payroll->deduction_total,
                'net_total'          => (float) $payroll->net_total,
                'notes'              => $payroll->notes,
            ],
            'summary'           => $summary,
            'data'              => $paginator->items(),
            'meta'              => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get single employee payslip detail JSON.
     */
    public function payslip(Request $request, Payroll $payroll, PayrollItem $item): JsonResponse
    {
        $this->authorizeAccess();
        $this->authorizePayrollScope($payroll);

        abort_unless((int) $item->payroll_id === (int) $payroll->id, 404, 'Payslip item not found in batch.');

        $item->loadMissing('employee');
        $payroll->loadMissing('company');
        $user = auth()->user();

        if ($this->isSelfServiceUser()) {
            abort_unless((int) optional($item->employee)->portal_user_id === (int) $user->id, 403, 'Unauthorized.');
        }

        $branchId = $user?->branch_id;
        $settings = QuotationSetting::allSettings($branchId);
        $company = $payroll->company ?: ($payroll->company_id ? Company::find($payroll->company_id) : null);

        $companyInfo = [
            'name'    => $settings['company_name'] ?: ($company?->company_name ?: 'Company'),
            'address' => $settings['company_address'] ?: ($company?->address ?: ''),
            'phone'   => $settings['company_phone'] ?: ($company?->mobile_number ?: ''),
            'email'   => $settings['company_email'] ?: ($company?->email ?: ''),
        ];

        return response()->json([
            'success'      => true,
            'company'      => $companyInfo,
            'payroll_id'   => $payroll->id,
            'salary_month' => optional($payroll->salary_month)->format('M Y'),
            'salary_date'  => optional($payroll->salary_date)->format('d M Y'),
            'item'         => $item,
            'pdf_url'      => route('hrms.payroll.payslip-pdf', ['payroll' => $payroll->id, 'item' => $item->id]),
        ]);
    }

    /**
     * Download or stream payslip PDF.
     */
    public function payslipPdf(Payroll $payroll, PayrollItem $item)
    {
        $this->authorizeAccess();
        $this->authorizePayrollScope($payroll);
        abort_unless((int) $item->payroll_id === (int) $payroll->id, 404);

        $payroll->loadMissing('company');
        $item->loadMissing('employee');

        if ($this->isSelfServiceUser()) {
            abort_unless((int) optional($item->employee)->portal_user_id === (int) auth()->id(), 403);
        }

        $branchId = auth()->user()?->branch_id;
        $settings = QuotationSetting::allSettings($branchId);
        $company = $payroll->company ?: ($payroll->company_id ? Company::find($payroll->company_id) : null);
        $signatureBase64 = $this->imageBase64($settings['signature'] ?? null);

        $logoPath = $settings['logo'] ?? null;
        if (! $logoPath || ! file_exists(public_path(ltrim((string) $logoPath, '/')))) {
            if (file_exists(public_path('images/my_agenci_logo.png'))) {
                $logoPath = 'images/my_agenci_logo.png';
            } elseif (file_exists(public_path('images/logo.png'))) {
                $logoPath = 'images/logo.png';
            }
        }
        $logoBase64 = $this->imageBase64($logoPath);

        $pdf = Pdf::loadView('pages.hrms.payroll.pdf', [
            'payroll'         => $payroll,
            'item'            => $item,
            'companyName'     => $settings['company_name'] ?: ($company?->company_name ?: 'Company'),
            'companyAddress'  => $settings['company_address'] ?: ($company?->address ?: ''),
            'companyPhone'    => $settings['company_phone'] ?: ($company?->mobile_number ?: ''),
            'companyEmail'    => $settings['company_email'] ?: ($company?->email ?: ''),
            'signatureBase64' => $signatureBase64,
            'logoBase64'      => $logoBase64,
        ])->setPaper('a4', 'portrait')
          ->setOptions([
              'defaultFont'          => 'DejaVu Sans',
              'isHtml5ParserEnabled' => true,
              'isRemoteEnabled'      => true,
          ]);

        return $pdf->stream('payslip_' . $item->employee_code . '_' . optional($payroll->salary_month)->format('Y_m') . '.pdf');
    }

    /**
     * Authorize company scope for a payroll.
     */
    private function authorizePayrollScope(Payroll $payroll): void
    {
        $companyId = auth()->user()?->company_id;
        abort_unless(! $companyId || (int) $payroll->company_id === (int) $companyId, 403, 'Forbidden.');
    }

    /**
     * Compute working date strings for a given date range.
     */
    private function workingDateStringsForPeriod(Carbon $start, Carbon $end): array
    {
        $appTz = config('app.timezone', 'Asia/Kolkata');

        $holidays = HolidayCalendar::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('holiday_date', [
                    $start->copy()->subDay()->toDateString(),
                    $end->copy()->addDay()->toDateString(),
                ]);
            })
            ->pluck('holiday_date')
            ->map(function ($date) use ($appTz) {
                return Carbon::parse($date)->setTimezone($appTz)->toDateString();
            })
            ->all();

        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $isSunday = $cursor->isSunday();
            $saturdayOccurrence = (int) ceil($cursor->day / 7);
            $isFirstOrThirdSaturday = $cursor->isSaturday() && ($saturdayOccurrence === 1 || $saturdayOccurrence === 3);

            if (! $isSunday && ! $isFirstOrThirdSaturday && ! in_array($cursor->toDateString(), $holidays, true)) {
                $dates[] = $cursor->toDateString();
            }

            $cursor->addDay();
        }

        return $dates;
    }

    private function workingDateStringsForMonth(Carbon $month): array
    {
        return $this->workingDateStringsForPeriod($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
    }

    private function isTimesheetLopDepartment(?Department $department): bool
    {
        if (! $department) {
            return false;
        }

        $name = strtolower($department->name);
        foreach (['develop', 'design', 'digital', 'marketing', 'dm', 'sales', 'customer', 'support', 'success', 'cst'] as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function getDepartmentSubmissionType(?Department $department): string
    {
        if (! $department) {
            return 'timesheet';
        }

        $name = strtolower($department->name);
        if (str_contains($name, 'sales')) {
            return 'sales_closing';
        }
        if (str_contains($name, 'customer') || str_contains($name, 'support') || str_contains($name, 'success') || str_contains($name, 'cst')) {
            return 'cst_closing';
        }

        return 'timesheet';
    }

    private function attendanceSummaryForMonth(
        Carbon $month,
        Collection|array $employees,
        int $workingDays,
        array $workingDates = [],
        ?PayrollSetting $settings = null
    ): array {
        return $this->attendanceSummaryForPeriod(
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth(),
            $employees,
            $workingDays,
            $workingDates,
            $settings
        );
    }

    /**
     * Compute attendance summary for list of employees over a given date range.
     */
    private function attendanceSummaryForPeriod(
        Carbon $start,
        Carbon $end,
        Collection|array $employees,
        int $workingDays,
        array $workingDates = [],
        ?PayrollSetting $settings = null
    ): array {
        $settings = $settings ?? PayrollSetting::forCompany(auth()->user()?->company_id);

        if (is_array($employees)) {
            $employees = EmployeeOnboarding::query()
                ->with(['department', 'portalUser'])
                ->whereIn('id', $employees)
                ->get();
        }

        $paidLeaveAllowance = max((float) ($settings?->paid_leave_days ?? 1.0), 0);
        $permissionAllowance = max((int) ($settings?->permission_days_per_month ?? 3), 0);
        $permissionMinutesPerDay = max((float) ($settings?->permission_hours_per_day ?? 2.0) * 60, 0);
        $graceLoginTime = (string) ($settings?->grace_login_time ?: '09:40:00');

        $employeeIds = $employees->pluck('id')->all();
        $portalUserIds = $employees->pluck('portal_user_id')->filter()->unique()->all();

        // 1. Fetch DailyAttendance matching either employee_id or portal_user_id
        $attendanceRows = DailyAttendance::query()
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($employeeIds, $portalUserIds) {
                $q->whereIn('employee_id', $employeeIds);
                if (! empty($portalUserIds)) {
                    $q->orWhereIn('employee_id', $portalUserIds);
                }
            })
            ->get(['id', 'employee_id', 'attendee_type', 'attendance_date', 'attendance_status', 'leave_category', 'leave_session', 'login_time']);

        // 2. Fetch approved LeaveRequests with leaveType
        $approvedLeaves = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where(function ($q) use ($employeeIds, $portalUserIds) {
                $q->whereIn('employee_id', $employeeIds);
                if (! empty($portalUserIds)) {
                    $q->orWhereIn('user_id', $portalUserIds);
                }
            })
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->with('leaveType')
            ->get(['id', 'employee_id', 'user_id', 'leave_type_id', 'start_date', 'end_date', 'total_days']);

        // 3. Fetch approved PermissionRequests
        $approvedPermissions = PermissionRequest::query()
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->where(function ($q) use ($employeeIds, $portalUserIds) {
                $q->whereIn('employee_id', $employeeIds);
                if (! empty($portalUserIds)) {
                    $q->orWhereIn('user_id', $portalUserIds);
                }
            })
            ->whereBetween('permission_date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'employee_id', 'user_id', 'permission_date', 'total_minutes']);

        // 4. Timesheet and daily closings for target portal users
        $timesheetsByUser = ProjectTimesheet::query()
            ->whereIn('user_id', $portalUserIds)
            ->whereBetween('timesheet_date', [$start->toDateString(), $end->toDateString()])
            ->get(['user_id', 'timesheet_date'])
            ->groupBy('user_id');

        $salesClosingsByUser = SalesDailyClosing::query()
            ->whereIn('user_id', $portalUserIds)
            ->whereBetween('closing_date', [$start->toDateString(), $end->toDateString()])
            ->get(['user_id', 'closing_date'])
            ->groupBy('user_id');

        $cstClosingsByUser = CstDailyClosing::query()
            ->whereIn('user_id', $portalUserIds)
            ->whereBetween('closing_date', [$start->toDateString(), $end->toDateString()])
            ->get(['user_id', 'closing_date'])
            ->groupBy('user_id');

        $summary = [];

        foreach ($employees as $employee) {
            $empAttendance = $attendanceRows->filter(fn ($r) =>
                (int) $r->employee_id === (int) $employee->id ||
                ($employee->portal_user_id && (int) $r->employee_id === (int) $employee->portal_user_id)
            );

            $empLeaves = $approvedLeaves->filter(fn ($l) =>
                (int) $l->employee_id === (int) $employee->id ||
                ($employee->portal_user_id && (int) $l->user_id === (int) $employee->portal_user_id)
            );

            $empPerms = $approvedPermissions->filter(fn ($p) =>
                (int) $p->employee_id === (int) $employee->id ||
                ($employee->portal_user_id && (int) $p->user_id === (int) $employee->portal_user_id)
            );

            // Approved leaves by date
            $approvedLeavesByDate = [];
            foreach ($empLeaves as $leave) {
                $isHalf = in_array((int) $leave->leave_type_id, [3, 4], true)
                    || str_contains(strtolower(optional($leave->leaveType)->name ?? ''), 'half')
                    || (float) $leave->total_days === 0.5;
                $val = $isHalf ? 0.5 : 1.0;

                $cur = Carbon::parse($leave->start_date)->startOfDay();
                $lEnd = Carbon::parse($leave->end_date)->startOfDay();
                while ($cur->lte($lEnd)) {
                    if ($cur->gte($start) && $cur->lte($end)) {
                        $d = $cur->toDateString();
                        $approvedLeavesByDate[$d] = max($approvedLeavesByDate[$d] ?? 0, $val);
                    }
                    $cur->addDay();
                }
            }

            $attendedByDate = [];
            $manualLeavesByDate = [];
            $lateLoginsByDate = [];

            foreach ($empAttendance as $r) {
                $d = optional($r->attendance_date)->toDateString();
                if (! $d) {
                    continue;
                }

                if ($r->attendance_status === 'present') {
                    $isHalf = $r->leave_category === 'half_day'
                        || ! empty($r->leave_session)
                        || (($approvedLeavesByDate[$d] ?? 0) === 0.5);

                    $attendedByDate[$d] = $isHalf ? 0.5 : 1.0;

                    if ($r->login_time && $r->login_time > $graceLoginTime) {
                        $lateLoginsByDate[$d] = true;
                    }
                } elseif ($r->attendance_status === 'leave') {
                    $val = match ($r->leave_category) {
                        'half_day' => 0.5,
                        default => 1.0,
                    };
                    $manualLeavesByDate[$d] = max($manualLeavesByDate[$d] ?? 0, $val);
                }
            }

            // Combine manual and approved leaves
            $combinedLeaves = $approvedLeavesByDate;
            foreach ($manualLeavesByDate as $d => $val) {
                $combinedLeaves[$d] = max($combinedLeaves[$d] ?? 0, $val);
            }

            // If an employee worked full day, remove leave. If worked half day, cap leave at 0.5.
            foreach ($attendedByDate as $d => $attVal) {
                if ($attVal >= 1.0) {
                    unset($combinedLeaves[$d]);
                } elseif ($attVal === 0.5) {
                    $combinedLeaves[$d] = min($combinedLeaves[$d] ?? 0.5, 0.5);
                }
            }

            $presentDays = array_sum($attendedByDate);
            $totalLeaveDays = array_sum($combinedLeaves);
            $paidLeaveDays = min($totalLeaveDays, $paidLeaveAllowance);

            // Timesheet LOP calculation
            $tsLop = 0.0;
            if ($this->isTimesheetLopDepartment($employee->department) && $employee->portal_user_id) {
                $subType = $this->getDepartmentSubmissionType($employee->department);
                $userTs = $timesheetsByUser->get($employee->portal_user_id, collect());
                $userSales = $salesClosingsByUser->get($employee->portal_user_id, collect());
                $userCst = $cstClosingsByUser->get($employee->portal_user_id, collect());

                $tsDates = $userTs->pluck('timesheet_date')->map(fn ($d) => optional($d)->toDateString())->filter()->flip()->all();
                $salesDates = $userSales->pluck('closing_date')->map(fn ($d) => optional($d)->toDateString())->filter()->flip()->all();
                $cstDates = $userCst->pluck('closing_date')->map(fn ($d) => optional($d)->toDateString())->filter()->flip()->all();

                $missingCount = 0;
                foreach ($attendedByDate as $d => $attVal) {
                    // Only count missing days when employee was present and not on full/half approved leave
                    if ($attVal > 0 && ! isset($combinedLeaves[$d])) {
                        $submitted = match ($subType) {
                            'sales_closing' => isset($salesDates[$d]) || isset($tsDates[$d]),
                            'cst_closing'   => isset($cstDates[$d]) || isset($tsDates[$d]),
                            default         => isset($tsDates[$d]),
                        };

                        if (! $submitted) {
                            $missingCount++;
                        }
                    }
                }

                $tsLop = floor($missingCount / 2) * 0.5;
            }

            // Permission & late login excess penalty calculation
            $permUsageByDate = [];
            foreach ($empPerms as $p) {
                $pd = optional($p->permission_date)->toDateString();
                if ($pd) {
                    $permUsageByDate[$pd] = ($permUsageByDate[$pd] ?? 0) + (int) $p->total_minutes;
                }
            }

            $dailyExcessPenalty = 0.0;
            foreach ($permUsageByDate as $pd => $mins) {
                if ($permissionMinutesPerDay > 0 && $mins > $permissionMinutesPerDay) {
                    $dailyExcessPenalty += round(($mins - $permissionMinutesPerDay) / (8 * 60), 2);
                }
            }

            $unapprovedLateLogins = 0;
            foreach ($lateLoginsByDate as $ld => $isLate) {
                if (! isset($permUsageByDate[$ld])) {
                    $unapprovedLateLogins++;
                }
            }

            $totalPermissionAndLateEvents = count($permUsageByDate) + $unapprovedLateLogins;
            $excessEvents = max($totalPermissionAndLateEvents - $permissionAllowance, 0);
            $excessLatePenalty = floor($excessEvents / 3) * 0.5;
            $permissionPenaltyDays = $dailyExcessPenalty + $excessLatePenalty;

            // Attendance LOP is working days minus attended and paid leave
            $baseAttendanceLop = max($workingDays - ($presentDays + $paidLeaveDays), 0);
            $totalLop = $baseAttendanceLop + $tsLop + $permissionPenaltyDays;
            $payableDays = max($workingDays - $totalLop, 0);

            $summary[$employee->id] = [
                'days_attended'      => round($presentDays, 2),
                'leave_days'         => round($paidLeaveDays, 2),
                'timesheet_lop_days' => round($tsLop, 2),
                'lop_days'           => round($totalLop, 2),
                'payable_days'       => round($payableDays, 2),
            ];
        }

        return $summary;
    }

    private function workingDatesBetween(
        Carbon $from,
        Carbon $to,
        Carbon $monthStart,
        Carbon $monthEnd,
        array $workingDateLookup
    ): array {
        $cursor = $from->copy()->greaterThan($monthStart) ? $from->copy() : $monthStart->copy();
        $lastDate = $to->copy()->lessThan($monthEnd) ? $to->copy() : $monthEnd->copy();
        $dates = [];

        while ($cursor->lte($lastDate)) {
            $dateKey = $cursor->toDateString();

            if (isset($workingDateLookup[$dateKey])) {
                $dates[] = $dateKey;
            }

            $cursor->addDay();
        }

        return $dates;
    }

    private function salaryComponentsForEmployee(EmployeeOnboarding $employee): array
    {
        $grossSalary = (float) ($employee->gross_salary ?? 0);
        $basicSalary = (float) ($employee->basic_salary ?? 0);
        $hra = (float) ($employee->hra ?? 0);
        $travelAllowance = (float) ($employee->special_allowance ?? 0);
        $otherAllowance = (float) ($employee->other_allowance ?? 0);

        if ($grossSalary > 0 && ($basicSalary + $hra + $travelAllowance + $otherAllowance) <= 0) {
            $basicSalary = round($grossSalary * self::BASIC_SALARY_RATIO, 2);
            $hra = round($grossSalary * self::HRA_RATIO, 2);
            $travelAllowance = round($grossSalary * self::TRAVEL_ALLOWANCE_RATIO, 2);
            $otherAllowance = round(max($grossSalary - $basicSalary - $hra - $travelAllowance, 0), 2);
        }

        return [
            'gross_salary'     => $grossSalary,
            'basic_salary'     => $basicSalary,
            'hra'              => $hra,
            'travel_allowance' => $travelAllowance,
            'other_allowance'  => $otherAllowance,
        ];
    }

    public function calculatePayrollRow(array $row): array
    {
        $workingDays = max((float) ($row['working_days'] ?? 0), 0);
        $daysAttended = max((float) ($row['days_attended'] ?? 0), 0);
        $leaveDays = max((float) ($row['leave_days'] ?? 0), 0);
        $timesheetLop = max((float) ($row['timesheet_lop_days'] ?? 0), 0);

        $attPayable = max($daysAttended + $leaveDays, 0);
        $baseLop = max($workingDays - $attPayable, 0);

        $lopDays = isset($row['lop_days']) ? max((float) $row['lop_days'], 0) : ($baseLop + $timesheetLop);
        $payableDays = isset($row['payable_days']) ? max((float) $row['payable_days'], 0) : max($workingDays - $lopDays, 0);
        $payableDays = min($workingDays > 0 ? $workingDays : $payableDays, $payableDays);

        $grossSalary = round((float) ($row['gross_salary'] ?? 0), 2);
        $salaryComponents = $this->salaryBreakdownFromGross($grossSalary);
        $basicSalary = $salaryComponents['basic_salary'];
        $hra = $salaryComponents['hra'];
        $travelAllowance = $salaryComponents['travel_allowance'];
        $otherAllowance = $salaryComponents['other_allowance'];

        $ratio = $workingDays > 0 ? min($payableDays / $workingDays, 1) : 0;

        $earnedBasic = round($basicSalary * $ratio, 2);
        $earnedHra = round($hra * $ratio, 2);
        $earnedTravel = round($travelAllowance * $ratio, 2);
        $earnedOther = round($otherAllowance * $ratio, 2);
        $earnedGross = round($earnedBasic + $earnedHra + $earnedTravel + $earnedOther, 2);

        $usePf = (bool) ($row['use_pf'] ?? false);
        $useEsi = (bool) ($row['use_esi'] ?? false);
        $pfEmployeePercentage = round((float) ($row['pf_employee_percentage'] ?? 12), 2);
        $pfEmployerPercentage = round((float) ($row['pf_employer_percentage'] ?? 13), 2);
        $esiEmployeePercentage = round((float) ($row['esi_employee_percentage'] ?? 4), 2);
        $esiEmployerPercentage = round((float) ($row['esi_employer_percentage'] ?? 0), 2);
        $esiSalaryLimit = round((float) ($row['esi_salary_limit'] ?? 21000), 2);

        static $cachedPayrollSettings = null;
        if ($cachedPayrollSettings === null) {
            $cachedPayrollSettings = \App\Models\PayrollSetting::forCompany(auth()->user()?->company_id);
        }

        $pfGrossThreshold = round((float) ($row['pf_gross_threshold'] ?? $cachedPayrollSettings->pf_gross_threshold ?? self::PF_GROSS_THRESHOLD), 2);
        $pfBasicCap = round((float) ($row['pf_basic_cap'] ?? $cachedPayrollSettings->pf_basic_cap ?? self::PF_BASIC_CAP), 2);

        $pfBaseAmount = $grossSalary > $pfGrossThreshold
            ? $pfBasicCap
            : ($earnedBasic + $earnedTravel + $earnedOther);
        $pfEmployee = $usePf ? round($pfBaseAmount * ($pfEmployeePercentage / 100), 2) : 0;
        $pfEmployer = $usePf ? round($pfBaseAmount * ($pfEmployerPercentage / 100), 2) : 0;
        $esiEmployee = $useEsi ? round($earnedGross * ($esiEmployeePercentage / 100), 2) : 0;
        $esiEmployer = $useEsi ? round($earnedGross * ($esiEmployerPercentage / 100), 2) : 0;
        $professionalTax = round((float) ($row['professional_tax'] ?? 0), 2);
        $tdsAmount = round((float) ($row['tds_amount'] ?? 0), 2);
        $loanDeduction = round((float) ($row['loan_deduction'] ?? 0), 2);
        $otherDeduction = round((float) ($row['other_deduction'] ?? 0), 2);

        $totalDeductions = round($pfEmployee + $pfEmployer + $esiEmployee + $professionalTax + $tdsAmount + $loanDeduction + $otherDeduction, 2);
        $netSalary = round(max($earnedGross - $totalDeductions, 0), 2);

        return [
            'employee_onboarding_id'   => $row['employee_onboarding_id'],
            'employee_code'            => $row['employee_code'] ?? null,
            'employee_name'            => $row['employee_name'] ?? 'Employee',
            'branch_name'              => $row['branch_name'] ?? null,
            'designation'              => $row['designation'] ?? null,
            'date_of_joining'          => $row['date_of_joining'] ?? null,
            'uan_no'                   => $row['uan_no'] ?? null,
            'esi_no'                   => $row['esi_no'] ?? null,
            'working_days'             => (int) round($workingDays),
            'days_attended'            => round($daysAttended, 2),
            'leave_days'               => round($leaveDays, 2),
            'timesheet_lop_days'       => round($timesheetLop, 2),
            'lop_days'                 => round($lopDays, 2),
            'payable_days'             => round($payableDays, 2),
            'use_pf'                   => $usePf,
            'use_esi'                  => $useEsi,
            'pf_employee_percentage'   => $pfEmployeePercentage,
            'pf_employer_percentage'   => $pfEmployerPercentage,
            'esi_employee_percentage'  => $esiEmployeePercentage,
            'esi_employer_percentage'  => $esiEmployerPercentage,
            'esi_salary_limit'         => $esiSalaryLimit,
            'gross_salary'             => $grossSalary,
            'basic_salary'             => $basicSalary,
            'hra'                      => $hra,
            'travel_allowance'         => $travelAllowance,
            'other_allowance'          => $otherAllowance,
            'earned_basic_salary'      => $earnedBasic,
            'earned_hra'               => $earnedHra,
            'earned_travel_allowance'  => $earnedTravel,
            'earned_other_allowance'   => $earnedOther,
            'earned_gross_salary'      => $earnedGross,
            'pf_employee_contribution' => $pfEmployee,
            'pf_employer_contribution' => $pfEmployer,
            'esi_employee_contribution'=> $esiEmployee,
            'esi_employer_contribution'=> $esiEmployer,
            'professional_tax'         => $professionalTax,
            'tds_amount'               => $tdsAmount,
            'loan_deduction'           => $loanDeduction,
            'other_deduction'          => $otherDeduction,
            'total_deductions'         => $totalDeductions,
            'net_salary'               => $netSalary,
        ];
    }

    private function salaryBreakdownFromGross(float $grossSalary): array
    {
        $grossSalary = round(max($grossSalary, 0), 2);

        if ($grossSalary <= 0) {
            return [
                'basic_salary'     => 0,
                'hra'              => 0,
                'travel_allowance' => 0,
                'other_allowance'  => 0,
            ];
        }

        $basicSalary = round($grossSalary * self::BASIC_SALARY_RATIO, 2);
        $hra = round($grossSalary * self::HRA_RATIO, 2);
        $travelAllowance = round($grossSalary * self::TRAVEL_ALLOWANCE_RATIO, 2);
        $otherAllowance = round(max($grossSalary - $basicSalary - $hra - $travelAllowance, 0), 2);

        return [
            'basic_salary'     => $basicSalary,
            'hra'              => $hra,
            'travel_allowance' => $travelAllowance,
            'other_allowance'  => $otherAllowance,
        ];
    }

    private function imageBase64(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        $resolvedPath = public_path(ltrim((string) $imagePath, '/'));

        if (! file_exists($resolvedPath)) {
            return null;
        }

        $type = pathinfo($resolvedPath, PATHINFO_EXTENSION);
        $data = file_get_contents($resolvedPath);

        if ($data === false) {
            return null;
        }

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
