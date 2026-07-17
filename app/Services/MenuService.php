<?php

namespace App\Services;

use App\Models\User;

class MenuService
{
    /** Module key => switcher metadata. Add a line here when a new module ships. */
    private const MODULE_META = [
        'crm'      => ['label' => 'CRM',      'order' => 10],
        'projects' => ['label' => 'Projects', 'order' => 20],
        'hrms'     => ['label' => 'HRMS',     'order' => 30],
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

        foreach (self::MODULE_META as $key => $meta) {
            $config = config("mobile_menu.$key");
            if ($config && $this->passesGate($user, $config['gate'] ?? null)) {
                $modules[] = ['key' => $key, 'label' => $meta['label'], 'order' => $meta['order']];
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

    private function resolveBadgeCount(array $item, User $user): ?int
    {
        return null; // unchanged — see laravel-mobile-menu-permissions.md
    }
}