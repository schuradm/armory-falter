<?php

namespace App\Providers;

use App\Http\Middleware\PageCache;
use App\Support\Seo;
use App\View\Composers\NavigationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $this->app->singleton(\App\Support\Settings::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'product' => \App\Models\Product::class,
            'category' => \App\Models\Category::class,
            'manufacturer' => \App\Models\Manufacturer::class,
            'caliber' => \App\Models\Caliber::class,
            'order' => \App\Models\Order::class,
            'customer_document' => \App\Models\CustomerDocument::class,
            'customer_entitlement' => \App\Models\CustomerEntitlement::class,
            'trade_request' => \App\Models\TradeRequest::class,
            'appointment' => \App\Models\Appointment::class,
            'blog_post' => \App\Models\BlogPost::class,
            'page' => \App\Models\Page::class,
            'weapon_item' => \App\Models\WeaponItem::class,
            'newsletter_campaign' => \App\Models\NewsletterCampaign::class,
            'event' => \App\Models\Event::class,
            'local_page' => \App\Models\LocalPage::class,
        ]);

        Password::defaults(fn () => Password::min(10)->letters()->mixedCase()->numbers()->uncompromised());

        Gate::before(fn ($user) => $user->hasRole('super-admin') ? true : null);

        View::composer(['partials.header', 'partials.mobile-nav', 'partials.footer'], NavigationComposer::class);

        // OrderPlaced → SendOrderConfirmation registriert Laravel per Event-Discovery (app/Listeners).
        // Kein zusätzliches Event::listen, sonst gehen Bestellbestätigung und Staff-Mail doppelt raus.

        // Ganzseiten-Cache leeren, sobald sich öffentlich sichtbare Inhalte ändern
        foreach ([\App\Models\Product::class, \App\Models\ProductVariant::class, \App\Models\ProductMedia::class, \App\Models\Category::class,
            \App\Models\Manufacturer::class, \App\Models\Caliber::class, \App\Models\Page::class, \App\Models\BlogPost::class,
            \App\Models\Faq::class, \App\Models\WorkshopService::class, \App\Models\Redirect::class, \App\Models\Testimonial::class, \App\Models\Event::class, \App\Models\LocalPage::class] as $model) {
            $model::saved(fn () => PageCache::flush());
            $model::deleted(fn () => PageCache::flush());
        }

        RateLimiter::for('forms', fn (Request $r) => [Limit::perMinute(5)->by($r->ip()), Limit::perDay(50)->by($r->ip())]);
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perHour(30)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
    }
}
