<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Seo;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, Seo $seo)
    {
        $q = trim((string) $request->query('q'));
        $seo->title($q ? "Suche: {$q}" : 'Suche')->noindex();

        return view('shop.search', ['q' => $q]);
    }

    public function compare(Request $request, Seo $seo)
    {
        $seo->title('Produktvergleich')->noindex();
        $ids = collect(explode(',', (string) $request->query('ids')))->map(fn ($v) => (int) $v)->filter()->take(4);
        $products = Product::visible(auth()->user())->whereIn('id', $ids)
            ->with(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media', 'attributes'])->get();
        $attributes = $products->flatMap->attributes->unique('id')->sortBy('sort_order');

        return view('shop.compare', compact('products', 'attributes'));
    }
}
