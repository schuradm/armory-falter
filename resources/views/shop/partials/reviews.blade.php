{{-- Produktbewertungen: Zusammenfassung, Verteilung, Hinweis nach § 5b Abs. 3 UWG, Liste mit „Mehr anzeigen“. --}}
<section id="bewertungen" class="mt-16 scroll-mt-28" aria-labelledby="bewertungen-titel">
    <div class="mb-8">
        <p class="eyebrow">Kundenstimmen</p>
        <h2 id="bewertungen-titel" class="mt-2 font-display text-2xl font-extrabold uppercase sm:text-3xl">Bewertungen</h2>
        <div class="brand-rule mt-3"></div>
    </div>
    <div class="grid gap-8 lg:grid-cols-[320px_1fr]">
        <div class="space-y-5">
            @if ($reviews)
                <div class="flex items-center gap-4">
                    <p class="font-display text-5xl font-extrabold">{{ number_format((float) $product->rating_avg, 1, ',', '') }}</p>
                    <div><x-stars :rating="$product->rating_avg" size="size-5"/><p class="mt-1 text-sm text-muted">{{ $product->rating_count }} {{ $product->rating_count === 1 ? 'Bewertung' : 'Bewertungen' }}</p></div>
                </div>
                <ul class="space-y-1.5 text-sm" aria-label="Verteilung der Sterne">
                    @foreach ($distribution as $stars => $n)
                        @php($pct = $product->rating_count ? round($n / $product->rating_count * 100) : 0)
                        <li class="grid grid-cols-[4.5rem_1fr_2rem] items-center gap-3">
                            <span class="text-muted">{{ $stars }} {{ $stars === 1 ? 'Stern' : 'Sterne' }}</span>
                            <span class="h-2 overflow-hidden rounded-full bg-surface-2" aria-hidden="true"><span class="block h-full rounded-full bg-accent" style="width: {{ $pct }}%"></span></span>
                            <span class="text-right tabular-nums"><span class="sr-only">{{ $stars }} {{ $stars === 1 ? 'Stern' : 'Sterne' }}: </span>{{ $n }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-muted">Für diesen Artikel gibt es noch keine Bewertungen.</p>
            @endif
            <a href="{{ route('account.reviews', ['produkt' => $product->slug]) }}" class="btn-outline w-full" rel="nofollow">Artikel gekauft? Jetzt bewerten</a>
            {{-- § 5b Abs. 3 UWG: ob und wie sichergestellt ist, dass Bewertungen von echten Käufern stammen --}}
            <div class="flex gap-2 rounded-md bg-surface-2 p-3 text-xs text-muted">
                <x-icon name="shield" class="size-4 shrink-0 text-accent"/>
                <p><span class="font-medium text-fg">Bewertungen stammen ausschließlich von verifizierten Käufern.</span>
                    Bewerten kann nur, wer angemeldet ist und diesen Artikel nachweislich bei uns gekauft und erhalten hat; wir prüfen das automatisch anhand der Bestellung, je Kunde ist eine Bewertung möglich.
                    Jede Bewertung sehen wir vor der Veröffentlichung durch und lehnen nur Beiträge mit Rechtsverstößen, personenbezogenen Daten oder ohne Produktbezug ab, negative Bewertungen veröffentlichen wir genauso wie positive.
                    Für Bewertungen gewähren wir keine Gegenleistung. Der Durchschnitt berücksichtigt alle veröffentlichten Bewertungen, sortiert ist nach Datum (neueste zuerst).</p>
            </div>
        </div>
        @if ($reviews)
            <div x-data="{ next: @js($reviews->hasMorePages() ? route('product.reviews', ['product' => $product, 'seite' => 2]) : null), busy: false, failed: false,
                    async more() {
                        this.busy = true; this.failed = false;
                        try {
                            const res = await fetch(this.next, { headers: { Accept: 'text/html' } });
                            if (! res.ok) throw new Error(res.status);
                            this.$refs.list.querySelector('[data-next-page]')?.remove();
                            this.$refs.list.insertAdjacentHTML('beforeend', await res.text());
                            const marker = this.$refs.list.querySelector('[data-next-page]');
                            this.next = marker?.dataset.nextPage ?? null;
                        } catch (e) { this.failed = true } finally { this.busy = false }
                    } }">
                <ol x-ref="list" class="divide-y divide-line border-y border-line" aria-live="polite">
                    @include('shop.partials.review-items', ['reviews' => $reviews, 'product' => $product])
                </ol>
                @if ($reviews->hasMorePages())
                    <button type="button" class="btn-outline mt-5" x-show="next" @click="more()" :disabled="busy">
                        <span x-text="busy ? 'Wird geladen …' : 'Mehr anzeigen'">Mehr anzeigen</span>
                    </button>
                    <p x-show="failed" x-cloak class="mt-2 text-xs text-red-400">Weitere Bewertungen konnten nicht geladen werden. Bitte später erneut versuchen.</p>
                @endif
            </div>
        @endif
    </div>
</section>
