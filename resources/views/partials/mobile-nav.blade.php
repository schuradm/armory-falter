<div x-show="mobile" x-cloak class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="Menü">
    <div x-show="mobile" x-transition.opacity class="absolute inset-0 bg-black/70" @click="mobile = false"></div>
    <nav x-show="mobile" x-transition:enter="transition duration-300 ease-out-expo" x-transition:enter-start="-translate-x-full"
         x-transition:leave="transition duration-200" x-transition:leave-end="-translate-x-full"
         class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col overflow-y-auto bg-surface">
        <div class="flex items-center justify-between border-b border-line p-4">
            <x-logo class="h-8 w-auto"/>
            <button type="button" class="btn-ghost" @click="mobile = false" aria-label="Menü schließen"><x-icon name="x" class="size-6"/></button>
        </div>
        <div class="p-4"><livewire:search-autocomplete :mobile="true" /></div>
        <ul class="flex-1 divide-y divide-line border-y border-line">
            @foreach ($navCategories as $cat)
                <li x-data="{ open: false }">
                    <div class="flex items-center">
                        <a href="{{ $cat->url() }}" class="flex flex-1 items-center gap-3 px-4 py-3.5 font-ui font-medium"><x-icon :name="$cat->icon ?? 'box'" class="size-5 text-accent"/>{{ $cat->name }}</a>
                        @if ($cat->children->isNotEmpty())
                            <button type="button" class="px-4 py-3.5" @click="open = !open" :aria-expanded="open" aria-label="Unterkategorien {{ $cat->name }}"><x-icon name="chevron-down" class="size-5 transition" ::class="open && 'rotate-180'"/></button>
                        @endif
                    </div>
                    @if ($cat->children->isNotEmpty())
                        <ul x-show="open" x-collapse class="bg-surface-2 pb-2">
                            @foreach ($cat->children as $child)
                                <li><a href="{{ $child->url() }}" class="block py-2 pl-12 pr-4 text-sm text-muted">{{ $child->name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
            <li><a href="{{ route('brands.index') }}" class="block px-4 py-3.5 font-ui font-medium">Hersteller</a></li>
            <li><a href="{{ route('trade.consignment') }}" class="block px-4 py-3.5 font-ui font-medium">Verkaufsservice (egun)</a></li>
            <li><a href="{{ route('trade.buyback') }}" class="block px-4 py-3.5 font-ui font-medium">Waffen-Ankauf</a></li>
            <li><a href="{{ route('workshop.index') }}" class="block px-4 py-3.5 font-ui font-medium">Werkstatt</a></li>
            <li><a href="{{ route('blog.index') }}" class="block px-4 py-3.5 font-ui font-medium">Blog</a></li>
            <li><a href="{{ route('contact') }}" class="block px-4 py-3.5 font-ui font-medium">Kontakt</a></li>
        </ul>
        <div class="grid grid-cols-3 gap-2 p-4 text-center text-xs">
            <a href="{{ route('account.dashboard') }}" class="card py-3"><x-icon name="user" class="mx-auto mb-1"/>Konto</a>
            <a href="{{ route('account.wishlists') }}" class="card py-3"><x-icon name="heart" class="mx-auto mb-1"/>Merkliste</a>
            <button type="button" class="card py-3" @click="$store.theme.toggle()"><x-icon name="sun" class="mx-auto mb-1"/>Hell/Dunkel</button>
        </div>
    </nav>
</div>
