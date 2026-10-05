<?php

namespace App\Enums;

enum StopType: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';
    case Other = 'other';

    /**
     * Get the display label for the stop type.
     */
    public function label(): string
    {
        return __("stop_types.{$this->value}");
    }

    /**
     * Get the stop types as value/label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()])
            ->values()
            ->toArray();
    }

    /**
     * Get the stop type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
