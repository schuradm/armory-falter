<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\User;
use App\Models\WorkshopService;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class AppointmentService
{
    public function __construct(private AuditLogger $audit) {}

    /** @return array<string, list<string>> Datum => freie Uhrzeiten */
    public function availableSlots(string $resource, int $days = null): array
    {
        $days ??= config('shop.appointments.days_ahead');
        $slot = config('shop.appointments.slot_minutes');
        $earliest = now()->addHours(config('shop.appointments.lead_time_hours'));
        $hours = config('shop.company.opening_hours');

        $taken = Appointment::where('resource', $resource)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('starts_at', [now(), now()->addDays($days + 1)])
            ->pluck('starts_at')->map(fn ($d) => $d->format('Y-m-d H:i'))->flip();

        $result = [];
        for ($d = 0; $d <= $days; $d++) {
            $date = CarbonImmutable::today()->addDays($d);
            $open = $hours[$date->dayOfWeekIso] ?? null;
            if (! $open) {
                continue;
            }
            [$from, $to] = $open;
            $t = $date->setTimeFromTimeString($from);
            $end = $date->setTimeFromTimeString($to);
            while ($t->addMinutes($slot) <= $end) {
                if ($t > $earliest && ! isset($taken[$t->format('Y-m-d H:i')])) {
                    $result[$date->toDateString()][] = $t->format('H:i');
                }
                $t = $t->addMinutes($slot);
            }
        }

        return $result;
    }

    public function book(string $type, string $resource, string $startsAt, array $contact, ?WorkshopService $service, ?User $user, ?string $weapon, ?string $notes): Appointment
    {
        $start = CarbonImmutable::parse($startsAt);
        $free = $this->availableSlots($resource)[$start->toDateString()] ?? [];
        if (! in_array($start->format('H:i'), $free, true)) {
            throw new InvalidArgumentException('Dieser Termin ist leider nicht mehr verfügbar.');
        }

        try {
            $appointment = Appointment::create([
                'type' => $type,
                'workshop_service_id' => $service?->id,
                'user_id' => $user?->id,
                'contact' => $contact,
                'starts_at' => $start,
                'ends_at' => $start->addMinutes(config('shop.appointments.slot_minutes')),
                'resource' => $resource,
                'status' => 'requested',
                'weapon_description' => $weapon,
                'notes' => $notes,
            ]);
        } catch (QueryException) {
            // Unique-Index (resource, starts_at) verhindert Doppelbuchungen bei gleichzeitigen Anfragen
            throw new InvalidArgumentException('Dieser Termin wurde gerade vergeben. Bitte wählen Sie einen anderen.');
        }

        $this->audit->log('appointment.booked', $appointment);

        return $appointment;
    }
}
