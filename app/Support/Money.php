<?php

namespace App\Support;

class Money
{
    /**
     * Format a cents amount for display. Prices are stored in cents throughout
     * the app (plans, paid actions), so every screen formats them the same way.
     */
    public static function format(int|float|null $cents, bool $decimals = true): string
    {
        $symbol = config('qrcode.currency_symbol', '€');

        return $symbol.number_format(((int) $cents) / 100, $decimals ? 2 : 0);
    }

    /** Compact form for stat tiles: €1.2k once the number gets long. */
    public static function compact(int|float|null $cents): string
    {
        $symbol = config('qrcode.currency_symbol', '€');
        $units = ((int) $cents) / 100;

        if (abs($units) >= 1000000) {
            return $symbol.rtrim(rtrim(number_format($units / 1000000, 1), '0'), '.').'M';
        }

        if (abs($units) >= 1000) {
            return $symbol.rtrim(rtrim(number_format($units / 1000, 1), '0'), '.').'k';
        }

        return $symbol.number_format($units, floor($units) == $units ? 0 : 2);
    }
}
