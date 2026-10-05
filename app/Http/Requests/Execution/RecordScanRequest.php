<?php

namespace App\Http\Requests\Execution;

use App\Enums\ScanType;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordScanRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['stop_id', 'latitude', 'longitude', 'notes'] as $field) {
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
            'package_id' => [
                'required',
                'integer',
                Rule::exists('packages', 'id')->where('team_id', $this->team()->id),
            ],
            'stop_id' => [
                'nullable',
                'integer',
                Rule::exists('stops', 'id')->where('team_id', $this->team()->id),
            ],
            'type' => ['required', Rule::enum(ScanType::class)],
            'occurred_at' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'idempotency_key' => ['required', 'uuid'],
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
