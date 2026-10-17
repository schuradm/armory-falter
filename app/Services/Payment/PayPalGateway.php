<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayPal Orders API v2 (serverseitig, ohne SDK). Nur für frei verkäufliche Artikel –
 * die PayPal-Nutzungsbedingungen schließen Waffen und Munition aus.
 */
class PayPalGateway
{
    private function base(): string
    {
        return config('shop.payment_methods.paypal.sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    private function token(): string
    {
        return Cache::remember('paypal.token', 30 * 60, function () {
            $res = Http::asForm()
                ->withBasicAuth(config('shop.payment_methods.paypal.client_id'), config('shop.payment_methods.paypal.secret'))
                ->post($this->base().'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->throw();

            return $res->json('access_token');
        });
    }

    /** Legt die PayPal-Order an und liefert die Freigabe-URL für den Kunden. */
    public function start(Order $order): string
    {
        $res = Http::withToken($this->token())
            ->withHeaders(['PayPal-Request-Id' => $order->idempotency_key])
            ->post($this->base().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'invoice_id' => $order->order_number,
                    'amount' => ['currency_code' => 'EUR', 'value' => number_format($order->amountDue() / 100, 2, '.', '')],
                ]],
                'payment_source' => ['paypal' => ['experience_context' => [
                    'brand_name' => config('shop.company.name'),
                    'locale' => 'de-DE',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('payment.paypal.return', $order),
                    'cancel_url' => route('account.orders.show', $order),
                ]]],
            ])->throw();

        $order->payments()->create([
            'provider' => 'paypal',
            'provider_reference' => $res->json('id'),
            'status' => 'initiated',
            'amount' => $order->amountDue(),
            'raw_response' => $res->json(),
        ]);

        $approve = collect($res->json('links'))->firstWhere('rel', 'payer-action')['href'] ?? null;

        return $approve ?? throw new RuntimeException('PayPal hat keine Freigabe-URL geliefert.');
    }

    public function capture(Order $order, string $paypalOrderId): bool
    {
        $payment = $order->payments()->where('provider', 'paypal')->where('provider_reference', $paypalOrderId)->firstOrFail();
        if ($payment->status === 'captured') {
            return true;
        }
        $res = Http::withToken($this->token())->withBody('{}', 'application/json')
            ->withHeaders(['PayPal-Request-Id' => 'cap-'.$order->idempotency_key])
            ->post($this->base()."/v2/checkout/orders/{$paypalOrderId}/capture");

        $capture = $res->json('purchase_units.0.payments.captures.0');
        $ok = $res->successful() && $res->json('status') === 'COMPLETED'
            && ($capture['status'] ?? null) === 'COMPLETED'
            && (int) round(((float) ($capture['amount']['value'] ?? 0)) * 100) === $order->amountDue();
        $payment->update(['status' => $ok ? 'captured' : 'failed', 'raw_response' => $res->json()]);

        return $ok;
    }

    /** Prüft die Webhook-Signatur über die PayPal-API (kein lokales Zertifikats-Handling nötig). */
    public function verifyWebhook(\Illuminate\Http\Request $request): bool
    {
        $webhookId = config('shop.payment_methods.paypal.webhook_id');
        if (! $webhookId) {
            return false;
        }
        $res = Http::withToken($this->token())->post($this->base().'/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $webhookId,
            'webhook_event' => $request->json()->all(),
        ]);

        return $res->successful() && $res->json('verification_status') === 'SUCCESS';
    }
}
