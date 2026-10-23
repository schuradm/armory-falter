<?php

namespace App\Livewire;

use App\Models\WorkshopService;
use App\Rules\Honeypot;
use App\Services\Booking\AppointmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Online-Terminbuchung für Werkstatt und Beratung. */
class AppointmentBooking extends Component
{
    #[Locked]
    public string $type = 'workshop';

    public ?int $serviceId = null;

    public ?string $date = null;

    public ?string $time = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $weapon = '';

    public string $notes = '';

    public bool $privacy = false;

    public string $website = '';

    public bool $booked = false;

    public function mount(string $type = 'workshop', ?int $serviceId = null): void
    {
        $this->type = in_array($type, ['workshop', 'consultation'], true) ? $type : 'workshop';
        $this->serviceId = $serviceId ?? (int) request()->query('leistung') ?: null;
        if ($u = Auth::user()) {
            $this->name = $u->name;
            $this->email = $u->email;
            $this->phone = (string) $u->phone;
        }
    }

    protected function resource(): string
    {
        return $this->type === 'consultation' ? 'beratung' : 'werkstatt';
    }

    public function updatedDate(): void
    {
        $this->time = null;
    }

    public function book(AppointmentService $service): void
    {
        $this->validate([
            'serviceId' => [$this->type === 'workshop' ? 'required' : 'nullable', 'exists:workshop_services,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'weapon' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'privacy' => ['accepted'],
            'website' => [new Honeypot],
        ], ['serviceId.required' => 'Bitte wählen Sie eine Leistung.', 'time.required' => 'Bitte wählen Sie eine Uhrzeit.']);

        if (! RateLimiter::attempt('booking:'.request()->ip(), 5, fn () => true, 3600)) {
            $this->addError('time', 'Zu viele Buchungen. Bitte rufen Sie uns an.');

            return;
        }

        try {
            $service->book($this->type, $this->resource(), "{$this->date} {$this->time}",
                ['name' => $this->name, 'email' => $this->email, 'phone' => $this->phone],
                $this->serviceId ? WorkshopService::find($this->serviceId) : null, Auth::user(), $this->weapon ?: null, $this->notes ?: null);
        } catch (InvalidArgumentException $e) {
            $this->addError('time', $e->getMessage());

            return;
        }
        $this->booked = true;
    }

    public function render(AppointmentService $service): View
    {
        $slots = $service->availableSlots($this->resource());

        return view('livewire.appointment-booking', [
            'services' => WorkshopService::where('is_bookable', true)->orderBy('sort_order')->get(),
            'slots' => $slots,
            'times' => $this->date ? ($slots[$this->date] ?? []) : [],
        ]);
    }
}
