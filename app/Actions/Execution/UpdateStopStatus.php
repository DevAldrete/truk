<?php

namespace App\Actions\Execution;

use App\Enums\StopStatus;
use App\Models\Stop;
use Illuminate\Validation\ValidationException;

/**
 * Moves a stop through its explicit status transitions as the driver works it.
 *
 * A completed stop never marks a shipment delivered on its own; the shipment
 * status is derived from delivery attempt lines.
 */
class UpdateStopStatus
{
    /**
     * Apply the status change.
     */
    public function handle(Stop $stop, StopStatus $status, ?string $notes = null): Stop
    {
        if ($status !== $stop->status && ! $stop->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => __('That status change is not allowed.'),
            ]);
        }

        $attributes = ['status' => $status];

        if ($status === StopStatus::Arrived && $stop->actual_arrival_at === null) {
            $attributes['actual_arrival_at'] = now();
        }

        if (
            in_array($status, [StopStatus::Completed, StopStatus::Failed, StopStatus::Skipped], true)
            && $stop->actual_departure_at === null
        ) {
            $attributes['actual_departure_at'] = now();
        }

        if ($notes !== null) {
            $attributes['notes'] = $notes;
        }

        $stop->update($attributes);

        return $stop;
    }
}
