<?php

namespace App\Support;

final class Money
{
    public static function format(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        return number_format($cents / 100, 2, ',', '.').' €';
    }

    /**
     * Versteht "1.299,00", "1299,5", "1299.50" und "1.500" (= 1.500 €).
     */
    public static function toCents(string|float|int|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }
        $value = str_replace(['€', ' ', "\u{00A0}"], '', trim($value));
        if (str_contains($value, ',')) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif (substr_count($value, '.') > 1 || preg_match('/^\d{1,3}\.\d{3}$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? (int) round(((float) $value) * 100) : null;
    }
}
