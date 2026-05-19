@php
    $form = $form ?? null;
    $fieldRows = old('fields');

    if (! is_array($fieldRows)) {
        $fieldRows = $form
            ? $form->fields->map(fn ($field) => [
                'id' => $field->id,
                'label' => $field->label,
                'field_type' => $field->field_type,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'is_required' => $field->is_required,
                'options_text' => is_array($field->options) ? implode(PHP_EOL, $field->options) : '',
            ])->toArray()
            : [
                ['label' => '', 'field_type' => 'text', 'placeholder' => '', 'help_text' => '', 'is_required' => true, 'options_text' => ''],
            ];
    }
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="eob-card">
        <div class="eob-card-head">
            <div>
                <div class="eob-card-title">Form Details</div>
                <div class="eob-card-sub">Create shareable forms with dynamic fields, public links, and response tracking.</div>
            </div>
        </div>
        <div class="eob-card-body">
            <div class="df-grid">
                <div class="eob-group">
                    <label class="eob-label">Form Title <span class="eob-label-required">*</span></label>
                    <input type="text" name="title" class="eob-input" value="{{ old('title', $form?->title) }}" required>
                    @error('title')<div class="eob-error">{{ $message }}</div>@enderror
                </div>
                <div class="eob-group">
                    <label class="eob-label">Success Message</label>
                    <input type="text" name="success_message" class="eob-input" value="{{ old('success_message', $form?->success_message) }}" placeholder="Thank you for your response.">
                    @error('success_message')<div class="eob-error">{{ $message }}</div>@enderror
                </div>
                <div class="eob-group df-full">
                    <label class="eob-label">Description</label>
                    <textarea name="description" class="eob-textarea">{{ old('description', $form?->description) }}</textarea>
                    @error('description')<div class="eob-error">{{ $message }}</div>@enderror
                </div>
                <div class="eob-group">
                    <label class="check-row">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $form?->is_active ?? true))>
                        <span>Form is active and share link should work</span>
                    </label>
                </div>
                <div class="eob-group">
                    <label class="check-row">
                        <input type="checkbox" name="allow_multiple_submissions" value="1" @checked(old('allow_multiple_submissions', $form?->allow_multiple_submissions ?? true))>
                        <span>Allow multiple submissions from shared link</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="eob-card">
        <div class="eob-card-head">
            <div>
                <div class="eob-card-title">Fields</div>
                <div class="eob-card-sub">Add text, number, dropdown, radio, checkbox, textarea, and file upload fields.</div>
            </div>
            <button type="button" class="eob-btn eob-btn-primary" id="addFieldRow">Add Field</button>
        </div>
        <div class="eob-card-body">
            @error('fields')<div class="eob-error mb-3">{{ $message }}</div>@enderror
            <div class="df-shell" id="fieldRowsWrap">
                @foreach($fieldRows as $index => $field)
                    <div class="df-field-card" data-field-row>
                        <div class="df-field-card-head">
                            <div class="df-field-title">Field {{ $index + 1 }}</div>
                            <button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-field>Remove</button>
                        </div>
                        <div class="df-field-grid">
                            <input type="hidden" name="fields[{{ $index }}][id]" value="{{ $field['id'] ?? '' }}">
                            <div class="eob-group">
                                <label class="eob-label">Label <span class="eob-label-required">*</span></label>
                                <input type="text" name="fields[{{ $index }}][label]" class="eob-input" value="{{ $field['label'] ?? '' }}" required>
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Field Type <span class="eob-label-required">*</span></label>
                                <select name="fields[{{ $index }}][field_type]" class="eob-select" data-field-type>
                                    @foreach(['text' => 'Text', 'number' => 'Number', 'textarea' => 'Textarea', 'select' => 'Dropdown', 'radio' => 'Radio Button', 'checkbox' => 'Checkbox', 'file' => 'File Upload'] as $value => $label)
                                        <option value="{{ $value }}" @selected(($field['field_type'] ?? 'text') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Placeholder</label>
                                <input type="text" name="fields[{{ $index }}][placeholder]" class="eob-input" value="{{ $field['placeholder'] ?? '' }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Help Text</label>
                                <input type="text" name="fields[{{ $index }}][help_text]" class="eob-input" value="{{ $field['help_text'] ?? '' }}">
                            </div>
                            <div class="eob-group df-full">
                                <label class="eob-label">Options</label>
                                <textarea name="fields[{{ $index }}][options_text]" class="eob-textarea" data-options-box>{{ $field['options_text'] ?? '' }}</textarea>
                                <div class="df-help">Dropdown / radio / checkbox fields-ku மட்டும். One option per line.</div>
                            </div>
                            <div class="eob-group df-full">
                                <label class="check-row">
                                    <input type="checkbox" name="fields[{{ $index }}][is_required]" value="1" @checked(! empty($field['is_required']))>
                                    <span>This field is required</span>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="eob-foot">
            <a href="{{ $cancelRoute }}" class="eob-btn eob-btn-ghost">Cancel</a>
            <button type="submit" class="eob-btn eob-btn-primary">{{ $submitLabel }}</button>
        </div>
    </div>
</form>

<template id="fieldRowTemplate">
    <div class="df-field-card" data-field-row>
        <div class="df-field-card-head">
            <div class="df-field-title">Field __NUMBER__</div>
            <button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-field>Remove</button>
        </div>
        <div class="df-field-grid">
            <div class="eob-group">
                <label class="eob-label">Label <span class="eob-label-required">*</span></label>
                <input type="text" name="fields[__INDEX__][label]" class="eob-input" required>
            </div>
            <div class="eob-group">
                <label class="eob-label">Field Type <span class="eob-label-required">*</span></label>
                <select name="fields[__INDEX__][field_type]" class="eob-select" data-field-type>
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="textarea">Textarea</option>
                    <option value="select">Dropdown</option>
                    <option value="radio">Radio Button</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="file">File Upload</option>
                </select>
            </div>
            <div class="eob-group">
                <label class="eob-label">Placeholder</label>
                <input type="text" name="fields[__INDEX__][placeholder]" class="eob-input">
            </div>
            <div class="eob-group">
                <label class="eob-label">Help Text</label>
                <input type="text" name="fields[__INDEX__][help_text]" class="eob-input">
            </div>
            <div class="eob-group df-full">
                <label class="eob-label">Options</label>
                <textarea name="fields[__INDEX__][options_text]" class="eob-textarea" data-options-box></textarea>
                <div class="df-help">Dropdown / radio / checkbox fields-ku மட்டும். One option per line.</div>
            </div>
            <div class="eob-group df-full">
                <label class="check-row">
                    <input type="checkbox" name="fields[__INDEX__][is_required]" value="1" checked>
                    <span>This field is required</span>
                </label>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.getElementById('fieldRowsWrap');
    const template = document.getElementById('fieldRowTemplate');
    const addButton = document.getElementById('addFieldRow');

    function updateFieldNumbers() {
        wrap.querySelectorAll('[data-field-row]').forEach(function (row, index) {
            const title = row.querySelector('.df-field-title');
            if (title) {
                title.textContent = 'Field ' + (index + 1);
            }
        });
    }

    function toggleOptionsBox(row) {
        const typeSelect = row.querySelector('[data-field-type]');
        const optionsBox = row.querySelector('[data-options-box]');
        const needsOptions = ['select', 'radio', 'checkbox'].includes(typeSelect.value);
        optionsBox.disabled = !needsOptions;
        optionsBox.closest('.eob-group').style.opacity = needsOptions ? '1' : '.55';
    }

    addButton?.addEventListener('click', function () {
        const index = wrap.querySelectorAll('[data-field-row]').length;
        const html = template.innerHTML
            .replaceAll('__INDEX__', index)
            .replaceAll('__NUMBER__', index + 1);

        wrap.insertAdjacentHTML('beforeend', html);
        const newRow = wrap.lastElementChild;
        toggleOptionsBox(newRow);
        updateFieldNumbers();
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-field]');
        if (!button) {
            return;
        }

        const rows = wrap.querySelectorAll('[data-field-row]');
        if (rows.length === 1) {
            return;
        }

        button.closest('[data-field-row]').remove();
        updateFieldNumbers();
    });

    document.addEventListener('change', function (event) {
        const typeSelect = event.target.closest('[data-field-type]');
        if (!typeSelect) {
            return;
        }

        toggleOptionsBox(typeSelect.closest('[data-field-row]'));
    });

    wrap.querySelectorAll('[data-field-row]').forEach(toggleOptionsBox);
});
</script>
@endpush
