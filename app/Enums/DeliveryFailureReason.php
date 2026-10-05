<?php

namespace App\Enums;

enum DeliveryFailureReason: string
{
    case RecipientAbsent = 'recipient_absent';
    case Refused = 'refused';
    case WrongAddress = 'wrong_address';
    case Damaged = 'damaged';
    case AccessDenied = 'access_denied';
    case Documentation = 'documentation';
    case Other = 'other';

    /**
     * Get the display label for the failure reason.
     */
    public function label(): string
    {
        return __("delivery_failure_reasons.{$this->value}");
    }

    /**
     * Get the failure reasons as value/label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $reason) => ['value' => $reason->value, 'label' => $reason->label()])
            ->values()
            ->toArray();
    }

    /**
     * Get the failure reason values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $reason) => $reason->value, self::cases());
    }
}
