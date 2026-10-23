<?php

namespace Tests\Feature;

use App\Http\Middleware\PageCache;
use App\Models\Product;
use App\Models\Redirect;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * HTTP-Schicht: Sicherheits-Header, noindex außerhalb der Live-Seite, Ganzseiten-Cache (Schlüssel je Host, CSRF-Platzhalter),
 * 301-Weiterleitungen alter URLs, Sitemap/robots.txt und der Cron-Endpunkt.
 */
class HttpLayerTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    private function production(): void
    {
        $this->app['env'] = 'production';
        config(['app.noindex' => false]);
    }

    public function test_sicherheits_header_auf_jeder_seite(): void
    {
        $response = $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $response->assertHeaderMissing('Strict-Transport-Security'); // nur über HTTPS

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_noindex_ausser_auf_der_live_seite(): void
    {
        // Test-/Staging-Umgebung: alles noindex, robots.txt sperrt alles
        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertSame("User-agent: *\nDisallow: /\n", $this->get('/robots.txt')->getContent());

        $this->production();
        $this->get('/shop')->assertOk()->assertHeaderMissing('X-Robots-Tag');
        $this->get('/konto/anmelden')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $robots = $this->get('/robots.txt')->getContent();
        $this->assertStringContainsString('Disallow: /kasse', $robots);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $robots);

        // Live-Umgebung mit APP_NOINDEX (z. B. Vorabschaltung)
        config(['app.noindex' => true]);
        $this->get('/shop')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('Disallow: /', $this->get('/robots.txt')->getContent());
    }

    public function test_seitencache_fuer_anonyme_besucher_mit_frischem_csrf_token(): void
    {
        $this->get('/shop')->assertOk()->assertHeader('X-Page-Cache', 'MISS');
        $hit = $this->get('/shop')->assertOk()->assertHeader('X-Page-Cache', 'HIT');

        // Sitzungsgebundene Werte werden je Anfrage neu eingesetzt
        $this->assertStringNotContainsString('__AF_CSRF__', $hit->getContent());
        $this->assertStringContainsString(session()->token(), $hit->getContent());

        // Schlüssel enthält den Host: www.… bekommt nicht die Seite eines anderen Hosts (absolute URLs)
        $this->get('http://www.example.test/shop')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('http://www.example.test/shop')->assertHeader('X-Page-Cache', 'HIT');

        // Reihenfolge der Parameter egal, zu viele Parameter nie gecacht
        $this->get('/shop?b=2&a=1')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/shop?a=1&b=2')->assertHeader('X-Page-Cache', 'HIT');
        $this->get('/shop?a=1&b=2&c=3&d=4')->assertHeaderMissing('X-Page-Cache');

        // Invalidierung über die Versionsnummer
        PageCache::flush();
        $this->get('/shop')->assertHeader('X-Page-Cache', 'MISS');
    }

    public function test_kein_seitencache_mit_konto_warenkorb_oder_auf_ausnahmen(): void
    {
        $this->get('/shop')->assertHeader('X-Page-Cache', 'MISS');

        $this->actingAs($this->customer())->get('/shop')->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->app['auth']->forgetGuards();

        $this->withCookie(CartService::COOKIE, 'irgendein-token')->get('/shop')->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get('/vertrag-widerrufen')->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get('/vertrag-widerrufen')->assertHeaderMissing('X-Page-Cache');
    }

    public function test_alte_urls_werden_dauerhaft_weitergeleitet(): void
    {
        $redirect = Redirect::create(['from_path' => '/produkt-kategorie/pistolen/', 'to_path' => '/shop/kurzwaffen/pistolen', 'status_code' => 301]);

        $this->get('/produkt-kategorie/pistolen/')->assertStatus(301)->assertRedirect('/shop/kurzwaffen/pistolen');
        $this->get('/produkt-kategorie/pistolen')->assertStatus(301)->assertRedirect('/shop/kurzwaffen/pistolen');
        $this->assertSame(2, $redirect->fresh()->hits);

        Redirect::create(['from_path' => '/aktion', 'to_path' => '/shop', 'status_code' => 302]);
        $this->get('/aktion')->assertStatus(302);

        $this->get('/gibt-es-nicht')->assertNotFound();
    }

    public function test_sitemap_listet_nur_sichtbare_produkte(): void
    {
        Product::where('sku', 'HK-CASE-PISTOL')->update(['authority_only' => true]);
        Product::where('sku', 'SIG-ROMEO5')->update(['status' => 'inactive']);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'Sitemap ist kein gültiges XML');
        $locs = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

        $this->assertContains(route('home'), $locs);
        $this->assertContains(route('product.show', Product::where('sku', 'GLO-G17-G5')->first()), $locs);
        $this->assertContains(route('calibers.show', '9mm-luger'), $locs);
        $this->assertNotContains(route('product.show', Product::where('sku', 'HK-CASE-PISTOL')->first()), $locs);
        $this->assertNotContains(route('product.show', Product::where('sku', 'SIG-ROMEO5')->first()), $locs);
        $this->assertSame(count($locs), count(array_unique($locs)), 'Doppelte URLs in der Sitemap');
    }

    public function test_cron_endpunkt_ohne_gueltiges_token_unsichtbar(): void
    {
        config(['shop.cron.token' => 'kurz']);
        $short = $this->get('/cron/kurzkurzkurzkurz'); // zu kurzes Token deaktiviert den Endpunkt
        config(['shop.cron.token' => str_repeat('a', 40)]);
        $wrong = $this->get('/cron/'.str_repeat('b', 40));

        foreach ([$short, $wrong] as $response) {
            $this->assertFalse($response->isSuccessful(), 'Scheduler ohne gültiges Token ausgelöst');
            $this->assertStringNotContainsString("OK\n", $response->getContent());
        }

        // Die Route läuft ohne Session; die 404-Seite darf trotzdem nicht an fehlendem $errors scheitern
        $short->assertNotFound();
        $wrong->assertNotFound();
    }
}
