<?php

namespace Tests\Feature;

use App\Mail\WithdrawalReceived;
use App\Models\Withdrawal;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Widerrufsbutton: Angaben → „Widerruf bestätigen“ → Eingangsbestätigung mit Zeitstempel. */
class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_widerruf_in_zwei_schritten_mit_eingangsbestaetigung(): void
    {
        Mail::fake();

        $this->get(route('withdrawal.create'))->assertOk()->assertSee('Vertrag widerrufen')->assertSee('Weiter zur Bestätigung');

        // Pflichtangaben
        $this->post(route('withdrawal.review'), [])->assertSessionHasErrors(['name', 'email', 'order_number']);

        $this->post(route('withdrawal.review'), ['name' => 'Max Testkunde', 'email' => 'max@example.test', 'order_number' => 'AF-2026-000999'])
            ->assertOk()->assertSee('Widerruf bestätigen')->assertSee('AF-2026-000999');
        $this->assertSame(0, Withdrawal::count(), 'Erst die Bestätigung erklärt den Widerruf');

        $this->post(route('withdrawal.store'))->assertRedirect();

        $w = Withdrawal::sole();
        $this->assertSame('AF-2026-000999', $w->order_number);
        $this->assertNull($w->order_id); // unbekannte Bestellung wird nicht verknüpft
        $this->assertNotNull($w->declared_at);
        Mail::assertSent(WithdrawalReceived::class, fn ($m) => $m->hasTo('max@example.test') && ! $m->internal
            && str_contains($m->render(), $w->declared_at->timezone('Europe/Berlin')->format('d.m.Y')));
        Mail::assertQueued(WithdrawalReceived::class, fn ($m) => $m->internal);

        // Erneutes Absenden ohne neue Angaben legt keinen zweiten Widerruf an
        $this->post(route('withdrawal.store'))->assertRedirect(route('withdrawal.create'));
        $this->assertSame(1, Withdrawal::count());
    }

    public function test_rechtstexte_und_button_im_footer(): void
    {
        $this->seed(PageSeeder::class);

        $this->get('/widerruf')->assertOk()->assertSee('Muster-Widerrufsformular')->assertSee(route('withdrawal.create'), false);
        $this->get('/impressum')->assertOk()->assertSee('§ 5 Digitale-Dienste-Gesetz', false)->assertSee(config('shop.company.legal_name'));
        $this->get('/agb')->assertOk()->assertSee('Besondere Bestimmungen für Waffen und Munition');
        $this->get('/datenschutz')->assertOk()->assertSee('Erwerbsberechtigungen, Ausweise und Waffenhandelsbuch')->assertSee('Waffenratgeber');
        $this->get('/')->assertOk()->assertSee('Vertrag widerrufen');
    }
}
