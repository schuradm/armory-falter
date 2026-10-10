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
            self::CashPickup => 'euro',
            self::PayPal => 'paypal',
            default => 'lock',
        };
    }

    public function isEnabled(): bool
    {
        return (bool) config("shop.payment_methods.{$this->value}.enabled", false);
    }
}
