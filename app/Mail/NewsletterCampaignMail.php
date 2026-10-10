<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterCampaign $campaign, public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter-campaign', with: [
            'campaign' => $this->campaign,
            'subscriber' => $this->subscriber,
            'pixel' => \URL::signedRoute('newsletter.open', ['campaign' => $this->campaign->id, 'subscriber' => $this->subscriber->id]),
        ]);
    }
}
