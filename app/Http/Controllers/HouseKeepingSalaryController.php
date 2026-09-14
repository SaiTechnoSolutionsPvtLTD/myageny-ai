<?php

namespace App\Http\Controllers;

use App\Models\HouseKeepingAttendance;
use App\Models\HouseKeepingEmployee;
use App\Models\HouseKeepingSalary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HouseKeepingSalaryController extends Controller
{
    public function index(Request $request): View
    {
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

        // 3. Fetch monthly attendance summary for these employees in this month
        $attendances = HouseKeepingAttendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->whereIn('house_keeping_employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('house_keeping_employee_id');

        // 4. Build monthly summary data for active employees
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

            return (object) [
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

        // 5. Overall history logs
        $historyQuery = HouseKeepingSalary::with('employee')
            ->orderBy('salary_month', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('filter_employee_id')) {
            $historyQuery->where('house_keeping_employee_id', $request->filter_employee_id);
        }

        if ($request->filled('filter_month')) {
            $historyQuery->where('salary_month', $request->filter_month);
        }

        $allEmployees = HouseKeepingEmployee::orderBy('name')->get();
        $salaryLogs = $historyQuery->paginate(15, ['*'], 'page')->withQueryString();

        // Summary stats for the selected month
        $totalStaff = $employees->count();
        $totalPaidCount = $salaries->where('payment_status', 'Paid')->count();
        $totalDisbursed = $salaries->sum('paid_amount');
        $totalBasePay = $employees->sum('salary');

        return view('pages.hrms.house-keeping.salaries.index', compact(
            'selectedMonth',
            'daysInMonth',
            'employeeData',
            'allEmployees',
            'salaryLogs',
            'totalStaff',
            'totalPaidCount',
            'totalDisbursed',
            'totalBasePay'
        ));
    }

    public function storeOrUpdate(Request $request): RedirectResponse
    {
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

        HouseKeepingSalary::updateOrCreate(
            [
                'house_keeping_employee_id' => $validated['house_keeping_employee_id'],
                'salary_month'              => $validated['salary_month'],
            ],
            $validated
        );

        return redirect()->back()->with('success', 'Housekeeping salary payout record has been saved successfully.');
    }

    public function destroy(HouseKeepingSalary $salary): RedirectResponse
    {
        $salary->delete();

        return redirect()->back()->with('success', 'Housekeeping salary record has been removed.');
    }
}