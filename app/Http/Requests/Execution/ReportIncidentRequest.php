<?php

namespace App\Http\Requests\Execution;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Models\Team;
use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportIncidentRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['stop_id', 'shipment_id'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        if (! $this->filled('occurred_at')) {
            $this->merge(['occurred_at' => now()->toIso8601String()]);
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
            'type' => ['required', Rule::enum(IncidentType::class)],
            'severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'description' => ['required', 'string', 'max:4000'],
            'occurred_at' => ['required', 'date'],
            'stop_id' => [
                'nullable',
                'integer',
                Rule::exists('stops', 'id')
                    ->where('team_id', $this->team()->id)
                    ->where('trip_id', $this->trip()->id),
            ],
            'shipment_id' => [
                'nullable',
                'integer',
                Rule::exists('shipments', 'id')->where('team_id', $this->team()->id),
            ],
            'idempotency_key' => ['required', 'uuid'],
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
     * Get the trip the incident is reported against.
     */
    protected function trip(): Trip
    {
        /** @var Trip $trip */
        $trip = $this->route('trip');

        return $trip;
    }
}
