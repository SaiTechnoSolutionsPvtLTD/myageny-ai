<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HouseKeepingWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $workId = $this->route('house_keeping_work')?->id;

        return [
            'house_keeping_category_id' => ['required', 'exists:house_keeping_categories,id'],
            'work_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('house_keeping_works', 'work_name')
                    ->ignore($workId)
                    ->where(fn ($query) => $query
                        ->where('house_keeping_category_id', $this->input('house_keeping_category_id'))
                        ->whereNull('deleted_at')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
