<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Captcha-Prüfung. Standard: Cloudflare Turnstile (keine Cookies, kein US-Tracking).
 * Optional: Google reCAPTCHA v3 – nur nach Einwilligung im Cookie-Banner einsetzen.
 */
class Captcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $driver = config('shop.captcha.driver');
        if ($driver === 'none' || app()->environment('testing')) {
            return;
        }
        if (! is_string($value) || $value === '') {
            $fail('Bitte bestätigen Sie, dass Sie kein Roboter sind.');

            return;
        }

        $ok = match ($driver) {
            'turnstile' => Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('shop.captcha.turnstile_secret'), 'response' => $value, 'remoteip' => request()->ip(),
            ])->json('success') === true,
            'recaptcha' => (function () use ($value) {
                $res = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('shop.captcha.recaptcha_secret'), 'response' => $value, 'remoteip' => request()->ip(),
                ])->json();

                return ($res['success'] ?? false) && ($res['score'] ?? 0) >= 0.5;
            })(),
            default => true,
        };

        if (! $ok) {
            $fail('Die Sicherheitsprüfung ist fehlgeschlagen. Bitte versuchen Sie es erneut.');
        }
    }
}
