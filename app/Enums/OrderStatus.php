<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum OrderStatus: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case PendingPayment = 'pending_payment';
    case PendingCompliance = 'pending_compliance';
    case ComplianceHold = 'compliance_hold';
    case Paid = 'paid';
    case Processing = 'processing';
    case ReadyForPickup = 'ready_for_pickup';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Wartet auf Zahlung',
            self::PendingCompliance => 'Berechtigungsprüfung',
            self::ComplianceHold => 'Unterlagen nachreichen',
            self::Paid => 'Bezahlt',
            self::Processing => 'In Bearbeitung',
            self::ReadyForPickup => 'Abholbereit',
            self::Shipped => 'Versendet',
            self::Delivered => 'Zugestellt',
            self::Completed => 'Abgeschlossen',
            self::Cancelled => 'Storniert',
            self::Refunded => 'Erstattet',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingCompliance, self::ComplianceHold => 'warning',
            self::Cancelled, self::Refunded => 'danger',
            self::Shipped, self::Delivered, self::Completed, self::ReadyForPickup => 'success',
            default => 'gray',
        };
    }
}
