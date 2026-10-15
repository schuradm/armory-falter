<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Kunden & Prüfung';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Benutzer';

    protected static ?string $pluralModelLabel = 'Kunden & Mitarbeiter';

    protected static ?string $navigationLabel = 'Kunden & Mitarbeiter';

    public const CUSTOMER_TYPES = ['private' => 'Privatkunde', 'company' => 'Firma', 'authority' => 'Behörde', 'dealer' => 'Händler'];

    public const SALUTATIONS = ['herr' => 'Herr', 'frau' => 'Frau', 'divers' => 'Divers', 'none' => 'Keine Angabe'];

    /** Felder, die nicht per Mass-Assignment gesetzt werden dürfen und deshalb in den Seiten per forceFill gespeichert werden. */
    public const PROTECTED_FIELDS = ['is_staff', 'is_active', 'email_verified'];

    public static function isSuperAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('super-admin');
    }

    public static function canEdit(Model $record): bool
    {
        // Nur Super-Admins dürfen andere Super-Admins bearbeiten (keine Rechteausweitung)
        return self::isSuperAdmin() || ! $record->hasRole('super-admin');
    }

    public static function canDelete(Model $record): bool
    {
        return self::canEdit($record) && $record->getKey() !== auth()->id();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'last_name', 'first_name', 'customer_number', 'company_name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name.' ('.$record->customer_number.')';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles')
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Group::make()->columnSpan(2)->schema([
                    Forms\Components\Section::make('Stammdaten')->columns(2)->schema([
                        Forms\Components\Select::make('customer_type')->label('Kundenart')->options(self::CUSTOMER_TYPES)
                            ->default('private')->required()->live(),
                        Forms\Components\Select::make('salutation')->label('Anrede')->options(self::SALUTATIONS)->default('none')->required(),
                        Forms\Components\TextInput::make('first_name')->label('Vorname')->required()->maxLength(100),
                        Forms\Components\TextInput::make('last_name')->label('Nachname')->required()->maxLength(100),
                        Forms\Components\TextInput::make('company_name')->label('Firma / Behörde')->maxLength(200)
                            ->visible(fn (Forms\Get $get) => $get('customer_type') !== 'private'),
                        Forms\Components\TextInput::make('vat_id')->label('USt-IdNr.')->maxLength(20)
                            ->visible(fn (Forms\Get $get) => $get('customer_type') !== 'private'),
                        Forms\Components\TextInput::make('email')->label('E-Mail')->email()->required()->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')->label('Telefon')->tel()->maxLength(40),
                        Forms\Components\DatePicker::make('birth_date')->label('Geburtsdatum')->native(false)->displayFormat('d.m.Y')
                            ->maxDate(today())
                            ->helperText('Änderungen heben die Altersbestätigung nicht auf. Bestätigt wird nur über die Dokumentenprüfung.'),
                        Forms\Components\TextInput::make('password')->label('Passwort')->password()->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->rule(\Illuminate\Validation\Rules\Password::default())
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leer lassen, um das Passwort nicht zu ändern.' : null),
                    ]),
                ]),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Konto')->schema([
                        Forms\Components\Placeholder::make('customer_number')->label('Kundennummer')
                            ->content(fn (?User $record) => $record?->customer_number ?? 'wird automatisch vergeben'),
                        Forms\Components\Toggle::make('is_active')->label('Konto aktiv')->default(true),
                        Forms\Components\Toggle::make('email_verified')->label('E-Mail bestätigt')->default(true),
                        Forms\Components\Placeholder::make('age_verified_at')->label('Alter bestätigt')
                            ->content(fn (?User $record) => $record?->age_verified_at
                                ? $record->age_verified_at->format('d.m.Y').' ('.($record->age_verification_method ?? '—').')'
                                : 'nein'),
                        Forms\Components\Placeholder::make('two_factor')->label('Zwei-Faktor-Anmeldung')
                            ->content(fn (?User $record) => $record?->two_factor_confirmed_at ? 'aktiv seit '.$record->two_factor_confirmed_at->format('d.m.Y') : 'nicht eingerichtet'),
                        Forms\Components\Placeholder::make('last_login_at')->label('Letzte Anmeldung')
                            ->content(fn (?User $record) => $record?->last_login_at?->format('d.m.Y H:i') ?? '—'),
                    ]),
                    Forms\Components\Section::make('Backend-Zugang')
                        ->description('Mitarbeiter benötigen eine Rolle und eine aktive Zwei-Faktor-Anmeldung.')
                        ->visible(fn () => self::isSuperAdmin())
                        ->schema([
                            Forms\Components\Toggle::make('is_staff')->label('Mitarbeiter (Zugang zu /kms-login)')->live(),
                            Forms\Components\Select::make('roles')->label('Rollen')
                                ->relationship('roles', 'name')->multiple()->preload()
                                ->visible(fn (Forms\Get $get) => (bool) $get('is_staff')),
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('customer_number')->label('Kd.-Nr.')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('last_name')->label('Name')->sortable()
                    ->formatStateUsing(fn (User $record) => $record->name)
                    ->description(fn (User $record) => $record->company_name)
                    ->searchable(['first_name', 'last_name', 'company_name']),
                Tables\Columns\TextColumn::make('email')->label('E-Mail')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('customer_type')->label('Art')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::CUSTOMER_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('roles.name')->label('Rollen')->badge()->toggleable(),
                Tables\Columns\IconColumn::make('age_verified_at')->label('Alter geprüft')->boolean()
                    ->getStateUsing(fn (User $record) => $record->age_verified_at !== null),
                Tables\Columns\IconColumn::make('is_active')->label('Aktiv')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Registriert')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')->label('Letzter Login')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_staff')->label('Mitarbeiter'),
                Tables\Filters\SelectFilter::make('customer_type')->label('Kundenart')->options(self::CUSTOMER_TYPES),
                Tables\Filters\TernaryFilter::make('age_verified_at')->label('Alter geprüft')->nullable(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
                Tables\Filters\TrashedFilter::make()->label('Gelöschte Konten'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EntitlementsRelationManager::class,
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
