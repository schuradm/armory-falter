<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Weitere Bewertungen als HTML-Fragment für „Mehr anzeigen“ auf der Produktseite (serverseitig gerendert,
     * per fetch angehängt). Gäste bekommen das Fragment aus dem Seiten-Cache.
     */
    public function index(Product $product, Request $request)
    {
        abort_unless($product->status === 'active' && ! $product->authority_only, 404);
        $page = max(2, (int) $request->query('seite', 2));
        $reviews = $product->reviews()->approved()->latest()->paginate(config('shop.reviews.per_page', 5), page: $page);

        return response()
            ->view('shop.partials.review-items', ['reviews' => $reviews, 'product' => $product])
            ->header('X-Robots-Tag', 'noindex');
    }

    /** Widerspruch gegen Bewertungseinladungen per signiertem Link (GET aus der Mail, POST für One-Click). */
    public function optOut(User $user)
    {
        if (! $user->review_requests_opted_out_at) {
            $user->forceFill(['review_requests_opted_out_at' => now()])->save();
        }
        if (request()->isMethod('POST')) {
            return response()->noContent();
        }

        return view('pages.message', ['title' => 'Abgemeldet', 'message' => 'Sie erhalten von uns keine Bewertungseinladungen mehr. Im Kundenkonto unter „Datenschutz“ können Sie das jederzeit ändern.']);
    }
}
