<?php

namespace App\Http\Requests\Execution;

use App\Enums\ExpenseType;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordExpenseRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * Money is entered in major units and stored as integer minor units;
     * litres become millilitres and kilometres become metres.
     */
    protected function prepareForValidation(): void
    {
        foreach (['stop_id', 'vendor', 'notes', 'liters', 'price_per_liter', 'odometer_km', 'tank'] as $field) {
            if (! $this->filled($field)) {
                $this->merge([$field => null]);
            }
        }

        if (! $this->filled('incurred_at')) {
            $this->merge(['incurred_at' => now()->toIso8601String()]);
        }

        if (! $this->filled('currency')) {
            $this->merge(['currency' => 'MXN']);
        } else {
            $this->merge(['currency' => strtoupper(trim((string) $this->input('currency')))]);
        }

        $this->merge([
            'amount_minor' => $this->filled('amount')
                ? (int) round((float) $this->input('amount') * 100)
                : 0,
            'liters_ml' => $this->filled('liters')
                ? (int) round((float) $this->input('liters') * 1000)
                : null,
            'price_per_liter_minor' => $this->filled('price_per_liter')
                ? (int) round((float) $this->input('price_per_liter') * 100)
                : null,
            'odometer_meters' => $this->filled('odometer_km')
                ? (int) round((float) $this->input('odometer_km') * 1000)
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
            'type' => ['required', Rule::enum(ExpenseType::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'incurred_at' => ['required', 'date'],
            'stop_id' => [
                'nullable',
                'integer',
                Rule::exists('stops', 'id')->where('team_id', $this->team()->id),
            ],
            'vendor' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'liters' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn (): bool => $this->input('type') === ExpenseType::Fuel->value),
            ],
            'liters_ml' => [
                'nullable',
                'integer',
                'min:0',
                Rule::requiredIf(fn (): bool => $this->input('type') === ExpenseType::Fuel->value),
            ],
            'price_per_liter' => ['nullable', 'numeric', 'min:0'],
            'price_per_liter_minor' => ['nullable', 'integer', 'min:0'],
            'odometer_km' => ['nullable', 'numeric', 'min:0'],
            'odometer_meters' => ['nullable', 'integer', 'min:0'],
            'tank' => ['nullable', 'string', 'max:40'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
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
}
