<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    private const CRM_PERMISSION_MAP = [
        'dashboard' => ['menuview', 'view'],
        'masters' => ['menuview', 'view'],
        'products' => ['menuview', 'view', 'create', 'edit', 'delete'],
        'form_customization' => ['menuview'],
        'leads' => ['menuview', 'view', 'create', 'edit', 'delete', 'update'],
        'call_updates' => ['menuview', 'view', 'create', 'delete'],
        'quotations' => ['menuview', 'view', 'create', 'delete', 'approve'],
        'price_requests' => ['menuview', 'view', 'create', 'approve', 'reject'],
        'projects' => ['menuview'],
        'ovp_module' => ['menuview'],
        'production_approval_module' => ['menuview'],
        'settings' => ['menuview', 'view', 'manage'],
        'authentication' => ['menuview', 'view', 'manage'],
        'users' => ['view', 'manage'],
        'roles' => ['view', 'manage'],
        'permissions' => ['view', 'manage'],
        'companies' => ['view', 'manage'],
        'employees' => ['menuview'],
        'recruitment' => ['menuview'],
        'interns' => ['menuview'],
        'attendance' => ['menuview'],
        'timesheet_lop' => ['menuview', 'view'],
        'payroll' => ['menuview'],
        'announcements' => ['menuview'],
        'leave_requests' => ['menuview'],
        'permission_requests' => ['menuview'],
        'od_request' => ['menuview'],
        'visitor_management' => ['menuview'],
        'dynamic_forms' => ['menuview'],
        'facility_management' => ['menuview'],
        'assets' => ['menuview'],
        'holiday_calendar' => ['menuview'],
        'house_keeping' => ['menuview'],
        'lead_status' => ['menuview', 'view', 'create', 'edit', 'delete'],
        'lead_source' => ['menuview', 'view', 'create', 'edit', 'delete'],
        'outcome_category' => ['menuview', 'view', 'create', 'edit', 'delete'],
        'outcome_sub_category' => ['menuview', 'view', 'create', 'edit', 'delete'],
        'product_category' => ['menuview', 'view', 'create', 'edit', 'delete'],
        'product_attributes' => ['menuview', 'view', 'create', 'edit', 'delete'],
        'quotation_settings' => ['menuview', 'manage'],
        'branches' => ['menuview', 'manage'],
        'payroll_settings' => ['menuview', 'manage'],
        'design_settings' => ['menuview', 'manage'],
        'facebook_integration' => ['menuview', 'manage'],
        'support' => ['menuview', 'view', 'create', 'update', 'delete'],
        'expense_pipeline' => ['menuview', 'manage'],
        'leave_hierarchy' => ['menuview', 'manage'],
    ];

    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'module',
        'description',
        'company_id',
    ];

    public static function tenantPermissionName(string $module, string $action, ?int $companyId): string
    {
        return $module . '.' . $action;
    }

    public static function tenantPermissionKey(string $permission, ?int $companyId): string
    {
        return $permission;
    }

    public static function ensureCrmPermissions(?int $companyId = null): Collection
    {
        $permissions = collect();

        foreach (self::CRM_PERMISSION_MAP as $module => $actions) {
            $moduleLabel = Str::title(str_replace('_', ' ', $module));

            foreach ($actions as $action) {
                $permissions->push(
                    static::withoutGlobalScopes()->firstOrCreate(
                        [
                            'name' => static::tenantPermissionName($module, $action, null),
                            'guard_name' => 'web',
                        ],
                        [
                            'display_name' => Str::title(str_replace('_', ' ', $action)) . ' ' . $moduleLabel,
                            'module' => $module,
                            'description' => 'Allows users to ' . str_replace('_', ' ', $action) . ' ' . strtolower($moduleLabel) . '.',
                            'company_id' => null,
                        ]
                    )
                );
            }
        }

        return $permissions;
    }
}
