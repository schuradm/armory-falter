<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Attributes\On;
use Livewire\Component;

class QuickView extends Component
{
    public ?int $productId = null;

    #[On('quick-view')]
    public function open(int $productId): void
    {
        $this->productId = $productId;
    }

    public function close(): void
    {
        $this->productId = null;
    }

    public function render()
    {
        $product = $this->productId
            ? Product::visible(auth()->user())->with(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media', 'attributes'])->find($this->productId)
            : null;

        return view('livewire.quick-view', compact('product'));
    }
}
