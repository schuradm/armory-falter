<div class="container-af py-10">
    <h1 class="font-display text-3xl font-extrabold uppercase">Warenkorb</h1>
    <div class="brand-rule mt-3"></div>

    @if (! $cart || $cart->items->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <x-icon name="cart" class="mx-auto size-12 text-muted"/>
            <p class="mt-4 font-display text-xl font-bold">Ihr Warenkorb ist leer</p>
            <a href="{{ route('shop.index') }}" class="btn-primary mt-6">Jetzt shoppen</a>
        </div>
    @else
        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_380px]">
            <ul class="space-y-4">
                @foreach ($cart->items as $item)
                    @php($p = $item->product)
                    @php($r = $evaluation?->resultFor($item))
                    <li class="card flex gap-4 p-4" wire:key="ci-{{ $item->id }}">
                        <a href="{{ $p->url() }}" class="grid size-24 shrink-0 place-items-center overflow-hidden rounded bg-surface-2">
                            @if ($img = $p->primaryImage())<img src="{{ $img->url() }}" alt="" class="size-full object-contain p-1" loading="lazy">@else<x-icon name="box" class="size-8 text-muted"/>@endif
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs text-muted">{{ $p->manufacturer?->name }} · Art.-Nr. {{ $item->variant?->sku ?? $p->sku }}</p>
                                    <a href="{{ $p->url() }}" class="font-display font-bold hover:text-accent">{{ $item->displayName() }}</a>
                                    @if ($item->variant)<p class="text-xs text-muted">{{ $item->variant->label() }}</p>@endif
                                    @if ($item->isGiftVoucher() && ! empty($item->options['recipient_email']))<p class="text-xs text-muted">Für {{ $item->options['recipient_name'] ?? $item->options['recipient_email'] }} · Versand per E-Mail</p>@endif
                                </div>
                                <p class="font-display font-bold whitespace-nowrap">{{ \App\Support\Money::format($item->lineTotal()) }}</p>
                            </div>
                            <x-legal-badge :legal-class="$p->legalClass" class="self-start"/>
                            @if ($r && ! $r->allowed)
                                <p class="mt-1 flex items-start gap-1.5 text-xs text-amber-400"><x-icon name="alert" class="size-4 shrink-0"/>{{ $r->reasons[0] ?? '' }}</p>
                            @endif
                            <div class="mt-auto flex items-center gap-3 pt-2">
                                @unless ($p->is_unique_item)
                                    <select wire:change="updateQuantity({{ $item->id }}, $event.target.value)" class="input w-20 py-1.5 text-sm" aria-label="Menge">
                                        @foreach (range(1, max(10, $item->quantity)) as $n)<option value="{{ $n }}" @selected($n === $item->quantity)>{{ $n }}</option>@endforeach
                                    </select>
                                @endunless
                                <button type="button" wire:click="remove({{ $item->id }})" class="flex items-center gap-1 text-xs text-muted hover:text-red-400"><x-icon name="trash" class="size-4"/> Entfernen</button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="card h-fit space-y-4 p-6 lg:sticky lg:top-28">
                <h2 class="font-display text-lg font-bold">Zusammenfassung</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Zwischensumme</dt><dd>{{ \App\Support\Money::format($subtotal) }}</dd></div>
                    @if ($totals->discount > 0)
                        <div class="flex justify-between text-accent"><dt>Rabatt ({{ $totals->discountCode->code }})</dt><dd>−{{ \App\Support\Money::format($totals->discount) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-muted">Versand</dt><dd>{{ $evaluation?->digitalOnly ? 'per E-Mail, kostenlos' : 'wird im nächsten Schritt berechnet' }}</dd></div>
                    @if ($totals->giftVoucherTotal() > 0)
                        <div class="flex justify-between text-accent"><dt>Geschenkgutschein</dt><dd>−{{ \App\Support\Money::format($totals->giftVoucherTotal()) }}</dd></div>
                    @endif
                </dl>
                @include('livewire.partials.voucher-codes')
                @if ($evaluation)
                    <div class="rounded-md bg-surface-2 p-3 text-xs text-muted">
                        <p><span class="text-fg">Versandart:</span> {{ $evaluation->shippingMode->label() }}</p>
                        <p class="mt-1"><span class="text-fg">Mögliche Zahlung:</span> {{ collect($evaluation->paymentMethods)->map->label()->join(', ') ?: '—' }}</p>
                        @if ($evaluation->requiresReview)
                            <p class="mt-2 text-amber-400">Enthält erlaubnispflichtige Artikel: Wir prüfen Ihre Berechtigung vor dem Versand. Die Zahlungsaufforderung erhalten Sie nach der Freigabe.</p>
                        @endif
                    </div>
                @endif
                @if ($freeShippingGap > 0 && $evaluation?->shippingMode->value === 'standard')
                    <p class="text-xs text-muted">Noch {{ \App\Support\Money::format($freeShippingGap) }} bis zum kostenlosen Standardversand.</p>
                @endif
                <div class="flex justify-between border-t border-line pt-4 font-display text-lg font-bold"><span>{{ $totals->giftVoucherTotal() > 0 ? 'Zu zahlen' : 'Gesamt' }}</span><span>{{ \App\Support\Money::format($totals->amountDue()) }}</span></div>
                <p class="-mt-2 text-xs text-muted">inkl. MwSt.{{ $evaluation?->digitalOnly ? '' : ', zzgl. Versand' }}</p>

                @if ($evaluation && ! $evaluation->allowed())
                    <div class="rounded-md border border-amber-500/40 p-3 text-sm">
                        <p class="font-medium">Bestellung noch nicht möglich</p>
                        <p class="mt-1 text-muted">Für mindestens einen Artikel fehlt ein geprüfter Nachweis.</p>
                        @guest<a href="{{ route('login') }}" class="btn-primary mt-3 w-full">Anmelden</a>@else<a href="{{ route('account.documents') }}" class="btn-primary mt-3 w-full">Unterlagen hochladen</a>@endguest
                    </div>
                @else
                    <a href="{{ route('checkout') }}" class="btn-primary w-full">Zur Kasse</a>
                @endif
                <a href="{{ route('shop.index') }}" class="btn-ghost w-full justify-center">Weiter einkaufen</a>
            </aside>
        </div>
    @endif
</div>
