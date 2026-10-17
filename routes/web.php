<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CaliberController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\FallbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShopActionController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Shop
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{path}', [ShopController::class, 'category'])->where('path', '[a-z0-9\-/]+')->name('shop.category');
Route::get('/produkt/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/produkt/{product:slug}/bewertungen', [\App\Http\Controllers\ReviewController::class, 'index'])->name('product.reviews');
Route::get('/hersteller', [BrandController::class, 'index'])->name('brands.index');
Route::get('/hersteller/{manufacturer:slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/kaliber/{caliber:slug}', [CaliberController::class, 'show'])->name('calibers.show');
Route::get('/suche', [SearchController::class, 'index'])->middleware('throttle:search')->name('search');
Route::get('/vergleich', [SearchController::class, 'compare'])->name('compare');
Route::get('/warenkorb', Livewire\CartPage::class)->name('cart');
Route::post('/warenkorb/artikel', [ShopActionController::class, 'addToCart'])->middleware('throttle:60,1')->name('cart.quick-add');
Route::middleware('auth')->group(function () {
    Route::get('/merkliste/ids', [ShopActionController::class, 'wishlistIds'])->name('wishlist.ids');
    Route::post('/merkliste/{product}', [ShopActionController::class, 'wishlistToggle'])->middleware('throttle:60,1')->name('wishlist.toggle');
});

// Services
Route::get('/verkaufsservice', [ServiceController::class, 'consignment'])->name('trade.consignment');
Route::get('/waffen-ankauf', [ServiceController::class, 'buyback'])->name('trade.buyback');
Route::get('/werkstatt', [ServiceController::class, 'workshop'])->name('workshop.index');
Route::get('/werkstatt/termin', fn (\App\Support\Seo $seo) => app(ServiceController::class)->booking($seo, 'workshop'))->name('appointments.workshop');
Route::get('/werkstatt/{service:slug}', [ServiceController::class, 'workshopService'])->name('workshop.show');
Route::get('/beratung', fn (\App\Support\Seo $seo) => app(ServiceController::class)->booking($seo, 'consultation'))->name('appointments.consultation');

// Marketing: Events, Club, regionale Seiten
Route::get('/events', [MarketingController::class, 'events'])->name('events.index');
Route::get('/events/{event:slug}', [MarketingController::class, 'event'])->name('events.show');
Route::get('/elite', [MarketingController::class, 'elite'])->name('elite');
Route::get('/waffenhaendler/{page:slug}', [MarketingController::class, 'local'])->name('local.show');

// Inhalte
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/kategorie/{category:slug}', [BlogController::class, 'index'])->name('blog.category');
Route::get('/blog/autor/{author:slug}', [BlogController::class, 'author'])->name('blog.author');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/kontakt', [PageController::class, 'contact'])->name('contact');
Route::get('/{slug}', [PageController::class, 'show'])->whereIn('slug', PageController::SLUGS)->name('page.show');

// Widerrufsbutton (Art. 11a Verbraucherrechte-RL, seit 19.06.2026): Angaben → „Widerruf bestätigen“ → Eingangsbestätigung
Route::get('/vertrag-widerrufen', [\App\Http\Controllers\WithdrawalController::class, 'create'])->name('withdrawal.create');
Route::post('/vertrag-widerrufen/pruefen', [\App\Http\Controllers\WithdrawalController::class, 'review'])->middleware('throttle:20,1')->name('withdrawal.review');
Route::post('/vertrag-widerrufen', [\App\Http\Controllers\WithdrawalController::class, 'store'])->middleware('throttle:10,1')->name('withdrawal.store');
Route::get('/vertrag-widerrufen/eingegangen', [\App\Http\Controllers\WithdrawalController::class, 'done'])->name('withdrawal.done');

// Newsletter, Consent, SEO
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:20,1')->name('newsletter.subscribe');
Route::get('/newsletter/bestaetigen/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/abmelden/{subscriber}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->name('newsletter.unsubscribe');
Route::post('/newsletter/abmelden/{subscriber}', [NewsletterController::class, 'unsubscribe'])->middleware('signed')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
Route::get('/newsletter/ratgeber/{subscriber}', [NewsletterController::class, 'ratgeber'])->middleware('signed')->name('newsletter.ratgeber');
Route::get('/bewertungen/abbestellen/{user}', [\App\Http\Controllers\ReviewController::class, 'optOut'])->middleware('signed')->name('reviews.opt-out');
Route::post('/bewertungen/abbestellen/{user}', [\App\Http\Controllers\ReviewController::class, 'optOut'])->middleware('signed')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
Route::get('/newsletter/o/{campaign}/{subscriber}', [NewsletterController::class, 'open'])->middleware('signed')->name('newsletter.open');
Route::post('/consent', [ConsentController::class, 'store'])->middleware('throttle:30,1')->name('consent.store');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots']);
// Scheduler-Auslösung für Hosting ohne Cronjob (Token aus CRON_TOKEN, siehe CronController). Ohne Session/CSRF.
Route::get('/cron/{token}', \App\Http\Controllers\CronController::class)
    ->where('token', '[A-Za-z0-9_\-]{16,128}')
    ->middleware('throttle:10,1')
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, \App\Http\Middleware\CachePublicPages::class])
    ->name('cron');
Route::get('/downloads/{document}', [AccountController::class, 'productDocument'])->name('product-document.download');

// Checkout & Konto
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/kasse', Livewire\Checkout::class)->name('checkout');
    Route::get('/kasse/danke/{order:order_number}', Livewire\CheckoutSuccess::class)->name('checkout.success');
    Route::get('/zahlung/{order:order_number}', [PaymentController::class, 'start'])->name('payment.start');
    Route::get('/zahlung/{order:order_number}/paypal', [PaymentController::class, 'paypalReturn'])->name('payment.paypal.return');
    Route::get('/zahlung/{order:order_number}/klarna', [PaymentController::class, 'klarnaReturn'])->name('payment.klarna.return');
});

// Zahlungs-Webhooks (ohne Session/CSRF, Authentizität wird im Controller geprüft)
Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->middleware('throttle:120,1')->group(function () {
    Route::post('/webhooks/paypal', [PaymentController::class, 'paypalWebhook'])->name('payment.paypal.webhook');
    Route::post('/webhooks/klarna/{order:order_number}', [PaymentController::class, 'klarnaWebhook'])->name('payment.klarna.webhook');
});

Route::middleware(['auth'])->prefix('konto')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/bestellungen', [AccountController::class, 'orders'])->name('orders');
    Route::get('/bestellungen/{order:order_number}', [AccountController::class, 'order'])->name('orders.show');
    Route::get('/rechnungen', [AccountController::class, 'invoices'])->name('invoices');
    Route::get('/rechnungen/{invoice}', [AccountController::class, 'invoice'])->name('invoices.download');
    Route::get('/downloads', [AccountController::class, 'downloads'])->name('downloads');
    Route::get('/dokumente', Livewire\Account\Documents::class)->name('documents');
    Route::get('/merklisten', Livewire\Account\Wishlists::class)->name('wishlists');
    Route::get('/bewertungen', Livewire\Account\Reviews::class)->name('reviews');
    Route::get('/termine', [AccountController::class, 'appointments'])->name('appointments');
    Route::get('/anfragen', [AccountController::class, 'requests'])->name('requests');
    Route::get('/daten', [AccountController::class, 'profile'])->name('profile');
    Route::get('/sicherheit', [AccountController::class, 'security'])->name('security');
    Route::get('/datenschutz', [AccountController::class, 'privacy'])->name('privacy');
    Route::post('/datenschutz/bewertungseinladungen', [AccountController::class, 'reviewInvites'])->name('privacy.review-invites');
    Route::get('/datenschutz/export', [AccountController::class, 'export'])->middleware('password.confirm')->name('privacy.export');
    Route::delete('/datenschutz/konto', [AccountController::class, 'destroy'])->name('privacy.destroy');
});

Route::fallback(FallbackController::class);
