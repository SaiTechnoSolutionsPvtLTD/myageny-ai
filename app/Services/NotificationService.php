<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AppNotification;

class NotificationService
{
    /**
     * Notify a single user. No-op if $user is null so call sites can pass
     * optional relations (e.g. $lead->assignedTo) without extra null checks.
     */
    public function notify(?User $user, string $module, string $type, array $data): void
    {
        if (! $user) {
            return;
        }

        $user->notify(new AppNotification(array_merge($data, [
            'module' => $module,
            'notification_type' => $type,
        ])));
    }

    /**
     * Notify many users with the same payload. Accepts any iterable of
     * User models (Eloquent Collection, array, LazyCollection, etc.).
     */
    public function notifyMany(iterable $users, string $module, string $type, array $data): void
    {
        foreach ($users as $user) {
            $this->notify($user, $module, $type, $data);
        }
    }

    /**
     * Narrows a candidate recipient pool to the SAME branch-isolation rule
     * already enforced everywhere else in this codebase — the global 'branch'
     * scope on User::booted() / Lead::booted() / EmployeeOnboarding::booted()
     * / Branch::booted(), which all use the identical conditional:
     *
     *     if ($candidate->isBranchAdmin() && $candidate->branch_id) { ... }
     *
     * A branch_admin only ever operates within their own branch (enforced
     * automatically on THEIR OWN queries by those global scopes); this
     * method applies that exact same rule from the recipient side — a
     * branch_admin candidate is only notified if their branch matches the
     * resource's branch. Every other role (company_admin, super_admin,
     * regular staff) is untouched by this filter, exactly mirroring how the
     * existing global scopes leave non-branch-admins unrestricted — those
     * roles are "company-wide" by this app's existing design, not
     * multi-branch-selective (no branch-pivot table exists for users to
     * hold several branches at once).
     *
     * $resourceBranchId is null when the resource itself carries no branch
     * concept at all (e.g. HrmsAnnouncement has no branch_id column) — in
     * that case nothing is filtered, since there is no branch to isolate by.
     *
     * @param iterable<User> $candidates
     * @return array<User>
     */
    public function filterByBranchVisibility(iterable $candidates, ?int $resourceBranchId): array
    {
        $result = [];

        foreach ($candidates as $candidate) {
            if ($resourceBranchId !== null && $candidate->isBranchAdmin() && $candidate->branch_id) {
                if ((int) $candidate->branch_id === $resourceBranchId) {
                    $result[] = $candidate;
                }
                continue;
            }

            $result[] = $candidate;
        }

        return $result;
    }
}