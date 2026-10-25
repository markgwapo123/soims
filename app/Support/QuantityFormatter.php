<?php

namespace App\Support;

final class QuantityFormatter
{
    /** Quantity columns shared by all report tables and CSV exports. */
    public const REPORT_QUANTITY_COLUMNS = [
        'quantity_on_hand', 'quantity_reserved', 'quantity_available', 'reorder_level',
        'quantity', 'requested_quantity', 'confirmed_quantity', 'issued_quantity',
        'available_quantity', 'not_available_quantity', 'unavailable_quantity',
        'quantity_per_property_card', 'quantity_per_physical_count',
        'shortage_quantity', 'overage_quantity',
    ];

    public static function reportValue(string $column, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return in_array($column, self::REPORT_QUANTITY_COLUMNS, true)
            ? self::display($value)
            : $value;
    }

    public static function display(int|float|string|null $quantity): string
    {
        if ($quantity === null || $quantity === '') {
            return '0';
        }

        return number_format((float) $quantity, 0, '.', ',');
    }
}
