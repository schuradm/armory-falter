<div id="anfrage">
    @if ($reference)
        <div class="card p-8 text-center">
            <x-icon name="check" class="mx-auto size-10 text-accent"/>
            <h3 class="mt-4 font-display text-2xl font-bold">Anfrage {{ $reference }} ist eingegangen</h3>
            <p class="mt-2 text-muted">Wir melden uns in der Regel innerhalb von zwei Werktagen mit einer Einschätzung. Eine Bestätigung haben wir an {{ $email }} gesendet.</p>
            <p class="mt-4 text-sm text-amber-400">Bitte senden Sie uns keine Waffe ohne Absprache zu.</p>
        </div>
    @else
        <form wire:submit="submit" class="card grid gap-5 p-6 sm:grid-cols-2 sm:p-8" novalidate>
            <h3 class="font-display text-xl font-bold sm:col-span-2">1. Ihre Kontaktdaten</h3>
            <div class="sm:col-span-2"><label class="label" for="tr-name">Name *</label><input id="tr-name" wire:model="name" class="input" autocomplete="name">@error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label class="label" for="tr-street">Anschrift (Straße, Nr.){{ $type === 'consignment' ? ' *' : '' }}</label><input id="tr-street" wire:model="street" class="input" autocomplete="street-address">@error('street')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="tr-pc">PLZ{{ $type === 'consignment' ? ' *' : '' }}</label><input id="tr-pc" wire:model="postal_code" class="input" inputmode="numeric">@error('postal_code')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="tr-city">Ort{{ $type === 'consignment' ? ' *' : '' }}</label><input id="tr-city" wire:model="city" class="input">@error('city')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="tr-phone">Telefon{{ $type === 'consignment' ? ' *' : '' }}</label><input id="tr-phone" wire:model="phone" class="input" type="tel">@error('phone')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="tr-email">E-Mail *</label><input id="tr-email" wire:model="email" class="input" type="email">@error('email')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>

            <h3 class="mt-4 font-display text-xl font-bold sm:col-span-2">2. Angaben zur Waffe</h3>
            @if ($type === 'consignment')
                <div class="sm:col-span-2"><label class="label" for="tr-wt">Waffenart *</label>
                    <select id="tr-wt" wire:model="weapon_type" class="input"><option value="">Bitte wählen</option>@foreach (['Kurzwaffe (Pistole)', 'Kurzwaffe (Revolver)', 'Repetierbüchse', 'Selbstladebüchse', 'Flinte', 'Kombinierte Waffe', 'Luftdruckwaffe', 'Optik', 'Sonstiges'] as $wt)<option>{{ $wt }}</option>@endforeach</select>
                    @error('weapon_type')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            @endif
            <div><label class="label" for="tr-man">Hersteller *</label>
                <select id="tr-man" wire:model.live="manufacturer_id" class="input"><option value="">Anderer Hersteller …</option>@foreach ($manufacturers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
            <div>@if (! $manufacturer_id)<label class="label" for="tr-mann">Herstellername</label><input id="tr-mann" wire:model="manufacturer_name" class="input">@error('manufacturer_name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror @endif</div>
            <div><label class="label" for="tr-model">Modell *</label><input id="tr-model" wire:model="model" class="input">@error('model')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="tr-cal">Kaliber{{ $type === 'buyback' ? ' *' : '' }}</label><input id="tr-cal" wire:model="caliber" class="input" placeholder="z. B. .308 Win.">@error('caliber')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="tr-cond">Zustand *</label><select id="tr-cond" wire:model="condition_code" class="input">@foreach ($conditions as $c)<option value="{{ $c->value }}">{{ $c->label() }}</option>@endforeach</select></div>
            <div><label class="label" for="tr-price">Wunschpreis (€)</label><input id="tr-price" wire:model="desired_price" class="input" inputmode="decimal" placeholder="optional">@error('desired_price')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label class="label" for="tr-desc">Beschreibung, Zubehör, Besonderheiten</label><textarea id="tr-desc" wire:model="description" rows="4" class="input" placeholder="Laufzustand, Schussleistung, Optik, Originalkarton, Belege …"></textarea></div>

            <h3 class="mt-4 font-display text-xl font-bold sm:col-span-2">3. Fotos (bis zu {{ config('shop.trade_requests.max_photos') }}){{ $type === 'consignment' ? ' *' : '' }}</h3>
            <div class="sm:col-span-2">
                <label for="tr-photos" class="flex cursor-pointer flex-col items-center gap-2 rounded-card border-2 border-dashed border-line p-6 text-center text-sm hover:border-accent">
                    <x-icon name="upload" class="size-8 text-accent"/>Fotos auswählen (JPG, PNG, WebP, je max. 8 MB)
                    <span class="text-xs text-muted">Gesamtansicht beidseitig, Lauf/Mündung, Verschluss, Beschuss- und Herstellerzeichen, Schaft, Optik</span>
                    <input id="tr-photos" type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
                <div wire:loading wire:target="photos" class="mt-2 text-xs text-muted">Fotos werden hochgeladen …</div>
                @error('photos')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                @error('photos.*')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                @if ($photos)
                    <ul class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-6">
                        @foreach ($photos as $i => $photo)
                            <li class="relative aspect-square overflow-hidden rounded bg-surface-2" wire:key="ph-{{ $i }}">
                                @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())<img src="{{ $photo->temporaryUrl() }}" alt="Foto {{ $i + 1 }}" class="size-full object-cover">@endif
                                <button type="button" wire:click="removePhoto({{ $i }})" class="absolute right-1 top-1 grid size-6 place-items-center rounded-full bg-black/70" aria-label="Foto entfernen"><x-icon name="x" class="size-3.5"/></button>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-muted">{{ count($photos) }} von {{ config('shop.trade_requests.max_photos') }} Fotos</p>
                @endif
            </div>

            <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" wire:model="privacy" class="mt-0.5 rounded border-line bg-surface-2 text-accent">
                <span>Ich bin mit der Verarbeitung meiner Angaben und Fotos zur Bearbeitung der Anfrage einverstanden. Für die Beratung erstellen wir daraus ein PDF. Details in der <a href="{{ route('page.show', 'datenschutz') }}" class="text-accent underline" target="_blank">Datenschutzerklärung</a>.</span></label>
            @error('privacy')<p class="text-xs text-red-400 sm:col-span-2">{{ $message }}</p>@enderror
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            @if (config('shop.captcha.driver') === 'turnstile' && config('shop.captcha.turnstile_site_key'))
                <div class="sm:col-span-2" wire:ignore><div class="cf-turnstile" data-sitekey="{{ config('shop.captcha.turnstile_site_key') }}" data-callback="afTurnstile" data-theme="dark"></div></div>
                <script>window.afTurnstile = (t) => @this.set('captcha', t);</script>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif
            @error('captcha')<p class="text-xs text-red-400 sm:col-span-2">{{ $message }}</p>@enderror
            <div class="sm:col-span-2"><button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="submit,photos">{{ $type === 'consignment' ? 'Waffe zum Verkauf anmelden' : 'Ankaufanfrage senden' }}</button></div>
        </form>
    @endif
</div>
