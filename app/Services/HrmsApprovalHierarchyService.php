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

    public function leaveApprovalChainFor(User $requester): Collection
    {
        $hierarchyApprovers = $this->approvalChainFor($requester)->take(2)->values();

        if ($hierarchyApprovers->isEmpty()) {
            return collect();
        }

        $excludedUserIds = $hierarchyApprovers
            ->pluck('id')
            ->prepend($requester->id)
            ->map(fn ($id) => (int) $id);

        $hrApprover = $this->resolveHrApprover($requester, $excludedUserIds, $hierarchyApprovers->last());

        return $hrApprover
            ? $hierarchyApprovers->push($hrApprover)->values()
            : $hierarchyApprovers;
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
