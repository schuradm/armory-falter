<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum TradeRequestType: string implements FilamentLabel
{
    use HasLabel;

    case Consignment = 'consignment';
    case Buyback = 'buyback';

    public function label(): string
    {
        return match ($this) {
            self::Consignment => 'Verkaufsservice (egun)',
            self::Buyback => 'Ankauf',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Consignment => 'VS',
            self::Buyback => 'AK',
        };
    }
}
