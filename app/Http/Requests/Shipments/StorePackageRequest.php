<?php

namespace App\Http\Requests\Shipments;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('weight_kg')) {
            $this->merge([
                'weight_grams' => (int) round((float) $this->input('weight_kg') * 1000),
            ]);
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
            'count' => ['required', 'integer', 'min:1', 'max:1000'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the team the request operates on.
     */
    protected function team(): Team
    {
        /** @var Team $team */
        $team = $this->route('current_team');

        return $team;
    }
}
