@php($c = config('shop.company'))
@php($days = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'])
<x-layouts.app>
    <div class="container-af py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <h1 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">Kontakt</h1>
        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_380px]">
            <livewire:contact-form/>
            <aside class="space-y-5">
                <div class="card p-6 text-sm">
                    <p class="font-display text-lg font-bold">{{ $c['name'] }}</p>
                    <p class="mt-2 text-muted">Inh. {{ $c['owner'] }}<br>{{ $c['street'] }}<br>{{ $c['postal_code'] }} {{ $c['city'] }}</p>
                    <p class="mt-4 flex items-center gap-2"><x-icon name="phone" class="size-4 text-accent"/><a href="tel:{{ preg_replace('/\s+/', '', $c['phone']) }}">{{ $c['phone'] }}</a></p>
                    <p class="mt-1 flex items-center gap-2"><x-icon name="mail" class="size-4 text-accent"/><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></p>
                    @if ($c['social']['whatsapp'])<p class="mt-1 flex items-center gap-2"><x-icon name="whatsapp" class="size-4 text-accent"/><a href="https://wa.me/{{ $c['social']['whatsapp'] }}" rel="noopener" target="_blank">WhatsApp</a></p>@endif
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($c['street'].', '.$c['postal_code'].' '.$c['city']) }}" target="_blank" rel="noopener" class="btn-outline mt-5 w-full py-2"><x-icon name="map-pin" class="size-4"/> Route planen</a>
                </div>
                <div class="card p-6 text-sm"><p class="label">Öffnungszeiten</p>
                    <dl class="mt-2 grid grid-cols-2 gap-y-1">@foreach ($days as $d => $name)<dt class="text-muted">{{ $name }}</dt><dd>{{ isset($c['opening_hours'][$d]) ? implode(' – ', $c['opening_hours'][$d]).' Uhr' : 'geschlossen' }}</dd>@endforeach</dl>
                    <p class="mt-3 text-xs text-muted">Beratung gerne mit Termin: <a href="{{ route('appointments.consultation') }}" class="text-accent">jetzt buchen</a></p>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.app>
