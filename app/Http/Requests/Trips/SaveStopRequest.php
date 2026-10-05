<?php

namespace App\Http\Requests\Trips;

use App\Enums\StopStatus;
use App\Enums\StopType;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStopRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['location_id', 'planned_at', 'notes'] as $field) {
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
        return [
            'type' => ['required', Rule::enum(StopType::class)],
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('team_id', $this->team()->id),
            ],
            'planned_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(StopStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
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
