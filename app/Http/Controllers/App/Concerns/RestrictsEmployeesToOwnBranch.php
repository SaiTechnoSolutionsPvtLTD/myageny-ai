<?php

namespace App\Http\Controllers\App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Mobile-only branch isolation for employee/"Assigned To" dropdowns and
 * filters across the CRM module (Lead Products, Call Updates, Price
 * Requests, CRM Tasks & Reminders, CST Allocation, and the primary Lead
 * meta() "users" list).
 *
 * This is intentionally NOT added to DataVisibilityService (app/Services/
 * DataVisibilityService.php), which is shared by both the web and mobile
 * controllers — editing it would change web behaviour too. This trait is
 * only ever `use`d by mobile (App\Http\Controllers\App\*) controllers, so
 * it cannot affect the web app.
 *
 * Rule: a Branch Admin or Manager (or any other non-company-wide role)
 * only ever sees employees from their own branch(es), plus themselves.
 * Exemptions (unrestricted, company-wide) mirror exactly what
 * DataVisibilityService::isCompanyWideUser() / hasPreSalesLikeRole()
 * already treat as company-wide elsewhere in the app (System Admin,
 * Company Admin, Branch Admin, CBO/COO, Pre-Sales) — this trait only ever
 * narrows results further, never loosens what the existing visibility
 * service already allows, so it can't break existing same-branch behaviour.
 *
 * Requires the consuming controller to have a `$this->visibility`
 * DataVisibilityService property (LeadController, CrmTaskApiController,
 * and CstAllocationApiController already do).
 *
 * If the acting user has no branch assigned at all, results fail safe to
 * "themselves only" rather than leaking company-wide data.
 */
trait RestrictsEmployeesToOwnBranch
{
    /**
     * Apply to a not-yet-executed Eloquent query over the users table (or
     * any table with a `branch_id` column) before calling ->get().
     */
    protected function scopeEmployeeQueryToOwnBranch(Builder $query, ?User $actingUser, string $branchColumn = 'branch_id'): Builder
    {
        if (! $actingUser) {
            return $query->whereRaw('1 = 0');
        }

        if ($actingUser->company_id) {
            $query->where('company_id', $actingUser->company_id);
        }

        if ($this->isExemptFromBranchRestriction($actingUser)) {
            return $query;
        }

        $branchIds = $actingUser->getMyBranchIds();

        if (empty($branchIds)) {
            return $query->where('id', $actingUser->id);
        }

        return $query->where(function (Builder $q) use ($branchIds, $branchColumn, $actingUser) {
            $q->whereIn($branchColumn, $branchIds)
              ->orWhere('id', $actingUser->id);
        });
    }

    /**
     * Apply to an already-fetched collection of User models (e.g. the
     * result of DataVisibilityService::visibleAssignableUsers()).
     */
    protected function restrictUserCollectionToOwnBranch(Collection $users, ?User $actingUser, string $branchAttr = 'branch_id'): Collection
    {
        if (! $actingUser) {
            return $users->take(0)->values();
        }

        if ($this->isExemptFromBranchRestriction($actingUser)) {
            return $users;
        }

        $branchIds = $actingUser->getMyBranchIds();

        if (empty($branchIds)) {
            return $users->where('id', $actingUser->id)->values();
        }

        return $users->filter(function ($u) use ($branchIds, $branchAttr, $actingUser) {
            return (int) $u->id === (int) $actingUser->id
                || in_array((int) $u->{$branchAttr}, $branchIds, true);
        })->values();
    }

    private function isExemptFromBranchRestriction(User $actingUser): bool
    {
        if (property_exists($this, 'visibility') && $this->visibility) {
            if ($this->visibility->isCompanyWideUser($actingUser)) {
                return true;
            }
        } elseif ($actingUser->isSystemAdmin() || $actingUser->isCompanyAdmin() || $actingUser->isBranchAdmin()) {
            // Fallback if a future consuming controller doesn't inject
            // DataVisibilityService — still exempts the core admin roles.
            return true;
        }

        return method_exists($actingUser, 'hasPreSalesLikeRole') && $actingUser->hasPreSalesLikeRole();
    }
}
