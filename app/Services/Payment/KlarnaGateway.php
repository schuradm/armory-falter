<?php

namespace App\Services\Payment;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klarna Payments über die Hosted Payment Page (HPP): kein JavaScript-SDK im Shop, nur Weiterleitung.
 *  - Sofortüberweisung → Kategorie „pay_now“, Klarna legt die Order an und bucht sofort ab (CAPTURE_ORDER).
 *  - Klarna Rechnung/Raten → „pay_later“/„pay_over_time“, Order wird autorisiert (PLACE_ORDER),
 *    die Abbuchung (Capture) erfolgt beim Versand über OrderService::ship().
 * Nur für frei verkäufliche Artikel: Klarna schließt Waffen und Munition in den Nutzungsbedingungen aus.
 */
class KlarnaGateway
{
    private function base(): string
    {
        return config('shop.payment_methods.klarna.playground') ? 'https://api.playground.klarna.com' : 'https://api.klarna.com';
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->base())->acceptJson()->asJson()->timeout(20)
            ->withBasicAuth((string) config('shop.payment_methods.klarna.username'), (string) config('shop.payment_methods.klarna.password'));
    }

    /** Legt Payment-Session + HPP-Session an und liefert die Weiterleitungs-URL. */
    public function start(Order $order): string
    {
        $order->loadMissing('items');
        $sofort = $order->payment_method_code === PaymentMethod::Sofort;
        $lines = $this->orderLines($order);

        $kp = $this->http()->post('/payments/v1/sessions', [
            'intent' => 'buy',
            'purchase_country' => 'DE',
            'purchase_currency' => 'EUR',
            'locale' => 'de-DE',
            'order_amount' => $order->total_gross,
            'order_tax_amount' => collect($lines)->sum('total_tax_amount'),
            'order_lines' => $lines,
            'merchant_reference1' => $order->order_number,
            'billing_address' => $this->address($order->billing_address, $order->user->email),
        ])->throw();

        $return = route('payment.klarna.return', $order);
        $hpp = $this->http()->post('/hpp/v1/sessions', [
            'payment_session_url' => $this->base().'/payments/v1/sessions/'.$kp->json('session_id'),
            'merchant_urls' => [
                'success' => $return.'?sid={{session_id}}&order_id={{order_id}}',
                'cancel' => route('account.orders.show', $order),
                'back' => route('account.orders.show', $order),
                'failure' => route('account.orders.show', $order),
                'error' => route('account.orders.show', $order),
                'status_update' => route('payment.klarna.webhook', ['order' => $order, 'sig' => $this->signature($order)]),
            ],
            'options' => [
                'payment_method_categories' => $sofort ? ['pay_now'] : ['pay_later', 'pay_over_time'],
                'place_order_mode' => $sofort ? 'CAPTURE_ORDER' : 'PLACE_ORDER',
            ],
        ])->throw();

        $order->payments()->create([
            'provider' => 'klarna',
            'provider_reference' => $hpp->json('session_id'),
            'status' => 'initiated',
            'amount' => $order->total_gross,
            'raw_response' => ['kp_session' => $kp->json('session_id'), 'category' => $sofort ? 'pay_now' : 'pay_later'],
        ]);

        return $hpp->json('redirect_url') ?? throw new RuntimeException('Klarna hat keine Weiterleitungs-URL geliefert.');
    }

    /**
     * Prüft nach der Rückkehr (oder per Webhook) den Status direkt bei Klarna – Query-Parameter allein
     * sind nie Beweis einer Zahlung. Liefert true, wenn die Order autorisiert bzw. gebucht ist und der Betrag stimmt.
     */
    public function confirm(Order $order, string $hppSessionId): bool
    {
        $payment = $order->payments()->where('provider', 'klarna')->where('provider_reference', $hppSessionId)->firstOrFail();
        if (in_array($payment->status, ['authorized', 'captured'], true)) {
            return true;
        }

        $session = $this->http()->get("/hpp/v1/sessions/{$hppSessionId}")->throw()->json();
        $klarnaOrderId = $session['order_id'] ?? null;
        if (($session['status'] ?? null) !== 'COMPLETED' || ! $klarnaOrderId) {
            return false;
        }

        $kOrder = $this->http()->get("/ordermanagement/v1/orders/{$klarnaOrderId}")->throw()->json();
        $ok = (int) ($kOrder['order_amount'] ?? -1) === $order->total_gross
            && in_array($kOrder['status'] ?? '', ['AUTHORIZED', 'PART_CAPTURED', 'CAPTURED'], true);

        $payment->update([
            'status' => ! $ok ? 'failed' : (($kOrder['status'] === 'CAPTURED') ? 'captured' : 'authorized'),
            'raw_response' => array_merge($payment->raw_response ?? [], ['klarna_order_id' => $klarnaOrderId, 'status' => $kOrder['status'] ?? null]),
        ]);

        return $ok;
    }

    /** Capture beim Versand (Klarna Rechnung/Raten). */
    public function capture(Payment $payment, ?string $trackingNumber = null, ?string $carrier = null): void
    {
        $klarnaOrderId = $payment->raw_response['klarna_order_id'] ?? null;
        if ($payment->status !== 'authorized' || ! $klarnaOrderId) {
            return;
        }
        $this->http()->withHeaders(['Klarna-Idempotency-Key' => 'cap-'.$payment->id])
            ->post("/ordermanagement/v1/orders/{$klarnaOrderId}/captures", array_filter([
                'captured_amount' => $payment->amount,
                'shipping_info' => $trackingNumber ? [['tracking_number' => $trackingNumber, 'shipping_company' => $carrier ?? 'DHL']] : null,
            ]))->throw();
        $payment->update(['status' => 'captured']);
    }

    public function signature(Order $order): string
    {
        return hash_hmac('sha256', 'klarna|'.$order->order_number, config('app.key'));
    }

    private function orderLines(Order $order): array
    {
        $lines = $order->items->map(function ($item) {
            $rate = (int) round($item->tax_rate * 100);

            return [
                'type' => 'physical',
                'reference' => $item->sku,
                'name' => mb_substr($item->name, 0, 255),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price_gross,
                'tax_rate' => $rate,
                'total_amount' => $item->line_total_gross,
                'total_tax_amount' => $this->tax($item->line_total_gross, $rate),
            ];
        })->all();

        if ($order->shipping_gross > 0) {
            $lines[] = ['type' => 'shipping_fee', 'name' => 'Versand', 'quantity' => 1, 'unit_price' => $order->shipping_gross,
                'tax_rate' => 1900, 'total_amount' => $order->shipping_gross, 'total_tax_amount' => $this->tax($order->shipping_gross, 1900)];
        }
        if ($order->discount_gross > 0) {
            $lines[] = ['type' => 'discount', 'name' => 'Rabatt', 'quantity' => 1, 'unit_price' => -$order->discount_gross,
                'tax_rate' => 1900, 'total_amount' => -$order->discount_gross, 'total_tax_amount' => -$this->tax($order->discount_gross, 1900)];
        }

        return $lines;
    }

    /** Klarna-Formel: total_tax_amount = total_amount - total_amount * 10000 / (10000 + tax_rate) */
    private function tax(int $gross, int $rate): int
    {
        return (int) round($gross - $gross * 10000 / (10000 + $rate));
    }

    private function address(array $a, string $email): array
    {
        return array_filter([
            'given_name' => $a['first_name'] ?? null,
            'family_name' => $a['last_name'] ?? null,
            'email' => $email,
            'street_address' => trim(($a['street'] ?? '').' '.($a['house_number'] ?? '')),
            'postal_code' => $a['postal_code'] ?? null,
            'city' => $a['city'] ?? null,
            'country' => $a['country'] ?? 'DE',
        ]);
    }
}
