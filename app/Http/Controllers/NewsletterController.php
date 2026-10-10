<?php

namespace App\Http\Controllers;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterEvent;
use App\Models\NewsletterSubscriber;
use App\Services\Newsletter\NewsletterService;

class NewsletterController extends Controller
{
    public function confirm(string $token, NewsletterService $service)
    {
        $sub = $service->confirm($token);
        $download = $sub && $sub->source === 'ratgeber' && app(\App\Support\Settings::class)->get('home.lead.file')
            ? \Illuminate\Support\Facades\URL::temporarySignedRoute('newsletter.ratgeber', now()->addDays(7), ['subscriber' => $sub->id])
            : null;

        return view('pages.message', [
            'title' => $sub ? 'Anmeldung bestätigt' : 'Link ungültig',
            'message' => $sub ? 'Vielen Dank! Sie erhalten ab sofort unseren Newsletter.' : 'Der Bestätigungslink ist abgelaufen oder wurde bereits verwendet. Bitte melden Sie sich erneut an.',
            'action' => $download ? ['label' => 'Waffenratgeber herunterladen (PDF)', 'url' => $download] : null,
        ]);
    }

    /** Leadmagnet-Download, nur über signierten Link nach Double-Opt-in. */
    public function ratgeber(NewsletterSubscriber $subscriber)
    {
        abort_unless($subscriber->status === 'confirmed', 403);
        $file = app(\App\Support\Settings::class)->get('home.lead.file');
        abort_unless($file && \Illuminate\Support\Facades\Storage::disk('public')->exists($file), 404);

        return \Illuminate\Support\Facades\Storage::disk('public')->download($file, 'Armory-Falter-Waffenratgeber.pdf');
    }

    public function unsubscribe(NewsletterSubscriber $subscriber, NewsletterService $service)
    {
        $service->unsubscribe($subscriber);

        return view('pages.message', ['title' => 'Abgemeldet', 'message' => 'Sie wurden vom Newsletter abgemeldet und erhalten keine weiteren E-Mails.']);
    }

    /** Öffnungs-Tracking (nur bei Anmeldung mit Hinweis auf Erfolgsmessung). */
    public function open(NewsletterCampaign $campaign, NewsletterSubscriber $subscriber)
    {
        NewsletterEvent::create(['newsletter_campaign_id' => $campaign->id, 'newsletter_subscriber_id' => $subscriber->id, 'event' => 'open', 'occurred_at' => now()]);

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store']);
    }
}
