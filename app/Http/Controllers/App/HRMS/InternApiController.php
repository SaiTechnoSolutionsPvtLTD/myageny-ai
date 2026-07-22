<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InternJoiningForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternApiController extends Controller
{
    // Mirrors InternJoiningFormController::DOCUMENT_LABELS — these fields
    // live on the related InternDocument row (`documents`), not on the
    // form itself (see intern-show.blade.php's `$form->documents?->{$field}`).
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

    // ── GET /api/mobile/hrms/interns ──────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = InternJoiningForm::query()
            ->with(['department', 'convertedEmployee'])
            ->when($request->search, function ($q) use ($request) {
                $s = trim((string) $request->search);
                $q->where(function ($sub) use ($s) {
                    $sub->where('intern_id',       'like', "%$s%")
                        ->orWhere('name',           'like', "%$s%")
                        ->orWhere('email',         'like', "%$s%")
                        ->orWhere('mobile',        'like', "%$s%")
                        ->orWhere('aadhaar_card_no','like', "%$s%");
                });
            })
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('internship_status'), fn ($q) => $q->where('internship_status', $request->string('internship_status')->toString()))
            ->latest();

        $perPage = (int) ($request->per_page ?? 15);
        $interns = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'interns'    => $interns->map(fn ($i) => $this->mapList($i)),
                'pagination' => [
                    'current_page' => $interns->currentPage(),
                    'last_page'    => $interns->lastPage(),
                    'per_page'     => $interns->perPage(),
                    'total'        => $interns->total(),
                    'has_more'     => $interns->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/interns/{id} ─────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $intern = InternJoiningForm::with([
            'educationalDetails',
            'employmentDetails',
            'familyDetails',
            'documents',
            'convertedEmployee',
            'department',
            'role',
            'portalUser',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->mapDetail($intern),
        ]);
    }

    // ── GET /api/mobile/hrms/interns/meta ─────────────────────────────────────
    public function meta(): JsonResponse
    {
        $departments = Department::whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Mirrors InternJoiningForm::STATUS_ACTIVE / STATUS_RESIGNED — same
        // two values intern-index.blade.php's Status <select> offers.
        $statuses = [InternJoiningForm::STATUS_ACTIVE, InternJoiningForm::STATUS_RESIGNED];

        return response()->json([
            'success' => true,
            'data'    => [
                'departments' => $departments,
                'statuses'    => $statuses,
            ],
        ]);
    }

    // ── Private: list shape (compact) ─────────────────────────────────────────
    private function mapList(InternJoiningForm $i): array
    {
        return [
            'id'              => $i->id,
            'name'            => $i->name,
            'email'           => $i->email,
            'mobile'          => $i->mobile,
            'father_name'     => $i->father_name      ?? '',
            'date_of_birth'   => optional($i->date_of_birth)->format('d M Y') ?? '',
            'age'             => $i->date_of_birth
                                    ? $i->date_of_birth->age . ' yrs'
                                    : '',
            'declaration_date'=> optional($i->declaration_date)->format('d M Y') ?? '',
            'avatar_initial'  => strtoupper(substr($i->name, 0, 1)),
            'created_at'      => optional($i->created_at)->format('d M Y'),

            // Mirrors the remaining intern-index.blade.php table columns
            // (Intern ID, Department, Internship Timeline, Status, and the
            // "Converted to {employee_id}" note).
            'intern_id'                  => $i->intern_id ?: '',
            'department'                 => optional($i->department)->name ?? '',
            'internship_status'          => $i->internship_status ?: 'active',
            'internship_start_date'      => optional($i->internship_start_date)->format('d M Y') ?? '',
            'internship_end_date'        => optional($i->internship_end_date)->format('d M Y') ?? '',
            'internship_duration_months' => $i->internship_duration_months,
            'converted_to_employee'      => $i->convertedEmployee?->employee_id,
            'photograph_url'             => $i->photograph ? asset('storage/' . $i->photograph) : null,
        ];
    }

    // ── Private: detail shape (full) ──────────────────────────────────────────
    private function mapDetail(InternJoiningForm $i): array
    {
        return [
            'id'                       => $i->id,
            'name'                     => $i->name,
            'email'                    => $i->email,
            'mobile'                   => $i->mobile,
            'avatar_initial'           => strtoupper(substr($i->name, 0, 1)),
            'photograph_url'           => $i->photograph ? asset('storage/' . $i->photograph) : null,

            // Internship — mirrors the "Personal Details" card's internship
            // rows plus the sidebar's Internship Status/snapshot.
            'intern_id'                  => $i->intern_id ?: '',
            'internship_status'          => $i->internship_status ?: 'active',
            'internship_start_date'      => optional($i->internship_start_date)->format('d M Y') ?? '',
            'internship_end_date'        => optional($i->internship_end_date)->format('d M Y') ?? '',
            'internship_duration_months' => $i->internship_duration_months,
            'department'                 => optional($i->department)->name ?? '',
            'role'                       => optional($i->role)->display_name ?? optional($i->role)->name ?? '',

            // Conversion status — mirrors the "Conversion Status" row
            // (either a link to the converted employee, or "Not converted yet").
            'converted_to_employee' => $i->convertedEmployee ? [
                'id'          => $i->convertedEmployee->id,
                'employee_id' => $i->convertedEmployee->employee_id,
            ] : null,

            // Portal account — mirrors the "Portal Account" / "Portal Email" rows.
            'portal_account' => [
                'enabled' => (bool) $i->portalUser,
                'email'   => $i->portalUser?->email ?? '',
            ],

            // Personal
            'father_name'              => $i->father_name             ?? '',
            'date_of_birth'            => optional($i->date_of_birth)->format('d M Y') ?? '',
            'age'                      => $i->date_of_birth ? $i->date_of_birth->age . ' yrs' : '',
            'blood_group'              => $i->blood_group             ?? '',
            'marital_status'           => $i->marital_status          ?? '',
            'date_of_marriage'         => optional($i->date_of_marriage)->format('d M Y') ?? '',
            'aadhaar_card_no'          => $i->aadhaar_card_no         ?? '',
            'pan_card_no'              => $i->pan_card_no             ?? '',
            'correspondence_address'   => $i->correspondence_address  ?? '',
            'permanent_address'        => $i->permanent_address       ?? '',

            // Emergency
            'emergency_contact_name'   => $i->emergency_contact_name      ?? '',
            'emergency_contact_relation'=> $i->emergency_contact_relation  ?? '',
            'emergency_contact_no'     => $i->emergency_contact_no         ?? '',

            // Declaration
            'declaration_accepted'     => (bool) ($i->declaration_accepted ?? false),
            'declaration_date'         => optional($i->declaration_date)->format('d M Y') ?? '',
            'declaration_place'        => $i->declaration_place ?? '',

            // Documents — mirrors the "Document Downloads" card. These live
            // on the related InternDocument row, not on the form itself.
            'documents' => collect(self::DOCUMENT_LABELS)->map(fn ($label, $field) => [
                'field' => $field,
                'label' => $label,
                'url'   => $i->documents?->{$field} ? asset('storage/' . $i->documents->{$field}) : null,
            ])->values(),

            // Relations
            'educations' => $i->educationalDetails->map(fn ($e) => [
                'qualification'  => $e->qualification    ?? '',
                'institution'    => $e->institution_name ?? '',
                'year'           => $e->year_of_passing  ?? '',
                'percentage'     => $e->percentage       ?? '',
                'specialization' => $e->specialization   ?? '',
            ])->values(),

            'employments' => $i->employmentDetails->map(fn ($e) => [
                'organisation' => $e->organisation ?? '',
                'designation'  => $e->designation  ?? '',
                'period_from'  => optional($e->period_from)->format('d M Y') ?? ($e->period_from ?? ''),
                'period_to'    => optional($e->period_to)->format('d M Y')   ?? ($e->period_to   ?? ''),
                'annual_ctc'   => $e->annual_ctc   ?? '',
            ])->values(),

            'family_details' => $i->familyDetails->map(fn ($f) => [
                'name'         => $f->name          ?? '',
                'relation'     => $f->relation      ?? '',
                'occupation'   => $f->occupation    ?? '',
                'date_of_birth'=> optional($f->date_of_birth)->format('d M Y') ?? '',
                'mobile_no'    => $f->mobile_no     ?? '',
            ])->values(),

            'created_at' => optional($i->created_at)->format('d M Y'),
        ];
    }
}