<?php

namespace App\Http\Requests\Execution;

use App\Models\Team;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordProofOfDeliveryRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['delivery_attempt_id', 'shipment_id', 'recipient_name', 'signature', 'photos', 'documents', 'notes', 'latitude', 'longitude'] as $field) {
            if (! $this->filled($field) && ! $this->hasFile($field)) {
                $this->merge([$field => null]);
            }
        }

        if (! $this->filled('captured_at')) {
            $this->merge(['captured_at' => now()->toIso8601String()]);
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
            'delivery_attempt_id' => [
                'nullable',
                'integer',
                Rule::exists('delivery_attempts', 'id')->where('team_id', $this->team()->id),
            ],
            'shipment_id' => [
                'nullable',
                'integer',
                Rule::exists('shipments', 'id')->where('team_id', $this->team()->id),
            ],
            'recipient_name' => ['nullable', 'string', 'max:160'],
            'signature' => ['nullable', 'string', 'regex:/^data:image\/(png|jpe?g|webp);base64,/'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:10240'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'consent' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'captured_at' => ['required', 'date'],
            'idempotency_key' => ['required', 'uuid'],
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
                $hasEvidence = $this->filled('signature')
                    || $this->hasFile('photos')
                    || $this->hasFile('documents');

                if (! $hasEvidence) {
                    $validator->errors()->add('signature', __('Capture a signature, photo, or document.'));
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
