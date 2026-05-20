<div class="eob-form-grid">
    <div class="eob-group">
        <label class="eob-label">Visitor Name <span class="eob-label-required">*</span></label>
        <input type="text" name="visitor_name" class="eob-input" value="{{ old('visitor_name', $visitor->visitor_name) }}" required>
        @error('visitor_name')<div class="eob-error">{{ $message }}</div>@enderror
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
