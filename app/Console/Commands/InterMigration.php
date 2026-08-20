<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\InternJoiningForm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InterMigration extends Command
{
    protected $signature = 'app:inter-migration
        {--company-id= : Target myagency company_id for imported interns}
        {--limit= : Limit the number of source interns to migrate}
        {--source-id=* : Migrate only the given source intern_details IDs}
        {--dry-run : Preview what will happen without saving anything}';

    protected $description = 'Migrate sts_hrms_new.intern_details rows into myagency intern_joining_forms';

    private const DEFAULT_DOB = '2000-01-01';
    private const DEFAULT_DECLARATION_PLACE = 'Imported';
    private const INTERN_PREFIX = 'STSINT';
    private const ALLOWED_BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function handle(): int
    {

        Log::info('Facebook lead sync running');
        $companyId = $this->resolveCompanyId();

        if ($this->option('company-id') !== null && $this->option('company-id') !== '' && $companyId === null) {
            return self::FAILURE;
        }

        if ($companyId === null && Company::query()->count() > 1) {
            return self::FAILURE;
        }

        $branch = $companyId !== null ? $this->resolveBranch($companyId) : null;

        if ($companyId !== null && ! $branch) {
            $this->error("No active branch found for company_id {$companyId}. Users cannot be created without a branch.");

            return self::FAILURE;
        }

        $sourceRows = $this->sourceRows();

        if ($sourceRows->isEmpty()) {
            $this->warn('No intern rows found in sts_hrms_new.intern_details for the given filters.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $userCreated = 0;
        $userReused = 0;
        $skipped = [];
        $credentials = [];

        foreach ($sourceRows as $sourceRow) {
            $prepared = $this->preparePayload($sourceRow, $companyId);

            if ($prepared['skip_reason'] !== null) {
                $skipped[] = [
                    'source_id' => (string) $sourceRow->id,
                    'name' => (string) ($sourceRow->name ?: 'Unknown'),
                    'reason' => $prepared['skip_reason'],
                ];
                $this->warn("Skipped source #{$sourceRow->id}: {$prepared['skip_reason']}");
                continue;
            }

            $existing = $this->findExistingTarget($sourceRow, $prepared['attributes'], $companyId);
            $action = $existing ? 'updated' : 'created';
            $userAction = 'skipped';

            if (! $this->option('dry-run')) {
                DB::transaction(function () use ($existing, $prepared, $companyId) {
                    $prepared['attributes']['portal_user_id'] = null;
                    $prepared['attributes']['role_id'] = $prepared['role']?->id;
                    $prepared['attributes']['department_id'] = $prepared['department']?->id;

                    if ($existing) {
                        $existing->update($prepared['attributes']);
                    } else {
                        InternJoiningForm::create($prepared['attributes']);
                    }
                });
            }

            if ($action === 'created') {
                $created++;
            } else {
                $updated++;
            }

            if ($userAction === 'created') {
                $userCreated++;
            } else {
                $userReused++;
            }

            $credentials[] = [
                'source_id' => (string) $sourceRow->id,
                'intern_id' => (string) $prepared['attributes']['intern_id'],
                'name' => (string) $prepared['attributes']['name'],
                'username' => (string) $prepared['attributes']['email'],
                'password' => (string) $this->portalPassword($prepared['attributes']),
                'user_action' => $userAction,
            ];

            $this->info(sprintf(
                '%s %s (source #%s) | user: %s',
                $this->option('dry-run') ? 'Previewed' : 'Processed',
                $prepared['attributes']['name'],
                $sourceRow->id,
                $userAction
            ));
        }

        $this->newLine();
        $this->info('Intern migration summary');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Target company_id', $companyId ?? 'null'],
                ['Source rows', $sourceRows->count()],
                ['Interns created', $created],
                ['Interns updated', $updated],
                ['Users created', $userCreated],
                ['Users reused', $userReused],
                ['Skipped', count($skipped)],
            ]
        );

        if ($skipped !== []) {
            $this->newLine();
            $this->warn('Skipped rows');
            $this->table(['Source ID', 'Name', 'Reason'], $skipped);
        }

        if ($credentials !== []) {
            $reportPath = $this->writeCredentialReport($credentials, $companyId);

            $this->newLine();
            $this->warn('Intern portal credentials');
            $this->table(
                ['Source ID', 'Intern ID', 'Name', 'Username', 'Password', 'User Action'],
                $credentials
            );
            $this->line('Credential report saved to: ' . $reportPath);
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run only. No records were written.');
        }

        return self::SUCCESS;
    }

    private function sourceRows(): Collection
    {
        $query = DB::connection('mysql2')
            ->table('intern_details')
            ->whereNull('deleted_at')
            ->orderBy('id');

        $sourceIds = collect((array) $this->option('source-id'))
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->values();

        if ($sourceIds->isNotEmpty()) {
            $query->whereIn('id', $sourceIds);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function resolveCompanyId(): ?int
    {
        $optionValue = $this->option('company-id');

        if ($optionValue !== null && $optionValue !== '') {
            $companyId = (int) $optionValue;
            $company = Company::query()->find($companyId);

            if (! $company) {
                $this->error("Company ID {$companyId} was not found in myagency.companies.");

                return null;
            }

            return $companyId;
        }

        $companies = Company::query()->orderBy('id')->get(['id', 'company_name']);

        if ($companies->count() === 1) {
            return (int) $companies->first()->id;
        }

        $this->error('Multiple companies found. Please rerun with --company-id=');
        $this->table(
            ['Company ID', 'Company Name'],
            $companies->map(fn (Company $company) => [
                'Company ID' => $company->id,
                'Company Name' => $company->company_name,
            ])->all()
        );

        return null;
    }

    private function preparePayload(object $sourceRow, ?int $companyId): array
    {
        $name = $this->cleanString($sourceRow->name) ?: ('Imported Intern ' . $sourceRow->id);
        $email = $this->resolveEmail($sourceRow);
        $startDate = $this->normalizeDate($sourceRow->date_of_joinig);
        $endDate = $this->normalizeDate($sourceRow->internship_end_date);
        $durationMonths = $this->resolveDurationMonths($sourceRow->duration, $startDate, $endDate);
        $department = $this->resolveDepartment((string) ($sourceRow->department ?? ''));
        $role = $this->resolveInternRole($department);

        $attributes = [
            'intern_id' => $this->resolveInternId($sourceRow),
            'photograph' => $this->cleanString($sourceRow->employee_photo),
            'name' => $name,
            'father_name' => $this->cleanString($sourceRow->father_name)
                ?: $this->cleanString($sourceRow->second_name)
                ?: '',
            'correspondence_address' => $this->cleanString($sourceRow->temp_address) ?: '',
            'permanent_address' => $this->cleanString($sourceRow->permanent_address)
                ?: $this->cleanString($sourceRow->temp_address)
                ?: '',
            'mobile' => $this->cleanMobile($sourceRow->mobile_number),
            'email' => $email,
            'date_of_birth' => $this->normalizeDate($sourceRow->date_of_birth) ?: self::DEFAULT_DOB,
            'internship_start_date' => $startDate,
            'internship_duration_months' => $durationMonths,
            'internship_end_date' => $endDate,
            'internship_status' => $this->resolveInternshipStatus($sourceRow->user_status),
            'blood_group' => $this->normalizeBloodGroup($sourceRow->blood_group),
            'marital_status' => $this->normalizeMaritalStatus($sourceRow->marital_status),
            'date_of_marriage' => null,
            'aadhaar_card_no' => $this->cleanString($sourceRow->aadhar_card_no) ?: '',
            'pan_card_no' => $this->cleanString($sourceRow->pan_card_no) ?: '',
            'emergency_contact_name' => '',
            'emergency_contact_relation' => '',
            'emergency_contact_no' => '',
            'role_id' => $role?->id,
            'department_id' => $department?->id,
            'portal_user_id' => null,
            'declaration_accepted' => false,
            'declaration_date' => $startDate ?: now()->toDateString(),
            'declaration_place' => self::DEFAULT_DECLARATION_PLACE,
            'company_id' => $companyId,
            'created_at' => $this->normalizeDateTime($sourceRow->created_at),
            'updated_at' => $this->normalizeDateTime($sourceRow->updated_at),
        ];

        return [
            'skip_reason' => null,
            'attributes' => $attributes,
            'department' => $department,
            'role' => $role,
        ];
    }

    private function findExistingTarget(object $sourceRow, array $attributes, ?int $companyId): ?InternJoiningForm
    {
        $query = InternJoiningForm::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $sourceInternId = trim((string) ($attributes['intern_id'] ?? ''));
        if ($sourceInternId !== '') {
            $existing = (clone $query)->where('intern_id', $sourceInternId)->latest('id')->first();

            if ($existing) {
                return $existing;
            }
        }

        if ($attributes['email'] !== '') {
            $existing = (clone $query)->where('email', $attributes['email'])->latest('id')->first();

            if ($existing) {
                return $existing;
            }
        }

        $mobile = trim((string) $attributes['mobile']);
        if ($mobile !== '' && $mobile !== '0000000000') {
            $existing = (clone $query)->where('mobile', $mobile)->where('name', $attributes['name'])->latest('id')->first();

            if ($existing) {
                return $existing;
            }
        }

        return (clone $query)
            ->where('name', $attributes['name'])
            ->whereDate('date_of_birth', $attributes['date_of_birth'])
            ->latest('id')
            ->first();
    }

    private function resolveInternId(object $sourceRow): string
    {
        $legacyId = trim((string) ($sourceRow->internship_employee_id ?? ''));

        if ($legacyId !== '') {
            return $legacyId;
        }

        return $this->generateNextInternId();
    }

    private function resolveEmail(object $sourceRow): string
    {
        $email = trim((string) ($sourceRow->email_id ?? ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Str::lower($email);
        }

        $slug = Str::of((string) ($sourceRow->internship_employee_id ?: $sourceRow->name ?: ('intern-' . $sourceRow->id)))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        return $slug . '@import.local';
    }

    private function cleanMobile(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return '0000000000';
        }

        if (strlen($digits) >= 10) {
            return substr($digits, 0, 10);
        }

        return str_pad($digits, 10, '0', STR_PAD_RIGHT);
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

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveDurationMonths(mixed $duration, ?string $startDate, ?string $endDate): ?int
    {
        $durationValue = trim((string) $duration);

        if ($durationValue !== '' && preg_match('/(\d+)/', $durationValue, $matches)) {
            return max((int) $matches[1], 0);
        }

        if ($startDate && $endDate) {
            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                return max($start->diffInMonths($end) + 1, 0);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function resolveInternshipStatus(mixed $value): string
    {
        return Str::lower(trim((string) $value)) === 'active' ? InternJoiningForm::STATUS_ACTIVE : InternJoiningForm::STATUS_RESIGNED;
    }

    private function normalizeBloodGroup(mixed $value): ?string
    {
        $bloodGroup = strtoupper(trim((string) $value));

        return in_array($bloodGroup, self::ALLOWED_BLOOD_GROUPS, true) ? $bloodGroup : null;
    }

    private function normalizeMaritalStatus(mixed $value): string
    {
        $normalized = Str::lower(trim((string) $value));

        if (in_array($normalized, ['married', '1', 'yes'], true)) {
            return 'married';
        }

        return 'single';
    }

    private function resolveDepartment(string $legacyDepartment): ?Department
    {
        $normalized = Str::of($legacyDepartment)->lower()->squish()->value();

        if ($normalized === '') {
            return null;
        }

        $map = [
            'web' => 'Development',
            'developer' => 'Development',
            'development' => 'Development',
            'mobile' => 'Development',
            'design' => 'Designing',
            'marketing' => 'Digital Marketing',
            'sales' => 'Sales',
            'business development' => 'Sales',
            'testing' => 'Testing',
            'test' => 'Testing',
            'hr' => 'HR & Accounts',
            'account' => 'HR & Accounts',
            'support' => 'Customer Support Team',
        ];

        $targetDepartmentName = collect($map)
            ->first(fn ($departmentName, $needle) => str_contains($normalized, $needle));

        if (! $targetDepartmentName) {
            return null;
        }

        return Department::query()->where('name', $targetDepartmentName)->first();
    }

    private function resolveInternRole(?Department $department): ?Role
    {
        if (! $department) {
            return null;
        }

        $preferredRoleNames = [
            'Development' => 'development_intern',
            'Designing' => 'design_intern',
            'Digital Marketing' => 'digital_marketing_intern',
            'Sales' => 'sales_intern',
        ];

        $preferredRoleName = $preferredRoleNames[$department->name] ?? null;

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
            ->where(function ($query) {
                $query->where('name', 'like', '%intern%')
                    ->orWhere('display_name', 'like', '%intern%');
            })
            ->orderBy('id')
            ->first();
    }

    private function resolveBranch(int $companyId): ?Branch
    {
        return Branch::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    private function syncPortalUser(array $attributes, ?Role $role, ?Branch $branch, ?int $companyId): array
    {
        $user = $this->findExistingUserByEmail($attributes['email']);
        $action = $user ? 'reused' : 'created';

        $userAttributes = [
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'company_id' => $companyId,
            'branch_id' => $branch?->id,
            'designation' => $role?->display_name
                ?: ($role?->name ? Str::of(Str::afterLast($role->name, '__'))->replace('_', ' ')->title()->value() : 'Intern'),
            'is_active' => $attributes['internship_status'] === InternJoiningForm::STATUS_ACTIVE,
        ];

        if (! $user) {
            $user = User::create($userAttributes + [
                'password' => Hash::make($this->portalPassword($attributes)),
            ]);
        } else {
            $user->update($userAttributes + [
                'password' => Hash::make($this->portalPassword($attributes)),
            ]);
        }

        if ($role) {
            $user->syncRoles([$role->name]);
        }

        return [
            'user' => $user,
            'action' => $action,
        ];
    }

    private function findExistingUserByEmail(string $email): ?User
    {
        if ($email === '') {
            return null;
        }

        return User::with('roles')->where('email', $email)->first();
    }

    private function portalPassword(array $attributes): string
    {
        $mobile = preg_replace('/\D+/', '', (string) ($attributes['mobile'] ?? ''));

        if ($mobile !== '') {
            if (strlen($mobile) >= 10) {
                return substr($mobile, 0, 10);
            }

            return str_pad($mobile, 10, '0', STR_PAD_RIGHT);
        }

        return '0000000000';
    }

    private function writeCredentialReport(array $credentials, ?int $companyId): string
    {
        $directory = 'reports';
        $fileName = 'intern_credentials_company_' . ($companyId ?? 'null') . '_' . now()->format('Ymd_His') . '.csv';
        $path = $directory . '/' . $fileName;

        $lines = [
            ['source_id', 'intern_id', 'name', 'username', 'password', 'user_action'],
        ];

        foreach ($credentials as $credential) {
            $lines[] = [
                $credential['source_id'],
                $credential['intern_id'],
                $credential['name'],
                $credential['username'],
                $credential['password'],
                $credential['user_action'],
            ];
        }

        $csv = collect($lines)
            ->map(function (array $line) {
                return collect($line)
                    ->map(function ($value) {
                        $value = (string) $value;

                        return '"' . str_replace('"', '""', $value) . '"';
                    })
                    ->implode(',');
            })
            ->implode(PHP_EOL);

        Storage::disk('local')->put($path, $csv);

        return storage_path('app/' . $path);
    }

    private function generateNextInternId(): string
    {
        $prefix = InternJoiningForm::INTERN_ID_PREFIX;
        $cleanPrefix = rtrim($prefix, '-') . '-';

        $internIds = InternJoiningForm::withoutGlobalScopes()
            ->pluck('intern_id');

        $lastNumber = 0;

        foreach ($internIds as $internId) {
            if (!$internId) {
                continue;
            }

            if (preg_match('/(\d+)$/', $internId, $matches)) {
                $num = (int) $matches[1];
                if ($num > $lastNumber) {
                    $lastNumber = $num;
                }
            }
        }

        $nextNumber = $lastNumber > 0 ? $lastNumber + 1 : 1001;

        return $cleanPrefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function cleanString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}