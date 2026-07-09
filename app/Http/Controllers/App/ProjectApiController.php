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
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectApiController extends Controller
{
    private const TL_ROLE_KEYS = [
        'tl', 'team_lead', 'team_leader', 'teamlead', 'manager',
        'project_manager', 'web_team_leader', 'mobile_app_team_leader',
        'design_team_lead', 'team_lead_digital_marketing', 'software_team_leader',
    ];

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

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'                        => $stats,
                'dashboard_filters'            => $dashboardFilters,
                'project_options'              => $projects->sortBy('product_name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->map(fn ($p) => $this->serializeProjectSummary($p)),
                'team_member_options'          => $this->dashboardTeamMembers($user, $projects),
                'current_month_delivery'       => $currentMonthDelivery->map(fn ($p) => $this->serializeProjectSummary($p))->values(),
                'recent_projects'              => $filteredProjects->take(10)->map(fn ($p) => $this->serializeProjectSummary($p))->values(),
                'timesheet_summary'            => [
                    'entries_count'         => $timesheetSummary['entries_count'],
                    'submitted_today'       => $timesheetSummary['submitted_today'],
                    'contributors_count'    => $timesheetSummary['contributors_count'],
                    'pending_delivery_count'=> $timesheetSummary['pending_delivery_count'],
                    'entries'               => $timesheetSummary['entries']->map(fn ($ts) => $this->serializeTimesheet($ts))->values(),
                ],
                'is_tl_scoped_view'            => $this->shouldLimitToAssignedProjects($user),
                'is_contributor_scoped_view'   => $this->shouldLimitToEmployeeProjects($user),
                'can_quick_add_production_update' => $this->canQuickAddProductionUpdate($user),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /mobile/projects
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $search  = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('project_category', ''));
        $bucket  = trim((string) $request->query('bucket', 'allocation_pending'));

        $isContributorScopedView = $this->shouldLimitToEmployeeProjects($user);
        $isTlScopedView          = $this->shouldLimitToAssignedProjects($user);

        if ($isContributorScopedView) {
            $projects = $this->visibleProjectsQuery($user)->get()
                ->map(function (ProductionInitiation $p) {
                    $p->project_delivery_date = $this->projectDeliveryDate($p);
                    return $p;
                });

            $filters = [
                'search'           => $search,
                'project_category' => $category,
                'delivery_from'    => trim((string) $request->query('delivery_from', '')),
                'delivery_to'      => trim((string) $request->query('delivery_to', '')),
            ];

            $filtered = $this->filterEmployeeProjects($projects, $filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'view_type'   => 'contributor',
                    'projects'    => $filtered->map(fn ($p) => $this->serializeProjectSummary($p))->values(),
                    'total'       => $filtered->count(),
                    'categories'  => $projects->map(fn ($p) => $p->product?->category?->name)
                        ->filter()->unique()->sort()->values(),
                    'filters'     => $filters,
                ],
            ]);
        }

        // TL / Admin view — bucket-based
        $initiations = $this->visibleProjectsQuery($user)->get()
            ->map(fn ($i) => $this->decorateProjectForUser($i, $user));

        $buckets = ['allocation_pending' => [], 'allocated' => []];
        foreach ($initiations as $initiation) {
            $b = $this->resolveBucketForUser($initiation, $user);
            if ($b) {
                $buckets[$b][] = $initiation;
            }
        }

        $validBucket  = array_key_exists($bucket, $buckets) ? $bucket : 'allocation_pending';
        $bucketCounts = [
            'allocation_pending' => count($buckets['allocation_pending']),
            'allocated'          => count($buckets['allocated']),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'view_type'      => $isTlScopedView ? 'tl' : 'admin',
                'selected_bucket'=> $validBucket,
                'bucket_counts'  => $bucketCounts,
                'projects'       => collect($buckets[$validBucket])
                    ->map(fn ($p) => $this->serializeProjectSummary($p))
                    ->values(),
                'total'          => count($buckets[$validBucket]),
            ],
        ]);
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

        if (in_array($selectedUpdateType, ['production_update', 'meeting_update', 'weekly_update'], true)) {
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

        return response()->json([
            'success' => true,
            'data' => [
                'project'              => $this->serializeProjectDetail($productionInitiation, $projectDeliveryDate),
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
                'project_updates'      => $updates->map(fn ($u) => $this->serializeUpdate($u))->values(),
                'update_counts'        => $projectUpdateCounts,
                'selected_update_type' => $selectedUpdateType,
                'selected_update_date' => $selectedUpdateDate,
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
            ->intersect(collect($validated['tl_user_ids'])->map(fn ($id) => (int) $id))
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
                        'employee_user_ids'=> $existing['employee_user_ids'] ?? [],
                    ]),
                ];
            })
            ->all();

        $productionInitiation->update([
            'project_allocation_status'    => 'allocated',
            'project_allocated_at'         => Carbon::now(),
            'project_allocated_by'         => $user->id,
            'project_allocated_tl_user_ids'=> $selectedTlIds,
            'tl_employee_allocations'      => $tlAllocations,
            ...$this->summarizeTlEmployeeAllocations($tlAllocations),
        ]);
        $this->syncProductionCountReportAllocation($productionInitiation->fresh(), $user->id);

        return response()->json(['success' => true, 'message' => 'Project allocated to TL successfully.']);
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
            ->intersect(collect($validated['employee_user_ids'])->map(fn ($id) => (int) $id))
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
            'employee_user_ids'=> $selectedEmployeeIds,
        ]));

        $productionInitiation->update([
            'tl_employee_allocations' => $tlAllocations->all(),
            ...$this->summarizeTlEmployeeAllocations($tlAllocations->all()),
        ]);
        $this->syncProductionCountReportAllocation($productionInitiation->fresh(), $user->id);

        return response()->json(['success' => true, 'message' => 'Employees allocated successfully.']);
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
            'project_execution_status' => ['required', 'in:ontrack,hold,delivered'],
        ]);

        $productionInitiation->update([
            'project_delivery_date'    => $validated['project_delivery_date'] ?: null,
            'project_execution_status' => $validated['project_execution_status'],
        ]);

        return response()->json(['success' => true, 'message' => 'Schedule updated successfully.']);
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

        $assignedProjects = $this->timesheetProjectsQuery($user)
            ->get()
            ->map(function (ProductionInitiation $p) {
                $p->timesheet_delivery_date = $this->projectDeliveryDate($p)?->toDateString();
                return $p;
            });

        $filters = [
            'filter_date'       => trim((string) $request->query('filter_date', '')),
            'filter_project_id' => trim((string) $request->query('filter_project_id', '')),
            'filter_status'     => trim((string) $request->query('filter_status', '')),
        ];

        $timesheets = ProjectTimesheet::query()
            ->with(['project' => fn ($q) => $q->with($this->projectRelations())])
            ->where('user_id', $user->id)
            ->when($filters['filter_date'] !== '', function ($q) use ($filters) {
                try {
                    $q->whereDate('timesheet_date', Carbon::parse($filters['filter_date'])->toDateString());
                } catch (\Throwable) {}
            })
            ->when($filters['filter_project_id'] !== '', function ($q) use ($filters) {
                $q->where('production_initiation_id', (int) $filters['filter_project_id']);
            })
            ->when($filters['filter_status'] === 'completed', function ($q) {
                $q->whereNotNull('project_delivery_date')
                  ->whereDate('project_delivery_date', '<=', Carbon::today()->toDateString());
            })
            ->when($filters['filter_status'] === 'pending', function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('project_delivery_date')
                       ->orWhereDate('project_delivery_date', '>', Carbon::today()->toDateString());
                });
            })
            ->latest('created_at')
            ->latest('timesheet_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'assigned_projects' => $assignedProjects->map(fn ($p) => [
                    'id'                       => $p->id,
                    'product_name'             => $p->product_name,
                    'company_name'             => $p->company_name,
                    'timesheet_delivery_date'  => $p->timesheet_delivery_date,
                ])->values(),
                'timesheets' => $timesheets->map(fn ($ts) => $this->serializeTimesheet($ts))->values(),
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
            'poster_count'             => ['nullable', 'integer', 'min:0', 'max:100000'],
            'video_count'              => ['nullable', 'integer', 'min:0', 'max:100000'],
            'day_closing_update'       => [
                'required', 'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $lines = collect(preg_split('/\R/', (string) $value))
                        ->map(fn ($l) => trim($l))->filter();
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

        $exists = ProjectTimesheet::query()
            ->where('production_initiation_id', $project->id)
            ->where('user_id', $user->id)
            ->whereDate('timesheet_date', $timesheetDate)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Timesheet already exists for this project on the selected date.',
                'errors'  => ['production_initiation_id' => ['Timesheet already exists for this project on the selected date.']],
            ], 422);
        }

        $timesheet = ProjectTimesheet::create([
            'company_id'               => $project->company_id,
            'production_initiation_id' => $project->id,
            'user_id'                  => $user->id,
            'timesheet_date'           => $timesheetDate,
            'project_delivery_date'    => $this->projectDeliveryDate($project)?->toDateString(),
            'poster_count'             => (int) ($validated['poster_count'] ?? 0),
            'video_count'              => (int) ($validated['video_count'] ?? 0),
            'day_closing_update'       => $validated['day_closing_update'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet saved successfully.',
            'data'    => $this->serializeTimesheet($timesheet),
        ], 201);
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

    private function serializeProjectDetail(ProductionInitiation $p, ?Carbon $deliveryDate): array
    {
        return array_merge($this->serializeProjectSummary($p), [
            'lead_id'             => $p->lead?->id,
            'lead_display_id'     => $p->lead ? 'LD-' . str_pad($p->lead->id, 4, '0', STR_PAD_LEFT) : null,
            'email'               => $p->lead?->email,
            'mobile'              => $p->lead?->mobile_number,
            'sales_person'        => $p->lead?->assignedTo?->name,
            'sales_email'         => $p->lead?->assignedTo?->email,
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
            'ovp_approved_by'     => $p->reviewedBy?->name,
            'ovp_approved_on'     => $p->ovp_reviewed_at ?? null,
        ]);
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
            'timesheet_date'        => $ts->timesheet_date?->toDateString(),
            'project_delivery_date' => $ts->project_delivery_date?->toDateString(),
            'poster_count'          => (int) $ts->poster_count,
            'video_count'           => (int) $ts->video_count,
            'day_closing_update'    => $ts->day_closing_update,
            'submitted_at'          => $ts->created_at?->toDateTimeString(),
            'is_completed'          => $ts->project_delivery_date
                && $ts->project_delivery_date->lte(Carbon::today()),
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
            'employees'    => collect($s->employees)->map(fn ($e) => [
                'id'   => $e->id,
                'name' => $e->name,
            ])->values(),
        ];
    })->values()->all();
}

    // ─────────────────────────────────────────────────────────────────────────
    //  All private helpers copied verbatim from web ProjectController
    // ─────────────────────────────────────────────────────────────────────────

    private function visibleProjectsQuery(User $user): Builder
    {
        $query = ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved']);

        if ($this->shouldLimitToAssignedProjects($user)) {
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
        return ProductionInitiation::query()
            ->with($this->projectRelations())
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->where('project_allocation_status', 'allocated')
            ->whereJsonContains('project_allocated_employee_user_ids', $user->id)
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
                $empIds = collect(Arr::wrap($project->project_allocated_employee_user_ids))->map(fn ($id) => (int) $id);
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

    private function filterEmployeeProjects(Collection $projects, array $filters): Collection
    {
        $deliveryFrom = $this->parseFilterDate($filters['delivery_from'] ?? '');
        $deliveryTo   = $this->parseFilterDate($filters['delivery_to'] ?? '');

        return $projects->filter(function (ProductionInitiation $project) use ($filters, $deliveryFrom, $deliveryTo) {
            $search = Str::lower($filters['search'] ?? '');
            if ($search !== '') {
                $haystack = Str::lower(implode(' ', [
                    $project->product_name, $project->client_name,
                    $project->company_name, $project->lead?->contact_name,
                    $project->lead?->company_name, $project->lead?->mobile_number,
                ]));
                if (! Str::contains($haystack, $search)) return false;
            }

            if (($filters['project_category'] ?? '') !== '' && $project->product?->category?->name !== $filters['project_category']) return false;

            $d = $project->project_delivery_date;
            if ($deliveryFrom && (! $d || $d->lt($deliveryFrom))) return false;
            if ($deliveryTo   && (! $d || $d->gt($deliveryTo)))   return false;

            return true;
        })->values();
    }

    private function parseFilterDate(string $value): ?Carbon
    {
        if (trim($value) === '') return null;
        try { return Carbon::parse($value)->startOfDay(); } catch (\Throwable) { return null; }
    }

    private function dashboardTeamMembers(User $user, Collection $projects): Collection
    {
        if (! $this->shouldAllowDashboardUserFilter($user)) return collect();

        $empIds = $projects
            ->flatMap(fn ($p) => Arr::wrap($p->project_allocated_employee_user_ids))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        return $this->usersFromIds($empIds);
    }

    private function shouldAllowDashboardUserFilter(User $user): bool
    {
        return $this->shouldLimitToAssignedProjects($user);
    }

    private function dashboardTimesheetSummary(User $user, Collection $projects, array $filters): array
    {
        $projectIds = $projects->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();

        if ($projectIds->isEmpty()) {
            return ['entries_count' => 0, 'submitted_today' => 0, 'contributors_count' => 0, 'pending_delivery_count' => 0, 'entries' => collect()];
        }

        $query = ProjectTimesheet::query()
            ->with(['user:id,name', 'project' => fn ($b) => $b->with($this->projectRelations())])
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

        return [
            'entries_count'          => $entries->count(),
            'submitted_today'        => $entries->filter(fn ($e) => optional($e->timesheet_date)?->isToday())->count(),
            'contributors_count'     => $entries->pluck('user_id')->filter()->unique()->count(),
            'pending_delivery_count' => $entries->filter(fn ($e) => ! $e->project_delivery_date || $e->project_delivery_date->isFuture())->count(),
            'entries'                => $entries->take(8),
        ];
    }

    private function currentMonthDeliveryProjects(Collection $projects): Collection
    {
        $start = Carbon::today()->startOfMonth();
        $end   = Carbon::today()->endOfMonth();

        return $projects->filter(fn ($p) => $p->project_delivery_date && $p->project_delivery_date->between($start, $end))
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
                'tl_user_id' => $user->id, 'status' => 'allocation_pending',
                'allocated_at' => null, 'allocated_by' => null, 'employee_user_ids' => [],
            ])
        );
    }

    private function tlEmployeeAllocationStatusForUser(ProductionInitiation $p, User $user): string
    {
        return (string) ($this->tlEmployeeAllocationForUser($p, $user)['status'] ?? 'allocation_pending');
    }

    private function normalizeTlEmployeeAllocation(array $allocation): array
    {
        $empIds = collect(Arr::wrap($allocation['employee_user_ids'] ?? []))->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        return [
            'tl_user_id'       => (int) ($allocation['tl_user_id'] ?? 0),
            'status'           => in_array($allocation['status'] ?? null, ['allocated', 'allocation_pending'], true)
                ? $allocation['status']
                : ($empIds !== [] ? 'allocated' : 'allocation_pending'),
            'allocated_at'     => ! empty($allocation['allocated_at']) ? Carbon::parse($allocation['allocated_at'])->toDateTimeString() : null,
            'allocated_by'     => ! empty($allocation['allocated_by']) ? (int) $allocation['allocated_by'] : null,
            'employee_user_ids'=> $empIds,
        ];
    }

    private function summarizeTlEmployeeAllocations(array $tlAllocations): array
    {
        $normalized = collect($tlAllocations)
            ->map(fn ($a) => $this->normalizeTlEmployeeAllocation((array) $a))
            ->filter(fn ($a) => $a['tl_user_id'] > 0)->values();

        $allEmpIds     = $normalized->flatMap(fn ($a) => $a['employee_user_ids'])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $latestAlloc   = $normalized->filter(fn ($a) => ! empty($a['allocated_at']))->sortByDesc('allocated_at')->first();
        $allTlAllocated = $normalized->isNotEmpty() && $normalized->every(fn ($a) => $a['status'] === 'allocated');

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
        if ($this->shouldLimitToAssignedProjects($user)) return false;

        $isApproved = in_array(strtolower(trim((string) $p->production_approval_status)), ['approval', 'approved'], true);
        if (! $isApproved) {
            return false;
        }

        $isPending = strtolower(trim((string) $p->project_allocation_status)) === 'allocation_pending';
        if ($isPending) {
            return true;
        }

        return $user->hasAdminLikeRole() || $this->hasProjectCoordinatorRole($user);
    }

    private function canAllocateEmployees(ProductionInitiation $p, User $user): bool
    {
        return $this->isAssignedTlForProject($p, $user)
            && strtolower(trim((string) $p->project_allocation_status)) === 'allocated'
            && in_array(strtolower(trim($this->tlEmployeeAllocationStatusForUser($p, $user))), ['allocation_pending', 'allocated'], true);
    }

    private function canManageProjectSchedule(ProductionInitiation $p, User $user): bool
    {
        return $user->hasAdminLikeRole() || $this->hasProjectCoordinatorRole($user);
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
            && $user->resolvedRoles(withDepartment: true)->contains(fn ($role) => $this->roleKey((string) ($role->department?->name ?? '')) === 'development');
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
        return $user->resolvedRoles(withDepartment: true)->contains(fn ($role) =>
            $this->isTlRole((string) $role->name) || $this->isTlRole((string) ($role->display_name ?? ''))
        );
    }

    private function isAssignedTlForProject(ProductionInitiation $p, User $user): bool
    {
        return collect(Arr::wrap($p->project_allocated_tl_user_ids))->map(fn ($id) => (int) $id)->contains((int) $user->id);
    }

    private function isAssignedEmployeeForProject(ProductionInitiation $p, User $user): bool
    {
        return collect(Arr::wrap($p->project_allocated_employee_user_ids))->map(fn ($id) => (int) $id)->contains((int) $user->id);
    }

    private function availableTlUsers(): Collection
    {
        return User::query()->where('is_active', true)->with(['roles.department'])->get()
            ->filter(fn ($u) => $u->resolvedRoles(withDepartment: true)->contains(fn ($role) =>
                $this->isTlRole((string) $role->name) || $this->isTlRole((string) ($role->display_name ?? ''))
            ))
            ->map(fn ($u) => $this->mapUserSummary($u))->sortBy('name')->values();
    }

    private function availableTeamMembers(User $user): Collection
    {
        return $user->managedUsers()->where('users.is_active', true)->with(['roles.department'])->get()
            ->map(fn ($m) => $this->mapUserSummary($m))
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
            ->map(fn ($id) => (int) $id)->filter()->unique()
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
        $selectedIds = collect($ids)->map(fn ($id) => (int) $id)->filter()->values();
        if ($selectedIds->isEmpty()) return collect();
        return User::query()->whereIn('id', $selectedIds->all())->where('is_active', true)
            ->with(['roles.department'])->get()
            ->map(fn ($u) => $this->mapUserSummary($u))->sortBy('name')->values();
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
        $departments = $roles->map(fn ($r) => $r->department?->name)->filter()->unique()->values();
        return (object) [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'role_names'   => $roles->map(fn ($r) => $r->display_name ?: Str::of((string) $r->name)->afterLast('__')->replace('_', ' ')->title()->value())->unique()->values()->all(),
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
}
