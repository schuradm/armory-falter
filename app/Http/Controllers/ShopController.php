<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request, Seo $seo): View
    {
        $seo->title('Shop – Waffen, Optik, Munition & Zubehör')
            ->description('Alle Produkte von Armory Falter: Kurzwaffen, Langwaffen, Jagd- und Sportwaffen, Optiken, Munition, Messer, Zubehör, Wiederladen und Bekleidung.')
            ->breadcrumb('Shop', route('shop.index'));
        if ($request->query()) {
            $seo->noindex()->canonical(route('shop.index'));
        }

        return view('shop.listing', ['category' => null]);
    }

    public function category(string $path, Request $request, Seo $seo): View
    {
        $category = Category::where('path', $path)->where('is_active', true)->with('seo', 'children')->firstOrFail();

        $seo->title(($category->parent ? $category->name.' – '.$category->parent->name : $category->name).' kaufen')
            ->description($category->description ?: "{$category->name} online kaufen beim Fachhändler Armory Falter. Große Auswahl, kompetente Beratung, Versand an Berechtigte.")
            ->canonical($category->url());
        foreach ($category->ancestry() as $node) {
            $seo->breadcrumb($node->name, $node->url());
        }
        if ($request->except('seite')) {
            $seo->noindex();
        }
        $seo->applyOverrides($category->seo);

        return view('shop.listing', ['category' => $category]);
    }
}
