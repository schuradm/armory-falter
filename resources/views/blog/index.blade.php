<x-layouts.app>
    <div class="container-af py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <h1 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">{{ $category?->name ?? 'Blog' }}</h1>
        <p class="mt-3 max-w-2xl text-muted">{{ $category?->description ?? 'Praxiswissen aus Jagd, Sportschießen und Werkstatt, verständlich erklärt.' }}</p>
        <nav class="mt-6 flex flex-wrap gap-2" aria-label="Blog-Kategorien">
            <a href="{{ route('blog.index') }}" @class(['badge px-3 py-1.5 normal-case tracking-normal', 'bg-brand text-accent-fg' => ! $category, 'border border-line' => $category])>Alle</a>
            @foreach ($categories as $c)<a href="{{ route('blog.category', $c) }}" @class(['badge px-3 py-1.5 normal-case tracking-normal', 'bg-brand text-accent-fg' => $category?->id === $c->id, 'border border-line' => $category?->id !== $c->id])>{{ $c->name }}</a>@endforeach
        </nav>
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">@forelse ($posts as $post)@include('blog.partials.card')@empty<p class="text-muted">Noch keine Beiträge.</p>@endforelse</div>
        <div class="mt-10">{{ $posts->links() }}</div>
    </div>
</x-layouts.app>
