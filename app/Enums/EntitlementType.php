<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum EntitlementType: string implements FilamentLabel
{
    use HasLabel;

    case WbkVoreintrag = 'wbk_voreintrag';
    case WbkYellowSport = 'wbk_yellow_sport';
    case WbkRedCollector = 'wbk_red_collector';
    case HuntingLicenseLonggun = 'hunting_license_longgun';
    case AmmoPermit = 'ammo_permit';
    case Dealer = 'dealer';
    case Authority = 'authority';

    public function label(): string
    {
        return match ($this) {
            self::WbkVoreintrag => 'Erwerbsberechtigung (Voreintrag WBK)',
            self::WbkYellowSport => 'Gelbe WBK Sportschütze',
            self::WbkRedCollector => 'Rote WBK Sammler',
            self::HuntingLicenseLonggun => 'Jagdschein: Langwaffen / Langwaffenmunition',
            self::AmmoPermit => 'Munitionserwerb (Kaliber)',
            self::Dealer => 'Waffenhändler',
            self::Authority => 'Behörde',
        };
    }
}
