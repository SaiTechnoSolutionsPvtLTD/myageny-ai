<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DailyAttendance;
use App\Models\EmployeeOnboarding;
use App\Models\HolidayCalendar;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;
use App\Models\PermissionRequest;
use App\Models\QuotationSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                    $month = Carbon::createFromFormat('Y-m', $request->string('month'))->startOfMonth();
                    $q->whereDate('salary_month', $month->toDateString());
                } catch (\Exception $e) {
                    // ignore invalid date format
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
            ? Carbon::createFromFormat('Y-m', $request->string('month'))->startOfMonth()
            : now()->startOfMonth();

        $user = auth()->user();
        $selectedBranchId = $request->filled('branch_id')
            ? $request->integer('branch_id')
            : ($user?->branch_id ?: 1);

        $branches = Branch::query()
            ->where('is_active', true)
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $workingDays = count($this->workingDateStringsForMonth($selectedMonth));
        $payrollSettings = PayrollSetting::forCompany($user?->company_id);

        return response()->json([
            'success'          => true,
            'selected_month'   => $selectedMonth->format('Y-m'),
            'month_label'      => $selectedMonth->format('M Y'),
            'selected_branch_id' => $selectedBranchId,
            'working_days'     => $workingDays,
            'branches'         => $branches,
            'settings'         => [
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
            ? Carbon::createFromFormat('Y-m', $request->string('month'))->startOfMonth()
            : now()->startOfMonth();

        $selectedBranchId = $request->filled('branch_id') ? $request->integer('branch_id') : null;
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
            ->with(['role', 'portalUser'])
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

        // Working dates and attendance calculation
        $workingDates   = $this->workingDateStringsForMonth($selectedMonth);
        $workingDays    = count($workingDates);
        $payrollSettings = PayrollSetting::forCompany($user?->company_id);
        $attendanceSummary = $this->attendanceSummaryForMonth($selectedMonth, $employees->pluck('id')->all(), $workingDays, $workingDates, $payrollSettings);

        $totalNetSalary = 0.0;
        $totalGrossSalary = 0.0;
        $totalDeductions = 0.0;

        $rows = $employees->map(function (EmployeeOnboarding $employee) use ($attendanceSummary, $workingDays, $payrollSettings, &$totalNetSalary, &$totalGrossSalary, &$totalDeductions) {
            $components = $this->salaryComponentsForEmployee($employee);
            $summary    = $attendanceSummary[$employee->id] ?? [
                'days_attended' => 0,
                'leave_days'    => 0,
                'lop_days'      => $workingDays,
                'payable_days'  => 0,
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

        return response()->json([
            'success'      => true,
            'working_days' => $workingDays,
            'month_label'  => $selectedMonth->format('M Y'),
            'summary'      => [
                'verified_employees' => $rows->count(),
                'total_working_days' => $workingDays,
                'payroll_month'      => $selectedMonth->format('M Y'),
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

        $validated = $request->validate([
            'month'                          => ['required', 'date_format:Y-m'],
            'salary_date'                    => ['nullable', 'date'],
            'notes'                          => ['nullable', 'string'],
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.employee_onboarding_id' => ['required', 'exists:employee_onboardings,id'],
            'items.*.working_days'           => ['required', 'numeric', 'min:0'],
            'items.*.days_attended'          => ['required', 'numeric', 'min:0'],
            'items.*.leave_days'             => ['required', 'numeric', 'min:0'],
            'items.*.lop_days'               => ['required', 'numeric', 'min:0'],
            'items.*.payable_days'           => ['required', 'numeric', 'min:0'],
            'items.*.use_pf'                 => ['nullable', 'boolean'],
            'items.*.use_esi'                => ['nullable', 'boolean'],
            'items.*.pf_employee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.pf_employer_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_employee_percentage'=> ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_employer_percentage'=> ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_salary_limit'       => ['required', 'numeric', 'min:0'],
            'items.*.gross_salary'           => ['required', 'numeric', 'min:0'],
            'items.*.basic_salary'           => ['required', 'numeric', 'min:0'],
            'items.*.hra'                    => ['required', 'numeric', 'min:0'],
            'items.*.travel_allowance'       => ['required', 'numeric', 'min:0'],
            'items.*.other_allowance'        => ['required', 'numeric', 'min:0'],
            'items.*.professional_tax'       => ['required', 'numeric', 'min:0'],
            'items.*.tds_amount'             => ['required', 'numeric', 'min:0'],
            'items.*.loan_deduction'         => ['required', 'numeric', 'min:0'],
            'items.*.other_deduction'        => ['required', 'numeric', 'min:0'],
        ]);

        $salaryMonth = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $employeeIds = collect($validated['items'])->pluck('employee_onboarding_id')->all();
        $employees = EmployeeOnboarding::query()
            ->with('role')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $payroll = DB::transaction(function () use ($validated, $salaryMonth, $employees) {
            $user = auth()->user();
            $payroll = Payroll::updateOrCreate(
                [
                    'company_id'   => $user?->company_id,
                    'salary_month' => $salaryMonth->toDateString(),
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
     * Compute working date strings for a given month.
     */
    private function workingDateStringsForMonth(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $holidays = HolidayCalendar::query()
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
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

    /**
     * Compute attendance summary for list of employees.
     */
    private function attendanceSummaryForMonth(
        Carbon $month,
        array $employeeIds,
        int $workingDays,
        array $workingDates = [],
        ?PayrollSetting $settings = null
    ): array {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $settings = $settings ?? PayrollSetting::forCompany(auth()->user()?->company_id);

        if (empty($workingDates)) {
            $workingDates = $this->workingDateStringsForMonth($month);
        }

        $workingDateLookup = array_flip($workingDates);
        $paidLeaveAllowance = max((float) $settings->paid_leave_days, 0);
        $permissionAllowance = max((int) $settings->permission_days_per_month, 0);
        $permissionMinutesPerDay = max((float) $settings->permission_hours_per_day * 60, 0);
        $graceLoginTime = (string) ($settings->grace_login_time ?: '09:30:00');

        $rows = DailyAttendance::query()
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get(['employee_id', 'attendance_date', 'attendance_status', 'leave_category', 'login_time']);

        $approvedLeaves = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get(['employee_id', 'start_date', 'end_date']);

        $approvedPermissions = PermissionRequest::query()
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('permission_date', [$start->toDateString(), $end->toDateString()])
            ->get(['employee_id', 'permission_date', 'total_minutes']);

        $summary = [];

        foreach ($employeeIds as $employeeId) {
            $employeeRows = $rows->where('employee_id', $employeeId);
            $presentDates = $employeeRows
                ->where('attendance_status', 'present')
                ->pluck('attendance_date')
                ->map(fn ($date) => optional($date)->toDateString())
                ->filter(fn (?string $date) => $date && isset($workingDateLookup[$date]))
                ->unique()
                ->values();

            $presentDateLookup = array_flip($presentDates->all());

            $manualLeaveDates = $employeeRows
                ->where('attendance_status', 'leave')
                ->mapWithKeys(function ($row) use ($workingDateLookup, $presentDateLookup) {
                    $date = optional($row->attendance_date)->toDateString();

                    if (! $date || ! isset($workingDateLookup[$date]) || isset($presentDateLookup[$date])) {
                        return [];
                    }

                    $value = match ($row->leave_category) {
                        'paid'     => 1.0,
                        'half_day' => 0.5,
                        default    => 0.0,
                    };

                    if ($value <= 0) {
                        return [];
                    }

                    return [$date => $value];
                });

            $approvedLeaveDates = collect();

            foreach ($approvedLeaves->where('employee_id', $employeeId) as $leaveRequest) {
                $approvedLeaveDates = $approvedLeaveDates->merge(
                    $this->workingDatesBetween(
                        Carbon::parse($leaveRequest->start_date),
                        Carbon::parse($leaveRequest->end_date),
                        $start,
                        $end,
                        $workingDateLookup
                    )
                );
            }

            $approvedLeaveMap = $approvedLeaveDates
                ->filter(fn (?string $date) => $date && ! isset($presentDateLookup[$date]))
                ->mapWithKeys(fn (string $date) => [$date => 1.0]);

            $leaveDaysByDate = $approvedLeaveMap;

            foreach ($manualLeaveDates as $date => $value) {
                $leaveDaysByDate[$date] = max((float) ($leaveDaysByDate[$date] ?? 0), min((float) $value, 1.0));
            }

            $paidLeaveDays = min($leaveDaysByDate->sum(), $paidLeaveAllowance);

            $permissionUsage = [];

            foreach ($approvedPermissions->where('employee_id', $employeeId) as $permissionRequest) {
                $permissionDate = optional($permissionRequest->permission_date)->toDateString();

                if (! $permissionDate || ! isset($workingDateLookup[$permissionDate])) {
                    continue;
                }

                $permissionUsage[$permissionDate] = ($permissionUsage[$permissionDate] ?? 0) + (int) $permissionRequest->total_minutes;
            }

            foreach ($employeeRows->where('attendance_status', 'present') as $attendanceRow) {
                $attendanceDate = optional($attendanceRow->attendance_date)->toDateString();

                if (! $attendanceDate || ! isset($workingDateLookup[$attendanceDate]) || ! $attendanceRow->login_time) {
                    continue;
                }

                if ($attendanceRow->login_time > $graceLoginTime) {
                    $permissionUsage[$attendanceDate] = $permissionUsage[$attendanceDate] ?? 0;
                }
            }

            ksort($permissionUsage);

            $permissionPenaltyDays = 0.0;
            $permissionIndex = 0;

            foreach ($permissionUsage as $minutesUsed) {
                if ($permissionIndex >= $permissionAllowance) {
                    $permissionPenaltyDays += 1;
                    $permissionIndex++;
                    continue;
                }

                if ($permissionMinutesPerDay <= 0) {
                    if ($minutesUsed > 0) {
                        $permissionPenaltyDays += 1;
                    }
                } elseif ($minutesUsed > $permissionMinutesPerDay) {
                    $permissionPenaltyDays += round(($minutesUsed - $permissionMinutesPerDay) / $permissionMinutesPerDay, 2);
                }

                $permissionIndex++;
            }

            $present = $presentDates->count();
            $leave = round($paidLeaveDays, 2);
            $payableDays = min($workingDays, max(($present + $paidLeaveDays) - $permissionPenaltyDays, 0));
            $lopDays = max($workingDays - $payableDays, 0);

            $summary[$employeeId] = [
                'days_attended' => $present,
                'leave_days'    => $leave,
                'lop_days'      => $lopDays,
                'payable_days'  => $payableDays,
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
        $rawPayableDays = max($daysAttended + $leaveDays, 0);
        $payableDays = min($workingDays > 0 ? $workingDays : $rawPayableDays, $rawPayableDays);
        $lopDays = max($workingDays - $payableDays, 0);

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
        $esiEmployeePercentage = round((float) ($row['esi_employee_percentage'] ?? 0.75), 2);
        $esiEmployerPercentage = round((float) ($row['esi_employer_percentage'] ?? 3.25), 2);
        $esiSalaryLimit = round((float) ($row['esi_salary_limit'] ?? 21000), 2);

        $pfBaseAmount = $grossSalary > self::PF_GROSS_THRESHOLD
            ? self::PF_BASIC_CAP
            : ($earnedBasic + $earnedTravel + $earnedOther);
        $pfEmployee = $usePf ? round($pfBaseAmount * ($pfEmployeePercentage / 100), 2) : 0;
        $pfEmployer = $usePf ? round($pfBaseAmount * ($pfEmployerPercentage / 100), 2) : 0;
        $esiEmployee = $useEsi ? round($earnedGross * ($esiEmployeePercentage / 100), 2) : 0;
        $esiEmployer = $useEsi ? round($earnedGross * ($esiEmployerPercentage / 100), 2) : 0;
        $professionalTax = round((float) ($row['professional_tax'] ?? 0), 2);
        $tdsAmount = round((float) ($row['tds_amount'] ?? 0), 2);
        $loanDeduction = round((float) ($row['loan_deduction'] ?? 0), 2);
        $otherDeduction = round((float) ($row['other_deduction'] ?? 0), 2);

        $totalDeductions = round($pfEmployee + $esiEmployee + $professionalTax + $tdsAmount + $loanDeduction + $otherDeduction, 2);
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
