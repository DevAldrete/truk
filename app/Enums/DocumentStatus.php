<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Stamped = 'stamped';
    case Error = 'error';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the document status.
     */
    public function label(): string
    {
        return __("document_statuses.{$this->value}");
    }

    /**
     * Get the document statuses as value/label pairs.
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
     * Get the document status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
