<div class="space-y-6">
    <h1 class="font-display text-2xl font-extrabold uppercase">Merklisten</h1>
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($lists as $l)
            <button type="button" wire:click="$set('activeId', {{ $l->id }})" @class(['badge px-3 py-1.5 normal-case tracking-normal', 'bg-brand text-accent-fg' => $l->id === $active->id, 'bg-surface-2' => $l->id !== $active->id])>{{ $l->name }} ({{ $l->products_count }})</button>
        @endforeach
        <form wire:submit="create" class="flex gap-2"><input wire:model="newName" placeholder="Neue Liste, z. B. Drückjagd" class="input py-1.5 text-sm" aria-label="Name der neuen Liste"><button class="btn-outline py-1.5" type="submit"><x-icon name="plus" class="size-4"/></button></form>
    </div>
    <div class="flex flex-wrap gap-3 text-sm">
        <button type="button" wire:click="share" class="text-accent">Link zum Teilen erzeugen</button>
        @if ($active->share_token)<span class="text-muted">Teilen-Code: <span class="font-mono">{{ $active->share_token }}</span></span>@endif
        @unless ($active->is_default)<button type="button" wire:click="deleteList" wire:confirm="Liste wirklich löschen?" class="text-red-400">Liste löschen</button>@endunless
    </div>
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($products as $p)
            <div class="space-y-2" wire:key="wl-{{ $p->id }}">
                <x-product-card :product="$p"/>
                <div class="flex gap-2"><button type="button" wire:click="toCart({{ $p->id }})" class="btn-primary flex-1 py-2">In den Warenkorb</button><button type="button" wire:click="remove({{ $p->id }})" class="btn-outline py-2" aria-label="Entfernen"><x-icon name="trash" class="size-4"/></button></div>
            </div>
        @empty
            <p class="card col-span-full p-8 text-center text-sm text-muted">Diese Liste ist leer. Mit dem Herz-Symbol merken Sie sich Artikel.</p>
        @endforelse
    </div>
</div>
