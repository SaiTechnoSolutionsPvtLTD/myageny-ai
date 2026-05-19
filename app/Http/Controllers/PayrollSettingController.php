<?php

namespace App\Http\Controllers;

use App\Models\PayrollSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollSettingController extends Controller
{
    public function index(): View
    {
        $settings = PayrollSetting::forCompany(auth()->user()?->company_id);

        return view('pages.settings.payroll.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pf_employee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'pf_employer_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_employee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_employer_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_salary_limit' => ['required', 'numeric', 'min:0'],
            'paid_leave_days' => ['required', 'numeric', 'min:0'],
            'permission_days_per_month' => ['required', 'integer', 'min:0'],
            'permission_hours_per_day' => ['required', 'numeric', 'min:0'],
            'grace_login_time' => ['required', 'date_format:H:i'],
        ]);

        $validated['grace_login_time'] .= ':00';

        $settings = PayrollSetting::forCompany(auth()->user()?->company_id);
        $settings->update($validated);

        return back()->with('success', 'Payroll and attendance settings updated successfully.');
    }
}
