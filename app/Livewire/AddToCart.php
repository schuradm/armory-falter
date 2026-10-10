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

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->variantId = $product->variants->first()?->id;
    }

    public function add(CartService $cart): void
    {
        $product = Product::visible(auth()->user())->findOrFail($this->productId);
        try {
            $cart->add($product, max(1, min(99, $this->quantity)), $this->variantId);
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage());

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
