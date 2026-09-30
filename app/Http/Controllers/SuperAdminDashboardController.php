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
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductionInitiation;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $lostQuery     = (clone $base())->lost();
        $lostLeads     = (clone $lostQuery)->count();

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

        // Query converted products in the selected date range using converted_at (converted date)
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
        // won_leads = unique leads whose products converted in the selected period or marked won in period
        $wonLeadIds    = (clone $wonQuery)->pluck('id')->merge($convertedLeadIdsInPeriod)->unique();
        $wonLeads      = $wonLeadIds->count();
        $wonValue      = $convertedValue > 0 ? $convertedValue : (float) (clone $wonQuery)->sum('deal_value');
        $activeLeads   = max(0, $totalLeads - $wonLeads - $lostLeads);




        $excludedIds   = $wonLeadIds->merge((clone $lostQuery)->pluck('id'))->unique();
        $pipelineValue = (float)(clone $base())->whereNotIn('id', $excludedIds)->sum('deal_value');
        $highPriority  = (clone $base())->where('priority', 'high')->whereNotIn('id', $excludedIds)->count();
        $convRate      = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;


        // ── 2. Pipeline funnel from lead_products.lead_status_id ───
        $leadIds = (clone $base())->pluck('id');
        $lpProducts = LeadProduct::whereIn('lead_id', $leadIds)->with('payments')->get();

        $nonConvertedProducts = $lpProducts->reject($isConvertedProduct);

        $upcomingAmount = (float) $nonConvertedProducts->sum('total_price');
        $totalProductsCount = $convertedProductsCount + $nonConvertedProducts->count();
        $convertedPercentage = $totalProductsCount > 0 ? round(($convertedProductsCount / $totalProductsCount) * 100, 1) : 0;

        $allLpProducts = $nonConvertedProducts->merge($convertedProducts)->unique('id');

        $followupsCount = \App\Models\LeadReminder::where('is_completed', false)
            ->whereIn('lead_id', $leadIds)
            ->whereDate('remind_at', today())
            ->count();
        $productStatusFunnel = $this->buildProductStatusFunnel($leadIds, $request, $allLpProducts, $convertedProductsCount, $convertedStatusIds);
        $stageTotal = $productStatusFunnel['total'];
        $stageFunnel = $productStatusFunnel['stages'];

        // ── 3. Source counts (grouped by distinct source name) ──────
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

        $unassignedCount = max(0, $totalLeads - $sourceTotal);
        if ($unassignedCount > 0) {
            $sourceCounts[] = [
                'key'   => 'unassigned',
                'label' => 'Unassigned',
                'count' => $unassignedCount,
            ];
            $sourceTotal = $totalLeads;
        }

        foreach ($sourceCounts as &$src) {
            $src['percent'] = $sourceTotal > 0 ? round($src['count'] / $sourceTotal * 100, 1) : 0;
        }
        unset($src);


        // ── 4. Financials (from lead_products + payments) ─────────
        $totalProductValue = (float) $allLpProducts->sum('total_price');
        $totalPaid         = (float) $allLpProducts->sum(fn (LeadProduct $lp) => $lp->amount_paid);
        $totalPending      = max(0, $convertedValue - $totalPaid);
        $convertedCount    = $convertedProductsCount;
        $payPct            = $convertedValue > 0 ? round($totalPaid / $convertedValue * 100, 1) : 0;

        $paymentsQuery = LeadProductPayment::query()
            ->whereHas('lead', function ($lq) use ($request, $branchId, $userId, $stage, $source) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId) $lq->where('branch_id', $branchId);
                if ($userId)   $lq->where('assigned_to', $userId);
                if ($stage)    $lq->where('lead_status', $stage);
                if ($source)   $lq->where('lead_source_id', $source);
            });

        if ($dateFrom) {
            $paymentsQuery->whereDate('payment_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paymentsQuery->whereDate('payment_date', '<=', $dateTo);
        }
        $totalReceivedAmount = (float) $paymentsQuery->sum('amount');

        $leadProductIds = $allLpProducts->pluck('id');
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
            $cnt = $allLpProducts->where('product_status', $pkey)->count();
            $productStatusDist[] = [
                'status'  => $pkey,
                'label'   => $plabel,
                'count'   => $cnt,
                'config'  => LeadProduct::PRODUCT_STATUS_CONFIG[$pkey] ?? [],
            ];
        }

        // ── 5. Today's follow-ups / Scheduled Followups ───────────
        $currentUser = $request->user();
        // Branch Managers excluded from $isUserAdmin to ensure applyLeadVisibility() scopes
        // their data to their branch's users only (not all company data).
        $isUserAdmin = ($currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole())
            && !($currentUser->isBranchManager() || $currentUser->isBranchAdmin());
        $effectiveUserId = $request->filled('user_id') ? (int) $request->user_id : ($isUserAdmin ? null : (int) $currentUser->id);

        $recentCallUpdatesQuery = LeadCallUpdate::query()
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());

                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                    ->when($stage,    fn($q2) => $q2->where('lead_status', $stage))
                    ->when($source,   fn($q2) => $q2->where('lead_source_id', $source));
            });

        if ($effectiveUserId) {
            $recentCallUpdatesQuery->where(function ($q) use ($effectiveUserId) {
                $q->where('user_id', $effectiveUserId)
                  ->orWhereHas('lead', fn($lq) => $lq->where('assigned_to', $effectiveUserId));
            });
        }

        $todayFollowups = $recentCallUpdatesQuery
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
                'id'              => $fu->id,
                'called_at'       => $fu->called_at?->toISOString(),
                'called_at_formatted' => $fu->called_at?->format('d M, h:i A'),
                'call_type'       => $fu->call_type,
                'call_type_label' => $fu->call_type_label,
                'outcome'         => $fu->outcome,
                'outcome_label'   => $fu->outCome?->name ?? ($fu->outcome_label ?: 'Call Update'),
                'outcome_subcategory' => $fu->outcome_subcategory,
                'outcome_subcategory_label' => $fu->outComeSubCategory?->name ?? $fu->outcome_subcategory_label,
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

        $todayRemindersCount = (clone $reminderQuery())
            ->whereDate('remind_at', today())
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
            ->map(function ($branch) use ($base, $request, $dateFrom, $dateTo, $userId, $stage, $source, $convertedStatusIds) {
                $q = (clone $base())->where('branch_id', $branch->id);

                $total          = (clone $q)->count();

                // Converted products for this branch within converted date range
                $branchConvProductQuery = LeadProduct::where(function ($lpq) use ($convertedStatusIds) {
                        $lpq->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                            ->orWhereIn('lead_status_id', $convertedStatusIds);
                    })
                    ->whereHas('lead', function ($lq) use ($branch, $request, $userId, $stage, $source) {
                        $this->visibility->applyLeadVisibility($lq, $request->user());
                        $lq->where('branch_id', $branch->id);
                        if ($userId) $lq->where('assigned_to', $userId);
                        if ($stage)  $lq->where('lead_status', $stage);
                        if ($source) $lq->where('lead_source_id', $source);
                    });

                if ($dateFrom) {
                    $branchConvProductQuery->whereDate('converted_at', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $branchConvProductQuery->whereDate('converted_at', '<=', $dateTo);
                }

                $branchConvProducts = $branchConvProductQuery->get();
                $productConvCnt = $branchConvProducts->count();
                $productConvVal = (float) $branchConvProducts->sum('total_price');

                $branchWonQuery = (clone $q)->converted();
                $branchWonIds   = (clone $branchWonQuery)->pluck('id')->merge($branchConvProducts->pluck('lead_id'))->unique();
                $wonLeads       = $branchWonIds->count();
                $dealWonVal     = (float) (clone $branchWonQuery)->sum('deal_value');
                $wonVal         = $productConvVal > 0 ? $productConvVal : $dealWonVal;

                $convertedCount = $productConvCnt > 0 ? $productConvCnt : $wonLeads;
                $convertedVal   = $wonVal;
                $convRate       = $total > 0 ? round($wonLeads / $total * 100, 1) : 0;
                $branchLostQuery = (clone $q)->lost();
                $lostLeads      = (clone $branchLostQuery)->count();
                $branchExcludedIds = $branchWonIds->merge((clone $branchLostQuery)->pluck('id'))->unique();
                $pipeline       = (float)(clone $q)->whereNotIn('id', $branchExcludedIds)->sum('deal_value');

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
            });

        $unassignedBranchLeads = (clone $base())->whereNull('branch_id')->count();
        if ($unassignedBranchLeads > 0) {
            $branchPerformance->push([
                'branch_id'            => null,
                'branch_name'          => 'No Branch',
                'branch_code'          => 'N/A',
                'total_leads'          => $unassignedBranchLeads,
                'converted_count'      => 0,
                'converted_value'      => 0,
                'converted_percentage' => 0,
                'won_leads'            => 0,
                'won_value'            => 0,
                'lost_leads'           => (clone $base())->whereNull('branch_id')->where('lead_status', 'lost')->count(),
                'pipeline_value'       => (float)(clone $base())->whereNull('branch_id')->sum('deal_value'),
                'conversion_rate'      => 0,
            ]);
        }

        $branchPerformance = $branchPerformance
            ->filter(fn($b) => $b['total_leads'] > 0 || $b['converted_count'] > 0)
            ->sortByDesc('converted_value')
            ->values();

        // ── 9. Team performance ───────────────────────────────────
        $assignedUserIds = (clone $base())->whereNotNull('assigned_to')->pluck('assigned_to')->unique();
        $assignableUserIds = $this->visibility->visibleAssignableUsers($request->user())
            ->when($branchId, fn($users) => $users->where('branch_id', $branchId))
            ->pluck('id');
        $allTeamUserIds = $assignedUserIds->merge($assignableUserIds)->unique()->filter();

        $teamPerformance = User::whereIn('id', $allTeamUserIds)
            ->with('roles')
            ->get()
            ->map(function ($user) use ($base, $request, $dateFrom, $dateTo, $branchId, $stage, $source, $convertedStatusIds) {
                $q = (clone $base())->where('assigned_to', $user->id);
                $total  = (clone $q)->count();

                // Converted products for this user within converted date range
                $userConvProductQuery = LeadProduct::where(function ($lpq) use ($convertedStatusIds) {
                        $lpq->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                            ->orWhereIn('lead_status_id', $convertedStatusIds);
                    })
                    ->whereHas('lead', function ($lq) use ($user, $request, $branchId, $stage, $source) {
                        $this->visibility->applyLeadVisibility($lq, $request->user());
                        $lq->where('assigned_to', $user->id);
                        if ($branchId) $lq->where('branch_id', $branchId);
                        if ($stage)    $lq->where('lead_status', $stage);
                        if ($source)   $lq->where('lead_source_id', $source);
                    });

                if ($dateFrom) {
                    $userConvProductQuery->whereDate('converted_at', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $userConvProductQuery->whereDate('converted_at', '<=', $dateTo);
                }

                $userConvProducts = $userConvProductQuery->get();
                $convertCount = $userConvProducts->count();
                $convertVal = (float) $userConvProducts->sum('total_price');
                $lost   = (clone $q)->where('lead_status', 'lost')->count();

                return [
                    'user_id'         => $user->id,
                    'user_name'       => $user->name,
                    'user_email'      => $user->email,
                    'role'            => $user->roles->first()?->display_name ?? 'Staff',
                    'role_name'       => $user->roles->first()?->name,
                    'total_leads'     => $total,
                    'convert_leads'   => $convertCount,
                    'lost_leads'      => $lost,
                    'active_leads'    => max(0, $total - $convertCount - $lost),
                    'convert_value'   => $convertVal,
                    'conversion_rate' => $total > 0 ? round($convertCount / $total * 100, 1) : 0,
                ];
            });

        $unassignedUserLeads = (clone $base())->whereNull('assigned_to')->count();
        if ($unassignedUserLeads > 0) {
            $teamPerformance->push([
                'user_id'         => null,
                'user_name'       => 'Unassigned',
                'user_email'      => null,
                'role'            => 'Unassigned',
                'role_name'       => 'unassigned',
                'total_leads'     => $unassignedUserLeads,
                'convert_leads'   => 0,
                'lost_leads'      => (clone $base())->whereNull('assigned_to')->where('lead_status', 'lost')->count(),
                'active_leads'    => $unassignedUserLeads,
                'convert_value'   => 0,
                'conversion_rate' => 0,
            ]);
        }

        $teamPerformance = $teamPerformance
            ->filter(fn($u) => $u['total_leads'] > 0 || $u['convert_leads'] > 0)
            ->sortByDesc('convert_value')
            ->values();

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

        // ── Forecasting / Total Prospects (Current Month Hot Products by Closure Date) ──
        $parsedMonth = $this->parseTargetMonth($request);
        $currentMonthHotProductsQuery = LeadProduct::query()
            ->whereRaw('LOWER(product_status) = ?', ['hot'])
            ->whereMonth('closure_date', $parsedMonth['month'])
            ->whereYear('closure_date', $parsedMonth['year'])
            ->whereHas('lead', function ($lq) use ($request, $branchId, $effectiveUserId) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId) $lq->where('branch_id', $branchId);
                if ($request->filled('user_id')) $lq->where('assigned_to', $request->user_id);
            });
        $currentMonthHotProductsCount = (clone $currentMonthHotProductsQuery)->count();
        $currentMonthHotProductsValue = (float) (clone $currentMonthHotProductsQuery)->sum('total_price');
        $currentMonthExpectedCollection = (float) (clone $currentMonthHotProductsQuery)->sum('expected_value');
        $vis = $this->resolveForecastingVisibility($request->user());
        $hasDefaultBranch      = $vis['hasDefaultBranch'];
        $hasCocoBranch         = $vis['hasCocoBranch'];
        $hasNonCocoBranch      = $vis['hasNonCocoBranch'];
        $canViewCst            = $vis['canViewCst'];
        $canViewActiveBranches = $vis['canViewActiveBranches'];
        $userRoleType          = $vis['userRoleType'];

        $cpMetrics = $this->buildChannelPartnerHotMetrics($currentMonthHotProductsQuery, $request);
        $cstMetrics = $this->buildCstProspectMetrics($request);
        $activeBranchesMetrics = $canViewActiveBranches ? $this->buildActiveBranchesHotMetrics($request) : [];

        $authUser = $request->user();
        $vis = $this->resolveForecastingVisibility($authUser);
        $hasDefaultBranch      = $vis['hasDefaultBranch'];
        $hasCocoBranch         = $vis['hasCocoBranch'];
        $hasNonCocoBranch      = $vis['hasNonCocoBranch'];
        $canViewCst            = $vis['canViewCst'];
        $canViewActiveBranches = $vis['canViewActiveBranches'];
        $userRoleType          = $vis['userRoleType'];

        $totalProspectsCount = 0;
        $totalProspectsDealValue = 0.0;
        $totalProspectsExpectedCollection = 0.0;

        if ($hasDefaultBranch) {
            $totalProspectsCount += (int) ($cpMetrics['nst_ho']['count'] ?? 0);
            $totalProspectsDealValue += (float) ($cpMetrics['nst_ho']['deal_value'] ?? 0);
            $totalProspectsExpectedCollection += (float) ($cpMetrics['nst_ho']['expected_value'] ?? 0);
        }
        if ($hasNonCocoBranch) {
            $totalProspectsCount += (int) ($cpMetrics['non_coco']['count'] ?? 0);
            $totalProspectsDealValue += (float) ($cpMetrics['non_coco']['deal_value'] ?? 0);
            $totalProspectsExpectedCollection += (float) ($cpMetrics['non_coco']['expected_value'] ?? 0);
        }
        if ($hasCocoBranch) {
            $totalProspectsCount += (int) ($cpMetrics['coco']['count'] ?? 0);
            $totalProspectsDealValue += (float) ($cpMetrics['coco']['deal_value'] ?? 0);
            $totalProspectsExpectedCollection += (float) ($cpMetrics['coco']['expected_value'] ?? 0);
        }
        if ($canViewCst) {
            $totalProspectsCount += (int) ($cstMetrics['count'] ?? 0);
            $totalProspectsDealValue += (float) ($cstMetrics['deal_value'] ?? 0);
            $totalProspectsExpectedCollection += (float) ($cstMetrics['expected_value'] ?? 0);
        }

        // For Company Admin / CBO, Total Prospects card MUST match Active Branches table total count
        $isCompanyAdminOrCboUnfiltered = ($authUser->isSuperAdmin() || $authUser->isSystemAdmin() || $authUser->isCompanyAdminRole() || $authUser->isCbo()) && !$request->filled('user_id');
        if ($isCompanyAdminOrCboUnfiltered && !empty($activeBranchesMetrics)) {
            $totalProspectsCount = (int) array_sum(array_column($activeBranchesMetrics, 'prospect_count'));
            $totalProspectsDealValue = (float) array_sum(array_column($activeBranchesMetrics, 'deal_value'));
            $totalProspectsExpectedCollection = (float) array_sum(array_column($activeBranchesMetrics, 'expected_value'));
        }

        // ── Day Sales Tracker (Current Date Converted Products) ──
        $todayStr = today()->toDateString();
        $todayConvertedQuery = LeadProduct::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                  ->orWhere('lead_status_id', 5);
            })
            ->where(function ($q) use ($todayStr) {
                $q->whereHas('payments', function ($pq) use ($todayStr) {
                    $pq->whereDate('payment_date', $todayStr);
                })
                ->orWhereDate('payment_date', $todayStr)
                ->orWhere(function ($sub) use ($todayStr) {
                    $sub->whereNull('payment_date')
                        ->whereDoesntHave('payments')
                        ->where(function ($dateSub) use ($todayStr) {
                            $dateSub->whereDate('converted_at', $todayStr)
                                    ->orWhere(function ($cSub) use ($todayStr) {
                                        $cSub->whereNull('converted_at')->whereDate('created_at', $todayStr);
                                    });
                        });
                });
            })
            ->whereHas('lead', function ($lq) use ($request, $branchId, $effectiveUserId) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId)        $lq->where('branch_id', $branchId);
                if ($effectiveUserId) $lq->where('assigned_to', $effectiveUserId);
            });
        $todayConvertedCount = (clone $todayConvertedQuery)->count();
        $todayConvertedValue = (float) (clone $todayConvertedQuery)->sum('total_price');
        $todayConvertedCollection = (float) (clone $todayConvertedQuery)->sum('amount_paid');

        // ── Build response ────────────────────────────────────────
        return $this->success([

            'filters_applied' => $filtersApplied,

            'day_sales_tracker' => [
                'count'            => $todayConvertedCount,
                'total_value'      => $todayConvertedValue,
                'total_collection' => $todayConvertedCollection,
            ],

            'kpis' => [
                'total_leads'                => $totalLeads,
                'active_leads'               => $activeLeads,
                'won_leads'                  => $wonLeads,
                'lost_leads'                 => $lostLeads,
                'high_priority'              => $highPriority,
                'pipeline_value'             => $pipelineValue,
                'won_value'                  => $wonValue,
                'conversion_rate'            => $convRate,
                'total_products_count'       => $totalProductsCount,
                'total_product_value'        => $totalProductValue,
                'converted_products_count'  => $convertedProductsCount,
                'upcoming_amount'            => $upcomingAmount,
                'total_received_amount'      => $totalReceivedAmount,
                'converted_value'            => $convertedValue,
                'converted_percentage'       => $convertedPercentage,
                'followups_count'            => $followupsCount,
                'scheduled_followups_count'  => $todayRemindersCount,
                'today_reminders_count'      => $todayRemindersCount,
                'overdue_reminders_count'    => $overdueCount,
                'today_completed_calls_count' => $todayCompletedCallsCount,
                'current_month_hot_products_count' => $currentMonthHotProductsCount,
                'total_prospects'            => $totalProspectsCount,
                'current_month_hot_products_value' => $currentMonthHotProductsValue,
                'current_month_expected_collection' => $currentMonthExpectedCollection,
                'nst_ho_prospects_count'     => $hasDefaultBranch ? $cpMetrics['nst_ho']['count'] : 0,
                'nst_ho_deal_value'          => $hasDefaultBranch ? $cpMetrics['nst_ho']['deal_value'] : 0.0,
                'nst_ho_expected_value'      => $hasDefaultBranch ? $cpMetrics['nst_ho']['expected_value'] : 0.0,
                'non_coco_prospects_count'   => $hasNonCocoBranch ? $cpMetrics['non_coco']['count'] : 0,
                'non_coco_deal_value'        => $hasNonCocoBranch ? $cpMetrics['non_coco']['deal_value'] : 0.0,
                'non_coco_expected_value'    => $hasNonCocoBranch ? $cpMetrics['non_coco']['expected_value'] : 0.0,
                'coco_prospects_count'       => $hasCocoBranch ? $cpMetrics['coco']['count'] : 0,
                'coco_deal_value'            => $hasCocoBranch ? $cpMetrics['coco']['deal_value'] : 0.0,
                'coco_expected_value'        => $hasCocoBranch ? $cpMetrics['coco']['expected_value'] : 0.0,
                'cst_prospects_count'        => $canViewCst ? $cstMetrics['count'] : 0,
                'cst_deal_value'             => $canViewCst ? $cstMetrics['deal_value'] : 0.0,
                'cst_expected_value'         => $canViewCst ? $cstMetrics['expected_value'] : 0.0,
            ],

            'forecasting' => [
                'total_prospects'            => $totalProspectsCount,
                'hot_products_count'         => $totalProspectsCount,
                'hot_products_value'         => $totalProspectsDealValue,
                'deal_value'                 => $totalProspectsDealValue,
                'expected_collection_value'  => $totalProspectsExpectedCollection,
                'month_name'                 => $parsedMonth['month_name'],
                'target_month'               => $parsedMonth['target_month'],
                'user_role_type'             => $userRoleType,
                'show_nst_ho'                => (bool) $hasDefaultBranch,
                'show_coco'                  => (bool) $hasCocoBranch,
                'show_non_coco'              => (bool) $hasNonCocoBranch,
                'show_cst'                   => (bool) $canViewCst,
                'show_active_branches'       => (bool) $canViewActiveBranches,
                'has_default_branch'         => (bool) $hasDefaultBranch,
                'has_coco_branch'            => (bool) $hasCocoBranch,
                'has_non_coco_branch'        => (bool) $hasNonCocoBranch,
                'can_view_active_branches'   => (bool) $canViewActiveBranches,
                'can_view_cst'               => (bool) $canViewCst,
                'nst_ho'                     => $hasDefaultBranch ? $cpMetrics['nst_ho'] : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'heading' => 'NST - HO'],
                'non_coco'                   => $hasNonCocoBranch ? $cpMetrics['non_coco'] : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'product_name' => 'Channel Partner NON COCO Model'],
                'coco'                       => $hasCocoBranch ? $cpMetrics['coco'] : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'product_name' => 'Channel Partner COCO Model'],
                'cst'                        => $canViewCst ? $cstMetrics : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'product_name' => 'CST'],
                'active_branches'            => $canViewActiveBranches ? $activeBranchesMetrics : [],
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
                'today_count'   => $todayRemindersCount,
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
                'all'     => [null, null],
                'today'   => [today()->toDateString(), today()->toDateString()],
                'week'    => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                'month'   => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
                'quarter' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
                'year'    => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                'custom'  => [
                    $this->parseDateInput($request->date_from),
                    $this->parseDateInput($request->date_to),
                ],
                default   => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            };
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            return [
                $this->parseDateInput($request->date_from),
                $this->parseDateInput($request->date_to),
            ];
        }

        return [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
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
            $query->whereHas('lead', function ($lq) use ($dateFrom) {
                $lq->whereDate('lead_date', '>=', $dateFrom)
                   ->orWhereDate('created_at', '>=', $dateFrom);
            });
        }

        if ($dateTo) {
            $query->whereHas('lead', function ($lq) use ($dateTo) {
                $lq->whereDate('lead_date', '<=', $dateTo)
                   ->orWhereDate('created_at', '<=', $dateTo);
            });
        }

        return $query;
    }

    private function buildProductStatusFunnel($leadIds, Request $request, $allLpProducts = null, $convertedProductsCount = 0, array $convertedStatusIds = []): array
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
            'quick_date' => ['nullable', 'in:all,today,week,month,quarter,year,custom'],
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
        $wonQuery      = (clone $base())->converted();
        $lostQuery     = (clone $base())->lost();
        $lostLeads     = (clone $lostQuery)->count();

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

        // Query converted products in the selected date range using converted_at (converted date)
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

        // won_leads = unique leads whose products were converted in the selected date range or marked won in period
        $convertedLeadIdsInPeriod = $convertedProducts->pluck('lead_id')->unique();
        $wonLeadIds    = (clone $wonQuery)->pluck('id')->merge($convertedLeadIdsInPeriod)->unique();
        $wonLeads      = $wonLeadIds->count();
        $wonValue      = $convertedValue > 0 ? $convertedValue : (float) (clone $wonQuery)->sum('deal_value');
        $activeLeads   = max(0, $totalLeads - $wonLeads - $lostLeads);


        $excludedIds   = $wonLeadIds->merge((clone $lostQuery)->pluck('id'))->unique();
        $pipelineValue = (float)(clone $base())->whereNotIn('id', $excludedIds)->sum('deal_value');
        $highPriority  = (clone $base())->where('priority', 'high')->whereNotIn('id', $excludedIds)->count();
        $convRate      = $totalLeads > 0 ? round($wonLeads / $totalLeads * 100, 1) : 0;

        // Previous period converted products using converted_at
        $prevConvertedProductsQuery = LeadProduct::query()
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

        if ($prevFrom) {
            $prevConvertedProductsQuery->whereDate('converted_at', '>=', $prevFrom);
        }
        if ($prevTo) {
            $prevConvertedProductsQuery->whereDate('converted_at', '<=', $prevTo);
        }

        $prevConvertedProducts = $prevConvertedProductsQuery->get();
        $prevConvertedLeadIdsInPeriod = $prevConvertedProducts->pluck('lead_id')->unique();

        $prevTotalLeads    = (clone $prevBase())->count();
        $prevWonQuery      = (clone $prevBase())->converted();
        $prevLostQuery     = (clone $prevBase())->lost();
        $prevLostLeads     = (clone $prevLostQuery)->count();

        $prevWonLeadIds    = $prevConvertedLeadIdsInPeriod;
        $prevWonLeads      = $prevWonLeadIds->count();
        $prevActiveLeads   = max(0, $prevTotalLeads - $prevWonLeads - $prevLostLeads);



        $prevConvertedValue = (float) $prevConvertedProducts->sum('total_price');
        $prevDealWonVal    = (float) (clone $prevWonQuery)->sum('deal_value');
        $prevWonValue      = $prevConvertedValue > 0 ? $prevConvertedValue : $prevDealWonVal;

        $prevExcludedIds   = $prevWonLeadIds->merge((clone $prevLostQuery)->pluck('id'))->unique();
        $prevPipelineValue = (float)(clone $prevBase())->whereNotIn('id', $prevExcludedIds)->sum('deal_value');
        $prevHighPriority  = (clone $prevBase())->where('priority', 'high')->whereNotIn('id', $prevExcludedIds)->count();
        $prevConvRate      = $prevTotalLeads > 0 ? round($prevWonLeads / $prevTotalLeads * 100, 1) : 0.0;

        $prevLeadIds = (clone $prevBase())->pluck('id');
        $prevLpProducts = LeadProduct::whereIn('lead_id', $prevLeadIds)->with('payments')->get();
        $prevNonConverted = $prevLpProducts->reject($isConvertedProduct);
        $prevAllLpProducts = $prevNonConverted->merge($prevConvertedProducts)->unique('id');
        $prevTotalProductValue = (float) $prevAllLpProducts->sum('total_price');
        $prevTotalPaid         = (float) $prevAllLpProducts->sum(fn (LeadProduct $lp) => $lp->amount_paid);
        $prevTotalPending      = max(0, $prevConvertedValue - $prevTotalPaid);

        // ── 2. Pipeline funnel from lead_products.lead_status_id ───
        $leadIds = (clone $base())->pluck('id');
        $lpProducts = LeadProduct::whereIn('lead_id', $leadIds)->with('payments')->get();

        $nonConvertedProducts = $lpProducts->reject($isConvertedProduct);

        $upcomingAmount = (float) $nonConvertedProducts->sum('total_price');
        $totalProductsCount = $convertedProductsCount + $nonConvertedProducts->count();
        $convertedPercentage = $totalProductsCount > 0 ? round(($convertedProductsCount / $totalProductsCount) * 100, 1) : 0;

        $allLpProducts = $nonConvertedProducts->merge($convertedProducts)->unique('id');

        $followupsCount = \App\Models\LeadReminder::where('is_completed', false)
            ->whereIn('lead_id', $leadIds)
            ->whereDate('remind_at', today())
            ->count();
        $productStatusFunnel = $this->buildProductStatusFunnel($leadIds, $request, $allLpProducts, $convertedProductsCount, $convertedStatusIds);
        $stageTotal = $productStatusFunnel['total'];
        $stageFunnel = $productStatusFunnel['stages'];

        // ── 3. Source counts (grouped by distinct source name) ──────
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

        $unassignedCount = max(0, $totalLeads - $sourceTotal);
        if ($unassignedCount > 0) {
            $sourceCounts[] = [
                'key'   => 'unassigned',
                'label' => 'Unassigned',
                'count' => $unassignedCount,
            ];
            $sourceTotal = $totalLeads;
        }

        foreach ($sourceCounts as &$src) {
            $src['percent'] = $sourceTotal > 0 ? round($src['count'] / $sourceTotal * 100, 1) : 0;
        }
        unset($src);


        // ── 4. Financials (from lead_products + payments) ─────────
        $totalProductValue = (float) $allLpProducts->sum('total_price');
        $totalPaid         = (float) $allLpProducts->sum(fn (LeadProduct $lp) => $lp->amount_paid);
        $totalPending      = max(0, $convertedValue - $totalPaid);
        $convertedCount    = $convertedProductsCount;
        $payPct            = $convertedValue > 0 ? round($totalPaid / $convertedValue * 100, 1) : 0;

        $paymentsQuery = LeadProductPayment::query()
            ->whereHas('lead', function ($lq) use ($request, $branchId, $userId, $stage, $source) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId) $lq->where('branch_id', $branchId);
                if ($userId)   $lq->where('assigned_to', $userId);
                if ($stage)    $lq->where('lead_status', $stage);
                if ($source)   $lq->where('lead_source_id', $source);
            });

        if ($dateFrom) {
            $paymentsQuery->whereDate('payment_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paymentsQuery->whereDate('payment_date', '<=', $dateTo);
        }
        $totalReceivedAmount = (float) $paymentsQuery->sum('amount');

        $leadProductIds = $allLpProducts->pluck('id');
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
            $cnt = $allLpProducts->where('product_status', $pkey)->count();
            $productStatusDist[] = [
                'status'  => $pkey,
                'label'   => $plabel,
                'count'   => $cnt,
                'config'  => LeadProduct::PRODUCT_STATUS_CONFIG[$pkey] ?? [],
            ];
        }

        // ── 5. Recent call updates (last 20) ───────────────────────────
        $currentUser = $request->user();
        // Branch Managers are excluded from $isUserAdmin so that applyLeadVisibility()
        // scopes their data to only their branch's users (via visibleUserIds).
        // If treated as admin, effectiveUserId would be null and they'd see ALL data.
        $isUserAdmin = ($currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin() || $currentUser->hasAdminLikeRole())
            && !($currentUser->isBranchManager() || $currentUser->isBranchAdmin());
        $effectiveUserId = $request->filled('user_id') ? (int) $request->user_id : ($isUserAdmin ? null : (int) $currentUser->id);

        $recentCallUpdatesQuery = LeadCallUpdate::query()
            ->whereHas('lead', function ($q) use ($request, $branchId, $effectiveUserId, $stage, $source) {
                $this->visibility->applyLeadVisibility($q, $request->user());

                $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId))
                    ->when($effectiveUserId, fn($q2) => $q2->where('assigned_to', $effectiveUserId))
                    ->when($stage,    fn($q2) => $q2->where('lead_status', $stage))
                    ->when($source,   fn($q2) => $q2->where('lead_source_id', $source));
            });

        if ($effectiveUserId) {
            $recentCallUpdatesQuery->where(function ($q) use ($effectiveUserId) {
                $q->where('user_id', $effectiveUserId)
                  ->orWhereHas('lead', fn($lq) => $lq->where('assigned_to', $effectiveUserId));
            });
        }

        $todayFollowups = $recentCallUpdatesQuery
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
                'id'              => $fu->id,
                'called_at'       => $fu->called_at?->toISOString(),
                'called_at_formatted' => $fu->called_at?->format('d M, h:i A'),
                'call_type'       => $fu->call_type,
                'call_type_label' => $fu->call_type_label,
                'outcome'         => $fu->outcome,
                'outcome_label'   => $fu->outCome?->name ?? ($fu->outcome_label ?: 'Call Update'),
                'outcome_subcategory' => $fu->outcome_subcategory,
                'outcome_subcategory_label' => $fu->outComeSubCategory?->name ?? $fu->outcome_subcategory_label,
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

        $todayRemindersCount = (clone $reminderQuery())
            ->whereDate('remind_at', today())
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
            ->map(function ($branch) use ($base, $request, $dateFrom, $dateTo, $userId, $stage, $source, $convertedStatusIds) {
                $q = (clone $base())->where('branch_id', $branch->id);

                $total          = (clone $q)->count();

                // Converted products for this branch within converted date range
                $branchConvProductQuery = LeadProduct::where(function ($lpq) use ($convertedStatusIds) {
                        $lpq->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                            ->orWhereIn('lead_status_id', $convertedStatusIds);
                    })
                    ->whereHas('lead', function ($lq) use ($branch, $request, $userId, $stage, $source) {
                        $this->visibility->applyLeadVisibility($lq, $request->user());
                        $lq->where('branch_id', $branch->id);
                        if ($userId) $lq->where('assigned_to', $userId);
                        if ($stage)  $lq->where('lead_status', $stage);
                        if ($source) $lq->where('lead_source_id', $source);
                    });

                if ($dateFrom) {
                    $branchConvProductQuery->whereDate('converted_at', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $branchConvProductQuery->whereDate('converted_at', '<=', $dateTo);
                }

                $branchConvProducts = $branchConvProductQuery->get();
                $productConvCnt = $branchConvProducts->count();
                $productConvVal = (float) $branchConvProducts->sum('total_price');

                $branchWonQuery = (clone $q)->converted();
                $branchWonIds   = (clone $branchWonQuery)->pluck('id')->merge($branchConvProducts->pluck('lead_id'))->unique();
                $wonLeads       = $branchWonIds->count();
                $dealWonVal     = (float) (clone $branchWonQuery)->sum('deal_value');
                $wonVal         = $productConvVal > 0 ? $productConvVal : $dealWonVal;

                $convertedCount = $productConvCnt > 0 ? $productConvCnt : $wonLeads;
                $convertedVal   = $wonVal;
                $convRate       = $total > 0 ? round($wonLeads / $total * 100, 1) : 0;
                $branchLostQuery = (clone $q)->lost();
                $lostLeads      = (clone $branchLostQuery)->count();
                $branchExcludedIds = $branchWonIds->merge((clone $branchLostQuery)->pluck('id'))->unique();
                $pipeline       = (float)(clone $q)->whereNotIn('id', $branchExcludedIds)->sum('deal_value');

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
            });

        $unassignedBranchLeads = (clone $base())->whereNull('branch_id')->count();
        if ($unassignedBranchLeads > 0) {
            $branchPerformance->push([
                'branch_id'            => null,
                'branch_name'          => 'No Branch',
                'branch_code'          => 'N/A',
                'total_leads'          => $unassignedBranchLeads,
                'converted_count'      => 0,
                'converted_value'      => 0,
                'converted_percentage' => 0,
                'won_leads'            => 0,
                'won_value'            => 0,
                'lost_leads'           => (clone $base())->whereNull('branch_id')->where('lead_status', 'lost')->count(),
                'pipeline_value'       => (float)(clone $base())->whereNull('branch_id')->sum('deal_value'),
                'conversion_rate'      => 0,
            ]);
        }

        $branchPerformance = $branchPerformance
            ->filter(fn($b) => $b['total_leads'] > 0 || $b['converted_count'] > 0)
            ->sortByDesc('converted_value')
            ->values();

        // ── 9. Team performance ───────────────────────────────────
        $assignedUserIds = (clone $base())->whereNotNull('assigned_to')->pluck('assigned_to')->unique();
        $assignableUserIds = $this->visibility->visibleAssignableUsers($request->user())
            ->when($branchId, fn($users) => $users->where('branch_id', $branchId))
            ->pluck('id');
        $allTeamUserIds = $assignedUserIds->merge($assignableUserIds)->unique()->filter();

        $teamPerformance = User::whereIn('id', $allTeamUserIds)
            ->with('roles')
            ->get()
            ->map(function ($user) use ($base, $request, $dateFrom, $dateTo, $branchId, $stage, $source, $convertedStatusIds) {
                $q = (clone $base())->where('assigned_to', $user->id);
                $total  = (clone $q)->count();

                // Converted products for this user within converted date range
                $userConvProductQuery = LeadProduct::where(function ($lpq) use ($convertedStatusIds) {
                        $lpq->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                            ->orWhereIn('lead_status_id', $convertedStatusIds);
                    })
                    ->whereHas('lead', function ($lq) use ($user, $request, $branchId, $stage, $source) {
                        $this->visibility->applyLeadVisibility($lq, $request->user());
                        $lq->where('assigned_to', $user->id);
                        if ($branchId) $lq->where('branch_id', $branchId);
                        if ($stage)    $lq->where('lead_status', $stage);
                        if ($source)   $lq->where('lead_source_id', $source);
                    });

                if ($dateFrom) {
                    $userConvProductQuery->whereDate('converted_at', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $userConvProductQuery->whereDate('converted_at', '<=', $dateTo);
                }

                $userConvProducts = $userConvProductQuery->get();
                $convertCount = $userConvProducts->count();
                $convertVal = (float) $userConvProducts->sum('total_price');
                $lost   = (clone $q)->where('lead_status', 'lost')->count();

                return [
                    'user_id'         => $user->id,
                    'user_name'       => $user->name,
                    'user_email'      => $user->email,
                    'role'            => $user->roles->first()?->display_name ?? 'Staff',
                    'role_name'       => $user->roles->first()?->name,
                    'total_leads'     => $total,
                    'convert_leads'   => $convertCount,
                    'lost_leads'      => $lost,
                    'active_leads'    => max(0, $total - $convertCount - $lost),
                    'convert_value'   => $convertVal,
                    'conversion_rate' => $total > 0 ? round($convertCount / $total * 100, 1) : 0,
                ];
            });

        $unassignedUserLeads = (clone $base())->whereNull('assigned_to')->count();
        if ($unassignedUserLeads > 0) {
            $teamPerformance->push([
                'user_id'         => null,
                'user_name'       => 'Unassigned',
                'user_email'      => null,
                'role'            => 'Unassigned',
                'role_name'       => 'unassigned',
                'total_leads'     => $unassignedUserLeads,
                'convert_leads'   => 0,
                'lost_leads'      => (clone $base())->whereNull('assigned_to')->where('lead_status', 'lost')->count(),
                'active_leads'    => $unassignedUserLeads,
                'convert_value'   => 0,
                'conversion_rate' => 0,
            ]);
        }

        $teamPerformance = $teamPerformance
            ->filter(fn($u) => $u['total_leads'] > 0 || $u['convert_leads'] > 0)
            ->sortByDesc('convert_value')
            ->values();

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

        // ── Forecasting / Total Prospects (Current Month Hot Products by Closure Date) ──
        $parsedMonth = $this->parseTargetMonth($request);
        $currentMonthHotProductsQuery = LeadProduct::query()
            ->whereRaw('LOWER(product_status) = ?', ['hot'])
            ->whereMonth('closure_date', $parsedMonth['month'])
            ->whereYear('closure_date', $parsedMonth['year'])
            ->whereHas('lead', function ($lq) use ($request, $branchId, $userId) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId) $lq->where('branch_id', $branchId);
                if ($userId)   $lq->where('assigned_to', $userId);
            });
        $currentMonthHotProductsCount = (clone $currentMonthHotProductsQuery)->count();
        $currentMonthHotProductsValue = (float) (clone $currentMonthHotProductsQuery)->sum('total_price');
        $currentMonthExpectedCollection = (float) (clone $currentMonthHotProductsQuery)->sum('expected_value');
        $cpMetrics = $this->buildChannelPartnerHotMetrics($currentMonthHotProductsQuery, $request);
        $cstMetrics = $this->buildCstProspectMetrics($request);

        $vis = $this->resolveForecastingVisibility($request->user());
        $hasDefaultBranch      = $vis['hasDefaultBranch'];
        $hasCocoBranch         = $vis['hasCocoBranch'];
        $hasNonCocoBranch      = $vis['hasNonCocoBranch'];
        $canViewCst            = $vis['canViewCst'];
        $canViewActiveBranches = $vis['canViewActiveBranches'];
        $userRoleType          = $vis['userRoleType'];

        $activeBranchesMetrics = $canViewActiveBranches ? $this->buildActiveBranchesHotMetrics($request) : [];

        $userTotalProspects = 0;
        $userTotalDealValue = 0.0;
        $userTotalExpectedCollection = 0.0;

        if ($hasDefaultBranch) {
            $userTotalProspects += (int) ($cpMetrics['nst_ho']['count'] ?? 0);
            $userTotalDealValue += (float) ($cpMetrics['nst_ho']['deal_value'] ?? 0);
            $userTotalExpectedCollection += (float) ($cpMetrics['nst_ho']['expected_value'] ?? 0);
        }
        if ($hasNonCocoBranch) {
            $userTotalProspects += (int) ($cpMetrics['non_coco']['count'] ?? 0);
            $userTotalDealValue += (float) ($cpMetrics['non_coco']['deal_value'] ?? 0);
            $userTotalExpectedCollection += (float) ($cpMetrics['non_coco']['expected_value'] ?? 0);
        }
        if ($hasCocoBranch) {
            $userTotalProspects += (int) ($cpMetrics['coco']['count'] ?? 0);
            $userTotalDealValue += (float) ($cpMetrics['coco']['deal_value'] ?? 0);
            $userTotalExpectedCollection += (float) ($cpMetrics['coco']['expected_value'] ?? 0);
        }
        if ($canViewCst) {
            $userTotalProspects += (int) ($cstMetrics['count'] ?? 0);
            $userTotalDealValue += (float) ($cstMetrics['deal_value'] ?? 0);
            $userTotalExpectedCollection += (float) ($cstMetrics['expected_value'] ?? 0);
        }

        $totalProspectsCount = $canViewActiveBranches
            ? (int) collect($activeBranchesMetrics)->sum('prospect_count')
            : $userTotalProspects;
        $totalProspectsDealValue = $canViewActiveBranches
            ? (float) collect($activeBranchesMetrics)->sum('deal_value')
            : $userTotalDealValue;
        $totalProspectsExpectedCollection = $canViewActiveBranches
            ? (float) collect($activeBranchesMetrics)->sum('expected_value')
            : $userTotalExpectedCollection;

        // ── Day Sales Tracker (Current Date Converted Products) ──
        $todayConvertedQuery = LeadProduct::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(product_status) in (?, ?)', ['converted', 'won'])
                  ->orWhere('lead_status_id', 5);
            })
            ->where(function ($q) {
                $q->whereDate('converted_at', today())
                  ->orWhere(function ($sub) {
                      $sub->whereNull('converted_at')->whereDate('created_at', today());
                  });
            })
            ->whereHas('lead', function ($lq) use ($request, $branchId, $userId) {
                $this->visibility->applyLeadVisibility($lq, $request->user());
                if ($branchId) $lq->where('branch_id', $branchId);
                if ($userId)   $lq->where('assigned_to', $userId);
            });
        $todayConvertedCount = (clone $todayConvertedQuery)->count();
        $todayConvertedValue = (float) (clone $todayConvertedQuery)->sum('total_price');
        $todayConvertedCollection = (float) (clone $todayConvertedQuery)->sum('amount_paid');

        // ── Build response ────────────────────────────────────────
        return $this->success([

            'filters_applied' => $filtersApplied,

            'day_sales_tracker' => [
                'count'            => $todayConvertedCount,
                'total_value'      => $todayConvertedValue,
                'total_collection' => $todayConvertedCollection,
            ],

            'kpis' => [
                'total_leads'       => $totalLeads,
                'active_leads'      => $activeLeads,
                'won_leads'         => $wonLeads,
                'lost_leads'        => $lostLeads,
                'followups_count'   => $followupsCount,
                'pipeline_value'    => $pipelineValue,
                'won_value'         => $wonValue,
                'conversion_rate'   => $convRate,
                'total_products_count' => $totalProductsCount,
                'total_product_value'  => $totalProductValue,
                'converted_products_count' => $convertedProductsCount,
                'upcoming_amount'   => $upcomingAmount,
                'total_received_amount' => $totalReceivedAmount,
                'converted_value'   => $convertedValue,
                'converted_percentage' => $convertedPercentage,
                'scheduled_followups_count'  => $todayRemindersCount,
                'today_reminders_count'      => $todayRemindersCount,
                'overdue_reminders_count'    => $overdueCount,
                'today_completed_calls_count' => $todayCompletedCallsCount,
                'current_month_hot_products_count' => $currentMonthHotProductsCount,
                'total_prospects'            => $totalProspectsCount,
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
                'cst_prospects_count'        => $cstMetrics['count'],
                'cst_deal_value'             => $cstMetrics['deal_value'],
                'cst_expected_value'         => $cstMetrics['expected_value'],
            ],

            'forecasting' => [
                'total_prospects'            => $totalProspectsCount,
                'hot_products_count'         => $totalProspectsCount,
                'hot_products_value'         => $totalProspectsDealValue,
                'deal_value'                 => $totalProspectsDealValue,
                'expected_collection_value'  => $totalProspectsExpectedCollection,
                'month_name'                 => now()->format('F Y'),
                'user_role_type'             => $userRoleType,
                'show_nst_ho'                => (bool) $hasDefaultBranch,
                'show_coco'                  => (bool) $hasCocoBranch,
                'show_non_coco'              => (bool) $hasNonCocoBranch,
                'show_cst'                   => (bool) $canViewCst,
                'show_active_branches'       => (bool) $canViewActiveBranches,
                'has_default_branch'         => (bool) $hasDefaultBranch,
                'has_coco_branch'            => (bool) $hasCocoBranch,
                'has_non_coco_branch'        => (bool) $hasNonCocoBranch,
                'can_view_active_branches'   => (bool) $canViewActiveBranches,
                'can_view_cst'               => (bool) $canViewCst,
                'nst_ho'                     => $hasDefaultBranch ? $cpMetrics['nst_ho'] : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'heading' => 'NST - HO'],
                'non_coco'                   => $hasNonCocoBranch ? $cpMetrics['non_coco'] : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'product_name' => 'Channel Partner NON COCO Model'],
                'coco'                       => $hasCocoBranch ? $cpMetrics['coco'] : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'product_name' => 'Channel Partner COCO Model'],
                'cst'                        => $canViewCst ? $cstMetrics : ['count' => 0, 'deal_value' => 0.0, 'expected_value' => 0.0, 'product_name' => 'CST'],
                'active_branches'            => $canViewActiveBranches ? $activeBranchesMetrics : [],
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
                'today_count'   => $todayRemindersCount,
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
                    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'branch_id' => $u->branch_id])
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

        $currentUser = $request?->user() ?: auth()->user();
        $isCompanyAdminOrCbo = $currentUser && ($currentUser->isSuperAdmin() || $currentUser->isSystemAdmin() || $currentUser->isCompanyAdminRole() || $currentUser->isCbo());

        // NON COCO Hot query: For Company Admin & CBO, filter strictly by Channel Partner NON COCO product
        $nonCocoHotQuery = (clone $currentMonthHotProductsQuery)->where(function ($q) use ($nonCocoProduct, $isCompanyAdminOrCbo) {
            $q->where(function ($sub) use ($nonCocoProduct) {
                if ($nonCocoProduct) {
                    $sub->where('product_id', $nonCocoProduct->id)
                        ->orWhere('product_name', 'like', '%NON%COCO%');
                } else {
                    $sub->where('product_name', 'like', '%NON%COCO%');
                }
            });
            if (!$isCompanyAdminOrCbo) {
                $q->orWhereHas('lead.branch', function ($bq) {
                    $bq->whereRaw("UPPER(TRIM(branch_type)) in ('NON COCO', 'NON_COCO', 'NON-COCO')");
                });
            }
        });
        $nonCocoHotCount = (clone $nonCocoHotQuery)->count();
        $nonCocoDealValue = (float) (clone $nonCocoHotQuery)->sum('total_price');
        $nonCocoExpectedValue = (float) (clone $nonCocoHotQuery)->sum('expected_value');

        // COCO Hot query: For Company Admin & CBO, filter strictly by Channel Partner COCO product
        $cocoHotQuery = (clone $currentMonthHotProductsQuery)->where(function ($q) use ($cocoProduct, $isCompanyAdminOrCbo) {
            $q->where(function ($sub) use ($cocoProduct) {
                if ($cocoProduct) {
                    $sub->where(function ($sq) use ($cocoProduct) {
                        $sq->where('product_id', $cocoProduct->id)
                           ->orWhere(function ($ssq) {
                               $ssq->where('product_name', 'like', '%COCO%')
                                   ->where('product_name', 'not like', '%NON%');
                           });
                    });
                } else {
                    $sub->where('product_name', 'like', '%COCO%')
                        ->where('product_name', 'not like', '%NON%');
                }
            });
            if (!$isCompanyAdminOrCbo) {
                $q->orWhereHas('lead.branch', function ($bq) {
                    $bq->whereRaw("UPPER(TRIM(branch_type)) = 'COCO'");
                });
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
        $companyId = $this->visibility->companyIdFor($request?->user()) ?? $request?->user()?->company_id;
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
        $vis = $this->resolveForecastingVisibility($request?->user());
        $shouldExcludeCpFromHo = ($isCompanyAdminOrCbo || ($vis['hasCocoBranch'] ?? false)) && !$request?->filled('user_id');

        $nstHoHotQuery = (clone $currentMonthHotProductsQuery)->whereHas('lead', function ($lq) use ($defaultBranchIds) {
            if (!empty($defaultBranchIds)) {
                $lq->whereIn('branch_id', $defaultBranchIds);
            }
        });
        if ($shouldExcludeCpFromHo) {
            $nstHoHotQuery->where(function ($q) use ($cpProductIds) {
                if (!empty($cpProductIds)) {
                    $q->whereNotIn('product_id', $cpProductIds);
                }
                $q->where('product_name', 'not like', '%COCO%')
                  ->where('product_name', 'not like', '%Channel Partner%');
            });
        }
        $nstHoHotCount = (clone $nstHoHotQuery)->count();
        $nstHoDealValue = (float) (clone $nstHoHotQuery)->sum('total_price');
        $nstHoExpectedValue = (float) (clone $nstHoHotQuery)->sum('expected_value');

        $vis = $this->resolveForecastingVisibility($request?->user());
        $userBranchName = $vis['userBranchName'] ?? null;

        $nstHoHeading = ($isCompanyAdminOrCbo || empty($userBranchName))
            ? 'NST - HO'
            : "NST ({$userBranchName})";

        $nonCocoHeading = ($isCompanyAdminOrCbo || empty($userBranchName))
            ? 'Channel Partner - NON COCO Model'
            : "Channel Partner - NON COCO Model ({$userBranchName})";

        $cocoHeading = ($isCompanyAdminOrCbo || empty($userBranchName))
            ? 'Channel Partner - COCO Model'
            : "Channel Partner - COCO Model ({$userBranchName})";

        return [
            'nst_ho' => [
                'count'          => $nstHoHotCount,
                'deal_value'     => $nstHoDealValue,
                'expected_value' => $nstHoExpectedValue,
                'heading'        => $nstHoHeading,
            ],
            'non_coco' => [
                'count'          => $nonCocoHotCount,
                'deal_value'     => $nonCocoDealValue,
                'expected_value' => $nonCocoExpectedValue,
                'product_id'     => $nonCocoProduct?->id,
                'product_name'   => $nonCocoHeading,
                'heading'        => $nonCocoHeading,
            ],
            'coco' => [
                'count'          => $cocoHotCount,
                'deal_value'     => $cocoDealValue,
                'expected_value' => $cocoExpectedValue,
                'product_id'     => $cocoProduct?->id,
                'product_name'   => $cocoHeading,
                'heading'        => $cocoHeading,
            ],
        ];
    }

    /**
     * Resolve forecasting section visibility flags and user role type matching web dashboard logic.
     */
    public function resolveForecastingVisibility(?User $authUser): array
    {
        if (!$authUser) {
            return [
                'hasDefaultBranch'      => false,
                'hasCocoBranch'         => false,
                'hasNonCocoBranch'      => false,
                'canViewCst'            => false,
                'canViewActiveBranches' => false,
                'userRoleType'          => 'nst',
            ];
        }

        $isCompanyAdmin = $authUser->isSuperAdmin() 
            || $authUser->isSystemAdmin() 
            || $authUser->isCompanyAdminRole()
            || $authUser->isCompanyAdmin();
        $isCbo          = $authUser->isCbo();

        if (!$isCompanyAdmin && method_exists($authUser, 'roleKeys')) {
            $keys = collect($authUser->roleKeys()->all());
            $isCompanyAdmin = $keys->contains('company_admin') || $keys->contains('super_admin') || $keys->contains('admin');
        }
        if (!$isCbo && method_exists($authUser, 'roleKeys')) {
            $keys = collect($authUser->roleKeys()->all());
            $isCbo = $keys->intersect(['cbo', 'chief_business_officer', 'cheif_business_officer'])->isNotEmpty();
        }
        if (!$isCompanyAdmin && method_exists($authUser, 'hasAnyRole')) {
            try {
                $isCompanyAdmin = $authUser->hasAnyRole(['company_admin', 'super_admin', 'admin', 'Super Admin', 'Company Admin']);
            } catch (\Throwable $e) {}
        }
        if (!$isCbo && method_exists($authUser, 'hasAnyRole')) {
            try {
                $isCbo = $authUser->hasAnyRole(['cbo', 'CBO', 'chief_business_officer', 'cheif_business_officer']);
            } catch (\Throwable $e) {}
        }

        $isBranchManager = $authUser->isBranchManager() && !$isCompanyAdmin && !$isCbo;
        $isBranchAdmin   = $authUser->isBranchAdmin() && !$isCompanyAdmin && !$isCbo && !$isBranchManager;
        $isTl            = $authUser->hasTlLikeRole() && !$isCompanyAdmin && !$isCbo && !$isBranchManager && !$isBranchAdmin;

        $userBranchIds = method_exists($authUser, 'getMyBranchIds') ? $authUser->getMyBranchIds() : [];
        $defaultBranchId = Branch::where('is_default', true)->value('id') ?? 1;

        if ($isCompanyAdmin || $isCbo) {
            $hasDefaultBranch = true;
            $hasCocoBranch    = true;
            $hasNonCocoBranch = true;
            $canViewCst       = true;
            $canViewActiveBranches = true;
        } elseif ($isBranchManager) {
            // Branch Manager:
            // 1. Additional branches (non-default) check for COCO / NON COCO
            $additionalBranchIds = array_values(array_filter($userBranchIds, fn($id) => (int)$id !== (int)$defaultBranchId));
            $additionalBranches = !empty($additionalBranchIds) ? Branch::whereIn('id', $additionalBranchIds)->get() : collect();

            $hasCocoBranch    = $additionalBranches->contains(fn($b) => strtoupper(trim((string) $b->branch_type)) === 'COCO');
            $hasNonCocoBranch = $additionalBranches->contains(fn($b) => in_array(strtoupper(trim((string) $b->branch_type)), ['NON COCO', 'NON_COCO', 'NON-COCO']));

            // 2. HO: show if BM has users mapped under them in HO OR has leads assigned to themselves in HO
            $descendants = $this->visibility->descendantUserIds($authUser);
            $hasHoMappedUsers = false;
            if ($descendants->isNotEmpty()) {
                $hasHoMappedUsers = User::withoutGlobalScope('branch')
                    ->whereIn('id', $descendants)
                    ->where('branch_id', $defaultBranchId)
                    ->exists();
            }

            $hasOwnHoLeads = Lead::where('branch_id', $defaultBranchId)
                ->where('assigned_to', $authUser->id)
                ->exists();

            $hasDefaultBranch = $hasHoMappedUsers || $hasOwnHoLeads;
            $canViewActiveBranches = false;
            $canViewCst = true;
        } elseif ($isBranchAdmin) {
            // Branch Admin: check their branch(es)
            $branchIds = $userBranchIds;
            if (empty($branchIds) && $authUser->branch_id) {
                $branchIds = [(int) $authUser->branch_id];
            }
            $adminBranches = !empty($branchIds) ? Branch::whereIn('id', $branchIds)->get() : collect();

            $hasDefaultBranch = $adminBranches->contains(fn($b) => (bool) $b->is_default);
            $hasCocoBranch    = $adminBranches->contains(fn($b) => strtoupper(trim((string) $b->branch_type)) === 'COCO');
            $hasNonCocoBranch = $adminBranches->contains(fn($b) => in_array(strtoupper(trim((string) $b->branch_type)), ['NON COCO', 'NON_COCO', 'NON-COCO']));
            $canViewActiveBranches = false;
            $canViewCst = true;
        } elseif ($isTl) {
            // Sales TL: check TL and team mapped members' branches
            $teamUserIds = $this->visibility->descendantUserIds($authUser)->push($authUser->id)->unique();
            $teamBranchIds = User::withoutGlobalScope('branch')
                ->whereIn('id', $teamUserIds)
                ->pluck('branch_id')
                ->filter()
                ->unique()
                ->all();
            if (empty($teamBranchIds) && $authUser->branch_id) {
                $teamBranchIds = [(int) $authUser->branch_id];
            }
            $tlBranches = !empty($teamBranchIds) ? Branch::whereIn('id', $teamBranchIds)->get() : collect();

            $hasDefaultBranch = $tlBranches->contains(fn($b) => (bool) $b->is_default);
            $hasCocoBranch    = $tlBranches->contains(fn($b) => strtoupper(trim((string) $b->branch_type)) === 'COCO');
            $hasNonCocoBranch = $tlBranches->contains(fn($b) => in_array(strtoupper(trim((string) $b->branch_type)), ['NON COCO', 'NON_COCO', 'NON-COCO']));
            $canViewActiveBranches = false;
            $canViewCst = true;
        } else {
            // Sales Executive (NST): check executive's branch
            $execBranch = $authUser->branch;
            $hasDefaultBranch = (bool) ($execBranch?->is_default || (int)$authUser->branch_id === (int)$defaultBranchId);
            $hasCocoBranch    = strtoupper(trim((string) ($execBranch?->branch_type ?? ''))) === 'COCO';
            $hasNonCocoBranch = in_array(strtoupper(trim((string) ($execBranch?->branch_type ?? ''))), ['NON COCO', 'NON_COCO', 'NON-COCO']);
            $canViewActiveBranches = false;
            $canViewCst = true;
        }

        if ($isCompanyAdmin) {
            $userRoleType = 'company_admin';
        } elseif ($isCbo) {
            $userRoleType = 'cbo';
        } elseif ($isBranchManager) {
            $userRoleType = 'branch_manager';
        } elseif ($isBranchAdmin) {
            $userRoleType = 'branch_admin';
        } elseif ($isTl) {
            $userRoleType = 'tl';
        } else {
            $userRoleType = 'nst';
        }

        $userBranchName = null;
        if (!empty($userBranchIds)) {
            $userBranchName = Branch::whereIn('id', $userBranchIds)->value('name');
        } elseif ($authUser->branch_id) {
            $userBranchName = Branch::where('id', $authUser->branch_id)->value('name');
        }

        return [
            'hasDefaultBranch'      => (bool) $hasDefaultBranch,
            'hasCocoBranch'         => (bool) $hasCocoBranch,
            'hasNonCocoBranch'      => (bool) $hasNonCocoBranch,
            'canViewCst'            => (bool) $canViewCst,
            'canViewActiveBranches' => (bool) $canViewActiveBranches,
            'userRoleType'          => $userRoleType,
            'userBranchName'        => $userBranchName,
        ];
    }

    /**
     * Parse target month parameter from request or default to current month.
     */
    private function parseTargetMonth(?Request $request): array
    {
        $targetDate = now();
        if ($request && $request->filled('month')) {
            $mStr = trim((string) $request->input('month'));
            if (preg_match('/^(\d{4})-(\d{2})$/', $mStr, $m)) {
                $targetDate = \Illuminate\Support\Carbon::createFromDate((int) $m[1], (int) $m[2], 1);
            } elseif (is_numeric($mStr) && (int) $mStr >= 1 && (int) $mStr <= 12) {
                $year = $request->filled('year') ? (int) $request->year : now()->year;
                $targetDate = \Illuminate\Support\Carbon::createFromDate($year, (int) $mStr, 1);
            }
        }

        return [
            'month'        => (int) $targetDate->month,
            'year'         => (int) $targetDate->year,
            'month_name'   => $targetDate->format('F Y'),
            'target_month' => $targetDate->format('Y-m'),
            'start_date'   => (clone $targetDate)->startOfMonth(),
            'end_date'     => (clone $targetDate)->endOfMonth(),
        ];
    }

    /**
     * Build active branches current month hot prospect metrics for the Total Prospects modal.
     * Includes HO (Default Branch) and combines both NST + CST prospects branch-wise.
     */
    private function buildActiveBranchesHotMetrics(Request $request): array
    {
        $companyId = $this->visibility->companyIdFor($request->user()) ?? ($request->user()?->company_id ?: 1);
        $visibleBranchIds = $this->visibility->visibleBranchIds($request->user());
        $parsedMonth = $this->parseTargetMonth($request);

        $branches = Branch::where('is_active', true)
            ->where(function ($query) use ($companyId) {
                $query->where('is_default', false)
                    ->orWhereNull('is_default')
                    ->orWhere(function ($dq) use ($companyId) {
                        $dq->where('is_default', true)->where('company_id', $companyId);
                    });
            })
            ->when($visibleBranchIds->isNotEmpty(), fn($query) => $query->whereIn('id', $visibleBranchIds))
            ->when($visibleBranchIds->isEmpty() && $this->visibility->companyIdFor($request->user()), fn($query) => $query->whereRaw('1 = 0'))
            ->when($request->filled('branch_id'), fn($query) => $query->where('id', $request->branch_id))
            ->orderByRaw('is_default DESC, name ASC')
            ->get();

        $branchIds = $branches->pluck('id')->toArray();
        if (empty($branchIds)) {
            return [];
        }

        // 1. NST Hot Products
        $hotProductsGrouped = LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id')
            ->whereRaw('LOWER(lead_products.product_status) = ?', ['hot'])
            ->whereMonth('lead_products.closure_date', $parsedMonth['month'])
            ->whereYear('lead_products.closure_date', $parsedMonth['year'])
            ->whereIn('leads.branch_id', $branchIds)
            ->when($request->filled('user_id'), fn($q) => $q->where('leads.assigned_to', $request->user_id))
            ->select(
                'leads.branch_id',
                DB::raw('COUNT(lead_products.id) as prospect_count'),
                DB::raw('COALESCE(SUM(CASE WHEN lead_products.total_price > 0 THEN lead_products.total_price ELSE lead_products.expected_value END), 0) as deal_value'),
                DB::raw('COALESCE(SUM(lead_products.expected_value), 0) as expected_value')
            )
            ->groupBy('leads.branch_id')
            ->get()
            ->keyBy('branch_id');

        // 2. CST Prospects (Renewals & Development)
        $cstItems = $this->getCstProspectItems($request);
        $cstGrouped = $cstItems->groupBy('branch_id');

        return $branches->map(function ($branch) use ($hotProductsGrouped, $cstGrouped) {
            $stats = $hotProductsGrouped->get($branch->id);
            $nstCount = $stats ? (int) $stats->prospect_count : 0;
            $nstDeal  = $stats ? (float) $stats->deal_value : 0.0;
            $nstExp   = $stats ? (float) $stats->expected_value : 0.0;

            $branchCst = $cstGrouped->get($branch->id, collect());
            $cstCount = $branchCst->count();
            $cstDeal  = (float) $branchCst->sum('deal_value');
            $cstExp   = (float) $branchCst->sum('expected_value');

            return [
                'id'             => $branch->id,
                'name'           => $branch->name,
                'code'           => $branch->code,
                'is_default'     => (bool) $branch->is_default,
                'branch_type'    => $branch->branch_type,
                'prospect_count' => $nstCount + $cstCount,
                'deal_value'     => $nstDeal + $cstDeal,
                'expected_value' => $nstExp + $cstExp,
                'nst_count'      => $nstCount,
                'cst_count'      => $cstCount,
                'nst_deal'       => $nstDeal,
                'cst_deal'       => $cstDeal,
                'nst_exp'        => $nstExp,
                'cst_exp'        => $cstExp,
            ];
        })->values()->toArray();
    }

    /**
     * Build CST prospect items combining:
     * 1. CST dashboard current month renewals (count-wise / renewal products with end date in current month).
     * 2. Development department projects moved to production with expected date in current month.
     */
    public function getCstProspectItems(?Request $request = null): Collection
    {
        $currentUser = $request?->user() ?: (auth('sanctum')->user() ?: auth()->user());
        $parsedMonth = $this->parseTargetMonth($request);
        $cmStart = $parsedMonth['start_date'];
        $cmEnd   = $parsedMonth['end_date'];

        $items = collect();
        $seenPiIds = [];
        $seenLpIds = [];

        $isCompanyAdminOrCbo = $currentUser && (
            $currentUser->isSuperAdmin() ||
            $currentUser->isSystemAdmin() ||
            $currentUser->isCompanyAdminRole() ||
            $currentUser->isCbo()
        );

        // 1. Fetch count-wise recurring initiations for renewals (matching CST dashboard)
        $renewalPisQuery = ProductionInitiation::query()
            ->with([
                'lead' => function ($lq) {
                    $lq->with(['assignedTo:id,name', 'branch:id,name', 'customerSupportExecutive:id,name', 'customerSupportTl:id,name']);
                },
                'leadProduct',
                'product',
                'department',
            ])
            ->whereHas('product', function ($q) {
                $q->where('count_wise_report', true)
                  ->orWhere('is_this_renewal_product', true);
            });

        if (!$isCompanyAdminOrCbo) {
            $renewalPisQuery->whereHas('lead', function ($lq) use ($currentUser, $request) {
                $this->visibility->applyLeadVisibility($lq, $currentUser);
                if ($request && $request->filled('user_id')) {
                    $lq->where('assigned_to', $request->user_id);
                }
            });
        } elseif ($request && $request->filled('user_id')) {
            $renewalPisQuery->whereHas('lead', function ($lq) use ($request) {
                $lq->where('assigned_to', $request->user_id);
            });
        }

        $renewalPis = $renewalPisQuery->get();

        foreach ($renewalPis as $pi) {
            $rDateStr = null;
            $formData = is_array($pi->custom_form_data)
                ? $pi->custom_form_data
                : json_decode($pi->custom_form_data ?? '[]', true) ?? [];

            foreach ($formData as $field) {
                if (!is_array($field)) continue;
                $fieldKey = $field['field_name'] ?? ($field['label'] ?? ($field['key'] ?? ''));
                $fieldVal = $field['value'] ?? '';
                $key = strtolower(is_array($fieldKey) ? implode(' ', array_filter(array_map('strval', $fieldKey))) : trim((string)$fieldKey));
                $val = is_array($fieldVal) ? implode(', ', array_filter(array_map('strval', $fieldVal))) : trim((string)$fieldVal);

                if (in_array($key, ['end_date', 'enddate', 'end date', 'smm_end_date', 'ovp_end_date'], true)) {
                    if (!empty($val)) {
                        try {
                            $rDateStr = Carbon::parse($val)->toDateString();
                            break;
                        } catch (\Throwable $e) {}
                    }
                }
            }

            if (!$rDateStr && $pi->project_delivery_date) {
                $rDateStr = Carbon::parse($pi->project_delivery_date)->toDateString();
            }

            if ($rDateStr) {
                $rDate = Carbon::parse($rDateStr);
                if ($rDate->between($cmStart, $cmEnd)) {
                    $lead = $pi->lead;
                    $lp = $pi->leadProduct;
                    $dealVal = (float) ($lp?->total_price ?? $pi->lead_budget_amount ?? 0);
                    $expVal = (float) ($pi->expected_value ?? $lp?->expected_value ?? $dealVal);

                    $seenPiIds[$pi->id] = true;
                    if ($pi->lead_product_id) {
                        $seenLpIds[$pi->lead_product_id] = true;
                    }

                    $salesPerson = $lead?->assignedTo?->name ?: '-';
                    $cstPerson   = $lead?->customerSupportExecutive?->name ?: ($lead?->customerSupportTl?->name ?: '-');
                    $assignedName = $cstPerson !== '-' ? $cstPerson : ($salesPerson !== '-' ? $salesPerson : 'Unassigned');

                    $items->push([
                        'pi_id'            => $pi->id,
                        'lead_id'          => $pi->lead_id,
                        'lead_view_url'    => $pi->lead_id ? route('leads.show', $pi->lead_id) : null,
                        'project_view_url' => url('/projects-details/' . $pi->id),
                        'company_name'     => $lead?->company_name ?: ($lead?->business_name ?: ($pi->company_name ?: '-')),
                        'customer_name'    => $lead?->contact_name ?: ($pi->client_name ?: '-'),
                        'product_name'     => $pi->product_name ?: ($pi->product?->product_name ?: ($lp?->product_name ?: 'Renewal Product')),
                        'status'           => 'Renewal',
                        'deal_value'       => $dealVal,
                        'expected_value'   => $expVal,
                        'closure_date'     => $rDate->format('d M Y'),
                        'closure_date_raw' => $rDate->format('Y-m-d'),
                        'sales_person_name' => $salesPerson,
                        'cst_person_name'   => $cstPerson,
                        'assigned_to'      => $assignedName,
                        'executive_name'   => $salesPerson !== '-' ? $salesPerson : $cstPerson,
                        'branch_id'        => $lead?->branch_id,
                        'branch_name'      => $lead?->branch?->name ?: 'General',
                        'source_type'      => 'renewal',
                    ]);
                }
            }
        }

        // 2. Fetch Development Department projects moved to production with expected date in current month
        $devPisQuery = ProductionInitiation::query()
            ->with([
                'lead' => function ($lq) {
                    $lq->with(['assignedTo:id,name', 'branch:id,name', 'customerSupportExecutive:id,name', 'customerSupportTl:id,name']);
                },
                'leadProduct',
                'product',
                'department',
            ])
            ->where(function ($q) {
                $q->where('department_id', 1)
                  ->orWhereHas('department', function ($dq) {
                      $dq->where('name', 'like', '%develop%');
                  });
            })
            ->where(function ($q) use ($cmStart, $cmEnd) {
                $q->whereBetween('expected_date', [$cmStart, $cmEnd])
                  ->orWhereHas('leadProduct', function ($lq) use ($cmStart, $cmEnd) {
                      $lq->whereBetween('closure_date', [$cmStart, $cmEnd]);
                  });
            });

        if (!$isCompanyAdminOrCbo) {
            $devPisQuery->whereHas('lead', function ($lq) use ($currentUser, $request) {
                $this->visibility->applyLeadVisibility($lq, $currentUser);
                if ($request && $request->filled('user_id')) {
                    $lq->where('assigned_to', $request->user_id);
                }
            });
        } elseif ($request && $request->filled('user_id')) {
            $devPisQuery->whereHas('lead', function ($lq) use ($request) {
                $lq->where('assigned_to', $request->user_id);
            });
        }

        $devPis = $devPisQuery->get();

        foreach ($devPis as $pi) {
            if (isset($seenPiIds[$pi->id]) || ($pi->lead_product_id && isset($seenLpIds[$pi->lead_product_id]))) {
                continue;
            }

            $lead = $pi->lead;
            $lp = $pi->leadProduct;
            $expDate = $pi->expected_date ?? $lp?->closure_date;
            if (!$expDate) continue;

            $cExpDate = Carbon::parse($expDate);
            if (!$cExpDate->between($cmStart, $cmEnd)) continue;

            $dealVal = (float) ($lp?->total_price ?? $pi->lead_budget_amount ?? 0);
            $expVal = (float) ($pi->expected_value ?? $lp?->expected_value ?? $dealVal);

            $salesPerson = $lead?->assignedTo?->name ?: '-';
            $cstPerson   = $lead?->customerSupportExecutive?->name ?: ($lead?->customerSupportTl?->name ?: '-');
            $assignedName = $cstPerson !== '-' ? $cstPerson : ($salesPerson !== '-' ? $salesPerson : 'Unassigned');

            $items->push([
                'pi_id'            => $pi->id,
                'lead_id'          => $pi->lead_id,
                'lead_view_url'    => $pi->lead_id ? route('leads.show', $pi->lead_id) : null,
                'project_view_url' => url('/projects-details/' . $pi->id),
                'company_name'     => $lead?->company_name ?: ($lead?->business_name ?: ($pi->company_name ?: '-')),
                'customer_name'    => $lead?->contact_name ?: ($pi->client_name ?: '-'),
                'product_name'     => $pi->product_name ?: ($pi->product?->product_name ?: ($lp?->product_name ?: 'Development Project')),
                'status'           => $lp?->product_status ? ucfirst($lp->product_status) : ($pi->status ? ucfirst($pi->status) : 'Development Prospect'),
                'deal_value'       => $dealVal,
                'expected_value'   => $expVal,
                'closure_date'     => $cExpDate->format('d M Y'),
                'closure_date_raw' => $cExpDate->format('Y-m-d'),
                'sales_person_name' => $salesPerson,
                'cst_person_name'   => $cstPerson,
                'assigned_to'      => $assignedName,
                'executive_name'   => $salesPerson !== '-' ? $salesPerson : $cstPerson,
                'branch_id'        => $lead?->branch_id,
                'branch_name'      => $lead?->branch?->name ?: 'General',
                'source_type'      => 'development',
            ]);
        }

        return $items;
    }

    public function buildCstProspectMetrics(?Request $request = null): array
    {
        $items = $this->getCstProspectItems($request);

        return [
            'count'          => $items->count(),
            'deal_value'     => (float) $items->sum('deal_value'),
            'expected_value' => (float) $items->sum('expected_value'),
            'product_name'   => 'CST',
        ];
    }

    /**
     * Get hot leads for a specific branch or card category in the current month by closure_date.
     */
    public function branchHotLeads(Request $request): JsonResponse
    {
        $currentUser = $request?->user() ?: (auth('sanctum')->user() ?: auth()->user());
        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $type = $request->input('type');
        $branchId = (int) $request->input('branch_id');

        if (!$type && !$branchId) {
            return $this->error('Type or Branch ID is required.', 422);
        }

        $vis = $this->resolveForecastingVisibility($currentUser);
        $hasDefaultBranch      = $vis['hasDefaultBranch'];
        $hasCocoBranch         = $vis['hasCocoBranch'];
        $hasNonCocoBranch      = $vis['hasNonCocoBranch'];
        $canViewCst            = $vis['canViewCst'];
        $canViewActiveBranches = $vis['canViewActiveBranches'];
        $userRoleType          = $vis['userRoleType'];
        $isCompanyAdminOrCbo   = in_array($userRoleType, ['company_admin', 'cbo'], true);

        // Security: Block unauthorized user_id parameter tampering
        if ($request->filled('user_id')) {
            $requestedUserId = (int) $request->user_id;
            if (!$isCompanyAdminOrCbo) {
                $allowedUserIds = $this->visibility->visibleUserIds($currentUser);
                if ($allowedUserIds !== null && !$allowedUserIds->contains($requestedUserId)) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized user filter.'], 403);
                }
            }
        }

        // Security: Validate section authorization
        if ($type === 'nst_ho' && !$hasDefaultBranch) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: NST - HO.'], 403);
        }
        if ($type === 'non_coco' && !$hasNonCocoBranch) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: Channel Partner NON COCO.'], 403);
        }
        if ($type === 'coco' && !$hasCocoBranch) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: Channel Partner COCO.'], 403);
        }
        if ($type === 'all' && !$canViewActiveBranches) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: Active Branches.'], 403);
        }
        if ($type === 'active_branch_coco' && !$canViewActiveBranches && !$hasCocoBranch) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: COCO Branches.'], 403);
        }
        if ($type === 'active_branch_non_coco' && !$canViewActiveBranches && !$hasNonCocoBranch) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: NON COCO Branches.'], 403);
        }
        if ($type === 'active_branch_ho' && !$canViewActiveBranches && !$hasDefaultBranch) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: HO Branches.'], 403);
        }
        if ($type === 'cst' && !$canViewCst) {
            return response()->json(['success' => false, 'message' => 'Unauthorized section access: CST.'], 403);
        }

        $parsedMonth = $this->parseTargetMonth($request);

        if ($type === 'all') {
            $title = 'All Active Branches Brief Report';
            $subtitle = 'Hot prospects & branch performance summary for ' . $parsedMonth['month_name'];
            $branchType = 'ALL';

            $query = LeadProduct::query()
                ->with([
                    'lead' => function ($lq) {
                        $lq->with(['assignedTo:id,name', 'branch:id,name', 'customerSupportExecutive:id,name', 'customerSupportTl:id,name']);
                    },
                    'product:id,product_name',
                    'leadStatus:id,name'
                ])
                ->whereRaw('LOWER(product_status) = ?', ['hot'])
                ->whereMonth('closure_date', $parsedMonth['month'])
                ->whereYear('closure_date', $parsedMonth['year']);

            $query->whereHas('lead', function ($lq) use ($currentUser, $request) {
                $this->visibility->applyLeadVisibility($lq, $currentUser);
                if ($request->filled('user_id')) {
                    $lq->where('assigned_to', $request->user_id);
                }
            });

            $hotProducts = $query->orderByDesc('closure_date')->get();

            $rows = $hotProducts->map(function ($item, $idx) {
                $lead = $item->lead;
                $salesPerson = $lead?->assignedTo?->name ?: '-';
                $cstPerson   = $lead?->customerSupportExecutive?->name ?: ($lead?->customerSupportTl?->name ?: '-');
                return [
                    'index'            => $idx + 1,
                    'lead_id'          => $lead?->id,
                    'lead_view_url'    => $lead ? route('leads.show', $lead->id) : null,
                    'company_name'     => $lead?->company_name ?: ($lead?->business_name ?: '-'),
                    'customer_name'    => $lead?->contact_name ?: '-',
                    'product_name'     => $item->product_name ?: ($item->product?->product_name ?: '-'),
                    'status'           => $item->product_status ? ucfirst($item->product_status) : ($item->leadStatus?->name ?? 'Hot'),
                    'deal_value'       => (float) $item->total_price,
                    'expected_value'   => (float) ($item->expected_value ?? 0),
                    'closure_date'     => $item->closure_date ? $item->closure_date->format('d M Y') : '-',
                    'closure_date_raw' => $item->closure_date ? $item->closure_date->format('Y-m-d') : null,
                    'sales_person_name' => $salesPerson,
                    'cst_person_name'   => $cstPerson,
                    'executive_name'   => $salesPerson !== '-' ? $salesPerson : ($cstPerson !== '-' ? $cstPerson : '-'),
                    'branch_name'      => $lead?->branch?->name ?: 'Coimbatore (HO)',
                ];
            });

            $cstItems = $this->getCstProspectItems($request);
            foreach ($cstItems as $cItem) {
                $rows->push([
                    'index'            => $rows->count() + 1,
                    'lead_id'          => $cItem['lead_id'],
                    'lead_view_url'    => $cItem['lead_view_url'],
                    'project_view_url' => $cItem['project_view_url'] ?? null,
                    'company_name'     => $cItem['company_name'],
                    'customer_name'    => $cItem['customer_name'],
                    'product_name'     => $cItem['product_name'],
                    'status'           => $cItem['status'] ?? 'Renewal',
                    'deal_value'       => (float) $cItem['deal_value'],
                    'expected_value'   => (float) $cItem['expected_value'],
                    'closure_date'     => $cItem['closure_date'],
                    'closure_date_raw' => $cItem['closure_date_raw'] ?? null,
                    'sales_person_name' => $cItem['sales_person_name'] ?? '-',
                    'cst_person_name'   => $cItem['cst_person_name'] ?? '-',
                    'executive_name'   => $cItem['sales_person_name'] ?? ($cItem['executive_name'] ?? '-'),
                    'branch_name'      => $cItem['branch_name'] ?? 'CST',
                ]);
            }

            $rows = $rows->values()->map(function ($r, $i) {
                $r['index'] = $i + 1;
                return $r;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'branch' => [
                        'id'          => 'all',
                        'name'        => $title,
                        'subtitle'    => $subtitle,
                        'branch_type' => $branchType,
                    ],
                    'period'         => $parsedMonth['month_name'],
                    'total_count'    => $rows->count(),
                    'total_deal'     => (float) $rows->sum('deal_value'),
                    'total_expected' => (float) $rows->sum('expected_value'),
                    'leads'          => $rows,
                ],
            ]);
        }

        if ($type === 'cst') {
            $subtitle = 'Renewals & Development Prospects for ' . $parsedMonth['month_name'];

            $items = $this->getCstProspectItems($request);
            $rows = $items->values()->map(function ($item, $idx) {
                $item['index'] = $idx + 1;
                $item['branch_name'] = $item['branch_name'] ?? 'CST';
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'branch' => [
                        'id'          => 'cst',
                        'name'        => 'CST',
                        'subtitle'    => $subtitle,
                        'branch_type' => 'CST',
                    ],
                    'leads'          => $rows,
                    'total_deal'     => (float) $rows->sum('deal_value'),
                    'total_expected' => (float) $rows->sum('expected_value'),
                ],
            ]);
        }

        $branch = null;
        $title = 'Hot Prospects';
        $subtitle = 'Closure hot leads for ' . $parsedMonth['month_name'];
        $branchType = null;

        $query = LeadProduct::query()
            ->with([
                'lead' => function ($lq) {
                    $lq->with(['assignedTo:id,name', 'branch:id,name', 'customerSupportExecutive:id,name', 'customerSupportTl:id,name']);
                },
                'product:id,product_name',
                'leadStatus:id,name'
            ])
            ->whereRaw('LOWER(product_status) = ?', ['hot'])
            ->whereMonth('closure_date', $parsedMonth['month'])
            ->whereYear('closure_date', $parsedMonth['year']);

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

            $query->whereHas('lead', function ($lq) use ($defaultBranchIds) {
                if (!empty($defaultBranchIds)) {
                    $lq->whereIn('branch_id', $defaultBranchIds);
                }
            });

            $shouldExcludeCpFromHo = ($isCompanyAdminOrCbo || ($vis['hasCocoBranch'] ?? false)) && !$request->filled('user_id');
            if ($shouldExcludeCpFromHo) {
                $query->where(function ($q) use ($cpProductIds) {
                    if (!empty($cpProductIds)) {
                        $q->whereNotIn('product_id', $cpProductIds);
                    }
                    $q->where('product_name', 'not like', '%COCO%')
                      ->where('product_name', 'not like', '%Channel Partner%');
                });
            }

        } elseif ($type === 'non_coco') {
            $isCompanyAdminOrCbo = $currentUser && ($currentUser->isSuperAdmin() || $currentUser->isSystemAdmin() || $currentUser->isCompanyAdminRole() || $currentUser->isCbo());
            $title = 'Channel Partner - NON COCO Model';
            $subtitle = $isCompanyAdminOrCbo ? 'Channel Partner NON COCO Hot Products' : 'NON COCO Hot Products & Branch Prospects';
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

            $query->where(function ($q) use ($nonCocoProduct, $isCompanyAdminOrCbo) {
                $q->where(function ($sub) use ($nonCocoProduct) {
                    if ($nonCocoProduct) {
                        $sub->where('product_id', $nonCocoProduct->id)
                            ->orWhere('product_name', 'like', '%NON%COCO%');
                    } else {
                        $sub->where('product_name', 'like', '%NON%COCO%');
                    }
                });
                if (!$isCompanyAdminOrCbo) {
                    $q->orWhereHas('lead.branch', function ($bq) {
                        $bq->whereRaw("UPPER(TRIM(branch_type)) in ('NON COCO', 'NON_COCO', 'NON-COCO')");
                    });
                }
            });

        } elseif ($type === 'coco') {
            $isCompanyAdminOrCbo = $currentUser && ($currentUser->isSuperAdmin() || $currentUser->isSystemAdmin() || $currentUser->isCompanyAdminRole() || $currentUser->isCbo());
            $title = 'Channel Partner - COCO Model';
            $subtitle = $isCompanyAdminOrCbo ? 'Channel Partner COCO Hot Products' : 'COCO Hot Products & Branch Prospects';
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

            $query->where(function ($q) use ($cocoProduct, $isCompanyAdminOrCbo) {
                $q->where(function ($sub) use ($cocoProduct) {
                    if ($cocoProduct) {
                        $sub->where(function ($sq) use ($cocoProduct) {
                            $sq->where('product_id', $cocoProduct->id)
                               ->orWhere(function ($ssq) {
                                   $ssq->where('product_name', 'like', '%COCO%')
                                       ->where('product_name', 'not like', '%NON%');
                               });
                        });
                    } else {
                        $sub->where('product_name', 'like', '%COCO%')
                            ->where('product_name', 'not like', '%NON%');
                    }
                });
                if (!$isCompanyAdminOrCbo) {
                    $q->orWhereHas('lead.branch', function ($bq) {
                        $bq->whereRaw("UPPER(TRIM(branch_type)) = 'COCO'");
                    });
                }
            });

        } elseif ($type === 'active_branch_coco') {
            $title = 'COCO Branches';
            $subtitle = 'Hot products & branch prospects for COCO model branches';
            $branchType = 'COCO';

            $cocoBranchIds = Branch::whereRaw("UPPER(TRIM(branch_type)) = 'COCO'")->pluck('id')->toArray();
            $query->whereHas('lead', function ($lq) use ($cocoBranchIds) {
                if (!empty($cocoBranchIds)) {
                    $lq->whereIn('branch_id', $cocoBranchIds);
                } else {
                    $lq->whereRaw('1 = 0');
                }
            });

        } elseif ($type === 'active_branch_non_coco') {
            $title = 'NON COC Branches';
            $subtitle = 'Hot products & branch prospects for NON COCO model branches';
            $branchType = 'NON COCO';

            $nonCocoBranchIds = Branch::whereRaw("UPPER(TRIM(branch_type)) in ('NON COCO', 'NON_COCO', 'NON-COCO')")->pluck('id')->toArray();
            $query->whereHas('lead', function ($lq) use ($nonCocoBranchIds) {
                if (!empty($nonCocoBranchIds)) {
                    $lq->whereIn('branch_id', $nonCocoBranchIds);
                } else {
                    $lq->whereRaw('1 = 0');
                }
            });

        } elseif ($type === 'active_branch_ho') {
            $title = 'Head Office (HO)';
            $subtitle = 'Hot products & branch prospects for default HO branches';
            $branchType = 'HO';

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

            $query->whereHas('lead', function ($lq) use ($defaultBranchIds) {
                if (!empty($defaultBranchIds)) {
                    $lq->whereIn('branch_id', $defaultBranchIds);
                } else {
                    $lq->whereRaw('1 = 0');
                }
            });

        } else {
            // By Branch ID
            $branch = Branch::find($branchId);
            if (!$branch) {
                return $this->error('Branch not found.', 404);
            }

            $visibleBranchIds = $this->visibility->visibleBranchIds($currentUser);
            if ($visibleBranchIds->isNotEmpty() && !$visibleBranchIds->contains($branchId)) {
                return $this->error('Unauthorized branch access.', 403);
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
            $salesPerson = $lead?->assignedTo?->name ?: '-';
            $cstPerson   = $lead?->customerSupportExecutive?->name ?: ($lead?->customerSupportTl?->name ?: '-');
            return [
                'index'            => $idx + 1,
                'lead_id'          => $lead?->id,
                'lead_view_url'    => $lead ? route('leads.show', $lead->id) : null,
                'company_name'     => $lead?->company_name ?: ($lead?->business_name ?: '-'),
                'customer_name'    => $lead?->contact_name ?: '-',
                'product_name'     => $item->product_name ?: ($item->product?->product_name ?: '-'),
                'status'           => $item->product_status ? ucfirst($item->product_status) : ($item->leadStatus?->name ?? 'Hot'),
                'deal_value'       => (float) $item->total_price,
                'expected_value'   => (float) ($item->expected_value ?? 0),
                'closure_date'     => $item->closure_date ? $item->closure_date->format('d M Y') : '-',
                'closure_date_raw' => $item->closure_date ? $item->closure_date->format('Y-m-d') : null,
                'sales_person_name' => $salesPerson,
                'cst_person_name'   => $cstPerson,
                'executive_name'   => $salesPerson !== '-' ? $salesPerson : ($cstPerson !== '-' ? $cstPerson : '-'),
                'branch_name'      => $lead?->branch?->name ?: 'Coimbatore (HO)',
            ];
        });

        $cstItemsToAppend = collect();
        if ($branchId) {
            $cstItemsToAppend = $this->getCstProspectItems($request)->filter(function ($cItem) use ($branchId) {
                return ($cItem['branch_id'] ?? null) == $branchId;
            });
        } elseif ($type === 'active_branch_coco') {
            $cocoBranchIds = Branch::whereRaw("UPPER(TRIM(branch_type)) = 'COCO'")->pluck('id')->toArray();
            $cstItemsToAppend = $this->getCstProspectItems($request)->filter(function ($cItem) use ($cocoBranchIds) {
                return in_array($cItem['branch_id'] ?? null, $cocoBranchIds);
            });
        } elseif ($type === 'active_branch_non_coco') {
            $nonCocoBranchIds = Branch::whereRaw("UPPER(TRIM(branch_type)) in ('NON COCO', 'NON_COCO', 'NON-COCO')")->pluck('id')->toArray();
            $cstItemsToAppend = $this->getCstProspectItems($request)->filter(function ($cItem) use ($nonCocoBranchIds) {
                return in_array($cItem['branch_id'] ?? null, $nonCocoBranchIds);
            });
        } elseif ($type === 'active_branch_ho') {
            $companyId = $this->visibility->companyIdFor($currentUser) ?? $currentUser?->company_id;
            $defaultBranchQuery = Branch::where('is_default', true);
            if ($companyId) {
                $defaultBranchQuery->where('company_id', $companyId);
            }
            $defaultBranchIds = $defaultBranchQuery->pluck('id')->toArray();
            if (empty($defaultBranchIds)) {
                $defaultBranchIds = Branch::where('is_default', true)->pluck('id')->toArray();
            }
            $cstItemsToAppend = $this->getCstProspectItems($request)->filter(function ($cItem) use ($defaultBranchIds) {
                return in_array($cItem['branch_id'] ?? null, $defaultBranchIds);
            });
        }

        if ($cstItemsToAppend->isNotEmpty()) {
            foreach ($cstItemsToAppend as $cItem) {
                $rows->push([
                    'index'            => $rows->count() + 1,
                    'lead_id'          => $cItem['lead_id'],
                    'lead_view_url'    => $cItem['lead_view_url'],
                    'project_view_url' => $cItem['project_view_url'] ?? null,
                    'company_name'     => $cItem['company_name'],
                    'customer_name'    => $cItem['customer_name'],
                    'product_name'     => $cItem['product_name'],
                    'status'           => $cItem['status'] ?? 'Renewal',
                    'deal_value'       => (float) $cItem['deal_value'],
                    'expected_value'   => (float) $cItem['expected_value'],
                    'closure_date'     => $cItem['closure_date'],
                    'closure_date_raw' => $cItem['closure_date_raw'] ?? null,
                    'sales_person_name' => $cItem['sales_person_name'] ?? '-',
                    'cst_person_name'   => $cItem['cst_person_name'] ?? '-',
                    'executive_name'   => $cItem['sales_person_name'] ?? ($cItem['executive_name'] ?? '-'),
                    'branch_name'      => $cItem['branch_name'] ?? 'CST',
                ]);
            }
            $rows = $rows->values()->map(function ($r, $i) {
                $r['index'] = $i + 1;
                return $r;
            });
        }


        return $this->success([
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
        ], 'Branch hot leads fetched.');
    }
}
