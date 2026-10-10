<x-layouts.app>
    <div class="container-af py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <div class="mt-6 grid gap-12 lg:grid-cols-[1fr_380px]">
            <article>
                <p class="eyebrow">{{ $event->starts_at->translatedFormat('l, d. F Y · H:i') }} Uhr</p>
                <h1 class="mt-3 font-display text-3xl font-extrabold uppercase sm:text-5xl">{{ $event->title }}</h1>
                <div class="brand-rule mt-4"></div>
                @if ($event->image_path)<img src="{{ Storage::disk('public')->url($event->image_path) }}" alt="" class="card mt-8 aspect-video w-full object-cover" width="1200" height="675" fetchpriority="high">@endif
                <p class="mt-6 text-lg text-muted">{{ $event->teaser }}</p>
                <div class="prose-af mt-6">{!! $event->description !!}</div>
            </article>
            <aside>
                <div class="card sticky top-28 space-y-4 p-6">
                    <dl class="space-y-2 text-sm">
                        <div class="flex gap-2"><dt><x-icon name="calendar" class="size-4 text-accent"/><span class="sr-only">Datum</span></dt><dd>{{ $event->starts_at->format('d.m.Y, H:i') }} Uhr @if ($event->ends_at)– {{ $event->ends_at->format('H:i') }} Uhr @endif</dd></div>
                        <div class="flex gap-2"><dt><x-icon name="map-pin" class="size-4 text-accent"/><span class="sr-only">Ort</span></dt><dd>{{ $event->location ?: config('shop.company.city') }}</dd></div>
                        <div class="flex gap-2"><dt><x-icon name="euro" class="size-4 text-accent"/><span class="sr-only">Preis</span></dt><dd>{{ $event->price_gross ? \App\Support\Money::format($event->price_gross).' pro Person' : 'kostenlos' }}</dd></div>
                    </dl>
                    @if ($event->requires_entitlement)<p class="rounded-md bg-surface-2 p-3 text-xs text-muted">Teilnahme am Schießen nur mit gültiger Erlaubnis (WBK/Jagdschein) und Ausweis.</p>@endif
                    <livewire:event-registration-form :event="$event"/>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.app>
