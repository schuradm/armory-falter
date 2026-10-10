<div>
    @if ($result)
        <div class="card border-emerald-500/40 p-5 text-sm" role="status">
            <p class="flex items-center gap-2 font-medium"><x-icon name="check" class="size-5 text-emerald-400"/> {{ $result === 'registered' ? 'Sie sind angemeldet!' : 'Sie stehen auf der Warteliste.' }}</p>
            <p class="mt-1 text-muted">Wir melden uns per E-Mail mit allen Details.</p>
        </div>
    @else
        <form wire:submit="register" class="space-y-4">
            <div><label class="label" for="ev-name">Name *</label><input id="ev-name" wire:model="name" class="input" autocomplete="name" required>@error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="ev-email">E-Mail *</label><input id="ev-email" type="email" wire:model="email" class="input" autocomplete="email" required>@error('email')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label" for="ev-phone">Telefon</label><input id="ev-phone" type="tel" wire:model="phone" class="input" autocomplete="tel"></div>
                <div><label class="label" for="ev-persons">Personen</label><select id="ev-persons" wire:model="persons" class="input">@for ($i = 1; $i <= 6; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
            </div>
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label class="flex items-start gap-2 text-xs text-muted"><input type="checkbox" wire:model="privacy" class="mt-0.5 rounded border-line bg-surface-2 text-brand-600"> <span>Ich habe die <a href="{{ route('page.show', 'datenschutz') }}" class="text-accent underline">Datenschutzerklärung</a> gelesen. *</span></label>
            @error('privacy')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            <button type="submit" class="btn-primary fx-shine w-full" wire:loading.attr="disabled">{{ $event->seatsLeft() === 0 ? 'Auf die Warteliste' : 'Verbindlich anmelden' }}</button>
        </form>
    @endif
</div>
