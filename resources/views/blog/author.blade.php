<x-layouts.app>
    <div class="container-af py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <div class="mt-6 flex items-center gap-6">
            <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-full border border-accent bg-surface-2">@if($author->avatar_path)<img src="{{ asset('storage/'.$author->avatar_path) }}" alt="{{ $author->name }}" class="size-full object-cover">@else<x-icon name="user" class="size-10 text-muted"/>@endif</div>
            <div><h1 class="font-display text-3xl font-extrabold">{{ $author->name }}</h1><p class="text-accent">{{ $author->role_title }}</p></div>
        </div>
        @if ($author->bio)<p class="mt-6 max-w-3xl text-muted">{{ $author->bio }}</p>@endif
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">@foreach ($posts as $post)@include('blog.partials.card')@endforeach</div>
        <div class="mt-10">{{ $posts->links() }}</div>
    </div>
</x-layouts.app>
