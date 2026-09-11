<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeOnboardingRequest;
use App\Http\Requests\UpdateEmployeeOnboardingRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\Role;
use App\Models\User;
use App\Models\UserMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeOnboardingController extends Controller
{
    private const FILE_DIRECTORY = 'employee_onboarding';
    private const EMPLOYEE_ID_PREFIX = 'STS';
    private const TEAM_LEAD_ROLE_KEYS = ['tl', 'team_lead', 'team_leader', 'teamlead', 'manager'];
    private const BASIC_SALARY_RATIO = 0.50;
    private const HRA_RATIO = 0.30;
    private const SPECIAL_ALLOWANCE_RATIO = 0.10;
    private const PF_EMPLOYEE_RATE = 0.12;
    private const PF_EMPLOYER_RATE = 0.13;
    private const PF_BASIC_SALARY_CAP = 15000.00;
    private const PF_GROSS_SALARY_THRESHOLD = 21000.00;
    private const ESI_EMPLOYEE_RATE = 0.0075;
    private const ESI_EMPLOYER_RATE = 0.0325;

    private const DOCUMENT_LABELS = [
        'photograph' => 'Photograph',
        'signature' => 'Signature',
        'document_10th_marksheet' => '10th Marksheet',
        'document_12th_marksheet' => '12th Marksheet',
        'document_consolidated_marksheet' => 'Consolidated Marksheet',
        'document_course_completion_certificate' => 'Course Completion Certificate',
        'document_degree_certificate' => 'Degree Certificate',
        'document_provisional_certificate' => 'Provisional Certificate',
        'document_tc' => 'TC',
        'document_aadhaar_card' => 'Aadhaar Card',
        'document_pan_card' => 'Pan Card',
        'document_voter_id' => 'Voter ID',
        'document_driving_licence' => 'Driving Licence',
        'document_experience_certificate' => 'Experience Certificate & Relieving Letter',
        'document_salary_slips' => 'Last 3 Salary Slips / Salary Certificate',
        'document_bank_passbook' => 'Bank Passbook',
    ];

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $employees = EmployeeOnboarding::query()
            ->with(['role', 'department', 'sourceIntern', 'educations', 'familyDetails', 'portalUser.branch'])
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('employee_id', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('aadhaar_card_no', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('branch_id'), function ($query) use ($request) {
                $branchId = $request->integer('branch_id');
                $branch = Branch::withoutGlobalScopes()->find($branchId);
                $query->where(function ($sub) use ($branchId, $branch) {
                    $sub->whereHas('portalUser', fn ($q) => $q->where('branch_id', $branchId));
                    if ($branch && $branch->code) {
                        $sub->orWhere(function ($q2) use ($branch) {
                            $q2->whereNull('portal_user_id')
                               ->where('employee_id', 'like', $branch->code . '%');
                        });
                    }
                });
            })
            ->when($request->filled('department_id'), function ($query) use ($request) {
                $query->where('department_id', $request->integer('department_id'));
            })
            ->when($request->filled('role_id'), function ($query) use ($request) {
                $query->where('role_id', $request->integer('role_id'));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $companyId = $this->currentCompanyId();
        $departments = Department::when($companyId, fn ($q) => $q->where('company_id', $companyId))->orderBy('name')->get(['id', 'name']);
        $branches = Branch::where('is_active', true)->when($companyId, fn ($q) => $q->where('company_id', $companyId))->orderBy('name')->get(['id', 'name', 'code']);
        $roles = Role::with(['department', 'roleParentMapping.parentRole'])
            ->where('company_id', $companyId)
            ->orderByRaw('COALESCE(display_name, name)')
            ->get();

        return view('pages.hrms.employee_onboarding.index', compact('employees', 'departments', 'branches', 'roles'));
    }

    public function updateStatus(Request $request, EmployeeOnboarding $employee_onboarding): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,resigned'],
        ]);

        $newStatus = $validated['status'];

        DB::transaction(function () use ($employee_onboarding, $newStatus) {
            $employee_onboarding->status = $newStatus;
            $employee_onboarding->updated_by = auth()->id();
            $employee_onboarding->save();

            if ($employee_onboarding->portalUser) {
                $employee_onboarding->portalUser->is_active = ($newStatus === EmployeeOnboarding::STATUS_ACTIVE);
                $employee_onboarding->portalUser->save();
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Status for {$employee_onboarding->name} updated to " . ucfirst($newStatus) . ".",
                'status' => $newStatus,
                'status_label' => ucfirst($newStatus),
                'portal_user_active' => $employee_onboarding->portalUser?->is_active,
            ]);
        }

        return back()->with('success', "Status for <strong>{$employee_onboarding->name}</strong> updated to <strong>" . ucfirst($newStatus) . "</strong>.");
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $companyId = $this->currentCompanyId();

        return view('pages.hrms.employee_onboarding.create', [
            'documentLabels' => self::DOCUMENT_LABELS,
            'generatedEmployeeId' => $this->generateNextEmployeeId(old('branch_id', auth()->user()?->branch_id)),
            'roles' => Role::with(['department', 'roleParentMapping.parentRole'])
                ->where('company_id', $companyId)
                ->orderByRaw('COALESCE(display_name, name)')
                ->get(),
            'departments' => Department::when($companyId, fn ($q) => $q->where('company_id', $companyId))->orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->when($companyId, fn ($q) => $q->where('company_id', $companyId))->orderBy('name')->get(),
            'tlUsers' => $this->teamLeadUsers($companyId),
        ]);
    }

    public function store(StoreEmployeeOnboardingRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $validated = $request->validated();

        $employee = DB::transaction(function () use ($request, $validated) {
            $portalUser = $this->syncPortalAccount(null, $validated);

            $employee = new EmployeeOnboarding();
            $employee->fill($this->extractAttributes($validated));
            $employee->employee_id = $this->generateNextEmployeeId($validated['branch_id'] ?? null);
            $employee->portal_user_id = $portalUser->id;
            $employee->created_by = auth()->id();
            $employee->updated_by = auth()->id();
            $this->fillFileAttributes($employee, $request);
            $employee->save();

            $this->syncRelatedRows($employee, $validated);
            $this->syncPortalMapping($portalUser, $validated);

            return $employee;
        });

        return redirect()
            ->route('employee-onboarding.show', $employee)
            ->with('success', "Employee onboarding for <strong>{$employee->name}</strong> created successfully.");
    }

    public function show(EmployeeOnboarding $employee_onboarding): View
    {
        $user = auth()->user();
        $isOwnProfile = $user && (int) $employee_onboarding->portal_user_id === (int) $user->id;
        $isHrOrAdmin = $user && $user->isHrOrAdmin();

        abort_unless($isHrOrAdmin || $isOwnProfile, 403, 'Unauthorized.');

        $employee_onboarding->load([
            'educations',
            'employments',
            'familyDetails',
            'creator',
            'updater',
            'sourceIntern',
            'role.department',
            'department',
            'portalUser.branch',
            'portalUser.roles',
            'portalUser.managerMappings.manager',
        ]);

        return view('pages.hrms.employee_onboarding.show', [
            'employee' => $employee_onboarding,
            'documentLabels' => self::DOCUMENT_LABELS,
        ]);
    }

    public function edit(EmployeeOnboarding $employee_onboarding): View
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $employee_onboarding->load([
            'educations',
            'employments',
            'familyDetails',
            'sourceIntern',
            'role.department',
            'department',
            'portalUser.branch',
            'portalUser.roles',
            'portalUser.managerMappings.manager',
        ]);

        $companyId = $this->currentCompanyId($employee_onboarding);

        $roles = Role::with(['department', 'roleParentMapping.parentRole'])
            ->where(function ($q) use ($companyId, $employee_onboarding) {
                $q->where('company_id', $companyId);
                if ($employee_onboarding->role_id) {
                    $q->orWhere('id', $employee_onboarding->role_id);
                }
            })
            ->orderByRaw('COALESCE(display_name, name)')
            ->get();

        return view('pages.hrms.employee_onboarding.edit', [
            'employee' => $employee_onboarding,
            'documentLabels' => self::DOCUMENT_LABELS,
            'generatedEmployeeId' => $employee_onboarding->employee_id,
            'roles' => $roles,
            'departments' => Department::when($companyId, fn ($q) => $q->where('company_id', $companyId))->orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->when($companyId, fn ($q) => $q->where('company_id', $companyId))->orderBy('name')->get(),
            'tlUsers' => $this->teamLeadUsers($companyId),
        ]);
    }

    public function update(UpdateEmployeeOnboardingRequest $request, EmployeeOnboarding $employee_onboarding): RedirectResponse
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $employee_onboarding) {
            $portalUser = $this->syncPortalAccount($employee_onboarding->portalUser, $validated);

            $employee_onboarding->fill($this->extractAttributes($validated));
            if ($portalUser) {
                $employee_onboarding->portal_user_id = $portalUser->id;
            }

            if (!empty($validated['branch_id'])) {
                $newBranchCode = Branch::withoutGlobalScopes()->where('id', $validated['branch_id'])->value('code') ?: self::EMPLOYEE_ID_PREFIX;
                $newBranchCode = trim((string) $newBranchCode);
                if ($newBranchCode !== '' && !str_starts_with((string) $employee_onboarding->employee_id, $newBranchCode)) {
                    $employee_onboarding->employee_id = $this->generateNextEmployeeId((int) $validated['branch_id']);
                }
            }

            $employee_onboarding->updated_by = auth()->id();
            $this->fillFileAttributes($employee_onboarding, $request, true);
            $employee_onboarding->save();

            $this->syncRelatedRows($employee_onboarding, $validated);
            if ($portalUser) {
                $this->syncPortalMapping($portalUser, $validated);
            }
        });

        return redirect()
            ->route('employee-onboarding.show', $employee_onboarding)
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(EmployeeOnboarding $employee_onboarding): RedirectResponse
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $employeeName = $employee_onboarding->name;

        DB::transaction(function () use ($employee_onboarding) {
            foreach (EmployeeOnboarding::DOCUMENT_FIELDS as $field) {
                $this->deleteStoredFile($employee_onboarding->{$field});
            }

            $employee_onboarding->educations()->delete();
            $employee_onboarding->employments()->delete();
            $employee_onboarding->familyDetails()->delete();
            $employee_onboarding->delete();
        });

        return redirect()
            ->route('employee-onboarding.index')
            ->with('success', "Employee onboarding for <strong>{$employeeName}</strong> deleted successfully.");
    }

    private function extractAttributes(array $validated): array
    {
        $attributes = Arr::except($validated, array_merge([
            'educations',
            'employments',
            'family_details',
            'employee_id',
            'portal_email',
            'portal_password',
            'branch_id',
            'tl_user_id',
        ], EmployeeOnboarding::DOCUMENT_FIELDS));

        if (($attributes['marital_status'] ?? null) !== 'married') {
            $attributes['date_of_marriage'] = null;
        }

        return $this->normalizeSalaryAttributes($attributes);
    }

    private function authorizeEmployeeDataAccess(): void
    {
        abort_if(auth()->user()?->isHrmsAttendanceOnlyUser(), 403);
    }

    private function normalizeSalaryAttributes(array $attributes): array
    {
        $grossSalary = round((float) ($attributes['gross_salary'] ?? 0), 2);

        if ($grossSalary <= 0) {
            foreach ([
                'basic_salary',
                'hra',
                'special_allowance',
                'other_allowance',
                'pf_employee_contribution',
                'pf_employer_contribution',
                'esi_employee_contribution',
                'esi_employer_contribution',
                'total_deduction',
                'net_salary',
            ] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $attributes[$field] = 0;
                }
            }

            return $attributes;
        }

        $basicSalary = round($grossSalary * self::BASIC_SALARY_RATIO, 2);
        $hra = round($grossSalary * self::HRA_RATIO, 2);
        $specialAllowance = round($grossSalary * self::SPECIAL_ALLOWANCE_RATIO, 2);
        $otherAllowance = round(max($grossSalary - $basicSalary - $hra - $specialAllowance, 0), 2);

        $pfEnabled = (bool) ($attributes['pf_enabled'] ?? false);
        $esiEnabled = (bool) ($attributes['esi_enabled'] ?? false);

        $pfBaseAmount = $grossSalary > self::PF_GROSS_SALARY_THRESHOLD
            ? self::PF_BASIC_SALARY_CAP
            : ($basicSalary + $specialAllowance + $otherAllowance);
        $pfEmployeeContribution = $pfEnabled ? round($pfBaseAmount * self::PF_EMPLOYEE_RATE, 2) : 0;
        $pfEmployerContribution = $pfEnabled ? round($pfBaseAmount * self::PF_EMPLOYER_RATE, 2) : 0;
        $esiEmployeeContribution = $esiEnabled ? round($grossSalary * self::ESI_EMPLOYEE_RATE, 2) : 0;
        $esiEmployerContribution = $esiEnabled ? round($grossSalary * self::ESI_EMPLOYER_RATE, 2) : 0;

        $professionalTax = round((float) ($attributes['professional_tax'] ?? 0), 2);
        $tdsAmount = round((float) ($attributes['tds_amount'] ?? 0), 2);
        $loanDeduction = round((float) ($attributes['loan_deduction'] ?? 0), 2);
        $otherDeduction = round((float) ($attributes['other_deduction'] ?? 0), 2);
        $totalDeduction = round($pfEmployeeContribution + $esiEmployeeContribution + $professionalTax + $tdsAmount + $loanDeduction + $otherDeduction, 2);
        $netSalary = round(max($grossSalary - $totalDeduction, 0), 2);

        $attributes['basic_salary'] = $basicSalary;
        $attributes['hra'] = $hra;
        $attributes['special_allowance'] = $specialAllowance;
        $attributes['other_allowance'] = $otherAllowance;
        $attributes['pf_employee_contribution'] = $pfEmployeeContribution;
        $attributes['pf_employer_contribution'] = $pfEmployerContribution;
        $attributes['esi_employee_contribution'] = $esiEmployeeContribution;
        $attributes['esi_employer_contribution'] = $esiEmployerContribution;
        $attributes['total_deduction'] = $totalDeduction;
        $attributes['net_salary'] = $netSalary;

        return $attributes;
    }

    private function syncPortalAccount(?User $user, array $validated): ?User
    {
        $email = trim((string) ($validated['portal_email'] ?? ''));
        $password = (string) ($validated['portal_password'] ?? '');

        if (! $user && $email === '') {
            return null;
        }

        $role = Role::withoutGlobalScopes()->find($validated['role_id'] ?? null);

        $attributes = [
            'name' => $validated['name'],
            'email' => $email,
        ];

        if (array_key_exists('branch_id', $validated)) {
            $attributes['branch_id'] = $validated['branch_id'] ?: null;
        }

        if (array_key_exists('status', $validated)) {
            $attributes['is_active'] = ($validated['status'] === EmployeeOnboarding::STATUS_ACTIVE);
        }

        if (! $user) {
            $attributes['company_id'] = auth()->user()?->company_id;
            $attributes['is_active'] = array_key_exists('status', $validated)
                ? ($validated['status'] === EmployeeOnboarding::STATUS_ACTIVE)
                : true;
            $attributes['password'] = Hash::make($password);

            $user = User::create($attributes);
        } else {
            if ($password !== '') {
                $attributes['password'] = Hash::make($password);
            }

            $user->update($attributes);
        }

        if ($role) {
            $user->syncRoles([$role->name]);
        }

        return $user;
    }

    private function syncPortalMapping(User $user, array $validated): void
    {
        if (! array_key_exists('tl_user_id', $validated)) {
            return;
        }

        if (empty($validated['tl_user_id'])) {
            UserMapping::withoutGlobalScopes()->where('user_id', $user->id)->delete();
            return;
        }

        UserMapping::withoutGlobalScopes()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'manager_id' => $validated['tl_user_id'],
                'company_id' => $user->company_id ?: auth()->user()?->company_id,
            ]
        );
    }

    private function fillFileAttributes(EmployeeOnboarding $employee, Request $request, bool $isUpdate = false): void
    {
        foreach (EmployeeOnboarding::DOCUMENT_FIELDS as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            if ($isUpdate) {
                $this->deleteStoredFile($employee->{$field});
            }

            $employee->{$field} = $this->uploadToPublicFolder($request->file($field));
        }
    }

    private function syncRelatedRows(EmployeeOnboarding $employee, array $validated): void
    {
        $educations = $this->sanitizeRows($validated['educations'] ?? [], [
            'qualification',
            'institution_name',
            'year_of_passing',
            'percentage',
            'specialization',
        ]);
        $employments = $this->sanitizeRows($validated['employments'] ?? [], [
            'organisation',
            'designation',
            'period_from',
            'period_to',
            'annual_ctc',
        ]);
        $familyDetails = $this->sanitizeRows($validated['family_details'] ?? [], [
            'name',
            'relation',
            'occupation',
            'date_of_birth',
            'mobile_no',
        ]);

        $employee->educations()->delete();
        $employee->employments()->delete();
        $employee->familyDetails()->delete();

        if ($educations !== []) {
            $employee->educations()->createMany($educations);
        }
        if ($employments !== []) {
            $employee->employments()->createMany($employments);
        }
        if ($familyDetails !== []) {
            $employee->familyDetails()->createMany($familyDetails);
        }
    }

    private function sanitizeRows(array $rows, array $keys): array
    {
        $cleanRows = [];

        foreach (array_values($rows) as $index => $row) {
            $row = is_array($row) ? $row : [];

            if (! $this->rowHasData($row, $keys)) {
                continue;
            }

            $cleanRows[] = collect($row)
                ->only($keys)
                ->map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                })
                ->put('sort_order', $index + 1)
                ->all();
        }

        return $cleanRows;
    }

    private function rowHasData(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function deleteStoredFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $publicFilePath = public_path($path);
        if (file_exists($publicFilePath) && is_file($publicFilePath)) {
            @unlink($publicFilePath);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uploadToPublicFolder(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $targetDir = public_path(self::FILE_DIRECTORY);

        if (! file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        return self::FILE_DIRECTORY . '/' . $filename;
    }

    private function currentCompanyId(?EmployeeOnboarding $employee = null): int
    {
        return (int) (
            $employee?->portalUser?->company_id
            ?: ($employee?->portalUser?->branch?->company_id
            ?: (auth()->user()?->company_id
            ?: (\App\Models\Company::first()?->id ?: 1)))
        );
    }

    private function teamLeadUsers(?int $companyId = null): Collection
    {
        $companyId = $companyId ?: $this->currentCompanyId();
        $companySuperAdminId = null;

        if ($companyId) {
            $companySuperAdminId = optional(\App\Models\Company::find($companyId))->super_admin_user_id;
        }

        return User::withoutGlobalScopes()
            ->with(['roles.roleMapping', 'branch'])
            ->where('is_active', true)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->roles->isNotEmpty() || (int) $user->id === (int) $companySuperAdminId || $user->isSuperAdmin() || $user->isCompanyAdmin())
            ->map(function (User $user) use ($companySuperAdminId) {
                $teamLeadRoles = $user->roles->filter(fn (Role $role) => $this->roleLooksLikeTeamLead($role));
                $displayRoles = $teamLeadRoles->isNotEmpty() ? $teamLeadRoles : $user->roles;
                $isSuperAdmin = $user->isSuperAdmin() || (int) $user->id === (int) $companySuperAdminId;
                $isCompanyAdmin = $user->isCompanyAdmin() || $isSuperAdmin || $user->hasRole('company_admin') || Str::contains($user->roles->pluck('name')->implode(','), 'company_admin');
                $isExecutive = $isCompanyAdmin || $isSuperAdmin || $this->isExecutiveUser($user);

                return [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'branch_id' => (string) ($user->branch_id ?? ''),
                    'branch_name' => $user->branch?->name,
                    'department_ids' => $displayRoles
                        ->map(fn (Role $role) => (string) ($role->department_id ?? ''))
                        ->unique()
                        ->values()
                        ->all(),
                    'role_ids' => $user->roles
                        ->map(fn (Role $role) => (string) $role->id)
                        ->unique()
                        ->values()
                        ->all(),
                    'role_label' => $displayRoles
                        ->map(fn (Role $role) => $this->roleLabel($role))
                        ->filter()
                        ->unique()
                        ->implode(', ') ?: ($isSuperAdmin ? 'Super Admin' : ($isCompanyAdmin ? 'Company Admin' : 'Team Lead')),
                    'is_super_admin' => $isSuperAdmin,
                    'is_company_admin' => $isCompanyAdmin,
                    'is_executive' => $isExecutive,
                    'is_branch_admin_or_manager' => $this->isBranchAdminOrManager($user),
                ];
            })
            ->values();
    }

    private function isExecutiveUser(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isCompanyAdmin() || $user->hasRole('company_admin')) {
            return true;
        }

        $executiveKeys = [
            'company_admin',
            'super_admin',
            'admin',
            'chief_business_officer',
            'cheif_business_officer',
            'cbo',
            'chief_operating_officer',
            'cheif_operating_officer',
            'coo',
            'managing_director',
            'director',
            'president',
            'vice_president',
            'vp',
        ];

        return $user->roles->contains(function (Role $role) use ($executiveKeys) {
            $roleKeys = collect([$role->name, $role->display_name])
                ->filter()
                ->flatMap(function (string $roleName) {
                    $normalized = $this->normalizeRoleKey($roleName);
                    return [$normalized, Str::afterLast($normalized, '__')];
                })
                ->unique();

            return $roleKeys->intersect($executiveKeys)->isNotEmpty();
        });
    }

    private function isBranchAdminOrManager(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin()) {
            return true;
        }

        $adminOrManagerKeys = [
            'branch_admin',
            'branch_manager',
            'manager',
            'admin',
            'company_admin',
            'super_admin',
            'coo',
            'cbo',
            'chief_operating_officer',
            'cheif_operating_officer',
            'chief_business_officer',
        ];

        return $user->roles->contains(function (Role $role) use ($adminOrManagerKeys) {
            $roleKeys = collect([$role->name, $role->display_name])
                ->filter()
                ->flatMap(function (string $roleName) {
                    $normalized = $this->normalizeRoleKey($roleName);

                    return [$normalized, Str::afterLast($normalized, '__')];
                })
                ->unique();

            return $roleKeys->intersect($adminOrManagerKeys)->isNotEmpty();
        });
    }

    private function userHasTeamLeadRole(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles->contains(fn (Role $role) => $this->roleLooksLikeTeamLead($role));
    }

    private function roleLooksLikeTeamLead(Role $role): bool
    {
        if ($role->relationLoaded('roleMapping') && $role->roleMapping?->access_level === 'team') {
            return true;
        }

        $roleKeys = collect([$role->name, $role->display_name])
            ->filter()
            ->flatMap(function (string $roleName) {
                $normalized = $this->normalizeRoleKey($roleName);

                return [$normalized, Str::afterLast($normalized, '__')];
            })
            ->unique();

        return $roleKeys->intersect(self::TEAM_LEAD_ROLE_KEYS)->isNotEmpty();
    }

    private function normalizeRoleKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }

    private function roleLabel(Role $role): string
    {
        return $role->display_name ?: Str::of(Str::afterLast($role->name, '__'))->replace('_', ' ')->title()->value();
    }

    public function getGeneratedId(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()?->isHrOrAdmin(), 403, 'Unauthorized.');

        $branchId = $request->query('branch_id');
        $employeeId = $this->generateNextEmployeeId($branchId ? (int) $branchId : null);
        return response()->json(['employee_id' => $employeeId]);
    }

    public function updatePhoto(Request $request, EmployeeOnboarding $employee_onboarding): RedirectResponse
    {
        $user = auth()->user();
        $isOwnProfile = $user && (int) $employee_onboarding->portal_user_id === (int) $user->id;
        $isHrOrAdmin = $user && $user->isHrOrAdmin();

        abort_unless($isHrOrAdmin || $isOwnProfile, 403, 'Unauthorized action.');

        $request->validate([
            'photograph' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('photograph')) {
            $this->deleteStoredFile($employee_onboarding->photograph);
            $employee_onboarding->photograph = $this->uploadToPublicFolder($request->file('photograph'));
            $employee_onboarding->save();

            $portalUser = $employee_onboarding->portalUser;
            if ($portalUser) {
                $portalUser->photo = $employee_onboarding->photograph;
                $portalUser->save();
            }
        }

        return back()->with('success', 'Profile photo updated successfully.');
    }

    public function updateDocument(Request $request, EmployeeOnboarding $employee_onboarding): RedirectResponse
    {
        $user = auth()->user();
        $isOwnProfile = $user && (int) $employee_onboarding->portal_user_id === (int) $user->id;
        $isHrOrAdmin = $user && $user->isHrOrAdmin();

        abort_unless($isHrOrAdmin || $isOwnProfile, 403, 'Unauthorized action.');

        $request->validate([
            'document_field' => ['required', 'string', 'in:' . implode(',', EmployeeOnboarding::DOCUMENT_FIELDS)],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt', 'max:10240'],
        ]);

        $field = $request->input('document_field');

        if ($request->hasFile('document_file')) {
            $this->deleteStoredFile($employee_onboarding->{$field});
            $employee_onboarding->{$field} = $this->uploadToPublicFolder($request->file('document_file'));
            $employee_onboarding->save();

            if ($field === 'photograph') {
                $portalUser = $employee_onboarding->portalUser;
                if ($portalUser) {
                    $portalUser->photo = $employee_onboarding->photograph;
                    $portalUser->save();
                }
            }
        }

        $label = self::DOCUMENT_LABELS[$field] ?? 'Document';

        return back()->with('success', "{$label} updated successfully.");
    }

    private function generateNextEmployeeId(?int $branchId = null): string
    {
        $branchCode = null;
        if ($branchId) {
            $branchCode = Branch::withoutGlobalScopes()->where('id', $branchId)->value('code');
        }

        $prefix = $branchCode ?: self::EMPLOYEE_ID_PREFIX;
        $prefix = trim((string) $prefix);

        $employeeIds = EmployeeOnboarding::withoutGlobalScopes()
            ->where('employee_id', 'like', $prefix . '%')
            ->pluck('employee_id');

        $lastNumber = 0;
        foreach ($employeeIds as $empId) {
            $numPart = substr($empId, strlen($prefix));
            if (is_numeric($numPart)) {
                $lastNumber = max($lastNumber, (int) $numPart);
            }
        }

        return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
