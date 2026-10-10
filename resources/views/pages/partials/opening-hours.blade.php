@php($c = config('shop.company'))
@php($days = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'])
<div class="card p-6 text-sm">
    <h2 class="label">Öffnungszeiten</h2>
    <dl class="mt-2 grid grid-cols-2 gap-y-1">@foreach ($days as $d => $name)<dt class="text-muted">{{ $name }}</dt><dd>{{ isset($c['opening_hours'][$d]) ? implode(' – ', $c['opening_hours'][$d]).' Uhr' : 'geschlossen' }}</dd>@endforeach</dl>
    <p class="mt-4"><a href="tel:{{ preg_replace('/\s+/', '', $c['phone']) }}" class="flex items-center gap-2 text-accent"><x-icon name="phone" class="size-4"/>{{ $c['phone'] }}</a></p>
</div>
