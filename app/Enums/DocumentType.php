<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum DocumentType: string implements FilamentLabel
{
    use HasLabel;

    case WbkGreen = 'wbk_green';
    case WbkYellow = 'wbk_yellow';
    case WbkRed = 'wbk_red';
    case HuntingLicense = 'hunting_license';
    case IdCard = 'id_card';
    case Passport = 'passport';
    case AmmoPermit = 'ammo_permit';
    case DealerLicense = 'dealer_license';
    case AuthorityCertificate = 'authority_certificate';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WbkGreen => 'Waffenbesitzkarte (grün)',
            self::WbkYellow => 'Waffenbesitzkarte für Sportschützen (gelb)',
            self::WbkRed => 'Waffenbesitzkarte für Sammler/Sachverständige (rot)',
            self::HuntingLicense => 'Jagdschein',
            self::IdCard => 'Personalausweis',
            self::Passport => 'Reisepass',
            self::AmmoPermit => 'Munitionserwerbsschein',
            self::DealerLicense => 'Waffenhandelserlaubnis',
            self::AuthorityCertificate => 'Behördenbescheinigung / Beschaffungsauftrag',
            self::Other => 'Sonstiges Dokument',
        };
    }

    /** Dokumente, die das Geburtsdatum belegen und damit eine Altersverifikation erlauben. */
    public function provesAge(): bool
    {
        return in_array($this, [self::IdCard, self::Passport, self::WbkGreen, self::WbkYellow, self::WbkRed, self::HuntingLicense], true);
    }

    /** Berechtigungen, die Sachbearbeiter bei Freigabe dieses Dokuments typischerweise anlegen. */
    public function suggestedEntitlements(): array
    {
        return match ($this) {
            self::WbkGreen => [EntitlementType::WbkVoreintrag, EntitlementType::AmmoPermit],
            self::WbkYellow => [EntitlementType::WbkYellowSport, EntitlementType::AmmoPermit],
            self::WbkRed => [EntitlementType::WbkRedCollector],
            self::HuntingLicense => [EntitlementType::HuntingLicenseLonggun],
            self::AmmoPermit => [EntitlementType::AmmoPermit],
            self::DealerLicense => [EntitlementType::Dealer],
            self::AuthorityCertificate => [EntitlementType::Authority],
            default => [],
        };
    }
}
