<?php

namespace App\Enums;

enum TripStatus: string
{
    case Planned = 'planned';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the trip status.
     */
    public function label(): string
    {
        return __("trip_statuses.{$this->value}");
    }

    /**
     * Get the statuses this status may transition to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Planned => [self::Dispatched, self::Cancelled],
            self::Dispatched => [self::InTransit, self::Cancelled],
            self::InTransit => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    /**
     * Determine whether the trip may move to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Determine whether the trip is still open for resource conflicts.
     */
    public function isOpen(): bool
    {
        return $this !== self::Completed && $this !== self::Cancelled;
    }

    /**
     * Get the trip statuses as value/label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()])
            ->values()
            ->toArray();
    }

    /**
     * Get the trip status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
