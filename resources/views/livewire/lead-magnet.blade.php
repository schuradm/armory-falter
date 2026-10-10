<div>
    @if ($done)
        <p class="flex items-start gap-2 rounded-md bg-white/5 p-4 text-sm text-white/85" role="status"><x-icon name="mail" class="mt-0.5 size-5 shrink-0 text-brand-400"/> Fast geschafft! Bitte bestätigen Sie Ihre E-Mail-Adresse. Direkt danach erhalten Sie den Download-Link.</p>
    @else
        <form wire:submit="subscribe" class="space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row">
                <label for="lead-email" class="sr-only">E-Mail-Adresse</label>
                <input id="lead-email" type="email" wire:model="email" placeholder="Ihre E-Mail-Adresse" required autocomplete="email" class="block w-full rounded-md border-white/15 bg-white/5 py-3.5 text-white placeholder:text-white/55 focus:border-brand-400 focus:ring-brand-400">
                <button type="submit" class="btn-primary fx-shine shrink-0 px-6" wire:loading.attr="disabled"><x-icon name="download" class="size-4"/> Gratis anfordern</button>
            </div>
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label class="flex items-start gap-2 text-xs text-white/65"><input type="checkbox" wire:model="consent" class="mt-0.5 rounded border-white/20 bg-white/5 text-brand-600 focus:ring-brand-400">
                <span>Ja, ich möchte den Ratgeber und den Newsletter von Armory Falter mit Angeboten, Neuheiten und Fachartikeln per E-Mail erhalten. Abmeldung jederzeit. <a href="{{ route('page.show', 'datenschutz') }}" class="underline">Datenschutz</a></span></label>
            @error('email')<p class="text-xs text-red-300">{{ $message }}</p>@enderror
            @error('consent')<p class="text-xs text-red-300">{{ $message }}</p>@enderror
        </form>
    @endif
</div>
