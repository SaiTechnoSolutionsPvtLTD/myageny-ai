<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetCategoryId = $this->route('asset_category')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('asset_categories', 'name')
                    ->ignore($assetCategoryId)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
