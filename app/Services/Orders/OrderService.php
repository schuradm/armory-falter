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
        $order->update(['status' => $to]);
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

        $next = $order->payment_method_code === PaymentMethod::CashPickup ? OrderStatus::Processing : OrderStatus::PendingPayment;
        $this->transition($order, $next, $officer, 'Erwerbsberechtigung geprüft und freigegeben.');
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
        $order->payments()->create([
            'provider' => $order->payment_method_code->value,
            'provider_reference' => $reference.'-'.$order->order_number,
            'status' => 'captured',
            'amount' => $order->total_gross,
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

    public function cancel(Order $order, ?User $by, string $reason): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items()->with('product')->get() as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
                if ($item->product->is_unique_item) {
                    $item->product->update(['availability' => 'in_stock']);
                }
            }
        });
        $this->transition($order, OrderStatus::Cancelled, $by, $reason);
    }
}
