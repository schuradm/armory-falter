<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffOrderAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $flag = $this->order->requiresCompliance() ? '[PRÜFUNG] ' : '';

        return new Envelope(subject: "{$flag}Neue Bestellung {$this->order->order_number}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.staff-order-alert', with: ['order' => $this->order->load('items', 'user')]);
    }
}
