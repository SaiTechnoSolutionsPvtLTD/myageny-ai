<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamilyDetail;
use App\Models\EmployeeOnboarding;
use App\Models\Role;
use App\Models\User;
use App\Models\UserMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeDataMigrationAndUserCreateCommand extends Command
{
    protected $signature = 'app:employee-data-migrate {--dry-run : Preview changes without persisting}';
    protected $description = 'Migrate employees from sts_hrms_new database, mapping to existing branches in master, education, family details, and creating users for active employees.';

    public function handle()
    {
        $this->info("Starting employee data migration...");

        // Ensure mysql2 connection is valid
        try {
            DB::connection('mysql2')->getPdo();
        } catch (\Exception $e) {
            $this->error("Failed to connect to mysql2 database (sts_hrms_new). Details: " . $e->getMessage());
            return self::FAILURE;
        }

        // Fetch current branches master list
        $branchesList = Branch::all();
        if ($branchesList->isEmpty()) {
            $this->error("No branches found in the branches master table. Please populate branches first.");
            return self::FAILURE;
        }

        // Fetch designation lookup from old database
        $designationLookup = [];
        try {
            $designations = DB::connection('mysql2')->table('designation_db')->get();
            foreach ($designations as $des) {
                $designationLookup[$des->id] = $des->designation;
            }
        } catch (\Exception $e) {
            $this->warn("Could not load designation_db table. Will fallback to original_designation or defaults.");
        }

        // Fetch source employee details
        $oldEmployees = DB::connection('mysql2')->table('employee_details')->orderBy('id')->get();
        if ($oldEmployees->isEmpty()) {
            $this->warn("No employees found in the old database.");
            return self::SUCCESS;
        }

        $createdCount = 0;
        $updatedCount = 0;
        $userCreatedCount = 0;
        $skippedCount = 0;

        foreach ($oldEmployees as $oldEmp) {
            $name = trim($oldEmp->name . ' ' . ($oldEmp->second_name ?? ''));
            $email = trim($oldEmp->emp_email ?? '');
            $mobile = trim($oldEmp->mobile_no ?? '');

            if (empty($name)) {
                $this->warn("Skipping record with empty name (Old ID: {$oldEmp->id})");
                $skippedCount++;
                continue;
            }

            // 1. Resolve Branch from the existing branches in our database (do not create any new branches)
            $branch = $this->resolveBranch($oldEmp, $branchesList);

            // 2. Resolve Status (active / inactive)
            // employee_status = 0 or rejoining = 1 means active, employee_status = 1 means resigned/inactive
            $isActive = trim((string) ($oldEmp->rejoining ?? '')) === '1'
                || trim((string) ($oldEmp->employee_status ?? '')) === '0';
            $status = $isActive ? 'active' : 'resigned';

            // 3. Resolve Designation & Department
            $designationText = trim($oldEmp->original_designation ?? '');
            if (empty($designationText) && isset($designationLookup[$oldEmp->desigination])) {
                $designationText = $designationLookup[$oldEmp->desigination];
            }
            if (empty($designationText)) {
                $designationText = 'Employee';
            }

            $departmentId = $this->resolveDepartmentId($designationText);
            $roleId = $this->resolveRoleId($departmentId, $designationText);

            // 4. Resolve Existing User & Employee Onboarding to update or create
            $existingUser = null;
            if (!empty($email)) {
                $existingUser = User::withTrashed()->where('email', $email)->first();
            }

            $existingEmployee = $this->findExistingEmployee($existingUser, $email, $mobile);

            if ($this->option('dry-run')) {
                $action = $existingEmployee ? 'Would update' : 'Would create';
                $this->info("[Dry Run] {$action} Employee: {$name} | Status: {$status} | Mapped Branch: {$branch->name} (Code: {$branch->code})");
                continue;
            }

            // --- Persisting Changes ---
            DB::transaction(function () use (
                $oldEmp, $name, $email, $mobile, $branch, $status, $isActive,
                $designationText, $departmentId, $roleId, &$existingUser, &$existingEmployee,
                &$createdCount, &$updatedCount, &$userCreatedCount
            ) {
                // Generate employee_id if new
                if ($existingEmployee) {
                    $employeeId = $existingEmployee->employee_id;
                } else {
                    $employeeId = $this->generateNextEmployeeId($branch->code);
                }

                // A. Handle User account creation (For all employees)
                $portalUserId = null;
                $userEmail = $email;
                if (empty($userEmail)) {
                    $userEmail = 'noemail_' . strtolower($employeeId) . '@myagency.com';
                }

                // Check if User already exists with this resolved email (including soft deleted)
                $user = User::withTrashed()->where('email', $userEmail)->first();
                if (!$user) {
                    $user = User::create([
                        'company_id' => 1,
                        'name' => $name,
                        'email' => $userEmail,
                        'password' => Hash::make('Welcome@123'),
                        'branch_id' => $branch->id,
                        'designation' => $designationText,
                        'is_active' => $isActive,
                    ]);
                    // Sync role if resolved
                    if ($roleId) {
                        $role = Role::find($roleId);
                        if ($role) {
                            $user->syncRoles([$role->name]);
                        }
                    }
                    $userCreatedCount++;
                    $this->info("Created portal User: {$userEmail} for {$name} (Active: " . ($isActive ? 'Yes' : 'No') . ")");
                } else {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $user->update([
                        'name' => $name,
                        'branch_id' => $branch->id,
                        'designation' => $designationText,
                        'is_active' => $isActive,
                    ]);
                }
                $portalUserId = $user->id;

                // Sync manager mapping if possible
                if ($roleId && $isActive) {
                    $this->resolveAndSetManager($user, $roleId, $branch->id);
                }

                // B. Save Employee Onboarding
                $dob = $this->normalizeDate($oldEmp->dob);
                $doj = $this->normalizeDate($oldEmp->doj);
                $dom = $this->normalizeDate($oldEmp->weddingdate);

                $gross = $this->resolveSalaryValue($oldEmp->GR_all ?? 0);
                $basic = $this->resolveSalaryValue($oldEmp->basic_salary ?? 0);

                $employeeData = [
                    'company_id' => 1,
                    'role_id' => $roleId,
                    'department_id' => $departmentId,
                    'portal_user_id' => $portalUserId,
                    'name' => $name,
                    'correspondence_address' => trim(($oldEmp->address1 ?? '') . "\n" . ($oldEmp->address2 ?? '')),
                    'permanent_address' => trim(($oldEmp->address1 ?? '') . "\n" . ($oldEmp->address2 ?? '')),
                    'mobile' => $mobile,
                    'email' => $email, // Leave blank if empty
                    'date_of_birth' => $dob,
                    'joining_date' => $doj,
                    'blood_group' => $this->normalizeBloodGroup($oldEmp->bloodgroup ?? ''),
                    'marital_status' => (trim((string) ($oldEmp->maritalstatus ?? '')) === '1' || !empty($dom)) ? 'married' : 'single',
                    'date_of_marriage' => $dom,
                    'aadhaar_card_no' => $this->normalizeDigits($oldEmp->adhar_noo ?? $oldEmp->adhar_no ?? ''),
                    'pan_card_no' => $this->normalizePan($oldEmp->pan_noo ?? $oldEmp->pan_no ?? ''),
                    'emergency_contact_name' => trim($oldEmp->inchargename ?? ''),
                    'emergency_contact_no' => $this->normalizeDigits($oldEmp->emergency_no ?? $oldEmp->inchargeno ?? ''),
                    'bank_name' => trim($oldEmp->bank_name ?? ''),
                    'bank_account_name' => trim($oldEmp->account_name ?? ''),
                    'bank_account_no' => trim($oldEmp->account_no ?? ''),
                    'bank_ifsc_code' => trim($oldEmp->ifcs_code ?? ''),
                    'bank_branch' => trim($oldEmp->branch_name ?? ''),
                    'gross_salary' => $gross,
                    'basic_salary' => $basic ?: round($gross * 0.50, 2),
                    'hra' => round($gross * 0.30, 2),
                    'special_allowance' => round($gross * 0.10, 2),
                    'other_allowance' => round(max($gross * 0.10, 0), 2),
                    'status' => $status,
                ];

                if ($existingEmployee) {
                    $existingEmployee->update($employeeData);
                    $existingEmployee->portal_user_id = $portalUserId;
                    $existingEmployee->save();
                    $updatedCount++;
                } else {
                    $employeeData['employee_id'] = $employeeId;
                    $existingEmployee = EmployeeOnboarding::create($employeeData);
                    $createdCount++;
                }

                // C. Import Education Details
                EmployeeEducation::where('employee_onboarding_id', $existingEmployee->id)->delete();
                $oldEdus = DB::connection('mysql2')->table('emp_eduction')
                    ->where('emped_id', $oldEmp->id)
                    ->get();

                $eduIndex = 1;
                foreach ($oldEdus as $oldEdu) {
                    EmployeeEducation::create([
                        'employee_onboarding_id' => $existingEmployee->id,
                        'qualification' => trim($oldEdu->std_level ?? $oldEdu->degree ?? 'Education'),
                        'specialization' => trim($oldEdu->degree ?? ''),
                        'institution_name' => trim($oldEdu->edu_university ?? ''),
                        'year_of_passing' => trim($oldEdu->yearpassout ?? ''),
                        'sort_order' => $eduIndex++,
                    ]);
                }

                // D. Import Family Details
                EmployeeFamilyDetail::where('employee_onboarding_id', $existingEmployee->id)->delete();
                $oldFams = DB::connection('mysql2')->table('emp_familydetails')
                    ->where('empp_id', $oldEmp->id)
                    ->get();

                $famIndex = 1;
                foreach ($oldFams as $oldFam) {
                    EmployeeFamilyDetail::create([
                        'employee_onboarding_id' => $existingEmployee->id,
                        'name' => trim($oldFam->fname ?? ''),
                        'relation' => trim($oldFam->frelation ?? 'Family'),
                        'mobile_no' => $this->normalizeDigits($oldFam->fmobile_no ?? ''),
                        'sort_order' => $famIndex++,
                    ]);
                }
            });
        }

        $this->newLine();
        $this->info("Migration completed successfully!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Employees Created', $createdCount],
                ['Employees Updated', $updatedCount],
                ['Portal Users Created', $userCreatedCount],
                ['Skipped', $skippedCount],
            ]
        );

        return self::SUCCESS;
    }

    private function resolveBranch(object $sourceRow, \Illuminate\Support\Collection $branchesList): Branch
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

    private function findExistingEmployee(?User $user, string $email, ?string $mobile): ?EmployeeOnboarding
    {
        $query = EmployeeOnboarding::query();

        if ($user) {
            $query->where('portal_user_id', $user->id);
        }
        if (!empty($email)) {
            $query->orWhere('email', $email);
        }
        if (!empty($mobile)) {
            $query->orWhere('mobile', $mobile);
        }

        return $query->latest('id')->first();
    }

    private function generateNextEmployeeId(string $branchCode): string
    {
        $latestEmployeeId = EmployeeOnboarding::where('employee_id', 'like', $branchCode . '%')
            ->orderByRaw('CAST(SUBSTRING(employee_id, ' . (strlen($branchCode) + 1) . ') AS UNSIGNED) DESC')
            ->value('employee_id');

        $nextNum = 1;
        if ($latestEmployeeId) {
            $numPart = substr($latestEmployeeId, strlen($branchCode));
            if (is_numeric($numPart)) {
                $nextNum = (int)$numPart + 1;
            }
        }

        return $branchCode . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    private function resolveDepartmentId(string $designation): ?int
    {
        $d = strtolower($designation);
        if (str_contains($d, 'developer') || str_contains($d, 'php') || str_contains($d, 'programmer') || str_contains($d, 'flutter') || str_contains($d, 'web') || str_contains($d, 'software') || str_contains($d, 'technical')) {
            return 1; // Development
        }
        if (str_contains($d, 'design') || str_contains($d, 'ux') || str_contains($d, 'ui') || str_contains($d, 'graphic') || str_contains($d, 'video')) {
            return 2; // Designing
        }
        if (str_contains($d, 'digital') || str_contains($d, 'marketing') || str_contains($d, 'seo') || str_contains($d, 'social media') || str_contains($d, 'content')) {
            return 3; // Digital Marketing
        }
        if (str_contains($d, 'sales') || str_contains($d, 'telecall') || str_contains($d, 'bde') || str_contains($d, 'business development') || str_contains($d, 'pre-sales') || str_contains($d, 'executive') || str_contains($d, 'business associate') || str_contains($d, 'business executive')) {
            return 4; // Sales
        }
        if (str_contains($d, 'support') || str_contains($d, 'customer') || str_contains($d, 'success')) {
            return 5; // Customer Support Team
        }
        if (str_contains($d, 'test') || str_contains($d, 'qa')) {
            return 6; // Testing
        }
        if (str_contains($d, 'hr') || str_contains($d, 'account') || str_contains($d, 'finance') || str_contains($d, 'admin') || str_contains($d, 'recruit')) {
            return 7; // HR & Accounts
        }
        return null;
    }

    private function resolveRoleId(?int $departmentId, string $designation): ?int
    {
        if ($departmentId === 4) {
            $d = strtolower($designation);
            if (str_contains($d, 'manager')) {
                return 4; // Sales Manager
            }
            if (str_contains($d, 'tl') || str_contains($d, 'lead') || str_contains($d, 'leader')) {
                return 5; // Sales TL
            }
            if (str_contains($d, 'intern')) {
                return 7; // Sales Intern
            }
            return 6; // Sales Executive
        }
        return null;
    }

    private function resolveAndSetManager(User $user, int $roleId, int $branchId)
    {
        // Try to map to parent manager role if hierarchy exists
        $mapping = DB::table('role_hierarchy_mappings')
            ->where('child_role_id', $roleId)
            ->first();

        $manager = null;
        if ($mapping) {
            $manager = User::where('is_active', true)
                ->where('branch_id', $branchId)
                ->whereHas('roles', fn ($q) => $q->where('id', $mapping->parent_role_id))
                ->first();
        }

        if (!$manager) {
            // Default to any active sales manager or sales TL in the branch
            $manager = User::where('is_active', true)
                ->where('branch_id', $branchId)
                ->whereHas('roles', fn ($q) => $q->whereIn('id', [4, 5]))
                ->first();
        }

        if ($manager) {
            UserMapping::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'manager_id' => $manager->id,
                    'company_id' => 1,
                ]
            );
        }
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

    private function resolveSalaryValue(mixed $value): float
    {
        return round((float) $value, 2);
    }

    private function normalizeDigits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private function normalizePan(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function normalizeBloodGroup(string $value): ?string
    {
        $value = strtoupper(trim($value));
        $allowed = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        return in_array($value, $allowed, true) ? $value : null;
    }
}
