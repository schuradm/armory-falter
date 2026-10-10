<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum TradeRequestStatus: string implements FilamentLabel
{
    use HasLabel;

    case New = 'new';
    case InReview = 'in_review';
    case OfferSent = 'offer_sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case ItemReceived = 'item_received';
    case Listed = 'listed';
    case Sold = 'sold';
    case PaidOut = 'paid_out';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Neu',
            self::InReview => 'In Prüfung',
            self::OfferSent => 'Angebot gesendet',
            self::Accepted => 'Angenommen',
            self::Declined => 'Abgelehnt',
            self::ItemReceived => 'Waffe eingegangen',
            self::Listed => 'Bei egun eingestellt',
            self::Sold => 'Verkauft',
            self::PaidOut => 'Ausgezahlt',
            self::Closed => 'Abgeschlossen',
        };
    }
}
