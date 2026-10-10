<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Rules\Honeypot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EventRegistrationForm extends Component
{
    #[Locked]
    public int $eventId;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public int $persons = 1;
    public bool $privacy = false;
    public string $website = '';
    public ?string $result = null;

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;
        if ($u = Auth::user()) {
            $this->name = $u->name;
            $this->email = $u->email;
        }
    }

    public function register(): void
    {
        $this->validate([
            'name' => 'required|string|max:160', 'email' => 'required|email|max:255', 'phone' => 'nullable|string|max:40',
            'persons' => 'required|integer|min:1|max:6', 'privacy' => 'accepted', 'website' => [new Honeypot],
        ]);
        if (! RateLimiter::attempt('event:'.request()->ip(), 5, fn () => true, 3600)) {
            $this->addError('email', 'Zu viele Anmeldungen. Bitte später erneut versuchen.');

            return;
        }

        $status = DB::transaction(function () {
            $event = Event::lockForUpdate()->findOrFail($this->eventId);
            $left = $event->seatsLeft();
            $status = ($left === null || $left >= $this->persons) ? 'registered' : 'waitlist';
            EventRegistration::updateOrCreate(['event_id' => $event->id, 'email' => strtolower($this->email)],
                ['name' => $this->name, 'phone' => $this->phone ?: null, 'persons' => $this->persons, 'status' => $status, 'user_id' => Auth::id()]);

            return $status;
        });

        $event = Event::find($this->eventId);
        Mail::raw("Event-Anmeldung ({$status}): {$event->title} am {$event->starts_at->format('d.m.Y H:i')}\n{$this->name} <{$this->email}> {$this->phone}, {$this->persons} Person(en)",
            fn ($m) => $m->to(config('shop.company.email'))->subject('Event-Anmeldung: '.$event->title));
        $this->result = $status;
    }

    public function render()
    {
        return view('livewire.event-registration-form', ['event' => Event::find($this->eventId)]);
    }
}
