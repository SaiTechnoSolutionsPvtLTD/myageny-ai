<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DaySalesTrackerCategory;
use App\Models\LeadProduct;
use App\Models\RoleMapping;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DaySalesTrackerApiController extends Controller
{
    public function __construct(
        protected DataVisibilityService $visibility
    ) {}

    /**
     * Mobile API: Day Sales Tracker converted products list & metrics with strict server-side role-based filtering.
     */
    public function data(Request $request): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = $request->user() ?: auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }

            $date = $request->input('date', now()->toDateString());
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $parsedDate = Carbon::parse($date);

            $query = LeadProduct::query()
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
                });

            // Date filtering: Supports date range (date_from / date_to) or single date
            if ($dateFrom && $dateTo) {
                $query->where(function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('converted_at', [$dateFrom, $dateTo])
                      ->orWhere(function ($sub) use ($dateFrom, $dateTo) {
                          $sub->whereNull('converted_at')->whereBetween('created_at', [$dateFrom, $dateTo]);
                      });
                });
            } else {
                $query->where(function ($q) use ($date) {
                    $q->whereDate('converted_at', $date)
                      ->orWhere(function ($sub) use ($date) {
                          $sub->whereNull('converted_at')->whereDate('created_at', $date);
                      });
                });
            }

            // Apply Strict Server-Side Role-Based Scope & Security Constraints
            $this->applyRoleBasedDataVisibility($query, $user, $request);

            $convertedProducts = $query->orderByDesc('id')->get();

            $items = [];
            foreach ($convertedProducts as $idx => $lp) {
                $lead = $lp->lead;
                $convDate = $lp->converted_at ?: $lp->created_at;
                $carbonDate = $convDate ? Carbon::parse($convDate) : $parsedDate;

                $mon = $carbonDate->format('M');
                $dateFormatted = $carbonDate->format('d-m-Y');
                $branch = $lead?->branch?->name ?: 'Main Branch';

                $branchType = $lead?->branch?->branch_type;
                if (empty($branchType)) {
                    $branchName = strtolower($lead?->branch?->name ?? '');
                    if (str_contains($branchName, 'non') || str_contains($branchName, 'non coco')) {
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
                        || $rawRoleNames->contains(fn($r) => str_contains($r, '_tl') || str_contains($r, 'team_leader'));

                    $isSm = $roleKeys->intersect(['sales_manager'])->isNotEmpty()
                        || $rawRoleNames->contains(fn($r) => str_contains($r, 'sales_manager'));

                    $isCbo = $assignedUser->isCbo()
                        || $roleKeys->intersect(['cbo', 'chief_business_officer'])->isNotEmpty()
                        || $rawRoleNames->contains(fn($r) => str_contains($r, 'cbo'));

                    $designation = strtolower($assignedUser->employeeOnboarding?->designation ?? ($assignedUser->designation ?? ''));
                    $hasLeaderDesignation = $designation !== '' && (
                        str_contains($designation, 'branch manager')
                        || str_contains($designation, 'sales manager')
                        || str_contains($designation, 'cbo')
                        || str_contains($designation, 'team leader')
                    );

                    $isSelfLeader = $isBm || $isTl || $isSm || $isCbo || $hasLeaderDesignation;
                }

                if ($isSelfLeader && $assignedUser) {
                    $teamLeaderName = $assignedUser->name;
                } else {
                    $mappedManager = $assignedUser?->mappedManagers?->first();
                    if ($mappedManager) {
                        $teamLeaderName = $mappedManager->name;
                    } elseif ($lead?->customerSupportTl) {
                        $teamLeaderName = $lead->customerSupportTl->name;
                    } else {
                        $teamLeaderName = 'Direct';
                    }
                }

                $clientName = $lead?->company_name ?: ($lead?->contact_name ?: 'Unnamed Client');

                $department = 'Sales';
                if ($assignedUser) {
                    $dept = $assignedUser->roles->first()?->department?->name
                        ?: ($assignedUser->employeeOnboarding?->department?->name ?: null);
                    if ($dept) {
                        $department = $dept;
                    }
                }

                $productName = $lp->product_name ?: 'N/A';
                $productPrice = (float) ($lp->deal_price ?: 0);

                $monthlyCollected = (float) $lp->payments->sum('amount');
                $totalPaidAllTime = (float) ($lp->total_paid ?? $lp->payments()->sum('amount'));

                $balancePending = max(0, $productPrice - $totalPaidAllTime);

                $catName = $lp->day_sales_category ?: ($lp->daySalesCategory?->name ?: '');
                $saleType = $lp->sale_type ?: 'NEW SALE';

                $items[] = [
                    's_no' => $idx + 1,
                    'id' => $lp->id,
                    'lead_product_id' => $lp->id,
                    'lead_id' => $lp->lead_id,
                    'mon' => $mon,
                    'date' => $dateFormatted,
                    'branch' => $branch,
                    'branch_id' => $lead?->branch_id,
                    'branch_type' => $branchType,
                    'team_leader' => $teamLeaderName,
                    'team_member' => $memberName,
                    'team_member_id' => $assignedUser?->id,
                    'client_name' => $clientName,
                    'account_name' => $clientName,
                    'department' => $department,
                    'product' => $productName,
                    'product_name' => $productName,
                    'product_price' => $productPrice,
                    'total_price' => $productPrice,
                    'month_collected' => $monthlyCollected,
                    'current_month_collection' => $monthlyCollected,
                    'balance_pending' => $balancePending,
                    'category' => $catName,
                    'sale_type' => $saleType,
                    'lead_url' => url("/leads/{$lp->lead_id}"),
                ];
            }

            $categories = DaySalesTrackerCategory::orderBy('name')->get();
            $totalCount = count($items);
            $totalCollection = (float) array_sum(array_column($items, 'month_collected'));
            $totalValue = (float) array_sum(array_column($items, 'product_price'));

            // Options available to logged-in user for client-side filter pickers
            $visibleBranches = $this->visibility->visibleBranches($user)->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
            ])->values()->all();

            $visibleUsers = $this->visibility->visibleAssignableUsers($user)->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values()->all();

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'formatted_date' => Carbon::parse($date)->format('d-m-Y'),
                    'total_count' => $totalCount,
                    'total_collection' => $totalCollection,
                    'total_value' => $totalValue,
                    'items' => $items,
                    'categories' => $categories->pluck('name')->values()->all(),
                    'categories_list' => $categories->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ])->values()->all(),
                    'visible_branches' => $visibleBranches,
                    'visible_users' => $visibleUsers,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Day Sales Tracker data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enforce strict role-based data visibility & security checks on LeadProduct query.
     */
    protected function applyRoleBasedDataVisibility($query, User $user, Request $request): void
    {
        // 1. Enforce company ID scoping
        $companyId = $user->company_id;
        if ($companyId) {
            $query->whereHas('lead', function ($lq) use ($companyId) {
                $lq->where('company_id', $companyId);
            });
        }

        // 2. Identify Role Classification
        $isCompanyWide = $this->visibility->isCompanyWideUser($user)
            || $user->isCompanyAdminRole()
            || $user->isCbo()
            || $user->isSuperAdmin()
            || $user->isSystemAdmin();

        $isBranchAdmin = !$isCompanyWide && $user->isBranchAdmin();
        $isBranchManager = !$isCompanyWide && !$isBranchAdmin && $user->isBranchManager();

        $roleKeys = collect($user->roleKeys()->all());
        $rawRoleNames = $user->roles->pluck('name')->map(fn($n) => strtolower($n));
        $isTl = !$isCompanyWide && !$isBranchAdmin && !$isBranchManager && (
            $roleKeys->intersect(['sales_tl', 'tl', 'team_leader', 'team_lead', 'teamlead'])->isNotEmpty()
            || $rawRoleNames->contains(fn($r) => str_contains($r, '_tl') || str_contains($r, 'team_leader') || str_contains($r, 'team_lead'))
            || $this->visibility->accessLevelFor($user) === RoleMapping::ACCESS_TL
            || $this->visibility->accessLevelFor($user) === RoleMapping::ACCESS_TEAM
        );

        $reqBranchId = $request->input('branch_id');
        $reqUserId = $request->input('user_id');
        $reqCategory = $request->input('category');
        $reqSaleType = $request->input('sale_type');
        $reqSearch = $request->input('search');

        // 3. Apply Server-Side Query Scoping By Role
        if ($isCompanyWide) {
            // Company Admin / CBO: Display overall sales data across all branches
            $query->whereHas('lead', function ($lq) use ($reqBranchId, $reqUserId) {
                if (!empty($reqBranchId)) {
                    $lq->where('branch_id', $reqBranchId);
                }
                if (!empty($reqUserId)) {
                    $lq->where('assigned_to', $reqUserId);
                }
            });
        } elseif ($isBranchAdmin) {
            // Branch Admin: Display data belonging to THEIR ASSIGNED BRANCHES ONLY
            $myBranchIds = array_map('intval', $user->getMyBranchIds());
            if (empty($myBranchIds) && $user->branch_id) {
                $myBranchIds = [(int) $user->branch_id];
            }

            // Security check: If branch_id param requested, validate against user's assigned branches
            if (!empty($reqBranchId) && in_array((int)$reqBranchId, $myBranchIds, true)) {
                $allowedBranchIds = [(int)$reqBranchId];
            } else {
                $allowedBranchIds = $myBranchIds;
            }

            $visibleUserIds = $this->visibility->visibleUserIds($user);

            $query->whereHas('lead', function ($lq) use ($allowedBranchIds, $reqUserId, $visibleUserIds) {
                if (!empty($allowedBranchIds)) {
                    $lq->whereIn('branch_id', $allowedBranchIds);
                } else {
                    $lq->whereRaw('1 = 0');
                }

                if (!empty($reqUserId)) {
                    if ($visibleUserIds === null || in_array((int)$reqUserId, array_map('intval', $visibleUserIds), true)) {
                        $lq->where('assigned_to', $reqUserId);
                    } else {
                        $lq->whereRaw('1 = 0');
                    }
                }
            });
        } elseif ($isBranchManager) {
            // Branch Manager Updated Rule:
            // 1. Main Branch ($mainBranchId): Logged-in Branch Manager's OWN LEADS ONLY ($user->id).
            //    (Branch managers do not have team members in the main branch - excludes all other main branch employees).
            // 2. Additional Assigned Branches ($additionalBranchIds): Leads belonging to assigned additional branches.
            // 3. Unassigned Branches: Excluded completely.

            $mainBranchId = $user->branch_id ? (int) $user->branch_id : null;
            $allBranchIds = array_map('intval', $user->getMyBranchIds());
            if (empty($allBranchIds) && $mainBranchId) {
                $allBranchIds = [$mainBranchId];
            }

            $additionalBranchIds = array_values(array_diff($allBranchIds, array_filter([$mainBranchId])));

            // Security check: If request branch_id parameter is passed, validate against allowed branches
            if (!empty($reqBranchId) && in_array((int)$reqBranchId, $allBranchIds, true)) {
                $targetBranchId = (int) $reqBranchId;
                if ($targetBranchId === $mainBranchId) {
                    $effectiveMainBranchId = $mainBranchId;
                    $effectiveAdditionalBranchIds = [];
                } else {
                    $effectiveMainBranchId = null;
                    $effectiveAdditionalBranchIds = [$targetBranchId];
                }
            } else {
                $effectiveMainBranchId = $mainBranchId;
                $effectiveAdditionalBranchIds = $additionalBranchIds;
            }

            $query->whereHas('lead', function ($lq) use ($user, $effectiveMainBranchId, $effectiveAdditionalBranchIds, $reqUserId) {
                $lq->where(function ($sub) use ($user, $effectiveMainBranchId, $effectiveAdditionalBranchIds) {
                    // Main Branch Condition: Must belong to main branch AND be assigned to the logged-in Branch Manager ONLY
                    if ($effectiveMainBranchId) {
                        $sub->where(function ($mainSub) use ($user, $effectiveMainBranchId) {
                            $mainSub->where('branch_id', $effectiveMainBranchId)
                                    ->where('assigned_to', $user->id);
                        });
                    }

                    // Additional Assigned Branches Condition: Leads belonging to assigned additional branches
                    if (!empty($effectiveAdditionalBranchIds)) {
                        if ($effectiveMainBranchId) {
                            $sub->orWhereIn('branch_id', $effectiveAdditionalBranchIds);
                        } else {
                            $sub->whereIn('branch_id', $effectiveAdditionalBranchIds);
                        }
                    }
                });

                // Optional user filter validation
                if (!empty($reqUserId)) {
                    $lq->where('assigned_to', $reqUserId);
                }
            });
        } elseif ($isTl) {
            // TL (Team Lead): Display own data + data belonging to their team members
            $teamUserIds = $this->visibility->descendantUserIds($user)->push($user->id)->map(fn($id) => (int)$id)->unique()->values()->all();

            if (!empty($reqUserId) && in_array((int)$reqUserId, $teamUserIds, true)) {
                $allowedUserIds = [(int)$reqUserId];
            } else {
                $allowedUserIds = $teamUserIds;
            }

            $query->whereHas('lead', function ($lq) use ($allowedUserIds, $reqBranchId) {
                $lq->whereIn('assigned_to', $allowedUserIds);
                if (!empty($reqBranchId)) {
                    $lq->where('branch_id', $reqBranchId);
                }
            });
        } else {
            // Other Roles / Executive: Display ONLY THEIR OWN DATA
            $query->whereHas('lead', function ($lq) use ($user) {
                $lq->where('assigned_to', $user->id);
            });
        }

        // Apply additional category, sale_type, and search filters securely
        if (!empty($reqCategory)) {
            $query->where(function ($q) use ($reqCategory) {
                $q->where('day_sales_category', $reqCategory)
                  ->orWhereHas('daySalesCategory', fn($catQ) => $catQ->where('name', $reqCategory));
            });
        }

        if (!empty($reqSaleType)) {
            $query->where('sale_type', $reqSaleType);
        }

        if (!empty($reqSearch)) {
            $searchTerm = '%' . trim($reqSearch) . '%';
            $query->where(function ($sq) use ($searchTerm) {
                $sq->where('product_name', 'like', $searchTerm)
                  ->orWhere('day_sales_category', 'like', $searchTerm)
                  ->orWhere('sale_type', 'like', $searchTerm)
                  ->orWhereHas('lead', function ($lq) use ($searchTerm) {
                      $lq->where('company_name', 'like', $searchTerm)
                        ->orWhere('contact_name', 'like', $searchTerm)
                        ->orWhereHas('assignedTo', fn($uq) => $uq->where('name', 'like', $searchTerm))
                        ->orWhereHas('branch', fn($bq) => $bq->where('name', 'like', $searchTerm));
                  });
            });
        }
    }

    /**
     * Mobile API: Day Sales Categories list.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = DaySalesTrackerCategory::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'categories' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ]),
        ]);
    }

    /**
     * Mobile API: Add Day Sales Category.
     */
    public function addCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:day_sales_tracker_categories,name'],
        ]);

        $cat = DaySalesTrackerCategory::create([
            'name' => trim($validated['name']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
            ],
        ], 201);
    }

    /**
     * Mobile API: Update Day Sales Category Name.
     */
    public function updateCategoryName(Request $request, int $id): JsonResponse
    {
        $cat = DaySalesTrackerCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:day_sales_tracker_categories,name,' . $id],
        ]);

        $cat->update(['name' => trim($validated['name'])]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
            ],
        ]);
    }

    /**
     * Mobile API: Delete Day Sales Category.
     */
    public function deleteCategory(Request $request, int $id): JsonResponse
    {
        $cat = DaySalesTrackerCategory::findOrFail($id);
        $cat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Mobile API: Update Category for a Day Sales Item.
     */
    public function updateCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_product_id' => ['required', 'integer', 'exists:lead_products,id'],
            'category' => ['nullable', 'string'],
        ]);

        /** @var User|null $user */
        $user = $request->user() ?: auth()->user();
        $lp = LeadProduct::with('lead')->findOrFail($validated['lead_product_id']);

        if ($user && $lp->lead && !$this->visibility->canAccessLead($lp->lead, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to modify this record.',
            ], 403);
        }

        $lp->update(['day_sales_category' => $validated['category'] ?? null]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated for item.',
            'lead_product_id' => $lp->id,
            'category' => $lp->day_sales_category,
        ]);
    }

    /**
     * Mobile API: Update Sale Type for a Day Sales Item.
     */
    public function updateSaleType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_product_id' => ['required', 'integer', 'exists:lead_products,id'],
            'sale_type' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = $request->user() ?: auth()->user();
        $lp = LeadProduct::with('lead')->findOrFail($validated['lead_product_id']);

        if ($user && $lp->lead && !$this->visibility->canAccessLead($lp->lead, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to modify this record.',
            ], 403);
        }

        $lp->update(['sale_type' => $validated['sale_type']]);

        return response()->json([
            'success' => true,
            'message' => 'Sale type updated for item.',
            'lead_product_id' => $lp->id,
            'sale_type' => $lp->sale_type,
        ]);
    }
}
