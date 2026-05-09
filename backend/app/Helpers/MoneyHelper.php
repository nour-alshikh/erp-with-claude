<?php

namespace App\Helpers;

class MoneyHelper
{
    /** Format integer cents to display string: 9999 → "99.99" */
    public static function format(int $cents, string $currency = ''): string
    {
        $formatted = number_format($cents / 100, 2);
        return $currency ? "{$currency} {$formatted}" : $formatted;
    }

    /** Convert a display value to cents integer: "99.99" → 9999 */
    public static function toCents(float|string $value): int
    {
        return (int) round((float) $value * 100);
    }
}
