<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\ScopesLeadStatusAndSourceToCompany;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\LeadCallUpdate;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadReminder;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Dashboard", description: "Mobile app dashboard endpoints")]

class DashboardController extends Controller
{
    use ScopesLeadStatusAndSourceToCompany;

    public function __construct(private readonly DataVisibilityService $visibility) {}

    // =========================================================================
    // INDEX — Full dashboard data (mirrors web admin dashboard)
    // =========================================================================

    #[OA\Get(
        path: "/api/mobile/dashboard",
        summary: "Get dashboard data",
        description: "Returns KPIs, pipeline funnel, source distribution, financials, today's follow-ups, pending reminders, recent leads, branch performance, team performance, and 6-month trend. All sections respect the same filters as the web dashboard.",
        security: [["sanctum" => []]],
        tags: ["Dashboard"],
        parameters: [
            new OA\Parameter(name: "branch_id",  in: "query", required: false, description: "Filter by branch ID",   schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "user_id",    in: "query", required: false, description: "Filter by user ID",     schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "stage",      in: "query", required: false, description: "Filter by lead stage",  schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "source",     in: "query", required: false, description: "Filter by lead source", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "date_from",  in: "query", required: false, description: "Start date (Y-m-d)",    schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "date_to",    in: "query", required: false, description: "End date (Y-m-d)",      schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "quick_date", in: "query", required: false, description: "Preset date range",     schema: new OA\Schema(type: "string", enum: ["today","yesterday","week","month","quarter","year"])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dashboard data",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Dashboard data fetched."),
                    new OA\Property(property: "data",    type: "object",  description: "See schema below"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 422, description: "Validation error",  content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    
    public function index(Request $request): JsonResponse
    {
        // ── Validate filter inputs ─────────────────────────────────
        $request->validate([
            'branch_id'  => ['nullable', 'exists:branches,id'],
            'user_id'    => ['nullable', 'exists:users,id'],
            'stage'      => ['nullable', Rule::in(Lead::statusKeys())],
            'source'     => ['nullable', Rule::in(Lead::sourceKeys())],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
            'quick_date' => ['nullable', 'in:all,today,yesterday,week,month,quarter,year,custom'],
        ]);

        // ── Resolve dates ──────────────────────────────────────────
        [$dateFrom, $dateTo] = $this->resolveDates($request);

        $branchId = $request->branch_id;
        $userId   = $request->user_id;
        $stage    = $request->stage;
        $source   = $request->source;

        // ── Base query factory ─────────────────────────────────────
        $base = function () use ($request, $branchId, $userId, $stage, $source, $dateFrom, $dateTo) {
            $query = Lead::query();

            $this->visibility->applyLeadVisibility($query, $request->user());

            return $query
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($userId, fn($q) => $q->where('assigned_to', $userId))
                ->when($stage, fn($q) => $q->where('lead_status', $stage))
                ->when($source, fn($q) => $q->where('lead_source_id', $source))
                ->when($dateFrom, fn($q) => $q->where(function($dq) use ($dateFrom) {
                    $dq->whereDate('lead_date', '>=', $dateFrom)
                      ->orWhereDate('created_at', '>=', $dateFrom);
                }))
                ->when($dateTo, fn($q) => $q->where(function($dq) use ($dateTo) {
                    $dq->whereDate('lead_date', '<=', $dateTo)
                      ->orWhereDate('created_at', '<=', $dateTo);
                }));
        };

        // ── 1. KPIs ───────────────────────────────────────────────
        $totalLeads    = (clone $base())->count();
        $wonQuery      = (clone $base())->converted();
        $lostLeads     = (clone $base())->where('lead_status', 'lost')->count();

        // Query converted products in the selected date range using converted_at (converted date)
        // matching SuperAdminDashboardController so won_leads (Active Customers) counts
        // unique leads whose products converted in the period or marked won in period
        $convertedStatusIds = LeadStatus::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) in (?, ?)', ['converted', 'won'])
                  ->orWhere('name', 'like', '%convert%');
            })
            ->pluck('id')
            ->toArray();

        $isConvertedProduct = function (LeadProduct $lp) use ($convertedStatusIds) {
            $status = strtolower(trim((string) $lp->product_status));
            return in_array($status, ['converted', 'won'])
                || ($lp->lead_status_id && in_array($lp->lead_status_id, $convertedStatusIds));
        };

        $convertedProductsQuery = LeadProduct::query()
            ->where(function ($q) use ($convertedStatusIds) {
                $q->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                  ->orWhereIn('lead_status_id', $convertedStatusIds);
            })
            ->whereHas('lead', function ($lq) use ($request, $branchId, $userId, $stage, $source) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId) $lq->where('branch_id', $branchId);
                if ($userId)   $lq->where('assigned_to', $userId);
                if ($stage)    $lq->where('lead_status', $stage);
                if ($source)   $lq->where('lead_source_id', $source);
            });

        if ($dateFrom) {
            $convertedProductsQuery->whereDate('converted_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $convertedProductsQuery->whereDate('converted_at', '<=', $dateTo);
        }

        $convertedProducts = $convertedProductsQuery->with('payments')->get();
        $convertedProductsCount = $convertedProducts->count();
        $convertedValue = (float) $convertedProducts->sum('total_price');

        $convertedLeadIdsInPeriod = $convertedProducts->pluck('lead_id')->unique();
        $wonLeadIds    = (clone $wonQuery)->pluck('id')->merge($convertedLeadIdsInPeriod)->unique();
        $wonLeads      = $wonLeadIds->count();
        $activeLeads   = max(0, $totalLeads - $wonLeads - $lostLeads);
        $pipelineValue = (float)(clone $base())->whereNotIn('lead_status', ['won', 'lost'])->sum('deal_value');
        $wonValue      = (float)(clone $base())->converted()->sum('deal_value');
        $highPriority  = (clone $base())->where('priority', 'high')->whereNotIn('lead_status', ['won', 'lost'])->count();
        $convRate      = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;

        // ── 2. Pipeline funnel from lead_products.lead_status_id ───
        $leadIds = (clone $base())->pluck('id');
        $lpProducts = LeadProduct::whereIn('lead_id', $leadIds)->with('payments')->get();

        $nonConvertedProducts = $lpProducts->reject($isConvertedProduct);

        $allLpProducts = $nonConvertedProducts->merge($convertedProducts)->unique('id');

        $productStatusFunnel = $this->buildProductStatusFunnel($leadIds, $request, $allLpProducts, $convertedProductsCount, $convertedStatusIds);
        $stageTotal  = $productStatusFunnel['total'];
        $stageFunnel = $productStatusFunnel['stages'];

        // ── 3. Source distribution ─────────────────────────────────
        $sourceCounts = [];
        $sourceTotal  = 0;
        $uniqueSources = LeadSource::withoutGlobalScope('company')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->unique(fn($s) => strtolower(trim($s->name)))
            ->values();

        foreach ($uniqueSources as $src) {
            $sName = strtolower(trim($src->name));
            $sameNameIds = LeadSource::withoutGlobalScope('company')->whereRaw('LOWER(name) = ?', [$sName])->pluck('id')->toArray();

            $count = (clone $base())
                ->where(function ($q) use ($sameNameIds, $src) {
                    $q->whereIn('lead_source_id', $sameNameIds)
                      ->orWhere('lead_source', $src->name);
                })
                ->count();
            $sourceTotal += $count;
            $sourceCounts[] = ['key' => (string) $src->id, 'label' => $src->name, 'count' => $count];
        }

        // Account for leads with unassigned/null sources so donut chart matches overall lead count
        $unassignedCount = max(0, $totalLeads - $sourceTotal);
        if ($unassignedCount > 0) {
            $sourceCounts[] = [
                'key'   => 'unassigned',
                'label' => 'Unassigned',
                'count' => $unassignedCount,
            ];
            $donutTotal = $totalLeads;
        } else {
            $donutTotal = $sourceTotal;
        }

        foreach ($sourceCounts as &$src) {
            $src['percent'] = $donutTotal > 0 ? round($src['count'] / $donutTotal * 100, 1) : 0;
        }
        unset($src);

        // ── 4. Financials ─────────────────────────────────────────
        $lpBase = $this->getLeadProductBaseQuery($request);
        $lpProducts = (clone $lpBase)->with('payments')->get();

        $convertedStatusIds = LeadProduct::convertedStatusIds();
        $isConvertedProduct = fn(LeadProduct $lp) => $lp->isConvertedProduct($convertedStatusIds);

        $convertedProducts = $lpProducts->filter($isConvertedProduct);
        $convertedValue    = (float) $convertedProducts->sum('total_price');
        $convertedCount    = $convertedProducts->count();

        // Amount Received / Amount Pending — Payment Financials cards.
        // $totalProductValue/$totalPaid/$totalPending were previously
        // referenced below (in the 'financials' response block) without
        // ever being assigned anywhere in this method — a pre-existing
        // bug where PHP silently treated the undefined variables as
        // null/0, which is also why these numbers could show as 0 or
        // drift out of sync with each other on mobile.
        //
        // Amount Received is sourced strictly from the Lead Products
        // Payment table (LeadProductPayment) — NOT the LeadProduct's own
        // amount_paid column/accessor. That column can be stale relative
        // to what's actually been logged as payments (e.g. an old
        // migrated/raw value), and summing the per-row clamped
        // getAmountPendingAttribute() (max(0, total_price - amount_paid))
        // silently inflates the total whenever any single converted
        // product's raw amount_paid overstates its real payments — a
        // negative per-row contribution gets clamped to 0 instead of
        // offsetting the total, which is exactly what was producing an
        // Amount Pending far larger than the Converted Products value.
        // Per the business rule Converted Products Amount = Amount
        // Received + Amount Pending, Pending is now the straight
        // aggregate difference, not a per-row sum.
        $convertedProductIds = $convertedProducts->pluck('id');
        $totalProductValue = (float) $lpProducts->sum('total_price');
        $totalPaid    = (float) LeadProductPayment::whereIn('lead_product_id', $convertedProductIds)->sum('amount');
        $totalPending = max(0, $convertedValue - $totalPaid);
        $payPct       = $convertedValue > 0 ? round($totalPaid / $convertedValue * 100, 1) : 0;

        // Total Received Amount (mirrors SuperAdminDashboardController) — sums
        // all collected payments matching active filters and date range.
        $paymentsQuery = LeadProductPayment::query()
            ->whereHas('lead', function ($lq) use ($request, $branchId, $userId, $stage, $source) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId) $lq->where('branch_id', $branchId);
                if ($userId)   $lq->where('assigned_to', $userId);
                if ($stage)    $lq->where('lead_status', $stage);
                if ($source)   $lq->where('lead_source', $source);
            });

        if ($dateFrom) {
            $paymentsQuery->whereDate('payment_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paymentsQuery->whereDate('payment_date', '<=', $dateTo);
        }
        $totalReceivedAmount = (float) $paymentsQuery->sum('amount');

        // Mirrors SuperAdminDashboardController's KPI block — these were
        // previously computed nowhere on the mobile side, so the "Converted
        // Products" / "Upcoming Amount" / "Converted Value" / "Converted
        // Percentage" Key Metrics cards always showed 0 on mobile even
        // though $lpProducts (above) already has everything needed.
        $upcomingAmount      = (float) $lpProducts->where('product_status', '!=', 'converted')->sum('total_price');
        $totalProductsCount  = $lpProducts->count();
        $convertedPercentage = $totalProductsCount > 0 ? round($convertedCount / $totalProductsCount * 100, 1) : 0;

        $leadProductIds = $lpProducts->pluck('id');
        $paymentByMode = LeadProductPayment::whereIn('lead_product_id', $leadProductIds)
            ->select('payment_mode', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as txn_count'))
            ->groupBy('payment_mode')
            ->orderByDesc('total')
            ->get()
            ->map(fn($pm) => [
                'mode'       => $pm->payment_mode,
                'mode_label' => LeadProduct::PAYMENT_MODES[$pm->payment_mode] ?? ucfirst($pm->payment_mode),
                'total'      => (float) $pm->total,
                'txn_count'  => (int)   $pm->txn_count,
            ]);

        $productStatusDist = [];
        foreach (LeadProduct::PRODUCT_STATUSES as $pkey => $plabel) {
            $cnt = $lpProducts->where('product_status', $pkey)->count();
            $productStatusDist[] = [
                'status' => $pkey,
                'label'  => $plabel,
                'count'  => $cnt,
                'config' => LeadProduct::PRODUCT_STATUS_CONFIG[$pkey] ?? [],
            ];
        }

        // ── 5. Recent call updates (last 15) ───────────────────────────
        // Dashboard card shows a bounded, most-recent-first slice — "Show
        // All" (mobile) / "Show all →" (web) is what opens the full,
        // unbounded, paginated Call Updates list.
        $todayFollowups = LeadCallUpdate::query()
            ->whereHas('lead', function ($q) use ($request, $branchId, $userId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());

                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                  ->when($userId,   fn($q2) => $q2->where('assigned_to', $userId))
                  ->when($stage,    fn($q2) => $q2->where('lead_status', $stage))
                  ->when($source,   fn($q2) => $q2->where('lead_source', $source));
            })
            ->with([
                'lead:id,company_name,contact_name,mobile_number,lead_status,branch_id,assigned_to',
                'lead.branch:id,name',
                'lead.assignedTo:id,name',
                'user:id,name',
                'outCome:id,name',
                'outComeSubCategory:id,name',
            ])
            ->latest('called_at')
            ->latest('id')
            ->take(15)
            ->get()
            ->map(fn($fu) => [
                'id'               => $fu->id,
                'called_at'        => $fu->called_at?->toISOString(),
                'called_at_formatted' => $fu->called_at?->format('d M, h:i A'),
                'call_type'        => $fu->call_type,
                'call_type_label'  => $fu->call_type_label,
                'outcome'          => $fu->outcome,
                'outcome_label'    => $fu->outCome?->name ?? ($fu->outcome_label ?: 'Call Update'),
                'outcome_subcategory' => $fu->outcome_subcategory,
                'outcome_subcategory_label' => $fu->outComeSubCategory?->name ?? $fu->outcome_subcategory_label,
                'outcome_color'    => $fu->outcome_color,
                'duration_minutes' => $fu->duration_minutes,
                'notes'            => $fu->notes,
                'next_follow_up'   => $fu->next_follow_up?->toDateString(),
                'logged_by'        => ['id' => $fu->user?->id, 'name' => $fu->user?->name],
                'lead' => [
                    'id'            => $fu->lead?->id,
                    'company_name'  => $fu->lead?->company_name,
                    'contact_name'  => $fu->lead?->contact_name,
                    'mobile_number' => $fu->lead?->mobile_number,
                    'lead_status'   => $fu->lead?->lead_status,
                    'branch'        => ['id' => $fu->lead?->branch?->id,     'name' => $fu->lead?->branch?->name],
                    'assigned_to'   => ['id' => $fu->lead?->assignedTo?->id, 'name' => $fu->lead?->assignedTo?->name],
                ],
            ]);

        // ── 6. Pending reminders today ────────────────────────────
        $currentUser = $request->user();
        $isUserAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();
        $effectiveUserId = $request->filled('user_id') ? (int) $request->user_id : ($isUserAdmin ? null : (int) $currentUser->id);

        $reminderQuery = fn() => LeadReminder::where('is_completed', false)
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());
                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                  ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                  ->when($stage, fn($q2) => $q2->where('lead_status', $stage))
                  ->when($source, fn($q2) => $q2->where('lead_source', $source));
            })
            ->when($effectiveUserId, function ($q) use ($effectiveUserId) {
                $q->where(function ($sub) use ($effectiveUserId) {
                    $sub->where('user_id', $effectiveUserId)
                        ->orWhereHas('lead', fn($lq) => $lq->where('assigned_to', $effectiveUserId));
                });
            });

        $overdueCount = (clone $reminderQuery())
            ->whereDate('remind_at', '<', today())
            ->count();

        $todayReminders = (clone $reminderQuery())
            ->whereDate('remind_at', today())
            ->with(['lead:id,company_name', 'user:id,name'])
            ->orderBy('remind_at')
            ->take(8)
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'title'       => $r->title,
                'description' => $r->description,
                // Naive "Y-m-d H:i:s" (no timezone offset) — toISOString()
                // appended a UTC offset that Dart's DateTime.parse() would
                // convert, shifting the displayed date back a day.
                'remind_at'      => optional($r->remind_at)->format('Y-m-d H:i:s'),
                // Explicit ->format() — the raw Carbon instance serializes to
                // UTC by default (Carbon::jsonSerialize()), which silently
                // shifted the displayed time back by the app's UTC+5:30 offset.
                'remainder_time' => optional($r->remainder_time)->format('H:i:s'),
                'type'        => $r->type,
                'type_label'  => $r->type_label,
                'type_icon'   => $r->type_icon,
                'priority'    => $r->priority,
                'is_overdue'  => $r->is_overdue,
                'user'        => ['id' => $r->user?->id, 'name' => $r->user?->name],
                'lead'        => ['id' => $r->lead?->id, 'company_name' => $r->lead?->company_name],
            ]);

        $overdueReminders = (clone $reminderQuery())
            ->whereDate('remind_at', '<', today())
            ->with(['lead:id,company_name', 'user:id,name'])
            ->orderBy('remind_at', 'desc')
            ->take(15)
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'title'       => $r->title,
                'description' => $r->description,
                'remind_at'      => optional($r->remind_at)->format('Y-m-d H:i:s'),
                // Explicit ->format() — the raw Carbon instance serializes to
                // UTC by default (Carbon::jsonSerialize()), which silently
                // shifted the displayed time back by the app's UTC+5:30 offset.
                'remainder_time' => optional($r->remainder_time)->format('H:i:s'),
                'type'        => $r->type,
                'type_label'  => $r->type_label,
                'type_icon'   => $r->type_icon,
                'priority'    => $r->priority,
                'is_overdue'  => true,
                'user'        => ['id' => $r->user?->id, 'name' => $r->user?->name],
                'lead'        => ['id' => $r->lead?->id, 'company_name' => $r->lead?->company_name],
            ]);

        // ── 7. Recent leads ───────────────────────────────────────
        $recentLeads = (clone $base())
            ->with(['branch:id,name', 'assignedTo:id,name'])
            ->latest('lead_date')
            ->take(8)
            ->get()
            ->map(fn($l) => [
                'id'                   => $l->id,
                'lead_number'          => 'LD-' . str_pad($l->id, 4, '0', STR_PAD_LEFT),
                'company_name'         => $l->company_name,
                'contact_name'         => $l->contact_name,
                'mobile_number'        => $l->mobile_number,
                'lead_date'            => $l->lead_date->toDateString(),
                'lead_source'          => $l->lead_source,
                'source_label'         => $l->source_label,
                'lead_status'          => $l->lead_status,
                'status_label'         => $l->status_label,
                'status_color'         => $l->status_color,
                'priority'             => $l->priority,
                'priority_label'       => $l->priority_label,
                'priority_color'       => $l->priority_color,
                'deal_value'           => (float) $l->deal_value,
                'deal_value_formatted' => $l->formatted_deal_value,
                'branch'               => ['id' => $l->branch?->id,     'name' => $l->branch?->name],
                'assigned_to'          => ['id' => $l->assignedTo?->id, 'name' => $l->assignedTo?->name],
            ]);

        // ── 8. Branch-wise performance ────────────────────────────
        $branchPerformanceQuery = Branch::where('is_active', true)
            ->when($request->user()?->company_id, fn($q, $companyId) => $q->where('company_id', $companyId));

        if (! $this->visibility->isCompanyWideUser($request->user())) {
            $branchPerformanceQuery->whereIn('id', $request->user()?->getMyBranchIds() ?? []);
        }

        $branchPerformance = $branchPerformanceQuery
            ->get()
            ->map(function ($branch) use ($request, $dateFrom, $dateTo) {
                $q = Lead::where('branch_id', $branch->id)
                    ->when($dateFrom, fn($q2) => $q2->whereDate('lead_date', '>=', $dateFrom))
                    ->when($dateTo,   fn($q2) => $q2->whereDate('lead_date', '<=', $dateTo));
                $this->visibility->applyLeadVisibility($q, $request->user());

                $total          = (clone $q)->count();
                $leadIds        = (clone $q)->pluck('id');
                $productConvCnt = LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->count();
                $productConvVal = (float) LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->sum('total_price');
                // Same fix as the main KPIs above — Lead::scopeConverted()
                // instead of the legacy lead_status='won' string, so this
                // "WON" column (rendered in the app's own Branch-wise
                // Performance table) agrees with the Active Customers KPI
                // and Lead List filter rather than a narrower, separately
                // -maintained condition.
                $wonLeads       = (clone $q)->converted()->count();
                $wonVal         = (float)(clone $q)->converted()->sum('deal_value');

                $convertedCount = $productConvCnt > 0 ? $productConvCnt : $wonLeads;
                $convertedVal   = $productConvVal > 0 ? $productConvVal : $wonVal;
                $convRate       = $total > 0 ? round($convertedCount / $total * 100, 1) : 0;
                $pipeline       = (float)(clone $q)->whereNotIn('lead_status', ['won', 'lost'])->sum('deal_value');

                return [
                    'branch_id'            => $branch->id,
                    'branch_name'          => $branch->name,
                    'branch_code'          => $branch->code,
                    'total_leads'          => $total,
                    'converted_count'      => $convertedCount,
                    'converted_value'      => $convertedVal,
                    'converted_percentage' => $convRate,
                    'won_leads'            => $wonLeads,
                    'won_value'            => $wonVal,
                    'lost_leads'           => (clone $q)->where('lead_status', 'lost')->count(),
                    'pipeline_value'       => $pipeline,
                    'conversion_rate'      => $convRate,
                ];
            })
            ->sortByDesc('converted_value')
            ->values();

        // ── 9. Team performance ───────────────────────────────────
        $teamUsers = $this->visibility->visibleAssignableUsers($request->user());
        if ($request->user() && ($request->user()->isBranchAdmin() || $request->user()->isBranchManager())) {
            $userBranchIds = $request->user()->getMyBranchIds();
            $bmUsers = User::query()
                ->where('is_active', true)
                ->when($request->user()->company_id, fn ($q) => $q->where('company_id', $request->user()->company_id))
                ->when(!empty($userBranchIds), fn ($q) => $q->inBranches($userBranchIds))
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'like', '%branch_manager%')
                      ->orWhere('name', 'like', '%bm%')
                      ->orWhere('display_name', 'like', '%branch%manager%');
                })
                ->get();
            $teamUsers = $teamUsers->concat($bmUsers)->unique('id');
        }

        $teamPerformance = $teamUsers
            ->when($branchId, fn($users) => $users->filter(fn($u) => (int)$u->branch_id === (int)$branchId || (method_exists($u, 'belongsToBranch') && $u->belongsToBranch($branchId)) || in_array((int)$branchId, $u->getMyBranchIds() ?? [], true)))
            ->map(function ($user) use ($request, $dateFrom, $dateTo, $branchId) {
                $q = Lead::where('assigned_to', $user->id)
                    ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($dateFrom, fn($q2) => $q2->whereDate('lead_date', '>=', $dateFrom))
                    ->when($dateTo,   fn($q2) => $q2->whereDate('lead_date', '<=', $dateTo));
                $this->visibility->applyLeadVisibility($q, $request->user());

                $total  = (clone $q)->count();
                // Same fix as the main KPIs above — Lead::scopeConverted()
                // instead of the legacy lead_status='won' string, so this
                // "WON" column (rendered in the app's own Team Performance
                // table) agrees with the Active Customers KPI and Lead
                // List filter rather than a narrower, separately
                // -maintained condition.
                $won    = (clone $q)->converted()->count();
                $lost   = (clone $q)->where('lead_status', 'lost')->count();
                $wonVal = (float)(clone $q)->converted()->sum('deal_value');

                $firstRole = $user->roles->first();
                $roleDisplay = $firstRole?->display_name;
                if (empty($roleDisplay) && !empty($firstRole?->name)) {
                    $clean = preg_replace('/^company_\d+__/', '', $firstRole->name);
                    $clean = str_replace(['_', '-'], ' ', $clean);
                    $roleDisplay = ucwords($clean);
                }
                if (empty($roleDisplay) && !empty($user->role)) {
                    $clean = preg_replace('/^company_\d+__/', '', $user->role);
                    $clean = str_replace(['_', '-'], ' ', $clean);
                    $roleDisplay = ucwords($clean);
                }

                return [
                    'user_id'           => $user->id,
                    'user_name'         => $user->name,
                    'user_email'        => $user->email,
                    'role'              => $roleDisplay ?: 'Staff',
                    'role_name'         => $firstRole?->name,
                    'role_display_name' => $roleDisplay ?: 'Staff',
                    'total_leads'       => $total,
                    'won_leads'         => $won,
                    'lost_leads'        => $lost,
                    'active_leads'      => $total - $won - $lost,
                    'won_value'         => $wonVal,
                    'conversion_rate'   => $total > 0 ? round($won / $total * 100, 1) : 0,
                ];
            })
            ->filter(fn($u) => $u['total_leads'] > 0)
            ->sortByDesc('won_value')
            ->values()
            ->take(10);

        // ── 10. 6-month trend ─────────────────────────────────────
        $monthTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $q = Lead::whereYear('lead_date', $month->year)
                     ->whereMonth('lead_date', $month->month)
                     ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                     ->when($userId,   fn($q2) => $q2->where('assigned_to', $userId));
            $this->visibility->applyLeadVisibility($q, $request->user());

            $monthTrend[] = [
                'month'       => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'year'        => (int) $month->format('Y'),
                'month_num'   => (int) $month->format('m'),
                'total'       => (clone $q)->count(),
                'won'         => (clone $q)->where('lead_status', 'won')->count(),
                'lost'        => (clone $q)->where('lead_status', 'lost')->count(),
                'won_value'   => (float)(clone $q)->where('lead_status', 'won')->sum('deal_value'),
            ];
        }

        // ── Active filters snapshot ───────────────────────────────
        $filtersApplied = array_filter([
            'branch_id'  => $branchId,
            'user_id'    => $userId,
            'stage'      => $request->stage,
            'source'     => $request->source,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'quick_date' => $request->quick_date,
        ]);

        // ── Completed Calls & Scheduled Reminders scoped to active date filter ──
        $completedCallsQuery = LeadCallUpdate::whereHas('lead', function ($leadQuery) use ($request, $branchId, $effectiveUserId, $stage, $source) {
            $this->visibility->applyLeadVisibility($leadQuery, $request->user());
            $leadQuery->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                      ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                      ->when($stage, fn($q2) => $q2->where('lead_status', $stage))
                      ->when($source, fn($q2) => $q2->where('lead_source', $source));
        })
        ->when($effectiveUserId, function ($q) use ($effectiveUserId) {
            $q->where(function ($sub) use ($effectiveUserId) {
                $sub->where('user_id', $effectiveUserId)
                    ->orWhereHas('lead', fn($lq) => $lq->where('assigned_to', $effectiveUserId));
            });
        });

        $hasDateFilter = $dateFrom || $dateTo || $request->quick_date === 'all';
        $completedCallsCount = $hasDateFilter
            ? (clone $completedCallsQuery)
                ->when($dateFrom, fn($q) => $q->whereDate('called_at', '>=', $dateFrom))
                ->when($dateTo,   fn($q) => $q->whereDate('called_at', '<=', $dateTo))
                ->count()
            : (clone $completedCallsQuery)->whereDate('called_at', today())->count();

        $periodRemindersQuery = (clone $reminderQuery())
            ->when($dateFrom, fn($q) => $q->whereDate('remind_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('remind_at', '<=', $dateTo));

        $scheduledRemindersCount = $hasDateFilter
            ? $periodRemindersQuery->count()
            : (clone $reminderQuery())->whereDate('remind_at', today())->count();

        // ── Forecasting / Total Prospects (Current Month Hot Products by Closure Date) ──
        $currentMonthHotProductsQuery = LeadProduct::query()
            ->whereRaw('LOWER(product_status) = ?', ['hot'])
            ->whereMonth('closure_date', now()->month)
            ->whereYear('closure_date', now()->year)
            ->whereHas('lead', function ($lq) use ($request, $branchId, $effectiveUserId) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId)        $lq->where('branch_id', $branchId);
                if ($effectiveUserId) $lq->where('assigned_to', $effectiveUserId);
            });
        $currentMonthHotProductsCount = (clone $currentMonthHotProductsQuery)->count();
        $currentMonthHotProductsValue = (float) (clone $currentMonthHotProductsQuery)->sum('total_price');
        $currentMonthExpectedCollection = (float) (clone $currentMonthHotProductsQuery)->sum('expected_value');
        $cpMetrics = $this->buildChannelPartnerHotMetrics($currentMonthHotProductsQuery, $request);

        // ── Build response ────────────────────────────────────────
        return response()->json([
            'success' => true,
            'message' => 'Dashboard data fetched.',
            'data'    => [

                'filters_applied' => $filtersApplied,

                'kpis' => [
                    'total_leads'     => $totalLeads,
                    'active_leads'    => $activeLeads,
                    'won_leads'       => $wonLeads,
                    'lost_leads'      => $lostLeads,
                    'high_priority'   => $highPriority,
                    'pipeline_value'  => $pipelineValue,
                    'won_value'       => $wonValue,
                    'conversion_rate'            => $convRate,
                    'converted_products_count'   => $convertedCount,
                    'upcoming_amount'            => $upcomingAmount,
                    'total_received_amount'      => $totalReceivedAmount,
                    'converted_value'            => $convertedValue,
                    'converted_percentage'       => $convertedPercentage,
                    'scheduled_followups_count'  => $scheduledRemindersCount,
                    'today_reminders_count'      => $scheduledRemindersCount,
                    'completed_calls_count'      => $completedCallsCount,
                    'today_completed_calls_count'=> $completedCallsCount,
                    'overdue_reminders_count'    => $overdueCount,
                    'current_month_hot_products_count' => $currentMonthHotProductsCount,
                    'total_prospects'            => $currentMonthHotProductsCount,
                    'current_month_hot_products_value' => $currentMonthHotProductsValue,
                    'current_month_expected_collection' => $currentMonthExpectedCollection,
                    'nst_ho_prospects_count'     => $cpMetrics['nst_ho']['count'],
                    'nst_ho_deal_value'          => $cpMetrics['nst_ho']['deal_value'],
                    'nst_ho_expected_value'      => $cpMetrics['nst_ho']['expected_value'],
                    'non_coco_prospects_count'   => $cpMetrics['non_coco']['count'],
                    'non_coco_deal_value'        => $cpMetrics['non_coco']['deal_value'],
                    'non_coco_expected_value'    => $cpMetrics['non_coco']['expected_value'],
                    'coco_prospects_count'       => $cpMetrics['coco']['count'],
                    'coco_deal_value'            => $cpMetrics['coco']['deal_value'],
                    'coco_expected_value'        => $cpMetrics['coco']['expected_value'],
                ],

                'forecasting' => [
                    'total_prospects'           => $currentMonthHotProductsCount,
                    'hot_products_count'        => $currentMonthHotProductsCount,
                    'hot_products_value'        => $currentMonthHotProductsValue,
                    'deal_value'                => $currentMonthHotProductsValue,
                    'expected_collection_value' => $currentMonthExpectedCollection,
                    'month_name'                => now()->format('F Y'),
                    'nst_ho'                    => $cpMetrics['nst_ho'],
                    'non_coco'                  => $cpMetrics['non_coco'],
                    'coco'                      => $cpMetrics['coco'],
                    'active_branches'           => $this->buildActiveBranchesHotMetrics($request),
                ],

                'financials' => [
                    'total_product_value' => $totalProductValue,
                    'amount_paid'         => $totalPaid,
                    'amount_pending'      => $totalPending,
                    'converted_value'     => $convertedValue,
                    'converted_count'     => $convertedCount,
                    'payment_percent'     => $payPct,
                    'payment_by_mode'     => $paymentByMode,
                    'product_status_dist' => $productStatusDist,
                ],

                'pipeline_funnel' => [
                    'total'  => $stageTotal,
                    'stages' => $stageFunnel,
                ],

                'source_distribution' => [
                    'total'   => $donutTotal,
                    'sources' => $sourceCounts,
                ],

                'today_followups' => [
                    'count' => $todayFollowups->count(),
                    'items' => $todayFollowups,
                ],

                'reminders' => [
                    'overdue_count' => $overdueCount,
                    'today_count'   => $todayReminders->count(),
                    'items'         => $todayReminders,
                    'overdue_items' => $overdueReminders,
                ],

                'recent_leads' => $recentLeads,

                'branch_performance' => $branchPerformance,

                'team_performance' => $teamPerformance,

                'month_trend' => $monthTrend,

                'sales_target_stats' => $this->getSalesTargetStats($branchId, $userId, $dateFrom, $dateTo, $request->user()),

                // Enum references for mobile UI rendering
                'enums' => [
                    'statuses'         => $this->companyScopedLeadStatusOptions($request->user()),
                    'sources'          => $this->companyScopedLeadSourceOptions($request->user()),
                    'priorities'       => Lead::PRIORITIES,
                    'status_colors'    => Lead::STATUS_COLORS,
                    'priority_colors'  => Lead::PRIORITY_COLORS,
                    'product_statuses' => LeadProduct::PRODUCT_STATUSES,
                    'payment_modes'    => LeadProduct::PAYMENT_MODES,
                ],
            ],
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    // Ported from SuperAdminDashboardController::getSalesTargetStats() — same
    // computation (per-user target if one is assigned/viewing their own data,
    // else per-branch, else company-wide), so the mobile "Sales Target
    // Tracking" card (Flutter UI/model already existed but had nothing to
    // read) shows the same numbers as web's.
    private function getSalesTargetStats($branchId, $userId, $dateFrom, $dateTo, $currentUser): array
    {
        $targetUserId = $userId ?: (!$currentUser->can('settings.manage') ? $currentUser->id : null);
        $effectiveBranchId = null;

        if ($targetUserId) {
            $target = (float) SalesTarget::where('user_id', $targetUserId)->value('target_amount');
            $userObj = User::find($targetUserId);
            $targetName = $userObj ? $userObj->name : 'Representative';
            $isIndividual = true;
            $title = "{$targetName}'s Target";
        } else {
            $effectiveBranchId = $branchId ?: $currentUser->branch_id;

            if ($effectiveBranchId) {
                $branchObj = Branch::find($effectiveBranchId);
                $targetName = $branchObj ? $branchObj->name : 'Branch';

                $userIds = User::where('branch_id', $effectiveBranchId)->pluck('id');
                $target = (float) SalesTarget::whereIn('user_id', $userIds)->sum('target_amount');
                $isIndividual = false;
                $title = "{$targetName} Target";
            } else {
                $target = (float) SalesTarget::sum('target_amount');
                $targetName = 'Overall';
                $isIndividual = false;
                $title = 'Overall Sales Target';
            }
        }

        $achievedQuery = LeadProductPayment::query();
        if ($targetUserId) {
            $achievedQuery->whereHas('lead', function ($q) use ($targetUserId) {
                $q->where('assigned_to', $targetUserId);
            });
        } elseif ($effectiveBranchId) {
            $achievedQuery->whereHas('lead', function ($q) use ($effectiveBranchId) {
                $q->where('branch_id', $effectiveBranchId);
            });
        }

        if ($dateFrom) {
            $achievedQuery->whereDate('payment_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $achievedQuery->whereDate('payment_date', '<=', $dateTo);
        }

        $achieved = (float) $achievedQuery->sum('amount');
        $pending  = max(0.00, $target - $achieved);
        $percent  = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;

        return [
            'name'          => $targetName,
            'is_individual' => $isIndividual,
            'target'        => $target,
            'achieved'      => $achieved,
            'pending'       => $pending,
            'percent'       => $percent,
            'title'         => $title,
        ];
    }

    // Ported from SuperAdminDashboardController::buildProductStatusFunnel() —
    // see the "── 2. Pipeline funnel ──" comment above for why this replaced
    // the old lead-status-based grouping. $leadIds is accepted to match the
    // original signature but, same as web's version, isn't actually used
    // inside — every count here comes from $this->getLeadProductBaseQuery().
    private function buildProductStatusFunnel($leadIds, Request $request, $allLpProducts = null, $convertedCount = 0, $convertedStatusIds = []): array
    {
        $companyId = $request->user()?->company_id;

        // 1. Try grouping by LeadStatus if present and yields results
        $statuses = LeadStatus::query()
            ->when(
                $companyId,
                fn($query) => $query->where(fn($statusQuery) => $statusQuery
                    ->where('company_id', $companyId)
                    ->orWhereNull('company_id'))
            )
            ->orderBy('id')
            ->get(['id', 'name'])
            ->unique(fn($s) => strtolower(trim($s->name)))
            ->values();

        $leadProducts = $allLpProducts ?? LeadProduct::whereIn('lead_id', $leadIds)->get(['id', 'lead_status_id', 'product_status']);

        if ($statuses->isNotEmpty() && $leadProducts->isNotEmpty()) {
            $statusByName = [];
            foreach ($statuses as $s) {
                $statusByName[strtolower(trim($s->name))] = $s->id;
            }

            $allStatusNames = LeadStatus::pluck('name', 'id')->toArray();

            $counts = [];
            foreach ($statuses as $s) {
                $counts[$s->id] = 0;
            }

            foreach ($leadProducts as $lp) {
                $pStatus = strtolower(trim((string)$lp->product_status));
                $isConv = in_array($pStatus, ['converted', 'won'])
                    || ($lp->lead_status_id && in_array($lp->lead_status_id, $convertedStatusIds));

                if ($isConv) {
                    $convStatusId = null;
                    foreach ($statuses as $s) {
                        $sLower = strtolower(trim($s->name));
                        if (in_array($sLower, ['converted', 'won']) || str_contains($sLower, 'convert')) {
                            $convStatusId = $s->id;
                            break;
                        }
                    }
                    if ($convStatusId && isset($counts[$convStatusId])) {
                        $counts[$convStatusId]++;
                        continue;
                    }
                }

                if (!empty($lp->lead_status_id) && isset($counts[$lp->lead_status_id])) {
                    $counts[$lp->lead_status_id]++;
                } elseif (!empty($lp->lead_status_id) && isset($allStatusNames[$lp->lead_status_id])) {
                    $sName = strtolower(trim($allStatusNames[$lp->lead_status_id]));
                    if (isset($statusByName[$sName])) {
                        $counts[$statusByName[$sName]]++;
                    }
                } elseif (!empty($lp->product_status)) {
                    $pStatusKey = strtolower(trim($lp->product_status));
                    if (isset($statusByName[$pStatusKey])) {
                        $counts[$statusByName[$pStatusKey]]++;
                    }
                }
            }

            $stageTotal = (int) array_sum($counts);

            if ($stageTotal > 0) {
                $stages = $statuses->map(function ($status) use ($counts, $stageTotal) {
                    $key = LeadProduct::statusKey($status->name);
                    $count = (int) ($counts[$status->id] ?? 0);

                    return [
                        'key'     => (string) $status->id,
                        'label'   => $status->name,
                        'count'   => $count,
                        'percent' => $stageTotal > 0 ? round($count / $stageTotal * 100, 1) : 0,
                        'color'   => LeadProduct::PRODUCT_STATUS_CONFIG[$key]
                            ?? ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'],
                    ];
                })->values()->toArray();

                return ['total' => $stageTotal, 'stages' => $stages];
            }
        }

        // 2. Default fallback: Group by product_status column (case insensitive)
        $rawCounts = $leadProducts->isEmpty()
            ? collect()
            : $leadProducts->groupBy(fn ($lp) => strtolower(trim($lp->product_status)))
                ->map(fn ($group) => $group->count());

        $stageTotal = (int) $rawCounts->sum();
        $stages = [];

        foreach (LeadProduct::PRODUCT_STATUSES as $pkey => $plabel) {
            $count = (int) ($rawCounts[$pkey] ?? 0);
            $config = LeadProduct::PRODUCT_STATUS_CONFIG[$pkey]
                ?? ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'];

            $stages[] = [
                'key'     => $pkey,
                'label'   => $plabel,
                'count'   => $count,
                'percent' => $stageTotal > 0 ? round($count / $stageTotal * 100, 1) : 0,
                'color'   => $config,
            ];
        }

        return ['total' => $stageTotal, 'stages' => $stages];
    }

    private function getLeadProductBaseQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        [$dateFrom, $dateTo] = $this->resolveDates($request);
        $branchId = $request->branch_id;
        $userId   = $request->user_id;

        $query = LeadProduct::query()
            ->whereHas('lead', function ($leadQuery) use ($request, $branchId, $userId) {
                $this->visibility->applyLeadVisibility($leadQuery, $request->user());
                if ($branchId) {
                    $leadQuery->where('branch_id', $branchId);
                }
                if ($userId) {
                    $leadQuery->where('assigned_to', $userId);
                }
            });

        if ($dateFrom) {
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('created_at', '>=', $dateFrom)
                  ->orWhereDate('converted_at', '>=', $dateFrom)
                  ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '>=', $dateFrom)->orWhereDate('created_at', '>=', $dateFrom));
            });
        }

        if ($dateTo) {
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('created_at', '<=', $dateTo)
                  ->orWhereDate('converted_at', '<=', $dateTo)
                  ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '<=', $dateTo)->orWhereDate('created_at', '<=', $dateTo));
            });
        }

        return $query;
    }

    private function resolveDates(Request $request): array
    {
        if ($request->filled('quick_date')) {
            return match ($request->quick_date) {
                'all'       => [null, null],
                'today'     => [today()->toDateString(), today()->toDateString()],
                'yesterday' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
                'week'      => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                'month'   => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                'quarter' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
                'year'    => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                'custom'  => [$request->date_from ?: null, $request->date_to ?: null],
                default   => [null, null],
            };
        }

        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
        }

        return [$request->date_from ?: null, $request->date_to ?: null];
    }

    /**
     * Build separate metrics for Channel Partner category products (NON COCO and COCO)
     * based on current month Hot lead products.
     */
    private function buildChannelPartnerHotMetrics($currentMonthHotProductsQuery, ?Request $request = null): array
    {
        $channelPartnerCat = ProductCategory::where('name', 'like', '%Channel Partner%')->first();
        $catId = $channelPartnerCat?->id;

        $cocoProduct = Product::where(function ($q) use ($catId) {
            if ($catId) {
                $q->where('product_category_id', $catId);
            }
            $q->where(function ($sq) {
                $sq->where('product_name', 'like', '%COCO%')
                   ->orWhere('package_name', 'like', '%COCO%');
            });
        })->where(function ($q) {
            $q->where('product_name', 'not like', '%NON%')
              ->where('package_name', 'not like', '%NON%');
        })->first();

        $nonCocoProduct = Product::where(function ($q) use ($catId) {
            if ($catId) {
                $q->where('product_category_id', $catId);
            }
            $q->where(function ($sq) {
                $sq->where('product_name', 'like', '%NON%COCO%')
                   ->orWhere('package_name', 'like', '%NON%COCO%');
            });
        })->first();

        // NON COCO Hot query
        $nonCocoHotQuery = (clone $currentMonthHotProductsQuery)->where(function ($q) use ($nonCocoProduct) {
            if ($nonCocoProduct) {
                $q->where('product_id', $nonCocoProduct->id)
                  ->orWhere('product_name', 'like', '%NON%COCO%');
            } else {
                $q->where('product_name', 'like', '%NON%COCO%');
            }
        });
        $nonCocoHotCount = (clone $nonCocoHotQuery)->count();
        $nonCocoDealValue = (float) (clone $nonCocoHotQuery)->sum('total_price');
        $nonCocoExpectedValue = (float) (clone $nonCocoHotQuery)->sum('expected_value');

        // COCO Hot query
        $cocoHotQuery = (clone $currentMonthHotProductsQuery)->where(function ($q) use ($cocoProduct) {
            if ($cocoProduct) {
                $q->where(function ($sq) use ($cocoProduct) {
                    $sq->where('product_id', $cocoProduct->id)
                       ->orWhere(function ($ssq) {
                           $ssq->where('product_name', 'like', '%COCO%')
                               ->where('product_name', 'not like', '%NON%');
                       });
                });
            } else {
                $q->where('product_name', 'like', '%COCO%')
                  ->where('product_name', 'not like', '%NON%');
            }
        });
        $cocoHotCount = (clone $cocoHotQuery)->count();
        $cocoDealValue = (float) (clone $cocoHotQuery)->sum('total_price');
        $cocoExpectedValue = (float) (clone $cocoHotQuery)->sum('expected_value');

        // Channel Partner product IDs to exclude from NST - HO
        $cpProductIds = [];
        if ($catId) {
            $cpProductIds = Product::where('product_category_id', $catId)->pluck('id')->toArray();
        }
        if ($nonCocoProduct && !in_array($nonCocoProduct->id, $cpProductIds)) {
            $cpProductIds[] = $nonCocoProduct->id;
        }
        if ($cocoProduct && !in_array($cocoProduct->id, $cpProductIds)) {
            $cpProductIds[] = $cocoProduct->id;
        }

        // Company's default branch filter for NST - HO
        $user = $request?->user();
        $companyId = $user ? ($this->visibility->companyIdFor($user) ?? $user->company_id) : null;
        $defaultBranchQuery = Branch::where('is_default', true);
        if ($companyId) {
            $defaultBranchQuery->where('company_id', $companyId);
        } else {
            $defaultBranchQuery->where('company_id', 1);
        }
        $defaultBranchIds = $defaultBranchQuery->pluck('id')->toArray();
        if (empty($defaultBranchIds)) {
            $defaultBranchIds = Branch::where('is_default', true)->pluck('id')->toArray();
        }

        // NST - HO Hot query: EXCLUDE Channel Partner products ONLY for Company Admin / CBO (unfiltered).
        // For Sales TL, Branch Admin, Branch Manager, Sales Executive (or when scoped to a user),
        // NST - HO represents all of their Default Branch (HO) hot prospects, including Channel Partner.
        $currentUser = $request?->user() ?: auth()->user();
        $isCompanyAdminOrCbo = $currentUser && ($currentUser->isSuperAdmin() || $currentUser->isSystemAdmin() || $currentUser->isCompanyAdminRole() || $currentUser->isCbo()) && !$request->filled('user_id');

        $nstHoHotQuery = clone $currentMonthHotProductsQuery;
        if ($isCompanyAdminOrCbo) {
            $nstHoHotQuery->where(function ($q) use ($cpProductIds) {
                if (!empty($cpProductIds)) {
                    $q->whereNotIn('product_id', $cpProductIds);
                }
                $q->where('product_name', 'not like', '%COCO%')
                  ->where('product_name', 'not like', '%Channel Partner%');
            });
        }
        if (!empty($defaultBranchIds)) {
            $nstHoHotQuery->whereHas('lead', function ($lq) use ($defaultBranchIds) {
                $lq->whereIn('branch_id', $defaultBranchIds);
            });
        }
        $nstHoHotCount = (clone $nstHoHotQuery)->count();
        $nstHoDealValue = (float) (clone $nstHoHotQuery)->sum('total_price');
        $nstHoExpectedValue = (float) (clone $nstHoHotQuery)->sum('expected_value');

        return [
            'nst_ho' => [
                'count'          => $nstHoHotCount,
                'deal_value'     => $nstHoDealValue,
                'expected_value' => $nstHoExpectedValue,
                'heading'        => 'NST - HO',
            ],
            'non_coco' => [
                'count'          => $nonCocoHotCount,
                'deal_value'     => $nonCocoDealValue,
                'expected_value' => $nonCocoExpectedValue,
                'product_id'     => $nonCocoProduct?->id,
                'product_name'   => $nonCocoProduct?->product_name ?? 'Channel Partner NON COCO Model',
            ],
            'coco' => [
                'count'          => $cocoHotCount,
                'deal_value'     => $cocoDealValue,
                'expected_value' => $cocoExpectedValue,
                'product_id'     => $cocoProduct?->id,
                'product_name'   => $cocoProduct?->product_name ?? 'Channel Partner COCO Model',
            ],
        ];
    }

    /**
     * Build active branches current month hot prospect metrics for the Total Prospects modal.
     */
    private function buildActiveBranchesHotMetrics(Request $request): array
    {
        $user = $request->user();
        $visibleBranchIds = $user ? $this->visibility->visibleBranchIds($user) : collect();
        $companyId = $user ? $this->visibility->companyIdFor($user) : null;

        $branches = Branch::where('is_active', true)
            ->where(function ($query) {
                $query->where('is_default', false)
                    ->orWhereNull('is_default');
            })
            ->when($visibleBranchIds->isNotEmpty(), fn($query) => $query->whereIn('id', $visibleBranchIds))
            ->when($visibleBranchIds->isEmpty() && $companyId, fn($query) => $query->whereRaw('1 = 0'))
            ->when($request->filled('branch_id'), fn($query) => $query->where('id', $request->branch_id))
            ->orderBy('name')
            ->get();

        $branchIds = $branches->pluck('id')->toArray();
        if (empty($branchIds)) {
            return [];
        }

        $hotProductsGrouped = LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->whereRaw('LOWER(lead_products.product_status) = ?', ['hot'])
            ->whereMonth('lead_products.closure_date', now()->month)
            ->whereYear('lead_products.closure_date', now()->year)
            ->whereIn('leads.branch_id', $branchIds)
            ->when($request->filled('user_id'), fn($q) => $q->where('leads.assigned_to', $request->user_id))
            ->select(
                'leads.branch_id',
                DB::raw('COUNT(lead_products.id) as prospect_count'),
                DB::raw('COALESCE(SUM(lead_products.total_price), 0) as deal_value'),
                DB::raw('COALESCE(SUM(lead_products.expected_value), 0) as expected_value')
            )
            ->groupBy('leads.branch_id')
            ->get()
            ->keyBy('branch_id');

        return $branches->map(function ($branch) use ($hotProductsGrouped) {
            $stats = $hotProductsGrouped->get($branch->id);

            return [
                'id'             => $branch->id,
                'name'           => $branch->name,
                'code'           => $branch->code,
                'is_default'     => (bool) $branch->is_default,
                'branch_type'    => $branch->branch_type,
                'prospect_count' => $stats ? (int) $stats->prospect_count : 0,
                'deal_value'     => $stats ? (float) $stats->deal_value : 0.0,
                'expected_value' => $stats ? (float) $stats->expected_value : 0.0,
            ];
        })->values()->toArray();
    }

    /**
     * Get hot leads for a specific branch or card category in the current month by closure_date for mobile app.
     */
    public function branchHotLeads(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $type = $request->input('type');
        $branchId = (int) $request->input('branch_id');

        if (!$type && !$branchId) {
            return response()->json(['success' => false, 'message' => 'Type or Branch ID is required.'], 422);
        }

        $branch = null;
        $title = 'Hot Prospects';
        $subtitle = 'Current month closure hot leads';
        $branchType = null;

        $query = LeadProduct::query()
            ->with([
                'lead' => function ($lq) {
                    $lq->with(['assignedTo:id,name', 'branch:id,name']);
                },
                'product:id,product_name',
                'leadStatus:id,name'
            ])
            ->whereRaw('LOWER(product_status) = ?', ['hot'])
            ->whereMonth('closure_date', now()->month)
            ->whereYear('closure_date', now()->year);

        // Apply Lead Visibility
        $query->whereHas('lead', function ($lq) use ($currentUser, $request) {
            $this->visibility->applyLeadVisibility($lq, $currentUser);
            if ($request->filled('user_id')) {
                $lq->where('assigned_to', $request->user_id);
            }
        });

        if ($type === 'nst_ho') {
            $title = 'NST - HO';
            $isCompanyAdminOrCbo = ($currentUser && ($currentUser->isSuperAdmin() || $currentUser->isSystemAdmin() || $currentUser->isCompanyAdminRole() || $currentUser->isCbo())) && !$request->filled('user_id');
            $subtitle = $isCompanyAdminOrCbo
                ? 'Default Branch (HO) • Excl. Channel Partner'
                : 'Default Branch (HO) • Hot Prospects';

            $channelPartnerCategory = ProductCategory::where('name', 'like', '%Channel Partner%')->first();
            $catId = $channelPartnerCategory?->id;

            $cocoProduct = Product::where(function ($q) use ($catId) {
                if ($catId) {
                    $q->where('product_category_id', $catId);
                }
                $q->where(function ($sq) {
                    $sq->where('product_name', 'like', '%COCO%')
                       ->orWhere('package_name', 'like', '%COCO%');
                });
            })->where(function ($q) {
                $q->where('product_name', 'not like', '%NON%')
                  ->where('package_name', 'not like', '%NON%');
            })->first();

            $nonCocoProduct = Product::where(function ($q) use ($catId) {
                if ($catId) {
                    $q->where('product_category_id', $catId);
                }
                $q->where(function ($sq) {
                    $sq->where('product_name', 'like', '%NON%COCO%')
                       ->orWhere('package_name', 'like', '%NON%COCO%');
                });
            })->first();

            $cpProductIds = [];
            if ($catId) {
                $cpProductIds = Product::where('product_category_id', $catId)->pluck('id')->toArray();
            }
            if ($nonCocoProduct && !in_array($nonCocoProduct->id, $cpProductIds)) {
                $cpProductIds[] = $nonCocoProduct->id;
            }
            if ($cocoProduct && !in_array($cocoProduct->id, $cpProductIds)) {
                $cpProductIds[] = $cocoProduct->id;
            }

            $companyId = $this->visibility->companyIdFor($currentUser) ?? $currentUser?->company_id;
            $defaultBranchQuery = Branch::where('is_default', true);
            if ($companyId) {
                $defaultBranchQuery->where('company_id', $companyId);
            } else {
                $defaultBranchQuery->where('company_id', 1);
            }
            $defaultBranchIds = $defaultBranchQuery->pluck('id')->toArray();
            if (empty($defaultBranchIds)) {
                $defaultBranchIds = Branch::where('is_default', true)->pluck('id')->toArray();
            }

            if ($isCompanyAdminOrCbo) {
                $query->where(function ($q) use ($cpProductIds) {
                    if (!empty($cpProductIds)) {
                        $q->whereNotIn('product_id', $cpProductIds);
                    }
                    $q->where('product_name', 'not like', '%COCO%')
                      ->where('product_name', 'not like', '%Channel Partner%');
                });
            }

            if (!empty($defaultBranchIds)) {
                $query->whereHas('lead', function ($lq) use ($defaultBranchIds) {
                    $lq->whereIn('branch_id', $defaultBranchIds);
                });
            }

        } elseif ($type === 'non_coco') {
            $title = 'Channel Partner - NON COCO Model';
            $subtitle = 'NON COCO Hot Products';
            $branchType = 'NON COCO';

            $channelPartnerCategory = ProductCategory::where('name', 'like', '%Channel Partner%')->first();
            $catId = $channelPartnerCategory?->id;

            $nonCocoProduct = Product::where(function ($q) use ($catId) {
                if ($catId) {
                    $q->where('product_category_id', $catId);
                }
                $q->where(function ($sq) {
                    $sq->where('product_name', 'like', '%NON%COCO%')
                       ->orWhere('package_name', 'like', '%NON%COCO%');
                });
            })->first();

            $query->where(function ($q) use ($nonCocoProduct) {
                if ($nonCocoProduct) {
                    $q->where('product_id', $nonCocoProduct->id)
                      ->orWhere('product_name', 'like', '%NON%COCO%');
                } else {
                    $q->where('product_name', 'like', '%NON%COCO%');
                }
            });

        } elseif ($type === 'coco') {
            $title = 'Channel Partner - COCO Model';
            $subtitle = 'COCO Hot Products';
            $branchType = 'COCO';

            $channelPartnerCategory = ProductCategory::where('name', 'like', '%Channel Partner%')->first();
            $catId = $channelPartnerCategory?->id;

            $cocoProduct = Product::where(function ($q) use ($catId) {
                if ($catId) {
                    $q->where('product_category_id', $catId);
                }
                $q->where(function ($sq) {
                    $sq->where('product_name', 'like', '%COCO%')
                       ->orWhere('package_name', 'like', '%COCO%');
                });
            })->where(function ($q) {
                $q->where('product_name', 'not like', '%NON%')
                  ->where('package_name', 'not like', '%NON%');
            })->first();

            $query->where(function ($q) use ($cocoProduct) {
                if ($cocoProduct) {
                    $q->where(function ($sq) use ($cocoProduct) {
                        $sq->where('product_id', $cocoProduct->id)
                           ->orWhere(function ($ssq) {
                               $ssq->where('product_name', 'like', '%COCO%')
                                   ->where('product_name', 'not like', '%NON%');
                           });
                    });
                } else {
                    $q->where('product_name', 'like', '%COCO%')
                      ->where('product_name', 'not like', '%NON%');
                }
            });

        } else {
            // By Branch ID
            $branch = Branch::find($branchId);
            if (!$branch) {
                return response()->json(['success' => false, 'message' => 'Branch not found.'], 404);
            }

            $visibleBranchIds = $this->visibility->visibleBranchIds($currentUser);
            if ($visibleBranchIds->isNotEmpty() && !$visibleBranchIds->contains($branchId)) {
                return response()->json(['success' => false, 'message' => 'Unauthorized branch access.'], 403);
            }

            $title = $branch->name;
            $subtitle = 'Hot prospects for current month closure';
            $branchType = $branch->branch_type;

            $query->whereHas('lead', function ($lq) use ($branchId) {
                $lq->where('branch_id', $branchId);
            });
        }

        $hotProducts = $query->orderByDesc('closure_date')->get();

        $rows = $hotProducts->map(function ($item, $idx) {
            $lead = $item->lead;
            return [
                'index'          => $idx + 1,
                'lead_id'        => $lead?->id,
                'company_name'   => $lead?->company_name ?: ($lead?->business_name ?: '-'),
                'customer_name'  => $lead?->contact_name ?: '-',
                'product_name'   => $item->product_name ?: ($item->product?->product_name ?: '-'),
                'status'         => $item->product_status ? ucfirst($item->product_status) : ($item->leadStatus?->name ?? 'Hot'),
                'deal_value'     => (float) $item->total_price,
                'expected_value' => (float) ($item->expected_value ?? 0),
                'closure_date'   => $item->closure_date ? $item->closure_date->format('d M Y') : '-',
                'closure_date_raw' => $item->closure_date ? $item->closure_date->format('Y-m-d') : null,
                'executive_name' => $lead?->assignedTo?->name ?: '-',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Branch hot leads fetched.',
            'data'    => [
                'branch' => [
                    'id'          => $branch?->id,
                    'name'        => $title,
                    'code'        => $branch?->code,
                    'branch_type' => $branchType,
                    'subtitle'    => $subtitle,
                ],
                'period'         => now()->format('F Y'),
                'total_count'    => $rows->count(),
                'total_deal'     => (float) $rows->sum('deal_value'),
                'total_expected' => (float) $rows->sum('expected_value'),
                'leads'          => $rows,
            ],
        ]);
    }
}
