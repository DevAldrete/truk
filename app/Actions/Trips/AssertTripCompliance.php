<?php

namespace App\Actions\Trips;

use App\Models\ComplianceDocument;
use App\Models\Driver;
use App\Models\Trip;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Checks the legality documents of the resources assigned to a trip.
 *
 * It reports every expired document (driver licence, and the compliance
 * documents of the driver, vehicle, and trailer) relative to the trip's
 * planned start, so dispatch can be blocked before an uninsured or unlicensed
 * unit rolls. It only blocks on *expired* documents; presence is enforced by
 * the fiscal gate in a later phase.
 */
class AssertTripCompliance
{
    /**
     * Get the compliance violations for the trip.
     *
     * @return array<int, array{resource: string, name: string, document: string, expires_at: string, message: string}>
     */
    public function handle(Trip $trip): array
    {
        $at = $trip->planned_start_at ?? now();

        $trip->loadMissing(['driver.documents', 'vehicle.documents', 'trailer.documents']);

        $violations = [];

        if ($trip->driver !== null) {
            $violations = [
                ...$violations,
                ...$this->forDriver($trip->driver, $at),
            ];
        }

        if ($trip->vehicle !== null) {
            $violations = [
                ...$violations,
                ...$this->forResource('vehicle', $trip->vehicle->name, $trip->vehicle->documents, $at),
            ];
        }

        if ($trip->trailer !== null) {
            $violations = [
                ...$violations,
                ...$this->forResource('trailer', $trip->trailer->name, $trip->trailer->documents, $at),
            ];
        }

        return $violations;
    }

    /**
     * Get the violations for a driver: the licence plus attached documents.
     *
     * @return array<int, array{resource: string, name: string, document: string, expires_at: string, message: string}>
     */
    protected function forDriver(Driver $driver, CarbonInterface $at): array
    {
        $violations = [];

        if ($driver->license_expires_at !== null && $driver->license_expires_at->lt($at)) {
            $violations[] = $this->violation('driver', $driver->name, __('Licence'), $driver->license_expires_at);
        }

        return [
            ...$violations,
            ...$this->forResource('driver', $driver->name, $driver->documents, $at),
        ];
    }

    /**
     * Get the violations for the expired documents of a resource.
     *
     * @param  Collection<int, ComplianceDocument>  $documents
     * @return array<int, array{resource: string, name: string, document: string, expires_at: string, message: string}>
     */
    protected function forResource(string $resource, string $name, Collection $documents, CarbonInterface $at): array
    {
        return $documents
            ->filter(fn (ComplianceDocument $document): bool => $document->expires_at !== null && $document->expires_at->lt($at))
            ->map(fn (ComplianceDocument $document): array => $this->violation(
                $resource,
                $name,
                $document->type->label(),
                $document->expires_at,
            ))
            ->values()
            ->all();
    }

    /**
     * Build a single violation payload.
     *
     * @return array{resource: string, name: string, document: string, expires_at: string, message: string}
     */
    protected function violation(string $resource, string $name, string $document, CarbonInterface $expiresAt): array
    {
        return [
            'resource' => $resource,
            'name' => $name,
            'document' => $document,
            'expires_at' => $expiresAt->toDateString(),
            'message' => __(':document for :name expired on :date.', [
                'document' => $document,
                'name' => $name,
                'date' => $expiresAt->toDateString(),
            ]),
        ];
    }
}
