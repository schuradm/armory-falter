<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Segment-Regeln (JSON): {"interests": ["jagd","sport"], "has_ordered": true, "bought_product_type": "optic"}
 */
class NewsletterSegment extends Model
{
    protected $fillable = ['name', 'rules'];

    protected $casts = ['rules' => 'array'];

    public function subscribersQuery(): Builder
    {
        $rules = $this->rules ?? [];
        $q = NewsletterSubscriber::query()->where('status', 'confirmed');

        if (! empty($rules['interests'])) {
            $q->where(function ($w) use ($rules) {
                foreach ($rules['interests'] as $interest) {
                    $w->orWhereJsonContains('interests', $interest);
                }
            });
        }
        if (! empty($rules['has_ordered'])) {
            $q->whereIn('user_id', Order::query()->select('user_id'));
        }
        if (! empty($rules['bought_product_type'])) {
            $q->whereIn('user_id', Order::query()->select('orders.user_id')
                ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('products.product_type', $rules['bought_product_type']));
        }

        return $q;
    }
}
