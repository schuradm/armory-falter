<?php

namespace App\Filament\Resources\LocalPageResource\Pages;

use App\Filament\Resources\LocalPageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLocalPages extends ListRecords
{
    protected static string $resource = LocalPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
