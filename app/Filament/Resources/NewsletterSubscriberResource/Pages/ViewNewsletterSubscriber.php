<?php

namespace App\Filament\Resources\NewsletterSubscriberResource\Pages;

use App\Filament\Resources\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use App\Services\AuditLogger;
use App\Services\Newsletter\NewsletterService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNewsletterSubscriber extends ViewRecord
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('unsubscribe')->label('Abmelden')->icon('heroicon-o-x-circle')->color('warning')
                ->requiresConfirmation()
                ->visible(fn (NewsletterSubscriber $record) => $record->status !== 'unsubscribed')
                ->action(function (NewsletterSubscriber $record) {
                    app(NewsletterService::class)->unsubscribe($record);
                    app(AuditLogger::class)->log('newsletter.unsubscribed_by_staff', null, [], ['subscriber_id' => $record->id]);
                }),
            Actions\DeleteAction::make()->label('Löschen (DSGVO)')
                ->modalDescription('Der Abonnent und sein Einwilligungsnachweis werden endgültig gelöscht. Für eine einfache Abmeldung bitte „Abmelden“ verwenden.'),
        ];
    }
}
