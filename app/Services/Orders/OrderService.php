<?php

namespace App\Services\Orders;

use App\Enums\ComplianceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\CustomerEntitlement;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Services\AuditLogger;
use App\Services\Vouchers\DiscountCodeService;
use App\Services\Vouchers\GiftVoucherService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(private AuditLogger $audit, private InvoiceService $invoices) {}

    public function transition(Order $order, OrderStatus $to, ?User $by = null, ?string $comment = null, bool $notify = true): void
    {
        $from = $order->status;
        if ($from === $to) {
            return;
        }
        $order->update(['status' => $to]
            // Zustellzeitpunkt: Grundlage für Bewertungsrecht und Bewertungseinladung
            + (in_array($to, [OrderStatus::Delivered, OrderStatus::Completed], true) && ! $order->delivered_at ? ['delivered_at' => now()] : []));
        $order->statusHistory()->create([
            'from_status' => $from->value,
            'to_status' => $to->value,
            'comment' => $comment,
            'notify_customer' => $notify,
            'changed_by' => $by?->id,
        ]);
        $this->audit->log('order.status_changed', $order, ['status' => $from->value], ['status' => $to->value, 'comment' => $comment]);

        if ($notify) {
            $order->user->notify(new OrderStatusChanged($order, $comment));
        }

        // Gekaufte Geschenkgutscheine erst nach Zahlungseingang ausgeben (idempotent)
        if ($to === OrderStatus::Paid) {
            app(GiftVoucherService::class)->issueFor($order);
        }
    }

    /**
     * Freigabe einer erlaubnispflichtigen Bestellung durch die Waffensachbearbeitung.
     * Verbraucht die zugeordneten Erwerbsberechtigungen (z. B. Voreintrag) und dokumentiert die Prüfung.
     */
    public function releaseCompliance(Order $order, User $officer, string $note, bool $originalDocumentSeen): void
    {
        if ($order->compliance_status !== ComplianceStatus::Pending) {
            throw new InvalidArgumentException('Diese Bestellung wartet nicht auf eine Freigabe.');
        }
        if (! $officer->can('compliance.release')) {
            throw new InvalidArgumentException('Keine Berechtigung zur Freigabe.');
        }

        DB::transaction(function () use ($order, $officer, $note, $originalDocumentSeen) {
            foreach ($order->complianceChecks()->where('result', 'pending')->with('orderItem')->get() as $check) {
                if ($check->customer_entitlement_id) {
                    $ent = CustomerEntitlement::lockForUpdate()->find($check->customer_entitlement_id);
                    $qty = $check->orderItem?->quantity ?? 1;
                    if ($ent->quantity_total !== null) {
                        if ($ent->remaining() < $qty) {
                            throw new InvalidArgumentException('Die Erwerbsberechtigung ist bereits verbraucht: '.$ent->describe());
                        }
                        $ent->quantity_used += $qty;
                        $ent->status = $ent->remaining() === 0 ? 'consumed' : $ent->status;
                        $ent->save();
                    }
                }
                $check->update([
                    'result' => 'passed',
                    'checked_by' => $officer->id,
                    'checked_at' => now(),
                    'details' => array_merge($check->details ?? [], ['note' => $note, 'original_document_seen' => $originalDocumentSeen]),
                ]);
            }
            $order->complianceChecks()->create([
                'check_type' => 'manual', 'result' => 'passed', 'checked_by' => $officer->id, 'checked_at' => now(),
                'details' => ['note' => $note, 'original_document_seen' => $originalDocumentSeen],
            ]);
            $order->update(['compliance_status' => ComplianceStatus::Approved]);
        });

        $next = match (true) {
            $order->amountDue() === 0 => OrderStatus::Paid, // vollständig per Geschenkgutschein bezahlt
            $order->payment_method_code === PaymentMethod::CashPickup => OrderStatus::Processing,
            default => OrderStatus::PendingPayment,
        };
        $this->transition($order, $next, $officer, 'Erwerbsberechtigung geprüft und freigegeben.');
        if ($next === OrderStatus::Paid) {
            $this->invoices->createFor($order);
        }
        $this->audit->log('order.compliance_released', $order, new: ['note' => $note, 'original_seen' => $originalDocumentSeen]);
    }

    public function holdCompliance(Order $order, User $officer, string $message): void
    {
        $this->transition($order, OrderStatus::ComplianceHold, $officer, $message);
    }

    public function rejectCompliance(Order $order, User $officer, string $reason): void
    {
        $order->update(['compliance_status' => ComplianceStatus::Rejected]);
        $order->complianceChecks()->where('result', 'pending')->update(['result' => 'failed', 'checked_by' => $officer->id, 'checked_at' => now()]);
        $this->cancel($order, $officer, 'Erwerbsberechtigung nicht ausreichend: '.$reason);
    }

    public function markPaid(Order $order, ?User $by = null, string $reference = 'manuell'): void
    {
        // Doppelte Meldung (Webhook nach Rückleitung, zweiter Klick): keine zweite Zahlung, kein Rücksprung im Status
        if (in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::ReadyForPickup, OrderStatus::Shipped,
            OrderStatus::Delivered, OrderStatus::Completed, OrderStatus::Refunded], true)) {
            return;
        }
        $order->payments()->create([
            'provider' => $order->payment_method_code->value,
            'provider_reference' => $reference.'-'.$order->order_number,
            'status' => 'captured',
            'amount' => $order->amountDue(), // Gutscheinanteil ist bereits als Zahlung erfasst
        ]);
        $this->transition($order, OrderStatus::Paid, $by, 'Zahlung eingegangen.');
        $this->invoices->createFor($order);
    }

    public function ship(Order $order, User $by, string $carrier, ?string $trackingNumber): Shipment
    {
        $shipment = $order->shipments()->create([
            'shipping_method_id' => $order->shippingMethod->id,
            'carrier' => $carrier,
            'tracking_number' => $trackingNumber,
            'status' => 'handed_over',
            'shipped_at' => now(),
        ]);
        // Klarna Rechnung/Raten: Abbuchung erst beim Versand (Klarna-Vorgabe)
        if ($payment = $order->payments()->where('provider', 'klarna')->where('status', 'authorized')->first()) {
            app(\App\Services\Payment\KlarnaGateway::class)->capture($payment, $trackingNumber, $carrier);
        }
        $this->transition($order, $order->shippingMethod->isPickup() ? OrderStatus::ReadyForPickup : OrderStatus::Shipped, $by,
            $trackingNumber ? "Sendungsnummer: {$trackingNumber}" : null);

        return $shipment;
    }

    /** Zugestellt (Versand) bzw. übergeben (Abholung). Ab hier darf der Kunde bewerten. */
    public function markDelivered(Order $order, ?User $by = null): void
    {
        if (! in_array($order->status, [OrderStatus::Shipped, OrderStatus::ReadyForPickup], true)) {
            throw new InvalidArgumentException('Nur versendete oder abholbereite Bestellungen können als zugestellt markiert werden.');
        }
        $this->transition($order, $order->status === OrderStatus::ReadyForPickup ? OrderStatus::Completed : OrderStatus::Delivered, $by,
            $order->status === OrderStatus::ReadyForPickup ? 'Im Geschäft übergeben.' : 'Sendung zugestellt.');
    }

    public function cancel(Order $order, ?User $by, string $reason): void
    {
        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            throw new InvalidArgumentException('Diese Bestellung ist bereits storniert.'); // sonst Bestand doppelt zurückgebucht
        }

        $notes = DB::transaction(function () use ($order, $by) {
            // Rabattcode-Einlösung freigeben, eingelöstes Gutscheinguthaben zurückbuchen, gekaufte Gutscheine sperren
            app(DiscountCodeService::class)->release($order);
            $vouchers = app(GiftVoucherService::class);
            $vouchers->release($order, $by);
            $notes = $vouchers->cancelIssued($order, $by);

            foreach ($order->items()->with('product')->get() as $item) {
                if ($item->isGiftVoucher()) {
                    continue; // kein Lagerbestand
                }
                $item->product->increment('stock_quantity', $item->quantity);
                // Beim Bestellen automatisch auf „ausverkauft“ gesetzte Artikel wieder verkaufbar machen
                if ($item->product->is_unique_item || ($item->product->availability->value === 'out_of_stock' && $item->product->stock_quantity > 0)) {
                    $item->product->update(['availability' => 'in_stock']);
                }
            }

            // Bei der Freigabe verbrauchte Erwerbsberechtigungen (z. B. Voreintrag) zurückgeben – die Waffe wurde nicht überlassen
            foreach ($order->complianceChecks()->where('result', 'passed')->whereNotNull('customer_entitlement_id')->with('orderItem')->get() as $check) {
                $ent = CustomerEntitlement::lockForUpdate()->find($check->customer_entitlement_id);
                if ($ent && $ent->quantity_total !== null && $ent->quantity_used > 0) {
                    $ent->quantity_used = max(0, $ent->quantity_used - ($check->orderItem?->quantity ?? 1));
                    $ent->status = $ent->status === 'consumed' ? 'active' : $ent->status;
                    $ent->save();
                }
            }

            return $notes;
        });
        $this->transition($order, OrderStatus::Cancelled, $by, trim($reason.' '.implode(' ', $notes)));
    }
}
