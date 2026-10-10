<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ProductType: string implements FilamentLabel
{
    use HasLabel;

    case Weapon = 'weapon';
    case Ammo = 'ammo';
    case Optic = 'optic';
    case Knife = 'knife';
    case Accessory = 'accessory';
    case Reloading = 'reloading';
    case Apparel = 'apparel';
    case Service = 'service';
    case Voucher = 'voucher';

    public function label(): string
    {
        return match ($this) {
            self::Weapon => 'Waffe',
            self::Ammo => 'Munition',
            self::Optic => 'Optik',
            self::Knife => 'Messer',
            self::Accessory => 'Zubehör',
            self::Reloading => 'Wiederladen',
            self::Apparel => 'Bekleidung',
            self::Service => 'Dienstleistung',
            self::Voucher => 'Gutschein',
        };
    }
}
