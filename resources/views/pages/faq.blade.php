<x-layouts.app>
    <div class="container-af max-w-4xl py-12">
        <x-breadcrumbs :items="app(\App\Support\Seo::class)->breadcrumbs"/>
        <h1 class="mt-4 font-display text-3xl font-extrabold uppercase sm:text-4xl">Häufige Fragen</h1>
        <div class="brand-rule mt-4"></div>
        @php($all = $groups->flatten())
        <x-faq-list :faqs="collect()" />
        @php(app(\App\Support\Seo::class)->schema(['@type' => 'FAQPage', 'mainEntity' => $all->map(fn ($f) => ['@type' => 'Question', 'name' => $f->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f->answer)]])->values()->all()]))
        @foreach ($groups as $group => $faqs)
            <section class="mt-10"><h2 class="font-display text-xl font-bold text-accent">{{ $group ?: 'Allgemein' }}</h2>
                <div class="mt-4 divide-y divide-line border-y border-line">
                    @foreach ($faqs as $faq)<details class="group py-4"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-ui font-medium">{{ $faq->question }}<x-icon name="chevron-down" class="size-5 shrink-0 text-accent transition group-open:rotate-180"/></summary><div class="prose-af mt-3 text-sm">{!! $faq->answer !!}</div></details>@endforeach
                </div></section>
        @endforeach
        <div class="card mt-12 flex flex-col items-center gap-3 p-8 text-center"><p class="font-display text-lg font-bold">Ihre Frage war nicht dabei?</p><a href="{{ route('contact') }}" class="btn-primary">Kontakt aufnehmen</a></div>
    </div>
</x-layouts.app>
