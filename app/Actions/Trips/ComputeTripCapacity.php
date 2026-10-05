<?php

namespace App\Actions\Trips;

use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Trip;
use Illuminate\Support\Collection;

/**
 * Computes the planned load of a trip against the effective vehicle/trailer
 * limit, so the dispatch gate can block an overload and the board can show how
 * full the trip is.
 */
class ComputeTripCapacity
{
    /**
     * @return array{
     *     shipments_count: int,
     *     weight_grams: int,
     *     volume_cm3: int,
     *     weight_limit_grams: int|null,
     *     volume_limit_cm3: int|null,
     *     weight_utilization: float|null,
     *     volume_utilization: float|null,
     *     over_weight: bool,
     *     over_volume: bool,
     *     over: bool
     * }
     */
    public function handle(Trip $trip): array
    {
        $trip->loadMissing(['vehicle', 'trailer']);

        $shipments = $this->shipments($trip);

        $weight = (int) $shipments->sum('weight_grams');
        $volume = (int) $shipments->sum('volume_cm3');

        $weightLimit = $this->limit([$trip->vehicle?->max_payload_grams, $trip->trailer?->max_payload_grams]);
        $volumeLimit = $this->limit([$trip->vehicle?->max_volume_cm3, $trip->trailer?->max_volume_cm3]);

        $overWeight = $weightLimit !== null && $weight > $weightLimit;
        $overVolume = $volumeLimit !== null && $volume > $volumeLimit;

        return [
            'shipments_count' => $shipments->count(),
            'weight_grams' => $weight,
            'volume_cm3' => $volume,
            'weight_limit_grams' => $weightLimit,
            'volume_limit_cm3' => $volumeLimit,
            'weight_utilization' => $this->utilization($weight, $weightLimit),
            'volume_utilization' => $this->utilization($volume, $volumeLimit),
            'over_weight' => $overWeight,
            'over_volume' => $overVolume,
            'over' => $overWeight || $overVolume,
        ];
    }

    /**
     * Get the distinct shipments served by the trip's stops.
     *
     * A shipment on two stops must not be counted twice.
     *
     * @return Collection<int, Shipment>
     */
    protected function shipments(Trip $trip): Collection
    {
        $ids = $trip->stops()
            ->with('shipments')
            ->get()
            ->flatMap(fn (Stop $stop) => $stop->shipments->pluck('id'))
            ->unique()
            ->values();

        return Shipment::query()->whereIn('id', $ids)->get();
    }

    /**
     * Get the smallest non-null limit, when any.
     *
     * @param  array<int, int|null>  $values
     */
    protected function limit(array $values): ?int
    {
        $values = array_values(array_filter($values, fn (?int $value): bool => $value !== null));

        return $values === [] ? null : (int) min($values);
    }

    /**
     * Get the fill percentage of a value against its limit.
     */
    protected function utilization(int $value, ?int $limit): ?float
    {
        if ($limit === null || $limit === 0) {
            return null;
        }

        return round($value / $limit * 100, 1);
    }
}
