<?php

namespace App\Http\Requests\Trips;

use Illuminate\Foundation\Http\FormRequest;

class DispatchTripRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['capacity_override_reason', 'compliance_override_reason'] as $field) {
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
            'capacity_override_reason' => ['nullable', 'string', 'max:500'],
            'compliance_override_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
