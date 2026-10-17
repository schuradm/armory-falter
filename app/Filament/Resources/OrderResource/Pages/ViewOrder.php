<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\InvoiceService;
use App\Services\Orders\OrderService;
use App\Support\Money;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Bestellung '.$this->getRecord()->order_number;
    }

    private const RELATIONS = [
        'user', 'items', 'payments', 'shipments.method', 'invoices', 'shippingMethod', 'statusHistory',
        'complianceChecks.entitlement.caliber', 'issuedGiftVouchers',
    ];

    protected function resolveRecord(int|string $key): Order
    {
        /** @var Order $order */
        $order = parent::resolveRecord($key);

        return $order->load(self::RELATIONS);
    }

    /**
     * Livewire stellt bei Folgeanfragen (z. B. Aktion öffnen) nur einen Teil der Relationen wieder her
     * (Schnittmenge über alle Prüfschritte, Prüfschritt ohne Berechtigung → ohne entitlement.caliber).
     * Ohne Nachladen bricht das Infolist mit LazyLoadingViolation (500) ab.
     */
    public function hydrate(): void
    {
        parent::hydrate();
        $this->getRecord()->loadMissing(self::RELATIONS);
    }

    private function refreshRecord(): void
    {
        $this->record = $this->resolveRecord($this->getRecord()->getRouteKey());
    }

    /** Führt eine Service-Aktion aus und zeigt Fehler als Benachrichtigung statt als 500. */
    private function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (Throwable $e) {
            report($e);
            Notification::make()->danger()->title('Aktion fehlgeschlagen')->body($e->getMessage())->persistent()->send();
        }
        $this->refreshRecord();
    }

    private const CARRIERS = ['dhl' => 'DHL', 'dpd' => 'DPD', 'ups' => 'UPS', 'gls' => 'GLS', 'hermes' => 'Hermes', 'other' => 'Sonstige'];

    /** Versandart-Dienstleister ist Freitext („DHL“, „dhl_express“) – auf einen Auswahlschlüssel abbilden. */
    private static function defaultCarrier(Order $order): string
    {
        $carrier = Str::before(Str::lower((string) $order->shippingMethod?->carrier), '_');

        return array_key_exists($carrier, self::CARRIERS) ? $carrier : 'dhl';
    }

    private static function canRelease(): bool
    {
        return (bool) auth()->user()?->can('compliance.release');
    }

    private static function awaitsRelease(Order $order): bool
    {
        return $order->compliance_status === ComplianceStatus::Pending && self::isOpen($order) && self::canRelease();
    }

    private static function isOpen(Order $order): bool
    {
        return ! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::Completed], true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('releaseCompliance')->label('Freigeben')->icon('heroicon-o-shield-check')->color('success')
                    ->visible(fn (Order $record) => self::awaitsRelease($record))
                    ->modalHeading('Erwerbsberechtigung freigeben')
                    ->modalDescription('Bestätigt die Prüfung der Erwerbsberechtigung (§ 34 WaffG). Verbraucht die zugeordneten Berechtigungen und wird im Audit-Log dokumentiert.')
                    ->form([
                        Forms\Components\Textarea::make('note')->label('Prüfvermerk')->required()->rows(3)
                            ->placeholder('z. B. WBK Nr. … geprüft, Voreintrag passt zu Waffenart und Kaliber.'),
                        Forms\Components\Toggle::make('original_document_seen')->label('Originaldokument gesehen bzw. wird bei Übergabe geprüft'),
                    ])
                    ->action(fn (array $data, Order $record) => $this->run(
                        fn () => app(OrderService::class)->releaseCompliance($record, auth()->user(), $data['note'], (bool) ($data['original_document_seen'] ?? false)),
                        'Bestellung freigegeben',
                    )),
                Actions\Action::make('holdCompliance')->label('Zurückhalten')->icon('heroicon-o-pause-circle')->color('warning')
                    ->visible(fn (Order $record) => self::awaitsRelease($record))
                    ->modalHeading('Unterlagen nachfordern')
                    ->modalDescription('Der Kunde wird per E-Mail informiert. Die Bestellung bleibt zur Freigabe offen.')
                    ->form([
                        Forms\Components\Textarea::make('message')->label('Nachricht an den Kunden')->required()->rows(4)
                            ->placeholder('Bitte laden Sie Ihre WBK mit gültigem Voreintrag im Kundenkonto hoch.'),
                    ])
                    ->action(fn (array $data, Order $record) => $this->run(
                        fn () => app(OrderService::class)->holdCompliance($record, auth()->user(), $data['message']),
                        'Bestellung zurückgehalten, Kunde informiert',
                    )),
                Actions\Action::make('rejectCompliance')->label('Ablehnen')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Order $record) => self::awaitsRelease($record))
                    ->modalHeading('Bestellung ablehnen und stornieren')
                    ->modalDescription('Die Bestellung wird storniert und der Bestand zurückgebucht. Der Kunde erhält die Begründung per E-Mail.')
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Begründung')->required()->rows(3),
                    ])
                    ->action(fn (array $data, Order $record) => $this->run(
                        fn () => app(OrderService::class)->rejectCompliance($record, auth()->user(), $data['reason']),
                        'Bestellung abgelehnt und storniert',
                    )),
            ])
                ->label('Compliance')->icon('heroicon-o-shield-exclamation')->color('warning')->button(),

            Actions\Action::make('markPaid')->label('Zahlung erhalten')->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn (Order $record) => $record->status === OrderStatus::PendingPayment
                    && ! in_array($record->compliance_status, [ComplianceStatus::Pending, ComplianceStatus::Rejected], true))
                ->modalHeading('Zahlungseingang buchen')
                ->modalDescription(fn (Order $record) => 'Betrag: '.Money::format($record->amountDue()).'. Die Rechnung wird automatisch erstellt.')
                ->form([
                    Forms\Components\TextInput::make('reference')->label('Referenz (z. B. Kontoauszug, Verwendungszweck)')
                        ->default('manuell')->required()->maxLength(60),
                ])
                ->action(fn (array $data, Order $record) => $this->run(
                    fn () => app(OrderService::class)->markPaid($record, auth()->user(), $data['reference']),
                    'Zahlung gebucht',
                )),

            Actions\Action::make('ship')->label(fn (Order $record) => $record->shippingMethod?->isPickup() ? 'Abholbereit melden' : 'Versenden')
                ->icon('heroicon-o-truck')->color('primary')
                ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::Paid, OrderStatus::Processing], true)
                    && ! in_array($record->compliance_status, [ComplianceStatus::Pending, ComplianceStatus::Rejected], true)
                    && $record->shippingMethod !== null)
                ->modalHeading('Versand erfassen')
                ->form(fn (Order $record) => $record->shippingMethod?->isPickup() ? [] : [
                    Forms\Components\Select::make('carrier')->label('Versanddienstleister')->required()
                        ->options(self::CARRIERS)
                        ->default(self::defaultCarrier($record)),
                    Forms\Components\TextInput::make('tracking_number')->label('Sendungsnummer')->maxLength(60)
                        ->required(fn () => ! $record->shippingMethod?->isPickup()),
                ])
                ->action(fn (array $data, Order $record) => $this->run(
                    fn () => app(OrderService::class)->ship($record, auth()->user(), $data['carrier'] ?? 'pickup', $data['tracking_number'] ?? null),
                    $record->shippingMethod?->isPickup() ? 'Als abholbereit gemeldet' : 'Versand erfasst, Kunde informiert',
                )),

            Actions\Action::make('markDelivered')->label(fn (Order $record) => $record->status === OrderStatus::ReadyForPickup ? 'Übergeben' : 'Zugestellt')
                ->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::Shipped, OrderStatus::ReadyForPickup], true))
                ->requiresConfirmation()
                ->modalDescription('Ab jetzt kann der Kunde die Artikel bewerten. Die Bewertungseinladung folgt nach der eingestellten Zahl von Tagen, sofern er nicht widersprochen hat.')
                ->action(fn (Order $record) => $this->run(
                    fn () => app(OrderService::class)->markDelivered($record, auth()->user()),
                    'Als zugestellt markiert',
                )),

            Actions\Action::make('invoice')->label('Rechnung (PDF)')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->visible(fn (Order $record) => $record->invoices->isNotEmpty()
                    || in_array($record->status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::ReadyForPickup, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed], true))
                ->action(function (Order $record) {
                    try {
                        $invoice = $record->invoices->firstWhere('type', 'invoice') ?? app(InvoiceService::class)->createFor($record);
                        if (! Storage::disk('local')->exists($invoice->pdf_path)) {
                            throw new \RuntimeException('PDF-Datei nicht gefunden: '.$invoice->pdf_path);
                        }

                        return Storage::disk('local')->download($invoice->pdf_path, $invoice->invoice_number.'.pdf');
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Rechnung konnte nicht erstellt werden')->body($e->getMessage())->send();

                        return null;
                    }
                }),

            Actions\Action::make('cancel')->label('Stornieren')->icon('heroicon-o-no-symbol')->color('danger')->outlined()
                ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::PendingPayment, OrderStatus::PendingCompliance, OrderStatus::ComplianceHold, OrderStatus::Paid, OrderStatus::Processing], true))
                ->requiresConfirmation()
                ->modalDescription('Der Bestand wird zurückgebucht und der Kunde informiert. Bereits gezahlte Beträge bitte separat erstatten.')
                ->form([Forms\Components\Textarea::make('reason')->label('Grund')->required()->rows(2)])
                ->action(fn (array $data, Order $record) => $this->run(
                    fn () => app(OrderService::class)->cancel($record, auth()->user(), $data['reason']),
                    'Bestellung storniert',
                )),
        ];
    }
}
