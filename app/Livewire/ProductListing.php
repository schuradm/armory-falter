<?php

namespace App\Livewire;

use App\Models\Caliber;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Produktliste mit Live-Filter (Hersteller, Kaliber, Preis, Verfügbarkeit, Zustand, Jagd/Sport). Filter liegen in der URL. */
class ProductListing extends Component
{
    use WithPagination;

    public ?int $categoryId = null;

    public ?int $manufacturerId = null;

    public ?int $caliberId = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'hersteller', except: [])]
    public array $brands = [];

    #[Url(as: 'kaliber', except: [])]
    public array $calibers = [];

    #[Url(as: 'preis_von', except: null)]
    public ?int $priceFrom = null;

    #[Url(as: 'preis_bis', except: null)]
    public ?int $priceTo = null;

    #[Url(as: 'verfuegbar', except: false)]
    public bool $inStock = false;

    #[Url(as: 'zustand', except: '')]
    public string $condition = '';

    #[Url(as: 'einsatz', except: '')]
    public string $usage = '';

    #[Url(as: 'sortierung', except: 'relevanz')]
    public string $sort = 'relevanz';

    public function updated(string $property): void
    {
        if ($property !== 'paginators') {
            $this->resetPage('seite');
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['brands', 'calibers', 'priceFrom', 'priceTo', 'inStock', 'condition', 'usage', 'sort']);
        $this->resetPage('seite');
    }

    protected function baseQuery(): Builder
    {
        $q = Product::query()->visible(auth()->user());

        if ($this->categoryId) {
            $ids = Category::find($this->categoryId)?->descendantIdsWithSelf() ?? [];
            $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids));
        }
        if ($this->manufacturerId) {
            $q->where('manufacturer_id', $this->manufacturerId);
        }
        if ($this->caliberId) {
            $q->where('caliber_id', $this->caliberId);
        }
        if ($this->search !== '') {
            $term = trim($this->search);
            $q->where(function ($w) use ($term) {
                if (\DB::getDriverName() === 'mysql' && mb_strlen($term) >= 3) {
                    $w->whereFullText(['name', 'short_description'], $term.'*', ['mode' => 'boolean']);
                } else {
                    $w->where('name', 'like', "%{$term}%");
                }
                $w->orWhere('sku', $term)->orWhere('ean', $term)
                    ->orWhereHas('manufacturer', fn ($m) => $m->where('name', 'like', "%{$term}%"));
            });
        }

        return $q;
    }

    protected function filteredQuery(): Builder
    {
        $q = $this->baseQuery();
        if ($this->brands) {
            $q->whereHas('manufacturer', fn ($m) => $m->whereIn('slug', $this->brands));
        }
        if ($this->calibers) {
            $q->whereHas('caliber', fn ($c) => $c->whereIn('slug', $this->calibers));
        }
        if ($this->priceFrom) {
            $q->where('price_gross', '>=', $this->priceFrom * 100);
        }
        if ($this->priceTo) {
            $q->where('price_gross', '<=', $this->priceTo * 100);
        }
        if ($this->inStock) {
            $q->whereIn('availability', ['in_stock', 'low_stock']);
        }
        if ($this->condition === 'new') {
            $q->where('condition_code', 'new');
        } elseif ($this->condition === 'used') {
            $q->where('condition_code', '!=', 'new');
        }
        if ($this->usage === 'jagd') {
            $q->where('usage_hunting', true);
        } elseif ($this->usage === 'sport') {
            $q->where('usage_sport', true);
        }

        return match ($this->sort) {
            'preis_auf' => $q->orderBy('price_gross'),
            'preis_ab' => $q->orderByDesc('price_gross'),
            'neu' => $q->orderByDesc('published_at'),
            'name' => $q->orderBy('name'),
            default => $q->orderByDesc('is_featured')->orderByRaw("availability IN ('in_stock','low_stock') DESC")->orderByDesc('published_at'),
        };
    }

    #[Computed]
    public function facets(): array
    {
        $ids = $this->baseQuery()->select('id');

        return [
            'brands' => Manufacturer::whereIn('id', (clone $this->baseQuery())->select('manufacturer_id'))
                ->withCount(['products' => fn ($q) => $q->whereIn('id', $ids)])->orderBy('name')->get(),
            'calibers' => Caliber::whereIn('id', (clone $this->baseQuery())->select('caliber_id'))
                ->withCount(['products' => fn ($q) => $q->whereIn('id', $ids)])->orderBy('name')->get(),
        ];
    }

    public function render(): View
    {
        $products = $this->filteredQuery()
            ->with(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media'])
            ->paginate(24, pageName: 'seite');

        return view('livewire.product-listing', [
            'products' => $products,
            'activeCount' => count($this->brands) + count($this->calibers) + ($this->priceFrom || $this->priceTo ? 1 : 0)
                + (int) $this->inStock + (int) ($this->condition !== '') + (int) ($this->usage !== ''),
        ]);
    }
}
