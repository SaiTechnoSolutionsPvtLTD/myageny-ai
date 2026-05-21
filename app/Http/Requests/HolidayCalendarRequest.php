<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $holidayId = $this->route('holiday_calendar')?->id;
        $companyId = auth()->user()?->company_id;

        return [
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('holiday_calendars', 'holiday_date')
                    ->ignore($holidayId)
                    ->where(function ($query) use ($companyId) {
                        $query->whereNull('deleted_at')
                            ->where(function ($companyQuery) use ($companyId) {
                                if ($companyId === null) {
                                    $companyQuery->whereNull('company_id');

                                    return;
                                }

                                $companyQuery->whereNull('company_id')
                                    ->orWhere('company_id', $companyId);
                            });
                    }),
            ],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
