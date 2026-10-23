<?php

namespace App\Http\Controllers;

use App\Mail\WithdrawalReceived;
use App\Models\Order;
use App\Models\Withdrawal;
use App\Rules\Honeypot;
use App\Services\AuditLogger;
use App\Services\NumberGenerator;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Widerrufsbutton nach Art. 11a der Verbraucherrechte-Richtlinie (RL (EU) 2023/2673, anzuwenden seit 19.06.2026):
 * 1. „Vertrag widerrufen“: Name, Bestellnummer, E-Mail für die Bestätigung angeben
 * 2. „Widerruf bestätigen“: Erklärung absenden
 * 3. Eingangsbestätigung mit Inhalt, Datum und Uhrzeit sofort per E-Mail (dauerhafter Datenträger)
 */
class WithdrawalController extends Controller
{
    private const DRAFT = 'withdrawal.draft';

    public function create(Request $request, Seo $seo): View
    {
        $seo->title('Vertrag widerrufen')->noindex()->breadcrumb('Vertrag widerrufen', route('withdrawal.create'));
        $user = $request->user();
        $orders = $user ? $user->orders()->latest('placed_at')->limit(20)->get(['id', 'order_number', 'placed_at', 'total_gross']) : collect();
        $draft = $request->session()->get(self::DRAFT, []);

        return view('pages.withdrawal', [
            'step' => 'form',
            'orders' => $orders,
            'draft' => $draft + [
                'name' => $user?->name,
                'email' => $user?->email,
                'order_number' => $request->query('bestellung'),
            ],
        ]);
    }

    /** Schritt 2: Angaben prüfen, Bestätigungsseite mit „Widerruf bestätigen“. */
    public function review(Request $request, Seo $seo): View
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'order_number' => ['required', 'string', 'max:40'],
            'items' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => [new Honeypot],
        ], [
            'name.required' => 'Bitte geben Sie Ihren Namen an.',
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse für die Eingangsbestätigung an.',
            'order_number.required' => 'Bitte geben Sie die Bestellnummer an (steht in der Bestellbestätigung).',
        ]);
        unset($data['website']);
        $request->session()->put(self::DRAFT, $data);
        $seo->title('Widerruf bestätigen')->noindex()->breadcrumb('Vertrag widerrufen', route('withdrawal.create'));

        return view('pages.withdrawal', ['step' => 'review', 'draft' => $data, 'orders' => collect()]);
    }

    /** Schritt 3: verbindlich absenden. */
    public function store(Request $request, NumberGenerator $numbers, AuditLogger $audit): RedirectResponse
    {
        $data = $request->session()->pull(self::DRAFT);
        if (! $data) {
            return redirect()->route('withdrawal.create')->withErrors(['name' => 'Bitte geben Sie Ihre Angaben erneut ein.']);
        }

        $order = Order::where('order_number', $data['order_number'])->first();
        // Bestellung nur verknüpfen, wenn sie zum angemeldeten Kunden bzw. zur angegebenen E-Mail passt
        if ($order && ! ($request->user()?->id === $order->user_id || strcasecmp((string) $order->user?->email, $data['email']) === 0)) {
            $order = null;
        }

        $withdrawal = Withdrawal::create($data + [
            'reference' => $numbers->next('withdrawal', 'WR', 5),
            'order_id' => $order?->id,
            'user_id' => $request->user()?->id,
            'status' => 'received',
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'declared_at' => now(),
        ]);
        $audit->log('withdrawal.declared', $withdrawal, new: ['order_number' => $data['order_number']]);

        Mail::to($withdrawal->email)->send(new WithdrawalReceived($withdrawal));
        Mail::to(config('shop.company.email'))->queue(new WithdrawalReceived($withdrawal, internal: true));

        return redirect()->route('withdrawal.done', ['ref' => $withdrawal->reference]);
    }

    public function done(Request $request, Seo $seo): View
    {
        $seo->title('Widerruf eingegangen')->noindex();

        return view('pages.message', [
            'title' => 'Ihr Widerruf ist eingegangen',
            'message' => 'Vielen Dank. Wir haben Ihren Widerruf (Vorgang '.e((string) $request->query('ref')).') am '.now()->format('d.m.Y').' um '.now()->format('H:i').' Uhr erhalten und Ihnen eine Bestätigung per E-Mail gesendet. Wir melden uns zur Rückabwicklung. Erlaubnispflichtige Ware bitte nicht als normales Paket zurücksenden, sondern persönlich bringen oder den Versand mit uns abstimmen.',
        ]);
    }
}
