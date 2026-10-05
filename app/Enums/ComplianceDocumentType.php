<?php

namespace App\Enums;

enum ComplianceDocumentType: string
{
    case License = 'license';
    case Insurance = 'insurance';
    case Verification = 'verification';
    case Permit = 'permit';
    case Inspection = 'inspection';
    case Other = 'other';

    /**
     * Get the display label for the document type.
     */
    public function label(): string
    {
        return __("compliance_document_types.{$this->value}");
    }

    /**
     * Get the document types as value/label pairs.
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
     * Get the document type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }
}
