<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ProductionCountReport;
use App\Models\ProductionInitiation;
use App\Models\ProjectTimesheet;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\Product;
use App\Models\DesignSettingTarget;
use App\Models\ProjectTestingDetail;
use App\Models\ProjectBug;
use App\Services\ProductionUpdateRecorder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class ProjectApiController extends Controller
{
    private const TL_ROLE_KEYS = [
        'tl',
        'team_lead',
        'team_leader',
        'teamlead',
        'manager',
        'project_manager',
        'web_team_leader',
        'mobile_app_team_leader',
        'design_team_lead',
        'team_lead_digital_marketing',
        'software_team_leader',
    ];

    public function __construct(private readonly NotificationService $notifications) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /mobile/projects/dashboard
    // ─────────────────────────────────────────────────────────────────────────
    public function dashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        $projects = $this->visibleProjectsQuery($user)
            ->get()
            ->map(function (ProductionInitiation $project) use ($user) {
                $project = $this->decorateProjectForUser($project, $user);
                $receivedAmount = (float) ($project->leadProduct?->payments?->sum('amount')
                    ?? $project->leadProduct?->amount_paid ?? 0);

                $project->dashboard_date       = $this->dashboardProjectDate($project, $user);
                $project->project_delivery_date = $this->projectDeliveryDate($project);
                $project->project_value         = (float) ($project->leadProduct?->total_price ?? 0);
                $project->received_amount       = $receivedAmount;
                $project->balance_amount        = max(0, $project->project_value - $receivedAmount);
                $project->allocated_employee_count = collect(Arr::wrap($project->project_allocated_employee_user_ids))
                    ->filter()->count();

                return $project;
            })
            ->values();

        $dashboardFilters = [
            'date_from'      => trim((string) $request->query('date_from', '')),
            'date_to'        => trim((string) $request->query('date_to', '')),
            'project_id'     => trim((string) $request->query('project_id', '')),
            'team_member_id' => $this->shouldAllowDashboardUserFilter($user)
                ? trim((string) $request->query('team_member_id', ''))
                : '',
            'allocation_status' => trim((string) $request->query('allocation_status', '')),
        ];

        $filteredProjects = $this->filterDashboardProjects($projects, $dashboardFilters, $user);

        $stats = [
            'allocated_projects' => $filteredProjects->count(),
            'project_value'      => round($filteredProjects->sum('project_value'), 2),
            'received_amount'    => round($filteredProjects->sum('received_amount'), 2),
            'balance_amount'     => round($filteredProjects->sum('balance_amount'), 2),
        ];

        $currentMonthDelivery = $this->currentMonthDeliveryProjects($filteredProjects);
        $timesheetSummary     = $this->dashboardTimesheetSummary($user, $filteredProjects, $dashboardFilters);
        $allocationPendingProjects = ProductionInitiation::query()
            ->whereNull('project_allocated_at')
            ->count();

        $allocationPendingProjects = ProductionInitiation::query()
            ->whereNull('project_allocated_at')
            ->count();

        [$deliverySectionTitle, $deliverySectionBadge] = $this->resolveDeliverySectionLabels($dashboardFilters);

        $quickUpdateProjects = $projects
            ->filter(fn(ProductionInitiation $p) => $this->isDevelopmentProject($p))
            ->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $developmentProductWiseStats = $currentMonthDelivery
            ->filter(fn($p) => $this->isDevelopmentProject($p))
            ->groupBy('product_name')
            ->map(fn($group) => [
                'delivered' => $group->where('project_execution_status', 'delivered')->count(),
                'ongoing'   => $group->whereIn('project_execution_status', ['ontrack', 'hold'])->count(),
            ]);

        $paymentStats = [
            'received' => round((float) $filteredProjects->sum('received_amount'), 2),
            'pending'  => round((float) $filteredProjects->sum('balance_amount'), 2),
        ];

        $sixMonthsRevenue = $this->sixMonthsRevenueTrend($projects);

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'                        => $stats,
                'dashboard_filters'            => $dashboardFilters,
                'project_options'              => $projects->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->map(fn($p) => $this->serializeProjectSummary($p)),
                'team_member_options'          => $this->dashboardTeamMembers($user, $projects),
                'current_month_delivery'       => $currentMonthDelivery->map(fn($p) => $this->serializeProjectSummary($p))->values(),
                'recent_projects'              => $filteredProjects->take(10)->map(fn($p) => $this->serializeProjectSummary($p))->values(),
                'timesheet_summary'            => $timesheetSummary,
                'is_tl_scoped_view'            => $this->shouldLimitToAssignedProjects($user),
                'is_contributor_scoped_view'   => $this->shouldLimitToEmployeeProjects($user),
                'can_quick_add_production_update' => $this->canQuickAddProductionUpdate($user),
                // These three were already being computed above (lines
                // 112-125) but never included in the response — the
                // Flutter dashboard model/UI expects them (development_
                // product_wise_stats / payment_stats / six_months_revenue)
                // and hides its chart sections entirely when they're
                // missing, which is why the graphs never rendered on
                // mobile even though the web dashboard shows them.
                'development_product_wise_stats' => $developmentProductWiseStats,
                'payment_stats'                 => $paymentStats,
                'six_months_revenue'            => $sixMonthsRevenue,
            ],
        ]);
    }

    private function resolveDeliverySectionLabels(array $dashboardFilters): array
    {
        $from = $this->parseFilterDate($dashboardFilters['date_from'] ?? '');
        $to   = $this->parseFilterDate($dashboardFilters['date_to'] ?? '');

        if (! $from || ! $to) {
            return ['Delivery Planned Projects', 'Planned'];
        }

        if ($from->format('Y-m') === $to->format('Y-m')) {
            $monthName = $from->format('F Y');
            return ["{$monthName} Delivery Planned Projects", "Planned in {$from->format('M Y')}"];
        }

        return [
            "Delivery Planned Projects ({$from->format('d M Y')} - {$to->format('d M Y')})",
            'Planned in Range',
        ];
    }

    private function isDevelopmentProject(ProductionInitiation $productionInitiation): bool
    {
        return $this->roleKey((string) ($productionInitiation->department?->name ?? '')) === 'development';
    }

    private function sixMonthsRevenueTrend(Collection $projects): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[now()->subMonths($i)->format('Y-m')] = [
                'month_name' => now()->subMonths($i)->format('F Y'),
                'revenue'    => 0.0,
            ];
        }

        $leadProductIds = $projects->pluck('lead_product_id')->filter()->unique()->all();
        if ($leadProductIds === []) {
            return array_values($months);
        }

        $sixMonthsAgo = now()->startOfMonth()->subMonths(5);
        $payments = DB::table('lead_product_payments')
            ->whereIn('lead_product_id', $leadProductIds)
            ->where('payment_date', '>=', $sixMonthsAgo->toDateString())
            ->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as total_amount')
            ->groupBy('month')
            ->get();

        foreach ($payments as $payment) {
            if (isset($months[$payment->month])) {
                $months[$payment->month]['revenue'] = round((float) $payment->total_amount, 2);
            }
        }

        return array_values($months);
    }

    // Valid project_execution_status values — matches the `in:` validation
    // rule on updateStatus() below (and web's ProjectController@updateStatus).
    private const EXECUTION_STATUSES = [
        'ontrack'   => 'On Track',
        'hold'      => 'On Hold',
        'delivered' => 'Delivered',
        'lost'      => 'Lost',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /mobile/projects
    //
    //  Previously this loaded EVERY project visible to the user (`->get()`
    //  with no LIMIT — for an Admin that's every allocation-pending +
    //  allocated project in the company) and did all filtering as in-memory
    //  Collection checks (and for the Admin/TL bucket view, there was no
    //  filtering at all — not even search). Mirrors the same fix already
    //  applied to ProductionApprovalApiController::index(): push every
    //  filter that maps cleanly onto a real column/relation down to a SQL
    //  WHERE clause via buildFilteredProjectsQuery(), and paginate at the
    //  database... with one deliberate exception — see the comment above
    //  filterByDeliveryAndDue() for why delivery-date/due-date filtering
    //  stays in-memory.
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $user    = auth()->user();
        $bucket  = trim((string) $request->query('bucket', 'allocation_pending'));
        $deliveryFrom = trim((string) $request->query('delivery_from', ''));
        $deliveryTo   = trim((string) $request->query('delivery_to', ''));
        $due          = trim((string) $request->query('due', ''));
        $page         = (int) $request->input('page', 1);
        $perPage      = (int) $request->input('per_page', 15);

        $isContributorScopedView = $this->shouldLimitToEmployeeProjects($user);
        $isTlScopedView          = $this->shouldLimitToAssignedProjects($user);

        $query = $this->buildFilteredProjectsQuery($user, [
            'search'            => (string) $request->query('search', ''),
            'product_id'        => (string) $request->query('product_id', ''),
            'department_id'     => (string) $request->query('department_id', ''),
            'project_category'  => (string) $request->query('project_category', ''),
            'status'            => (string) $request->query('status', ''),
            'employee_id'       => (string) $request->query('employee_id', ''),
            'customer'          => (string) $request->query('customer', ''),
            'created_from'      => (string) $request->query('created_from', ''),
            'created_to'        => (string) $request->query('created_to', ''),
            'my_projects'       => $request->boolean('my_projects'),
        ]);

        $candidates = $query->get()->map(fn (ProductionInitiation $p) => $this->decorateProjectForUser($p, $user));

        // Computed once per row here (not written onto the model yet) so
        // filterByDeliveryAndDue() judges every project — contributor or
        // bucket view alike — by the same effective delivery date. Whether
        // that computed value actually overwrites the raw column for
        // display purposes is decided per-branch below, to avoid changing
        // what the Admin/TL card currently shows (see note there).
        $deliveryDates = $candidates->mapWithKeys(fn (ProductionInitiation $p) => [$p->id => $this->projectDeliveryDate($p)]);
        $candidates = $this->filterByDeliveryAndDue($candidates, $deliveryDates, $deliveryFrom, $deliveryTo, $due);

        $optionLists = [
            'categories'  => $this->categoryOptions($candidates),
            'departments' => $this->departmentOptions(),
            'products'    => $this->productOptions(),
            'statuses'    => self::EXECUTION_STATUSES,
            'employees'   => $this->employeeOptions($user),
        ];

        if ($isContributorScopedView) {
            // Unchanged from before this rewrite: the contributor card
            // always shows the computed fallback delivery date, not just
            // the raw column.
            $candidates = $candidates->map(function (ProductionInitiation $p) use ($deliveryDates) {
                $p->project_delivery_date = $deliveryDates->get($p->id);
                return $p;
            });

            $paged = $this->paginateCollection($candidates, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => array_merge([
                    'view_type'   => 'contributor',
                    'projects'    => $paged->getCollection()->map(fn ($p) => $this->serializeProjectSummary($p))->values(),
                    'total'       => $paged->total(),
                    'pagination'  => $this->paginationMeta($paged),
                    'filters'     => $this->echoFilters($request),
                ], $optionLists),
            ]);
        }

        // TL / Admin view — bucket-based. Bucket membership depends on
        // per-viewer role/department/allocation logic (resolveBucketForUser)
        // that isn't a simple column value, so — same reasoning as the
        // delivery-date filter — it stays an in-memory classification pass,
        // but now runs over the already-SQL-filtered candidate set instead
        // of every visible project in the company.
        $buckets = ['allocation_pending' => collect(), 'allocated' => collect()];
        foreach ($candidates as $initiation) {
            $b = $this->resolveBucketForUser($initiation, $user);
            if ($b) {
                $buckets[$b]->push($initiation);
            }
        }

        $validBucket  = array_key_exists($bucket, $buckets) ? $bucket : 'allocation_pending';
        $bucketCounts = [
            'allocation_pending' => $buckets['allocation_pending']->count(),
            'allocated'          => $buckets['allocated']->count(),
        ];

        $paged = $this->paginateCollection($buckets[$validBucket]->values(), $page, $perPage);

        return response()->json([
            'success' => true,
            'data' => array_merge([
                'view_type'       => $isTlScopedView ? 'tl' : 'admin',
                'selected_bucket' => $validBucket,
                'bucket_counts'   => $bucketCounts,
                'projects'        => $paged->getCollection()->map(fn ($p) => $this->serializeProjectSummary($p))->values(),
                'total'           => $paged->total(),
                'pagination'      => $this->paginationMeta($paged),
                'filters'         => $this->echoFilters($request),
            ], $optionLists),
        ]);
    }

    /**
     * Applies every NEW filter that maps cleanly onto a real column or
     * relation as a genuine SQL WHERE clause on top of visibleProjectsQuery()
     * (which already scopes rows to what this user is allowed to see —
     * untouched, still the single source of truth for that). Existing
     * 'search'/'project_category' filters moved here from their old
     * in-memory Collection checks too, since both are equally
     * SQL-expressible.
     */
    private function buildFilteredProjectsQuery(User $user, array $filters): Builder
    {
        $query = $this->visibleProjectsQuery($user);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('contact_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%");
                    });
            });
        }

        $productId = trim((string) ($filters['product_id'] ?? ''));
        if ($productId !== '' && is_numeric($productId)) {
            $query->where('product_id', (int) $productId);
        }

        $departmentId = trim((string) ($filters['department_id'] ?? ''));
        if ($departmentId !== '' && is_numeric($departmentId)) {
            $query->where('department_id', (int) $departmentId);
        }

        $category = trim((string) ($filters['project_category'] ?? ''));
        if ($category !== '') {
            $query->whereHas('product.category', fn ($q) => $q->where('name', $category));
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && array_key_exists($status, self::EXECUTION_STATUSES)) {
            $query->where('project_execution_status', $status);
        }

        $employeeId = trim((string) ($filters['employee_id'] ?? ''));
        if ($employeeId !== '' && is_numeric($employeeId)) {
            $query->whereJsonContains('project_allocated_employee_user_ids', (int) $employeeId);
        }

        // Distinct from 'search' — a dedicated Customer-wise filter matching
        // only the company/client identity fields, not the product name too.
        $customer = trim((string) ($filters['customer'] ?? ''));
        if ($customer !== '') {
            $query->where(function ($q) use ($customer) {
                $q->where('company_name', 'like', "%{$customer}%")
                    ->orWhere('client_name', 'like', "%{$customer}%")
                    ->orWhereHas('lead', function ($lq) use ($customer) {
                        $lq->where('company_name', 'like', "%{$customer}%")
                            ->orWhere('contact_name', 'like', "%{$customer}%");
                    });
            });
        }

        $createdFrom = $this->parseFilterDate((string) ($filters['created_from'] ?? ''));
        if ($createdFrom) {
            $query->whereDate('created_at', '>=', $createdFrom->toDateString());
        }

        $createdTo = $this->parseFilterDate((string) ($filters['created_to'] ?? ''));
        if ($createdTo) {
            $query->whereDate('created_at', '<=', $createdTo->toDateString());
        }

        if (! empty($filters['my_projects'])) {
            $userId = $user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('initiated_by', $userId)
                    ->orWhereJsonContains('project_allocated_tl_user_ids', $userId)
                    ->orWhereJsonContains('project_allocated_employee_user_ids', $userId);
            });
        }

        return $query;
    }

    /**
     * Delivery-date range and Due Date/Deadline-wise (upcoming / overdue /
     * completed) filtering deliberately stay in-memory rather than becoming
     * a whereDate() on the raw project_delivery_date column: projectDeliveryDate()
     * falls back to a *computed* date (approval/allocation date + working
     * days) whenever that column is null, and the existing contributor-view
     * delivery filter (and the dashboard's own date filter) already judge
     * projects by that computed fallback — filtering the raw column only
     * here would silently exclude/include different projects than every
     * other delivery-date-aware view in this same controller.
     */
    private function filterByDeliveryAndDue(Collection $projects, Collection $deliveryDates, string $deliveryFrom, string $deliveryTo, string $due): Collection
    {
        $from = $this->parseFilterDate($deliveryFrom);
        $to   = $this->parseFilterDate($deliveryTo);
        $due  = in_array($due, ['upcoming', 'overdue', 'completed'], true) ? $due : '';

        if (! $from && ! $to && $due === '') {
            return $projects;
        }

        return $projects->filter(function (ProductionInitiation $p) use ($deliveryDates, $from, $to, $due) {
            $d = $deliveryDates->get($p->id);

            if ($from || $to) {
                if (! $d) return false;
                if ($from && $d->lt($from)) return false;
                if ($to && $d->gt($to)) return false;
            }

            if ($due !== '') {
                $isDelivered = $p->project_execution_status === 'delivered';
                $isOverdue   = $d && $d->isPast() && ! $isDelivered;

                return match ($due) {
                    'completed' => $isDelivered,
                    'overdue'   => $isOverdue,
                    'upcoming'  => ! $isDelivered && ! $isOverdue && $d && $d->isFuture(),
                    default     => true,
                };
            }

            return true;
        })->values();
    }

    private function paginateCollection(Collection $items, int $page, int $perPage): LengthAwarePaginator
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage ?: 15));
        $slice   = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($slice, $items->count(), $perPage, $page);
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ];
    }

    private function categoryOptions(Collection $projects): Collection
    {
        return $projects->map(fn ($p) => $p->product?->category?->name)
            ->filter()->unique()->sort()->values();
    }

    private function departmentOptions(): Collection
    {
        return Department::query()->orderBy('name')->get(['id', 'name']);
    }

    private function productOptions(): Collection
    {
        return Product::query()->orderBy('product_name')->get(['id', 'product_name as name']);
    }

    /**
     * Deliberately queried independently of buildFilteredProjectsQuery()'s
     * other filters (same as how the Product/Department option lists are
     * always the full set) so picking one filter doesn't shrink what's
     * offered in another — and only plucks the one JSON column needed
     * instead of hydrating full rows + relations.
     */
    private function employeeOptions(User $user): Collection
    {
        // ->get([...]) rather than ->pluck() — pluck() runs on the base
        // query builder and would return the raw unserialized column value
        // instead of respecting ProductionInitiation's `array` cast on this
        // column. setEagerLoads([]) strips visibleProjectsQuery()'s ->with()
        // relations back off again so this only ever selects the two
        // columns below, not the half-dozen relations that call normally
        // eager-loads for the full list response.
        $ids = $this->visibleProjectsQuery($user)
            ->setEagerLoads([])
            ->get(['id', 'project_allocated_employee_user_ids'])
            ->flatMap(fn ($p) => Arr::wrap($p->project_allocated_employee_user_ids))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $this->usersFromIds($ids);
    }

    private function echoFilters(Request $request): array
    {
        return [
            'search'           => (string) $request->query('search', ''),
            'product_id'       => (string) $request->query('product_id', ''),
            'department_id'    => (string) $request->query('department_id', ''),
            'project_category' => (string) $request->query('project_category', ''),
            'status'           => (string) $request->query('status', ''),
            'employee_id'      => (string) $request->query('employee_id', ''),
            'customer'         => (string) $request->query('customer', ''),
            'delivery_from'    => (string) $request->query('delivery_from', ''),
            'delivery_to'      => (string) $request->query('delivery_to', ''),
            'created_from'     => (string) $request->query('created_from', ''),
            'created_to'       => (string) $request->query('created_to', ''),
            'due'              => (string) $request->query('due', ''),
            'my_projects'      => $request->boolean('my_projects'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /mobile/projects/{id}
    // ─────────────────────────────────────────────────────────────────────────
    public function show(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);
        $productionInitiation = $this->decorateProjectForUser($productionInitiation, $user);

        $projectUpdateCounts = $productionInitiation->projectUpdates()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $selectedUpdateType = (string) $request->query('update_type', '');
        $selectedUpdateDate = (string) $request->query('update_date', '');

        $updatesQuery = $productionInitiation->projectUpdates()->with('createdBy:id,name');

        if (in_array($selectedUpdateType, ['production_update', 'meeting_update', 'weekly_update', 'timesheet'], true)) {
            $updatesQuery->where('type', $selectedUpdateType);
        } else {
            $selectedUpdateType = '';
        }
        if ($selectedUpdateDate !== '') {
            try {
                $updatesQuery->whereDate('created_at', Carbon::parse($selectedUpdateDate)->toDateString());
            } catch (\Throwable) {
                $selectedUpdateDate = '';
            }
        }

        $allocatedTlUsers    = $this->allocatedTlUsers($productionInitiation);
        $allocatedEmployees  = $this->allocatedEmployees(
            $productionInitiation,
            $this->shouldLimitToAssignedProjects($user) ? $user : null
        );
        $tlAllocationSummaries = $this->tlAllocationSummaries($productionInitiation);
        $projectDeliveryDate   = $this->projectDeliveryDate($productionInitiation);
        $updates               = $updatesQuery->latest('created_at')->get();

        // Testing tab data — only computed when the viewer is actually allowed
        // to see the tab, same short-circuit web's Blade does by wrapping the
        // whole panel in @if($canSeeTestingTab) rather than always querying it.
        $canSeeTestingTab = $this->canSeeTestingTab($productionInitiation, $user);
        $testingDetails = [];
        $testingBugs = [];
        $testingTlUsers = [];
        if ($canSeeTestingTab) {
            $testingDetails = $productionInitiation->testingDetails()
                ->with(['movedBy', 'testingTl'])
                ->latest()
                ->get()
                ->map(fn ($h) => $this->serializeHandoverDetail($h))
                ->values()
                ->all();

            $testingBugs = $productionInitiation->bugs()
                ->with('createdBy')
                ->latest()
                ->get()
                ->map(fn ($b) => $this->serializeBug($b))
                ->values()
                ->all();

            // Mirrors ProjectController::show()'s $testingTlUsers derivation
            // exactly: prefer users in a Testing-flavored department/role,
            // fall back to any TL-like/admin user if none exist.
            $testingTlUsers = User::with(['roles.department', 'branch'])
                ->where('is_active', true)
                ->get()
                ->filter(function ($u) {
                    $deptNames = strtolower($u->roles->map(fn ($r) => $r->department?->name)->filter()->implode(' '));
                    $roleNames = strtolower($u->roles->implode('display_name', ' ') . ' ' . $u->roles->implode('name', ' '));
                    return str_contains($deptNames, 'testing') || str_contains($roleNames, 'testing');
                })
                ->values();

            if ($testingTlUsers->isEmpty()) {
                $testingTlUsers = User::with(['roles.department', 'branch'])
                    ->where('is_active', true)
                    ->get()
                    ->filter(fn ($u) => $u->hasTlLikeRole() || $u->isSuperAdmin() || $u->isCompanyAdmin())
                    ->values();
            }

            $testingTlUsers = $testingTlUsers
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role_display' => $u->role_display_name])
                ->values()
                ->all();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'project'              => $this->serializeProjectDetail($productionInitiation, $projectDeliveryDate, $user),
                'can_allocate'         => $this->canAllocateTl($productionInitiation, $user),
                'can_allocate_employees' => $this->canAllocateEmployees($productionInitiation, $user),
                'can_manage_schedule'  => $this->canManageProjectSchedule($productionInitiation, $user),
                'is_tl_scoped_view'    => $this->shouldLimitToAssignedProjects($user),
                'is_assigned_tl'       => $this->isAssignedTlForProject($productionInitiation, $user),
                'allocated_tl_users'   => $allocatedTlUsers,
                'tl_allocation_summaries' => $this->serializeTlAllocationSummaries($tlAllocationSummaries),
                'allocated_employees'  => $allocatedEmployees,
                'available_tl_users'   => $this->canAllocateTl($productionInitiation, $user)
                    ? $this->availableTlUsers()
                    : [],
                'team_members'         => $this->canAllocateEmployees($productionInitiation, $user)
                    ? $this->availableTeamMembers($user)
                    : [],
                'project_updates'      => $updates->map(fn($u) => $this->serializeUpdate($u))->values(),
                'update_counts'        => $projectUpdateCounts,
                'selected_update_type' => $selectedUpdateType,
                'selected_update_date' => $selectedUpdateDate,
                'can_approve_content_calendar' => $this->isContentCalendarDept($productionInitiation)
                    && $user->belongsToDesigningDepartment()
                    && ! $productionInitiation->content_calendar_approved,
                'testing_details' => $testingDetails,
                'bugs'            => $testingBugs,
                'testing_tl_users' => $testingTlUsers,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /mobile/projects/{id}/allocate   (TL allocation)
    // ─────────────────────────────────────────────────────────────────────────
    public function allocate(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        if (! $this->canAllocateTl($productionInitiation, $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'tl_user_ids'   => ['required', 'array', 'min:1'],
            'tl_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedTlIds = $this->availableTlUsers()
            ->pluck('id')
            ->intersect(collect($validated['tl_user_ids'])->map(fn($id) => (int) $id))
            ->values()
            ->all();

        if ($selectedTlIds === []) {
            return response()->json(['success' => false, 'message' => 'Please select at least one valid TL user.'], 422);
        }

        $existingTlAllocations = $this->tlEmployeeAllocations($productionInitiation);
        $tlAllocations = collect($selectedTlIds)
            ->mapWithKeys(function (int $tlUserId) use ($existingTlAllocations) {
                $existing = $existingTlAllocations->get((string) $tlUserId, []);
                return [
                    (string) $tlUserId => $this->normalizeTlEmployeeAllocation([
                        'tl_user_id'       => $tlUserId,
                        'status'           => $existing['status'] ?? 'allocation_pending',
                        'allocated_at'     => $existing['allocated_at'] ?? null,
                        'allocated_by'     => $existing['allocated_by'] ?? null,
                        'employee_user_ids' => $existing['employee_user_ids'] ?? [],
                    ]),
                ];
            })
            ->all();

        $productionInitiation->update([
            'project_allocation_status'    => 'allocated',
            'project_allocated_at'         => Carbon::now(),
            'project_allocated_by'         => $user->id,
            'project_allocated_tl_user_ids' => $selectedTlIds,
            'tl_employee_allocations'      => $tlAllocations,
            ...$this->summarizeTlEmployeeAllocations($tlAllocations),
        ]);
        $this->syncProductionCountReportAllocation($productionInitiation->fresh(), $user->id);

        try {
            app(ProductionUpdateRecorder::class)->recordTlAllocation(
                $productionInitiation->fresh(),
                $selectedTlIds,
                $user
            );
        } catch (\Throwable $e) {
            Log::error('Failed to record TL allocation update: ' . $e->getMessage());
        }

        $this->notifications->notifyMany(
            User::query()->whereIn('id', $selectedTlIds)->where('is_active', true)->get(),
            'projects',
            'project_tl_allocated',
            [
                'title' => 'New Project Allocation',
                'message' => 'You were allocated as TL for ' . ($productionInitiation->product_name ?? 'a project') . '.',
                'detail' => $productionInitiation->company_name ?? $productionInitiation->lead?->company_name,
                'action_url' => route('projects.show', $productionInitiation),
                'priority' => 'medium',
                'request_type' => 'project_allocation',
                'request_id' => $productionInitiation->id,
                'actor_name' => $user->name,
                'status' => 'allocated',
            ]
        );

        try {
            $productionInitiation->loadMissing(['lead.branch', 'leadProduct', 'department']);
            $allocatedTls = User::whereIn('id', $selectedTlIds)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->get();
            $tlEmails = $allocatedTls->pluck('email')->filter()->unique()->values()->all();

            if (!empty($tlEmails)) {
                $allocatedBy = auth()->user();
                Mail::send('emails.tl_allocation', [
                    'initiation' => $productionInitiation,
                    'lead' => $productionInitiation->lead,
                    'leadProduct' => $productionInitiation->leadProduct,
                    'departmentName' => $productionInitiation->department?->name ?? 'Production',
                    'allocatedBy' => $allocatedBy,
                    'allocatedTls' => $allocatedTls,
                ], function ($message) use ($tlEmails, $productionInitiation) {
                    $message->to($tlEmails)
                        ->subject('New Project TL Allocation - Lead #' . $productionInitiation->lead_id . ' (' . ($productionInitiation->product_name ?: 'Product') . ')');
                });
            }
        } catch (\Throwable $e) {
            Log::error('Failed sending TL Allocation email: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Project allocated to TL successfully and notification email sent to allocated Team Lead(s).']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /mobile/projects/{id}/employee-allocate
    // ─────────────────────────────────────────────────────────────────────────
    public function allocateEmployees(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        if (! $this->canAllocateEmployees($productionInitiation, $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'employee_user_ids'   => ['required', 'array', 'min:1'],
            'employee_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedEmployeeIds = $this->availableTeamMembers($user)
            ->pluck('id')
            ->intersect(collect($validated['employee_user_ids'])->map(fn($id) => (int) $id))
            ->values()
            ->all();

        if ($selectedEmployeeIds === []) {
            return response()->json(['success' => false, 'message' => 'Please select at least one valid employee.'], 422);
        }

        $tlAllocations = $this->tlEmployeeAllocations($productionInitiation);
        $tlAllocations->put((string) $user->id, $this->normalizeTlEmployeeAllocation([
            'tl_user_id'       => $user->id,
            'status'           => 'allocated',
            'allocated_at'     => Carbon::now()->toDateTimeString(),
            'allocated_by'     => $user->id,
            'employee_user_ids' => $selectedEmployeeIds,
        ]));

        $productionInitiation->update([
            'tl_employee_allocations' => $tlAllocations->all(),
            ...$this->summarizeTlEmployeeAllocations($tlAllocations->all()),
        ]);
        $this->syncProductionCountReportAllocation($productionInitiation->fresh(), $user->id);

        try {
            app(ProductionUpdateRecorder::class)->recordTeamAllocation(
                $productionInitiation->fresh(),
                $selectedEmployeeIds,
                $user
            );
        } catch (\Throwable $e) {
            Log::error('Failed to record team allocation update: ' . $e->getMessage());
        }

        $this->notifications->notifyMany(
            User::query()->whereIn('id', $selectedEmployeeIds)->where('is_active', true)->get(),
            'projects',
            'project_employee_allocated',
            [
                'title' => 'New Project Allocation',
                'message' => 'You were assigned to ' . ($productionInitiation->product_name ?? 'a project') . '.',
                'detail' => $productionInitiation->company_name ?? $productionInitiation->lead?->company_name,
                'action_url' => route('projects.show', $productionInitiation),
                'priority' => 'medium',
                'request_type' => 'project_allocation',
                'request_id' => $productionInitiation->id,
                'actor_name' => $user->name,
                'status' => 'allocated',
            ]
        );

        try {
            $productionInitiation->loadMissing(['lead.branch', 'leadProduct', 'department']);
            $allocatedEmployees = User::whereIn('id', $selectedEmployeeIds)
                ->where('is_active', true)
                ->whereNotNull('email')
                ->get();
            $empEmails = $allocatedEmployees->pluck('email')->filter()->unique()->values()->all();

            if (!empty($empEmails)) {
                $allocatedBy = auth()->user();
                Mail::send('emails.team_allocation', [
                    'initiation' => $productionInitiation,
                    'lead' => $productionInitiation->lead,
                    'leadProduct' => $productionInitiation->leadProduct,
                    'departmentName' => $productionInitiation->department?->name ?? 'Production',
                    'allocatedBy' => $allocatedBy,
                    'allocatedEmployees' => $allocatedEmployees,
                ], function ($message) use ($empEmails, $productionInitiation) {
                    $message->to($empEmails)
                        ->subject('New Team Project Assignment - Lead #' . $productionInitiation->lead_id . ' (' . ($productionInitiation->product_name ?: 'Product') . ')');
                });
            }
        } catch (\Throwable $e) {
            Log::error('Failed sending Team Allocation email: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Employees allocated successfully and notification email sent to assigned team members.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /mobile/projects/{id}/schedule
    // ─────────────────────────────────────────────────────────────────────────
    public function updateSchedule(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        if (! $this->canManageProjectSchedule($productionInitiation, $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'project_delivery_date'    => ['nullable', 'date'],
            'project_execution_status' => ['required', 'in:ontrack,hold,delivered,lost'],
        ]);

        $oldDeliveryDate = $productionInitiation->project_delivery_date;
        $oldStatus       = $productionInitiation->project_execution_status;
        $newDeliveryDate = $validated['project_delivery_date'] ?: null;
        $newStatus       = $validated['project_execution_status'];

        $changes = [];
        $oldDateStr = $oldDeliveryDate ? Carbon::parse($oldDeliveryDate)->toDateString() : 'None';
        $newDateStr = $newDeliveryDate ? Carbon::parse($newDeliveryDate)->toDateString() : 'None';
        if ($oldDateStr !== $newDateStr) {
            $changes[] = "Delivery Date updated from <strong>{$oldDateStr}</strong> to <strong>{$newDateStr}</strong>";
        }
        if ($oldStatus !== $newStatus) {
            $changes[] = 'Project Status updated from <strong>' . ucfirst($oldStatus ?: 'None') . '</strong> to <strong>' . ucfirst($newStatus) . '</strong>';
        }

        $productionInitiation->update([
            'project_delivery_date'    => $newDeliveryDate,
            'project_execution_status' => $newStatus,
        ]);

        if (count($changes) > 0) {
            $productionInitiation->projectUpdates()->create([
                'type'       => 'schedule_history',
                'content'    => implode('<br>', $changes),
                'created_by' => $user->id,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Schedule updated successfully.']);
    }

    public function updateTimesheetStatus(Request $request, ProjectTimesheet $timesheet): JsonResponse
    {
        $user = auth()->user();
        if ($timesheet->user_id !== $user->id && ! $user->hasRole('super admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate(['status' => ['required', 'string', 'in:pending,completed']]);
        $timesheet->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet status updated successfully.',
            'data' => $this->serializeTimesheet($timesheet),
        ]);
    }

    public function storeQuickUpdate(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($this->canQuickAddProductionUpdate($user), 403);

        $validated = $request->validate([
            'production_initiation_id' => ['required', 'integer'],
            'type' => ['required', 'in:production_update,meeting_update,weekly_update'],
            'content' => ['required', 'string'],
        ]);

        $productionInitiation = $this->visibleProjectsQuery($user)
            ->whereKey($validated['production_initiation_id'])
            ->get()
            ->first(fn(ProductionInitiation $project) => $this->isDevelopmentProject($project));

        abort_unless($productionInitiation, 404);

        $update = $productionInitiation->projectUpdates()->create([
            'type' => $validated['type'],
            'content' => $validated['content'],
            'created_by' => $user->id,
        ]);
        $update->load('createdBy:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Project update added successfully.',
            'data' => $this->serializeUpdate($update),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /mobile/projects/{id}/updates
    // ─────────────────────────────────────────────────────────────────────────
    public function storeUpdate(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        $validated = $request->validate([
            'type'    => ['required', 'in:production_update,meeting_update,weekly_update'],
            'content' => ['required', 'string'],
        ]);

        $update = $productionInitiation->projectUpdates()->create([
            'type'       => $validated['type'],
            'content'    => $validated['content'],
            'created_by' => $user->id,
        ]);

        $update->load('createdBy:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Update added successfully.',
            'data'    => $this->serializeUpdate($update),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /mobile/projects/timesheets
    // ─────────────────────────────────────────────────────────────────────────
    public function timesheets(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        $visibility = app(\App\Services\DataVisibilityService::class);
        $mappedIds = $visibility->descendantUserIds($user);
        $directManagedIds = $user->managedUsers()->pluck('users.id');
        $allAccessibleIds = $mappedIds->merge($directManagedIds)->push($user->id)->unique()->filter()->map(fn($id) => (int)$id)->values()->all();

        $assignedProjects = $this->timesheetProjectsQuery($user)
            ->get()
            ->map(function (ProductionInitiation $p) {
                $p->timesheet_delivery_date = $this->projectDeliveryDate($p)?->toDateString();
                return $p;
            });

        $filters = [
            'filter_date'       => trim((string) $request->query('filter_date', '')),
            'filter_lead_id'     => trim((string) $request->query('filter_lead_id', '')),
            'filter_project_id' => trim((string) $request->query('filter_project_id', '')),
            'filter_status'     => trim((string) $request->query('filter_status', '')),
            'filter_user_id'    => trim((string) $request->query('filter_user_id', '')),
        ];

        $accessibleUserIds = $isAdminLike
            ? null
            : $allAccessibleIds;

        $timesheets = ProjectTimesheet::query()
            ->with(['project' => fn($q) => $q->with($this->projectRelations()), 'user'])
            ->when(!$isAdminLike, function ($q) use ($accessibleUserIds) {
                $q->whereIn('user_id', $accessibleUserIds);
            })
            ->when($filters['filter_date'] !== '', function ($q) use ($filters) {
                try {
                    $q->whereDate('timesheet_date', Carbon::parse($filters['filter_date'])->toDateString());
                } catch (\Throwable) {
                }
            })
            ->when($filters['filter_lead_id'] !== '', function ($q) use ($filters) {
                $q->whereHas('project', function ($sq) use ($filters) {
                    $sq->where('lead_id', (int) $filters['filter_lead_id']);
                });
            })
            ->when($filters['filter_project_id'] !== '', function ($q) use ($filters) {
                $q->where('production_initiation_id', (int) $filters['filter_project_id']);
            })
            ->when($filters['filter_status'] !== '', function ($q) use ($filters) {
                $q->where('status', $filters['filter_status']);
            })
            ->when($filters['filter_user_id'] !== '', function ($q) use ($filters, $isAdminLike, $accessibleUserIds) {
                $targetUserId = (int) $filters['filter_user_id'];
                if ($isAdminLike || in_array($targetUserId, $accessibleUserIds ?? [], true)) {
                    $q->where('user_id', $targetUserId);
                }
            })
            ->latest('created_at')
            ->latest('timesheet_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'assigned_projects' => $assignedProjects->map(fn($p) => [
                    'id'                      => $p->id,
                    'product_name'            => $p->product_name,
                    'company_name'            => $p->company_name,
                    'lead_id'                 => $p->lead_id,
                    'department'              => $p->department?->name,
                    'timesheet_delivery_date' => $p->timesheet_delivery_date,
                ])->values(),
                'timesheets' => $timesheets->map(fn($ts) => $this->serializeTimesheet($ts))->values(),
                'filters'    => $filters,
                'today'      => Carbon::today()->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /mobile/projects/timesheets
    // ─────────────────────────────────────────────────────────────────────────
    public function storeTimesheet(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'production_initiation_id' => ['required', 'integer'],
            'timesheet_date'           => ['required', 'date'],
            'status'                   => ['required', 'string', 'in:pending,completed'],
            'project_type'             => ['nullable', 'string', 'in:recurring,onetime'],
            'poster_count'             => ['nullable', 'integer', 'min:0', 'max:100000'],
            'video_count'              => ['nullable', 'integer', 'min:0', 'max:100000'],
            'committed_posters'        => ['nullable', 'integer', 'min:0', 'max:100000'],
            'committed_videos'         => ['nullable', 'integer', 'min:0', 'max:100000'],
            'waiting_posters'          => ['nullable', 'integer', 'min:0', 'max:100000'],
            'waiting_videos'           => ['nullable', 'integer', 'min:0', 'max:100000'],
            'day_closing_update'       => ['nullable', 'string'],
        ]);

        $project = $this->timesheetProjectsQuery($user)
            ->whereKey($validated['production_initiation_id'])
            ->firstOrFail();

        $timesheetDate = Carbon::parse($validated['timesheet_date'])->toDateString();
        $isOnetime = ($validated['project_type'] ?? '') === 'onetime';

        // Mirrors web: day closing update required unless the project is
        // Design/DM AND project_type is 'recurring'.
        if (trim((string) ($validated['day_closing_update'] ?? '')) === '') {
            return response()->json([
                'success' => false,
                'message' => 'The day closing update is required.',
                'errors'  => ['day_closing_update' => ['The day closing update is required.']],
            ], 422);
        }
        if (filled($validated['day_closing_update'] ?? null)) {
            $lines = collect(preg_split('/\R/', (string) $validated['day_closing_update']))
                ->map(fn($l) => trim($l))->filter();
            if ($lines->count() < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please add at least 1 task line in the day closing update.',
                    'errors'  => ['day_closing_update' => ['Please add at least 1 task line in the day closing update.']],
                ], 422);
            }
        }

        $payload = [
            'status'             => $validated['status'],
            'project_type'       => $validated['project_type'] ?? null,
            'poster_count'       => $isOnetime ? 0 : (int) ($validated['poster_count'] ?? 0),
            'video_count'        => $isOnetime ? 0 : (int) ($validated['video_count'] ?? 0),
            'committed_posters'  => $isOnetime ? 0 : (int) ($validated['committed_posters'] ?? 0),
            'committed_videos'   => $isOnetime ? 0 : (int) ($validated['committed_videos'] ?? 0),
            'waiting_posters'    => $isOnetime ? 0 : (int) ($validated['waiting_posters'] ?? 0),
            'waiting_videos'     => $isOnetime ? 0 : (int) ($validated['waiting_videos'] ?? 0),
            'day_closing_update' => $validated['day_closing_update'] ?? '',
        ];

        // Upsert — matches web (edit today's entry instead of hard-rejecting).
        $timesheet = ProjectTimesheet::where('production_initiation_id', $project->id)
            ->where('user_id', $user->id)
            ->whereDate('timesheet_date', $timesheetDate)
            ->first();

        if ($timesheet) {
            $timesheet->update($payload);
            $statusCode = 200;
        } else {
            $timesheet = ProjectTimesheet::create(array_merge($payload, [
                'company_id'               => $project->company_id,
                'production_initiation_id' => $project->id,
                'user_id'                  => $user->id,
                'timesheet_date'           => $timesheetDate,
                'project_delivery_date'    => $this->projectDeliveryDate($project)?->toDateString(),
            ]));
            $statusCode = 201;
        }

        \App\Models\ProjectUpdate::create([
            'production_initiation_id' => $project->id,
            'type'       => 'timesheet',
            'content'    => 'Timesheet Date: ' . Carbon::parse($timesheetDate)->format('d M Y')
                . "\nUpdate:\n" . ($payload['day_closing_update'] ?: '(none)'),
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet saved successfully.',
            'data'    => $this->serializeTimesheet($timesheet),
        ], $statusCode);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SERIALIZERS
    // ─────────────────────────────────────────────────────────────────────────

    private function serializeProjectSummary(ProductionInitiation $p): array
    {
        return [
            'id'                          => $p->id,
            'product_name'                => $p->product_name,
            'company_name'                => $p->company_name,
            'department'                  => $p->department?->name,
            'project_allocation_status'   => $p->project_allocation_status,
            'project_execution_status'    => $p->project_execution_status,
            'project_delivery_date'       => $p->project_delivery_date instanceof Carbon
                ? $p->project_delivery_date->toDateString()
                : ($p->project_delivery_date ? Carbon::parse($p->project_delivery_date)->toDateString() : null),
            'dashboard_date'              => $p->dashboard_date instanceof Carbon
                ? $p->dashboard_date->toDateString()
                : null,
            'project_value'               => $p->project_value ?? 0,
            'received_amount'             => $p->received_amount ?? 0,
            'balance_amount'              => $p->balance_amount ?? 0,
            'allocated_employee_count'    => $p->allocated_employee_count ?? 0,
            'project_allocated_at'        => $p->project_allocated_at,
            'project_allocated_tl_user_ids'   => Arr::wrap($p->project_allocated_tl_user_ids),
            'project_allocated_employee_user_ids' => Arr::wrap($p->project_allocated_employee_user_ids),
            'allocated_person_label'      => $p->allocated_person_label ?? null,
            'client_name'                 => $p->lead?->contact_name,
            'total_working_days'          => $p->total_working_days,
            'production_approval_status'  => $p->production_approval_status,
            'initiated_by'                => $p->initiatedBy?->name,
            'approved_by'                 => $p->productionApprovalReviewedBy?->name,
            'approved_on'                 => $p->production_approval_reviewed_at,
            // TL-scoped decoration
            'current_team_status'         => $p->current_team_status ?? null,
            'current_team_allocated_at'   => $p->current_team_allocated_at instanceof Carbon
                ? $p->current_team_allocated_at->toDateTimeString()
                : null,
        ];
    }

    private function serializeProjectDetail(ProductionInitiation $p, ?Carbon $deliveryDate, ?User $user = null): array
    {

        $deptName = strtolower(trim((string) ($p->department?->name ?? '')));

        // Mirrors web's Blade gate exactly: `auth()->user()->canViewBudgetApprovalDetails()
        // && $projectItem->lead_budget_amount` (pages/projects/show.blade.php).
        $canViewBudget = ($user?->canViewBudgetApprovalDetails() ?? false) && $p->lead_budget_amount;

        return array_merge($this->serializeProjectSummary($p), [
            'lead_id'             => $p->lead?->id,
            'lead_display_id'     => $p->lead ? 'LD-' . str_pad($p->lead->id, 4, '0', STR_PAD_LEFT) : null,
            'email'               => $p->lead?->email,
            'mobile'              => $p->lead?->mobile_number,
            'sales_person'        => $p->lead?->assignedTo?->name,
            'sales_email'         => $p->lead?->assignedTo?->email,
            'sales_person_role'   => $p->lead?->assignedTo?->designation,
            'initiated_on'        => $p->created_at,
            'allocated_by'        => $p->projectAllocatedBy?->name,
            'allocated_on'        => $p->project_allocated_at,
            'employee_allocated_by' => $p->employeeAllocatedBy?->name,
            'product_value'       => (float) ($p->leadProduct?->total_price ?? 0),
            'received_amount_detail' => (float) ($p->leadProduct?->payments?->sum('amount')
                ?? $p->leadProduct?->amount_paid ?? 0),
            'computed_delivery_date'  => $deliveryDate?->toDateString(),
            'ui_available'        => (bool) ($p->ui_available ?? false),
            'requirements'        => $p->project_requirements ?? $p->remarks ?? null,
            'attachment'          => $p->attachment_name ?? null,
            'attachment_url'      => $p->attachment_url,
            'ovp_approved_by'     => $p->reviewedBy?->name,
            'ovp_approved_on'     => $p->ovp_reviewed_at ?? null,
            // Dynamic OVP form fields collected at lead/product stage — raw
            // pass-through of the same JSON column web reads directly
            // ($productionInitiation->custom_form_data), so the Flutter side
            // renders whatever labels/values/file entries exist per project
            // rather than the backend guessing a fixed schema.
            'custom_form_data'    => $p->custom_form_data ?? [],
            'production_approval_remarks' => $p->production_approval_remarks ?? null,
            // Budget fields: null unless the viewing user passes
            // canViewBudgetApprovalDetails() AND an amount was actually
            // recorded — same double gate the web Blade uses.
            'lead_budget_amount'  => $canViewBudget ? $p->lead_budget_amount : null,
            'budget_amount_type'  => $canViewBudget ? $p->budget_amount_type : null,
            'content_calendar_sheet_url' => $p->content_calendar_sheet_url ?? null,
            'content_calendar_approved'  => (bool) ($p->content_calendar_approved ?? false),
            'content_calendar_remarks'   => $p->content_calendar_remarks ?? null,
            'is_content_calendar_dept'   => $this->isContentCalendarDept($p),
            // Web only lets non-Designing-department users edit the sheet
            // URL (Designing dept gets a read-only "Open Sheet" link and
            // approves instead) — see the @if(belongsToDesigningDepartment())
            // branch in show.blade.php around the cc-sheet-form.
            'can_edit_content_calendar_sheet' => ! ($user?->belongsToDesigningDepartment() ?? false),
            'can_see_testing_tab' => $this->canSeeTestingTab($p, $user),
        ]);
    }

    private function isContentCalendarDept(ProductionInitiation $productionInitiation): bool
    {
        return in_array(
            strtolower(trim((string) ($productionInitiation->department?->name ?? ''))),
            ['designing', 'digital marketing'],
            true
        );
    }

    /**
     * Mirrors show.blade.php's $canSeeTestingTab exactly: visible when either
     * the project's own department or the viewing user is development-flavored,
     * UNLESS the project sits in a Digital Marketing/Design department (those
     * never get a Testing tab regardless of who's viewing). This gates the
     * Development-side "Testing" tab on Project Detail — a separate concern
     * from the Testing Dashboard's own belongsToTestingDepartment() gate.
     */
    private function canSeeTestingTab(ProductionInitiation $p, ?User $user): bool
    {
        $deptName = strtolower(trim((string) ($p->department?->name ?? '')));

        $isDevUser = $user && (
            $user->belongsToDevelopmentDepartment()
            || $user->hasDevelopmentLikeRole()
            || (method_exists($user, 'isDevelopmentTeam') && $user->isDevelopmentTeam())
            || $user->isSuperAdmin()
            || $user->isCompanyAdmin()
        );

        $isDevDept = str_contains($deptName, 'development') || str_contains($deptName, 'dev') || str_contains($deptName, 'software') || str_contains($deptName, 'web') || str_contains($deptName, 'app');
        $isNonDevDept = str_contains($deptName, 'digital') || str_contains($deptName, 'marketing') || str_contains($deptName, 'design') || str_contains($deptName, 'dm');

        return ($isDevDept || $isDevUser) && ! $isNonDevDept;
    }

    private function serializeUpdate(ProjectUpdate $u): array
    {
        return [
            'id'         => $u->id,
            'type'       => $u->type,
            'content'    => $u->content,
            'created_by' => $u->createdBy?->name,
            'created_at' => $u->created_at?->toDateTimeString(),
        ];
    }

    private function serializeTimesheet(ProjectTimesheet $ts): array
    {
        return [
            'id'                    => $ts->id,
            'production_initiation_id' => $ts->production_initiation_id,
            'project_name'          => $ts->project?->product_name,
            'company_name'          => $ts->project?->company_name,
            'lead_id'               => $ts->project?->lead_id,
            'timesheet_date'        => $ts->timesheet_date?->toDateString(),
            'project_delivery_date' => $ts->project_delivery_date?->toDateString(),
            'status'                => $ts->status,
            'project_type'          => $ts->project_type,
            'poster_count'          => (int) $ts->poster_count,
            'video_count'           => (int) $ts->video_count,
            'committed_posters'     => (int) $ts->committed_posters,
            'committed_videos'      => (int) $ts->committed_videos,
            'waiting_posters'       => (int) $ts->waiting_posters,
            'waiting_videos'        => (int) $ts->waiting_videos,
            'day_closing_update'    => $ts->day_closing_update,
            'submitted_at'          => $ts->created_at?->toDateTimeString(),
            // Fixed: read the real status column instead of comparing dates.
            'is_completed'          => $ts->status === 'completed',
        ];
    }

    private function serializeTlAllocationSummaries(Collection $summaries): array
    {
        return $summaries->map(function ($s) {
            return [
                'tl_user_id'   => $s->tl_user_id,
                'tl_name'      => $s->tl_user?->name ?? null,
                'status'       => $s->status,
                'allocated_at' => $s->allocated_at?->toDateTimeString(),
                // ↓ resolve int ID → name, matching what web ProjectController does
                'allocated_by' => $this->userNameFromId($s->allocated_by ?? null),
                'employees'    => collect($s->employees)->map(fn($e) => [
                    'id'   => $e->id,
                    'name' => $e->name,
                ])->values(),
            ];
        })->values()->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  All private helpers copied verbatim from web ProjectController
    // ─────────────────────────────────────────────────────────────────────────

    private function designDepartmentIds(): array
    {
        return Department::where(function ($q) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%design%']);
        })->pluck('id')->toArray();
    }

    private function digitalMarketingDepartmentIds(): array
    {
        return Department::where(function ($q) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
              ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
        })->pluck('id')->toArray();
    }

    private function visibleProjectsQuery(User $user): Builder
    {
        $query = ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved']);

        if ($user->isDevelopmentProjectCoordinator()) {
            $devDeptIds = Department::whereRaw('LOWER(name) LIKE ?', ['%develop%'])->pluck('id')->toArray();
            $query->where(function ($q) use ($devDeptIds) {
                $q->whereIn('department_id', $devDeptIds)
                  ->orWhereHas('department', fn ($dq) => $dq->whereRaw('LOWER(name) LIKE ?', ['%develop%']));
            })->whereIn('project_allocation_status', ['allocation_pending', 'allocated']);
        } elseif ($user->isDesigningTl() || ($user->belongsToDesigningDepartment() && $user->hasTlLikeRole())) {
            $designDeptIds = $this->designDepartmentIds();
            $query->where(function ($q) use ($designDeptIds, $user) {
                $q->whereIn('department_id', $designDeptIds)
                  ->orWhereHas('department', fn ($dq) => $dq->whereRaw('LOWER(name) LIKE ?', ['%design%']))
                  ->orWhereJsonContains('project_allocated_tl_user_ids', $user->id);
            })->whereIn('project_allocation_status', ['allocation_pending', 'allocated']);
        } elseif ($user->isDigitalMarketingTl()) {
            $dmDeptIds = $this->digitalMarketingDepartmentIds();
            $query->where(function ($q) use ($dmDeptIds, $user) {
                $q->whereIn('department_id', $dmDeptIds)
                  ->orWhereHas('department', fn ($dq) => $dq->whereRaw('LOWER(name) LIKE ?', ['%digital%'])->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%']))
                  ->orWhereJsonContains('project_allocated_tl_user_ids', $user->id);
            })->whereIn('project_allocation_status', ['allocation_pending', 'allocated']);
        } elseif ($this->shouldLimitToAssignedProjects($user)) {
            $query->where('project_allocation_status', 'allocated')
                ->whereJsonContains('project_allocated_tl_user_ids', $user->id);
        } elseif ($this->shouldLimitToEmployeeProjects($user)) {
            $query->where('project_allocation_status', 'allocated')
                ->whereJsonContains('project_allocated_employee_user_ids', $user->id);
        } else {
            $query->whereIn('project_allocation_status', ['allocation_pending', 'allocated']);
        }

        return $query->latest('production_approval_reviewed_at');
    }

    private function timesheetProjectsQuery(User $user): Builder
    {
        if ($user->hasAdminLikeRole() || $user->isDevelopmentProjectCoordinator()) {
            return ProductionInitiation::query()
                ->with($this->projectRelations())
                ->whereIn('production_approval_status', ['approval', 'approved'])
                ->latest('production_approval_reviewed_at');
        }

        $visibility = app(\App\Services\DataVisibilityService::class);
        $mappedIds = $visibility->descendantUserIds($user);
        $directManagedIds = $user->managedUsers()->pluck('users.id');
        $allAccessibleIds = $mappedIds->merge($directManagedIds)->push($user->id)->unique()->filter()->map(fn($id) => (int)$id)->values()->all();

        return ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->where(function ($q) use ($user, $allAccessibleIds) {
                foreach ($allAccessibleIds as $uId) {
                    $q->orWhereJsonContains('project_allocated_employee_user_ids', $uId)
                      ->orWhereJsonContains('project_allocated_tl_user_ids', $uId);
                }

                $q->orWhereHas('testingDetails', function ($tq) use ($allAccessibleIds) {
                    $tq->whereIn('testing_tl_id', $allAccessibleIds)
                       ->orWhereIn('moved_by_user_id', $allAccessibleIds);
                })
                ->orWhereHas('productionTasks', function ($tq) use ($allAccessibleIds) {
                    $tq->whereIn('assigned_to', $allAccessibleIds);
                })
                ->orWhereHas('timesheets', function ($tq) use ($allAccessibleIds) {
                    $tq->whereIn('user_id', $allAccessibleIds);
                });
            })
            ->latest('production_approval_reviewed_at');
    }

    private function projectRelations(): array
    {
        return [
            'lead:id,company_name,contact_name,email,mobile_number,assigned_to,created_by',
            'lead.assignedTo:id,name,email,designation',
            'lead.createdBy:id,name,email,designation',
            'leadProduct:id,lead_id,product_id,total_price,amount_paid',
            'leadProduct.payments:id,lead_product_id,amount',
            'department:id,name',
            'product:id,product_category_id',
            'product.category:id,name',
            'initiatedBy:id,name',
            'reviewedBy:id,name',
            'productionApprovalReviewedBy:id,name',
            'projectAllocatedBy:id,name',
            'employeeAllocatedBy:id,name',
        ];
    }

    private function projectDeliveryDate(ProductionInitiation $project): ?Carbon
    {
        if ($project->project_delivery_date) {
            return $project->project_delivery_date instanceof Carbon
                ? $project->project_delivery_date
                : Carbon::parse($project->project_delivery_date);
        }

        $baseDate = $project->production_approval_reviewed_at
            ?: $project->project_allocated_at
            ?: $project->created_at;

        return $baseDate ? Carbon::parse($baseDate)->addDays((int) $project->total_working_days) : null;
    }

    private function dashboardProjectDate(ProductionInitiation $project, User $user): ?Carbon
    {
        if ($this->shouldLimitToEmployeeProjects($user)) {
            return $project->employee_allocated_at ? Carbon::parse($project->employee_allocated_at) : null;
        }
        if ($this->shouldLimitToAssignedProjects($user)) {
            return $project->current_team_allocated_at
                ? ($project->current_team_allocated_at instanceof Carbon
                    ? $project->current_team_allocated_at
                    : Carbon::parse($project->current_team_allocated_at))
                : null;
        }
        return $project->project_allocated_at
            ? Carbon::parse($project->project_allocated_at)
            : ($project->production_approval_reviewed_at ? Carbon::parse($project->production_approval_reviewed_at) : null);
    }

    private function filterDashboardProjects(Collection $projects, array $filters, User $user): Collection
    {
        $dateFrom          = $this->parseFilterDate($filters['date_from'] ?? '');
        $dateTo            = $this->parseFilterDate($filters['date_to'] ?? '')?->endOfDay();
        $selectedProjectId = (int) ($filters['project_id'] ?? 0);
        $selectedMemberId  = (int) ($filters['team_member_id'] ?? 0);
        $selectedAllocationStatus = trim((string) ($filters['allocation_status'] ?? ''));

        return $projects->filter(function (ProductionInitiation $project) use ($dateFrom, $dateTo, $selectedProjectId, $selectedMemberId, $selectedAllocationStatus, $user) {
            if ($selectedProjectId > 0 && (int) $project->id !== $selectedProjectId) return false;

            if ($dateFrom || $dateTo) {
                $d = $project->dashboard_date;
                if (! $d) return false;
                if ($dateFrom && $d->lt($dateFrom)) return false;
                if ($dateTo && $d->gt($dateTo)) return false;
            }

            if ($selectedMemberId > 0 && $this->shouldAllowDashboardUserFilter($user)) {
                $empIds = collect(Arr::wrap($project->project_allocated_employee_user_ids))->map(fn($id) => (int) $id);
                if (! $empIds->contains($selectedMemberId)) return false;
            }

            if ($selectedAllocationStatus !== '') {
                if ($project->project_allocation_status !== $selectedAllocationStatus) {
                    return false;
                }
            }

            return true;
        })->values();
    }


    private function parseFilterDate(string $value): ?Carbon
    {
        if (trim($value) === '') return null;
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dashboardTeamMembers(User $user, Collection $projects): Collection
    {
        if (! $this->shouldAllowDashboardUserFilter($user)) return collect();

        $empIds = $projects
            ->flatMap(fn($p) => Arr::wrap($p->project_allocated_employee_user_ids))
            ->map(fn($id) => (int) $id)->filter()->unique()->values()->all();

        return $this->usersFromIds($empIds);
    }

    private function shouldAllowDashboardUserFilter(User $user): bool
    {
        return $this->shouldLimitToAssignedProjects($user);
    }

    // Mirrors web's ProjectController::dashboardTimesheetSummary() exactly —
    // this used to return a different shape entirely (aggregate stats:
    // entries_count/submitted_today/contributors_count/pending_delivery_count)
    // instead of the per-project rows (project_name, company_name,
    // entries_count, total_posters, total_videos) the "Timesheet & Activity
    // Summary" table actually needs. That table didn't exist on mobile yet,
    // so nothing was consuming the old shape — safe to replace outright.
    private function dashboardTimesheetSummary(User $user, Collection $projects, array $filters): array
    {
        $projectIds = $projects->pluck('id')->map(fn($id) => (int) $id)->filter()->values();

        if ($projectIds->isEmpty()) {
            return [];
        }

        $query = ProjectTimesheet::query()
            ->with(['user:id,name', 'project' => fn($b) => $b->with($this->projectRelations())])
            ->whereIn('production_initiation_id', $projectIds->all());

        if ($this->shouldLimitToEmployeeProjects($user)) $query->where('user_id', $user->id);

        $dateFrom = $this->parseFilterDate($filters['date_from'] ?? '');
        $dateTo   = $this->parseFilterDate($filters['date_to'] ?? '')?->endOfDay();

        if ($dateFrom) $query->whereDate('timesheet_date', '>=', $dateFrom->toDateString());
        if ($dateTo)   $query->whereDate('timesheet_date', '<=', $dateTo->toDateString());

        $selectedMemberId = (int) ($filters['team_member_id'] ?? 0);
        if ($selectedMemberId > 0 && $this->shouldAllowDashboardUserFilter($user)) {
            $query->where('user_id', $selectedMemberId);
        }

        $entries = $query->latest('timesheet_date')->latest('created_at')->get();

        return $entries->groupBy('production_initiation_id')->map(function ($projectEntries) {
            $first = $projectEntries->first();
            $project = $first->project;
            return [
                'project_name' => $project?->product_name ?: 'Unknown Project',
                'company_name' => $project?->company_name ?: ($project?->lead?->company_name ?: 'No Company'),
                'entries_count' => $projectEntries->count(),
                'total_posters' => (int) $projectEntries->sum('poster_count'),
                'total_videos'  => (int) $projectEntries->sum('video_count'),
            ];
        })->values()->all();
    }

    private function currentMonthDeliveryProjects(Collection $projects): Collection
    {
        $start = Carbon::today()->startOfMonth();
        $end   = Carbon::today()->endOfMonth();

        return $projects->filter(fn($p) => $p->project_delivery_date && $p->project_delivery_date->between($start, $end))
            ->map(function (ProductionInitiation $p) {
                $empNames = $this->usersFromIds(Arr::wrap($p->project_allocated_employee_user_ids))->pluck('name')->filter()->values();
                $tlNames  = $this->usersFromIds(Arr::wrap($p->project_allocated_tl_user_ids))->pluck('name')->filter()->values();
                $p->allocated_person_label = $empNames->isNotEmpty()
                    ? $empNames->implode(', ')
                    : ($tlNames->isNotEmpty() ? $tlNames->implode(', ') : 'Not allocated');
                return $p;
            })
            ->sortBy('project_delivery_date')->values();
    }

    private function decorateProjectForUser(ProductionInitiation $p, User $user): ProductionInitiation
    {
        if (! $this->shouldLimitToAssignedProjects($user)) return $p;

        $allocation = $this->tlEmployeeAllocationForUser($p, $user);
        $p->current_team_status          = $allocation['status'];
        $p->current_team_allocated_at    = $allocation['allocated_at'] ? Carbon::parse($allocation['allocated_at']) : null;
        $p->current_team_allocated_by    = $allocation['allocated_by'];
        $p->current_team_employee_user_ids = $allocation['employee_user_ids'];

        return $p;
    }

    private function ensureProjectIsVisibleToUser(ProductionInitiation $p, User $user): void
    {
        $p->loadMissing($this->projectRelations());

        if ($user->isDevelopmentProjectCoordinator()) {
            abort_unless($this->isDevelopmentProject($p), 403, 'Development Project Coordinator can only view Development Department projects.');
        }

        if ($user->isDesigningTl() || ($user->belongsToDesigningDepartment() && $user->hasTlLikeRole())) {
            $isDesignProject = ($p->department_id && in_array((int) $p->department_id, $this->designDepartmentIds(), true))
                || Str::contains(strtolower((string) $p->department?->name), 'design')
                || $this->isAssignedTlForProject($p, $user);

            abort_unless($isDesignProject, 403, 'Design Team Leader can only view Design Department projects.');
            abort_unless($this->resolveBucketForUser($p, $user) !== null, 404);

            return;
        }

        if ($user->isDigitalMarketingTl()) {
            $isDmProject = ($p->department_id && in_array((int) $p->department_id, $this->digitalMarketingDepartmentIds(), true))
                || Str::contains(strtolower((string) $p->department?->name), ['digital', 'marketing', 'dm'])
                || $this->isAssignedTlForProject($p, $user);

            abort_unless($isDmProject, 403, 'Digital Marketing Team Leader can only view Digital Marketing Department projects.');
            abort_unless($this->resolveBucketForUser($p, $user) !== null, 404);

            return;
        }

        abort_unless($this->resolveBucketForUser($p, $user) !== null, 404);
        if ($this->shouldLimitToAssignedProjects($user)) abort_unless($this->isAssignedTlForProject($p, $user), 403);
        if ($this->shouldLimitToEmployeeProjects($user))  abort_unless($this->isAssignedEmployeeForProject($p, $user), 403);
    }

    private function tlEmployeeAllocations(ProductionInitiation $p): Collection
    {
        return collect(Arr::wrap($p->tl_employee_allocations))
            ->mapWithKeys(function ($allocation, $tlUserId) {
                if (is_array($allocation) && array_key_exists('tl_user_id', $allocation)) {
                    $tlUserId = (int) $allocation['tl_user_id'];
                } else {
                    $tlUserId = (int) $tlUserId;
                    $allocation = is_array($allocation) ? $allocation : [];
                    $allocation['tl_user_id'] = $tlUserId;
                }
                if ($tlUserId <= 0) return [];
                return [(string) $tlUserId => $this->normalizeTlEmployeeAllocation($allocation)];
            });
    }

    private function tlEmployeeAllocationForUser(ProductionInitiation $p, User $user): array
    {
        return $this->normalizeTlEmployeeAllocation(
            $this->tlEmployeeAllocations($p)->get((string) $user->id, [
                'tl_user_id' => $user->id,
                'status' => 'allocation_pending',
                'allocated_at' => null,
                'allocated_by' => null,
                'employee_user_ids' => [],
            ])
        );
    }

    private function tlEmployeeAllocationStatusForUser(ProductionInitiation $p, User $user): string
    {
        return (string) ($this->tlEmployeeAllocationForUser($p, $user)['status'] ?? 'allocation_pending');
    }

    private function normalizeTlEmployeeAllocation(array $allocation): array
    {
        $empIds = collect(Arr::wrap($allocation['employee_user_ids'] ?? []))->map(fn($id) => (int) $id)->filter()->unique()->values()->all();
        return [
            'tl_user_id'       => (int) ($allocation['tl_user_id'] ?? 0),
            'status'           => in_array($allocation['status'] ?? null, ['allocated', 'allocation_pending'], true)
                ? $allocation['status']
                : ($empIds !== [] ? 'allocated' : 'allocation_pending'),
            'allocated_at'     => ! empty($allocation['allocated_at']) ? Carbon::parse($allocation['allocated_at'])->toDateTimeString() : null,
            'allocated_by'     => ! empty($allocation['allocated_by']) ? (int) $allocation['allocated_by'] : null,
            'employee_user_ids' => $empIds,
        ];
    }

    private function summarizeTlEmployeeAllocations(array $tlAllocations): array
    {
        $normalized = collect($tlAllocations)
            ->map(fn($a) => $this->normalizeTlEmployeeAllocation((array) $a))
            ->filter(fn($a) => $a['tl_user_id'] > 0)->values();

        $allEmpIds     = $normalized->flatMap(fn($a) => $a['employee_user_ids'])->map(fn($id) => (int) $id)->filter()->unique()->values()->all();
        $latestAlloc   = $normalized->filter(fn($a) => ! empty($a['allocated_at']))->sortByDesc('allocated_at')->first();
        $allTlAllocated = $normalized->isNotEmpty() && $normalized->every(fn($a) => $a['status'] === 'allocated');

        return [
            'employee_allocation_status'          => $allTlAllocated ? 'allocated' : 'allocation_pending',
            'employee_allocated_at'               => $latestAlloc['allocated_at'] ?? null,
            'employee_allocated_by'               => $latestAlloc['allocated_by'] ?? null,
            'project_allocated_employee_user_ids' => $allEmpIds === [] ? null : $allEmpIds,
        ];
    }

    private function syncProductionCountReportAllocation(?ProductionInitiation $project, ?int $allocatedBy): void
    {
        if (! $project) {
            return;
        }

        $countReport = ProductionCountReport::query()
            ->where('production_initiation_id', $project->id)
            ->first();

        if (! $countReport) {
            return;
        }

        $countReport->update([
            'allocated_team_user_ids' => Arr::wrap($project->project_allocated_tl_user_ids) ?: null,
            'allocated_user_ids' => Arr::wrap($project->project_allocated_employee_user_ids) ?: null,
            'allocated_by' => $allocatedBy,
            'allocated_at' => Carbon::now(),
            'status' => $project->employee_allocation_status === 'allocated' ? 'employee_allocated' : 'team_allocated',
        ]);
    }

    private function resolveBucketForUser(ProductionInitiation $p, User $user): ?string
    {
        if ($user->isDesigningTl() || ($user->belongsToDesigningDepartment() && $user->hasTlLikeRole())) {
            if (strtolower(trim((string) $p->project_allocation_status)) === 'allocation_pending') {
                return 'allocation_pending';
            }

            if ($this->isAssignedTlForProject($p, $user)) {
                return $this->resolveBucket((string) ($p->current_team_status ?? $this->tlEmployeeAllocationStatusForUser($p, $user)));
            }

            return $this->resolveBucket((string) $p->project_allocation_status);
        }

        if ($user->isDigitalMarketingTl()) {
            if (strtolower(trim((string) $p->project_allocation_status)) === 'allocation_pending') {
                return 'allocation_pending';
            }

            if ($this->isAssignedTlForProject($p, $user)) {
                return $this->resolveBucket((string) ($p->current_team_status ?? $this->tlEmployeeAllocationStatusForUser($p, $user)));
            }

            return $this->resolveBucket((string) $p->project_allocation_status);
        }

        if ($this->shouldLimitToAssignedProjects($user)) {
            return $this->resolveBucket((string) ($p->current_team_status ?? $this->tlEmployeeAllocationStatusForUser($p, $user)));
        }
        if ($this->shouldLimitToEmployeeProjects($user)) {
            return $this->isAssignedEmployeeForProject($p, $user) ? 'allocated' : null;
        }
        return $this->resolveBucket((string) $p->project_allocation_status);
    }

    private function resolveBucket(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'allocation_pending', 'pending' => 'allocation_pending',
            'allocated'                     => 'allocated',
            default                         => null,
        };
    }

    private function canAllocateTl(ProductionInitiation $p, User $user): bool
    {
        $isApproved = in_array(strtolower(trim((string) $p->production_approval_status)), ['approval', 'approved'], true);
        if (! $isApproved) {
            return false;
        }

        if ($user->hasAdminLikeRole() || $this->hasProjectCoordinatorRole($user) || $user->isDevelopmentProjectCoordinator()) {
            return true;
        }

        if ($this->isAssignedTlForProject($p, $user)) {
            return true;
        }

        if ($user->isDesigningTl() || ($user->belongsToDesigningDepartment() && $user->hasTlLikeRole())) {
            $isDesignProject = ($p->department_id && in_array((int) $p->department_id, $this->designDepartmentIds(), true))
                || Str::contains(strtolower((string) $p->department?->name), 'design');

            if ($isDesignProject) {
                return true;
            }
        }

        if ($user->isDigitalMarketingTl()) {
            $isDmProject = ($p->department_id && in_array((int) $p->department_id, $this->digitalMarketingDepartmentIds(), true))
                || Str::contains(strtolower((string) $p->department?->name), ['digital', 'marketing', 'dm']);

            if ($isDmProject) {
                return true;
            }
        }

        return false;
    }

    private function canAllocateEmployees(ProductionInitiation $p, User $user): bool
    {
        if (strtolower(trim((string) $p->project_allocation_status)) !== 'allocated') {
            return false;
        }

        if ($user->hasAdminLikeRole()) {
            return true;
        }

        if ($this->isAssignedTlForProject($p, $user)) {
            return true;
        }

        if ($user->isDesigningTl() || ($user->belongsToDesigningDepartment() && $user->hasTlLikeRole())) {
            $isDesignProject = ($p->department_id && in_array((int) $p->department_id, $this->designDepartmentIds(), true))
                || Str::contains(strtolower((string) $p->department?->name), 'design');

            if ($isDesignProject) {
                return true;
            }
        }

        if ($user->isDigitalMarketingTl()) {
            $isDmProject = ($p->department_id && in_array((int) $p->department_id, $this->digitalMarketingDepartmentIds(), true))
                || Str::contains(strtolower((string) $p->department?->name), ['digital', 'marketing', 'dm']);

            if ($isDmProject) {
                return true;
            }
        }

        return false;
    }

    private function canManageProjectSchedule(ProductionInitiation $p, User $user): bool
    {
        return $user->hasAdminLikeRole()
            || $this->hasProjectCoordinatorRole($user)
            || $this->isUserTl($user)
            || $user->hasTlLikeRole();
    }

    private function shouldLimitToAssignedProjects(User $user): bool
    {
        return ! $user->hasAdminLikeRole() && $this->isUserTl($user);
    }

    private function shouldLimitToEmployeeProjects(User $user): bool
    {
        return ! $user->hasAdminLikeRole() && ! $this->isUserTl($user);
    }

    private function canQuickAddProductionUpdate(User $user): bool
    {
        return $this->shouldLimitToEmployeeProjects($user)
            && $user->resolvedRoles(withDepartment: true)->contains(fn($role) => $this->roleKey((string) ($role->department?->name ?? '')) === 'development');
    }

    private function hasProjectCoordinatorRole(User $user): bool
    {
        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
            $keys = [$this->roleKey((string) $role->name), $this->roleKey((string) ($role->display_name ?? ''))];
            return collect($keys)->intersect(['project_coordinator', 'project_coordination', 'pc', 'development_project_coordinator'])->isNotEmpty();
        });
    }

    private function isUserTl(User $user): bool
    {
        return $user->resolvedRoles(withDepartment: true)->contains(
            fn($role) =>
            $this->isTlRole((string) $role->name) || $this->isTlRole((string) ($role->display_name ?? ''))
        );
    }

    private function isAssignedTlForProject(ProductionInitiation $p, User $user): bool
    {
        return collect(Arr::wrap($p->project_allocated_tl_user_ids))->map(fn($id) => (int) $id)->contains((int) $user->id);
    }

    private function isAssignedEmployeeForProject(ProductionInitiation $p, User $user): bool
    {
        return collect(Arr::wrap($p->project_allocated_employee_user_ids))->map(fn($id) => (int) $id)->contains((int) $user->id);
    }

    private function availableTlUsers(): Collection
    {
        return User::query()->where('is_active', true)->with(['roles.department'])->get()
            ->filter(fn($u) => $u->resolvedRoles(withDepartment: true)->contains(
                fn($role) =>
                $this->isTlRole((string) $role->name) || $this->isTlRole((string) ($role->display_name ?? ''))
            ))
            ->map(fn($u) => $this->mapUserSummary($u))->sortBy('name')->values();
    }

    private function availableTeamMembers(User $user): Collection
    {
        $managedMembers = $user->managedUsers()->where('users.is_active', true)->with(['roles.department'])->get()
            ->map(fn($m) => $this->mapUserSummary($m));

        if ($user->belongsToDesigningDepartment()) {
            $designDeptIds = $this->designDepartmentIds();
            $deptMembers = User::where('users.is_active', true)
                ->whereHas('roles.department', fn ($dq) => $dq->whereIn('id', $designDeptIds)->orWhereRaw('LOWER(name) LIKE ?', ['%design%']))
                ->with(['roles.department'])
                ->get()
                ->map(fn ($m) => $this->mapUserSummary($m));

            return $managedMembers
                ->concat($deptMembers)
                ->push($this->mapUserSummary($user))
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        if ($user->belongsToDigitalMarketingDepartment()) {
            $dmDeptIds = $this->digitalMarketingDepartmentIds();
            $deptMembers = User::where('users.is_active', true)
                ->whereHas('roles.department', fn ($dq) => $dq->whereIn('id', $dmDeptIds)->orWhereRaw('LOWER(name) LIKE ?', ['%digital%'])->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%']))
                ->with(['roles.department'])
                ->get()
                ->map(fn ($m) => $this->mapUserSummary($m));

            return $managedMembers
                ->concat($deptMembers)
                ->push($this->mapUserSummary($user))
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        return $managedMembers
            ->push($this->mapUserSummary($user))
            ->unique('id')->sortBy('name')->values();
    }

    private function allocatedTlUsers(ProductionInitiation $p): Collection
    {
        return $this->usersFromIds(Arr::wrap($p->project_allocated_tl_user_ids));
    }

    private function tlAllocationSummaries(ProductionInitiation $p): Collection
    {
        $allocatedTlUsers = $this->allocatedTlUsers($p)->keyBy('id');
        $tlAllocations    = $this->tlEmployeeAllocations($p);

        return collect(Arr::wrap($p->project_allocated_tl_user_ids))
            ->map(fn($id) => (int) $id)->filter()->unique()
            ->map(function (int $tlUserId) use ($allocatedTlUsers, $tlAllocations) {
                $allocation = $this->normalizeTlEmployeeAllocation($tlAllocations->get((string) $tlUserId, ['tl_user_id' => $tlUserId]));
                return (object) [
                    'tl_user_id'     => $tlUserId,
                    'tl_user'        => $allocatedTlUsers->get($tlUserId),
                    'status'         => (string) ($allocation['status'] ?? 'allocation_pending'),
                    'allocated_at'   => ! empty($allocation['allocated_at']) ? Carbon::parse($allocation['allocated_at']) : null,
                    'allocated_by_name' => $this->userNameFromId($allocation['allocated_by'] ?? null) ?: 'Pending',
                    'employees'      => $this->usersFromIds($allocation['employee_user_ids']),
                ];
            })->values();
    }

    private function allocatedEmployees(ProductionInitiation $p, ?User $tlUser = null): Collection
    {
        $empIds = $tlUser
            ? ($this->tlEmployeeAllocations($p)->get((string) $tlUser->id)['employee_user_ids'] ?? [])
            : Arr::wrap($p->project_allocated_employee_user_ids);
        return $this->usersFromIds(Arr::wrap($empIds));
    }

    private function usersFromIds(array $ids): Collection
    {
        $selectedIds = collect($ids)->map(fn($id) => (int) $id)->filter()->values();
        if ($selectedIds->isEmpty()) return collect();
        return User::query()->whereIn('id', $selectedIds->all())->where('is_active', true)
            ->with(['roles.department'])->get()
            ->map(fn($u) => $this->mapUserSummary($u))->sortBy('name')->values();
    }

    private function userNameFromId(?int $userId): ?string
    {
        static $cache = [];
        $userId = $userId ? (int) $userId : 0;
        if ($userId <= 0) return null;
        if (! array_key_exists($userId, $cache)) {
            $cache[$userId] = User::query()->whereKey($userId)->value('name');
        }
        return $cache[$userId];
    }

    private function mapUserSummary(User $user): object
    {
        $roles = $user->resolvedRoles(withDepartment: true);
        $departments = $roles->map(fn($r) => $r->department?->name)->filter()->unique()->values();
        return (object) [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'role_names'   => $roles->map(fn($r) => $r->display_name ?: Str::of((string) $r->name)->afterLast('__')->replace('_', ' ')->title()->value())->unique()->values()->all(),
            'departments'  => $departments->all(),
            'department_label' => $departments->isNotEmpty() ? $departments->implode(', ') : 'All Departments',
        ];
    }

    private function roleKey(string $value): string
    {
        $value = Str::contains($value, '__') ? Str::afterLast($value, '__') : $value;
        return Str::of($value)->lower()->replace('&', 'and')->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')->replaceMatches('/_+/', '_')->trim('_')->value();
    }

    private function isTlRole(string $value): bool
    {
        $key = $this->roleKey($value);
        if (in_array($key, self::TL_ROLE_KEYS, true)) return true;
        return Str::contains($key, ['tl', 'team_lead', 'teamleader', 'team_leader', 'manager', 'lead']);
    }

    public function myAccounts(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless(
            $user->belongsToDesigningDepartment() || $user->belongsToDigitalMarketingDepartment(),
            403
        );

        $projectQuery = ProductionInitiation::query()
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->where('project_allocation_status', 'allocated');

        if ($user->isDesigningTl() || ($user->belongsToDesigningDepartment() && $user->hasTlLikeRole())) {
            $designDeptIds = $this->designDepartmentIds();
            $projectQuery->where(function ($q) use ($designDeptIds, $user) {
                $q->whereIn('department_id', $designDeptIds)
                    ->orWhereHas('department', fn ($dq) => $dq->whereRaw('LOWER(name) LIKE ?', ['%design%']))
                    ->orWhereJsonContains('project_allocated_tl_user_ids', $user->id);
            });
        } elseif ($this->shouldLimitToAssignedProjects($user)) {
            $projectQuery->whereJsonContains('project_allocated_tl_user_ids', $user->id);
        } elseif ($this->shouldLimitToEmployeeProjects($user)) {
            $projectQuery->whereJsonContains('project_allocated_employee_user_ids', $user->id);
        } else {
            $deptIds = Department::where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%design%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%digital%']);
            })->pluck('id')->toArray();
            $projectQuery->whereIn('department_id', $deptIds);
        }

        $allocatedProjects = $projectQuery->get();
        $leadIds = $allocatedProjects->pluck('lead_id')->filter()->unique();

        $leads = Lead::whereIn('id', $leadIds)
            ->orderBy('company_name')
            ->get()
            ->map(function ($lead) {
                $projects = ProductionInitiation::where('lead_id', $lead->id)->get();

                $totalPosters = 0;
                $totalVideos = 0;
                foreach ($projects as $project) {
                    if (is_array($project->custom_form_data)) {
                        foreach ($project->custom_form_data as $field) {
                            $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                            if ($label === 'number of posters' || $label === 'number of poster') {
                                $totalPosters += (int) ($field['value'] ?? 0);
                            } elseif ($label === 'number of videos' || $label === 'number of video') {
                                $totalVideos += (int) ($field['value'] ?? 0);
                            }
                        }
                    }
                }

                $completedPosters = (int) ProjectTimesheet::whereIn('production_initiation_id', $projects->pluck('id'))->sum('poster_count');
                $completedVideos = (int) ProjectTimesheet::whereIn('production_initiation_id', $projects->pluck('id'))->sum('video_count');

                return [
                    'id' => $lead->id,
                    'company_name' => $lead->company_name ?: 'No Company Name',
                    'contact_name' => $lead->contact_name,
                    'mobile_number' => $lead->mobile_number,
                    'email' => $lead->email,
                    'renewals_count' => LeadProduct::where('lead_id', $lead->id)
                        ->whereHas('product', fn($q) => $q->where('count_wise_report', true))
                        ->count(),
                    'total_posters' => $totalPosters,
                    'completed_posters' => $completedPosters,
                    'pending_posters' => max(0, $totalPosters - $completedPosters),
                    'total_videos' => $totalVideos,
                    'completed_videos' => $completedVideos,
                    'pending_videos' => max(0, $totalVideos - $completedVideos),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'leads' => $leads,
                'stats' => [
                    'total_accounts' => $leads->count(),
                    'total_renewals' => $leads->sum('renewals_count'),
                    'total_posters' => $leads->sum('total_posters'),
                    'completed_posters' => $leads->sum('completed_posters'),
                    'pending_posters' => $leads->sum('pending_posters'),
                    'total_videos' => $leads->sum('total_videos'),
                    'completed_videos' => $leads->sum('completed_videos'),
                    'pending_videos' => $leads->sum('pending_videos'),
                ],
            ],
        ]);
    }

    public function showMyAccount(Lead $lead): JsonResponse
    {
        $user = auth()->user();
        abort_unless(
            $user->belongsToDesigningDepartment() || $user->belongsToDigitalMarketingDepartment(),
            403
        );

        $renewals = LeadProduct::where('lead_id', $lead->id)
            ->whereHas('product', fn($q) => $q->where('count_wise_report', true))
            ->with(['product'])
            ->get();

        $projects = ProductionInitiation::where('lead_id', $lead->id)
            ->whereIn('lead_product_id', $renewals->pluck('id'))
            ->with(['department', 'timesheets', 'leadProduct.product'])
            ->get()
            ->map(function ($project) {
                $startDateCustom = null;
                $endDateCustom = null;
                if (is_array($project->custom_form_data)) {
                    foreach ($project->custom_form_data as $field) {
                        $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                        $value = trim((string) ($field['value'] ?? ''));
                        if ($label === 'start date') $startDateCustom = $value;
                        elseif ($label === 'end date') $endDateCustom = $value;
                    }
                }

                $start = $startDateCustom ? Carbon::parse($startDateCustom)->startOfDay() : ($project->production_approval_reviewed_at ?: ($project->project_allocated_at ?: $project->created_at));
                $project->start_date = $start ? Carbon::parse($start)->startOfDay() : null;

                $end = $endDateCustom ? Carbon::parse($endDateCustom)->startOfDay() : $this->projectDeliveryDate($project);
                $project->project_delivery_date = $end ? Carbon::parse($end)->startOfDay() : null;

                $project->is_overdue = $project->project_delivery_date
                    && $project->project_delivery_date->isPast()
                    && $project->project_execution_status !== 'delivered';

                $project->onboarded_posters = 0;
                $project->onboarded_videos = 0;
                if (is_array($project->custom_form_data)) {
                    foreach ($project->custom_form_data as $field) {
                        $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                        if ($label === 'number of posters' || $label === 'number of poster') {
                            $project->onboarded_posters = (int) ($field['value'] ?? 0);
                        } elseif ($label === 'number of videos' || $label === 'number of video') {
                            $project->onboarded_videos = (int) ($field['value'] ?? 0);
                        }
                    }
                }

                $project->delivered_posters = (int) $project->timesheets->sum('poster_count');
                $project->delivered_videos = (int) $project->timesheets->sum('video_count');
                $project->allocated_names = $this->allocatedEmployees($project)->pluck('name')->implode(', ') ?: 'Pending';

                return $project;
            });

        $tableRows = collect();
        foreach ($renewals as $renewal) {
            $renewalProjects = $projects->where('lead_product_id', $renewal->id);
            if ($renewalProjects->isNotEmpty()) {
                foreach ($renewalProjects as $project) {
                    $tableRows->push([
                        'renewal_id' => $renewal->id,
                        'renewal_name' => $renewal->product?->product_name ?: ($renewal->product_name ?: '—'),
                        'has_project' => true,
                        'project' => [
                            'id' => $project->id,
                            'product_name' => $project->product_name,
                            'department' => $project->department?->name,
                            'start_date' => $project->start_date?->toDateString(),
                            'project_delivery_date' => $project->project_delivery_date?->toDateString(),
                            'allocated_names' => $project->allocated_names,
                            'onboarded_posters' => $project->onboarded_posters,
                            'onboarded_videos' => $project->onboarded_videos,
                            'delivered_posters' => $project->delivered_posters,
                            'delivered_videos' => $project->delivered_videos,
                            'project_execution_status' => $project->project_execution_status,
                            'is_overdue' => $project->is_overdue,
                        ],
                    ]);
                }
            } else {
                $tableRows->push([
                    'renewal_id' => $renewal->id,
                    'renewal_name' => $renewal->product?->product_name ?: ($renewal->product_name ?: '—'),
                    'has_project' => false,
                    'project' => null,
                ]);
            }
        }

        $totalPosters = $projects->sum('onboarded_posters');
        $completedPosters = $projects->sum('delivered_posters');
        $totalVideos = $projects->sum('onboarded_videos');
        $completedVideos = $projects->sum('delivered_videos');

        return response()->json([
            'success' => true,
            'data' => [
                'lead' => [
                    'id' => $lead->id,
                    'company_name' => $lead->company_name ?: 'Account Details',
                    'contact_name' => $lead->contact_name,
                    'mobile_number' => $lead->mobile_number,
                    'email' => $lead->email,
                    'lead_source' => $lead->source_label ?? $lead->lead_source,
                    'lead_status' => null,
                    'status_color' => $lead->status_color,
                    'priority_color' => $lead->priority_color,
                    'priority_label' => $lead->priority_label,
                    'formatted_deal_value' => $lead->formatted_deal_value,
                ],
                'stats' => [
                    'total_renewals' => $renewals->count(),
                    'total_posters' => $totalPosters,
                    'completed_posters' => $completedPosters,
                    'pending_posters' => max(0, $totalPosters - $completedPosters),
                    'overdue_posters' => $projects->filter(fn($p) => $p->is_overdue)->sum(fn($p) => max(0, $p->onboarded_posters - $p->delivered_posters)),
                    'total_videos' => $totalVideos,
                    'completed_videos' => $completedVideos,
                    'pending_videos' => max(0, $totalVideos - $completedVideos),
                    'overdue_videos' => $projects->filter(fn($p) => $p->is_overdue)->sum(fn($p) => max(0, $p->onboarded_videos - $p->delivered_videos)),
                ],
                'table_rows' => $tableRows,
            ],
        ]);
    }

    public function designingDashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->belongsToDesigningDepartment(), 403);

        $designDeptId = Department::whereRaw('LOWER(name) LIKE ?', ['%design%'])->value('id');

        $designProjects = $this->visibleProjectsQuery($user)
            ->where('department_id', $designDeptId)
            ->get();
        $designProjects->load('timesheets');

        $totalPosters = 0;
        $totalVideos = 0;
        $completedPosters = 0;
        $completedVideos = 0;
        $pendingPosters = 0;
        $pendingVideos = 0;
        $overduePosters = 0;
        $overdueVideos = 0;

        foreach ($designProjects as $project) {
            $posterCountCustom = 0;
            $videoCountCustom = 0;
            $endDateCustom = null;
            if (is_array($project->custom_form_data)) {
                foreach ($project->custom_form_data as $field) {
                    $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                    $value = trim((string) ($field['value'] ?? ''));
                    if ($label === 'number of posters' || $label === 'number of poster') {
                        $posterCountCustom = (int) $value;
                    } elseif ($label === 'number of videos' || $label === 'number of video') {
                        $videoCountCustom = (int) $value;
                    } elseif ($label === 'end date') {
                        $endDateCustom = $value;
                    }
                }
            }

            $projectPostersDelivered = (int) $project->timesheets->sum('poster_count');
            $projectVideosDelivered = (int) $project->timesheets->sum('video_count');
            $projectPostersPending = max(0, $posterCountCustom - $projectPostersDelivered);
            $projectVideosPending = max(0, $videoCountCustom - $projectVideosDelivered);

            $totalPosters += $posterCountCustom;
            $totalVideos += $videoCountCustom;
            $completedPosters += $projectPostersDelivered;
            $completedVideos += $projectVideosDelivered;
            $pendingPosters += $projectPostersPending;
            $pendingVideos += $projectVideosPending;

            $end = $endDateCustom ? Carbon::parse($endDateCustom)->startOfDay() : $this->projectDeliveryDate($project);
            $isOverdue = $end && $end->isPast() && ($projectPostersPending > 0 || $projectVideosPending > 0);
            if ($isOverdue) {
                $overduePosters += $projectPostersPending;
                $overdueVideos += $projectVideosPending;
            }
        }

        $isTl = $this->shouldLimitToAssignedProjects($user);
        $userIds = [$user->id];
        if ($isTl) {
            $userIds = array_merge($userIds, $designProjects
                ->flatMap(fn($p) => Arr::wrap($p->project_allocated_employee_user_ids))
                ->map(fn($id) => (int) $id)->filter()->unique()->values()->toArray());
        }

        $userPosterTarget = (int) DesignSettingTarget::whereIn('user_id', $userIds)
            ->whereRaw('LOWER(product_type) = ?', ['poster'])->where('is_active', true)->sum('daily_target');
        $userVideoTarget = (int) DesignSettingTarget::whereIn('user_id', $userIds)
            ->whereRaw('LOWER(product_type) = ?', ['video'])->where('is_active', true)->sum('daily_target');

        $stats = [
            'daily_target_posters' => $userPosterTarget,
            'daily_target_videos' => $userVideoTarget,
            'overdue_posters' => $overduePosters,
            'overdue_videos' => $overdueVideos,
            'total_accounts' => $designProjects->count(),
            'total_posters' => $totalPosters,
            'total_videos' => $totalVideos,
            'completed_posters' => $completedPosters,
            'completed_videos' => $completedVideos,
            'pending_posters' => max(0, $pendingPosters - $overduePosters),
            'pending_videos' => max(0, $pendingVideos - $overdueVideos),
        ];

        $filterAccountId = trim((string) $request->query('project_id', ''));
        $filterDate = trim((string) $request->query('date', Carbon::today()->toDateString()));
        $filterStatus = trim((string) $request->query('status', ''));

        $filteredProjects = $designProjects;
        if ($filterAccountId !== '') {
            $filteredProjects = $filteredProjects->where('id', (int) $filterAccountId);
        }
        if ($filterStatus !== '') {
            $filteredProjects = $filteredProjects->filter(function ($project) use ($filterStatus) {
                $deliveryDate = $this->projectDeliveryDate($project);
                $isOverdue = $deliveryDate && $deliveryDate->isPast() && $project->project_execution_status !== 'delivered';
                return match ($filterStatus) {
                    'waiting_approval' => ! $project->content_calendar_approved,
                    'inprogress' => in_array($project->project_execution_status, ['ontrack', 'hold'], true),
                    'waiting_review' => strtolower(trim((string) $project->production_approval_status)) === 'approval',
                    'completed' => $project->project_execution_status === 'delivered',
                    'overdue' => $isOverdue,
                    default => true,
                };
            });
        }

        $today = Carbon::today()->startOfDay();

        $tasks = $filteredProjects->map(function ($project) use ($filterDate, $today, $user) {
            $timesheetQuery = ProjectTimesheet::where('production_initiation_id', $project->id)
                ->whereDate('timesheet_date', $filterDate);
            if ($this->shouldLimitToEmployeeProjects($user)) {
                $timesheetQuery->where('user_id', $user->id);
            }
            $timesheet = $timesheetQuery->first();

            $startDateCustom = null;
            $endDateCustom = null;
            $tenureCustom = null;
            $posterCountCustom = 0;
            $videoCountCustom = 0;
            if (is_array($project->custom_form_data)) {
                foreach ($project->custom_form_data as $field) {
                    $label = strtolower(trim((string) ($field['label'] ?? ($field['key'] ?? ''))));
                    $value = trim((string) ($field['value'] ?? ''));
                    if ($label === 'start date') $startDateCustom = $value;
                    elseif ($label === 'end date') $endDateCustom = $value;
                    elseif ($label === 'tenure') $tenureCustom = strtolower($value);
                    elseif ($label === 'number of posters' || $label === 'number of poster') $posterCountCustom = (int) $value;
                    elseif ($label === 'number of videos' || $label === 'number of video') $videoCountCustom = (int) $value;
                }
            }

            $start = $startDateCustom ? Carbon::parse($startDateCustom)->startOfDay() : ($project->production_approval_reviewed_at ?: ($project->project_allocated_at ?: $project->created_at));
            $start = $start ? Carbon::parse($start)->startOfDay() : null;
            $end = $endDateCustom ? Carbon::parse($endDateCustom)->startOfDay() : $this->projectDeliveryDate($project);
            $end = $end ? Carbon::parse($end)->startOfDay() : null;

            $targetDate = Carbon::parse($filterDate)->startOfDay();
            $deliveredPosters = (int) $project->timesheets->sum('poster_count');
            $deliveredVideos = (int) $project->timesheets->sum('video_count');
            $remainingPosters = max(0, $posterCountCustom - $deliveredPosters);
            $remainingVideos = max(0, $videoCountCustom - $deliveredVideos);

            $referenceDate = $end && $end->gte($today) ? $today : $targetDate;
            $remainingDays = $end ? max(1, (int) $referenceDate->diffInDays($end) + 1) : 1;

            $committedPosters = $timesheet ? (int) $timesheet->committed_posters : 0;
            $committedVideos = $timesheet ? (int) $timesheet->committed_videos : 0;

            return [
                'production_initiation_id' => $project->id,
                'product_name' => $project->product_name,
                'company_name' => $project->company_name ?: ($project->lead?->company_name ?: 'No Company'),
                'account_name' => $project->product_name . ' (' . ($project->company_name ?: ($project->lead?->company_name ?: 'No Company')) . ')',
                'committed_posters' => $committedPosters,
                'committed_videos' => $committedVideos,
                'waiting_posters' => $timesheet ? (int) $timesheet->waiting_posters : 0,
                'waiting_videos' => $timesheet ? (int) $timesheet->waiting_videos : 0,
                'completed_posters' => $timesheet ? (int) $timesheet->poster_count : 0,
                'completed_videos' => $timesheet ? (int) $timesheet->video_count : 0,
                'start_date' => $start ? $start->format('d M Y') : null,
                'end_date' => $end ? $end->format('d M Y') : null,
                'tenure' => ucfirst($tenureCustom ?: 'daily'),
                'day_closing_update' => $timesheet ? $timesheet->day_closing_update : '',
                'pending_posters' => $remainingPosters,
                'pending_videos' => $remainingVideos,
                'remaining_days' => $remainingDays,
                'per_day_posters' => (int) ceil($remainingPosters / $remainingDays),
                'per_day_videos' => (int) ceil($remainingVideos / $remainingDays),
                'is_overdue' => $end && $end->lt($today) && ($remainingPosters > 0 || $remainingVideos > 0),
            ];
        })
            ->filter(fn($t) => $t['committed_posters'] > 0 || $t['committed_videos'] > 0)
            ->values();

        $overdueTasksList = $tasks->filter(fn($t) => $t['is_overdue'])->values();
        $todayPlannedTasks = $tasks->filter(fn($t) => ! $t['is_overdue'])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'design_projects' => $designProjects->map(fn($p) => ['id' => $p->id, 'product_name' => $p->product_name])->values(),
                'today_planned_tasks' => $todayPlannedTasks,
                'overdue_tasks_list' => $overdueTasksList,
                'is_tl' => $isTl,
                'team_members' => $isTl ? $this->availableTeamMembers($user) : [],
                'filters' => [
                    'project_id' => $filterAccountId,
                    'date' => $filterDate,
                    'status' => $filterStatus,
                ],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Testing Department Dashboard — mobile mirror of
    //  App\Http\Controllers\ProjectController::dashboard()'s 'testing' branch
    //  + resources/views/pages/projects/testing-dashboard.blade.php /
    //  testing-details.blade.php. Same statuses (open/moved_to_testing treated
    //  as one 'open' bucket, ongoing, retesting, completed), same counts, same
    //  unscoped (company-only, via ProjectTestingDetail's BelongsToCompany
    //  global scope) visibility — web applies no extra branch/user scoping
    //  here either, so mobile matches that exactly rather than introducing a
    //  narrower view the web page doesn't have.
    //
    //  GET /mobile/projects/testing-dashboard
    // ─────────────────────────────────────────────────────────────────────────
    public function testingDashboard(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->belongsToTestingDepartment() || $user->hasTestingLikeRole(), 403);

        $testingHandovers = ProjectTestingDetail::with([
            'productionInitiation.leadProduct',
            'productionInitiation.product',
            'productionInitiation.lead',
            'productionInitiation.bugs',
            'movedBy',
            'testingTl',
        ])->latest()->get();

        $activeStatus = (string) $request->query('status', 'open');

        $openCount = $testingHandovers->whereIn('status', ['moved_to_testing', 'open'])->count();
        $ongoingCount = $testingHandovers->where('status', 'ongoing')->count();
        $retestingCount = $testingHandovers->where('status', 'retesting')->count();
        $completedCount = $testingHandovers->where('status', 'completed')->count();

        $filteredHandovers = match ($activeStatus) {
            'ongoing' => $testingHandovers->where('status', 'ongoing'),
            'retesting' => $testingHandovers->where('status', 'retesting'),
            'completed' => $testingHandovers->where('status', 'completed'),
            default => $testingHandovers->filter(fn ($h) => in_array($h->status, ['moved_to_testing', 'open'], true)),
        };

        if (! in_array($activeStatus, ['open', 'ongoing', 'retesting', 'completed'], true)) {
            $activeStatus = 'open';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'active_status' => $activeStatus,
                'stats' => [
                    'open' => $openCount,
                    'ongoing' => $ongoingCount,
                    'retesting' => $retestingCount,
                    'completed' => $completedCount,
                ],
                'handovers' => $filteredHandovers->values()->map(fn ($h) => $this->serializeTestingHandover($h))->all(),
            ],
        ]);
    }

    /**
     * GET /mobile/projects/testing-dashboard/{productionInitiation}
     * Mirrors ProjectController::testingDetails() — the "View Details" screen
     * reached from a Testing Dashboard card.
     */
    public function testingProjectDetails(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $productionInitiation->load([
            'leadProduct',
            'product',
            'lead',
            'testingDetails.movedBy',
            'testingDetails.testingTl',
            'bugs.createdBy',
        ]);

        $latestHandover = $productionInitiation->testingDetails()->latest()->first();
        $bugs = $productionInitiation->bugs()->with('createdBy')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $this->serializeProjectSummary($productionInitiation),
                'handover' => $latestHandover ? $this->serializeHandoverDetail($latestHandover) : null,
                'bugs' => $bugs->map(fn ($b) => $this->serializeBug($b))->values()->all(),
            ],
        ]);
    }

    /**
     * POST /mobile/projects/testing-dashboard/{productionInitiation}/status
     * Mirrors ProjectController::updateTestingStatus() exactly (same allowed
     * values, updates the latest handover row only).
     */
    public function updateTestingStatus(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(['open', 'moved_to_testing', 'ongoing', 'retesting', 'completed'])],
        ]);

        $latestHandover = $productionInitiation->testingDetails()->latest()->first();
        if (! $latestHandover) {
            return response()->json(['success' => false, 'message' => 'No testing handover found for this project.'], 404);
        }

        $latestHandover->update(['status' => $validated['status']]);
        $latestHandover->load(['movedBy', 'testingTl']);

        return response()->json([
            'success' => true,
            'message' => 'Testing status updated successfully.',
            'data' => $this->serializeHandoverDetail($latestHandover),
        ]);
    }

    /**
     * POST /mobile/projects/{productionInitiation}/bugs
     * Mirrors ProjectController::storeBug() exactly — including the 15s
     * duplicate-submission guard and public-disk file storage. Called both
     * from the Testing Department's testing-details screen and the
     * Development side's Project Detail "Testing" tab, same as web.
     */
    public function storeBug(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', \Illuminate\Validation\Rule::in(['High', 'Medium', 'Low'])],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        $existingBug = ProjectBug::where('production_initiation_id', $productionInitiation->id)
            ->where('created_by_user_id', auth()->id())
            ->where('description', $validated['description'])
            ->where('created_at', '>=', now()->subSeconds(15))
            ->first();

        if ($existingBug) {
            return response()->json([
                'success' => true,
                'message' => 'Bug report already submitted.',
                'data' => $this->serializeBug($existingBug->load('createdBy')),
            ]);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();

            $folder = public_path('uploads/project-bugs');
            if (! file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            $fileName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($folder, $fileName);
            $attachmentPath = 'uploads/project-bugs/' . $fileName;
        }

        $bug = $productionInitiation->bugs()->create([
            'company_id' => auth()->user()?->company_id,
            'lead_id' => $productionInitiation->lead_id,
            'lead_product_id' => $productionInitiation->lead_product_id,
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => $attachmentName,
            'status' => 'open',
            'created_by_user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bug reported successfully to project testing.',
            'data' => $this->serializeBug($bug->load('createdBy')),
        ], 201);
    }

    /**
     * PATCH /mobile/projects/bugs/{bug}/status
     * Mirrors ProjectController::updateBugStatus() exactly.
     */
    public function updateBugStatus(Request $request, ProjectBug $bug): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(['open', 'fixed', 'closed'])],
        ]);

        $bug->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Bug status updated successfully.',
            'data' => $this->serializeBug($bug->load('createdBy')),
        ]);
    }

    /**
     * POST /mobile/projects/{productionInitiation}/move-to-testing
     * Mirrors ProjectController::moveToTesting() exactly, including the
     * notification email (TO the selected Testing TL, CC'd to
     * projects@saitechnosolutions.net + the project's Development TLs).
     * Development-side action — reached from the mobile Project Detail
     * screen's "Testing" tab, not the Testing Department dashboard.
     */
    public function moveToTesting(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $validated = $request->validate([
            'credentials' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'testing_tl_id' => ['nullable', 'exists:users,id'],
        ]);

        $testingDetail = $productionInitiation->testingDetails()->create([
            'company_id' => auth()->user()?->company_id,
            'lead_id' => $productionInitiation->lead_id,
            'lead_product_id' => $productionInitiation->lead_product_id,
            'credentials' => $validated['credentials'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'moved_to_testing',
            'moved_by_user_id' => auth()->id(),
            'testing_tl_id' => $validated['testing_tl_id'] ?? null,
        ]);

        $testingTl = ! empty($validated['testing_tl_id']) ? User::find($validated['testing_tl_id']) : null;
        $toEmail = $testingTl?->email ?: 'projects@saitechnosolutions.net';

        $filterDevUsers = function ($collection) {
            return $collection->filter(function ($u) {
                if (! is_object($u)) return false;
                $roles = method_exists($u, 'resolvedRoles') ? $u->resolvedRoles(true) : ($u->roles ?? collect());
                $deptNames = strtolower($roles->map(fn ($r) => $r->department?->name)->filter()->implode(' '));
                $roleNames = strtolower($roles->implode('display_name', ' ') . ' ' . $roles->implode('name', ' '));
                return str_contains($deptNames, 'development')
                    || str_contains($roleNames, 'development')
                    || str_contains($roleNames, 'software')
                    || str_contains($roleNames, 'web')
                    || str_contains($roleNames, 'app');
            })->pluck('email')->filter()->all();
        };

        $userDevTlUsers = auth()->user()?->mappedManagers()->get() ?? collect();
        $allocatedDevTlUsers = $this->allocatedTlUsers($productionInitiation);

        $ccEmails = array_values(array_unique(array_filter(array_merge(
            ['projects@saitechnosolutions.net'],
            $filterDevUsers($userDevTlUsers),
            $filterDevUsers($allocatedDevTlUsers)
        ))));
        $ccEmails = array_values(array_diff($ccEmails, [$toEmail]));

        try {
            Mail::to($toEmail)
                ->cc($ccEmails)
                ->send(new \App\Mail\ProjectTestingNotificationMail($productionInitiation, $testingDetail));
        } catch (\Throwable $e) {
            Log::error('Failed sending Project Testing notification mail (mobile): ' . $e->getMessage());
        }

        $testingDetail->load(['movedBy', 'testingTl']);

        return response()->json([
            'success' => true,
            'message' => 'Project details updated and moved to Testing. Notification email sent to Testing TL.',
            'data' => $this->serializeHandoverDetail($testingDetail),
        ], 201);
    }

    /** Compact row for the Testing Dashboard's handover list. */
    private function serializeTestingHandover(ProjectTestingDetail $handover): array
    {
        $proj = $handover->productionInitiation;
        $allBugs = $proj?->bugs ?? collect();

        return [
            'id' => $handover->id,
            'production_initiation_id' => $handover->production_initiation_id,
            'status' => $handover->status,
            'project_name' => $proj?->leadProduct?->name
                ?? $proj?->product?->name
                ?? ($proj?->lead?->company_name ? $proj->lead->company_name . ' Project' : 'Project #' . $handover->production_initiation_id),
            'company_name' => $proj?->lead?->company_name,
            'moved_at' => optional($handover->created_at)->toIso8601String(),
            'developer_name' => $handover->movedBy?->name ?? 'Developer Team',
            'delivery_date' => $proj ? optional($this->projectDeliveryDate($proj))->format('Y-m-d') : null,
            'total_bugs' => $allBugs->count(),
            'resolved_bugs' => $allBugs->whereIn('status', ['fixed', 'closed', 'resolved'])->count(),
        ];
    }

    /** Full handover payload for the testing-details / Testing-tab screens. */
    private function serializeHandoverDetail(ProjectTestingDetail $handover): array
    {
        return [
            'id' => $handover->id,
            'production_initiation_id' => $handover->production_initiation_id,
            'status' => $handover->status,
            'credentials' => $handover->credentials,
            'notes' => $handover->notes,
            'moved_at' => optional($handover->created_at)->toIso8601String(),
            'developer_name' => $handover->movedBy?->name ?? 'Dev Team',
            'testing_tl' => $handover->testingTl ? [
                'id' => $handover->testingTl->id,
                'name' => $handover->testingTl->name,
            ] : null,
        ];
    }

    private function serializeBug(ProjectBug $bug): array
    {
        return [
            'id' => $bug->id,
            'production_initiation_id' => $bug->production_initiation_id,
            'description' => $bug->description,
            'priority' => $bug->priority,
            'status' => $bug->status,
            'attachment_url' => $bug->attachment_path ? asset($bug->attachment_path) : null,
            'attachment_name' => $bug->attachment_original_name,
            'created_at' => optional($bug->created_at)->toIso8601String(),
            'created_by' => $bug->createdBy ? [
                'id' => $bug->createdBy->id,
                'name' => $bug->createdBy->name,
            ] : null,
        ];
    }

    public function updatePlannedTask(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->belongsToDesigningDepartment(), 403);

        $validated = $request->validate([
            'production_initiation_id' => ['required', 'integer'],
            'timesheet_date' => ['required', 'date'],
            'committed_posters' => ['required', 'integer', 'min:0', 'max:100000'],
            'committed_videos' => ['required', 'integer', 'min:0', 'max:100000'],
            'waiting_posters' => ['required', 'integer', 'min:0', 'max:100000'],
            'waiting_videos' => ['required', 'integer', 'min:0', 'max:100000'],
            'poster_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'video_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'day_closing_update' => ['nullable', 'string'],
        ]);

        if (($validated['poster_count'] + $validated['waiting_posters']) > $validated['committed_posters']) {
            return response()->json([
                'success' => false,
                'message' => "Completed + Waiting posters cannot exceed Committed posters ({$validated['committed_posters']}).",
            ], 422);
        }
        if (($validated['video_count'] + $validated['waiting_videos']) > $validated['committed_videos']) {
            return response()->json([
                'success' => false,
                'message' => "Completed + Waiting videos cannot exceed Committed videos ({$validated['committed_videos']}).",
            ], 422);
        }

        $project = $this->visibleProjectsQuery($user)->whereKey($validated['production_initiation_id'])->firstOrFail();
        $timesheetDate = Carbon::parse($validated['timesheet_date'])->toDateString();

        $timesheet = ProjectTimesheet::where('production_initiation_id', $project->id)
            ->where('user_id', $user->id)
            ->whereDate('timesheet_date', $timesheetDate)
            ->first();

        $payload = [
            'committed_posters' => $validated['committed_posters'],
            'committed_videos' => $validated['committed_videos'],
            'waiting_posters' => $validated['waiting_posters'],
            'waiting_videos' => $validated['waiting_videos'],
            'poster_count' => $validated['poster_count'],
            'video_count' => $validated['video_count'],
            'day_closing_update' => $validated['day_closing_update'] ?: ($timesheet?->day_closing_update ?? ''),
        ];

        if ($timesheet) {
            $timesheet->update($payload);
        } else {
            $timesheet = ProjectTimesheet::create($payload + [
                'company_id' => $project->company_id,
                'production_initiation_id' => $project->id,
                'user_id' => $user->id,
                'timesheet_date' => $timesheetDate,
                'project_delivery_date' => $this->projectDeliveryDate($project)?->toDateString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Planned task details updated successfully.',
            'data' => $this->serializeTimesheet($timesheet),
        ]);
    }

    public function allocateDailyTask(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->belongsToDesigningDepartment(), 403);

        $validated = $request->validate([
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
            'timesheet_date' => ['required', 'date'],
            'allocations' => ['required', 'array'],
            'allocations.*.production_initiation_id' => ['required', 'integer', 'exists:production_initiations,id'],
            'allocations.*.committed_posters' => ['required', 'integer', 'min:0', 'max:100000'],
            'allocations.*.committed_videos' => ['required', 'integer', 'min:0', 'max:100000'],
            'allocations.*.selected' => ['nullable'],
        ]);

        $isTl = $this->shouldLimitToAssignedProjects($user);
        $assignedUserId = (int) $validated['assigned_user_id'];

        if (! $isTl && $assignedUserId !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You can only allocate tasks to yourself.'], 403);
        }

        // Mobile sends only the chosen allocations already (no 'selected' key at
        // all, unlike web's checkbox-based full list) — treat absence of the key
        // as "selected", same effective result either way.
        $allocations = collect($validated['allocations'])->filter(function ($a) {
            if (! array_key_exists('selected', $a)) return true;
            return in_array($a['selected'], ['1', 1, true], true);
        });

        if ($allocations->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Please select at least one account/project to allocate tasks.'], 422);
        }

        $posterTarget = (int) DesignSettingTarget::where('user_id', $assignedUserId)
            ->whereRaw('LOWER(product_type) = ?', ['poster'])->where('is_active', true)->value('daily_target');
        $videoTarget = (int) DesignSettingTarget::where('user_id', $assignedUserId)
            ->whereRaw('LOWER(product_type) = ?', ['video'])->where('is_active', true)->value('daily_target');

        foreach ($allocations as $alloc) {
            $committedPosters = (int) $alloc['committed_posters'];
            $committedVideos = (int) $alloc['committed_videos'];
            if ($committedPosters > 0 && $committedPosters < $posterTarget) {
                return response()->json(['success' => false, 'message' => "Committed posters cannot be less than your daily target of {$posterTarget}."], 422);
            }
            if ($committedVideos > 0 && $committedVideos < $videoTarget) {
                return response()->json(['success' => false, 'message' => "Committed videos cannot be less than your daily target of {$videoTarget}."], 422);
            }
        }

        $timesheetDate = Carbon::parse($validated['timesheet_date'])->toDateString();

        foreach ($allocations as $alloc) {
            $project = $this->visibleProjectsQuery($user)->whereKey((int) $alloc['production_initiation_id'])->firstOrFail();
            $committedPosters = (int) $alloc['committed_posters'];
            $committedVideos = (int) $alloc['committed_videos'];

            $timesheet = ProjectTimesheet::where('production_initiation_id', $project->id)
                ->where('user_id', $assignedUserId)
                ->whereDate('timesheet_date', $timesheetDate)
                ->first();

            if ($timesheet) {
                $timesheet->update(['committed_posters' => $committedPosters, 'committed_videos' => $committedVideos]);
            } else {
                ProjectTimesheet::create([
                    'company_id' => $project->company_id,
                    'production_initiation_id' => $project->id,
                    'user_id' => $assignedUserId,
                    'timesheet_date' => $timesheetDate,
                    'project_delivery_date' => $this->projectDeliveryDate($project)?->toDateString(),
                    'committed_posters' => $committedPosters,
                    'committed_videos' => $committedVideos,
                    'waiting_posters' => 0,
                    'waiting_videos' => 0,
                    'poster_count' => 0,
                    'video_count' => 0,
                    'day_closing_update' => '',
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Daily task allocated successfully.']);
    }

    public function updateContentCalendarSheet(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        if ($productionInitiation->content_calendar_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Content Calendar is already approved. You cannot change the Google Sheet URL.',
            ], 422);
        }

        $validated = $request->validate(['content_calendar_sheet_url' => ['nullable', 'url', 'max:2000']]);
        $productionInitiation->update(['content_calendar_sheet_url' => $validated['content_calendar_sheet_url'] ?: null]);

        return response()->json(['success' => true, 'message' => 'Content Calendar Google Sheet URL updated successfully.']);
    }

    public function approveContentCalendar(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        if (! $user->belongsToDesigningDepartment()) {
            return response()->json(['success' => false, 'message' => 'Only members of the Designing department can approve the Content Calendar.'], 403);
        }
        if ($productionInitiation->content_calendar_approved) {
            return response()->json(['success' => false, 'message' => 'Content Calendar is already approved.'], 422);
        }

        $validated = $request->validate(['remarks' => ['required', 'string', 'min:3', 'max:5000']]);
        $productionInitiation->update([
            'content_calendar_approved' => true,
            'content_calendar_remarks' => $validated['remarks'],
        ]);

        return response()->json(['success' => true, 'message' => 'Content Calendar approved successfully with remarks.']);
    }

    public function fetchContentCalendarData(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();
        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        $rawUrl = (string) ($productionInitiation->content_calendar_sheet_url ?? '');
        if (empty($rawUrl)) {
            return response()->json(['error' => 'No sheet URL configured for this project.'], 422);
        }

        $csvUrl = $this->buildGoogleSheetCsvUrl($rawUrl);
        if (! $csvUrl) {
            return response()->json(['error' => 'Invalid Google Sheets URL. Please reconfigure.'], 422);
        }

        try {
            $response = Http::timeout(20)->withHeaders(['Accept' => 'text/csv,text/plain,*/*'])->get($csvUrl);
            if (! $response->successful()) {
                return response()->json(['error' => 'Google Sheet fetch failed (HTTP ' . $response->status() . '). Make sure the sheet is publicly accessible.'], 502);
            }
            return response()->json(['csv' => $response->body()]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    private function buildGoogleSheetCsvUrl(string $raw): ?string
    {
        $raw = trim((string) preg_replace('/#.*$/', '', $raw));

        if (str_contains($raw, 'output=csv') || str_contains($raw, 'format=csv')) {
            return $raw;
        }
        if (preg_match('#^(https://docs\.google\.com/spreadsheets/d/e/[A-Za-z0-9_-]+)/pub(html)?(\?.*)?$#', $raw, $m)) {
            return $m[1] . '/pub?output=csv';
        }
        if (preg_match('#/spreadsheets/d/([A-Za-z0-9_-]+)#', $raw, $m)) {
            $sheetId = $m[1];
            $gid = '0';
            if (preg_match('/[?&]gid=([0-9]+)/', $raw, $gm)) {
                $gid = $gm[1];
            }
            return 'https://docs.google.com/spreadsheets/d/' . $sheetId . '/export?format=csv&gid=' . $gid;
        }
        return null;
    }
}
