<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getTabs(): array
    {
        return [
            'new' => Tab::make('Neu')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new')),
            'handled' => Tab::make('Erledigt')->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'handled')),
            'all' => Tab::make('Alle'),
        ];
    }
}
