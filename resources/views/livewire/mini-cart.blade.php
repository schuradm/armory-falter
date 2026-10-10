<div x-data="{ open: false }"
     x-on:open-mini-cart.window="open = true"
     x-on:cart-updated.window="open = true"
     x-on:keydown.escape.window="open = false"
     x-effect="document.documentElement.classList.toggle('overflow-hidden', open)">
    <div x-show="open" x-cloak x-transition.opacity.duration.300ms class="fixed inset-0 z-[80] bg-black/60 backdrop-blur-sm" x-on:click="open = false" aria-hidden="true"></div>

    <aside x-show="open" x-cloak
           x-transition:enter="transition duration-500 ease-out-expo" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition duration-300 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-[90] flex w-full max-w-md flex-col border-l border-line bg-bg shadow-2xl"
           role="dialog" aria-modal="true" aria-labelledby="mini-cart-title" x-trap.noscroll="open">
        <header class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 id="mini-cart-title" class="flex items-center gap-2 font-display text-lg font-extrabold uppercase"><x-icon name="bag" class="size-5 text-accent"/> Warenkorb</h2>
            <button type="button" class="btn-ghost -mr-2" x-on:click="open = false" aria-label="Schließen"><x-icon name="x"/></button>
        </header>

        <div wire:loading.delay class="h-0.5 w-full overflow-hidden bg-line"><div class="fx-progress h-full w-1/3 bg-brand"></div></div>

        @if (! $loaded)
            <div class="flex-1 space-y-4 p-5" aria-busy="true">@for ($i = 0; $i < 3; $i++)<div class="fx-skeleton h-24 rounded-card"></div>@endfor</div>
        @elseif (! $cart)
            <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
                <div class="grid size-20 place-items-center rounded-full bg-surface"><x-icon name="bag" class="size-9 text-muted"/></div>
                <p class="mt-5 font-display text-xl font-bold">Noch leer</p>
                <p class="mt-1 text-sm text-muted">Entdecken Sie unser Sortiment.</p>
                <a href="{{ route('shop.index') }}" class="btn-primary mt-6">Zum Shop</a>
            </div>
        @else
            {{-- Fortschritt bis zum Gratisversand (nur Standardversand) --}}
            @if ($freeFrom > 0 && $evaluation?->shippingMode->value === 'standard')
                <div class="border-b border-line px-5 py-3 text-xs">
                    @if ($progress >= 100)
                        <p class="flex items-center gap-1.5 text-emerald-400"><x-icon name="check" class="size-4"/> Kostenloser Standardversand freigeschaltet</p>
                    @else
                        <p class="text-muted">Noch <strong class="text-fg">{{ \App\Support\Money::format($freeFrom - $subtotal) }}</strong> bis zum kostenlosen Versand</p>
                    @endif
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-2"><div class="fx-brand-bar h-full rounded-full transition-all duration-700 ease-out-expo" style="width: {{ $progress }}%"></div></div>
                </div>
            @endif

            <ul class="flex-1 divide-y divide-line overflow-y-auto overscroll-contain px-5">
                @foreach ($cart->items as $item)
                    @php($p = $item->product)
                    @php($r = $evaluation?->resultFor($item))
                    <li wire:key="mc-{{ $item->id }}" @class(['flex gap-4 py-4', 'fx-flash' => $justAdded === $p->id])>
                        <a href="{{ $p->url() }}" class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-md bg-surface">
                            @if ($img = $p->primaryImage())<img src="{{ $img->url('thumb') }}" alt="" width="80" height="80" class="size-full object-contain p-1" loading="lazy" decoding="async">@else<x-icon name="box" class="size-7 text-muted"/>@endif
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <p class="truncate text-[11px] tracking-wider text-muted uppercase">{{ $p->manufacturer?->name }}</p>
                            <a href="{{ $p->url() }}" class="line-clamp-2 font-ui text-sm font-medium hover:text-accent">{{ $p->name }}</a>
                            @if ($item->variant)<p class="text-xs text-muted">{{ $item->variant->label() }}</p>@endif
                            @if ($r && ! $r->allowed)<p class="mt-1 flex items-center gap-1 text-[11px] text-amber-400"><x-icon name="shield" class="size-3.5"/> Nachweis erforderlich</p>@endif
                            <div class="mt-auto flex items-center justify-between pt-2">
                                @if ($p->is_unique_item)
                                    <span class="text-xs text-muted">Einzelstück</span>
                                @else
                                    <div class="flex items-center rounded-md border border-line text-sm">
                                        <button type="button" class="px-2 py-1 text-muted hover:text-fg" wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})" @disabled($item->quantity <= 1) aria-label="Weniger"><x-icon name="minus" class="size-3.5"/></button>
                                        <span class="w-7 text-center tabular-nums">{{ $item->quantity }}</span>
                                        <button type="button" class="px-2 py-1 text-muted hover:text-fg" wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})" aria-label="Mehr"><x-icon name="plus" class="size-3.5"/></button>
                                    </div>
                                @endif
                                <div class="flex items-center gap-3">
                                    <span class="font-display text-sm font-bold">{{ \App\Support\Money::format($item->lineTotal()) }}</span>
                                    <button type="button" wire:click="remove({{ $item->id }})" class="text-muted hover:text-red-400" aria-label="{{ $p->name }} entfernen"><x-icon name="trash" class="size-4"/></button>
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($suggestions->isNotEmpty())
                <div class="border-t border-line px-5 py-4">
                    <p class="font-ui text-xs font-semibold tracking-wider text-muted uppercase">Passend dazu</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($suggestions as $s)
                            <li class="flex items-center gap-3" wire:key="sg-{{ $s->id }}">
                                <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded bg-surface">@if ($img = $s->primaryImage())<x-img :media="$img" size="thumb" sizes="48px" alt="" class="size-full object-contain p-0.5"/>@else<x-icon name="box" class="size-5 text-muted"/>@endif</span>
                                <a href="{{ $s->url() }}" class="min-w-0 flex-1 text-sm"><span class="line-clamp-1">{{ $s->name }}</span><span class="font-display text-xs font-bold">{{ \App\Support\Money::format($s->currentPrice()) }}</span></a>
                                <button type="button" x-data="{ b: false }" @click="b = true; $store.cart.add({{ $s->id }}).finally(() => b = false)" :disabled="b" class="grid size-8 place-items-center rounded-full border border-line hover:border-brand hover:bg-brand hover:text-white" aria-label="{{ $s->name }} hinzufügen"><x-icon name="plus" class="size-4"/></button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <footer class="space-y-3 border-t border-line bg-surface px-5 py-5">
                @if ($evaluation?->requiresReview)
                    <p class="flex gap-2 rounded-md bg-surface-2 p-3 text-xs text-muted"><x-icon name="shield" class="size-4 shrink-0 text-accent"/> Erlaubnispflichtige Artikel: Wir prüfen Ihre Berechtigung vor Versand. Zahlung erst nach Freigabe.</p>
                @endif
                <div class="flex items-baseline justify-between">
                    <span class="text-sm text-muted">Zwischensumme</span>
                    <span class="font-display text-2xl font-extrabold">{{ \App\Support\Money::format($subtotal) }}</span>
                </div>
                <p class="-mt-2 text-right text-[11px] text-muted">inkl. MwSt., zzgl. <a href="{{ route('page.show', 'versandinformationen') }}" class="underline">Versand</a></p>
                <div class="flex flex-wrap gap-1.5" aria-label="Mögliche Zahlungsarten">
                    @foreach ($evaluation?->paymentMethods ?? [] as $pm)<span class="badge bg-surface-2 text-muted">{{ $pm->label() }}</span>@endforeach
                </div>
                @if ($evaluation && ! $evaluation->allowed())
                    @guest<a href="{{ route('login') }}" class="btn-primary w-full">Anmelden & fortfahren</a>@else<a href="{{ route('account.documents') }}" class="btn-primary w-full">Unterlagen hochladen</a>@endguest
                @else
                    <a href="{{ route('checkout') }}" class="btn-primary fx-shine w-full py-4"><x-icon name="lock" class="size-4"/> Sicher zur Kasse</a>
                @endif
                <a href="{{ route('cart') }}" class="btn-ghost w-full justify-center text-sm">Warenkorb bearbeiten</a>
            </footer>
        @endif
    </aside>
</div>
