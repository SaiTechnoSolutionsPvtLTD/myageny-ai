<div class="crm-custom-fields-builder" style="margin-top: 10px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding-top:14px; border-top:1px dashed #e2e8f0;">
        <div>
            <div style="font-size:15px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span>Category Form Fields (Dynamic)</span>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:2px;">
                Define custom fields that will appear when creating assets under this category (Text, Number, Checkbox, Select box, File Upload, Date, Radio button).
            </div>
        </div>
        <button type="button" id="btnAddCustomField" class="crm-btn" style="background:#fff7ed; border:1px solid #fdba74; color:#ea580c; font-weight:700; font-size:13px; display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; cursor:pointer;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Add Field</span>
        </button>
    </div>

    {{-- Container for dynamic field cards --}}
    <div id="customFieldsList" style="display:flex; flex-direction:column; gap:12px;"></div>

    {{-- Empty State --}}
    <div id="customFieldsEmptyState" style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:24px; text-align:center;">
        <div style="color:#94a3b8; font-size:28px; margin-bottom:6px;">📋</div>
        <div style="font-size:13px; font-weight:600; color:#475569;">No custom form fields added yet</div>
        <div style="font-size:12px; color:#94a3b8; margin-top:2px;">Click "+ Add Field" above to configure dynamic fields for this category.</div>
    </div>
</div>

<template id="customFieldTemplate">
    <div class="cf-item-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:16px 18px; box-shadow:0 1px 3px rgba(0,0,0,0.03); transition:all 0.2s;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid #f1f5f9;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="cf-badge-num" style="background:#fff7ed; color:#ea580c; font-weight:800; font-size:11px; padding:2px 8px; border-radius:6px; border:1px solid #fed7aa;">#1</span>
                <span class="cf-field-type-badge" style="background:#f1f5f9; color:#475569; font-weight:700; font-size:11px; padding:2px 8px; border-radius:6px; text-transform:uppercase;">TEXT</span>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:#475569; cursor:pointer; user-select:none;">
                    <input type="checkbox" class="cf-input-required" value="1">
                    <span>Required Field</span>
                </label>
                <button type="button" class="cf-btn-delete" style="background:none; border:none; color:#ef4444; cursor:pointer; padding:4px 6px; border-radius:6px; display:inline-flex; align-items:center;" title="Remove this field">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px;">Field Label <span style="color:#ef4444;">*</span></label>
                <input type="text" class="cf-input-label crm-input" placeholder="e.g. RAM Size, Operating System, IMEI" required style="padding:7px 10px; font-size:13px;">
            </div>

            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px;">Field Type <span style="color:#ef4444;">*</span></label>
                <select class="cf-input-type crm-input" style="padding:7px 10px; font-size:13px;">
                    <option value="text">Text (Single line text input)</option>
                    <option value="number">Number (Numeric input)</option>
                    <option value="checkbox">Checkbox (Yes / No or options)</option>
                    <option value="select">Select box (Dropdown list)</option>
                    <option value="file">File Upload (Document / Image)</option>
                    <option value="date">Date (Calendar picker)</option>
                    <option value="radio">Radio button (Single selection)</option>
                </select>
            </div>
        </div>

        {{-- Options row for select / radio / checkbox --}}
        <div class="cf-options-wrap" style="display:none; margin-top:10px;">
            <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:4px;">
                Options <span style="font-size:11px; font-weight:normal; color:#64748b;">(Comma separated: Option 1, Option 2, Option 3)</span>
            </label>
            <input type="text" class="cf-input-options crm-input" placeholder="e.g. 8GB, 16GB, 32GB or Windows, Mac, Linux" style="padding:7px 10px; font-size:13px;">
        </div>

        {{-- Placeholder / Helper row --}}
        <div style="margin-top:10px;">
            <label style="display:block; font-size:12px; font-weight:600; color:#64748b; margin-bottom:4px;">
                Placeholder / Helper Note <span style="font-size:11px; font-weight:normal; color:#94a3b8;">(Optional)</span>
            </label>
            <input type="text" class="cf-input-placeholder crm-input" placeholder="e.g. Enter serial or model specification" style="padding:7px 10px; font-size:13px;">
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('customFieldsList');
    const emptyState = document.getElementById('customFieldsEmptyState');
    const btnAdd = document.getElementById('btnAddCustomField');
    const template = document.getElementById('customFieldTemplate');

    // Initial fields from server
    const initialFields = @json(old('custom_fields', $assetCategory->custom_fields ?? []));

    function checkEmptyState() {
        const count = container.querySelectorAll('.cf-item-card').length;
        if (emptyState) {
            emptyState.style.display = count === 0 ? 'block' : 'none';
        }
    }

    function reindexFields() {
        const cards = container.querySelectorAll('.cf-item-card');
        cards.forEach((card, index) => {
            const numBadge = card.querySelector('.cf-badge-num');
            if (numBadge) numBadge.textContent = '#' + (index + 1);

            const labelInput = card.querySelector('.cf-input-label');
            const typeInput = card.querySelector('.cf-input-type');
            const reqInput = card.querySelector('.cf-input-required');
            const optInput = card.querySelector('.cf-input-options');
            const plInput = card.querySelector('.cf-input-placeholder');

            if (labelInput) labelInput.name = `custom_fields[${index}][label]`;
            if (typeInput) typeInput.name = `custom_fields[${index}][type]`;
            if (reqInput) reqInput.name = `custom_fields[${index}][required]`;
            if (optInput) optInput.name = `custom_fields[${index}][options]`;
            if (plInput) plInput.name = `custom_fields[${index}][placeholder]`;
        });
        checkEmptyState();
    }

    function addField(data = null) {
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.cf-item-card');

        const labelInput = card.querySelector('.cf-input-label');
        const typeInput = card.querySelector('.cf-input-type');
        const reqInput = card.querySelector('.cf-input-required');
        const optInput = card.querySelector('.cf-input-options');
        const plInput = card.querySelector('.cf-input-placeholder');
        const optionsWrap = card.querySelector('.cf-options-wrap');
        const typeBadge = card.querySelector('.cf-field-type-badge');
        const btnDelete = card.querySelector('.cf-btn-delete');

        if (data) {
            if (labelInput && data.label) labelInput.value = data.label;
            if (typeInput && data.type) typeInput.value = data.type;
            if (reqInput) reqInput.checked = Boolean(data.required);
            if (optInput && data.options) {
                optInput.value = Array.isArray(data.options) ? data.options.join(', ') : data.options;
            }
            if (plInput && data.placeholder) plInput.value = data.placeholder;
        }

        function syncTypeUI() {
            const t = typeInput.value;
            typeBadge.textContent = t.toUpperCase();
            if (['select', 'radio', 'checkbox'].includes(t)) {
                optionsWrap.style.display = 'block';
            } else {
                optionsWrap.style.display = 'none';
            }
        }

        typeInput.addEventListener('change', syncTypeUI);
        syncTypeUI();

        btnDelete.addEventListener('click', function () {
            card.remove();
            reindexFields();
        });

        container.appendChild(card);
        reindexFields();
    }

    btnAdd?.addEventListener('click', function () {
        addField();
    });

    if (Array.isArray(initialFields) && initialFields.length > 0) {
        initialFields.forEach(f => addField(f));
    } else {
        checkEmptyState();
    }
});
</script>
