<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\Role;
use App\Models\RoleHierarchyMapping;
use App\Models\User;
use App\Models\UserMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class employeeMigration extends Command
{
    protected $signature = 'app:employee-migration
        {--limit= : Limit the number of source employees to migrate}
        {--emp-id=* : Migrate only the given source employee IDs}
        {--dry-run : Preview what will happen without saving anything}
        {--default-password= : Override the generated default password for newly created users}';

    protected $description = 'Migrate HRMS employee_details rows into employee_onboardings and portal users';

    private const EMPLOYEE_ID_PREFIX = 'STS';
    private const BASIC_SALARY_RATIO = 0.50;
    private const HRA_RATIO = 0.30;
    private const SPECIAL_ALLOWANCE_RATIO = 0.10;
    private const PF_CONTRIBUTION_RATE = 0.25;
    private const PF_BASIC_SALARY_CAP = 15000.00;
    private const PF_GROSS_SALARY_THRESHOLD = 21000.00;
    private const ESI_CONTRIBUTION_RATE = 0.04;
    private const ALLOWED_BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    private const TEAM_LEAD_ROLE_KEYS = ['tl', 'team_lead', 'team_leader', 'teamlead', 'manager'];

    public function handle(): int
    {
        $sourceRows = $this->sourceRows();

        if ($sourceRows->isEmpty()) {
            $this->warn('No employee rows found in sts_force_hrms.employee_details for the given filters.');

            return self::SUCCESS;
        }

        $branchesList = Branch::query()->get();

        if ($branchesList->isEmpty()) {
            $this->error('No branch found. Employee store logic requires a branch.');

            return self::FAILURE;
        }

        $createdBy = User::query()->where('is_active', true)->orderBy('id')->value('id');
        $designationLookup = $this->designationLookup();
        $credentials = [];
        $skipped = [];
        $created = 0;
        $updated = 0;
        $userCreated = 0;
        $userReused = 0;

        foreach ($sourceRows as $sourceRow) {
            $branch = $this->resolveBranch($sourceRow, $branchesList);
            $context = $this->buildContext($sourceRow, $designationLookup, $branch);

            if ($context['skip_reason'] !== null) {
                $skipped[] = [
                    'emp_id' => (string) $sourceRow->emp_id,
                    'name' => (string) $sourceRow->name,
                    'reason' => $context['skip_reason'],
                ];
                $this->warn("Skipped {$sourceRow->emp_id} ({$sourceRow->name}): {$context['skip_reason']}");
                continue;
            }

            $result = $this->option('dry-run')
                ? $this->previewRow($sourceRow, $context)
                : $this->persistRow($sourceRow, $context, $createdBy);

            if ($result['employee_action'] === 'created') {
                $created++;
            } elseif ($result['employee_action'] === 'updated') {
                $updated++;
            }

            if ($result['user_action'] === 'created') {
                $userCreated++;
                $credentials[] = [
                    'employee_id' => (string) $sourceRow->emp_id,
                    'name' => (string) $sourceRow->name,
                    'username' => $result['username'],
                    'password' => $result['password'],
                ];
            } else {
                $userReused++;
            }

            $this->info(sprintf(
                '%s %s (%s) | user: %s | employee: %s',
                $this->option('dry-run') ? 'Previewed' : 'Processed',
                $sourceRow->name,
                $sourceRow->emp_id,
                $result['user_action'],
                $result['employee_action']
            ));
        }

        $this->newLine();
        $this->info('Migration summary');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Source rows', $sourceRows->count()],
                ['Employees created', $created],
                ['Employees updated', $updated],
                ['Users created', $userCreated],
                ['Users reused', $userReused],
                ['Skipped', count($skipped)],
            ]
        );

        if ($credentials !== []) {
            $this->newLine();
            $this->warn('New portal credentials');
            $this->table(['Source Emp ID', 'Name', 'Username', 'Password'], $credentials);
            $this->line('Note: users table has no separate username column, so email is used as the username.');
        }

        if ($skipped !== []) {
            $this->newLine();
            $this->warn('Skipped rows');
            $this->table(['Source Emp ID', 'Name', 'Reason'], $skipped);
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run only. No records were written.');
        }

        return self::SUCCESS;
    }

    private function sourceRows(): Collection
    {
        $query = DB::connection('mysql2')
            ->table('employee_details')
            ->orderBy('id');

        $empIds = collect((array) $this->option('emp-id'))
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->values();

        if ($empIds->isNotEmpty()) {
            $query->whereIn('emp_id', $empIds);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function designationLookup(): array
    {
        return DB::connection('mysql2')
            ->table('designation_db')
            ->pluck('designation', 'id')
            ->mapWithKeys(fn ($designation, $id) => [(string) $id => (string) $designation])
            ->all();
    }

    private function buildContext(object $sourceRow, array $designationLookup, Branch $branch): array
    {
        $sourceDesignation = trim((string) ($designationLookup[(string) $sourceRow->desigination] ?? ''));
        $department = $this->resolveDepartment($sourceDesignation);

        if (! $department) {
            return ['skip_reason' => "Department mapping not found for source designation [{$sourceDesignation}]"];
        }

        $existingUser = $this->findExistingUser($sourceRow);
        $role = $this->resolveRole($sourceDesignation, $department, $existingUser);

        if (! $role) {
            return ['skip_reason' => "Role mapping not found for source designation [{$sourceDesignation}]"];
        }

        $teamLead = $this->resolveTeamLead($role, $branch);

        if (! $teamLead) {
            $roleLabel = $role->display_name ?: $role->name;

            return ['skip_reason' => "No valid TL/manager found for role [{$roleLabel}]"];
        }

        return [
            'skip_reason' => null,
            'source_designation' => $sourceDesignation,
            'department' => $department,
            'role' => $role,
            'team_lead' => $teamLead,
            'existing_user' => $existingUser,
            'branch' => $branch,
            'portal_email' => $this->portalEmail($sourceRow),
            'portal_password' => $this->portalPassword($sourceRow),
            'employee_status' => $this->employeeStatus($sourceRow),
        ];
    }

    private function previewRow(object $sourceRow, array $context): array
    {
        $userAction = $context['existing_user'] ? 'reused' : 'created';
        $existingEmployee = $this->findExistingEmployee($context['existing_user'], $context['portal_email']);

        return [
            'user_action' => $userAction,
            'employee_action' => $existingEmployee ? 'updated' : 'created',
            'username' => $context['portal_email'],
            'password' => $context['portal_password'],
        ];
    }

    private function persistRow(object $sourceRow, array $context, ?int $actorId): array
    {
        return DB::transaction(function () use ($sourceRow, $context, $actorId) {
            $userAction = 'reused';
            $user = $context['existing_user'];

            if (! $user) {
                $user = User::create([
                    'name' => (string) $sourceRow->name,
                    'email' => $context['portal_email'],
                    'password' => Hash::make($context['portal_password']),
                    'branch_id' => $context['branch']->id,
                    'company_id' => null,
                    'designation' => $context['source_designation'] ?: ($context['role']->display_name ?? $context['role']->name),
                    'is_active' => $context['employee_status'] === EmployeeOnboarding::STATUS_ACTIVE,
                ]);

                $user->syncRoles([$context['role']->name]);
                $userAction = 'created';
            } else {
                $attributes = [
                    'name' => (string) $sourceRow->name,
                    'email' => $context['portal_email'],
                    'branch_id' => $context['branch']->id,
                    'designation' => $context['source_designation'] ?: ($context['role']->display_name ?? $context['role']->name),
                    'is_active' => $context['employee_status'] === EmployeeOnboarding::STATUS_ACTIVE,
                ];

                $user->update($attributes);

                if ($user->roles->isEmpty()) {
                    $user->syncRoles([$context['role']->name]);
                }
            }

            $employee = $this->findExistingEmployee($user, $context['portal_email']);
            $employeeAction = $employee ? 'updated' : 'created';

            if (! $employee) {
                $employee = new EmployeeOnboarding();
                $employee->employee_id = $this->generateNextEmployeeId($context['branch']->code);
                $employee->created_by = $actorId;
                $employee->company_id = 1;
            }

            $employee->fill($this->employeeAttributes($sourceRow, $context));
            $employee->portal_user_id = $user->id;
            $employee->updated_by = $actorId;
            $employee->save();

            UserMapping::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'manager_id' => $context['team_lead']->id,
                    'company_id' => $user->company_id,
                ]
            );

            return [
                'user_action' => $userAction,
                'employee_action' => $employeeAction,
                'username' => $context['portal_email'],
                'password' => $context['portal_password'],
            ];
        });
    }

    private function resolveDepartment(string $sourceDesignation): ?Department
    {
        $departmentMap = [
            'web development' => 'Development',
            'mobile app development' => 'Development',
            'business development' => 'Sales',
            'accounts & hr' => 'HR & Accounts',
            'digital marketing' => 'Digital Marketing',
            'graphic designing' => 'Designing',
            'testing' => 'Testing',
        ];

        $departmentName = $departmentMap[Str::lower($sourceDesignation)] ?? null;

        if (! $departmentName) {
            return null;
        }

        return Department::query()->where('name', $departmentName)->first();
    }

    private function resolveRole(string $sourceDesignation, Department $department, ?User $existingUser): ?Role
    {
        if ($existingUser && $existingUser->roles->isNotEmpty()) {
            $existingRole = $existingUser->roles->first();

            if (! $existingRole->department_id || (int) $existingRole->department_id === (int) $department->id) {
                return $existingRole;
            }
        }

        $preferredRoleNames = [
            'web development' => 'junior_web_developer',
            'mobile app development' => 'junior_mobile_app_developer',
            'business development' => 'sales_executive',
            'accounts & hr' => 'hr',
        ];

        $preferredRoleName = $preferredRoleNames[Str::lower($sourceDesignation)] ?? null;

        if ($preferredRoleName) {
            $role = Role::withoutGlobalScopes()
                ->where('name', $preferredRoleName)
                ->where(function ($query) use ($department) {
                    $query->whereNull('department_id')
                        ->orWhere('department_id', $department->id);
                })
                ->first();

            if ($role) {
                return $role;
            }
        }

        return Role::withoutGlobalScopes()
            ->where('department_id', $department->id)
            ->orderBy('id')
            ->first();
    }

    private function resolveTeamLead(Role $role, Branch $branch): ?User
    {
        $mapping = RoleHierarchyMapping::with('parentRole')
            ->where('child_role_id', $role->id)
            ->first();

        if ($mapping) {
            $manager = User::query()
                ->where('is_active', true)
                ->where(function ($query) use ($branch) {
                    $query->whereNull('branch_id')
                        ->orWhere('branch_id', $branch->id);
                })
                ->whereHas('roles', fn ($query) => $query->where('roles.id', $mapping->parent_role_id))
                ->orderBy('id')
                ->first();

            if ($manager) {
                return $manager;
            }
        }

        $tl = User::query()
            ->with('roles.roleMapping')
            ->where('is_active', true)
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branch->id);
            })
            ->get()
            ->first(fn (User $user) => $this->userHasTeamLeadRoleForDepartment($user, $role->department_id));

        if ($tl) {
            return $tl;
        }

        // Fallback: search parent role globally across all branches
        if ($mapping) {
            $managerGlobal = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('roles.id', $mapping->parent_role_id))
                ->orderBy('id')
                ->first();

            if ($managerGlobal) {
                return $managerGlobal;
            }
        }

        // Fallback: search team lead globally across all branches
        $tlGlobal = User::query()
            ->with('roles.roleMapping')
            ->where('is_active', true)
            ->get()
            ->first(fn (User $user) => $this->userHasTeamLeadRoleForDepartment($user, $role->department_id));

        if ($tlGlobal) {
            return $tlGlobal;
        }

        // Final fallback: first active user
        return User::query()->where('is_active', true)->orderBy('id')->first();
    }

    private function findExistingUser(object $sourceRow): ?User
    {
        $email = $this->portalEmail($sourceRow);

        if ($email === '') {
            return null;
        }

        return User::with('roles')->where('email', $email)->first();
    }

    private function findExistingEmployee(?User $user, string $email): ?EmployeeOnboarding
    {
        return EmployeeOnboarding::query()
            ->when($user, function ($query) use ($user) {
                $query->where('portal_user_id', $user->id);
            }, function ($query) use ($email) {
                $query->where('email', $email);
            })
            ->latest('id')
            ->first();
    }

    private function portalEmail(object $sourceRow): string
    {
        $email = trim((string) ($sourceRow->emp_email ?? ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Str::lower($email);
        }

        $slug = Str::of((string) ($sourceRow->emp_id ?: $sourceRow->name ?: 'employee'))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        return $slug . '@import.local';
    }

    private function portalPassword(object $sourceRow): string
    {
        $override = trim((string) $this->option('default-password'));
        if ($override !== '') {
            return $override;
        }

        $mobile = preg_replace('/\D+/', '', (string) ($sourceRow->mobile_no ?? ''));
        if ($mobile !== '' && strlen($mobile) >= 8) {
            return $mobile;
        }

        $employeeCode = preg_replace('/\s+/', '', (string) ($sourceRow->emp_id ?? ''));
        if ($employeeCode !== '') {
            return $employeeCode . '@123';
        }

        return 'ChangeMe@123';
    }

    private function employeeAttributes(object $sourceRow, array $context): array
    {
        $grossSalary = $this->resolveGrossSalary($sourceRow);
        $address = $this->combinedAddress($sourceRow);

        $attributes = [
            'role_id' => $context['role']->id,
            'department_id' => $context['department']->id,
            'name' => $this->cleanString($sourceRow->name),
            'father_name' => $this->cleanString($sourceRow->second_name),
            'correspondence_address' => $address,
            'permanent_address' => $address,
            'mobile' => $this->cleanString($sourceRow->mobile_no),
            'email' => $context['portal_email'],
            'date_of_birth' => $this->normalizeDate($sourceRow->dob),
            'joining_date' => $this->normalizeDate($sourceRow->doj),
            'blood_group' => $this->normalizeBloodGroup($sourceRow->bloodgroup),
            'marital_status' => $this->normalizeMaritalStatus($sourceRow),
            'date_of_marriage' => $this->normalizeDate($sourceRow->weddingdate),
            'aadhaar_card_no' => $this->normalizeAadhaar($sourceRow->adhar_no),
            'pan_card_no' => $this->normalizePan($sourceRow->pan_no),
            'emergency_contact_name' => $this->cleanString($sourceRow->inchargename),
            'emergency_relation' => null,
            'emergency_contact_no' => $this->cleanString($sourceRow->emergency_no ?: $sourceRow->inchargeno),
            'bank_name' => $this->cleanString($sourceRow->bank_name),
            'bank_account_name' => $this->cleanString($sourceRow->account_name ?: $sourceRow->name),
            'bank_account_no' => $this->cleanString($sourceRow->account_no),
            'bank_ifsc_code' => $this->cleanString($sourceRow->ifcs_code),
            'bank_branch' => $this->cleanString($sourceRow->branch_name),
            'gross_salary' => $grossSalary,
            'salary_effective_from' => $this->normalizeDate($sourceRow->doj),
            'salary_payment_mode' => $sourceRow->account_no ? 'bank_transfer' : null,
            'status' => $context['employee_status'],
        ];

        return $this->normalizeSalaryAttributes($attributes);
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
                $attributes[$field] = 0;
            }

            $attributes['professional_tax'] = 0;
            $attributes['tds_amount'] = 0;
            $attributes['loan_deduction'] = 0;
            $attributes['other_deduction'] = 0;
            $attributes['pf_enabled'] = false;
            $attributes['esi_enabled'] = false;

            return $attributes;
        }

        $basicSalary = round($grossSalary * self::BASIC_SALARY_RATIO, 2);
        $hra = round($grossSalary * self::HRA_RATIO, 2);
        $specialAllowance = round($grossSalary * self::SPECIAL_ALLOWANCE_RATIO, 2);
        $otherAllowance = round(max($grossSalary - $basicSalary - $hra - $specialAllowance, 0), 2);

        $pfBaseAmount = $grossSalary > self::PF_GROSS_SALARY_THRESHOLD
            ? self::PF_BASIC_SALARY_CAP
            : ($basicSalary + $specialAllowance + $otherAllowance);

        $pfEmployeeContribution = round($pfBaseAmount * self::PF_CONTRIBUTION_RATE, 0);
        $pfEmployerContribution = round($pfBaseAmount * self::PF_CONTRIBUTION_RATE, 0);
        $esiEmployeeContribution = round($grossSalary * self::ESI_CONTRIBUTION_RATE, 0);
        $esiEmployerContribution = round($grossSalary * self::ESI_CONTRIBUTION_RATE, 0);

        $attributes['basic_salary'] = $basicSalary;
        $attributes['hra'] = $hra;
        $attributes['special_allowance'] = $specialAllowance;
        $attributes['other_allowance'] = $otherAllowance;
        $attributes['pf_enabled'] = true;
        $attributes['esi_enabled'] = true;
        $attributes['pf_employee_contribution'] = $pfEmployeeContribution;
        $attributes['pf_employer_contribution'] = $pfEmployerContribution;
        $attributes['esi_employee_contribution'] = $esiEmployeeContribution;
        $attributes['esi_employer_contribution'] = $esiEmployerContribution;
        $attributes['professional_tax'] = 0;
        $attributes['tds_amount'] = 0;
        $attributes['loan_deduction'] = 0;
        $attributes['other_deduction'] = 0;
        $attributes['total_deduction'] = round($pfEmployeeContribution + $esiEmployeeContribution, 2);
        $attributes['net_salary'] = round(max($grossSalary - $attributes['total_deduction'], 0), 2);

        return $attributes;
    }

    private function generateNextEmployeeId(string $branchCode): string
    {
        $latestEmployeeId = EmployeeOnboarding::query()
            ->where('employee_id', 'like', $branchCode . '%')
            ->orderByRaw('CAST(SUBSTRING(employee_id, ' . (strlen($branchCode) + 1) . ') AS UNSIGNED) DESC')
            ->value('employee_id');

        $nextNumber = 1;
        if ($latestEmployeeId) {
            $numPart = substr($latestEmployeeId, strlen($branchCode));
            if (is_numeric($numPart)) {
                $nextNumber = (int) $numPart + 1;
            }
        }

        return $branchCode . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function resolveBranch(object $sourceRow, Collection $branchesList): Branch
    {
        $branchName = '';
        if (isset($sourceRow->branch_id) && !empty($sourceRow->branch_id)) {
            $branchName = trim((string) $sourceRow->branch_id);
        } elseif (isset($sourceRow->branch_name) && !empty($sourceRow->branch_name)) {
            $branchName = trim((string) $sourceRow->branch_name);
        } elseif (isset($sourceRow->branch) && !empty($sourceRow->branch)) {
            $branchName = trim((string) $sourceRow->branch);
        }

        if (!empty($branchName)) {
            if (is_numeric($branchName)) {
                $branch = $branchesList->firstWhere('id', (int) $branchName);
                if ($branch) {
                    return $branch;
                }
            }

            $branch = $branchesList->first(function ($b) use ($branchName) {
                return strcasecmp($b->name, $branchName) === 0
                    || strcasecmp($b->code, $branchName) === 0
                    || str_contains(strtolower($b->name), strtolower($branchName))
                    || str_contains(strtolower($branchName), strtolower($b->name));
            });

            if ($branch) {
                return $branch;
            }
        }

        return $branchesList->firstWhere('is_default', true) ?: $branchesList->first();
    }

    private function resolveGrossSalary(object $sourceRow): float
    {
        $values = [
            (float) ($sourceRow->GR_all ?? 0),
            (float) ($sourceRow->uni_amount ?? 0),
            (float) ($sourceRow->basic_salary ?? 0),
        ];

        return round(max($values), 2);
    }

    private function combinedAddress(object $sourceRow): ?string
    {
        $parts = collect([
            $this->cleanString($sourceRow->address1),
            $this->cleanString($sourceRow->address2),
        ])->filter();

        return $parts->isEmpty() ? null : $parts->implode("\n");
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeBloodGroup(mixed $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        $map = [
            'A1+' => 'A+',
            'A1-' => 'A-',
            'A1B+' => 'AB+',
            'A1B-' => 'AB-',
            'A2B+' => 'AB+',
            'A2B-' => 'AB-',
        ];

        $value = $map[$value] ?? $value;

        return in_array($value, self::ALLOWED_BLOOD_GROUPS, true) ? $value : null;
    }

    private function normalizeMaritalStatus(object $sourceRow): string
    {
        if ($this->normalizeDate($sourceRow->weddingdate) !== null) {
            return 'married';
        }

        return trim((string) $sourceRow->maritalstatus) === '1' ? 'married' : 'single';
    }

    private function normalizeAadhaar(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return strlen($digits) === 12 ? $digits : null;
    }

    private function normalizePan(mixed $value): ?string
    {
        $pan = strtoupper(trim((string) $value));

        return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan) ? $pan : null;
    }

    private function employeeStatus(object $sourceRow): string
    {
        if (trim((string) $sourceRow->rejoining) === '1') {
            return EmployeeOnboarding::STATUS_ACTIVE;
        }

        return trim((string) $sourceRow->employee_status) === '0'
            ? EmployeeOnboarding::STATUS_ACTIVE
            : EmployeeOnboarding::STATUS_RESIGNED;
    }

    private function cleanString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function userHasTeamLeadRoleForDepartment(User $user, mixed $departmentId): bool
    {
        return $user->roles->contains(function (Role $role) use ($departmentId) {
            $departmentMatches = ! $departmentId
                || ! $role->department_id
                || (int) $role->department_id === (int) $departmentId;

            return $departmentMatches && $this->roleLooksLikeTeamLead($role);
        });
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
}