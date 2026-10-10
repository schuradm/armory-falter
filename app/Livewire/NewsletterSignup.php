<?php

namespace App\Livewire;

use App\Rules\Honeypot;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class NewsletterSignup extends Component
{
    public string $email = '';
    public array $interests = [];
    public bool $consent = false;
    public string $website = '';
    public bool $done = false;

    /** footer | lead (Leadmagnet „Waffenratgeber“) */
    #[\Livewire\Attributes\Locked]
    public string $variant = 'footer';

    public function subscribe(NewsletterService $service): void
    {
        $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'interests' => ['array'], 'interests.*' => ['in:jagd,sport,optik,sammler,behoerde'],
            'consent' => ['accepted'],
            'website' => [new Honeypot],
        ], ['consent.accepted' => 'Bitte bestätigen Sie die Einwilligung.']);

        if (! RateLimiter::attempt('nl:'.request()->ip(), 5, fn () => true, 3600)) {
            $this->addError('email', 'Zu viele Versuche. Bitte später erneut versuchen.');

            return;
        }
        $service->subscribe($this->email, $this->interests, null, $this->variant === 'lead' ? 'ratgeber' : 'footer', auth()->id());
        $this->done = true;
    }

    public function render()
    {
        return view($this->variant === 'lead' ? 'livewire.lead-magnet' : 'livewire.newsletter-signup');
    }
}
