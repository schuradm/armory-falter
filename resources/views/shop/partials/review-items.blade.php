{{-- Bewertungen als Listeneinträge. Auch Fragment für „Mehr anzeigen“ (ReviewController::index). --}}
@foreach ($reviews as $r)
    <li class="py-5" wire:key="review-{{ $r->id }}">
        <article>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <x-stars :rating="$r->rating"/>
                @if ($r->title)<h3 class="font-display text-base font-bold">{{ $r->title }}</h3>@endif
            </div>
            <p class="mt-1 text-xs text-muted">
                {{ $r->author_name }} · <time datetime="{{ $r->created_at->toDateString() }}">{{ $r->created_at->format('d.m.Y') }}</time>
                · <span class="inline-flex items-center gap-1 text-emerald-400"><x-icon name="check" class="size-3.5"/>Verifizierter Kauf</span>
            </p>
            @if ($r->body)<p class="mt-2 text-sm whitespace-pre-line text-fg/85">{{ $r->body }}</p>@endif
            @if ($r->reply)
                <div class="mt-3 rounded-md border-l-2 border-brand bg-surface-2 p-3 text-sm">
                    <p class="font-medium">Antwort von Armory Falter</p>
                    <p class="mt-1 whitespace-pre-line text-fg/85">{{ $r->reply }}</p>
                </div>
            @endif
        </article>
    </li>
@endforeach
@if ($reviews->hasMorePages())
    <li hidden data-next-page="{{ route('product.reviews', ['product' => $product, 'seite' => $reviews->currentPage() + 1]) }}"></li>
@endif
