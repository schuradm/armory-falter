{{-- Eigenständige Fehlerseite ohne Datenbank, Session, Vite oder Layout-Komponenten (für 500/503).
     Variablen: $code, $title, $text --}}
@php
    $logo = '';
    try {
        $logo = preg_replace(['/^<\?xml[^>]*>\s*/', '/<title[^>]*>.*?<\/title>/s', '/\srole="img"|\saria-labelledby="[^"]*"/'], '',
            (string) @file_get_contents(public_path('images/brand/armory-falter-logo-light.svg')));
    } catch (\Throwable) {
    }
    $company = config('shop.company', []);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} – Armory Falter</title>
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0e0e0e; color: #f2f2f2;
            font: 16px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; padding: 24px; }
        main { max-width: 560px; text-align: center; }
        .logo svg { width: 200px; height: auto; }
        .code { margin-top: 32px; color: #e0786f; font-weight: 600; letter-spacing: .15em; text-transform: uppercase; font-size: 13px; }
        h1 { margin: 8px 0 0; font-size: clamp(24px, 5vw, 34px); text-transform: uppercase; line-height: 1.2; }
        .rule { width: 64px; height: 3px; background: #9d4640; margin: 16px auto 0; }
        p { color: #c4c4c4; }
        a.btn { display: inline-block; margin-top: 16px; padding: 12px 24px; background: #9d4640; color: #fff; text-decoration: none; font-weight: 600; border-radius: 4px; }
        a.btn:hover, a.btn:focus-visible { background: #b0524b; }
        .contact { margin-top: 32px; font-size: 14px; color: #a8a8a8; }
        .contact a { color: #e0786f; }
    </style>
</head>
<body>
<main>
    <div class="logo" aria-hidden="true">{!! $logo !!}</div>
    <p class="code">Fehler {{ $code }}</p>
    <h1>{{ $title }}</h1>
    <div class="rule"></div>
    <p>{{ $text }}</p>
    <a class="btn" href="/">Zur Startseite</a>
    @if (! empty($company['phone']) || ! empty($company['email']))
        <p class="contact">Sie erreichen uns auch direkt:
            @if (! empty($company['phone']))<a href="tel:{{ preg_replace('/\s+/', '', $company['phone']) }}">{{ $company['phone'] }}</a>@endif
            @if (! empty($company['phone']) && ! empty($company['email'])) · @endif
            @if (! empty($company['email']))<a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>@endif
        </p>
    @endif
</main>
</body>
</html>
