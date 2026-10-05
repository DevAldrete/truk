<?php

namespace App\Http\Requests\Fleet;

use App\Models\Team;
use App\Models\Trailer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTrailerRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * Capacity is entered in kilograms and cubic metres but stored as grams
     * and cubic centimetres, so no float ever reaches the database.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('carrier_party_id')) {
            $this->merge(['carrier_party_id' => null]);
        }

        foreach (['name', 'configuration'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }

        if ($this->filled('plate')) {
            $this->merge(['plate' => strtoupper(trim((string) $this->input('plate')))]);
        }

        $this->merge([
            'max_payload_grams' => $this->filled('max_payload_kg')
                ? (int) round((float) $this->input('max_payload_kg') * 1000)
                : null,
            'max_volume_cm3' => $this->filled('max_volume_m3')
                ? (int) round((float) $this->input('max_volume_m3') * 1000000)
                : null,
        ]);
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
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('trailers', 'plate')
                    ->where('team_id', $this->team()->id)
                    ->withoutTrashed()
                    ->ignore($this->trailer()?->id),
            ],
            'configuration' => ['required', 'string', 'max:60'],
            'max_payload_kg' => ['required', 'numeric', 'min:0.001'],
            'max_payload_grams' => ['required', 'integer', 'min:1'],
            'max_volume_m3' => ['nullable', 'numeric', 'min:0.001'],
            'max_volume_cm3' => ['nullable', 'integer', 'min:1'],
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
     * Get the trailer being updated, if any.
     */
    protected function trailer(): ?Trailer
    {
        /** @var Trailer|null $trailer */
        $trailer = $this->route('trailer');

        return $trailer;
    }
}
