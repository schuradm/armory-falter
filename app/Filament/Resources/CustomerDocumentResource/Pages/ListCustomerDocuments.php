<?php

namespace App\Filament\Resources\CustomerDocumentResource\Pages;

use App\Filament\Resources\CustomerDocumentResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDocuments extends ListRecords
{
    protected static string $resource = CustomerDocumentResource::class;
}
