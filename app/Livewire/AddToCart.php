<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\Cart\CartService;
use App\Services\Compliance\ComplianceResult;
use App\Services\Compliance\ComplianceService;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddToCart extends Component
{
    #[Locked]
    public int $productId;

    public int $quantity = 1;
    public ?int $variantId = null;

    // Geschenkgutschein: Betrag in Euro (Vorgabe oder frei) und optional Beschenkter
    public string $voucherAmount = '50';
    public string $customAmount = '';
    public bool $forSomeoneElse = false;
    public string $recipientName = '';
    public string $recipientEmail = '';
    public string $message = '';

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->variantId = $product->variants->first()?->id;
        if ($product->isGiftVoucher()) {
            $this->voucherAmount = (string) intdiv($product->currentPrice(), 100);
        }
    }

    /** @return array<string,mixed> Optionen für CartService::add (nur Geschenkgutscheine) */
    protected function voucherOptions(): array
    {
        $euros = $this->voucherAmount === 'custom' ? (int) preg_replace('/\D/', '', $this->customAmount) : (int) $this->voucherAmount;
        if ($this->forSomeoneElse) {
            $this->validate([
                'recipientName' => ['nullable', 'string', 'max:100'],
                'recipientEmail' => ['nullable', 'email', 'max:255'],
                'message' => ['nullable', 'string', 'max:300'],
            ], ['recipientEmail.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']);
        }

        return ['amount' => $euros * 100] + ($this->forSomeoneElse ? [
            'recipient_name' => $this->recipientName,
            'recipient_email' => $this->recipientEmail,
            'message' => $this->message,
        ] : []);
    }

    public function add(CartService $cart): void
    {
        $product = Product::visible(auth()->user())->findOrFail($this->productId);
        try {
            $cart->add($product, max(1, min(99, $this->quantity)), $this->variantId, $product->isGiftVoucher() ? $this->voucherOptions() : []);
        } catch (InvalidArgumentException $e) {
            $product->isGiftVoucher() ? $this->addError('voucherAmount', $e->getMessage()) : $this->dispatch('toast', message: $e->getMessage());

            return;
        }
        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: "„{$product->name}“ liegt im Warenkorb.");
    }

    public function render(ComplianceService $compliance)
    {
        $product = Product::with(['legalClass', 'variants', 'caliber'])->findOrFail($this->productId);

        return view('livewire.add-to-cart', [
            'product' => $product,
            'check' => $compliance->evaluate($product, auth()->user(), $this->quantity),
            'hints' => [
                ComplianceResult::HINT_LOGIN => ['Anmelden oder registrieren', route('login')],
                ComplianceResult::HINT_VERIFY_AGE => ['Ausweis im Kundenkonto hochladen', route('account.documents')],
                ComplianceResult::HINT_UPLOAD_ENTITLEMENT => ['WBK / Jagdschein hochladen', route('account.documents')],
                ComplianceResult::HINT_CONTACT => ['Kontakt aufnehmen', route('contact')],
            ],
        ]);
    }
}
