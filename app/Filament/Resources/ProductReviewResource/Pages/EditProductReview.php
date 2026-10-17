<?php

namespace App\Filament\Resources\ProductReviewResource\Pages;

use App\Filament\Resources\ProductReviewResource;
use App\Models\ProductReview;
use App\Services\Reviews\ReviewService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProductReview extends EditRecord
{
    protected static string $resource = ProductReviewResource::class;

    public function getTitle(): string
    {
        return 'Bewertung: '.$this->getRecord()->product?->name;
    }

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load(['product', 'user', 'orderItem.order']);
    }

    /** Gespeichert wird nur die Antwort (über den Service: Antwortender, Zeitpunkt, Seiten-Cache). */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ProductReview $record */
        app(ReviewService::class)->reply($record, auth()->user(), $data['reply'] ?? null);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')->label('Veröffentlichen')->icon('heroicon-o-check')->color('success')
                ->visible(fn (ProductReview $record) => $record->status !== 'approved')
                ->action(fn (ProductReview $record) => app(ReviewService::class)->approve($record, auth()->user())),
            Actions\Action::make('reject')->label('Ablehnen')->icon('heroicon-o-x-mark')->color('danger')
                ->visible(fn (ProductReview $record) => $record->status !== 'rejected')
                ->form([Forms\Components\Select::make('reason')->label('Grund')->required()
                    ->options(array_combine(ProductReviewResource::REJECT_REASONS, ProductReviewResource::REJECT_REASONS))])
                ->action(fn (array $data, ProductReview $record) => app(ReviewService::class)->reject($record, auth()->user(), $data['reason'])),
            Actions\DeleteAction::make()->using(function (ProductReview $record): bool {
                app(ReviewService::class)->delete($record);

                return true;
            }),
        ];
    }
}
