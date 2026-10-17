<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/** Bewertungseinladung mit Widerspruchslink in jeder Mail (§ 7 Abs. 3 Nr. 4 UWG), siehe SendReviewRequests. */
class ReviewRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public User $user, public Collection $products) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Wie zufrieden sind Sie mit Ihrem Einkauf bei Armory Falter?');
    }

    public function optOutUrl(): string
    {
        return URL::signedRoute('reviews.opt-out', ['user' => $this->user->id]);
    }

    /** One-Click-Abmeldung im Mailprogramm (RFC 8058). */
    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->optOutUrl().'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.review-request', with: [
            'name' => $this->user->first_name,
            'products' => $this->products,
            'optOutUrl' => $this->optOutUrl(),
        ]);
    }
}
