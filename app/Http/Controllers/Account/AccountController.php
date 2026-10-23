<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ProductDocument;
use App\Services\AuditLogger;
use App\Services\Reviews\ReviewService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function __construct(Seo $seo)
    {
        $seo->noindex();
    }

    public function dashboard(Request $request, Seo $seo): View
    {
        $seo->title('Mein Konto');
        $user = $request->user()->load(['activeEntitlements.caliber']);
        $orders = $user->orders()->latest('placed_at')->take(3)->get();
        $openDocs = $user->documents()->awaitingReview()->count();

        return view('account.dashboard', compact('user', 'orders', 'openDocs'));
    }

    public function orders(Request $request, Seo $seo): View
    {
        $seo->title('Meine Bestellungen');
        $orders = $request->user()->orders()->withCount('items')->latest('placed_at')->paginate(10, pageName: 'seite');

        return view('account.orders', compact('orders'));
    }

    public function order(Order $order, Seo $seo): View
    {
        abort_unless($order->user_id === Auth::id(), 404);
        $seo->title('Bestellung '.$order->order_number);
        $order->load(['items', 'shipments.events', 'invoices', 'statusHistory']);

        return view('account.order', compact('order'));
    }

    public function invoice(Invoice $invoice): StreamedResponse
    {
        abort_unless($invoice->order->user_id === Auth::id(), 404);

        return Storage::disk('local')->download($invoice->pdf_path, $invoice->invoice_number.'.pdf');
    }

    public function invoices(Request $request, Seo $seo): View
    {
        $seo->title('Rechnungen');
        $invoices = Invoice::whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))->with('order')->latest('issued_at')->get();

        return view('account.invoices', compact('invoices'));
    }

    /** Anleitungen und Dokumente zu gekauften Produkten. */
    public function downloads(Request $request, Seo $seo): View
    {
        $seo->title('Downloads');
        $productIds = Order::where('user_id', $request->user()->id)->join('order_items', 'orders.id', '=', 'order_items.order_id')->pluck('order_items.product_id');
        $downloads = ProductDocument::whereIn('product_id', $productIds)->with('product')->get()->groupBy('product.name');

        return view('account.downloads', compact('downloads'));
    }

    public function productDocument(ProductDocument $document): StreamedResponse
    {
        $allowed = $document->visibility === 'public'
            || ($document->visibility === 'customers' && Auth::check())
            || (Auth::check() && Order::where('user_id', Auth::id())->whereHas('items', fn ($q) => $q->where('product_id', $document->product_id))->exists());
        abort_unless($allowed, 403);

        // Nur öffentliche Dokumente liegen im öffentlichen Speicher, alle anderen sind nur über diese Prüfung erreichbar
        $disk = $document->visibility === 'public' ? 'public' : 'local';

        return Storage::disk($disk)->download($document->path, \Str::slug($document->title).'.pdf');
    }

    public function appointments(Request $request, Seo $seo): View
    {
        $seo->title('Meine Termine');
        $appointments = $request->user()->appointments()->with('service')->latest('starts_at')->get();

        return view('account.appointments', compact('appointments'));
    }

    public function requests(Request $request, Seo $seo): View
    {
        $seo->title('Meine Anfragen');
        $requests = $request->user()->tradeRequests()->latest()->get();

        return view('account.requests', compact('requests'));
    }

    public function security(Seo $seo): View
    {
        $seo->title('Sicherheit & Zwei-Faktor-Anmeldung');

        return view('account.security');
    }

    public function profile(Seo $seo): View
    {
        $seo->title('Persönliche Daten');

        return view('account.profile', ['user' => Auth::user()->load('addresses')]);
    }

    public function privacy(Seo $seo): View
    {
        $seo->title('Datenschutz & Konto');

        return view('account.privacy');
    }

    /** Bewertungseinladungen per E-Mail an/aus (Widerspruch nach § 7 Abs. 3 UWG). */
    public function reviewInvites(Request $request): RedirectResponse
    {
        $allow = $request->boolean('allow');
        $request->user()->forceFill(['review_requests_opted_out_at' => $allow ? null : now()])->save();

        return back()->with('status', $allow ? 'Sie erhalten wieder Bewertungseinladungen.' : 'Sie erhalten keine Bewertungseinladungen mehr.');
    }

    /** Art. 15/20 DSGVO: Datenauskunft als JSON. */
    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        $u = $request->user()->load(['addresses', 'orders.items', 'documents', 'entitlements', 'wishlists.products:id,name', 'tradeRequests', 'appointments', 'reviews.product:id,name']);
        $audit->log('privacy.export', $u);
        $data = [
            'exportiert_am' => now()->toIso8601String(),
            'konto' => $u->only(['customer_number', 'customer_type', 'salutation', 'first_name', 'last_name', 'company_name', 'email', 'phone', 'birth_date', 'created_at']),
            'adressen' => $u->addresses->map->toSnapshot(),
            'bestellungen' => $u->orders->map(fn ($o) => $o->only(['order_number', 'placed_at', 'status', 'total_gross', 'billing_address', 'shipping_address']) + ['positionen' => $o->items->map->only(['sku', 'name', 'quantity', 'line_total_gross'])]),
            'dokumente' => $u->documents->map(fn ($d) => ['typ' => $d->type->label(), 'status' => $d->status->label(), 'hochgeladen' => $d->created_at, 'gueltig_bis' => $d->valid_until]),
            'berechtigungen' => $u->entitlements->map->describe(),
            'merklisten' => $u->wishlists->map(fn ($w) => ['name' => $w->name, 'produkte' => $w->products->pluck('name')]),
            'anfragen' => $u->tradeRequests->map->only(['reference', 'type', 'model', 'status', 'created_at']),
            'termine' => $u->appointments->map->only(['type', 'starts_at', 'status']),
            'bewertungen' => $u->reviews->map(fn ($r) => ['produkt' => $r->product?->name, 'sterne' => $r->rating, 'titel' => $r->title, 'text' => $r->body, 'status' => $r->statusLabel(), 'datum' => $r->created_at]),
        ];

        return response()->streamDownload(fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)), 'armory-falter-datenauskunft.json', ['Content-Type' => 'application/json']);
    }

    /**
     * Art. 17 DSGVO: Konto löschen. Daten mit gesetzlicher Aufbewahrungspflicht (Rechnungen, Waffenhandelsbuch)
     * bleiben gesperrt erhalten; personenbezogene Kontodaten werden anonymisiert.
     */
    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();
        $audit->log('privacy.account_deleted', $user);

        $user->documents()->each(function ($doc) {
            Storage::disk(config('shop.documents.disk'))->delete($doc->storage_path);
            $doc->forceFill(['storage_path' => 'deleted', 'document_number' => null])->save();
        });
        $user->addresses()->delete();
        $user->wishlists()->delete();
        // Bewertungen tragen den Namen: mit dem Konto löschen, Produktdurchschnitt neu berechnen
        $reviewService = app(ReviewService::class);
        $user->reviews()->get()->each(fn ($review) => $reviewService->delete($review));
        $user->forceFill([
            'first_name' => 'Gelöscht', 'last_name' => 'Kunde '.$user->id, 'email' => 'geloescht-'.$user->id.'@invalid.local',
            'phone' => null, 'company_name' => null, 'password' => Hash::make(\Str::random(40)), 'is_active' => false,
            'two_factor_secret' => null, 'two_factor_recovery_codes' => null,
        ])->save();
        $user->delete();

        Auth::logout();
        $request->session()->invalidate();

        return redirect()->route('home')->with('status', 'Ihr Konto wurde gelöscht.');
    }
}
