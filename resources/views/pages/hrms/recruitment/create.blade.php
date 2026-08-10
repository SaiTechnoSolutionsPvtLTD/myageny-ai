@extends('layouts.app')

@section('title', 'Add Recruitment Candidate')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.recruitment.styles')
    <style>
        .rec-section-card {
            background: #fff;
            border: 1px solid #e1dee3;
            border-radius: 16px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .rec-section-head {
            padding: 16px 22px;
            background: #fafafa;
            border-bottom: 1px solid #f0eef2;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .rec-section-title {
            font-size: 15px;
            font-weight: 800;
            color: #121212;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rec-type-selector {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .rec-type-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            border: 2px solid #e1dee3;
            background: #fff;
            font-weight: 700;
            font-size: 13px;
            color: #444;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .rec-type-label:hover {
            border-color: #fe5f04;
            background: #fff7f1;
        }
        .rec-type-label input[type="radio"] {
            accent-color: #fe5f04;
            width: 16px;
            height: 16px;
        }
        .rec-type-label.active {
            border-color: #fe5f04;
            background: #fff7f1;
            color: #fe5f04;
        }
        .rec-edu-tbl th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Add Candidate</div>
            <div class="eob-breadcrumb">HRMS > Recruitment > Add Candidate</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('recruitment.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please review the highlighted fields and try again.</div>
        @endif

        <form method="POST" action="{{ route('recruitment.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Basic Candidate Details Card --}}
            <div class="rec-section-card">
                <div class="rec-section-head">
                    <div class="rec-section-title">Candidate Profile Details</div>
                    <div class="rec-chip rec-chip-applied">{{ $candidateNo }}</div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group">
                            <label class="eob-label">Candidate Name <span class="eob-label-required">*</span></label>
                            <input type="text" name="name" class="eob-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Full Candidate Name" required>
                            @error('name')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Mobile Number <span class="eob-label-required">*</span></label>
                            <input type="text" name="mobile_number" class="eob-input @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number') }}" placeholder="10 digit mobile number" required>
                            @error('mobile_number')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Email Address</label>
                            <input type="email" name="email" class="eob-input @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="candidate@example.com">
                            @error('email')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Location / City</label>
                            <input type="text" name="location" class="eob-input @error('location') is-invalid @enderror" value="{{ old('location') }}" placeholder="Chennai, Bangalore, Coimbatore...">
                            @error('location')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Job Applied For <span class="eob-label-required">*</span></label>
                            <input type="text" name="job_title" class="eob-input @error('job_title') is-invalid @enderror" value="{{ old('job_title') }}" placeholder="PHP Developer, HR Executive..." required>
                            @error('job_title')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Expected CTC (₹)</label>
                            <input type="number" step="0.01" min="0" name="expected_ctc" class="eob-input @error('expected_ctc') is-invalid @enderror" value="{{ old('expected_ctc') }}" placeholder="e.g. 350000">
                            @error('expected_ctc')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        {{-- Source Dropdown & Others field --}}
                        <div class="eob-group">
                            <label class="eob-label">Source</label>
                            <select name="source" id="sourceSelect" class="eob-select @error('source') is-invalid @enderror" onchange="toggleSourceDetails()">
                                <option value="">Select Source</option>
                                <option value="Mail" @selected(old('source') === 'Mail')>Mail</option>
                                <option value="Whatsapp" @selected(old('source') === 'Whatsapp')>Whatsapp</option>
                                <option value="Naukri" @selected(old('source') === 'Naukri')>Naukri</option>
                                <option value="Indeed" @selected(old('source') === 'Indeed')>Indeed</option>
                                <option value="Others" @selected(old('source') === 'Others')>Others</option>
                            </select>
                            @error('source')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group" id="sourceDetailsGroup" style="display: {{ old('source') === 'Others' ? 'flex' : 'none' }};">
                            <label class="eob-label">Other Source Details <span class="eob-label-required">*</span></label>
                            <input type="text" name="source_details" id="sourceDetailsInput" class="eob-input @error('source_details') is-invalid @enderror" value="{{ old('source_details') }}" placeholder="Specify source details...">
                            @error('source_details')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Applicant Type: Fresher / Intern / Experienced --}}
            <div class="rec-section-card">
                <div class="rec-section-head">
                    <div class="rec-section-title">Candidate Type</div>
                    <div class="eob-help">Select candidate type: Fresher, Intern, or Experienced worker.</div>
                </div>
                <div class="eob-card-body">
                    <div class="rec-type-selector">
                        <label class="rec-type-label {{ old('candidate_type', 'fresher') === 'fresher' ? 'active' : '' }}" id="typeLabelFresher">
                            <input type="radio" name="candidate_type" value="fresher" onchange="toggleCandidateType()" {{ old('candidate_type', 'fresher') === 'fresher' ? 'checked' : '' }}>
                            Fresher
                        </label>
                        <label class="rec-type-label {{ old('candidate_type') === 'intern' ? 'active' : '' }}" id="typeLabelIntern">
                            <input type="radio" name="candidate_type" value="intern" onchange="toggleCandidateType()" {{ old('candidate_type') === 'intern' ? 'checked' : '' }}>
                            Intern
                        </label>
                        <label class="rec-type-label {{ old('candidate_type') === 'experienced' ? 'active' : '' }}" id="typeLabelExperienced">
                            <input type="radio" name="candidate_type" value="experienced" onchange="toggleCandidateType()" {{ old('candidate_type') === 'experienced' ? 'checked' : '' }}>
                            Experienced
                        </label>
                    </div>
                </div>
            </div>

            {{-- Intern Details Card (Conditional for Intern) --}}
            <div class="rec-section-card" id="internSectionCard" style="display: {{ old('candidate_type') === 'intern' ? 'block' : 'none' }};">
                <div class="rec-section-head">
                    <div class="rec-section-title">Internship Details</div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group">
                            <label class="eob-label">Institute / College Name</label>
                            <input type="text" name="institute_name" class="eob-input @error('institute_name') is-invalid @enderror" value="{{ old('institute_name') }}" placeholder="e.g. Loyola College, Anna University...">
                            @error('institute_name')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Course / Degree</label>
                            <input type="text" name="course_name" class="eob-input @error('course_name') is-invalid @enderror" value="{{ old('course_name') }}" placeholder="e.g. B.E CSE, B.Sc IT, BCA...">
                            @error('course_name')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Internship Duration (Months)</label>
                            <input type="text" name="internship_months" class="eob-input @error('internship_months') is-invalid @enderror" value="{{ old('internship_months') }}" placeholder="e.g. 1 Month, 3 Months, 6 Months...">
                            @error('internship_months')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Stipend Provided?</label>
                            <select name="has_stipend" id="hasStipendSelect" class="eob-select @error('has_stipend') is-invalid @enderror" onchange="toggleStipendAmount()">
                                <option value="">Select Option</option>
                                <option value="yes" @selected(old('has_stipend') === 'yes')>Yes</option>
                                <option value="no" @selected(old('has_stipend') === 'no')>No</option>
                            </select>
                            @error('has_stipend')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group" id="stipendAmountGroup" style="display: {{ old('has_stipend') === 'yes' ? 'flex' : 'none' }};">
                            <label class="eob-label">Stipend Amount (₹)</label>
                            <input type="number" step="0.01" min="0" name="stipend_amount" class="eob-input @error('stipend_amount') is-invalid @enderror" value="{{ old('stipend_amount') }}" placeholder="e.g. 5000">
                            @error('stipend_amount')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Experience Details Card (Conditional for Experienced) --}}
            <div class="rec-section-card" id="experienceSectionCard" style="display: {{ old('candidate_type') === 'experienced' ? 'block' : 'none' }};">
                <div class="rec-section-head">
                    <div class="rec-section-title">Experience & Previous Employment Details</div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group">
                            <label class="eob-label">Experience Years</label>
                            <input type="number" name="experience_years" min="0" max="60" class="eob-input @error('experience_years') is-invalid @enderror" value="{{ old('experience_years') }}" placeholder="e.g. 2">
                            @error('experience_years')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Notice Period</label>
                            <input type="text" name="notice_period" class="eob-input @error('notice_period') is-invalid @enderror" value="{{ old('notice_period') }}" placeholder="Immediate, 15 Days, 30 Days...">
                            @error('notice_period')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Current CTC (₹)</label>
                            <input type="number" step="0.01" min="0" name="current_ctc" class="eob-input @error('current_ctc') is-invalid @enderror" value="{{ old('current_ctc') }}" placeholder="e.g. 250000">
                            @error('current_ctc')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Previous Company Name</label>
                            <input type="text" name="previous_company" class="eob-input @error('previous_company') is-invalid @enderror" value="{{ old('previous_company') }}" placeholder="e.g. ABC Technologies">
                            @error('previous_company')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Previous Company HR Name</label>
                            <input type="text" name="previous_hr_name" class="eob-input @error('previous_hr_name') is-invalid @enderror" value="{{ old('previous_hr_name') }}" placeholder="HR Manager Name">
                            @error('previous_hr_name')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Previous Company HR Contact Number</label>
                            <input type="text" name="previous_hr_contact" class="eob-input @error('previous_hr_contact') is-invalid @enderror" value="{{ old('previous_hr_contact') }}" placeholder="HR Phone Number">
                            @error('previous_hr_contact')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Relieving Reason</label>
                            <input type="text" name="relieving_reason" class="eob-input @error('relieving_reason') is-invalid @enderror" value="{{ old('relieving_reason') }}" placeholder="Reason for leaving previous job">
                            @error('relieving_reason')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group">
                            <label class="eob-label">Own Laptop Available?</label>
                            <select name="has_laptop" class="eob-select @error('has_laptop') is-invalid @enderror">
                                <option value="">Select Option</option>
                                <option value="yes" @selected(old('has_laptop') === 'yes')>Yes</option>
                                <option value="no" @selected(old('has_laptop') === 'no')>No</option>
                            </select>
                            @error('has_laptop')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Education Details Card --}}
            <div class="rec-section-card">
                <div class="rec-section-head">
                    <div class="rec-section-title">Education Details</div>
                    <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" onclick="addEducationRow()">+ Add Education</button>
                </div>
                <div class="eob-card-body">
                    <div class="eob-table-wrap">
                        <table class="eob-table rec-edu-tbl" id="educationTable">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Degree / Qualification</th>
                                    <th style="width: 30%;">School / College / Institution</th>
                                    <th style="width: 25%;">Specialization / Stream</th>
                                    <th style="width: 12%;">Passing Year</th>
                                    <th style="width: 13%;">Percentage / CGPA</th>
                                    <th style="width: 45px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="educationTableBody">
                                @php
                                    $oldEdu = old('education_details', []);
                                    if (empty($oldEdu)) {
                                        $oldEdu = [
                                            ['degree' => '', 'institution' => '', 'specialization' => '', 'year_of_passing' => '', 'percentage' => '']
                                        ];
                                    }
                                @endphp
                                @foreach($oldEdu as $index => $edu)
                                    <tr class="edu-row">
                                        <td>
                                            <input type="text" name="education_details[{{ $index }}][degree]" class="eob-input" value="{{ $edu['degree'] ?? '' }}" placeholder="SSLC, HSC, B.E, B.Sc...">
                                        </td>
                                        <td>
                                            <input type="text" name="education_details[{{ $index }}][institution]" class="eob-input" value="{{ $edu['institution'] ?? '' }}" placeholder="School or College Name">
                                        </td>
                                        <td>
                                            <input type="text" name="education_details[{{ $index }}][specialization]" class="eob-input" value="{{ $edu['specialization'] ?? '' }}" placeholder="CSE, IT, ECE, Bio...">
                                        </td>
                                        <td>
                                            <input type="text" name="education_details[{{ $index }}][year_of_passing]" class="eob-input" value="{{ $edu['year_of_passing'] ?? '' }}" placeholder="2023">
                                        </td>
                                        <td>
                                            <input type="text" name="education_details[{{ $index }}][percentage]" class="eob-input" value="{{ $edu['percentage'] ?? '' }}" placeholder="85% / 8.5">
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <button type="button" class="eob-icon-btn danger" onclick="removeEducationRow(this)" title="Remove line">&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Resume & Remarks Card --}}
            <div class="rec-section-card">
                <div class="rec-section-head">
                    <div class="rec-section-title">Resume & Additional Remarks</div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group full">
                            <label class="eob-label">Resume Attachment</label>
                            <input type="file" name="resume" class="eob-input @error('resume') is-invalid @enderror" accept=".pdf,.doc,.docx">
                            <div class="eob-help">Supported formats: PDF, DOC, DOCX up to 5 MB.</div>
                            @error('resume')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="eob-group full">
                            <label class="eob-label">Remarks / Notes</label>
                            <textarea name="remarks" class="eob-textarea @error('remarks') is-invalid @enderror" placeholder="Initial notes from HR, referral details, screening context...">{{ old('remarks') }}</textarea>
                            @error('remarks')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="eob-foot" style="background:#fff; border-radius:16px; border:1px solid #e1dee3; margin-bottom:30px;">
                <a href="{{ route('recruitment.index') }}" class="eob-btn eob-btn-ghost">Cancel</a>
                <button type="submit" class="eob-btn eob-btn-primary">Save Candidate Profile</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleSourceDetails() {
        const sourceSelect = document.getElementById('sourceSelect');
        const detailsGroup = document.getElementById('sourceDetailsGroup');
        const detailsInput = document.getElementById('sourceDetailsInput');

        if (sourceSelect.value === 'Others') {
            detailsGroup.style.display = 'flex';
        } else {
            detailsGroup.style.display = 'none';
            detailsInput.value = '';
        }
    }

    function toggleCandidateType() {
        const selectedType = document.querySelector('input[name="candidate_type"]:checked')?.value || 'fresher';
        const expCard = document.getElementById('experienceSectionCard');
        const internCard = document.getElementById('internSectionCard');
        const labelFresher = document.getElementById('typeLabelFresher');
        const labelIntern = document.getElementById('typeLabelIntern');
        const labelExperienced = document.getElementById('typeLabelExperienced');

        if (labelFresher) labelFresher.classList.toggle('active', selectedType === 'fresher');
        if (labelIntern) labelIntern.classList.toggle('active', selectedType === 'intern');
        if (labelExperienced) labelExperienced.classList.toggle('active', selectedType === 'experienced');

        if (selectedType === 'experienced') {
            if (expCard) expCard.style.display = 'block';
            if (internCard) internCard.style.display = 'none';
        } else if (selectedType === 'intern') {
            if (expCard) expCard.style.display = 'none';
            if (internCard) internCard.style.display = 'block';
        } else {
            if (expCard) expCard.style.display = 'none';
            if (internCard) internCard.style.display = 'none';
        }
    }

    function toggleStipendAmount() {
        const stipendSelect = document.getElementById('hasStipendSelect');
        const stipendGroup = document.getElementById('stipendAmountGroup');
        if (stipendSelect && stipendGroup) {
            stipendGroup.style.display = stipendSelect.value === 'yes' ? 'flex' : 'none';
        }
    }

    let eduRowIndex = {{ count(old('education_details', [1])) }};

    function addEducationRow() {
        const tbody = document.getElementById('educationTableBody');
        const tr = document.createElement('tr');
        tr.className = 'edu-row';
        tr.innerHTML = `
            <td>
                <input type="text" name="education_details[${eduRowIndex}][degree]" class="eob-input" placeholder="SSLC, HSC, B.E, B.Sc...">
            </td>
            <td>
                <input type="text" name="education_details[${eduRowIndex}][institution]" class="eob-input" placeholder="School or College Name">
            </td>
            <td>
                <input type="text" name="education_details[${eduRowIndex}][specialization]" class="eob-input" placeholder="CSE, IT, ECE, Bio...">
            </td>
            <td>
                <input type="text" name="education_details[${eduRowIndex}][year_of_passing]" class="eob-input" placeholder="2023">
            </td>
            <td>
                <input type="text" name="education_details[${eduRowIndex}][percentage]" class="eob-input" placeholder="85% / 8.5">
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <button type="button" class="eob-icon-btn danger" onclick="removeEducationRow(this)" title="Remove line">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
        eduRowIndex++;
    }

    function removeEducationRow(btn) {
        const tbody = document.getElementById('educationTableBody');
        if (tbody.querySelectorAll('.edu-row').length > 1) {
            btn.closest('tr').remove();
        } else {
            const inputs = btn.closest('tr').querySelectorAll('input');
            inputs.forEach(input => input.value = '');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleSourceDetails();
        toggleCandidateType();
    });
</script>
@endpush
