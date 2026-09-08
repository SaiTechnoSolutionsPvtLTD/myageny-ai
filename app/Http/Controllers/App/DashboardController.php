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
use App\Models\LeadStatus;
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
            new OA\Parameter(name: "quick_date", in: "query", required: false, description: "Preset date range",     schema: new OA\Schema(type: "string", enum: ["today","week","month","quarter","year"])),
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
            'quick_date' => ['nullable', 'in:all,today,week,month,quarter,year,custom'],
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
                ->when($source, fn($q) => $q->where('lead_source', $source))
                ->when($dateFrom, fn($q) => $q->whereDate('lead_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('lead_date', '<=', $dateTo));
        };

        // ── 1. KPIs ───────────────────────────────────────────────
        $totalLeads    = (clone $base())->count();
        $wonLeads      = (clone $base())->where('lead_status', 'won')->count();
        $lostLeads     = (clone $base())->where('lead_status', 'lost')->count();
        $activeLeads   = $totalLeads - $wonLeads - $lostLeads;
        $pipelineValue = (float)(clone $base())->whereNotIn('lead_status', ['won', 'lost'])->sum('deal_value');
        $wonValue      = (float)(clone $base())->where('lead_status', 'won')->sum('deal_value');
        $highPriority  = (clone $base())->where('priority', 'high')->whereNotIn('lead_status', ['won', 'lost'])->count();
        $convRate      = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;

        // ── 2. Pipeline funnel ──────────────────────────────────────
        // Web's Pipeline Funnel counts lead PRODUCTS grouped by each
        // product's own pipeline stage (lead_products.lead_status_id against
        // the company's LeadStatus master rows) — not leads grouped by
        // Lead::lead_status like this used to. The two totals differ
        // whenever a lead has zero or multiple products, which is why web's
        // funnel total and its "Overall Leads Count" KPI aren't the same
        // number. See buildProductStatusFunnel() below (ported from
        // SuperAdminDashboardController).
        $leadIds = (clone $base())->pluck('id');
        $productStatusFunnel = $this->buildProductStatusFunnel($leadIds, $request);
        $stageTotal  = $productStatusFunnel['total'];
        $stageFunnel = $productStatusFunnel['stages'];

        // ── 3. Source distribution ─────────────────────────────────
        $sourceCounts = [];
        $sourceTotal  = 0;
        foreach (Lead::sourceOptions() as $key => $label) {
            $count = (clone $base())
                ->where(function ($q) use ($key, $label) {
                    $q->where('lead_source_id', $key)
                        ->orWhere(function ($q2) use ($label) {
                            $q2->whereNull('lead_source_id')->where('lead_source', $label);
                        });
                })
                ->count();
            $sourceTotal += $count;
            $sourceCounts[] = ['key' => $key, 'label' => $label, 'count' => $count];
        }
        foreach ($sourceCounts as &$src) {
            $src['percent'] = $sourceTotal > 0 ? round($src['count'] / $sourceTotal * 100, 1) : 0;
        }
        unset($src);

        // ── 4. Financials ─────────────────────────────────────────
        $lpBase = $this->getLeadProductBaseQuery($request);
        $lpProducts = (clone $lpBase)->with('payments')->get();

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

        $convertedProducts = $lpProducts->filter($isConvertedProduct);
        $convertedValue    = (float) $convertedProducts->sum('total_price');
        $convertedCount    = $convertedProducts->count();
        $payPct            = $totalProductValue > 0 ? round($totalPaid / $totalProductValue * 100, 1) : 0;

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

        // ── 5. Recent call updates (last 20) ───────────────────────────
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
            ->take(20)
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
        $branchPerformance = Branch::where('is_active', true)
            ->when($request->user()?->company_id, fn($q, $companyId) => $q->where('company_id', $companyId))
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
                $wonLeads       = (clone $q)->where('lead_status', 'won')->count();
                $wonVal         = (float)(clone $q)->where('lead_status', 'won')->sum('deal_value');

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
        $teamPerformance = $this->visibility->visibleAssignableUsers($request->user())
            ->when($branchId, fn($users) => $users->where('branch_id', $branchId))
            ->map(function ($user) use ($request, $dateFrom, $dateTo, $branchId) {
                $q = Lead::where('assigned_to', $user->id)
                    ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($dateFrom, fn($q2) => $q2->whereDate('lead_date', '>=', $dateFrom))
                    ->when($dateTo,   fn($q2) => $q2->whereDate('lead_date', '<=', $dateTo));
                $this->visibility->applyLeadVisibility($q, $request->user());

                $total  = (clone $q)->count();
                $won    = (clone $q)->where('lead_status', 'won')->count();
                $lost   = (clone $q)->where('lead_status', 'lost')->count();
                $wonVal = (float)(clone $q)->where('lead_status', 'won')->sum('deal_value');

                return [
                    'user_id'         => $user->id,
                    'user_name'       => $user->name,
                    'user_email'      => $user->email,
                    'role'            => $user->roles->first()?->display_name,
                    'role_name'       => $user->roles->first()?->name,
                    'total_leads'     => $total,
                    'won_leads'       => $won,
                    'lost_leads'      => $lost,
                    'active_leads'    => $total - $won - $lost,
                    'won_value'       => $wonVal,
                    'conversion_rate' => $total > 0 ? round($won / $total * 100, 1) : 0,
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
                    'converted_value'            => $convertedValue,
                    'converted_percentage'       => $convertedPercentage,
                    'scheduled_followups_count'  => $todayFollowups->count(),
                    'overdue_reminders_count'    => $overdueCount,
                    'today_completed_calls_count' => LeadCallUpdate::whereHas('lead', fn($leadQuery) => $this->visibility->applyLeadVisibility($leadQuery, $request->user()))->whereDate('called_at', today())->count(),
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
                    'total'   => $sourceTotal,
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
    private function buildProductStatusFunnel($leadIds, Request $request): array
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
            ->get(['id', 'name']);

        $leadProducts = $this->getLeadProductBaseQuery($request)->get(['id', 'lead_status_id', 'product_status']);

        if ($statuses->isNotEmpty() && $leadProducts->isNotEmpty()) {
            $statusByName = [];
            foreach ($statuses as $s) {
                $statusByName[strtolower(trim($s->name))] = $s->id;
            }

            $counts = [];
            foreach ($statuses as $s) {
                $counts[$s->id] = 0;
            }

            foreach ($leadProducts as $lp) {
                if (!empty($lp->lead_status_id) && isset($counts[$lp->lead_status_id])) {
                    $counts[$lp->lead_status_id]++;
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
                  ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '>=', $dateFrom)->orWhereDate('created_at', '>=', $dateFrom));
            });
        }

        if ($dateTo) {
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('created_at', '<=', $dateTo)
                  ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '<=', $dateTo)->orWhereDate('created_at', '<=', $dateTo));
            });
        }

        return $query;
    }

    private function resolveDates(Request $request): array
    {
        if ($request->filled('quick_date')) {
            return match ($request->quick_date) {
                'all'     => [null, null],
                'today'   => [today()->toDateString(), today()->toDateString()],
                'week'    => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
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
}
