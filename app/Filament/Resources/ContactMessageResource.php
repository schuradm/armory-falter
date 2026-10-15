<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** Kontaktanfragen aus dem Formular: nur lesen und als erledigt markieren. */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Service';

    protected static ?string $modelLabel = 'Kontaktanfrage';

    protected static ?string $pluralModelLabel = 'Kontaktanfragen';

    protected static ?string $navigationLabel = 'Kontaktanfragen';

    protected static ?int $navigationSort = 10;

    public const STATUSES = ['new' => 'Neu', 'handled' => 'Erledigt', 'spam' => 'Spam'];

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
        $count = ContactMessage::where('status', 'new')->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Absender')->columns(3)->schema([
                Infolists\Components\TextEntry::make('name')->label('Name'),
                Infolists\Components\TextEntry::make('email')->label('E-Mail')->copyable()->url(fn ($record) => 'mailto:'.$record->email),
                Infolists\Components\TextEntry::make('phone')->label('Telefon')->placeholder('–')->url(fn ($record) => $record->phone ? 'tel:'.preg_replace('/\s+/', '', $record->phone) : null),
                Infolists\Components\TextEntry::make('created_at')->label('Eingegangen')->dateTime('d.m.Y H:i'),
                Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => $state === 'new' ? 'warning' : 'success'),
            ]),
            Infolists\Components\Section::make('Nachricht')->schema([
                Infolists\Components\TextEntry::make('subject')->label('Betreff')->weight('bold'),
                Infolists\Components\TextEntry::make('message')->label('Nachricht')->prose()
                    ->formatStateUsing(fn (string $state) => nl2br(e($state)))->html(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (ContactMessage $r) => self::getUrl('view', ['record' => $r]))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Eingegangen')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('subject')->label('Betreff')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => $state === 'new' ? 'warning' : ($state === 'spam' ? 'danger' : 'success')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('handled')->label('Erledigt')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (ContactMessage $r) => $r->status === 'new')
                    ->action(fn (ContactMessage $r) => $r->update(['status' => 'handled'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('handled')->label('Als erledigt markieren')->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => ContactMessage::whereKey($records->modelKeys())->update(['status' => 'handled']))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('spam')->label('Als Spam markieren')->icon('heroicon-o-no-symbol')
                        ->action(fn (Collection $records) => ContactMessage::whereKey($records->modelKeys())->update(['status' => 'spam']))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
        ];
    }
}
