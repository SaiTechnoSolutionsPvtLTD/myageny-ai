<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->latest('salary_month')
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

        $employees = EmployeeOnboarding::query()
            ->active()
            ->where(function ($query) {
                $query->whereNull('portal_user_id')
                    ->orWhereHas('portalUser', function ($userQuery) {
                        $userQuery->where('is_active', true);
                    });
            })
            ->with(['role', 'portalUser.branch'])
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

        $branches = Branch::query()
            ->where('is_active', true)
            ->when(auth()->user()?->company_id, fn ($q) => $q->where('company_id', auth()->user()->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $workingDays = $this->workingDaysInMonth($selectedMonth);
        $payrollSettings = PayrollSetting::forCompany(auth()->user()?->company_id);
        $attendanceSummary = $this->attendanceSummaryForMonth($selectedMonth, $employees->pluck('id')->all(), $workingDays);

        $rows = $employees->map(function (EmployeeOnboarding $employee) use ($attendanceSummary, $workingDays, $payrollSettings) {
            $components = $this->salaryComponentsForEmployee($employee);
            $summary = $attendanceSummary[$employee->id] ?? [
                'days_attended' => 0,
                'leave_days' => 0,
                'lop_days' => $workingDays,
                'payable_days' => 0,
            ];

            return $this->calculatePayrollRow([
                'employee_onboarding_id' => $employee->id,
                'employee_code' => $employee->employee_id,
                'employee_name' => $employee->name,
                'branch_name' => $employee->branch_name,
                'designation' => $employee->role?->display_name ?: ($employee->role?->name ? Str::of(Str::afterLast($employee->role->name, '__'))->replace('_', ' ')->title()->value() : 'Employee'),
                'date_of_joining' => optional($employee->joining_date ?: $employee->salary_effective_from)->format('Y-m-d'),
                'uan_no' => $employee->uan_no,
                'esi_no' => $employee->esi_no,
                'working_days' => $workingDays,
                'days_attended' => $summary['days_attended'],
                'leave_days' => $summary['leave_days'],
                'lop_days' => $summary['lop_days'],
                'payable_days' => $summary['payable_days'],
                'use_pf' => (bool) $employee->pf_enabled,
                'use_esi' => (bool) $employee->esi_enabled,
                'pf_employee_percentage' => (float) $payrollSettings->pf_employee_percentage,
                'pf_employer_percentage' => (float) $payrollSettings->pf_employer_percentage,
                'esi_employee_percentage' => (float) $payrollSettings->esi_employee_percentage,
                'esi_employer_percentage' => (float) $payrollSettings->esi_employer_percentage,
                'esi_salary_limit' => (float) $payrollSettings->esi_salary_limit,
                'gross_salary' => $components['gross_salary'],
                'basic_salary' => $components['basic_salary'],
                'hra' => $components['hra'],
                'travel_allowance' => $components['travel_allowance'],
                'other_allowance' => $components['other_allowance'],
                'professional_tax' => (float) ($employee->professional_tax ?? 0),
                'tds_amount' => (float) ($employee->tds_amount ?? 0),
                'loan_deduction' => (float) ($employee->loan_deduction ?? 0),
                'other_deduction' => (float) ($employee->other_deduction ?? 0),
            ]);
        })->values();

        return view('pages.hrms.payroll.create', [
            'selectedMonth' => $selectedMonth,
            'selectedBranchId' => $selectedBranchId,
            'branches' => $branches,
            'workingDays' => $workingDays,
            'payrollSettings' => $payrollSettings,
            'rows' => $rows,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePayrollModuleAccess();

        abort_if($this->isSelfServiceUser(), 403);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'salary_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.employee_onboarding_id' => ['required', 'exists:employee_onboardings,id'],
            'items.*.working_days' => ['required', 'numeric', 'min:0'],
            'items.*.days_attended' => ['required', 'numeric', 'min:0'],
            'items.*.leave_days' => ['required', 'numeric', 'min:0'],
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
        $employeeIds = collect($validated['items'])->pluck('employee_onboarding_id')->all();
        $employees = EmployeeOnboarding::query()
            ->with('role')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $payroll = DB::transaction(function () use ($validated, $salaryMonth, $employees) {
            $payroll = Payroll::updateOrCreate(
                [
                    'company_id' => auth()->user()?->company_id,
                    'salary_month' => $salaryMonth->toDateString(),
                ],
                [
                    'salary_date' => $validated['salary_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
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

    private function workingDaysInMonth(Carbon $month): int
    {
        return count($this->workingDateStringsForMonth($month));
    }

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

    private function attendanceSummaryForMonth(Carbon $month, array $employeeIds, int $workingDays): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $settings = PayrollSetting::forCompany(auth()->user()?->company_id);
        $workingDates = $this->workingDateStringsForMonth($month);
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
                        'paid' => 1.0,
                        'half_day' => 0.5,
                        default => 0.0,
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
                'leave_days' => $leave,
                'lop_days' => $lopDays,
                'payable_days' => $payableDays,
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
