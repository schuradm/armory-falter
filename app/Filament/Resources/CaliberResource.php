<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\CaliberResource\Pages;
use App\Filament\Support\FaqRepeater;
use App\Models\Caliber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CaliberResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Caliber::class;

    protected static ?string $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Kaliber';

    protected static ?string $pluralModelLabel = 'Kaliber';

    protected static ?string $navigationLabel = 'Kaliber';

    protected static ?string $recordTitleAttribute = 'name';

    public const KINDS = [
        'rifle' => 'Büchse (Langwaffe)',
        'handgun' => 'Kurzwaffe',
        'shotgun' => 'Flinte (Schrot)',
        'rimfire' => 'Randfeuer (KK)',
        'airgun' => 'Luftdruck',
        'other' => 'Sonstiges',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('products');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Bezeichnung')->required()->maxLength(80)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug(str_replace(['.', ','], '-', (string) $state)));
                        }
                    }),
                Forms\Components\TextInput::make('slug')->label('URL-Kürzel')->required()->maxLength(80)->alphaDash()->unique(ignoreRecord: true),
                Forms\Components\Select::make('kind')->label('Art')->options(self::KINDS)->required()
                    ->helperText('Büchse, Flinte und Randfeuer gelten als Langwaffenkaliber (Jagdschein § 13 WaffG).'),
                Forms\Components\Toggle::make('is_large_bore')->label('Großkaliber')->inline(false)
                    ->helperText('Für Sportschützen erst ab 21 Jahren (§ 14 WaffG).'),
                Forms\Components\TagsInput::make('aliases')->label('Alternative Schreibweisen')->columnSpanFull()
                    ->placeholder('z. B. 308 Win, 7,62×51')
                    ->helperText('Werden in der Suche gefunden.'),
                Forms\Components\RichEditor::make('content')->label('Kaliberbeschreibung (Kaliberseite)')
                    ->disableToolbarButtons(['attachFiles'])->columnSpanFull(),
            ]),
            Forms\Components\Section::make('FAQ')->schema([FaqRepeater::make()]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bezeichnung')->searchable(['name', 'aliases'])->sortable()
                    ->description(fn (Caliber $record) => $record->aliases ? implode(', ', $record->aliases) : null),
                Tables\Columns\TextColumn::make('kind')->label('Art')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::KINDS[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_large_bore')->label('Großkaliber')->boolean(),
                Tables\Columns\TextColumn::make('products_count')->label('Produkte')->numeric()->sortable()->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kind')->label('Art')->options(self::KINDS),
                Tables\Filters\TernaryFilter::make('is_large_bore')->label('Großkaliber'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->hidden(fn (Caliber $record) => $record->products_count > 0),
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
            'index' => Pages\ListCalibers::route('/'),
            'create' => Pages\CreateCaliber::route('/create'),
            'edit' => Pages\EditCaliber::route('/{record}/edit'),
        ];
    }
}
