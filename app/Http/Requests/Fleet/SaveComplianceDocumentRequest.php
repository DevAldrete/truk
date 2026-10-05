<?php

namespace App\Http\Requests\Fleet;

use App\Enums\ComplianceDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveComplianceDocumentRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['number', 'issued_at', 'expires_at', 'notes'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        foreach (['number', 'notes'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
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
            'type' => ['required', Rule::enum(ComplianceDocumentType::class)],
            'number' => ['nullable', 'string', 'max:60'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
