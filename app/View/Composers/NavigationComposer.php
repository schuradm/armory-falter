<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class NavigationComposer
{
    public function __construct(private CartService $cart) {}

    public function compose(View $view): void
    {
        $nav = Cache::remember('nav.v1', 600, fn () => [
            'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')
                ->with('children')->get(['id', 'name', 'slug', 'path', 'icon', 'parent_id']),
            'brands' => Manufacturer::where('is_featured', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'logo_path']),
        ]);

        $view->with('navCategories', $nav['categories'])
            ->with('navBrands', $nav['brands'])
            ->with('cartCount', $this->cart->count());
    }
}
