<?php

namespace App\Http\Controllers;

use App\Console\Commands\BuildRatgeber;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterEvent;
use App\Models\NewsletterSubscriber;
use App\Rules\Honeypot;
use App\Services\Newsletter\NewsletterService;
use App\Support\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    /**
     * Anmeldung (Fußzeile und Leadmagnet „Waffenratgeber“) als normales Formular. Das JavaScript wird für PageSpeed
     * erst nach dem Laden nachgeladen; ein Livewire-Formular ging verloren, wenn vorher abgeschickt wurde.
     * Ergebnis per Flash-Meldung, der Seiten-Cache wird dafür übergangen (CachePublicPages).
     */
    public function subscribe(Request $request, NewsletterService $service): RedirectResponse
    {
        $variant = $request->input('variant') === 'lead' ? 'lead' : 'footer';
        $bag = 'newsletter_'.$variant;
        $back = fn () => redirect()->to(strtok(url()->previous(), '#').($variant === 'lead' ? '#ratgeber' : '#newsletter'));

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'interests' => ['array'], 'interests.*' => ['in:jagd,sport,optik,sammler,behoerde'],
            'consent' => ['accepted'],
            'website' => [new Honeypot],
        ], [
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'consent.accepted' => 'Bitte bestätigen Sie die Einwilligung.',
        ]);
        if ($validator->fails()) {
            return $back()->withErrors($validator, $bag)->withInput($request->only('email', 'interests'));
        }
        if (! RateLimiter::attempt('nl:'.$request->ip(), 5, fn () => true, 3600)) {
            return $back()->withErrors(['email' => 'Zu viele Versuche. Bitte später erneut versuchen.'], $bag);
        }

        $service->subscribe((string) $request->input('email'), $request->input('interests', []), null,
            $variant === 'lead' ? 'ratgeber' : 'footer', $request->user()?->id);

        return $back()->with('newsletter_done', $variant);
    }

    public function confirm(string $token, NewsletterService $service): View
    {
        $sub = $service->confirm($token);
        $download = $sub && $sub->source === 'ratgeber' ? $sub->ratgeberUrl() : null;

        return view('pages.message', [
            'title' => $sub ? 'Anmeldung bestätigt' : 'Link ungültig',
            'message' => $sub ? ($download ? 'Vielen Dank! Ihr Waffenratgeber steht zum Download bereit. Den Link finden Sie außerdem in unserer Willkommensmail.' : 'Vielen Dank! Sie erhalten ab sofort unseren Newsletter.') : 'Der Bestätigungslink ist abgelaufen oder wurde bereits verwendet. Bitte melden Sie sich erneut an.',
            'action' => $download ? ['label' => 'Waffenratgeber herunterladen (PDF)', 'url' => $download] : null,
        ]);
    }

    /** Leadmagnet-Download, nur über signierten Link nach Double-Opt-in. */
    public function ratgeber(NewsletterSubscriber $subscriber): StreamedResponse|BinaryFileResponse
    {
        abort_unless($subscriber->status === 'confirmed', 403);
        // Im Backend hochgeladenes PDF hat Vorrang, sonst der mitgelieferte Ratgeber (php artisan ratgeber:pdf)
        $file = app(Settings::class)->get('home.lead.file');
        if ($file && Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->download($file, 'Armory-Falter-Waffenratgeber.pdf');
        }
        abort_unless(is_readable($bundled = resource_path(BuildRatgeber::FILE)), 404);

        return response()->download($bundled, 'Armory-Falter-Waffenratgeber.pdf');
    }

    public function unsubscribe(NewsletterSubscriber $subscriber, NewsletterService $service): View
    {
        $service->unsubscribe($subscriber);

        return view('pages.message', ['title' => 'Abgemeldet', 'message' => 'Sie wurden vom Newsletter abgemeldet und erhalten keine weiteren E-Mails.']);
    }

    /** Öffnungs-Tracking (nur bei Anmeldung mit Hinweis auf Erfolgsmessung). */
    public function open(NewsletterCampaign $campaign, NewsletterSubscriber $subscriber): Response
    {
        NewsletterEvent::create(['newsletter_campaign_id' => $campaign->id, 'newsletter_subscriber_id' => $subscriber->id, 'event' => 'open', 'occurred_at' => now()]);

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store']);
    }
}
