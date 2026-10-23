<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/** AJAX-Suche mit Vorschlägen (Produkte, Hersteller, Kategorien). */
class SearchAutocomplete extends Component
{
    public bool $mobile = false;

    public string $q = '';

    public function render(): View
    {
        $results = ['products' => collect(), 'brands' => collect(), 'categories' => collect()];
        $term = trim($this->q);

        if (mb_strlen($term) >= 2 && RateLimiter::attempt('ac:'.request()->ip(), 120, fn () => true)) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
            $results = [
                'products' => Product::visible(auth()->user())->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', $term))
                    ->with(['media', 'manufacturer'])->orderByDesc('is_featured')->take(6)->get(),
                'brands' => Manufacturer::where('name', 'like', $like)->take(3)->get(),
                'categories' => Category::where('is_active', true)->where('name', 'like', $like)->take(3)->get(),
            ];
        }

        return view('livewire.search-autocomplete', $results);
    }
}
