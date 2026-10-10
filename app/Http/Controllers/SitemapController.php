<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Caliber;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Page;
use App\Models\Product;
use App\Models\WorkshopService;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = collect([
                ['loc' => route('home'), 'priority' => '1.0'],
                ['loc' => route('shop.index')], ['loc' => route('brands.index')], ['loc' => route('trade.consignment')],
                ['loc' => route('trade.buyback')], ['loc' => route('workshop.index')], ['loc' => route('blog.index')],
                ['loc' => route('faq')], ['loc' => route('contact')],
            ]);
            Category::where('is_active', true)->get()->each(fn ($c) => $urls->push(['loc' => $c->url(), 'lastmod' => $c->updated_at]));
            Product::visible()->select('slug', 'updated_at')->chunk(500, fn ($ps) => $ps->each(fn ($p) => $urls->push(['loc' => route('product.show', $p), 'lastmod' => $p->updated_at])));
            Manufacturer::all()->each(fn ($m) => $urls->push(['loc' => route('brands.show', $m), 'lastmod' => $m->updated_at]));
            Caliber::whereHas('products')->get()->each(fn ($c) => $urls->push(['loc' => route('calibers.show', $c)]));
            WorkshopService::all()->each(fn ($s) => $urls->push(['loc' => route('workshop.show', $s)]));
            BlogPost::published()->get()->each(fn ($p) => $urls->push(['loc' => route('blog.show', $p), 'lastmod' => $p->updated_at]));
            Page::where('status', 'published')->get()->each(fn ($p) => $urls->push(['loc' => route('page.show', $p->slug)]));

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = app()->isProduction() && ! config('app.noindex')
            ? ['User-agent: *', 'Disallow: /konto', 'Disallow: /kasse', 'Disallow: /warenkorb', 'Disallow: /admin', 'Disallow: /suche', 'Disallow: /vergleich', 'Disallow: /*?*sortierung=', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
