<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\PageResource\Pages;
use App\Filament\Support\FormFields;
use App\Http\Controllers\PageController;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Statische Seiten (Rechtstexte, Über uns …). Erreichbar sind nur die in PageController::SLUGS hinterlegten Adressen. */
class PageResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Seite';

    protected static ?string $pluralModelLabel = 'Seiten';

    protected static ?string $navigationLabel = 'Seiten';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public const SLUG_LABELS = [
        'impressum' => 'Impressum', 'datenschutz' => 'Datenschutz', 'agb' => 'AGB', 'widerruf' => 'Widerrufsbelehrung',
        'versandinformationen' => 'Versandinformationen', 'zahlungsarten' => 'Zahlungsarten', 'barrierefreiheit' => 'Barrierefreiheit',
        'ueber-uns' => 'Über uns', 'altersverifikation' => 'Altersverifikation',
    ];

    public static function slugOptions(): array
    {
        return collect(PageController::SLUGS)->mapWithKeys(fn ($s) => [$s => (self::SLUG_LABELS[$s] ?? $s)." (/{$s})"])->all();
    }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Group::make()->columnSpan(['lg' => 2])->schema([
                Forms\Components\Section::make('Seite')->schema([
                    Forms\Components\TextInput::make('title')->label('Titel')->required()->maxLength(200),
                    FormFields::content('body', 'Inhalt', 'pages')->required()
                        ->helperText('Rechtstexte vor Veröffentlichung anwaltlich prüfen lassen.'),
                ]),
                FormFields::seoSection(),
            ]),
            Forms\Components\Section::make('Veröffentlichung')->columnSpan(['lg' => 1])->schema([
                Forms\Components\Select::make('slug')->label('Adresse')->options(self::slugOptions())->required()
                    ->unique(ignoreRecord: true)->searchable(),
                Forms\Components\Select::make('status')->label('Status')->options(FormFields::publishStatus())->required()->default('published'),
                Forms\Components\DateTimePicker::make('published_at')->label('Veröffentlicht am')->seconds(false)->default(now()),
                Forms\Components\Select::make('template')->label('Vorlage')->options(['default' => 'Standard', 'legal' => 'Rechtstext'])->default('default')->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Adresse')->prefix('/'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => FormFields::publishStatus()[$state] ?? $state)
                    ->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('updated_at')->label('Geändert')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')->label('Ansehen')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Page $r) => in_array($r->slug, PageController::SLUGS, true) ? route('page.show', $r->slug) : null)->openUrlInNewTab()
                    ->visible(fn (Page $r) => $r->status === 'published' && in_array($r->slug, PageController::SLUGS, true)),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
