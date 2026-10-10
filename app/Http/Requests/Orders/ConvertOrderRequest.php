<?php

namespace App\Http\Requests\Orders;

use App\Models\Order;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertOrderRequest extends FormRequest
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
                Rule::exists('locations', 'id')->where('team_id', $this->team()->id)->whereNull('deleted_at'),
            ],
            'delivery_location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('team_id', $this->team()->id)->whereNull('deleted_at'),
            ],
            'package_count' => ['nullable', 'integer', 'min:0', 'max:'.config('shipments.max_packages_per_shipment')],
        ];
    }

    /**
     * Get the order being converted.
     */
    public function order(): Order
    {
        /** @var Order $order */
        $order = $this->route('order');

        return $order;
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
