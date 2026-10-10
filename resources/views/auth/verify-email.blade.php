@php(app(\App\Support\Seo::class)->title('E-Mail bestätigen')->noindex())
<x-auth-card title="E-Mail bestätigen" subtitle="Wir haben Ihnen einen Bestätigungslink geschickt. Bitte prüfen Sie auch den Spam-Ordner.">
    @if (session('status') === 'verification-link-sent')<p class="mb-4 text-sm text-emerald-400">Ein neuer Link wurde gesendet.</p>@endif
    <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn-primary w-full">Link erneut senden</button></form>
</x-auth-card>
