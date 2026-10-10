@props(['items' => []])
<nav aria-label="Brotkrumen" class="text-xs text-muted">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ route('home') }}" class="hover:text-fg">Startseite</a></li>
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5"><x-icon name="chevron-right" class="size-3"/>
                @if (! $loop->last)<a href="{{ $item['url'] }}" class="hover:text-fg">{{ $item['name'] }}</a>@else<span class="text-fg" aria-current="page">{{ $item['name'] }}</span>@endif
            </li>
        @endforeach
    </ol>
</nav>
