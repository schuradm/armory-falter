<?php

namespace App\Filament\Resources\CustomerDocumentResource\Pages;

use App\Filament\Resources\CustomerDocumentResource;
use App\Models\CustomerDocument;
use App\Services\AuditLogger;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerDocument extends ViewRecord
{
    protected static string $resource = CustomerDocumentResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        // Einsicht in Ausweisdaten wird protokolliert
        app(AuditLogger::class)->log('document.viewed', $this->getRecord());
    }

    protected function resolveRecord(int|string $key): CustomerDocument
    {
        /** @var CustomerDocument $doc */
        $doc = parent::resolveRecord($key);

        return $doc->load(['user', 'reviewer', 'entitlements.caliber']);
    }

    public function getTitle(): string
    {
        /** @var CustomerDocument $doc */
        $doc = $this->getRecord();

        return $doc->type->label().' – '.$doc->user?->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            CustomerDocumentResource::approveAction(Actions\Action::class)
                ->after(fn () => $this->record = $this->resolveRecord($this->getRecord()->getKey())),
            CustomerDocumentResource::rejectAction(Actions\Action::class)
                ->after(fn () => $this->record = $this->resolveRecord($this->getRecord()->getKey())),
            CustomerDocumentResource::downloadAction(Actions\Action::class),
            Actions\EditAction::make()->label('Angaben korrigieren')->color('gray'),
        ];
    }
}
