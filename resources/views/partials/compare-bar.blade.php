<div x-data x-show="$store.compare.items.length > 0" x-cloak x-transition
     class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 backdrop-blur">
    <div class="container-af flex items-center gap-4 py-3">
        <x-icon name="compare" class="hidden size-5 text-accent sm:block"/>
        <div class="flex flex-1 gap-2 overflow-x-auto">
            <template x-for="item in $store.compare.items" :key="item.id">
                <span class="badge shrink-0 bg-surface-2 normal-case tracking-normal">
                    <span x-text="item.name" class="max-w-40 truncate"></span>
                    <button type="button" @click="$store.compare.toggle(item)" aria-label="Entfernen"><x-icon name="x" class="size-3.5"/></button>
                </span>
            </template>
        </div>
        <button type="button" class="btn-ghost hidden sm:inline-flex" @click="$store.compare.clear()">Leeren</button>
        <a :href="$store.compare.url" class="btn-primary py-2">Vergleichen (<span x-text="$store.compare.items.length"></span>)</a>
    </div>
</div>
