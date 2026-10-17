<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftVoucherResource\Pages;
use App\Filament\Resources\GiftVoucherResource\RelationManagers;
use App\Filament\Support\Euro;
use App\Models\GiftVoucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Geschenkgutscheine (Mehrzweck-Gutscheine): Restguthaben, Ablauf, Einlösungsverlauf. */
class GiftVoucherResource extends Resource
{
    protected static ?string $model = GiftVoucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Verkauf';

    protected static ?string $modelLabel = 'Geschenkgutschein';

    protected static ?string $pluralModelLabel = 'Geschenkgutscheine';

    protected static ?string $navigationLabel = 'Geschenkgutscheine';

    protected static ?int $navigationSort = 20;

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'recipient_email', 'recipient_name'];
    }

    /** Manuelle Ausgabe (z. B. Verkauf im Laden, Kulanz). Code und Ablauf erzeugt GiftVoucherService. */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Euro::input('initial_value', 'Wert')->required(),
                Forms\Components\TextInput::make('recipient_name')->label('Beschenkter (Name)')->maxLength(100),
                Forms\Components\TextInput::make('recipient_email')->label('E-Mail für den Versand')->email()->maxLength(255)
                    ->helperText('Leer = kein E-Mail-Versand, PDF über „PDF herunterladen“ ausdrucken.'),
                Forms\Components\Textarea::make('message')->label('Nachricht auf dem Gutschein')->rows(2)->maxLength(300),
                Forms\Components\TextInput::make('note')->label('Interner Vermerk')->maxLength(255)->placeholder('z. B. Verkauf im Laden, Beleg 123'),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()->columns(4)->schema([
                Infolists\Components\TextEntry::make('code')->label('Code')->formatStateUsing(fn (GiftVoucher $record) => $record->formattedCode())->copyable()->weight('bold'),
                Euro::entry('initial_value', 'Wert'),
                Euro::entry('balance', 'Restguthaben')->weight('bold'),
                Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                    ->getStateUsing(fn (GiftVoucher $record) => $record->statusLabel())
                    ->color(fn (GiftVoucher $record) => $record->isRedeemable() ? 'success' : 'gray'),
                Infolists\Components\TextEntry::make('created_at')->label('Ausgestellt')->date('d.m.Y'),
                Infolists\Components\TextEntry::make('expires_at')->label('Gültig bis')->date('d.m.Y'),
                Infolists\Components\TextEntry::make('order.order_number')->label('Gekauft mit')->placeholder('manuell')
                    ->url(fn (GiftVoucher $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                Infolists\Components\TextEntry::make('purchaser.email')->label('Käufer')->placeholder('—'),
                Infolists\Components\TextEntry::make('recipient_name')->label('Beschenkter')->placeholder('—'),
                Infolists\Components\TextEntry::make('recipient_email')->label('Versand an')->placeholder('Käufer'),
                Infolists\Components\TextEntry::make('sent_at')->label('Versendet')->dateTime('d.m.Y H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('message')->label('Nachricht')->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order:id,order_number', 'purchaser:id,email']))
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable()->weight('bold')
                    ->formatStateUsing(fn (GiftVoucher $record) => '…'.substr($record->code, -8))->tooltip('Vollständiger Code in der Detailansicht'),
                Euro::column('initial_value', 'Wert')->sortable(),
                Euro::column('balance', 'Restguthaben')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->getStateUsing(fn (GiftVoucher $record) => $record->statusLabel())
                    ->color(fn (string $state) => match ($state) { 'Aktiv' => 'success', 'Eingelöst' => 'gray', default => 'danger' }),
                Tables\Columns\TextColumn::make('expires_at')->label('Gültig bis')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')->label('Bestellung')->placeholder('manuell'),
                Tables\Columns\TextColumn::make('recipient_email')->label('Empfänger')->placeholder(fn (GiftVoucher $record) => $record->purchaser?->email)->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Ausgestellt')->date('d.m.Y')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(GiftVoucher::STATUSES),
                Tables\Filters\Filter::make('open_balance')->label('Mit Restguthaben')
                    ->query(fn (Builder $q) => $q->where('status', 'active')->where('balance', '>', 0)->whereDate('expires_at', '>=', today())),
            ])
            ->actions([Tables\Actions\ViewAction::make()->label('Öffnen')])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\TransactionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiftVouchers::route('/'),
            'create' => Pages\CreateGiftVoucher::route('/create'),
            'view' => Pages\ViewGiftVoucher::route('/{record}'),
        ];
    }
}
