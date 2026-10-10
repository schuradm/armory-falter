<?php

namespace App\Livewire;

use App\Models\Order;
use App\Support\Seo;
use Livewire\Component;

class CheckoutSuccess extends Component
{
    public Order $order;

    public function mount(Order $order, Seo $seo): void
    {
        abort_unless($order->user_id === auth()->id(), 404);
        $this->order = $order->load('items');
        $seo->title('Vielen Dank für Ihre Bestellung')->noindex();
    }

    public function render()
    {
        return view('livewire.checkout-success', ['bank' => config('shop.company.bank')]);
    }
}
