<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirm extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subscriber->source === 'ratgeber'
            ? 'Ihr Waffenratgeber: bitte E-Mail-Adresse bestätigen'
            : 'Bitte bestätigen Sie Ihre Newsletter-Anmeldung');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter-confirm', with: [
            'url' => route('newsletter.confirm', $this->subscriber->confirm_token),
            'ratgeber' => $this->subscriber->source === 'ratgeber',
        ]);
    }
}
