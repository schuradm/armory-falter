{{-- 404 im Shop-Layout (Navigation, Suche, Footer). Läuft über Route::fallback in der web-Middleware. --}}
@php(app(\App\Support\Seo::class)->title('Seite nicht gefunden')->noindex())
<x-layouts.app>
    <div class="container-af grid min-h-[55vh] max-w-3xl place-items-center py-16 text-center">
        <div>
            <p class="eyebrow">Fehler 404</p>
            <h1 class="mt-3 font-display text-3xl font-extrabold uppercase sm:text-4xl">Diese Seite gibt es nicht (mehr)</h1>
            <div class="brand-rule mx-auto mt-4"></div>
            <p class="mt-6 text-muted">Der Link ist veraltet oder die Adresse wurde falsch eingegeben. Vielleicht finden Sie über die Suche, was Sie gesucht haben.</p>
            <form action="{{ route('search') }}" method="get" role="search" class="mx-auto mt-8 flex max-w-md gap-2">
                <label for="nf-search" class="sr-only">Suchbegriff</label>
                <input id="nf-search" name="q" type="search" class="input flex-1" placeholder="Hersteller, Modell, Kaliber …">
                <button type="submit" class="btn-primary"><x-icon name="search" class="size-4"/> Suchen</button>
            </form>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('shop.index') }}" class="btn-outline">Zum Shop</a>
                <a href="{{ route('home') }}" class="btn-outline">Zur Startseite</a>
                <a href="{{ route('contact') }}" class="btn-ghost">Kontakt aufnehmen</a>
            </div>
        </div>
    </div>
</x-layouts.app>
