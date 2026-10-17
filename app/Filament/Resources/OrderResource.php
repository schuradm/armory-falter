<?php

namespace App\Filament\Resources;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Support\Euro;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Verkauf';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Bestellung';

    protected static ?string $pluralModelLabel = 'Bestellungen';

    protected static ?string $navigationLabel = 'Bestellungen';

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function canCreate(): bool
    {
        return false; // Bestellungen entstehen ausschließlich im Checkout
    }

    public static function getNavigationBadge(): ?string
    {
        $open = Order::where('compliance_status', ComplianceStatus::Pending)
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Refunded])->count();

        return $open ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Bestellungen, die auf die Freigabe der Waffensachbearbeitung warten';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'user.email', 'user.last_name'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Bestellnr.')->searchable()->sortable()->weight('bold')->copyable(),
                Tables\Columns\TextColumn::make('placed_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.last_name')->label('Kunde')
                    ->formatStateUsing(fn (Order $record) => $record->user?->name)
                    ->description(fn (Order $record) => $record->user?->email)
                    ->searchable(['first_name', 'last_name', 'email']),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('compliance_status')->label('Freigabe')->badge()
                    ->color(fn (ComplianceStatus $state) => match ($state) {
                        ComplianceStatus::Pending => 'warning',
                        ComplianceStatus::Approved => 'success',
                        ComplianceStatus::Rejected => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_method_code')->label('Zahlart')->toggleable(),
                Tables\Columns\TextColumn::make('shipping_method_code')->label('Versand')->toggleable(isToggledHiddenByDefault: true),
                Euro::column('total_gross', 'Summe')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(OrderStatus::class)->multiple(),
                Tables\Filters\SelectFilter::make('compliance_status')->label('Freigabe')->options(ComplianceStatus::class),
                Tables\Filters\SelectFilter::make('payment_method_code')->label('Zahlart')->options(PaymentMethod::class),
                Tables\Filters\Filter::make('placed_at')->label('Zeitraum')
                    ->form([
                        DatePicker::make('from')->label('Von'),
                        DatePicker::make('until')->label('Bis'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('placed_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('placed_at', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Öffnen'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Grid::make(3)->schema([
                Infolists\Components\Group::make()->columnSpan(2)->schema([
                    Infolists\Components\Section::make('Positionen')->schema([
                        Infolists\Components\RepeatableEntry::make('items')->hiddenLabel()->contained(false)
                            ->columns(6)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')->label('Artikel')->columnSpan(2)->weight('bold')
                                    ->helperText(fn (OrderItem $record) => 'SKU '.$record->sku),
                                Infolists\Components\TextEntry::make('legal_class_code')->label('Rechtsklasse')->badge()->color('gray'),
                                Infolists\Components\TextEntry::make('quantity')->label('Menge'),
                                Euro::entry('unit_price_gross', 'Einzelpreis'),
                                Euro::entry('line_total_gross', 'Summe'),
                            ]),
                        Infolists\Components\Grid::make(4)->schema([
                            Euro::entry('subtotal_gross', 'Zwischensumme'),
                            Euro::entry('shipping_gross', 'Versand'),
                            Euro::entry('discount_gross', 'Rabatt'),
                            Euro::entry('total_gross', 'Gesamt (brutto)')->weight('bold')
                                ->helperText(fn (Order $record) => 'enthält MwSt. '.Money::format($record->total_tax)),
                        ]),
                        Infolists\Components\Grid::make(4)
                            ->visible(fn (Order $record) => $record->discount_code || $record->gift_voucher_gross > 0)
                            ->schema([
                                Infolists\Components\TextEntry::make('discount_code')->label('Rabattcode')->placeholder('—')
                                    ->url(fn (Order $record) => ($id = DiscountCode::where('code', $record->discount_code)->value('id')) ? DiscountCodeResource::getUrl('edit', ['record' => $id]) : null),
                                Euro::entry('gift_voucher_gross', 'Per Geschenkgutschein bezahlt'),
                                Infolists\Components\TextEntry::make('amount_due')->label('Zahlbetrag')->weight('bold')
                                    ->getStateUsing(fn (Order $record) => Money::format($record->amountDue())),
                            ]),
                        Infolists\Components\TextEntry::make('customer_note')->label('Anmerkung des Kunden')
                            ->visible(fn (Order $record) => filled($record->customer_note)),
                    ]),

                    Infolists\Components\Section::make('Waffenrechtliche Prüfung')
                        ->visible(fn (Order $record) => $record->requiresCompliance())
                        ->schema([
                            Infolists\Components\TextEntry::make('compliance_status')->label('Status')->badge()
                                ->color(fn (ComplianceStatus $state) => match ($state) {
                                    ComplianceStatus::Pending => 'warning',
                                    ComplianceStatus::Approved => 'success',
                                    ComplianceStatus::Rejected => 'danger',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('legal_declaration_accepted_at')->label('Erklärung zur Erwerbsberechtigung bestätigt')
                                ->dateTime('d.m.Y H:i')->placeholder('—'),
                            Infolists\Components\RepeatableEntry::make('complianceChecks')->label('Prüfschritte')->columns(4)
                                ->schema([
                                    Infolists\Components\TextEntry::make('check_type')->label('Art')
                                        ->formatStateUsing(fn (string $state) => match ($state) {
                                            'manual' => 'Freigabe Sachbearbeitung',
                                            'entitlement' => 'Erwerbsberechtigung',
                                            'age' => 'Altersprüfung',
                                            default => $state,
                                        }),
                                    Infolists\Components\TextEntry::make('result')->label('Ergebnis')->badge()
                                        ->formatStateUsing(fn (string $state) => ['passed' => 'bestanden', 'failed' => 'abgelehnt', 'pending' => 'offen'][$state] ?? $state)
                                        ->color(fn (string $state) => ['passed' => 'success', 'failed' => 'danger', 'pending' => 'warning'][$state] ?? 'gray'),
                                    Infolists\Components\TextEntry::make('entitlement')->label('Berechtigung')
                                        ->getStateUsing(fn ($record) => $record->entitlement?->describe())->placeholder('—'),
                                    Infolists\Components\TextEntry::make('details')->label('Notiz')
                                        ->getStateUsing(fn ($record) => trim(($record->details['note'] ?? '').(($record->details['original_document_seen'] ?? false) ? ' · Original gesehen' : '')) ?: null)
                                        ->placeholder('—'),
                                ]),
                        ]),

                    Infolists\Components\Section::make('Verlauf')->collapsible()->schema([
                        Infolists\Components\RepeatableEntry::make('statusHistory')->hiddenLabel()->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')->label('Zeit')->dateTime('d.m.Y H:i'),
                                Infolists\Components\TextEntry::make('to_status')->label('Status')
                                    ->formatStateUsing(fn (string $state) => OrderStatus::tryFrom($state)?->label() ?? $state),
                                Infolists\Components\TextEntry::make('comment')->label('Kommentar')->placeholder('—')->columnSpan(2),
                            ]),
                    ]),
                ]),

                Infolists\Components\Group::make()->schema([
                    Infolists\Components\Section::make('Bestellung')->schema([
                        Infolists\Components\TextEntry::make('order_number')->label('Bestellnummer')->copyable()->weight('bold'),
                        Infolists\Components\TextEntry::make('placed_at')->label('Bestellt am')->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                    ]),
                    Infolists\Components\Section::make('Kunde')->schema([
                        Infolists\Components\TextEntry::make('user.name')->label('Name')
                            ->url(fn (Order $record) => $record->user ? UserResource::getUrl('edit', ['record' => $record->user]) : null),
                        Infolists\Components\TextEntry::make('user.email')->label('E-Mail')->copyable(),
                        Infolists\Components\TextEntry::make('user.phone')->label('Telefon')->placeholder('—'),
                        Infolists\Components\TextEntry::make('user.customer_number')->label('Kundennummer'),
                    ]),
                    Infolists\Components\Section::make('Adressen')->schema([
                        Infolists\Components\TextEntry::make('billing_address')->label('Rechnungsadresse')
                            ->getStateUsing(fn (Order $record) => self::formatAddress($record->billing_address))->html(),
                        Infolists\Components\TextEntry::make('shipping_address')->label('Lieferadresse')
                            ->getStateUsing(fn (Order $record) => $record->shipping_address ? self::formatAddress($record->shipping_address) : 'wie Rechnungsadresse')->html(),
                    ]),
                    Infolists\Components\Section::make('Zahlung')->schema([
                        Infolists\Components\TextEntry::make('payment_method_code')->label('Zahlart'),
                        Infolists\Components\RepeatableEntry::make('payments')->hiddenLabel()->columns(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                                    ->formatStateUsing(fn (string $state) => [
                                        'captured' => 'Eingegangen', 'authorized' => 'Autorisiert', 'pending' => 'Ausstehend',
                                        'failed' => 'Fehlgeschlagen', 'refunded' => 'Erstattet', 'created' => 'Angelegt',
                                    ][$state] ?? $state)
                                    ->color(fn (string $state) => in_array($state, ['captured'], true) ? 'success' : (in_array($state, ['failed'], true) ? 'danger' : 'gray')),
                                Euro::entry('amount', 'Betrag'),
                                Infolists\Components\TextEntry::make('provider_reference')->label('Referenz')->columnSpanFull(),
                            ]),
                    ]),
                    Infolists\Components\Section::make('Versand')->schema([
                        Infolists\Components\TextEntry::make('shippingMethod.name')->label('Versandart')
                            ->placeholder(fn (Order $record) => $record->shipping_method_code),
                        Infolists\Components\RepeatableEntry::make('shipments')->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('tracking_number')->label('Sendungsnummer')->copyable()->placeholder('—')
                                    ->url(fn ($record) => $record->trackingUrl())->openUrlInNewTab(),
                                Infolists\Components\TextEntry::make('status')->label('Status')
                                    ->getStateUsing(fn ($record) => $record->statusLabel()),
                                Infolists\Components\TextEntry::make('shipped_at')->label('Versendet')->dateTime('d.m.Y H:i')->placeholder('—'),
                            ]),
                    ]),
                    Infolists\Components\Section::make('Gekaufte Geschenkgutscheine')
                        ->visible(fn (Order $record) => $record->items->contains(fn ($i) => $i->isGiftVoucher()))
                        ->schema([
                            Infolists\Components\RepeatableEntry::make('issuedGiftVouchers')->hiddenLabel()->columns(2)
                                ->placeholder('Wird nach Zahlungseingang erzeugt und versendet.')
                                ->schema([
                                    Infolists\Components\TextEntry::make('code')->label('Code')
                                        ->formatStateUsing(fn ($record) => '…'.substr($record->code, -8))
                                        ->url(fn ($record) => GiftVoucherResource::getUrl('view', ['record' => $record])),
                                    Euro::entry('balance', 'Restguthaben'),
                                ]),
                        ]),
                    Infolists\Components\Section::make('Rechnungen')->visible(fn (Order $record) => $record->invoices->isNotEmpty())->schema([
                        Infolists\Components\RepeatableEntry::make('invoices')->hiddenLabel()->columns(2)->schema([
                            Infolists\Components\TextEntry::make('invoice_number')->label('Nummer'),
                            Infolists\Components\TextEntry::make('issued_at')->label('Datum')->date('d.m.Y'),
                        ]),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function formatAddress(?array $a): string
    {
        if (! $a) {
            return '—';
        }
        $lines = array_filter([
            $a['company_name'] ?? null,
            trim(($a['first_name'] ?? '').' '.($a['last_name'] ?? '')),
            trim(($a['street'] ?? '').' '.($a['house_number'] ?? '')),
            $a['address_extra'] ?? null,
            trim(($a['postal_code'] ?? '').' '.($a['city'] ?? '')),
            ($a['country_code'] ?? 'DE') !== 'DE' ? ($a['country_code'] ?? null) : null,
        ]);

        return implode('<br>', array_map('e', $lines));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
