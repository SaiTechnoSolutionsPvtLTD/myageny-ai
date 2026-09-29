<?php

namespace App\Services;

use App\Models\LeadReminder;
use App\Models\OutsideOfficeAttendanceRequest;
use App\Services\DataVisibilityService;
use App\Models\ProductionInitiation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MenuService
{
    public function __construct(private readonly DataVisibilityService $visibility = new DataVisibilityService()) {}

    /** Module key => switcher metadata. Add a line here when a new module ships. */
    private const MODULE_META = [
        'crm'      => ['label' => 'CRM',      'order' => 10],
        'projects' => ['label' => 'Projects', 'order' => 20],
        'hrms'     => ['label' => 'HRMS',     'order' => 30],
    ];

    // ── OVP role scoping — mirrors resources/views/layouts/sidebar.blade.php
    // (the $ovpNewCount block) and App\Http\Controllers\App\OvpModuleApiController
    // exactly, so the badge always agrees with what the OVP list itself shows.
    private const OVP_TL_ROLE_KEYS = [
        'customer_support_team_tl',
        'senior_customer_success_team_executive',
        'senior_customer_success_executive',
        'senior_success_executive',
        'senior_customer_support_executive',
        'senior_support_executive',
        'senior_cst_executive',
    ];
    private const OVP_EXECUTIVE_ROLE_KEYS = [
        'customer_support_team_executive',
        'customer_support_executive',
        'customer_success_executive',
        'senior_customer_success_team_executive',
        'cst_executive',
        'support_executive',
        'executive',
    ];

    /**
     * Build the ordered, permission-filtered menu tree for one module.
     * Empty array if the module doesn't exist or the user fails its gate.
     */
    public function build(User $user, string $module = 'crm'): array
    {
        $config = config("mobile_menu.$module");
        if (! $config || ! $this->passesGate($user, $config['gate'] ?? null)) {
            return [];
        }

        $companyId = $user->company_id;
        $dbPermissions = \Illuminate\Support\Facades\DB::table('permissions')
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->get(['name', 'display_name', 'company_id']);

        return $this->filterAndSort($config['items'] ?? [], $user, $dbPermissions);
    }

    /**
     * Which modules can this user switch into at all — feeds the mobile
     * module-switcher FAB (the "Quick module access" panel).
     *
     * Deliberately does NOT reuse build()'s broader `gate` (department/role
     * heuristics like hasSalesLikeRole(), belongsToSalesDepartment(),
     * hasTlLikeRole(), or fallbacks onto unrelated permissions like
     * dashboard.view/leads.menuview). Those heuristics decide what's inside
     * a module's menu once you're in it — they were never meant to decide
     * whether the module tile itself is offered.
     *
     * The web app's own switcher (resources/views/layouts/module_sidebar.blade.php)
     * shows each tile based on exactly one check: can("modules_menu.$key").
     * Mirrored here 1:1 so an employee who lacks e.g. modules_menu.crm never
     * sees the CRM tile on mobile even if their role/department would have
     * granted them CRM *menu* access via the broader build() gate.
     *
     * Exception: HRMS stays unconditionally offered, same as its `gate =>
     * null` in config/mobile_menu.php — plain self-service employees are
     * frequently never assigned an explicit modules_menu.hrms permission,
     * and still need the HRMS tile to check in/out. Web's own left sidebar
     * treats HRMS the same way (dashboard.view || dashboard.menuview ||
     * modules_menu.hrms || isCompanyAdmin || isSystemAdmin) rather than
     * requiring modules_menu.hrms alone.
     *
     * face_attendance gets the same exception for the same reason: it's a
     * self-service tile (mark attendance via face) with `gate => null`, and
     * no web-sidebar equivalent exists to have ever granted a
     * modules_menu.face_attendance permission in the first place.
     */
    public function accessibleModules(User $user): array
    {
        $modules = [];

        $alwaysVisible = ['hrms', 'face_attendance'];

        foreach (config('mobile_menu', []) as $key => $config) {
            $canAccess = in_array($key, $alwaysVisible, true) || $user->can("modules_menu.$key");

            if ($canAccess) {
                $modules[] = [
                    'key'   => $key,
                    'label' => $config['label'] ?? ucfirst($key),
                    'order' => $config['order'] ?? 0,
                ];
            }
        }

        usort($modules, fn($a, $b) => $a['order'] <=> $b['order']);

        return array_values($modules);
    }

    private function filterAndSort(array $items, User $user, $dbPermissions = null): array
    {
        if ($dbPermissions === null) {
            $companyId = $user->company_id;
            $dbPermissions = \Illuminate\Support\Facades\DB::table('permissions')
                ->where(function ($q) use ($companyId) {
                    $q->whereNull('company_id');
                    if ($companyId) {
                        $q->orWhere('company_id', $companyId);
                    }
                })
                ->get(['name', 'display_name', 'company_id']);
        }

        $visible = [];

        foreach ($items as $item) {
            $matchedPermission = null;
            if (! $this->isVisible($user, $item, $dbPermissions, $matchedPermission)) {
                continue;
            }

            $node = [
                'key'         => $item['key'],
                'label'       => $this->resolveItemLabel($item, $matchedPermission),
                'section'     => $item['section'] ?? null,
                'order'       => $item['order'] ?? 0,
                'badge_count' => $this->resolveBadgeCount($item, $user),
            ];

            $node['children'] = ! empty($item['children'])
                ? $this->filterAndSort($item['children'], $user, $dbPermissions)
                : [];

            $visible[] = $node;
        }

        usort($visible, fn($a, $b) => $a['order'] <=> $b['order']);

        return array_values($visible);
    }

    /**
     * Resolves the menu item's display label.
     * Uses the backend dynamic display_name from the permissions table when available,
     * stripping seeder prefixes ("Menuview ", "View ") and falling back to static label.
     */
    private function resolveItemLabel(array $item, ?object $permissionRecord): string
    {
        $staticLabel = $item['label'] ?? '';

        if (! $permissionRecord || empty($permissionRecord->display_name)) {
            return $staticLabel;
        }

        $rawDisplayName = trim($permissionRecord->display_name);
        if ($rawDisplayName === '') {
            return $staticLabel;
        }

        // Clean common seeder prefixes like "Menuview " or "View "
        $cleaned = trim(preg_replace('/^(menuview|view)\s+/i', '', $rawDisplayName));

        // If after cleaning it matches static label case-insensitively, keep static formatting
        if (strcasecmp($cleaned, $staticLabel) === 0 || strcasecmp(str_replace(' ', '', $cleaned), str_replace(' ', '', $staticLabel)) === 0) {
            return $staticLabel;
        }

        $itemKey = $item['key'] ?? '';
        $basePermName = preg_replace('/^company_\d+__/', '', (string)$permissionRecord->name);
        $declaredPerm = ! empty($item['permission']) ? preg_replace('/^company_\d+__/', '', (string)$item['permission']) : '';

        // If the permission matched is a fallback/borrowed permission (e.g. leads.view for lead_products, reminders_tasks, or day_closing),
        // do not let the borrowed permission's display_name overwrite this item's specific label.
        $permEntity = explode('.', $basePermName)[0] ?? '';
        $itemEntity = explode('.', $itemKey)[0] ?? '';

        $isOwnPermission = ($declaredPerm !== '' && strcasecmp($basePermName, $declaredPerm) === 0)
            || ($permEntity !== '' && (
                strcasecmp($permEntity, $itemEntity) === 0 ||
                strcasecmp($permEntity, $itemKey) === 0
            ));

        if (! $isOwnPermission) {
            return $staticLabel;
        }

        // Extra safeguard: do not let a generic "Leads" label overwrite items that are not Leads
        if (strcasecmp($cleaned, 'leads') === 0 && ! in_array($itemKey, ['leads', 'all_leads'], true)) {
            return $staticLabel;
        }

        return $cleaned !== '' ? $cleaned : $staticLabel;
    }

    /**
     * Locate the matching permission record in the database for the given rule.
     * Checks primary permission and any aliases, prioritizing company-scoped permissions.
     */
    private function findPermissionRecord(array $rule, ?int $companyId, $dbPermissions): ?object
    {
        $candidates = [];
        if (! empty($rule['permission'])) {
            $candidates[] = $rule['permission'];
        }
        if (! empty($rule['permission_aliases']) && is_array($rule['permission_aliases'])) {
            foreach ($rule['permission_aliases'] as $alias) {
                if (! in_array($alias, $candidates, true)) {
                    $candidates[] = $alias;
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // 1. Check company-scoped permission name first (e.g. company_1__day_closing.menuview)
        if ($companyId) {
            foreach ($candidates as $cand) {
                $scopedName = "company_{$companyId}__{$cand}";
                $found = $dbPermissions->first(fn($p) => $p->name === $scopedName && (int)$p->company_id === (int)$companyId);
                if ($found) {
                    return $found;
                }
            }
        }

        // 2. Check company-matching permission with exact name
        if ($companyId) {
            foreach ($candidates as $cand) {
                $found = $dbPermissions->first(fn($p) => $p->name === $cand && (int)$p->company_id === (int)$companyId);
                if ($found) {
                    return $found;
                }
            }
        }

        // 3. Check global permission (company_id is null)
        foreach ($candidates as $cand) {
            $found = $dbPermissions->first(fn($p) => $p->name === $cand);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Same check used for both individual menu items AND whole-module gates
     * (the `gate` key in config/mobile_menu.php) — one rule set, two callers.
     */
    private function isVisible(User $user, array $rule, $dbPermissions = null, ?object &$matchedPermission = null): bool
    {
        if (! empty($rule['permission'])) {
            if ($dbPermissions === null) {
                $companyId = $user->company_id;
                $dbPermissions = \Illuminate\Support\Facades\DB::table('permissions')
                    ->where(function ($q) use ($companyId) {
                        $q->whereNull('company_id');
                        if ($companyId) {
                            $q->orWhere('company_id', $companyId);
                        }
                    })
                    ->get(['name', 'display_name', 'company_id']);
            }

            $matchedPermission = $this->findPermissionRecord($rule, $user->company_id, $dbPermissions);

            // If the permission does not exist in the database (deleted or renamed),
            // it MUST NOT be visible, even if the user is a super admin!
            if (! $matchedPermission) {
                return false;
            }

            // Check if user has permission to access this record or the base permission
            $hasAccess = $user->can($matchedPermission->name);
            if (! $hasAccess && ! empty($rule['permission']) && $matchedPermission->name !== $rule['permission']) {
                $hasAccess = $user->can($rule['permission']);
            }

            if (! $hasAccess) {
                return false;
            }
        }

        if (! empty($rule['require_method'])) {
            $method = $rule['require_method'];
            if (! method_exists($user, $method) || ! $user->{$method}()) {
                return false;
            }
        }

        if (! empty($rule['require_any_method'])) {
            $ok = false;
            foreach ($rule['require_any_method'] as $method) {
                if (method_exists($user, $method) && $user->{$method}()) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return false;
            }
        }

        // Inverse of require_method: hide when the method returns true.
        // Used by the CRM module gate (forbid isHrmsAttendanceOnlyUser) and by
        // every HRMS item except Dashboard/Attendance.
        if (! empty($rule['forbid_method'])) {
            $method = $rule['forbid_method'];
            if (method_exists($user, $method) && $user->{$method}()) {
                return false;
            }
        }

        return true;
    }

    private function passesGate(User $user, ?array $gate): bool
    {
        if (! $gate) {
            return true;
        }

        return $this->isVisible($user, $gate);
    }

    /**
     * Badge counts for the menu items that need them (Price Requests is
     * deliberately excluded — web has no badge or per-user "assigned"
     * concept for it, so mobile shows none either, per parity).
     *
     * Called only for items that already passed isVisible() above, so the
     * permission gate for each key (ovp_module.menuview, etc.) is already
     * satisfied here — no need to re-check it.
     */
    private function resolveBadgeCount(array $item, User $user): ?int
    {
        $count = match ($item['key'] ?? null) {
            'ovp_module'                   => $this->ovpBadgeCount($user),
            'production_approvals'         => $this->productionApprovalBadgeCount($user),
            'notifications'                => $this->notificationBadgeCount($user),
            'hrms.outside_office_approval' => self::getOutsideOfficePendingCount($user),
            'reminders_tasks'              => $this->remindersTasksOverdueBadgeCount($user),
            'hrms.leave'                   => self::getLeavePendingCount($user),
            'hrms.permission'              => self::getPermissionPendingCount($user),
            'hrms.od_request'              => self::getOdPendingCount($user),
            'hrms.expense_request'         => self::getExpensePendingCount($user),
            'price_requests'               => $this->priceRequestBadgeCount($user),
            default                        => null,
        };

        return ($count !== null && $count > 0) ? $count : null;
    }

    public static function getHrmsPendingCounts(User $user): array
    {
        return [
            'leave_request'           => self::getLeavePendingCount($user),
            'permission_request'      => self::getPermissionPendingCount($user),
            'od_request'              => self::getOdPendingCount($user),
            'expense_request'         => self::getExpensePendingCount($user),
            'outside_office_approval' => self::getOutsideOfficePendingCount($user),
        ];
    }

    public static function getLeavePendingCount(User $user): int
    {
        if (! class_exists(\App\Models\LeaveApproval::class)) {
            return 0;
        }

        return \App\Models\LeaveApproval::where('status', \App\Models\LeaveApproval::STATUS_PENDING)
            ->whereHas('leaveRequest', function ($q) use ($user) {
                $q->where('status', \App\Models\LeaveRequest::STATUS_PENDING)
                  ->where('user_id', '!=', $user->id);
            })
            ->where(function ($q) use ($user) {
                $q->where('approver_user_id', $user->id);
                if ($user->isSystemAdmin()) {
                    $q->orWhereRaw('1 = 1');
                }
            })
            ->whereHas('leaveRequest', function ($q) {
                $q->whereColumn('leave_requests.current_step', 'leave_approvals.step_key');
            })
            ->count();
    }

    public static function getPermissionPendingCount(User $user): int
    {
        if (! class_exists(\App\Models\PermissionApproval::class)) {
            return 0;
        }

        return \App\Models\PermissionApproval::where('status', \App\Models\PermissionApproval::STATUS_PENDING)
            ->whereHas('permissionRequest', function ($q) use ($user) {
                $q->where('status', \App\Models\PermissionRequest::STATUS_PENDING)
                  ->where('user_id', '!=', $user->id);
            })
            ->where(function ($q) use ($user) {
                $q->where('approver_user_id', $user->id);
                if ($user->isSystemAdmin()) {
                    $q->orWhereRaw('1 = 1');
                }
            })
            ->whereHas('permissionRequest', function ($q) {
                $q->whereColumn('permission_requests.current_step', 'permission_approvals.step_key');
            })
            ->count();
    }

    public static function getOdPendingCount(User $user): int
    {
        if (! class_exists(\App\Models\OdApproval::class)) {
            return 0;
        }

        return \App\Models\OdApproval::where('status', \App\Models\OdApproval::STATUS_PENDING)
            ->whereHas('odRequest', function ($q) use ($user) {
                $q->where('status', \App\Models\OdRequest::STATUS_PENDING)
                  ->where('user_id', '!=', $user->id);
            })
            ->where(function ($q) use ($user) {
                $q->where('approver_user_id', $user->id);
                if ($user->isSystemAdmin()) {
                    $q->orWhereRaw('1 = 1');
                }
            })
            ->whereHas('odRequest', function ($q) {
                $q->whereColumn('od_requests.current_step', 'od_approvals.step_key');
            })
            ->count();
    }

    public static function getExpensePendingCount(User $user): int
    {
        if (! class_exists(\App\Models\ExpenseRequest::class)) {
            return 0;
        }

        $companyId = $user->company_id;

        $userRoleIds = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->pluck('role_id')
            ->toArray();

        if (empty($userRoleIds) && $user->relationLoaded('roles')) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        $userRoleNames = \App\Models\Role::withoutGlobalScopes()
            ->whereIn('id', $userRoleIds)
            ->pluck('name')
            ->map(fn($n) => preg_replace('/^company_\d+__/', '', $n))
            ->toArray();

        $matchingRoleIds = \App\Models\Role::withoutGlobalScopes()
            ->where(function($q) use ($userRoleIds, $userRoleNames) {
                $q->whereIn('id', $userRoleIds);
                foreach ($userRoleNames as $rn) {
                    $q->orWhere('name', 'like', "%{$rn}%");
                }
            })
            ->pluck('id')
            ->toArray();

        return \App\Models\ExpenseRequest::where('status', 'pending')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($user, $matchingRoleIds) {
                $q->where('approver_id', $user->id)
                  ->orWhereIn('current_approver_role_id', $matchingRoleIds);
            })
            ->count();
    }

    public static function getOutsideOfficePendingCount(User $user): int
    {
        if (! class_exists(\App\Models\OutsideOfficeAttendanceRequest::class)) {
            return 0;
        }

        $canManage = $user->isSystemAdmin()
            || $user->belongsToHrDepartment()
            || $user->hasHrLikeRole()
            || $user->isCompanyAdmin()
            || $user->isBranchAdmin()
            || $user->isBranchManager();

        if (! $canManage) {
            return 0;
        }

        $isCompanyAdmin = $user->isSuperAdmin()
            || $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->hasRole('company_admin');

        $actingBranchId = $isCompanyAdmin ? null : $user->branch_id;

        $query = \App\Models\OutsideOfficeAttendanceRequest::where('status', \App\Models\OutsideOfficeAttendanceRequest::STATUS_PENDING);

        if ($actingBranchId) {
            $branch = \App\Models\Branch::find($actingBranchId);
            $branchCode = $branch?->code;
            $query->where(function ($sub) use ($actingBranchId, $branchCode) {
                $sub->whereHas('employee.portalUser', fn ($pu) => $pu->where('branch_id', $actingBranchId))
                    ->orWhereHas('intern.portalUser', fn ($pu) => $pu->where('branch_id', $actingBranchId));
                if ($branchCode && $branchCode !== 'STS') {
                    $sub->orWhereHas('employee', fn ($eq) => $eq->whereNull('portal_user_id')->where('employee_id', 'like', $branchCode . '%'))
                        ->orWhereHas('intern', fn ($iq) => $iq->whereNull('portal_user_id')->where('intern_id', 'like', $branchCode . '%'));
                }
            });
        }

        return $query->count();
    }

    private function priceRequestBadgeCount(User $user): int
    {
        if (class_exists(\App\Models\LeadPriceRequest::class)) {
            return \App\Models\LeadPriceRequest::where('status', 'pending')->count();
        }
        return 0;
    }

    /**
     * Same "Overdue" count CrmTaskApiController::index() computes for its
     * overdue tab badge — not-completed reminders whose remind_at date has
     * already passed, scoped through the exact same
     * DataVisibilityService::applyLeadRelationVisibility() used there, so
     * this always agrees with what the Reminders & Tasks screen itself
     * shows for the same user.
     */
    private function remindersTasksOverdueBadgeCount(User $user): int
    {
        $query = LeadReminder::query();
        $this->visibility->applyLeadRelationVisibility($query, 'lead', $user);

        return $query->where('is_completed', false)
            ->whereDate('remind_at', '<', now()->toDateString())
            ->count();
    }

    /**
     * Same query DashboardApiController::organizationDashboard() uses for
     * the "Outside Office Pending" Key Metrics card — deliberately NOT
     * date-scoped, since an unreviewed request from any day is still
     * actionable. Relies on OutsideOfficeAttendanceRequest::booted()'s own
     * company/branch visibility scope (mirrors DailyAttendance) rather than
     * re-deriving it here, so this always agrees with what the approval
     * queue itself shows for the same user.
     */
    private function outsideOfficePendingBadgeCount(): int
    {
        return OutsideOfficeAttendanceRequest::query()
            ->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING)
            ->count();
    }

    /**
     * Mirrors sidebar.blade.php's $ovpNewCount exactly: TL-like/admin-like
     * users see every OVP item created in the last 3 days; executive-scoped
     * users only see the ones allocated to them.
     */
    private function ovpBadgeCount(User $user): int
    {
        $isTlScoped = ! $user->hasAdminLikeRole()
            && ($this->hasRoleKey($user, self::OVP_TL_ROLE_KEYS) || $user->hasTlLikeRole());
        $isExecutiveScoped = ! $user->hasAdminLikeRole() && ! $isTlScoped
            && ($this->hasRoleKey($user, self::OVP_EXECUTIVE_ROLE_KEYS) || $user->hasExecutiveLikeRole());

        $query = ProductionInitiation::query()
            ->whereIn('status', ['ovp_pending', 'initiated']);

        if ($isExecutiveScoped) {
            $query->where('ovp_allocated_to', $user->id);
        }

        return $query->where('created_at', '>=', Carbon::now()->subDays(3))->count();
    }

    /**
     * Mirrors sidebar.blade.php's $prodApprovalPendingCount exactly — every
     * pending production approval visible to anyone with the menuview
     * permission, no further per-user scoping (web has none here either).
     */
    private function productionApprovalBadgeCount(User $user): int
    {
        return ProductionInitiation::query()
            ->whereIn('status', ['approval', 'approved'])
            ->where('production_approval_status', 'pending')
            ->count();
    }

    /** Mirrors NotificationApiController's branch-filtered unread count. */
    private function notificationBadgeCount(User $user): int
    {
        $query = $user->unreadNotifications();
        $targetBranchId = request()->header('X-Branch-Id') 
            ?: (request()->filled('branch_id') ? (int) request()->query('branch_id') : $user->branch_id);

        if ($targetBranchId !== null) {
            $query->where(function ($q) use ($targetBranchId) {
                $q->where('data->branch_id', $targetBranchId)
                  ->orWhere('data->branch_id', (string) $targetBranchId);
            });
        } elseif (! $user->isSystemAdmin()) {
            $allowedBranchIds = array_filter($user->getMyBranchIds());
            if (!empty($allowedBranchIds)) {
                $query->where(function ($q) use ($allowedBranchIds) {
                    foreach ($allowedBranchIds as $bId) {
                        $q->orWhere('data->branch_id', $bId)
                          ->orWhere('data->branch_id', (string) $bId);
                    }
                });
            }
        }

        return $query->count();
    }

    /** Mirrors sidebar.blade.php's inline $hasRoleKey closure. */
    private function hasRoleKey(User $user, array $keys): bool
    {
        $normalizedKeys = collect($keys)->map(fn (string $key) => $this->normalizeRoleKey($key))->filter()->unique();

        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) use ($normalizedKeys) {
            return $normalizedKeys->contains($this->normalizeRoleKey((string) $role->name))
                || $normalizedKeys->contains($this->normalizeRoleKey((string) ($role->display_name ?? '')));
        });
    }

    /** Mirrors sidebar.blade.php's inline $normalizeRole closure. */
    private function normalizeRoleKey(string $value): string
    {
        $value = Str::contains($value, '__') ? Str::afterLast($value, '__') : $value;

        return Str::of($value)
            ->lower()
            ->replace('&', 'and')
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }
}
