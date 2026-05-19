<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDynamicFormRequest;
use App\Http\Requests\UpdateDynamicFormRequest;
use App\Models\DynamicForm;
use App\Models\DynamicFormField;
use App\Models\DynamicFormSubmission;
use App\Models\DynamicFormSubmissionValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DynamicFormController extends Controller
{
    private const FILE_DIRECTORY = 'dynamic_forms';

    public function index(Request $request): View
    {
        $forms = DynamicForm::query()
            ->withCount(['fields', 'submissions'])
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.hrms.dynamic_forms.index', compact('forms'));
    }

    public function create(): View
    {
        return view('pages.hrms.dynamic_forms.create');
    }

    public function store(StoreDynamicFormRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $form = DB::transaction(function () use ($validated) {
            $form = DynamicForm::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'success_message' => $validated['success_message'] ?? 'Your response has been submitted successfully.',
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'allow_multiple_submissions' => (bool) ($validated['allow_multiple_submissions'] ?? false),
                'slug' => $this->makeUniqueSlug($validated['title']),
                'public_token' => Str::random(32),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->syncFields($form, $validated['fields']);

            return $form;
        });

        return redirect()
            ->route('dynamic-forms.show', $form)
            ->with('success', "Form <strong>{$form->title}</strong> created successfully.");
    }

    public function show(DynamicForm $dynamicForm): View
    {
        $dynamicForm->load(['fields', 'creator', 'updater']);

        return view('pages.hrms.dynamic_forms.show', [
            'form' => $dynamicForm,
            'shareUrl' => route('dynamic-forms.public.show', $dynamicForm->public_token),
            'responseCount' => $dynamicForm->submissions()->count(),
        ]);
    }

    public function responses(Request $request, DynamicForm $dynamicForm): View
    {
        $dynamicForm->load('fields');

        $submissions = $this->filteredSubmissionsQuery($request, $dynamicForm)
            ->with(['values.field'])
            ->paginate(15)
            ->withQueryString();

        return view('pages.hrms.dynamic_forms.responses', [
            'form' => $dynamicForm,
            'submissions' => $submissions,
            'responseRows' => $this->buildResponseRows($submissions->items()),
        ]);
    }

    public function edit(DynamicForm $dynamicForm): View
    {
        $dynamicForm->load('fields');

        return view('pages.hrms.dynamic_forms.edit', [
            'form' => $dynamicForm,
        ]);
    }

    public function update(UpdateDynamicFormRequest $request, DynamicForm $dynamicForm): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($dynamicForm, $validated) {
            $dynamicForm->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'success_message' => $validated['success_message'] ?? 'Your response has been submitted successfully.',
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'allow_multiple_submissions' => (bool) ($validated['allow_multiple_submissions'] ?? false),
                'slug' => $this->makeUniqueSlug($validated['title'], $dynamicForm->id),
                'updated_by' => auth()->id(),
            ]);

            $this->syncFields($dynamicForm, $validated['fields']);
        });

        return redirect()
            ->route('dynamic-forms.show', $dynamicForm)
            ->with('success', "Form <strong>{$dynamicForm->title}</strong> updated successfully.");
    }

    public function destroy(DynamicForm $dynamicForm): RedirectResponse
    {
        DB::transaction(function () use ($dynamicForm) {
            $dynamicForm->load(['submissions.values']);

            foreach ($dynamicForm->submissions as $submission) {
                foreach ($submission->values as $value) {
                    $this->deleteStoredFile($value->file_path);
                }
            }

            $dynamicForm->delete();
        });

        return redirect()
            ->route('dynamic-forms.index')
            ->with('success', 'Form deleted successfully.');
    }

    public function publicShow(string $token): View
    {
        $form = DynamicForm::query()
            ->where('public_token', $token)
            ->where('is_active', true)
            ->with('fields')
            ->firstOrFail();

        return view('pages.hrms.dynamic_forms.public_form', compact('form'));
    }

    public function publicSubmit(Request $request, string $token): RedirectResponse
    {
        $form = DynamicForm::query()
            ->where('public_token', $token)
            ->where('is_active', true)
            ->with('fields')
            ->firstOrFail();

        $rules = [];
        foreach ($form->fields as $field) {
            $fieldName = 'field_' . $field->id;
            $ruleSet = $this->validationRulesForField($field);
            $rules[$fieldName] = $ruleSet;

            if ($field->field_type === 'checkbox') {
                $rules[$fieldName . '.*'] = ['string', 'max:255'];
            }
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($form, $request, $validated) {
            $submission = DynamicFormSubmission::create([
                'dynamic_form_id' => $form->id,
                'submitted_by_ip' => $request->ip(),
                'submitted_by_user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);

            foreach ($form->fields as $field) {
                $fieldName = 'field_' . $field->id;
                $value = $validated[$fieldName] ?? null;
                $filePath = null;

                if ($field->field_type === 'file' && $request->hasFile($fieldName)) {
                    $filePath = $request->file($fieldName)->store(self::FILE_DIRECTORY . '/' . $form->id, 'public');
                    $value = $request->file($fieldName)->getClientOriginalName();
                } elseif (is_array($value)) {
                    $value = implode(', ', $value);
                }

                DynamicFormSubmissionValue::create([
                    'dynamic_form_submission_id' => $submission->id,
                    'dynamic_form_field_id' => $field->id,
                    'value' => $value !== null ? (string) $value : null,
                    'file_path' => $filePath,
                ]);
            }
        });

        return redirect()
            ->route('dynamic-forms.public.show', $form->public_token)
            ->with('success', $form->success_message ?: 'Your response has been submitted successfully.');
    }

    public function export(Request $request, DynamicForm $dynamicForm): Response
    {
        $dynamicForm->load('fields');
        $submissions = $this->filteredSubmissionsQuery($request, $dynamicForm)
            ->with(['values.field'])
            ->get();
        $rows = $this->buildResponseRows($submissions);
        $filename = Str::slug($dynamicForm->title) . '-responses-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($dynamicForm, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_merge(['Submission ID', 'Submitted At'], $dynamicForm->fields->pluck('label')->all()));

            foreach ($rows as $row) {
                $values = [$row['submission_id'], $row['submitted_at']];
                foreach ($dynamicForm->fields as $field) {
                    $values[] = $row['values'][$field->id] ?? '';
                }

                fputcsv($handle, $values);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    private function syncFields(DynamicForm $form, array $fields): void
    {
        $existingIds = [];

        collect(array_values($fields))
            ->each(function (array $field, int $index) use ($form, &$existingIds) {
                $options = collect(preg_split('/\r\n|\r|\n/', (string) ($field['options_text'] ?? '')))
                    ->map(fn ($item) => trim($item))
                    ->filter()
                    ->values()
                    ->all();

                $attributes = [
                    'label' => trim((string) $field['label']),
                    'field_key' => DynamicFormField::makeFieldKey((string) $field['label'], $index + 1),
                    'field_type' => $field['field_type'],
                    'placeholder' => trim((string) ($field['placeholder'] ?? '')) ?: null,
                    'help_text' => trim((string) ($field['help_text'] ?? '')) ?: null,
                    'options' => in_array($field['field_type'], ['select', 'radio', 'checkbox'], true) ? $options : null,
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'sort_order' => $index + 1,
                ];

                $model = $form->fields()->updateOrCreate(
                    ['id' => $field['id'] ?? null],
                    $attributes
                );

                $existingIds[] = $model->id;
            });

        $form->fields()->whereNotIn('id', $existingIds)->delete();
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base !== '' ? $base : 'form';
        $counter = 1;

        while (
            DynamicForm::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function validationRulesForField(DynamicFormField $field): array
    {
        $rules = $field->is_required ? ['required'] : ['nullable'];

        return match ($field->field_type) {
            'number' => array_merge($rules, ['numeric']),
            'textarea' => array_merge($rules, ['string', 'max:5000']),
            'select', 'radio' => array_merge($rules, ['string', 'max:255']),
            'checkbox' => array_merge($rules, ['array']),
            'file' => array_merge($rules, ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xlsx,xls', 'max:5120']),
            default => array_merge($rules, ['string', 'max:255']),
        };
    }

    private function filteredSubmissionsQuery(Request $request, DynamicForm $form)
    {
        return DynamicFormSubmission::query()
            ->where('dynamic_form_id', $form->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    if (is_numeric($search)) {
                        $subQuery->where('id', (int) $search);
                    }

                    $subQuery->orWhereHas('values', function ($valueQuery) use ($search) {
                        $valueQuery->where('value', 'like', '%' . $search . '%');
                    });
                });
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date_to))
            ->when($request->filled('field_id') && $request->filled('field_value'), function ($query) use ($request) {
                $fieldId = (int) $request->field_id;
                $fieldValue = trim((string) $request->field_value);

                $query->whereHas('values', function ($valueQuery) use ($fieldId, $fieldValue) {
                    $valueQuery->where('dynamic_form_field_id', $fieldId)
                        ->where('value', 'like', '%' . $fieldValue . '%');
                });
            })
            ->latest();
    }

    private function buildResponseRows(iterable $submissions): array
    {
        return collect($submissions)->map(function (DynamicFormSubmission $submission) {
            $values = [];

            foreach ($submission->values as $value) {
                $displayValue = $value->file_path
                    ? asset('storage/' . $value->file_path)
                    : (string) ($value->value ?? '');

                $values[$value->dynamic_form_field_id] = $displayValue;
            }

            return [
                'submission_id' => $submission->id,
                'submitted_at' => optional($submission->created_at)->format('d M Y h:i A'),
                'values' => $values,
            ];
        })->all();
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
