<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\ProductionInitiation;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CrmReportController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function index(): View
    {
        $reports = [
            [
                'title' => 'Leads Summary',
                'description' => 'Track monthly lead volume, ownership, and follow-up activity from one report workspace.',
                'theme' => 'lead',
                'status' => 'Ready for setup',
                'route' => route('reports.crm.leads-summary'),
            ],
            [
                'title' => 'Product Wise Report',
                'description' => 'Review product-wise enquiries, deal movement, and current opportunity distribution.',
                'theme' => 'product',
                'status' => 'Ready for setup',
                'route' => route('reports.crm.product-wise'),
            ],
            [
                'title' => 'Revenue Comparision Report',
                'description' => 'Compare current and previous period revenue movement with value, count, and growth insights.',
                'theme' => 'pipeline',
                'status' => 'Ready for setup',
                'route' => route('reports.crm.revenue-comparison'),
            ],
            [
                'title' => 'Payment Collection Report',
                'description' => 'Review date-wise collections, customer receipts, payment modes, outstanding balances, and collector performance.',
                'theme' => 'payment',
                'status' => 'Ready for setup',
                'route' => route('reports.crm.payment-collection'),
            ],

        ];

        return view('pages.reports.crm.index', compact('reports'));
    }

    public function leadsSummary(Request $request): View
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $this->applyDefaultDateRange($request, $defaultFromDate, $defaultToDate);

        $query = $this->buildLeadsSummaryQuery($request);

        $reportRows = $query->paginate(20)->withQueryString();
        $analyticsRows = (clone $query)->get();

        $summaryQuery = clone $query;
        $summaryRows = (clone $summaryQuery)->get();
        $leadProductIds = $summaryRows->pluck('lead_product_id')->filter()->unique()->toArray();

        $totalPaid = 0;
        if (!empty($leadProductIds)) {
            $totalPaid = (float) DB::table('lead_product_payments')
                ->whereIn('lead_product_id', $leadProductIds)
                ->sum('amount');
        }

        $totalCost = (float) $summaryRows->sum('total_price');
        $summary = [
            'rows' => $summaryRows->count(),
            'total_cost' => $totalCost,
            'received_cost' => $totalPaid,
            'pending_cost' => max(0, $totalCost - $totalPaid),
        ];
        $analytics = $this->buildLeadsSummaryAnalytics($analyticsRows);

        $sourceOptions = LeadSource::query()
            ->orderBy('name')
            ->pluck('name');

        $statusOptions = LeadStatus::query()
            ->orderBy('name')
            ->pluck('name');

        $users = $this->visibility->visibleAssignableUsers();

        $productOptions = Product::query()
            ->orderBy('package_name');
        $this->visibility->applyProductVisibility($productOptions);
        $products = $productOptions->get(['id', 'package_name', 'product_name']);

        $filterPanelOpen =
            $request->filled('lead_source')
            || $request->filled('lead_status')
            || $request->filled('assigned_to')
            || $request->filled('product_id')
            || $request->input('date_from') !== $defaultFromDate
            || $request->input('date_to') !== $defaultToDate;

        return view('pages.reports.crm.leads-summary', compact(
            'reportRows',
            'summary',
            'analytics',
            'sourceOptions',
            'statusOptions',
            'users',
            'products',
            'defaultFromDate',
            'defaultToDate',
            'filterPanelOpen'
        ));
    }

    public function exportLeadsSummary(Request $request): Response
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $this->applyDefaultDateRange($request, $defaultFromDate, $defaultToDate);

        $allRows = $this->buildLeadsSummaryQuery($request)->get();
        // Get all payments for these products
        $productIds = $allRows->pluck('lead_product_id')->filter()->unique()->toArray();
        $paymentsMap = [];
        if (!empty($productIds)) {
            $paymentsMap = DB::table('lead_product_payments')
                ->whereIn('lead_product_id', $productIds)
                ->groupBy('lead_product_id')
                ->selectRaw('lead_product_id, SUM(amount) as total_paid')
                ->pluck('total_paid', 'lead_product_id')
                ->toArray();
        }

        $rows = $allRows->map(function ($row) use ($paymentsMap) {
            $entryDate = $row->lead_date ?? optional($row->lead_created_at)?->toDateString();
            $entryCarbon = $entryDate ? \Illuminate\Support\Carbon::parse($entryDate) : null;
            $convertedCarbon = $row->converted_at ? \Illuminate\Support\Carbon::parse($row->converted_at) : null;
            $leadStatus = $row->product_lead_status ?: $row->base_lead_status;
            $receivedAmount = (float) ($paymentsMap[$row->lead_product_id] ?? 0);
            $pendingCost = max(0, (float) ($row->total_price ?? 0) - $receivedAmount);

            return [
                'Lead ID' => 'LD-' . str_pad((string) $row->lead_id, 4, '0', STR_PAD_LEFT),
                'Name' => $row->contact_name ?: '-',
                'Email' => $row->email ?: '-',
                'Mobile Number' => $row->mobile_number ?: '-',
                'Lead Source' => $row->lead_source ?: '-',
                'Lead Status' => $leadStatus ?: '-',
                'Product Name' => $row->product_name ?: '-',
                'Entry Date' => $entryCarbon?->format('d-m-Y') ?: '-',
                'Converted Date' => $convertedCarbon?->format('d-m-Y') ?: '-',
                'Total Cost' => number_format((float) ($row->total_price ?? 0), 2, '.', ''),
                'Received Cost' => number_format($receivedAmount, 2, '.', ''),
                'Pending Cost' => number_format($pendingCost, 2, '.', ''),
                'Allocated To' => $row->allocated_to_name ?: '-',
                'Lead Age' => $entryCarbon ? $entryCarbon->diffForHumans(now(), true) : '-',
            ];
        });

        $html = view('pages.reports.crm.leads-summary-export', [
            'rows' => $rows,
            'selectedFromDate' => $request->input('date_from', $defaultFromDate),
            'selectedToDate' => $request->input('date_to', $defaultToDate),
        ])->render();

        $fileName = 'crm_leads_summary_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function productWise(Request $request): View
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $this->applyDefaultDateRange($request, $defaultFromDate, $defaultToDate);

        $query = $this->buildProductWiseQuery($request);
        $reportRows = $query->paginate(20)->withQueryString();
        $analyticsRows = (clone $query)->get();

        $summary = [
            'rows' => $analyticsRows->count(),
            'quantity_sold' => (int) $analyticsRows->sum('quantity_sold'),
            'sales_amount' => (float) $analyticsRows->sum('sales_amount'),
            'net_revenue' => (float) $analyticsRows->sum('net_revenue'),
        ];

        $analytics = $this->buildProductWiseAnalytics($analyticsRows);

        $productOptions = Product::query()->orderBy('package_name');
        $this->visibility->applyProductVisibility($productOptions);
        $products = $productOptions->get(['id', 'package_name', 'product_name', 'sku']);

        $filterPanelOpen =
            $request->filled('product_id')
            || $request->input('date_from') !== $defaultFromDate
            || $request->input('date_to') !== $defaultToDate;

        return view('pages.reports.crm.product-wise', compact(
            'reportRows',
            'summary',
            'analytics',
            'products',
            'defaultFromDate',
            'defaultToDate',
            'filterPanelOpen'
        ));
    }

    public function exportProductWise(Request $request): Response
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $this->applyDefaultDateRange($request, $defaultFromDate, $defaultToDate);

        $rows = $this->buildProductWiseQuery($request)->get()->map(function ($row) {
            return [
                'Product Code' => $row->product_code ?: '-',
                'Product Name' => $row->product_name ?: '-',
                'Category' => $row->category_name ?: '-',
                'Quantity Sold' => (int) ($row->quantity_sold ?? 0),
                'Sales Amount' => number_format((float) ($row->sales_amount ?? 0), 2, '.', ''),
                'Discount Amount' => number_format((float) ($row->discount_amount ?? 0), 2, '.', ''),
                'Tax Amount' => number_format((float) ($row->tax_amount ?? 0), 2, '.', ''),
                'Net Revenue' => number_format((float) ($row->net_revenue ?? 0), 2, '.', ''),
            ];
        });

        $html = view('pages.reports.crm.product-wise-export', [
            'rows' => $rows,
            'selectedFromDate' => $request->input('date_from', $defaultFromDate),
            'selectedToDate' => $request->input('date_to', $defaultToDate),
        ])->render();

        $fileName = 'crm_product_wise_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function revenueComparison(Request $request): View
    {
        $filterOptions = $this->revenueComparisonFilterOptions();
        $selectedFilters = $this->normalizeRevenueComparisonFilters($request);
        $comparison = $this->buildRevenueComparisonData($selectedFilters);

        return view('pages.reports.crm.revenue-comparison', compact(
            'filterOptions',
            'selectedFilters',
            'comparison'
        ));
    }

    public function exportRevenueComparison(Request $request): Response
    {
        $selectedFilters = $this->normalizeRevenueComparisonFilters($request);
        $comparison = $this->buildRevenueComparisonData($selectedFilters);

        $rows = collect([
            [
                'Period' => $comparison['table']['period_label'],
                'Current Period Value' => number_format($comparison['table']['current_period_value'], 2, '.', ''),
                'Current Period Count' => $comparison['table']['current_period_count'],
                'Previous Period Value' => number_format($comparison['table']['previous_period_value'], 2, '.', ''),
                'Previous Period Count' => $comparison['table']['previous_period_count'],
                'Difference Amount' => number_format($comparison['table']['difference_amount'], 2, '.', ''),
                'Difference %' => number_format($comparison['table']['difference_percent'], 2, '.', '') . '%',
                'Growth %' => number_format($comparison['table']['growth_percent'], 2, '.', '') . '%',
            ],
        ]);

        $html = view('pages.reports.crm.revenue-comparison-export', [
            'rows' => $rows,
            'selectedFilters' => $selectedFilters,
        ])->render();

        $fileName = 'crm_revenue_comparison_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function paymentCollection(Request $request): View
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $this->applyDefaultDateRange($request, $defaultFromDate, $defaultToDate);

        $query = $this->buildPaymentCollectionQuery($request);
        $reportRows = $query->paginate(20)->withQueryString();
        $analyticsRows = (clone $query)->get();

        $summary = [
            'rows' => $analyticsRows->count(),
            'total_amount' => (float) $analyticsRows->sum('total_amount'),
            'received_amount' => (float) $analyticsRows->sum('received_amount'),
            'outstanding_amount' => (float) $analyticsRows->sum('outstanding_amount'),
        ];

        $analytics = $this->buildPaymentCollectionAnalytics($analyticsRows);
        $paymentModes = LeadProduct::PAYMENT_MODES;
        $customers = $this->paymentCollectionCustomerOptions();

        $filterPanelOpen =
            $request->filled('customer_id')
            || $request->filled('payment_mode')
            || $request->input('date_from') !== $defaultFromDate
            || $request->input('date_to') !== $defaultToDate;

        return view('pages.reports.crm.payment-collection', compact(
            'reportRows',
            'summary',
            'analytics',
            'paymentModes',
            'customers',
            'defaultFromDate',
            'defaultToDate',
            'filterPanelOpen'
        ));
    }

    public function exportPaymentCollection(Request $request): Response
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $this->applyDefaultDateRange($request, $defaultFromDate, $defaultToDate);

        $rows = $this->buildPaymentCollectionQuery($request)->get()->map(function ($row) {
            return [
                'Payment ID' => 'PMT-' . str_pad((string) $row->payment_id, 4, '0', STR_PAD_LEFT),
                'Payment Date' => $row->payment_date ? Carbon::parse($row->payment_date)->format('d-m-Y') : '-',
                'Receipt No' => 'RCT-' . str_pad((string) $row->payment_id, 4, '0', STR_PAD_LEFT),
                'Customer ID' => 'LD-' . str_pad((string) $row->customer_id, 4, '0', STR_PAD_LEFT),
                'Customer Name' => $row->customer_name ?: '-',
                'Total Amount' => number_format((float) ($row->total_amount ?? 0), 2, '.', ''),
                'Received Amount' => number_format((float) ($row->received_amount ?? 0), 2, '.', ''),
                'Outstanding Amount' => number_format((float) ($row->outstanding_amount ?? 0), 2, '.', ''),
                'Payment Mode' => LeadProduct::PAYMENT_MODES[$row->payment_mode] ?? ucwords(str_replace('_', ' ', (string) $row->payment_mode)),
                'Transaction Reference' => $row->transaction_reference ?: '-',
                'Received By' => $row->received_by ?: '-',
            ];
        });

        $html = view('pages.reports.crm.payment-collection-export', [
            'rows' => $rows,
            'selectedFromDate' => $request->input('date_from', $defaultFromDate),
            'selectedToDate' => $request->input('date_to', $defaultToDate),
        ])->render();

        $fileName = 'crm_payment_collection_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function applyDefaultDateRange(Request $request, string $defaultFromDate, string $defaultToDate): void
    {
        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            $request->merge([
                'date_from' => $defaultFromDate,
                'date_to' => $defaultToDate,
            ]);
        }
    }

    private function buildLeadsSummaryQuery(Request $request)
    {
        $convertedSubquery = ProductionInitiation::query()
            ->selectRaw('lead_product_id, MIN(created_at) as converted_at')
            ->groupBy('lead_product_id');

        $query = Lead::query()
            ->leftJoin('lead_products', 'lead_products.lead_id', '=', 'leads.id')
            ->leftJoin('lead_statuses as product_lead_statuses', 'product_lead_statuses.id', '=', 'lead_products.lead_status_id')
            ->leftJoin('users as assigned_users', 'assigned_users.id', '=', 'leads.assigned_to')
            ->leftJoinSub($convertedSubquery, 'converted_products', function ($join) {
                $join->on('converted_products.lead_product_id', '=', 'lead_products.id');
            })
            ->select([
                'leads.id as lead_id',
                'leads.contact_name',
                'leads.email',
                'leads.mobile_number',
                'leads.lead_source',
                'leads.lead_status as base_lead_status',
                'leads.lead_date',
                'leads.created_at as lead_created_at',
                'lead_products.id as lead_product_id',
                'lead_products.product_id',
                'lead_products.product_name',
                'lead_products.total_price',
                'lead_products.amount_paid',
                'lead_products.created_at as lead_product_created_at',
                'product_lead_statuses.name as product_lead_status',
                'assigned_users.name as allocated_to_name',
                DB::raw('COALESCE(converted_products.converted_at, CASE WHEN lead_products.product_status = "converted" THEN lead_products.updated_at END) as converted_at'),
            ])
            ->orderByDesc('leads.lead_date')
            ->orderByDesc('leads.id')
            ->orderByDesc('lead_products.id');

        $companyId = $this->visibility->companyIdFor();
        $visibleUserIds = $this->visibility->visibleUserIds();

        if ($companyId) {
            $query->where('leads.company_id', $companyId);
        }

        if ($visibleUserIds !== null) {
            $query->whereIn('leads.assigned_to', $visibleUserIds);
        }

        if ($request->filled('lead_source')) {
            $query->where('leads.lead_source', $request->lead_source);
        }

        if ($request->filled('lead_status')) {
            $query->where(function ($statusQuery) use ($request) {
                $statusQuery
                    ->where('leads.lead_status', $request->lead_status)
                    ->orWhere('product_lead_statuses.name', $request->lead_status);
            });
        }

        if ($request->filled('assigned_to')) {
            $query->where('leads.assigned_to', $request->assigned_to);
        }

        if ($request->filled('product_id')) {
            $query->where('lead_products.product_id', $request->product_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('leads.lead_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('leads.lead_date', '<=', $request->date_to);
        }

        return $query;
    }

    private function buildProductWiseQuery(Request $request)
    {
        $query = LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->join('products', 'products.id', '=', 'lead_products.product_id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->select([
                'products.id as product_id',
                'products.sku as product_code',
                DB::raw('COALESCE(products.package_name, products.product_name, lead_products.product_name) as product_name'),
                'product_categories.name as category_name',
                DB::raw('SUM(COALESCE(lead_products.quantity, 0)) as quantity_sold'),
                DB::raw('ROUND(SUM(COALESCE(lead_products.unit_price, 0) * COALESCE(lead_products.quantity, 0)), 2) as sales_amount'),
                DB::raw('ROUND(SUM((COALESCE(lead_products.unit_price, 0) * COALESCE(lead_products.quantity, 0)) * (COALESCE(lead_products.discount_percent, 0) / 100)), 2) as discount_amount'),
                DB::raw('ROUND(SUM(CASE
                    WHEN products.tax_type = "percentage"
                        THEN ((COALESCE(lead_products.unit_price, 0) * COALESCE(lead_products.quantity, 0)) - ((COALESCE(lead_products.unit_price, 0) * COALESCE(lead_products.quantity, 0)) * (COALESCE(lead_products.discount_percent, 0) / 100))) * (COALESCE(products.tax_value, 0) / 100)
                    ELSE COALESCE(products.tax_value, 0) * COALESCE(lead_products.quantity, 0)
                END), 2) as tax_amount'),
                DB::raw('ROUND(SUM(COALESCE(lead_products.total_price, 0)), 2) as net_revenue'),
                DB::raw('MIN(lead_products.created_at) as first_sold_at'),
            ])
            ->groupBy(
                'products.id',
                'products.sku',
                'products.package_name',
                'products.product_name',
                'product_categories.name'
            )
            ->orderByDesc('quantity_sold')
            ->orderBy('product_name');

        $this->visibility->applyLeadRelationVisibility($query);

        if ($request->filled('product_id')) {
            $query->where('products.id', $request->product_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('lead_products.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('lead_products.created_at', '<=', $request->date_to);
        }

        return $query;
    }

    private function buildPaymentCollectionQuery(Request $request)
    {
        $paidSubquery = LeadProductPayment::query()
            ->selectRaw('lead_product_id, SUM(amount) as total_received')
            ->groupBy('lead_product_id');

        $query = LeadProductPayment::query()
            ->join('leads', 'leads.id', '=', 'lead_product_payments.lead_id')
            ->join('lead_products', 'lead_products.id', '=', 'lead_product_payments.lead_product_id')
            ->leftJoin('users as collectors', 'collectors.id', '=', 'lead_product_payments.recorded_by')
            ->leftJoinSub($paidSubquery, 'payment_totals', function ($join) {
                $join->on('payment_totals.lead_product_id', '=', 'lead_products.id');
            })
            ->select([
                'lead_product_payments.id as payment_id',
                'lead_product_payments.payment_date',
                'lead_product_payments.payment_mode',
                'lead_product_payments.reference_number as transaction_reference',
                'lead_product_payments.amount as received_amount',
                'leads.id as customer_id',
                DB::raw('COALESCE(NULLIF(leads.contact_name, ""), NULLIF(leads.company_name, ""), CONCAT("Lead #", leads.id)) as customer_name'),
                DB::raw('COALESCE(lead_products.total_price, 0) as total_amount'),
                DB::raw('GREATEST(COALESCE(lead_products.total_price, 0) - COALESCE(payment_totals.total_received, 0), 0) as outstanding_amount'),
                'collectors.name as received_by',
            ])
            ->orderByDesc('lead_product_payments.payment_date')
            ->orderByDesc('lead_product_payments.id');

        $companyId = $this->visibility->companyIdFor();
        $visibleUserIds = $this->visibility->visibleUserIds();

        if ($companyId) {
            $query->where('leads.company_id', $companyId);
        }

        if ($visibleUserIds !== null) {
            $query->whereIn('leads.assigned_to', $visibleUserIds);
        }

        if ($request->filled('customer_id')) {
            $query->where('leads.id', $request->customer_id);
        }

        if ($request->filled('payment_mode')) {
            $query->where('lead_product_payments.payment_mode', $request->payment_mode);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('lead_product_payments.payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('lead_product_payments.payment_date', '<=', $request->date_to);
        }

        return $query;
    }

    private function buildLeadsSummaryAnalytics($rows): array
    {
        // Get all payments for these products
        $productIds = collect($rows)->pluck('lead_product_id')->filter()->unique()->toArray();
        $paymentsMap = [];
        if (!empty($productIds)) {
            $paymentsMap = DB::table('lead_product_payments')
                ->whereIn('lead_product_id', $productIds)
                ->groupBy('lead_product_id')
                ->selectRaw('lead_product_id, SUM(amount) as total_paid')
                ->pluck('total_paid', 'lead_product_id')
                ->toArray();
        }

        $normalizedRows = collect($rows)->map(function ($row) use ($paymentsMap) {
            $entryDate = $row->lead_date ?? optional($row->lead_created_at)?->toDateString();
            $leadStatus = $row->product_lead_status ?: $row->base_lead_status;
            $totalCost = (float) ($row->total_price ?? 0);
            $receivedCost = (float) ($paymentsMap[$row->lead_product_id] ?? 0);

            return [
                'source' => $row->lead_source ?: 'Unknown',
                'status' => $leadStatus ?: 'Unknown',
                'allocated_to' => $row->allocated_to_name ?: 'Unassigned',
                'product' => $row->product_name ?: 'No Product',
                'entry_month' => $entryDate ? \Illuminate\Support\Carbon::parse($entryDate)->format('M Y') : 'Unknown',
                'entry_month_sort' => $entryDate ? \Illuminate\Support\Carbon::parse($entryDate)->format('Y-m') : '9999-99',
                'total_cost' => $totalCost,
                'received_cost' => $receivedCost,
                'pending_cost' => max(0, $totalCost - $receivedCost),
            ];
        });

        return [
            'sources' => $this->summarizeByKey($normalizedRows, 'source', 6),
            'statuses' => $this->summarizeByKey($normalizedRows, 'status', 6),
            'owners' => $this->summarizeByKey($normalizedRows, 'allocated_to', 6),
            'products' => $this->summarizeByKey($normalizedRows, 'product', 6),
            'monthly_trend' => $normalizedRows
                ->groupBy('entry_month_sort')
                ->map(function ($items) {
                    return [
                        'label' => $items->first()['entry_month'],
                        'sort' => $items->first()['entry_month_sort'],
                        'count' => $items->count(),
                        'total_cost' => round($items->sum('total_cost'), 2),
                        'received_cost' => round($items->sum('received_cost'), 2),
                    ];
                })
                ->sortBy('sort')
                ->values()
                ->map(fn ($item) => [
                    'label' => $item['label'],
                    'count' => $item['count'],
                    'total_cost' => $item['total_cost'],
                    'received_cost' => $item['received_cost'],
                ])
                ->values()
                ->all(),
        ];
    }

    private function summarizeByKey($rows, string $key, int $limit = 6): array
    {
        return $rows
            ->groupBy($key)
            ->map(function ($items, $label) {
                return [
                    'label' => $label,
                    'count' => $items->count(),
                    'total_cost' => round($items->sum('total_cost'), 2),
                    'received_cost' => round($items->sum('received_cost'), 2),
                    'pending_cost' => round($items->sum('pending_cost'), 2),
                ];
            })
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    private function buildProductWiseAnalytics($rows): array
    {
        $normalizedRows = collect($rows)->map(function ($row) {
            $month = $row->first_sold_at
                ? \Illuminate\Support\Carbon::parse($row->first_sold_at)
                : null;

            return [
                'product_name' => $row->product_name ?: 'Unknown Product',
                'category_name' => $row->category_name ?: 'Uncategorized',
                'quantity_sold' => (int) ($row->quantity_sold ?? 0),
                'sales_amount' => (float) ($row->sales_amount ?? 0),
                'discount_amount' => (float) ($row->discount_amount ?? 0),
                'tax_amount' => (float) ($row->tax_amount ?? 0),
                'net_revenue' => (float) ($row->net_revenue ?? 0),
                'month_label' => $month?->format('M Y') ?: 'Unknown',
                'month_sort' => $month?->format('Y-m') ?: '9999-99',
            ];
        });

        return [
            'products' => $normalizedRows
                ->sortByDesc('quantity_sold')
                ->take(6)
                ->values()
                ->all(),
            'categories' => $normalizedRows
                ->groupBy('category_name')
                ->map(fn ($items, $label) => [
                    'label' => $label,
                    'quantity_sold' => (int) $items->sum('quantity_sold'),
                    'sales_amount' => round($items->sum('sales_amount'), 2),
                    'net_revenue' => round($items->sum('net_revenue'), 2),
                ])
                ->sortByDesc('quantity_sold')
                ->take(6)
                ->values()
                ->all(),
            'monthly_trend' => $normalizedRows
                ->groupBy('month_sort')
                ->map(fn ($items) => [
                    'label' => $items->first()['month_label'],
                    'sort' => $items->first()['month_sort'],
                    'quantity_sold' => (int) $items->sum('quantity_sold'),
                    'sales_amount' => round($items->sum('sales_amount'), 2),
                    'net_revenue' => round($items->sum('net_revenue'), 2),
                ])
                ->sortBy('sort')
                ->values()
                ->map(fn ($item) => [
                    'label' => $item['label'],
                    'quantity_sold' => $item['quantity_sold'],
                    'sales_amount' => $item['sales_amount'],
                    'net_revenue' => $item['net_revenue'],
                ])
                ->all(),
        ];
    }

    private function buildPaymentCollectionAnalytics($rows): array
    {
        $normalizedRows = collect($rows)->map(function ($row) {
            $date = $row->payment_date ? Carbon::parse($row->payment_date) : null;

            return [
                'payment_mode' => LeadProduct::PAYMENT_MODES[$row->payment_mode] ?? ucwords(str_replace('_', ' ', (string) $row->payment_mode)),
                'customer_name' => $row->customer_name ?: 'Unknown Customer',
                'received_by' => $row->received_by ?: 'Unknown User',
                'month_label' => $date?->format('M Y') ?: 'Unknown',
                'month_sort' => $date?->format('Y-m') ?: '9999-99',
                'total_amount' => (float) ($row->total_amount ?? 0),
                'received_amount' => (float) ($row->received_amount ?? 0),
                'outstanding_amount' => (float) ($row->outstanding_amount ?? 0),
            ];
        });

        return [
            'monthly_trend' => $normalizedRows
                ->groupBy('month_sort')
                ->map(fn ($items) => [
                    'label' => $items->first()['month_label'],
                    'sort' => $items->first()['month_sort'],
                    'received_amount' => round($items->sum('received_amount'), 2),
                    'outstanding_amount' => round($items->sum('outstanding_amount'), 2),
                    'count' => $items->count(),
                ])
                ->sortBy('sort')
                ->values()
                ->map(fn ($item) => [
                    'label' => $item['label'],
                    'received_amount' => $item['received_amount'],
                    'outstanding_amount' => $item['outstanding_amount'],
                    'count' => $item['count'],
                ])
                ->all(),
            'payment_modes' => $normalizedRows
                ->groupBy('payment_mode')
                ->map(fn ($items, $label) => [
                    'label' => $label,
                    'received_amount' => round($items->sum('received_amount'), 2),
                    'count' => $items->count(),
                ])
                ->sortByDesc('received_amount')
                ->values()
                ->all(),
            'customers' => $normalizedRows
                ->groupBy('customer_name')
                ->map(fn ($items, $label) => [
                    'label' => $label,
                    'received_amount' => round($items->sum('received_amount'), 2),
                    'outstanding_amount' => round($items->sum('outstanding_amount'), 2),
                    'count' => $items->count(),
                ])
                ->sortByDesc('received_amount')
                ->take(6)
                ->values()
                ->all(),
            'collectors' => $normalizedRows
                ->groupBy('received_by')
                ->map(fn ($items, $label) => [
                    'label' => $label,
                    'received_amount' => round($items->sum('received_amount'), 2),
                    'count' => $items->count(),
                ])
                ->sortByDesc('received_amount')
                ->take(6)
                ->values()
                ->all(),
        ];
    }

    private function paymentCollectionCustomerOptions()
    {
        $query = Lead::query()
            ->join('lead_product_payments', 'lead_product_payments.lead_id', '=', 'leads.id')
            ->select([
                'leads.id',
                DB::raw('COALESCE(NULLIF(leads.contact_name, ""), NULLIF(leads.company_name, ""), CONCAT("Lead #", leads.id)) as customer_name'),
            ])
            ->distinct()
            ->orderBy('customer_name');

        $companyId = $this->visibility->companyIdFor();
        $visibleUserIds = $this->visibility->visibleUserIds();

        if ($companyId) {
            $query->where('leads.company_id', $companyId);
        }

        if ($visibleUserIds !== null) {
            $query->whereIn('leads.assigned_to', $visibleUserIds);
        }

        return $query->get();
    }

    private function revenueComparisonFilterOptions(): array
    {
        $currentYear = now()->year;
        $productOptions = Product::query()->orderBy('package_name');
        $this->visibility->applyProductVisibility($productOptions);

        return [
            'period_types' => [
                'month' => 'Month',
                'quarter' => 'Quarter',
                'year' => 'Year',
            ],
            'years' => collect(range($currentYear - 5, $currentYear + 1))->sortDesc()->values()->all(),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => Carbon::create()->month($month)->format('F'),
            ])->all(),
            'quarters' => [
                1 => 'Quarter 1',
                2 => 'Quarter 2',
                3 => 'Quarter 3',
                4 => 'Quarter 4',
            ],
            'products' => $productOptions->get(['id', 'package_name', 'product_name', 'sku']),
        ];
    }

    private function normalizeRevenueComparisonFilters(Request $request): array
    {
        $now = now();
        $periodType = (string) $request->input('period_type', 'month');

        return [
            'period_type' => in_array($periodType, ['month', 'quarter', 'year'], true) ? $periodType : 'month',
            'year' => (int) $request->input('year', $now->year),
            'month' => max(1, min(12, (int) $request->input('month', $now->month))),
            'quarter' => max(1, min(4, (int) $request->input('quarter', (int) ceil($now->month / 3)))),
            'product_id' => $request->filled('product_id') ? (int) $request->input('product_id') : null,
        ];
    }

    private function buildRevenueComparisonData(array $filters): array
    {
        [$currentStart, $currentEnd, $currentLabel] = $this->resolveRevenueComparisonRange($filters, false);
        [$previousStart, $previousEnd, $previousLabel] = $this->resolveRevenueComparisonRange($filters, true);

        $currentMetrics = $this->revenueMetricsForRange($currentStart, $currentEnd, $filters['product_id']);
        $previousMetrics = $this->revenueMetricsForRange($previousStart, $previousEnd, $filters['product_id']);

        $differenceAmount = round($currentMetrics['value'] - $previousMetrics['value'], 2);
        $differencePercent = $previousMetrics['value'] > 0
            ? round(($differenceAmount / $previousMetrics['value']) * 100, 2)
            : ($currentMetrics['value'] > 0 ? 100.00 : 0.00);
        $growthPercent = $previousMetrics['count'] > 0
            ? round((($currentMetrics['count'] - $previousMetrics['count']) / $previousMetrics['count']) * 100, 2)
            : ($currentMetrics['count'] > 0 ? 100.00 : 0.00);

        return [
            'cards' => [
                'current_value' => $currentMetrics['value'],
                'current_count' => $currentMetrics['count'],
                'difference_amount' => $differenceAmount,
                'growth_percent' => $growthPercent,
            ],
            'table' => [
                'period_label' => $currentLabel,
                'current_period_label' => $currentLabel,
                'previous_period_label' => $previousLabel,
                'current_period_value' => $currentMetrics['value'],
                'current_period_count' => $currentMetrics['count'],
                'previous_period_value' => $previousMetrics['value'],
                'previous_period_count' => $previousMetrics['count'],
                'difference_amount' => $differenceAmount,
                'difference_percent' => $differencePercent,
                'growth_percent' => $growthPercent,
            ],
            'analytics' => [
                'comparison_chart' => [
                    ['label' => $previousLabel, 'value' => $previousMetrics['value'], 'count' => $previousMetrics['count']],
                    ['label' => $currentLabel, 'value' => $currentMetrics['value'], 'count' => $currentMetrics['count']],
                ],
                'trend_chart' => $this->revenueTrendAnalytics($filters),
            ],
        ];
    }

    private function resolveRevenueComparisonRange(array $filters, bool $previous = false): array
    {
        return match ($filters['period_type']) {
            'year' => $this->resolveRevenueYearRange($filters['year'], $previous),
            'quarter' => $this->resolveRevenueQuarterRange($filters['year'], $filters['quarter'], $previous),
            default => $this->resolveRevenueMonthRange($filters['year'], $filters['month'], $previous),
        };
    }

    private function resolveRevenueMonthRange(int $year, int $month, bool $previous = false): array
    {
        $date = Carbon::create($year, $month, 1);

        if ($previous) {
            $date->subMonth();
        }

        return [
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth(),
            $date->format('F Y'),
        ];
    }

    private function resolveRevenueQuarterRange(int $year, int $quarter, bool $previous = false): array
    {
        $startMonth = (($quarter - 1) * 3) + 1;
        $date = Carbon::create($year, $startMonth, 1);

        if ($previous) {
            $date->subQuarter();
        }

        return [
            $date->copy()->startOfQuarter(),
            $date->copy()->endOfQuarter(),
            'Q' . (int) ceil($date->month / 3) . ' ' . $date->year,
        ];
    }

    private function resolveRevenueYearRange(int $year, bool $previous = false): array
    {
        $date = Carbon::create($year, 1, 1);

        if ($previous) {
            $date->subYear();
        }

        return [
            $date->copy()->startOfYear(),
            $date->copy()->endOfYear(),
            (string) $date->year,
        ];
    }

    private function revenueMetricsForRange(Carbon $start, Carbon $end, ?int $productId = null): array
    {
        $query = LeadProduct::query()
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(total_price), 0) as total_value')
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()]);

        $this->visibility->applyLeadRelationVisibility($query);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $row = $query->first();

        return [
            'count' => (int) ($row->total_count ?? 0),
            'value' => round((float) ($row->total_value ?? 0), 2),
        ];
    }

    private function revenueTrendAnalytics(array $filters): array
    {
        return collect(range(5, 0))->reverse()->map(function ($index) use ($filters) {
            $shiftedFilters = $filters;

            if ($filters['period_type'] === 'year') {
                $shiftedFilters['year'] = $filters['year'] - $index;
            } elseif ($filters['period_type'] === 'quarter') {
                $baseDate = Carbon::create($filters['year'], (($filters['quarter'] - 1) * 3) + 1, 1)->subQuarters($index);
                $shiftedFilters['year'] = $baseDate->year;
                $shiftedFilters['quarter'] = (int) ceil($baseDate->month / 3);
            } else {
                $baseDate = Carbon::create($filters['year'], $filters['month'], 1)->subMonths($index);
                $shiftedFilters['year'] = $baseDate->year;
                $shiftedFilters['month'] = $baseDate->month;
            }

            [$start, $end, $label] = $this->resolveRevenueComparisonRange($shiftedFilters, false);
            $metrics = $this->revenueMetricsForRange($start, $end, $filters['product_id']);

            return [
                'label' => $label,
                'value' => $metrics['value'],
                'count' => $metrics['count'],
            ];
        })->values()->all();
    }
}
