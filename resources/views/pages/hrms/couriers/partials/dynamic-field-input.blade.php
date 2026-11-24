@php
    $fieldName = "fields[{$field->id}]";
    $errorKey = "fields.{$field->id}";
    $oldVal = old("fields.{$field->id}");
    $currentVal = $oldVal !== null ? $oldVal : ($existingValue?->value ?? null);
    $options = is_array($field->options) ? $field->options : [];
    $isFull = in_array($field->field_type, ['textarea'], true);
    $isRequired = (bool) $field->is_required;
    $hasExistingFile = $existingValue && ! empty($existingValue->file_path);
@endphp

<div class="eob-group {{ $isFull ? 'full' : '' }}">
    <label class="eob-label">
        {{ $field->label }}
        @if($isRequired)
            <span class="eob-label-required">*</span>
        @endif
    </label>

    @if($field->field_type === 'textarea')
        <textarea name="{{ $fieldName }}"
                  class="eob-textarea @error($errorKey) is-invalid @enderror"
                  placeholder="{{ $field->placeholder }}"
                  @required($isRequired)>{{ is_array($currentVal) ? implode(', ', $currentVal) : $currentVal }}</textarea>

    @elseif($field->field_type === 'number')
        <input type="number" step="any" name="{{ $fieldName }}"
               class="eob-input @error($errorKey) is-invalid @enderror"
               value="{{ is_array($currentVal) ? '' : $currentVal }}"
               placeholder="{{ $field->placeholder }}"
               @required($isRequired)>

    @elseif($field->field_type === 'date')
        <input type="date" name="{{ $fieldName }}"
               class="eob-input @error($errorKey) is-invalid @enderror"
               value="{{ is_array($currentVal) ? '' : $currentVal }}"
               @required($isRequired)>

    @elseif($field->field_type === 'select')
        <select name="{{ $fieldName }}"
                class="eob-select @error($errorKey) is-invalid @enderror"
                @required($isRequired)>
            <option value="">{{ $field->placeholder ?: 'Select an option' }}</option>
            @foreach($options as $option)
                <option value="{{ $option }}" @selected((string) $currentVal === (string) $option)>{{ $option }}</option>
            @endforeach
        </select>

    @elseif($field->field_type === 'radio')
        <div class="eob-radio-group">
            @foreach($options as $option)
                <label class="eob-radio">
                    <input type="radio" name="{{ $fieldName }}" value="{{ $option }}"
                           @checked((string) $currentVal === (string) $option)
                           @if($loop->first && $isRequired) required @endif>
                    <span>{{ $option }}</span>
                </label>
            @endforeach
        </div>

    @elseif($field->field_type === 'checkbox')
        @if(count($options) > 0)
            @php
                $selectedOptions = is_array($currentVal)
                    ? $currentVal
                    : array_values(array_filter(array_map('trim', explode(',', (string) $currentVal))));
            @endphp
            <div class="cr-option-list">
                @foreach($options as $option)
                    <label class="cr-check-row">
                        <input type="checkbox" name="{{ $fieldName }}[]" value="{{ $option }}"
                               @checked(in_array((string) $option, array_map('strval', $selectedOptions), true))>
                        <span>{{ $option }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <label class="cr-check-row" style="min-height:42px;">
                <input type="hidden" name="{{ $fieldName }}" value="0">
                <input type="checkbox" name="{{ $fieldName }}" value="1"
                       @checked(in_array(strtolower((string) $currentVal), ['1', 'true', 'yes', 'on'], true))
                       @required($isRequired)>
                <span>{{ $field->placeholder ?: 'Yes' }}</span>
            </label>
        @endif

    @elseif($field->field_type === 'file')
        <input type="file" name="{{ $fieldName }}"
               class="eob-input @error($errorKey) is-invalid @enderror"
               @if($isRequired && ! $hasExistingFile) required @endif>
        <div class="eob-help">Allowed: PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX, CSV, TXT (max 10 MB).</div>

        @if($hasExistingFile && isset($courier))
            <div class="cr-file-preview-box">
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="cr-help" style="color:#1e293b; font-weight:700;">
                        <i class="bi bi-paperclip"></i> {{ $existingValue->file_name ?: 'Uploaded File' }}
                    </span>
                    <a href="{{ asset('storage/' . $existingValue->file_path) }}" target="_blank" class="cr-file-pill">
                        <i class="bi bi-eye"></i> View
                    </a>
                    <a href="{{ route('hrms.couriers.values.download', [$courier, $existingValue]) }}" class="cr-file-pill">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
                <label class="cr-check-row" style="color:#be123c; font-size:12px;">
                    <input type="checkbox" name="remove_files[{{ $field->id }}]" value="1">
                    <span>Remove existing file</span>
                </label>
            </div>
        @endif

    @else
        <input type="text" name="{{ $fieldName }}"
               class="eob-input @error($errorKey) is-invalid @enderror"
               value="{{ is_array($currentVal) ? '' : $currentVal }}"
               placeholder="{{ $field->placeholder }}"
               @required($isRequired)>
    @endif

    @if($field->help_text)
        <div class="eob-help">{{ $field->help_text }}</div>
    @endif
    @error($errorKey)<div class="eob-error">{{ $message }}</div>@enderror
    @error($errorKey . '.*')<div class="eob-error">{{ $message }}</div>@enderror
</div>
