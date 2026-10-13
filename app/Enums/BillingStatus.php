<?php

namespace App\Enums;

enum BillingStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the billing status.
     */
    public function label(): string
    {
        return __("billing_statuses.{$this->value}");
    }

    /**
     * Get the billing statuses as value/label pairs.
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
     * Get the billing status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
