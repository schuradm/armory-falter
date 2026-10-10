<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Compliance\ComplianceService;
use App\Support\Seo;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function show(Product $product, Seo $seo, ComplianceService $compliance)
    {
        abort_unless($product->status === 'active' && (! $product->authority_only || auth()->user()?->isAuthorityOrDealer()), 404);

        $product->load(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media', 'downloads', 'attributes', 'categories.parent', 'variants', 'faqs', 'seo', 'accessories.media', 'accessories.legalClass', 'accessories.taxRate']);
        $primary = $product->primaryCategory();
        $image = $product->primaryImage();

        $seo->title(Str::limit($product->name, 50, ''))
            ->description($product->short_description ?: "{$product->name} von {$product->manufacturer?->name} bei Armory Falter kaufen.")
            ->canonical($product->url())
            ->type('product')
            ->image($image?->url());
        if ($primary) {
            foreach ($primary->ancestry() as $node) {
                $seo->breadcrumb($node->name, $node->url());
            }
        }
        $seo->breadcrumb($product->name, $product->url());

        $seo->schema(array_filter([
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'gtin13' => $product->ean,
            'mpn' => $product->manufacturer_part_number,
            'description' => Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 500),
            'image' => $product->media->where('type', 'image')->map->url()->values()->all() ?: null,
            'brand' => $product->manufacturer ? ['@type' => 'Brand', 'name' => $product->manufacturer->name] : null,
            'itemCondition' => $product->condition_code->schemaOrg(),
            'offers' => [
                '@type' => 'Offer',
                'url' => $product->url(),
                'priceCurrency' => 'EUR',
                'price' => number_format($product->currentPrice() / 100, 2, '.', ''),
                'availability' => $product->availability->schemaOrg(),
                'itemCondition' => $product->condition_code->schemaOrg(),
                'seller' => ['@id' => url('/').'#organization'],
                'eligibleRegion' => 'DE',
            ],
        ]));
        $seo->applyOverrides($product->seo);

        $related = Product::visible()->where('id', '!=', $product->id)
            ->where(fn ($q) => $q->where('manufacturer_id', $product->manufacturer_id)->orWhere('caliber_id', $product->caliber_id))
            ->with(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media', 'variants'])->take(4)->get();

        $check = $compliance->evaluate($product, auth()->user());

        return view('shop.product', compact('product', 'related', 'check'));
    }
}
