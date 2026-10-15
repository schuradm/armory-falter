<?php

namespace App\Filament\Resources\ProductResource\Concerns;

use App\Models\Product;

/**
 * Speichert die Teile des Produktformulars, die keine einfache Relation sind:
 * Merkmale (Pivot mit Werten), Zubehör (product_relations, type=accessory, sortiert)
 * und die Hauptkategorie (Pivot is_primary).
 */
trait SyncsProductRelations
{
    protected function fillProductExtras(array $data): array
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $product->loadMissing(['attributes', 'accessories', 'categories']);

        $data['attribute_values'] = $product->attributes->map(fn ($a) => [
            'attribute_definition_id' => $a->id,
            'value_text' => $a->pivot->value_text,
            'value_number' => $a->pivot->value_number,
        ])->values()->all();
        $data['accessory_ids'] = $product->accessories->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        $data['primary_category_id'] = $product->categories->firstWhere('pivot.is_primary', true)?->id;

        return $data;
    }

    protected function syncProductExtras(): void
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $state = $this->form->getRawState();

        $attributes = [];
        foreach ($state['attribute_values'] ?? [] as $row) {
            if (empty($row['attribute_definition_id'])) {
                continue;
            }
            $attributes[(int) $row['attribute_definition_id']] = [
                'value_text' => filled($row['value_text'] ?? null) ? $row['value_text'] : null,
                'value_number' => filled($row['value_number'] ?? null) ? $row['value_number'] : null,
            ];
        }
        $product->attributes()->sync($attributes);

        $accessories = [];
        $sort = 0;
        foreach ($state['accessory_ids'] ?? [] as $row) {
            $id = is_array($row) ? ($row['related_product_id'] ?? null) : $row;
            if ($id && (int) $id !== $product->id && ! isset($accessories[(int) $id])) {
                $accessories[(int) $id] = ['type' => 'accessory', 'sort_order' => $sort++];
            }
        }
        $product->accessories()->sync($accessories);

        $categoryIds = array_map('intval', $product->categories()->pluck('categories.id')->all());
        $primary = (int) ($state['primary_category_id'] ?? 0);
        if (! in_array($primary, $categoryIds, true)) {
            $primary = $categoryIds[0] ?? 0;
        }
        foreach ($categoryIds as $id) {
            $product->categories()->updateExistingPivot($id, ['is_primary' => $id === $primary]);
        }
    }
}
