<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierForm;
use App\Models\CourierFormField;
use App\Models\CourierValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourierController extends Controller
{
    private const FILE_DIRECTORY = 'couriers';

    public function index(Request $request): View
    {
        $form = CourierForm::query()
            ->with('fields')
            ->withCount('couriers')
            ->first();

        $displayFields = collect();
        if ($form) {
            $displayFields = $form->fields->where('show_in_list', true)->values();
            if ($displayFields->isEmpty()) {
                $displayFields = $form->fields->take(5)->values();
            }
        }

        $couriers = Courier::query()
            ->with(['form', 'values', 'creator'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('courier_code', 'like', '%' . $search . '%')
                        ->orWhere('form_title', 'like', '%' . $search . '%')
                        ->orWhereHas('values', function ($valQuery) use ($search) {
                            $valQuery->where('value', 'like', '%' . $search . '%')
                                ->orWhere('file_name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total_couriers' => Courier::count(),
            'today_couriers' => Courier::whereDate('created_at', today())->count(),
            'configured_fields' => $form ? $form->fields->count() : 0,
        ];

        return view('pages.hrms.couriers.index', [
            'couriers' => $couriers,
            'form' => $form,
            'displayFields' => $displayFields,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $existingForm = CourierForm::query()->with('fields')->first();
        $activeForm = ($existingForm && $existingForm->is_active) ? $existingForm : null;

        return view('pages.hrms.couriers.create', [
            'form' => $activeForm,
            'existingForm' => $existingForm,
            'nextCode' => $this->generateNextCourierCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $formQuery = CourierForm::query()
            ->where('is_active', true)
            ->with('fields');

        $form = $request->filled('courier_form_id')
            ? $formQuery->findOrFail((int) $request->input('courier_form_id'))
            : $formQuery->firstOrFail();

        [$rules, $attributes] = $this->buildFieldValidation($form->fields, $request);
        $validated = $request->validate($rules, [], $attributes);
        $fieldInputs = $validated['fields'] ?? [];

        $courier = DB::transaction(function () use ($form, $request, $fieldInputs) {
            $courier = Courier::create([
                'courier_code' => $this->generateNextCourierCode(),
                'courier_form_id' => $form->id,
                'form_title' => $form->title,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($form->fields as $field) {
                $this->persistFieldValue($courier, $field, $request, $fieldInputs);
            }

            return $courier;
        });

        return redirect()
            ->route('hrms.couriers.show', $courier)
            ->with('success', 'Courier <strong>' . e($courier->courier_code) . '</strong> created successfully.');
    }

    public function show(Courier $courier): View
    {
        $courier->load(['form.fields', 'values.field', 'creator', 'updater']);

        return view('pages.hrms.couriers.show', compact('courier'));
    }

    public function edit(Courier $courier): View
    {
        $courier->load(['form.fields', 'values']);

        $existingValuesByFieldId = $courier->values
            ->whereNotNull('courier_form_field_id')
            ->keyBy('courier_form_field_id');

        return view('pages.hrms.couriers.edit', [
            'courier' => $courier,
            'form' => $courier->form,
            'existingValuesByFieldId' => $existingValuesByFieldId,
        ]);
    }

    public function update(Request $request, Courier $courier): RedirectResponse
    {
        $courier->load(['form.fields', 'values']);

        if ($courier->form && $courier->form->fields->isNotEmpty()) {
            $existingByFieldId = $courier->values
                ->whereNotNull('courier_form_field_id')
                ->keyBy('courier_form_field_id');

            [$rules, $attributes] = $this->buildFieldValidation($courier->form->fields, $request, $existingByFieldId);
            $validated = $request->validate($rules, [], $attributes);
            $fieldInputs = $validated['fields'] ?? [];
            $removeFiles = $request->input('remove_files', []);

            DB::transaction(function () use ($courier, $request, $fieldInputs, $existingByFieldId, $removeFiles) {
                $courier->update([
                    'form_title' => $courier->form->title,
                    'updated_by' => auth()->id(),
                ]);

                $keptValueIds = [];

                foreach ($courier->form->fields as $field) {
                    $existingValue = $existingByFieldId->get($field->id);
                    $removeExistingFile = ! empty($removeFiles[$field->id]);

                    $savedValue = $this->persistFieldValue(
                        $courier,
                        $field,
                        $request,
                        $fieldInputs,
                        $existingValue,
                        $removeExistingFile
                    );

                    $keptValueIds[] = $savedValue->id;
                }

                // Clean up values for fields that no longer exist on the form
                $removedValues = $courier->values()->whereNotIn('id', $keptValueIds)->get();
                foreach ($removedValues as $removedValue) {
                    $this->deleteStoredFile($removedValue->file_path);
                    $removedValue->delete();
                }
            });
        } else {
            // Fallback if the original CourierForm was deleted: allow editing the recorded CourierValues directly.
            $rules = [];
            $attributes = [];
            foreach ($courier->values as $val) {
                $key = "snapshot_values.{$val->id}";
                $attributes[$key] = $val->field_label;
                if ($val->field_type === 'file') {
                    $rules[$key] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:10240'];
                } elseif ($val->field_type === 'date') {
                    $rules[$key] = ['nullable', 'date'];
                } elseif ($val->field_type === 'number') {
                    $rules[$key] = ['nullable', 'numeric'];
                } else {
                    $rules[$key] = ['nullable', 'string', 'max:5000'];
                }
            }

            $validated = $request->validate($rules, [], $attributes);
            $snapshotInputs = $validated['snapshot_values'] ?? [];
            $removeFiles = $request->input('remove_snapshot_files', []);

            DB::transaction(function () use ($courier, $request, $snapshotInputs, $removeFiles) {
                $courier->update(['updated_by' => auth()->id()]);

                foreach ($courier->values as $val) {
                    if ($val->field_type === 'file') {
                        if ($request->hasFile("snapshot_values.{$val->id}")) {
                            $this->deleteStoredFile($val->file_path);
                            $file = $request->file("snapshot_values.{$val->id}");
                            $path = $file->store(self::FILE_DIRECTORY . '/' . $courier->id, 'public');
                            $val->update([
                                'file_path' => $path,
                                'file_name' => $file->getClientOriginalName(),
                                'value' => $file->getClientOriginalName(),
                            ]);
                        } elseif (! empty($removeFiles[$val->id])) {
                            $this->deleteStoredFile($val->file_path);
                            $val->update([
                                'file_path' => null,
                                'file_name' => null,
                                'value' => null,
                            ]);
                        }
                    } else {
                        $raw = $snapshotInputs[$val->id] ?? null;
                        $val->update([
                            'value' => $raw !== null ? (string) $raw : null,
                        ]);
                    }
                }
            });
        }

        return redirect()
            ->route('hrms.couriers.show', $courier)
            ->with('success', 'Courier <strong>' . e($courier->courier_code) . '</strong> updated successfully.');
    }

    public function destroy(Courier $courier): RedirectResponse
    {
        $code = $courier->courier_code;

        DB::transaction(function () use ($courier) {
            $courier->load('values');
            foreach ($courier->values as $value) {
                $this->deleteStoredFile($value->file_path);
            }
            $courier->values()->delete();
            $courier->delete();
        });

        return redirect()
            ->route('hrms.couriers.index')
            ->with('success', 'Courier <strong>' . e($code) . '</strong> deleted successfully.');
    }

    public function downloadFile(Courier $courier, CourierValue $value): StreamedResponse
    {
        abort_unless($value->courier_id === $courier->id, 404);
        abort_unless($value->file_path && Storage::disk('public')->exists($value->file_path), 404);

        return Storage::disk('public')->download(
            $value->file_path,
            $value->file_name ?: basename($value->file_path)
        );
    }

    private function buildFieldValidation($fields, Request $request, $existingByFieldId = null): array
    {
        $rules = [];
        $attributes = [];
        $removeFiles = $request->input('remove_files', []);

        foreach ($fields as $field) {
            $inputKey = "fields.{$field->id}";
            $attributes[$inputKey] = $field->label;

            $isRequired = (bool) $field->is_required;

            if ($field->field_type === 'file') {
                $existingValue = $existingByFieldId?->get($field->id);
                $hasExistingFile = $existingValue && ! empty($existingValue->file_path) && empty($removeFiles[$field->id]);
                $baseRule = ($isRequired && ! $hasExistingFile) ? 'required' : 'nullable';
                $rules[$inputKey] = [$baseRule, 'file', 'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:10240'];
                continue;
            }

            $baseRule = $isRequired ? 'required' : 'nullable';
            $options = is_array($field->options) ? $field->options : [];

            switch ($field->field_type) {
                case 'number':
                    $rules[$inputKey] = [$baseRule, 'numeric'];
                    break;
                case 'date':
                    $rules[$inputKey] = [$baseRule, 'date'];
                    break;
                case 'textarea':
                    $rules[$inputKey] = [$baseRule, 'string', 'max:5000'];
                    break;
                case 'select':
                case 'radio':
                    $typeRules = [$baseRule, 'string', 'max:255'];
                    if (! empty($options)) {
                        $typeRules[] = Rule::in($options);
                    }
                    $rules[$inputKey] = $typeRules;
                    break;
                case 'checkbox':
                    if (! empty($options)) {
                        $rules[$inputKey] = [$baseRule, 'array'];
                        $rules["{$inputKey}.*"] = ['string', 'max:255', Rule::in($options)];
                        $attributes["{$inputKey}.*"] = $field->label;
                    } else {
                        $rules[$inputKey] = $isRequired ? ['accepted'] : ['nullable'];
                    }
                    break;
                default:
                    $rules[$inputKey] = [$baseRule, 'string', 'max:255'];
                    break;
            }
        }

        return [$rules, $attributes];
    }

    private function persistFieldValue(
        Courier $courier,
        CourierFormField $field,
        Request $request,
        array $fieldInputs,
        ?CourierValue $existingValue = null,
        bool $removeExistingFile = false
    ): CourierValue {
        $valueText = null;
        $filePath = $existingValue?->file_path;
        $fileName = $existingValue?->file_name;

        if ($field->field_type === 'file') {
            if ($request->hasFile("fields.{$field->id}")) {
                $this->deleteStoredFile($existingValue?->file_path);
                $uploadedFile = $request->file("fields.{$field->id}");
                $filePath = $uploadedFile->store(self::FILE_DIRECTORY . '/' . $courier->id, 'public');
                $fileName = $uploadedFile->getClientOriginalName();
                $valueText = $fileName;
            } elseif ($removeExistingFile) {
                $this->deleteStoredFile($existingValue?->file_path);
                $filePath = null;
                $fileName = null;
                $valueText = null;
            } else {
                $valueText = $fileName ?: $existingValue?->value;
            }
        } elseif ($field->field_type === 'checkbox') {
            $raw = $fieldInputs[$field->id] ?? null;
            if (is_array($field->options) && count($field->options) > 0) {
                $valueText = is_array($raw) && count($raw) > 0 ? implode(', ', $raw) : null;
            } else {
                $valueText = ! empty($raw) ? 'Yes' : 'No';
            }
            $filePath = null;
            $fileName = null;
        } else {
            $raw = $fieldInputs[$field->id] ?? null;
            if (is_array($raw)) {
                $valueText = implode(', ', $raw);
            } else {
                $trimmed = $raw !== null ? trim((string) $raw) : '';
                $valueText = $trimmed !== '' ? $trimmed : null;
            }
            $filePath = null;
            $fileName = null;
        }

        $payload = [
            'courier_form_field_id' => $field->id,
            'field_label' => $field->label,
            'field_key' => $field->field_key,
            'field_type' => $field->field_type,
            'value' => $valueText,
            'file_path' => $filePath,
            'file_name' => $fileName,
        ];

        if ($existingValue) {
            $existingValue->update($payload);

            return $existingValue;
        }

        return $courier->values()->create($payload);
    }

    private function generateNextCourierCode(): string
    {
        $codes = Courier::withoutGlobalScopes()
            ->where('courier_code', 'like', 'CUR-%')
            ->pluck('courier_code');

        $maxNum = 0;
        foreach ($codes as $code) {
            if (preg_match('/^CUR-(\d+)$/', (string) $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        return 'CUR-' . str_pad((string) ($maxNum + 1), 4, '0', STR_PAD_LEFT);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
