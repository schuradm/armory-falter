<?php

namespace Egulias\EmailValidator\Validation {
    // Test- und CI-Umgebungen ohne DNS: Die Registrierung prüft E-Mails mit email:rfc,dns.
    // DNSGetRecordWrapper ruft dns_get_record() unqualifiziert auf, daher genügt diese Attrappe.
    if (! function_exists(__NAMESPACE__.'\dns_get_record')) {
        function dns_get_record(string $hostname, int $type = DNS_ANY): array
        {
            return [['host' => $hostname, 'class' => 'IN', 'ttl' => 300, 'type' => 'A', 'ip' => '192.0.2.1']];
        }
    }
}

namespace Tests\Feature {

    use App\Enums\ComplianceStatus;
    use App\Enums\DocumentStatus;
    use App\Enums\OrderStatus;
    use App\Filament\Resources\CustomerDocumentResource\Pages\ViewCustomerDocument;
    use App\Filament\Resources\OrderResource\Pages\ViewOrder;
    use App\Livewire\Account\Documents;
    use App\Livewire\Checkout;
    use App\Mail\OrderConfirmation;
    use App\Mail\StaffOrderAlert;
    use App\Models\CustomerDocument;
    use App\Models\Order;
    use App\Models\Product;
    use App\Models\User;
    use App\Notifications\DocumentReviewed;
    use App\Notifications\OrderStatusChanged;
    use App\Services\Cart\CartService;
    use App\Services\Orders\OrderService;
    use Database\Seeders\CatalogSeeder;
    use Database\Seeders\LegalClassSeeder;
    use Database\Seeders\ProductSeeder;
    use Database\Seeders\RolesAndPermissionsSeeder;
    use Database\Seeders\ShippingMethodSeeder;
    use Database\Seeders\TaxRateSeeder;
    use Illuminate\Auth\Notifications\VerifyEmail;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Notification;
    use Illuminate\Support\Facades\Storage;
    use Livewire\Livewire;
    use Tests\TestCase;

    /**
     * Kompletter Bestellablauf: Registrierung → Dokumente → Freigabe → Bestellung (frei / erlaubnispflichtig / gemischt)
     * → Compliance → Zahlung → Rechnung → Versand, dazu Ablehnung, Storno und Bestand.
     * Ausführen gegen MySQL: DB_CONNECTION=mysql DB_DATABASE=armory_test php artisan test --filter=OrderFlowTest
     */
    class OrderFlowTest extends TestCase
    {
        use RefreshDatabase;

        private User $admin;

        protected function setUp(): void
        {
            parent::setUp();

            Mail::fake();
            Notification::fake();
            Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]); // Password::uncompromised()
            Storage::fake('documents');
            Storage::fake('local');
            config(['shop.documents.disk' => 'documents']);

            $this->seed([RolesAndPermissionsSeeder::class, LegalClassSeeder::class, TaxRateSeeder::class,
                ShippingMethodSeeder::class, CatalogSeeder::class, ProductSeeder::class]);

            $this->admin = new User(['first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'Geheim-Passwort-123']);
            $this->admin->forceFill(['is_staff' => true, 'is_active' => true, 'email_verified_at' => now(), 'two_factor_confirmed_at' => now()])->save();
            $this->admin->assignRole('super-admin');
        }

        // ---------------------------------------------------------------- Hilfen

        private function register(string $email = 'max@example.de'): User
        {
            $this->post('/konto/registrieren', [
                'customer_type' => 'private', 'salutation' => 'herr', 'first_name' => 'Max', 'last_name' => 'Testkunde',
                'birth_date' => '1985-05-01', 'email' => $email, 'password' => 'Sicheres-Passwort-2026!',
                'password_confirmation' => 'Sicheres-Passwort-2026!', 'privacy' => '1',
            ])->assertRedirect('/konto');

            return User::where('email', $email)->firstOrFail();
        }

        private function verifiedCustomer(): User
        {
            $user = User::factory()->create(['first_name' => 'Max', 'last_name' => 'Testkunde', 'birth_date' => '1985-05-01']);
            $user->assignRole('customer');

            return $user;
        }

        private function upload(User $user, string $type, string $validUntil = '2036-01-01'): CustomerDocument
        {
            Livewire::actingAs($user)->test(Documents::class)
                ->set('type', $type)
                ->set('validUntil', $validUntil)
                ->set('documentNumber', strtoupper($type).'-123')
                ->set('file', UploadedFile::fake()->image($type.'.png', 1400, 1000))
                ->set('confirm', true)
                ->call('save')
                ->assertHasNoErrors();

            return $user->documents()->latest('id')->firstOrFail();
        }

        /** Personalausweis (Alter) und grüne WBK mit einem Voreintrag freigeben. */
        private function entitle(User $user): void
        {
            $id = $this->upload($user, 'id_card');
            $wbk = $this->upload($user, 'wbk_green');
            $this->actingAs($this->admin);
            Livewire::test(ViewCustomerDocument::class, ['record' => $id->getKey()])
                ->callAction('approve', ['entitlements' => [], 'confirm_birth_date' => true, 'birth_date' => '1985-05-01'])
                ->assertHasNoActionErrors();
            // Vorbelegung des Formulars übernehmen: Voreintrag (beliebige Waffenart, 1 Stück) + Munitionserwerb
            Livewire::test(ViewCustomerDocument::class, ['record' => $wbk->getKey()])
                ->callAction('approve', ['confirm_birth_date' => true, 'birth_date' => '1985-05-01'])
                ->assertHasNoActionErrors();
            $user->refresh(); // Livewire::actingAs nutzt dieses Objekt als angemeldeten Kunden
        }

        /** @param array<string,int> $skus */
        private function fillCart(User $user, array $skus): void
        {
            $this->actingAs($user);
            foreach ($skus as $sku => $qty) {
                app(CartService::class)->add(Product::where('sku', $sku)->firstOrFail(), $qty);
            }
        }

        private function checkout(User $user, string $shipping, string $payment = 'prepayment', bool $declaration = false): Order
        {
            Livewire::actingAs($user)->test(Checkout::class)
                ->set('billing.street', 'Hauptstraße')->set('billing.house_number', '12')
                ->set('billing.postal_code', '72649')->set('billing.city', 'Wolfschlugen')
                ->call('toStep', 2)->assertHasNoErrors()
                ->set('shippingMethod', $shipping)->call('toStep', 3)->assertHasNoErrors()
                ->set('paymentMethod', $payment)->call('toStep', 4)->assertHasNoErrors()
                ->set('legalDeclaration', $declaration)->set('terms', true)
                ->call('placeOrder')
                ->assertHasNoErrors()
                ->assertRedirect();

            return $user->orders()->latest('id')->firstOrFail();
        }

        private function orderPage(Order $order)
        {
            $this->actingAs($this->admin);

            return Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()]);
        }

        // ---------------------------------------------------------------- Tests

        public function test_registration_sends_german_verification_mail_with_german_url(): void
        {
            $this->post('/konto/registrieren', ['email' => 'kaputt'])
                ->assertSessionHasErrors(['first_name' => 'Vorname muss ausgefüllt werden.', 'email' => 'E-Mail-Adresse muss eine gültige E-Mail-Adresse sein.']);

            $user = $this->register();
            $this->assertFalse($user->hasVerifiedEmail());
            $this->assertTrue($user->hasRole('customer'));
            $this->get('/kasse')->assertRedirect('/konto/email-bestaetigen');

            $url = null;
            Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $n) use ($user, &$url) {
                $mail = $n->toMail($user);
                $url = $mail->actionUrl;

                return $mail->subject === 'E-Mail-Adresse bestätigen' && str_contains($url, '/konto/email-bestaetigen/');
            });

            $this->get($url)->assertRedirect();
            $this->assertTrue($user->fresh()->hasVerifiedEmail());
        }

        public function test_free_item_with_prepayment(): void
        {
            $user = $this->verifiedCustomer();
            $this->fillCart($user, ['WAL-CLEAN-UNI' => 2]);
            $stock = Product::where('sku', 'WAL-CLEAN-UNI')->value('stock_quantity');

            $order = $this->checkout($user, 'dhl_standard');

            $this->assertSame(OrderStatus::PendingPayment, $order->status);
            $this->assertSame(ComplianceStatus::NotRequired, $order->compliance_status);
            $this->assertSame(2 * 4990, $order->subtotal_gross);
            $this->assertSame(595, $order->shipping_gross); // kostenlos erst ab 150 €
            $this->assertSame(10575, $order->total_gross);
            $this->assertSame((int) round(10575 - 10575 / 1.19), $order->total_tax);
            $this->assertSame($stock - 2, Product::where('sku', 'WAL-CLEAN-UNI')->value('stock_quantity'));
            $this->assertSame(0, app(CartService::class)->count());

            // Genau eine Bestätigung und eine Staff-Mail (Listener war doppelt registriert)
            Mail::assertQueuedCount(2);
            Mail::assertQueued(OrderConfirmation::class, fn (OrderConfirmation $m) => $m->hasTo($user->email)
                && str_contains($m->render(), 'Bitte überweisen Sie den Betrag') && str_contains($m->render(), '105,75 €'));
            Mail::assertQueued(StaffOrderAlert::class, fn (StaffOrderAlert $m) => str_contains($m->render(), '/kms-login/orders/'.$order->order_number)
                && ! str_contains($m->render(), '/admin/'));

            // Vorkasse: Zahlung buchen, Rechnung als bezahlt, Versand
            $this->orderPage($order)
                ->assertActionHidden('releaseCompliance')
                ->callAction('markPaid', ['reference' => 'Kontoauszug'])->assertHasNoActionErrors()
                ->callAction('ship', ['carrier' => 'dhl', 'tracking_number' => '00340434161234567890'])->assertHasNoActionErrors();

            $order->refresh();
            $this->assertSame(OrderStatus::Shipped, $order->status);
            $this->assertSame('dhl', $order->shipments()->value('carrier'));
            $this->assertCount(1, $order->invoices);
            Storage::disk('local')->assertExists($order->invoices->first()->pdf_path);
        }

        public function test_permit_gun_in_mixed_cart_full_workflow(): void
        {
            $user = $this->verifiedCustomer();

            // Ohne geprüfte WBK: Kasse leitet in den Warenkorb, der den Grund zeigt
            $this->fillCart($user, ['GLO-G17-G5' => 1, 'HK-CASE-PISTOL' => 1]);
            Livewire::actingAs($user)->test(Checkout::class)->assertRedirect(route('cart'));

            $this->entitle($user);
            Notification::assertSentToTimes($user, DocumentReviewed::class, 2);
            $this->assertNotNull($user->fresh()->age_verified_at);

            $glock = Product::where('sku', 'GLO-G17-G5')->first();
            $glockStock = $glock->stock_quantity;

            // Gemischter Warenkorb: strengste Versandart, nur Vorkasse/Bar, Erklärung Pflicht
            Livewire::actingAs($user)->test(Checkout::class)
                ->set('billing.street', 'Hauptstraße')->set('billing.house_number', '12')
                ->set('billing.postal_code', '72649')->set('billing.city', 'Wolfschlugen')
                ->call('toStep', 2)
                ->set('shippingMethod', 'dhl_standard')->call('toStep', 3)
                ->assertHasErrors('shippingMethod');
            $order = $this->checkout($user, 'weapon_personal_overnight', 'prepayment', declaration: true);

            $this->assertSame(OrderStatus::PendingCompliance, $order->status);
            $this->assertSame(ComplianceStatus::Pending, $order->compliance_status);
            $this->assertSame(79900 + 5900 + 3900, $order->total_gross);
            $this->assertSame(14322, $order->total_tax);
            $this->assertSame($glockStock - 1, $glock->fresh()->stock_quantity);
            Mail::assertQueued(OrderConfirmation::class, fn ($m) => str_contains($m->render(), 'Erst nach dieser Freigabe erhalten Sie die Zahlungsaufforderung'));
            Mail::assertQueued(StaffOrderAlert::class, fn ($m) => str_starts_with($m->envelope()->subject, '[PRÜFUNG]'));

            // Vor der Freigabe weder Zahlung noch Versand
            $page = $this->orderPage($order)->assertActionHidden('markPaid')->assertActionHidden('ship');

            // Freigabe → Zahlungsaufforderung; danach Zahlung, Rechnung, Versand auf derselben Seite (Livewire-Roundtrips)
            $page->callAction('releaseCompliance', ['note' => 'WBK Nr. 123 geprüft, Voreintrag Kurzwaffe.', 'original_document_seen' => true])
                ->assertHasNoActionErrors()
                ->callAction('markPaid', ['reference' => 'Kontoauszug 17.10.'])->assertHasNoActionErrors()
                ->callAction('invoice')
                ->assertFileDownloaded('RE-'.now()->year.'-000001.pdf')
                ->callAction('ship', ['carrier' => 'dhl', 'tracking_number' => '00340434161234567890'])->assertHasNoActionErrors();

            $order->refresh();
            $this->assertSame(OrderStatus::Shipped, $order->status);
            $this->assertSame(ComplianceStatus::Approved, $order->compliance_status);
            $ent = $user->entitlements()->where('type', 'wbk_voreintrag')->first();
            $this->assertSame(1, $ent->quantity_used);
            $this->assertSame('consumed', $ent->status);

            // Statusmails: Status zum Zeitpunkt des Wechsels, Bankdaten nur in der Zahlungsaufforderung
            $sent = Notification::sent($user, OrderStatusChanged::class)->map(fn (OrderStatusChanged $n) => $n->toMail($user));
            $this->assertSame([
                'Bestellung '.$order->order_number.': Wartet auf Zahlung',
                'Bestellung '.$order->order_number.': Bezahlt',
                'Bestellung '.$order->order_number.': Versendet',
            ], $sent->pluck('subject')->all());
            $this->assertStringContainsString('Bitte überweisen Sie jetzt 897,00 €', implode(' ', $sent[0]->introLines));
            if (blank(config('shop.company.bank.bic'))) {
                $this->assertStringNotContainsString('BIC', implode(' ', $sent[0]->introLines));
            }
            $this->assertStringEndsWith('/konto/bestellungen/'.$order->order_number, $sent[2]->actionUrl);

            // Rechnung: Beträge, USt., deutsches Format, bezahlt-Vermerk statt Zahlungsaufforderung
            $html = view('pdf.invoice', ['order' => $order->load('items', 'user', 'payments'), 'number' => 'RE-TEST', 'company' => config('shop.company')])->render();
            foreach (['799,00 €', '59,00 €', '39,00 €', '897,00 €', "netto 753,78\u{00A0}€", '143,22 €', 'bereits beglichen'] as $needle) {
                $this->assertStringContainsString($needle, $html);
            }
            $this->assertStringNotContainsString('Bitte überweisen', $html);

            // Kunde lädt seine Rechnung im Konto herunter
            $this->actingAs($user)->get(route('account.invoices.download', $order->invoices->first()))->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            // Voreintrag verbraucht → zweite Glock nicht mehr bestellbar
            $this->fillCart($user, ['GLO-G17-G5' => 1]);
            Livewire::actingAs($user)->test(Checkout::class)->assertRedirect(route('cart'));
        }

        public function test_rejected_document_grants_nothing(): void
        {
            $user = $this->verifiedCustomer();
            $wbk = $this->upload($user, 'wbk_green');
            $this->assertSame(DocumentStatus::PrecheckPassed, $wbk->status);

            Livewire::actingAs($user)->test(Documents::class)
                ->set('type', 'wbk_green')->set('validUntil', '2020-01-01')
                ->set('file', UploadedFile::fake()->image('alt.png', 1400, 1000))->set('confirm', true)
                ->call('save')->assertHasErrors(['validUntil']);

            $this->actingAs($this->admin);
            Livewire::test(ViewCustomerDocument::class, ['record' => $wbk->getKey()])
                ->callAction('reject', ['reason' => 'Es fehlt die Seite mit dem Voreintrag.'])
                ->assertHasNoActionErrors();

            $this->assertSame(DocumentStatus::Rejected, $wbk->fresh()->status);
            $this->assertSame(0, $user->entitlements()->count());
            Notification::assertSentTo($user, DocumentReviewed::class, function (DocumentReviewed $n) use ($user) {
                $mail = $n->toMail($user);

                return $mail->subject === 'Ihr Dokument konnte nicht freigegeben werden'
                    && str_contains(implode(' ', $mail->introLines), 'Es fehlt die Seite mit dem Voreintrag.');
            });

            $this->fillCart($user, ['GLO-G17-G5' => 1]);
            Livewire::actingAs($user)->test(Checkout::class)->assertRedirect(route('cart'));
        }

        public function test_compliance_rejection_cancels_and_restores_stock_and_availability(): void
        {
            $user = $this->verifiedCustomer();
            $this->entitle($user);
            $sig = Product::where('sku', 'SIG-P320-X5')->first(); // letztes Stück
            $this->assertSame(1, $sig->stock_quantity);

            $this->fillCart($user, ['SIG-P320-X5' => 1]);
            $order = $this->checkout($user, 'pickup', 'cash_pickup', declaration: true);
            $this->assertSame(0, $sig->fresh()->stock_quantity);
            $this->assertSame('out_of_stock', $sig->fresh()->availability->value);

            $this->orderPage($order)
                ->callAction('rejectCompliance', ['reason' => 'Voreintrag gilt nicht für diese Waffe.'])
                ->assertHasNoActionErrors()
                ->assertActionHidden('cancel');

            $order->refresh();
            $this->assertSame(OrderStatus::Cancelled, $order->status);
            $this->assertSame(ComplianceStatus::Rejected, $order->compliance_status);
            $this->assertSame(1, $sig->fresh()->stock_quantity);
            $this->assertSame('in_stock', $sig->fresh()->availability->value);
            $this->assertSame(0, $user->entitlements()->where('type', 'wbk_voreintrag')->value('quantity_used'));

            // Doppelte Stornierung bucht den Bestand nicht zweimal zurück
            $this->expectException(\InvalidArgumentException::class);
            app(OrderService::class)->cancel($order, $this->admin, 'nochmal');
        }

        public function test_cancel_after_release_returns_the_voreintrag(): void
        {
            $user = $this->verifiedCustomer();
            $this->entitle($user);
            $this->fillCart($user, ['GLO-G17-G5' => 1]);
            $order = $this->checkout($user, 'weapon_personal_overnight', declaration: true);

            $this->orderPage($order)
                ->callAction('releaseCompliance', ['note' => 'geprüft'])->assertHasNoActionErrors()
                ->callAction('cancel', ['reason' => 'Zahlung nicht eingegangen.'])->assertHasNoActionErrors();

            $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
            $ent = $user->entitlements()->where('type', 'wbk_voreintrag')->first();
            $this->assertSame(0, $ent->quantity_used);
            $this->assertSame('active', $ent->status);

            // Kunde kann mit demselben Voreintrag erneut bestellen
            $this->fillCart($user, ['GLO-G17-G5' => 1]);
            $this->assertSame(OrderStatus::PendingCompliance, $this->checkout($user, 'weapon_personal_overnight', declaration: true)->status);
        }

        public function test_backend_login_returns_to_panel(): void
        {
            $this->get('/kms-login')->assertRedirect('/kms-login/login');
            $this->get('/kms-login/login')->assertRedirect(route('login'));
            $this->assertSame(url('/kms-login'), session('url.intended'));

            $this->post('/konto/anmelden', ['email' => 'admin@example.test', 'password' => 'Geheim-Passwort-123'])
                ->assertRedirect(url('/kms-login'));
            $this->get('/kms-login/login')->assertRedirect(url('/kms-login'));
        }
    }
}
