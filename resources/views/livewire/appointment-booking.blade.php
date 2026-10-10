<div>
    @if ($booked)
        <div class="card p-8 text-center"><x-icon name="calendar" class="mx-auto size-10 text-accent"/>
            <h2 class="mt-4 font-display text-2xl font-bold">Terminanfrage gesendet</h2>
            <p class="mt-2 text-muted">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d. F Y') }} um {{ $time }} Uhr. Wir bestätigen den Termin per E-Mail.</p>
            @if ($type === 'workshop')<p class="mt-4 text-sm text-amber-400">Bitte bringen Sie die Waffe ungeladen im verschlossenen Behältnis und Ihre WBK mit.</p>@endif
        </div>
    @else
        <form wire:submit="book" class="grid gap-8 lg:grid-cols-2">
            <div class="space-y-6">
                @if ($type === 'workshop')
                    <fieldset><legend class="label">1. Leistung</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($services as $s)
                                <label class="cursor-pointer"><input type="radio" wire:model="serviceId" value="{{ $s->id }}" class="peer sr-only">
                                    <span class="card block p-4 text-sm peer-checked:border-accent"><span class="font-medium">{{ $s->name }}</span>@if($s->price_from_gross)<span class="block text-xs text-muted">ab {{ \App\Support\Money::format($s->price_from_gross) }}</span>@endif</span></label>
                            @endforeach
                        </div>
                        @error('serviceId')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </fieldset>
                @endif
                <fieldset><legend class="label">{{ $type === 'workshop' ? '2.' : '1.' }} Tag</legend>
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-5">
                        @foreach (array_slice(array_keys($slots), 0, 15) as $d)
                            @php($c = \Carbon\Carbon::parse($d))
                            <label class="cursor-pointer"><input type="radio" wire:model.live="date" value="{{ $d }}" class="peer sr-only">
                                <span class="block rounded-md border border-line p-2 text-center text-xs peer-checked:border-accent peer-checked:text-accent"><span class="block text-muted">{{ $c->translatedFormat('D') }}</span><span class="font-display text-base font-bold">{{ $c->format('d.') }}</span>{{ $c->translatedFormat('M') }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
                @if ($date)
                    <fieldset><legend class="label">Uhrzeit</legend>
                        <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                            @foreach ($times as $t)
                                <label class="cursor-pointer"><input type="radio" wire:model="time" value="{{ $t }}" class="peer sr-only"><span class="block rounded-md border border-line py-2 text-center text-sm peer-checked:border-accent peer-checked:text-accent">{{ $t }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                @error('time')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="card space-y-4 p-6">
                <p class="label">Ihre Daten</p>
                <input wire:model="name" class="input" placeholder="Name *" aria-label="Name" autocomplete="name">@error('name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                <input wire:model="email" type="email" class="input" placeholder="E-Mail *" aria-label="E-Mail" autocomplete="email">@error('email')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                <input wire:model="phone" type="tel" class="input" placeholder="Telefon *" aria-label="Telefon" autocomplete="tel">@error('phone')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                @if ($type === 'workshop')<input wire:model="weapon" class="input" placeholder="Waffe (Hersteller, Modell, Kaliber)" aria-label="Waffe">@endif
                <textarea wire:model="notes" rows="3" class="input" placeholder="{{ $type === 'workshop' ? 'Was soll gemacht werden?' : 'Worum geht es? (z. B. erste Kurzwaffe, Drückjagd-Optik)' }}" aria-label="Anmerkungen"></textarea>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="privacy" class="mt-0.5 rounded border-line bg-surface-2 text-accent"> <span>Ich bin mit der Verarbeitung meiner Daten zur Terminvereinbarung einverstanden.</span></label>
                @error('privacy')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                <button class="btn-primary w-full" wire:loading.attr="disabled">Termin anfragen</button>
            </div>
        </form>
    @endif
</div>
