<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\InvoiceService;
use App\Services\Orders\OrderService;
use App\Services\Payment\KlarnaGateway;
use App\Services\Payment\PayPalGateway;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    /** Zahlung starten (nach Bestellung bzw. nach Compliance-Freigabe). */
    public function start(Order $order, PayPalGateway $paypal, KlarnaGateway $klarna): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);
        if ($order->status !== OrderStatus::PendingPayment) {
            return redirect()->route('account.orders.show', $order);
        }

        try {
            return match ($order->payment_method_code) {
                PaymentMethod::PayPal => redirect()->away($paypal->start($order)),
                PaymentMethod::Sofort, PaymentMethod::Klarna => redirect()->away($klarna->start($order)),
                default => redirect()->route('account.orders.show', $order),
            };
        } catch (Throwable $e) {
            Log::error('Zahlungsstart fehlgeschlagen', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            return redirect()->route('account.orders.show', $order)
                ->withErrors(['payment' => 'Der Zahlungsdienst ist gerade nicht erreichbar. Bitte versuchen Sie es in einigen Minuten erneut oder wählen Sie Vorkasse.']);
        }
    }

    public function paypalReturn(Order $order, Request $request, PayPalGateway $paypal): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $token = (string) $request->query('token');

        if ($token && ($order->status !== OrderStatus::PendingPayment || $paypal->capture($order, $token))) {
            $this->markPaid($order, 'PayPal');

            return redirect()->route('checkout.success', $order)->with('status', 'Vielen Dank, Ihre Zahlung ist eingegangen.');
        }

        return $this->failed($order);
    }

    public function klarnaReturn(Order $order, Request $request, KlarnaGateway $klarna): RedirectResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $sid = (string) $request->query('sid');

        if ($sid && ($order->status !== OrderStatus::PendingPayment || $klarna->confirm($order, $sid))) {
            $this->markPaid($order, $order->payment_method_code->label());

            return redirect()->route('checkout.success', $order)->with('status', 'Vielen Dank, Ihre Zahlung ist bestätigt.');
        }

        return $this->failed($order);
    }

    /** Klarna status_update: Absicherung, falls der Kunde den Browser vor der Rückleitung schließt. */
    public function klarnaWebhook(Order $order, Request $request, KlarnaGateway $klarna): Response
    {
        abort_unless(hash_equals($klarna->signature($order), (string) $request->query('sig')), 403);
        $payment = $order->payments()->where('provider', 'klarna')->latest('id')->first();
        if ($payment && $order->status === OrderStatus::PendingPayment && $klarna->confirm($order, $payment->provider_reference)) {
            $this->markPaid($order, $order->payment_method_code->label());
        }

        return response()->noContent();
    }

    /** PayPal-Webhook PAYMENT.CAPTURE.COMPLETED, Signatur wird bei PayPal verifiziert. */
    public function paypalWebhook(Request $request, PayPalGateway $paypal): Response
    {
        if (! $paypal->verifyWebhook($request)) {
            return response()->noContent(400);
        }
        if ($request->input('event_type') === 'PAYMENT.CAPTURE.COMPLETED') {
            $paypalOrderId = $request->input('resource.supplementary_data.related_ids.order_id');
            $payment = Payment::where('provider', 'paypal')->where('provider_reference', $paypalOrderId)->with('order')->first();
            if ($payment && $payment->order->status === OrderStatus::PendingPayment
                && (int) round(((float) $request->input('resource.amount.value')) * 100) === $payment->amount) {
                $payment->update(['status' => 'captured']);
                $this->markPaid($payment->order, 'PayPal (Webhook)');
            }
        }

        return response()->noContent();
    }

    /** Idempotent: Rückleitung und Webhook können gleichzeitig eintreffen. */
    private function markPaid(Order $order, string $via): void
    {
        try {
            Cache::lock('order-paid-'.$order->id, 30)->block(10, function () use ($order, $via) {
                $order->refresh();
                if ($order->status !== OrderStatus::PendingPayment) {
                    return;
                }
                app(OrderService::class)->transition($order, OrderStatus::Paid, null, "Zahlung per {$via} eingegangen.");
                app(InvoiceService::class)->createFor($order);
            });
        } catch (LockTimeoutException) {
            // Die parallele Anfrage (Rückleitung oder Webhook) bucht die Zahlung gerade, Kunde sieht trotzdem die Bestätigung
            Log::warning('Zahlungsbuchung läuft bereits parallel', ['order' => $order->order_number, 'via' => $via]);
        }
    }

    private function failed(Order $order): RedirectResponse
    {
        return redirect()->route('account.orders.show', $order)
            ->withErrors(['payment' => 'Die Zahlung wurde nicht abgeschlossen. Sie können es über „Jetzt bezahlen“ erneut versuchen.']);
    }
}
