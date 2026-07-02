<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DesignSettingTarget;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignSettingController extends Controller
{
    /**
     * Show the Design Settings page.
     * Lists all users whose roles belong to a department named "Designing" (case-insensitive),
     * along with their per-product-type daily targets.
     */
    public function index(): View
    {
        $companyId = auth()->user()?->company_id;

        // Find the Designing department(s) for this company
        $designDeptIds = Department::where('company_id', $companyId)
            ->whereRaw('LOWER(name) LIKE ?', ['%design%'])
            ->pluck('id');

        // Fetch active users belonging to those departments via their roles
        $users = User::with(['roles.department'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->whereIn('department_id', $designDeptIds))
            ->orderBy('name')
            ->get();

        // Existing targets keyed by [user_id][product_type]
        $targets = DesignSettingTarget::mapForCompany($companyId);

        // Product types in use + default list (union, unique, sorted)
        $savedTypes = DesignSettingTarget::where('company_id', $companyId)
            ->distinct()
            ->pluck('product_type')
            ->toArray();

        $productTypes = collect(array_merge(DesignSettingTarget::defaultProductTypes(), $savedTypes))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('pages.settings.design-settings.index', compact(
            'users',
            'targets',
            'productTypes',
        ));
    }

    /**
     * Save / update design targets.
     * Expects: targets[user_id][product_type] = daily_target (integer)
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()?->company_id;

        $request->validate([
            'targets'                       => ['nullable', 'array'],
            'targets.*'                     => ['array'],
            'targets.*.*'                   => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $rows = $request->input('targets', []);

        foreach ($rows as $userId => $typeMap) {
            foreach ($typeMap as $productType => $dailyTarget) {
                $productType = trim($productType);
                if ($productType === '') {
                    continue;
                }

                DesignSettingTarget::updateOrCreate(
                    [
                        'company_id'   => $companyId,
                        'user_id'      => (int) $userId,
                        'product_type' => $productType,
                    ],
                    [
                        'daily_target' => (int) ($dailyTarget ?? 0),
                        'is_active'    => true,
                    ]
                );
            }
        }

        return back()->with('success', 'Design targets saved successfully.');
    }
}
