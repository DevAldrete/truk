<?php

namespace App\Enums;

enum DeliveryOutcome: string
{
    case Delivered = 'delivered';
    case PartiallyDelivered = 'partially_delivered';
    case Failed = 'failed';
    case Returned = 'returned';

    /**
     * Get the display label for the delivery outcome.
     */
    public function label(): string
    {
        return __("delivery_outcomes.{$this->value}");
    }

    /**
     * Determine whether the outcome delivered at least part of the goods.
     */
    public function succeeded(): bool
    {
        return $this === self::Delivered || $this === self::PartiallyDelivered;
    }

    /**
     * Get the delivery outcomes as value/label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $outcome) => ['value' => $outcome->value, 'label' => $outcome->label()])
            ->values()
            ->toArray();
    }

    /**
     * Get the delivery outcome values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $outcome) => $outcome->value, self::cases());
    }
}
