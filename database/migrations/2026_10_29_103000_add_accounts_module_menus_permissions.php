<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Define the modules and their available actions for the Accounts module.
     */
    protected array $permissionsData = [
        'accounts_dashboard' => [
            'menuview' => ['display' => 'Menuview Accounts Dashboard', 'desc' => 'Allows users to view Accounts Dashboard menu item.'],
            'view'     => ['display' => 'View Accounts Dashboard', 'desc' => 'Allows users to view Accounts Dashboard metrics and analytics.'],
        ],
        'accounts_ad_budget' => [
            'menuview' => ['display' => 'Menuview Ad Budget', 'desc' => 'Allows users to view Ad Budget menu item in Accounts module.'],
            'view'     => ['display' => 'View Ad Budget', 'desc' => 'Allows users to view Ad Budget details, clients, partners, and spend.'],
            'create'   => ['display' => 'Create Ad Budget', 'desc' => 'Allows users to create Ad Budget requests and spend entries.'],
            'edit'     => ['display' => 'Edit Ad Budget', 'desc' => 'Allows users to update Ad Budget requests, spend, and approvals.'],
            'delete'   => ['display' => 'Delete Ad Budget', 'desc' => 'Allows users to delete Ad Budget entries and spend records.'],
        ],
        'accounts_domains_hosting' => [
            'menuview' => ['display' => 'Menuview Domains & Hosting', 'desc' => 'Allows users to view Domains & Hosting menu item in Accounts module.'],
            'view'     => ['display' => 'View Domains & Hosting', 'desc' => 'Allows users to view Domains and Hosting services list.'],
            'create'   => ['display' => 'Create Domains & Hosting', 'desc' => 'Allows users to add new Domains and Hosting records.'],
            'edit'     => ['display' => 'Edit Domains & Hosting', 'desc' => 'Allows users to update Domains, Hosting, renewals, and migration.'],
            'delete'   => ['display' => 'Delete Domains & Hosting', 'desc' => 'Allows users to delete Domains and Hosting records.'],
        ],
        'accounts_quotations' => [
            'menuview' => ['display' => 'Menuview Accounts Quotations', 'desc' => 'Allows users to view Quotations menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Quotations', 'desc' => 'Allows users to view Quotations in Accounts module.'],
            'create'   => ['display' => 'Create Accounts Quotations', 'desc' => 'Allows users to create Quotations in Accounts module.'],
            'edit'     => ['display' => 'Edit Accounts Quotations', 'desc' => 'Allows users to edit Quotations in Accounts module.'],
            'delete'   => ['display' => 'Delete Accounts Quotations', 'desc' => 'Allows users to delete Quotations in Accounts module.'],
        ],
        'accounts_sales' => [
            'menuview' => ['display' => 'Menuview Accounts Sales', 'desc' => 'Allows users to view Sales menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Sales', 'desc' => 'Allows users to view Sales section and transactions.'],
            'create'   => ['display' => 'Create Accounts Sales', 'desc' => 'Allows users to create Sales transactions.'],
            'edit'     => ['display' => 'Edit Accounts Sales', 'desc' => 'Allows users to edit Sales transactions.'],
            'delete'   => ['display' => 'Delete Accounts Sales', 'desc' => 'Allows users to delete Sales transactions.'],
        ],
        'accounts_invoices' => [
            'menuview' => ['display' => 'Menuview Accounts Invoices', 'desc' => 'Allows users to view Invoices menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Invoices', 'desc' => 'Allows users to view Sales Invoices and details.'],
            'create'   => ['display' => 'Create Accounts Invoices', 'desc' => 'Allows users to create new Sales Invoices.'],
            'edit'     => ['display' => 'Edit Accounts Invoices', 'desc' => 'Allows users to update Sales Invoices.'],
            'delete'   => ['display' => 'Delete Accounts Invoices', 'desc' => 'Allows users to delete Sales Invoices.'],
        ],
        'accounts_payments' => [
            'menuview' => ['display' => 'Menuview Accounts Payments', 'desc' => 'Allows users to view Payments Received menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Payments', 'desc' => 'Allows users to view Invoice Payments received.'],
            'create'   => ['display' => 'Create Accounts Payments', 'desc' => 'Allows users to record new Payments received.'],
            'edit'     => ['display' => 'Edit Accounts Payments', 'desc' => 'Allows users to update Payment records.'],
            'delete'   => ['display' => 'Delete Accounts Payments', 'desc' => 'Allows users to delete Payment records.'],
        ],
        'accounts_credit_notes' => [
            'menuview' => ['display' => 'Menuview Accounts Credit Notes', 'desc' => 'Allows users to view Credit Notes menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Credit Notes', 'desc' => 'Allows users to view Credit Notes list and details.'],
            'create'   => ['display' => 'Create Accounts Credit Notes', 'desc' => 'Allows users to issue new Credit Notes.'],
            'edit'     => ['display' => 'Edit Accounts Credit Notes', 'desc' => 'Allows users to update or void Credit Notes.'],
            'delete'   => ['display' => 'Delete Accounts Credit Notes', 'desc' => 'Allows users to delete Credit Notes.'],
        ],
        'accounts_purchase_orders' => [
            'menuview' => ['display' => 'Menuview Purchase Orders', 'desc' => 'Allows users to view PO Orders menu item in Accounts module.'],
            'view'     => ['display' => 'View Purchase Orders', 'desc' => 'Allows users to view Purchase Orders list and details.'],
            'create'   => ['display' => 'Create Purchase Orders', 'desc' => 'Allows users to create new Purchase Orders.'],
            'edit'     => ['display' => 'Edit Purchase Orders', 'desc' => 'Allows users to update Purchase Orders and statuses.'],
            'delete'   => ['display' => 'Delete Purchase Orders', 'desc' => 'Allows users to delete Purchase Orders.'],
        ],
        'accounts_expenses' => [
            'menuview' => ['display' => 'Menuview Accounts Expenses', 'desc' => 'Allows users to view Expense menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Expenses', 'desc' => 'Allows users to view Expenses list and Pivot report.'],
            'create'   => ['display' => 'Create Accounts Expenses', 'desc' => 'Allows users to record and create new Expenses.'],
            'edit'     => ['display' => 'Edit Accounts Expenses', 'desc' => 'Allows users to update Expense records.'],
            'delete'   => ['display' => 'Delete Accounts Expenses', 'desc' => 'Allows users to delete Expense records and attachments.'],
        ],
        'accounts_reports' => [
            'menuview' => ['display' => 'Menuview Accounts Reports', 'desc' => 'Allows users to view Reports menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Reports', 'desc' => 'Allows users to view Accounts Reports (Balance Sheet, Category Wise, P&L).'],
            'create'   => ['display' => 'Create Accounts Reports', 'desc' => 'Allows users to generate and export Accounts Reports.'],
            'edit'     => ['display' => 'Edit Accounts Reports', 'desc' => 'Allows users to configure Accounts Reports filters and settings.'],
            'delete'   => ['display' => 'Delete Accounts Reports', 'desc' => 'Allows users to delete generated Accounts Reports.'],
        ],
        'accounts_master' => [
            'menuview' => ['display' => 'Menuview Accounts Master', 'desc' => 'Allows users to view Master menu item in Accounts module.'],
            'view'     => ['display' => 'View Accounts Master', 'desc' => 'Allows users to view Accounts Master configurations.'],
            'create'   => ['display' => 'Create Accounts Master', 'desc' => 'Allows users to create new Accounts Master records.'],
            'edit'     => ['display' => 'Edit Accounts Master', 'desc' => 'Allows users to update Accounts Master records and statuses.'],
            'delete'   => ['display' => 'Delete Accounts Master', 'desc' => 'Allows users to delete Accounts Master records.'],
        ],
        'accounts_zoho' => [
            'menuview' => ['display' => 'Menuview Zoho Books Sync', 'desc' => 'Allows users to view Zoho Books Sync menu item in Accounts module.'],
            'view'     => ['display' => 'View Zoho Books Sync', 'desc' => 'Allows users to view Zoho Books sync status, settings, and migration lists.'],
            'create'   => ['display' => 'Create Zoho Books Sync', 'desc' => 'Allows users to initiate Zoho Books sync operations.'],
            'edit'     => ['display' => 'Edit Zoho Books Sync', 'desc' => 'Allows users to migrate Zoho records and update Zoho credentials.'],
            'delete'   => ['display' => 'Delete Zoho Books Sync', 'desc' => 'Allows users to disconnect or remove Zoho Books integrations.'],
        ],
    ];

    public function up(): void
    {
        $timestamp = now();
        $records = [];
        $permissionNames = [];

        foreach ($this->permissionsData as $module => $actions) {
            foreach ($actions as $action => $meta) {
                $name = $module . '.' . $action;
                $permissionNames[] = $name;
                $records[] = [
                    'name'         => $name,
                    'guard_name'   => 'web',
                    'display_name' => $meta['display'],
                    'module'       => $module,
                    'description'  => $meta['desc'],
                    'company_id'   => null,
                    'created_at'   => $timestamp,
                    'updated_at'   => $timestamp,
                ];
            }
        }

        DB::table('permissions')->upsert(
            $records,
            ['name', 'guard_name'],
            ['display_name', 'module', 'description', 'company_id', 'updated_at']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Assign to Super Admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $createdPermissions = Permission::whereIn('name', $permissionNames)->where('guard_name', 'web')->get();
            $superAdmin->givePermissionTo($createdPermissions);
        }

        // Assign to all Company Admin roles
        $companyAdminRoles = Role::where(function ($q) {
            $q->where('name', 'like', '%company_admin')
              ->orWhere('name', 'like', '%admin');
        })->get();

        $allCreated = Permission::whereIn('name', $permissionNames)->where('guard_name', 'web')->get();
        foreach ($companyAdminRoles as $role) {
            try {
                $role->givePermissionTo($allCreated);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Grant to existing users who already have Accounts Module access
        User::query()
            ->with('roles')
            ->chunkById(100, function ($users) use ($permissionNames) {
                foreach ($users as $user) {
                    try {
                        if ($user->canAccessAccountsModule()) {
                            foreach ($permissionNames as $permName) {
                                if (! $user->hasPermissionTo($permName)) {
                                    $user->givePermissionTo($permName);
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $modules = array_keys($this->permissionsData);

        DB::table('permissions')
            ->whereIn('module', $modules)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
