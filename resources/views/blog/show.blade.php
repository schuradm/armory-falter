<x-layouts.app>
    <article class="container-af max-w-3xl py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <a href="{{ route('blog.category', $post->category) }}" class="eyebrow mt-6 inline-block">{{ $post->category->name }}</a>
        <h1 class="mt-3 font-display text-3xl leading-tight font-extrabold sm:text-5xl">{{ $post->title }}</h1>
        <p class="mt-4 text-sm text-muted">Von <a href="{{ route('blog.author', $post->author) }}" class="text-fg hover:text-accent">{{ $post->author->name }}</a>@if($post->author->role_title), {{ $post->author->role_title }}@endif · {{ $post->published_at->translatedFormat('d. F Y') }}@if($post->reading_minutes) · {{ $post->reading_minutes }} Min. Lesezeit @endif</p>
        @if ($post->cover_path)<img src="{{ asset('storage/'.$post->cover_path) }}" alt="" class="mt-8 w-full rounded-card" fetchpriority="high">@endif
        <div class="prose-af prose-lg mt-10">{!! $post->body !!}</div>
        @if ($post->faqs->isNotEmpty())<section class="mt-12"><h2 class="font-display text-2xl font-bold">Häufige Fragen</h2><x-faq-list :faqs="$post->faqs" class="mt-4"/></section>@endif

        <aside class="card mt-12 flex gap-5 p-6">
            <div class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-full bg-surface-2">@if($post->author->avatar_path)<img src="{{ asset('storage/'.$post->author->avatar_path) }}" alt="" class="size-full object-cover">@else<x-icon name="user" class="size-7 text-muted"/>@endif</div>
            <div><p class="label">Über den Autor</p><p class="font-display font-bold">{{ $post->author->name }}</p><p class="mt-1 text-sm text-muted">{{ $post->author->bio }}</p></div>
        </aside>

        @if ($post->comments_enabled)
            <section class="mt-12" id="kommentare">
                <h2 class="font-display text-2xl font-bold">Kommentare ({{ $comments->count() }})</h2>
                <div class="mt-6 space-y-4">
                    @foreach ($comments as $comment)
                        <div class="card p-5"><p class="text-sm font-medium">{{ $comment->author_name }} <span class="text-xs font-normal text-muted">· {{ $comment->created_at->format('d.m.Y') }}</span></p><p class="mt-2 text-sm whitespace-pre-line">{{ $comment->body }}</p>
                            @foreach ($comment->replies as $reply)<div class="mt-4 border-l-2 border-accent pl-4"><p class="text-sm font-medium">{{ $reply->author_name }}</p><p class="mt-1 text-sm whitespace-pre-line">{{ $reply->body }}</p></div>@endforeach
                        </div>
                    @endforeach
                </div>
                <livewire:comment-form :post="$post"/>
            </section>
        @endif
    </article>
    @if ($related->isNotEmpty())
        <section class="container-af pb-12"><x-section-heading title="Weiterlesen"/><div class="grid gap-6 md:grid-cols-3">@foreach ($related as $post)@include('blog.partials.card')@endforeach</div></section>
    @endif
</x-layouts.app>
