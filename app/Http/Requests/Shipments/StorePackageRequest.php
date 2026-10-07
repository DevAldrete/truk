<?php

namespace App\Http\Requests\Shipments;

use App\Models\Shipment;
use App\Models\Team;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('weight_kg')) {
            $this->merge([
                'weight_grams' => (int) round((float) $this->input('weight_kg') * 1000),
            ]);
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
            'count' => ['required', 'integer', 'min:1', 'max:'.config('shipments.max_packages_per_request')],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator after the rules run.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('count')) {
                    return;
                }

                $shipment = $this->route('shipment');

                if (! $shipment instanceof Shipment) {
                    return;
                }

                $existing = $shipment->packages()->withTrashed()->count();
                $remaining = (int) config('shipments.max_packages_per_shipment') - $existing;
                $requested = (int) $this->input('count');

                if ($requested > $remaining) {
                    $validator->errors()->add('count', __('A shipment can hold at most :max packages. You can add :remaining more.', [
                        'max' => config('shipments.max_packages_per_shipment'),
                        'remaining' => max($remaining, 0),
                    ]));
                }
            },
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
