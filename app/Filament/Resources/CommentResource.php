<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Http\Middleware\PageCache;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Inhalte';

    protected static ?string $modelLabel = 'Kommentar';

    protected static ?string $pluralModelLabel = 'Kommentare';

    protected static ?string $navigationLabel = 'Kommentare';

    protected static ?int $navigationSort = 40;

    public const STATUSES = ['pending' => 'Wartet auf Freigabe', 'approved' => 'Freigegeben', 'spam' => 'Spam', 'rejected' => 'Abgelehnt'];

    public static function getNavigationBadge(): ?string
    {
        $count = Comment::where('status', 'pending')->count();

        return $count ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('blog_post_id')->label('Beitrag')->relationship('post', 'title')->searchable()->required(),
                Forms\Components\Select::make('parent_id')->label('Antwort auf Kommentar')
                    ->options(fn (Forms\Get $get, ?Comment $record) => $get('blog_post_id')
                        ? Comment::where('blog_post_id', $get('blog_post_id'))->whereNull('parent_id')->when($record, fn ($query) => $query->whereKeyNot($record->id))
                            ->latest()->limit(200)->get(['id', 'author_name', 'body'])
                            ->mapWithKeys(fn (Comment $c) => [$c->id => "{$c->author_name}: ".Str::limit($c->body, 60)])
                        : [])
                    ->searchable()
                    ->placeholder('Kein (Hauptkommentar)'),
                Forms\Components\TextInput::make('author_name')->label('Name')->required()->maxLength(80)
                    ->default(fn () => auth()->user()?->first_name ? auth()->user()->first_name.' (Armory Falter)' : 'Armory Falter'),
                Forms\Components\Select::make('status')->label('Status')->options(self::STATUSES)->required()->default('approved'),
                Forms\Components\Textarea::make('body')->label('Kommentar')->required()->rows(5)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('post:id,title,slug'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('author_name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('body')->label('Kommentar')->limit(80)->wrap()->searchable(),
                Tables\Columns\TextColumn::make('post.title')->label('Beitrag')->limit(40),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success', 'pending' => 'warning', default => 'danger'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::STATUSES),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Freigeben')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Comment $r) => $r->status !== 'approved')
                    ->action(fn (Comment $r) => self::setStatus([$r->id], 'approved')),
                Tables\Actions\Action::make('spam')->label('Spam')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (Comment $r) => $r->status !== 'spam')
                    ->action(fn (Comment $r) => self::setStatus([$r->id], 'spam')),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')->label('Freigeben')->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => self::setStatus($records->modelKeys(), 'approved'))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('spam')->label('Als Spam markieren')->icon('heroicon-o-no-symbol')
                        ->action(fn (Collection $records) => self::setStatus($records->modelKeys(), 'spam'))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Status setzen und Seiten-Cache leeren, damit freigegebene Kommentare sofort sichtbar sind. */
    public static function setStatus(array $ids, string $status): void
    {
        Comment::whereKey($ids)->update(['status' => $status]);
        PageCache::flush();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
