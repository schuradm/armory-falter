<div class="mt-4">
    @if ($done)
        <p class="flex items-start gap-2 text-white/80"><x-icon name="mail" class="mt-0.5 size-4 text-brand-400"/> Fast geschafft: Bitte bestätigen Sie die Anmeldung über den Link in der E-Mail.</p>
    @else
        <form wire:submit="subscribe" class="space-y-3">
            <div class="flex gap-2">
                <label for="nl-email" class="sr-only">E-Mail-Adresse</label>
                <input id="nl-email" type="email" wire:model="email" placeholder="E-Mail-Adresse" required class="block w-full rounded-md border-white/15 bg-white/5 text-sm text-white placeholder:text-white/55 focus:border-brand-400 focus:ring-brand-400">
                <button type="submit" class="btn-primary px-4" wire:loading.attr="disabled" aria-label="Anmelden"><x-icon name="arrow-right" class="size-4"/></button>
            </div>
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-white/60">
                @foreach (['jagd' => 'Jagd', 'sport' => 'Sport', 'optik' => 'Optik', 'sammler' => 'Sammler'] as $k => $v)
                    <label class="flex items-center gap-1.5"><input type="checkbox" wire:model="interests" value="{{ $k }}" class="rounded border-white/20 bg-white/5 text-brand-400 focus:ring-brand-400">{{ $v }}</label>
                @endforeach
            </div>
            <label class="flex items-start gap-2 text-xs text-white/50"><input type="checkbox" wire:model="consent" class="mt-0.5 rounded border-white/20 bg-white/5 text-brand-400 focus:ring-brand-400">
                <span>Ich möchte den Newsletter erhalten und willige in die Auswertung von Öffnungen und Klicks ein. Abmeldung jederzeit. <a href="{{ route('page.show', 'datenschutz') }}" class="underline">Datenschutz</a></span></label>
            @error('email')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            @error('consent')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
        </form>
    @endif
</div>
