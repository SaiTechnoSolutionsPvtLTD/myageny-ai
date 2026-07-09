<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesTargetSettingController extends Controller
{
    /**
     * Show the Target Allocation page under settings.
     */
    public function index(): View
    {
        $companyId = auth()->user()?->company_id ?: 1;

        // Find the Sales department(s)
        $salesDeptIds = Department::where('company_id', $companyId)
            ->whereRaw('LOWER(name) LIKE ?', ['%Sales%'])
            ->pluck('id');

        // Fetch active users belonging to those departments via their roles
        $users = User::with(['roles.department'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->whereIn('department_id', $salesDeptIds))
            ->orderBy('name')
            ->get();

        // Existing targets keyed by user_id => target_amount
        $targets = SalesTarget::where('company_id', $companyId)
            ->pluck('target_amount', 'user_id')
            ->toArray();

        return view('pages.settings.sales-targets.index', compact('users', 'targets'));
    }

    /**
     * Store / update sales targets.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()?->company_id ?: 1;

        $request->validate([
            'targets'   => ['nullable', 'array'],
            'targets.*' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        $rows = $request->input('targets', []);

        foreach ($rows as $userId => $targetAmount) {
            SalesTarget::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'user_id'    => (int) $userId,
                ],
                [
                    'target_amount' => (float) ($targetAmount ?? 0.00),
                ]
            );
        }

        return back()->with('success', 'Sales targets allocated successfully.');
    }
}