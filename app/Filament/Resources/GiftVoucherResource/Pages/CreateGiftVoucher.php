<?php

namespace App\Filament\Resources\GiftVoucherResource\Pages;

use App\Filament\Resources\GiftVoucherResource;
use App\Services\Vouchers\GiftVoucherService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGiftVoucher extends CreateRecord
{
    protected static string $resource = GiftVoucherResource::class;

    protected static ?string $title = 'Geschenkgutschein ausstellen';

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(GiftVoucherService::class);
        $voucher = $service->create((int) $data['initial_value'], null, [
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_email' => $data['recipient_email'] ?? null,
            'message' => $data['message'] ?? null,
        ], $data['note'] ?? 'Manuell ausgestellt', auth()->user());
        $service->send($voucher); // PDF erzeugen, bei E-Mail-Adresse versenden

        return $voucher;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
