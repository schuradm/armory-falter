<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\AuthorResource\Pages;
use App\Filament\Support\FormFields;
use App\Models\Author;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuthorResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = Author::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Autor';

    protected static ?string $pluralModelLabel = 'Autoren';

    protected static ?string $navigationLabel = 'Autoren';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                FormFields::title('name', 'Name', 'slug', 120),
                FormFields::slug('slug', 140, '/blog/autor/'),
                Forms\Components\TextInput::make('role_title')->label('Funktion')->placeholder('z. B. Büchsenmachermeister')->maxLength(120),
                Forms\Components\Select::make('user_id')->label('Verknüpfter Benutzer')
                    ->options(fn () => User::where('is_staff', true)->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email'])
                        ->mapWithKeys(fn ($u) => [$u->id => trim("{$u->first_name} {$u->last_name}")." ({$u->email})"]))
                    ->searchable(),
                Forms\Components\Textarea::make('bio')->label('Kurzbiografie')->rows(4)->columnSpanFull(),
                FormFields::image('avatar_path', 'Porträtfoto', 'authors')->avatar(),
                Forms\Components\TagsInput::make('same_as')->label('Profile (E-E-A-T, schema.org sameAs)')
                    ->placeholder('https://… eingeben und Enter')
                    ->nestedRecursiveRules(['url']),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_path')->label('')->disk('public')->circular(),
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role_title')->label('Funktion'),
                Tables\Columns\TextColumn::make('posts_count')->label('Beiträge')->counts('posts')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
