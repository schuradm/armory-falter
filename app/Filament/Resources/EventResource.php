<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Filament\Support\FormFields;
use App\Models\Event;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Events';

    protected static ?string $navigationLabel = 'Events';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Group::make()->columnSpan(['lg' => 2])->schema([
                Forms\Components\Section::make('Event')->schema([
                    FormFields::title(),
                    FormFields::slug('slug', 160, '/events/'),
                    Forms\Components\Textarea::make('teaser')->label('Teaser')->rows(2)->maxLength(300),
                    FormFields::content('description', 'Beschreibung', 'events'),
                ]),
            ]),
            Forms\Components\Group::make()->columnSpan(['lg' => 1])->schema([
                Forms\Components\Section::make('Termin & Ort')->schema([
                    Forms\Components\DateTimePicker::make('starts_at')->label('Beginn')->required()->seconds(false),
                    Forms\Components\DateTimePicker::make('ends_at')->label('Ende')->seconds(false)->after('starts_at'),
                    Forms\Components\TextInput::make('location')->label('Ort')->maxLength(200),
                    Forms\Components\TextInput::make('capacity')->label('Plätze')->numeric()->minValue(1)->helperText('Leer = unbegrenzt. Danach Warteliste.'),
                    FormFields::euro('price_gross', 'Preis (brutto)')->helperText('Leer = kostenlos.'),
                    Forms\Components\Toggle::make('requires_entitlement')->label('Nur mit Erlaubnis (WBK/Jagdschein)'),
                    Forms\Components\Toggle::make('is_published')->label('Veröffentlicht'),
                ]),
                Forms\Components\Section::make('Bild')->schema([
                    FormFields::image('image_path', 'Eventbild (16:9)', 'events')->hiddenLabel(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum(['registrations as booked_persons' => fn ($r) => $r->where('status', 'registered')], 'persons'))
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')->label('Beginn')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('location')->label('Ort')->toggleable(),
                Tables\Columns\TextColumn::make('booked_persons')->label('Anmeldungen')
                    ->formatStateUsing(fn ($state, Event $r) => ((int) $state).($r->capacity ? " / {$r->capacity}" : ''))
                    ->default(0),
                Tables\Columns\TextColumn::make('price_gross')->label('Preis')->formatStateUsing(fn ($state) => Money::format((int) $state))->placeholder('kostenlos'),
                Tables\Columns\IconColumn::make('is_published')->label('Online')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Veröffentlicht'),
                Tables\Filters\Filter::make('upcoming')->label('Nur kommende')->query(fn (Builder $query) => $query->where('starts_at', '>=', now()->startOfDay())),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\RegistrationsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
