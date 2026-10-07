<?php

namespace App\Http\Requests\Shipments;

use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveShipmentRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['pickup_location_id', 'delivery_location_id'] as $field) {
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
            'pickup_location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('team_id', $this->team()->id),
            ],
            'delivery_location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('team_id', $this->team()->id),
            ],
        ];
    }

    /**
     * Get the shipment being updated.
     */
    public function shipment(): Shipment
    {
        /** @var Shipment $shipment */
        $shipment = $this->route('shipment');

        return $shipment;
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
