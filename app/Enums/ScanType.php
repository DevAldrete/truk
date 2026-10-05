<?php

namespace App\Enums;

enum ScanType: string
{
    case Loaded = 'loaded';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Damaged = 'damaged';

    /**
     * Get the display label for the scan type.
     */
    public function label(): string
    {
        return __("scan_types.{$this->value}");
    }

    /**
     * Get the package status this custody event drives.
     */
    public function packageStatus(): PackageStatus
    {
        return match ($this) {
            self::Loaded => PackageStatus::Loaded,
            self::InTransit => PackageStatus::InTransit,
            self::Delivered => PackageStatus::Delivered,
            self::Returned => PackageStatus::Returned,
            self::Damaged => PackageStatus::Damaged,
        };
    }

    /**
     * Get the scan types as value/label pairs.
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
     * Get the scan type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
