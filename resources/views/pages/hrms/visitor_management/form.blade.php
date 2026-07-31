@php
    $selectedType = old('visitor_type', $visitor->visitor_type ?: 'others');
@endphp
<div class="eob-form-grid">
    <div class="eob-group">
        <label class="eob-label">Visitor Name <span class="eob-label-required">*</span></label>
        <input type="text" name="visitor_name" class="eob-input" value="{{ old('visitor_name', $visitor->visitor_name) }}" required>
        @error('visitor_name')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group">
        <label class="eob-label">Visitor Type <span class="eob-label-required">*</span></label>
        <select name="visitor_type" class="eob-select" id="visitorTypeSelect" required>
            <option value="others" @selected($selectedType === 'others')>Others</option>
            <option value="candidate" @selected($selectedType === 'candidate')>Candidate</option>
            <option value="client" @selected($selectedType === 'client')>Client</option>
        </select>
        @error('visitor_type')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <!-- Candidate Specific Fields -->
    <div class="eob-group candidate-field" style="display: none;">
        <label class="eob-label">Email ID <span class="eob-label-required">*</span></label>
        <input type="email" name="email" class="eob-input" id="candidateEmailInput" value="{{ old('email', $visitor->email) }}" placeholder="Enter email address">
        @error('email')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group candidate-field" style="display: none;">
        <label class="eob-label">Applied Position <span class="eob-label-required">*</span></label>
        <input type="text" name="applied_position" class="eob-input" id="candidatePositionInput" value="{{ old('applied_position', $visitor->applied_position) }}" placeholder="Enter applied position">
        @error('applied_position')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <!-- Client Specific Fields -->
    <div class="eob-group client-field" style="display: none;">
        <label class="eob-label">Company Name <span class="eob-label-required">*</span></label>
        <input type="text" name="company_name" class="eob-input" id="clientCompanyInput" value="{{ old('company_name', $visitor->company_name) }}" placeholder="Enter company name">
        @error('company_name')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group">
        <label class="eob-label">Mobile Number <span class="eob-label-required">*</span></label>
        <input type="text" name="mobile_number" class="eob-input" value="{{ old('mobile_number', $visitor->mobile_number) }}" required>
        @error('mobile_number')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group">
        <label class="eob-label">Date <span class="eob-label-required">*</span></label>
        <input type="date" name="visit_date" class="eob-input" value="{{ old('visit_date', optional($visitor->visit_date)->format('Y-m-d') ?: $visitor->visit_date) }}" required>
        @error('visit_date')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group">
        <label class="eob-label">In Time <span class="eob-label-required">*</span></label>
        <input type="time" name="in_time" class="eob-input" value="{{ old('in_time', $visitor->in_time ? \Carbon\Carbon::parse($visitor->in_time)->format('H:i') : '') }}" required>
        @error('in_time')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group">
        <label class="eob-label">Out Time</label>
        <input type="time" name="out_time" class="eob-input" value="{{ old('out_time', $visitor->out_time ? \Carbon\Carbon::parse($visitor->out_time)->format('H:i') : '') }}">
        @error('out_time')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group">
        <label class="eob-label">Whom To Meet <span class="eob-label-required">*</span></label>
        <input type="text" name="person_to_meet" class="eob-input" value="{{ old('person_to_meet', $visitor->person_to_meet) }}" required>
        @error('person_to_meet')<div class="eob-error">{{ $message }}</div>@enderror
    </div>

    <div class="eob-group full">
        <label class="eob-label">Purpose of Meet <span class="eob-label-required">*</span></label>
        <textarea name="remarks" class="eob-textarea" placeholder="Enter purpose of meeting" required>{{ old('remarks', $visitor->remarks) }}</textarea>
        @error('remarks')<div class="eob-error">{{ $message }}</div>@enderror
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const visitorTypeSelect = document.getElementById('visitorTypeSelect');
    const candidateFields = document.querySelectorAll('.candidate-field');
    const clientFields = document.querySelectorAll('.client-field');
    const candidateEmail = document.getElementById('candidateEmailInput');
    const candidatePosition = document.getElementById('candidatePositionInput');
    const clientCompany = document.getElementById('clientCompanyInput');

    function toggleVisitorTypeFields() {
        const value = visitorTypeSelect ? visitorTypeSelect.value : 'others';

        candidateFields.forEach(el => {
            el.style.display = (value === 'candidate') ? 'block' : 'none';
        });

        clientFields.forEach(el => {
            el.style.display = (value === 'client') ? 'block' : 'none';
        });

        if (candidateEmail) candidateEmail.required = (value === 'candidate');
        if (candidatePosition) candidatePosition.required = (value === 'candidate');
        if (clientCompany) clientCompany.required = (value === 'client');
    }

    if (visitorTypeSelect) {
        visitorTypeSelect.addEventListener('change', toggleVisitorTypeFields);
        toggleVisitorTypeFields();
    }
});
</script>
