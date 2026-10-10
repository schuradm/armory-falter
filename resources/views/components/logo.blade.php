@props(['class' => 'h-10 w-auto', 'variant' => 'auto'])
{{-- Logo von www.armory-falter.de als Inline-SVG (kein extra Request). Schrift folgt der Textfarbe, das Rot dem Farbschema.
     variant="light" erzwingt weiße Schrift (z. B. im dunklen Footer). --}}
@php
    static $svg = null;
    $svg ??= str_replace(['fill="#ffffff"', 'fill="#c25a50"'], ['fill="currentColor"', 'fill="var(--af-logo-red)"'],
        preg_replace('/^<\?xml[^>]*>\s*/', '', file_get_contents(public_path('images/brand/armory-falter-logo-light.svg'))));
    $id = 'af-logo-'.\Illuminate\Support\Str::random(6);
@endphp
<span {{ $attributes->merge(['class' => 'inline-block '.($variant === 'light' ? 'text-white [--af-logo-red:#c25a50]' : 'text-fg')]) }}>
    {!! str_replace(['af-logo-title', '<svg '], [$id, '<svg class="'.$class.'" aria-labelledby="'.$id.'" '], preg_replace('/\saria-labelledby="[^"]*"/', '', $svg)) !!}
</span>
