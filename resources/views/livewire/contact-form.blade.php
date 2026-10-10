<div>
    @if ($sent)
        <div class="card p-8 text-center"><x-icon name="mail" class="mx-auto size-10 text-accent"/><p class="mt-3 font-display text-xl font-bold">Vielen Dank für Ihre Nachricht</p><p class="mt-1 text-muted">Wir melden uns schnellstmöglich.</p></div>
    @else
        <form wire:submit="send" class="card grid gap-4 p-6 sm:grid-cols-2">
            <div><label class="label" for="c-name">Name *</label><input id="c-name" wire:model="name" class="input" autocomplete="name">@error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="c-mail">E-Mail *</label><input id="c-mail" type="email" wire:model="email" class="input" autocomplete="email">@error('email')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="c-phone">Telefon</label><input id="c-phone" wire:model="phone" class="input" autocomplete="tel"></div>
            <div><label class="label" for="c-sub">Betreff *</label><input id="c-sub" wire:model="subject" class="input">@error('subject')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label class="label" for="c-msg">Nachricht *</label><textarea id="c-msg" wire:model="message" rows="6" class="input"></textarea>@error('message')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" wire:model="privacy" class="mt-0.5 rounded border-line bg-surface-2 text-accent"> <span>Ich bin mit der Verarbeitung meiner Angaben zur Beantwortung einverstanden (<a href="{{ route('page.show', 'datenschutz') }}" class="text-accent underline">Datenschutz</a>).</span></label>
            @error('privacy')<p class="text-xs text-red-400 sm:col-span-2">{{ $message }}</p>@enderror
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="sm:col-span-2"><button class="btn-primary" wire:loading.attr="disabled">Nachricht senden</button></div>
        </form>
    @endif
</div>
