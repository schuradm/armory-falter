<?php

namespace App\Filament\Resources;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\EntitlementType;
use App\Filament\Resources\CustomerDocumentResource\Pages;
use App\Models\Caliber;
use App\Models\CustomerDocument;
use App\Services\AuditLogger;
use App\Services\Documents\DocumentService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Prüfung hochgeladener Erwerbs- und Ausweisdokumente. Zugriff nur mit Recht `compliance.release`,
 * weil hier personenbezogene Ausweisdaten sichtbar sind (Datenminimierung).
 */
class CustomerDocumentResource extends Resource
{
    protected static ?string $model = CustomerDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Kunden & Prüfung';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Kundendokument';

    protected static ?string $pluralModelLabel = 'Kundendokumente';

    protected static ?string $navigationLabel = 'Dokumentenprüfung';

    public const WEAPON_KINDS = ['any' => 'Beliebig', 'kurzwaffe' => 'Kurzwaffe', 'langwaffe' => 'Langwaffe'];

    public static function canReview(): bool
    {
        return (bool) auth()->user()?->can('compliance.release');
    }

    public static function canViewAny(): bool
    {
        return self::canReview();
    }

    public static function canView(Model $record): bool
    {
        return self::canReview();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canReview();
    }

    public static function canCreate(): bool
    {
        return false; // Upload nur durch Kunden im Kundenkonto
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Löschung über das Löschkonzept (DocumentService::housekeeping)
    }

    public static function getNavigationBadge(): ?string
    {
        if (! self::canReview()) {
            return null;
        }
        $n = CustomerDocument::awaitingReview()->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'reviewer']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Angaben zum Dokument')->columns(2)
                ->description('Korrektur der vom Kunden eingegebenen Daten. Die Datei selbst kann nicht geändert werden.')
                ->schema([
                    Forms\Components\Select::make('type')->label('Dokumentart')->options(DocumentType::class)->required(),
                    Forms\Components\TextInput::make('document_number')->label('Dokumentnummer')->maxLength(100),
                    Forms\Components\TextInput::make('issuing_authority')->label('Ausstellende Behörde')->maxLength(200),
                    Forms\Components\DatePicker::make('issued_at')->label('Ausgestellt am')->native(false)->displayFormat('d.m.Y'),
                    Forms\Components\DatePicker::make('valid_until')->label('Gültig bis')->native(false)->displayFormat('d.m.Y'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Eingang')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.last_name')->label('Kunde')
                    ->formatStateUsing(fn (CustomerDocument $record) => $record->user?->name)
                    ->description(fn (CustomerDocument $record) => $record->user?->customer_number)
                    ->searchable(['first_name', 'last_name', 'email', 'customer_number']),
                Tables\Columns\TextColumn::make('type')->label('Dokument')->wrap(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->wrap(),
                Tables\Columns\TextColumn::make('flags')->label('Hinweise')
                    ->getStateUsing(fn (CustomerDocument $record) => count($record->precheck_result['flags'] ?? []) ?: null)
                    ->formatStateUsing(fn ($state) => $state.' Auffälligkeit(en)')
                    ->color('warning')->placeholder('—'),
                Tables\Columns\TextColumn::make('valid_until')->label('Gültig bis')->date('d.m.Y')->sortable()->placeholder('unbefristet'),
                Tables\Columns\TextColumn::make('reviewer.last_name')->label('Geprüft von')
                    ->formatStateUsing(fn (CustomerDocument $record) => $record->reviewer?->name)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('awaiting')->label('Wartet auf Prüfung')->default()
                    ->query(fn (Builder $query) => $query->awaitingReview()),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(DocumentStatus::class),
                Tables\Filters\SelectFilter::make('type')->label('Dokumentart')->options(DocumentType::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Prüfen'),
                self::downloadAction(Tables\Actions\Action::class),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Grid::make(3)->schema([
                Infolists\Components\Section::make('Datei')->columnSpan(2)->schema([
                    Infolists\Components\TextEntry::make('preview')->hiddenLabel()
                        ->getStateUsing(fn (CustomerDocument $record) => self::previewHtml($record)),
                    Infolists\Components\TextEntry::make('original_name')->label('Originaldateiname')
                        ->helperText(fn (CustomerDocument $record) => $record->mime_type.' · '.number_format($record->file_size / 1024, 0, ',', '.').' KB'),
                ]),
                Infolists\Components\Group::make()->schema([
                    Infolists\Components\Section::make('Kunde')->schema([
                        Infolists\Components\TextEntry::make('user.name')->label('Name')
                            ->url(fn (CustomerDocument $record) => $record->user ? UserResource::getUrl('edit', ['record' => $record->user]) : null),
                        Infolists\Components\TextEntry::make('user.customer_number')->label('Kundennummer'),
                        Infolists\Components\TextEntry::make('user.birth_date')->label('Geburtsdatum (Konto)')->date('d.m.Y')->placeholder('nicht angegeben')
                            ->helperText(fn (CustomerDocument $record) => $record->user?->age_verified_at ? 'Alter bestätigt am '.$record->user->age_verified_at->format('d.m.Y') : 'Alter nicht bestätigt'),
                    ]),
                    Infolists\Components\Section::make('Dokument')->schema([
                        Infolists\Components\TextEntry::make('type')->label('Art'),
                        Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                        Infolists\Components\TextEntry::make('document_number')->label('Dokumentnummer')->placeholder('—')->copyable(),
                        Infolists\Components\TextEntry::make('issuing_authority')->label('Behörde')->placeholder('—'),
                        Infolists\Components\TextEntry::make('issued_at')->label('Ausgestellt')->date('d.m.Y')->placeholder('—'),
                        Infolists\Components\TextEntry::make('valid_until')->label('Gültig bis')->date('d.m.Y')->placeholder('unbefristet'),
                        Infolists\Components\TextEntry::make('rejection_reason')->label('Ablehnungsgrund')->color('danger')
                            ->visible(fn (CustomerDocument $record) => filled($record->rejection_reason)),
                        Infolists\Components\TextEntry::make('reviewer.name')->label('Geprüft von')
                            ->visible(fn (CustomerDocument $record) => $record->reviewed_by !== null)
                            ->helperText(fn (CustomerDocument $record) => $record->reviewed_at?->format('d.m.Y H:i')),
                    ]),
                    Infolists\Components\Section::make('Automatische Vorprüfung')->schema([
                        Infolists\Components\TextEntry::make('precheck_flags')->label('Auffälligkeiten')
                            ->getStateUsing(fn (CustomerDocument $record) => $record->precheck_result['flags'] ?? [])
                            ->listWithLineBreaks()->bulleted()->color('warning')->placeholder('Keine'),
                        Infolists\Components\TextEntry::make('precheck_info')->label('Info')
                            ->getStateUsing(fn (CustomerDocument $record) => collect($record->precheck_result['info'] ?? [])
                                ->map(fn ($v, $k) => ($k === 'dimensions' ? 'Auflösung' : $k).': '.$v)->values()->all())
                            ->listWithLineBreaks()->placeholder('—'),
                    ]),
                    Infolists\Components\Section::make('Angelegte Berechtigungen')
                        ->visible(fn (CustomerDocument $record) => $record->entitlements->isNotEmpty())
                        ->schema([
                            Infolists\Components\TextEntry::make('entitlement_list')->hiddenLabel()
                                ->getStateUsing(fn (CustomerDocument $record) => $record->entitlements->map(fn ($e) => $e->describe().' ('.$e->status.')')->all())
                                ->listWithLineBreaks()->bulleted(),
                        ]),
                ]),
            ]),
        ]);
    }

    /** Bildvorschau inline (entschlüsselt, nur im Speicher); PDFs über den Download. */
    public static function previewHtml(CustomerDocument $doc): HtmlString
    {
        if ($doc->storage_path === 'deleted') {
            return new HtmlString('<p>Die Datei wurde nach dem Löschkonzept bereits gelöscht.</p>');
        }
        if (! str_starts_with((string) $doc->mime_type, 'image/') || $doc->file_size > 8 * 1024 * 1024) {
            return new HtmlString('<p>Keine Vorschau für diesen Dateityp. Bitte über „Datei öffnen“ herunterladen.</p>');
        }
        try {
            $content = app(DocumentService::class)->contents($doc);
        } catch (Throwable $e) {
            report($e);

            return new HtmlString('<p style="color:#dc2626">Datei konnte nicht entschlüsselt werden.</p>');
        }
        $src = 'data:'.e($doc->mime_type).';base64,'.base64_encode($content);

        return new HtmlString('<img src="'.$src.'" alt="Vorschau '.e($doc->type->label()).'" style="max-width:100%;max-height:75vh;border-radius:.5rem;object-fit:contain" referrerpolicy="no-referrer">');
    }

    /**
     * Download der entschlüsselten Datei als Livewire-Download (kein öffentlicher Pfad, kein Cache).
     *
     * @param  class-string<Action|Tables\Actions\Action>  $class
     */
    public static function downloadAction(string $class)
    {
        return $class::make('download')->label('Datei öffnen')->icon('heroicon-o-arrow-down-tray')->color('gray')
            ->visible(fn (CustomerDocument $record) => $record->storage_path !== 'deleted' && self::canReview())
            ->action(function (CustomerDocument $record): ?StreamedResponse {
                try {
                    $content = app(DocumentService::class)->contents($record);
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->danger()->title('Datei konnte nicht geöffnet werden')->body($e->getMessage())->send();

                    return null;
                }
                app(AuditLogger::class)->log('document.downloaded', $record);
                $ext = match ($record->mime_type) {
                    'application/pdf' => 'pdf', 'image/png' => 'png', 'image/jpeg' => 'jpg', default => 'bin',
                };
                $name = 'dokument-'.$record->id.'-'.$record->type->value.'.'.$ext;

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $name, [
                    'Content-Type' => $record->mime_type,
                    'Cache-Control' => 'no-store, private',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            });
    }

    /** @param  class-string<Action|Tables\Actions\Action>  $class */
    public static function approveAction(string $class)
    {
        return $class::make('approve')->label('Freigeben')->icon('heroicon-o-check-badge')->color('success')
            ->visible(fn (CustomerDocument $record) => $record->status->awaitingReview() && self::canReview())
            ->modalHeading('Dokument freigeben')
            ->modalDescription('Lege die Berechtigungen an, die dieses Dokument belegt. Sie werden bei der Bestellprüfung verwendet.')
            ->modalWidth('3xl')
            ->fillForm(fn (CustomerDocument $record) => [
                'entitlements' => collect($record->type->suggestedEntitlements())->map(fn (EntitlementType $t) => [
                    'type' => $t->value,
                    'weapon_kind' => $t === EntitlementType::WbkVoreintrag ? 'any' : null,
                    'caliber_id' => null,
                    'quantity_total' => $t === EntitlementType::WbkVoreintrag ? 1 : null,
                    'valid_until' => $record->valid_until?->toDateString(),
                ])->all(),
                'confirm_birth_date' => $record->type->provesAge(),
                'birth_date' => $record->user?->birth_date?->toDateString(),
            ])
            ->form(fn (CustomerDocument $record) => [
                Forms\Components\Repeater::make('entitlements')->label('Berechtigungen')
                    ->defaultItems(0)->addActionLabel('Berechtigung hinzufügen')->columns(5)
                    ->schema([
                        Forms\Components\Select::make('type')->label('Art')->options(EntitlementType::class)->required()->columnSpan(2),
                        Forms\Components\Select::make('weapon_kind')->label('Waffenart')->options(self::WEAPON_KINDS),
                        Forms\Components\Select::make('caliber_id')->label('Kaliber')
                            ->options(fn () => Caliber::orderBy('name')->pluck('name', 'id'))->searchable(),
                        Forms\Components\TextInput::make('quantity_total')->label('Anzahl')->numeric()->integer()->minValue(1)
                            ->helperText('Leer = unbegrenzt'),
                        Forms\Components\DatePicker::make('valid_until')->label('Gültig bis')->native(false)->displayFormat('d.m.Y')->columnSpan(2),
                    ]),
                Forms\Components\Toggle::make('confirm_birth_date')->label('Geburtsdatum anhand des Dokuments bestätigen (Altersprüfung)')
                    ->visible($record->type->provesAge())->live(),
                Forms\Components\DatePicker::make('birth_date')->label('Geburtsdatum laut Dokument')->native(false)->displayFormat('d.m.Y')
                    ->visible(fn (Forms\Get $get) => $record->type->provesAge() && $get('confirm_birth_date'))
                    ->required(fn (Forms\Get $get) => (bool) $get('confirm_birth_date'))
                    ->beforeOrEqual(today()->subYears(14)->toDateString()),
            ])
            ->action(function (array $data, CustomerDocument $record) {
                try {
                    $entitlements = collect($data['entitlements'] ?? [])->map(fn ($e) => [
                        'type' => $e['type'] instanceof EntitlementType ? $e['type'] : EntitlementType::from($e['type']),
                        'weapon_kind' => $e['weapon_kind'] ?? null,
                        'caliber_id' => $e['caliber_id'] ?? null,
                        'quantity_total' => filled($e['quantity_total'] ?? null) ? (int) $e['quantity_total'] : null,
                        'valid_until' => $e['valid_until'] ?? null,
                    ])->values()->all();
                    app(DocumentService::class)->approve($record, auth()->user(), $entitlements,
                        (bool) ($data['confirm_birth_date'] ?? false), $data['birth_date'] ?? null);
                    Notification::make()->success()->title('Dokument freigegeben')->send();
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->danger()->title('Freigabe fehlgeschlagen')->body($e->getMessage())->persistent()->send();
                }
            });
    }

    /** @param  class-string<Action|Tables\Actions\Action>  $class */
    public static function rejectAction(string $class)
    {
        return $class::make('reject')->label('Ablehnen')->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (CustomerDocument $record) => $record->status->awaitingReview() && self::canReview())
            ->modalHeading('Dokument ablehnen')
            ->modalDescription('Der Kunde erhält die Begründung per E-Mail und kann ein neues Dokument hochladen.')
            ->form([
                Forms\Components\Select::make('preset')->label('Vorlage')->dehydrated(false)->live()
                    ->options([
                        'Das Dokument ist nicht vollständig lesbar. Bitte laden Sie einen scharfen Scan aller relevanten Seiten hoch.' => 'Nicht lesbar',
                        'Das Dokument ist abgelaufen. Bitte laden Sie ein gültiges Dokument hoch.' => 'Abgelaufen',
                        'Name oder Geburtsdatum stimmen nicht mit Ihrem Kundenkonto überein.' => 'Daten passen nicht',
                        'Es fehlt die Seite mit dem Voreintrag bzw. den eingetragenen Waffen.' => 'Seite fehlt',
                    ])
                    ->afterStateUpdated(fn (?string $state, Forms\Set $set) => $state ? $set('reason', $state) : null),
                Forms\Components\Textarea::make('reason')->label('Begründung (geht an den Kunden)')->required()->maxLength(500)->rows(4),
            ])
            ->action(function (array $data, CustomerDocument $record) {
                try {
                    app(DocumentService::class)->reject($record, auth()->user(), $data['reason']);
                    Notification::make()->success()->title('Dokument abgelehnt')->send();
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->danger()->title('Ablehnung fehlgeschlagen')->body($e->getMessage())->send();
                }
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerDocuments::route('/'),
            'view' => Pages\ViewCustomerDocument::route('/{record}'),
            'edit' => Pages\EditCustomerDocument::route('/{record}/edit'),
        ];
    }
}
