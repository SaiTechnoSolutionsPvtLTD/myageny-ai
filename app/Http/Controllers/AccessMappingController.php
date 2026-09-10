<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProductionWorkflowMapping;
use App\Models\Role;
use App\Models\RoleHierarchyMapping;
use App\Models\RoleMapping;
use App\Models\User;
use App\Models\UserMapping;
use App\Services\DataVisibilityService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccessMappingController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function roleIndex()
    {
        $companyId = auth()->user()?->company_id;

        $roles = Role::with(['roleMapping', 'users', 'roleParentMapping.parentRole', 'childRoleMappings.childRole'])
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->orderByRaw('COALESCE(display_name, name)')
            ->get();
        $roleChart = $this->buildRoleChart($roles);

        $accessLevels = RoleMapping::ACCESS_LEVELS;

        return view('pages.auth_menu.mappings.roles', compact('roles', 'accessLevels', 'roleChart'));
    }

    public function roleUpdate(Request $request)
    {
        $data = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*' => ['required', Rule::in(array_keys(RoleMapping::ACCESS_LEVELS))],
            'parents' => ['nullable', 'array'],
            'parents.*' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        $companyId = auth()->user()?->company_id;
        $rolesQuery = Role::whereIn('id', array_keys($data['mappings']));
        if ($companyId !== null) {
            $rolesQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }
        $roles = $rolesQuery->get();
        $rolesById = $roles->keyBy('id');
        $submittedParents = collect($data['parents'] ?? [])
            ->mapWithKeys(fn ($parentId, $childId) => [(int) $childId => $parentId ? (int) $parentId : null]);

        $parentMap = RoleHierarchyMapping::query()
            ->pluck('parent_role_id', 'child_role_id')
            ->map(fn ($parentId) => (int) $parentId)
            ->all();

        foreach ($submittedParents as $childId => $parentId) {
            if (! $parentId) {
                unset($parentMap[$childId]);
                continue;
            }

            $parentMap[$childId] = $parentId;
        }

        foreach ($submittedParents as $childId => $parentId) {
            if (! $parentId) {
                continue;
            }

            if ((int) $childId === (int) $parentId || $this->roleHierarchyWouldLoop((int) $childId, (int) $parentId, $parentMap)) {
                return back()
                    ->withInput()
                    ->with('error', 'This role mapping would create a hierarchy loop. Please choose another parent role.');
            }
        }

        foreach ($roles as $role) {
            RoleMapping::updateOrCreate(
                ['role_id' => $role->id],
                [
                    'company_id' => $role->company_id ?: $companyId,
                    'access_level' => $data['mappings'][$role->id],
                ]
            );
        }

        foreach ($submittedParents as $childId => $parentId) {
            $childRole = $rolesById->get($childId);

            if (! $childRole) {
                continue;
            }

            if (! $parentId) {
                RoleHierarchyMapping::where('child_role_id', $childId)->delete();
                continue;
            }

            RoleHierarchyMapping::updateOrCreate(
                ['child_role_id' => $childId],
                [
                    'company_id' => $childRole->company_id ?: $companyId,
                    'parent_role_id' => $parentId,
                ]
            );
        }

        return back()->with('success', 'Role mapping updated successfully.');
    }

    public function userIndex(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $users = User::with('roles')
            ->where('is_active', true)
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get();

        $userIds = $users->pluck('id')->all();

        $selectedManagerId = (int) ($request->input('manager_id') ?: $users->first()?->id);
        $selectedUserIds = $selectedManagerId
            ? UserMapping::withoutGlobalScopes()
                ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
                ->where('manager_id', $selectedManagerId)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];
        $parentMap = UserMapping::withoutGlobalScopes()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('user_id', $userIds)
            ->pluck('manager_id', 'user_id')
            ->map(fn ($managerId) => (int) $managerId)
            ->all();

        $mappings = UserMapping::withoutGlobalScopes()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('user_id', $userIds)
            ->with(['manager.roles', 'user.roles'])
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $selectedManager = $users->firstWhere('id', $selectedManagerId);
        $userTree = $selectedManager
            ? $this->buildUserTree($selectedManager, $users, collect([$selectedManager->id]))
            : null;
        $userChart = $this->buildUserChart($users, $parentMap);

        return view('pages.auth_menu.mappings.users', compact(
            'users',
            'selectedManagerId',
            'selectedUserIds',
            'mappings',
            'selectedManager',
            'userTree',
            'parentMap',
            'userChart'
        ));
    }

    public function userUpdate(Request $request)
    {
        $hasParentsPayload = is_array($request->input('parents'));

        if ($hasParentsPayload) {
            return $this->updateUserHierarchy($request);
        }

        return $this->updateUserMappingsFromChecklist($request);
    }

    public function userDestroy(UserMapping $mapping)
    {
        $mapping->delete();

        return back()->with('success', 'User mapping removed successfully.');
    }

    public function productionIndex()
    {
        $companyId = auth()->user()?->company_id;

        $departments = Department::when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->with(['products' => function ($query) use ($companyId) {
                $query->select('products.id', 'package_name', 'product_name', 'final_price', 'status')
                    ->when($companyId !== null, fn ($q) => $q->where('products.company_id', $companyId))
                    ->orderBy('package_name')
                    ->orderBy('product_name');
            }])
            ->orderBy('name')
            ->get();

        $mappedProductsCount = $departments->sum(fn (Department $department) => $department->products->count());

        return view('pages.auth_menu.mappings.production', [
            'departments' => $departments,
            'mappedProductsCount' => $mappedProductsCount,
        ]);
    }

    public function productionShow(Department $department)
    {
        $companyId = auth()->user()?->company_id;
        if ($companyId !== null && $department->company_id && (int) $department->company_id !== (int) $companyId) {
            abort(403);
        }

        $department->load(['products' => function ($query) use ($companyId) {
            $query->select('products.id', 'package_name', 'product_name', 'final_price', 'status')
                ->when($companyId !== null, fn ($q) => $q->where('products.company_id', $companyId))
                ->orderBy('package_name')
                ->orderBy('product_name');
        }]);

        $roles = Role::query()
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->orderByRaw('COALESCE(display_name, name)')
            ->get();

        $stageDefinitions = $this->productionWorkflowStageDefinitions();
        $workflowMapping = ProductionWorkflowMapping::query()
            ->where('department_id', $department->id)
            ->first();
        $workflowData = $workflowMapping?->workflow_data ?? [];

        return view('pages.auth_menu.mappings.production_flow', [
            'department' => $department,
            'roles' => $roles,
            'stageDefinitions' => $stageDefinitions,
            'workflowData' => $workflowData,
            'workflowChart' => $this->buildProductionWorkflowChart($stageDefinitions, $workflowData, $roles),
        ]);
    }

    public function productionUpdate(Request $request, Department $department)
    {
        $stageDefinitions = $this->productionWorkflowStageDefinitions();
        $rules = [];

        foreach ($stageDefinitions as $stageKey => $stage) {
            foreach ($stage['role_fields'] as $fieldKey => $field) {
                $path = "workflow.$stageKey.$fieldKey";
                $rules[$path] = ['nullable', 'array'];
                $rules["$path.*"] = ['integer', 'distinct', 'exists:roles,id'];
            }

            foreach ($stage['text_fields'] as $fieldKey => $field) {
                $rules["workflow.$stageKey.$fieldKey"] = ['nullable', 'string', 'max:255'];
            }
        }

        $validated = $request->validate($rules);
        $workflowInput = $validated['workflow'] ?? [];
        $workflowData = [];

        foreach ($stageDefinitions as $stageKey => $stage) {
            $stageInput = $workflowInput[$stageKey] ?? [];
            $stagePayload = [];

            foreach ($stage['role_fields'] as $fieldKey => $field) {
                $stagePayload[$fieldKey] = collect($stageInput[$fieldKey] ?? [])
                    ->map(fn ($roleId) => (int) $roleId)
                    ->unique()
                    ->values()
                    ->all();
            }

            foreach ($stage['text_fields'] as $fieldKey => $field) {
                $stagePayload[$fieldKey] = isset($stageInput[$fieldKey])
                    ? trim((string) $stageInput[$fieldKey])
                    : null;
            }

            $workflowData[$stageKey] = $stagePayload;
        }

        ProductionWorkflowMapping::updateOrCreate(
            ['department_id' => $department->id],
            [
                'company_id' => $department->company_id ?: auth()->user()?->company_id,
                'workflow_data' => $workflowData,
            ]
        );

        return redirect()
            ->route('auth.production-mappings.show', $department)
            ->with('success', 'Production workflow mapping saved successfully.');
    }

    private function buildRoleChart(Collection $roles): array
    {
        $nodes = $roles
            ->map(function (Role $role) {
                $accessLevel = $role->roleMapping?->access_level ?? $this->visibility->defaultAccessLevelForRole($role);

                return [
                    'id' => 'role-' . $role->id,
                    'roleId' => (int) $role->id,
                    'parentRoleId' => $role->roleParentMapping?->parent_role_id ? (int) $role->roleParentMapping->parent_role_id : null,
                    'name' => $this->roleDisplayName($role),
                    'key' => $role->name,
                    'accessLevel' => $accessLevel,
                    'accessLabel' => RoleMapping::labelFor($accessLevel),
                    'userCount' => $role->users->count(),
                    'childCount' => $role->childRoleMappings->count(),
                    'color' => $this->roleChartColor($accessLevel),
                ];
            })
            ->values();

        $links = $nodes
            ->filter(fn (array $node) => $node['parentRoleId'])
            ->map(fn (array $node) => ['role-' . $node['parentRoleId'], $node['id']])
            ->values();

        return [
            'nodes' => $nodes,
            'links' => $links,
            'mappedCount' => $links->count(),
            'parentCount' => $nodes->where('childCount', '>', 0)->count(),
        ];
    }

    private function roleHierarchyWouldLoop(int $childId, int $parentId, array $parentMap): bool
    {
        $visited = [$childId => true];
        $currentId = $parentId;

        while ($currentId) {
            if (isset($visited[$currentId])) {
                return true;
            }

            $visited[$currentId] = true;
            $currentId = $parentMap[$currentId] ?? null;
        }

        return false;
    }

    private function roleDisplayName(Role $role): string
    {
        return $role->display_name ?: str($role->name)->after('__')->replace('_', ' ')->title()->value();
    }

    private function roleChartColor(string $accessLevel): string
    {
        return match ($accessLevel) {
            RoleMapping::ACCESS_COMPANY => '#008236',
            RoleMapping::ACCESS_TEAM => '#8fd3a9',
            RoleMapping::ACCESS_TL => '#65b7d9',
            default => '#f4c95d',
        };
    }

    private function buildUserTree(User $user, Collection $users, Collection $visited): array
    {
        $companyId = auth()->user()?->company_id;

        $children = UserMapping::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->where('manager_id', $user->id)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $userId) => $visited->contains($userId))
            ->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $this->roleLabel($user),
            'children' => $children
                ->map(function (int $userId) use ($users, $visited) {
                    $child = $users->firstWhere('id', $userId);

                    if (! $child) {
                        return null;
                    }

                    return $this->buildUserTree($child, $users, $visited->merge([$userId])->unique()->values());
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function roleLabel(?User $user): string
    {
        if (! $user) {
            return 'No Role';
        }

        $role = $user->roles->first();

        if (! $role) {
            return 'No Role';
        }

        return $role->display_name ?: str($role->name)->after('__')->replace('_', ' ')->title()->value();
    }

    private function userChartPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $this->roleLabel($user),
        ];
    }

    private function buildUserChart(Collection $users, array $parentMap): array
    {
        $childCounts = array_fill_keys($users->pluck('id')->map(fn ($id) => (int) $id)->all(), 0);

        foreach ($parentMap as $userId => $managerId) {
            $managerId = (int) $managerId;

            if (isset($childCounts[$managerId])) {
                $childCounts[$managerId]++;
            }
        }

        $nodes = $users
            ->map(function (User $user) use ($childCounts, $parentMap) {
                $userId = (int) $user->id;
                $childCount = $childCounts[$userId] ?? 0;
                $parentUserId = isset($parentMap[$userId]) ? (int) $parentMap[$userId] : null;
                $category = $childCount > 0
                    ? ($parentUserId ? 'lead' : 'manager')
                    : 'individual';

                return [
                    'id' => 'user-' . $userId,
                    'userId' => $userId,
                    'parentUserId' => $parentUserId,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->roleLabel($user),
                    'childCount' => $childCount,
                    'category' => $category,
                    'meta' => $childCount > 0
                        ? $childCount . ' direct report' . ($childCount === 1 ? '' : 's')
                        : 'Individual contributor',
                ];
            })
            ->values();

        $links = $nodes
            ->filter(fn (array $node) => ! empty($node['parentUserId']))
            ->map(fn (array $node) => ['user-' . $node['parentUserId'], $node['id']])
            ->values();

        return [
            'nodes' => $nodes,
            'links' => $links,
            'mappedCount' => $links->count(),
            'parentCount' => $nodes->where('childCount', '>', 0)->count(),
        ];
    }

    private function updateUserHierarchy(Request $request)
    {
        $data = $request->validate([
            'parents' => ['required', 'array'],
            'parents.*' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $companyId = auth()->user()?->company_id;

        $parentMap = collect($data['parents'])
            ->mapWithKeys(fn ($managerId, $userId) => [(int) $userId => $managerId ? (int) $managerId : null]);

        $userIds = $parentMap->keys()->map(fn ($id) => (int) $id)->values();
        $usersQuery = User::with('roles')->whereIn('id', $userIds);

        if ($companyId !== null) {
            $usersQuery->where('company_id', $companyId);
        }

        $users = $usersQuery->get()->keyBy(fn ($user) => (int) $user->id);

        if ($companyId !== null) {
            // Keep only mappings for users belonging to the company
            $parentMap = $parentMap->filter(fn ($managerId, $userId) => $users->has($userId));
            $validUserIds = $parentMap->keys()->values();
        } else {
            $validUserIds = $userIds;
        }

        foreach ($parentMap as $userId => $managerId) {
            if (! $users->has($userId)) {
                continue;
            }

            if ($managerId !== null && ! $users->has($managerId)) {
                return back()
                    ->withInput()
                    ->with('error', 'Every selected manager must be part of the active user list.');
            }

            if ($managerId !== null && (int) $userId === (int) $managerId) {
                return back()
                    ->withInput()
                    ->with('error', 'A user cannot be mapped under themselves.');
            }
        }

        foreach ($parentMap as $userId => $managerId) {
            if (! $managerId) {
                continue;
            }

            if ($this->userHierarchyWouldLoop((int) $userId, (int) $managerId, $parentMap->all())) {
                return back()
                    ->withInput()
                    ->with('error', 'This mapping would create a reporting loop. Please choose another user.');
            }
        }

        UserMapping::withoutGlobalScopes()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('user_id', $validUserIds)
            ->delete();

        foreach ($parentMap as $userId => $managerId) {
            if (! $managerId) {
                continue;
            }

            $manager = $users->get((int) $managerId);
            $targetUser = $users->get((int) $userId);
            $targetCompanyId = $manager?->company_id ?: ($targetUser?->company_id ?: $companyId);

            UserMapping::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => (int) $userId],
                [
                    'manager_id' => (int) $managerId,
                    'company_id' => $targetCompanyId,
                ]
            );
        }

        return redirect()
            ->route('auth.user-mappings.index')
            ->with('success', 'User hierarchy updated successfully.');
    }

    private function updateUserMappingsFromChecklist(Request $request)
    {
        $data = $request->validate([
            'manager_id' => ['required', 'exists:users,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $companyId = auth()->user()?->company_id;

        $manager = User::findOrFail($data['manager_id']);

        if ($companyId !== null && (int) $manager->company_id !== (int) $companyId) {
            abort(403);
        }

        $userIds = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $manager->id)
            ->unique()
            ->values();

        if ($companyId !== null && $userIds->isNotEmpty()) {
            $invalidCompanyUser = User::whereIn('id', $userIds)
                ->where('company_id', '!=', $companyId)
                ->exists();

            abort_if($invalidCompanyUser, 403);
        }

        foreach ($userIds as $userId) {
            $candidate = User::find($userId);

            if ($candidate && $this->visibility->descendantUserIds($candidate)->contains($manager->id)) {
                return back()
                    ->withInput()
                    ->with('error', 'This mapping would create a reporting loop. Please choose another user.');
            }
        }

        UserMapping::withoutGlobalScopes()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->where('manager_id', $manager->id)
            ->whereNotIn('user_id', $userIds)
            ->delete();

        foreach ($userIds as $userId) {
            $targetUser = User::find($userId);
            $targetCompanyId = $manager->company_id ?: ($targetUser?->company_id ?: $companyId);

            UserMapping::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => (int) $userId],
                [
                    'manager_id' => (int) $manager->id,
                    'company_id' => $targetCompanyId,
                ]
            );
        }

        return redirect()
            ->route('auth.user-mappings.index', ['manager_id' => $manager->id])
            ->with('success', 'User mapping updated successfully.');
    }

    private function userHierarchyWouldLoop(int $userId, int $managerId, array $parentMap): bool
    {
        $visited = [$userId => true];
        $currentManagerId = $managerId;

        while ($currentManagerId) {
            if (isset($visited[$currentManagerId])) {
                return true;
            }

            $visited[$currentManagerId] = true;
            $currentManagerId = $parentMap[$currentManagerId] ?? null;
        }

        return false;
    }

    private function productionWorkflowStageDefinitions(): array
    {
        return [
            'production_initiation' => [
                'step' => 'Stage 1',
                'title' => 'Production Initiation',
                'description' => 'The entry point where the production request is initiated by the business or intake team.',
                'accent' => '#0f766e',
                'role_fields' => [
                    'initiation_roles' => ['label' => 'Initiation Roles', 'placeholder' => 'Choose initiation roles'],
                ],
                'text_fields' => [
                    'priority_template' => ['label' => 'Priority Template', 'placeholder' => 'Ex: Standard / High / Critical'],
                ],
            ],
            'ovp_team_review' => [
                'step' => 'Stage 2',
                'title' => 'OVP Team Review',
                'description' => 'The stage where the OVP team reviews and verifies the incoming request.',
                'accent' => '#0284c7',
                'role_fields' => [
                    'ovp_review_roles' => ['label' => 'OVP Review Roles', 'placeholder' => 'Choose OVP review roles'],
                    'business_team_roles' => ['label' => 'Business Team Roles', 'placeholder' => 'Choose business team roles'],
                ],
                'text_fields' => [],
            ],
            'production_approval_team' => [
                'step' => 'Stage 3',
                'title' => 'Production Approval Team',
                'description' => 'The stage for approval-side roles and checklist verification after OVP review.',
                'accent' => '#7c3aed',
                'role_fields' => [
                    'approval_roles' => ['label' => 'Approval Roles', 'placeholder' => 'Choose approval roles'],
                ],
                'text_fields' => [
                    'approval_checklist' => ['label' => 'Approval Checklist', 'placeholder' => 'Ex: SOW, PO, requirement doc'],
                ],
            ],
            'project_coordinator' => [
                'step' => 'Stage 4',
                'title' => 'Project Coordinator',
                'description' => 'The stage where the approved request is received and the project scope and timeline are coordinated.',
                'accent' => '#ea580c',
                'role_fields' => [
                    'coordinator_roles' => ['label' => 'Project Coordinator Roles', 'placeholder' => 'Choose project coordinator roles'],
                ],
                'text_fields' => [
                    'timeline_template' => ['label' => 'Timeline Template', 'placeholder' => 'Ex: 5 working days / 2 sprints'],
                ],
            ],
            'allocation_multiple_tl_split' => [
                'step' => 'Stage 5 & 6',
                'title' => 'Allocation and Multiple TL Split',
                'description' => 'Configure the required roles for the Project Coordinator to TL to Developer allocation flow.',
                'accent' => '#2563eb',
                'role_fields' => [
                    'tl_roles' => ['label' => 'TL Roles', 'placeholder' => 'Choose TL roles'],
                    'developer_roles' => ['label' => 'Developer Roles', 'placeholder' => 'Choose developer roles'],
                ],
                'text_fields' => [
                    'allocation_note' => ['label' => 'Allocation Note', 'placeholder' => 'Ex: Split based on module scope'],
                ],
            ],
        ];
    }

    private function buildProductionWorkflowChart(array $stageDefinitions, array $workflowData, Collection $roles): array
    {
        $roleNames = $roles->mapWithKeys(fn (Role $role) => [
            (int) $role->id => $this->roleDisplayName($role),
        ]);

        $nodes = [];
        $links = [];
        $previousStageKey = null;

        foreach ($stageDefinitions as $stageKey => $stage) {
            $stagePayload = $workflowData[$stageKey] ?? [];
            $roleGroups = [];
            $assignedRoleCount = 0;
            $notes = [];

            foreach ($stage['role_fields'] as $fieldKey => $field) {
                $names = collect($stagePayload[$fieldKey] ?? [])
                    ->map(fn ($roleId) => $roleNames[(int) $roleId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $assignedRoleCount += count($names);
                $roleGroups[] = [
                    'label' => $field['label'],
                    'roles' => $names,
                ];
            }

            foreach ($stage['text_fields'] as $fieldKey => $field) {
                $value = trim((string) ($stagePayload[$fieldKey] ?? ''));

                if ($value !== '') {
                    $notes[] = $field['label'] . ': ' . $value;
                }
            }

            $nodes[] = [
                'id' => $stageKey,
                'step' => $stage['step'],
                'title' => $stage['title'],
                'accent' => $stage['accent'],
                'description' => $stage['description'],
                'roleGroups' => $roleGroups,
                'notes' => $notes,
                'assignedRoleCount' => $assignedRoleCount,
            ];

            if ($previousStageKey) {
                $links[] = [$previousStageKey, $stageKey];
            }

            $previousStageKey = $stageKey;
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
            'mappedStageCount' => collect($nodes)->filter(fn (array $node) => $node['assignedRoleCount'] > 0)->count(),
            'totalStageCount' => count($nodes),
        ];
    }
}
