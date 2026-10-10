<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Ihre Bestellung {$this->order->order_number} bei Armory Falter");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-confirmation', with: ['order' => $this->order->load('items'), 'company' => config('shop.company')]);
    }
}
