{{-- Styles der Hero-Explosionszeichnung (components/hero-assembly). Bewusst inline und nur auf der Startseite statt in
     app.css: hält das render-blockierende CSS aller Seiten schlank. Steht vor dem Hero-Inhalt (kein Umspringen). --}}
    <style>
        /* Bühne: Desktop pinnt den ganzen Hero, mobil nur die Grafik. Ohne Bewegung: kein Pinning, statisch montiert. */
        .asm-hero{overflow:clip}
        .asm-pin{position:relative;display:flex;align-items:center}
        .asm-grid{display:grid;width:100%;align-items:center;column-gap:2.5rem;padding-block:3.5rem}
        .asm-stage{position:relative;width:100%;max-width:34rem;margin:2.5rem auto 0}
        .asm-cap{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:center;gap:.25rem 1rem;margin-top:.5rem;text-align:center;font:.875rem/1.25rem var(--font-ui);color:rgb(255 255 255/.75)}
        .asm-cap strong{font-weight:600;color:#fff}
        .asm-cap a{display:inline-flex;min-height:2.75rem;align-items:center;gap:.375rem;font-weight:600;color:#e0786f;text-underline-offset:4px}
        .asm-cap a:hover{text-decoration:underline}
        @media (min-width:640px){.asm-grid{padding-block:5rem}}
        @media (min-width:1024px){
            .asm-pin{min-height:calc(100svh - 4rem)}
            .asm-grid{grid-template-columns:minmax(0,1.08fr) minmax(0,.92fr);padding-block:2rem}
            .asm-grid h1{font-size:4rem}
            .asm-grid .asm-lead{margin-top:1.25rem}
            .asm-grid .asm-ctas{margin-top:2rem}
            .asm-grid .asm-trust{margin-top:1.5rem}
            .asm-grid .asm-stats{margin-top:2rem;padding-top:1.5rem}
            .asm-stage{grid-column:2;grid-row:1/span 2;margin:0;max-width:none}
            .asm-cap{justify-content:flex-end;text-align:right}
        }
        @media (min-width:1024px) and (max-height:760px){.asm-grid .asm-trust{display:none}}
        @media (min-width:1024px) and (max-height:700px){.asm-grid .asm-stats{display:none}}
        @media (min-width:1280px){.asm-grid h1{font-size:4.75rem}}
        @media (prefers-reduced-motion:no-preference) and (min-width:1024px){
            .asm-hero{height:220vh}
            .asm-pin{position:sticky;top:4rem;height:calc(100svh - 4rem)}
        }
        @media (prefers-reduced-motion:no-preference) and (max-width:1023px){
            .asm-stage{height:170vh}
            .asm-stage>.asm{position:sticky;top:max(5rem,calc(50svh - 11rem))}
        }
        /* Teile: Versatz (--x/--y), Start (--s) und Dauer (--l) auf der Fortschrittsachse --p (0 = Explosion, 1 = montiert) */
        @property --p{syntax:"<number>";inherits:true;initial-value:1}
        .asm svg{aspect-ratio:630/480}
        .asm-p{--t:clamp(0,(var(--p) - var(--s)) / var(--l),1);--e:calc((1 - var(--t)) * (1 - var(--t)) * (1 - var(--t)));transform:translate(calc(var(--x) * var(--e)),calc(var(--y) * var(--e)))}
        .asm-slide{--ty:clamp(0,(var(--p) - var(--s)) / var(--l) * 2,1);--tx:clamp(0,(var(--p) - var(--s)) / var(--l) * 2 - 1,1);transform:translate(calc(var(--x) * (1 - var(--tx)) * (1 - var(--tx))),calc(var(--y) * (1 - var(--ty)) * (1 - var(--ty))))}
        .asm-l{opacity:calc(1 - clamp(0,(var(--p) - var(--s)) / var(--l) * 2,1));fill:none;stroke:#9d4640;stroke-width:1.1}
        .asm-l circle{fill:#111;stroke-width:1.6}
        .asm-l text{fill:#e6e6e6;stroke:none;font:500 14px var(--font-ui);letter-spacing:.02em}
        .asm-glow{opacity:clamp(0,(var(--p) - .78) * 6,1)}
        .asm-sheen{transform:translateX(calc(clamp(0,(var(--p) - .84) / .16,1) * 820px)) skewX(-18deg)}
        .asm-cap{transform:translateY(calc((1 - clamp(0,(var(--p) - .8) * 5,1)) * 10px))}
        @media (max-width:639px){.asm-l text{font-size:19px}}
        @supports not (animation-timeline:view()){html.js .asm{--p:0}}
        @supports (animation-timeline:view()){
            .asm{animation-name:asm-p;animation-timing-function:linear;animation-fill-mode:both;animation-duration:auto;animation-timeline:--asm-m;animation-range:contain 0% contain 100%}
            @media (min-width:1024px){.asm{animation-timeline:--asm-d}}
        }
        @media (prefers-reduced-motion:reduce){.asm{animation:none!important;--p:1!important}.asm-sheen{display:none}}
        @keyframes asm-p{from{--p:0}to{--p:1}}
    </style>
