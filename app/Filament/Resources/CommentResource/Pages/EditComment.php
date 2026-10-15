<?php

namespace App\Filament\Resources\CommentResource\Pages;

use App\Filament\Resources\CommentResource;
use App\Http\Middleware\PageCache;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComment extends EditRecord
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->after(fn () => PageCache::flush())];
    }

    protected function afterSave(): void
    {
        PageCache::flush();
    }
}
