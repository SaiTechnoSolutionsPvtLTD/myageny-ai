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

    /**
     * Resolve the branch ID for a database notification.
     */
    public static function resolveNotificationBranchId($notification): ?int
    {
        $data = is_array($notification->data) ? $notification->data : (json_decode($notification->data, true) ?: []);

        if (!empty($data['branch_id'])) {
            return (int) $data['branch_id'];
        }

        $type = $data['request_type'] ?? null;
        $id = $data['request_id'] ?? null;

        if ($type === 'leave' && $id) {
            $branchId = \Illuminate\Support\Facades\DB::table('leave_requests')
                ->leftJoin('users', 'leave_requests.user_id', '=', 'users.id')
                ->where('leave_requests.id', $id)
                ->value('users.branch_id');
            if ($branchId) return (int) $branchId;
        }

        if ($type === 'od' && $id) {
            $branchId = \Illuminate\Support\Facades\DB::table('od_requests')
                ->leftJoin('users', 'od_requests.user_id', '=', 'users.id')
                ->where('od_requests.id', $id)
                ->value('users.branch_id');
            if ($branchId) return (int) $branchId;
        }

        if ($type === 'permission' && $id) {
            $branchId = \Illuminate\Support\Facades\DB::table('permission_requests')
                ->leftJoin('users', 'permission_requests.user_id', '=', 'users.id')
                ->where('permission_requests.id', $id)
                ->value('users.branch_id');
            if ($branchId) return (int) $branchId;
        }

        if ($type === 'outside_office' && $id) {
            $oor = \Illuminate\Support\Facades\DB::table('outside_office_attendance_requests')->where('id', $id)->first();
            if ($oor) {
                if ($oor->employee_id) {
                    $branchId = \Illuminate\Support\Facades\DB::table('employee_onboardings')
                        ->leftJoin('users', 'employee_onboardings.portal_user_id', '=', 'users.id')
                        ->where('employee_onboardings.id', $oor->employee_id)
                        ->value('users.branch_id');
                    if ($branchId) return (int) $branchId;
                }
                if ($oor->intern_joining_form_id) {
                    $branchId = \Illuminate\Support\Facades\DB::table('intern_joining_forms')
                        ->leftJoin('users', 'intern_joining_forms.portal_user_id', '=', 'users.id')
                        ->where('intern_joining_forms.id', $oor->intern_joining_form_id)
                        ->value('users.branch_id');
                    if ($branchId) return (int) $branchId;
                }
            }
        }

        if (!empty($data['requester_name'])) {
            $branchId = \Illuminate\Support\Facades\DB::table('users')->where('name', $data['requester_name'])->value('branch_id');
            if ($branchId) return (int) $branchId;
        }

        // Fallback: Notifiable user's branch
        if ($notification->notifiable_id) {
            $branchId = \Illuminate\Support\Facades\DB::table('users')->where('id', $notification->notifiable_id)->value('branch_id');
            if ($branchId) return (int) $branchId;
        }

        return null;
    }

    /**
     * Get branch-filtered notifications and unread count for a given user.
     */
    public static function getBranchFilteredNotifications(User $user, int $limit = 8): array
    {
        $activeBranchId = session('active_branch_id') ?: $user->branch_id;

        $allNotifications = $user->notifications()->latest()->limit(50)->get();

        // If user has no branch associated and is system admin without active branch, show all
        if (! $activeBranchId && $user->isSystemAdmin()) {
            return [
                'items' => $allNotifications->take($limit),
                'unread_count' => $user->unreadNotifications()->count(),
                'filtered_notifications' => $allNotifications,
            ];
        }

        // Target branch IDs: active branch or user's branch IDs
        $allowedBranchIds = array_filter(array_unique(array_merge(
            $activeBranchId ? [(int) $activeBranchId] : [],
            $user->getMyBranchIds()
        )));

        if ($activeBranchId) {
            $allowedBranchIds = [(int) $activeBranchId];
        }

        $filteredNotifications = $allNotifications->filter(function ($n) use ($allowedBranchIds) {
            $branchId = self::resolveNotificationBranchId($n);
            return $branchId !== null && in_array($branchId, $allowedBranchIds, true);
        });

        $unreadCount = $filteredNotifications->whereNull('read_at')->count();

        return [
            'items' => $filteredNotifications->take($limit)->values(),
            'unread_count' => $unreadCount,
            'filtered_notifications' => $filteredNotifications,
        ];
    }
}