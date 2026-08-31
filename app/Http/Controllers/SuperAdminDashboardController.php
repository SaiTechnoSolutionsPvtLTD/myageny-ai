<?php
// ================================================================
// FILE: app/Http/Controllers/Api/V1/SuperAdminDashboardController.php
// Full Super Admin Dashboard API with all filters and sections
// Endpoint: GET /api/v1/dashboard/admin
// ================================================================

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\LeadCallUpdate;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadReminder;
use App\Models\LeadStatus;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Carbon\Carbon;

class SuperAdminDashboardController extends ApiController
{
    public function __construct(private readonly DataVisibilityService $visibility) {}


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
            'quick_date' => ['nullable', 'in:all,today,week,month,quarter,year'],
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
        $wonLeads      = (clone $base())->where('lead_status', 'won')->count();
        $lostLeads     = (clone $base())->where('lead_status', 'lost')->count();
        $activeLeads   = $totalLeads - $wonLeads - $lostLeads;
        $pipelineValue = (float)(clone $base())->whereNotIn('lead_status', ['won', 'lost'])->sum('deal_value');
        $wonValue      = (float)(clone $base())->where('lead_status', 'won')->sum('deal_value');
        $highPriority  = (clone $base())->where('priority', 'high')->whereNotIn('lead_status', ['won', 'lost'])->count();
        $convRate      = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;

        // ── 2. Pipeline funnel from lead_products.lead_status_id ───
        $leadIds = (clone $base())->pluck('id');
        $lpBase  = $this->getLeadProductBaseQuery($request);
        $lpProducts = (clone $lpBase)->with('payments')->get();

        $convertedProductsCount = $lpProducts->where('product_status', 'converted')->count();
        $upcomingAmount = (float) $lpProducts->where('product_status', '!=', 'converted')->sum('total_price');
        $convertedValue = (float) $lpProducts->where('product_status', 'converted')->sum('total_price');
        $totalProductsCount = $lpProducts->count();
        $convertedPercentage = $totalProductsCount > 0 ? round(($convertedProductsCount / $totalProductsCount) * 100, 1) : 0;

        $followupsCount = \App\Models\LeadReminder::where('is_completed', false)
            ->whereIn('lead_id', $leadIds)
            ->whereDate('remind_at', today())
            ->count();
        $productStatusFunnel = $this->buildProductStatusFunnel($leadIds, $request);
        $stageTotal = $productStatusFunnel['total'];
        $stageFunnel = $productStatusFunnel['stages'];

        // ── 3. Source counts (from enum) ───────────────────────────
        $sourceCounts = [];
        $sourceTotal  = 0;
        foreach (Lead::sourceOptions() as $key => $label) {
            $count = (clone $base())
                ->where(function ($q) use ($key, $label) {
                    $q->where('lead_source_id', $key)
                        ->orWhere('lead_source', $label);
                })
                ->count();
            $sourceTotal += $count;
            $sourceCounts[] = ['key' => $key, 'label' => $label, 'count' => $count];
        }
        foreach ($sourceCounts as &$src) {
            $src['percent'] = $sourceTotal > 0 ? round($src['count'] / $sourceTotal * 100, 1) : 0;
        }
        unset($src);

        // ── 4. Financials (from lead_products + payments) ─────────
        $totalProductValue = (float) $lpProducts->sum('total_price');
        $totalPaid         = (float) $lpProducts->sum(fn (LeadProduct $lp) => $lp->amount_paid);
        $totalPending      = max(0, $totalProductValue - $totalPaid);
        $convertedCount    = $convertedProductsCount;
        $payPct            = $totalProductValue > 0 ? round($totalPaid / $totalProductValue * 100, 1) : 0;

        $leadProductIds = $lpProducts->pluck('id');
        $paymentByMode = LeadProductPayment::whereIn('lead_product_id', $leadProductIds)
            ->select('payment_mode', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as txn_count'))
            ->groupBy('payment_mode')
            ->orderByDesc('total')
            ->get()
            ->map(fn($pm) => [
                'mode'      => $pm->payment_mode,
                'mode_label' => LeadProduct::PAYMENT_MODES[$pm->payment_mode] ?? ucfirst($pm->payment_mode),
                'total'     => (float) $pm->total,
                'txn_count' => (int)   $pm->txn_count,
            ]);

        // Product status distribution
        $productStatusDist = [];
        foreach (LeadProduct::PRODUCT_STATUSES as $pkey => $plabel) {
            $cnt = $lpProducts->where('product_status', $pkey)->count();
            $productStatusDist[] = [
                'status'  => $pkey,
                'label'   => $plabel,
                'count'   => $cnt,
                'config'  => LeadProduct::PRODUCT_STATUS_CONFIG[$pkey] ?? [],
            ];
        }

        // ── 5. Today's follow-ups / Scheduled Followups ───────────
        $currentUser = $request->user();
        $isUserAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();
        $effectiveUserId = $request->filled('user_id') ? (int) $request->user_id : ($isUserAdmin ? null : (int) $currentUser->id);

        $todayFollowupsQuery = LeadCallUpdate::whereDate('next_follow_up', today())
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());

                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                    ->when($stage,    fn($q2) => $q2->where('lead_status', $stage))
                    ->when($source,   fn($q2) => $q2->where('lead_source_id', $source));
            });

        if ($effectiveUserId) {
            $todayFollowupsQuery->where(function ($q) use ($effectiveUserId) {
                $q->where('user_id', $effectiveUserId)
                  ->orWhereHas('lead', fn($lq) => $lq->where('assigned_to', $effectiveUserId));
            });
        }

        $todayFollowups = $todayFollowupsQuery
            ->with([
                'lead:id,company_name,contact_name,mobile_number,lead_status,branch_id,assigned_to',
                'lead.branch:id,name',
                'lead.assignedTo:id,name',
                'user:id,name'
            ])
            ->latest()
            ->take(15)
            ->get()
            ->map(fn($fu) => [
                'id'              => $fu->id,
                'called_at'       => $fu->called_at?->toISOString(),
                'call_type'       => $fu->call_type,
                'call_type_label' => $fu->call_type_label,
                'outcome'         => $fu->outcome,
                'outcome_label'   => $fu->outcome_label,
                'outcome_color'   => $fu->outcome_color,
                'duration_minutes' => $fu->duration_minutes,
                'notes'           => $fu->notes,
                'next_follow_up'  => $fu->next_follow_up?->toDateString(),
                'logged_by'       => ['id' => $fu->user?->id, 'name' => $fu->user?->name],
                'lead'            => [
                    'id'           => $fu->lead?->id,
                    'company_name' => $fu->lead?->company_name,
                    'contact_name' => $fu->lead?->contact_name,
                    'mobile_number' => $fu->lead?->mobile_number,
                    'lead_status'  => $fu->lead?->lead_status,
                    'branch'       => ['id' => $fu->lead?->branch?->id, 'name' => $fu->lead?->branch?->name],
                    'assigned_to'  => ['id' => $fu->lead?->assignedTo?->id, 'name' => $fu->lead?->assignedTo?->name],
                ],
            ]);

        // ── 6. Pending reminders today ────────────────────────────
        $reminderQuery = fn() => LeadReminder::where('is_completed', false)
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());
                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                  ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                  ->when($stage, fn($q2) => $q2->where('lead_status', $stage))
                  ->when($source, fn($q2) => $q2->where('lead_source_id', $source));
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
                'remind_at'   => optional($r->remind_at)->toISOString() ?: optional($r->remind_at)->format('Y-m-d H:i:s'),
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
                'remind_at'   => optional($r->remind_at)->toISOString() ?: optional($r->remind_at)->format('Y-m-d H:i:s'),
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
                'id'            => $l->id,
                'lead_number'   => 'LD-' . str_pad($l->id, 4, '0', STR_PAD_LEFT),
                'company_name'  => $l->company_name,
                'contact_name'  => $l->contact_name,
                'mobile_number' => $l->mobile_number,
                'lead_date'     => $l->lead_date->toDateString(),
                'lead_source'   => $l->lead_source,
                'source_label'  => $l->source_label,
                'lead_status'   => $l->lead_status,
                'status_label'  => $l->status_label,
                'status_color'  => $l->status_color,
                'priority'      => $l->priority,
                'priority_label' => $l->priority_label,
                'priority_color' => $l->priority_color,
                'deal_value'    => (float) $l->deal_value,
                'deal_value_formatted' => $l->formatted_deal_value,
                'branch'        => ['id' => $l->branch?->id, 'name' => $l->branch?->name],
                'assigned_to'   => ['id' => $l->assignedTo?->id, 'name' => $l->assignedTo?->name],
            ]);

        // ── 8. Branch-wise performance ────────────────────────────
        $visibleBranchIds = $this->visibility->visibleBranchIds($request->user());
        $branchPerformance = Branch::where('is_active', true)
            ->when($visibleBranchIds->isNotEmpty(), fn($query) => $query->whereIn('id', $visibleBranchIds))
            ->when($visibleBranchIds->isEmpty() && $this->visibility->companyIdFor($request->user()), fn($query) => $query->whereRaw('1 = 0'))
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
                $leadIds = (clone $q)->pluck('id');
                $convertCount = LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->count();
                $lost   = (clone $q)->where('lead_status', 'lost')->count();
                $convertVal = (float) LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->sum('total_price');

                return [
                    'user_id'         => $user->id,
                    'user_name'       => $user->name,
                    'user_email'      => $user->email,
                    'role'            => $user->roles->first()?->display_name,
                    'role_name'       => $user->roles->first()?->name,
                    'total_leads'     => $total,
                    'convert_leads'   => $convertCount,
                    'lost_leads'      => $lost,
                    'active_leads'    => $total - $convertCount - $lost,
                    'convert_value'   => $convertVal,
                    'conversion_rate' => $total > 0 ? round($convertCount / $total * 100, 1) : 0,
                ];
            })
            ->filter(fn($u) => $u['total_leads'] > 0)
            ->sortByDesc('convert_value')
            ->values()
            ->take(10);

        // ── 10. 6-month trend ────────────────────────────────────
        $monthTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $q = Lead::whereYear('lead_date', $month->year)
                ->whereMonth('lead_date', $month->month)
                ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                ->when($userId,   fn($q2) => $q2->where('assigned_to', $userId));
            $this->visibility->applyLeadVisibility($q, $request->user());

            $leadIds = (clone $q)->pluck('id');
            $convertCount = (clone $q)->whereHas('products', fn($qp) => $qp->where('product_status', 'converted'))->count();
            $convertVal = (float) LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->sum('total_price');

            $monthTrend[] = [
                'month'       => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'year'        => (int) $month->format('Y'),
                'month_num'   => (int) $month->format('m'),
                'total'       => (clone $q)->count(),
                'convert'     => $convertCount,
                'lost'        => (clone $q)->where('lead_status', 'lost')->count(),
                'convert_value' => $convertVal,
            ];
        }

        // ── Active filters applied ────────────────────────────────
        $filtersApplied = array_filter([
            'branch_id'  => $branchId,
            'user_id'    => $userId,
            'stage'      => $request->stage,
            'source'     => $request->source,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'quick_date' => $request->quick_date,
            'year'       => $request->year,
        ]);

        $todayCompletedCallsCount = LeadCallUpdate::whereDate('called_at', today())
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());
                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                    ->when($stage,    fn($q2) => $q2->where('lead_status', $stage))
                    ->when($source,   fn($q2) => $q2->where('lead_source_id', $source));
            })->count();

        // ── Build response ────────────────────────────────────────
        return $this->success([

            'filters_applied' => $filtersApplied,

            'kpis' => [
                'total_leads'                => $totalLeads,
                'active_leads'               => $activeLeads,
                'won_leads'                  => $wonLeads,
                'lost_leads'                 => $lostLeads,
                'high_priority'              => $highPriority,
                'pipeline_value'             => $pipelineValue,
                'won_value'                  => $wonValue,
                'conversion_rate'            => $convRate,
                'converted_products_count'  => $convertedProductsCount,
                'upcoming_amount'            => $upcomingAmount,
                'converted_value'            => $convertedValue,
                'converted_percentage'       => $convertedPercentage,
                'followups_count'            => $followupsCount,
                'scheduled_followups_count'  => $todayFollowups->count(),
                'overdue_reminders_count'    => $overdueCount,
                'today_completed_calls_count' => $todayCompletedCallsCount,
            ],

            'financials' => [
                'total_product_value'  => $totalProductValue,
                'amount_paid'          => $totalPaid,
                'amount_pending'       => $totalPending,
                'converted_value'      => $convertedValue,
                'converted_count'      => $convertedCount,
                'payment_percent'      => $payPct,
                'payment_by_mode'      => $paymentByMode,
                'product_status_dist'  => $productStatusDist,
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
                'count'  => $todayFollowups->count(),
                'items'  => $todayFollowups,
            ],

            'today_scheduled_followups' => [
                'count'  => $todayFollowups->count(),
                'items'  => $todayFollowups,
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

            // Enum references for mobile UI
            'enums' => [
                'statuses'         => Lead::statusOptions(),
                'sources'          => Lead::sourceOptions(),
                'priorities'       => Lead::PRIORITIES,
                'status_colors'    => Lead::STATUS_COLORS,
                'priority_colors'  => Lead::PRIORITY_COLORS,
                'product_statuses' => LeadProduct::PRODUCT_STATUSES,
                'payment_modes'    => LeadProduct::PAYMENT_MODES,
            ],

        ], 'Super Admin Dashboard data fetched.');
    }

    private function getSalesTargetStats($branchId, $userId, $dateFrom, $dateTo, $currentUser): array
    {
        $targetUserId = $userId ?: (!$currentUser->can('settings.manage') ? $currentUser->id : null);

        if ($targetUserId) {
            $target = (float) \App\Models\SalesTarget::where('user_id', $targetUserId)->value('target_amount');
            $userObj = \App\Models\User::find($targetUserId);
            $targetName = $userObj ? $userObj->name : 'Representative';
            $isIndividual = true;
            $title = "{$targetName}'s Target";
        } else {
            $effectiveBranchId = $branchId ?: $currentUser->branch_id;

            if ($effectiveBranchId) {
                $branchObj = \App\Models\Branch::find($effectiveBranchId);
                $targetName = $branchObj ? $branchObj->name : 'Branch';

                $userIds = \App\Models\User::where('branch_id', $effectiveBranchId)->pluck('id');
                $target = (float) \App\Models\SalesTarget::whereIn('user_id', $userIds)->sum('target_amount');
                $isIndividual = false;
                $title = "{$targetName} Target";
            } else {
                $target = (float) \App\Models\SalesTarget::sum('target_amount');
                $targetName = 'Overall';
                $isIndividual = false;
                $title = 'Overall Sales Target';
            }
        }

        $achievedQuery = \App\Models\LeadProductPayment::query();
        if ($targetUserId) {
            $achievedQuery->whereHas('lead', function ($q) use ($targetUserId) {
                $q->where('assigned_to', $targetUserId);
            });
        } elseif (isset($effectiveBranchId) && $effectiveBranchId) {
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

    // ── Private: resolve date range from quick_date or explicit dates ──
    private function resolveDates(Request $request): array
    {
        // Year-wise dropdown — an explicit calendar year takes priority over
        // quick_date/date_from/date_to, since it's a separate, more specific
        // selection in the UI (pick any past year, not just "this year").
        if ($request->filled('year')) {
            $year = (int) $request->year;
            return [
                Carbon::create($year, 1, 1)->toDateString(),
                Carbon::create($year, 12, 31)->toDateString(),
            ];
        }

        if ($request->filled('quick_date')) {
            return match ($request->quick_date) {
                // 'All' — no date restriction at all. Every consumer below
                // already uses ->when($dateFrom, ...)/->when($dateTo, ...),
                // so returning nulls here is sufficient; no other query needs
                // to change to support it.
                'all'     => [null, null],
                'today'   => [today()->toDateString(), today()->toDateString()],
                'week'    => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                'month'   => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                'quarter' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
                'year'    => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                default   => [null, null],
            };
        }
        return [
            $this->parseDateInput($request->date_from),
            $this->parseDateInput($request->date_to),
        ];
    }

    private function parseDateInput(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);

        try {
            if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $dateStr, $matches)) {
                return Carbon::createFromDate((int)$matches[3], (int)$matches[2], (int)$matches[1])->toDateString();
            }

            return Carbon::parse($dateStr)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getLeadProductBaseQuery(Request $request, $customFrom = null, $customTo = null)
    {
        [$resolvedFrom, $resolvedTo] = $this->resolveDates($request);
        $dateFrom = $customFrom !== null ? $customFrom : $resolvedFrom;
        $dateTo   = $customTo !== null ? $customTo : $resolvedTo;
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

    /*
|--------------------------------------------------------------------------
| Home Page Dashboard Routes
|--------------------------------------------------------------------------
*/

    public function dashboardData(Request $request)
    {
        $request->validate([
            'branch_id'  => ['nullable', 'exists:branches,id'],
            'user_id'    => ['nullable', 'exists:users,id'],
            'stage'      => ['nullable', Rule::in(Lead::statusKeys())],
            'source'     => ['nullable', Rule::in(Lead::sourceKeys())],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
            'quick_date' => ['nullable', 'in:all,today,week,month,quarter,year'],
            // Year-wise filter — an explicit calendar year (e.g. 2024), distinct
            // from the 'year' quick_date value (which always means "this year").
            // Takes priority over quick_date/date_from/date_to when present —
            // see resolveDates().
            'year'       => ['nullable', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
        ]);

        // ── Resolve dates ──────────────────────────────────────────
        [$dateFrom, $dateTo] = $this->resolveDates($request);



        $branchId = $request->branch_id;
        $userId   = $request->user_id;
        $stage    = $request->stage;
        $source   = $request->source;

        [$prevFrom, $prevTo, $comparisonLabel] = $this->resolvePreviousPeriod(
            $request->quick_date,
            $dateFrom,
            $dateTo,
            $request->filled('year') ? (int) $request->year : null
        );

        $prevBase = function () use ($request, $branchId, $userId, $stage, $source, $prevFrom, $prevTo) {
            $query = Lead::query();
            $this->visibility->applyLeadVisibility($query, $request->user());

            return $query
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($userId, fn($q) => $q->where('assigned_to', $userId))
                ->when($stage, fn($q) => $q->where('lead_status', $stage))
                ->when($source, fn($q) => $q->where('lead_source_id', $source))
                ->when($prevFrom, fn($q) => $q->whereDate('lead_date', '>=', $prevFrom))
                ->when($prevTo, fn($q) => $q->whereDate('lead_date', '<=', $prevTo));
        };

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
        $wonLeads      = (clone $base())->where('lead_status', 'won')->count();
        $lostLeads     = (clone $base())->where('lead_status', 'lost')->count();
        $activeLeads   = $totalLeads - $wonLeads - $lostLeads;
        $pipelineValue = (float)(clone $base())->whereNotIn('lead_status', ['won', 'lost'])->sum('deal_value');
        $wonValue      = (float)(clone $base())->where('lead_status', 'won')->sum('deal_value');
        $highPriority  = (clone $base())->where('priority', 'high')->whereNotIn('lead_status', ['won', 'lost'])->count();
        $convRate      = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;

        $prevTotalLeads    = (clone $prevBase())->count();
        $prevWonLeads      = (clone $prevBase())->where('lead_status', 'won')->count();
        $prevLostLeads     = (clone $prevBase())->where('lead_status', 'lost')->count();
        $prevActiveLeads   = $prevTotalLeads - $prevWonLeads - $prevLostLeads;
        $prevPipelineValue = (float)(clone $prevBase())->whereNotIn('lead_status', ['won', 'lost'])->sum('deal_value');
        $prevWonValue      = (float)(clone $prevBase())->where('lead_status', 'won')->sum('deal_value');
        $prevHighPriority  = (clone $prevBase())->where('priority', 'high')->whereNotIn('lead_status', ['won', 'lost'])->count();
        $prevConvRate      = $prevTotalLeads > 0 ? round($prevWonLeads / $prevTotalLeads * 100, 1) : 0.0;

        $prevLpBase = $this->getLeadProductBaseQuery($request, $prevFrom, $prevTo);
        $prevLpProducts = (clone $prevLpBase)->with('payments')->get();

        $prevTotalProductValue = (float) $prevLpProducts->sum('total_price');
        $prevTotalPaid         = (float) $prevLpProducts->sum(fn (LeadProduct $lp) => $lp->amount_paid);
        $prevTotalPending      = max(0, $prevTotalProductValue - $prevTotalPaid);
        $prevConvertedValue    = (float) $prevLpProducts->where('product_status', 'converted')->sum('total_price');

        // ── 2. Pipeline funnel from lead_products.lead_status_id ───
        $leadIds = (clone $base())->pluck('id');
        $lpBase  = $this->getLeadProductBaseQuery($request);
        $lpProducts = (clone $lpBase)->with('payments')->get();

        $convertedProductsCount = $lpProducts->where('product_status', 'converted')->count();
        $upcomingAmount = (float) $lpProducts->where('product_status', '!=', 'converted')->sum('total_price');
        $convertedValue = (float) $lpProducts->where('product_status', 'converted')->sum('total_price');
        $totalProductsCount = $lpProducts->count();
        $convertedPercentage = $totalProductsCount > 0 ? round(($convertedProductsCount / $totalProductsCount) * 100, 1) : 0;

        $followupsCount = \App\Models\LeadReminder::where('is_completed', false)
            ->whereIn('lead_id', $leadIds)
            ->whereDate('remind_at', today())
            ->count();
        $productStatusFunnel = $this->buildProductStatusFunnel($leadIds, $request);
        $stageTotal = $productStatusFunnel['total'];
        $stageFunnel = $productStatusFunnel['stages'];

        // ── 3. Source counts (from enum) ───────────────────────────
        $sourceCounts = [];
        $sourceTotal  = 0;
        foreach (Lead::sourceOptions() as $key => $label) {
            $count = (clone $base())
                ->where(function ($q) use ($key, $label) {
                    $q->where('lead_source_id', $key)
                        ->orWhere('lead_source', $label);
                })
                ->count();
            $sourceTotal += $count;
            $sourceCounts[] = ['key' => $key, 'label' => $label, 'count' => $count];
        }
        foreach ($sourceCounts as &$src) {
            $src['percent'] = $sourceTotal > 0 ? round($src['count'] / $sourceTotal * 100, 1) : 0;
        }
        unset($src);

        // ── 4. Financials (from lead_products + payments) ─────────
        $totalProductValue = (float) $lpProducts->sum('total_price');
        $totalPaid         = (float) $lpProducts->sum(fn (LeadProduct $lp) => $lp->amount_paid);
        $totalPending      = max(0, $totalProductValue - $totalPaid);
        $convertedCount    = $convertedProductsCount;
        $payPct            = $totalProductValue > 0 ? round($totalPaid / $totalProductValue * 100, 1) : 0;

        $leadProductIds = $lpProducts->pluck('id');
        $paymentByMode = LeadProductPayment::whereIn('lead_product_id', $leadProductIds)
            ->select('payment_mode', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as txn_count'))
            ->groupBy('payment_mode')
            ->orderByDesc('total')
            ->get()
            ->map(fn($pm) => [
                'mode'      => $pm->payment_mode,
                'mode_label' => LeadProduct::PAYMENT_MODES[$pm->payment_mode] ?? ucfirst($pm->payment_mode),
                'total'     => (float) $pm->total,
                'txn_count' => (int)   $pm->txn_count,
            ]);

        // Product status distribution
        $productStatusDist = [];
        foreach (LeadProduct::PRODUCT_STATUSES as $pkey => $plabel) {
            $cnt = $lpProducts->where('product_status', $pkey)->count();
            $productStatusDist[] = [
                'status'  => $pkey,
                'label'   => $plabel,
                'count'   => $cnt,
                'config'  => LeadProduct::PRODUCT_STATUS_CONFIG[$pkey] ?? [],
            ];
        }

        // ── 5. Today's follow-ups / Scheduled Followups ───────────
        $currentUser = $request->user();
        $isUserAdmin = $currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole();
        $effectiveUserId = $request->filled('user_id') ? (int) $request->user_id : ($isUserAdmin ? null : (int) $currentUser->id);

        $todayFollowupsQuery = LeadCallUpdate::whereDate('next_follow_up', today())
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());

                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                    ->when($stage,    fn($q2) => $q2->where('lead_status', $stage))
                    ->when($source,   fn($q2) => $q2->where('lead_source_id', $source));
            });

        if ($effectiveUserId) {
            $todayFollowupsQuery->where(function ($q) use ($effectiveUserId) {
                $q->where('user_id', $effectiveUserId)
                  ->orWhereHas('lead', fn($lq) => $lq->where('assigned_to', $effectiveUserId));
            });
        }

        $todayFollowups = $todayFollowupsQuery
            ->with([
                'lead:id,company_name,contact_name,mobile_number,lead_status,branch_id,assigned_to',
                'lead.branch:id,name',
                'lead.assignedTo:id,name',
                'user:id,name'
            ])
            ->latest()
            ->take(15)
            ->get()
            ->map(fn($fu) => [
                'id'              => $fu->id,
                'called_at'       => $fu->called_at?->toISOString(),
                'call_type'       => $fu->call_type,
                'call_type_label' => $fu->call_type_label,
                'outcome'         => $fu->outcome,
                'outcome_label'   => $fu->outcome_label,
                'outcome_color'   => $fu->outcome_color,
                'duration_minutes' => $fu->duration_minutes,
                'notes'           => $fu->notes,
                'next_follow_up'  => $fu->next_follow_up?->toDateString(),
                'logged_by'       => ['id' => $fu->user?->id, 'name' => $fu->user?->name],
                'lead'            => [
                    'id'           => $fu->lead?->id,
                    'company_name' => $fu->lead?->company_name,
                    'contact_name' => $fu->lead?->contact_name,
                    'mobile_number' => $fu->lead?->mobile_number,
                    'lead_status'  => $fu->lead?->lead_status,
                    'branch'       => ['id' => $fu->lead?->branch?->id, 'name' => $fu->lead?->branch?->name],
                    'assigned_to'  => ['id' => $fu->lead?->assignedTo?->id, 'name' => $fu->lead?->assignedTo?->name],
                ],
            ]);

        // ── 6. Pending reminders today ────────────────────────────
        $reminderQuery = fn() => LeadReminder::where('is_completed', false)
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());
                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                  ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                  ->when($stage, fn($q2) => $q2->where('lead_status', $stage))
                  ->when($source, fn($q2) => $q2->where('lead_source_id', $source));
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
                'remind_at'   => optional($r->remind_at)->toISOString() ?: optional($r->remind_at)->format('Y-m-d H:i:s'),
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
                'remind_at'   => optional($r->remind_at)->toISOString() ?: optional($r->remind_at)->format('Y-m-d H:i:s'),
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
            ->with(['branch:id,name', 'assignedTo:id,name', 'leadSource:id,name', 'products.leadSource:id,name'])
            ->latest('lead_date')
            ->take(8)
            ->get()
            ->map(function ($l) {
                $dealValue = $l->products->sum('total_price');
                $sourceName = $l->leadSource?->name
                    ?: ($l->lead_source
                    ?: ($l->products->first()?->leadSource?->name
                    ?: ($l->source_label
                    ?: '—')));

                return [
                    'id'                     => $l->id,
                    'lead_number'            => 'LD-' . str_pad($l->id, 4, '0', STR_PAD_LEFT),
                    'company_name'           => $l->company_name,
                    'contact_name'           => $l->contact_name,
                    'mobile_number'          => $l->mobile_number,
                    'lead_date'              => $l->lead_date->toDateString(),
                    'lead_source'            => $sourceName,
                    'source_label'           => $sourceName,
                    'lead_status'            => $l->lead_status,
                    'status_label'           => $l->status_label,
                    'status_color'           => $l->status_color,
                    'priority'               => $l->priority,
                    'priority_label'         => $l->priority_label,
                    'priority_color'         => $l->priority_color,
                    'deal_value'             => $dealValue,
                    'deal_value_formatted'   => number_format($dealValue, 2, '.', ''),
                    'branch'                 => [
                        'id' => $l->branch?->id,
                        'name' => $l->branch?->name,
                    ],
                    'assigned_to'            => [
                        'id' => $l->assignedTo?->id,
                        'name' => $l->assignedTo?->name,
                    ],
                ];
            });

        // ── 8. Branch-wise performance ────────────────────────────
        $visibleBranchIds = $this->visibility->visibleBranchIds($request->user());
        $branchPerformance = Branch::where('is_active', true)
            ->when($visibleBranchIds->isNotEmpty(), fn($query) => $query->whereIn('id', $visibleBranchIds))
            ->when($visibleBranchIds->isEmpty() && $this->visibility->companyIdFor($request->user()), fn($query) => $query->whereRaw('1 = 0'))
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
                $leadIds = (clone $q)->pluck('id');
                $convertCount = LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->count();
                $lost   = (clone $q)->where('lead_status', 'lost')->count();
                $convertVal = (float) LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->sum('total_price');

                return [
                    'user_id'         => $user->id,
                    'user_name'       => $user->name,
                    'user_email'      => $user->email,
                    'role'            => $user->roles->first()?->name,
                    'role_name'       => $user->roles->first()?->display_name,
                    'total_leads'     => $total,
                    'convert_leads'   => $convertCount,
                    'lost_leads'      => $lost,
                    'active_leads'    => $total - $convertCount - $lost,
                    'convert_value'   => $convertVal,
                    'conversion_rate' => $total > 0 ? round($convertCount / $total * 100, 1) : 0,
                ];
            })
            ->filter(fn($u) => $u['total_leads'] > 0)
            ->sortByDesc('convert_value')
            ->values()
            ->take(10);

        // ── 10. 6-month trend ────────────────────────────────────
        $monthTrend = [];
        $trendMonths = [];
        $trendStart = now()->startOfMonth()->subMonths(5);
        for ($i = 0; $i < 6; $i++) {
            $month = (clone $trendStart)->addMonths($i);
            $monthKey = $month->format('Y-m');
            if (isset($trendMonths[$monthKey])) {
                continue;
            }
            $trendMonths[$monthKey] = true;
            $q = Lead::whereYear('lead_date', $month->year)
                ->whereMonth('lead_date', $month->month)
                ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                ->when($userId,   fn($q2) => $q2->where('assigned_to', $userId));
            $this->visibility->applyLeadVisibility($q, $request->user());

            $leadIds = (clone $q)->pluck('id');
            $convertCount = (clone $q)->whereHas('products', fn($qp) => $qp->where('product_status', 'converted'))->count();
            $convertVal = (float) LeadProduct::whereIn('lead_id', $leadIds)->where('product_status', 'converted')->sum('total_price');

            $monthTrend[] = [
                'month'       => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'year'        => (int) $month->format('Y'),
                'month_num'   => (int) $month->format('m'),
                'total'       => (clone $q)->count(),
                'convert'     => $convertCount,
                'lost'        => (clone $q)->where('lead_status', 'lost')->count(),
                'convert_value' => $convertVal,
            ];
        }

        // ── Active filters applied ────────────────────────────────
        $filtersApplied = array_filter([
            'branch_id'  => $branchId,
            'user_id'    => $userId,
            'stage'      => $request->stage,
            'source'     => $request->source,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'quick_date' => $request->quick_date,
            'year'       => $request->year,
        ]);

        $overdueCount = LeadReminder::where('is_completed', false)
            ->whereHas('lead', function ($q) use ($request, $branchId, $userId) {
                $this->visibility->applyLeadVisibility($q, $request->user());
                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($userId,   fn($q2) => $q2->where('assigned_to', $userId));
            })
            ->whereDate('remind_at', '<', today())
            ->count();

        $todayCompletedCallsCount = LeadCallUpdate::whereDate('called_at', today())
            ->whereHas('lead', function ($q) use ($request, $branchId, $userId) {
                $this->visibility->applyLeadVisibility($q, $request->user());
                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($userId,   fn($q2) => $q2->where('assigned_to', $userId));
            })->count();

        // ── Build response ────────────────────────────────────────
        return $this->success([

            'filters_applied' => $filtersApplied,

            'kpis' => [
                'total_leads'       => $totalLeads,
                'active_leads'      => $activeLeads,
                'won_leads'         => $wonLeads,
                'lost_leads'        => $lostLeads,
                'followups_count'   => $followupsCount,
                'pipeline_value'    => $pipelineValue,
                'won_value'         => $wonValue,
                'conversion_rate'   => $convRate,
                'converted_products_count' => $convertedProductsCount,
                'upcoming_amount'   => $upcomingAmount,
                'converted_value'   => $convertedValue,
                'converted_percentage' => $convertedPercentage,
                'scheduled_followups_count'  => $todayFollowups->count(),
                'overdue_reminders_count'    => $overdueCount,
                'today_completed_calls_count' => $todayCompletedCallsCount,
            ],

            'trends' => [
                'comparison_label'    => $comparisonLabel,
                'total_leads'         => $this->buildTrend((float) $totalLeads,    (float) $prevTotalLeads,    true),
                'active_leads'        => $this->buildTrend((float) $activeLeads,   (float) $prevActiveLeads,   true),
                'won_leads'           => $this->buildTrend((float) $wonLeads,      (float) $prevWonLeads,      true),
                'lost_leads'          => $this->buildTrend((float) $lostLeads,     (float) $prevLostLeads,     false),
                'pipeline_value'      => $this->buildTrend($pipelineValue,          $prevPipelineValue,          true),
                'won_value'           => $this->buildTrend($wonValue,               $prevWonValue,               true),
                'conversion_rate'     => $this->buildPointsTrend($convRate,         $prevConvRate,               true),
                'high_priority'       => $this->buildTrend((float) $highPriority,  (float) $prevHighPriority,  false),
                // Payment Financials cards:
                'total_product_value' => $this->buildTrend($totalProductValue, $prevTotalProductValue, true),
                'amount_paid'         => $this->buildTrend($totalPaid,          $prevTotalPaid,          true),
                'amount_pending'      => $this->buildTrend($totalPending,       $prevTotalPending,       false),
                'converted_value'     => $this->buildTrend($convertedValue,     $prevConvertedValue,     true),
            ],

            'financials' => [
                'total_product_value'  => $totalProductValue,
                'amount_paid'          => $totalPaid,
                'amount_pending'       => $totalPending,
                'converted_value'      => $convertedValue,
                'converted_count'      => $convertedCount,
                'payment_percent'      => $payPct,
                'payment_by_mode'      => $paymentByMode,
                'product_status_dist'  => $productStatusDist,
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
                'count'  => $todayFollowups->count(),
                'items'  => $todayFollowups,
            ],

            'today_scheduled_followups' => [
                'count'  => $todayFollowups->count(),
                'items'  => $todayFollowups,
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

            // Enum references for mobile UI
            'enums' => [
                'statuses'         => Lead::statusOptions(),
                'sources'          => Lead::sourceOptions(),
                'priorities'       => Lead::PRIORITIES,
                'status_colors'    => Lead::STATUS_COLORS,
                'priority_colors'  => Lead::PRIORITY_COLORS,
                'product_statuses' => LeadProduct::PRODUCT_STATUSES,
                'payment_modes'    => LeadProduct::PAYMENT_MODES,
                'users' => $this->visibility->visibleAssignableUsers($request->user())
                    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                    ->values(),
            ],

        ], 'Super Admin Dashboard data fetched.');
    }

    public function adminProductindex(Request $request): View
    {
        $user = $request->user();
        $user->tokens()->where('name', 'dashboard-session')->delete();
        $apiToken = $user->createToken('dashboard-session')->plainTextToken;

        // Dropdown options for filter selects (server-side for initial render)
        $productQuery = \App\Models\Product::query();
        $this->visibility->applyProductVisibility($productQuery, $user);
        $products = $productQuery->select('id', 'product_name')->orderBy('product_name')->get();
        $branches = $this->visibility->visibleBranches($user);
        $users    = $this->visibility->visibleAssignableUsers($user);
        $sources  = $this->visibility->visibleLeadSources($user);
        $statuses = ['new', 'hot', 'warm', 'cold', 'converted', 'lost'];

        return view('pages.dashboard.leads.admin-product-wise-dashboard', compact('products', 'branches', 'users', 'sources', 'statuses', 'apiToken'));
    }

    private function resolvePreviousPeriod(?string $quickDate, ?string $dateFrom, ?string $dateTo, ?int $year = null): array
    {
        // Year-wise dropdown — compare against the same calendar range one
        // year earlier, same convention as the 'year' quick_date case below.
        if ($year) {
            $prevYear = $year - 1;
            return [
                Carbon::create($prevYear, 1, 1)->toDateString(),
                Carbon::create($prevYear, 12, 31)->toDateString(),
                "vs {$prevYear}",
            ];
        }

        if ($quickDate) {
            return match ($quickDate) {
                // 'All' has no meaningful "previous period" to compare
                // against — a null comparison_label tells the Flutter side to
                // hide the vs-badge entirely rather than show a misleading
                // comparison (see dashboard_filter_bar.dart / trend cards).
                'all' => [null, null, null],
                'today' => [
                    now()->subDay()->toDateString(),
                    now()->subDay()->toDateString(),
                    'vs Yesterday',
                ],
                'week' => [
                    now()->subWeek()->startOfWeek()->toDateString(),
                    now()->subWeek()->endOfWeek()->toDateString(),
                    'vs Previous Week',
                ],
                'month' => [
                    now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                    now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                    'vs Previous Month',
                ],
                'quarter' => [
                    now()->subQuarter()->startOfQuarter()->toDateString(),
                    now()->subQuarter()->endOfQuarter()->toDateString(),
                    'vs Previous Quarter',
                ],
                'year' => [
                    now()->subYear()->startOfYear()->toDateString(),
                    now()->subYear()->endOfYear()->toDateString(),
                    'vs Previous Year',
                ],
                default => [null, null, 'vs Previous Period'],
            };
        }

        if ($dateFrom && $dateTo) {
            $from = Carbon::parse($dateFrom);
            $to   = Carbon::parse($dateTo);
            $days = $from->diffInDays($to) + 1;

            return [
                $from->copy()->subDays($days)->toDateString(),
                $from->copy()->subDay()->toDateString(),
                'vs Previous Period',
            ];
        }

        // No date filter active at all (shouldn't normally happen — mobile always
        // defaults quick_date to 'month') — fall back to month-over-month so the
        // chips still have a meaningful baseline instead of going blank.
        return [
            now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            'vs Previous Month',
        ];
    }

    private function buildTrend(float $current, float $previous, bool $higherIsBetter): array
    {
        if ($previous == 0.0 && $current == 0.0) {
            return [
                'current'          => $current,
                'previous'         => $previous,
                'change_percent'   => 0.0,
                'direction'        => 'flat',
                'higher_is_better' => $higherIsBetter,
            ];
        }

        if ($previous == 0.0) {
            // Nothing existed in the previous period but there's data now —
            // a percent change is mathematically undefined, not "infinite%".
            return [
                'current'          => $current,
                'previous'         => $previous,
                'change_percent'   => null,
                'direction'        => 'new',
                'higher_is_better' => $higherIsBetter,
            ];
        }

        $changePercent = round((($current - $previous) / $previous) * 100, 1);
        $direction     = $changePercent > 0 ? 'up' : ($changePercent < 0 ? 'down' : 'flat');

        return [
            'current'          => $current,
            'previous'         => $previous,
            'change_percent'   => $changePercent,
            'direction'        => $direction,
            'higher_is_better' => $higherIsBetter,
        ];
    }

    private function buildPointsTrend(float $current, float $previous, bool $higherIsBetter): array
    {
        $changePoints = round($current - $previous, 1);
        $direction    = $changePoints > 0 ? 'up' : ($changePoints < 0 ? 'down' : 'flat');

        return [
            'current'          => $current,
            'previous'         => $previous,
            'change_points'    => $changePoints,
            'direction'        => $direction,
            'higher_is_better' => $higherIsBetter,
        ];
    }
}
