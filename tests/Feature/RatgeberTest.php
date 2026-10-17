<?php

namespace Tests\Feature;

use App\Mail\NewsletterConfirm;
use App\Mail\NewsletterWelcome;
use App\Mail\RatgeberDownload;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Leadmagnet: E-Mail eintragen → Double-Opt-in → Ratgeber-PDF herunterladen. */
class RatgeberTest extends TestCase
{
    use RefreshDatabase;

    public function test_ratgeber_nach_double_opt_in(): void
    {
        Mail::fake();

        // Normales Formular (POST), funktioniert ohne JavaScript
        $this->from('/')->post(route('newsletter.subscribe'), ['variant' => 'lead', 'email' => 'jaeger@example.test'])
            ->assertRedirect('/#ratgeber')->assertSessionHasErrors('consent', null, 'newsletter_lead');
        $this->from('/')->post(route('newsletter.subscribe'), ['variant' => 'lead', 'email' => 'jaeger@example.test', 'consent' => '1'])
            ->assertRedirect('/#ratgeber')->assertSessionHas('newsletter_done', 'lead');
        $this->get('/')->assertSee('Fast geschafft');

        $sub = NewsletterSubscriber::where('email', 'jaeger@example.test')->firstOrFail();
        $this->assertSame('ratgeber', $sub->source);
        Mail::assertQueued(NewsletterConfirm::class, fn ($m) => str_contains($m->envelope()->subject, 'Waffenratgeber')
            && str_contains($m->render(), 'Bestätigen und Ratgeber herunterladen'));

        // Vor der Bestätigung kein Download
        $this->get($sub->ratgeberUrl())->assertForbidden();

        $confirm = $this->get(route('newsletter.confirm', $sub->confirm_token))
            ->assertOk()->assertSee('Waffenratgeber herunterladen (PDF)');
        preg_match('#href="([^"]*newsletter/ratgeber[^"]*)"#', $confirm->getContent(), $m);
        $this->assertNotEmpty($m, 'Download-Link fehlt auf der Bestätigungsseite');

        $download = $this->get(html_entity_decode($m[1]))->assertOk();
        $this->assertStringContainsString('Armory-Falter-Waffenratgeber.pdf', $download->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', file_get_contents($download->baseResponse->getFile()->getPathname()));

        // Ohne Signatur kein Zugriff
        $this->get(route('newsletter.ratgeber', $sub))->assertForbidden();

        // Willkommensmail enthält den Link ebenfalls
        $this->assertStringContainsString('Waffenratgeber herunterladen', (new NewsletterWelcome($sub->fresh(), 1))->render());
    }

    public function test_bestaetigter_abonnent_bekommt_link_direkt(): void
    {
        Mail::fake();
        NewsletterSubscriber::create(['email' => 'stamm@example.test', 'status' => 'confirmed', 'confirmed_at' => now(), 'source' => 'footer']);

        $this->from('/')->post(route('newsletter.subscribe'), ['variant' => 'lead', 'email' => 'stamm@example.test', 'consent' => '1'])
            ->assertSessionHas('newsletter_done', 'lead');

        Mail::assertQueued(RatgeberDownload::class);
        Mail::assertNotQueued(NewsletterConfirm::class);
    }
}
