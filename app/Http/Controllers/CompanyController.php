<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Permission;
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
                'company_status' => $data['company_status'],
                'facebook_client_id' => $data['facebook_client_id'],
                'facebook_client_secret' => $data['facebook_client_secret'],
            ]);

            $role = Role::withoutGlobalScopes()->firstOrCreate(
                [
                    'name' => Role::tenantRoleName('company_admin', $company->id),
                    'guard_name' => 'web',
                ],
                [
                    'display_name' => 'Company Admin',
                    'description' => 'Full access inside the company workspace.',
                    'company_id' => $company->id,
                ]
            );

            $role->syncPermissions(Permission::ensureCrmPermissions($company->id));

            $superAdmin = User::create([
                'name' => $data['super_admin_name'],
                'email' => $data['super_admin_email'],
                'password' => Hash::make($data['super_admin_password']),
                'company_id' => $company->id,
                'is_active' => true,
            ]);

            $superAdmin->assignRole($role);

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
}
