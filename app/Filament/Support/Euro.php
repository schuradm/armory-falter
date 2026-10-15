<?php

namespace App\Filament\Support;

use App\Support\Money;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;

/**
 * Geldfelder im Backend: in der Datenbank Cent (int), im Formular Euro mit Komma.
 * "1.299,00", "1299,5" und "1299.50" werden gleichermaßen verstanden.
 */
final class Euro
{
    public static function toCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }
        $value = str_replace(['€', ' ', "\u{00A0}"], '', trim((string) $value));
        if (str_contains($value, ',')) {
            // deutsches Format: Punkt = Tausendertrenner, Komma = Dezimaltrenner
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif (substr_count($value, '.') > 1) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? (int) round(((float) $value) * 100) : null;
    }

    public static function fromCents(mixed $cents): ?string
    {
        if ($cents === null || $cents === '') {
            return null;
        }

        return number_format(((int) $cents) / 100, 2, ',', '');
    }

    public static function input(string $name, ?string $label = null): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->suffix('€')
            ->inputMode('decimal')
            ->placeholder('0,00')
            ->rule('regex:/^\s*\d{1,3}(\.?\d{3})*([.,]\d{1,2})?\s*(€)?\s*$/u')
            ->validationMessages(['regex' => 'Bitte einen Betrag in Euro eingeben, z. B. 1299,00.'])
            ->formatStateUsing(fn ($state) => self::fromCents($state))
            ->dehydrateStateUsing(fn ($state) => self::toCents($state));
    }

    public static function column(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->alignEnd()
            ->formatStateUsing(fn ($state) => Money::format($state === null ? null : (int) $state));
    }

    public static function entry(string $name, ?string $label = null): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state) => Money::format($state === null ? null : (int) $state));
    }
}
