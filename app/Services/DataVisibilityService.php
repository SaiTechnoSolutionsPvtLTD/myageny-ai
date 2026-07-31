<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\RoleMapping;
use App\Models\User;
use App\Models\UserMapping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataVisibilityService
{
    public function companyIdFor(?User $user = null): ?int
    {
        $user ??= auth()->user();

        return $user?->company_id ? (int) $user->company_id : null;
    }

    public function applyCompanyVisibility($query, ?User $user = null, string $companyColumn = 'company_id')
    {
        $companyId = $this->companyIdFor($user);

        if ($companyId) {
            $query->where($companyColumn, $companyId);
        }

        return $query;
    }

    public function accessLevelFor(?User $user = null): string
    {
        $user ??= auth()->user();

        if (! $user) {
            return RoleMapping::ACCESS_COMPANY;
        }

        if ($this->isCompanyWideUser($user)) {
            return RoleMapping::ACCESS_COMPANY;
        }

        $roleIds = $user->roles()->pluck('roles.id');

        $mappedLevels = RoleMapping::query()
            ->whereIn('role_id', $roleIds)
            ->pluck('access_level');

        if ($mappedLevels->contains(RoleMapping::ACCESS_COMPANY)) {
            return RoleMapping::ACCESS_COMPANY;
        }

        if ($mappedLevels->contains(RoleMapping::ACCESS_TEAM)) {
            return RoleMapping::ACCESS_TEAM;
        }

        if ($mappedLevels->contains(RoleMapping::ACCESS_TL)) {
            return RoleMapping::ACCESS_TL;
        }

        foreach ($user->roles as $role) {
            $defaultLevel = $this->defaultAccessLevelForRole($role);

            if ($defaultLevel === RoleMapping::ACCESS_TEAM) {
                return RoleMapping::ACCESS_TEAM;
            }

            if ($defaultLevel === RoleMapping::ACCESS_TL) {
                return RoleMapping::ACCESS_TL;
            }
        }

        return RoleMapping::ACCESS_SELF;
    }

    public function defaultAccessLevelForRole(Role $role): string
    {
        $keys = collect([$role->name, $role->display_name])
            ->filter()
            ->map(fn (string $name) => $this->roleKey($name));

        if ($keys->intersect(['super_admin', 'admin', 'company_admin', 'branch_admin', 'chief_business_officer', 'cbo', 'chief_operating_officer', 'cheif_operating_officer', 'coo'])->isNotEmpty()) {
            return RoleMapping::ACCESS_COMPANY;
        }

        if ($keys->intersect(['tl', 'team_leader', 'teamlead', 'manager', 'sales_manager', 'sales_head', 'branch_manager'])->isNotEmpty()) {
            return RoleMapping::ACCESS_TEAM;
        }

        if ($keys->intersect(['project_coordinator', 'project_coordination', 'pc'])->isNotEmpty()) {
            return RoleMapping::ACCESS_TL;
        }

        return RoleMapping::ACCESS_SELF;
    }

    public function visibleUserIds(?User $user = null): ?array
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        if ($this->hasBranchAdminRole($user)) {
            $branchIds = $user->getMyBranchIds();
            $companyId = $this->companyIdFor($user);

            try {
                return User::query()
                    ->where('is_active', true)
                    ->when(!empty($branchIds), fn ($query) => $query->whereIn('branch_id', $branchIds))
                    ->when(empty($branchIds) && $user->branch_id, fn ($query) => $query->where('branch_id', $user->branch_id))
                    ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                    ->pluck('id')
                    ->all();
            } catch (\Throwable $e) {
                return [$user->id];
            }
        }

        if ($this->isCompanyWideUser($user)) {
            return null;
        }

        $accessLevel = $this->accessLevelFor($user);

        if ($accessLevel === RoleMapping::ACCESS_COMPANY) {
            return null;
        }

        if ($accessLevel === RoleMapping::ACCESS_TEAM) {
            return $this->descendantUserIds($user)->push($user->id)->unique()->values()->all();
        }

        if ($accessLevel === RoleMapping::ACCESS_TL) {
            return $this->mappedTlUserIds($user)->push($user->id)->unique()->values()->all();
        }

        return [$user->id];
    }

    public function descendantUserIds(User $manager): Collection
    {
        $visited = collect([$manager->id]);
        $frontier = collect([$manager->id]);
        $descendants = collect();
        $companyId = $manager->company_id;

        while ($frontier->isNotEmpty()) {
            $children = UserMapping::query()
                ->whereIn('manager_id', $frontier)
                ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
                ->pluck('user_id')
                ->reject(fn (int $userId) => $visited->contains($userId))
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $descendants = $descendants->merge($children);
            $visited = $visited->merge($children)->unique()->values();
            $frontier = $children;
        }

        return $descendants->unique()->values();
    }

    public function mappedTlUserIds(User $manager): Collection
    {
        $companyId = $manager->company_id;

        $tlIds = UserMapping::query()
            ->where('manager_id', $manager->id)
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $userId) => $this->userHasTlRole($userId))
            ->unique()
            ->values();

        return $tlIds
            ->map(fn (int $tlId) => User::find($tlId))
            ->filter()
            ->flatMap(fn (User $tlUser) => $this->descendantUserIds($tlUser)->push($tlUser->id))
            ->unique()
            ->values();
    }

    public function visibleAssignableUsers(?User $user = null): Collection
    {
        $user ??= auth()->user();
        $visibleIds = $this->visibleUserIds($user);
        $companyId = $this->companyIdFor($user);

        $users = User::query()
            ->with('roles')
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereHas('roles.department', function (Builder $q) {
                    $q->whereIn(DB::raw('LOWER(name)'), [
                        'sales',
                        'crm',
                        'business development',
                        'marketing',
                        'telecalling',
                    ])
                    ->orWhereIn(DB::raw('LOWER(REPLACE(name, " ", "_"))'), [
                        'sales',
                        'crm',
                        'business_development',
                        'marketing',
                        'telecalling',
                    ]);
                })
                ->orWhereHas('roles', function (Builder $q) {
                    $q->whereIn(DB::raw('LOWER(name)'), [
                        'sales_manager',
                        'sales_executive',
                        'sales_tl',
                        'sales_intern',
                        'bde',
                        'business_development_executive',
                        'telecaller',
                    ])
                    ->orWhereIn(DB::raw('LOWER(REPLACE(name, " ", "_"))'), [
                        'sales_manager',
                        'sales_executive',
                        'sales_tl',
                        'sales_intern',
                        'bde',
                        'business_development_executive',
                        'telecaller',
                    ]);
                });
            })
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->when($visibleIds !== null, fn (Builder $query) => $query->whereIn('id', $visibleIds))
            ->orderBy('name')
            ->get();

        if ($user && !$users->contains('id', $user->id)) {
            $user->loadMissing('roles');
            $users->push($user);
            $users = $users->sortBy('name')->values();
        }

        return $users;
    }

    public function canAssignTo(int|string|null $userId, ?User $actor = null): bool
    {
        if (! $userId) {
            return false;
        }

        $visibleIds = $this->visibleUserIds($actor);

        return $visibleIds === null || in_array((int) $userId, $visibleIds, true);
    }

    public function applyLeadVisibility(Builder $query, ?User $user = null, string $assignedColumn = 'assigned_to'): Builder
    {
        $this->applyCompanyVisibility($query, $user, 'company_id');

        $user ??= auth()->user();
        if ($user && $user->hasCustomerSupportLikeRole() && !$this->isCompanyWideUser($user)) {
            return $query->where(function ($q) use ($user) {
                $q->where('customer_support_tl_id', $user->id)
                  ->orWhere('customer_support_executive_id', $user->id);
            });
        }

        $visibleIds = $this->visibleUserIds($user);

        if ($visibleIds === null) {
            return $query;
        }

        return $query->whereIn($assignedColumn, $visibleIds);
    }

    public function applyLeadRelationVisibility(Builder $query, string $relation = 'lead', ?User $user = null): Builder
    {
        $companyId = $this->companyIdFor($user);

        if ($companyId) {
            $query->whereHas($relation, function (Builder $leadQuery) use ($companyId) {
                $leadQuery->where('company_id', $companyId);
            });
        }

        $user ??= auth()->user();
        if ($user && $user->hasCustomerSupportLikeRole() && !$this->isCompanyWideUser($user)) {
            return $query->whereHas($relation, function ($q) use ($user) {
                $q->where('customer_support_tl_id', $user->id)
                  ->orWhere('customer_support_executive_id', $user->id);
            });
        }

        $visibleIds = $this->visibleUserIds($user);

        if ($visibleIds === null) {
            return $query;
        }

        return $query->whereHas($relation, function (Builder $leadQuery) use ($visibleIds) {
            $leadQuery->whereIn('assigned_to', $visibleIds);
        });
    }

    public function applyQuotationVisibility(Builder $query, ?User $user = null): Builder
    {
        $this->applyCompanyVisibility($query, $user, 'company_id');

        $user ??= auth()->user();
        if ($user && $user->hasCustomerSupportLikeRole() && !$this->isCompanyWideUser($user)) {
            return $query->where(function (Builder $quotationQuery) use ($user) {
                $quotationQuery
                    ->whereHas('lead', function (Builder $leadQuery) use ($user) {
                        $leadQuery->where('customer_support_tl_id', $user->id)
                                  ->orWhere('customer_support_executive_id', $user->id);
                    });
            });
        }

        $visibleIds = $this->visibleUserIds($user);

        if ($visibleIds === null) {
            return $query;
        }

        return $query->where(function (Builder $quotationQuery) use ($visibleIds) {
            $quotationQuery
                ->whereHas('lead', fn (Builder $leadQuery) => $leadQuery->whereIn('assigned_to', $visibleIds))
                ->orWhere(function (Builder $noLeadQuery) use ($visibleIds) {
                    $noLeadQuery
                        ->whereNull('lead_id')
                        ->whereIn('created_by', $visibleIds);
                });
        });
    }

    public function applyProductVisibility(Builder $query, ?User $user = null): Builder
    {
        $this->applyCompanyVisibility($query, $user, 'company_id');

        $visibleIds = $this->visibleUserIds($user);

        if ($visibleIds === null) {
            return $query;
        }

        return $query->where(function (Builder $productQuery) use ($visibleIds) {
            $productQuery
                ->whereNull('assigned_to')
                ->orWhereIn('assigned_to', $visibleIds)
                ->orWhereIn('created_by', $visibleIds);
        });
    }

    public function canAccessLead(Lead $lead, ?User $user = null): bool
    {
        $companyId = $this->companyIdFor($user);

        if ($companyId && (int) $lead->company_id !== $companyId) {
            return false;
        }

        $user ??= auth()->user();
        if ($user && $user->hasCustomerSupportLikeRole() && !$this->isCompanyWideUser($user)) {
            return (int) $lead->customer_support_tl_id === $user->id
                || (int) $lead->customer_support_executive_id === $user->id;
        }

        $visibleIds = $this->visibleUserIds($user);

        return $visibleIds === null || in_array((int) $lead->assigned_to, $visibleIds, true);
    }

    public function canAccessQuotation(Quotation $quotation, ?User $user = null): bool
    {
        $companyId = $this->companyIdFor($user);

        if ($companyId && (int) $quotation->company_id !== $companyId) {
            return false;
        }

        $user ??= auth()->user();
        if ($user && $user->hasCustomerSupportLikeRole() && !$this->isCompanyWideUser($user)) {
            $quotation->loadMissing('lead:id,customer_support_tl_id,customer_support_executive_id');
            if ($quotation->lead) {
                return (int) $quotation->lead->customer_support_tl_id === $user->id
                    || (int) $quotation->lead->customer_support_executive_id === $user->id;
            }
            return false;
        }

        $visibleIds = $this->visibleUserIds($user);

        if ($visibleIds === null) {
            return true;
        }

        $quotation->loadMissing('lead:id,assigned_to');

        if ($quotation->lead) {
            return in_array((int) $quotation->lead->assigned_to, $visibleIds, true);
        }

        return in_array((int) $quotation->created_by, $visibleIds, true);
    }

    public function canAccessProduct(Product $product, ?User $user = null): bool
    {
        $companyId = $this->companyIdFor($user);

        if ($companyId && (int) $product->company_id !== $companyId) {
            return false;
        }

        $visibleIds = $this->visibleUserIds($user);

        if ($visibleIds === null || $product->assigned_to === null) {
            return true;
        }

        return in_array((int) $product->assigned_to, $visibleIds, true)
            || in_array((int) $product->created_by, $visibleIds, true);
    }

    public function visibleBranchIds(?User $user = null): Collection
    {
        $user ??= auth()->user();

        $leadBranchQuery = Lead::query()->whereNotNull('branch_id');
        $this->applyLeadVisibility($leadBranchQuery, $user);
        $leadBranchIds = $leadBranchQuery->distinct()->pluck('branch_id');

        $visibleIds = $this->visibleUserIds($user);
        $companyId = $this->companyIdFor($user);

        $userBranchIds = User::query()
            ->whereNotNull('branch_id')
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->when($visibleIds !== null, fn (Builder $query) => $query->whereIn('id', $visibleIds))
            ->distinct()
            ->pluck('branch_id');

        return $leadBranchIds
            ->merge($userBranchIds)
            ->filter()
            ->unique()
            ->values();
    }

    public function visibleBranches(?User $user = null): Collection
    {
        $branchIds = $this->visibleBranchIds($user);
        $companyId = $this->companyIdFor($user);

        return DB::table('branches')
            ->select('id', 'name')
            ->where('is_active', true)
            ->when($branchIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $branchIds))
            ->when($branchIds->isEmpty() && $companyId, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get();
    }

    public function visibleLeadSources(?User $user = null): Collection
    {
        $query = Lead::query()->whereNotNull('lead_source');
        $this->applyLeadVisibility($query, $user);

        return $query
            ->distinct()
            ->orderBy('lead_source')
            ->pluck('lead_source')
            ->filter()
            ->values();
    }


    public function isCompanyWideUser(User $user): bool
    {
        if ($user->isSystemAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin()) {
            return true;
        }

        if ($user->company_id && DB::table('companies')
            ->where('id', $user->company_id)
            ->where('super_admin_user_id', $user->id)
            ->exists()) {
            return true;
        }

        return $user->roles->contains(function ($role) {
            return in_array($this->roleKey($role->name), ['super_admin', 'admin', 'company_admin', 'branch_admin', 'chief_business_officer', 'cbo', 'chief_operating_officer', 'cheif_operating_officer', 'coo'], true)
                || in_array($this->roleKey((string) $role->display_name), ['super_admin', 'admin', 'company_admin', 'branch_admin', 'chief_business_officer', 'cbo', 'chief_operating_officer', 'cheif_operating_officer', 'coo'], true);
        });
    }



    private function userHasTlRole(int $userId): bool
    {
        $user = User::with('roles')->find($userId);

        if (! $user) {
            return false;
        }

        return $user->roles->contains(function ($role) {
            return in_array($this->roleKey($role->name), ['tl', 'team_leader', 'teamlead'], true)
                || in_array($this->roleKey((string) $role->display_name), ['tl', 'team_leader', 'teamlead'], true);
        });
    }

    private function roleKey(string $roleName): string
    {
        $roleName = Str::contains($roleName, '__')
            ? Str::afterLast($roleName, '__')
            : $roleName;

        return Str::of($roleName)->lower()->replace(' ', '_')->slug('_')->value();
    }
}
