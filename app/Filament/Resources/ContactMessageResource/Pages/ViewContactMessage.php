<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')->label('Per E-Mail antworten')->icon('heroicon-o-envelope')
                ->url(fn (ContactMessage $record) => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: '.$record->subject)),
            Actions\Action::make('handled')->label('Als erledigt markieren')->icon('heroicon-o-check')->color('success')
                ->visible(fn (ContactMessage $record) => $record->status !== 'handled')
                ->action(function (ContactMessage $record) {
                    $record->update(['status' => 'handled']);
                    $this->redirect(ContactMessageResource::getUrl('index'));
                }),
            Actions\Action::make('reopen')->label('Wieder öffnen')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                ->visible(fn (ContactMessage $record) => $record->status === 'handled')
                ->action(fn (ContactMessage $record) => $record->update(['status' => 'new'])),
            Actions\DeleteAction::make(),
        ];
    }
}
