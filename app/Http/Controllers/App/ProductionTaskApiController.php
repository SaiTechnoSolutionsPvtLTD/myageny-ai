<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\ProductionInitiation;
use App\Models\ProductionTask;
use App\Models\ProjectTimesheet;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mobile mirror of the web's NEW Production Tasks flow
 * (App\Http\Controllers\ProductionTaskController — Projects > Tasks). This
 * is a brand new mobile module; there is no older mobile implementation to
 * be compatible with.
 *
 * Deliberate difference from web: web's index() loads every matching task
 * row into memory, groups them in PHP by (task_date, assigned_to), then
 * hand-builds a LengthAwarePaginator over the in-memory groups — the same
 * scaling problem already fixed for mobile OVP/Production Approval
 * elsewhere in this app. Here we paginate at the DB level over the distinct
 * (task_date, assigned_to) group keys first, and only fetch full task rows
 * (with relations) for the ≤$perPage groups that landed on the requested
 * page — see index() below.
 *
 * Deliberate addition beyond web: web computes 'status'/'can_delete'/
 * 'delete_url' per task for its "View Tasks" modal but never renders any
 * control for them — updateStatus()/destroy() exist on web with real
 * permission checks but have no UI trigger anywhere on the Tasks page.
 * Mobile exposes working status-change + delete controls using those same
 * permission rules, since the ticket calls for task actions to be
 * supported and the backend logic already exists.
 */
class ProductionTaskApiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  GET /mobile/projects/tasks/meta
    // ─────────────────────────────────────────────────────────────────────────
    public function meta(Request $request): JsonResponse
    {
        $user = auth()->user();
        $assignedProjects = $this->getAccessibleProjects($user);

        $uniqueLeads = $assignedProjects->groupBy('lead_id')->map(function ($projects) {
            $firstProj = $projects->first();
            $companyName = trim($firstProj->company_name ?: ($firstProj->lead?->company_name ?: ''));
            if (! $companyName) {
                $companyName = trim($firstProj->client_name ?: ($firstProj->lead?->contact_name ?: 'No Company'));
            }
            return [
                'lead_id' => $firstProj->lead_id,
                'lead_display_id' => $firstProj->lead_id ? 'LD-' . str_pad((string) $firstProj->lead_id, 4, '0', STR_PAD_LEFT) : null,
                'company_name' => $companyName ?: 'No Company',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'assigned_projects' => $assignedProjects->map(fn (ProductionInitiation $p) => [
                    'id' => $p->id,
                    'lead_id' => $p->lead_id,
                    'allocated_user_ids' => $p->allocated_user_ids ?? [],
                    'resolved_product_name' => $p->resolved_product_name ?? ($p->product_name ?: 'Product'),
                    'resolved_company_name' => $p->resolved_company_name ?? 'No Company',
                    'product_name' => $p->product_name ?: 'Product',
                    'timesheet_delivery_date' => $p->timesheet_delivery_date ?? null,
                ])->values(),
                'unique_leads' => $uniqueLeads,
                'mapped_team_members' => $this->getMappedTeamMembers($user)->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ])->values(),
                'today' => Carbon::today()->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /mobile/projects/tasks
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        $managedUsers = $user->managedUsers()->where('users.user_status', 'active')->get(['users.id']);
        $accessibleUserIds = $isAdminLike
            ? null
            : array_values(array_unique(array_merge([$user->id], $managedUsers->pluck('id')->all())));

        $filters = [
            'filter_date' => trim((string) $request->query('filter_date', '')),
            'filter_lead_id' => trim((string) $request->query('filter_lead_id', '')),
            'filter_project_id' => trim((string) $request->query('filter_project_id', '')),
            'filter_user_id' => trim((string) $request->query('filter_user_id', '')),
            'filter_status' => trim((string) $request->query('filter_status', '')),
        ];

        $applyFilters = function ($query) use ($filters, $isAdminLike, $accessibleUserIds, $user) {
            if (! $isAdminLike) {
                $query->where(function ($q) use ($accessibleUserIds, $user) {
                    $q->whereIn('assigned_to', $accessibleUserIds)
                      ->orWhere('created_by', $user->id);
                });
            }
            if ($filters['filter_date'] !== '') {
                try {
                    $query->whereDate('task_date', Carbon::parse($filters['filter_date'])->toDateString());
                } catch (\Throwable) {
                }
            }
            if ($filters['filter_lead_id'] !== '') {
                $query->where('lead_id', (int) $filters['filter_lead_id']);
            }
            if ($filters['filter_project_id'] !== '') {
                $query->where('production_initiation_id', (int) $filters['filter_project_id']);
            }
            if ($filters['filter_user_id'] !== '') {
                $query->where('assigned_to', (int) $filters['filter_user_id']);
            }
            if ($filters['filter_status'] !== '') {
                $query->where('status', $filters['filter_status']);
            }
            return $query;
        };

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;

        // 1) Count distinct (task_date, assigned_to) groups without loading
        //    task rows — this is the DB-level replacement for web's
        //    load-everything-then-groupBy approach.
        $groupKeysQuery = $applyFilters(
            ProductionTask::query()->select('task_date', 'assigned_to')->distinct()
        );
        $totalGroups = DB::query()->fromSub($groupKeysQuery, 'g')->count();

        // 2) Fetch only this page's group keys.
        $pageGroupKeys = $applyFilters(
            ProductionTask::query()->select('task_date', 'assigned_to')->distinct()
        )
            ->orderByDesc('task_date')
            ->orderBy('assigned_to')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $groups = [];
        if ($pageGroupKeys->isNotEmpty()) {
            // 3) Fetch full task rows, but only for the groups on this page.
            $tasks = ProductionTask::query()
                ->with(['creator:id,name', 'assignedUser:id,name,email', 'lead:id,company_name', 'project:id,product_name,company_name'])
                ->where(function ($q) use ($pageGroupKeys) {
                    foreach ($pageGroupKeys as $key) {
                        $dateStr = $key->task_date instanceof Carbon ? $key->task_date->toDateString() : Carbon::parse($key->task_date)->toDateString();
                        $q->orWhere(function ($sq) use ($dateStr, $key) {
                            $sq->whereDate('task_date', $dateStr)->where('assigned_to', $key->assigned_to);
                        });
                    }
                })
                ->latest('id')
                ->get();

            $groupedTasks = $tasks->groupBy(function (ProductionTask $t) {
                return ($t->task_date ? $t->task_date->toDateString() : 'no_date') . '_' . $t->assigned_to;
            });

            // Preserve the DB-decided ordering of pageGroupKeys rather than
            // whatever order groupBy() happens to yield.
            foreach ($pageGroupKeys as $key) {
                $dateStr = $key->task_date instanceof Carbon ? $key->task_date->toDateString() : Carbon::parse($key->task_date)->toDateString();
                $groupKey = $dateStr . '_' . $key->assigned_to;
                $groupTasks = $groupedTasks->get($groupKey, collect());
                if ($groupTasks->isEmpty()) {
                    continue;
                }
                $groups[] = $this->serializeGroup($groupTasks, $user);
            }
        }

        $paginator = new LengthAwarePaginator(
            $groups,
            $totalGroups,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $perPage,
                    'total' => $totalGroups,
                    'has_more' => $paginator->hasMorePages(),
                ],
                'filters' => $filters,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /mobile/projects/tasks
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'task_date' => ['required', 'date'],
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
            'tasks' => ['required', 'array', 'min:1'],
            'tasks.*.lead_id' => ['required', 'integer', 'exists:leads,id'],
            'tasks.*.production_initiation_id' => ['required', 'integer', 'exists:production_initiations,id'],
            'tasks.*.task_description' => ['required', 'string', 'min:1'],
        ], [
            'task_date.required' => 'The date field is mandatory.',
            'assigned_to_user_id.required' => 'Selecting a team member is mandatory.',
            'tasks.required' => 'Please add at least one task item.',
            'tasks.*.lead_id.required' => 'Lead is mandatory for all task rows.',
            'tasks.*.production_initiation_id.required' => 'Product / Project is mandatory for all task rows.',
            'tasks.*.task_description.required' => 'Task description is mandatory for all task rows.',
        ]);

        $taskDate = Carbon::parse($validated['task_date'])->toDateString();
        $assignedUserId = (int) $validated['assigned_to_user_id'];
        $projectIds = collect($validated['tasks'])->pluck('production_initiation_id')->unique()->all();
        $projects = ProductionInitiation::whereIn('id', $projectIds)->get()->keyBy('id');

        $createdIds = [];

        DB::transaction(function () use ($validated, $user, $taskDate, $assignedUserId, $projects, &$createdIds) {
            foreach ($validated['tasks'] as $taskRow) {
                $projectId = (int) $taskRow['production_initiation_id'];
                $leadId = (int) $taskRow['lead_id'];
                $project = $projects->get($projectId);
                $productName = $project?->product_name ?? 'Project Task';
                $taskDesc = trim((string) $taskRow['task_description']);

                $task = ProductionTask::create([
                    'company_id' => $user->company_id,
                    'created_by' => $user->id,
                    'assigned_to' => $assignedUserId,
                    'task_date' => $taskDate,
                    'lead_id' => $leadId,
                    'production_initiation_id' => $projectId,
                    'product_name' => $productName,
                    'task_description' => $taskDesc,
                    'status' => 'pending',
                ]);
                $createdIds[] = $task->id;

                // Auto-create or sync a matching ProjectTimesheet — mirrors
                // web's ProductionTaskController::store() exactly, which is
                // how "Timesheet is properly linked to the Task module" is
                // implemented.
                $existingTimesheet = ProjectTimesheet::where('production_initiation_id', $projectId)
                    ->where('user_id', $assignedUserId)
                    ->whereDate('timesheet_date', $taskDate)
                    ->first();

                $deliveryDate = $project ? ($project->project_delivery_date?->toDateString()) : null;

                if (! $existingTimesheet) {
                    ProjectTimesheet::create([
                        'company_id' => $project?->company_id ?? $user->company_id,
                        'production_initiation_id' => $projectId,
                        'user_id' => $assignedUserId,
                        'timesheet_date' => $taskDate,
                        'project_delivery_date' => $deliveryDate,
                        'status' => 'pending',
                        'project_type' => 'recurring',
                        'poster_count' => 0,
                        'video_count' => 0,
                        'committed_posters' => 0,
                        'committed_videos' => 0,
                        'waiting_posters' => 0,
                        'waiting_videos' => 0,
                        'day_closing_update' => "Assigned Task:\n" . $taskDesc,
                    ]);
                } elseif (empty(trim((string) $existingTimesheet->day_closing_update))) {
                    $existingTimesheet->update([
                        'day_closing_update' => "Assigned Task:\n" . $taskDesc,
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Tasks created and assigned successfully.',
            'data' => ['created_task_ids' => $createdIds],
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PATCH /mobile/projects/tasks/{task}/status
    // ─────────────────────────────────────────────────────────────────────────
    public function updateStatus(Request $request, ProductionTask $task): JsonResponse
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        if (! $isAdminLike && $task->assigned_to !== $user->id && $task->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,completed'],
        ]);

        $task->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully.',
            'data' => ['id' => $task->id, 'status' => $task->status],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DELETE /mobile/projects/tasks/{task}
    // ─────────────────────────────────────────────────────────────────────────
    public function destroy(ProductionTask $task): JsonResponse
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        if (! $isAdminLike && $task->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to delete this task.'], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SERIALIZERS
    // ─────────────────────────────────────────────────────────────────────────

    private function serializeGroup(Collection|EloquentCollection $groupTasks, User $viewer): array
    {
        $first = $groupTasks->first();
        $assignedUser = $first->assignedUser;
        $creator = $first->creator;

        $uniqueCompanies = $groupTasks->map(function (ProductionTask $t) {
            return $t->lead?->company_name ?: ($t->project?->company_name ?: 'No Company');
        })->unique()->values();

        return [
            'task_date' => $first->task_date ? $first->task_date->toDateString() : null,
            'assigned_user' => $assignedUser ? [
                'id' => $assignedUser->id,
                'name' => $assignedUser->name,
                'email' => $assignedUser->email,
            ] : null,
            'assigned_by' => $creator?->name,
            'created_at' => optional($first->created_at)->toDateTimeString(),
            'total_tasks' => $groupTasks->count(),
            'companies' => $uniqueCompanies,
            'tasks' => $groupTasks->values()->map(function (ProductionTask $t, int $idx) use ($viewer) {
                return [
                    'id' => $t->id,
                    'sno' => $idx + 1,
                    'lead_id' => $t->lead_id,
                    'lead_display_id' => $t->lead_id ? 'LD-' . str_pad((string) $t->lead_id, 4, '0', STR_PAD_LEFT) : 'N/A',
                    'lead_company' => $t->lead?->company_name ?: ($t->project?->company_name ?: 'No Company'),
                    'product_name' => $t->product_name ?: ($t->project?->product_name ?: 'General Task'),
                    'task_description' => $t->task_description,
                    'status' => $t->status,
                    'can_update_status' => $viewer->hasAdminLikeRole() || $t->assigned_to === $viewer->id || $t->created_by === $viewer->id,
                    'can_delete' => $viewer->hasAdminLikeRole() || $t->created_by === $viewer->id,
                ];
            })->values(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers — mirrored from web's ProductionTaskController
    // ─────────────────────────────────────────────────────────────────────────

    private function getAccessibleProjects(User $user): Collection
    {
        $isAdminLike = $user->hasAdminLikeRole() || $user->isDevelopmentProjectCoordinator() || $user->hasTlLikeRole();

        if ($isAdminLike) {
            $projects = ProductionInitiation::query()
                ->with(['lead.branch', 'leadProduct', 'department'])
                ->whereIn('production_approval_status', ['approval', 'approved'])
                ->latest('id')
                ->get();
        } else {
            $managedUserIds = $user->managedUsers()->pluck('users.id')->push($user->id)->all();

            $projects = ProductionInitiation::query()
                ->with(['lead.branch', 'leadProduct', 'department'])
                ->whereIn('production_approval_status', ['approval', 'approved'])
                ->where(function ($q) use ($user, $managedUserIds) {
                    $q->whereJsonContains('project_allocated_tl_user_ids', $user->id)
                      ->orWhereJsonContains('project_allocated_employee_user_ids', $user->id)
                      ->orWhere(function ($sub) use ($managedUserIds) {
                          foreach ($managedUserIds as $mId) {
                              $sub->orWhereJsonContains('project_allocated_employee_user_ids', $mId);
                          }
                      })
                      ->orWhereHas('testingDetails', function ($tq) use ($user) {
                          $tq->where('testing_tl_id', $user->id)
                             ->orWhere('moved_by_user_id', $user->id);
                      });
                })
                ->latest('id')
                ->get();

            if ($projects->isEmpty()) {
                $projects = ProductionInitiation::query()
                    ->with(['lead.branch', 'leadProduct', 'department'])
                    ->whereIn('production_approval_status', ['approval', 'approved'])
                    ->latest('id')
                    ->get();
            }
        }

        return $projects->map(function (ProductionInitiation $project) {
            $empIds = is_array($project->project_allocated_employee_user_ids)
                ? $project->project_allocated_employee_user_ids
                : (json_decode($project->project_allocated_employee_user_ids ?? '[]', true) ?? []);
            $tlIds = is_array($project->project_allocated_tl_user_ids)
                ? $project->project_allocated_tl_user_ids
                : (json_decode($project->project_allocated_tl_user_ids ?? '[]', true) ?? []);
            $tlAlloc = is_array($project->tl_employee_allocations)
                ? $project->tl_employee_allocations
                : (json_decode($project->tl_employee_allocations ?? '[]', true) ?? []);

            $allocUserIds = collect($empIds)
                ->concat($tlIds)
                ->concat(collect($tlAlloc)->flatMap(function ($a, $key) {
                    if (is_array($a)) {
                        $tl = $a['tl_user_id'] ?? (is_numeric($key) ? (int) $key : null);
                        $emps = $a['employee_user_ids'] ?? [];
                        return array_merge([$tl], is_array($emps) ? $emps : []);
                    }
                    return is_numeric($key) ? [(int) $key] : [];
                }))
                ->push($project->ovp_allocated_to)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $project->allocated_user_ids = $allocUserIds;
            $project->resolved_product_name = $project->product_name ?: ($project->leadProduct?->product_name ?: 'Product');
            $project->resolved_company_name = trim($project->company_name ?: ($project->lead?->company_name ?: ($project->client_name ?: ($project->lead?->contact_name ?: 'No Company'))));
            $project->timesheet_delivery_date = $project->project_delivery_date?->toDateString();
            return $project;
        });
    }

    private function getMappedTeamMembers(User $user): Collection
    {
        if ($user->hasAdminLikeRole()) {
            return User::where('is_active', true)
                ->where('user_status', 'active')
                ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        $visibility = app(DataVisibilityService::class);
        $mappedIds = $visibility->descendantUserIds($user);
        $directManagedIds = $user->managedUsers()->pluck('users.id');
        $allMappedIds = $mappedIds->merge($directManagedIds)->push($user->id)->unique()->filter()->values();

        return User::whereIn('id', $allMappedIds)
            ->where('is_active', true)
            ->where('user_status', 'active')
            ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
