<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\FaqResource\Pages;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Manufacturer;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FaqResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQs';

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?int $navigationSort = 50;

    public const TYPES = ['product' => 'Produkt', 'blog_post' => 'Blogbeitrag', 'manufacturer' => 'Hersteller'];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('question')->label('Frage')->required()->maxLength(300)->columnSpanFull(),
                Forms\Components\RichEditor::make('answer')->label('Antwort')->required()
                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList'])->columnSpanFull(),
                Forms\Components\TextInput::make('group_name')->label('Gruppe')->maxLength(80)
                    ->datalist(fn () => Faq::query()->whereNotNull('group_name')->distinct()->orderBy('group_name')->pluck('group_name')->all())
                    ->helperText('Gruppe auf der FAQ-Seite. „Verkaufsservice“ und „Ankauf“ erscheinen auf den Service-Seiten.'),
                Forms\Components\TextInput::make('sort_order')->label('Reihenfolge')->numeric()->default(0),
            ]),
            Forms\Components\Section::make('Zuordnung')
                ->description('Optional: FAQ nur bei einem Produkt, Beitrag oder Hersteller anzeigen. Leer = allgemeine FAQ-Seite.')
                ->collapsible()
                ->schema([
                    Forms\Components\MorphToSelect::make('faqable')->label('Gehört zu')
                        ->types([
                            Forms\Components\MorphToSelect\Type::make(Product::class)->titleAttribute('name')->label('Produkt'),
                            Forms\Components\MorphToSelect\Type::make(BlogPost::class)->titleAttribute('title')->label('Blogbeitrag'),
                            Forms\Components\MorphToSelect\Type::make(Manufacturer::class)->titleAttribute('name')->label('Hersteller'),
                        ])
                        ->searchable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('question')->label('Frage')->searchable()->limit(70)->wrap(),
                Tables\Columns\TextColumn::make('group_name')->label('Gruppe')->badge()->placeholder('–')->sortable(),
                Tables\Columns\TextColumn::make('faqable_type')->label('Zuordnung')
                    ->formatStateUsing(fn ($state, Faq $r) => (self::TYPES[$state] ?? $state)." #{$r->faqable_id}")
                    ->placeholder('Allgemein'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_name')->label('Gruppe')
                    ->options(fn () => Faq::query()->whereNotNull('group_name')->distinct()->orderBy('group_name')->pluck('group_name', 'group_name')->all()),
                Tables\Filters\TernaryFilter::make('general')->label('Allgemeine FAQ')
                    ->queries(true: fn (Builder $query) => $query->whereNull('faqable_type'), false: fn (Builder $query) => $query->whereNotNull('faqable_type')),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
