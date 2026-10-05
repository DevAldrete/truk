<?php

namespace App\Enums;

enum IncidentType: string
{
    case Delay = 'delay';
    case Accident = 'accident';
    case Breakdown = 'breakdown';
    case Damage = 'damage';
    case Theft = 'theft';
    case Documentation = 'documentation';
    case Customer = 'customer';
    case Other = 'other';

    /**
     * Get the display label for the incident type.
     */
    public function label(): string
    {
        return __("incident_types.{$this->value}");
    }

    /**
     * Get the incident types as value/label pairs.
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
     * Get the incident type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
