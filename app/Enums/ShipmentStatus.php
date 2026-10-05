<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Planned = 'planned';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case PartiallyDelivered = 'partially_delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the shipment status.
     */
    public function label(): string
    {
        return __("shipment_statuses.{$this->value}");
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
            self::Dispatched => [self::InTransit, self::Failed, self::Cancelled],
            self::InTransit => [self::Delivered, self::PartiallyDelivered, self::Failed, self::Cancelled],
            self::PartiallyDelivered => [self::Delivered, self::Failed, self::Cancelled],
            self::Failed => [self::Planned, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    /**
     * Determine whether the shipment may move to the given status.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Get the shipment statuses as value/label pairs.
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
     * Get the shipment status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
