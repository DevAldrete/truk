<?php

namespace App\Enums;

enum IncidentSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * Get the display label for the incident severity.
     */
    public function label(): string
    {
        return __("incident_severities.{$this->value}");
    }

    /**
     * Get the incident severities as value/label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $severity) => ['value' => $severity->value, 'label' => $severity->label()])
            ->values()
            ->toArray();
    }

    /**
     * Get the incident severity values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $severity) => $severity->value, self::cases());
    }
}
