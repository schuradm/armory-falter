{{-- Responsives Produktbild: WebP-srcset, feste Abmessungen gegen Layout-Shift (CLS), lazy außer bei priority. --}}
@props(['media', 'size' => 'card', 'sizes' => '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw', 'priority' => false, 'alt' => null])
<img src="{{ $media->url($size) }}"
     @if ($srcset = $media->srcset()) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
     alt="{{ $alt ?? $media->alt_text }}"
     width="{{ $media->width ?? 800 }}" height="{{ $media->height ?? 600 }}"
     @if ($priority) fetchpriority="high" loading="eager" @else loading="lazy" @endif
     decoding="async"
     {{ $attributes }}>
