{{--
  Reusable product form partial.
  Variables:
    $product     – Product model (may be new/unsaved on create)
    $categories  – Collection of ProductCategory
    $existingValues – Collection keyed by attribute_id (only on edit)
--}}

@php
    $isEdit        = isset($product) && $product->exists;
    $existing      = $existingValues ?? collect();
    $selCategoryId = old('product_category_id', $isEdit ? $product->product_category_id : null);
    $selectedDepartmentIds = collect(old('department_ids', $isEdit ? $product->departments->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<div class="pm-form-grid">

    {{-- ═══ LEFT COLUMN ═══════════════════════════════════════════ --}}
    <div class="pm-form-col">

        {{-- Category --}}
        <div class="pm-field">
            <label class="pm-label pm-label--required" for="product_category_id">Category</label>
            <select id="product_category_id" name="product_category_id"
                    class="pm-select pm-select--lg @error('product_category_id') is-invalid @enderror"
                    required>
                <option value="">— Select category —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                            @selected($selCategoryId == $cat->id)>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('product_category_id')
                <span class="pm-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Package Name --}}
        <div class="pm-field">
            <label class="pm-label pm-label--required" for="package_name">Package Name (Product Name)</label>
            <input type="text" id="package_name" name="package_name"
                   class="pm-input @error('package_name') is-invalid @enderror"
                   value="{{ old('package_name', $isEdit ? $product->package_name : '') }}"
                   placeholder="e.g. Silver, Gold, Gold Plus"
                   required>
            @error('package_name')
                <span class="pm-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Status --}}
        <div class="pm-field">
            <label class="pm-label" for="status">Status</label>
            <select id="status" name="status"
                    class="pm-select @error('status') is-invalid @enderror">
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft'] as $val => $label)
                    <option value="{{ $val }}"
                            @selected(old('status', $isEdit ? $product->status : 'active') === $val)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        @isset($users)
        <div class="pm-field">
            <label class="pm-label" for="assigned_to">Visible To</label>
            <select id="assigned_to" name="assigned_to"
                    class="pm-select @error('assigned_to') is-invalid @enderror">
                <option value="">All mapped users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}"
                            @selected((string) old('assigned_to', $isEdit ? $product->assigned_to : '') === (string) $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('assigned_to')
                <span class="pm-error">{{ $message }}</span>
            @enderror
        </div>
        @endisset


        @isset($departments)
        <div class="pm-field">
            <label class="pm-label" for="department_ids">Departments</label>
            <select id="department_ids" name="department_ids[]"
                    class="pm-select pm-select--departments js-pm-department-select @error('department_ids') is-invalid @enderror @error('department_ids.*') is-invalid @enderror"
                    data-placeholder="Select departments"
                    multiple>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}"
                            @selected(in_array((string) $department->id, $selectedDepartmentIds, true))>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
            <span class="pm-help">Search and choose one or more departments from masters.</span>
            @error('department_ids')
                <span class="pm-error">{{ $message }}</span>
            @enderror
            @error('department_ids.*')
                <span class="pm-error">{{ $message }}</span>
            @enderror
        </div>
        @endisset

        {{-- Sort Order --}}
        {{--  <div class="pm-field pm-field--sm">
            <label class="pm-label" for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" min="0"
                   class="pm-input"
                   value="{{ old('sort_order', $isEdit ? $product->sort_order : 0) }}">
        </div>  --}}

        {{-- Description --}}
        <div class="pm-field">
            <label class="pm-label" for="description">Description</label>
            <textarea id="description" name="description" rows="4"
                      class="pm-textarea @error('description') is-invalid @enderror"
                      placeholder="Describe this package…">{{ old('description', $isEdit ? $product->description : '') }}</textarea>
        </div>

    </div>

    {{-- ═══ RIGHT COLUMN — PRICING ════════════════════════════════ --}}
    <div class="pm-form-col">

        <div class="pm-pricing-box">
            <h4 class="pm-pricing-box__title">Pricing</h4>

            {{-- Base Price --}}
            <div class="pm-field">
                <label class="pm-label pm-label--required" for="base_price">Base Price (₹)</label>
                <div class="pm-input-prefix">
                    <span class="pm-prefix">₹</span>
                    <input type="number" id="base_price" name="base_price" min="0" step="0.01"
                           class="pm-input pm-input--prefixed @error('base_price') is-invalid @enderror"
                           value="{{ old('base_price', $isEdit ? $product->base_price : '') }}"
                           placeholder="0.00" required>
                </div>
                @error('base_price')
                    <span class="pm-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Tax --}}
            <div class="pm-field">
                <label class="pm-label">Tax</label>
                <div class="pm-combo-row">
                    <select name="tax_type" id="tax_type" class="pm-select pm-select--type">
                        <option value="percentage" @selected(old('tax_type', $isEdit ? $product->tax_type : 'percentage') === 'percentage')>%</option>
                        <option value="fixed"      @selected(old('tax_type', $isEdit ? $product->tax_type : 'percentage') === 'fixed')>₹ Fixed</option>
                    </select>
                    <input type="number" id="tax_value" name="tax_value" min="0" step="0.01"
                           class="pm-input"
                           value="{{ old('tax_value', $isEdit ? $product->tax_value : 0) }}"
                           placeholder="0">
                </div>
            </div>

            {{-- Discount --}}
            <div class="pm-field">
                <label class="pm-label">Discount</label>
                <div class="pm-combo-row">
                    <select name="discount_type" id="discount_type" class="pm-select pm-select--type">
                        <option value="percentage" @selected(old('discount_type', $isEdit ? $product->discount_type : 'percentage') === 'percentage')>%</option>
                        <option value="fixed"      @selected(old('discount_type', $isEdit ? $product->discount_type : 'percentage') === 'fixed')>₹ Fixed</option>
                    </select>
                    <input type="number" id="discount_value" name="discount_value" min="0" step="0.01"
                           class="pm-input"
                           value="{{ old('discount_value', $isEdit ? $product->discount_value : 0) }}"
                           placeholder="0">
                </div>
            </div>

            {{-- Final Price Preview --}}
            <div class="pm-final-price-box">
                <span class="pm-final-price-label">Final Price</span>
                <span class="pm-final-price-value" id="finalPriceDisplay">
                    ₹{{ $isEdit ? number_format($product->final_price, 2) : '0.00' }}
                </span>
            </div>
        </div>

    </div>

</div>

{{-- ═══ DYNAMIC ATTRIBUTES SECTION ══════════════════════════════ --}}
<div class="pm-section">
    <div class="pm-section-head">
        <h4 class="pm-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Product Attributes
        </h4>
        <span class="pm-section-hint" id="attributeHint">
            @if($selCategoryId)Select attributes for this product.@else Select a category first.@endif
        </span>
        <button type="button" id="btnAddAttribute" class="pm-btn pm-btn--sm pm-btn--outline"
                @if(!$selCategoryId) disabled @endif>
            + Add Attribute
        </button>
    </div>

    <div id="attributeRowsContainer" class="pm-attr-rows">
        {{-- Existing / Old attribute rows will be rendered here --}}
        @if($isEdit)
            @foreach($product->attributeValues as $idx => $pav)
                @include('pages.products.partials.attribute-row', [
                    'rowIndex'    => $idx,
                    'selectedId'  => $pav->attribute_id,
                    'value'       => old("attributes.{$idx}.value", $pav->value),
                    'attribute'   => $pav->attribute,
                ])
            @endforeach
        @elseif(old('attributes'))
            @foreach(old('attributes') as $idx => $row)
                @include('pages.products.partials.attribute-row', [
                    'rowIndex'   => $idx,
                    'selectedId' => $row['attribute_id'] ?? null,
                    'value'      => $row['value'] ?? '',
                    'attribute'  => null,
                ])
            @endforeach
        @endif
    </div>

    <p class="pm-no-attrs" id="noAttributesMsg"
       @if($isEdit && $product->attributeValues->isNotEmpty()) style="display:none" @endif
       @if(old('attributes')) style="display:none" @endif>
        No attributes added yet.
    </p>
</div>

{{-- ─── Hidden JSON payload (consumed by product.js) ─────────── --}}
@if($selCategoryId)
@php
    $attributes = \App\Models\Attribute::where('product_category_id', $selCategoryId)
        ->active()
        ->orderBy('sort_order')
        ->get(['id','name','key','field_type','unit','placeholder','is_required']);
@endphp

<script>
window.PM_EXISTING_ATTRIBUTES = @json($attributes);
</script>
@else
<script>
window.PM_EXISTING_ATTRIBUTES = [];
</script>
@endif

<script>
window.PM_ROUTES = {
    attributesByCategory : "{{ url('products/attributes-by-category') }}",
    previewPrice         : "{{ route('products.preview-price') }}",
};
window.PM_CSRF = "{{ csrf_token() }}";
</script>

@push('scripts')
<script>
window.addEventListener('load', function () {
    if (!window.jQuery || !window.jQuery.fn.select2) {
        return;
    }

    const $select = window.jQuery('#department_ids');

    if (! $select.length) {
        return;
    }

    const currentValues = $select.val() || [];

    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }

    $select.select2({
        placeholder: $select.data('placeholder') || 'Select departments',
        width: '100%',
        closeOnSelect: false,
        dropdownAutoWidth: true
    });

    if (currentValues.length) {
        $select.val(currentValues).trigger('change');
    }

    $select.next('.select2-container').find('.select2-selection--multiple').addClass('pm-select2-selection');
});
</script>
@endpush
