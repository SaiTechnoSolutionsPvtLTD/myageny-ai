<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\ProductionCountReport;
use App\Models\ProductionInitiation;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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
            [
                'title' => 'Branch Wise Comparison Report',
                'description' => 'Compare lead volume, revenue collection, sources, and status stages across multiple branches.',
                'theme' => 'branch',
                'status' => 'Ready for setup',
                'route' => route('reports.crm.branch-comparison'),
            ],
            [
                'title' => 'SMM Report',
                'description' => 'Track Social Media Marketing deliverables — committed poster/video counts, Design & DM team completions, overdue items, and status per lead account.',
                'theme' => 'smm',
                'status' => 'Ready for setup',
                'route' => route('reports.crm.smm'),
            ],
        ];

        return view('pages.reports.crm.index', compact('reports'));
    }

    public function leadsSummary(Request $request): View
    {
        $query = $this->buildLeadsSummaryQuery($request);

        // Get all data for analytics and summary
        $allRows = $query->get();

        // Paginate for display (100 records per page for better data visibility)
        $reportRows = $query->paginate(100)->withQueryString();
        $analyticsRows = $allRows;

        $summaryRows = $allRows;
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
            ->pluck('name', 'id');

        $statusOptions = LeadStatus::query()
            ->orderBy('name')
            ->pluck('name', 'id');

        $users = $this->visibility->visibleAssignableUsers();

        $productOptions = Product::query()
            ->orderBy('package_name');
        $this->visibility->applyProductVisibility($productOptions);
        $products = $productOptions->get(['id', 'package_name', 'product_name']);

        $branches = \App\Models\Branch::query()->orderBy('name')->get(['id', 'name']);

        $filterPanelOpen =
            $request->filled('lead_source')
            || $request->filled('lead_status')
            || $request->filled('assigned_to')
            || $request->filled('product_id')
            || $request->filled('branch_id')
            || $request->filled('date_from')
            || $request->filled('date_to');

        return view('pages.reports.crm.leads-summary', compact(
            'reportRows',
            'summary',
            'analytics',
            'sourceOptions',
            'statusOptions',
            'users',
            'products',
            'branches',
            'filterPanelOpen'
        ));
    }

    public function exportLeadsSummary(Request $request): Response
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $request->merge([
            'date_from' => $request->input('date_from', $defaultFromDate),
            'date_to'   => $request->input('date_to', $defaultToDate),
        ]);

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

        $branches = \App\Models\Branch::query()->orderBy('name')->get(['id', 'name']);

        $filterPanelOpen =
            $request->filled('product_id')
            || $request->filled('branch_id')
            || $request->filled('date_from')
            || $request->filled('date_to');

        return view('pages.reports.crm.product-wise', compact(
            'reportRows',
            'summary',
            'analytics',
            'products',
            'branches',
            'filterPanelOpen'
        ));
    }

    public function exportProductWise(Request $request): Response
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $request->merge([
            'date_from' => $request->input('date_from', $defaultFromDate),
            'date_to'   => $request->input('date_to', $defaultToDate),
        ]);

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
        $branches = \App\Models\Branch::query()->orderBy('name')->get(['id', 'name']);

        $filterPanelOpen =
            $request->filled('customer_id')
            || $request->filled('payment_mode')
            || $request->filled('branch_id')
            || $request->filled('date_from')
            || $request->filled('date_to');

        return view('pages.reports.crm.payment-collection', compact(
            'reportRows',
            'summary',
            'analytics',
            'paymentModes',
            'customers',
            'branches',
            'filterPanelOpen'
        ));
    }

    public function exportPaymentCollection(Request $request): Response
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->endOfMonth()->toDateString();

        $request->merge([
            'date_from' => $request->input('date_from', $defaultFromDate),
            'date_to'   => $request->input('date_to', $defaultToDate),
        ]);

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

    private function applyDefaultDateRange(Request $request, string $defaultFromDate, string $defaultToDate): ?RedirectResponse
    {
        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            return redirect()->to(
                $request->fullUrlWithQuery([
                    'date_from' => $defaultFromDate,
                    'date_to'   => $defaultToDate,
                ])
            );
        }

        return null;
    }

    private function buildLeadsSummaryQuery(Request $request)
    {
        $convertedSubquery = ProductionInitiation::query()
            ->selectRaw('lead_product_id, MIN(created_at) as converted_at')
            ->groupBy('lead_product_id');

        $query = Lead::query()
            ->leftJoin('lead_products', 'lead_products.lead_id', '=', 'leads.id')
            ->leftJoin('lead_sources as lead_source_table', 'lead_source_table.id', '=', 'leads.lead_source_id')
            ->leftJoin('lead_statuses as lead_status_table', 'lead_status_table.id', '=', 'leads.lead_status_id')
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
                // Prefer FK-resolved name, fall back to legacy string column
                DB::raw('COALESCE(lead_source_table.name, leads.lead_source) as lead_source'),
                DB::raw('COALESCE(lead_status_table.name, leads.lead_status) as base_lead_status'),
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
            // Filter accepts either an ID (new) or a name string (legacy)
            $val = $request->lead_source;
            if (is_numeric($val)) {
                $query->where('leads.lead_source_id', (int) $val);
            } else {
                $query->where('lead_source_table.name', $val);
            }
        }

        if ($request->filled('lead_status')) {
            $val = $request->lead_status;
            if (is_numeric($val)) {
                $query->where(function ($statusQuery) use ($val) {
                    $statusQuery
                        ->where('leads.lead_status_id', (int) $val)
                        ->orWhere('lead_products.lead_status_id', (int) $val);
                });
            } else {
                $query->where(function ($statusQuery) use ($val) {
                    $statusQuery
                        ->where('lead_status_table.name', $val)
                        ->orWhere('product_lead_statuses.name', $val);
                });
            }
        }

        if ($request->filled('assigned_to')) {
            $query->where('leads.assigned_to', $request->assigned_to);
        }

        if ($request->filled('product_id')) {
            $query->where('lead_products.product_id', $request->product_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('leads.branch_id', $request->branch_id);
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

        if ($request->filled('branch_id')) {
            $query->where('leads.branch_id', $request->branch_id);
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

        if ($request->filled('branch_id')) {
            $query->where('leads.branch_id', $request->branch_id);
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
        $branches = \App\Models\Branch::query()->orderBy('name')->get(['id', 'name']);

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
            'branches' => $branches,
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
            'branch_id' => $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
        ];
    }

    private function buildRevenueComparisonData(array $filters): array
    {
        [$currentStart, $currentEnd, $currentLabel] = $this->resolveRevenueComparisonRange($filters, false);
        [$previousStart, $previousEnd, $previousLabel] = $this->resolveRevenueComparisonRange($filters, true);

        $currentMetrics = $this->revenueMetricsForRange($currentStart, $currentEnd, $filters['product_id'], $filters['branch_id']);
        $previousMetrics = $this->revenueMetricsForRange($previousStart, $previousEnd, $filters['product_id'], $filters['branch_id']);

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

    private function revenueMetricsForRange(Carbon $start, Carbon $end, ?int $productId = null, ?int $branchId = null): array
    {
        $query = LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->selectRaw('COUNT(lead_products.id) as total_count, COALESCE(SUM(lead_products.total_price), 0) as total_value')
            ->whereBetween('lead_products.created_at', [$start->toDateTimeString(), $end->toDateTimeString()]);

        $this->visibility->applyLeadRelationVisibility($query);

        if ($productId) {
            $query->where('lead_products.product_id', $productId);
        }

        if ($branchId) {
            $query->where('leads.branch_id', $branchId);
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
            $metrics = $this->revenueMetricsForRange($start, $end, $filters['product_id'], $filters['branch_id']);

            return [
                'label' => $label,
                'value' => $metrics['value'],
                'count' => $metrics['count'],
            ];
        })->values()->all();
    }

    public function branchComparison(Request $request): View
    {
        [$dateFrom, $dateTo, $periodLabel, $periodType] = $this->resolvePeriodRange($request);

        $filterOptions = [
            'period_types' => [
                'custom' => 'Custom Date Range',
                'month' => 'Monthly',
                'quarter' => 'Quarterly',
                'year' => 'Yearly',
            ],
            'years' => collect(range(now()->year - 5, now()->year + 1))->sortDesc()->values()->all(),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => Carbon::create()->month($month)->format('F'),
            ])->all(),
            'quarters' => [
                1 => 'Quarter 1',
                2 => 'Quarter 2',
                3 => 'Quarter 3',
                4 => 'Quarter 4',
            ],
        ];

        $selectedFilters = [
            'period_type' => $periodType,
            'year' => (int) $request->input('year', now()->year),
            'month' => (int) $request->input('month', now()->month),
            'quarter' => (int) $request->input('quarter', ceil(now()->month / 3)),
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', now()->endOfMonth()->toDateString()),
        ];

        $comparisonData = $this->buildBranchComparisonData($dateFrom, $dateTo);

        return view('pages.reports.crm.branch-comparison', compact(
            'filterOptions',
            'selectedFilters',
            'comparisonData',
            'periodLabel',
            'dateFrom',
            'dateTo'
        ));
    }

    public function exportBranchComparison(Request $request): Response
    {
        [$dateFrom, $dateTo, $periodLabel] = $this->resolvePeriodRange($request);
        $comparisonData = $this->buildBranchComparisonData($dateFrom, $dateTo);

        $html = view('pages.reports.crm.branch-comparison-export', [
            'rows' => $comparisonData['rows'],
            'allSources' => $comparisonData['all_sources'],
            'allStatuses' => $comparisonData['all_statuses'],
            'periodLabel' => $periodLabel,
        ])->render();

        $fileName = 'branch_wise_comparison_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function resolvePeriodRange(Request $request): array
    {
        $now = now();
        $periodType = $request->input('period_type', 'month');

        if ($periodType === 'custom') {
            $dateFrom = $request->input('date_from', $now->startOfMonth()->toDateString());
            $dateTo = $request->input('date_to', $now->endOfMonth()->toDateString());
            $label = Carbon::parse($dateFrom)->format('d M Y') . ' to ' . Carbon::parse($dateTo)->format('d M Y');
        } elseif ($periodType === 'quarter') {
            $year = (int) $request->input('year', $now->year);
            $quarter = (int) $request->input('quarter', ceil($now->month / 3));
            $startMonth = (($quarter - 1) * 3) + 1;
            $start = Carbon::create($year, $startMonth, 1)->startOfQuarter();
            $end = $start->copy()->endOfQuarter();
            $dateFrom = $start->toDateString();
            $dateTo = $end->toDateString();
            $label = 'Q' . $quarter . ' ' . $year;
        } elseif ($periodType === 'year') {
            $year = (int) $request->input('year', $now->year);
            $start = Carbon::create($year, 1, 1)->startOfYear();
            $end = $start->copy()->endOfYear();
            $dateFrom = $start->toDateString();
            $dateTo = $end->toDateString();
            $label = (string) $year;
        } else {
            $year = (int) $request->input('year', $now->year);
            $month = (int) $request->input('month', $now->month);
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $dateFrom = $start->toDateString();
            $dateTo = $end->toDateString();
            $label = $start->format('F Y');
        }

        return [$dateFrom, $dateTo, $label, $periodType];
    }

    private function buildBranchComparisonData(string $dateFrom, string $dateTo): array
    {
        $companyId = $this->visibility->companyIdFor();
        $branchesQuery = \App\Models\Branch::query()->orderBy('name');
        if ($companyId) {
            $branchesQuery->where('company_id', $companyId);
        }
        $branches = $branchesQuery->get(['id', 'name']);

        // 1. Total Leads count per branch
        $leadCounts = DB::table('leads')
            ->select('branch_id', DB::raw('COUNT(*) as total_leads'))
            ->whereBetween('lead_date', [$dateFrom, $dateTo])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->groupBy('branch_id')
            ->pluck('total_leads', 'branch_id')
            ->toArray();

        // 2. Converted Leads count per branch
        $convertedCounts = DB::table('lead_products')
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->select('leads.branch_id', DB::raw('COUNT(DISTINCT leads.id) as converted_leads'))
            ->where('lead_products.product_status', 'converted')
            ->whereBetween('lead_products.updated_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->when($companyId, fn($q) => $q->where('leads.company_id', $companyId))
            ->groupBy('leads.branch_id')
            ->pluck('converted_leads', 'branch_id')
            ->toArray();

        // 3. Total Revenue (Sales contract value) per branch
        $revenueAmounts = DB::table('lead_products')
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->select('leads.branch_id', DB::raw('SUM(COALESCE(lead_products.total_price, 0)) as revenue'))
            ->whereBetween('lead_products.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->when($companyId, fn($q) => $q->where('leads.company_id', $companyId))
            ->groupBy('leads.branch_id')
            ->pluck('revenue', 'branch_id')
            ->toArray();

        // 4. Received revenue (Payments collected) per branch
        $receivedAmounts = DB::table('lead_product_payments')
            ->join('leads', 'leads.id', '=', 'lead_product_payments.lead_id')
            ->select('leads.branch_id', DB::raw('SUM(COALESCE(lead_product_payments.amount, 0)) as received'))
            ->whereBetween('lead_product_payments.payment_date', [$dateFrom, $dateTo])
            ->when($companyId, fn($q) => $q->where('leads.company_id', $companyId))
            ->groupBy('leads.branch_id')
            ->pluck('received', 'branch_id')
            ->toArray();

        // 5. Lead Source distribution per branch
        $sourceStats = DB::table('leads')
            ->select('branch_id', 'lead_source', DB::raw('COUNT(*) as count'))
            ->whereBetween('lead_date', [$dateFrom, $dateTo])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->groupBy('branch_id', 'lead_source')
            ->get();

        // 6. Lead Status distribution per branch
        $statusStats = DB::table('leads')
            ->select('branch_id', 'lead_status', DB::raw('COUNT(*) as count'))
            ->whereBetween('lead_date', [$dateFrom, $dateTo])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->groupBy('branch_id', 'lead_status')
            ->get();

        $rows = [];
        $allSources = [];
        $allStatuses = [];

        foreach ($branches as $branch) {
            $branchId = $branch->id;
            $rows[$branchId] = [
                'branch_id' => $branchId,
                'branch_name' => $branch->name,
                'total_leads' => (int) ($leadCounts[$branchId] ?? 0),
                'converted_leads' => (int) ($convertedCounts[$branchId] ?? 0),
                'revenue' => (float) ($revenueAmounts[$branchId] ?? 0.0),
                'received' => (float) ($receivedAmounts[$branchId] ?? 0.0),
                'sources' => [],
                'statuses' => [],
            ];
        }

        foreach ($sourceStats as $stat) {
            $branchId = $stat->branch_id;
            if (isset($rows[$branchId])) {
                $sourceName = $stat->lead_source ?: 'Unknown';
                $rows[$branchId]['sources'][$sourceName] = (int) $stat->count;
                $allSources[$sourceName] = true;
            }
        }

        foreach ($statusStats as $stat) {
            $branchId = $stat->branch_id;
            if (isset($rows[$branchId])) {
                $statusName = $stat->lead_status ?: 'Unknown';
                $rows[$branchId]['statuses'][$statusName] = (int) $stat->count;
                $allStatuses[$statusName] = true;
            }
        }

        $allSources = array_keys($allSources);
        $allStatuses = array_keys($allStatuses);

        return [
            'rows' => array_values($rows),
            'all_sources' => $allSources,
            'all_statuses' => $allStatuses,
        ];
    }

    // ─── SMM Report ──────────────────────────────────────────────────────────────

    public function smmReport(Request $request): View
    {
        $companyId = $this->visibility->companyIdFor();

        // Filter options
        $leads = Lead::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'contact_name']);

        $products = Product::query()
            ->countWise()
            ->when($companyId, fn($q) => $q->where('products.company_id', $companyId))
            ->orderBy('package_name')
            ->get(['id', 'package_name']);

        $departments = Department::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = $this->buildSmmReportData($request, $companyId);

        $filters = [
            'date_from'   => $request->input('date_from', ''),
            'date_to'     => $request->input('date_to', ''),
            'lead_id'     => $request->input('lead_id'),
            'product_id'  => $request->input('product_id'),
            'status'      => $request->input('status'),
        ];

        return view('pages.reports.crm.smm-report', compact(
            'rows', 'filters', 'leads', 'products', 'departments'
        ));
    }

    public function exportSmmReport(Request $request): Response
    {
        $companyId = $this->visibility->companyIdFor();
        $rows = $this->buildSmmReportData($request, $companyId);

        $filters = [
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to'   => $request->input('date_to', now()->endOfMonth()->toDateString()),
        ];

        $html = view('pages.reports.crm.smm-report-export', compact('rows', 'filters'))->render();
        $fileName = 'smm_report_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function buildSmmReportData(Request $request, ?int $companyId): \Illuminate\Support\Collection
    {
        $dateFrom  = $request->input('date_from', '');
        $dateTo    = $request->input('date_to', '');
        $leadId    = $request->input('lead_id');
        $productId = $request->input('product_id');
        $status    = $request->input('status');

        // Fetch all users with their departments to map allocations
        $usersWithDept = DB::table('users as u')
            ->leftJoin('employee_onboardings as eo', function ($join) {
                $join->on('eo.portal_user_id', '=', 'u.id')
                     ->whereNull('eo.deleted_at');
            })
            ->leftJoin('departments as dep', 'dep.id', '=', 'eo.department_id')
            ->select([
                'u.id',
                'u.name',
                'dep.name as dept_name',
            ])
            ->get()
            ->keyBy('id');

        // Base query: one row per production_initiation (= one product order per lead)
        $query = DB::table('production_initiations as pi')
            ->join('leads as l', 'l.id', '=', 'pi.lead_id')
            ->join('lead_products as lp', 'lp.id', '=', 'pi.lead_product_id')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->leftJoin('departments as d', 'd.id', '=', 'pi.department_id')
            ->select([
                'pi.id as pi_id',
                'pi.lead_id',
                'pi.lead_product_id',
                'pi.product_id',
                'pi.department_id',
                'pi.project_delivery_date',
                'pi.project_execution_status',
                'pi.custom_form_data',
                'pi.created_at as initiated_at',
                'pi.tl_employee_allocations',
                'pi.project_allocated_employee_user_ids',
                'pi.project_allocated_tl_user_ids',
                'l.company_name',
                'l.contact_name',
                'p.package_name as product_name',
                'd.name as department_name',
            ])
            ->when($companyId, fn($q) => $q->where('pi.company_id', $companyId))
            ->where('p.count_wise_report', true)
            ->when($leadId, fn($q) => $q->where('pi.lead_id', $leadId))
            ->when($productId, fn($q) => $q->where('pi.product_id', $productId))
            ->orderBy('l.company_name')
            ->orderBy('pi.id');

        // Date filter against project delivery date
        if ($dateFrom) {
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereNull('pi.project_delivery_date')
                  ->orWhereBetween('pi.project_delivery_date', [$dateFrom, $dateTo]);
            });
        }

        $initiations = $query->get();

        // Gather all production count report rows for these initiations (per department)
        $piIds = $initiations->pluck('pi_id')->unique()->values()->all();

        $countReports = DB::table('production_count_reports as pcr')
            ->join('departments as dep', 'dep.id', '=', 'pcr.department_id')
            ->whereIn('pcr.production_initiation_id', $piIds)
            ->select([
                'pcr.production_initiation_id',
                'pcr.department_id',
                'dep.name as dept_name',
                'pcr.poster_count',
                'pcr.video_count',
                'pcr.allocated_user_ids',
                'pcr.status',
            ])
            ->get()
            ->groupBy('production_initiation_id');

        // Gather timesheet-completed poster/video counts per initiation + department
        $timesheetSums = DB::table('project_timesheets as pt')
            ->join('users as u', 'u.id', '=', 'pt.user_id')
            ->leftJoin('employee_onboardings as eo', function ($join) {
                $join->on('eo.portal_user_id', '=', 'pt.user_id')
                     ->whereNull('eo.deleted_at');
            })
            ->leftJoin('departments as dep_eo', 'dep_eo.id', '=', 'eo.department_id')
            ->leftJoin('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'pt.user_id')
                     ->where('mhr.model_type', '=', 'App\Models\User');
            })
            ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
            ->leftJoin('departments as dep_role', 'dep_role.id', '=', 'r.department_id')
            ->whereIn('pt.production_initiation_id', $piIds)
            ->select([
                'pt.production_initiation_id',
                DB::raw('COALESCE(dep_eo.name, dep_role.name) as dept_name'),
                DB::raw('SUM(COALESCE(pt.poster_count, 0)) as completed_posters'),
                DB::raw('SUM(COALESCE(pt.video_count, 0)) as completed_videos'),
                DB::raw('GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ", ") as persons'),
            ])
            ->groupBy('pt.production_initiation_id', DB::raw('COALESCE(dep_eo.name, dep_role.name)'))
            ->get()
            ->groupBy('production_initiation_id');

        $today = now()->toDateString();

        $rows = $initiations->map(function ($pi) use ($countReports, $timesheetSums, $today, $status, $usersWithDept) {
            $piId = $pi->pi_id;

            // Parse custom_form_data for start/end date and committed counts
            $formData = json_decode($pi->custom_form_data ?? '[]', true) ?? [];

            $startDate = null;
            $endDate   = null;
            $committedPostersFromForm = 0;
            $committedVideosFromForm  = 0;

            foreach ($formData as $field) {
                $key = strtolower(trim($field['field_name'] ?? ''));
                $label = strtolower(trim($field['label'] ?? ($field['key'] ?? '')));
                $value = trim((string) ($field['value'] ?? ''));

                if (in_array($key, ['start_date', 'startdate', 'start date', 'smm_start_date', 'Start Date', 'ovp_start_date'])) {
                    $startDate = $value ?: null;
                }
                if (in_array($key, ['end_date', 'enddate', 'end date', 'smm_end_date', 'End Date', 'ovp_end_date'])) {
                    $endDate = $value ?: null;
                }
                if ($label === 'number of posters' || $label === 'number of poster' || str_contains($key, 'number_of_posters') || str_contains($key, 'poster_count')) {
                    $committedPostersFromForm = (int) $value;
                }
                if ($label === 'number of videos' || $label === 'number of video' || str_contains($key, 'number_of_videos') || str_contains($key, 'video_count')) {
                    $committedVideosFromForm = (int) $value;
                }
            }

            // Tenure in months
            $tenure = null;
            if ($startDate && $endDate) {
                try {
                    $tenure = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate), true);
                    // $tenure = round($tenure, 1);
                } catch (\Exception $e) {}
            }

            // Month label from start date or delivery date
            $monthLabel = null;
            $dateForMonth = $startDate ?? $pi->project_delivery_date;
            if ($dateForMonth) {
                try {
                    $monthLabel = Carbon::parse($dateForMonth)->format('M Y');
                } catch (\Exception $e) {}
            }

            // Production count reports: split by "design" and "dm" in name
            $pcrs = $countReports->get($piId, collect());
            $designPcr = $pcrs->first(fn($r) => str_contains(strtolower($r->dept_name ?? ''), 'design'));
            $dmPcr     = $pcrs->first(fn($r) => str_contains(strtolower($r->dept_name ?? ''), 'digital') || str_contains(strtolower($r->dept_name ?? ''), 'dm') || str_contains(strtolower($r->dept_name ?? ''), 'marketing'));

            $designCommittedPosters = $designPcr ? (int) $designPcr->poster_count : 0;
            $designCommittedVideos  = $designPcr ? (int) $designPcr->video_count  : 0;
            $dmCommittedPosters     = $dmPcr     ? (int) $dmPcr->poster_count     : 0;
            $dmCommittedVideos      = $dmPcr     ? (int) $dmPcr->video_count      : 0;

            // Resolve main committed counts (fallback to custom form data if count report is 0)
            $mainCommittedPosters = $designCommittedPosters ?: ($dmCommittedPosters ?: $committedPostersFromForm);
            $mainCommittedVideos  = $designCommittedVideos  ?: ($dmCommittedVideos  ?: $committedVideosFromForm);

            // Both Design and DM teams get the same committed deliverables count for the project
            $designCommittedPosters = $mainCommittedPosters;
            $designCommittedVideos  = $mainCommittedVideos;
            $dmCommittedPosters     = $mainCommittedPosters;
            $dmCommittedVideos      = $mainCommittedVideos;

            $committedPosters = $mainCommittedPosters;
            $committedVideos  = $mainCommittedVideos;

            // Timesheet completions: split by design vs dm
            $tsSets = $timesheetSums->get($piId, collect());

            $designCompletedPosters = 0;
            $designCompletedVideos  = 0;
            $designPersonsList      = [];

            $dmCompletedPosters = 0;
            $dmCompletedVideos  = 0;
            $dmPersonsList      = [];

            foreach ($tsSets as $ts) {
                $tsDeptLower = strtolower($ts->dept_name ?? '');
                $tsPersons = array_filter(array_map('trim', explode(',', $ts->persons ?? '')));

                if (str_contains($tsDeptLower, 'design')) {
                    $designCompletedPosters += (int) $ts->completed_posters;
                    $designCompletedVideos  += (int) $ts->completed_videos;
                    $designPersonsList = array_merge($designPersonsList, $tsPersons);
                } elseif (str_contains($tsDeptLower, 'digital') || str_contains($tsDeptLower, 'dm') || str_contains($tsDeptLower, 'marketing')) {
                    $dmCompletedPosters += (int) $ts->completed_posters;
                    $dmCompletedVideos  += (int) $ts->completed_videos;
                    $dmPersonsList = array_merge($dmPersonsList, $tsPersons);
                } else {
                    // Fallback to project's department
                    $projDeptLower = strtolower($pi->department_name ?? '');
                    if (str_contains($projDeptLower, 'design')) {
                        $designCompletedPosters += (int) $ts->completed_posters;
                        $designCompletedVideos  += (int) $ts->completed_videos;
                        $designPersonsList = array_merge($designPersonsList, $tsPersons);
                    } elseif (str_contains($projDeptLower, 'digital') || str_contains($projDeptLower, 'dm') || str_contains($projDeptLower, 'marketing')) {
                        $dmCompletedPosters += (int) $ts->completed_posters;
                        $dmCompletedVideos  += (int) $ts->completed_videos;
                        $dmPersonsList = array_merge($dmPersonsList, $tsPersons);
                    } else {
                        // Default to Design
                        $designCompletedPosters += (int) $ts->completed_posters;
                        $designCompletedVideos  += (int) $ts->completed_videos;
                        $designPersonsList = array_merge($designPersonsList, $tsPersons);
                    }
                }
            }

            $designPersons = !empty($designPersonsList) ? implode(', ', array_unique($designPersonsList)) : '-';
            $dmPersons     = !empty($dmPersonsList)     ? implode(', ', array_unique($dmPersonsList))     : '-';

            // Pending = Committed − Done (from timesheets), clamped to 0
            $designPendingPosters = max(0, $designCommittedPosters - $designCompletedPosters);
            $designPendingVideos  = max(0, $designCommittedVideos  - $designCompletedVideos);
            $dmPendingPosters     = max(0, $dmCommittedPosters     - $dmCompletedPosters);
            $dmPendingVideos      = max(0, $dmCommittedVideos      - $dmCompletedVideos);

            // Allocated persons fallback using project_allocated_employee_user_ids and project_allocated_tl_user_ids
            $designAllocatedNames = [];
            $dmAllocatedNames     = [];

            $allocatedEmployeeIds = json_decode($pi->project_allocated_employee_user_ids ?? '[]', true) ?? [];
            $allocatedTlIds       = json_decode($pi->project_allocated_tl_user_ids ?? '[]', true) ?? [];
            $allocatedUserIds     = array_unique(array_filter(array_merge($allocatedEmployeeIds, $allocatedTlIds)));

            foreach ($allocatedUserIds as $uId) {
                $userDept = $usersWithDept->get($uId);
                if ($userDept) {
                    $uName = $userDept->name;
                    $uDeptLower = strtolower($userDept->dept_name ?? '');

                    if (str_contains($uDeptLower, 'design')) {
                        $designAllocatedNames[] = $uName;
                    } elseif (str_contains($uDeptLower, 'digital') || str_contains($uDeptLower, 'dm') || str_contains($uDeptLower, 'marketing')) {
                        $dmAllocatedNames[] = $uName;
                    } else {
                        // Fallback to project's department if user's department doesn't match Design/DM
                        $projDeptLower = strtolower($pi->department_name ?? '');
                        if (str_contains($projDeptLower, 'design')) {
                            $designAllocatedNames[] = $uName;
                        } elseif (str_contains($projDeptLower, 'digital') || str_contains($projDeptLower, 'dm') || str_contains($projDeptLower, 'marketing')) {
                            $dmAllocatedNames[] = $uName;
                        } else {
                            $designAllocatedNames[] = $uName;
                        }
                    }
                }
            }

            if ($designPersons === '-') {
                $designPersons = !empty($designAllocatedNames) ? implode(', ', array_unique($designAllocatedNames)) : '-';
            }
            if ($dmPersons === '-') {
                $dmPersons = !empty($dmAllocatedNames) ? implode(', ', array_unique($dmAllocatedNames)) : '-';
            }

            // Fallback to tl_employee_allocations if still '-'
            if ($designPersons === '-' && $pi->tl_employee_allocations) {
                try {
                    $allocs = json_decode($pi->tl_employee_allocations, true) ?? [];
                    $names  = collect($allocs)->pluck('name')->filter()->values()->implode(', ');
                    if ($names) $designPersons = $names;
                } catch (\Exception $e) {}
            }

            $completedPosters = $designCompletedPosters + $dmCompletedPosters;
            $completedVideos  = $designCompletedVideos + $dmCompletedVideos;
            $pendingPosters   = max(0, $committedPosters - $completedPosters);
            $pendingVideos    = max(0, $committedVideos - $completedVideos);

            // Status calculation
            $totalCommitted  = $committedPosters + $committedVideos;
            $totalCompleted  = $completedPosters + $completedVideos;
            $deliveryDate    = $pi->project_delivery_date ?? $endDate;

            $computedStatus = 'pending';
            if ($totalCommitted > 0 && $totalCompleted >= $totalCommitted) {
                $computedStatus = 'completed';
            } elseif ($deliveryDate && $deliveryDate < $today) {
                $computedStatus = 'overdue';
            }

            // Apply status filter
            if ($status && $computedStatus !== $status) {
                return null;
            }

            $accountName = trim(($pi->company_name ?? '') ?: (trim(($pi->contact_name ?? '') . ' ' . ($pi->last_name ?? ''))));

            return [
                'pi_id'                    => $piId,
                'lead_id'                  => $pi->lead_id,
                'month'                    => $monthLabel ?? '-',
                'account_name'             => $accountName ?: 'N/A',
                'product_name'             => $pi->product_name ?? '-',
                'start_date'               => $startDate,
                'end_date'                 => $endDate,
                'delivery_date'            => $pi->project_delivery_date,
                'tenure'                   => $tenure,
                'committed_posters'        => $committedPosters,
                'committed_videos'         => $committedVideos,
                'completed_posters'        => $completedPosters,
                'pending_posters'          => $pendingPosters,
                'completed_videos'         => $completedVideos,
                'pending_videos'           => $pendingVideos,
                'design_completed_posters' => $designCompletedPosters,
                'design_pending_posters'   => $designPendingPosters,
                'design_completed_videos'  => $designCompletedVideos,
                'design_pending_videos'    => $designPendingVideos,
                'design_persons'           => $designPersons,
                'dm_completed_posters'     => $dmCompletedPosters,
                'dm_pending_posters'       => $dmPendingPosters,
                'dm_completed_videos'      => $dmCompletedVideos,
                'dm_pending_videos'        => $dmPendingVideos,
                'dm_persons'               => $dmPersons,
                'status'                   => $computedStatus,
                'department_name'          => $pi->department_name ?? '-',
            ];
        })->filter()->values();

        return $rows;
    }
}
