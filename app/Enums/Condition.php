<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum Condition: string implements FilamentLabel
{
    use HasLabel;

    case New = 'new';
    case LikeNew = 'like_new';
    case Demo = 'demo';
    case Used = 'used';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Neu',
            self::LikeNew => 'Neuwertig',
            self::Demo => 'Vorführware',
            self::Used => 'Gebraucht',
        };
    }

    public function schemaOrg(): string
    {
        return match ($this) {
            self::New => 'https://schema.org/NewCondition',
            self::LikeNew, self::Demo, self::Used => 'https://schema.org/UsedCondition',
        };
    }
}
