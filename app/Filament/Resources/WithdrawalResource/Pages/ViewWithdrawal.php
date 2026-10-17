<?php

namespace App\Filament\Resources\WithdrawalResource\Pages;

use App\Filament\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWithdrawal extends ViewRecord
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('processed')->label('Als bearbeitet markieren')->icon('heroicon-o-check')->color('success')
                ->visible(fn (Withdrawal $record) => $record->status === 'received')
                ->action(fn (Withdrawal $record) => $record->update(['status' => 'processed', 'processed_at' => now()])),
            Actions\Action::make('reply')->label('Per E-Mail antworten')->icon('heroicon-o-envelope')->color('gray')
                ->url(fn (Withdrawal $record) => 'mailto:'.$record->email.'?subject='.rawurlencode("Ihr Widerruf {$record->reference}")),
        ];
    }
}
