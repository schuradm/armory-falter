<?php

namespace App\Jobs;

use App\Models\ProductMedia;
use App\Services\Media\ImageVariants;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateImageVariants implements ShouldQueue
{
    use Queueable;

    public int $timeout = 50; // passt in das Cron-Fenster von queue:work --max-time=50

    public function __construct(public int $mediaId) {}

    public function handle(ImageVariants $variants): void
    {
        $media = ProductMedia::find($this->mediaId);
        if (! $media || $media->type !== 'image' || str_starts_with($media->path, 'http')) {
            return;
        }
        $done = $variants->generate($media->path);
        $size = @getimagesize(\Storage::disk('public')->path($media->path));
        $media->forceFill([
            'variant_widths' => array_keys($done) ?: null,
            'width' => $size[0] ?? $media->width,
            'height' => $size[1] ?? $media->height,
        ])->saveQuietly();
    }
}
