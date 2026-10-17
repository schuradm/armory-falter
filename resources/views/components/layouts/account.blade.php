@props(['active' => 'dashboard'])
@php
    $nav = [
        'dashboard' => ['Übersicht', 'account.dashboard', 'user'],
        'orders' => ['Bestellungen & Versand', 'account.orders', 'truck'],
        'invoices' => ['Rechnungen', 'account.invoices', 'euro'],
        'documents' => ['Dokumente & WBK', 'account.documents', 'shield'],
        'wishlists' => ['Merklisten', 'account.wishlists', 'heart'],
        'downloads' => ['Downloads', 'account.downloads', 'download'],
        'appointments' => ['Termine', 'account.appointments', 'calendar'],
        'requests' => ['Verkauf & Ankauf', 'account.requests', 'euro'],
        'profile' => ['Persönliche Daten', 'account.profile', 'user'],
        'security' => ['Sicherheit & 2FA', 'account.security', 'lock'],
        'privacy' => ['Datenschutz', 'account.privacy', 'eye'],
    ];
@endphp
<x-layouts.app>
    <div class="container-af grid gap-8 py-10 lg:grid-cols-[240px_1fr]">
        <aside>
            <p class="eyebrow">Kundenkonto</p>
            <p class="mt-1 font-display text-lg font-bold">{{ auth()->user()->name }}</p>
            <p class="text-xs text-muted">Kundennr. {{ auth()->user()->customer_number }}</p>
            <nav class="mt-5 flex gap-1 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible" aria-label="Kontonavigation">
                @foreach ($nav as $key => [$label, $route, $icon])
                    <a href="{{ route($route) }}" @class(['flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm', 'bg-surface text-accent' => $active === $key, 'text-muted hover:text-fg' => $active !== $key])
                       @if($active === $key) aria-current="page" @endif><x-icon :name="$icon" class="size-4"/>{{ $label }}</a>
                @endforeach
                @if (auth()->user()->is_staff)
                    <a href="{{ url(filament()->getPanel('admin')->getPath()) }}" class="flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm text-accent"><x-icon name="wrench" class="size-4"/>Admin-Bereich</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm text-muted hover:text-fg"><x-icon name="logout" class="size-4"/>Abmelden</button></form>
            </nav>
        </aside>
        <div class="min-w-0">{{ $slot }}</div>
    </div>
</x-layouts.app>
