<?php

namespace App\Filament\Resources;

use App\Enums\ItemCondition;
use App\Enums\TradeRequestStatus;
use App\Enums\TradeRequestType;
use App\Filament\Resources\TradeRequestResource\Pages;
use App\Filament\Support\Euro;
use App\Models\TradeRequest;
use App\Models\User;
use App\Services\Trade\TradeRequestService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TradeRequestResource extends Resource
{
    protected static ?string $model = TradeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Service';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Ankauf-/Verkaufsanfrage';

    protected static ?string $pluralModelLabel = 'Ankauf & Verkaufsservice';

    protected static ?string $navigationLabel = 'Ankauf & Verkaufsservice';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationBadge(): ?string
    {
        $n = TradeRequest::where('status', TradeRequestStatus::New)->count();

        return $n ? (string) $n : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['manufacturer', 'assignee']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Group::make()->columnSpan(2)->schema([
                    Forms\Components\Section::make('Waffe / Artikel')->columns(2)->schema([
                        Forms\Components\Select::make('type')->label('Art der Anfrage')->options(TradeRequestType::class)->required()
                            ->default(TradeRequestType::Consignment->value),
                        Forms\Components\TextInput::make('weapon_type')->label('Waffenart')->maxLength(80)
                            ->placeholder('z. B. Repetierbüchse, Pistole'),
                        Forms\Components\Select::make('manufacturer_id')->label('Hersteller')
                            ->relationship('manufacturer', 'name')->searchable()->preload(),
                        Forms\Components\TextInput::make('manufacturer_name')->label('Hersteller (Freitext)')->maxLength(120)
                            ->helperText('Falls nicht in der Liste.'),
                        Forms\Components\TextInput::make('model')->label('Modell')->required()->maxLength(160),
                        Forms\Components\TextInput::make('caliber')->label('Kaliber')->maxLength(80),
                        Forms\Components\Select::make('condition_code')->label('Zustand')->options(ItemCondition::class)->required(),
                        Euro::input('desired_price', 'Preisvorstellung Kunde'),
                        Forms\Components\Textarea::make('description')->label('Beschreibung / Zubehör')->rows(4)->columnSpanFull(),
                    ]),
                    Forms\Components\Section::make('Fotos')
                        ->visible(fn (?TradeRequest $record) => $record !== null)
                        ->schema([
                            Forms\Components\Placeholder::make('photos')->hiddenLabel()
                                ->content(fn (?TradeRequest $record) => $record ? self::photosHtml($record) : null),
                        ]),
                ]),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Bearbeitung')->schema([
                        Forms\Components\Placeholder::make('reference_display')->label('Referenz')
                            ->content(fn (?TradeRequest $record) => $record?->reference ?? 'wird beim Speichern vergeben'),
                        Forms\Components\Select::make('status')->label('Status')->options(TradeRequestStatus::class)
                            ->default(TradeRequestStatus::New->value)->required(),
                        Forms\Components\Select::make('assigned_to')->label('Zuständig')
                            ->options(fn () => User::where('is_staff', true)->orderBy('last_name')->get()->pluck('name', 'id')),
                        Euro::input('offer_price', 'Unser Angebot / egun-Startpreis'),
                        Forms\Components\TextInput::make('egun_listing_id')->label('egun-Auktionsnummer')->maxLength(60),
                    ]),
                    Forms\Components\Section::make('Kontakt')->schema([
                        Forms\Components\TextInput::make('contact.name')->label('Name')->required()->maxLength(120),
                        Forms\Components\TextInput::make('contact.email')->label('E-Mail')->email()->required()->maxLength(190),
                        Forms\Components\TextInput::make('contact.phone')->label('Telefon')->tel()->maxLength(40),
                        Forms\Components\TextInput::make('contact.street')->label('Straße')->maxLength(200),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('contact.postal_code')->label('PLZ')->maxLength(10),
                            Forms\Components\TextInput::make('contact.city')->label('Ort')->maxLength(100)->columnSpan(2),
                        ]),
                        Forms\Components\Select::make('user_id')->label('Kundenkonto')
                            ->relationship('user', 'email')->searchable(['email', 'last_name']),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function photosHtml(TradeRequest $record): HtmlString
    {
        $record->loadMissing('media');
        if ($record->media->isEmpty()) {
            return new HtmlString('<p>Keine Fotos hochgeladen.</p>');
        }
        $disk = Storage::disk('local');
        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.75rem">';
        foreach ($record->media as $m) {
            if (! $disk->exists($m->path) || $disk->size($m->path) > 6 * 1024 * 1024) {
                continue;
            }
            $content = $disk->get($m->path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
            if (! str_starts_with($mime, 'image/')) {
                continue;
            }
            $src = 'data:'.e($mime).';base64,'.base64_encode($content);
            $html .= '<a href="'.$src.'" target="_blank" rel="noopener"><img src="'.$src.'" alt="Foto '.($m->sort_order + 1).'" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:.5rem"></a>';
        }

        return new HtmlString($html.'</div>');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->label('Referenz')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')->label('Eingang')->dateTime('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Art')->badge()
                    ->color(fn (TradeRequestType $state) => $state === TradeRequestType::Buyback ? 'info' : 'primary'),
                Tables\Columns\TextColumn::make('model')->label('Artikel')->searchable(['model', 'manufacturer_name', 'caliber'])
                    ->formatStateUsing(fn (TradeRequest $record) => trim($record->manufacturerLabel().' '.$record->model))
                    ->description(fn (TradeRequest $record) => $record->caliber)->wrap(),
                Tables\Columns\TextColumn::make('contact.name')->label('Kontakt')
                    ->description(fn (TradeRequest $record) => $record->contact['email'] ?? null),
                Tables\Columns\TextColumn::make('condition_code')->label('Zustand')->toggleable(),
                Euro::column('desired_price', 'Wunschpreis')->toggleable(),
                Euro::column('offer_price', 'Angebot')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable()
                    ->color(fn (TradeRequestStatus $state) => match ($state) {
                        TradeRequestStatus::New => 'warning',
                        TradeRequestStatus::Declined, TradeRequestStatus::Closed => 'gray',
                        TradeRequestStatus::Sold, TradeRequestStatus::PaidOut, TradeRequestStatus::Accepted => 'success',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('assignee.last_name')->label('Zuständig')
                    ->formatStateUsing(fn (TradeRequest $record) => $record->assignee?->name)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Art')->options(TradeRequestType::class),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(TradeRequestStatus::class)->multiple(),
            ])
            ->actions([
                self::pdfAction(Tables\Actions\Action::class)->iconButton()->tooltip('PDF-Dossier'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('egunCsv')->label('egun-CSV exportieren')->icon('heroicon-o-table-cells')
                    ->action(fn (Collection $records) => self::egunCsvResponse($records))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('setStatus')->label('Status setzen')->icon('heroicon-o-flag')
                    ->form([Forms\Components\Select::make('status')->label('Status')->options(TradeRequestStatus::class)->required()])
                    ->action(fn (Collection $records, array $data) => $records->each->update(['status' => $data['status']]))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /** @param  class-string<\Filament\Actions\Action|\Filament\Tables\Actions\Action>  $class */
    public static function pdfAction(string $class)
    {
        return $class::make('pdf')->label('PDF-Dossier')->icon('heroicon-o-document-text')->color('gray')
            ->action(function (TradeRequest $record) {
                try {
                    $path = app(TradeRequestService::class)->generatePdf($record);

                    return Storage::disk('local')->download($path, $record->reference.'.pdf');
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->danger()->title('PDF konnte nicht erstellt werden')->body($e->getMessage())->send();

                    return null;
                }
            });
    }

    /** @param  Collection<int, TradeRequest>  $records */
    public static function egunCsvResponse(Collection $records): StreamedResponse
    {
        $service = app(TradeRequestService::class);
        $records = TradeRequest::with('manufacturer')->whereKey($records->modelKeys())->orderBy('reference')->get();

        return response()->streamDownload(function () use ($records, $service) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8-BOM für Excel
            $header = false;
            foreach ($records as $r) {
                $row = $service->egunCsvRow($r);
                if (! $header) {
                    fputcsv($out, array_keys($row), ';');
                    $header = true;
                }
                fputcsv($out, array_map(fn ($v) => (string) $v, array_values($row)), ';');
            }
            fclose($out);
        }, 'egun-export-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTradeRequests::route('/'),
            'create' => Pages\CreateTradeRequest::route('/create'),
            'edit' => Pages\EditTradeRequest::route('/{record}/edit'),
        ];
    }
}
