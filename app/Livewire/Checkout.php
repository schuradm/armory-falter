<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Livewire\Concerns\HandlesVoucherCodes;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutData;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\CheckoutService;
use App\Services\Compliance\CartComplianceEvaluator;
use App\Services\Vouchers\CartCodeService;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Mehrstufiger Checkout: Adresse → Versand → Zahlung → Prüfen. Alle Regeln prüft CheckoutService erneut. */
class Checkout extends Component
{
    use HandlesVoucherCodes;

    public int $step = 1;

    public array $billing = [];
    public bool $differentShipping = false;
    public array $shipping = [];
    public bool $saveAddress = true;

    public ?string $shippingMethod = null;
    public ?string $paymentMethod = null;
    public string $note = '';
    public bool $terms = false;
    public bool $legalDeclaration = false;
    public bool $noReviewInvite = false; // Widerspruch gegen Bewertungseinladung direkt beim Bestellen

    #[Locked]
    public string $idempotencyKey = '';

    public function mount(Seo $seo, CartService $cart, CartComplianceEvaluator $evaluator)
    {
        $seo->title('Kasse')->noindex();
        if (! $cart->current() || $cart->current()->items()->doesntExist()) {
            return $this->redirectRoute('cart');
        }
        // Fehlt ein Nachweis, zeigt der Warenkorb je Artikel den Grund – nicht erst nach vier Schritten beim Bestellen scheitern
        if (! $evaluator->evaluate($cart->current(), Auth::user())->allowed()) {
            return $this->redirectRoute('cart');
        }
        $this->idempotencyKey = (string) Str::uuid();
        $user = Auth::user();
        $fields = ['company_name' => $user->company_name, 'first_name' => $user->first_name, 'last_name' => $user->last_name,
            'street' => '', 'house_number' => '', 'address_extra' => '', 'postal_code' => '', 'city' => '', 'country_code' => 'DE'];
        $this->billing = array_merge($fields, $user->addresses()->where('type', 'billing')->orderByDesc('is_default')->first()?->toSnapshot() ?? []);
        $this->shipping = array_merge($fields, $user->addresses()->where('type', 'shipping')->orderByDesc('is_default')->first()?->toSnapshot() ?? []);
    }

    protected function addressRules(string $prefix): array
    {
        return [
            "$prefix.first_name" => ['required', 'string', 'max:100'],
            "$prefix.last_name" => ['required', 'string', 'max:100'],
            "$prefix.company_name" => ['nullable', 'string', 'max:200'],
            "$prefix.street" => ['required', 'string', 'max:200'],
            "$prefix.house_number" => ['required', 'string', 'max:20'],
            "$prefix.address_extra" => ['nullable', 'string', 'max:200'],
            "$prefix.postal_code" => ['required', 'regex:/^\d{5}$/'],
            "$prefix.city" => ['required', 'string', 'max:100'],
            "$prefix.country_code" => ['required', 'in:DE'], // Waffen-/Munitionsversand nur innerhalb Deutschlands
        ];
    }

    protected function validationAttributes(): array
    {
        return ['billing.postal_code' => 'PLZ', 'shipping.postal_code' => 'PLZ', 'billing.street' => 'Straße', 'billing.house_number' => 'Hausnummer', 'billing.city' => 'Ort'];
    }

    public function toStep(int $step, CartService $carts, CartComplianceEvaluator $evaluator, CheckoutService $checkout): void
    {
        if ($step > 1) {
            $this->validate($this->addressRules('billing') + ($this->differentShipping ? $this->addressRules('shipping') : []));
            if ($this->saveAddress) {
                $this->storeAddresses();
            }
        }
        if ($step > 2) {
            $methods = $checkout->shippingMethodsFor($evaluator->evaluate($carts->current(), Auth::user()))->pluck('code')->all();
            $this->validate(['shippingMethod' => ['required', 'in:'.implode(',', $methods)]], ['shippingMethod.required' => 'Bitte wählen Sie eine Versandart.']);
        }
        if ($step > 3 && ! $this->coveredByVouchers($carts)) {
            $this->validate(['paymentMethod' => ['required']], ['paymentMethod.required' => 'Bitte wählen Sie eine Zahlungsart.']);
        }
        $this->step = max(1, min(4, $step));
    }

    /** Deckt das Gutscheinguthaben den Gesamtbetrag, entfällt die Zahlungsart. */
    protected function coveredByVouchers(CartService $carts): bool
    {
        $cart = $carts->current();
        if (! $cart) {
            return false;
        }
        $methods = app(CheckoutService::class)->shippingMethodsFor(app(CartComplianceEvaluator::class)->evaluate($cart, Auth::user()));
        $shipping = $methods->firstWhere('code', $this->shippingMethod)?->priceFor($carts->subtotal($cart)) ?? 0;
        [$totals] = app(CartCodeService::class)->totals($cart, Auth::user(), $shipping);

        return $totals->giftVoucherTotal() > 0 && $totals->amountDue() === 0;
    }

    protected function storeAddresses(): void
    {
        $user = Auth::user();
        $user->addresses()->updateOrCreate(['type' => 'billing', 'is_default' => true], collect($this->billing)->only((new \App\Models\Address)->getFillable())->all());
        if ($this->differentShipping) {
            $user->addresses()->updateOrCreate(['type' => 'shipping', 'is_default' => true], collect($this->shipping)->only((new \App\Models\Address)->getFillable())->all());
        }
    }

    public function placeOrder(CartService $carts, CheckoutService $checkout)
    {
        $this->validate(['terms' => ['accepted']], ['terms.accepted' => 'Bitte akzeptieren Sie AGB und Widerrufsbelehrung.']);

        try {
            $order = $checkout->placeOrder(Auth::user(), $carts->current(), new CheckoutData(
                billingAddress: $this->billing,
                shippingAddress: $this->differentShipping ? $this->shipping : null,
                shippingMethodCode: (string) $this->shippingMethod,
                paymentMethod: PaymentMethod::tryFrom((string) $this->paymentMethod) ?? PaymentMethod::GiftVoucher, // Server prüft die Deckung
                termsAccepted: $this->terms,
                legalDeclarationAccepted: $this->legalDeclaration,
                idempotencyKey: $this->idempotencyKey,
                customerNote: $this->note ?: null,
                reviewInvite: ! $this->noReviewInvite,
            ));
        } catch (CheckoutException $e) {
            $this->addError('order', $e->getMessage());

            return null;
        }

        $this->dispatch('cart-updated');

        return $this->redirectAfterOrder($order);
    }

    protected function redirectAfterOrder(Order $order)
    {
        if ($order->status->value === 'pending_payment' && $order->payment_method_code->requiresOnlineCapture()) {
            return redirect()->route('payment.start', $order);
        }

        return redirect()->route('checkout.success', $order);
    }

    public function render(CartService $carts, CartComplianceEvaluator $evaluator, CheckoutService $checkout, CartCodeService $codes)
    {
        $cart = $carts->current();
        $cart?->load('items.product.legalClass', 'items.product.taxRate', 'items.product.media', 'items.product.caliber', 'items.product.categories', 'items.variant');
        $evaluation = $evaluator->evaluate($cart, Auth::user());
        $shippingMethods = $checkout->shippingMethodsFor($evaluation);
        $selected = $shippingMethods->firstWhere('code', $this->shippingMethod);
        $payments = $checkout->paymentMethodsFor($evaluation, $selected);
        if ($this->paymentMethod && ! in_array(PaymentMethod::tryFrom($this->paymentMethod), $payments, true)) {
            $this->paymentMethod = null;
        }
        $subtotal = $carts->subtotal($cart);
        $shippingCost = $selected?->priceFor($subtotal) ?? 0;
        [$totals, $codeNotices] = $codes->totals($cart, Auth::user(), $shippingCost);
        $covered = $totals->giftVoucherTotal() > 0 && $totals->amountDue() === 0;

        return view('livewire.checkout', compact('cart', 'evaluation', 'shippingMethods', 'payments', 'subtotal', 'shippingCost', 'selected', 'totals', 'codeNotices', 'covered'));
    }
}
