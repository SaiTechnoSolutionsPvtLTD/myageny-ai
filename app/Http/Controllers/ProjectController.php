<?php

namespace App\Http\Controllers;

use App\Models\ProductionInitiation;
use App\Models\ProjectTimesheet;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectController extends Controller
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

    public function dashboard(Request $request): View
    {
        $user = auth()->user();
        $canQuickAddProductionUpdate = $this->canQuickAddProductionUpdate($user);
        $projects = $this->visibleProjectsQuery($user)
            ->get()
            ->map(function (ProductionInitiation $project) use ($user) {
                $project = $this->decorateProjectForUser($project, $user);
                $receivedAmount = (float) ($project->leadProduct?->payments?->sum('amount') ?? $project->leadProduct?->amount_paid ?? 0);

                $project->dashboard_date = $this->dashboardProjectDate($project, $user);
                $project->project_delivery_date = $this->projectDeliveryDate($project);
                $project->project_value = (float) ($project->leadProduct?->total_price ?? 0);
                $project->received_amount = $receivedAmount;
                $project->balance_amount = max(0, $project->project_value - $project->received_amount);
                $project->allocated_employee_count = collect(Arr::wrap($project->project_allocated_employee_user_ids))
                    ->filter()
                    ->count();

                return $project;
            })
            ->values();
        $quickUpdateProjects = $projects
            ->filter(fn (ProductionInitiation $project) => $this->isDevelopmentProject($project))
            ->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $dashboardFilters = [
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'project_id' => trim((string) $request->query('project_id', '')),
            'team_member_id' => $this->shouldAllowDashboardUserFilter($user)
                ? trim((string) $request->query('team_member_id', ''))
                : '',
        ];

        $filteredProjects = $this->filterDashboardProjects($projects, $dashboardFilters, $user);
        $stats = [
            'allocated_projects' => $filteredProjects->count(),
            'project_value' => round($filteredProjects->sum('project_value'), 2),
            'received_amount' => round($filteredProjects->sum('received_amount'), 2),
            'balance_amount' => round($filteredProjects->sum('balance_amount'), 2),
        ];

        return view('pages.projects.dashboard', [
            'stats' => $stats,
            'dashboardFilters' => $dashboardFilters,
            'projectOptions' => $projects->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'teamMemberOptions' => $this->dashboardTeamMembers($user, $projects),
            'currentMonthDeliveryProjects' => $this->currentMonthDeliveryProjects($filteredProjects),
            'recentProjects' => $filteredProjects->take(10),
            'timesheetSummary' => $this->dashboardTimesheetSummary($user, $filteredProjects, $dashboardFilters),
            'isTlScopedView' => $this->shouldLimitToAssignedProjects($user),
            'isContributorScopedView' => $this->shouldLimitToEmployeeProjects($user),
            'canQuickAddProductionUpdate' => $canQuickAddProductionUpdate,
            'quickUpdateProjects' => $quickUpdateProjects,
        ]);
    }

    public function timesheets(Request $request): View
    {
        $user = auth()->user();
        $assignedProjects = $this->timesheetProjectsQuery($user)
            ->get()
            ->map(function (ProductionInitiation $project) {
                $project->timesheet_delivery_date = $this->projectDeliveryDate($project)?->toDateString();

                return $project;
            });
        $timesheetFilters = [
            'filter_date' => trim((string) $request->query('filter_date', '')),
            'filter_project_id' => trim((string) $request->query('filter_project_id', '')),
            'filter_status' => trim((string) $request->query('filter_status', '')),
        ];
        $timesheets = ProjectTimesheet::query()
            ->with(['project' => fn ($query) => $query->with($this->projectRelations())])
            ->where('user_id', $user->id)
            ->when($timesheetFilters['filter_date'] !== '', function ($query) use ($timesheetFilters) {
                try {
                    $query->whereDate('timesheet_date', Carbon::parse($timesheetFilters['filter_date'])->toDateString());
                } catch (\Throwable) {
                    // Ignore invalid filter dates from query string.
                }
            })
            ->when($timesheetFilters['filter_project_id'] !== '', function ($query) use ($timesheetFilters) {
                $query->where('production_initiation_id', (int) $timesheetFilters['filter_project_id']);
            })
            ->when($timesheetFilters['filter_status'] === 'completed', function ($query) {
                $query->whereNotNull('project_delivery_date')
                    ->whereDate('project_delivery_date', '<=', Carbon::today()->toDateString());
            })
            ->when($timesheetFilters['filter_status'] === 'pending', function ($query) {
                $query->where(function ($statusQuery) {
                    $statusQuery
                        ->whereNull('project_delivery_date')
                        ->orWhereDate('project_delivery_date', '>', Carbon::today()->toDateString());
                });
            })
            ->latest('created_at')
            ->latest('timesheet_date')
            ->get();

        return view('pages.projects.timesheets', [
            'assignedProjects' => $assignedProjects,
            'timesheets' => $timesheets,
            'timesheetFilters' => $timesheetFilters,
            'today' => Carbon::today()->toDateString(),
        ]);
    }

    public function storeTimesheet(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'production_initiation_id' => ['required', 'integer'],
            'timesheet_date' => ['required', 'date'],
            'day_closing_update' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $lines = collect(preg_split('/\R/', (string) $value))
                        ->map(fn (string $line) => trim($line))
                        ->filter();

                    if ($lines->count() < 5) {
                        $fail('Please add at least 5 task lines in the day closing update.');
                    }
                },
            ],
        ]);

        $project = $this->timesheetProjectsQuery($user)
            ->whereKey($validated['production_initiation_id'])
            ->firstOrFail();
        $timesheetDate = Carbon::parse($validated['timesheet_date'])->toDateString();

        $alreadyExists = ProjectTimesheet::query()
            ->where('production_initiation_id', $project->id)
            ->where('user_id', $user->id)
            ->whereDate('timesheet_date', $timesheetDate)
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withErrors(['production_initiation_id' => 'Timesheet already exists for this project on the selected date.'])
                ->withInput();
        }

        ProjectTimesheet::create([
            'company_id' => $project->company_id,
            'production_initiation_id' => $project->id,
            'user_id' => $user->id,
            'timesheet_date' => $timesheetDate,
            'project_delivery_date' => $this->projectDeliveryDate($project)?->toDateString(),
            'day_closing_update' => $validated['day_closing_update'],
        ]);

        return redirect()
            ->route('projects.timesheets')
            ->with('success', 'Timesheet saved successfully.');
    }

    public function index(Request $request): View
    {
        $user = auth()->user();
        $isTlScopedView = $this->shouldLimitToAssignedProjects($user);
        $isContributorScopedView = $this->shouldLimitToEmployeeProjects($user);
        if ($isContributorScopedView) {
            $projects = $this->visibleProjectsQuery($user)
                ->get()
                ->map(function (ProductionInitiation $project) {
                    $project->project_delivery_date = $this->projectDeliveryDate($project);

                    return $project;
                });

            $filters = [
                'search' => trim((string) $request->query('search', '')),
                'project_category' => trim((string) $request->query('project_category', '')),
                'delivery_from' => trim((string) $request->query('delivery_from', '')),
                'delivery_to' => trim((string) $request->query('delivery_to', '')),
            ];

            $filteredProjects = $this->filterEmployeeProjects($projects, $filters);

            return view('pages.projects.index', [
                'cards' => [],
                'selectedBucket' => 'allocated',
                'selectedCard' => [
                    'title' => 'Allocated Projects',
                    'status_label' => 'Allocated',
                    'count' => $filteredProjects->count(),
                    'items' => $filteredProjects,
                ],
                'isTlScopedView' => false,
                'isContributorScopedView' => true,
                'employeeProjects' => $filteredProjects,
                'projectFilters' => $filters,
                'projectCategories' => $projects
                    ->map(fn (ProductionInitiation $project) => $project->product?->category?->name)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),
            ]);
        }

        $initiations = $this->visibleProjectsQuery($user)
            ->get()
            ->map(fn (ProductionInitiation $initiation) => $this->decorateProjectForUser($initiation, $user));

        $buckets = [
            'allocation_pending' => [
                'title' => 'Allocation Pending',
                'status_label' => 'Allocation Pending',
                'count' => 0,
                'items' => collect(),
            ],
            'allocated' => [
                'title' => 'Allocated',
                'status_label' => 'Allocated',
                'count' => 0,
                'items' => collect(),
            ],
        ];

        foreach ($initiations as $initiation) {
            $bucket = $this->resolveBucketForUser($initiation, $user);

            if (! $bucket) {
                continue;
            }

            /** @var Collection $items */
            $items = $buckets[$bucket]['items'];
            $items->push($initiation);
            $buckets[$bucket]['items'] = $items;
            $buckets[$bucket]['count']++;
        }

        $selectedBucket = (string) $request->query('bucket', 'allocation_pending');

        if (! array_key_exists($selectedBucket, $buckets)) {
            $selectedBucket = 'allocation_pending';
        }

        return view('pages.projects.index', [
            'cards' => $buckets,
            'selectedBucket' => $selectedBucket,
            'selectedCard' => $buckets[$selectedBucket],
            'isTlScopedView' => $isTlScopedView,
            'isContributorScopedView' => false,
        ]);
    }

    public function show(Request $request, ProductionInitiation $productionInitiation): View
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);
        $productionInitiation = $this->decorateProjectForUser($productionInitiation, $user);

        $projectUpdatesQuery = $productionInitiation->projectUpdates()
            ->with('createdBy:id,name');
        $projectUpdateCounts = $productionInitiation->projectUpdates()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $selectedUpdateType = (string) $request->query('update_type', '');
        $selectedUpdateDate = (string) $request->query('update_date', '');

        if (in_array($selectedUpdateType, ['production_update', 'meeting_update', 'weekly_update'], true)) {
            $projectUpdatesQuery->where('type', $selectedUpdateType);
        } else {
            $selectedUpdateType = '';
        }

        if ($selectedUpdateDate !== '') {
            try {
                $projectUpdatesQuery->whereDate('created_at', Carbon::parse($selectedUpdateDate)->toDateString());
            } catch (\Throwable) {
                $selectedUpdateDate = '';
            }
        }

        $allocatedTlUsers = $this->allocatedTlUsers($productionInitiation);
        $teamMembers = $this->availableTeamMembers($user);
        $allocatedEmployees = $this->allocatedEmployees(
            $productionInitiation,
            $this->shouldLimitToAssignedProjects($user) ? $user : null
        );
        $tlAllocationSummaries = $this->tlAllocationSummaries($productionInitiation);
        $projectDeliveryDate = $this->projectDeliveryDate($productionInitiation);

        return view('pages.projects.show', [
            'projectItem' => $productionInitiation,
            'projectDeliveryDate' => $projectDeliveryDate,
            'canAllocate' => $this->canAllocateTl($productionInitiation, $user),
            'tlUsers' => $this->availableTlUsers(),
            'allocatedTlUsers' => $allocatedTlUsers,
            'tlAllocationSummaries' => $tlAllocationSummaries,
            'isTlScopedView' => $this->shouldLimitToAssignedProjects($user),
            'isAssignedTl' => $this->isAssignedTlForProject($productionInitiation, $user),
            'canManageProjectSchedule' => $this->canManageProjectSchedule($productionInitiation, $user),
            'teamMembers' => $teamMembers,
            'canAllocateEmployees' => $this->canAllocateEmployees($productionInitiation, $user),
            'allocatedEmployees' => $allocatedEmployees,
            'projectUpdates' => $projectUpdatesQuery->latest('created_at')->get(),
            'projectUpdateCounts' => $projectUpdateCounts,
            'selectedUpdateType' => $selectedUpdateType,
            'selectedUpdateDate' => $selectedUpdateDate,
        ]);
    }

    public function allocate(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($this->canAllocateTl($productionInitiation, $user), 403);

        $validated = $request->validate([
            'tl_user_ids' => ['required', 'array', 'min:1'],
            'tl_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedTlIds = $this->availableTlUsers()
            ->pluck('id')
            ->intersect(collect($validated['tl_user_ids'])->map(fn ($id) => (int) $id))
            ->values()
            ->all();

        abort_if($selectedTlIds === [], 422, 'Please select at least one TL user.');

        $existingTlAllocations = $this->tlEmployeeAllocations($productionInitiation);
        $tlAllocations = collect($selectedTlIds)
            ->mapWithKeys(function (int $tlUserId) use ($existingTlAllocations) {
                $existingAllocation = $existingTlAllocations->get((string) $tlUserId, []);

                return [
                    (string) $tlUserId => $this->normalizeTlEmployeeAllocation([
                        'tl_user_id' => $tlUserId,
                        'status' => $existingAllocation['status'] ?? 'allocation_pending',
                        'allocated_at' => $existingAllocation['allocated_at'] ?? null,
                        'allocated_by' => $existingAllocation['allocated_by'] ?? null,
                        'employee_user_ids' => $existingAllocation['employee_user_ids'] ?? [],
                    ]),
                ];
            })
            ->all();

        $productionInitiation->update([
            'project_allocation_status' => 'allocated',
            'project_allocated_at' => Carbon::now(),
            'project_allocated_by' => $user->id,
            'project_allocated_tl_user_ids' => $selectedTlIds,
            'tl_employee_allocations' => $tlAllocations,
            ...$this->summarizeTlEmployeeAllocations($tlAllocations),
        ]);

        return redirect()
            ->route('projects.show', $productionInitiation)
            ->with('success', 'Project moved to TL allocation successfully.');
    }

    public function allocateEmployees(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($this->canAllocateEmployees($productionInitiation, $user), 403);

        $validated = $request->validate([
            'employee_user_ids' => ['required', 'array', 'min:1'],
            'employee_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $selectedEmployeeIds = $this->availableTeamMembers($user)
            ->pluck('id')
            ->intersect(collect($validated['employee_user_ids'])->map(fn ($id) => (int) $id))
            ->values()
            ->all();

        abort_if($selectedEmployeeIds === [], 422, 'Please select at least one employee.');

        $tlAllocations = $this->tlEmployeeAllocations($productionInitiation);
        $tlAllocations->put((string) $user->id, $this->normalizeTlEmployeeAllocation([
            'tl_user_id' => $user->id,
            'status' => 'allocated',
            'allocated_at' => Carbon::now()->toDateTimeString(),
            'allocated_by' => $user->id,
            'employee_user_ids' => $selectedEmployeeIds,
        ]));
        $allocationSummary = $this->summarizeTlEmployeeAllocations($tlAllocations->all());

        $productionInitiation->update([
            'tl_employee_allocations' => $tlAllocations->all(),
            ...$allocationSummary,
        ]);

        return redirect()
            ->route('projects.show', $productionInitiation)
            ->with('success', 'Employees allocated to this project successfully.');
    }

    public function updateSchedule(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);
        abort_unless($this->canManageProjectSchedule($productionInitiation, $user), 403);

        $validated = $request->validate([
            'project_delivery_date' => ['nullable', 'date'],
            'project_execution_status' => ['required', 'in:ontrack,hold,delivered'],
        ]);

        $productionInitiation->update([
            'project_delivery_date' => $validated['project_delivery_date'] ?: null,
            'project_execution_status' => $validated['project_execution_status'],
        ]);

        return redirect()
            ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'overview'])
            ->with('success', 'Project delivery date and status updated successfully.');
    }

    public function storeUpdate(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        $this->ensureProjectIsVisibleToUser($productionInitiation, $user);

        $validated = $request->validate([
            'type' => ['required', 'in:production_update,meeting_update,weekly_update'],
            'content' => ['required', 'string'],
        ]);

        $productionInitiation->projectUpdates()->create([
            'type' => $validated['type'],
            'content' => $validated['content'],
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('projects.show', ['productionInitiation' => $productionInitiation, 'tab' => 'updates'])
            ->with('success', 'Project update added successfully.');
    }

    public function storeQuickUpdate(Request $request): RedirectResponse
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
            ->first(function (ProductionInitiation $project) {
                return $this->isDevelopmentProject($project);
            });

        abort_unless($productionInitiation, 404);

        $productionInitiation->projectUpdates()->create([
            'type' => $validated['type'],
            'content' => $validated['content'],
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project update added successfully.');
    }

    private function visibleProjectsQuery(User $user): Builder
    {
        $query = ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved']);

        if ($this->shouldLimitToAssignedProjects($user)) {
            $query
                ->where('project_allocation_status', 'allocated')
                ->whereJsonContains('project_allocated_tl_user_ids', $user->id);
        } elseif ($this->shouldLimitToEmployeeProjects($user)) {
            $query
                ->where('project_allocation_status', 'allocated')
                ->whereJsonContains('project_allocated_employee_user_ids', $user->id);
        } else {
            $query->whereIn('project_allocation_status', ['allocation_pending', 'allocated']);
        }

        return $query->latest('production_approval_reviewed_at');
    }

    private function timesheetProjectsQuery(User $user): Builder
    {
        return ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->where('project_allocation_status', 'allocated')
            ->whereJsonContains('project_allocated_employee_user_ids', $user->id)
            ->latest('production_approval_reviewed_at');
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

        if (! $baseDate) {
            return null;
        }

        return Carbon::parse($baseDate)->addDays((int) $project->total_working_days);
    }

    private function dashboardProjectDate(ProductionInitiation $project, User $user): ?Carbon
    {
        if ($this->shouldLimitToEmployeeProjects($user)) {
            return $project->employee_allocated_at
                ? Carbon::parse($project->employee_allocated_at)
                : null;
        }

        if ($this->shouldLimitToAssignedProjects($user)) {
            return $project->current_team_allocated_at
                ? Carbon::parse($project->current_team_allocated_at)
                : null;
        }

        return $project->project_allocated_at
            ? Carbon::parse($project->project_allocated_at)
            : ($project->production_approval_reviewed_at
                ? Carbon::parse($project->production_approval_reviewed_at)
                : null);
    }

    private function filterDashboardProjects(Collection $projects, array $filters, User $user): Collection
    {
        $dateFrom = $this->parseFilterDate($filters['date_from'] ?? '');
        $dateTo = $this->parseFilterDate($filters['date_to'] ?? '')?->endOfDay();
        $selectedProjectId = (int) ($filters['project_id'] ?? 0);
        $selectedTeamMemberId = (int) ($filters['team_member_id'] ?? 0);

        return $projects
            ->filter(function (ProductionInitiation $project) use ($dateFrom, $dateTo, $selectedProjectId, $selectedTeamMemberId, $user) {
                if ($selectedProjectId > 0 && (int) $project->id !== $selectedProjectId) {
                    return false;
                }

                if ($dateFrom || $dateTo) {
                    $dashboardDate = $project->dashboard_date;

                    if (! $dashboardDate) {
                        return false;
                    }

                    if ($dateFrom && $dashboardDate->lt($dateFrom)) {
                        return false;
                    }

                    if ($dateTo && $dashboardDate->gt($dateTo)) {
                        return false;
                    }
                }

                if ($selectedTeamMemberId > 0 && $this->shouldAllowDashboardUserFilter($user)) {
                    $employeeIds = collect(Arr::wrap($project->project_allocated_employee_user_ids))
                        ->map(fn ($id) => (int) $id);

                    if (! $employeeIds->contains($selectedTeamMemberId)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    private function filterEmployeeProjects(Collection $projects, array $filters): Collection
    {
        $deliveryFrom = $this->parseFilterDate($filters['delivery_from'] ?? '');
        $deliveryTo = $this->parseFilterDate($filters['delivery_to'] ?? '');

        return $projects
            ->filter(function (ProductionInitiation $project) use ($filters, $deliveryFrom, $deliveryTo) {
                $search = Str::lower($filters['search'] ?? '');

                if ($search !== '') {
                    $haystack = Str::lower(implode(' ', [
                        $project->product_name,
                        $project->client_name,
                        $project->company_name,
                        $project->lead?->contact_name,
                        $project->lead?->company_name,
                        $project->lead?->mobile_number,
                    ]));

                    if (! Str::contains($haystack, $search)) {
                        return false;
                    }
                }

                if (($filters['project_category'] ?? '') !== '' && $project->product?->category?->name !== $filters['project_category']) {
                    return false;
                }

                $deliveryDate = $project->project_delivery_date;

                if ($deliveryFrom) {
                    if (! $deliveryDate || $deliveryDate->lt($deliveryFrom)) {
                        return false;
                    }
                }

                if ($deliveryTo) {
                    if (! $deliveryDate || $deliveryDate->gt($deliveryTo)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    private function parseFilterDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dashboardTeamMembers(User $user, Collection $projects): Collection
    {
        if (! $this->shouldAllowDashboardUserFilter($user)) {
            return collect();
        }

        $employeeIds = $projects
            ->flatMap(fn (ProductionInitiation $project) => Arr::wrap($project->project_allocated_employee_user_ids))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $this->usersFromIds($employeeIds);
    }

    private function shouldAllowDashboardUserFilter(User $user): bool
    {
        return $this->shouldLimitToAssignedProjects($user);
    }

    private function dashboardTimesheetSummary(User $user, Collection $projects, array $filters): array
    {
        $projectIds = $projects->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($projectIds->isEmpty()) {
            return [
                'entries_count' => 0,
                'submitted_today' => 0,
                'contributors_count' => 0,
                'pending_delivery_count' => 0,
                'entries' => collect(),
            ];
        }

        $query = ProjectTimesheet::query()
            ->with([
                'user:id,name',
                'project' => fn ($builder) => $builder->with($this->projectRelations()),
            ])
            ->whereIn('production_initiation_id', $projectIds->all());

        if ($this->shouldLimitToEmployeeProjects($user)) {
            $query->where('user_id', $user->id);
        }

        $dateFrom = $this->parseFilterDate($filters['date_from'] ?? '');
        $dateTo = $this->parseFilterDate($filters['date_to'] ?? '')?->endOfDay();

        if ($dateFrom) {
            $query->whereDate('timesheet_date', '>=', $dateFrom->toDateString());
        }

        if ($dateTo) {
            $query->whereDate('timesheet_date', '<=', $dateTo->toDateString());
        }

        $selectedTeamMemberId = (int) ($filters['team_member_id'] ?? 0);

        if ($selectedTeamMemberId > 0 && $this->shouldAllowDashboardUserFilter($user)) {
            $query->where('user_id', $selectedTeamMemberId);
        }

        $entries = $query
            ->latest('timesheet_date')
            ->latest('created_at')
            ->get();

        return [
            'entries_count' => $entries->count(),
            'submitted_today' => $entries->filter(fn (ProjectTimesheet $entry) => optional($entry->timesheet_date)?->isToday())->count(),
            'contributors_count' => $entries->pluck('user_id')->filter()->unique()->count(),
            'pending_delivery_count' => $entries->filter(function (ProjectTimesheet $entry) {
                return ! $entry->project_delivery_date || $entry->project_delivery_date->isFuture();
            })->count(),
            'entries' => $entries->take(8),
        ];
    }

    private function currentMonthDeliveryProjects(Collection $projects): Collection
    {
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        return $projects
            ->filter(function (ProductionInitiation $project) use ($monthStart, $monthEnd) {
                return $project->project_delivery_date
                    && $project->project_delivery_date->between($monthStart, $monthEnd);
            })
            ->map(function (ProductionInitiation $project) {
                $employeeNames = $this->usersFromIds(Arr::wrap($project->project_allocated_employee_user_ids))
                    ->pluck('name')
                    ->filter()
                    ->values();
                $tlNames = $this->usersFromIds(Arr::wrap($project->project_allocated_tl_user_ids))
                    ->pluck('name')
                    ->filter()
                    ->values();

                $project->allocated_person_label = $employeeNames->isNotEmpty()
                    ? $employeeNames->implode(', ')
                    : ($tlNames->isNotEmpty() ? $tlNames->implode(', ') : 'Not allocated');

                return $project;
            })
            ->sortBy('project_delivery_date')
            ->values();
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

    private function decorateProjectForUser(ProductionInitiation $productionInitiation, User $user): ProductionInitiation
    {
        if (! $this->shouldLimitToAssignedProjects($user)) {
            return $productionInitiation;
        }

        $currentAllocation = $this->tlEmployeeAllocationForUser($productionInitiation, $user);

        $productionInitiation->current_team_status = $currentAllocation['status'];
        $productionInitiation->current_team_allocated_at = $currentAllocation['allocated_at']
            ? Carbon::parse($currentAllocation['allocated_at'])
            : null;
        $productionInitiation->current_team_allocated_by = $currentAllocation['allocated_by'];
        $productionInitiation->current_team_allocated_by_name = $this->userNameFromId($currentAllocation['allocated_by']);
        $productionInitiation->current_team_employee_user_ids = $currentAllocation['employee_user_ids'];

        return $productionInitiation;
    }

    private function ensureProjectIsVisibleToUser(ProductionInitiation $productionInitiation, User $user): void
    {
        $productionInitiation->loadMissing($this->projectRelations());

        abort_unless($this->resolveBucketForUser($productionInitiation, $user) !== null, 404);

        if ($this->shouldLimitToAssignedProjects($user)) {
            abort_unless($this->isAssignedTlForProject($productionInitiation, $user), 403);
        }

        if ($this->shouldLimitToEmployeeProjects($user)) {
            abort_unless($this->isAssignedEmployeeForProject($productionInitiation, $user), 403);
        }
    }

    private function tlEmployeeAllocations(ProductionInitiation $productionInitiation): Collection
    {
        return collect(Arr::wrap($productionInitiation->tl_employee_allocations))
            ->mapWithKeys(function ($allocation, $tlUserId) {
                if (is_array($allocation) && array_key_exists('tl_user_id', $allocation)) {
                    $tlUserId = (int) $allocation['tl_user_id'];
                } else {
                    $tlUserId = (int) $tlUserId;
                    $allocation = is_array($allocation) ? $allocation : [];
                    $allocation['tl_user_id'] = $tlUserId;
                }

                if ($tlUserId <= 0) {
                    return [];
                }

                return [
                    (string) $tlUserId => $this->normalizeTlEmployeeAllocation($allocation),
                ];
            });
    }

    private function tlEmployeeAllocationForUser(ProductionInitiation $productionInitiation, User $user): array
    {
        $allocation = $this->tlEmployeeAllocations($productionInitiation)->get((string) $user->id, [
            'tl_user_id' => $user->id,
            'status' => 'allocation_pending',
            'allocated_at' => null,
            'allocated_by' => null,
            'employee_user_ids' => [],
        ]);

        return $this->normalizeTlEmployeeAllocation($allocation);
    }

    private function tlEmployeeAllocationStatusForUser(ProductionInitiation $productionInitiation, User $user): string
    {
        return (string) ($this->tlEmployeeAllocationForUser($productionInitiation, $user)['status'] ?? 'allocation_pending');
    }

    private function normalizeTlEmployeeAllocation(array $allocation): array
    {
        $employeeUserIds = collect(Arr::wrap($allocation['employee_user_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'tl_user_id' => (int) ($allocation['tl_user_id'] ?? 0),
            'status' => in_array($allocation['status'] ?? null, ['allocated', 'allocation_pending'], true)
                ? $allocation['status']
                : ($employeeUserIds !== [] ? 'allocated' : 'allocation_pending'),
            'allocated_at' => ! empty($allocation['allocated_at'])
                ? Carbon::parse($allocation['allocated_at'])->toDateTimeString()
                : null,
            'allocated_by' => ! empty($allocation['allocated_by']) ? (int) $allocation['allocated_by'] : null,
            'employee_user_ids' => $employeeUserIds,
        ];
    }

    private function summarizeTlEmployeeAllocations(array $tlAllocations): array
    {
        $normalizedAllocations = collect($tlAllocations)
            ->map(fn ($allocation) => $this->normalizeTlEmployeeAllocation((array) $allocation))
            ->filter(fn (array $allocation) => $allocation['tl_user_id'] > 0)
            ->values();

        $allEmployeeIds = $normalizedAllocations
            ->flatMap(fn (array $allocation) => $allocation['employee_user_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $latestAllocation = $normalizedAllocations
            ->filter(fn (array $allocation) => ! empty($allocation['allocated_at']))
            ->sortByDesc('allocated_at')
            ->first();

        $allTlAllocated = $normalizedAllocations->isNotEmpty()
            && $normalizedAllocations->every(fn (array $allocation) => $allocation['status'] === 'allocated');

        return [
            'employee_allocation_status' => $allTlAllocated ? 'allocated' : 'allocation_pending',
            'employee_allocated_at' => $latestAllocation['allocated_at'] ?? null,
            'employee_allocated_by' => $latestAllocation['allocated_by'] ?? null,
            'project_allocated_employee_user_ids' => $allEmployeeIds === [] ? null : $allEmployeeIds,
        ];
    }

    private function resolveBucketForUser(ProductionInitiation $productionInitiation, User $user): ?string
    {
        if ($this->shouldLimitToAssignedProjects($user)) {
            return $this->resolveBucket((string) ($productionInitiation->current_team_status ?? $this->tlEmployeeAllocationStatusForUser($productionInitiation, $user)));
        }

        if ($this->shouldLimitToEmployeeProjects($user)) {
            return $this->isAssignedEmployeeForProject($productionInitiation, $user)
                ? 'allocated'
                : null;
        }

        return $this->resolveBucket((string) $productionInitiation->project_allocation_status);
    }

    private function resolveBucket(string $status): ?string
    {
        return match (strtolower(trim($status))) {
            'allocation_pending', 'pending' => 'allocation_pending',
            'allocated' => 'allocated',
            default => null,
        };
    }

    private function canAllocateTl(ProductionInitiation $productionInitiation, User $user): bool
    {
        if ($this->shouldLimitToAssignedProjects($user)) {
            return false;
        }

        return in_array(strtolower(trim((string) $productionInitiation->production_approval_status)), ['approval', 'approved'], true)
            && strtolower(trim((string) $productionInitiation->project_allocation_status)) === 'allocation_pending';
    }

    private function canAllocateEmployees(ProductionInitiation $productionInitiation, User $user): bool
    {
        return $this->isAssignedTlForProject($productionInitiation, $user)
            && strtolower(trim((string) $productionInitiation->project_allocation_status)) === 'allocated'
            && in_array(strtolower(trim((string) $this->tlEmployeeAllocationStatusForUser($productionInitiation, $user))), ['allocation_pending', 'allocated'], true);
    }

    private function canManageProjectSchedule(ProductionInitiation $productionInitiation, User $user): bool
    {
        if ($user->hasAdminLikeRole() || $this->hasProjectCoordinatorRole($user)) {
            return true;
        }

        return $this->isAssignedTlForProject($productionInitiation, $user);
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
            && $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
                return $this->roleKey((string) ($role->department?->name ?? '')) === 'development';
            });
    }

    private function isDevelopmentProject(ProductionInitiation $productionInitiation): bool
    {
        return $this->roleKey((string) ($productionInitiation->department?->name ?? '')) === 'development';
    }

    private function hasProjectCoordinatorRole(User $user): bool
    {
        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
            $keys = [
                $this->roleKey((string) $role->name),
                $this->roleKey((string) ($role->display_name ?? '')),
            ];

            return collect($keys)->intersect([
                'project_coordinator',
                'project_coordination',
                'pc',
                'development_project_coordinator',
            ])->isNotEmpty();
        });
    }

    private function isUserTl(User $user): bool
    {
        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
            return $this->isTlRole((string) $role->name)
                || $this->isTlRole((string) ($role->display_name ?? ''));
        });
    }

    private function isAssignedTlForProject(ProductionInitiation $productionInitiation, User $user): bool
    {
        return collect(Arr::wrap($productionInitiation->project_allocated_tl_user_ids))
            ->map(fn ($id) => (int) $id)
            ->contains((int) $user->id);
    }

    private function isAssignedEmployeeForProject(ProductionInitiation $productionInitiation, User $user): bool
    {
        return collect(Arr::wrap($productionInitiation->project_allocated_employee_user_ids))
            ->map(fn ($id) => (int) $id)
            ->contains((int) $user->id);
    }

    private function availableTlUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->with(['roles.department'])
            ->get()
            ->filter(function (User $user) {
                return $user->resolvedRoles(withDepartment: true)->contains(function ($role) {
                    return $this->isTlRole((string) $role->name)
                        || $this->isTlRole((string) ($role->display_name ?? ''));
                });
            })
            ->map(fn (User $user) => $this->mapUserSummary($user))
            ->sortBy('name')
            ->values();
    }

    private function availableTeamMembers(User $user): Collection
    {
        $managedMembers = $user->managedUsers()
            ->where('users.is_active', true)
            ->with(['roles.department'])
            ->get()
            ->map(fn (User $teamMember) => $this->mapUserSummary($teamMember));

        return $managedMembers
            ->push($this->mapUserSummary($user))
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    private function allocatedTlUsers(ProductionInitiation $productionInitiation): Collection
    {
        return $this->usersFromIds(
            Arr::wrap($productionInitiation->project_allocated_tl_user_ids)
        );
    }

    private function tlAllocationSummaries(ProductionInitiation $productionInitiation): Collection
    {
        $allocatedTlUsers = $this->allocatedTlUsers($productionInitiation)->keyBy('id');
        $tlAllocations = $this->tlEmployeeAllocations($productionInitiation);

        return collect(Arr::wrap($productionInitiation->project_allocated_tl_user_ids))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->map(function (int $tlUserId) use ($allocatedTlUsers, $tlAllocations) {
                $tlUser = $allocatedTlUsers->get($tlUserId);
                $allocation = $this->normalizeTlEmployeeAllocation(
                    $tlAllocations->get((string) $tlUserId, ['tl_user_id' => $tlUserId])
                );
                $employees = $this->usersFromIds($allocation['employee_user_ids']);

                return (object) [
                    'tl_user_id' => $tlUserId,
                    'tl_user' => $tlUser,
                    'status' => (string) ($allocation['status'] ?? 'allocation_pending'),
                    'allocated_at' => ! empty($allocation['allocated_at'])
                        ? Carbon::parse($allocation['allocated_at'])
                        : null,
                    'allocated_by_name' => $this->userNameFromId($allocation['allocated_by'] ?? null) ?: 'Pending',
                    'employees' => $employees,
                ];
            })
            ->values();
    }

    private function allocatedEmployees(ProductionInitiation $productionInitiation, ?User $tlUser = null): Collection
    {
        $employeeUserIds = $tlUser
            ? ($this->tlEmployeeAllocations($productionInitiation)->get((string) $tlUser->id)['employee_user_ids'] ?? [])
            : Arr::wrap($productionInitiation->project_allocated_employee_user_ids);

        return $this->usersFromIds(
            Arr::wrap($employeeUserIds)
        );
    }

    private function usersFromIds(array $ids): Collection
    {
        $selectedIds = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $selectedIds->all())
            ->where('is_active', true)
            ->with(['roles.department'])
            ->get()
            ->map(fn (User $user) => $this->mapUserSummary($user))
            ->sortBy('name')
            ->values();
    }

    private function userNameFromId(?int $userId): ?string
    {
        static $userNameCache = [];

        $userId = $userId ? (int) $userId : 0;

        if ($userId <= 0) {
            return null;
        }

        if (! array_key_exists($userId, $userNameCache)) {
            $userNameCache[$userId] = User::query()
                ->whereKey($userId)
                ->value('name');
        }

        return $userNameCache[$userId];
    }

    private function mapUserSummary(User $user): object
    {
        $roles = $user->resolvedRoles(withDepartment: true);
        $departments = $roles
            ->map(fn ($role) => $role->department?->name)
            ->filter()
            ->unique()
            ->values();

        return (object) [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_names' => $roles
                ->map(fn ($role) => $role->display_name ?: Str::of((string) $role->name)->afterLast('__')->replace('_', ' ')->title()->value())
                ->unique()
                ->values()
                ->all(),
            'departments' => $departments->all(),
            'department_label' => $departments->isNotEmpty() ? $departments->implode(', ') : 'All Departments',
        ];
    }

    private function roleKey(string $value): string
    {
        $value = Str::contains($value, '__') ? Str::afterLast($value, '__') : $value;

        return Str::of($value)
            ->lower()
            ->replace('&', 'and')
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }

    private function isTlRole(string $value): bool
    {
        $key = $this->roleKey($value);

        if (in_array($key, self::TL_ROLE_KEYS, true)) {
            return true;
        }

        return Str::contains($key, [
            'tl',
            'team_lead',
            'teamleader',
            'team_leader',
            'manager',
            'lead',
        ]);
    }
}
