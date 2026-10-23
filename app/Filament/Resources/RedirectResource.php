<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Weiterleitungen alter URLs (z. B. WordPress) – greifen nur, wenn keine Route passt (FallbackController). */
class RedirectResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Redirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Weiterleitung';

    protected static ?string $pluralModelLabel = 'Weiterleitungen';

    protected static ?string $navigationLabel = 'Weiterleitungen';

    protected static ?int $navigationSort = 30;

    /** Vollständige URL oder Pfad → Pfad mit führendem Slash (Query bleibt erhalten). */
    public static function normalizePath(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }
        $value = trim($value);
        if (preg_match('#^https?://#i', $value)) {
            $parts = parse_url($value);
            $value = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        return '/'.ltrim($value, '/');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('from_path')->label('Alte Adresse')->required()->maxLength(500)
                    ->placeholder('/alte-seite/')
                    ->helperText('Pfad ohne Domain. Eine komplette URL wird automatisch gekürzt.')
                    ->dehydrateStateUsing(fn ($state) => self::normalizePath($state))
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('to_path')->label('Neue Adresse')->required()->maxLength(500)
                    ->placeholder('/shop/jagdwaffen')
                    ->helperText('Pfad (intern) oder vollständige URL (extern).')
                    ->dehydrateStateUsing(fn ($state) => preg_match('#^https?://#i', (string) $state) && ! str_contains((string) $state, parse_url(config('app.url'), PHP_URL_HOST) ?? '#') ? trim($state) : self::normalizePath($state))
                    ->different('from_path'),
                Forms\Components\Select::make('status_code')->label('Art')->options([
                    301 => '301 – dauerhaft verschoben',
                    302 => '302 – vorübergehend',
                ])->default(301)->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('from_path')
            ->columns([
                Tables\Columns\TextColumn::make('from_path')->label('Alte Adresse')->searchable()->sortable()->limit(60),
                Tables\Columns\TextColumn::make('to_path')->label('Neue Adresse')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('status_code')->label('Code')->badge(),
                Tables\Columns\TextColumn::make('hits')->label('Aufrufe')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Geändert')->dateTime('d.m.Y')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status_code')->label('Code')->options([301 => '301', 302 => '302'])])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
