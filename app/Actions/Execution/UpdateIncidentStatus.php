<?php

namespace App\Actions\Execution;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Moves an incident through its allowed status transitions and records who
 * resolved it and why.
 */
class UpdateIncidentStatus
{
    /**
     * Apply the status change.
     */
    public function handle(Incident $incident, IncidentStatus $status, ?User $user = null, ?string $resolution = null): Incident
    {
        if ($status !== $incident->status && ! $incident->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => __('That status change is not allowed.'),
            ]);
        }

        $attributes = ['status' => $status];

        if ($status === IncidentStatus::Resolved || $status === IncidentStatus::Dismissed) {
            $attributes['resolution'] = $resolution;
            $attributes['resolved_by'] = $user?->id;
            $attributes['resolved_at'] = now();
        }

        $incident->update($attributes);

        return $incident;
    }
}
