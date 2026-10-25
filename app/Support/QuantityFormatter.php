<?php

namespace App\Support;

final class QuantityFormatter
{
    public static function display(int|float|string|null $quantity): string
    {
        if ($quantity === null || $quantity === '') {
            return '0';
        }

        return number_format((float) $quantity, 0, '.', ',');
    }
}
