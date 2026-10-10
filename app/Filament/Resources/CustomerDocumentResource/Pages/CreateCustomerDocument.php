<?php

namespace App\Filament\Resources\CustomerDocumentResource\Pages;

use App\Filament\Resources\CustomerDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDocument extends CreateRecord
{
    protected static string $resource = CustomerDocumentResource::class;
}
