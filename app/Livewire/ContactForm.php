<?php

namespace App\Livewire;

use App\Models\ContactMessage;
use App\Rules\Honeypot;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ContactForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $subject = '';

    public string $message = '';

    public bool $privacy = false;

    public string $website = '';

    public int $startedAt = 0;

    public bool $sent = false;

    public function mount(?string $subject = null): void
    {
        $this->subject = $subject ?? (string) request()->query('betreff', '');
        $this->startedAt = time();
    }

    public function send(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'privacy' => ['accepted'],
            'website' => [new Honeypot],
        ]);
        // Zeitfalle: Bots senden meist in unter 3 Sekunden ab
        if (time() - $this->startedAt < 3 || ! RateLimiter::attempt('contact:'.request()->ip(), 5, fn () => true, 3600)) {
            $this->addError('message', 'Ihre Nachricht konnte nicht gesendet werden. Bitte versuchen Sie es in Kürze erneut.');

            return;
        }
        $msg = ContactMessage::create($this->only(['name', 'email', 'phone', 'subject', 'message']));
        Mail::raw("Kontaktanfrage von {$msg->name} <{$msg->email}> {$msg->phone}\n\n{$msg->message}", fn ($m) => $m
            ->to(config('shop.company.email'))->replyTo($msg->email, $msg->name)->subject('Kontakt: '.$msg->subject));
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
