<?php

namespace App\Http\Requests\Trips;

use App\Enums\TripStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTripRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['planned_start_at', 'planned_end_at', 'timezone', 'notes', 'capacity_override_reason', 'compliance_override_reason'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TripStatus::class)],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
            'timezone' => ['nullable', 'string', Rule::in(timezone_identifiers_list())],
            'notes' => ['nullable', 'string', 'max:2000'],
            'capacity_override_reason' => ['nullable', 'string', 'max:500'],
            'compliance_override_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
