<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadFormFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'company_id'           => $this->company_id,
            'company_name'         => $this->company?->company_name,
            'label'                => $this->label,
            'field_name'           => $this->field_name,
            'field_type'           => $this->field_type,
            'placeholder'          => $this->placeholder,
            'default_value'        => $this->default_value,
            'is_required'          => $this->is_required,
            'is_active'            => $this->is_active,
            'show_on_lead_create'  => $this->show_on_lead_create,
            'sort_order'           => $this->sort_order,
            'is_calculation'       => $this->is_calculation,
            'calculation_formula'  => $this->calculation_formula,
            'calculation_label'    => $this->calculation_label,
            'options'              => $this->options,          // array of {label, value}
            'validation_rules'     => $this->validation_rules,
            'branch_id'            => $this->branch_id,
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),
        ];
    }
}