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

    public static function toCents(string|float|int|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = str_replace(['.', ',', '€', ' '], ['', '.', '', ''], $value);
        }

        return (int) round(((float) $value) * 100);
    }
}
