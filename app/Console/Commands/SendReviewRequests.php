<?php

namespace App\Console\Commands;

use App\Mail\ReviewRequest;
use App\Models\Order;
use App\Services\Reviews\ReviewService;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Bewertungseinladung X Tage nach Zustellung (Standard 7, Backend: Einstellungen → Bewertungen).
 *
 * Rechtlicher Rahmen: Eine Bitte um Bewertung per E-Mail ist Werbung, auch an Bestandskunden
 * (BGH, Urt. v. 10.07.2018 – VI ZR 225/17). Ohne Einwilligung ist sie nur unter den Voraussetzungen des
 * § 7 Abs. 3 UWG zulässig: (1) E-Mail-Adresse im Zusammenhang mit dem Verkauf erhalten, (2) Bezug auf eigene, ähnliche
 * Waren – hier die gekaufte Ware selbst, (3) kein Widerspruch des Kunden, (4) klarer Hinweis auf das Widerspruchsrecht
 * bei der Erhebung der Adresse UND bei jeder Verwendung. Deshalb:
 *  - nur Bestellungen mit review_invite_allowed: Hinweis an der Kasse gezeigt, Kunde hat dort nicht widersprochen
 *    (Altbestellungen ohne diesen Hinweis bekommen nie eine Einladung),
 *  - kein Versand nach Widerspruch (users.review_requests_opted_out_at, Link in jeder Mail und im Kundenkonto),
 *  - höchstens eine Einladung je Bestellung, keine Erinnerungen, keine Gegenleistung für Bewertungen.
 * Diese Einordnung ersetzt keine Rechtsberatung; vor dem Livegang anwaltlich bestätigen lassen.
 */
class SendReviewRequests extends Command
{
    protected $signature = 'reviews:send-requests {--limit=200 : Höchstzahl E-Mails pro Lauf} {--dry-run : Nur anzeigen, nichts senden}';

    protected $description = 'Versendet fällige Bewertungseinladungen für zugestellte Bestellungen';

    public function handle(Settings $settings, ReviewService $reviews): int
    {
        if (! (bool) $settings->get('reviews.request_enabled', config('shop.reviews.request_enabled', true))) {
            $this->info('Bewertungseinladungen sind ausgeschaltet.');

            return self::SUCCESS;
        }
        $days = max(1, (int) $settings->get('reviews.request_days', config('shop.reviews.request_days', 7)));
        $sent = 0;

        $due = Order::query()
            ->whereIn('status', ReviewService::DELIVERED)
            ->where('review_invite_allowed', true)
            ->whereNull('review_requested_at')
            ->where('delivered_at', '<=', now()->subDays($days))
            ->where('delivered_at', '>=', now()->subDays($days + 30)) // nichts Uraltes nachholen (z. B. nach Ausfall)
            ->whereHas('user', fn ($q) => $q->where('is_active', true)->whereNull('review_requests_opted_out_at'))
            ->with('user')
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        foreach ($due as $order) {
            $products = $reviews->reviewableProducts($order->user)
                ->whereIn('id', $order->items()->where('item_type', 'product')->pluck('product_id'))->values();
            if ($this->option('dry-run')) {
                $this->line("{$order->order_number} → {$order->user->email} ({$products->count()} Artikel)");

                continue;
            }
            // Vor dem Versand atomar markieren: parallele Läufe senden nichts doppelt
            if (! Order::whereKey($order->id)->whereNull('review_requested_at')->update(['review_requested_at' => now()])) {
                continue;
            }
            if ($products->isEmpty()) {
                continue; // alles schon bewertet
            }
            Mail::to($order->user->email)->queue(new ReviewRequest($order, $order->user, $products));
            $sent++;
        }

        $this->info(($this->option('dry-run') ? 'Fällig: '.$due->count() : 'Eingereiht: '.$sent).' Einladung(en).');

        return self::SUCCESS;
    }
}
