<?php

namespace App\Services\Compliance;

use App\Enums\EntitlementType;
use App\Models\CustomerEntitlement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Prüft, ob ein Kunde ein Produkt in einer bestimmten Menge erwerben darf.
 *
 * Die Prüfung ist eine technische Vorprüfung. Bei erlaubnispflichtiger Ware bleibt
 * die Freigabe durch einen Sachbearbeiter Pflicht (requiresReview = true), weil der
 * Händler die Erwerbsberechtigung vor dem Überlassen selbst feststellen muss (§ 34 WaffG).
 */
class ComplianceService
{
    /**
     * @param  array<int,int>  $alreadyConsumed  Bereits im selben Warenkorb verbrauchte Menge je Entitlement-ID
     */
    public function evaluate(Product $product, ?User $user, int $quantity = 1, array &$alreadyConsumed = []): ComplianceResult
    {
        $product->loadMissing('legalClass', 'caliber');
        $legal = $product->legalClass;

        if ($product->authority_only && ! $user?->isVerifiedAuthorityOrDealer()) {
            return ComplianceResult::deny('Dieser Artikel ist nur für Behörden und Händler erhältlich.', ComplianceResult::HINT_CONTACT);
        }

        $needsIdentity = $legal->min_age > 0 || $legal->requires_entitlement;
        if (! $needsIdentity) {
            return ComplianceResult::ok();
        }

        if (! $user) {
            return ComplianceResult::deny('Für diesen Artikel ist ein Kundenkonto mit geprüften Unterlagen erforderlich.', ComplianceResult::HINT_LOGIN);
        }

        if ($legal->min_age > 0) {
            $age = $user->verifiedAge();
            if ($age === null) {
                return ComplianceResult::deny("Abgabe erst ab {$legal->min_age} Jahren. Bitte bestätigen Sie Ihr Alter mit einem Ausweisdokument in Ihrem Kundenkonto.", ComplianceResult::HINT_VERIFY_AGE);
            }
            if ($age < $legal->min_age) {
                return ComplianceResult::deny("Abgabe erst ab {$legal->min_age} Jahren.");
            }
        }

        if (! $legal->requires_entitlement) {
            return ComplianceResult::ok();
        }

        if ($user->isAuthorityOrDealer() && $this->hasActive($user, [EntitlementType::Dealer, EntitlementType::Authority])) {
            return ComplianceResult::ok(requiresReview: true, reasons: ['Händler-/Behördenerwerb: Erlaubnis bei Übergabe prüfen.']);
        }

        $entitlement = $this->findMatchingEntitlement($product, $user, $quantity, $alreadyConsumed);
        if (! $entitlement) {
            return ComplianceResult::deny(
                'Für diesen Artikel benötigen wir eine passende, geprüfte Erwerbsberechtigung (z. B. WBK mit Voreintrag, Jagdschein oder Munitionserwerbsberechtigung).',
                ComplianceResult::HINT_UPLOAD_ENTITLEMENT,
            );
        }

        $alreadyConsumed[$entitlement->id] = ($alreadyConsumed[$entitlement->id] ?? 0) + $quantity;

        return ComplianceResult::ok(requiresReview: true, entitlement: $entitlement, reasons: [
            'Vorprüfung bestanden mit: '.$entitlement->describe(),
            'Die Bestellung wird vor dem Versand durch unsere Waffensachbearbeitung freigegeben.',
        ]);
    }

    /** @param list<EntitlementType> $types */
    private function hasActive(User $user, array $types): bool
    {
        return $user->activeEntitlements()->whereIn('type', array_map(fn ($t) => $t->value, $types))->exists();
    }

    private function findMatchingEntitlement(Product $product, User $user, int $quantity, array $alreadyConsumed): ?CustomerEntitlement
    {
        $accepted = array_map(fn (EntitlementType $t) => $t->value, $product->legalClass->acceptedEntitlementTypes());

        /** @var Collection<int,CustomerEntitlement> $candidates */
        $candidates = $user->activeEntitlements()
            ->with('caliber')
            ->whereIn('type', $accepted)
            ->orderByRaw('valid_until IS NULL, valid_until ASC') // zuerst die bald ablaufenden verbrauchen
            ->get();

        foreach ($candidates as $ent) {
            $remaining = $ent->remaining();
            if ($remaining !== null && $remaining - ($alreadyConsumed[$ent->id] ?? 0) < $quantity) {
                continue;
            }
            if ($this->fits($ent, $product, $user)) {
                return $ent;
            }
        }

        return null;
    }

    private function fits(CustomerEntitlement $ent, Product $product, User $user): bool
    {
        $isAmmo = $product->product_type->value === 'ammo';
        $kind = $product->weapon_kind;
        $caliber = $product->caliber;

        return match ($ent->type) {
            // Voreintrag: Waffenart und (falls eingetragen) Kaliber müssen passen
            EntitlementType::WbkVoreintrag => ! $isAmmo
                && ($ent->weapon_kind === null || $ent->weapon_kind === 'any' || $ent->weapon_kind === $kind)
                && ($ent->caliber_id === null || $ent->caliber_id === $product->caliber_id),

            // Gelbe WBK: nur Langwaffen (Einzellader/Repetierer – Bauart prüft der Sachbearbeiter).
            // Großkaliber für Sportschützen erst ab 21 (§ 14 WaffG).
            EntitlementType::WbkYellowSport => ! $isAmmo && $kind === 'langwaffe'
                && ! ($caliber?->is_large_bore && ($user->verifiedAge() ?? 0) < 21),

            // Rote WBK: Sammelthema prüft der Sachbearbeiter
            EntitlementType::WbkRedCollector => ! $isAmmo,

            // Jagdschein: Langwaffen und Langwaffenmunition ohne Voreintrag (§ 13 WaffG)
            EntitlementType::HuntingLicenseLonggun => $isAmmo
                ? (bool) $caliber?->isLonggunCaliber()
                : $kind === 'langwaffe' || $product->product_type->value === 'accessory',

            // Munitionserwerb: kaliberbezogen
            EntitlementType::AmmoPermit => $isAmmo && $ent->caliber_id !== null && $ent->caliber_id === $product->caliber_id,

            EntitlementType::Dealer, EntitlementType::Authority => true,
        };
    }
}
