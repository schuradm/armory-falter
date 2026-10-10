<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

/** Zustandsangabe in Verkaufs- und Ankaufanfragen (angelehnt an gängige egun-Stufen). */
enum ItemCondition: string implements FilamentLabel
{
    use HasLabel;

    case New = 'new';
    case LikeNew = 'like_new';
    case VeryGood = 'very_good';
    case Good = 'good';
    case Used = 'used';
    case Defective = 'defective';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Neu / unbenutzt',
            self::LikeNew => 'Neuwertig',
            self::VeryGood => 'Sehr gut',
            self::Good => 'Gut',
            self::Used => 'Gebraucht mit Spuren',
            self::Defective => 'Defekt / Bastlerware',
        };
    }
}
