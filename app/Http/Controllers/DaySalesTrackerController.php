<?php

namespace App\Http\Controllers;

use App\Models\DaySalesTrackerCategory;
use App\Models\LeadProduct;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DaySalesTrackerController extends Controller
{
    public function __construct(
        protected DataVisibilityService $visibility
    ) {}

    /**
     * Get Day Sales Tracker converted products for a specific date (defaults to today).
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $user = $request->user() ?: auth()->user();
            $date = $request->input('date', now()->toDateString());
            $parsedDate = Carbon::parse($date);

            $convertedProducts = LeadProduct::query()
                ->with([
                    'lead.branch',
                    'lead.assignedTo.roles.department',
                    'lead.assignedTo.employeeOnboarding.department',
                    'lead.assignedTo.mappedManagers',
                    'lead.customerSupportTl',
                    'payments' => function ($q) use ($parsedDate) {
                        $q->whereMonth('payment_date', $parsedDate->month)
                          ->whereYear('payment_date', $parsedDate->year);
                    }
                ])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                      ->orWhere('lead_status_id', 5);
                })
                ->where(function ($q) use ($date) {
                    $q->whereDate('converted_at', $date)
                      ->orWhere(function ($sub) use ($date) {
                          $sub->whereNull('converted_at')->whereDate('created_at', $date);
                      });
                })
                ->whereHas('lead', function ($lq) use ($user) {
                    if ($user) {
                        $this->visibility->applyLeadVisibility($lq, $user);
                    }
                })
                ->orderByDesc('id')
                ->get();

            $items = [];
            foreach ($convertedProducts as $idx => $lp) {
                $lead = $lp->lead;
                $convDate = $lp->converted_at ?: $lp->created_at;
                $carbonDate = $convDate ? Carbon::parse($convDate) : $parsedDate;

                // 1. Mon
                $mon = $carbonDate->format('M');

                // 2. Date
                $dateFormatted = $carbonDate->format('d-m-Y');

                // 3. Branch
                $branch = $lead?->branch?->name ?: 'Coimbatore (HO)';

                // 4. Branch Type
                $branchType = $lead?->branch?->branch_type;
                if (empty($branchType)) {
                    $branchName = strtolower($lead?->branch?->name ?? '');
                    if (str_contains($branchName, 'non') || str_contains($branchName, 'non coco') || str_contains($branchName, 'non-coco')) {
                        $branchType = 'NON COCO';
                    } elseif (str_contains($branchName, 'coco')) {
                        $branchType = 'COCO';
                    } elseif (str_contains($branchName, 'ho') || ($lead?->branch?->is_default ?? false)) {
                        $branchType = 'HO';
                    } else {
                        $branchType = 'Branch';
                    }
                }

                // 5 & 6. Team Leader & Team Member name
                $assignedUser = $lead?->assignedTo;
                $memberName = $assignedUser?->name ?: 'Unassigned';

                // Check if assigned user is Branch Manager, TL, Sales Manager, or CBO
                $isSelfLeader = false;
                if ($assignedUser) {
                    $roleKeys = collect($assignedUser->roleKeys()->all());
                    $rawRoleNames = $assignedUser->roles->pluck('name')->map(fn($n) => strtolower($n));

                    $isBm = $assignedUser->isBranchManager()
                        || $roleKeys->intersect(['branch_manager', 'bm'])->isNotEmpty()
                        || $rawRoleNames->contains(fn($r) => str_contains($r, 'branch_manager'));

                    $isTl = $roleKeys->intersect(['sales_tl', 'tl', 'team_leader', 'team_lead', 'teamlead'])->isNotEmpty()
                        || $rawRoleNames->contains(fn($r) => str_contains($r, '_tl') || str_contains($r, 'team_leader') || str_contains($r, 'team_lead'));

                    $isSm = $roleKeys->intersect(['sales_manager'])->isNotEmpty()
                        || $rawRoleNames->contains(fn($r) => str_contains($r, 'sales_manager'));

                    $isCbo = $assignedUser->isCbo()
                        || $roleKeys->intersect(['cbo', 'chief_business_officer', 'cheif_business_officer'])->isNotEmpty()
                        || $rawRoleNames->contains(fn($r) => str_contains($r, 'chief_business_officer') || str_contains($r, 'cbo'));

                    $designation = strtolower($assignedUser->employeeOnboarding?->designation ?? ($assignedUser->designation ?? ''));
                    $hasLeaderDesignation = false;
                    if ($designation !== '') {
                        $hasLeaderDesignation = str_contains($designation, 'branch manager')
                            || str_contains($designation, 'sales manager')
                            || str_contains($designation, 'cbo')
                            || str_contains($designation, 'chief business officer')
                            || str_contains($designation, 'team leader')
                            || str_contains($designation, 'team lead')
                            || str_contains($designation, 'sales tl')
                            || preg_match('/\b(tl|bm)\b/', $designation);
                    }

                    if ($isBm || $isTl || $isSm || $isCbo || $hasLeaderDesignation) {
                        $isSelfLeader = true;
                    }
                }

                if ($isSelfLeader) {
                    // Branch Manager, TL, Sales Manager, and CBO act as their own Team Leader
                    $tlName = $memberName;
                    $teamMemberDisplay = $memberName;
                } else {
                    $manager = $assignedUser?->mappedManagers?->first();
                    $tlName = $manager?->name ?: ($lead?->customerSupportTl?->name ?: null);

                    $deptName = $assignedUser?->employeeOnboarding?->department?->name
                        ?: ($assignedUser?->roles?->first()?->department?->name ?: 'Sales');

                    if (empty($tlName)) {
                        $teamMemberDisplay = $memberName !== 'Unassigned' ? "{$memberName} ({$deptName})" : $deptName;
                    } else {
                        $teamMemberDisplay = $memberName;
                    }
                }

                // 7. Category
                $category = $lp->day_sales_category ?: '';

                // 8. Account name (Company Name - Customer name)
                $compName = trim((string)($lead?->company_name ?? ''));
                $contactName = trim((string)($lead?->contact_name ?? ''));
                if ($compName && $contactName && $compName !== $contactName) {
                    $accountName = "{$compName} - {$contactName}";
                } else {
                    $accountName = $compName ?: ($contactName ?: 'N/A');
                }

                // 9. Current Month Collection (Received amount show aganum)
                $monthPayments = (float) $lp->payments->sum('amount');
                $receivedAmount = $monthPayments > 0 ? $monthPayments : (float) ($lp->amount_paid ?: 0);

                $items[] = [
                    's_no'                     => $idx + 1,
                    'id'                       => $lp->id,
                    'lead_id'                  => $lp->lead_id,
                    'product_name'             => $lp->product_name ?: 'Product',
                    'mon'                      => $mon,
                    'date'                     => $dateFormatted,
                    'branch'                   => $branch,
                    'branch_type'              => $branchType,
                    'team_leader'              => $tlName ?: '—',
                    'team_member'              => $teamMemberDisplay,
                    'category'                 => $category,
                    'sale_type'                => $lp->sale_type ?: '',
                    'account_name'             => $accountName,
                    'current_month_collection' => $receivedAmount,
                    'total_price'              => (float) $lp->total_price,
                    'lead_url'                 => url('/leads/' . $lp->lead_id),
                ];
            }

            $companyId = $user?->company_id;
            $categoriesRecords = DaySalesTrackerCategory::query()
                ->when($companyId, fn($q) => $q->where(fn($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name']);

            $categories = $categoriesRecords->pluck('name')->unique()->values()->all();

            return response()->json([
                'success' => true,
                'data' => [
                    'date'             => $date,
                    'formatted_date'   => $parsedDate->format('d M Y'),
                    'items'            => $items,
                    'categories'       => $categories,
                    'categories_list'  => $categoriesRecords,
                    'total_count'      => count($items),
                    'total_collection' => round(collect($items)->sum('current_month_collection'), 2),
                    'total_value'      => round(collect($items)->sum('total_price'), 2),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load day sales tracker data.',
                'error'   => config('app.debug') ? $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() : null,
            ], 500);
        }
    }

    /**
     * Get list of all categories with ID and name.
     */
    public function categories(Request $request): JsonResponse
    {
        $user = $request->user() ?: auth()->user();
        $companyId = $user?->company_id;

        $categories = DaySalesTrackerCategory::query()
            ->when($companyId, fn($q) => $q->where(fn($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * Add a dynamic category to the Day Sales Tracker.
     */
    public function addCategory(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $user = $request->user() ?: auth()->user();
        $name = trim($request->input('name'));

        $cat = DaySalesTrackerCategory::firstOrCreate([
            'name'       => $name,
            'company_id' => $user?->company_id,
        ]);

        $companyId = $user?->company_id;
        $categoriesRecords = DaySalesTrackerCategory::query()
            ->when($companyId, fn($q) => $q->where(fn($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'         => true,
            'message'         => 'Category added successfully.',
            'category'        => $cat->name,
            'category_item'   => $cat,
            'categories'      => $categoriesRecords->pluck('name')->unique()->values()->all(),
            'categories_list' => $categoriesRecords,
        ]);
    }

    /**
     * Update an existing category name.
     */
    public function updateCategoryName(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $user = $request->user() ?: auth()->user();
        $newName = trim($request->input('name'));

        $cat = DaySalesTrackerCategory::where('id', $id)->first();
        if (!$cat) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $oldName = $cat->name;
        $cat->name = $newName;
        $cat->save();

        if ($oldName !== $newName) {
            LeadProduct::where('day_sales_category', $oldName)->update([
                'day_sales_category' => $newName,
            ]);
        }

        $companyId = $user?->company_id;
        $categoriesRecords = DaySalesTrackerCategory::query()
            ->when($companyId, fn($q) => $q->where(fn($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'         => true,
            'message'         => 'Category updated successfully.',
            'category'        => $cat->name,
            'category_item'   => $cat,
            'categories'      => $categoriesRecords->pluck('name')->unique()->values()->all(),
            'categories_list' => $categoriesRecords,
        ]);
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(Request $request, $id): JsonResponse
    {
        $user = $request->user() ?: auth()->user();
        $cat = DaySalesTrackerCategory::where('id', $id)->first();
        if (!$cat) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $catName = $cat->name;
        $cat->delete();

        // Nullify deleted category on converted products
        LeadProduct::where('day_sales_category', $catName)->update([
            'day_sales_category' => null,
        ]);

        $companyId = $user?->company_id;
        $categoriesRecords = DaySalesTrackerCategory::query()
            ->when($companyId, fn($q) => $q->where(fn($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success'         => true,
            'message'         => 'Category deleted successfully.',
            'deleted_name'    => $catName,
            'categories'      => $categoriesRecords->pluck('name')->unique()->values()->all(),
            'categories_list' => $categoriesRecords,
        ]);
    }

    /**
     * Update the category for a specific converted product (lead_product).
     */
    public function updateCategory(Request $request): JsonResponse
    {
        $request->validate([
            'lead_product_id' => 'required|integer|exists:lead_products,id',
            'category'        => 'nullable|string|max:100',
        ]);

        LeadProduct::where('id', $request->lead_product_id)->update([
            'day_sales_category' => $request->category,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category saved successfully.',
        ]);
    }

    /**
     * Update the sale type (NST, CST, etc.) for a specific converted product (lead_product).
     */
    public function updateSaleType(Request $request): JsonResponse
    {
        $request->validate([
            'lead_product_id' => 'required|integer|exists:lead_products,id',
            'sale_type'       => 'nullable|string|max:50',
        ]);

        LeadProduct::where('id', $request->lead_product_id)->update([
            'sale_type' => $request->sale_type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale type saved successfully.',
        ]);
    }
}
