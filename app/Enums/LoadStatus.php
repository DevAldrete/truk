<?php

namespace App\Enums;

enum LoadStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case InTransit = 'in_transit';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the load status.
     */
    public function label(): string
    {
        return __("load_statuses.{$this->value}");
    }

    /**
     * Get the statuses this status may transition to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Planned, self::Cancelled],
            self::Planned => [self::InTransit, self::Cancelled],
            self::InTransit => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    /**
     * Determine whether the load may move to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Get the load statuses as value/label pairs.
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
     * Get the load status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
