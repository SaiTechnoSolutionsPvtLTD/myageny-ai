<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SalesTarget;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesTargetSettingController extends Controller
{
    /**
     * Show the Target Allocation page under settings.
     */
    public function index(Request $request): View
    {
        $companyId = auth()->user()?->company_id ?: 1;

        // Fetch all branches for filtering
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();

        $selectedMonth = $request->query('month', date('Y-m'));
        $selectedBranchId = $request->query('branch_id');

        if (!$selectedBranchId) {
            $selectedBranchId = auth()->user()->branch_id ?: ($branches->first()?->id ?? null);
        }

        // Find the Sales department(s)
        $salesDeptIds = Department::where('company_id', $companyId)
            ->whereRaw('LOWER(name) LIKE ?', ['%Sales%'])
            ->pluck('id');

        $targetRoleBaseNames = [
            'branch_admin',
            'branch_manager',
            'cheif_operating_officer',
            'chief_business_officer'
        ];

        // Fetch active users belonging to sales department or matching administrative roles
        $usersQuery = User::with(['roles.department', 'branches'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function($query) use ($salesDeptIds, $companyId, $targetRoleBaseNames) {
                // Belong to Sales department
                $query->whereHas('roles', fn($q) => $q->whereIn('department_id', $salesDeptIds));

                // Or have branch_admin, branch_manager, cheif_operating_officer, chief_business_officer roles
                foreach ($targetRoleBaseNames as $baseRole) {
                    $tenantRole = \App\Models\Role::tenantRoleName($baseRole, $companyId);
                    $query->orWhereHas('roles', fn($q) => $q->where('name', $baseRole)->orWhere('name', $tenantRole));
                }
            });

        // Filter users by selected branch
        if ($selectedBranchId) {
            $usersQuery->where(function($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId)
                  ->orWhereHas('branches', fn($bq) => $bq->where('branches.id', $selectedBranchId));
            });
        }

        $users = $usersQuery->orderBy('name')->get();

        // Existing targets keyed by user_id => target_amount for selected month & branch
        $targets = SalesTarget::where('company_id', $companyId)
            ->where('target_month', $selectedMonth)
            ->where('branch_id', $selectedBranchId)
            ->pluck('target_amount', 'user_id')
            ->toArray();

        return view('pages.settings.sales-targets.index', compact('users', 'targets', 'branches', 'selectedMonth', 'selectedBranchId'));
    }

    /**
     * Store / update sales targets.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()?->company_id ?: 1;

        $validated = $request->validate([
            'month'       => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'branch_id'   => ['required', 'integer', 'exists:branches,id'],
            'targets'     => ['nullable', 'array'],
            'targets.*'   => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        $month = $validated['month'];
        $branchId = (int) $validated['branch_id'];
        $rows = $request->input('targets', []);

        foreach ($rows as $userId => $targetAmount) {
            SalesTarget::updateOrCreate(
                [
                    'company_id'   => $companyId,
                    'user_id'      => (int) $userId,
                    'target_month' => $month,
                    'branch_id'    => $branchId,
                ],
                [
                    'target_amount' => (float) ($targetAmount ?? 0.00),
                ]
            );
        }

        return back()->with('success', 'Sales targets allocated successfully.');
    }
}