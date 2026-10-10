@props(['title', 'subtitle' => null])
<x-layouts.app>
    <div class="container-af grid min-h-[70vh] place-items-center py-12">
        <div class="card w-full max-w-md p-8">
            <h1 class="font-display text-2xl font-extrabold uppercase">{{ $title }}</h1>
            <div class="brand-rule mt-3"></div>
            @if ($subtitle)<p class="mt-3 text-sm text-muted">{{ $subtitle }}</p>@endif
            @if ($errors->any())<div class="mt-4 rounded-md border border-red-500/40 p-3 text-sm text-red-300" role="alert">{{ $errors->first() }}</div>@endif
            <div class="mt-6">{{ $slot }}</div>
        </div>
    </div>
</x-layouts.app>
