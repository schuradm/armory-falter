<div>
    @if ($sent)
        <p class="card p-4 text-sm">Danke! Ihr Kommentar erscheint nach Prüfung durch die Redaktion.</p>
    @endif
    <form wire:submit="submit" class="card mt-4 grid gap-3 p-5">
        <p class="font-display font-bold">Kommentar schreiben</p>
        <input wire:model="author" class="input" placeholder="Name (wird angezeigt) *" aria-label="Name">@error('author')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
        <textarea wire:model="body" rows="4" class="input" placeholder="Ihr Kommentar *" aria-label="Kommentar"></textarea>@error('body')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
        <label class="flex items-start gap-2 text-xs text-muted"><input type="checkbox" wire:model="privacy" class="mt-0.5 rounded border-line bg-surface-2 text-accent"> Ich habe die Datenschutzerklärung gelesen. Mein Name und Kommentar werden nach Freigabe öffentlich angezeigt.</label>
        @error('privacy')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
        <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
        <button class="btn-primary justify-self-start" wire:loading.attr="disabled">Absenden</button>
    </form>
</div>
