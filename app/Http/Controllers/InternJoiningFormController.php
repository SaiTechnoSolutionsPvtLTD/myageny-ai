<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Http\Requests\StoreInternJoiningFormRequest;
use App\Http\Requests\UpdateInternJoiningFormRequest;
use App\Http\Requests\ConvertInternToEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\InternDocument;
use App\Models\InternJoiningForm;
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

class InternJoiningFormController extends Controller
{
    private const FILE_DIRECTORY = 'interns';
    private const EMPLOYEE_FILE_DIRECTORY = 'employee_onboarding';
    private const EMPLOYEE_ID_PREFIX = 'STS';
    private const TEAM_LEAD_ROLE_KEYS = ['tl', 'team_lead', 'team_leader', 'teamlead', 'manager'];

    private const DOCUMENT_LABELS = [
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
        $forms = InternJoiningForm::query()
            ->with(['documents', 'convertedEmployee', 'department'])
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('intern_id', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('aadhaar_card_no', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('department_id'), function ($query) use ($request) {
                $query->where('department_id', $request->integer('department_id'));
            })
            ->when($request->filled('internship_status'), function ($query) use ($request) {
                $query->where('internship_status', $request->string('internship_status')->toString());
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('pages.hrms.Interns.intern_joining_forms.index', compact('forms', 'departments'));
    }

    public function create(): View
    {
        return view('pages.hrms.Interns.intern_joining_forms.create', [
            'documentLabels' => self::DOCUMENT_LABELS,
            'roles' => Role::with(['department', 'roleParentMapping.parentRole'])->orderByRaw('COALESCE(display_name, name)')->get(),
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'tlUsers' => $this->teamLeadUsers(),
        ]);
    }

    public function store(StoreInternJoiningFormRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $form = DB::transaction(function () use ($request, $validated) {
            $attributes = $this->extractMainAttributes($validated, $request);
            $attributes['intern_id'] = $this->generateNextInternId();

            $portalUser = null;
            if (! empty($validated['create_portal_account'])) {
                $portalUser = $this->syncPortalAccount(null, $validated);
                $attributes['portal_user_id'] = $portalUser->id;
                $attributes['role_id'] = $validated['role_id'];
                $attributes['department_id'] = $validated['department_id'];
            }

            $form = InternJoiningForm::create($attributes);
            $this->syncEducationalDetails($form, $validated['educational_details'] ?? []);
            $this->syncEmploymentDetails($form, $validated['employment_details'] ?? []);
            $this->syncFamilyDetails($form, $validated['family_details'] ?? []);
            $this->syncDocuments($form, $request);

            if ($portalUser) {
                $this->syncPortalMapping($portalUser, $validated['tl_user_id'] ?? null);
            }

            return $form;
        });

        return redirect()
            ->route('interns.show', $form)
            ->with('success', "Intern joining form for <strong>{$form->name}</strong> created successfully.");
    }

    public function show(InternJoiningForm $intern): View
    {
        $intern->load([
            'educationalDetails',
            'employmentDetails',
            'familyDetails',
            'documents',
            'convertedEmployee',
            'role.department',
            'department',
            'portalUser.branch',
            'portalUser.roles',
            'portalUser.managerMappings.manager',
        ]);

        return view('pages.hrms.Interns.intern_joining_forms.show', [
            'form' => $intern,
            'documentLabels' => self::DOCUMENT_LABELS,
        ]);
    }

    public function edit(InternJoiningForm $intern): View
    {
        $intern->load([
            'educationalDetails',
            'employmentDetails',
            'familyDetails',
            'documents',
            'role.department',
            'department',
            'portalUser.branch',
            'portalUser.roles',
            'portalUser.managerMappings.manager',
        ]);

        return view('pages.hrms.Interns.intern_joining_forms.edit', [
            'form' => $intern,
            'documentLabels' => self::DOCUMENT_LABELS,
            'roles' => Role::with(['department', 'roleParentMapping.parentRole'])->orderByRaw('COALESCE(display_name, name)')->get(),
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'tlUsers' => $this->teamLeadUsers(),
        ]);
    }

    public function update(UpdateInternJoiningFormRequest $request, InternJoiningForm $intern): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $intern) {
            $attributes = $this->extractMainAttributes($validated, $request, $intern);
            $portalUser = null;

            if (! empty($validated['create_portal_account'])) {
                $portalUser = $this->syncPortalAccount($intern->portalUser, $validated);
                $attributes['portal_user_id'] = $portalUser->id;
                $attributes['role_id'] = $validated['role_id'];
                $attributes['department_id'] = $validated['department_id'];
            } elseif ($intern->portal_user_id) {
                $attributes['portal_user_id'] = $intern->portal_user_id;
                $attributes['role_id'] = $intern->role_id;
                $attributes['department_id'] = $intern->department_id;
            } else {
                $attributes['portal_user_id'] = null;
                $attributes['role_id'] = null;
                $attributes['department_id'] = null;
            }

            $intern->update($attributes);
            $this->syncEducationalDetails($intern, $validated['educational_details'] ?? []);
            $this->syncEmploymentDetails($intern, $validated['employment_details'] ?? []);
            $this->syncFamilyDetails($intern, $validated['family_details'] ?? []);
            $this->syncDocuments($intern, $request, true);

            if ($portalUser) {
                $this->syncPortalMapping($portalUser, $validated['tl_user_id'] ?? null);
            }
        });

        return redirect()
            ->route('interns.show', $intern)
            ->with('success', "Intern joining form for <strong>{$intern->name}</strong> updated successfully.");
    }

    public function destroy(InternJoiningForm $intern): RedirectResponse
    {
        $name = $intern->name;

        DB::transaction(function () use ($intern) {
            $this->deleteStoredFile($intern->photograph);

            if ($intern->documents) {
                foreach (InternJoiningForm::DOCUMENT_FIELDS as $field) {
                    $this->deleteStoredFile($intern->documents->{$field});
                }
            }

            $intern->educationalDetails()->delete();
            $intern->employmentDetails()->delete();
            $intern->familyDetails()->delete();
            $intern->documents()?->delete();
            $intern->delete();
        });

        return redirect()
            ->route('interns.index')
            ->with('success', "Intern joining form for <strong>{$name}</strong> deleted successfully.");
    }

    public function showConvertToEmployeeForm(InternJoiningForm $intern): View
    {
        $intern->loadMissing('convertedEmployee');

        if ($intern->convertedEmployee) {
            return view('pages.hrms.Interns.intern_joining_forms.convert_to_employee', [
                'form' => $intern,
                'generatedEmployeeId' => $intern->convertedEmployee->employee_id,
                'roles' => Role::with(['department', 'roleParentMapping.parentRole'])->orderByRaw('COALESCE(display_name, name)')->get(),
                'departments' => Department::orderBy('name')->get(),
                'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
                'tlUsers' => $this->teamLeadUsers(),
            ]);
        }

        return view('pages.hrms.Interns.intern_joining_forms.convert_to_employee', [
            'form' => $intern,
            'generatedEmployeeId' => $this->generateNextEmployeeId(),
            'roles' => Role::with(['department', 'roleParentMapping.parentRole'])->orderByRaw('COALESCE(display_name, name)')->get(),
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'tlUsers' => $this->teamLeadUsers(),
        ]);
    }

    public function convertToEmployee(ConvertInternToEmployeeRequest $request, InternJoiningForm $intern): RedirectResponse
    {
        $intern->loadMissing(['educationalDetails', 'employmentDetails', 'familyDetails', 'documents', 'convertedEmployee']);

        if ($intern->convertedEmployee) {
            return redirect()
                ->route('interns.show', $intern)
                ->with('error', 'This intern has already been converted to an employee.');
        }

        $validated = $request->validated();

        $employee = DB::transaction(function () use ($intern, $validated) {
            $portalUser = $this->createPortalUser($intern, $validated);

            $employee = new EmployeeOnboarding();
            $employee->fill([
                'employee_id' => $this->generateNextEmployeeId(),
                'source_intern_joining_form_id' => $intern->id,
                'role_id' => $validated['role_id'],
                'department_id' => $validated['department_id'],
                'portal_user_id' => $portalUser->id,
                'name' => $intern->name,
                'father_name' => $intern->father_name,
                'correspondence_address' => $intern->correspondence_address,
                'permanent_address' => $intern->permanent_address,
                'mobile' => $intern->mobile,
                'email' => $intern->email,
                'date_of_birth' => $intern->date_of_birth,
                'blood_group' => $intern->blood_group,
                'marital_status' => $intern->marital_status,
                'date_of_marriage' => $intern->date_of_marriage,
                'aadhaar_card_no' => $intern->aadhaar_card_no,
                'pan_card_no' => $intern->pan_card_no,
                'emergency_contact_name' => $intern->emergency_contact_name,
                'emergency_relation' => $intern->emergency_contact_relation,
                'emergency_contact_no' => $intern->emergency_contact_no,
                'declaration_date' => $intern->declaration_date,
                'declaration_place' => $intern->declaration_place,
                'status' => EmployeeOnboarding::STATUS_ACTIVE,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $employee->photograph = $this->cloneStoredFile($intern->photograph, self::EMPLOYEE_FILE_DIRECTORY);

            if ($intern->documents) {
                foreach (InternJoiningForm::DOCUMENT_FIELDS as $field) {
                    if ($field === 'signature_path') {
                        continue;
                    }

                    $employee->{$field} = $this->cloneStoredFile($intern->documents->{$field}, self::EMPLOYEE_FILE_DIRECTORY);
                }

                $employee->signature = $this->cloneStoredFile($intern->documents->signature_path, self::EMPLOYEE_FILE_DIRECTORY);
            }

            $employee->save();

            $educationRows = $intern->educationalDetails->map(fn ($row) => [
                'qualification' => $row->qualification,
                'institution_name' => $row->institution_name,
                'year_of_passing' => $row->year_of_passing,
                'percentage' => $row->percentage,
                'specialization' => $row->specialization,
                'sort_order' => $row->sort_order,
            ])->all();

            if ($educationRows !== []) {
                $employee->educations()->createMany($educationRows);
            }

            $employmentRows = $intern->employmentDetails->map(fn ($row) => [
                'organisation' => $row->organisation,
                'designation' => $row->designation,
                'period_from' => $row->period_from,
                'period_to' => $row->period_to,
                'annual_ctc' => $row->annual_ctc,
                'sort_order' => $row->sort_order,
            ])->all();

            if ($employmentRows !== []) {
                $employee->employments()->createMany($employmentRows);
            }

            $familyRows = $intern->familyDetails->map(fn ($row) => [
                'name' => $row->name,
                'relation' => $row->relation,
                'occupation' => $row->occupation,
                'date_of_birth' => $row->date_of_birth,
                'mobile_no' => $row->mobile_no,
                'sort_order' => $row->sort_order,
            ])->all();

            if ($familyRows !== []) {
                $employee->familyDetails()->createMany($familyRows);
            }

            $this->syncPortalMapping($portalUser, $validated['tl_user_id']);

            return $employee;
        });

        return redirect()
            ->route('employee-onboarding.show', $employee)
            ->with('success', "Intern <strong>{$intern->name}</strong> was converted to employee <strong>{$employee->employee_id}</strong>.");
    }

    private function extractMainAttributes(array $validated, Request $request, ?InternJoiningForm $form = null): array
    {
        $attributes = Arr::only($validated, [
            'name',
            'father_name',
            'correspondence_address',
            'permanent_address',
            'mobile',
            'email',
            'date_of_birth',
            'internship_start_date',
            'internship_duration_months',
            'internship_end_date',
            'internship_status',
            'blood_group',
            'marital_status',
            'date_of_marriage',
            'aadhaar_card_no',
            'pan_card_no',
            'emergency_contact_name',
            'emergency_contact_relation',
            'emergency_contact_no',
            'declaration_accepted',
            'declaration_date',
            'declaration_place',
        ]);

        if (($attributes['marital_status'] ?? null) !== 'married') {
            $attributes['date_of_marriage'] = null;
        }

        $attributes['internship_end_date'] = $this->calculateInternshipEndDate(
            $validated['internship_start_date'] ?? null,
            $validated['internship_duration_months'] ?? null
        );

        if ($request->hasFile('photograph')) {
            if ($form?->photograph) {
                $this->deleteStoredFile($form->photograph);
            }

            $attributes['photograph'] = $request->file('photograph')->store(self::FILE_DIRECTORY . '/photographs', 'public');
        }

        return $attributes;
    }

    private function calculateInternshipEndDate(?string $startDate, mixed $durationMonths): ?string
    {
        if (! $startDate || ! $durationMonths) {
            return null;
        }

        return Carbon::parse($startDate)
            ->addMonthsNoOverflow((int) $durationMonths)
            ->subDay()
            ->toDateString();
    }

    private function syncEducationalDetails(InternJoiningForm $form, array $rows): void
    {
        $form->educationalDetails()->delete();

        $preparedRows = collect(array_values($rows))
            ->map(fn (array $row, int $index) => [
                'qualification' => trim((string) ($row['qualification'] ?? '')),
                'institution_name' => trim((string) ($row['institution_name'] ?? '')),
                'year_of_passing' => trim((string) ($row['year_of_passing'] ?? '')),
                'percentage' => trim((string) ($row['percentage'] ?? '')),
                'specialization' => trim((string) ($row['specialization'] ?? '')),
                'sort_order' => $index + 1,
            ])
            ->all();

        $form->educationalDetails()->createMany($preparedRows);
    }

    private function syncEmploymentDetails(InternJoiningForm $form, array $rows): void
    {
        $form->employmentDetails()->delete();

        $preparedRows = collect(array_values($rows))
            ->filter(fn ($row) => $this->rowHasData((array) $row, ['organisation', 'designation', 'period_from', 'period_to', 'annual_ctc']))
            ->map(fn (array $row, int $index) => [
                'organisation' => trim((string) ($row['organisation'] ?? '')),
                'designation' => trim((string) ($row['designation'] ?? '')),
                'period_from' => $row['period_from'] ?: null,
                'period_to' => $row['period_to'] ?: null,
                'annual_ctc' => trim((string) ($row['annual_ctc'] ?? '')),
                'sort_order' => $index + 1,
            ])
            ->values()
            ->all();

        if ($preparedRows !== []) {
            $form->employmentDetails()->createMany($preparedRows);
        }
    }

    private function syncFamilyDetails(InternJoiningForm $form, array $rows): void
    {
        $form->familyDetails()->delete();

        $preparedRows = collect(array_values($rows))
            ->filter(fn ($row) => $this->rowHasData((array) $row, ['name', 'relation', 'occupation', 'date_of_birth', 'mobile_no']))
            ->map(fn (array $row, int $index) => [
                'name' => trim((string) ($row['name'] ?? '')),
                'relation' => trim((string) ($row['relation'] ?? '')),
                'occupation' => trim((string) ($row['occupation'] ?? '')),
                'date_of_birth' => $row['date_of_birth'] ?: null,
                'mobile_no' => trim((string) ($row['mobile_no'] ?? '')),
                'sort_order' => $index + 1,
            ])
            ->values()
            ->all();

        if ($preparedRows !== []) {
            $form->familyDetails()->createMany($preparedRows);
        }
    }

    private function syncDocuments(InternJoiningForm $form, Request $request, bool $isUpdate = false): void
    {
        $documents = $form->documents ?: new InternDocument();
        $documents->intern_joining_form_id = $form->id;

        foreach (InternJoiningForm::DOCUMENT_FIELDS as $field) {
            if ($field === 'signature_path') {
                continue;
            }

            if (! $request->hasFile($field)) {
                continue;
            }

            if ($isUpdate && $documents->{$field}) {
                $this->deleteStoredFile($documents->{$field});
            }

            $documents->{$field} = $request->file($field)->store(self::FILE_DIRECTORY . '/documents', 'public');
        }

        if ($request->hasFile('signature_upload')) {
            if ($isUpdate && $documents->signature_path) {
                $this->deleteStoredFile($documents->signature_path);
            }

            $documents->signature_path = $request->file('signature_upload')->store(self::FILE_DIRECTORY . '/signatures', 'public');
        } elseif ($signatureData = $request->input('signature_data')) {
            if ($isUpdate && $documents->signature_path) {
                $this->deleteStoredFile($documents->signature_path);
            }

            $storedPath = $this->storeSignatureData($signatureData);
            if ($storedPath !== '') {
                $documents->signature_path = $storedPath;
            }
        }

        $documents->save();
    }

    private function storeSignatureData(string $signatureData): string
    {
        if (! preg_match('/^data:image\/png;base64,/', $signatureData)) {
            return '';
        }

        $encoded = substr($signatureData, strpos($signatureData, ',') + 1);
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            return '';
        }

        $path = self::FILE_DIRECTORY . '/signatures/' . Str::uuid() . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
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
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function createPortalUser(InternJoiningForm $intern, array $validated): User
    {
        $role = Role::withoutGlobalScopes()->findOrFail($validated['role_id']);

        $user = User::create([
            'name' => $intern->name,
            'email' => $validated['portal_email'],
            'password' => Hash::make($validated['portal_password']),
            'company_id' => auth()->user()?->company_id,
            'branch_id' => $validated['branch_id'],
            'is_active' => true,
        ]);

        $user->syncRoles([$role->name]);

        return $user;
    }

    private function syncPortalAccount(?User $user, array $validated): User
    {
        $role = Role::withoutGlobalScopes()->findOrFail($validated['role_id']);

        $attributes = [
            'name' => $validated['name'],
            'email' => trim((string) $validated['portal_email']),
            'branch_id' => $validated['branch_id'],
        ];

        if (! $user) {
            $attributes['company_id'] = auth()->user()?->company_id;
            $attributes['is_active'] = true;
            $attributes['password'] = Hash::make($validated['portal_password']);

            $user = User::create($attributes);
        } else {
            if (! empty($validated['portal_password'])) {
                $attributes['password'] = Hash::make($validated['portal_password']);
            }

            $user->update($attributes);
        }

        $user->syncRoles([$role->name]);

        return $user;
    }

    private function syncPortalMapping(User $user, mixed $tlUserId): void
    {
        UserMapping::updateOrCreate(
            ['user_id' => $user->id],
            [
                'manager_id' => $tlUserId,
                'company_id' => $user->company_id ?: auth()->user()?->company_id,
            ]
        );
    }

    private function cloneStoredFile(?string $path, string $directory): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $targetPath = trim($directory, '/') . '/' . Str::uuid() . ($extension ? '.' . $extension : '');

        Storage::disk('public')->copy($path, $targetPath);

        return $targetPath;
    }

    private function generateNextInternId(): string
    {
        $latestInternId = InternJoiningForm::query()
            ->where('intern_id', 'like', InternJoiningForm::INTERN_ID_PREFIX . '%')
            ->orderByDesc('intern_id')
            ->lockForUpdate()
            ->value('intern_id');

        $lastNumber = 0;

        if ($latestInternId && preg_match('/^' . preg_quote(InternJoiningForm::INTERN_ID_PREFIX, '/') . '(\d+)$/', $latestInternId, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return InternJoiningForm::INTERN_ID_PREFIX . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    private function generateNextEmployeeId(): string
    {
        $latestEmployeeId = EmployeeOnboarding::query()
            ->where('employee_id', 'like', self::EMPLOYEE_ID_PREFIX . '%')
            ->orderByDesc('employee_id')
            ->lockForUpdate()
            ->value('employee_id');

        $lastNumber = 0;

        if ($latestEmployeeId && preg_match('/^' . preg_quote(self::EMPLOYEE_ID_PREFIX, '/') . '(\d+)$/', $latestEmployeeId, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return self::EMPLOYEE_ID_PREFIX . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    private function teamLeadUsers(): Collection
    {
        $companySuperAdminId = null;

        if ($companyId = auth()->user()?->company_id) {
            $companySuperAdminId = optional(\App\Models\Company::find($companyId))->super_admin_user_id;
        }

        return User::with(['roles.roleMapping', 'branch'])
            ->where('is_active', true)
            ->when(auth()->user()?->company_id, fn ($query) => $query->where('company_id', auth()->user()->company_id))
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->roles->isNotEmpty() || (int) $user->id === (int) $companySuperAdminId || $user->isSuperAdmin())
            ->map(function (User $user) use ($companySuperAdminId) {
                $teamLeadRoles = $user->roles->filter(fn (Role $role) => $this->roleLooksLikeTeamLead($role));
                $displayRoles = $teamLeadRoles->isNotEmpty() ? $teamLeadRoles : $user->roles;
                $isSuperAdmin = $user->isSuperAdmin() || (int) $user->id === (int) $companySuperAdminId;

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
                        ->implode(', ') ?: ($isSuperAdmin ? 'Super Admin' : 'Team Lead'),
                    'is_super_admin' => $isSuperAdmin,
                ];
            })
            ->values();
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
}
