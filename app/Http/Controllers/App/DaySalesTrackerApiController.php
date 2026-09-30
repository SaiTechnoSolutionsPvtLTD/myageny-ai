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

            $date     = $request->input('date', now()->toDateString());
            $dateFrom = $request->input('date_from');
            $dateTo   = $request->input('date_to');

            $startDateStr = Carbon::parse($dateFrom ?: ($dateTo ?: $date))->toDateString();
            $endDateStr   = Carbon::parse($dateTo ?: ($dateFrom ?: $date))->toDateString();

            if ($startDateStr > $endDateStr) {
                [$startDateStr, $endDateStr] = [$endDateStr, $startDateStr];
            }

            if ($startDateStr === $endDateStr) {
                $formattedDateLabel = Carbon::parse($startDateStr)->format('d M Y');
            } else {
                $formattedDateLabel = Carbon::parse($startDateStr)->format('d M Y') . ' to ' . Carbon::parse($endDateStr)->format('d M Y');
            }

            $query = LeadProduct::query()
                ->with([
                    'lead.branch',
                    'lead.assignedTo.roles.department',
                    'lead.assignedTo.employeeOnboarding.department',
                    'lead.assignedTo.mappedManagers',
                    'lead.customerSupportTl',
                    'payments' => function ($q) use ($startDateStr, $endDateStr) {
                        $q->whereBetween('payment_date', [$startDateStr, $endDateStr]);
                    }
                ])
                ->where(function ($q) {
                    $q->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                      ->orWhere('lead_status_id', 5);
                })
                ->where(function ($q) use ($startDateStr, $endDateStr) {
                    $q->whereHas('payments', function ($pq) use ($startDateStr, $endDateStr) {
                        $pq->whereBetween('payment_date', [$startDateStr, $endDateStr]);
                    })
                    ->orWhereBetween('payment_date', [$startDateStr, $endDateStr])
                    ->orWhere(function ($sub) use ($startDateStr, $endDateStr) {
                        $sub->whereNull('payment_date')
                            ->whereDoesntHave('payments')
                            ->where(function ($dateSub) use ($startDateStr, $endDateStr) {
                                $dateSub->whereBetween('converted_at', [$startDateStr, $endDateStr])
                                        ->orWhere(function ($cSub) use ($startDateStr, $endDateStr) {
                                            $cSub->whereNull('converted_at')
                                                 ->whereBetween('created_at', [$startDateStr, $endDateStr]);
                                        });
                            });
                    });
                });

            // Apply Strict Server-Side Role-Based Scope & Security Constraints
            $this->applyRoleBasedDataVisibility($query, $user, $request);

            $convertedProducts = $query->orderByDesc('id')->get();

            $items = [];
            foreach ($convertedProducts as $idx => $lp) {
                $lead = $lp->lead;

                // Determine payment received date & collection amount in selected range
                $paymentDate = null;
                $receivedAmount = 0.0;

                if ($lp->payments && $lp->payments->count() > 0) {
                    $paymentDate = $lp->payments->first()->payment_date;
                    $receivedAmount = (float) $lp->payments->sum('amount');
                } elseif ($lp->payment_date) {
                    $paymentDate = $lp->payment_date;
                    $receivedAmount = (float) ($lp->amount_paid ?: 0);
                } else {
                    $paymentDate = $lp->converted_at ?: $lp->created_at;
                    $receivedAmount = (float) ($lp->amount_paid ?: 0);
                }

                $carbonDate = $paymentDate ? Carbon::parse($paymentDate) : Carbon::parse($startDateStr);

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
                $category = $lp->day_sales_category ?: ($lp->daySalesCategory?->name ?: '');

                // 8. Account name (Company Name - Customer name)
                $compName = trim((string)($lead?->company_name ?? ''));
                $contactName = trim((string)($lead?->contact_name ?? ''));
                if ($compName && $contactName && $compName !== $contactName) {
                    $accountName = "{$compName} - {$contactName}";
                } else {
                    $accountName = $compName ?: ($contactName ?: 'N/A');
                }

                // 9. Collection & pending balance
                $productPrice = (float) ($lp->total_price ?: ($lp->deal_price ?: 0));
                $totalPaidAllTime = (float) ($lp->total_paid ?? ($receivedAmount > 0 ? $receivedAmount : ($lp->amount_paid ?: 0)));
                $balancePending = max(0, $productPrice - $totalPaidAllTime);

                $productName = $lp->product_name ?: 'Product';
                $saleType = $lp->sale_type ?: 'NEW SALE';
                $deptName = $assignedUser?->employeeOnboarding?->department?->name
                    ?: ($assignedUser?->roles?->first()?->department?->name ?: 'Sales');

                $items[] = [
                    's_no'                     => $idx + 1,
                    'id'                       => $lp->id,
                    'lead_product_id'          => $lp->id,
                    'lead_id'                  => $lp->lead_id,
                    'mon'                      => $mon,
                    'date'                     => $dateFormatted,
                    'branch'                   => $branch,
                    'branch_id'                => $lead?->branch_id,
                    'branch_type'              => $branchType,
                    'team_leader'              => $tlName ?: '—',
                    'team_member'              => $teamMemberDisplay,
                    'team_member_id'           => $assignedUser?->id,
                    'client_name'              => $accountName,
                    'account_name'             => $accountName,
                    'department'               => $deptName,
                    'product'                  => $productName,
                    'product_name'             => $productName,
                    'product_price'            => $productPrice,
                    'total_price'              => $productPrice,
                    'month_collected'          => $receivedAmount,
                    'current_month_collection' => $receivedAmount,
                    'balance_pending'          => $balancePending,
                    'category'                 => $category,
                    'sale_type'                => $saleType,
                    'lead_url'                 => url("/leads/{$lp->lead_id}"),
                ];
            }

            $companyId = $user?->company_id;
            $categoriesRecords = DaySalesTrackerCategory::query()
                ->when($companyId, fn($q) => $q->where(fn($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name']);

            $categories = $categoriesRecords->pluck('name')->unique()->values()->all();
            $totalCount = count($items);
            $totalCollection = round(collect($items)->sum('current_month_collection'), 2);
            $totalValue = round(collect($items)->sum('total_price'), 2);

            // Options available to logged-in user for client-side filter pickers
            $visibleBranches = $this->visibility->visibleBranches($user)->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
            ])->values()->all();

            $visibleUsers = $this->visibility->visibleAssignableUsers($user)->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values()->all();

            $canEdit = $this->canUserEditDaySalesTracker($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $startDateStr,
                    'date_from' => $startDateStr,
                    'date_to' => $endDateStr,
                    'formatted_date' => $formattedDateLabel,
                    'total_count' => $totalCount,
                    'total_collection' => $totalCollection,
                    'total_value' => $totalValue,
                    'can_edit' => $canEdit,
                    'can_manage_categories' => $canEdit,
                    'items' => $items,
                    'categories' => $categories,
                    'categories_list' => $categoriesRecords,
                    'sale_types' => ['NST', 'CST'],
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
     * Enforce strict role-based data visibility & security checks on LeadProduct query matching Web DaySalesTracker.
     */
    protected function applyRoleBasedDataVisibility($query, User $user, Request $request): void
    {
        // 1. Enforce Web standard lead visibility
        $query->whereHas('lead', function ($lq) use ($user, $request) {
            if ($user) {
                $this->visibility->applyLeadVisibility($lq, $user);
            }

            // Optional client filter: branch_id
            if ($request->filled('branch_id')) {
                $lq->where('branch_id', $request->input('branch_id'));
            }

            // Optional client filter: user_id
            if ($request->filled('user_id')) {
                $lq->where('assigned_to', $request->input('user_id'));
            }
        });

        // 2. Apply additional category, sale_type, and search filters securely
        if ($request->filled('category')) {
            $reqCategory = $request->input('category');
            $query->where(function ($q) use ($reqCategory) {
                $q->where('day_sales_category', $reqCategory)
                  ->orWhereHas('daySalesCategory', fn($catQ) => $catQ->where('name', $reqCategory));
            });
        }

        if ($request->filled('sale_type')) {
            $query->where('sale_type', $request->input('sale_type'));
        }

        if ($request->filled('search')) {
            $searchTerm = '%' . trim($request->input('search')) . '%';
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
     * Check if user has permission to add, edit, or modify Day Sales Tracker records.
     * Strictly restricted to the Company Admin role only.
     * The CBO role and all other non-company-admin roles are strictly view-only.
     */
    protected function canUserEditDaySalesTracker(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roleKey = strtolower((string) ($user->role ?? ''));
        $roleKeys = collect($user->roleKeys()->all())->map(fn($k) => strtolower((string) $k));

        $isCompanyAdmin = $user->isCompanyAdminRole()
            || $user->isCompanyAdmin()
            || $user->isSuperAdmin()
            || $user->isSystemAdmin()
            || $roleKey === 'company_admin'
            || $roleKeys->contains('company_admin');

        // CBO and all other non-company-admin roles are strictly view-only
        if ($user->isCbo() && !$isCompanyAdmin) {
            return false;
        }

        return (bool) $isCompanyAdmin;
    }

    /**
     * Mobile API: Add Day Sales Category.
     */
    public function addCategory(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user() ?: auth()->user();
        if (!$this->canUserEditDaySalesTracker($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only Company Admin can add, edit, or modify Day Sales Tracker data.',
            ], 403);
        }

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
        /** @var User|null $user */
        $user = $request->user() ?: auth()->user();
        if (!$this->canUserEditDaySalesTracker($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only Company Admin can add, edit, or modify Day Sales Tracker data.',
            ], 403);
        }

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
        /** @var User|null $user */
        $user = $request->user() ?: auth()->user();
        if (!$this->canUserEditDaySalesTracker($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only Company Admin can add, edit, or modify Day Sales Tracker data.',
            ], 403);
        }

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
        /** @var User|null $user */
        $user = $request->user() ?: auth()->user();
        if (!$this->canUserEditDaySalesTracker($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only Company Admin can add, edit, or modify Day Sales Tracker data.',
            ], 403);
        }

        $validated = $request->validate([
            'lead_product_id' => ['required', 'integer', 'exists:lead_products,id'],
            'category' => ['nullable', 'string'],
        ]);

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
        /** @var User|null $user */
        $user = $request->user() ?: auth()->user();
        if (!$this->canUserEditDaySalesTracker($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only Company Admin can add, edit, or modify Day Sales Tracker data.',
            ], 403);
        }

        $validated = $request->validate([
            'lead_product_id' => ['required', 'integer', 'exists:lead_products,id'],
            'sale_type' => ['nullable', 'string', 'max:50'],
        ]);

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

    /**
     * Mobile API: Get Category-wise Day Sales Pivot Report for a selected month (defaults to current month).
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
     * Mobile API: Get Branch-wise Quarterly Collection Trend Analysis and YoY Growth.
     */
    public function trendAnalysis(Request $request): JsonResponse
    {
        try {
            $user = $request->user() ?: auth()->user();
            if ($user && !$user->isSuperAdmin() && !$user->isSystemAdmin() && !$user->isCompanyAdminRole() && !$user->isCbo()) {
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
