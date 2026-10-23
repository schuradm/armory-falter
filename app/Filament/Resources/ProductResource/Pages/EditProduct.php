<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Concerns\SyncsProductRelations;
use App\Models\OrderItem;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use SyncsProductRelations;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->getRecord();
        $data['stock_reserved'] = $product->stock_reserved;

        return $this->fillProductExtras($data);
    }

    protected function afterSave(): void
    {
        $this->syncProductExtras();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('shop')->label('Im Shop ansehen')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn (Product $record) => route('product.show', $record))->openUrlInNewTab()
                ->visible(fn (Product $record) => $record->status === 'active'),
            Actions\DeleteAction::make()
                ->hidden(fn (Product $record) => OrderItem::where('product_id', $record->id)->exists()),
        ];
    }
}
