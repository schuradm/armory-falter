<?php

namespace App\Filament\Resources\NewsletterSubscriberResource\Pages;

use App\Filament\Resources\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use App\Services\AuditLogger;
use App\Services\Newsletter\NewsletterService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    public function getTabs(): array
    {
        return [
            'confirmed' => Tab::make('Bestätigt')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed')),
            'pending' => Tab::make('Unbestätigt')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'unsubscribed' => Tab::make('Abgemeldet')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'unsubscribed')),
            'all' => Tab::make('Alle'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')->label('CSV-Export')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => $this->exportCsv()),

            // Kein direktes Anlegen als „bestätigt“: Aufnahme nur per Double-Opt-In mit dokumentiertem Anlass.
            Actions\Action::make('invite')->label('Per Double-Opt-In einladen')->icon('heroicon-o-envelope')
                ->modalDescription('Die Person erhält eine Bestätigungs-E-Mail und wird erst nach Klick auf den Link aufgenommen (§ 7 UWG, DSGVO).')
                ->form([
                    Forms\Components\TextInput::make('email')->label('E-Mail')->email()->required(),
                    Forms\Components\TextInput::make('first_name')->label('Vorname')->maxLength(100),
                    Forms\Components\CheckboxList::make('interests')->label('Interessen')->options(NewsletterSubscriberResource::INTERESTS)->columns(3),
                    Forms\Components\Textarea::make('consent_note')->label('Anlass / Nachweis der Bitte um Aufnahme')->required()->maxLength(500)
                        ->placeholder('z. B. „Kunde bat am 12.10. im Laden um Newsletter, Formular unterschrieben“'),
                    Forms\Components\Checkbox::make('consent')->label('Die Person hat ausdrücklich um den Newsletter gebeten.')->accepted(),
                ])
                ->action(function (array $data) {
                    $sub = app(NewsletterService::class)->subscribe($data['email'], $data['interests'] ?? [], $data['first_name'] ?: null, 'backend');
                    app(AuditLogger::class)->log('newsletter.invited_by_staff', null, [], [
                        'subscriber_id' => $sub->id, 'note' => $data['consent_note'],
                    ]);
                    Notification::make()->success()
                        ->title($sub->status === 'confirmed' ? 'Bereits bestätigt – Interessen ergänzt.' : 'Bestätigungs-E-Mail wurde versendet.')
                        ->send();
                }),
        ];
    }

    protected function exportCsv(): StreamedResponse
    {
        $query = $this->getFilteredTableQuery()->reorder('id');
        $interests = NewsletterSubscriberResource::INTERESTS;
        app(AuditLogger::class)->log('newsletter.exported', null, [], ['tab' => $this->activeTab]);

        return response()->streamDownload(function () use ($query, $interests) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['E-Mail', 'Vorname', 'Status', 'Interessen', 'Quelle', 'Anmeldung', 'Bestätigt', 'Abgemeldet'], ';');
            $query->chunkById(500, function ($chunk) use ($out, $interests) {
                foreach ($chunk as $s) {
                    /** @var NewsletterSubscriber $s */
                    fputcsv($out, [
                        $s->email, $s->first_name, NewsletterSubscriberResource::STATUSES[$s->status] ?? $s->status,
                        collect($s->interests ?? [])->map(fn ($i) => $interests[$i] ?? $i)->implode(', '),
                        $s->source, $s->opt_in_at?->format('d.m.Y H:i'), $s->confirmed_at?->format('d.m.Y H:i'), $s->unsubscribed_at?->format('d.m.Y H:i'),
                    ], ';');
                }
            });
            fclose($out);
        }, 'newsletter-abonnenten-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
