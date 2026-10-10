<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum DocumentStatus: string implements FilamentLabel, HasColor
{
    use HasLabel;

    case Uploaded = 'uploaded';
    case PrecheckPassed = 'precheck_passed';
    case PrecheckFlagged = 'precheck_flagged';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Hochgeladen',
            self::PrecheckPassed => 'Vorprüfung ok – wartet auf Freigabe',
            self::PrecheckFlagged => 'Vorprüfung auffällig – wartet auf Prüfung',
            self::Approved => 'Freigegeben',
            self::Rejected => 'Abgelehnt',
            self::Expired => 'Abgelaufen',
            self::Revoked => 'Widerrufen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Approved => 'success',
            self::Rejected, self::Revoked, self::Expired => 'danger',
            self::PrecheckFlagged => 'warning',
            default => 'gray',
        };
    }

    public function awaitingReview(): bool
    {
        return in_array($this, [self::Uploaded, self::PrecheckPassed, self::PrecheckFlagged], true);
    }
}
