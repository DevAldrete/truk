<?php

namespace App\Enums;

enum PartyType: string
{
    case Customer = 'customer';
    case Carrier = 'carrier';
    case Supplier = 'supplier';

    /**
     * Get the display label for the party type.
     */
    public function label(): string
    {
        return __("party_types.{$this->value}");
    }

    /**
     * Get the party types as value/label pairs.
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
     * Get the party types that can be selected as a default for new records.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
