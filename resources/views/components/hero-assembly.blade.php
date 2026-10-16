@props(['config' => []])
{{-- Explosionszeichnung Glock 17 Gen5 (generisch, ohne Marken-/Schriftzüge). Teile starten auseinandergezogen
     und setzen sich beim Scrollen zusammen: --p (0 = Explosion, 1 = montiert) kommt aus einer CSS-Scroll-Timeline
     (--asm-d am Hero bzw. --asm-m an der mobilen Bühne, siehe home.blade.php) oder ersatzweise aus app.js.
     Styles: partials/hero-assembly-styles (stehen am Anfang des Heros, vor dessen Inhalt).
     Koordinaten: 1 mm ≈ 2 Einheiten, Mündung links. Zusammengebaut ist der Grundzustand (reduced motion = statisch). --}}
<figure {{ $attributes->class('asm') }} data-assembly>
    <svg viewBox="-20 0 630 480" width="630" height="480" class="block h-auto w-full overflow-visible" role="img"
         aria-label="Explosionszeichnung einer Glock 17 Gen5: Verschluss, Lauf, Schließfeder, Abzug, Griffstück und Magazin setzen sich zur fertigen Pistole zusammen.">
        <defs>
            <linearGradient id="aS" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fafafa"/><stop offset=".12" stop-color="#d9dbdd"/><stop offset=".55" stop-color="#a3a6aa"/><stop offset="1" stop-color="#6c6f73"/></linearGradient>
            <linearGradient id="aB" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7d8084"/><stop offset=".3" stop-color="#f4f4f4"/><stop offset=".6" stop-color="#a9acb0"/><stop offset="1" stop-color="#55585c"/></linearGradient>
            <linearGradient id="aP" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#55555a"/><stop offset=".3" stop-color="#333337"/><stop offset="1" stop-color="#1c1c1f"/></linearGradient>
            <linearGradient id="aR" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".45" stop-color="#fff" stop-opacity=".42"/><stop offset=".55" stop-color="#e0786f" stop-opacity=".35"/><stop offset="1" stop-color="#e0786f" stop-opacity="0"/></linearGradient>
            <linearGradient id="aH" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".3" stop-color="#fff" stop-opacity=".28"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/><stop offset=".8" stop-color="#000" stop-opacity=".12"/></linearGradient>
            <radialGradient id="aG"><stop offset="0" stop-color="#9d4640" stop-opacity=".7"/><stop offset=".55" stop-color="#9d4640" stop-opacity=".18"/><stop offset="1" stop-color="#9d4640" stop-opacity="0"/></radialGradient>
            <pattern id="aSer" width="6" height="8" patternUnits="userSpaceOnUse"><path fill="#3e4146" d="M0 0h2.6v8H0z"/><path fill="#fff" fill-opacity=".7" d="M2.6 0h.8v8h-.8z"/></pattern>
            <pattern id="aTex" width="5" height="5" patternUnits="userSpaceOnUse"><path fill="#4a4a4f" d="M2.5.6 4.4 2.5 2.5 4.4.6 2.5z"/><path fill="#111" fill-opacity=".6" d="M2.5 4.4 4.4 2.5H.6z"/></pattern>
            <pattern id="aCoil" width="6" height="14" patternUnits="userSpaceOnUse"><path d="M.5 14 5.5 0" stroke="#e6e6e6" stroke-width="1.7"/><path d="M2 14 7 0" stroke="#55585c" stroke-width=".8"/></pattern>
            {{-- Umrisse: einmal definiert, für Füllung, Glanz-Maske und Kanten wiederverwendet --}}
            <path id="aSlide" d="M98 106l8-10h356q6 0 6 6v46q0 4-4 4H120l-18-10q-4-2-4-7z"/>
            <path id="aFrame" fill-rule="evenodd" clip-rule="evenodd" d="M118 148h350l10 2q14 5 10 16-4 6-18 7-11 14-8 46 26 50 28 108v25l-96 8q-6 0-6-6l-42-114q-4-6-12-4H246q-14 0-14-14l-4-48h-98q-12 0-12-8zm126 32h76q16 4 18 30 0 16-12 16h-74q-8 0-8-8z"/>
            <circle id="aPin" r="3.2" fill="#c9cbce" stroke="#2a2a2d" stroke-width="1.2"/>
            <clipPath id="aClip"><use href="#aSlide"/><use href="#aFrame"/></clipPath>
        </defs>

        <ellipse class="asm-glow" cx="300" cy="230" rx="260" ry="170" fill="url(#aG)"/>

        {{-- Magazin --}}
        <g class="asm-p" style="--x:36px;--y:96px;--s:.02;--l:.24">
            <path fill="url(#aP)" stroke="#55555a" d="M354 186l84-6 54 170-92 8z"/>
            <path fill="#d0d2d5" d="M356 184l80-6 2 6-80 6z"/>
            <g fill="#0d0d0e" stroke="#6a6a70" stroke-width=".8"><circle cx="452" cy="220" r="2.4"/><circle cx="460" cy="246" r="2.4"/><circle cx="468" cy="272" r="2.4"/><circle cx="476" cy="298" r="2.4"/><circle cx="484" cy="324" r="2.4"/></g>
            <path fill="url(#aP)" stroke="#5a5a5f" d="M394 358l100-8 4 12q0 4-4 4l-92 7q-8 0-8-6z"/>
            <g class="asm-l" aria-hidden="true"><circle cx="414" cy="320" r="3"/><path d="M414 320l-30 26h-70"/><text x="318" y="340">Magazin</text></g>
        </g>

        {{-- Abzug mit Sicherungszüngel --}}
        <g class="asm-p" style="--x:-6px;--y:80px;--s:.66;--l:.16">
            <path fill="url(#aP)" stroke="#5a5a5f" d="M300 170c-8 14-12 30-8 52h8c-2-18 2-34 10-46z"/>
            <path stroke="#9a9a9f" stroke-width="1.6" d="M297 192v26"/>
            <g class="asm-l" aria-hidden="true"><circle cx="296" cy="214" r="3"/><path d="M296 214l-30 30h-70"/><text x="200" y="238">Abzug</text></g>
        </g>

        {{-- Griffstück (Polymer, Gen5-Textur) --}}
        <g class="asm-frame" style="--s:.74;--l:.14">
            <path fill="url(#aS)" stroke="#3a3a3e" stroke-width=".8" d="M244 139h42l6 9h-48zM438 142h26v6h-26zM150 143h40v5h-40z"/>
            <use href="#aFrame" fill="url(#aP)" stroke="#6b6b71" stroke-width="1.3"/>
            <use href="#aFrame" fill="url(#aH)" fill-opacity=".6"/>
            <path fill="url(#aTex)" d="M362 240h96q14 34 26 70 2 14 2 30l-82 6z"/>
            <path fill="none" stroke="#fff" stroke-opacity=".14" d="M120 150h346M340 182q60 6 126-6M131 175h96M246 227h80M392 356l94-8"/>
            <path fill="#151517" stroke="#4d4d52" d="M150 172h14v4h-14zM338 190h12l2 18h-12z"/>
            <path fill="none" stroke="#000" stroke-opacity=".5" d="M130 166h96"/>
            <g class="asm-l" aria-hidden="true"><circle cx="478" cy="236" r="3"/><path d="M478 236l26-26h66"/><text x="506" y="204">Griffstück</text></g>
        </g>

        {{-- Verriegelung, Schlittenfanghebel, Stifte --}}
        <g class="asm-p" style="--x:0px;--y:-34px;--s:.72;--l:.14">
            <path fill="url(#aS)" stroke="#3a3a3e" stroke-width=".8" d="M240 152h26v8h-26z"/>
            <path stroke="#45484c" d="M246 153v6M252 153v6M258 153v6"/>
            <path fill="url(#aS)" stroke="#3a3a3e" stroke-width=".8" d="M334 152h34l10-2v12l-10 1h-34z"/>
            <use href="#aPin" x="262" y="168"/><use href="#aPin" x="320" y="168"/><use href="#aPin" x="446" y="166"/>
        </g>

        {{-- Verschluss (Schlitten) mit Front-/Heckriffelung und Visierung --}}
        <g class="asm-slide" style="--x:64px;--y:-60px;--s:.42;--l:.26">
            {{-- Schließfeder und Lauf wandern zuerst in den Verschluss und reisen dann mit ihm aufs Griffstück --}}
            {{-- Lauf --}}
            <g class="asm-p" style="--x:-164px;--y:54px;--s:.24;--l:.2">
                <path fill="url(#aB)" d="M104 112h178v20H104q-4 0-4-4v-12q0-4 4-4z"/>
                <path fill="url(#aB)" d="M282 104h54q4 0 4 4v20l-10 8h-48z"/>
                <path fill="#8b8e92" d="M298 136h20l12 10-16 4-12-8z"/>
                <path fill="#3a3c40" d="M100 116h4v12h-4z"/>
                <path stroke="#fff" stroke-opacity=".5" d="M284 106h50"/>
                <g class="asm-l" aria-hidden="true"><circle cx="150" cy="113" r="3"/><path d="M150 113 130 70h70"/><text x="134" y="64">Lauf</text></g>
            </g>

            {{-- Schließfederführung mit Feder --}}
            <g class="asm-p" style="--x:-184px;--y:118px;--s:.1;--l:.2">
                <path fill="#9fa2a6" d="M110 139h190v4H110z"/>
                <path fill="url(#aCoil)" d="M114 135h178v12H114z"/>
                <path fill="url(#aB)" d="M108 136h6v10h-6zM292 133h12v16h-12z"/>
                <g class="asm-l" aria-hidden="true"><circle cx="200" cy="147" r="3"/><path d="M200 147v53h110"/><text x="204" y="194">Schließfeder</text></g>
            </g>

            <use href="#aSlide" fill="url(#aS)" stroke="#55585c"/>
            <use href="#aSlide" fill="url(#aH)"/>
            <path fill="#000" fill-opacity=".22" d="M98 104l6-2v40l-4-2q-2-2-2-6zM120 152l-18-10h20l14 10z"/>
            <path fill="url(#aSer)" d="M408 102h48v44h-48zM128 104h28v40h-28z"/>
            <path fill="#fff" fill-opacity=".55" d="M106 97h356v3H104z"/>
            <path fill="none" stroke="#3a3c40" stroke-opacity=".55" d="M120 148h346M462 98v52"/>
            <path fill="#2c2c2f" d="M110 96l2-7h10l2 7zM440 96l3-9h15l2 9z"/>
            <circle cx="117" cy="91.5" r="1.6" fill="#e0786f"/><path fill="#fff" d="M446 89h2v4h-2zM452 89h2v4h-2z"/>
            <g class="asm-l" aria-hidden="true"><circle cx="300" cy="100" r="3"/><path d="M300 100l-16-20h-94"/><text x="194" y="75">Verschluss</text></g>
        </g>

        <g clip-path="url(#aClip)" aria-hidden="true"><rect class="asm-sheen" x="-160" y="80" width="120" height="300" fill="url(#aR)"/></g>
    </svg>
    @if (filled($config['title'] ?? null) || filled($config['cta']['label'] ?? null))
        <figcaption class="asm-cap">
            @if (filled($config['title'] ?? null))<span><strong class="font-semibold text-white">{{ $config['title'] }}</strong>@if (filled($config['text'] ?? null)) {{ $config['text'] }}@endif</span>@endif
            @if (filled($config['cta']['label'] ?? null) && filled($config['cta']['url'] ?? null))
                <a href="{{ $config['cta']['url'] }}">{{ $config['cta']['label'] }} <x-icon name="arrow-right" class="size-4"/></a>
            @endif
        </figcaption>
    @endif
</figure>
