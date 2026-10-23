<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\WeaponBookEntryResource\Pages;
use App\Models\WeaponBookEntry;
use App\Models\WeaponItem;
use App\Support\BlindIndex;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Elektronisches Waffenhandelsbuch (§ 23 WaffG). Einträge sind unveränderlich:
 * Korrektur nur per Gegenbuchung, nachträglich pflegbar ist nur die NWR-Meldung.
 */
class WeaponBookEntryResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = WeaponBookEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Kunden & Prüfung';

    protected static ?string $modelLabel = 'Waffenbuch-Eintrag';

    protected static ?string $pluralModelLabel = 'Waffenhandelsbuch';

    protected static ?string $navigationLabel = 'Waffenhandelsbuch';

    protected static ?int $navigationSort = 50;

    public const DIRECTIONS = ['in' => 'Zugang (Erwerb)', 'out' => 'Abgang (Überlassung)'];

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function weaponLabel(WeaponItem $item): string
    {
        return ($item->product->name ?? 'Waffe')." – SN {$item->serial_number}";
    }

    public static function form(Form $form): Form
    {
        $locked = fn (string $operation) => $operation === 'edit';

        return $form->schema([
            Forms\Components\Section::make('Buchung')
                ->description('Nach dem Speichern nicht mehr änderbar. Fehler bitte per Gegenbuchung korrigieren.')
                ->columns(2)->schema([
                    Forms\Components\Select::make('weapon_item_id')->label('Waffe')->required()->disabled($locked)
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => WeaponItem::query()->with('product:id,name')
                            ->where(fn (Builder $query) => $query->where('serial_bidx', BlindIndex::make($search))
                                ->orWhereHas('product', fn (Builder $p) => $p->where('name', 'like', "%{$search}%")))
                            ->limit(30)->get()->mapWithKeys(fn (WeaponItem $i) => [$i->id => self::weaponLabel($i)])->all())
                        ->getOptionLabelUsing(fn ($value) => ($i = WeaponItem::with('product:id,name')->find($value)) ? self::weaponLabel($i) : null)
                        ->helperText('Suche: exakte Seriennummer oder Produktname.'),
                    Forms\Components\Select::make('direction')->label('Richtung')->options(self::DIRECTIONS)->required()->disabled($locked),
                    Forms\Components\DateTimePicker::make('booked_at')->label('Buchungsdatum')->required()->default(now())->seconds(false)->maxDate(now()->endOfDay())->disabled($locked),
                    Forms\Components\TextInput::make('permit_reference')->label('Erlaubnis (WBK-Nr. / Jagdschein-Nr.)')->maxLength(120)->disabled($locked),
                    Forms\Components\Select::make('order_id')->label('Bestellung')->relationship('order', 'order_number')->searchable()->disabled($locked),
                ]),
            Forms\Components\Section::make('Erwerber / Überlasser')->columns(2)->disabled($locked)->schema([
                Forms\Components\TextInput::make('counterparty.name')->label('Name')->required()->maxLength(200),
                Forms\Components\DatePicker::make('counterparty.birth_date')->label('Geburtsdatum'),
                Forms\Components\TextInput::make('counterparty.address')->label('Anschrift')->required()->maxLength(300)->columnSpanFull(),
                Forms\Components\TextInput::make('counterparty.authority')->label('Ausstellende Behörde')->maxLength(200),
            ]),
            Forms\Components\Section::make('Nationales Waffenregister')->columns(2)->schema([
                Forms\Components\DateTimePicker::make('nwr_reported_at')->label('Gemeldet am')->seconds(false),
                Forms\Components\TextInput::make('nwr_reference')->label('NWR-Vorgangsnummer')->maxLength(80),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('booked_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['weaponItem.product:id,name', 'order:id,order_number', 'booker:id,first_name,last_name']))
            ->columns([
                Tables\Columns\TextColumn::make('booked_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('direction')->label('Richtung')->badge()
                    ->formatStateUsing(fn ($state) => self::DIRECTIONS[$state] ?? $state)
                    ->color(fn ($state) => $state === 'in' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('weaponItem.product.name')->label('Waffe')->limit(40),
                Tables\Columns\TextColumn::make('weaponItem.serial_number')->label('Seriennummer')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('counterparty.name')->label('Gegenpartei'),
                Tables\Columns\TextColumn::make('order.order_number')->label('Bestellung')->placeholder('–'),
                Tables\Columns\IconColumn::make('nwr_reported_at')->label('NWR')->boolean()
                    ->getStateUsing(fn (WeaponBookEntry $r) => $r->nwr_reported_at !== null),
                Tables\Columns\TextColumn::make('booker.last_name')->label('Gebucht von')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direction')->label('Richtung')->options(self::DIRECTIONS),
                Tables\Filters\TernaryFilter::make('nwr_reported_at')->label('NWR gemeldet')->nullable(),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('NWR-Meldung')])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeaponBookEntries::route('/'),
            'create' => Pages\CreateWeaponBookEntry::route('/create'),
            'edit' => Pages\EditWeaponBookEntry::route('/{record}/edit'),
        ];
    }
}
