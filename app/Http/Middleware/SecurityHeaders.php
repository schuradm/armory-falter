<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($request->is('kms-login*', 'konto*', 'kasse*') || ! app()->isProduction() || config('app.noindex')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        // CSP im Report-Only-Modus starten und nach Prüfung der Reports scharf schalten (siehe 13-sicherheit.md).
        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://www.paypal.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "font-src 'self'",
            'frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com https://www.paypal.com',
            "connect-src 'self' https://challenges.cloudflare.com",
            "media-src 'self' blob:",
            "form-action 'self' https://www.paypal.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
        ]));

        return $response;
    }
}
