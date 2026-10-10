<x-layouts.app>
    <div class="border-b border-line bg-surface/40">
        <div class="container-af py-10">
            <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
            <h1 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">{{ $category?->name ?? 'Shop' }}</h1>
            @if ($category?->description)<p class="mt-3 max-w-3xl text-muted">{{ $category->description }}</p>@endif
            @if ($category && $category->children->isNotEmpty())
                <div class="mt-6 flex flex-wrap gap-2">@foreach ($category->children as $child)<a href="{{ $child->url() }}" class="badge border border-line px-3 py-1.5 normal-case tracking-normal hover:border-accent hover:text-accent">{{ $child->name }}</a>@endforeach</div>
            @endif
        </div>
    </div>
    <div class="container-af py-10">
        <livewire:product-listing :category-id="$category?->id"/>
        @if ($category?->content_bottom)<div class="prose-af mt-16 max-w-4xl border-t border-line pt-10">{!! $category->content_bottom !!}</div>@endif
    </div>
</x-layouts.app>
