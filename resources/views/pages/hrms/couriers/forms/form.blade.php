@php
    $form = $form ?? null;
    $fieldTypes = \App\Models\CourierFormField::FIELD_TYPES;
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
                'show_in_list' => $field->show_in_list,
                'options_text' => is_array($field->options) ? implode(PHP_EOL, $field->options) : '',
            ])->toArray()
            : [
                ['label' => '', 'field_type' => 'text', 'placeholder' => '', 'help_text' => '', 'is_required' => true, 'show_in_list' => true, 'options_text' => ''],
            ];
    }
@endphp

<form method="POST" action="{{ $action }}" id="courierFormBuilder">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="cr-shell">
        <div class="eob-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Courier Form Details</div>
                    <div class="eob-card-sub">Define a dynamic Courier form template (e.g., Inward Courier, Outward Courier, Document Dispatch).</div>
                </div>
            </div>
            <div class="eob-card-body">
                <div class="cr-grid">
                    <div class="eob-group">
                        <label class="eob-label">Form Title <span class="eob-label-required">*</span></label>
                        <input type="text" name="title" class="eob-input @error('title') is-invalid @enderror"
                               value="{{ old('title', $form?->title) }}"
                               placeholder="e.g. Outward Courier Form" required>
                        @error('title')<div class="eob-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="eob-group" style="justify-content:flex-end;">
                        <label class="eob-label">Status</label>
                        <label class="cr-check-row" style="min-height:44px; padding:0 4px;">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $form?->is_active ?? true))>
                            <span>Active (available when creating Courier entries)</span>
                        </label>
                    </div>

                    <div class="eob-group cr-full">
                        <label class="eob-label">Description</label>
                        <textarea name="description" class="eob-textarea @error('description') is-invalid @enderror"
                                  style="min-height:80px;"
                                  placeholder="Optional notes about when to use this Courier form...">{{ old('description', $form?->description) }}</textarea>
                        @error('description')<div class="eob-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="eob-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Dynamic Form Fields</div>
                    <div class="eob-card-sub">Add Textbox, Number, Textarea, Date, Dropdown, Radio Button, Checkbox, and File Upload fields.</div>
                </div>
                <button type="button" class="eob-btn eob-btn-primary" id="addCourierFieldRow">
                    <i class="bi bi-plus-lg"></i> Add Field
                </button>
            </div>
            <div class="eob-card-body">
                @error('fields')<div class="eob-error" style="margin-bottom:12px;">{{ $message }}</div>@enderror

                <div class="cr-shell" id="courierFieldRowsWrap">
                    @foreach($fieldRows as $index => $field)
                        @php
                            $fieldType = $field['field_type'] ?? 'text';
                            $typeLabel = $fieldTypes[$fieldType] ?? strtoupper($fieldType);
                        @endphp
                        <div class="cr-field-card" data-field-row>
                            <div class="cr-field-card-head">
                                <div class="cr-field-title-wrap">
                                    <span class="cr-field-title">Field {{ $index + 1 }}</span>
                                    <span class="cr-type-pill" data-type-badge>{{ $typeLabel }}</span>
                                </div>
                                <div class="eob-inline-actions">
                                    <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-move-up title="Move Up">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-move-down title="Move Down">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-field>
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </div>
                            </div>

                            <div class="cr-field-grid">
                                <input type="hidden" name="fields[{{ $index }}][id]" value="{{ $field['id'] ?? '' }}" data-field-input="id">

                                <div class="eob-group">
                                    <label class="eob-label">Field Label <span class="eob-label-required">*</span></label>
                                    <input type="text" name="fields[{{ $index }}][label]"
                                           class="eob-input @error("fields.{$index}.label") is-invalid @enderror"
                                           value="{{ $field['label'] ?? '' }}"
                                           placeholder="e.g. Tracking Number, Courier Partner, Dispatch Date"
                                           data-field-input="label" required>
                                    @error("fields.{$index}.label")<div class="eob-error">{{ $message }}</div>@enderror
                                </div>

                                <div class="eob-group">
                                    <label class="eob-label">Field Type <span class="eob-label-required">*</span></label>
                                    <select name="fields[{{ $index }}][field_type]"
                                            class="eob-select @error("fields.{$index}.field_type") is-invalid @enderror"
                                            data-field-type data-field-input="field_type">
                                        @foreach($fieldTypes as $value => $label)
                                            <option value="{{ $value }}" @selected($fieldType === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error("fields.{$index}.field_type")<div class="eob-error">{{ $message }}</div>@enderror
                                </div>

                                <div class="eob-group">
                                    <label class="eob-label">Placeholder</label>
                                    <input type="text" name="fields[{{ $index }}][placeholder]" class="eob-input"
                                           value="{{ $field['placeholder'] ?? '' }}"
                                           placeholder="Optional hint inside input"
                                           data-field-input="placeholder">
                                </div>

                                <div class="eob-group">
                                    <label class="eob-label">Help Text</label>
                                    <input type="text" name="fields[{{ $index }}][help_text]" class="eob-input"
                                           value="{{ $field['help_text'] ?? '' }}"
                                           placeholder="Optional note shown below field"
                                           data-field-input="help_text">
                                </div>

                                <div class="eob-group cr-full" data-options-group>
                                    <label class="eob-label">Options <span class="cr-help" style="font-weight:500;">(For Dropdown, Radio Button, or Checkbox — one option per line)</span></label>
                                    <textarea name="fields[{{ $index }}][options_text]"
                                              class="eob-textarea @error("fields.{$index}.options_text") is-invalid @enderror"
                                              style="min-height:86px;"
                                              placeholder="BlueDart&#10;DTDC&#10;Delhivery&#10;India Post"
                                              data-options-box data-field-input="options_text">{{ $field['options_text'] ?? '' }}</textarea>
                                    @error("fields.{$index}.options_text")<div class="eob-error">{{ $message }}</div>@enderror
                                </div>

                                <div class="eob-group cr-full">
                                    <div style="display:flex; gap:24px; flex-wrap:wrap; padding-top:4px;">
                                        <label class="cr-check-row">
                                            <input type="checkbox" name="fields[{{ $index }}][is_required]" value="1"
                                                   @checked(! empty($field['is_required'])) data-field-input="is_required">
                                            <span>Required field</span>
                                        </label>
                                        <label class="cr-check-row">
                                            <input type="checkbox" name="fields[{{ $index }}][show_in_list]" value="1"
                                                   @checked(! array_key_exists('show_in_list', $field) || ! empty($field['show_in_list'])) data-field-input="show_in_list">
                                            <span>Show column in Courier table</span>
                                        </label>
                                    </div>
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
    </div>
</form>

<template id="courierFieldRowTemplate">
    <div class="cr-field-card" data-field-row>
        <div class="cr-field-card-head">
            <div class="cr-field-title-wrap">
                <span class="cr-field-title">Field __NUMBER__</span>
                <span class="cr-type-pill" data-type-badge>Textbox</span>
            </div>
            <div class="eob-inline-actions">
                <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-move-up title="Move Up">
                    <i class="bi bi-arrow-up"></i>
                </button>
                <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-move-down title="Move Down">
                    <i class="bi bi-arrow-down"></i>
                </button>
                <button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-field>
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        </div>

        <div class="cr-field-grid">
            <input type="hidden" name="fields[__INDEX__][id]" value="" data-field-input="id">

            <div class="eob-group">
                <label class="eob-label">Field Label <span class="eob-label-required">*</span></label>
                <input type="text" name="fields[__INDEX__][label]" class="eob-input"
                       placeholder="e.g. Tracking Number, Courier Partner, Dispatch Date"
                       data-field-input="label" required>
            </div>

            <div class="eob-group">
                <label class="eob-label">Field Type <span class="eob-label-required">*</span></label>
                <select name="fields[__INDEX__][field_type]" class="eob-select" data-field-type data-field-input="field_type">
                    @foreach($fieldTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="eob-group">
                <label class="eob-label">Placeholder</label>
                <input type="text" name="fields[__INDEX__][placeholder]" class="eob-input"
                       placeholder="Optional hint inside input" data-field-input="placeholder">
            </div>

            <div class="eob-group">
                <label class="eob-label">Help Text</label>
                <input type="text" name="fields[__INDEX__][help_text]" class="eob-input"
                       placeholder="Optional note shown below field" data-field-input="help_text">
            </div>

            <div class="eob-group cr-full" data-options-group>
                <label class="eob-label">Options <span class="cr-help" style="font-weight:500;">(For Dropdown, Radio Button, or Checkbox — one option per line)</span></label>
                <textarea name="fields[__INDEX__][options_text]" class="eob-textarea" style="min-height:86px;"
                          placeholder="BlueDart&#10;DTDC&#10;Delhivery&#10;India Post"
                          data-options-box data-field-input="options_text"></textarea>
            </div>

            <div class="eob-group cr-full">
                <div style="display:flex; gap:24px; flex-wrap:wrap; padding-top:4px;">
                    <label class="cr-check-row">
                        <input type="checkbox" name="fields[__INDEX__][is_required]" value="1" checked data-field-input="is_required">
                        <span>Required field</span>
                    </label>
                    <label class="cr-check-row">
                        <input type="checkbox" name="fields[__INDEX__][show_in_list]" value="1" checked data-field-input="show_in_list">
                        <span>Show column in Courier table</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.getElementById('courierFieldRowsWrap');
    const template = document.getElementById('courierFieldRowTemplate');
    const addButton = document.getElementById('addCourierFieldRow');
    const typeLabels = @json($fieldTypes);

    function reindexFields() {
        const rows = wrap.querySelectorAll('[data-field-row]');
        rows.forEach(function (row, index) {
            const title = row.querySelector('.cr-field-title');
            if (title) {
                title.textContent = 'Field ' + (index + 1);
            }

            row.querySelectorAll('[data-field-input]').forEach(function (el) {
                const key = el.getAttribute('data-field-input');
                el.name = 'fields[' + index + '][' + key + ']';
            });

            const upBtn = row.querySelector('[data-move-up]');
            const downBtn = row.querySelector('[data-move-down]');
            if (upBtn) upBtn.disabled = index === 0;
            if (downBtn) downBtn.disabled = index === rows.length - 1;
        });
    }

    function syncRowUI(row) {
        const typeSelect = row.querySelector('[data-field-type]');
        const optionsGroup = row.querySelector('[data-options-group]');
        const optionsBox = row.querySelector('[data-options-box]');
        const badge = row.querySelector('[data-type-badge]');
        const typeVal = typeSelect.value;

        if (badge) {
            badge.textContent = typeLabels[typeVal] || typeVal.toUpperCase();
        }

        const supportsOptions = ['select', 'radio', 'checkbox'].includes(typeVal);
        if (optionsBox && optionsGroup) {
            optionsBox.disabled = !supportsOptions;
            optionsGroup.style.display = supportsOptions ? 'flex' : 'none';
        }
    }

    addButton?.addEventListener('click', function () {
        const index = wrap.querySelectorAll('[data-field-row]').length;
        const html = template.innerHTML
            .replaceAll('__INDEX__', index)
            .replaceAll('__NUMBER__', index + 1);

        wrap.insertAdjacentHTML('beforeend', html);
        const newRow = wrap.lastElementChild;
        syncRowUI(newRow);
        reindexFields();
    });

    wrap?.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('[data-remove-field]');
        if (removeBtn) {
            const rows = wrap.querySelectorAll('[data-field-row]');
            if (rows.length <= 1) {
                alert('At least one field is required in a Courier form.');
                return;
            }
            removeBtn.closest('[data-field-row]').remove();
            reindexFields();
            return;
        }

        const upBtn = event.target.closest('[data-move-up]');
        if (upBtn) {
            const row = upBtn.closest('[data-field-row]');
            if (row && row.previousElementSibling) {
                wrap.insertBefore(row, row.previousElementSibling);
                reindexFields();
            }
            return;
        }

        const downBtn = event.target.closest('[data-move-down]');
        if (downBtn) {
            const row = downBtn.closest('[data-field-row]');
            if (row && row.nextElementSibling) {
                wrap.insertBefore(row.nextElementSibling, row);
                reindexFields();
            }
        }
    });

    wrap?.addEventListener('change', function (event) {
        const typeSelect = event.target.closest('[data-field-type]');
        if (typeSelect) {
            syncRowUI(typeSelect.closest('[data-field-row]'));
        }
    });

    wrap?.querySelectorAll('[data-field-row]').forEach(syncRowUI);
    reindexFields();
});
</script>
@endpush
