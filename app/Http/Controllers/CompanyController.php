<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\RoleHierarchyMapping;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->isSystemAdmin()) {
            $company = Company::findOrFail(auth()->user()->company_id);

            return redirect()->route('companies.show', $company);
        }

        $companies = Company::query()
            ->when($request->search, function ($query) use ($request) {
                $search = $request->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('company_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%');
                });
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('company_status', $request->status);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('pages.company.index', compact('companies'));
    }

    public function create()
    {
        $this->ensureMainSuperAdmin();

        return view('pages.company.create');
    }

    public function store(StoreCompanyRequest $request)
    {
        $this->ensureMainSuperAdmin();

        $company = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $company = Company::create([
                'company_name' => $data['company_name'],
                'email' => $data['email'],
                'mobile_number' => $data['mobile_number'],
                'address' => $data['address'],
                'number_of_accounts' => $data['number_of_accounts'],
                'expiry_date' => $data['expiry_date'],
                'company_status' => $data['company_status'],
                'facebook_client_id' => $data['facebook_client_id'],
                'facebook_client_secret' => $data['facebook_client_secret'],
            ]);

            $defaultRoles = $this->createDefaultCompanyRoles($company);
            $companyAdminRole = $defaultRoles['company_admin'];

            $superAdmin = User::create([
                'name' => $data['super_admin_name'],
                'email' => $data['super_admin_email'],
                'password' => Hash::make($data['super_admin_password']),
                'company_id' => $company->id,
                'is_active' => true,
            ]);

            $superAdmin->assignRole($companyAdminRole);

            $company->update(['super_admin_user_id' => $superAdmin->id]);

            Branch::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'name' => 'Default Branch',
                'code' => 'CMP' . $company->id . '-MAIN',
                'address' => $company->address,
                'email' => $company->email,
                'phone' => $company->mobile_number,
                'is_active' => true,
                'is_default' => true,
            ]);

            return $company;
        });

        $company->syncExpiryState();

        return redirect()
            ->route('companies.index')
            ->with('success', "Company <strong>{$company->company_name}</strong> created successfully.");
    }

    public function show(Company $company)
    {
        $this->ensureCompanyAccess($company);

        return view('pages.company.show', compact('company'));
    }

    public function edit(Company $company)
    {
        $this->ensureMainSuperAdmin();

        return view('pages.company.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $this->ensureMainSuperAdmin();

        $company->update($request->validated());
        $company->syncExpiryState();

        return redirect()
            ->route('companies.show', $company)
            ->with('success', "Company <strong>{$company->company_name}</strong> updated successfully.");
    }

    public function destroy(Company $company)
    {
        $this->ensureMainSuperAdmin();

        $companyName = $company->company_name;
        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', "Company <strong>{$companyName}</strong> deleted successfully.");
    }

    private function ensureCompanyAccess(Company $company): void
    {
        if (! auth()->user()->isSystemAdmin() && (int) auth()->user()->company_id !== (int) $company->id) {
            abort(403);
        }
    }

    private function ensureMainSuperAdmin(): void
    {
        abort_unless(auth()->user()->isSystemAdmin(), 403);
    }

    /**
     * Build the default company sales structure with starter permissions.
     *
     * @return array<string, \App\Models\Role>
     */
    private function createDefaultCompanyRoles(Company $company): array
    {
        $companyPermissions = Permission::ensureCrmPermissions($company->id);

        $excludedModules = [
            'ovp_module',
            'production_approval_module',
            'facebook_integration',
            'payroll_settings',
            'design_settings'
        ];

        $allPermissionNames = $companyPermissions->filter(function ($permission) use ($excludedModules) {
            return !in_array($permission->module, $excludedModules, true);
        })->pluck('name')->values()->all();

        $roleDefinitions = [
            'company_admin' => [
                'display_name' => 'Company Admin',
                'description' => 'Full access inside the company workspace.',
                'permissions' => $allPermissionNames,
                'parent' => null,
            ],
            'branch_admin' => [
                'display_name' => 'Branch Admin',
                'description' => 'Manages branch operations, team workflows, and HRMS records.',
                'permissions' => $allPermissionNames,
                'parent' => 'company_admin',
            ],
            'sales_manager' => [
                'display_name' => 'Sales Manager',
                'description' => 'Manages sales performance, approvals, and customer follow-up.',
                'permissions' => $this->tenantPermissionNames($company->id, [
                    'dashboard.menuview',
                    'dashboard.view',
                    'masters.menuview',
                    'masters.view',
                    'products.menuview',
                    'products.view',
                    'products.create',
                    'products.edit',
                    'products.delete',
                    'leads.menuview',
                    'leads.view',
                    'leads.create',
                    'leads.edit',
                    'leads.delete',
                    'leads.update',
                    'call_updates.menuview',
                    'call_updates.view',
                    'call_updates.create',
                    'call_updates.delete',
                    'quotations.menuview',
                    'quotations.view',
                    'quotations.create',
                    'quotations.delete',
                    'price_requests.menuview',
                    'price_requests.view',
                    'price_requests.create',
                    'price_requests.approve',
                    'price_requests.reject',
                    'projects.menuview',
                ]),
                'parent' => 'company_admin',
            ],
            'sales_tl' => [
                'display_name' => 'Sales TL',
                'description' => 'Leads a sales team and manages day-to-day CRM work.',
                'permissions' => $this->tenantPermissionNames($company->id, [
                    'dashboard.menuview',
                    'dashboard.view',
                    'masters.menuview',
                    'masters.view',
                    'products.menuview',
                    'products.view',
                    'leads.menuview',
                    'leads.view',
                    'leads.create',
                    'leads.edit',
                    'leads.update',
                    'call_updates.menuview',
                    'call_updates.view',
                    'call_updates.create',
                    'call_updates.delete',
                    'quotations.menuview',
                    'quotations.view',
                    'quotations.create',
                    'projects.menuview',
                ]),
                'parent' => 'sales_manager',
            ],
            'sales_executive' => [
                'display_name' => 'Sales Executive',
                'description' => 'Handles lead follow-up, calls, and quotation preparation.',
                'permissions' => $this->tenantPermissionNames($company->id, [
                    'dashboard.menuview',
                    'dashboard.view',
                    'products.menuview',
                    'products.view',
                    'leads.menuview',
                    'leads.view',
                    'leads.create',
                    'leads.edit',
                    'leads.update',
                    'call_updates.menuview',
                    'call_updates.view',
                    'call_updates.create',
                    'quotations.menuview',
                    'quotations.view',
                    'quotations.create',
                ]),
                'parent' => 'sales_tl',
            ],
            'sales_intern' => [
                'display_name' => 'Sales Intern',
                'description' => 'Supports lead capture and follow-up work with limited access.',
                'permissions' => $this->tenantPermissionNames($company->id, [
                    'dashboard.menuview',
                    'dashboard.view',
                    'products.menuview',
                    'products.view',
                    'leads.menuview',
                    'leads.view',
                    'leads.create',
                    'call_updates.menuview',
                    'call_updates.view',
                    'call_updates.create',
                ]),
                'parent' => 'sales_executive',
            ],
        ];

        $createdRoles = [];
        $roleLookup = [];

        foreach ($roleDefinitions as $roleKey => $definition) {
            $role = Role::withoutGlobalScopes()->firstOrCreate(
                [
                    'name' => Role::tenantRoleName($roleKey, $company->id),
                    'guard_name' => 'web',
                ],
                [
                    'display_name' => $definition['display_name'],
                    'description' => $definition['description'],
                    'company_id' => $company->id,
                ]
            );

            $role->syncPermissions($definition['permissions']);

            $createdRoles[$roleKey] = $role;
            $roleLookup[$roleKey] = $role;
        }

        foreach ($roleDefinitions as $roleKey => $definition) {
            if (! $definition['parent']) {
                continue;
            }

            RoleHierarchyMapping::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'child_role_id' => $createdRoles[$roleKey]->id,
                ],
                [
                    'parent_role_id' => $createdRoles[$definition['parent']]->id,
                ]
            );
        }

        return $roleLookup;
    }

    /**
     * Convert plain module.action keys into tenant-scoped permission names.
     *
     * @return array<int, string>
     */
    private function tenantPermissionNames(int $companyId, array $permissions): array
    {
        return collect($permissions)
            ->map(fn (string $permission) => Permission::tenantPermissionKey($permission, $companyId))
            ->values()
            ->all();
    }
}
