<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserMapping;
use Illuminate\Support\Collection;

class HrmsApprovalHierarchyService
{
    public function approvalChainFor(User $requester): Collection
    {
        $chain = collect();
        $visitedUserIds = collect([(int) $requester->id]);
        $currentUser = $requester->loadMissing('roles.roleParentMapping.parentRole');

        for ($depth = 0; $depth < 15; $depth++) {
            $nextApprover = $this->resolveNextApprover($currentUser, $requester, $visitedUserIds);

            if (! $nextApprover) {
                break;
            }

            $chain->push($nextApprover);
            $visitedUserIds->push((int) $nextApprover->id);
            $currentUser = $nextApprover;
        }

        return $chain->values();
    }

    public function permissionApprovalChainFor(User $requester): Collection
    {
        return $this->leaveApprovalChainFor($requester);
    }

    public function leaveApprovalChainFor(User $requester): Collection
    {
        $companyId = $requester->company_id;

        $userRoleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $requester->id)
            ->pluck('role_id')
            ->toArray();

        if (empty($userRoleIds) && $requester->relationLoaded('roles')) {
            $userRoleIds = $requester->roles->pluck('id')->toArray();
        }

        $roleNames = Role::withoutGlobalScopes()
            ->whereIn('id', $userRoleIds)
            ->pluck('name')
            ->map(fn($n) => preg_replace('/^company_\d+__/', '', $n))
            ->toArray();

        $hierarchy = null;
        if (!empty($userRoleIds) || !empty($roleNames)) {
            $hierarchy = \App\Models\LeaveHierarchy::withoutGlobalScopes()
                ->where(function($q) use ($userRoleIds, $roleNames) {
                    if (!empty($userRoleIds)) {
                        $q->whereIn('role_id', $userRoleIds);
                    }
                    if (!empty($roleNames)) {
                        $q->orWhereHas('role', fn($rq) => $rq->whereIn('name', $roleNames));
                    }
                })
                ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
                ->where('is_active', true)
                ->orderBy('id', 'desc')
                ->first();
        }

        if ($hierarchy && !empty($hierarchy->approval_chain)) {
            $mappedManagerChain = $this->mappedManagerChain($requester);
            $approverUsers = collect();

            foreach ($hierarchy->approval_chain as $roleId) {
                $targetRole = Role::withoutGlobalScopes()->find($roleId);
                $targetRoleName = $targetRole ? preg_replace('/^company_\d+__/', '', $targetRole->name) : null;

                // 1. Check if applicant has a direct/indirect manager in UserMapping possessing this role
                $approver = $mappedManagerChain->first(function (User $manager) use ($roleId, $targetRoleName, $requester) {
                    if ((int) $manager->id === (int) $requester->id) {
                        return false;
                    }
                    $hasRoleId = $manager->roles->contains('id', $roleId);
                    $hasRoleName = $targetRoleName && $manager->roles->contains(function ($r) use ($targetRoleName) {
                        return str_contains(strtolower($r->name), strtolower($targetRoleName));
                    });
                    return $hasRoleId || $hasRoleName;
                });

                // 2. If no UserMapping manager matches this role, fallback to active users in branch/company
                if (! $approver) {
                    $users = User::query()
                        ->where(function ($q) use ($roleId, $targetRoleName) {
                            $q->whereIn('id', function ($sub) use ($roleId) {
                                $sub->select('model_id')
                                    ->from('model_has_roles')
                                    ->where('role_id', $roleId)
                                    ->where('model_type', User::class);
                            });
                            if ($targetRoleName) {
                                $q->orWhereHas('roles', fn($rq) => $rq->where('name', 'like', "%{$targetRoleName}"));
                            }
                        })
                        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                        ->where('is_active', true)
                        ->get()
                        ->sortBy(function ($u) use ($requester) {
                            return ($u->branch_id && $requester->branch_id && (int)$u->branch_id === (int)$requester->branch_id) ? 0 : 1;
                        });

                    $approver = $users->first(fn($u) => (int)$u->id !== (int)$requester->id);
                }

                if ($approver) {
                    $approverUsers->push($approver);
                }
            }

            return $approverUsers->values();
        }

        return collect();
    }

    public function mappedManagerChain(User $requester): Collection
    {
        $managers = collect();
        $visitedUserIds = collect([(int) $requester->id]);
        $currentUser = $requester;

        for ($depth = 0; $depth < 15; $depth++) {
            $manager = UserMapping::query()
                ->with('manager.roles')
                ->when($requester->company_id, fn ($query) => $query->where('company_id', $requester->company_id))
                ->where('user_id', $currentUser->id)
                ->first()?->manager;

            if (! $manager || $visitedUserIds->contains((int) $manager->id) || ! $manager->is_active) {
                break;
            }

            $managers->push($manager);
            $visitedUserIds->push((int) $manager->id);
            $currentUser = $manager;
        }

        return $managers;
    }

    public function resolveHrApprover(User $requester, ?Collection $excludedUserIds = null, ?User $referenceUser = null): ?User
    {
        $referenceUser ??= $requester;
        $excludedIds = ($excludedUserIds ?? collect())->map(fn ($id) => (int) $id)->all();

        return User::query()
            ->with('roles.department')
            ->where('is_active', true)
            ->when($requester->company_id, fn ($query) => $query->where('company_id', $requester->company_id))
            ->when($excludedIds !== [], fn ($query) => $query->whereNotIn('id', $excludedIds))
            ->get()
            ->filter(fn (User $candidate) => $candidate->belongsToHrDepartment() || $candidate->hasHrLikeRole())
            ->sortBy(function (User $candidate) use ($referenceUser) {
                return [
                    $candidate->branch_id && $referenceUser->branch_id && (int) $candidate->branch_id === (int) $referenceUser->branch_id ? 0 : 1,
                    strtolower((string) $candidate->name),
                ];
            })
            ->first();
    }

    private function resolveNextApprover(User $currentUser, User $requester, Collection $visitedUserIds): ?User
    {
        return $this->mappedManagerFor($currentUser, $requester, $visitedUserIds)
            ?? $this->roleHierarchyApproverFor($currentUser, $requester, $visitedUserIds);
    }

    private function mappedManagerFor(User $currentUser, User $requester, Collection $visitedUserIds): ?User
    {
        $manager = UserMapping::query()
            ->with('manager.roles.roleParentMapping.parentRole')
            ->when($requester->company_id, fn ($query) => $query->where('company_id', $requester->company_id))
            ->where('user_id', $currentUser->id)
            ->first()?->manager;

        return $this->isEligibleApprover($manager, $requester, $visitedUserIds) ? $manager : null;
    }

    private function roleHierarchyApproverFor(User $currentUser, User $requester, Collection $visitedUserIds): ?User
    {
        foreach ($this->parentRolesFor($currentUser) as $parentRole) {
            $approver = $this->userForParentRole($parentRole, $currentUser, $requester, $visitedUserIds);

            if ($approver) {
                return $approver;
            }
        }

        return null;
    }

    private function parentRolesFor(User $user): Collection
    {
        return $user->resolvedRoles()
            ->loadMissing('roleParentMapping.parentRole')
            ->map(fn (Role $role) => $role->roleParentMapping?->parentRole)
            ->filter()
            ->unique('id')
            ->values();
    }

    private function userForParentRole(Role $parentRole, User $currentUser, User $requester, Collection $visitedUserIds): ?User
    {
        return User::query()
            ->with('roles.roleParentMapping.parentRole')
            ->where('is_active', true)
            ->when($requester->company_id, fn ($query) => $query->where('company_id', $requester->company_id))
            ->whereNotIn('id', $visitedUserIds->all())
            ->whereHas('roles', fn ($query) => $query->where('roles.id', $parentRole->id))
            ->get()
            ->sortBy(function (User $candidate) use ($currentUser) {
                return [
                    $candidate->branch_id && $currentUser->branch_id && (int) $candidate->branch_id === (int) $currentUser->branch_id ? 0 : 1,
                    strtolower((string) $candidate->name),
                ];
            })
            ->first(fn (User $candidate) => $this->isEligibleApprover($candidate, $requester, $visitedUserIds));
    }

    private function isEligibleApprover(?User $user, User $requester, Collection $visitedUserIds): bool
    {
        if (! $user || ! $user->is_active || $visitedUserIds->contains((int) $user->id)) {
            return false;
        }

        if ($requester->company_id && $user->company_id && (int) $requester->company_id !== (int) $user->company_id) {
            return false;
        }

        return ! $user->isSystemAdmin();
    }
}
