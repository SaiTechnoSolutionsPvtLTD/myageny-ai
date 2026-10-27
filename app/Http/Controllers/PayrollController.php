<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PayrollController extends Controller
{
    private const BASIC_SALARY_RATIO = 0.50;
    private const HRA_RATIO = 0.30;
    private const TRAVEL_ALLOWANCE_RATIO = 0.10;
    private const PF_BASIC_CAP = 15000.00;
    private const PF_GROSS_THRESHOLD = 21000.00;

    public function index(Request $request): View
    {
        $this->authorizePayrollModuleAccess();

        $selfServiceMode = $this->isSelfServiceUser();

        $payrolls = Payroll::query()
            ->when(auth()->user()?->company_id, fn ($query) => $query->where('company_id', auth()->user()->company_id))
            ->when($selfServiceMode, function ($query) {
                $query->whereHas('items.employee', function ($itemQuery) {
                    $itemQuery->where('portal_user_id', auth()->id());
                });
            })
            ->when($request->month, function ($query) use ($request) {
                $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
                $query->whereDate('salary_month', $month->toDateString());
            })
            ->when($request->filled('salary_cycle'), function ($query) use ($request) {
                if (in_array($request->salary_cycle, ['26_to_25', '25_to_25'], true)) {
                    $query->whereIn('salary_cycle', ['26_to_25', '25_to_25']);
                } elseif (in_array($request->salary_cycle, ['1_to_30', '10_to_10'], true)) {
                    $query->whereIn('salary_cycle', ['1_to_30', '10_to_10']);
                } else {
                    $query->where('salary_cycle', $request->salary_cycle);
                }
            })
            ->latest('salary_month')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $latestNetTotal = (float) optional($payrolls->first())->net_total;

        if ($selfServiceMode && $payrolls->isNotEmpty()) {
            $latestItem = $payrolls->first()
                ->items()
                ->whereHas('employee', function ($query) {
                    $query->where('portal_user_id', auth()->id());
                })
                ->latest('id')
                ->first();

            $latestNetTotal = (float) optional($latestItem)->net_salary;
        }

        return view('pages.hrms.payroll.index', compact('payrolls', 'selfServiceMode', 'latestNetTotal'));
    }

    public function create(Request $request): View
    {
        $this->authorizePayrollModuleAccess();

        abort_if($this->isSelfServiceUser(), 403);

        $selectedMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->string('month'))->startOfMonth()
            : now()->startOfMonth();

        $selectedBranchId = $request->filled('branch_id') ? $request->integer('branch_id') : null;
        $selectedCycle    = $request->filled('salary_cycle') ? $request->string('salary_cycle')->value() : '26_to_25';

        $branches = Branch::query()
            ->where('is_active', true)
            ->when(auth()->user()?->company_id, fn ($q) => $q->where('company_id', auth()->user()->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Fast shell — employee data is loaded via AJAX (loadRows)
        return view('pages.hrms.payroll.create', [
            'selectedMonth'    => $selectedMonth,
            'selectedBranchId' => $selectedBranchId,
            'selectedCycle'    => $selectedCycle,
            'branches'         => $branches,
        ]);
    }

    /**
     * AJAX endpoint: returns payroll row data as JSON.
     * Called by the create view via fetch() to avoid slow full-page reloads.
     */
    public function loadRows(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizePayrollModuleAccess();

        abort_if($this->isSelfServiceUser(), 403);

        $selectedMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->string('month'))->startOfMonth()
            : now()->startOfMonth();

        $selectedBranchId = $request->filled('branch_id') ? $request->integer('branch_id') : null;
        $selectedCycle    = $request->filled('salary_cycle') ? $request->string('salary_cycle')->value() : '26_to_25';

        $employees = EmployeeOnboarding::query()
            ->active()
            ->where(function ($query) {
                $query->whereNull('portal_user_id')
                    ->orWhereHas('portalUser', function ($userQuery) {
                        $userQuery->where('is_active', true);
                    });
            })
            ->with(['role', 'portalUser', 'department'])
            ->when($selectedCycle, function ($query) use ($selectedCycle) {
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
            })
            ->when(auth()->user()?->company_id, function ($query) {
                $query->whereHas('portalUser', function ($subQuery) {
                    $subQuery->where('company_id', auth()->user()->company_id);
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
            ->orderBy('name')
            ->get();

        // Compute cycle range and working days for chosen cycle
        [$cycleStart, $cycleEnd] = self::getCycleDateRange($selectedMonth, $selectedCycle);
        $workingDates   = $this->workingDateStringsForPeriod($cycleStart, $cycleEnd);
        $workingDays    = count($workingDates);
        $payrollSettings = PayrollSetting::forCompany(auth()->user()?->company_id);
        $attendanceSummary = $this->attendanceSummaryForPeriod($cycleStart, $cycleEnd, $employees, $workingDays, $workingDates, $payrollSettings);

        $rows = $employees->map(function (EmployeeOnboarding $employee) use ($attendanceSummary, $workingDays, $payrollSettings) {
            $components = $this->salaryComponentsForEmployee($employee);
            $summary    = $attendanceSummary[$employee->id] ?? [
                'days_attended'      => 0,
                'leave_days'         => 0,
                'timesheet_lop_days' => 0,
                'lop_days'           => $workingDays,
                'payable_days'       => 0,
            ];

            return $this->calculatePayrollRow([
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
                'timesheet_lop_days'       => $summary['timesheet_lop_days'],
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
        })->values();

        $monthLabel = $selectedMonth->format('M Y') . ' (' . $cycleStart->format('d M') . ' - ' . $cycleEnd->format('d M') . ')';

        return response()->json([
            'working_days' => $workingDays,
            'month_label'  => $monthLabel,
            'salary_cycle' => $selectedCycle,
            'cycle_start'  => $cycleStart->toDateString(),
            'cycle_end'    => $cycleEnd->toDateString(),
            'rows'         => $rows,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePayrollModuleAccess();

        abort_if($this->isSelfServiceUser(), 403);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'salary_cycle' => ['nullable', 'string', 'in:26_to_25,1_to_30,25_to_25,10_to_10'],
            'salary_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.employee_onboarding_id' => ['required', 'exists:employee_onboardings,id'],
            'items.*.working_days' => ['required', 'numeric', 'min:0'],
            'items.*.days_attended' => ['required', 'numeric', 'min:0'],
            'items.*.leave_days' => ['required', 'numeric', 'min:0'],
            'items.*.timesheet_lop_days' => ['nullable', 'numeric', 'min:0'],
            'items.*.lop_days' => ['required', 'numeric', 'min:0'],
            'items.*.payable_days' => ['required', 'numeric', 'min:0'],
            'items.*.use_pf' => ['nullable', 'boolean'],
            'items.*.use_esi' => ['nullable', 'boolean'],
            'items.*.pf_employee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.pf_employer_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_employee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_employer_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.esi_salary_limit' => ['required', 'numeric', 'min:0'],
            'items.*.gross_salary' => ['required', 'numeric', 'min:0'],
            'items.*.basic_salary' => ['required', 'numeric', 'min:0'],
            'items.*.hra' => ['required', 'numeric', 'min:0'],
            'items.*.travel_allowance' => ['required', 'numeric', 'min:0'],
            'items.*.other_allowance' => ['required', 'numeric', 'min:0'],
            'items.*.professional_tax' => ['required', 'numeric', 'min:0'],
            'items.*.tds_amount' => ['required', 'numeric', 'min:0'],
            'items.*.loan_deduction' => ['required', 'numeric', 'min:0'],
            'items.*.other_deduction' => ['required', 'numeric', 'min:0'],
        ]);

        $salaryMonth = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $salaryCycle = $validated['salary_cycle'] ?? '26_to_25';
        $employeeIds = collect($validated['items'])->pluck('employee_onboarding_id')->all();
        $employees = EmployeeOnboarding::query()
            ->with('role')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $payroll = DB::transaction(function () use ($validated, $salaryMonth, $salaryCycle, $employees) {
            $payroll = Payroll::updateOrCreate(
                [
                    'company_id'   => auth()->user()?->company_id,
                    'salary_month' => $salaryMonth->toDateString(),
                    'salary_cycle' => $salaryCycle,
                ],
                [
                    'salary_date' => $validated['salary_date'] ?? null,
                    'notes'       => $validated['notes'] ?? null,
                    'created_by'  => auth()->id(),
                    'updated_by'  => auth()->id(),
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
                    'employee_onboarding_id' => $employee->id,
                    'employee_code' => $employee->employee_id,
                    'employee_name' => $employee->name,
                    'designation' => $employee->role?->display_name ?: ($employee->role?->name ? Str::of(Str::afterLast($employee->role->name, '__'))->replace('_', ' ')->title()->value() : 'Employee'),
                    'date_of_joining' => optional($employee->joining_date ?: $employee->salary_effective_from)->format('Y-m-d'),
                    'uan_no' => $employee->uan_no,
                    'esi_no' => $employee->esi_no,
                    'working_days' => $item['working_days'],
                    'days_attended' => $item['days_attended'],
                    'leave_days' => $item['leave_days'],
                    'timesheet_lop_days' => $item['timesheet_lop_days'] ?? 0,
                    'lop_days' => $item['lop_days'],
                    'payable_days' => $item['payable_days'],
                    'use_pf' => (bool) ($item['use_pf'] ?? false),
                    'use_esi' => (bool) ($item['use_esi'] ?? false),
                    'pf_employee_percentage' => $item['pf_employee_percentage'],
                    'pf_employer_percentage' => $item['pf_employer_percentage'],
                    'esi_employee_percentage' => $item['esi_employee_percentage'],
                    'esi_employer_percentage' => $item['esi_employer_percentage'],
                    'esi_salary_limit' => $item['esi_salary_limit'],
                    'gross_salary' => $item['gross_salary'],
                    'basic_salary' => $item['basic_salary'],
                    'hra' => $item['hra'],
                    'travel_allowance' => $item['travel_allowance'],
                    'other_allowance' => $item['other_allowance'],
                    'professional_tax' => $item['professional_tax'],
                    'tds_amount' => $item['tds_amount'],
                    'loan_deduction' => $item['loan_deduction'],
                    'other_deduction' => $item['other_deduction'],
                ]);

                $payroll->items()->create($row);

                $grossTotal += $row['earned_gross_salary'];
                $deductionTotal += $row['total_deductions'];
                $netTotal += $row['net_salary'];
                $workingDays = max($workingDays, (int) $row['working_days']);
            }

            $payroll->update([
                'total_working_days' => $workingDays,
                'employee_count' => $payroll->items()->count(),
                'gross_total' => round($grossTotal, 2),
                'deduction_total' => round($deductionTotal, 2),
                'net_total' => round($netTotal, 2),
                'updated_by' => auth()->id(),
            ]);

            return $payroll;
        });

        return redirect()
            ->route('payroll.show', $payroll)
            ->with('success', 'Payroll processed successfully for ' . $salaryMonth->format('F Y') . '.');
    }

    public function show(Payroll $payroll): View
    {
        $this->authorizePayrollModuleAccess();

        $this->authorizePayroll($payroll);
        $payroll->load(['items.employee', 'company']);

        $selfServiceMode = $this->isSelfServiceUser();
        $items = $payroll->items;

        if ($selfServiceMode) {
            $items = $items->filter(function (PayrollItem $item) {
                return (int) optional($item->employee)->portal_user_id === (int) auth()->id();
            })->values();

            abort_if($items->isEmpty(), 403);
        }

        $summary = [
            'employee_count' => $items->count(),
            'gross_total' => round((float) $items->sum('earned_gross_salary'), 2),
            'deduction_total' => round((float) $items->sum('total_deductions'), 2),
            'net_total' => round((float) $items->sum('net_salary'), 2),
        ];

        return view('pages.hrms.payroll.show', compact('payroll', 'items', 'summary', 'selfServiceMode'));
    }

    public function payslip(Payroll $payroll, PayrollItem $item)
    {
        $this->authorizePayrollModuleAccess();

        $this->authorizePayroll($payroll);
        abort_unless((int) $item->payroll_id === (int) $payroll->id, 404);

        $payroll->loadMissing('company');
        $item->loadMissing('employee');
        abort_if(
            $this->isSelfServiceUser() && (int) optional($item->employee)->portal_user_id !== (int) auth()->id(),
            403
        );

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
            'payroll' => $payroll,
            'item' => $item,
            'companyName' => $settings['company_name'] ?: ($company?->company_name ?: 'Company'),
            'companyAddress' => $settings['company_address'] ?: ($company?->address ?: ''),
            'companyPhone' => $settings['company_phone'] ?: ($company?->mobile_number ?: ''),
            'companyEmail' => $settings['company_email'] ?: ($company?->email ?: ''),
            'signatureBase64' => $signatureBase64,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->stream('payslip_' . $item->employee_code . '_' . $payroll->salary_month->format('Y_m') . '.pdf');
    }

    private function authorizePayroll(Payroll $payroll): void
    {
        abort_unless(! auth()->user()?->company_id || (int) $payroll->company_id === (int) auth()->user()->company_id, 403);
    }

    private function authorizePayrollModuleAccess(): void
    {
        abort_if(auth()->user()?->isHrmsAttendanceOnlyUser(), 403);
    }

    private function isSelfServiceUser(): bool
    {
        return auth()->user()?->isHrmsAttendanceOnlyUser() ?? false;
    }

    public static function getCycleDateRange(Carbon $month, ?string $cycle): array
    {
        if (in_array($cycle, ['26_to_25', '25_to_25'], true)) {
            $start = $month->copy()->subMonthNoOverflow()->setDay(26)->startOfDay();
            $end   = $month->copy()->setDay(25)->endOfDay();
        } elseif (in_array($cycle, ['1_to_30', '10_to_10'], true)) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
        } else {
            $start = $month->copy()->subMonthNoOverflow()->setDay(26)->startOfDay();
            $end   = $month->copy()->setDay(25)->endOfDay();
        }

        return [$start, $end];
    }

    private function workingDaysInMonth(Carbon $month): int
    {
        return count($this->workingDateStringsForMonth($month));
    }

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

    private function attendanceSummaryForMonth(Carbon $month, Collection|array $employees, int $workingDays, array $workingDates = [], ?PayrollSetting $settings = null): array
    {
        return $this->attendanceSummaryForPeriod($month->copy()->startOfMonth(), $month->copy()->endOfMonth(), $employees, $workingDays, $workingDates, $settings);
    }

    private function attendanceSummaryForPeriod(Carbon $start, Carbon $end, Collection|array $employees, int $workingDays, array $workingDates = [], ?PayrollSetting $settings = null): array
    {
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
            $basicSalary = round($grossSalary * 0.50, 2);
            $hra = round($grossSalary * 0.30, 2);
            $travelAllowance = round($grossSalary * 0.10, 2);
            $otherAllowance = round(max($grossSalary - $basicSalary - $hra - $travelAllowance, 0), 2);
        }

        return [
            'gross_salary' => $grossSalary,
            'basic_salary' => $basicSalary,
            'hra' => $hra,
            'travel_allowance' => $travelAllowance,
            'other_allowance' => $otherAllowance,
        ];
    }

    private function calculatePayrollRow(array $row): array
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
            'employee_onboarding_id' => $row['employee_onboarding_id'],
            'employee_code' => $row['employee_code'] ?? null,
            'employee_name' => $row['employee_name'] ?? 'Employee',
            'branch_name' => $row['branch_name'] ?? null,
            'designation' => $row['designation'] ?? null,
            'date_of_joining' => $row['date_of_joining'] ?? null,
            'uan_no' => $row['uan_no'] ?? null,
            'esi_no' => $row['esi_no'] ?? null,
            'working_days' => (int) round($workingDays),
            'days_attended' => round($daysAttended, 2),
            'leave_days' => round($leaveDays, 2),
            'timesheet_lop_days' => round($timesheetLop, 2),
            'lop_days' => round($lopDays, 2),
            'payable_days' => round($payableDays, 2),
            'use_pf' => $usePf,
            'use_esi' => $useEsi,
            'pf_employee_percentage' => $pfEmployeePercentage,
            'pf_employer_percentage' => $pfEmployerPercentage,
            'esi_employee_percentage' => $esiEmployeePercentage,
            'esi_employer_percentage' => $esiEmployerPercentage,
            'esi_salary_limit' => $esiSalaryLimit,
            'gross_salary' => $grossSalary,
            'basic_salary' => $basicSalary,
            'hra' => $hra,
            'travel_allowance' => $travelAllowance,
            'other_allowance' => $otherAllowance,
            'earned_basic_salary' => $earnedBasic,
            'earned_hra' => $earnedHra,
            'earned_travel_allowance' => $earnedTravel,
            'earned_other_allowance' => $earnedOther,
            'earned_gross_salary' => $earnedGross,
            'pf_employee_contribution' => $pfEmployee,
            'pf_employer_contribution' => $pfEmployer,
            'esi_employee_contribution' => $esiEmployee,
            'esi_employer_contribution' => $esiEmployer,
            'professional_tax' => $professionalTax,
            'tds_amount' => $tdsAmount,
            'loan_deduction' => $loanDeduction,
            'other_deduction' => $otherDeduction,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
        ];
    }

    private function salaryBreakdownFromGross(float $grossSalary): array
    {
        $grossSalary = round(max($grossSalary, 0), 2);

        if ($grossSalary <= 0) {
            return [
                'basic_salary' => 0,
                'hra' => 0,
                'travel_allowance' => 0,
                'other_allowance' => 0,
            ];
        }

        $basicSalary = round($grossSalary * self::BASIC_SALARY_RATIO, 2);
        $hra = round($grossSalary * self::HRA_RATIO, 2);
        $travelAllowance = round($grossSalary * self::TRAVEL_ALLOWANCE_RATIO, 2);
        $otherAllowance = round(max($grossSalary - $basicSalary - $hra - $travelAllowance, 0), 2);

        return [
            'basic_salary' => $basicSalary,
            'hra' => $hra,
            'travel_allowance' => $travelAllowance,
            'other_allowance' => $otherAllowance,
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
