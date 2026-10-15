<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Concerns\SyncsProductRelations;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use SyncsProductRelations;

    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $this->syncProductExtras();
    }
}
