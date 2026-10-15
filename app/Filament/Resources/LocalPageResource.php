<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocalPageResource\Pages;
use App\Filament\Support\FormFields;
use App\Models\LocalPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** Regionale Landingpages (/waffenhaendler/{ort}). Jede Seite braucht eigenen Inhalt, keine Textkopien. */
class LocalPageResource extends Resource
{
    protected static ?string $model = LocalPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Regionalseite';

    protected static ?string $pluralModelLabel = 'Regionalseiten';

    protected static ?string $navigationLabel = 'Regionalseiten';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'city';

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Group::make()->columnSpan(['lg' => 2])->schema([
                Forms\Components\Section::make('Inhalt')->schema([
                    Forms\Components\TextInput::make('headline')->label('Überschrift (H1)')->required()->maxLength(200),
                    FormFields::content('body', 'Text', 'local')->required()
                        ->helperText('Eigener Text pro Ort (Anfahrt, Bezug zur Region). Doppelte Inhalte schaden dem Ranking.'),
                ]),
                Forms\Components\Section::make('Häufige Fragen')->collapsible()->schema([
                    Forms\Components\Repeater::make('faqs')->hiddenLabel()
                        ->schema([
                            Forms\Components\TextInput::make('question')->label('Frage')->required()->maxLength(300),
                            Forms\Components\Textarea::make('answer')->label('Antwort')->required()->rows(3),
                        ])
                        ->itemLabel(fn (array $state) => $state['question'] ?? null)
                        ->collapsible()->reorderable()->defaultItems(0)->addActionLabel('Frage hinzufügen'),
                ]),
            ]),
            Forms\Components\Group::make()->columnSpan(['lg' => 1])->schema([
                Forms\Components\Section::make('Ort')->schema([
                    Forms\Components\TextInput::make('city')->label('Ort')->required()->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                            if ($operation === 'create' || blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                            if (blank($get('title'))) {
                                $set('title', "Waffenhändler {$state} – Armory Falter");
                            }
                            if (blank($get('headline'))) {
                                $set('headline', "Ihr Waffenhändler für {$state}");
                            }
                        }),
                    FormFields::slug('slug', 120, '/waffenhaendler/'),
                    Forms\Components\TextInput::make('distance_km')->label('Entfernung zum Laden (km)')->numeric()->minValue(0),
                    Forms\Components\Toggle::make('is_published')->label('Veröffentlicht'),
                ]),
                Forms\Components\Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('title')->label('Seitentitel')->required()->maxLength(200)->helperText('Ideal: max. 60 Zeichen.'),
                    Forms\Components\Textarea::make('meta_description')->label('Meta-Beschreibung')->rows(3)->maxLength(300)->helperText('Ideal: 120–160 Zeichen.'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('city')
            ->columns([
                Tables\Columns\TextColumn::make('city')->label('Ort')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('URL')->prefix('/waffenhaendler/'),
                Tables\Columns\TextColumn::make('distance_km')->label('km')->sortable()->suffix(' km'),
                Tables\Columns\IconColumn::make('is_published')->label('Online')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Geändert')->dateTime('d.m.Y')->sortable()->toggleable(),
            ])
            ->filters([Tables\Filters\TernaryFilter::make('is_published')->label('Veröffentlicht')])
            ->actions([
                Tables\Actions\Action::make('view')->label('Ansehen')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (LocalPage $r) => route('local.show', $r->slug))->openUrlInNewTab()->visible(fn (LocalPage $r) => $r->is_published),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocalPages::route('/'),
            'create' => Pages\CreateLocalPage::route('/create'),
            'edit' => Pages\EditLocalPage::route('/{record}/edit'),
        ];
    }
}
