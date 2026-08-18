@extends('layouts.app')

@section('title', 'New Lead')

@push('styles')
    @include('pages.leads.form_style')
@endpush

@section('content')
<div class="lf-page">

    <div class="lf-topbar">
        <div>
            <div class="lf-title">New Lead</div>
            <div class="lf-crumb"><a href="{{ route('leads.index') }}">Leads</a> › Create</div>
        </div>
        <div class="lf-topbar-right">
            <button type="submit" form="leadForm" class="lf-btn lf-btn-primary">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Create Lead
            </button>
            <a href="{{ route('leads.index') }}" class="lf-btn lf-btn-outline">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="lf-body">
    <form method="POST" action="{{ route('leads.store') }}" id="leadForm" enctype="multipart/form-data">
        @csrf
        @include('pages.leads.form', ['lead' => null])
    </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Priority selector
document.querySelectorAll('.lf-pri-opt').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.lf-pri-opt').forEach(o => o.className = 'lf-pri-opt');
        el.classList.add('selected-' + el.dataset.val);
        el.querySelector('input').checked = true;
    });
});

// Status selector
document.querySelectorAll('.lf-status-opt').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.lf-status-opt').forEach(o => {
            o.className = 'lf-status-opt';
        });
        el.classList.add('sel-' + el.dataset.val);
        el.querySelector('input').checked = true;
    });
});

function toggleCustomFieldsByBranch() {
    const branchSelect = document.querySelector('[name="branch_id"]');
    const selectedOption = branchSelect?.options[branchSelect.selectedIndex];
    const branchValue = branchSelect?.value || '';
    const companyValue = selectedOption?.dataset.companyId || '';

    document.querySelectorAll('[data-custom-field]').forEach(field => {
        const fieldBranchId = field.dataset.branchId || '';
        const fieldCompanyId = field.dataset.companyId || '';
        
        const matchCompany = !fieldCompanyId || (companyValue && String(fieldCompanyId) === String(companyValue));
        const matchBranch = !fieldBranchId || (branchValue && String(fieldBranchId) === String(branchValue));
        
        const shouldShow = matchCompany && matchBranch;

        field.style.display = shouldShow ? '' : 'none';

        field.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.type === 'hidden') {
                return;
            }

            if (shouldShow) {
                if (input.dataset.wasRequired === 'true') {
                    input.required = true;
                }
                input.disabled = false;
            } else {
                input.dataset.wasRequired = input.required ? 'true' : 'false';
                input.required = false;
                input.disabled = true;
            }
        });
    });
}

function recalculateCustomFormulaFields() {
    const values = {};

    document.querySelectorAll('[data-field-name]').forEach(input => {
        if (input.disabled || input.type === 'hidden') {
            return;
        }

        const fieldName = input.dataset.fieldName;
        let value = input.value;

        if (input.type === 'radio') {
            if (!input.checked) {
                return;
            }
        }

        values[fieldName] = isNaN(Number(value)) ? 0 : Number(value);
    });

    document.querySelectorAll('[data-calculation-formula]').forEach(calcInput => {
        const formula = calcInput.dataset.calculationFormula || '';

        if (!formula) {
            return;
        }

        let expression = formula;
        Object.entries(values).forEach(([fieldName, value]) => {
            expression = expression.replaceAll(fieldName, value);
        });

        expression = expression.replace(/[^0-9+\-*/().\s]/g, '');

        try {
            const result = Function('"use strict"; return (' + expression + ')')();
            calcInput.value = Number.isFinite(result) ? result : '';
        } catch (error) {
            calcInput.value = '';
        }
    });
}

document.querySelector('[name="branch_id"]')?.addEventListener('change', toggleCustomFieldsByBranch);
document.querySelectorAll('[data-field-name]').forEach(input => {
    input.addEventListener('input', recalculateCustomFormulaFields);
    input.addEventListener('change', recalculateCustomFormulaFields);
});

toggleCustomFieldsByBranch();
recalculateCustomFormulaFields();
</script>
@endpush
