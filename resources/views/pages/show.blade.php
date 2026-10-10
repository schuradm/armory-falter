<x-layouts.app>
    <div class="container-af max-w-4xl py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <h1 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">{{ $page->title }}</h1>
        <div class="brand-rule mt-4"></div>
        <article class="prose-af mt-8">{!! $page->body !!}</article>
        <p class="mt-10 text-xs text-muted">Stand: {{ $page->updated_at->format('d.m.Y') }}</p>
    </div>
</x-layouts.app>
