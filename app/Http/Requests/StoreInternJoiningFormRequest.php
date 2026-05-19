<?php

namespace App\Http\Requests;

use App\Models\InternJoiningForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInternJoiningFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function baseRules(): array
    {
        $maxUploadKb = (int) config('interns.max_upload_kb', 2048);
        $fileRules = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . $maxUploadKb];
        $intern = $this->route('intern');
        $portalUserId = $intern?->portal_user_id;

        return [
            'photograph' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:' . $maxUploadKb],
            'name' => ['required', 'string', 'max:150'],
            'father_name' => ['required', 'string', 'max:150'],
            'correspondence_address' => ['required', 'string', 'max:2000'],
            'permanent_address' => ['required', 'string', 'max:2000'],
            'mobile' => ['required', 'regex:/^[0-9]{10}$/'],
            'email' => ['required', 'email', 'max:150'],
            'date_of_birth' => ['required', 'date'],
            'internship_start_date' => ['required', 'date'],
            'internship_duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'internship_end_date' => ['nullable', 'date', 'after_or_equal:internship_start_date'],
            'internship_status' => ['required', Rule::in([InternJoiningForm::STATUS_ACTIVE, InternJoiningForm::STATUS_RESIGNED])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'marital_status' => ['required', Rule::in(['single', 'married'])],
            'date_of_marriage' => ['nullable', 'date', 'required_if:marital_status,married'],
            'aadhaar_card_no' => ['required', 'regex:/^\d{12}$/'],
            'pan_card_no' => ['required', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_relation' => ['required', 'string', 'max:100'],
            'emergency_contact_no' => ['required', 'regex:/^[0-9]{10}$/'],
            'create_portal_account' => ['nullable', 'boolean'],
            'portal_email' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_account')),
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($portalUserId),
            ],
            'portal_password' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_account') && ! $portalUserId),
                'nullable',
                'string',
                'min:8',
            ],
            'branch_id' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_account')),
                'nullable',
                'integer',
                'exists:branches,id',
            ],
            'department_id' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_account')),
                'nullable',
                'integer',
                'exists:departments,id',
            ],
            'role_id' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_account')),
                'nullable',
                'integer',
                'exists:roles,id',
            ],
            'tl_user_id' => [
                Rule::requiredIf(fn () => $this->boolean('create_portal_account')),
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'educational_details' => ['nullable', 'array', 'max:4'],
            'educational_details.*.qualification' => ['nullable', 'string', 'max:100'],
            'educational_details.*.institution_name' => ['nullable', 'string', 'max:255'],
            'educational_details.*.year_of_passing' => ['nullable', 'string', 'max:20'],
            'educational_details.*.percentage' => ['nullable', 'string', 'max:20'],
            'educational_details.*.specialization' => ['nullable', 'string', 'max:255'],

            'employment_details' => ['nullable', 'array', 'max:3'],
            'employment_details.*.organisation' => ['nullable', 'string', 'max:255'],
            'employment_details.*.designation' => ['nullable', 'string', 'max:150'],
            'employment_details.*.period_from' => ['nullable', 'date'],
            'employment_details.*.period_to' => ['nullable', 'date'],
            'employment_details.*.annual_ctc' => ['nullable', 'string', 'max:50'],

            'family_details' => ['nullable', 'array', 'max:5'],
            'family_details.*.name' => ['nullable', 'string', 'max:150'],
            'family_details.*.relation' => ['nullable', 'string', 'max:100'],
            'family_details.*.occupation' => ['nullable', 'string', 'max:150'],
            'family_details.*.date_of_birth' => ['nullable', 'date'],
            'family_details.*.mobile_no' => ['nullable', 'regex:/^[0-9]{10}$/'],

            'document_10th_marksheet' => $fileRules,
            'document_12th_marksheet' => $fileRules,
            'document_consolidated_marksheet' => $fileRules,
            'document_course_completion_certificate' => $fileRules,
            'document_degree_certificate' => $fileRules,
            'document_provisional_certificate' => $fileRules,
            'document_tc' => $fileRules,
            'document_aadhaar_card' => $fileRules,
            'document_pan_card' => $fileRules,
            'document_voter_id' => $fileRules,
            'document_driving_licence' => $fileRules,
            'document_experience_certificate' => $fileRules,
            'document_salary_slips' => $fileRules,
            'document_bank_passbook' => $fileRules,

            'declaration_accepted' => ['nullable', 'boolean'],
            'declaration_date' => ['nullable', 'date'],
            'declaration_place' => ['nullable', 'string', 'max:150'],
            'signature_upload' => $fileRules,
            'signature_data' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_portal_account' => $this->boolean('create_portal_account'),
        ]);
    }
}
