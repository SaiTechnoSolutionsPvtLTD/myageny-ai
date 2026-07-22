<?php

namespace App\Services;

use App\Models\ProductionInitiation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MenuService
{
    /** Module key => switcher metadata. Add a line here when a new module ships. */
    private const MODULE_META = [
        'crm'      => ['label' => 'CRM',      'order' => 10],
        'projects' => ['label' => 'Projects', 'order' => 20],
        'hrms'     => ['label' => 'HRMS',     'order' => 30],
    ];

    // ── OVP role scoping — mirrors resources/views/layouts/sidebar.blade.php
    // (the $ovpNewCount block) and App\Http\Controllers\App\OvpModuleApiController
    // exactly, so the badge always agrees with what the OVP list itself shows.
    private const OVP_TL_ROLE_KEYS = ['customer_support_team_tl'];
    private const OVP_EXECUTIVE_ROLE_KEYS = ['customer_support_team_executive'];

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

        return $this->filterAndSort($config['items'] ?? [], $user);
    }

    /**
     * Which modules can this user switch into at all — feeds the mobile
     * module-switcher FAB. A module is offered iff the user passes its gate;
     * reuses the exact same gate check build() uses, so this is never a
     * second, independently-drifting authorization path.
     */
    public function accessibleModules(User $user): array
    {
        $modules = [];

        foreach (config('mobile_menu', []) as $key => $config) {
            if ($this->passesGate($user, $config['gate'] ?? null)) {
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

    private function filterAndSort(array $items, User $user): array
    {
        $visible = [];

        foreach ($items as $item) {
            if (! $this->isVisible($user, $item)) {
                continue;
            }

            $node = [
                'key'         => $item['key'],
                'label'       => $item['label'],
                'section'     => $item['section'] ?? null,
                'order'       => $item['order'] ?? 0,
                'badge_count' => $this->resolveBadgeCount($item, $user),
            ];

            $node['children'] = ! empty($item['children'])
                ? $this->filterAndSort($item['children'], $user)
                : [];

            $visible[] = $node;
        }

        usort($visible, fn($a, $b) => $a['order'] <=> $b['order']);

        return array_values($visible);
    }

    /**
     * Same check used for both individual menu items AND whole-module gates
     * (the `gate` key in config/mobile_menu.php) — one rule set, two callers.
     */
    private function isVisible(User $user, array $rule): bool
    {
        if (! empty($rule['permission']) && ! $user->can($rule['permission'])) {
            return false;
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

        // NEW — inverse of require_method: hide when the method returns true.
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
        return match ($item['key'] ?? null) {
            'ovp_module'           => $this->ovpBadgeCount($user),
            'production_approvals' => $this->productionApprovalBadgeCount($user),
            'notifications'        => $this->notificationBadgeCount($user),
            default                => null,
        };
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

    /** Mirrors the web bell icon's $notificationUnreadCount exactly. */
    private function notificationBadgeCount(User $user): int
    {
        return $user->unreadNotifications()->count();
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
