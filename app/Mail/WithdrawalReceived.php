<?php

namespace App\Mail;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Eingangsbestätigung des online erklärten Widerrufs (Kunde) bzw. Hinweis an den Shop (internal). */
class WithdrawalReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Withdrawal $withdrawal, public bool $internal = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->internal
            ? "[WIDERRUF] {$this->withdrawal->reference} zu Bestellung {$this->withdrawal->order_number}"
            : "Eingangsbestätigung Ihres Widerrufs ({$this->withdrawal->reference})");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.withdrawal-received', with: ['w' => $this->withdrawal, 'internal' => $this->internal]);
    }
}
