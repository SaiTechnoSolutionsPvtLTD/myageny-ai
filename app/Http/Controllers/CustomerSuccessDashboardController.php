<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductionInitiation;
use App\Models\CustomerCampaign;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerSuccessDashboardController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Display the customer success dashboard view.
     */
    public function index(Request $request): View
    {
        return view('pages.dashboard.customer-success');
    }

    /**
     * Get Customer Support Team members.
     */
    private function getSupportUserIds(User $user): array
    {
        $visibleUserIds = $this->visibility->visibleUserIds();
        
        $supportUserIds = User::where(function($query) {
            $query->whereHas('roles.department', function($q) {
                $q->where('name', 'like', '%customer support%')
                  ->orWhere('name', 'like', '%customer success%')
                  ->orWhere('id', 5);
            })->orWhereHas('employeeOnboarding', function($q) {
                $q->where('department_id', 5);
            });
        })->pluck('id')->toArray();

        if ($visibleUserIds !== null) {
            return array_values(array_intersect($visibleUserIds, $supportUserIds));
        }

        return $supportUserIds;
    }

    /**
     * Return filter options (Executives, Branches, Products, Sources).
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
                'status' => true,
                'data'   => compact('users', 'branches', 'products', 'sources'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load filter options.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Helper to retrieve the expiry date of a count_wise production initiation.
     */
    private function getRenewalDate($pi): ?string
    {
        $formData = is_array($pi->custom_form_data) 
            ? $pi->custom_form_data 
            : json_decode($pi->custom_form_data ?? '[]', true) ?? [];
            
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
            
            if (in_array($key, ['end_date', 'enddate', 'end date', 'smm_end_date', 'End Date', 'ovp_end_date'])) {
                if (!empty($value)) {
                    try {
                        return Carbon::parse($value)->toDateString();
                    } catch (\Exception $e) {}
                }
            }
        }
        
        return $pi->project_delivery_date ? Carbon::parse($pi->project_delivery_date)->toDateString() : null;
    }

    /**
     * Return dashboard metrics data feed.
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $supportUserIds = $this->getSupportUserIds($currentUser);
            
            $filters = $request->only([
                'user_id', 'branch_id', 'product_id', 'source', 'from_date', 'to_date'
            ]);

            // Set default date range to current month if empty
            if (empty($filters['from_date']) && empty($filters['to_date'])) {
                $filters['from_date'] = now()->startOfMonth()->toDateString();
                $filters['to_date'] = now()->endOfMonth()->toDateString();
            }

            $isSupportTl = $currentUser->hasCustomerSupportLikeRole() && $currentUser->hasTlLikeRole();
            $isSupportExec = $currentUser->hasCustomerSupportLikeRole() && !$currentUser->hasTlLikeRole();
            $isAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();

            // Set up base query constraint closure
            $applySupportScope = function($query) use ($filters, $currentUser, $isSupportTl, $isSupportExec, $isAdmin) {
                if (!empty($filters['user_id'])) {
                    $query->where('leads.customer_support_executive_id', (int) $filters['user_id']);
                } else {
                    if ($isSupportTl && !$isAdmin) {
                        $query->where('leads.customer_support_tl_id', $currentUser->id);
                    } elseif ($isSupportExec && !$isAdmin) {
                        $query->where('leads.customer_support_executive_id', $currentUser->id);
                    } else {
                        $comp = $currentUser->company_id;
                        if ($comp) {
                            $query->where('leads.company_id', $comp);
                        }
                    }
                }
            };

            if (empty($supportUserIds) && !$isAdmin) {
                return response()->json([
                    'status' => true,
                    'data'   => $this->emptyDashboardResponse()
                ]);
            }

            // 1. Fetch count-wise recurring initiations for renewals
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
                    'products.is_this_renewal_product'
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
                $paid  = (float) $pi->amount_paid;

                $item = [
                    'id'           => $pi->id,
                    'company_name' => $pi->company_name ?: ($pi->contact_name ?: 'N/A'),
                    'product_name' => $pi->product_name,
                    'renewal_date' => $rDateStr,
                    'value'        => $price,
                    'paid'         => $paid,
                    'pending'      => max(0, $price - $paid),
                    'assigned_to'  => $pi->customer_support_executive_id ?: $pi->customer_support_tl_id
                ];

                if ($rDate->between($cmStart, $cmEnd)) {
                    $cmrCount++;
                    $cmrValue += $price;
                    $cmrItems[] = $item;
                } elseif ($rDate->between($nmStart, $nmEnd)) {
                    $nmrCount++;
                    $nmrValue += $price;
                    $nmrItems[] = $item;
                } elseif ($rDate->between($lmStart, $lmEnd)) {
                    $lmrCount++;
                    $lmrValue += $price;
                    $lmrItems[] = $item;
                }
            }

            // 2. Department & Product wise pending payments
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

                $key = $deptName . '_' . $productName;
                if (!isset($deptProductData[$key])) {
                    $deptProductData[$key] = [
                        'department'     => $deptName,
                        'product'        => $productName,
                        'pending_amount' => 0.0,
                        'count'          => 0
                    ];
                }
                $deptProductData[$key]['pending_amount'] += (float) $row->total_pending;
                $deptProductData[$key]['count'] += (int) $row->total_count;
            }

            usort($deptProductData, fn($a, $b) => $b['pending_amount'] <=> $a['pending_amount']);
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

            $todayPayments = (clone $paymentQuery)
                ->whereBetween('lead_product_payments.payment_date', [$todayStart, $todayEnd])
                ->sum('lead_product_payments.amount');

            $monthPayments = (clone $paymentQuery)
                ->whereBetween('lead_product_payments.payment_date', [$monthStart, $monthEnd])
                ->sum('lead_product_payments.amount');

            // Daily trend
            $trendQuery = clone $paymentQuery;
            if ($fromDate) {
                $trendQuery->whereDate('lead_product_payments.payment_date', '>=', $fromDate);
            }
            if ($toDate) {
                $trendQuery->whereDate('lead_product_payments.payment_date', '<=', $toDate);
            }
            $trendData = $trendQuery
                ->selectRaw("DATE_FORMAT(lead_product_payments.payment_date, '%Y-%m-%d') as day, SUM(lead_product_payments.amount) as daily_amount")
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->map(fn($row) => [
                    'date'   => $row->day,
                    'amount' => (float) $row->daily_amount
                ])->toArray();

            // 4. Upsell Leads (non count-wise converted products)
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

            if ($fromDate) {
                $upsellQuery->whereDate('lead_products.created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $upsellQuery->whereDate('lead_products.created_at', '<=', $toDate);
            }

            $upsellStats = (clone $upsellQuery)
                ->selectRaw('COUNT(DISTINCT leads.id) as upsell_count, SUM(lead_products.total_price) as upsell_value')
                ->first();

            $upsellCount = (int) ($upsellStats->upsell_count ?? 0);
            $upsellValue = (float) ($upsellStats->upsell_value ?? 0);

            $upsellLeadsList = (clone $upsellQuery)
                ->select([
                    'leads.id',
                    'leads.company_name',
                    'leads.contact_name',
                    'products.product_name',
                    'lead_products.total_price',
                    'lead_products.created_at'
                ])
                ->orderByDesc('lead_products.created_at')
                ->limit(10)
                ->get()
                ->map(fn($row) => [
                    'id'           => $row->id,
                    'company_name' => $row->company_name ?: ($row->contact_name ?: 'N/A'),
                    'product_name' => $row->product_name,
                    'value'        => (float) $row->total_price,
                    'created_at'   => $row->created_at ? Carbon::parse($row->created_at)->format('d M Y') : '—'
                ])->toArray();

            // 5. User-wise aggregates
            $userStats = [];
            $displayUserQuery = User::whereIn('id', $supportUserIds);
            if ($isSupportTl && !$isAdmin) {
                $assignedExecIds = Lead::where('customer_support_tl_id', $currentUser->id)
                    ->whereNotNull('customer_support_executive_id')
                    ->pluck('customer_support_executive_id')
                    ->unique()
                    ->toArray();
                $displayUserQuery->whereIn('id', array_merge([$currentUser->id], $assignedExecIds));
            } elseif ($isSupportExec && !$isAdmin) {
                $displayUserQuery->where('id', $currentUser->id);
            }
            $supportUsers = $displayUserQuery->orderBy('name')->get();

            foreach ($supportUsers as $user) {
                $uid = $user->id;
                $userIsTl = $user->hasCustomerSupportLikeRole() && $user->hasTlLikeRole();

                $handledCount = Lead::where(function($q) use ($uid, $userIsTl) {
                        if ($userIsTl) {
                            $q->where('customer_support_tl_id', $uid);
                        } else {
                            $q->where('customer_support_executive_id', $uid);
                        }
                    })
                    ->when($fromDate, fn($q) => $q->whereDate('lead_date', '>=', $fromDate))
                    ->when($toDate, fn($q) => $q->whereDate('lead_date', '<=', $toDate))
                    ->count();

                $convertedCount = Lead::where(function($q) use ($uid, $userIsTl) {
                        if ($userIsTl) {
                            $q->where('customer_support_tl_id', $uid);
                        } else {
                            $q->where('customer_support_executive_id', $uid);
                        }
                    })
                    ->where('lead_status_id', 5)
                    ->when($fromDate, fn($q) => $q->whereDate('lead_date', '>=', $fromDate))
                    ->when($toDate, fn($q) => $q->whereDate('lead_date', '<=', $toDate))
                    ->count();

                $uCollected = LeadProductPayment::query()
                    ->where('recorded_by', $uid)
                    ->when($fromDate, fn($q) => $q->whereDate('payment_date', '>=', $fromDate))
                    ->when($toDate, fn($q) => $q->whereDate('payment_date', '<=', $toDate))
                    ->sum('amount');

                // Renewal counts for this user (CMR month)
                $userCmrCount = 0;
                $userCmrValue = 0;
                foreach ($cmrItems as $item) {
                    if ($item['assigned_to'] == $uid) {
                        $userCmrCount++;
                        $userCmrValue += $item['value'];
                    }
                }

                $userStats[] = [
                    'id'              => $uid,
                    'name'            => $user->name,
                    'handled_leads'   => $handledCount,
                    'converted_leads' => $convertedCount,
                    'total_collected' => (float) $uCollected,
                    'cmr_count'       => $userCmrCount,
                    'cmr_value'       => (float) $userCmrValue
                ];
            }

            // 6. Current Month Delivery Projects (for payment followup)
            $deliveryProjects = ProductionInitiation::query()
                ->join('leads', 'leads.id', '=', 'production_initiations.lead_id')
                ->join('lead_products', 'lead_products.id', '=', 'production_initiations.lead_product_id')
                ->leftJoin('departments', 'departments.id', '=', 'production_initiations.department_id')
                ->select([
                    'production_initiations.id',
                    'production_initiations.product_name',
                    'production_initiations.project_delivery_date',
                    'production_initiations.project_execution_status',
                    'production_initiations.project_allocated_employee_user_ids',
                    'production_initiations.project_allocated_tl_user_ids',
                    'lead_products.total_price',
                    'lead_products.amount_paid',
                    'leads.company_name',
                    'leads.contact_name',
                    'departments.name as department_name'
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
            $allUserIds = [];
            foreach ($deliveryProjects as $dp) {
                $employeeIds = is_array($dp->project_allocated_employee_user_ids)
                    ? $dp->project_allocated_employee_user_ids
                    : json_decode($dp->project_allocated_employee_user_ids ?? '[]', true) ?? [];
                $tlIds = is_array($dp->project_allocated_tl_user_ids)
                    ? $dp->project_allocated_tl_user_ids
                    : json_decode($dp->project_allocated_tl_user_ids ?? '[]', true) ?? [];
                foreach ($employeeIds as $eid) { if ($eid) $allUserIds[] = (int) $eid; }
                foreach ($tlIds as $tid) { if ($tid) $allUserIds[] = (int) $tid; }
            }
            $allUserIds = array_unique($allUserIds);
            $userMap = !empty($allUserIds)
                ? User::whereIn('id', $allUserIds)->with('employeeOnboarding.department')->get()->keyBy('id')
                : collect();

            foreach ($deliveryProjects as $dp) {
                // Find allocated person label (TL or Employee)
                $employeeIds = is_array($dp->project_allocated_employee_user_ids)
                    ? $dp->project_allocated_employee_user_ids
                    : json_decode($dp->project_allocated_employee_user_ids ?? '[]', true) ?? [];
                
                $tlIds = is_array($dp->project_allocated_tl_user_ids)
                    ? $dp->project_allocated_tl_user_ids
                    : json_decode($dp->project_allocated_tl_user_ids ?? '[]', true) ?? [];

                $allocatedNames = [];
                $allocatedDept = '';

                if (!empty($employeeIds)) {
                    $employees = collect($employeeIds)->map(fn($id) => $userMap->get($id))->filter();
                    $allocatedNames = $employees->pluck('name')->toArray();
                    $depts = $employees->map(fn($e) => $e->employeeOnboarding?->department?->name)->filter()->unique()->toArray();
                    $allocatedDept = implode(', ', $depts);
                } elseif (!empty($tlIds)) {
                    $tls = collect($tlIds)->map(fn($id) => $userMap->get($id))->filter();
                    $allocatedNames = $tls->pluck('name')->toArray();
                    $depts = $tls->map(fn($t) => $t->employeeOnboarding?->department?->name)->filter()->unique()->toArray();
                    $allocatedDept = implode(', ', $depts);
                }

                $allocatedPersonLabel = !empty($allocatedNames) ? implode(', ', $allocatedNames) : 'Not allocated';

                $price = (float) $dp->total_price;
                $paid = (float) $dp->amount_paid;
                $pending = max(0, $price - $paid);

                $deliveryProjectsData[] = [
                    'id' => $dp->id,
                    'product_name' => $dp->product_name,
                    'company_name' => $dp->company_name ?: ($dp->contact_name ?: 'N/A'),
                    'delivery_date' => $dp->project_delivery_date ? Carbon::parse($dp->project_delivery_date)->format('d M Y') : '—',
                    'allocated_person' => $allocatedPersonLabel,
                    'allocated_department' => $allocatedDept ?: ($dp->department_name ?: '—'),
                    'status' => strtoupper((string) ($dp->project_execution_status ?: 'onboard')),
                    'total_value' => $price,
                    'received_amount' => $paid,
                    'pending_amount' => $pending,
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

            // 7. Customer Campaigns (Expired, Current Month Renewed, Current Month Not Renewed)
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
            $cmStartStr = $cmStart->toDateString();
            $cmEndStr = $cmEnd->toDateString();

            foreach ($campaignsQuery as $c) {
                $endDate = $c->end_date ? Carbon::parse($c->end_date)->toDateString() : null;
                $startDate = $c->start_date ? Carbon::parse($c->start_date)->toDateString() : null;
                $createdAt = Carbon::parse($c->created_at)->toDateString();
                
                $isExpired = in_array($c->status, ['expired', 'completed'], true) || ($endDate && $endDate < $nowDate);
                $isExtended = in_array($c->id, $extendedParentIds, true) || $c->extensions->isNotEmpty();
                $isAnExtension = !empty($c->extended_from_id);
                $budget = (float) ($c->budget_amount ?? 0);

                $item = [
                    'id' => $c->id,
                    'lead_id' => $c->lead_id,
                    'campaign_name' => $c->campaign_name,
                    'company_name' => $c->company_name ?: ($c->contact_name ?: 'N/A'),
                    'mobile_number' => $c->mobile_number ?: '—',
                    'platform' => $c->platform ?: 'Digital Marketing',
                    'ad_account_name' => $c->ad_account_name ?: '—',
                    'budget' => $budget,
                    'budget_type' => $c->budget_type ?: 'Monthly',
                    'start_date' => $startDate ? Carbon::parse($startDate)->format('d M Y') : '—',
                    'end_date' => $endDate ? Carbon::parse($endDate)->format('d M Y') : '—',
                    'status' => strtoupper((string) ($c->status ?: 'active')),
                    'is_renewed' => $isExtended,
                ];

                // 1. Expired Campaigns (overall or ended in past)
                if ($isExpired) {
                    $expiredCampaignItems[] = $item;
                    $expiredCampaignValue += $budget;
                }

                // 2. Current Month Renewed Campaigns
                if (($isExtended && $endDate && $endDate >= $cmStartStr && $endDate <= $cmEndStr) || 
                    ($isAnExtension && (($createdAt >= $cmStartStr && $createdAt <= $cmEndStr) || ($startDate && $startDate >= $cmStartStr && $startDate <= $cmEndStr)))) {
                    $cmRenewedCampaignItems[] = $item;
                    $cmRenewedCampaignValue += $budget;
                }

                // 3. Current Month Not Renewed Campaigns (ends in current month and not yet extended)
                if ($endDate && $endDate >= $cmStartStr && $endDate <= $cmEndStr && !$isExtended) {
                    $cmNotRenewedCampaignItems[] = $item;
                    $cmNotRenewedCampaignValue += $budget;
                }
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'cmr' => [
                        'count' => $cmrCount,
                        'value' => round($cmrValue, 2),
                        'items' => $cmrItems
                    ],
                    'cmr_plus' => [
                        'count' => $nmrCount,
                        'value' => round($nmrValue, 2),
                        'items' => $nmrItems
                    ],
                    'cmr_minus' => [
                        'count' => $lmrCount,
                        'value' => round($lmrValue, 2),
                        'items' => $lmrItems
                    ],
                    'campaigns' => [
                        'expired' => [
                            'count' => count($expiredCampaignItems),
                            'value' => round($expiredCampaignValue, 2),
                            'items' => $expiredCampaignItems
                        ],
                        'cm_renewed' => [
                            'count' => count($cmRenewedCampaignItems),
                            'value' => round($cmRenewedCampaignValue, 2),
                            'items' => $cmRenewedCampaignItems
                        ],
                        'cm_not_renewed' => [
                            'count' => count($cmNotRenewedCampaignItems),
                            'value' => round($cmNotRenewedCampaignValue, 2),
                            'items' => $cmNotRenewedCampaignItems
                        ],
                    ],
                    'dept_product_pending' => $deptProductList,
                    'today_payments'       => round((float)$todayPayments, 2),
                    'month_payments'       => round((float)$monthPayments, 2),
                    'upsells' => [
                        'count' => $upsellCount,
                        'value' => round($upsellValue, 2),
                        'items' => $upsellLeadsList
                    ],
                    'daily_trend'          => $trendData,
                    'user_performance'     => $userStats,
                    'delivery_projects'    => $deliveryProjectsData,
                    'delivery_title'       => $deliverySectionTitle,
                    'delivery_badge'       => $deliverySectionBadge,
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load customer success data.',
                'error'   => config('app.debug') ? $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() : null,
            ], 500);
        }
    }

    /**
     * Default empty dashboard payload.
     */
    private function emptyDashboardResponse(): array
    {
        return [
            'cmr'                  => ['count' => 0, 'value' => 0, 'items' => []],
            'cmr_plus'             => ['count' => 0, 'value' => 0, 'items' => []],
            'cmr_minus'            => ['count' => 0, 'value' => 0, 'items' => []],
            'campaigns'            => [
                'expired'        => ['count' => 0, 'value' => 0, 'items' => []],
                'cm_renewed'     => ['count' => 0, 'value' => 0, 'items' => []],
                'cm_not_renewed' => ['count' => 0, 'value' => 0, 'items' => []],
            ],
            'dept_product_pending' => [],
            'today_payments'       => 0,
            'month_payments'       => 0,
            'upsells'              => ['count' => 0, 'value' => 0, 'items' => []],
            'daily_trend'          => [],
            'user_performance'     => [],
            'delivery_projects'    => [],
            'delivery_title'       => 'Delivery Planned Projects',
            'delivery_badge'       => 'Planned',
        ];
    }
}
