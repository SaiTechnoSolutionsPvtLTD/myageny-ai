<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductionInitiation;
use App\Models\CustomerCampaign;
use App\Models\SmmSheet;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerSuccessDashboardApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Return filter options (Support Agents, Branches, Products, Sources).
     * GET /mobile/customer-success/filters
     */
    public function filters(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $supportUserIds = $this->getSupportUserIds($currentUser);

            $users = User::whereIn('id', $supportUserIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            $branches = $this->visibility->visibleBranches($currentUser);

            $products = Product::query()
                ->where('is_this_renewal_product', true)
                ->whereNotNull('product_name')
                ->where('product_name', '!=', '');
            $this->visibility->applyProductVisibility($products, $currentUser);
            $products = $products->select('id', 'product_name')->orderBy('product_name')->get();

            $sources = $this->visibility->visibleLeadSources($currentUser);

            return response()->json([
                'success' => true,
                'data'    => compact('users', 'branches', 'products', 'sources'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load filter options.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Fast Initial Overview Dashboard Data.
     * GET /mobile/customer-success/data
     *
     * Returns summary metrics, cards, charts, and initial 5 preview items
     * for visible sections with has_more flags. Additional records are
     * loaded on-demand via GET /mobile/customer-success/section.
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $supportUserIds = $this->getSupportUserIds($currentUser);

            $filters = $request->only([
                'user_id', 'branch_id', 'product_id', 'source', 'from_date', 'to_date'
            ]);

            if (empty($filters['from_date']) && empty($filters['to_date'])) {
                $filters['from_date'] = now()->startOfMonth()->toDateString();
                $filters['to_date'] = now()->endOfMonth()->toDateString();
            }

            $isSupportTl = $currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole();
            $isSupportExec = $currentUser->hasCustomerSupportLikeRole() && !$currentUser->hasTlLikeRole();
            $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

            $applySupportScope = $this->buildSupportScopeClosure($filters, $currentUser, $isSupportTl, $isSupportExec, $isAdmin);

            if (empty($supportUserIds) && !$isAdmin) {
                return response()->json([
                    'success' => true,
                    'data'    => $this->emptyDashboardResponse(),
                ]);
            }

            $fromDate = !empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->startOfDay() : null;
            $toDate   = !empty($filters['to_date'])   ? Carbon::parse($filters['to_date'])->endOfDay()   : null;

            // 1. Renewals Data (CMR / CMR+1 / CMR-1)
            $renewals = $this->getRenewalsData($filters, $applySupportScope);

            // 2. Department & Product Wise Pending Payments
            $deptProductAll = $this->getDeptProductPendingData($filters, $applySupportScope);
            $deptProductPreview = array_slice($deptProductAll, 0, 5);

            // 3. Payment Collections & Trend
            $todayStart = Carbon::today()->startOfDay();
            $todayEnd   = Carbon::today()->endOfDay();
            $monthStart = Carbon::today()->startOfMonth()->startOfDay();
            $monthEnd   = Carbon::today()->endOfMonth()->endOfDay();

            $paymentQuery = LeadProductPayment::query()
                ->join('leads', 'leads.id', '=', 'lead_product_payments.lead_id')
                ->join('lead_products', 'lead_products.id', '=', 'lead_product_payments.lead_product_id')
                ->whereIn('lead_product_payments.recorded_by', $supportUserIds)
                ->where($applySupportScope)
                ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
                ->when(!empty($filters['product_id']), fn($q) => $q->where('lead_products.product_id', $filters['product_id']))
                ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']));

            $todayPayments = (clone $paymentQuery)->whereBetween('lead_product_payments.payment_date', [$todayStart, $todayEnd])->sum('lead_product_payments.amount');
            $monthPayments = (clone $paymentQuery)->whereBetween('lead_product_payments.payment_date', [$monthStart, $monthEnd])->sum('lead_product_payments.amount');

            $trendQuery = clone $paymentQuery;
            if ($fromDate) $trendQuery->whereDate('lead_product_payments.payment_date', '>=', $fromDate);
            if ($toDate)   $trendQuery->whereDate('lead_product_payments.payment_date', '<=', $toDate);
            $trendData = $trendQuery
                ->selectRaw("DATE_FORMAT(lead_product_payments.payment_date, '%Y-%m-%d') as day, SUM(lead_product_payments.amount) as daily_amount")
                ->groupBy('day')->orderBy('day')->get()
                ->map(fn($row) => ['date' => $row->day, 'amount' => (float) $row->daily_amount])->toArray();

            // 4. Upsell Leads (Top 5 preview)
            $upsellsData = $this->getUpsellsData($filters, $applySupportScope, $fromDate, $toDate, 1, 5);

            // 5. Optimized User-Wise Support Performance (Grouped aggregations)
            $userPerformance = $this->getUserPerformanceData($filters, $applySupportScope, $supportUserIds, $currentUser, $isSupportTl, $isSupportExec, $isAdmin, $fromDate, $toDate, $renewals['cmr']['items']);

            // 6. Delivery Planned Projects (Top 5 preview)
            $deliveryData = $this->getDeliveryProjectsData($filters, $applySupportScope, $fromDate, $toDate, 1, 5);

            // 7. Customer Campaigns (Expired, Renewed, Not Renewed)
            $campaignsData = $this->getCampaignsData($filters, $applySupportScope);

            // 8. Pending Welcome Call Updates (Top 5 preview)
            $pendingWcData = $this->getPendingWelcomeCallsData($filters, $currentUser, 1, 5);

            // 9. SMM Sheet Expiry Details (Top 5 preview per bucket)
            $smmSheetData = $this->getSmmSheetsData($filters, $currentUser);

            // 10. Development Ongoing Projects (Top 5 preview)
            $devOngoingData = $this->getDevOngoingData($filters, $applySupportScope, 1, 5);

            return response()->json([
                'success' => true,
                'data' => [
                    'cmr' => [
                        'count'    => $renewals['cmr']['count'],
                        'value'    => $renewals['cmr']['value'],
                        'items'    => array_slice($renewals['cmr']['items'], 0, 5),
                        'has_more' => $renewals['cmr']['count'] > 5,
                    ],
                    'cmr_plus' => [
                        'count'    => $renewals['cmr_plus']['count'],
                        'value'    => $renewals['cmr_plus']['value'],
                        'items'    => array_slice($renewals['cmr_plus']['items'], 0, 5),
                        'has_more' => $renewals['cmr_plus']['count'] > 5,
                    ],
                    'cmr_minus' => [
                        'count'    => $renewals['cmr_minus']['count'],
                        'value'    => $renewals['cmr_minus']['value'],
                        'items'    => array_slice($renewals['cmr_minus']['items'], 0, 5),
                        'has_more' => $renewals['cmr_minus']['count'] > 5,
                    ],
                    'campaigns' => [
                        'expired' => [
                            'count'    => $campaignsData['expired']['count'],
                            'value'    => $campaignsData['expired']['value'],
                            'items'    => array_slice($campaignsData['expired']['items'], 0, 5),
                            'has_more' => $campaignsData['expired']['count'] > 5,
                        ],
                        'cm_renewed' => [
                            'count'    => $campaignsData['cm_renewed']['count'],
                            'value'    => $campaignsData['cm_renewed']['value'],
                            'items'    => array_slice($campaignsData['cm_renewed']['items'], 0, 5),
                            'has_more' => $campaignsData['cm_renewed']['count'] > 5,
                        ],
                        'cm_not_renewed' => [
                            'count'    => $campaignsData['cm_not_renewed']['count'],
                            'value'    => $campaignsData['cm_not_renewed']['value'],
                            'items'    => array_slice($campaignsData['cm_not_renewed']['items'], 0, 5),
                            'has_more' => $campaignsData['cm_not_renewed']['count'] > 5,
                        ],
                    ],
                    'dept_product_pending' => $deptProductPreview,
                    'dept_product_has_more'=> count($deptProductAll) > 5,
                    'dept_product_total'   => count($deptProductAll),
                    'today_payments'       => round((float) $todayPayments, 2),
                    'month_payments'       => round((float) $monthPayments, 2),
                    'upsells'              => $upsellsData,
                    'daily_trend'          => $trendData,
                    'user_performance'     => $userPerformance,
                    'delivery_projects'    => $deliveryData['items'],
                    'delivery_has_more'    => $deliveryData['has_more'],
                    'delivery_total'       => $deliveryData['count'],
                    'delivery_title'       => $deliveryData['title'],
                    'delivery_badge'       => $deliveryData['badge'],
                    'pending_welcome_calls'=> [
                        'count'    => $pendingWcData['count'],
                        'items'    => $pendingWcData['items'],
                        'has_more' => $pendingWcData['has_more'],
                    ],
                    'smm_sheet' => [
                        'current_month' => [
                            'count'    => $smmSheetData['current_month']['count'],
                            'items'    => array_slice($smmSheetData['current_month']['items'], 0, 5),
                            'has_more' => $smmSheetData['current_month']['count'] > 5,
                        ],
                        'last_month' => [
                            'count'    => $smmSheetData['last_month']['count'],
                            'items'    => array_slice($smmSheetData['last_month']['items'], 0, 5),
                            'has_more' => $smmSheetData['last_month']['count'] > 5,
                        ],
                        'next_month' => [
                            'count'    => $smmSheetData['next_month']['count'],
                            'items'    => array_slice($smmSheetData['next_month']['items'], 0, 5),
                            'has_more' => $smmSheetData['next_month']['count'] > 5,
                        ],
                    ],
                    'development_ongoing' => [
                        'count'         => $devOngoingData['count'],
                        'value'         => $devOngoingData['value'],
                        'received'      => $devOngoingData['received'],
                        'pending'       => $devOngoingData['pending'],
                        'ontrack_count' => $devOngoingData['ontrack_count'],
                        'hold_count'    => $devOngoingData['hold_count'],
                        'items'         => $devOngoingData['items'],
                        'has_more'      => $devOngoingData['has_more'],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load customer success data.',
                'error'   => config('app.debug') ? $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() : null,
            ], 500);
        }
    }

    /**
     * Dedicated Section-Wise Pagination & Lazy Loading.
     * GET /mobile/customer-success/section
     */
    public function section(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            if (!$currentUser) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $section  = trim((string) $request->query('section', ''));
            $page     = max(1, (int) $request->query('page', 1));
            $perPage  = max(1, min(50, (int) $request->query('per_page', 5)));
            $search   = trim((string) $request->query('search', ''));
            $bucket   = trim((string) $request->query('bucket', ''));
            $category = trim((string) $request->query('category', ''));
            $executionStatus = trim((string) ($request->query('execution_status') ?? $request->query('status', '')));
            $paymentStatus   = trim((string) $request->query('payment_status', ''));

            $filters = $request->only([
                'user_id', 'branch_id', 'product_id', 'source', 'from_date', 'to_date'
            ]);

            if (empty($filters['from_date']) && empty($filters['to_date'])) {
                $filters['from_date'] = now()->startOfMonth()->toDateString();
                $filters['to_date'] = now()->endOfMonth()->toDateString();
            }

            $isSupportTl = $currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole();
            $isSupportExec = $currentUser->hasCustomerSupportLikeRole() && !$currentUser->hasTlLikeRole();
            $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

            $applySupportScope = $this->buildSupportScopeClosure($filters, $currentUser, $isSupportTl, $isSupportExec, $isAdmin);
            $fromDate = !empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->startOfDay() : null;
            $toDate   = !empty($filters['to_date'])   ? Carbon::parse($filters['to_date'])->endOfDay()   : null;

            switch ($section) {
                case 'renewals':
                    $renewals = $this->getRenewalsData($filters, $applySupportScope, $search);
                    $targetBucket = in_array($bucket, ['cmr', 'cmr_plus', 'cmr_minus', 'nmr', 'lmr'], true) ? $bucket : 'cmr';
                    if ($targetBucket === 'nmr') $targetBucket = 'cmr_plus';
                    if ($targetBucket === 'lmr') $targetBucket = 'cmr_minus';

                    $bucketData = $renewals[$targetBucket] ?? ['count' => 0, 'value' => 0, 'items' => []];
                    $allItems = $bucketData['items'];
                    $total = count($allItems);
                    $offset = ($page - 1) * $perPage;
                    $pageItems = array_slice($allItems, $offset, $perPage);

                    return response()->json([
                        'success' => true,
                        'section' => 'renewals',
                        'data'    => [
                            'items'        => $pageItems,
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $total,
                            'has_more'     => ($offset + count($pageItems)) < $total,
                            'bucket'       => $targetBucket,
                        ],
                    ]);

                case 'dept_product_pending':
                case 'dept_pending':
                    $allPending = $this->getDeptProductPendingData($filters, $applySupportScope, $search);
                    $total = count($allPending);
                    $offset = ($page - 1) * $perPage;
                    $pageItems = array_slice($allPending, $offset, $perPage);

                    return response()->json([
                        'success' => true,
                        'section' => 'dept_product_pending',
                        'data'    => [
                            'items'        => $pageItems,
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $total,
                            'has_more'     => ($offset + count($pageItems)) < $total,
                        ],
                    ]);

                case 'upsells':
                    $upsells = $this->getUpsellsData($filters, $applySupportScope, $fromDate, $toDate, $page, $perPage, $search);
                    return response()->json([
                        'success' => true,
                        'section' => 'upsells',
                        'data'    => [
                            'items'        => $upsells['items'],
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $upsells['count'],
                            'has_more'     => $upsells['has_more'],
                        ],
                    ]);

                case 'campaigns':
                    $campaigns = $this->getCampaignsData($filters, $applySupportScope, $search);
                    $targetCat = in_array($category, ['cm_not_renewed', 'cm_renewed', 'expired'], true) ? $category : 'cm_not_renewed';
                    $catData = $campaigns[$targetCat] ?? ['count' => 0, 'value' => 0, 'items' => []];
                    $allItems = $catData['items'];
                    $total = count($allItems);
                    $offset = ($page - 1) * $perPage;
                    $pageItems = array_slice($allItems, $offset, $perPage);

                    return response()->json([
                        'success' => true,
                        'section' => 'campaigns',
                        'data'    => [
                            'items'        => $pageItems,
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $total,
                            'has_more'     => ($offset + count($pageItems)) < $total,
                            'category'     => $targetCat,
                        ],
                    ]);

                case 'delivery_projects':
                    $delivery = $this->getDeliveryProjectsData($filters, $applySupportScope, $fromDate, $toDate, $page, $perPage, $search);
                    return response()->json([
                        'success' => true,
                        'section' => 'delivery_projects',
                        'data'    => [
                            'items'        => $delivery['items'],
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $delivery['count'],
                            'has_more'     => $delivery['has_more'],
                        ],
                    ]);

                case 'pending_welcome_calls':
                case 'welcome_calls':
                    $wc = $this->getPendingWelcomeCallsData($filters, $currentUser, $page, $perPage, $search);
                    return response()->json([
                        'success' => true,
                        'section' => 'pending_welcome_calls',
                        'data'    => [
                            'items'        => $wc['items'],
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $wc['count'],
                            'has_more'     => $wc['has_more'],
                        ],
                    ]);

                case 'smm_sheet':
                    $smm = $this->getSmmSheetsData($filters, $currentUser, $search);
                    $targetBucket = in_array($bucket, ['current', 'last', 'next', 'current_month', 'last_month', 'next_month'], true) ? $bucket : 'current_month';
                    if ($targetBucket === 'current') $targetBucket = 'current_month';
                    if ($targetBucket === 'last') $targetBucket = 'last_month';
                    if ($targetBucket === 'next') $targetBucket = 'next_month';

                    $bucketData = $smm[$targetBucket] ?? ['count' => 0, 'items' => []];
                    $allItems = $bucketData['items'];
                    $total = count($allItems);
                    $offset = ($page - 1) * $perPage;
                    $pageItems = array_slice($allItems, $offset, $perPage);

                    return response()->json([
                        'success' => true,
                        'section' => 'smm_sheet',
                        'data'    => [
                            'items'        => $pageItems,
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $total,
                            'has_more'     => ($offset + count($pageItems)) < $total,
                            'bucket'       => $targetBucket,
                        ],
                    ]);

                case 'development_ongoing':
                    $devOngoing = $this->getDevOngoingData($filters, $applySupportScope, $page, $perPage, $search, $executionStatus, $paymentStatus);
                    return response()->json([
                        'success' => true,
                        'section' => 'development_ongoing',
                        'data'    => [
                            'items'        => $devOngoing['items'],
                            'current_page' => $page,
                            'per_page'     => $perPage,
                            'total'        => $devOngoing['count'],
                            'value'        => $devOngoing['value'],
                            'received'     => $devOngoing['received'],
                            'pending'      => $devOngoing['pending'],
                            'has_more'     => $devOngoing['has_more'],
                        ],
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => "Unknown dashboard section: {$section}",
                    ], 400);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load section data.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal Query Helpers (Reusable & Scoped)
    // ─────────────────────────────────────────────────────────────────────────

    private function buildSupportScopeClosure(array $filters, User $currentUser, bool $isSupportTl, bool $isSupportExec, bool $isAdmin): \Closure
    {
        return function ($query) use ($filters, $currentUser, $isSupportTl, $isSupportExec, $isAdmin) {
            if (!empty($filters['user_id'])) {
                $targetUid = (int) $filters['user_id'];
                $query->where(function ($q) use ($targetUid) {
                    $q->where('leads.customer_support_executive_id', $targetUid)
                      ->orWhere('leads.customer_support_tl_id', $targetUid);
                });
            } else {
                if (($isSupportTl || $isSupportExec) && !$isAdmin) {
                    $subordinateIds = $this->visibility->customerSupportUserIds($currentUser);
                    $query->where(function ($q) use ($subordinateIds) {
                        $q->whereIn('leads.customer_support_executive_id', $subordinateIds)
                          ->orWhereIn('leads.customer_support_tl_id', $subordinateIds);
                    });
                } else {
                    $comp = $currentUser->company_id;
                    if ($comp) {
                        $query->where('leads.company_id', $comp);
                    }
                }
            }
        };
    }

    private function getRenewalsData(array $filters, \Closure $applySupportScope, string $search = ''): array
    {
        $initiations = ProductionInitiation::query()
            ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
            ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
            ->join('products', 'products.id', '=', 'production_initiations.product_id')
            ->select([
                'production_initiations.id',
                'production_initiations.lead_id',
                'production_initiations.project_delivery_date',
                'production_initiations.custom_form_data',
                'production_initiations.product_name',
                'lead_products.total_price',
                'lead_products.amount_paid',
                'lead_products.payment_status',
                'leads.company_name',
                'leads.contact_name',
                'leads.mobile_number',
                'leads.branch_id',
                'leads.customer_support_tl_id',
                'leads.customer_support_executive_id',
                'products.is_this_renewal_product',
            ])
            ->where(fn($q) => $q->where('products.count_wise_report', true)->orWhere('products.is_this_renewal_product', true))
            ->where($applySupportScope)
            ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
            ->when(!empty($filters['product_id']), fn($q) => $q->where('production_initiations.product_id', $filters['product_id']))
            ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('leads.company_name', 'like', "%{$search}%")
                       ->orWhere('leads.contact_name', 'like', "%{$search}%")
                       ->orWhere('production_initiations.product_name', 'like', "%{$search}%")
                       ->orWhere('leads.mobile_number', 'like', "%{$search}%");
                });
            })
            ->get();

        $today = Carbon::today();
        $cmStart = $today->copy()->startOfMonth();
        $cmEnd   = $today->copy()->endOfMonth();
        $nmStart = $today->copy()->addMonth()->startOfMonth();
        $nmEnd   = $today->copy()->addMonth()->endOfMonth();
        $lmStart = $today->copy()->subMonth()->startOfMonth();
        $lmEnd   = $today->copy()->subMonth()->endOfMonth();

        $cmrCount = 0; $cmrValue = 0; $cmrItems = [];
        $nmrCount = 0; $nmrValue = 0; $nmrItems = [];
        $lmrCount = 0; $lmrValue = 0; $lmrItems = [];

        foreach ($initiations as $pi) {
            $rDateStr = $this->getRenewalDate($pi);
            if (!$rDateStr) continue;

            $rDate = Carbon::parse($rDateStr);
            $price = (float) $pi->total_price;
            $paid  = (float) $pi->amount_paid;

            $item = [
                'id'             => $pi->id,
                'lead_id'        => $pi->lead_id,
                'company_name'   => $pi->company_name ?: ($pi->contact_name ?: 'N/A'),
                'contact_name'   => $pi->contact_name ?: '',
                'mobile_number'  => $pi->mobile_number ?: '',
                'product_name'   => $pi->product_name,
                'renewal_date'   => $rDateStr,
                'value'          => $price,
                'paid'           => $paid,
                'pending'        => max(0, $price - $paid),
                'payment_status' => $pi->payment_status ?: ($paid >= $price ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')),
                'assigned_to'    => $pi->customer_support_executive_id ?: $pi->customer_support_tl_id,
            ];

            if ($rDate->between($cmStart, $cmEnd)) {
                $cmrCount++; $cmrValue += $price; $cmrItems[] = $item;
            } elseif ($rDate->between($nmStart, $nmEnd)) {
                $nmrCount++; $nmrValue += $price; $nmrItems[] = $item;
            } elseif ($rDate->between($lmStart, $lmEnd)) {
                $lmrCount++; $lmrValue += $price; $lmrItems[] = $item;
            }
        }

        return [
            'cmr'      => ['count' => $cmrCount, 'value' => round($cmrValue, 2), 'items' => $cmrItems],
            'cmr_plus' => ['count' => $nmrCount, 'value' => round($nmrValue, 2), 'items' => $nmrItems],
            'cmr_minus'=> ['count' => $lmrCount, 'value' => round($lmrValue, 2), 'items' => $lmrItems],
        ];
    }

    private function getDeptProductPendingData(array $filters, \Closure $applySupportScope, string $search = ''): array
    {
        $pendingRows = LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->leftJoin('products', 'products.id', '=', 'lead_products.product_id')
            ->leftJoin('production_initiations', 'production_initiations.lead_product_id', '=', 'lead_products.id')
            ->leftJoin('departments as pi_dept', 'pi_dept.id', '=', 'production_initiations.department_id')
            ->leftJoin('department_product', 'department_product.product_id', '=', 'products.id')
            ->leftJoin('departments as prod_dept', 'prod_dept.id', '=', 'department_product.department_id')
            ->selectRaw("
                COALESCE(NULLIF(products.product_name, ''), NULLIF(lead_products.product_name, ''), 'General Product') as resolved_product,
                COALESCE(pi_dept.name, prod_dept.name) as raw_dept,
                SUM(GREATEST(0, lead_products.total_price - lead_products.amount_paid)) as total_pending,
                COUNT(DISTINCT lead_products.id) as total_count
            ")
            ->where($applySupportScope)
            ->where('lead_products.payment_status', '!=', 'paid')
            ->whereRaw('(lead_products.total_price - lead_products.amount_paid) > 0')
            ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
            ->when(!empty($filters['product_id']), fn($q) => $q->where(function($sub) use ($filters) {
                $sub->where('lead_products.product_id', $filters['product_id'])
                    ->orWhere('products.id', $filters['product_id']);
            }))
            ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
            ->groupBy('resolved_product', 'raw_dept')
            ->get();

        $deptProductData = [];
        foreach ($pendingRows as $row) {
            $productName = trim((string) $row->resolved_product) ?: 'General Product';
            $deptName = $row->raw_dept;

            if (empty($deptName) || strtolower($deptName) === 'unassigned') {
                $pnameLower = strtolower($productName);
                if (preg_match('/(website|software|app|web|crm|erp|portal|e-commerce|ecommerce|dynamic|static|laravel|react|wordpress|shopify|matrimony|booking|server|domain|hosting|developer|development|inventory)/i', $pnameLower)) {
                    $deptName = 'Development';
                } elseif (preg_match('/(design|logo|flyer|brochure|banner|graphic|ui|ux|card|business card)/i', $pnameLower)) {
                    $deptName = 'Designing';
                } elseif (preg_match('/(marketing|seo|lead generation|ad|ads|facebook|meta|instagram|google|smm|smo|youtube|sms|sender|reel|boosting|promotion|campaign|digital)/i', $pnameLower)) {
                    $deptName = 'Digital Marketing';
                } elseif (preg_match('/(support|amc|maintenance|service)/i', $pnameLower)) {
                    $deptName = 'Customer Support Team';
                } elseif (preg_match('/(sales|partner|consultation)/i', $pnameLower)) {
                    $deptName = 'Sales';
                } elseif (preg_match('/(account|hr|hrms)/i', $pnameLower)) {
                    $deptName = 'HR & Accounts';
                } elseif (preg_match('/(test|qa)/i', $pnameLower)) {
                    $deptName = 'Testing';
                } else {
                    $deptName = 'General';
                }
            }

            if (!empty($search)) {
                $sLower = strtolower($search);
                if (!str_contains(strtolower($deptName), $sLower) && !str_contains(strtolower($productName), $sLower)) {
                    continue;
                }
            }

            $key = $deptName . '_' . $productName;
            $deptProductData[$key] ??= [
                'department'     => $deptName,
                'product'        => $productName,
                'pending_amount' => 0.0,
                'count'          => 0
            ];
            $deptProductData[$key]['pending_amount'] += (float) $row->total_pending;
            $deptProductData[$key]['count'] += (int) $row->total_count;
        }

        usort($deptProductData, fn($a, $b) => $b['pending_amount'] <=> $a['pending_amount']);
        return array_values($deptProductData);
    }

    private function getUpsellsData(array $filters, \Closure $applySupportScope, ?Carbon $fromDate, ?Carbon $toDate, ?int $page = 1, ?int $perPage = 5, string $search = ''): array
    {
        $currentUser = auth()->user();
        $supportUserIds = $this->getSupportUserIds($currentUser);

        $upsellQuery = LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->join('products', 'products.id', '=', 'lead_products.product_id')
            ->where($applySupportScope)
            ->whereIn('lead_products.created_by', $supportUserIds)
            ->where('lead_products.product_status', '=', 'converted')
            ->where('products.count_wise_report', '!=', true)
            ->where('products.is_this_renewal_product', '!=', true)
            ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
            ->when(!empty($filters['product_id']), fn($q) => $q->where('lead_products.product_id', $filters['product_id']))
            ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('leads.company_name', 'like', "%{$search}%")
                       ->orWhere('leads.contact_name', 'like', "%{$search}%")
                       ->orWhere('products.product_name', 'like', "%{$search}%");
                });
            });

        if ($fromDate) $upsellQuery->whereDate('lead_products.created_at', '>=', $fromDate);
        if ($toDate)   $upsellQuery->whereDate('lead_products.created_at', '<=', $toDate);

        $upsellStats = (clone $upsellQuery)->selectRaw('COUNT(DISTINCT leads.id) as upsell_count, SUM(lead_products.total_price) as upsell_value')->first();
        $upsellCount = (int) ($upsellStats->upsell_count ?? 0);
        $upsellValue = (float) ($upsellStats->upsell_value ?? 0);

        $p = $page ?: 1;
        $pp = $perPage ?: 5;
        $offset = ($p - 1) * $pp;

        $items = (clone $upsellQuery)
            ->select(['leads.id', 'leads.company_name', 'leads.contact_name', 'products.product_name', 'lead_products.total_price', 'lead_products.created_at'])
            ->orderByDesc('lead_products.created_at')
            ->offset($offset)
            ->limit($pp)
            ->get()
            ->map(fn($row) => [
                'id'           => $row->id,
                'company_name' => $row->company_name ?: ($row->contact_name ?: 'N/A'),
                'product_name' => $row->product_name,
                'value'        => (float) $row->total_price,
                'created_at'   => $row->created_at ? Carbon::parse($row->created_at)->format('d M Y') : '—',
            ])->toArray();

        return [
            'count'    => $upsellCount,
            'value'    => round($upsellValue, 2),
            'items'    => $items,
            'has_more' => ($offset + count($items)) < $upsellCount,
        ];
    }

    private function getUserPerformanceData(array $filters, \Closure $applySupportScope, array $supportUserIds, User $currentUser, bool $isSupportTl, bool $isSupportExec, bool $isAdmin, ?Carbon $fromDate, ?Carbon $toDate, array $cmrItems): array
    {
        $displayUserQuery = User::whereIn('id', $supportUserIds);
        if (($isSupportTl || $isSupportExec) && !$isAdmin) {
            $subordinateIds = $this->visibility->customerSupportUserIds($currentUser);
            $assignedExecIds = Lead::whereIn('customer_support_tl_id', $subordinateIds)
                ->whereNotNull('customer_support_executive_id')
                ->pluck('customer_support_executive_id')->unique()->toArray();
            $displayUserQuery->whereIn('id', array_values(array_unique(array_merge($subordinateIds, $assignedExecIds))));
        }
        $supportUsers = $displayUserQuery->orderBy('name')->get();

        // Optimized: Single group query for handled counts
        $handledCounts = Lead::query()
            ->where(function ($q) {
                $q->whereNotNull('customer_support_executive_id')
                  ->orWhereNotNull('customer_support_tl_id');
            })
            ->when($fromDate, fn($q) => $q->whereDate('lead_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('lead_date', '<=', $toDate))
            ->selectRaw('COALESCE(customer_support_executive_id, customer_support_tl_id) as uid, COUNT(*) as cnt')
            ->groupBy('uid')
            ->pluck('cnt', 'uid')
            ->toArray();

        // Optimized: Single group query for converted counts
        $convertedCounts = Lead::query()
            ->where(function ($q) {
                $q->whereNotNull('customer_support_executive_id')
                  ->orWhereNotNull('customer_support_tl_id');
            })
            ->where('lead_status_id', 5)
            ->when($fromDate, fn($q) => $q->whereDate('lead_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('lead_date', '<=', $toDate))
            ->selectRaw('COALESCE(customer_support_executive_id, customer_support_tl_id) as uid, COUNT(*) as cnt')
            ->groupBy('uid')
            ->pluck('cnt', 'uid')
            ->toArray();

        // Optimized: Single group query for collected sums
        $collectedSums = LeadProductPayment::query()
            ->whereIn('recorded_by', $supportUserIds)
            ->when($fromDate, fn($q) => $q->whereDate('payment_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('payment_date', '<=', $toDate))
            ->selectRaw('recorded_by as uid, SUM(amount) as total')
            ->groupBy('recorded_by')
            ->pluck('total', 'uid')
            ->toArray();

        $userStats = [];
        foreach ($supportUsers as $user) {
            $uid = $user->id;
            $handledCount   = (int) ($handledCounts[$uid] ?? 0);
            $convertedCount = (int) ($convertedCounts[$uid] ?? 0);
            $uCollected     = (float) ($collectedSums[$uid] ?? 0.0);

            $userCmrCount = 0; $userCmrValue = 0;
            foreach ($cmrItems as $item) {
                if (($item['assigned_to'] ?? null) == $uid) {
                    $userCmrCount++;
                    $userCmrValue += (float) ($item['value'] ?? 0);
                }
            }

            $userStats[] = [
                'id'              => $uid,
                'name'            => $user->name,
                'handled_leads'   => $handledCount,
                'converted_leads' => $convertedCount,
                'total_collected' => (float) $uCollected,
                'cmr_count'       => $userCmrCount,
                'cmr_value'       => (float) $userCmrValue,
            ];
        }

        return $userStats;
    }

    private function getDeliveryProjectsData(array $filters, \Closure $applySupportScope, ?Carbon $fromDate, ?Carbon $toDate, ?int $page = 1, ?int $perPage = 5, string $search = ''): array
    {
        $dpQuery = ProductionInitiation::query()
            ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
            ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
            ->leftJoin('departments', 'departments.id', '=', 'production_initiations.department_id')
            ->select([
                'production_initiations.id', 'production_initiations.product_name',
                'production_initiations.project_delivery_date', 'production_initiations.project_execution_status',
                'production_initiations.project_allocated_employee_user_ids', 'production_initiations.project_allocated_tl_user_ids',
                'lead_products.total_price', 'lead_products.amount_paid',
                'leads.company_name', 'leads.contact_name', 'departments.name as department_name',
            ])
            ->where($applySupportScope)
            ->whereRaw('LOWER(departments.name) LIKE ?', ['%development%'])
            ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
            ->when(!empty($filters['product_id']), fn($q) => $q->where('production_initiations.product_id', $filters['product_id']))
            ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('leads.company_name', 'like', "%{$search}%")
                       ->orWhere('leads.contact_name', 'like', "%{$search}%")
                       ->orWhere('production_initiations.product_name', 'like', "%{$search}%");
                });
            });

        if ($fromDate && $toDate) {
            $dpQuery->whereBetween('production_initiations.project_delivery_date', [$fromDate->toDateString(), $toDate->toDateString()]);
        }

        $totalCount = (clone $dpQuery)->count();
        $p = $page ?: 1;
        $pp = $perPage ?: 5;
        $offset = ($p - 1) * $pp;

        $deliveryProjects = (clone $dpQuery)
            ->orderBy('production_initiations.project_delivery_date')
            ->offset($offset)
            ->limit($pp)
            ->get();

        $allUserIds = [];
        foreach ($deliveryProjects as $dp) {
            $employeeIds = is_array($dp->project_allocated_employee_user_ids) ? $dp->project_allocated_employee_user_ids : json_decode($dp->project_allocated_employee_user_ids ?? '[]', true) ?? [];
            $tlIds = is_array($dp->project_allocated_tl_user_ids) ? $dp->project_allocated_tl_user_ids : json_decode($dp->project_allocated_tl_user_ids ?? '[]', true) ?? [];
            foreach ($employeeIds as $eid) { if ($eid) $allUserIds[] = (int) $eid; }
            foreach ($tlIds as $tid) { if ($tid) $allUserIds[] = (int) $tid; }
        }
        $allUserIds = array_unique($allUserIds);
        $userMap = !empty($allUserIds)
            ? User::whereIn('id', $allUserIds)->with('employeeOnboarding.department')->get()->keyBy('id')
            : collect();

        $deliveryProjectsData = [];
        foreach ($deliveryProjects as $dp) {
            $employeeIds = is_array($dp->project_allocated_employee_user_ids) ? $dp->project_allocated_employee_user_ids : json_decode($dp->project_allocated_employee_user_ids ?? '[]', true) ?? [];
            $tlIds = is_array($dp->project_allocated_tl_user_ids) ? $dp->project_allocated_tl_user_ids : json_decode($dp->project_allocated_tl_user_ids ?? '[]', true) ?? [];

            $allocatedNames = []; $allocatedDept = '';
            if (!empty($employeeIds)) {
                $employees = collect($employeeIds)->map(fn($id) => $userMap->get($id))->filter();
                $allocatedNames = $employees->pluck('name')->toArray();
                $allocatedDept = implode(', ', $employees->map(fn($e) => $e->employeeOnboarding?->department?->name)->filter()->unique()->toArray());
            } elseif (!empty($tlIds)) {
                $tls = collect($tlIds)->map(fn($id) => $userMap->get($id))->filter();
                $allocatedNames = $tls->pluck('name')->toArray();
                $allocatedDept = implode(', ', $tls->map(fn($t) => $t->employeeOnboarding?->department?->name)->filter()->unique()->toArray());
            }

            $allocatedPersonLabel = !empty($allocatedNames) ? implode(', ', $allocatedNames) : 'Not allocated';
            $price = (float) $dp->total_price; $paid = (float) $dp->amount_paid; $pending = max(0, $price - $paid);

            $deliveryProjectsData[] = [
                'id'                   => $dp->id,
                'product_name'         => $dp->product_name,
                'company_name'         => $dp->company_name ?: ($dp->contact_name ?: 'N/A'),
                'delivery_date'        => $dp->project_delivery_date ? Carbon::parse($dp->project_delivery_date)->format('d M Y') : '—',
                'allocated_person'     => $allocatedPersonLabel,
                'allocated_department' => $allocatedDept ?: ($dp->department_name ?: '—'),
                'status'               => strtoupper((string) ($dp->project_execution_status ?: 'onboard')),
                'total_value'          => $price, 'received_amount' => $paid, 'pending_amount' => $pending,
            ];
        }

        $deliverySectionTitle = 'Delivery Planned Projects';
        $deliverySectionBadge = 'Planned';
        if ($fromDate && $toDate) {
            if ($fromDate->format('Y-m') === $toDate->format('Y-m')) {
                $monthName = $fromDate->format('F Y');
                $deliverySectionTitle = "{$monthName} Delivery Planned Projects";
                $deliverySectionBadge = "Planned in {$fromDate->format('M Y')}";
            } else {
                $deliverySectionTitle = "Delivery Planned Projects ({$fromDate->format('d M Y')} - {$toDate->format('d M Y')})";
                $deliverySectionBadge = "Planned in Range";
            }
        }

        return [
            'count'    => $totalCount,
            'items'    => $deliveryProjectsData,
            'has_more' => ($offset + count($deliveryProjectsData)) < $totalCount,
            'title'    => $deliverySectionTitle,
            'badge'    => $deliverySectionBadge,
        ];
    }

    private function getCampaignsData(array $filters, \Closure $applySupportScope, string $search = ''): array
    {
        $campaignsQuery = CustomerCampaign::query()
            ->join('leads', 'leads.id', '=', 'customer_campaigns.lead_id')
            ->select([
                'customer_campaigns.id',
                'customer_campaigns.lead_id',
                'customer_campaigns.campaign_name',
                'customer_campaigns.platform',
                'customer_campaigns.ad_account_name',
                'customer_campaigns.status',
                'customer_campaigns.budget_amount',
                'customer_campaigns.budget_type',
                'customer_campaigns.start_date',
                'customer_campaigns.end_date',
                'customer_campaigns.extended_from_id',
                'customer_campaigns.created_at',
                'leads.company_name',
                'leads.contact_name',
                'leads.mobile_number',
                'leads.customer_support_tl_id',
                'leads.customer_support_executive_id',
            ])
            ->where($applySupportScope)
            ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
            ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('leads.company_name', 'like', "%{$search}%")
                       ->orWhere('leads.contact_name', 'like', "%{$search}%")
                       ->orWhere('customer_campaigns.campaign_name', 'like', "%{$search}%")
                       ->orWhere('leads.mobile_number', 'like', "%{$search}%");
                });
            })
            ->with('extensions:id,extended_from_id')
            ->get();

        $extendedParentIds = $campaignsQuery->pluck('extended_from_id')->filter()->unique()->toArray();

        $expiredCampaignItems = [];
        $cmRenewedCampaignItems = [];
        $cmNotRenewedCampaignItems = [];
        $expiredCampaignValue = 0;
        $cmRenewedCampaignValue = 0;
        $cmNotRenewedCampaignValue = 0;

        $nowDate = Carbon::today()->toDateString();
        $cmStartStr = Carbon::today()->startOfMonth()->toDateString();
        $cmEndStr   = Carbon::today()->endOfMonth()->toDateString();

        foreach ($campaignsQuery as $c) {
            $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
            $startDate = $c->start_date ? Carbon::parse($c->start_date)->toDateString() : null;
            $createdAt = Carbon::parse($c->created_at)->toDateString();

            $isExpired = in_array($c->status, ['expired', 'completed'], true) || ($endDate && $endDate < $nowDate);
            $isExtended = in_array($c->id, $extendedParentIds, true) || $c->extensions->isNotEmpty();
            $isAnExtension = !empty($c->extended_from_id);
            $budget = (float) ($c->budget_amount ?? 0);

            $item = [
                'id'              => $c->id,
                'lead_id'         => $c->lead_id,
                'campaign_name'   => $c->campaign_name,
                'company_name'    => $c->company_name ?: ($c->contact_name ?: 'N/A'),
                'mobile_number'   => $c->mobile_number ?: '—',
                'platform'        => $c->platform ?: 'Digital Marketing',
                'ad_account_name' => $c->ad_account_name ?: '—',
                'budget'          => $budget,
                'budget_type'     => $c->budget_type ?: 'Monthly',
                'start_date'      => $startDate ? Carbon::parse($startDate)->format('d M Y') : '—',
                'end_date'        => $endDate ? Carbon::parse($endDate)->format('d M Y') : '—',
                'status'          => strtoupper((string) ($c->status ?: 'active')),
                'is_renewed'      => $isExtended,
            ];

            if ($isExpired) {
                $expiredCampaignItems[] = $item;
                $expiredCampaignValue += $budget;
            }

            if (($isExtended && $endDate && $endDate >= $cmStartStr && $endDate <= $cmEndStr) ||
                ($isAnExtension && (($createdAt >= $cmStartStr && $createdAt <= $cmEndStr) || ($startDate && $startDate >= $cmStartStr && $startDate <= $cmEndStr)))) {
                $cmRenewedCampaignItems[] = $item;
                $cmRenewedCampaignValue += $budget;
            }

            if ($endDate && $endDate >= $cmStartStr && $endDate <= $cmEndStr && !$isExtended) {
                $cmNotRenewedCampaignItems[] = $item;
                $cmNotRenewedCampaignValue += $budget;
            }
        }

        return [
            'expired' => [
                'count' => count($expiredCampaignItems),
                'value' => round($expiredCampaignValue, 2),
                'items' => $expiredCampaignItems,
            ],
            'cm_renewed' => [
                'count' => count($cmRenewedCampaignItems),
                'value' => round($cmRenewedCampaignValue, 2),
                'items' => $cmRenewedCampaignItems,
            ],
            'cm_not_renewed' => [
                'count' => count($cmNotRenewedCampaignItems),
                'value' => round($cmNotRenewedCampaignValue, 2),
                'items' => $cmNotRenewedCampaignItems,
            ],
        ];
    }

    private function getPendingWelcomeCallsData(array $filters, User $currentUser, ?int $page = 1, ?int $perPage = 5, string $search = ''): array
    {
        $pendingWcProjects = ProductionInitiation::query()
            ->with([
                'lead:id,company_name,contact_name,mobile_number,company_id,branch_id,lead_source',
                'leadProduct:id,total_price,amount_paid',
                'department:id,name',
                'product:id,product_name',
            ])
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->whereDoesntHave('projectUpdates', function ($q) {
                $q->where('type', 'welcome_call_update');
            })
            ->when($currentUser->company_id, function ($q) use ($currentUser) {
                $q->where(function ($sq) use ($currentUser) {
                    $sq->where('production_initiations.company_id', $currentUser->company_id)
                       ->orWhereHas('lead', fn($lq) => $lq->where('company_id', $currentUser->company_id));
                });
            })
            ->when(!empty($filters['branch_id']), function ($q) use ($filters) {
                $q->whereHas('lead', fn($lq) => $lq->where('branch_id', $filters['branch_id']));
            })
            ->when(!empty($filters['product_id']), fn($q) => $q->where('production_initiations.product_id', $filters['product_id']))
            ->when(!empty($filters['source']), function ($q) use ($filters) {
                $q->whereHas('lead', fn($lq) => $lq->where('lead_source', $filters['source']));
            })
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('production_initiations.company_name', 'like', "%{$search}%")
                       ->orWhere('production_initiations.product_name', 'like', "%{$search}%")
                       ->orWhereHas('lead', function ($lq) use ($search) {
                           $lq->where('company_name', 'like', "%{$search}%")
                              ->orWhere('contact_name', 'like', "%{$search}%")
                              ->orWhere('mobile_number', 'like', "%{$search}%");
                       });
                });
            });

        $totalCount = (clone $pendingWcProjects)->count();
        $p = $page ?: 1;
        $pp = $perPage ?: 5;
        $offset = ($p - 1) * $pp;

        $results = (clone $pendingWcProjects)
            ->latest('production_initiations.id')
            ->offset($offset)
            ->limit($pp)
            ->get();

        $items = [];
        foreach ($results as $pwp) {
            $compName = $pwp->company_name ?: ($pwp->lead?->company_name ?: ($pwp->client_name ?: ($pwp->lead?->client_name ?: ($pwp->lead?->contact_name ?: 'N/A'))));
            $price = (float) ($pwp->leadProduct?->total_price ?? 0);
            $paid  = (float) ($pwp->leadProduct?->amount_paid ?? 0);
            $approvedAt = $pwp->production_approval_reviewed_at
                ? Carbon::parse($pwp->production_approval_reviewed_at)->format('d M Y')
                : ($pwp->created_at ? $pwp->created_at->format('d M Y') : '—');

            $items[] = [
                'id'              => $pwp->id,
                'lead_id'         => $pwp->lead_id,
                'company_name'    => $compName,
                'mobile_number'   => $pwp->lead?->mobile_number ?: '—',
                'product_name'    => $pwp->product_name ?: ($pwp->product?->product_name ?: '—'),
                'department_name' => $pwp->department?->name ?: 'Development',
                'approved_date'   => $approvedAt,
                'total_value'     => $price,
                'received_amount' => $paid,
                'pending_amount'  => max(0, $price - $paid),
                'action_url'      => url('/projects-details/' . $pwp->id),
            ];
        }

        return [
            'count'    => $totalCount,
            'items'    => $items,
            'has_more' => ($offset + count($items)) < $totalCount,
        ];
    }

    private function getSmmSheetsData(array $filters, User $currentUser, string $search = ''): array
    {
        $today = Carbon::today();
        $cmStart = $today->copy()->startOfMonth();
        $cmEnd   = $today->copy()->endOfMonth();
        $nmStart = $today->copy()->addMonth()->startOfMonth();
        $nmEnd   = $today->copy()->addMonth()->endOfMonth();
        $lmStart = $today->copy()->subMonth()->startOfMonth();
        $lmEnd   = $today->copy()->subMonth()->endOfMonth();

        $smmSheets = SmmSheet::query()
            ->with([
                'lead:id,company_name,contact_name,mobile_number,company_id,branch_id,lead_source,customer_support_executive_id,customer_support_tl_id',
                'leadProduct:id,total_price,amount_paid',
                'product:id,product_name,package_name',
                'department:id,name',
            ])
            ->whereNull('deleted_at')
            ->whereNotNull('end_date')
            ->when($currentUser->company_id, function ($q) use ($currentUser) {
                $q->where(function ($sq) use ($currentUser) {
                    $sq->where('smm_sheets.company_id', $currentUser->company_id)
                       ->orWhereHas('lead', fn($lq) => $lq->where('company_id', $currentUser->company_id));
                });
            })
            ->when(!empty($filters['branch_id']), function ($q) use ($filters) {
                $q->whereHas('lead', fn($lq) => $lq->where('branch_id', $filters['branch_id']));
            })
            ->when(!empty($filters['product_id']), fn($q) => $q->where('smm_sheets.product_id', $filters['product_id']))
            ->when(!empty($filters['source']), function ($q) use ($filters) {
                $q->whereHas('lead', fn($lq) => $lq->where('lead_source', $filters['source']));
            })
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->whereHas('lead', function ($lq) use ($search) {
                        $lq->where('company_name', 'like', "%{$search}%")
                           ->orWhere('contact_name', 'like', "%{$search}%")
                           ->orWhere('mobile_number', 'like', "%{$search}%");
                    })->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('product_name', 'like', "%{$search}%")
                           ->orWhere('package_name', 'like', "%{$search}%");
                    });
                });
            })
            ->orderBy('end_date', 'asc')
            ->get();

        $smmCurrentMonthItems = [];
        $smmLastMonthItems = [];
        $smmNextMonthItems = [];

        $cmStartStr = $cmStart->toDateString();
        $cmEndStr   = $cmEnd->toDateString();
        $lmStartStr = $lmStart->toDateString();
        $lmEndStr   = $lmEnd->toDateString();
        $nmStartStr = $nmStart->toDateString();
        $nmEndStr   = $nmEnd->toDateString();

        foreach ($smmSheets as $s) {
            $endDate = $s->end_date ? Carbon::parse($s->end_date)->toDateString() : null;
            if (!$endDate) continue;

            $committedPosters = (int) $s->committed_posters;
            $committedVideos  = (int) $s->committed_videos;
            $completedPosters = (int) $s->design_completed_posters + (int) $s->dm_completed_posters;
            $completedVideos  = (int) $s->design_completed_videos + (int) $s->dm_completed_videos;
            $totalCommitted   = $committedPosters + $committedVideos;
            $totalCompleted   = $completedPosters + $completedVideos;

            $computedStatus = 'pending';
            if ($totalCommitted > 0 && $totalCompleted >= $totalCommitted) {
                $computedStatus = 'completed';
            } elseif ($endDate < $today->toDateString()) {
                $computedStatus = 'overdue';
            }

            $price = (float) ($s->leadProduct?->total_price ?? 0);
            $paid  = (float) ($s->leadProduct?->amount_paid ?? 0);

            $item = [
                'id'                => $s->id,
                'pi_id'             => $s->production_initiation_id,
                'lead_id'           => $s->lead_id,
                'company_name'      => $s->lead?->company_name ?: ($s->lead?->contact_name ?: 'N/A'),
                'mobile_number'     => $s->lead?->mobile_number ?: '—',
                'product_name'      => $s->product?->package_name ?: ($s->product?->product_name ?: 'SMM Package'),
                'department_name'   => $s->department?->name ?: 'Digital Marketing',
                'start_date'        => $s->start_date ? Carbon::parse($s->start_date)->format('d M Y') : '—',
                'end_date'          => Carbon::parse($endDate)->format('d M Y'),
                'end_date_raw'      => $endDate,
                'committed_posters' => $committedPosters,
                'completed_posters' => $completedPosters,
                'committed_videos'  => $committedVideos,
                'completed_videos'  => $completedVideos,
                'status'            => strtoupper($s->status ?: $computedStatus),
                'total_value'       => $price,
                'received_amount'   => $paid,
                'pending_amount'    => max(0, $price - $paid),
                'action_url'        => $s->production_initiation_id ? url('/projects-details/' . $s->production_initiation_id) : url('/projects/smm-sheet'),
            ];

            if ($endDate >= $cmStartStr && $endDate <= $cmEndStr) {
                $smmCurrentMonthItems[] = $item;
            } elseif ($endDate >= $lmStartStr && $endDate <= $lmEndStr) {
                $smmLastMonthItems[] = $item;
            } elseif ($endDate >= $nmStartStr && $endDate <= $nmEndStr) {
                $smmNextMonthItems[] = $item;
            }
        }

        return [
            'current_month' => [
                'count' => count($smmCurrentMonthItems),
                'items' => $smmCurrentMonthItems,
            ],
            'last_month' => [
                'count' => count($smmLastMonthItems),
                'items' => $smmLastMonthItems,
            ],
            'next_month' => [
                'count' => count($smmNextMonthItems),
                'items' => $smmNextMonthItems,
            ],
        ];
    }

    private function getDevOngoingData(
        array $filters,
        \Closure $applySupportScope,
        ?int $page = 1,
        ?int $perPage = 5,
        string $search = '',
        string $executionStatus = '',
        string $paymentStatus = ''
    ): array
    {
        $devOngoingProjects = ProductionInitiation::query()
            ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
            ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
            ->leftJoin('departments', 'departments.id', '=', 'production_initiations.department_id')
            ->leftJoin('branches', 'branches.id', '=', 'leads.branch_id')
            ->select([
                'production_initiations.id',
                'production_initiations.lead_id',
                'production_initiations.product_name',
                'production_initiations.project_delivery_date',
                'production_initiations.project_execution_status',
                'production_initiations.project_allocated_employee_user_ids',
                'production_initiations.project_allocated_tl_user_ids',
                'production_initiations.client_name',
                'production_initiations.company_name as pi_company_name',
                'lead_products.total_price',
                'lead_products.amount_paid',
                'lead_products.payment_status',
                'leads.company_name',
                'leads.contact_name',
                'leads.mobile_number',
                'leads.branch_id',
                'branches.name as branch_name',
                'departments.name as department_name'
            ])
            ->where($applySupportScope)
            ->whereRaw('LOWER(departments.name) LIKE ?', ['%development%'])
            ->where(function ($q) {
                $q->whereNotIn('production_initiations.project_execution_status', ['delivered', 'lost'])
                  ->orWhereNull('production_initiations.project_execution_status');
            })
            ->where(function ($q) {
                $q->whereNull('production_initiations.production_approval_status')
                  ->orWhere('production_initiations.production_approval_status', '!=', 'rejected');
            })
            ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
            ->when(!empty($filters['product_id']), fn($q) => $q->where('production_initiations.product_id', $filters['product_id']))
            ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
            ->when(!empty($executionStatus) && $executionStatus !== 'all', function ($q) use ($executionStatus) {
                $status = strtolower($executionStatus);
                if (str_contains($status, 'progress')) {
                    $q->whereRaw('LOWER(production_initiations.project_execution_status) LIKE ?', ['%progress%']);
                } elseif (str_contains($status, 'waiting') || str_contains($status, 'content')) {
                    $q->where(function ($sq) {
                        $sq->whereRaw('LOWER(production_initiations.project_execution_status) LIKE ?', ['%waiting%'])
                           ->orWhereRaw('LOWER(production_initiations.project_execution_status) LIKE ?', ['%content%']);
                    });
                } else {
                    $q->whereRaw('LOWER(production_initiations.project_execution_status) = ?', [$status]);
                }
            })
            ->when(!empty($paymentStatus) && $paymentStatus !== 'all', function ($q) use ($paymentStatus) {
                $pay = strtolower($paymentStatus);
                if ($pay === 'paid') {
                    $q->where(function ($sq) {
                        $sq->where('lead_products.amount_paid', '>=', DB::raw('lead_products.total_price'))
                           ->orWhere('lead_products.payment_status', 'paid');
                    });
                } elseif ($pay === 'unpaid') {
                    $q->where(function ($sq) {
                        $sq->where('lead_products.amount_paid', '<=', 0)
                           ->orWhere('lead_products.payment_status', 'unpaid');
                    });
                } elseif ($pay === 'partial') {
                    $q->where('lead_products.amount_paid', '>', 0)
                      ->where('lead_products.amount_paid', '<', DB::raw('lead_products.total_price'));
                }
            })
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('leads.company_name', 'like', "%{$search}%")
                       ->orWhere('leads.contact_name', 'like', "%{$search}%")
                       ->orWhere('production_initiations.product_name', 'like', "%{$search}%")
                       ->orWhere('production_initiations.company_name', 'like', "%{$search}%")
                       ->orWhere('production_initiations.client_name', 'like', "%{$search}%")
                       ->orWhere('branches.name', 'like', "%{$search}%")
                       ->orWhere('leads.mobile_number', 'like', "%{$search}%");
                });
            });

        $aggregates = (clone $devOngoingProjects)
            ->selectRaw('COUNT(*) as total_count, SUM(lead_products.total_price) as total_val, SUM(lead_products.amount_paid) as total_rec')
            ->first();
        $totalCount           = (int) ($aggregates?->total_count ?? 0);
        $overallTotalValue    = (float) ($aggregates?->total_val ?? 0);
        $overallTotalReceived = (float) ($aggregates?->total_rec ?? 0);
        $overallTotalPending  = max(0, $overallTotalValue - $overallTotalReceived);

        $p = $page ?: 1;
        $pp = $perPage ?: 5;
        $offset = ($p - 1) * $pp;

        $projects = (clone $devOngoingProjects)
            ->orderByDesc('production_initiations.id')
            ->offset($offset)
            ->limit($pp)
            ->get();

        $devUserIds = [];
        foreach ($projects as $dp) {
            $employeeIds = is_array($dp->project_allocated_employee_user_ids)
                ? $dp->project_allocated_employee_user_ids
                : json_decode($dp->project_allocated_employee_user_ids ?? '[]', true) ?? [];
            $tlIds = is_array($dp->project_allocated_tl_user_ids)
                ? $dp->project_allocated_tl_user_ids
                : json_decode($dp->project_allocated_tl_user_ids ?? '[]', true) ?? [];
            foreach ($employeeIds as $eid) { if ($eid) $devUserIds[] = (int) $eid; }
            foreach ($tlIds as $tid) { if ($tid) $devUserIds[] = (int) $tid; }
        }
        $devUserIds = array_unique($devUserIds);
        $devUserMap = !empty($devUserIds)
            ? User::whereIn('id', $devUserIds)->pluck('name', 'id')
            : collect();

        $devOngoingItems = [];
        $devOngoingOntrackCount = 0;
        $devOngoingHoldCount = 0;

        foreach ($projects as $dp) {
            $employeeIds = is_array($dp->project_allocated_employee_user_ids)
                ? $dp->project_allocated_employee_user_ids
                : json_decode($dp->project_allocated_employee_user_ids ?? '[]', true) ?? [];
            $tlIds = is_array($dp->project_allocated_tl_user_ids)
                ? $dp->project_allocated_tl_user_ids
                : json_decode($dp->project_allocated_tl_user_ids ?? '[]', true) ?? [];

            $allocatedNames = [];
            foreach ($employeeIds as $eid) {
                if (isset($devUserMap[$eid])) $allocatedNames[] = $devUserMap[$eid];
            }
            if (empty($allocatedNames)) {
                foreach ($tlIds as $tid) {
                    if (isset($devUserMap[$tid])) $allocatedNames[] = $devUserMap[$tid];
                }
            }
            $allocatedPersonLabel = !empty($allocatedNames) ? implode(', ', $allocatedNames) : 'Unassigned';

            $price   = (float) $dp->total_price;
            $paid    = (float) $dp->amount_paid;
            $pending = max(0, $price - $paid);

            $rawStatus = strtolower(trim((string) ($dp->project_execution_status ?: 'ontrack')));
            if ($rawStatus === 'ontrack') $devOngoingOntrackCount++;
            elseif ($rawStatus === 'hold') $devOngoingHoldCount++;

            $compName = $dp->company_name ?: ($dp->pi_company_name ?: ($dp->contact_name ?: ($dp->client_name ?: 'N/A')));
            $contactName = $dp->contact_name ?: ($dp->client_name ?: '');

            $devOngoingItems[] = [
                'id'                     => $dp->id,
                'lead_id'                => $dp->lead_id,
                'product_name'           => $dp->product_name ?: 'Development Project',
                'company_name'           => $compName,
                'contact_name'           => $contactName,
                'mobile_number'          => $dp->mobile_number ?: '—',
                'branch_name'            => $dp->branch_name ?: 'General',
                'department_name'        => $dp->department_name ?: 'Development',
                'project_delivery_date'  => $dp->project_delivery_date ? Carbon::parse($dp->project_delivery_date)->format('d M Y') : '—',
                'raw_delivery_date'      => $dp->project_delivery_date ? Carbon::parse($dp->project_delivery_date)->toDateString() : '',
                'allocated_person'       => $allocatedPersonLabel,
                'execution_status'       => $rawStatus,
                'execution_status_label' => ucfirst($rawStatus),
                'total_value'            => $price,
                'received_amount'        => $paid,
                'pending_amount'         => $pending,
                'payment_status'         => $dp->payment_status ?: ($paid >= $price ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')),
                'project_url'            => url('/projects-details/' . $dp->id),
                'lead_url'               => $dp->lead_id ? url('/leads/' . $dp->lead_id) : '#',
            ];
        }

        return [
            'count'         => $totalCount,
            'value'         => round($overallTotalValue, 2),
            'received'      => round($overallTotalReceived, 2),
            'pending'       => round($overallTotalPending, 2),
            'ontrack_count' => $devOngoingOntrackCount,
            'hold_count'    => $devOngoingHoldCount,
            'items'         => $devOngoingItems,
            'has_more'      => ($offset + count($devOngoingItems)) < $totalCount,
        ];
    }

    private function getSupportUserIds(User $user): array
    {
        $visibleUserIds = $this->visibility->visibleUserIds($user);

        $supportUserIds = User::where(function ($query) {
            $query->whereHas('roles.department', function ($q) {
                $q->where('name', 'like', '%customer support%')
                  ->orWhere('name', 'like', '%customer success%')
                  ->orWhere('id', 5);
            })->orWhereHas('employeeOnboarding', function ($q) {
                $q->where('department_id', 5);
            });
        })->pluck('id')->toArray();

        if ($visibleUserIds !== null) {
            return array_values(array_intersect($visibleUserIds, $supportUserIds));
        }

        return $supportUserIds;
    }

    private function getRenewalDate($pi): ?string
    {
        $formData = is_array($pi->custom_form_data) ? $pi->custom_form_data : json_decode($pi->custom_form_data ?? '[]', true) ?? [];

        foreach ($formData as $field) {
            if (!is_array($field)) {
                continue;
            }
            $fieldKey = $field['field_name'] ?? '';
            $fieldVal = $field['value'] ?? '';

            $key = strtolower(is_array($fieldKey) ? implode(' ', array_filter(array_map('strval', $fieldKey))) : trim((string) $fieldKey));
            if (is_array($fieldVal)) {
                $value = implode(', ', array_filter(array_map(fn($v) => is_array($v) ? json_encode($v) : (string)$v, $fieldVal)));
            } else {
                $value = trim((string) $fieldVal);
            }
            if (in_array($key, ['end_date', 'enddate', 'end date', 'smm_end_date', 'End Date', 'ovp_end_date']) && !empty($value)) {
                try {
                    return Carbon::parse($value)->toDateString();
                } catch (\Exception $e) {}
            }
        }

        return $pi->project_delivery_date ? Carbon::parse($pi->project_delivery_date)->toDateString() : null;
    }

    private function emptyDashboardResponse(): array
    {
        return [
            'cmr'                  => ['count' => 0, 'value' => 0, 'items' => [], 'has_more' => false],
            'cmr_plus'             => ['count' => 0, 'value' => 0, 'items' => [], 'has_more' => false],
            'cmr_minus'            => ['count' => 0, 'value' => 0, 'items' => [], 'has_more' => false],
            'campaigns'            => [
                'expired'        => ['count' => 0, 'value' => 0, 'items' => [], 'has_more' => false],
                'cm_renewed'     => ['count' => 0, 'value' => 0, 'items' => [], 'has_more' => false],
                'cm_not_renewed' => ['count' => 0, 'value' => 0, 'items' => [], 'has_more' => false],
            ],
            'dept_product_pending' => [],
            'dept_product_has_more'=> false,
            'dept_product_total'   => 0,
            'today_payments'       => 0,
            'month_payments'       => 0,
            'upsells'              => ['count' => 0, 'value' => 0, 'items' => [], 'has_more' => false],
            'daily_trend'          => [],
            'user_performance'     => [],
            'delivery_projects'    => [],
            'delivery_has_more'    => false,
            'delivery_total'       => 0,
            'delivery_title'       => 'Delivery Planned Projects',
            'delivery_badge'       => 'Planned',
            'pending_welcome_calls'=> ['count' => 0, 'items' => [], 'has_more' => false],
            'smm_sheet'            => [
                'current_month' => ['count' => 0, 'items' => [], 'has_more' => false],
                'last_month'    => ['count' => 0, 'items' => [], 'has_more' => false],
                'next_month'    => ['count' => 0, 'items' => [], 'has_more' => false],
            ],
            'development_ongoing'  => [
                'count'         => 0,
                'value'         => 0,
                'received'      => 0,
                'pending'       => 0,
                'ontrack_count' => 0,
                'hold_count'    => 0,
                'items'         => [],
                'has_more'      => false,
            ],
        ];
    }
}