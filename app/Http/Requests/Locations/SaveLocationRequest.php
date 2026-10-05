<?php

namespace App\Http\Requests\Locations;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLocationRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $nullable = [
            'party_id',
            'exterior_number',
            'interior_number',
            'neighborhood',
            'references',
        ];

        foreach ($nullable as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        foreach (['name', 'street', 'city', 'state', 'postal_code'] as $field) {
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
            'party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where('team_id', $this->team()->id),
            ],
            'name' => ['required', 'string', 'max:160'],
            'street' => ['required', 'string', 'max:160'],
            'exterior_number' => ['nullable', 'string', 'max:20'],
            'interior_number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:10'],
            'references' => ['nullable', 'string', 'max:500'],
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
