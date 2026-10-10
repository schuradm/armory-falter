<?php

namespace App\Models;

use App\Jobs\GenerateImageVariants;
use App\Services\Media\ImageVariants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMedia extends Model
{
    protected $fillable = ['product_id', 'type', 'path', 'alt_text', 'width', 'height', 'sort_order'];

    protected $casts = ['variant_widths' => 'array'];

    protected static function booted(): void
    {
        static::saved(function (self $media) {
            if ($media->wasChanged('path') || $media->wasRecentlyCreated) {
                GenerateImageVariants::dispatch($media->id);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isExternal(): bool
    {
        return Str::startsWith($this->path, ['http://', 'https://', '/']);
    }

    /** URL des Originals oder (falls erzeugt) der passenden WebP-Variante: thumb, card, medium, large, zoom. */
    public function url(?string $size = null): string
    {
        if ($this->isExternal()) {
            return $this->path;
        }
        $widths = $this->variant_widths ?? [];
        if ($size && $widths) {
            $wanted = ImageVariants::WIDTHS[$size] ?? 800;
            $best = collect($widths)->sort()->first(fn ($w) => $w >= $wanted) ?? max($widths);

            return Storage::disk('public')->url(ImageVariants::path($this->path, $best));
        }

        return Storage::disk('public')->url($this->path);
    }

    public function srcset(): ?string
    {
        if ($this->isExternal() || empty($this->variant_widths)) {
            return null;
        }

        return collect($this->variant_widths)->sort()
            ->map(fn ($w) => Storage::disk('public')->url(ImageVariants::path($this->path, $w))." {$w}w")->join(', ');
    }
}
