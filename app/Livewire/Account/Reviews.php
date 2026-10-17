<?php

namespace App\Livewire\Account;

use App\Models\Product;
use App\Services\Reviews\ReviewService;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Url;
use Livewire\Component;

/** „Meine Bewertungen“: abgegebene Bewertungen mit Status und Formular für gekaufte, zugestellte Artikel. */
class Reviews extends Component
{
    /** Vorauswahl aus der Bewertungseinladung (?produkt=slug). */
    #[Url(as: 'produkt')]
    public ?string $productSlug = null;

    public int $rating = 0;
    public string $title = '';
    public string $body = '';

    public function mount(Seo $seo): void
    {
        $seo->title('Meine Bewertungen')->noindex();
    }

    public function select(?string $slug): void
    {
        $this->productSlug = $slug;
        $this->reset('rating', 'title', 'body');
        $this->resetValidation();
    }

    public function submit(ReviewService $reviews): void
    {
        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:3000'],
        ], ['rating.between' => 'Bitte vergeben Sie 1 bis 5 Sterne.', 'rating.required' => 'Bitte vergeben Sie 1 bis 5 Sterne.']);

        $product = Product::where('slug', (string) $this->productSlug)->firstOrFail();
        try {
            $reviews->submit(Auth::user(), $product, ['rating' => $this->rating, 'title' => $this->title, 'body' => $this->body]);
        } catch (InvalidArgumentException $e) {
            $this->addError('rating', $e->getMessage());

            return;
        }
        $this->select(null);
        $this->dispatch('toast', message: 'Vielen Dank! Ihre Bewertung wird geprüft und dann veröffentlicht.');
    }

    public function render(ReviewService $reviews)
    {
        $user = Auth::user();
        $reviewable = $reviews->reviewableProducts($user);
        $selected = $this->productSlug ? $reviewable->firstWhere('slug', $this->productSlug) : null;

        return view('livewire.account.reviews', [
            'reviewable' => $reviewable,
            'selected' => $selected,
            'mine' => $user->reviews()->with('product:id,name,slug')->latest()->get(),
        ])->layout('components.layouts.account', ['active' => 'reviews']);
    }
}
