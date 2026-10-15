@props(['class' => 'h-10 w-auto', 'variant' => 'auto'])
{{-- Logo von www.armory-falter.de als Inline-SVG (kein extra Request). Schrift folgt der Textfarbe, das Rot dem Farbschema.
     variant="light" erzwingt weiße Schrift (z. B. im dunklen Footer).
     HTML-Größe: Pfadkoordinaten auf 1 Nachkommastelle gerundet; die Pfade stehen nur beim ersten Logo der Seite im HTML,
     weitere Logos (Mobilmenü, Footer) verweisen per <use> darauf. --}}
@php
    static $inner = null;
    $inner ??= (function () {
        $svg = file_get_contents(public_path('images/brand/armory-falter-logo-light.svg'));
        $svg = preg_replace(['/^<\?xml[^>]*>\s*/', '/<svg[^>]*>/', '/<\/svg>\s*$/', '/<title[^>]*>.*?<\/title>/s'], '', $svg);
        $svg = preg_replace_callback('/\d+\.\d+/', fn ($m) => rtrim(rtrim(number_format(round((float) $m[0], 1), 1, '.', ''), '0'), '.'), $svg);

        return trim(str_replace(['fill="#ffffff"', 'fill="#c25a50"'], ['fill="currentColor"', 'fill="var(--af-logo-red)"'], $svg));
    })();
    $first = ! request()->attributes->get('af-logo-rendered');
    request()->attributes->set('af-logo-rendered', true);
@endphp
<span {{ $attributes->merge(['class' => 'inline-block '.($variant === 'light' ? 'text-white [--af-logo-red:#c25a50]' : 'text-fg')]) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 930 470" class="{{ $class }}" role="img" aria-label="Armory Falter">@if ($first)<g id="af-logo-g">{!! $inner !!}</g>@else<use href="#af-logo-g"/>@endif</svg>
</span>
