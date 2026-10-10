<div class="container-af py-10">
    <h1 class="font-display text-3xl font-extrabold uppercase">Kasse</h1>
    <ol class="mt-6 grid grid-cols-4 gap-2 font-ui text-xs" aria-label="Fortschritt">
        @foreach ([1 => 'Adresse', 2 => 'Versand', 3 => 'Zahlung', 4 => 'Prüfen'] as $n => $label)
            <li @class(['border-t-2 pt-2', 'border-accent text-fg' => $step >= $n, 'border-line text-muted' => $step < $n]) @if($step === $n) aria-current="step" @endif>{{ $n }}. {{ $label }}</li>
        @endforeach
    </ol>

    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_380px]">
        <div class="space-y-6">
            @error('order')<div class="card flex gap-3 border-red-500/50 p-4 text-sm" role="alert"><x-icon name="alert" class="size-5 text-red-400"/>{{ $message }}</div>@enderror

            @if ($step === 1)
                <section class="card p-6">
                    <h2 class="font-display text-xl font-bold">Rechnungsadresse</h2>
                    <p class="mt-1 text-sm text-muted">Bei erlaubnispflichtiger Ware muss die Adresse mit der Anschrift in Ihrer Erlaubnis übereinstimmen.</p>
                    <div class="mt-5"><x-address-fields prefix="billing"/></div>
                    <label class="mt-5 flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="differentShipping" class="rounded border-line bg-surface-2 text-accent"> Abweichende Lieferadresse</label>
                    @if ($differentShipping)
                        <div class="mt-5 border-t border-line pt-5"><h3 class="mb-4 font-display font-bold">Lieferadresse</h3><x-address-fields prefix="shipping"/>
                            @if ($evaluation->shippingMode->value === 'weapon_personal')<p class="mt-3 text-xs text-amber-400">Erlaubnispflichtige Artikel liefern wir nur an die Wohnanschrift des Erwerbers mit persönlicher Übergabe.</p>@endif
                        </div>
                    @endif
                    <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="saveAddress" class="rounded border-line bg-surface-2 text-accent"> Adressen im Kundenkonto speichern</label>
                    <button type="button" wire:click="toStep(2)" class="btn-primary mt-6">Weiter zum Versand</button>
                </section>
            @elseif ($step === 2)
                <section class="card p-6">
                    <h2 class="font-display text-xl font-bold">Versandart</h2>
                    <p class="mt-1 text-sm text-muted">Für Ihren Warenkorb gilt: <span class="text-fg">{{ $evaluation->shippingMode->label() }}</span>.</p>
                    <div class="mt-5 space-y-3">
                        @forelse ($shippingMethods as $m)
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border p-4 {{ $shippingMethod === $m->code ? 'border-accent' : 'border-line' }}">
                                <input type="radio" wire:model.live="shippingMethod" value="{{ $m->code }}" class="mt-1 border-line bg-surface-2 text-accent">
                                <span class="flex-1"><span class="font-medium">{{ $m->name }}</span><span class="block text-xs text-muted">{{ $m->description }}</span></span>
                                <span class="font-display font-bold">{{ $m->priceFor($subtotal) === 0 ? 'kostenlos' : \App\Support\Money::format($m->priceFor($subtotal)) }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-red-400">Für diesen Warenkorb ist derzeit keine Versandart verfügbar. Bitte kontaktieren Sie uns.</p>
                        @endforelse
                    </div>
                    @error('shippingMethod')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                    <div class="mt-6 flex gap-3"><button type="button" wire:click="toStep(1)" class="btn-outline">Zurück</button><button type="button" wire:click="toStep(3)" class="btn-primary">Weiter zur Zahlung</button></div>
                </section>
            @elseif ($step === 3)
                <section class="card p-6">
                    <h2 class="font-display text-xl font-bold">Zahlungsart</h2>
                    @if (count($payments) < count(\App\Enums\PaymentMethod::cases()))
                        <p class="mt-1 text-sm text-muted">Für Waffen und Munition sind nur bestimmte Zahlungsarten möglich, weil viele Zahlungsanbieter diese Waren ausschließen.</p>
                    @endif
                    <div class="mt-5 space-y-3">
                        @foreach ($payments as $pm)
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border p-4 {{ $paymentMethod === $pm->value ? 'border-accent' : 'border-line' }}">
                                <input type="radio" wire:model.live="paymentMethod" value="{{ $pm->value }}" class="mt-1 border-line bg-surface-2 text-accent">
                                <span><span class="font-medium">{{ $pm->label() }}</span><span class="block text-xs text-muted">{{ $pm->description() }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('paymentMethod')<p class="mt-2 text-xs text-red-400">{{ $message }}</p>@enderror
                    <div class="mt-6 flex gap-3"><button type="button" wire:click="toStep(2)" class="btn-outline">Zurück</button><button type="button" wire:click="toStep(4)" class="btn-primary">Weiter zur Übersicht</button></div>
                </section>
            @else
                <section class="card space-y-5 p-6">
                    <h2 class="font-display text-xl font-bold">Bestellung prüfen</h2>
                    <div class="grid gap-4 text-sm sm:grid-cols-3">
                        <div><p class="label">Rechnungsadresse</p><p>{{ $billing['first_name'] }} {{ $billing['last_name'] }}<br>{{ $billing['street'] }} {{ $billing['house_number'] }}<br>{{ $billing['postal_code'] }} {{ $billing['city'] }}</p><button type="button" class="mt-1 text-xs text-accent" wire:click="toStep(1)">Ändern</button></div>
                        <div><p class="label">Versand</p><p>{{ $selected?->name }}</p><button type="button" class="mt-1 text-xs text-accent" wire:click="toStep(2)">Ändern</button></div>
                        <div><p class="label">Zahlung</p><p>{{ \App\Enums\PaymentMethod::tryFrom((string) $paymentMethod)?->label() }}</p><button type="button" class="mt-1 text-xs text-accent" wire:click="toStep(3)">Ändern</button></div>
                    </div>
                    <div><label for="note" class="label">Anmerkung (optional)</label><textarea id="note" wire:model="note" rows="2" maxlength="1000" class="input"></textarea></div>

                    @if ($evaluation->requiresReview)
                        <div class="rounded-md border border-amber-500/40 p-4 text-sm">
                            <p class="flex items-center gap-2 font-medium"><x-icon name="shield" class="size-5 text-amber-400"/> Erlaubnispflichtige Artikel</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-muted">
                                <li>Wir prüfen Ihre Erwerbsberechtigung vor dem Versand bzw. der Übergabe.</li>
                                <li>Die Zahlungsaufforderung erhalten Sie erst nach unserer Freigabe.</li>
                                <li>Für das Überlassen benötigen wir Ihre Erlaubnis im Original (siehe Versandinformationen).</li>
                            </ul>
                            <label class="mt-3 flex items-start gap-2"><input type="checkbox" wire:model="legalDeclaration" class="mt-0.5 rounded border-line bg-surface-2 text-accent">
                                <span>Ich versichere, dass ich zum Erwerb der bestellten erlaubnispflichtigen Artikel berechtigt bin, die hinterlegten Unterlagen aktuell sind und ich die Artikel für mich selbst erwerbe.</span></label>
                        </div>
                    @endif

                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="terms" class="mt-0.5 rounded border-line bg-surface-2 text-accent">
                        <span>Ich habe die <a href="{{ route('page.show', 'agb') }}" target="_blank" class="text-accent underline">AGB</a> und die <a href="{{ route('page.show', 'widerruf') }}" target="_blank" class="text-accent underline">Widerrufsbelehrung</a> gelesen und akzeptiere sie. Hinweise zum Datenschutz finden Sie in der <a href="{{ route('page.show', 'datenschutz') }}" target="_blank" class="text-accent underline">Datenschutzerklärung</a>.</span></label>
                    @error('terms')<p class="text-xs text-red-400">{{ $message }}</p>@enderror

                    <div class="flex gap-3"><button type="button" wire:click="toStep(3)" class="btn-outline">Zurück</button>
                        <button type="button" wire:click="placeOrder" wire:loading.attr="disabled" class="btn-primary flex-1">
                            {{ $evaluation->requiresReview ? 'Zahlungspflichtig bestellen (nach Prüfung)' : 'Zahlungspflichtig bestellen' }}
                        </button>
                    </div>
                </section>
            @endif
        </div>

        <aside class="card h-fit space-y-3 p-6 lg:sticky lg:top-28">
            <h2 class="font-display text-lg font-bold">Ihre Bestellung</h2>
            <ul class="divide-y divide-line text-sm">
                @foreach ($cart->items as $item)
                    <li class="flex justify-between gap-3 py-2"><span>{{ $item->quantity }} × {{ $item->product->name }}</span><span class="whitespace-nowrap">{{ \App\Support\Money::format($item->lineTotal()) }}</span></li>
                @endforeach
            </ul>
            <dl class="space-y-1 border-t border-line pt-3 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Zwischensumme</dt><dd>{{ \App\Support\Money::format($subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Versand</dt><dd>{{ $selected ? \App\Support\Money::format($shippingCost) : '—' }}</dd></div>
                <div class="flex justify-between border-t border-line pt-2 font-display text-lg font-bold"><dt>Gesamt</dt><dd>{{ \App\Support\Money::format($subtotal + $shippingCost) }}</dd></div>
            </dl>
            <p class="text-xs text-muted">Alle Preise inkl. gesetzl. MwSt.</p>
        </aside>
    </div>
</div>
