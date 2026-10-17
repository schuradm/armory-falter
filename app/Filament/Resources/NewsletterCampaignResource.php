<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterCampaignResource\Pages;
use App\Models\NewsletterCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaignResource extends Resource
{
    protected static ?string $model = NewsletterCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Newsletter-Kampagne';

    protected static ?string $pluralModelLabel = 'Newsletter-Kampagnen';

    protected static ?string $navigationLabel = 'Newsletter';

    protected static ?int $navigationSort = 30;

    public const STATUSES = ['draft' => 'Entwurf', 'scheduled' => 'Geplant', 'sending' => 'Wird versendet', 'sent' => 'Versendet'];

    public static function canEdit(Model $record): bool
    {
        return in_array($record->status, ['draft', 'scheduled'], true);
    }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Section::make('Inhalt')->columnSpan(['lg' => 2])->schema([
                Forms\Components\TextInput::make('subject')->label('Betreff')->required()->maxLength(200),
                Forms\Components\TextInput::make('preheader')->label('Vorschauzeile (Preheader)')->maxLength(200),
                Forms\Components\MarkdownEditor::make('body')->label('Text')->required()
                    ->helperText('Markdown. Abmeldelink und Impressum werden automatisch angehängt.')
                    ->toolbarButtons(['bold', 'italic', 'link', 'heading', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo']),
            ]),
            Forms\Components\Section::make('Versand')->columnSpan(['lg' => 1])->schema([
                Forms\Components\TextInput::make('name')->label('Interner Name')->required()->maxLength(160),
                Forms\Components\Select::make('newsletter_segment_id')->label('Segment')->relationship('segment', 'name')
                    ->placeholder('Alle bestätigten Abonnenten'),
                Forms\Components\Select::make('status')->label('Status')->options(['draft' => 'Entwurf', 'scheduled' => 'Geplant'])->required()->default('draft')->live(),
                Forms\Components\DateTimePicker::make('scheduled_at')->label('Versand am')->seconds(false)
                    ->required(fn (Forms\Get $get) => $get('status') === 'scheduled')->minDate(now()),
                Forms\Components\Placeholder::make('sent_at_info')->label('Versendet am')
                    ->content(fn (?NewsletterCampaign $record) => $record?->sent_at?->format('d.m.Y H:i') ?? '–'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('segment:id,name')
                ->withCount(['events as opens_count' => fn ($e) => $e->where('event', 'open')]))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('subject')->label('Betreff')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('segment.name')->label('Segment')->placeholder('Alle'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'sent' => 'success', 'scheduled', 'sending' => 'warning', default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')->label('Geplant')->dateTime('d.m.Y H:i')->placeholder('–')->sortable(),
                Tables\Columns\TextColumn::make('sent_at')->label('Versendet')->dateTime('d.m.Y H:i')->placeholder('–')->sortable(),
                Tables\Columns\TextColumn::make('opens_count')->label('Öffnungen')->toggleable(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::STATUSES)])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterCampaigns::route('/'),
            'create' => Pages\CreateNewsletterCampaign::route('/create'),
            'edit' => Pages\EditNewsletterCampaign::route('/{record}/edit'),
        ];
    }
}
