<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmation;
use App\Mail\StaffOrderAlert;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation
{
    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->order->user->email)->queue(new OrderConfirmation($event->order));
        Mail::to(config('shop.company.email'))->queue(new StaffOrderAlert($event->order));
    }
}
