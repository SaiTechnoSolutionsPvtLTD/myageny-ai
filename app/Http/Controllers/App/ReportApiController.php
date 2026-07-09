<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\DataVisibilityService;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\ProductionInitiation;

class ReportApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function leadsSummaryApi(Request $request): JsonResponse
    {
        try {
            $defaultFromDate = now()->startOfMonth()->toDateString();
            $defaultToDate = now()->endOfMonth()->toDateString();

            // Same filters as the web form — validated so the mobile app gets
            // clean 422 responses instead of silently-wrong queries.
            $request->validate([
                'lead_source' => 'nullable|string|max:255',
                'lead_status' => 'nullable|string|max:255',
                'assigned_to' => 'nullable|integer',
                'branch_id'   => 'nullable|integer',
                'product_id'  => 'nullable|integer',
                'date_from'   => 'nullable|date',
                'date_to'     => 'nullable|date',
                'page'        => 'nullable|integer|min:1',
                'per_page'    => 'nullable|integer|min:1|max:200',
            ]);

            $this->applyDefaultDateRange($request, $defaultFromDate, $defaultToDate);

            $query = $this->buildLeadsSummaryQuery($request);

            // Full filtered set — needed for accurate totals + analytics,
            // exactly like the web controller does.
            $allRows = $query->get();

            $perPage = (int) $request->input('per_page', 100);
            $reportRows = $query->paginate($perPage)->withQueryString();

            $leadProductIds = $allRows->pluck('lead_product_id')->filter()->unique()->toArray();
            $totalPaid = 0;
            if (!empty($leadProductIds)) {
                $totalPaid = (float) DB::table('lead_product_payments')
                    ->whereIn('lead_product_id', $leadProductIds)
                    ->sum('amount');
            }

            $totalCost = (float) $allRows->sum('total_price');
            $summary = [
                'rows'          => $allRows->count(),
                'total_cost'    => round($totalCost, 2),
                'received_cost' => round($totalPaid, 2),
                'pending_cost'  => round(max(0, $totalCost - $totalPaid), 2),
            ];

            // Unchanged analytics builder — same shape the web Chart.js code consumes
            // (monthly_trend, sources, statuses, owners, products).
            $analytics = $this->buildLeadsSummaryAnalytics($allRows);

            $sourceOptions = LeadSource::query()->orderBy('name')->pluck('name')->values();
            $statusOptions = LeadStatus::query()->orderBy('name')->pluck('name')->values();
            $users = $this->visibility->visibleAssignableUsers();

            $productOptions = Product::query()->orderBy('package_name');
            $this->visibility->applyProductVisibility($productOptions);
            $products = $productOptions->get(['id', 'package_name', 'product_name']);

            $branches = Branch::query()->orderBy('name')->get(['id', 'name']);

            $rowsData = $reportRows->getCollection()->map(function ($row) {
                $entryDate = $row->lead_date ?? optional($row->lead_created_at)?->toDateString();
                $entryCarbon = $entryDate ? Carbon::parse($entryDate) : null;
                $convertedCarbon = $row->converted_at ? Carbon::parse($row->converted_at) : null;
                $leadStatus = $row->product_lead_status ?: $row->base_lead_status;
                $receivedAmount = (float) ($row->amount_paid ?? 0);
                $pendingCost = max(0, (float) ($row->total_price ?? 0) - $receivedAmount);

                return [
                    'lead_id'        => $row->lead_id,
                    'lead_code'      => 'LD-' . str_pad((string) $row->lead_id, 4, '0', STR_PAD_LEFT),
                    'name'           => $row->contact_name ?: null,
                    'email'          => $row->email ?: null,
                    'mobile_number'  => $row->mobile_number ?: null,
                    'lead_source'    => $row->lead_source ?: null,
                    'lead_status'    => $leadStatus ?: null,
                    'product_name'   => $row->product_name ?: null,
                    'entry_date'     => $entryCarbon?->toDateString(),
                    'converted_date' => $convertedCarbon?->toDateString(),
                    'total_cost'     => round((float) ($row->total_price ?? 0), 2),
                    'received_cost'  => round($receivedAmount, 2),
                    'pending_cost'   => round($pendingCost, 2),
                    'allocated_to'   => $row->allocated_to_name ?: null,
                    'lead_age'       => $entryCarbon ? $entryCarbon->diffForHumans(now(), true) : null,
                ];
            })->values();

            return response()->json([
                'status'  => true,
                'message' => 'Leads summary report fetched successfully.',
                'data'    => $rowsData,
                'summary' => $summary,
                'analytics' => $analytics,
                'filters' => [
                    'sources'  => $sourceOptions,
                    'statuses' => $statusOptions,
                    'users'    => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values(),
                    'products' => $products->map(fn($p) => [
                        'id'   => $p->id,
                        'name' => $p->package_name ?: $p->product_name,
                    ])->values(),
                    'branches' => $branches->map(fn($b) => ['id' => $b->id, 'name' => $b->name])->values(),
                ],
                'pagination' => [
                    'current_page' => $reportRows->currentPage(),
                    'last_page'    => $reportRows->lastPage(),
                    'per_page'     => $reportRows->perPage(),
                    'total'        => $reportRows->total(),
                    'from'         => $reportRows->firstItem(),
                    'to'           => $reportRows->lastItem(),
                ],
                'meta' => [
                    'default_from_date' => $defaultFromDate,
                    'default_to_date'   => $defaultToDate,
                    'applied_from_date' => $request->input('date_from', $defaultFromDate),
                    'applied_to_date'   => $request->input('date_to', $defaultToDate),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid filters supplied.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to load the leads summary report right now.',
            ], 500);
        }
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
                ->map(fn($item) => [
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

    private function buildLeadsSummaryQuery(Request $request)
    {
        $convertedSubquery = ProductionInitiation::query()
            ->selectRaw('lead_product_id, MIN(created_at) as converted_at')
            ->groupBy('lead_product_id');

        $query = Lead::query()
            ->leftJoin('lead_products', 'lead_products.lead_id', '=', 'leads.id')
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.lead_source')
            ->leftJoin('products', 'products.id', '=', 'lead_products.product_id')
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
                'lead_sources.name as lead_source',
                'leads.lead_status as base_lead_status',
                'leads.lead_date',
                'leads.created_at as lead_created_at',
                'lead_products.id as lead_product_id',
                'lead_products.product_id',
                'products.package_name as product_name',
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
}
