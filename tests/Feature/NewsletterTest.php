<?php

namespace Tests\Feature;

use App\Mail\NewsletterConfirm;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Newsletter nach § 7 UWG: Double-Opt-in mit Nachweis, Abmeldung per signiertem Link, Löschkonzept (newsletter:prune). */
class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function subscribe(array $data)
    {
        return $this->from('/')->post(route('newsletter.subscribe'), $data + ['consent' => '1']);
    }

    public function test_anmeldung_erst_nach_bestaetigung_aktiv(): void
    {
        $this->subscribe(['email' => '  Max@Example.TEST ', 'interests' => ['jagd', 'optik']])
            ->assertRedirectContains('#newsletter')->assertSessionHas('newsletter_done', 'footer');

        $sub = NewsletterSubscriber::sole();
        $this->assertSame('max@example.test', $sub->email, 'E-Mail normalisiert');
        $this->assertSame('pending', $sub->status);
        $this->assertSame(['jagd', 'optik'], $sub->interests);
        $this->assertSame(64, strlen($sub->confirm_token));
        $this->assertNotNull($sub->opt_in_ip_hash);
        $this->assertNotSame('127.0.0.1', $sub->opt_in_ip_hash, 'IP nur als Hash gespeichert');
        Mail::assertQueued(NewsletterConfirm::class, fn ($m) => $m->hasTo('max@example.test'));

        $this->get(route('newsletter.confirm', $sub->confirm_token))->assertOk()->assertSee('Anmeldung bestätigt');
        $sub->refresh();
        $this->assertSame('confirmed', $sub->status);
        $this->assertNotNull($sub->confirmed_at);
        $this->assertNotNull($sub->confirmed_ip_hash);
        $this->assertNull($sub->confirm_token, 'Bestätigungslink ist nur einmal gültig');
    }

    public function test_abgelaufener_oder_unbekannter_bestaetigungslink(): void
    {
        $this->subscribe(['email' => 'spaet@example.test']);
        $sub = NewsletterSubscriber::sole();

        $this->get(route('newsletter.confirm', str_repeat('x', 64)))->assertOk()->assertSee('Link ungültig');

        $this->travel(8)->days();
        $this->get(route('newsletter.confirm', $sub->confirm_token))->assertOk()->assertSee('Link ungültig');
        $this->assertSame('pending', $sub->fresh()->status);
    }

    public function test_validierung_honeypot_und_interessen(): void
    {
        $this->subscribe(['email' => 'kein-at'])->assertSessionHasErrors(['email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'], null, 'newsletter_footer');
        $this->subscribe(['email' => 'a@example.test', 'interests' => ['waffenschmuggel']])->assertSessionHasErrors('interests.0', null, 'newsletter_footer');
        $this->subscribe(['email' => 'bot@example.test', 'website' => 'http://spam.example'])->assertSessionHasErrors('website', null, 'newsletter_footer');

        $this->assertSame(0, NewsletterSubscriber::count());
        Mail::assertNothingQueued();
    }

    public function test_erneute_anmeldung_nach_abmeldung_braucht_neues_opt_in(): void
    {
        $sub = NewsletterSubscriber::create(['email' => 'zurueck@example.test', 'status' => 'unsubscribed', 'unsubscribed_at' => now(), 'source' => 'footer']);

        $this->subscribe(['email' => 'zurueck@example.test']);

        $this->assertSame('pending', $sub->fresh()->status);
        $this->assertSame(1, NewsletterSubscriber::count());
        Mail::assertQueued(NewsletterConfirm::class);
    }

    public function test_abmeldung_nur_mit_signiertem_link_auch_als_one_click_post(): void
    {
        $sub = NewsletterSubscriber::create(['email' => 'leser@example.test', 'status' => 'confirmed', 'confirmed_at' => now(), 'source' => 'footer']);
        $other = NewsletterSubscriber::create(['email' => 'andere@example.test', 'status' => 'confirmed', 'confirmed_at' => now(), 'source' => 'footer']);

        // Ohne bzw. mit manipulierter Signatur (fremde ID) keine Abmeldung
        $this->get(route('newsletter.unsubscribe', $sub))->assertForbidden();
        $this->get(str_replace('/abmelden/'.$sub->id, '/abmelden/'.$other->id, $sub->unsubscribeUrl()))->assertForbidden();
        $this->assertSame('confirmed', $other->fresh()->status);

        $this->get($sub->unsubscribeUrl())->assertOk()->assertSee('Abgemeldet');
        $this->assertSame('unsubscribed', $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->unsubscribed_at);

        // List-Unsubscribe-Post (RFC 8058): ohne CSRF-Token
        $this->post(URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $other->id]))->assertOk();
        $this->assertSame('unsubscribed', $other->fresh()->status);
    }

    public function test_prune_loescht_nach_loeschkonzept(): void
    {
        $oldPending = NewsletterSubscriber::create(['email' => 'alt@example.test', 'status' => 'pending', 'source' => 'footer']);
        $oldPending->forceFill(['created_at' => now()->subDays(31)])->save();
        $freshPending = NewsletterSubscriber::create(['email' => 'neu@example.test', 'status' => 'pending', 'source' => 'footer']);
        $recentUnsub = NewsletterSubscriber::create(['email' => 'weg@example.test', 'status' => 'unsubscribed', 'unsubscribed_at' => now()->subMonth(),
            'first_name' => 'Wilhelm', 'interests' => ['jagd'], 'source' => 'footer']);
        $ancientUnsub = NewsletterSubscriber::create(['email' => 'uralt@example.test', 'status' => 'unsubscribed', 'unsubscribed_at' => now()->subYears(3)->subDay(), 'source' => 'footer']);
        $confirmed = NewsletterSubscriber::create(['email' => 'treu@example.test', 'status' => 'confirmed', 'first_name' => 'Tina', 'interests' => ['sport'], 'source' => 'footer']);
        $confirmed->forceFill(['created_at' => now()->subYears(5)])->save();

        $this->artisan('newsletter:prune')
            ->expectsOutput('Unbestätigt gelöscht: 1, Zusatzangaben entfernt: 1, Sperrliste abgelaufen: 1')
            ->assertSuccessful();

        $this->assertModelMissing($oldPending);
        $this->assertModelExists($freshPending);
        $this->assertModelMissing($ancientUnsub);
        // Abgemeldete bleiben als Sperrliste, aber ohne Zusatzangaben
        $recentUnsub->refresh();
        $this->assertNull($recentUnsub->first_name);
        $this->assertNull($recentUnsub->interests);
        $this->assertSame('weg@example.test', $recentUnsub->email);
        // Bestätigte Abonnenten bleiben unverändert
        $this->assertSame('Tina', $confirmed->fresh()->first_name);
    }
}
