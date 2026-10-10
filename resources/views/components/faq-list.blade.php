@props(['faqs'])
@if ($faqs->isNotEmpty())
    @php(app(\App\Support\Seo::class)->schema(['@type' => 'FAQPage', 'mainEntity' => $faqs->map(fn ($f) => ['@type' => 'Question', 'name' => $f->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f->answer)]])->values()->all()]))
    <div {{ $attributes->merge(['class' => 'divide-y divide-line border-y border-line']) }}>
        @foreach ($faqs as $faq)
            <details class="group py-4">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-ui font-medium">
                    {{ $faq->question }} <x-icon name="chevron-down" class="size-5 shrink-0 text-accent transition group-open:rotate-180"/>
                </summary>
                <div class="prose-af mt-3 text-sm">{!! $faq->answer !!}</div>
            </details>
        @endforeach
    </div>
@endif
