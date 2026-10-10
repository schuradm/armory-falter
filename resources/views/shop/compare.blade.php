<x-layouts.app>
    <div class="container-af py-10">
        <h1 class="font-display text-3xl font-extrabold uppercase">Produktvergleich</h1>
        @if ($products->isEmpty())
            <p class="card mt-8 p-8 text-center text-muted">Noch keine Artikel ausgewählt. Nutzen Sie das Vergleichs-Symbol auf den Produktkarten (bis zu 4 Artikel).</p>
        @else
            <div class="card mt-8 overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead><tr><th class="w-44 p-4"></th>@foreach ($products as $p)<th class="p-4 text-left align-top"><a href="{{ $p->url() }}" class="font-display font-bold hover:text-accent">{{ $p->name }}</a><x-price :product="$p" class="mt-2"/></th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-line">
                        <tr><th class="p-4 text-left font-normal text-muted">Hersteller</th>@foreach ($products as $p)<td class="p-4">{{ $p->manufacturer?->name }}</td>@endforeach</tr>
                        <tr><th class="p-4 text-left font-normal text-muted">Kaliber</th>@foreach ($products as $p)<td class="p-4">{{ $p->caliber?->name ?? '–' }}</td>@endforeach</tr>
                        <tr><th class="p-4 text-left font-normal text-muted">Zustand</th>@foreach ($products as $p)<td class="p-4">{{ $p->condition_code->label() }}</td>@endforeach</tr>
                        <tr><th class="p-4 text-left font-normal text-muted">Verfügbarkeit</th>@foreach ($products as $p)<td class="p-4 {{ $p->availability->color() }}">{{ $p->availability->label() }}</td>@endforeach</tr>
                        <tr><th class="p-4 text-left font-normal text-muted">Erwerb</th>@foreach ($products as $p)<td class="p-4">{{ $p->legalClass->name }}</td>@endforeach</tr>
                        @foreach ($attributes as $a)
                            <tr><th class="p-4 text-left font-normal text-muted">{{ $a->name }}</th>@foreach ($products as $p)@php($v = $p->attributes->firstWhere('id', $a->id))<td class="p-4">{{ $v ? ($v->pivot->value_text ?? rtrim(rtrim((string) $v->pivot->value_number, '0'), '.')).' '.$a->unit : '–' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
