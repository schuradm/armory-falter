<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * Versandart, die eine Rechtsklasse mindestens verlangt. Die Reihenfolge der Fälle
 * ist die Strenge: ein Warenkorb nimmt immer den strengsten Modus seiner Positionen.
 */
enum ShippingMode: string implements FilamentLabel
{
    use HasLabel;

    case Standard = 'standard';
    case AgeCheck = 'age_check';
    case WeaponPersonal = 'weapon_personal';
    case PickupOnly = 'pickup_only';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standardversand',
            self::AgeCheck => 'Versand mit Alterssichtprüfung 18+',
            self::WeaponPersonal => 'Waffenversand mit persönlicher Übergabe',
            self::PickupOnly => 'Nur Abholung im Geschäft',
        };
    }

    public function strictness(): int
    {
        return array_search($this, self::cases(), true);
    }

    public static function strictest(iterable $modes): self
    {
        $result = self::Standard;
        foreach ($modes as $mode) {
            if ($mode->strictness() > $result->strictness()) {
                $result = $mode;
            }
        }

        return $result;
    }
}
