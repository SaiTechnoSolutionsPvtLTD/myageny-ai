<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductionInitiation;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

            $products = Product::query();
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
     * Dashboard metrics feed. GET /mobile/customer-success/data
     * Identical business logic/scoping to the web endpoint — only the
     * response envelope ('success' instead of 'status') differs, to match
     * every other mobile controller's contract this session.
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

            $applySupportScope = function ($query) use ($filters, $currentUser, $isSupportTl, $isSupportExec, $isAdmin) {
                if (!empty($filters['user_id'])) {
                    $query->where('leads.customer_support_executive_id', (int) $filters['user_id']);
                } else {
                    if ($isSupportTl && !$isAdmin) {
                        $query->where('leads.customer_support_tl_id', $currentUser->id);
                    } elseif ($isSupportExec && !$isAdmin) {
                        $query->where('leads.customer_support_executive_id', $currentUser->id);
                    } else {
                        $query->whereNotNull('leads.customer_support_tl_id');
                    }
                }
            };

            if (empty($supportUserIds) && !$isAdmin) {
                return response()->json([
                    'success' => true,
                    'data'    => $this->emptyDashboardResponse(),
                ]);
            }

            // 1. Renewals (CMR / CMR+1 / CMR-1)
            $initiations = ProductionInitiation::query()
                ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
                ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
                ->join('products', 'products.id', '=', 'production_initiations.product_id')
                ->select([
                    'production_initiations.id',
                    'production_initiations.project_delivery_date',
                    'production_initiations.custom_form_data',
                    'production_initiations.product_name',
                    'lead_products.total_price',
                    'lead_products.amount_paid',
                    'leads.company_name',
                    'leads.contact_name',
                    'leads.customer_support_tl_id',
                    'leads.customer_support_executive_id',
                    'products.is_this_renewal_product',
                ])
                ->where(fn($q) => $q->where('products.count_wise_report', true)->orWhere('products.is_this_renewal_product', true))
                ->where($applySupportScope)
                ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
                ->when(!empty($filters['product_id']), fn($q) => $q->where('production_initiations.product_id', $filters['product_id']))
                ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
                ->get();

            $today = Carbon::today();
            $cmStart = $today->copy()->startOfMonth();
            $cmEnd = $today->copy()->endOfMonth();
            $nmStart = $today->copy()->addMonth()->startOfMonth();
            $nmEnd = $today->copy()->addMonth()->endOfMonth();
            $lmStart = $today->copy()->subMonth()->startOfMonth();
            $lmEnd = $today->copy()->subMonth()->endOfMonth();

            $cmrCount = 0; $cmrValue = 0; $cmrItems = [];
            $nmrCount = 0; $nmrValue = 0; $nmrItems = [];
            $lmrCount = 0; $lmrValue = 0; $lmrItems = [];

            foreach ($initiations as $pi) {
                $rDateStr = $this->getRenewalDate($pi);
                if (!$rDateStr) continue;

                $rDate = Carbon::parse($rDateStr);
                $price = (float) $pi->total_price;
                $paid = (float) $pi->amount_paid;

                $item = [
                    'id' => $pi->id,
                    'company_name' => $pi->company_name ?: ($pi->contact_name ?: 'N/A'),
                    'product_name' => $pi->product_name,
                    'renewal_date' => $rDateStr,
                    'value' => $price,
                    'paid' => $paid,
                    'pending' => max(0, $price - $paid),
                    'assigned_to' => $pi->customer_support_executive_id ?: $pi->customer_support_tl_id,
                ];

                if ($rDate->between($cmStart, $cmEnd)) {
                    $cmrCount++; $cmrValue += $price; $cmrItems[] = $item;
                } elseif ($rDate->between($nmStart, $nmEnd)) {
                    $nmrCount++; $nmrValue += $price; $nmrItems[] = $item;
                } elseif ($rDate->between($lmStart, $lmEnd)) {
                    $lmrCount++; $lmrValue += $price; $lmrItems[] = $item;
                }
            }

            // 2. Department & Product wise pending payments
            $pendingProducts = LeadProduct::query()
                ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
                ->join('products', 'products.id', '=', 'lead_products.product_id')
                ->select([
                    'lead_products.id', 'lead_products.total_price', 'lead_products.amount_paid',
                    'products.product_name', 'products.id as p_id', 'leads.company_name',
                ])
                ->where($applySupportScope)
                ->where('lead_products.payment_status', '!=', 'paid')
                ->when(!empty($filters['branch_id']), fn($q) => $q->where('leads.branch_id', $filters['branch_id']))
                ->when(!empty($filters['product_id']), fn($q) => $q->where('lead_products.product_id', $filters['product_id']))
                ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']))
                ->with('product.departments')
                ->get();

            $deptProductData = [];
            foreach ($pendingProducts as $lp) {
                $pending = (float) $lp->total_price - (float) $lp->amount_paid;
                if ($pending <= 0) continue;

                $productName = $lp->product_name;
                $departments = $lp->product?->departments ?? collect();

                if ($departments->isEmpty()) {
                    $deptName = 'Unassigned';
                    $key = $deptName . '_' . $productName;
                    $deptProductData[$key] ??= ['department' => $deptName, 'product' => $productName, 'pending_amount' => 0.0, 'count' => 0];
                    $deptProductData[$key]['pending_amount'] += $pending;
                    $deptProductData[$key]['count']++;
                } else {
                    foreach ($departments as $dept) {
                        $deptName = $dept->name;
                        $key = $deptName . '_' . $productName;
                        $deptProductData[$key] ??= ['department' => $deptName, 'product' => $productName, 'pending_amount' => 0.0, 'count' => 0];
                        $deptProductData[$key]['pending_amount'] += $pending;
                        $deptProductData[$key]['count']++;
                    }
                }
            }
            $deptProductList = array_values($deptProductData);

            // 3. Payment Collections
            $todayStart = Carbon::today()->startOfDay();
            $todayEnd = Carbon::today()->endOfDay();
            $monthStart = Carbon::today()->startOfMonth()->startOfDay();
            $monthEnd = Carbon::today()->endOfMonth()->endOfDay();
            $fromDate = !empty($filters['from_date']) ? Carbon::parse($filters['from_date'])->startOfDay() : null;
            $toDate = !empty($filters['to_date']) ? Carbon::parse($filters['to_date'])->endOfDay() : null;

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
            if ($toDate) $trendQuery->whereDate('lead_product_payments.payment_date', '<=', $toDate);
            $trendData = $trendQuery
                ->selectRaw("DATE_FORMAT(lead_product_payments.payment_date, '%Y-%m-%d') as day, SUM(lead_product_payments.amount) as daily_amount")
                ->groupBy('day')->orderBy('day')->get()
                ->map(fn($row) => ['date' => $row->day, 'amount' => (float) $row->daily_amount])->toArray();

            // 4. Upsell Leads
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
                ->when(!empty($filters['source']), fn($q) => $q->where('leads.lead_source', $filters['source']));

            if ($fromDate) $upsellQuery->whereDate('lead_products.created_at', '>=', $fromDate);
            if ($toDate) $upsellQuery->whereDate('lead_products.created_at', '<=', $toDate);

            $upsellStats = (clone $upsellQuery)->selectRaw('COUNT(DISTINCT leads.id) as upsell_count, SUM(lead_products.total_price) as upsell_value')->first();
            $upsellCount = (int) ($upsellStats->upsell_count ?? 0);
            $upsellValue = (float) ($upsellStats->upsell_value ?? 0);

            $upsellLeadsList = (clone $upsellQuery)
                ->select(['leads.id', 'leads.company_name', 'leads.contact_name', 'products.product_name', 'lead_products.total_price', 'lead_products.created_at'])
                ->orderByDesc('lead_products.created_at')->limit(10)->get()
                ->map(fn($row) => [
                    'id' => $row->id,
                    'company_name' => $row->company_name ?: ($row->contact_name ?: 'N/A'),
                    'product_name' => $row->product_name,
                    'value' => (float) $row->total_price,
                    'created_at' => $row->created_at ? Carbon::parse($row->created_at)->format('d M Y') : '—',
                ])->toArray();

            // 5. User-wise aggregates
            $userStats = [];
            $displayUserQuery = User::whereIn('id', $supportUserIds);
            if ($isSupportTl && !$isAdmin) {
                $assignedExecIds = Lead::where('customer_support_tl_id', $currentUser->id)
                    ->whereNotNull('customer_support_executive_id')
                    ->pluck('customer_support_executive_id')->unique()->toArray();
                $displayUserQuery->whereIn('id', array_merge([$currentUser->id], $assignedExecIds));
            } elseif ($isSupportExec && !$isAdmin) {
                $displayUserQuery->where('id', $currentUser->id);
            }
            $supportUsers = $displayUserQuery->orderBy('name')->get();

            foreach ($supportUsers as $user) {
                $uid = $user->id;
                $userIsTl = $user->hasCustomerSupportLikeRole() && $user->hasTlLikeRole();

                $handledCount = Lead::where(function ($q) use ($uid, $userIsTl) {
                        $userIsTl ? $q->where('customer_support_tl_id', $uid) : $q->where('customer_support_executive_id', $uid);
                    })
                    ->when($fromDate, fn($q) => $q->whereDate('lead_date', '>=', $fromDate))
                    ->when($toDate, fn($q) => $q->whereDate('lead_date', '<=', $toDate))
                    ->count();

                $convertedCount = Lead::where(function ($q) use ($uid, $userIsTl) {
                        $userIsTl ? $q->where('customer_support_tl_id', $uid) : $q->where('customer_support_executive_id', $uid);
                    })
                    ->where('lead_status_id', 5)
                    ->when($fromDate, fn($q) => $q->whereDate('lead_date', '>=', $fromDate))
                    ->when($toDate, fn($q) => $q->whereDate('lead_date', '<=', $toDate))
                    ->count();

                $uCollected = LeadProductPayment::query()->where('recorded_by', $uid)
                    ->when($fromDate, fn($q) => $q->whereDate('payment_date', '>=', $fromDate))
                    ->when($toDate, fn($q) => $q->whereDate('payment_date', '<=', $toDate))
                    ->sum('amount');

                $userCmrCount = 0; $userCmrValue = 0;
                foreach ($cmrItems as $item) {
                    if ($item['assigned_to'] == $uid) { $userCmrCount++; $userCmrValue += $item['value']; }
                }

                $userStats[] = [
                    'id' => $uid, 'name' => $user->name,
                    'handled_leads' => $handledCount, 'converted_leads' => $convertedCount,
                    'total_collected' => (float) $uCollected,
                    'cmr_count' => $userCmrCount, 'cmr_value' => (float) $userCmrValue,
                ];
            }

            // 6. Current Month Delivery Projects
            $deliveryProjects = ProductionInitiation::query()
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
                ->whereBetween('production_initiations.project_delivery_date', [$fromDate->toDateString(), $toDate->toDateString()])
                ->orderBy('production_initiations.project_delivery_date')
                ->get();

            $deliveryProjectsData = [];
            foreach ($deliveryProjects as $dp) {
                $employeeIds = is_array($dp->project_allocated_employee_user_ids) ? $dp->project_allocated_employee_user_ids : json_decode($dp->project_allocated_employee_user_ids ?? '[]', true) ?? [];
                $tlIds = is_array($dp->project_allocated_tl_user_ids) ? $dp->project_allocated_tl_user_ids : json_decode($dp->project_allocated_tl_user_ids ?? '[]', true) ?? [];

                $allocatedNames = []; $allocatedDept = '';
                if (!empty($employeeIds)) {
                    $employees = User::whereIn('id', $employeeIds)->with('employeeOnboarding.department')->get();
                    $allocatedNames = $employees->pluck('name')->toArray();
                    $allocatedDept = implode(', ', $employees->map(fn($e) => $e->employeeOnboarding?->department?->name)->filter()->unique()->toArray());
                } elseif (!empty($tlIds)) {
                    $tls = User::whereIn('id', $tlIds)->with('employeeOnboarding.department')->get();
                    $allocatedNames = $tls->pluck('name')->toArray();
                    $allocatedDept = implode(', ', $tls->map(fn($t) => $t->employeeOnboarding?->department?->name)->filter()->unique()->toArray());
                }

                $allocatedPersonLabel = !empty($allocatedNames) ? implode(', ', $allocatedNames) : 'Not allocated';
                $price = (float) $dp->total_price; $paid = (float) $dp->amount_paid; $pending = max(0, $price - $paid);

                $deliveryProjectsData[] = [
                    'id' => $dp->id,
                    'product_name' => $dp->product_name,
                    'company_name' => $dp->company_name ?: ($dp->contact_name ?: 'N/A'),
                    'delivery_date' => $dp->project_delivery_date ? Carbon::parse($dp->project_delivery_date)->format('d M Y') : '—',
                    'allocated_person' => $allocatedPersonLabel,
                    'allocated_department' => $allocatedDept ?: ($dp->department_name ?: '—'),
                    'status' => strtoupper((string) ($dp->project_execution_status ?: 'onboard')),
                    'total_value' => $price, 'received_amount' => $paid, 'pending_amount' => $pending,
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

            return response()->json([
                'success' => true,
                'data' => [
                    'cmr' => ['count' => $cmrCount, 'value' => round($cmrValue, 2), 'items' => $cmrItems],
                    'cmr_plus' => ['count' => $nmrCount, 'value' => round($nmrValue, 2), 'items' => $nmrItems],
                    'cmr_minus' => ['count' => $lmrCount, 'value' => round($lmrValue, 2), 'items' => $lmrItems],
                    'dept_product_pending' => $deptProductList,
                    'today_payments' => round((float) $todayPayments, 2),
                    'month_payments' => round((float) $monthPayments, 2),
                    'upsells' => ['count' => $upsellCount, 'value' => round($upsellValue, 2), 'items' => $upsellLeadsList],
                    'daily_trend' => $trendData,
                    'user_performance' => $userStats,
                    'delivery_projects' => $deliveryProjectsData,
                    'delivery_title' => $deliverySectionTitle,
                    'delivery_badge' => $deliverySectionBadge,
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

    private function getSupportUserIds(User $user): array
    {
        $visibleUserIds = $this->visibility->visibleUserIds();

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
            'cmr' => ['count' => 0, 'value' => 0, 'items' => []],
            'cmr_plus' => ['count' => 0, 'value' => 0, 'items' => []],
            'cmr_minus' => ['count' => 0, 'value' => 0, 'items' => []],
            'dept_product_pending' => [],
            'today_payments' => 0,
            'month_payments' => 0,
            'upsells' => ['count' => 0, 'value' => 0, 'items' => []],
            'daily_trend' => [],
            'user_performance' => [],
            'delivery_projects' => [],
            'delivery_title' => 'Delivery Planned Projects',
            'delivery_badge' => 'Planned',
        ];
    }
}