<?php

namespace App\Mail;

use App\Models\TradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TradeRequestReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public TradeRequest $request, public bool $internal) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->internal
            ? "Neue Anfrage {$this->request->type->label()}: {$this->request->reference}"
            : "Ihre Anfrage {$this->request->reference} bei Armory Falter");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.trade-request', with: ['r' => $this->request, 'internal' => $this->internal]);
    }

    public function attachments(): array
    {
        return $this->internal && $this->request->advisory_pdf_path
            ? [Attachment::fromStorageDisk('local', $this->request->advisory_pdf_path)->as($this->request->reference.'.pdf')]
            : [];
    }
}
