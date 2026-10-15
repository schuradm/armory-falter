<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Willkommensstrecke für bestätigte Newsletter-Abonnenten (Schritt 1–4 = Tag 1/3/7/14 nach Bestätigung).
 * Versand über `newsletter:welcome` (Scheduler).
 */
class NewsletterWelcome extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const SUBJECTS = [
        1 => 'Willkommen bei Armory Falter – Ihr persönlicher Waffenberater',
        2 => 'So einfach kaufen Sie Waffen online bei uns',
        3 => 'Werkstatt, Ankauf und Verkaufsservice: mehr als ein Shop',
        4 => 'Events, Testtage und Elite-Club: Seien Sie dabei',
    ];

    public function __construct(public NewsletterSubscriber $subscriber, public int $step) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->step] ?? self::SUBJECTS[1]);
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
        return new Content(markdown: 'mail.newsletter-welcome', with: [
            'subscriber' => $this->subscriber,
            'step' => $this->step,
        ]);
    }
}
