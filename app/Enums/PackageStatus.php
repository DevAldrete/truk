<?php

namespace App\Enums;

enum PackageStatus: string
{
    case Created = 'created';
    case Loaded = 'loaded';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Damaged = 'damaged';

    /**
     * Get the display label for the package status.
     */
    public function label(): string
    {
        return __("package_statuses.{$this->value}");
    }

    /**
     * Get the statuses this status may transition to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Created => [self::Loaded, self::Damaged],
            self::Loaded => [self::InTransit, self::Damaged],
            self::InTransit => [self::Delivered, self::Returned, self::Damaged],
            self::Delivered, self::Returned, self::Damaged => [],
        };
    }

    /**
     * Determine whether the package may move to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Get the package statuses as value/label pairs.
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
     * Get the package status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
