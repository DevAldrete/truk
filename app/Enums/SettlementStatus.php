<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case Open = 'open';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case Paid = 'paid';

    /**
     * Get the display label for the settlement status.
     */
    public function label(): string
    {
        return __("settlement_statuses.{$this->value}");
    }

    /**
     * Get the settlement statuses as value/label pairs.
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
     * Get the settlement status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
