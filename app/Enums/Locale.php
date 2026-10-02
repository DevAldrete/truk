<?php

namespace App\Enums;

enum Locale: string
{
    case Es = 'es';
    case En = 'en';

    /**
     * Get the name of the language in that language.
     */
    public function label(): string
    {
        return match ($this) {
            self::Es => 'Español',
            self::En => 'English',
        };
    }

    /**
     * Get the locale codes the application supports.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $locale) => $locale->value, self::cases());
    }

    /**
     * Get the selectable locales as value/label pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $locale) => ['value' => $locale->value, 'label' => $locale->label()])
            ->values()
            ->toArray();
    }
}
