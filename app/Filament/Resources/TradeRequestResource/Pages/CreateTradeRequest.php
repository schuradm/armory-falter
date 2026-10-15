<?php

namespace App\Filament\Resources\TradeRequestResource\Pages;

use App\Enums\TradeRequestType;
use App\Filament\Resources\TradeRequestResource;
use App\Services\NumberGenerator;
use Filament\Resources\Pages\CreateRecord;

/** Manuelle Erfassung, z. B. nach telefonischer Anfrage oder im Laden. */
class CreateTradeRequest extends CreateRecord
{
    protected static string $resource = TradeRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $type = $data['type'] instanceof TradeRequestType ? $data['type'] : TradeRequestType::from($data['type']);
        $data['reference'] = app(NumberGenerator::class)->next('trade-'.$type->value, $type->prefix(), 4);
        $data['privacy_accepted_at'] = now(); // Einwilligung bei manueller Erfassung mündlich/schriftlich einholen

        return $data;
    }
}
