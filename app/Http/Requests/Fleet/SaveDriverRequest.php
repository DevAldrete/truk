<?php

namespace App\Http\Requests\Fleet;

use App\Models\Driver;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDriverRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['carrier_party_id', 'license_number', 'license_expires_at'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        foreach (['name', 'phone'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }

        if ($this->filled('license_number')) {
            $this->merge(['license_number' => strtoupper(trim((string) $this->input('license_number')))]);
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
            'carrier_party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where('team_id', $this->team()->id),
            ],
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'license_number' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('drivers', 'license_number')
                    ->where('team_id', $this->team()->id)
                    ->withoutTrashed()
                    ->ignore($this->driver()?->id),
            ],
            'license_expires_at' => ['nullable', 'date'],
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
     * Get the driver being updated, if any.
     */
    protected function driver(): ?Driver
    {
        /** @var Driver|null $driver */
        $driver = $this->route('driver');

        return $driver;
    }
}
