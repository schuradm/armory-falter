<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Anmeldungen';

    protected static ?string $modelLabel = 'Anmeldung';

    protected static ?string $pluralModelLabel = 'Anmeldungen';

    public const STATUSES = ['registered' => 'Angemeldet', 'waitlist' => 'Warteliste', 'cancelled' => 'Storniert'];

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->registrations()->where('status', 'registered')->sum('persons');
    }

    public function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')->label('Name')->required()->maxLength(160),
            Forms\Components\TextInput::make('email')->label('E-Mail')->email()->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('event_id', $this->getOwnerRecord()->getKey())),
            Forms\Components\TextInput::make('phone')->label('Telefon')->tel()->maxLength(40),
            Forms\Components\TextInput::make('persons')->label('Personen')->numeric()->minValue(1)->maxValue(20)->default(1)->required(),
            Forms\Components\Select::make('status')->label('Status')->options(self::STATUSES)->default('registered')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Angemeldet am')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon')->toggleable(),
                Tables\Columns\TextColumn::make('persons')->label('Personen'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'registered' => 'success', 'waitlist' => 'warning', default => 'danger' }),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::STATUSES)])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('csv')->label('Teilnehmerliste (CSV)')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn () => $this->exportCsv()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirm')->label('Von Warteliste anmelden')->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'registered'])),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function exportCsv(): StreamedResponse
    {
        $event = $this->getOwnerRecord();

        return response()->streamDownload(function () use ($event) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'E-Mail', 'Telefon', 'Personen', 'Status', 'Angemeldet am'], ';');
            $event->registrations()->orderBy('created_at')->each(function ($r) use ($out) {
                fputcsv($out, [$r->name, $r->email, $r->phone, $r->persons, self::STATUSES[$r->status] ?? $r->status, $r->created_at?->format('d.m.Y H:i')], ';');
            });
            fclose($out);
        }, 'teilnehmer-'.$event->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
