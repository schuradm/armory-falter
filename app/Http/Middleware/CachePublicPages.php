<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ganzseiten-Cache für anonyme Besucher ohne Warenkorb (Shared Hosting hat kein Varnish).
 * Ein Treffer spart Datenbank und Blade-Rendering: typische TTFB < 50 ms.
 *
 * Sitzungsgebundene Werte (CSRF-Token, CSP-Nonce) werden beim Speichern durch Platzhalter ersetzt und beim
 * Ausliefern wieder eingesetzt, damit Livewire-Formulare auch aus dem Cache funktionieren.
 * Invalidierung: PageCache::flush() erhöht die Versionsnummer (bei Änderungen an Produkten, Kategorien, Inhalten).
 */
class CachePublicPages
{
    private const TOKEN = '__AF_CSRF__';

    private const NONCE = '__AF_NONCE__';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cacheable($request)) {
            return $next($request);
        }

        $key = PageCache::key($request);
        if ($html = Cache::get($key)) {
            return response($this->hydrate($html))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Page-Cache', 'HIT')
                ->header('Cache-Control', 'private, no-cache');
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->session()->has('errors') && ! $request->session()->has('status')) {
            Cache::put($key, $this->dehydrate($response->getContent()), now()->addSeconds(config('shop.page_cache.ttl', 900)));
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }

    private function cacheable(Request $request): bool
    {
        return config('shop.page_cache.enabled', true)
            && $request->isMethod('GET')
            && ! $request->user()
            && ! $request->hasCookie('af_cart')
            && ! $request->header('X-Livewire')
            && empty($request->session()->get('_flash.old')) // Meldungen nach einem Formular (z. B. Newsletter) nie aus dem Cache
            && ! $request->is(...config('shop.page_cache.except', []))
            && count($request->query()) <= 3; // Filterkombinationen nicht unbegrenzt cachen
    }

    private function dehydrate(string $html): string
    {
        $html = str_replace(csrf_token(), self::TOKEN, $html);

        return ($nonce = Vite::cspNonce()) ? str_replace($nonce, self::NONCE, $html) : $html;
    }

    private function hydrate(string $html): string
    {
        return str_replace([self::TOKEN, self::NONCE], [csrf_token(), (string) Vite::cspNonce()], $html);
    }
}
