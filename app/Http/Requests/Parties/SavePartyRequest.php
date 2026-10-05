<?php

namespace App\Http\Requests\Parties;

use App\Enums\PartyType;
use App\Models\Party;
use App\Models\Team;
use App\Rules\Rfc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePartyRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['legal_name', 'rfc', 'email', 'phone'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        if ($this->filled('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        if ($this->filled('rfc')) {
            $this->merge(['rfc' => strtoupper(trim((string) $this->input('rfc')))]);
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
            'type' => ['required', Rule::enum(PartyType::class)],
            'name' => ['required', 'string', 'max:160'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'rfc' => [
                'nullable',
                'string',
                'max:13',
                new Rfc,
                Rule::unique('parties', 'rfc')
                    ->where('team_id', $this->team()->id)
                    ->withoutTrashed()
                    ->ignore($this->party()?->id),
            ],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
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

    /**
     * Get the party being updated, if any.
     */
    protected function party(): ?Party
    {
        /** @var Party|null $party */
        $party = $this->route('party');

        return $party;
    }
}
