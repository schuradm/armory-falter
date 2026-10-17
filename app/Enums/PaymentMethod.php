<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

/**
 * PayPal, Klarna und viele Karten-Acquirer schließen Waffen und Munition in ihren
 * Nutzungsbedingungen aus. Welche Methode für welche Rechtsklasse erlaubt ist,
 * steht in legal_classes.allowed_payment_codes und wird im Admin gepflegt.
 */
enum PaymentMethod: string implements FilamentLabel
{
    use HasLabel;

    case Prepayment = 'prepayment';
    case CashPickup = 'cash_pickup';
    case PayPal = 'paypal';
    case Card = 'card';
    case Sofort = 'sofort';
    case Klarna = 'klarna';
    case WeaponPsp = 'psp_weapon';
    // Kein Zahlungsanbieter: vollständig per Geschenkgutschein bezahlt. Nie wählbar, setzt nur der Checkout.
    case GiftVoucher = 'gift_voucher';

    /** Zahlarten, die Kunde und Backend wählen bzw. Rechtsklassen zuordnen können (ohne Gutschein). */
    public static function selectable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $m) => $m !== self::GiftVoucher));
    }

    /** @return array<string,string> value => label, für Filament-Auswahlfelder */
    public static function selectableOptions(): array
    {
        return collect(self::selectable())->mapWithKeys(fn (self $m) => [$m->value => $m->label()])->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Prepayment => 'Vorkasse (Überweisung)',
            self::CashPickup => 'Barzahlung bei Abholung',
            self::PayPal => 'PayPal',
            self::Card => 'Kreditkarte',
            self::Sofort => 'Sofortüberweisung (Klarna)',
            self::Klarna => 'Klarna Rechnung / Ratenkauf',
            self::WeaponPsp => 'Kartenzahlung (waffenfreundlicher Zahlungsanbieter)',
            self::GiftVoucher => 'Geschenkgutschein',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Prepayment => 'Sie erhalten unsere Bankverbindung mit der Bestellbestätigung. Bei erlaubnispflichtiger Ware bitte erst nach unserer Freigabe überweisen.',
            self::CashPickup => 'Sie zahlen bar oder mit EC-Karte bei Abholung in Wolfschlugen.',
            self::PayPal => 'Weiterleitung zu PayPal. Nur für frei verkäufliche Artikel.',
            self::Card => 'Visa, Mastercard. Nur für frei verkäufliche Artikel.',
            self::Sofort => 'Direkt über Ihr Online-Banking bezahlen, ohne Registrierung. Nur für frei verkäufliche Artikel.',
            self::Klarna => 'Erst kaufen, dann in 30 Tagen oder in Raten bezahlen. Nur für frei verkäufliche Artikel.',
            self::WeaponPsp => 'Sichere Kartenzahlung über unseren Zahlungspartner.',
            self::GiftVoucher => 'Der Gesamtbetrag ist durch Ihre Geschenkgutscheine gedeckt.',
        };
    }

    public function requiresOnlineCapture(): bool
    {
        return in_array($this, [self::PayPal, self::Card, self::Sofort, self::Klarna, self::WeaponPsp], true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Prepayment, self::Sofort => 'bank',
            self::GiftVoucher => 'sparkles',
            self::CashPickup => 'euro',
            self::PayPal => 'paypal',
            default => 'lock',
        };
    }

    /**
     * Aktiv, wenn im Backend (Einstellungen → Zahlarten) eingeschaltet – ohne Backend-Wert gilt config/shop.php.
     * Online-Zahlarten bleiben ohne Zugangsdaten immer aus, damit der Checkout nicht ins Leere läuft.
     */
    public function isEnabled(): bool
    {
        if ($this === self::GiftVoucher) {
            return true;
        }
        $override = app(\App\Support\Settings::class)->get("payment_methods.{$this->value}");
        if ($override === null) {
            return (bool) config("shop.payment_methods.{$this->value}.enabled", false);
        }

        return (bool) $override && $this->isConfigured();
    }

    /** Sind die nötigen Zugangsdaten bzw. eine Anbindung vorhanden? */
    public function isConfigured(): bool
    {
        return match ($this) {
            self::PayPal => filled(config('shop.payment_methods.paypal.client_id')) && filled(config('shop.payment_methods.paypal.secret')),
            self::Sofort, self::Klarna => filled(config('shop.payment_methods.klarna.username')) && filled(config('shop.payment_methods.klarna.password')),
            // Noch keine Gateway-Anbindung: nur über die .env (CARD_ENABLED / WEAPON_PSP_ENABLED) einschaltbar
            self::Card, self::WeaponPsp => (bool) config("shop.payment_methods.{$this->value}.enabled", false),
            default => true,
        };
    }
}
