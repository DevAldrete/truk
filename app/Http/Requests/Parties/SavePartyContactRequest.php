<?php

namespace App\Http\Requests\Parties;

use App\Models\Party;
use Illuminate\Foundation\Http\FormRequest;

class SavePartyContactRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['position', 'email', 'phone'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        if ($this->filled('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
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
            'name' => ['required', 'string', 'max:160'],
            'position' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * Get the party the contact belongs to.
     */
    public function party(): Party
    {
        /** @var Party $party */
        $party = $this->route('party');

        return $party;
    }
}
