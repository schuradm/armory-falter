<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Online erklärte Widerrufe (Widerrufsbutton): nur lesen und als bearbeitet markieren. */
class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'Verkauf';

    protected static ?string $modelLabel = 'Widerruf';

    protected static ?string $pluralModelLabel = 'Widerrufe';

    protected static ?int $navigationSort = 5;

    public const STATUSES = ['received' => 'Eingegangen', 'processed' => 'Bearbeitet'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Withdrawal::where('status', 'received')->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Widerrufserklärung')->columns(3)->schema([
                Infolists\Components\TextEntry::make('reference')->label('Vorgang')->weight('bold')->copyable(),
                Infolists\Components\TextEntry::make('declared_at')->label('Erklärt am')->dateTime('d.m.Y H:i:s'),
                Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => $state === 'received' ? 'danger' : 'success'),
                Infolists\Components\TextEntry::make('name')->label('Name'),
                Infolists\Components\TextEntry::make('email')->label('E-Mail')->copyable()->url(fn ($record) => 'mailto:'.$record->email),
                Infolists\Components\TextEntry::make('order_number')->label('Bestellnummer')
                    ->url(fn ($record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null)
                    ->helperText(fn ($record) => $record->order_id ? null : 'Keiner Bestellung des Kunden zugeordnet, bitte prüfen.'),
                Infolists\Components\TextEntry::make('items')->label('Artikel')->placeholder('gesamte Bestellung')->columnSpanFull(),
                Infolists\Components\TextEntry::make('message')->label('Nachricht')->placeholder('–')->columnSpanFull(),
                Infolists\Components\TextEntry::make('processed_at')->label('Bearbeitet am')->dateTime('d.m.Y H:i')->placeholder('–'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('declared_at', 'desc')
            ->recordUrl(fn (Withdrawal $record) => self::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('declared_at')->label('Erklärt am')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('reference')->label('Vorgang')->searchable(),
                Tables\Columns\TextColumn::make('order_number')->label('Bestellung')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => $state === 'received' ? 'danger' : 'success'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('processed')->label('Bearbeitet')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Withdrawal $record) => $record->status === 'received')
                    ->action(fn (Withdrawal $record) => $record->update(['status' => 'processed', 'processed_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
            'view' => Pages\ViewWithdrawal::route('/{record}'),
        ];
    }
}
