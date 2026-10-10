<?php

namespace App\Enums;

enum CfdiType: string
{
    case Ingreso = 'ingreso';
    case Traslado = 'traslado';

    /**
     * Get the display label for the CFDI type.
     */
    public function label(): string
    {
        return __("cfdi_types.{$this->value}");
    }

    /**
     * Get the CFDI types as value/label pairs.
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
     * Get the CFDI type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
