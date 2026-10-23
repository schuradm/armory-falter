<?php

namespace App\Providers;

use App\Http\Middleware\PageCache;
use App\Models\Appointment;
use App\Models\BlogPost;
use App\Models\Caliber;
use App\Models\Category;
use App\Models\CustomerDocument;
use App\Models\CustomerEntitlement;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GiftVoucher;
use App\Models\LocalPage;
use App\Models\Manufacturer;
use App\Models\NewsletterCampaign;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Redirect;
use App\Models\Testimonial;
use App\Models\TradeRequest;
use App\Models\User;
use App\Models\WeaponItem;
use App\Models\Withdrawal;
use App\Models\WorkshopService;
use App\Support\Seo;
use App\Support\Settings;
use App\View\Composers\NavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Seo::class);
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        // Vorgeschalteter Proxy (Traefik, Cloudflare): echte Client-IP aus X-Forwarded-For, siehe config/app.php
        $proxies = (string) config('app.trusted_proxies');
        TrustProxies::at($proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies)))));

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'user' => User::class,
            'withdrawal' => Withdrawal::class,
            'product' => Product::class,
            'category' => Category::class,
            'manufacturer' => Manufacturer::class,
            'caliber' => Caliber::class,
            'order' => Order::class,
            'customer_document' => CustomerDocument::class,
            'customer_entitlement' => CustomerEntitlement::class,
            'trade_request' => TradeRequest::class,
            'appointment' => Appointment::class,
            'blog_post' => BlogPost::class,
            'page' => Page::class,
            'weapon_item' => WeaponItem::class,
            'newsletter_campaign' => NewsletterCampaign::class,
            'event' => Event::class,
            'local_page' => LocalPage::class,
            'product_review' => ProductReview::class,
            'discount_code' => DiscountCode::class,
            'gift_voucher' => GiftVoucher::class,
        ]);

        Password::defaults(fn () => Password::min(10)->letters()->mixedCase()->numbers()->uncompromised());

        Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);

        View::composer(['partials.header', 'partials.mobile-nav', 'partials.footer'], NavigationComposer::class);

        // OrderPlaced → SendOrderConfirmation registriert Laravel per Event-Discovery (app/Listeners).
        // Kein zusätzliches Event::listen, sonst gehen Bestellbestätigung und Staff-Mail doppelt raus.

        // Ganzseiten-Cache leeren, sobald sich öffentlich sichtbare Inhalte ändern
        foreach ([Product::class, ProductVariant::class, ProductMedia::class, Category::class,
            Manufacturer::class, Caliber::class, Page::class, BlogPost::class,
            Faq::class, WorkshopService::class, Redirect::class, Testimonial::class, Event::class, LocalPage::class] as $model) {
            $model::saved(fn () => PageCache::flush());
            $model::deleted(fn () => PageCache::flush());
        }

        RateLimiter::for('forms', fn (Request $r) => [Limit::perMinute(5)->by($r->ip()), Limit::perDay(50)->by($r->ip())]);
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perHour(30)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
    }
}
