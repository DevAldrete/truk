<?php

namespace App\Http\Requests\Trips;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTripResourcesRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['driver_id', 'vehicle_id', 'trailer_id'] as $field) {
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
        $teamId = $this->team()->id;

        return [
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('drivers', 'id')->where('team_id', $teamId),
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicles', 'id')->where('team_id', $teamId),
            ],
            'trailer_id' => [
                'nullable',
                'integer',
                Rule::exists('trailers', 'id')->where('team_id', $teamId),
            ],
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
