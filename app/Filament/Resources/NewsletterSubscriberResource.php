<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Newsletter-Abonnenten: lesen, exportieren, abmelden, löschen.
 * Aufnahme nur per Double-Opt-In (Aktion „Einladen“ in der Liste), nie direkt als bestätigt.
 */
class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Abonnent';

    protected static ?string $pluralModelLabel = 'Newsletter-Abonnenten';

    protected static ?string $navigationLabel = 'Abonnenten';

    protected static ?int $navigationSort = 40;

    public const STATUSES = ['pending' => 'Unbestätigt', 'confirmed' => 'Bestätigt', 'unsubscribed' => 'Abgemeldet'];

    public const INTERESTS = ['jagd' => 'Jagd', 'sport' => 'Sport', 'optik' => 'Optik', 'sammler' => 'Sammler', 'behoerde' => 'Behörde'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Abonnent')->columns(3)->schema([
                Infolists\Components\TextEntry::make('email')->label('E-Mail')->copyable(),
                Infolists\Components\TextEntry::make('first_name')->label('Vorname')->placeholder('–'),
                Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'confirmed' => 'success', 'pending' => 'warning', default => 'gray' }),
                Infolists\Components\TextEntry::make('interests')->label('Interessen')->badge()
                    ->formatStateUsing(fn ($state) => self::INTERESTS[$state] ?? $state)->placeholder('–'),
                Infolists\Components\TextEntry::make('source')->label('Quelle')->placeholder('–'),
                Infolists\Components\TextEntry::make('welcome_step')->label('Willkommensstrecke (Schritt)'),
            ]),
            Infolists\Components\Section::make('Einwilligungsnachweis (Double-Opt-In)')->columns(3)->schema([
                Infolists\Components\TextEntry::make('opt_in_at')->label('Anmeldung')->dateTime('d.m.Y H:i:s')->placeholder('–'),
                Infolists\Components\TextEntry::make('confirmed_at')->label('Bestätigt')->dateTime('d.m.Y H:i:s')->placeholder('–'),
                Infolists\Components\TextEntry::make('unsubscribed_at')->label('Abgemeldet')->dateTime('d.m.Y H:i:s')->placeholder('–'),
                Infolists\Components\TextEntry::make('opt_in_ip_hash')->label('IP-Hash Anmeldung')->fontFamily('mono')->limit(16)->placeholder('–'),
                Infolists\Components\TextEntry::make('confirmed_ip_hash')->label('IP-Hash Bestätigung')->fontFamily('mono')->limit(16)->placeholder('–'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('first_name')->label('Vorname')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'confirmed' => 'success', 'pending' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('interests')->label('Interessen')->badge()
                    ->formatStateUsing(fn ($state) => self::INTERESTS[$state] ?? $state)->toggleable(),
                Tables\Columns\TextColumn::make('source')->label('Quelle')->toggleable(),
                Tables\Columns\TextColumn::make('confirmed_at')->label('Bestätigt am')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')->label('Quelle')
                    ->options(['footer' => 'Footer', 'ratgeber' => 'Ratgeber (Leadmagnet)', 'backend' => 'Backend-Einladung']),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()->label('Löschen (DSGVO)')])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
            'view' => Pages\ViewNewsletterSubscriber::route('/{record}'),
        ];
    }
}
