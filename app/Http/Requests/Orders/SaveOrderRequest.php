<?php

namespace App\Http\Requests\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOrderRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * Line weights and volumes are entered in kilograms and cubic metres but
     * stored as grams and cubic centimetres, so no float reaches the database.
     */
    protected function prepareForValidation(): void
    {
        foreach (['customer_party_id', 'requested_pickup_at', 'requested_delivery_at', 'notes'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        if ($this->filled('currency')) {
            $this->merge(['currency' => strtoupper(trim((string) $this->input('currency')))]);
        }

        $rawItems = $this->input('items');
        $items = [];

        foreach (is_array($rawItems) ? $rawItems : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $items[] = [
                'description' => trim((string) ($item['description'] ?? '')),
                'quantity' => $item['quantity'] ?? null,
                'unit' => filled($item['unit'] ?? null) ? trim((string) $item['unit']) : 'piece',
                'weight_kg' => $item['weight_kg'] ?? null,
                'weight_grams' => filled($item['weight_kg'] ?? null)
                    ? (int) round((float) $item['weight_kg'] * 1000)
                    : 0,
                'volume_m3' => $item['volume_m3'] ?? null,
                'volume_cm3' => filled($item['volume_m3'] ?? null)
                    ? (int) round((float) $item['volume_m3'] * 1000000)
                    : 0,
                'hazmat' => filter_var($item['hazmat'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $this->merge(['items' => $items]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_party_id' => [
                'nullable',
                'integer',
                Rule::exists('parties', 'id')->where('team_id', $this->team()->id),
            ],
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'currency' => ['required', 'string', 'size:3'],
            'requested_pickup_at' => ['nullable', 'date'],
            'requested_delivery_at' => ['nullable', 'date', 'after_or_equal:requested_pickup_at'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.weight_grams' => ['required', 'integer', 'min:0'],
            'items.*.volume_m3' => ['nullable', 'numeric', 'min:0'],
            'items.*.volume_cm3' => ['required', 'integer', 'min:0'],
            'items.*.hazmat' => ['boolean'],
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
     * Get the order being updated, if any.
     */
    protected function order(): ?Order
    {
        /** @var Order|null $order */
        $order = $this->route('order');

        return $order;
    }
}
