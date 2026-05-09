<?php

use App\Helpers\MoneyHelper;

if (! function_exists('money')) {
    function money(int $cents, string $currency = ''): string
    {
        return MoneyHelper::format($cents, $currency);
    }
}

if (! function_exists('to_cents')) {
    function to_cents(float|string $value): int
    {
        return MoneyHelper::toCents($value);
    }
}
