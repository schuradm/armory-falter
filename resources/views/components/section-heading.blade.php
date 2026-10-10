@props(['eyebrow' => null, 'title', 'link' => null, 'linkText' => 'Alle ansehen'])
<div {{ $attributes->merge(['class' => 'mb-8 flex items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)<p class="eyebrow">{{ $eyebrow }}</p>@endif
        <h2 class="mt-2 font-display text-2xl font-extrabold uppercase sm:text-3xl">{{ $title }}</h2>
        <div class="brand-rule mt-3"></div>
    </div>
    @if ($link)<a href="{{ $link }}" class="hidden items-center gap-1 font-ui text-sm text-accent sm:inline-flex">{{ $linkText }} <x-icon name="arrow-right" class="size-4"/></a>@endif
</div>
