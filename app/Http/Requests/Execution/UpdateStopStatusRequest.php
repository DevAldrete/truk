<?php

namespace App\Http\Requests\Execution;

use App\Enums\StopStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStopStatusRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('notes')) {
            $this->merge(['notes' => null]);
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
            'status' => ['required', Rule::enum(StopStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
