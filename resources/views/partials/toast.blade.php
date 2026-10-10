<div x-data="{ items: [] }"
     @toast.window="const id = Date.now(); items.push({ id, ...$event.detail }); setTimeout(() => items = items.filter(i => i.id !== id), 3500)"
     class="pointer-events-none fixed right-4 top-24 z-[80] flex w-80 flex-col gap-2" aria-live="polite">
    <template x-for="item in items" :key="item.id">
        <div x-transition class="card pointer-events-auto flex items-start gap-3 border-accent/40 px-4 py-3 text-sm">
            <span class="mt-0.5 size-2 shrink-0 rounded-full bg-brand"></span>
            <span x-text="item.message"></span>
        </div>
    </template>
</div>
