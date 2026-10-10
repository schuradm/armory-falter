<?php

namespace App\Services\Newsletter;

use App\Mail\NewsletterConfirm;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Double-Opt-in nach § 7 UWG / DSGVO: Anmeldung, Bestätigung, Abmeldung mit Nachweis. */
class NewsletterService
{
    public function subscribe(string $email, array $interests = [], ?string $firstName = null, string $source = 'footer', ?int $userId = null): NewsletterSubscriber
    {
        $email = Str::lower(trim($email));
        $sub = NewsletterSubscriber::firstOrNew(['email' => $email]);

        if ($sub->exists && $sub->status === 'confirmed') {
            $sub->update(['interests' => array_values(array_unique(array_merge($sub->interests ?? [], $interests)))]);

            return $sub;
        }

        $sub->fill([
            'first_name' => $firstName,
            'interests' => $interests,
            'status' => 'pending',
            'confirm_token' => Str::random(64),
            'opt_in_ip_hash' => hash('sha256', request()->ip().config('app.key')),
            'opt_in_at' => now(),
            'source' => $source,
            'user_id' => $userId,
        ])->save();

        Mail::to($sub->email)->queue(new NewsletterConfirm($sub));

        return $sub;
    }

    public function confirm(string $token): ?NewsletterSubscriber
    {
        $sub = NewsletterSubscriber::where('confirm_token', $token)->where('status', 'pending')
            ->where('opt_in_at', '>', now()->subDays(7))->first();
        if (! $sub) {
            return null;
        }
        $sub->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_ip_hash' => hash('sha256', request()->ip().config('app.key')),
            'confirm_token' => null,
        ]);

        return $sub;
    }

    public function unsubscribe(NewsletterSubscriber $sub): void
    {
        $sub->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
    }
}
