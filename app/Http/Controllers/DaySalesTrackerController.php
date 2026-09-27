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

    /**
     * Get Category-wise Day Sales Pivot Report for a selected month (defaults to current month).
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $user = $request->user() ?: auth()->user();
            $monthInput = $request->input('month', now()->format('Y-m'));

            try {
                $carbonMonth = Carbon::parse($monthInput . '-01');
            } catch (\Throwable $e) {
                $carbonMonth = now();
            }

            $monthStart = $carbonMonth->copy()->startOfMonth();
            $monthEnd   = $carbonMonth->copy()->endOfMonth();

            $convertedProducts = LeadProduct::query()
                ->with([
                    'lead.branch',
                    'lead.assignedTo.roles.department',
                    'lead.assignedTo.employeeOnboarding.department',
                    'lead.assignedTo.mappedManagers',
                    'lead.customerSupportTl',
                    'payments' => function ($q) use ($carbonMonth) {
                        $q->whereMonth('payment_date', $carbonMonth->month)
                          ->whereYear('payment_date', $carbonMonth->year);
                    }
                ])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                      ->orWhere('lead_status_id', 5);
                })
                ->where(function ($q) use ($monthStart, $monthEnd) {
                    $q->whereBetween('converted_at', [$monthStart, $monthEnd])
                      ->orWhere(function ($sub) use ($monthStart, $monthEnd) {
                          $sub->whereNull('converted_at')->whereBetween('created_at', [$monthStart, $monthEnd]);
                      });
                })
                ->whereHas('lead', function ($lq) use ($user) {
                    if ($user) {
                        $this->visibility->applyLeadVisibility($lq, $user);
                    }
                })
                ->get();

            $categoriesMap = [];
            foreach ($convertedProducts as $lp) {
                $catName = trim((string) ($lp->day_sales_category ?: 'Unassigned'));
                if (!isset($categoriesMap[$catName])) {
                    $categoriesMap[$catName] = [
                        'category'         => $catName,
                        'count'            => 0,
                        'total_value'      => 0.0,
                        'total_collection' => 0.0,
                        'items'            => [],
                    ];
                }

                $lead = $lp->lead;
                $convDate = $lp->converted_at ?: $lp->created_at;
                $carbonDate = $convDate ? Carbon::parse($convDate) : $carbonMonth;

                $mon = $carbonDate->format('M');
                $dateFormatted = $carbonDate->format('d-m-Y');
                $branch = $lead?->branch?->name ?: 'Coimbatore (HO)';

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

                $assignedUser = $lead?->assignedTo;
                $memberName = $assignedUser?->name ?: 'Unassigned';

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

                $compName = trim((string)($lead?->company_name ?? ''));
                $contactName = trim((string)($lead?->contact_name ?? ''));
                if ($compName && $contactName && $compName !== $contactName) {
                    $accountName = "{$compName} - {$contactName}";
                } else {
                    $accountName = $compName ?: ($contactName ?: 'N/A');
                }

                $monthPayments = (float) $lp->payments->sum('amount');
                $receivedAmount = $monthPayments > 0 ? $monthPayments : (float) ($lp->amount_paid ?: 0);

                $categoriesMap[$catName]['count'] += 1;
                $categoriesMap[$catName]['total_value'] += (float) $lp->total_price;
                $categoriesMap[$catName]['total_collection'] += $receivedAmount;

                $categoriesMap[$catName]['items'][] = [
                    'id'                       => $lp->id,
                    'lead_id'                  => $lp->lead_id,
                    'product_name'             => $lp->product_name ?: 'Product',
                    'mon'                      => $mon,
                    'date'                     => $dateFormatted,
                    'branch'                   => $branch,
                    'branch_type'              => $branchType,
                    'team_leader'              => $tlName ?: '—',
                    'team_member'              => $teamMemberDisplay,
                    'category'                 => $catName,
                    'sale_type'                => $lp->sale_type ?: '',
                    'account_name'             => $accountName,
                    'current_month_collection' => $receivedAmount,
                    'total_price'              => (float) $lp->total_price,
                    'lead_url'                 => url('/leads/' . $lp->lead_id),
                ];
            }

            $reportItems = collect(array_values($categoriesMap))->sortByDesc('total_collection')->values()->all();

            $monthsList = [];
            for ($i = 0; $i < 12; $i++) {
                $m = now()->subMonths($i);
                $monthsList[] = [
                    'value' => $m->format('Y-m'),
                    'label' => $m->format('F Y'),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'month'            => $carbonMonth->format('Y-m'),
                    'formatted_month'  => $carbonMonth->format('F Y'),
                    'months_list'      => $monthsList,
                    'items'            => $reportItems,
                    'total_categories' => count($reportItems),
                    'total_count'      => collect($reportItems)->sum('count'),
                    'total_collection' => round(collect($reportItems)->sum('total_collection'), 2),
                    'total_value'      => round(collect($reportItems)->sum('total_value'), 2),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load Day Sales report.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get Branch-wise Quarterly Collection Trend Analysis and YoY Growth.
     */
    public function trendAnalysis(Request $request): JsonResponse
    {
        try {
            $user = $request->user() ?: auth()->user();
            if ($user && !$user->isSuperAdmin() && !$user->isSystemAdmin() && !$user->isCompanyAdminRole()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access. Trend analysis is restricted to Company Admin.',
                ], 403);
            }

            $year = (int) $request->input('year', now()->year);
            $mode = $request->input('mode', 'financial');

            $prevYear = $year - 1;

            $quarters = $this->getQuartersForYear($year, $mode);
            $prevQuarters = $this->getQuartersForYear($prevYear, $mode);

            $companyId = $user?->company_id;
            $branches = \App\Models\Branch::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'branch_type', 'is_default']);

            $branchDataMap = [];

            foreach ($branches as $b) {
                $bName = $b->name ?: 'Branch #' . $b->id;
                $branchDataMap[$b->id] = [
                    'branch_id'       => $b->id,
                    'branch_name'     => $bName,
                    'branch_type'     => $b->branch_type ?: ($b->is_default ? 'HO' : 'Branch'),
                    'q1'              => 0.0,
                    'q2'              => 0.0,
                    'q3'              => 0.0,
                    'q4'              => 0.0,
                    'total'           => 0.0,
                    'prev_year_total' => 0.0,
                    'yoy_growth'      => 0.0,
                ];
            }

            $cstBranchId = 'cst';
            $branchDataMap[$cstBranchId] = [
                'branch_id'       => 'cst',
                'branch_name'     => 'CST (Renewals & Dev)',
                'branch_type'     => 'CST',
                'q1'              => 0.0,
                'q2'              => 0.0,
                'q3'              => 0.0,
                'q4'              => 0.0,
                'total'           => 0.0,
                'prev_year_total' => 0.0,
                'yoy_growth'      => 0.0,
            ];

            $allStart = $prevQuarters['q1']['start'];
            $allEnd   = $quarters['q4']['end'];

            $payments = \App\Models\LeadProductPayment::query()
                ->with(['lead.branch'])
                ->whereBetween('payment_date', [$allStart, $allEnd])
                ->whereHas('lead', function ($lq) use ($user) {
                    if ($user) {
                        $this->visibility->applyLeadVisibility($lq, $user);
                    }
                })
                ->get();

            foreach ($payments as $p) {
                $pDate = Carbon::parse($p->payment_date);
                $pAmt = (float) $p->amount;
                $bId = $p->lead?->branch_id ?: ($branches->firstWhere('is_default', true)?->id ?? $branches->first()?->id);

                if ($p->lead && ($p->lead->category === 'cst' || str_contains(strtolower($p->lead->category ?? ''), 'cst'))) {
                    $targetBranchKey = $cstBranchId;
                } elseif (isset($branchDataMap[$bId])) {
                    $targetBranchKey = $bId;
                } else {
                    $targetBranchKey = $branches->first()?->id ?? $cstBranchId;
                }

                foreach (['q1', 'q2', 'q3', 'q4'] as $qKey) {
                    if ($pDate->between($quarters[$qKey]['start'], $quarters[$qKey]['end'])) {
                        $branchDataMap[$targetBranchKey][$qKey] += $pAmt;
                        $branchDataMap[$targetBranchKey]['total'] += $pAmt;
                        break;
                    }
                    if ($pDate->between($prevQuarters[$qKey]['start'], $prevQuarters[$qKey]['end'])) {
                        $branchDataMap[$targetBranchKey]['prev_year_total'] += $pAmt;
                        break;
                    }
                }
            }

            $convertedProducts = LeadProduct::query()
                ->with(['lead.branch', 'payments'])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                      ->orWhere('lead_status_id', 5);
                })
                ->where(function ($q) use ($allStart, $allEnd) {
                    $q->whereBetween('converted_at', [$allStart, $allEnd])
                      ->orWhere(function ($sub) use ($allStart, $allEnd) {
                          $sub->whereNull('converted_at')->whereBetween('created_at', [$allStart, $allEnd]);
                      });
                })
                ->whereHas('lead', function ($lq) use ($user) {
                    if ($user) {
                        $this->visibility->applyLeadVisibility($lq, $user);
                    }
                })
                ->get();

            foreach ($convertedProducts as $lp) {
                if ($lp->payments->count() > 0) {
                    continue;
                }

                $convDate = Carbon::parse($lp->converted_at ?: $lp->created_at);
                $pAmt = (float) ($lp->amount_paid ?: $lp->total_price);
                if ($pAmt <= 0) continue;

                $lead = $lp->lead;
                $bId = $lead?->branch_id ?: ($branches->firstWhere('is_default', true)?->id ?? $branches->first()?->id);

                if ($lead && ($lead->category === 'cst' || str_contains(strtolower($lead->category ?? ''), 'cst'))) {
                    $targetBranchKey = $cstBranchId;
                } elseif (isset($branchDataMap[$bId])) {
                    $targetBranchKey = $bId;
                } else {
                    $targetBranchKey = $branches->first()?->id ?? $cstBranchId;
                }

                foreach (['q1', 'q2', 'q3', 'q4'] as $qKey) {
                    if ($convDate->between($quarters[$qKey]['start'], $quarters[$qKey]['end'])) {
                        $branchDataMap[$targetBranchKey][$qKey] += $pAmt;
                        $branchDataMap[$targetBranchKey]['total'] += $pAmt;
                        break;
                    }
                    if ($convDate->between($prevQuarters[$qKey]['start'], $prevQuarters[$qKey]['end'])) {
                        $branchDataMap[$targetBranchKey]['prev_year_total'] += $pAmt;
                        break;
                    }
                }
            }

            $branchList = [];
            foreach ($branchDataMap as $bKey => &$bRow) {
                $prev = $bRow['prev_year_total'];
                $curr = $bRow['total'];
                $bRow['yoy_growth'] = $prev > 0 ? round((($curr - $prev) / $prev) * 100, 1) : ($curr > 0 ? 100.0 : 0.0);
                $branchList[] = $bRow;
            }

            usort($branchList, fn($a, $b) => $b['total'] <=> $a['total']);

            $q1Total = collect($branchList)->sum('q1');
            $q2Total = collect($branchList)->sum('q2');
            $q3Total = collect($branchList)->sum('q3');
            $q4Total = collect($branchList)->sum('q4');
            $grandTotal = collect($branchList)->sum('total');
            $grandPrevTotal = collect($branchList)->sum('prev_year_total');
            $overallYoy = $grandPrevTotal > 0 ? round((($grandTotal - $grandPrevTotal) / $grandPrevTotal) * 100, 1) : ($grandTotal > 0 ? 100.0 : 0.0);

            $qTotals = ['Q1' => $q1Total, 'Q2' => $q2Total, 'Q3' => $q3Total, 'Q4' => $q4Total];
            arsort($qTotals);
            $bestQuarterKey = array_key_first($qTotals) ?: 'Q1';

            $topBranchName = !empty($branchList) && $branchList[0]['total'] > 0 ? $branchList[0]['branch_name'] : 'N/A';

            $yearsList = [];
            $currentYear = now()->year;
            for ($y = $currentYear; $y >= $currentYear - 4; $y--) {
                $yearsList[] = $y;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'year'             => $year,
                    'prev_year'        => $prevYear,
                    'mode'             => $mode,
                    'years_list'       => $yearsList,
                    'quarter_labels'   => [
                        'q1' => $quarters['q1']['label'],
                        'q2' => $quarters['q2']['label'],
                        'q3' => $quarters['q3']['label'],
                        'q4' => $quarters['q4']['label'],
                    ],
                    'branches'         => $branchList,
                    'totals'           => [
                        'q1'              => round($q1Total, 2),
                        'q2'              => round($q2Total, 2),
                        'q3'              => round($q3Total, 2),
                        'q4'              => round($q4Total, 2),
                        'total'           => round($grandTotal, 2),
                        'prev_year_total' => round($grandPrevTotal, 2),
                        'yoy_growth'      => $overallYoy,
                        'best_quarter'    => $bestQuarterKey,
                        'top_branch'      => $topBranchName,
                    ]
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate trend analysis.',
                'error'   => config('app.debug') ? $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() : null,
            ], 500);
        }
    }

    private function getQuartersForYear(int $year, string $mode): array
    {
        if ($mode === 'calendar') {
            return [
                'q1' => ['start' => Carbon::create($year, 1, 1)->startOfDay(),  'end' => Carbon::create($year, 3, 31)->endOfDay(), 'label' => 'Q1 (Jan - Mar)'],
                'q2' => ['start' => Carbon::create($year, 4, 1)->startOfDay(),  'end' => Carbon::create($year, 6, 30)->endOfDay(), 'label' => 'Q2 (Apr - Jun)'],
                'q3' => ['start' => Carbon::create($year, 7, 1)->startOfDay(),  'end' => Carbon::create($year, 9, 30)->endOfDay(), 'label' => 'Q3 (Jul - Sep)'],
                'q4' => ['start' => Carbon::create($year, 10, 1)->startOfDay(), 'end' => Carbon::create($year, 12, 31)->endOfDay(), 'label' => 'Q4 (Oct - Dec)'],
            ];
        }

        return [
            'q1' => ['start' => Carbon::create($year, 4, 1)->startOfDay(),  'end' => Carbon::create($year, 6, 30)->endOfDay(), 'label' => 'Q1 (Apr - Jun)'],
            'q2' => ['start' => Carbon::create($year, 7, 1)->startOfDay(),  'end' => Carbon::create($year, 9, 30)->endOfDay(), 'label' => 'Q2 (Jul - Sep)'],
            'q3' => ['start' => Carbon::create($year, 10, 1)->startOfDay(), 'end' => Carbon::create($year, 12, 31)->endOfDay(), 'label' => 'Q3 (Oct - Dec)'],
            'q4' => ['start' => Carbon::create($year + 1, 1, 1)->startOfDay(), 'end' => Carbon::create($year + 1, 3, 31)->endOfDay(), 'label' => 'Q4 (Jan - Mar)'],
        ];
    }
}
