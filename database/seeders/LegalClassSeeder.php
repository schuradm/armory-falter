<?php

namespace Database\Seeders;

use App\Enums\EntitlementType as E;
use App\Enums\PaymentMethod as P;
use App\Enums\ShippingMode;
use App\Models\LegalClass;
use Illuminate\Database\Seeder;

/**
 * Rechtsklassen. PayPal, Karte, Sofort (Klarna Pay now) und Klarna nur für frei verkäufliche Ware,
 * weil diese Anbieter Waffen/Munition in ihren AGB ausschließen.
 * [PLATZHALTER] Werte vor Go-live anwaltlich bestätigen lassen (docs/10-waffenrecht-compliance.md).
 */
class LegalClassSeeder extends Seeder
{
    public function run(): void
    {
        $offline = [P::Prepayment->value, P::CashPickup->value, P::WeaponPsp->value];

        $classes = [
            [
                'code' => 'free',
                'name' => 'Frei verkäuflich',
                'min_age' => 0,
                'requires_entitlement' => false,
                'accepted_entitlements' => [],
                'shipping_mode' => ShippingMode::Standard,
                'allowed_payment_codes' => [P::Prepayment->value, P::CashPickup->value, P::PayPal->value, P::Card->value, P::Sofort->value, P::Klarna->value],
                'requires_serial' => false,
                'notice_text' => null,
            ],
            [
                'code' => 'age_18',
                'name' => 'Abgabe ab 18 Jahren',
                'min_age' => 18,
                'requires_entitlement' => false,
                'accepted_entitlements' => [],
                'shipping_mode' => ShippingMode::AgeCheck,
                'allowed_payment_codes' => $offline,
                'requires_serial' => false,
                'notice_text' => 'Abgabe nur an Personen ab 18 Jahren. Der Versand erfolgt mit DHL-Alterssichtprüfung. Beachten Sie die Führverbote nach § 42a WaffG.',
            ],
            [
                'code' => 'firearm_permit',
                'name' => 'Erlaubnispflichtige Schusswaffe',
                'min_age' => 18,
                'requires_entitlement' => true,
                'accepted_entitlements' => [E::WbkVoreintrag->value, E::WbkYellowSport->value, E::WbkRedCollector->value,
                    E::HuntingLicenseLonggun->value, E::Dealer->value, E::Authority->value],
                'shipping_mode' => ShippingMode::WeaponPersonal,
                'allowed_payment_codes' => $offline,
                'requires_serial' => true,
                'notice_text' => 'Erwerb nur gegen Nachweis einer gültigen Erwerbsberechtigung (WBK mit Voreintrag, gelbe WBK oder Jagdschein für Langwaffen). Die Bestellung wird vor dem Versand durch unsere Waffensachbearbeitung geprüft (§ 34 WaffG). Versand nur mit persönlicher Übergabe an den Erwerber oder Abholung im Geschäft.',
            ],
            [
                'code' => 'ammo_permit',
                'name' => 'Erlaubnispflichtige Munition',
                'min_age' => 18,
                'requires_entitlement' => true,
                'accepted_entitlements' => [E::AmmoPermit->value, E::HuntingLicenseLonggun->value, E::Dealer->value, E::Authority->value],
                'shipping_mode' => ShippingMode::WeaponPersonal,
                'allowed_payment_codes' => $offline,
                'requires_serial' => false,
                'notice_text' => 'Abgabe nur gegen Nachweis der Munitionserwerbsberechtigung für das Kaliber (WBK mit Munitionserwerb oder Jagdschein für Langwaffenmunition). Versand mit persönlicher Übergabe ab 18 Jahren.',
            ],
            [
                'code' => 'pickup_only',
                'name' => 'Nur Abholung (Sprengstoffrecht)',
                'min_age' => 18,
                'requires_entitlement' => false,
                'accepted_entitlements' => [],
                'shipping_mode' => ShippingMode::PickupOnly,
                'allowed_payment_codes' => [P::Prepayment->value, P::CashPickup->value],
                'requires_serial' => false,
                'notice_text' => 'Treibladungspulver und Anzündhütchen geben wir nur im Geschäft ab. Bitte bringen Sie Ihre Erlaubnis nach § 27 SprengG bzw. Ihren Nachweis nach dem Sprengstoffgesetz zur Abholung mit. [PLATZHALTER: anwaltlich prüfen]',
            ],
        ];

        foreach ($classes as $class) {
            LegalClass::updateOrCreate(['code' => $class['code']], $class);
        }
    }
}
