<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Filament\Support\FormFields;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Blogbeitrag';

    protected static ?string $pluralModelLabel = 'Blogbeiträge';

    protected static ?string $navigationLabel = 'Blog';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Group::make()->columnSpan(['lg' => 2])->schema([
                Forms\Components\Section::make('Beitrag')->columns(2)->schema([
                    FormFields::title()->columnSpanFull(),
                    FormFields::slug('slug', 220, '/blog/')->columnSpanFull(),
                    Forms\Components\Textarea::make('excerpt')->label('Auszug / Teaser')->rows(3)->maxLength(500)->columnSpanFull(),
                    FormFields::content('body', 'Inhalt', 'blog')->required(),
                ]),
                FormFields::seoSection(),
            ]),
            Forms\Components\Group::make()->columnSpan(['lg' => 1])->schema([
                Forms\Components\Section::make('Veröffentlichung')->schema([
                    Forms\Components\Select::make('status')->label('Status')->options(FormFields::publishStatus())->required()->default('draft'),
                    Forms\Components\DateTimePicker::make('published_at')->label('Veröffentlicht am')->seconds(false)->default(now())
                        ->helperText('Zukünftiges Datum = geplante Veröffentlichung.'),
                    Forms\Components\Select::make('blog_category_id')->label('Kategorie')->relationship('category', 'name')->required()->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Name')->required(),
                            Forms\Components\TextInput::make('slug')->label('Slug')->required()->unique('blog_categories', 'slug'),
                        ]),
                    Forms\Components\Select::make('author_id')->label('Autor')->relationship('author', 'name')->required()->preload(),
                    Forms\Components\TextInput::make('reading_minutes')->label('Lesezeit (Minuten)')->numeric()->minValue(1)->maxValue(120),
                    Forms\Components\Toggle::make('comments_enabled')->label('Kommentare erlauben')->default(true),
                ]),
                Forms\Components\Section::make('Titelbild')->schema([
                    FormFields::image('cover_path', 'Titelbild', 'blog')->hiddenLabel(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'author']))
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')->label('')->disk('public')->square(),
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable()->limit(60)->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Kategorie'),
                Tables\Columns\TextColumn::make('author.name')->label('Autor')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => FormFields::publishStatus()[$state] ?? $state)
                    ->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('published_at')->label('Veröffentlicht')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(FormFields::publishStatus()),
                Tables\Filters\SelectFilter::make('blog_category_id')->label('Kategorie')->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')->label('Ansehen')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (BlogPost $r) => route('blog.show', $r->slug))->openUrlInNewTab()
                    ->visible(fn (BlogPost $r) => $r->status === 'published'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
