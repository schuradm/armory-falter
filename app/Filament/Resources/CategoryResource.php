<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Support\SeoFields;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class CategoryResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Kategorie';

    protected static ?string $pluralModelLabel = 'Kategorien';

    protected static ?string $navigationLabel = 'Kategorien';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['parent', 'defaultLegalClass'])->withCount('products');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->columnSpanFull()->tabs([
                Forms\Components\Tabs\Tab::make('Allgemein')->columns(2)->schema([
                    Forms\Components\TextInput::make('name')->label('Name')->required()->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                            if ($operation === 'create' && blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')->label('URL-Kürzel')->required()->maxLength(140)->alphaDash()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('parent_id', $get('parent_id'))),
                    Forms\Components\Select::make('parent_id')->label('Übergeordnete Kategorie')
                        ->relationship('parent', 'name', fn (Builder $query, ?Category $record) => $record ? $query->whereKeyNot($record->getKey())
                            ->where('path', 'not like', $record->path.'/%') : $query)
                        ->getOptionLabelFromRecordUsing(fn (Category $record) => str_replace('/', ' › ', $record->path))
                        ->searchable()->preload()
                        ->placeholder('Hauptkategorie (oberste Ebene)'),
                    Forms\Components\Select::make('default_legal_class_id')->label('Standard-Rechtsklasse für neue Produkte')
                        ->relationship('defaultLegalClass', 'name')->preload(),
                    Forms\Components\TextInput::make('icon')->label('Icon')->maxLength(40)
                        ->helperText('Name eines Shop-Icons (resources/svg), z. B. pistol, rifle, scope, target, knife.'),
                    Forms\Components\TextInput::make('sort_order')->label('Sortierung')->numeric()->integer()->default(0),
                    Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
                    Forms\Components\Toggle::make('show_on_home')->label('Auf der Startseite zeigen'),
                    Forms\Components\FileUpload::make('image_path')->label('Kategoriebild')->image()->imageEditor()
                        ->disk('public')->directory('categories')->visibility('public')->maxSize(4096)->columnSpanFull(),
                ]),
                Forms\Components\Tabs\Tab::make('Texte')->schema([
                    Forms\Components\Textarea::make('description')->label('Einleitung (über der Produktliste)')->rows(3),
                    Forms\Components\RichEditor::make('content_bottom')->label('SEO-Text (unter der Produktliste)')
                        ->disableToolbarButtons(['attachFiles']),
                ]),
                Forms\Components\Tabs\Tab::make('SEO')->schema([SeoFields::make()]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('path')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('')->disk('public')->square()->size(40),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable()
                    ->description(fn (Category $record) => $record->parent ? str_replace('/', ' › ', $record->path) : null),
                Tables\Columns\TextColumn::make('products_count')->label('Produkte')->numeric()->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('defaultLegalClass.name')->label('Standard-Rechtsklasse')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
                Tables\Columns\ToggleColumn::make('show_on_home')->label('Startseite'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sort.')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')->label('Übergeordnet')
                    ->relationship('parent', 'name', fn (Builder $query) => $query->whereNull('parent_id')),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (Category $record) => $record->products_count > 0 || Category::where('parent_id', $record->id)->exists()),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
