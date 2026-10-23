<?php

namespace Tests\Feature;

use App\Enums\TradeRequestStatus;
use App\Livewire\AppointmentBooking;
use App\Livewire\TradeRequestForm;
use App\Mail\TradeRequestReceived;
use App\Models\Appointment;
use App\Models\TradeRequest;
use App\Models\Withdrawal;
use App\Models\WorkshopService;
use App\Services\Booking\AppointmentService;
use Carbon\Carbon;
use Database\Seeders\WorkshopServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/** Anfragen ohne Warenkorb: Verkaufsservice/Ankauf, Terminbuchung (Werkstatt/Beratung) und Zuordnung von Widerrufen. */
class ServiceRequestsTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    /** Montag, 9 Uhr: Vorlaufzeit 24 h, Öffnungszeiten Mo–Fr 10–18, Sa 10–14. */
    private function monday(): Carbon
    {
        $monday = now()->next(Carbon::MONDAY)->setTime(9, 0);
        $this->travelTo($monday);

        return $monday;
    }

    // ---------------------------------------------------------------- Verkaufsservice / Ankauf

    public function test_verkaufsservice_anfrage_mit_fotos_dossier_und_bestaetigung(): void
    {
        Livewire::test(TradeRequestForm::class, ['type' => 'consignment'])
            ->set('name', 'Max Verkäufer')->set('street', 'Hauptstraße 12')->set('postal_code', '72649')->set('city', 'Wolfschlugen')
            ->set('phone', '0711 1')->set('email', 'verkauf@example.test')->set('weapon_type', 'Repetierbüchse')
            ->set('manufacturer_name', 'Mauser')->set('model', 'M12')->set('caliber', '.308 Win')
            ->set('desired_price', '1250')
            ->set('photos', [UploadedFile::fake()->image('links.jpg', 1200, 800), UploadedFile::fake()->image('rechts.jpg', 1200, 800)])
            ->set('privacy', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('reference', 'VS-'.now()->year.'-0001');

        $request = TradeRequest::sole();
        $this->assertSame(TradeRequestStatus::New, $request->status);
        $this->assertSame('verkauf@example.test', $request->contact['email']);
        $this->assertSame(125000, $request->desired_price);
        $this->assertCount(2, $request->media);
        foreach ($request->media as $media) {
            Storage::disk('local')->assertExists($media->path);
        }
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($request->advisory_pdf_path));

        Mail::assertQueued(TradeRequestReceived::class, fn ($m) => $m->internal && $m->hasTo(config('shop.company.email')));
        Mail::assertQueued(TradeRequestReceived::class, fn ($m) => ! $m->internal && $m->hasTo('verkauf@example.test'));
    }

    public function test_ankauf_preis_in_cent_und_eigener_nummernkreis(): void
    {
        Livewire::test(TradeRequestForm::class, ['type' => 'buyback'])
            ->set('name', 'Anna Ankauf')->set('email', 'ankauf@example.test')
            ->set('manufacturer_name', 'Sauer')->set('model', '202')->set('caliber', '7x64')
            ->set('desired_price', '1.250,50')->set('privacy', true)
            ->call('submit')->assertHasNoErrors();

        $request = TradeRequest::sole();
        $this->assertSame(125050, $request->desired_price);
        $this->assertStringStartsWith('AK-', $request->reference, 'Ankauf hat einen eigenen Nummernkreis');
    }

    public function test_pflichtfelder_je_anfrageart(): void
    {
        // Verkaufsservice: Adresse und Fotos Pflicht
        Livewire::test(TradeRequestForm::class, ['type' => 'consignment'])
            ->set('name', 'Max')->set('email', 'max@example.test')->set('model', 'M12')->set('privacy', true)
            ->call('submit')
            ->assertHasErrors(['street', 'postal_code', 'city', 'phone', 'weapon_type', 'photos', 'manufacturer_name'])
            ->assertHasNoErrors('caliber');

        // Ankauf: Kaliber Pflicht, Fotos optional; ungültige PLZ und Preis
        Livewire::test(TradeRequestForm::class, ['type' => 'buyback'])
            ->set('name', 'Max')->set('email', 'max@example.test')->set('model', 'M12')->set('manufacturer_name', 'Mauser')
            ->set('postal_code', '1234')->set('desired_price', 'viel')
            ->call('submit')
            ->assertHasErrors(['caliber', 'postal_code', 'desired_price', 'privacy'])
            ->assertHasNoErrors(['photos', 'street']);

        // Der Anfragetyp ist gesperrt und kann vom Browser nicht umgestellt werden
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(TradeRequestForm::class, ['type' => 'buyback'])->set('type', 'consignment');
    }

    // ---------------------------------------------------------------- Termine

    public function test_freie_termine_nach_oeffnungszeiten_vorlauf_und_belegung(): void
    {
        $monday = $this->monday();
        $service = app(AppointmentService::class);
        Appointment::create(['type' => 'workshop', 'contact' => ['name' => 'X'], 'resource' => 'werkstatt', 'status' => 'confirmed',
            'starts_at' => $monday->copy()->addDay()->setTime(10, 30), 'ends_at' => $monday->copy()->addDay()->setTime(11, 0)]);

        $slots = $service->availableSlots('werkstatt', 7);

        $this->assertArrayNotHasKey($monday->toDateString(), $slots, 'Heute liegt alles innerhalb der Vorlaufzeit von 24 h');
        $tuesday = $slots[$monday->copy()->addDay()->toDateString()];
        $this->assertSame('10:00', $tuesday[0]);
        $this->assertSame('17:30', end($tuesday), 'Letzter Termin endet zur Ladenschlusszeit');
        $this->assertNotContains('10:30', $tuesday, 'Belegter Termin');
        $this->assertCount(15, $tuesday);
        $this->assertCount(8, $slots[$monday->copy()->addDays(5)->toDateString()], 'Samstag 10–14 Uhr');
        $this->assertArrayNotHasKey($monday->copy()->addDays(6)->toDateString(), $slots, 'Sonntag geschlossen');

        // Andere Ressource ist unabhängig belegt
        $this->assertContains('10:30', $service->availableSlots('beratung', 2)[$monday->copy()->addDay()->toDateString()]);
    }

    public function test_terminbuchung_und_doppelbuchung(): void
    {
        $this->seed(WorkshopServiceSeeder::class);
        $monday = $this->monday();
        $tuesday = $monday->copy()->addDay()->toDateString();
        $service = WorkshopService::where('is_bookable', true)->firstOrFail();

        $book = fn (string $email) => Livewire::test(AppointmentBooking::class, ['type' => 'workshop'])
            ->set('serviceId', $service->id)->set('date', $tuesday)->set('time', '11:00')
            ->set('name', 'Max')->set('email', $email)->set('phone', '0711')->set('privacy', true)
            ->call('book');

        $book('erst@example.test')->assertHasNoErrors()->assertSet('booked', true);
        $appointment = Appointment::sole();
        $this->assertSame('werkstatt', $appointment->resource);
        $this->assertSame('requested', $appointment->status);
        $this->assertSame($tuesday.' 11:30', $appointment->ends_at->format('Y-m-d H:i'));

        $book('zweit@example.test')->assertHasErrors(['time' => 'Dieser Termin ist leider nicht mehr verfügbar.'])->assertSet('booked', false);
        $this->assertSame(1, Appointment::count());

        // Außerhalb der Öffnungszeiten bzw. Vorlaufzeit
        $this->expectException(InvalidArgumentException::class);
        app(AppointmentService::class)->book('workshop', 'werkstatt', $monday->copy()->setTime(14, 0)->toDateTimeString(), ['name' => 'X'], null, null, null, null);
    }

    public function test_abgesagter_termin_wird_wieder_buchbar(): void
    {
        $monday = $this->monday();
        $slot = $monday->copy()->addDay()->setTime(12, 0);
        Appointment::create(['type' => 'consultation', 'contact' => ['name' => 'Abgesagt'], 'resource' => 'beratung', 'status' => 'cancelled',
            'starts_at' => $slot, 'ends_at' => $slot->copy()->addMinutes(30)]);

        $this->assertContains('12:00', app(AppointmentService::class)->availableSlots('beratung', 2)[$slot->toDateString()]);

        // Der abgesagte Termin gibt seinen Slot frei (slot_key leer), die Buchung klappt
        app(AppointmentService::class)->book('consultation', 'beratung', $slot->toDateTimeString(), ['name' => 'Neu'], null, null, null, null);
        $this->assertSame(1, Appointment::where('status', 'requested')->count());
    }

    // ---------------------------------------------------------------- Widerruf

    public function test_widerruf_wird_nur_der_passenden_bestellung_zugeordnet(): void
    {
        $owner = $this->customer('Wanda', 'Widerruf');
        $this->fillCart($owner, ['WAL-CLEAN-UNI' => 1]);
        $order = $this->checkout($owner);
        $this->app['auth']->forgetGuards();

        // Fremde E-Mail-Adresse: Bestellnummer allein verknüpft nicht (keine Fremdzuordnung)
        $this->post(route('withdrawal.review'), ['name' => 'Eve', 'email' => 'eve@example.test', 'order_number' => $order->order_number])->assertOk();
        $this->post(route('withdrawal.store'))->assertRedirect();
        $this->assertNull(Withdrawal::latest('id')->first()->order_id);

        // Gast mit der E-Mail der Bestellung (Groß-/Kleinschreibung egal)
        $this->post(route('withdrawal.review'), ['name' => 'Wanda', 'email' => strtoupper($owner->email), 'order_number' => $order->order_number])->assertOk();
        $this->post(route('withdrawal.store'))->assertRedirect();
        $this->assertSame($order->id, Withdrawal::latest('id')->first()->order_id);

        // Angemeldete Kundin mit abweichender Kontakt-E-Mail
        $this->actingAs($owner)->post(route('withdrawal.review'), ['name' => 'Wanda', 'email' => 'privat@example.test', 'order_number' => $order->order_number]);
        $this->actingAs($owner)->post(route('withdrawal.store'))->assertRedirect();
        $latest = Withdrawal::latest('id')->first();
        $this->assertSame($order->id, $latest->order_id);
        $this->assertSame($owner->id, $latest->user_id);
        $this->assertMatchesRegularExpression('/^WR-\d{4}-00003$/', $latest->reference);
    }
}
