<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeApiController extends Controller
{
    // Mirrors EmployeeOnboardingController::DOCUMENT_LABELS, minus
    // photograph/signature — those two are rendered as the profile photo /
    // signature image, never listed in the "Document Uploads" section on
    // web (see employee-show.blade.php's @continue for those two fields).
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

    // ── GET /api/mobile/hrms/employees ────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeOnboarding::query()
            ->with(['role', 'department', 'sourceIntern'])
            ->when($request->search, function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('employee_id', 'like', "%$s%")
                        ->orWhere('name',        'like', "%$s%")
                        ->orWhere('email',       'like', "%$s%")
                        ->orWhere('mobile',      'like', "%$s%")
                        ->orWhere('aadhaar_card_no', 'like', "%$s%");
                });
            })
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->role_id, fn ($q) => $q->where('role_id', $request->role_id))
            // Branch lives on the linked portal user (User::branch_id), not
            // on EmployeeOnboarding itself — mirrors the same relationship
            // this model's own booted() global scope already filters through
            // for branch admins.
            ->when($request->branch_id, fn ($q) => $q->whereHas(
                'portalUser',
                fn ($sub) => $sub->where('branch_id', $request->branch_id)
            ))
            ->latest();

        $perPage    = (int) ($request->per_page ?? 15);
        $employees  = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'employees'  => $employees->map(fn ($e) => $this->mapList($e)),
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'last_page'    => $employees->lastPage(),
                    'per_page'     => $employees->perPage(),
                    'total'        => $employees->total(),
                    'has_more'     => $employees->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/employees/{id} ───────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $employee = EmployeeOnboarding::with([
            'role',
            'department',
            'educations',
            'employments',
            'familyDetails',
            'sourceIntern',
            'creator',
            'updater',
            'portalUser.branch',
            'portalUser.roles',
            'portalUser.managerMappings.manager',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->mapDetail($employee),
        ]);
    }

    // ── GET /api/mobile/hrms/employees/meta ───────────────────────────────────
    public function meta(): JsonResponse
    {
        $departments = Department::whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Mirrors EmployeeOnboarding::STATUS_ACTIVE / STATUS_RESIGNED exactly —
        // these are the only two values the `status` column ever holds (see
        // employee-index.blade.php's filter <select>). The previous list
        // here (pending/verified/rejected) never matched a real row, so the
        // mobile status filter silently returned nothing.
        $statuses = [EmployeeOnboarding::STATUS_ACTIVE, EmployeeOnboarding::STATUS_RESIGNED];

        $user = request()->user();

        // Branch options — mirrors LeadController::meta()'s own
        // company/branch-admin scoping (Branch::is_active + company_id, then
        // narrowed to the branch admin's own branch(es)) so this filter list
        // never offers a branch that would just return an empty result —
        // that scoping is the same one EmployeeOnboarding's booted() global
        // scope already enforces on the underlying query.
        $branchesQuery = Branch::where('is_active', true);
        if ($user?->company_id) {
            $branchesQuery->where('company_id', $user->company_id);
        }
        if ($user && $user->isBranchAdmin()) {
            $branchIds = $user->getMyBranchIds();
            if (!empty($branchIds)) {
                $branchesQuery->whereIn('id', $branchIds);
            }
        }
        $branches = $branchesQuery->orderBy('name')->get(['id', 'name']);

        // Role options — company-scoped (Role carries BelongsToCompany).
        // Uses display_name (falling back to the technical name) since
        // that's what mapList()/mapDetail() already show for `role`.
        $roles = Role::when($user?->company_id, fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', 'display_name'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->display_name ?: $r->name])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'departments' => $departments,
                'statuses'    => $statuses,
                'branches'    => $branches,
                'roles'       => $roles,
            ],
        ]);
    }

    // ── Private: list shape (compact) ─────────────────────────────────────────
    private function mapList(EmployeeOnboarding $e): array
    {
        return [
            'id'             => $e->id,
            'employee_id'    => $e->employee_id,
            'name'           => $e->name,
            'email'          => $e->email,
            'mobile'         => $e->mobile,
            'role'           => optional($e->role)->display_name ?? optional($e->role)->name,
            'department'     => optional($e->department)->name,
            'status'         => $e->status,
            'avatar_initial' => strtoupper(substr($e->name, 0, 1)),
            'created_at'     => optional($e->created_at)->format('d M Y'),

            // Mirrors the remaining employee-index.blade.php table columns
            // (DOB, Employee Type) plus the "Employee" cell's sub-line,
            // which shows either the father's name or a converted-from-
            // intern note.
            'date_of_birth'  => optional($e->date_of_birth)->format('d M Y') ?? '',
            'employee_type'  => $e->employee_type === 'non_billable' ? 'non_billable' : 'billable',
            'father_name'    => $e->father_name ?? '',
            'converted_from_intern' => $e->sourceIntern
                ? ($e->sourceIntern->intern_id ?: $e->sourceIntern->name)
                : null,
            'photograph_url' => $e->getFileUrl('photograph'),
        ];
    }

    // ── Private: detail shape (full) ──────────────────────────────────────────
    private function mapDetail(EmployeeOnboarding $e): array
    {
        $portalUser = $e->portalUser;
        $portalManager = $portalUser?->managerMappings?->first()?->manager;

        return [
            // Identity
            'id'                       => $e->id,
            'employee_id'              => $e->employee_id,
            'name'                     => $e->name,
            'email'                    => $e->email,
            'mobile'                   => $e->mobile,
            'role'                     => optional($e->role)->display_name ?? optional($e->role)->name,
            'department'               => optional($e->department)->name,
            'status'                   => $e->status,
            'employee_type'            => $e->employee_type === 'non_billable' ? 'non_billable' : 'billable',
            'avatar_initial'           => strtoupper(substr($e->name, 0, 1)),
            'photograph_url'           => $e->getFileUrl('photograph'),

            // Source — mirrors the "Source" row on employee-show.blade.php
            // (either "Direct Employee Onboarding" or a link to the intern
            // this employee was converted from).
            'source_intern' => $e->sourceIntern ? [
                'id'        => $e->sourceIntern->id,
                'intern_id' => $e->sourceIntern->intern_id ?: $e->sourceIntern->name,
                'name'      => $e->sourceIntern->name,
            ] : null,

            // Personal
            'father_name'              => $e->father_name             ?? '',
            'date_of_birth'            => optional($e->date_of_birth)->format('d M Y') ?? '',
            'blood_group'              => $e->blood_group             ?? '',
            'marital_status'           => $e->marital_status          ?? '',
            'date_of_marriage'         => optional($e->date_of_marriage)->format('d M Y') ?? '',
            'aadhaar_card_no'          => $e->aadhaar_card_no         ?? '',
            'pan_card_no'              => $e->pan_card_no             ?? '',
            'correspondence_address'   => $e->correspondence_address  ?? '',
            'permanent_address'        => $e->permanent_address       ?? '',

            // Emergency
            'emergency_contact_name'   => $e->emergency_contact_name  ?? '',
            'emergency_relation'       => $e->emergency_relation       ?? '',
            'emergency_contact_no'     => $e->emergency_contact_no     ?? '',

            // Portal account — mirrors the "Employee Portal Account" card.
            'portal_account' => [
                'email'         => $portalUser?->email ?? '',
                'branch'        => $portalUser?->branch?->name ?? '',
                'department'    => optional($e->department)->name ?? '',
                'role'          => optional($e->role)->display_name ?? optional($e->role)->name ?? '',
                'tl_mapping'    => $portalManager?->name ?? '',
                'account_status'=> $portalUser ? ($portalUser->is_active ? 'Active' : 'Inactive') : '',
            ],

            // Professional reference — mirrors the "Professional Reference" card.
            'professional_reference' => [
                'name'              => $e->reference_name ?? '',
                'organization_name' => $e->reference_organization_name ?? '',
                'designation'       => $e->reference_designation ?? '',
                'contact_no'        => $e->reference_contact_no ?? '',
                'mail_id'           => $e->reference_mail_id ?? '',
            ],

            // Salary — full breakdown, mirrors the "Employee Salaries" card
            // exactly. `gross_salary`/`net_salary` kept at top level too
            // (existing keys other clients may already read).
            'joining_date'              => optional($e->joining_date)->format('d M Y') ?? '',
            'salary_effective_from'     => optional($e->salary_effective_from)->format('d M Y') ?? '',
            'gross_salary'               => $e->gross_salary !== null ? (float) $e->gross_salary : null,
            'net_salary'                 => $e->net_salary !== null ? (float) $e->net_salary : null,
            'salary_payment_mode'      => $e->salary_payment_mode      ?? '',
            'basic_salary'               => $e->basic_salary !== null ? (float) $e->basic_salary : null,
            'hra'                        => $e->hra !== null ? (float) $e->hra : null,
            'special_allowance'          => $e->special_allowance !== null ? (float) $e->special_allowance : null,
            'other_allowance'            => $e->other_allowance !== null ? (float) $e->other_allowance : null,
            'pf_enabled'               => (bool) ($e->pf_enabled       ?? false),
            'uan_no'                     => $e->uan_no ?? '',
            'pf_account_no'              => $e->pf_account_no ?? '',
            'pf_employee_contribution'   => $e->pf_employee_contribution !== null ? (float) $e->pf_employee_contribution : null,
            'pf_employer_contribution'   => $e->pf_employer_contribution !== null ? (float) $e->pf_employer_contribution : null,
            'esi_enabled'              => (bool) ($e->esi_enabled       ?? false),
            'esi_no'                     => $e->esi_no ?? '',
            'esi_employee_contribution'  => $e->esi_employee_contribution !== null ? (float) $e->esi_employee_contribution : null,
            'esi_employer_contribution'  => $e->esi_employer_contribution !== null ? (float) $e->esi_employer_contribution : null,
            'professional_tax'           => $e->professional_tax !== null ? (float) $e->professional_tax : null,
            'tds_amount'                 => $e->tds_amount !== null ? (float) $e->tds_amount : null,
            'loan_deduction'             => $e->loan_deduction !== null ? (float) $e->loan_deduction : null,
            'other_deduction'            => $e->other_deduction !== null ? (float) $e->other_deduction : null,
            'total_deduction'            => $e->total_deduction !== null ? (float) $e->total_deduction : null,
            'deduction_notes'            => $e->deduction_notes ?? '',

            // Bank — `bank_ifsc` kept for backward compatibility (previously
            // pointed at a non-existent column and was always null); the
            // model column is bank_ifsc_code.
            'bank_name'                => $e->bank_name                ?? '',
            'bank_account_name'          => $e->bank_account_name ?? '',
            'bank_account_no'          => $e->bank_account_no          ?? '',
            'bank_ifsc'                => $e->bank_ifsc_code            ?? '',
            'bank_branch'              => $e->bank_branch               ?? '',

            // Documents — mirrors the "Document Uploads" card (labels +
            // direct storage URLs so the app can view/download).
            'documents' => collect(self::DOCUMENT_LABELS)->map(fn ($label, $field) => [
                'field' => $field,
                'label' => $label,
                'url'   => $e->getFileUrl($field),
            ])->values(),

            // Relations
            'educations'  => $e->educations->map(fn ($ed) => [
                'qualification'  => $ed->qualification  ?? '',
                'institution'    => $ed->institution_name ?? '',
                'year'           => $ed->year_of_passing ?? '',
                'percentage'     => $ed->percentage      ?? '',
                'specialization' => $ed->specialization  ?? '',
            ])->values(),

            'employments' => $e->employments->map(fn ($em) => [
                'organisation' => $em->organisation   ?? '',
                'designation'  => $em->designation    ?? '',
                'period_from'  => optional($em->period_from)->format('d M Y') ?? ($em->period_from ?? ''),
                'period_to'    => optional($em->period_to)->format('d M Y')   ?? ($em->period_to   ?? ''),
                'annual_ctc'   => $em->annual_ctc      ?? '',
            ])->values(),

            'family_details' => $e->familyDetails->map(fn ($f) => [
                'name'         => $f->name          ?? '',
                'relation'     => $f->relation       ?? '',
                'occupation'   => $f->occupation     ?? '',
                'date_of_birth'=> optional($f->date_of_birth)->format('d M Y') ?? '',
                'mobile_no'    => $f->mobile_no      ?? '',
            ])->values(),

            // Audit
            'created_by' => $e->creator?->name ?? 'System',
            'updated_by' => $e->updater?->name ?? 'System',
            'created_at' => optional($e->created_at)->format('d M Y'),
        ];
    }
}