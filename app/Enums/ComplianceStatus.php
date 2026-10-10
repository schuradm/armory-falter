<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum ComplianceStatus: string implements FilamentLabel
{
    use HasLabel;

    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Nicht erforderlich',
            self::Pending => 'Offen',
            self::Approved => 'Freigegeben',
            self::Rejected => 'Abgelehnt',
        };
    }
}
