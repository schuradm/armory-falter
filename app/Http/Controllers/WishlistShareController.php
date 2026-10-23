<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class WishlistShareController extends Controller
{
    /** Geteilte Merkliste (Link aus dem Kundenkonto), nur lesbar und ohne Angaben zur Person. */
    public function show(string $token, Seo $seo): View
    {
        abort_unless(strlen($token) === 32, 404);
        $list = Wishlist::where('share_token', $token)->firstOrFail();
        $seo->title('Merkliste: '.$list->name)->noindex();
        $products = $list->products()->visible(auth()->user())
            ->with(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media', 'variants'])->get();

        return view('shop.shared-wishlist', compact('list', 'products'));
    }
}
