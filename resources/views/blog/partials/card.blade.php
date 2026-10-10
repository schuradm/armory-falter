<article class="card reveal group overflow-hidden">
    <a href="{{ route('blog.show', $post) }}" class="block aspect-[16/9] overflow-hidden bg-surface-2">
        @if ($post->cover_path)<img src="{{ asset('storage/'.$post->cover_path) }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">@else<div class="grid size-full place-items-center"><x-icon name="crosshair" class="size-12 text-accent/30"/></div>@endif
    </a>
    <div class="p-5">
        <p class="eyebrow">{{ $post->category->name }}</p>
        <h3 class="mt-2 font-display text-lg leading-snug font-bold"><a href="{{ route('blog.show', $post) }}" class="hover:text-accent">{{ $post->title }}</a></h3>
        <p class="mt-2 line-clamp-3 text-sm text-muted">{{ $post->excerpt }}</p>
        <p class="mt-4 text-xs text-muted">{{ $post->author->name }} · {{ $post->published_at->format('d.m.Y') }}@if($post->reading_minutes) · {{ $post->reading_minutes }} Min.@endif</p>
    </div>
</article>
