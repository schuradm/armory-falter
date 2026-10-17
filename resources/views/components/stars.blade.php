@props(['rating' => 0, 'count' => null, 'size' => 'size-4'])
@php
    // Sterne als Inline-SVG ohne IDs (beliebig oft je Seite): graue Grundreihe, darüber die gefüllte Reihe mit Breite = Anteil.
    $rating = max(0, min(5, (float) $rating));
    $value = rtrim(rtrim(number_format($rating, 1, ',', ''), '0'), ',');
    $label = "Bewertet mit {$value} von 5 Sternen".($count !== null ? ", {$count} ".($count === 1 ? 'Bewertung' : 'Bewertungen') : '');
    $star = '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>';
    $row = fn (string $cls) => '<span class="flex shrink-0 '.$cls.'">'.str_repeat('<svg viewBox="0 0 24 24" class="'.$size.' shrink-0" fill="currentColor" aria-hidden="true">'.$star.'</svg>', 5).'</span>';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }} role="img" aria-label="{{ $label }}">
    <span class="relative inline-flex" aria-hidden="true">
        {!! $row('text-muted/40') !!}
        <span class="absolute inset-y-0 left-0 overflow-hidden" style="width: {{ round($rating / 5 * 100, 1) }}%">{!! $row('text-accent') !!}</span>
    </span>
    @if ($count !== null)<span class="text-xs text-muted" aria-hidden="true">({{ $count }})</span>@endif
</span>
