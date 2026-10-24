<?php

namespace App\Support;

final class QuantityFormatter
{
    public static function display(int|float|string|null $quantity): string
    {
        if ($quantity === null || $quantity === '') {
            return '0';
        }

        $formatted = number_format((float) $quantity, 3, '.', ',');
        return str_contains($formatted, '.')
            ? rtrim(rtrim($formatted, '0'), '.')
            : $formatted;
    }
}
