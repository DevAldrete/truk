<?php

namespace App\Http\Requests\Execution;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryOutcome;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordDeliveryAttemptRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['shipment_id', 'failure_reason', 'recipient_name', 'notes', 'latitude', 'longitude'] as $field) {
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
            'outcome' => ['required', Rule::enum(DeliveryOutcome::class)],
            'failure_reason' => [
                'nullable',
                Rule::enum(DeliveryFailureReason::class),
                Rule::requiredIf(fn (): bool => in_array($this->input('outcome'), [DeliveryOutcome::Failed->value, DeliveryOutcome::Returned->value], true)),
            ],
            'recipient_name' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'occurred_at' => ['required', 'date'],
            'idempotency_key' => ['required', 'uuid'],
            'shipment_id' => [
                'nullable',
                'integer',
                Rule::exists('shipments', 'id')->where('team_id', $this->team()->id),
            ],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.shipment_id' => [
                'required',
                'integer',
                Rule::exists('shipments', 'id')->where('team_id', $this->team()->id),
            ],
            'lines.*.shipment_item_id' => [
                'nullable',
                'integer',
                Rule::exists('shipment_items', 'id')->where('team_id', $this->team()->id),
            ],
            'lines.*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'lines.*.success' => ['required', 'boolean'],
            'lines.*.discrepancy_reason' => ['nullable', 'string', 'max:160'],
            'lines.*.notes' => ['nullable', 'string', 'max:2000'],
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
