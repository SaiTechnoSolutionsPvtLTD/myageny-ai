<?php

namespace App\Http\Controllers;

use App\Models\ProductionInitiation;
use App\Models\ProductionTask;
use App\Models\ProjectTimesheet;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionTaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        $managedUsers = $user->managedUsers()->where('users.user_status', 'active')->orderBy('name')->get(['users.id', 'users.name']);
        $hasMappedUsers = $managedUsers->isNotEmpty();
        $accessibleUserIds = $isAdminLike
            ? null
            : ($hasMappedUsers
                ? array_unique(array_merge([$user->id], $managedUsers->pluck('id')->all()))
                : [$user->id]);

        $quickDate = trim((string) $request->query('quick_date', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $filterDate = trim((string) $request->query('filter_date', ''));

        // Handle quick_date presets if dates not explicitly passed
        if ($quickDate !== '' && $quickDate !== 'all' && $quickDate !== 'custom') {
            $now = Carbon::now();
            if ($quickDate === 'today') {
                $dateFrom = $now->toDateString();
                $dateTo = $now->toDateString();
            } elseif (in_array($quickDate, ['week', 'this_week', 'weekly'], true)) {
                $dateFrom = $now->copy()->startOfWeek()->toDateString();
                $dateTo = $now->copy()->endOfWeek()->toDateString();
            } elseif (in_array($quickDate, ['month', 'this_month', 'monthly'], true)) {
                $dateFrom = $now->copy()->startOfMonth()->toDateString();
                $dateTo = $now->copy()->endOfMonth()->toDateString();
            } elseif (in_array($quickDate, ['quarter', 'this_quarter', 'quarterly'], true)) {
                $dateFrom = $now->copy()->startOfQuarter()->toDateString();
                $dateTo = $now->copy()->endOfQuarter()->toDateString();
            } elseif (in_array($quickDate, ['year', 'this_year', 'yearly'], true)) {
                $dateFrom = $now->copy()->startOfYear()->toDateString();
                $dateTo = $now->copy()->endOfYear()->toDateString();
            }
        } elseif ($filterDate !== '' && $dateFrom === '' && $dateTo === '') {
            $dateFrom = $filterDate;
            $dateTo = $filterDate;
        }

        $filterLeadId = trim((string) $request->query('filter_lead_id', ''));
        $filterProjectId = trim((string) $request->query('filter_project_id', ''));
        $filterUserId = trim((string) $request->query('filter_user_id', ''));
        $filterStatus = trim((string) $request->query('filter_status', ''));

        $allTasks = ProductionTask::query()
            ->with(['creator', 'assignedUser', 'lead', 'project'])
            ->when(!$isAdminLike, function ($query) use ($accessibleUserIds, $user) {
                $query->where(function ($q) use ($accessibleUserIds, $user) {
                    $q->whereIn('assigned_to', $accessibleUserIds)
                      ->orWhere('created_by', $user->id);
                });
            })
            ->when($dateFrom !== '' && $dateTo !== '', function ($query) use ($dateFrom, $dateTo) {
                try {
                    $from = Carbon::parse($dateFrom)->startOfDay()->toDateString();
                    $to = Carbon::parse($dateTo)->endOfDay()->toDateString();
                    $query->whereBetween('task_date', [$from, $to]);
                } catch (\Throwable) {}
            })
            ->when($dateFrom !== '' && $dateTo === '', function ($query) use ($dateFrom) {
                try {
                    $query->whereDate('task_date', '>=', Carbon::parse($dateFrom)->toDateString());
                } catch (\Throwable) {}
            })
            ->when($dateFrom === '' && $dateTo !== '', function ($query) use ($dateTo) {
                try {
                    $query->whereDate('task_date', '<=', Carbon::parse($dateTo)->toDateString());
                } catch (\Throwable) {}
            })
            ->when($filterLeadId !== '', function ($query) use ($filterLeadId) {
                $query->where('lead_id', (int) $filterLeadId);
            })
            ->when($filterProjectId !== '', function ($query) use ($filterProjectId) {
                $query->where('production_initiation_id', (int) $filterProjectId);
            })
            ->when($filterUserId !== '', function ($query) use ($filterUserId) {
                $query->where('assigned_to', (int) $filterUserId);
            })
            ->when($filterStatus !== '', function ($query) use ($filterStatus) {
                $query->where('status', $filterStatus);
            })
            ->latest('task_date')
            ->latest('id')
            ->get();

        // Group tasks by Date + Assigned User
        $groupedTasks = $allTasks->groupBy(function ($task) {
            return ($task->task_date ? $task->task_date->toDateString() : 'no_date') . '_' . $task->assigned_to;
        });

        $page = (int) $request->query('page', 1);
        $perPage = 15;
        $totalGroups = $groupedTasks->count();
        $slicedGroups = $groupedTasks->slice(($page - 1) * $perPage, $perPage)->values();

        $paginatedGroups = new \Illuminate\Pagination\LengthAwarePaginator(
            $slicedGroups,
            $totalGroups,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $assignedProjects = $this->getAccessibleProjects($user);
        $uniqueLeads = $assignedProjects->groupBy('lead_id')->map(function ($projects) {
            $firstProj = $projects->first();
            $companyName = trim($firstProj->company_name ?: ($firstProj->lead?->company_name ?: ''));
            if (!$companyName) {
                $companyName = trim($firstProj->client_name ?: ($firstProj->lead?->contact_name ?: 'No Company'));
            }
            return [
                'lead_id' => $firstProj->lead_id,
                'company_name' => $companyName ?: 'No Company'
            ];
        })->values();

        $mappedTeamMembers = $this->getMappedTeamMembers($user);

        $hasActiveFilters = (!empty($quickDate) && !in_array($quickDate, ['all'], true))
            || !empty($dateFrom)
            || !empty($dateTo)
            || !empty($filterDate)
            || !empty($filterLeadId)
            || !empty($filterProjectId)
            || !empty($filterUserId)
            || !empty($filterStatus);

        return view('pages.projects.tasks.index', [
            'groupedTasks' => $paginatedGroups,
            'assignedProjects' => $assignedProjects,
            'uniqueLeads' => $uniqueLeads,
            'mappedTeamMembers' => $mappedTeamMembers,
            'isAdminLike' => $isAdminLike,
            'hasActiveFilters' => $hasActiveFilters,
            'filters' => [
                'quick_date' => $quickDate,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'filter_date' => $filterDate,
                'filter_lead_id' => $filterLeadId,
                'filter_project_id' => $filterProjectId,
                'filter_user_id' => $filterUserId,
                'filter_status' => $filterStatus,
            ],
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        $assignedProjects = $this->getAccessibleProjects($user);

        $uniqueLeads = $assignedProjects->groupBy('lead_id')->map(function ($projects) {
            $firstProj = $projects->first();
            $companyName = trim($firstProj->company_name ?: ($firstProj->lead?->company_name ?: ''));
            if (!$companyName) {
                $companyName = trim($firstProj->client_name ?: ($firstProj->lead?->contact_name ?: 'No Company'));
            }
            return [
                'lead_id' => $firstProj->lead_id,
                'company_name' => $companyName ?: 'No Company'
            ];
        })->values();

        $assignedProjectsPayload = $assignedProjects->map(function ($project) {
            return [
                'id' => $project->id,
                'lead_id' => $project->lead_id,
                'allocated_user_ids' => $project->allocated_user_ids ?? [],
                'resolved_product_name' => $project->resolved_product_name ?? ($project->product_name ?: 'Product'),
                'resolved_company_name' => $project->resolved_company_name ?? 'No Company',
                'product_name' => $project->product_name ?: 'Product',
                'timesheet_delivery_date' => $project->timesheet_delivery_date ?? null,
            ];
        })->values();

        $mappedTeamMembers = $this->getMappedTeamMembers($user);
        $today = Carbon::today()->toDateString();

        return view('pages.projects.tasks.create', [
            'assignedProjects' => $assignedProjectsPayload,
            'uniqueLeads' => $uniqueLeads,
            'mappedTeamMembers' => $mappedTeamMembers,
            'today' => $today,
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            'tasks.*.lead_id.required' => 'Lead Name is mandatory for all task rows.',
            'tasks.*.production_initiation_id.required' => 'Product Name is mandatory for all task rows.',
            'tasks.*.task_description.required' => 'Task Description is mandatory for all task rows.',
        ]);

        $taskDate = Carbon::parse($validated['task_date'])->toDateString();
        $assignedUserId = (int) $validated['assigned_to_user_id'];
        $projectIds = collect($validated['tasks'])->pluck('production_initiation_id')->unique()->all();
        $projects = ProductionInitiation::whereIn('id', $projectIds)->get()->keyBy('id');

        DB::transaction(function () use ($validated, $user, $taskDate, $assignedUserId, $projects) {
            foreach ($validated['tasks'] as $taskRow) {
                $projectId = (int) $taskRow['production_initiation_id'];
                $leadId = (int) $taskRow['lead_id'];
                $project = $projects->get($projectId);
                $productName = $project?->product_name ?? 'Project Task';
                $taskDesc = trim((string) $taskRow['task_description']);

                ProductionTask::create([
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

                // Auto-create or sync pending ProjectTimesheet for the assigned team member
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
                } else {
                    if (empty(trim((string)$existingTimesheet->day_closing_update))) {
                        $existingTimesheet->update([
                            'day_closing_update' => "Assigned Task:\n" . $taskDesc,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('projects.tasks.index')
            ->with('success', 'Tasks created and assigned successfully.');
    }

    public function updateStatus(Request $request, ProductionTask $task): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        // Permission check: user must be assigned, creator, or admin
        if (!$isAdminLike && $task->assigned_to !== $user->id && $task->created_by !== $user->id) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,completed'],
        ]);

        $task->update([
            'status' => $validated['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully.',
                'status' => $task->status,
            ]);
        }

        return redirect()->back()->with('success', 'Task status updated successfully.');
    }

    public function destroy(ProductionTask $task): RedirectResponse
    {
        $user = auth()->user();
        $isAdminLike = $user->hasAdminLikeRole();

        if (!$isAdminLike && $task->created_by !== $user->id) {
            abort(403, 'Unauthorized to delete this task.');
        }

        $task->delete();

        return redirect()
            ->route('projects.tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    private function getAccessibleProjects(User $user): Collection
    {
        $isDevProjectCoordinator = $user->isDevelopmentProjectCoordinator();
        $isSuperOrCompanyAdmin = $user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isSystemAdmin();

        if ($isSuperOrCompanyAdmin) {
            $projects = ProductionInitiation::query()
                ->with(['lead.branch', 'leadProduct', 'department'])
                ->whereIn('production_approval_status', ['approval', 'approved'])
                ->latest('id')
                ->get();
        } elseif ($isDevProjectCoordinator) {
            // Project Coordinator: Show all development projects
            $devDeptIds = \App\Models\Department::whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%software%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%web%'])
                ->orWhereRaw('LOWER(name) LIKE ?', ['%app%'])
                ->pluck('id')
                ->toArray();

            $projects = ProductionInitiation::query()
                ->with(['lead.branch', 'leadProduct', 'department'])
                ->whereIn('production_approval_status', ['approval', 'approved'])
                ->where(function ($q) use ($devDeptIds) {
                    if (!empty($devDeptIds)) {
                        $q->whereIn('department_id', $devDeptIds)
                          ->orWhereHas('department', function ($dq) {
                              $dq->whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                                 ->orWhereRaw('LOWER(name) LIKE ?', ['%software%'])
                                 ->orWhereRaw('LOWER(name) LIKE ?', ['%web%'])
                                 ->orWhereRaw('LOWER(name) LIKE ?', ['%app%']);
                          });
                    } else {
                        $q->whereHas('department', function ($dq) {
                            $dq->whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                               ->orWhereRaw('LOWER(name) LIKE ?', ['%software%'])
                               ->orWhereRaw('LOWER(name) LIKE ?', ['%web%'])
                               ->orWhereRaw('LOWER(name) LIKE ?', ['%app%']);
                        });
                    }
                })
                ->latest('id')
                ->get();

            // Fallback: if no department tagged, get all approved production projects
            if ($projects->isEmpty()) {
                $projects = ProductionInitiation::query()
                    ->with(['lead.branch', 'leadProduct', 'department'])
                    ->whereIn('production_approval_status', ['approval', 'approved'])
                    ->latest('id')
                    ->get();
            }
        } else {
            // Testing TL and other TLs/Users: Only projects allocated to them or their managed team members
            $visibility = app(DataVisibilityService::class);
            $mappedIds = $visibility->descendantUserIds($user);
            $directManagedIds = $user->managedUsers()->pluck('users.id');
            $allAccessibleIds = $mappedIds->merge($directManagedIds)->push($user->id)->unique()->filter()->map(fn($id) => (int)$id)->values()->all();

            $projects = ProductionInitiation::query()
                ->with(['lead.branch', 'leadProduct', 'department'])
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
                        $tq->whereIn('assigned_to', $allAccessibleIds)
                           ->orWhereIn('created_by', $allAccessibleIds);
                    })
                    ->orWhereHas('timesheets', function ($tq) use ($allAccessibleIds) {
                        $tq->whereIn('user_id', $allAccessibleIds);
                    });
                })
                ->latest('id')
                ->get();
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
                        $tl = $a['tl_user_id'] ?? (is_numeric($key) ? (int)$key : null);
                        $emps = $a['employee_user_ids'] ?? [];
                        return array_merge([$tl], is_array($emps) ? $emps : []);
                    }
                    return is_numeric($key) ? [(int)$key] : [];
                }))
                ->push($project->ovp_allocated_to)
                ->filter()
                ->map(fn($id) => (int) $id)
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
        $prodScope = function ($query) {
            $query->where(function ($q) {
                // 1. Via roles department
                $q->whereHas('roles.department', function ($dq) {
                    $dq->whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%design%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%digital%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
                })
                // 2. Via employee onboarding department
                ->orWhereHas('employeeOnboarding.department', function ($dq) {
                    $dq->whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%design%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%digital%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
                })
                // 3. Via intern joining form department
                ->orWhereHas('internJoiningForm.department', function ($dq) {
                    $dq->whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%design%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%digital%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
                })
                // 4. Via role keywords
                ->orWhereHas('roles', function ($rq) {
                    $rq->whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%design%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%digital%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%flutter%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%laravel%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%react%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%frontend%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%backend%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%fullstack%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%graphic%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%ui%'])
                       ->orWhereRaw('LOWER(name) LIKE ?', ['%ux%']);
                });
            });
        };

        if ($user->hasAdminLikeRole() || $user->isDevelopmentProjectCoordinator()) {
            return User::where('is_active', true)
                ->where('user_status', 'active')
                ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                ->tap($prodScope)
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
            ->tap($prodScope)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
