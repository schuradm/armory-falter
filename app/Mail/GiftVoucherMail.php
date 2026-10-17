<?php

namespace App\Mail;

use App\Models\GiftVoucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Geschenkgutschein als PDF an den Beschenkten (Käufer in Kopie) bzw. an den Käufer. */
class GiftVoucherMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public GiftVoucher $voucher) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->voucher->recipient_name
            ? "Ein Geschenkgutschein von Armory Falter für {$this->voucher->recipient_name}"
            : 'Ihr Geschenkgutschein von Armory Falter');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.gift-voucher', with: ['voucher' => $this->voucher->loadMissing('purchaser')]);
    }

    public function attachments(): array
    {
        return $this->voucher->pdf_path
            ? [Attachment::fromStorageDisk('local', $this->voucher->pdf_path)->as('Geschenkgutschein-Armory-Falter.pdf')->withMime('application/pdf')]
            : [];
    }
}
