<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Service';

    protected static ?string $modelLabel = 'Termin';

    protected static ?string $pluralModelLabel = 'Termine';

    protected static ?string $navigationLabel = 'Termine';

    protected static ?int $navigationSort = 20;

    public const TYPES = ['workshop' => 'Werkstatt', 'consultation' => 'Beratung', 'pickup' => 'Abholung'];

    public const STATUSES = ['requested' => 'Angefragt', 'confirmed' => 'Bestätigt', 'cancelled' => 'Abgesagt', 'done' => 'Erledigt'];

    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::where('status', 'requested')->count();

        return $count ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Termin')->columns(2)->schema([
                Forms\Components\Select::make('type')->label('Art')->options(self::TYPES)->required()->default('consultation'),
                Forms\Components\Select::make('resource')->label('Ressource')->options(config('shop.appointments.resources'))->required()->default('beratung'),
                Forms\Components\Select::make('workshop_service_id')->label('Werkstatt-Leistung')->relationship('service', 'name')->searchable()->preload(),
                Forms\Components\Select::make('status')->label('Status')->options(self::STATUSES)->required()->default('confirmed'),
                Forms\Components\DateTimePicker::make('starts_at')->label('Beginn')->required()->seconds(false)->minutesStep(15),
                Forms\Components\DateTimePicker::make('ends_at')->label('Ende')->required()->seconds(false)->minutesStep(15)->after('starts_at'),
            ]),
            Forms\Components\Section::make('Kunde')->columns(2)->schema([
                Forms\Components\Select::make('user_id')->label('Kundenkonto')
                    ->relationship('user', 'email')
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->first_name} {$record->last_name}")." ({$record->email})")
                    ->searchable(['email', 'last_name', 'first_name']),
                Forms\Components\TextInput::make('contact.name')->label('Name')->required()->maxLength(160),
                Forms\Components\TextInput::make('contact.email')->label('E-Mail')->email()->required(),
                Forms\Components\TextInput::make('contact.phone')->label('Telefon')->tel(),
                Forms\Components\TextInput::make('weapon_description')->label('Waffe / Gegenstand')->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('notes')->label('Notizen')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('service'))
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')->label('Beginn')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Art')->formatStateUsing(fn ($state) => self::TYPES[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('service.name')->label('Leistung')->placeholder('–'),
                Tables\Columns\TextColumn::make('contact.name')->label('Kunde')->searchable(query: fn (Builder $query, string $search) => $query->where('contact->name', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('contact.phone')->label('Telefon')->toggleable(),
                Tables\Columns\TextColumn::make('resource')->label('Ressource')->formatStateUsing(fn ($state) => config("shop.appointments.resources.$state", $state))->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'requested' => 'warning', 'confirmed' => 'success', 'cancelled' => 'danger', default => 'gray'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::STATUSES),
                Tables\Filters\SelectFilter::make('type')->label('Art')->options(self::TYPES),
                Tables\Filters\Filter::make('upcoming')->label('Nur kommende')->query(fn (Builder $query) => $query->where('starts_at', '>=', now()->startOfDay()))->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')->label('Bestätigen')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Appointment $r) => $r->status === 'requested')
                    ->action(fn (Appointment $r) => $r->update(['status' => 'confirmed'])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
