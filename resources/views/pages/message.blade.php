<x-layouts.app>
    <div class="container-af grid min-h-[50vh] max-w-2xl place-items-center py-16 text-center">
        <div>
            <h1 class="font-display text-3xl font-extrabold uppercase">{{ $title }}</h1>
            <p class="mt-4 text-muted">{{ $message }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @if (! empty($action))<a href="{{ $action['url'] }}" class="btn-primary fx-shine" data-no-prerender><x-icon name="download" class="size-4"/> {{ $action['label'] }}</a>@endif
                <a href="{{ route('home') }}" class="{{ empty($action) ? 'btn-primary' : 'btn-outline' }}">Zur Startseite</a>
            </div>
        </div>
    </div>
</x-layouts.app>
