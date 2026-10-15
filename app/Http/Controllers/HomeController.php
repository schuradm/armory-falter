<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Event;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Testimonial;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

/** Startseite nach AIDA: Attention (Hero) → Interest (Kategorien, Versprechen) → Desire (Produkte, Stimmen) → Action (CTA). */
class HomeController extends Controller
{
    public function __invoke(Seo $seo, Settings $settings)
    {
        $seo->title($settings->get('seo.home_title', 'Armory Falter – Waffenhändler bei Stuttgart für Jagd, Sport & Präzision'), false)
            ->description($settings->get('seo.home_description', 'Die erste Adresse für Jäger, Sportschützen, Sammler und Behörden: Jagd- und Sportwaffen, Optik, Munition, Werkstatt, Ankauf und Verkaufsservice. Persönliche Beratung in Wolfschlugen bei Stuttgart.'))
            ->schema(Seo::organization())
            ->schema(['@type' => 'WebSite', 'url' => url('/'), 'name' => 'Armory Falter', 'potentialAction' => [
                '@type' => 'SearchAction', 'target' => route('search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string',
            ]]);

        $with = ['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media', 'variants'];
        $data = Cache::remember('home.v2', 300, fn () => [
            'categories' => Category::whereNull('parent_id')->where('is_active', true)->where('show_on_home', true)->orderBy('sort_order')->get(),
            'brands' => Manufacturer::where('is_featured', true)->orderBy('sort_order')->get(),
            'featured' => Product::visible()->where('is_featured', true)->with($with)->latest('published_at')->take(8)->get(),
            'offers' => Product::visible()->whereNotNull('special_price_gross')->whereColumn('special_price_gross', '<', 'price_gross')
                ->where(fn ($q) => $q->whereNull('special_from')->orWhere('special_from', '<=', now()))->where(fn ($q) => $q->whereNull('special_to')->orWhere('special_to', '>', now()))->with($with)->take(4)->get(),
            'newest' => Product::visible()->with($with)->latest('published_at')->take(4)->get(),
            'testimonials' => Testimonial::where('is_published', true)->orderBy('sort_order')->take(6)->get(),
            'events' => Event::upcoming()->take(3)->get(),
            'posts' => BlogPost::published()->with('category', 'author')->latest('published_at')->take(3)->get(),
        ]);

        $home = $settings->home();

        // Hinweis: Eigene Kundenstimmen nicht als aggregateRating auszeichnen (Google: „self-serving reviews“).
        return view('home', $data + ['home' => $home, 'community' => array_filter($settings->get('community', []) + config('shop.community'))]);
    }
}
