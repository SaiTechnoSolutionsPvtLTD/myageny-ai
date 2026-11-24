<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRMS\CourierFormRequest;
use App\Models\CourierForm;
use App\Models\CourierFormField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CourierFormController extends Controller
{
    public function index(): View
    {
        $form = CourierForm::query()
            ->with(['fields', 'creator', 'updater'])
            ->withCount('couriers')
            ->first();

        if (! $form) {
            return view('pages.hrms.couriers.forms.create');
        }

        $recentCouriers = $form->couriers()
            ->with(['values', 'creator'])
            ->take(5)
            ->get();

        return view('pages.hrms.couriers.forms.show', [
            'form' => $form,
            'recentCouriers' => $recentCouriers,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $existingForm = CourierForm::query()->first();

        if ($existingForm) {
            return redirect()
                ->route('hrms.couriers.forms.edit', $existingForm)
                ->with('info', 'Dynamic Courier form is already created. You can update its fields below.');
        }

        return view('pages.hrms.couriers.forms.create');
    }

    public function store(CourierFormRequest $request): RedirectResponse
    {
        $existingForm = CourierForm::query()->first();

        if ($existingForm) {
            return redirect()
                ->route('hrms.couriers.forms.edit', $existingForm)
                ->with('error', 'Only one dynamic Courier form is allowed. Please edit the existing form.');
        }

        $validated = $request->validated();

        $form = DB::transaction(function () use ($validated) {
            $form = CourierForm::create([
                'title' => trim((string) $validated['title']),
                'slug' => $this->makeUniqueSlug($validated['title']),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->syncFields($form, $validated['fields']);

            return $form;
        });

        return redirect()
            ->route('hrms.couriers.forms.index')
            ->with('success', 'Courier form <strong>' . e($form->title) . '</strong> created successfully.');
    }

    public function show(CourierForm $courierForm): View
    {
        $courierForm->load(['fields', 'creator', 'updater'])
            ->loadCount('couriers');

        $recentCouriers = $courierForm->couriers()
            ->with(['values', 'creator'])
            ->take(5)
            ->get();

        return view('pages.hrms.couriers.forms.show', [
            'form' => $courierForm,
            'recentCouriers' => $recentCouriers,
        ]);
    }

    public function edit(CourierForm $courierForm): View
    {
        $courierForm->load('fields');

        return view('pages.hrms.couriers.forms.edit', [
            'form' => $courierForm,
        ]);
    }

    public function update(CourierFormRequest $request, CourierForm $courierForm): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($courierForm, $validated) {
            $courierForm->update([
                'title' => trim((string) $validated['title']),
                'slug' => $this->makeUniqueSlug($validated['title'], $courierForm->id),
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'updated_by' => auth()->id(),
            ]);

            $this->syncFields($courierForm, $validated['fields']);
        });

        return redirect()
            ->route('hrms.couriers.forms.index')
            ->with('success', 'Courier form <strong>' . e($courierForm->title) . '</strong> updated successfully.');
    }

    public function destroy(CourierForm $courierForm): RedirectResponse
    {
        $title = $courierForm->title;

        DB::transaction(function () use ($courierForm) {
            $courierForm->fields()->delete();
            $courierForm->delete();
        });

        return redirect()
            ->route('hrms.couriers.forms.index')
            ->with('success', 'Courier form <strong>' . e($title) . '</strong> deleted successfully.');
    }

    public function fields(CourierForm $courierForm): JsonResponse
    {
        $courierForm->load('fields');

        return response()->json([
            'success' => true,
            'form' => [
                'id' => $courierForm->id,
                'title' => $courierForm->title,
                'description' => $courierForm->description,
            ],
            'fields' => $courierForm->fields->map(fn (CourierFormField $field) => [
                'id' => $field->id,
                'label' => $field->label,
                'field_key' => $field->field_key,
                'field_type' => $field->field_type,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'options' => $field->options ?? [],
                'is_required' => $field->is_required,
                'show_in_list' => $field->show_in_list,
            ])->values(),
        ]);
    }

    private function syncFields(CourierForm $form, array $fields): void
    {
        // Temporarily prefix existing field keys so reordering/renaming never hits the unique constraint.
        foreach ($form->fields()->get() as $existingField) {
            $existingField->update([
                'field_key' => '__tmp_' . $existingField->id . '_' . Str::random(6),
            ]);
        }

        $keptIds = [];
        $usedKeys = [];

        collect(array_values($fields))
            ->each(function (array $field, int $index) use ($form, &$keptIds, &$usedKeys) {
                $label = trim((string) $field['label']);
                $candidateKey = CourierFormField::makeFieldKey($label, $index + 1);
                $suffix = 1;
                while (in_array($candidateKey, $usedKeys, true)) {
                    $candidateKey = CourierFormField::makeFieldKey($label, $index + 1 + $suffix);
                    $suffix++;
                }
                $usedKeys[] = $candidateKey;

                $options = in_array($field['field_type'], CourierFormField::OPTION_FIELD_TYPES, true)
                    ? CourierFormRequest::parseOptions($field['options_text'] ?? '')
                    : null;

                $attributes = [
                    'label' => $label,
                    'field_key' => $candidateKey,
                    'field_type' => $field['field_type'],
                    'placeholder' => trim((string) ($field['placeholder'] ?? '')) ?: null,
                    'help_text' => trim((string) ($field['help_text'] ?? '')) ?: null,
                    'options' => $options ?: null,
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'show_in_list' => (bool) ($field['show_in_list'] ?? false),
                    'sort_order' => $index + 1,
                ];

                $fieldId = ! empty($field['id']) ? (int) $field['id'] : null;
                $existing = $fieldId ? $form->fields()->where('id', $fieldId)->first() : null;

                if ($existing) {
                    $existing->update($attributes);
                    $keptIds[] = $existing->id;
                } else {
                    $created = $form->fields()->create($attributes);
                    $keptIds[] = $created->id;
                }
            });

        $form->fields()->whereNotIn('id', $keptIds)->delete();
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'courier-form';
        $slug = $base;
        $counter = 1;

        while (
            CourierForm::withoutGlobalScopes()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
