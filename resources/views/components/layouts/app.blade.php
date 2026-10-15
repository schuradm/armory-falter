@php($seo = app(\App\Support\Seo::class))
@php($settings = app(\App\Support\Settings::class))
<!DOCTYPE html>
<html lang="de" data-theme="dark" data-auth="{{ auth()->check() ? '1' : '0' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <script>document.documentElement.classList.add('js');try{var t=localStorage.getItem('af-theme');if(t)document.documentElement.dataset.theme=JSON.parse(t);}catch(e){}</script>
    <title>{{ $seo->title ?: ($title ?? 'Armory Falter') }}</title>
    @if ($seo->description)<meta name="description" content="{{ $seo->description }}">@endif
    <meta name="robots" content="{{ $seo->robots }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ $seo->canonical ?? url()->current() }}">
    @foreach ($seo->links ?? [] as $rel => $href)<link rel="{{ $rel }}" href="{{ $href }}">@endforeach
    <meta property="og:site_name" content="Armory Falter">
    <meta property="og:locale" content="de_DE">
    <meta property="og:type" content="{{ $seo->type }}">
    <meta property="og:title" content="{{ $seo->title ?: 'Armory Falter' }}">
    @if ($seo->description)<meta property="og:description" content="{{ $seo->description }}">@endif
    <meta property="og:url" content="{{ $seo->canonical ?? url()->current() }}">
    <meta property="og:image" content="{{ $seo->image ?? asset('images/og-default.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#0e0e0e">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('images/brand/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    {{-- Schriften der ersten Bildschirmseite vorladen; übrige laden bei Bedarf --}}
    <link rel="preload" href="/fonts/montserrat-700-800.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/roboto-400-700.woff2" as="font" type="font/woff2" crossorigin>
    {{ $head ?? '' }}
    @php($vite = \Illuminate\Support\Facades\Vite::getFacadeRoot())
    @if ($vite->isRunningHot() || ! is_file(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        @if (config('shop.performance.inline_css') && ! app()->environment('local'))
            {{-- CSS inline: spart einen render-blockierenden Request (FCP/LCP). Ganzseiten-Cache macht die Größe unkritisch. --}}
            <style>{!! $vite->content('resources/css/app.css') !!}</style>
        @else
            @vite(['resources/css/app.css'])
        @endif
        {{-- JS (Livewire + Alpine, ~65 kB gzip) wird für die erste Darstellung nicht gebraucht: Es lädt erst, wenn die Seite
             geladen und der erste Inhalt gezeichnet ist (load + first-contentful-paint), bzw. sofort bei der ersten Interaktion. So bekommen HTML, CSS, Schriften und das LCP-Bild auf langsamen
             Mobilverbindungen die volle Bandbreite. Die Datei ist unveränderlich gecacht, Folgeseiten laden sie aus dem Cache. --}}
        <script nonce="{{ $vite->cspNonce() }}">(function(){var d=0,l=0,p=0,go=function(){if(d)return;d=1;var s=document.createElement('script');s.type='module';s.src=@js($vite->asset('resources/js/app.js'));document.head.appendChild(s)},t=function(){if(l&&p)setTimeout(go)};try{if(PerformanceObserver.supportedEntryTypes.indexOf('paint')<0)throw 0;new PerformanceObserver(function(x){x.getEntries().forEach(function(e){if(e.name==='first-contentful-paint'){p=1;t()}})}).observe({type:'paint',buffered:true})}catch(e){p=1}if(document.readyState==='complete'){l=1;t()}else addEventListener('load',function(){l=1;t()},{once:true});['pointerdown','keydown','touchstart'].forEach(function(e){addEventListener(e,go,{once:true,passive:true})})})();</script>
    @endif
    @livewireStyles
    @foreach ($seo->allJsonLd() as $block)
        <script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
    {{-- Seiten beim Hovern vorab rendern (Chrome/Edge): Navigation fühlt sich sofort an. Keine Konto-/Kassen-/Aktions-URLs. --}}
    <script type="speculationrules">{"prerender":[{"source":"document","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/konto*","/kasse*","/zahlung*","/warenkorb*","/merkliste*","/kms-login*","/logout","/newsletter/*","/downloads/*","/*\\?*"]}},{"not":{"selector_matches":"[rel~=nofollow],[data-no-prerender]"}}]},"eagerness":"moderate"}]}</script>
</head>
<body class="flex min-h-screen flex-col">
    <a href="#inhalt" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] btn-primary">Zum Inhalt springen</a>

    @if ($bar = $settings->get('promo_bar'))
        <div class="relative z-[51] bg-brand px-4 py-2 text-center font-ui text-xs font-semibold tracking-wide text-white">{!! $bar !!}</div>
    @endif

    @include('partials.header')

    <main id="inhalt" class="flex-1">
        @if (session('status'))
            <div class="container-af mt-6"><div class="card flex items-center gap-3 border-accent/40 px-4 py-3 text-sm" role="status"><x-icon name="check" class="size-5 text-accent"/> {{ session('status') }}</div></div>
        @endif
        {{ $slot }}
    </main>

    @include('partials.footer')
    <livewire:mini-cart />
    <livewire:quick-view />
    @include('partials.compare-bar')
    @include('partials.cookie-consent')
    @include('partials.toast')

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
