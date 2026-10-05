<?php

namespace App\Http\Requests\Execution;

use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentStatusRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('resolution')) {
            $this->merge(['resolution' => null]);
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
            'status' => ['required', Rule::enum(IncidentStatus::class)],
            'resolution' => [
                'nullable',
                'string',
                'max:4000',
                Rule::requiredIf(fn (): bool => in_array($this->input('status'), [IncidentStatus::Resolved->value, IncidentStatus::Dismissed->value], true)),
            ],
        ];
    }
}
