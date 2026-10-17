<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Download-Link zum Waffenratgeber für bereits bestätigte Abonnenten (kein erneutes Opt-in nötig). */
class RatgeberDownload extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihr Waffenratgeber von Armory Falter');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.ratgeber-download', with: ['url' => $this->subscriber->ratgeberUrl()]);
    }
}
