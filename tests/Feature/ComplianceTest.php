<?php

namespace Tests\Feature;

use App\Enums\EntitlementType;
use App\Enums\PaymentMethod;
use App\Enums\ShippingMode;
use App\Models\Caliber;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Compliance\CartComplianceEvaluator;
use App\Services\Compliance\CartEvaluation;
use App\Services\Compliance\ComplianceResult;
use App\Services\Compliance\ComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * Waffenrechtliche Vorprüfung (§ 34 WaffG): Alter, passende Erwerbsberechtigung, Mengen, Behördenware,
 * dazu die Zusammenfassung eines Warenkorbs (strengste Versandart, gemeinsame Zahlungsarten).
 */
class ComplianceTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    private function check(string $sku, ?User $user, int $quantity = 1): ComplianceResult
    {
        return app(ComplianceService::class)->evaluate(Product::where('sku', $sku)->firstOrFail(), $user, $quantity);
    }

    private function caliber(string $slug): int
    {
        return Caliber::where('slug', $slug)->value('id');
    }

    /** @param array<string,int> $skus */
    private function evaluateCart(User $user, array $skus): CartEvaluation
    {
        $this->fillCart($user, $skus);

        return app(CartComplianceEvaluator::class)->evaluate(app(CartService::class)->current(), $user);
    }

    public function test_freie_ware_fuer_alle_altersware_nur_mit_geprueftem_alter(): void
    {
        $this->assertTrue($this->check('WAL-CLEAN-UNI', null)->allowed);

        $guest = $this->check('WAL-BWK2', null);
        $this->assertFalse($guest->allowed);
        $this->assertSame(ComplianceResult::HINT_LOGIN, $guest->hint);

        // Geburtsdatum allein genügt nicht, das Alter muss per Ausweis bestätigt sein
        $unverified = $this->check('WAL-BWK2', $this->customer());
        $this->assertFalse($unverified->allowed);
        $this->assertSame(ComplianceResult::HINT_VERIFY_AGE, $unverified->hint);

        $minor = $this->check('WAL-BWK2', $this->verifyAge($this->customer('Jung'), now()->subYears(17)->toDateString()));
        $this->assertFalse($minor->allowed);
        $this->assertNull($minor->hint); // kein Upload-Hinweis: Dokumente helfen hier nicht
        $this->assertStringContainsString('ab 18 Jahren', $minor->reasons[0]);

        $adult = $this->check('WAL-BWK2', $this->verifyAge($this->customer('Alt'), now()->subYears(18)->toDateString()));
        $this->assertTrue($adult->allowed);
        $this->assertFalse($adult->requiresReview, 'Altersware braucht keine Freigabe durch die Sachbearbeitung');
    }

    public function test_waffe_ohne_berechtigung_verlangt_upload(): void
    {
        $result = $this->check('GLO-G17-G5', $this->verifyAge($this->customer()));

        $this->assertFalse($result->allowed);
        $this->assertSame(ComplianceResult::HINT_UPLOAD_ENTITLEMENT, $result->hint);
    }

    public function test_voreintrag_passt_nur_zu_waffenart_und_kaliber(): void
    {
        $user = $this->verifyAge($this->customer());
        $ent = $this->grantEntitlement($user, EntitlementType::WbkVoreintrag,
            ['weapon_kind' => 'kurzwaffe', 'caliber_id' => $this->caliber('9mm-luger'), 'quantity_total' => 1]);

        $glock = $this->check('GLO-G17-G5', $user);
        $this->assertTrue($glock->allowed);
        $this->assertTrue($glock->requiresReview);
        $this->assertSame($ent->id, $glock->entitlement->id);

        $this->assertFalse($this->check('BLA-R8-PRO-308', $user)->allowed, 'Langwaffe passt nicht zum Kurzwaffen-Voreintrag');
        $this->assertFalse($this->check('SIG-AMMO-9-124', $user)->allowed, 'Ein Voreintrag berechtigt nicht zum Munitionserwerb');

        $ent->update(['caliber_id' => $this->caliber('308-win')]);
        $this->assertFalse($this->check('GLO-G17-G5', $user)->allowed, 'Kaliber im Voreintrag passt nicht');
    }

    public function test_menge_des_voreintrags_wird_im_warenkorb_verbraucht(): void
    {
        $user = $this->verifyAge($this->customer());
        $ent = $this->grantEntitlement($user, EntitlementType::WbkVoreintrag, ['weapon_kind' => 'any', 'quantity_total' => 1]);

        $this->assertFalse($this->check('GLO-G17-G5', $user, 2)->allowed, 'Zwei Stück bei einem Voreintrag');

        // Zwei verschiedene Kurzwaffen: der eine Voreintrag reicht nur für eine Position
        $evaluation = $this->evaluateCart($user, ['GLO-G17-G5' => 1, 'HK-SFP9-SF' => 1]);
        $this->assertFalse($evaluation->allowed());
        $this->assertSame([true, false], array_values(array_map(fn (ComplianceResult $r) => $r->allowed, $evaluation->lineResults)));
        $this->assertContains(ComplianceResult::HINT_UPLOAD_ENTITLEMENT, $evaluation->hints());

        // Bereits verbrauchte Voreinträge zählen nicht mehr
        $ent->update(['quantity_used' => 1]);
        $this->assertFalse($this->check('GLO-G17-G5', $user)->allowed);
    }

    public function test_gelbe_wbk_nur_langwaffen_und_grosskaliber_erst_ab_21(): void
    {
        $young = $this->verifyAge($this->customer('Jung'), now()->subYears(20)->toDateString());
        $this->grantEntitlement($young, EntitlementType::WbkYellowSport);

        $this->assertTrue($this->check('CZ-457-VAR-22', $young)->allowed, 'Kleinkaliber-Langwaffe ab 18');
        $this->assertFalse($this->check('BLA-R8-PRO-308', $young)->allowed, 'Großkaliber erst ab 21 (§ 14 WaffG)');
        $this->assertFalse($this->check('GLO-G17-G5', $young)->allowed, 'Gelbe WBK gilt nicht für Kurzwaffen');

        $older = $this->verifyAge($this->customer('Alt'), now()->subYears(25)->toDateString());
        $this->grantEntitlement($older, EntitlementType::WbkYellowSport);
        $this->assertTrue($this->check('BLA-R8-PRO-308', $older)->allowed);
    }

    public function test_jagdschein_langwaffen_und_langwaffenmunition(): void
    {
        $hunter = $this->verifyAge($this->customer());
        $this->grantEntitlement($hunter, EntitlementType::HuntingLicenseLonggun);

        $this->assertTrue($this->check('BLA-R8-PRO-308', $hunter)->allowed);
        $this->assertTrue($this->check('BLA-CDC-308', $hunter)->allowed);
        $this->assertFalse($this->check('SIG-AMMO-9-124', $hunter)->allowed, 'Kurzwaffenmunition nicht mit Jagdschein');
        $this->assertFalse($this->check('GLO-G17-G5', $hunter)->allowed, 'Kurzwaffe braucht einen Voreintrag');
    }

    public function test_munitionserwerb_gilt_nur_fuer_das_eingetragene_kaliber(): void
    {
        $user = $this->verifyAge($this->customer());
        $this->grantEntitlement($user, EntitlementType::AmmoPermit, ['caliber_id' => $this->caliber('9mm-luger')]);

        $this->assertTrue($this->check('SIG-AMMO-9-124', $user)->allowed);
        $this->assertFalse($this->check('BLA-CDC-308', $user)->allowed);
        $this->assertFalse($this->check('GLO-G17-G5', $user)->allowed, 'Munitionserwerb berechtigt nicht zum Waffenerwerb');
    }

    public function test_abgelaufene_kuenftige_und_verbrauchte_berechtigungen_zaehlen_nicht(): void
    {
        $user = $this->verifyAge($this->customer());
        $ent = $this->grantEntitlement($user, EntitlementType::HuntingLicenseLonggun, ['valid_until' => now()->subDay()]);
        $this->assertFalse($this->check('BLA-R8-PRO-308', $user)->allowed, 'abgelaufen');

        $ent->update(['valid_until' => now()->addYear(), 'valid_from' => now()->addDay()]);
        $this->assertFalse($this->check('BLA-R8-PRO-308', $user)->allowed, 'noch nicht gültig');

        $ent->update(['valid_from' => today(), 'status' => 'expired']);
        $this->assertFalse($this->check('BLA-R8-PRO-308', $user)->allowed, 'Status abgelaufen');

        $ent->update(['status' => 'active']);
        $this->assertTrue($this->check('BLA-R8-PRO-308', $user)->allowed, 'Gegenprobe');
    }

    public function test_behoerdenware_nur_fuer_haendler_und_behoerden(): void
    {
        Product::where('sku', 'GLO-G17-G5')->update(['authority_only' => true]);

        $private = $this->verifyAge($this->customer());
        $this->grantEntitlement($private, EntitlementType::WbkVoreintrag, ['weapon_kind' => 'any']);
        $denied = $this->check('GLO-G17-G5', $private);
        $this->assertFalse($denied->allowed);
        $this->assertSame(ComplianceResult::HINT_CONTACT, $denied->hint);

        $dealer = $this->verifyAge($this->customer('Hans', 'Händler'));
        $dealer->forceFill(['customer_type' => 'dealer'])->save();
        $this->assertFalse($this->check('GLO-G17-G5', $dealer)->allowed, 'Händler ohne geprüfte Händlererlaubnis');

        $this->grantEntitlement($dealer, EntitlementType::Dealer);
        $ok = $this->check('GLO-G17-G5', $dealer->fresh()); // neue Anfrage: frisch geladenes Konto
        $this->assertTrue($ok->allowed);
        $this->assertTrue($ok->requiresReview, 'Auch Händlererwerb wird bei Übergabe geprüft');
    }

    public function test_warenkorb_nimmt_strengste_versandart_und_gemeinsame_zahlungsarten(): void
    {
        config(['shop.payment_methods.paypal.enabled' => true]);
        $user = $this->verifyAge($this->customer());
        $checkout = app(CheckoutService::class);

        $free = $this->evaluateCart($user, ['WAL-CLEAN-UNI' => 1]);
        $this->assertSame(ShippingMode::Standard, $free->shippingMode);
        $this->assertContains(PaymentMethod::PayPal, $free->paymentMethods);
        $this->assertSame(['dhl_standard', 'pickup'], $checkout->shippingMethodsFor($free)->pluck('code')->all());

        // Messer dazu: Alterssichtprüfung, PayPal entfällt (AGB der Zahlungsanbieter)
        $mixed = $this->evaluateCart($user, ['WAL-BWK2' => 1]);
        $this->assertTrue($mixed->allowed());
        $this->assertFalse($mixed->requiresReview);
        $this->assertSame(ShippingMode::AgeCheck, $mixed->shippingMode);
        $this->assertEquals([PaymentMethod::Prepayment, PaymentMethod::CashPickup], $mixed->paymentMethods);
        $this->assertSame(['dhl_age_check', 'pickup'], $checkout->shippingMethodsFor($mixed)->pluck('code')->all());

        // Barzahlung nur bei Abholung
        $dhl = $checkout->shippingMethodsFor($mixed)->firstWhere('code', 'dhl_age_check');
        $pickup = $checkout->shippingMethodsFor($mixed)->firstWhere('code', 'pickup');
        $this->assertEquals([PaymentMethod::Prepayment], $checkout->paymentMethodsFor($mixed, $dhl));
        $this->assertEquals([PaymentMethod::Prepayment, PaymentMethod::CashPickup], $checkout->paymentMethodsFor($mixed, $pickup));

        // Erlaubnispflichtige Waffe: persönliche Übergabe, Freigabe nötig
        $this->grantEntitlement($user, EntitlementType::WbkVoreintrag, ['weapon_kind' => 'any', 'quantity_total' => 1]);
        $weapon = $this->evaluateCart($user, ['GLO-G17-G5' => 1]);
        $this->assertSame(ShippingMode::WeaponPersonal, $weapon->shippingMode);
        $this->assertTrue($weapon->requiresReview);
        $this->assertSame(['weapon_personal_overnight', 'pickup'], $checkout->shippingMethodsFor($weapon)->pluck('code')->all());
    }
}
