<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Leichtgewichtige JSON-Aktionen für Produktkarten (Schnellkauf, Merkliste).
 * Ersetzt je Karte eine eigene Livewire-Komponente: Listing-Seiten bleiben dadurch klein und schnell.
 */
class ShopActionController extends Controller
{
    public function addToCart(Request $request, CartService $cart): JsonResponse
    {
        $data = $request->validate(['product_id' => 'required|integer', 'quantity' => 'nullable|integer|min:1|max:99']);
        $product = Product::visible($request->user())->with('variants')->findOrFail($data['product_id']);

        if ($product->variants->count() > 1) {
            return response()->json(['redirect' => $product->url(), 'message' => 'Bitte Ausführung wählen.'], 409);
        }
        try {
            $cart->add($product, $data['quantity'] ?? 1, $product->variants->first()?->id);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['count' => $cart->count(), 'product_id' => $product->id, 'message' => "„{$product->name}“ liegt im Warenkorb."]);
    }

    public function wishlistIds(Request $request): JsonResponse
    {
        $ids = $request->user()->wishlists()->with('products:id')->get()->flatMap->products->pluck('id')->unique()->values();

        return response()->json(['ids' => $ids]);
    }

    public function wishlistToggle(Request $request, Product $product): JsonResponse
    {
        $result = $request->user()->defaultWishlist()->products()->toggle([$product->id]);
        $active = ! empty($result['attached']);

        return response()->json(['active' => $active, 'message' => $active ? 'Zur Merkliste hinzugefügt.' : 'Von der Merkliste entfernt.']);
    }
}
