<?php

namespace App\Enums;

enum ExpenseType: string
{
    case Fuel = 'fuel';
    case Toll = 'toll';
    case Lodging = 'lodging';
    case Maintenance = 'maintenance';
    case Misc = 'misc';

    /**
     * Get the display label for the expense type.
     */
    public function label(): string
    {
        return __("expense_types.{$this->value}");
    }

    /**
     * Determine whether the type captures fuel volume.
     */
    public function isFuel(): bool
    {
        return $this === self::Fuel;
    }

    /**
     * Get the expense types as value/label pairs.
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
     * Get the expense type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
