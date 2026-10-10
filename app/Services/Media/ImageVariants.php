<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;

/**
 * Erzeugt WebP-Varianten in festen Breiten (GD, auf jedem Shared Hosting verfügbar).
 * Ablage neben dem Original: produkte/glock-17.jpg → produkte/glock-17-480.webp
 */
class ImageVariants
{
    public const WIDTHS = ['thumb' => 160, 'card' => 480, 'medium' => 800, 'large' => 1200, 'zoom' => 2000];

    public static function path(string $original, int $width): string
    {
        return preg_replace('/\.[a-z0-9]+$/i', '', $original)."-{$width}.webp";
    }

    /** @return array<int,int> erzeugte Breiten => Höhe */
    public function generate(string $path, string $disk = 'public', int $quality = 78): array
    {
        $fs = Storage::disk($disk);
        if (! function_exists('imagewebp') || ! $fs->exists($path)) {
            return [];
        }
        $source = @imagecreatefromstring($fs->get($path));
        if (! $source) {
            return [];
        }
        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);
        $w = imagesx($source);
        $h = imagesy($source);
        $done = [];

        foreach (self::WIDTHS as $target) {
            if ($target > $w && $done !== []) {
                break; // nicht hochskalieren; mindestens eine Variante erzeugen
            }
            $tw = min($target, $w);
            $th = (int) round($h * $tw / $w);
            $img = imagecreatetruecolor($tw, $th);
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagecopyresampled($img, $source, 0, 0, 0, 0, $tw, $th, $w, $h);
            ob_start();
            imagewebp($img, null, $quality);
            $fs->put(self::path($path, $target), ob_get_clean());
            imagedestroy($img);
            $done[$target] = $th;
        }
        imagedestroy($source);

        return $done;
    }
}
