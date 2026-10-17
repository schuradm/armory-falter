<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductReviewResource\Pages;
use App\Models\ProductReview;
use App\Services\Reviews\ReviewService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Moderation der Produktbewertungen: wartend → veröffentlicht/abgelehnt, öffentliche Antwort.
 * Sterne und Text des Kunden sind schreibgeschützt (eine nachträgliche Änderung wäre eine Irreführung, § 5b UWG).
 */
class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'Bewertung';

    protected static ?string $pluralModelLabel = 'Bewertungen';

    protected static ?string $navigationLabel = 'Bewertungen';

    protected static ?int $navigationSort = 60;

    /** Abgelehnt wird nur aus diesen Gründen; schlechte Noten allein sind kein Grund. */
    public const REJECT_REASONS = [
        'Beleidigung oder unsachliche Schmähkritik',
        'Personenbezogene Daten Dritter',
        'Kein Bezug zum Produkt (z. B. nur Versand oder Lieferzeit)',
        'Werbung oder Links',
        'Rechtswidriger Inhalt (z. B. Anleitung zu Straftaten)',
    ];

    public static function canCreate(): bool
    {
        return false; // Bewertungen entstehen ausschließlich im Kundenkonto verifizierter Käufer
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ProductReview::where('status', 'pending')->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Bewertung des Kunden')->columns(3)->schema([
                Forms\Components\Placeholder::make('product')->label('Produkt')->content(fn (ProductReview $record) => $record->product?->name),
                Forms\Components\Placeholder::make('author')->label('Angezeigt als')->content(fn (ProductReview $record) => $record->author_name.' · '.$record->user?->email),
                Forms\Components\Placeholder::make('proof')->label('Kaufnachweis')
                    ->content(fn (ProductReview $record) => $record->orderItem?->order ? 'Bestellung '.$record->orderItem->order->order_number.' ('.$record->orderItem->order->status->label().')' : '—'),
                Forms\Components\Placeholder::make('rating')->label('Sterne')->content(fn (ProductReview $record) => str_repeat('★', $record->rating).str_repeat('☆', 5 - $record->rating)),
                Forms\Components\Placeholder::make('title')->label('Überschrift')->content(fn (ProductReview $record) => $record->title ?: '—')->columnSpan(2),
                Forms\Components\Placeholder::make('body')->label('Text')->content(fn (ProductReview $record) => $record->body ?: '—')->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Öffentliche Antwort')->schema([
                Forms\Components\Textarea::make('reply')->label('Antwort von Armory Falter')->rows(4)->maxLength(2000)
                    ->helperText('Erscheint unter der Bewertung. Sachlich bleiben, keine Kundendaten nennen. Leer = keine Antwort.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product:id,name,slug', 'user:id,email']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('product.name')->label('Produkt')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('rating')->label('Sterne')->sortable()
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))->color('warning'),
                Tables\Columns\TextColumn::make('title')->label('Bewertung')->limit(60)->wrap()
                    ->description(fn (ProductReview $r) => \Illuminate\Support\Str::limit((string) $r->body, 100))->searchable(['title', 'body']),
                Tables\Columns\TextColumn::make('author_name')->label('Kunde')->description(fn (ProductReview $r) => $r->user?->email),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => ProductReview::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'approved' => 'success', 'pending' => 'warning', default => 'danger' }),
                Tables\Columns\IconColumn::make('reply')->label('Antwort')->boolean()->getStateUsing(fn (ProductReview $r) => filled($r->reply)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(ProductReview::STATUSES)->default('pending'),
                Tables\Filters\SelectFilter::make('rating')->label('Sterne')->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Veröffentlichen')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (ProductReview $r) => $r->status !== 'approved')
                    ->action(function (ProductReview $r) {
                        app(ReviewService::class)->approve($r, auth()->user());
                        Notification::make()->success()->title('Bewertung veröffentlicht')->send();
                    }),
                Tables\Actions\Action::make('reject')->label('Ablehnen')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn (ProductReview $r) => $r->status !== 'rejected')
                    ->modalDescription('Nur bei Verstößen ablehnen. Negative, aber sachliche Bewertungen werden veröffentlicht. Der Kunde sieht den Grund im Kundenkonto.')
                    ->form([
                        Forms\Components\Select::make('reason')->label('Grund')->required()
                            ->options(array_combine(self::REJECT_REASONS, self::REJECT_REASONS)),
                    ])
                    ->action(function (array $data, ProductReview $r) {
                        app(ReviewService::class)->reject($r, auth()->user(), $data['reason']);
                        Notification::make()->success()->title('Bewertung abgelehnt')->send();
                    }),
                Tables\Actions\EditAction::make()->label('Antworten'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')->label('Veröffentlichen')->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each(fn (ProductReview $r) => app(ReviewService::class)->approve($r, auth()->user())))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductReviews::route('/'),
            'edit' => Pages\EditProductReview::route('/{record}/edit'),
        ];
    }
}
