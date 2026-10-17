<div class="container-af max-w-3xl py-16 text-center">
    <div class="mx-auto grid size-16 place-items-center rounded-full bg-brand/15"><x-icon name="check" class="size-8 text-accent"/></div>
    <h1 class="mt-6 font-display text-3xl font-extrabold uppercase">Vielen Dank für Ihre Bestellung</h1>
    <p class="mt-3 text-muted">Bestellnummer <span class="font-semibold text-fg">{{ $order->order_number }}</span>. Eine Bestätigung ist unterwegs an Ihre E-Mail-Adresse.</p>

    <div class="card mt-8 p-6 text-left text-sm">
        @if ($order->requiresCompliance())
            <p class="font-display text-lg font-bold">So geht es weiter</p>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-muted">
                <li>Unsere Waffensachbearbeitung prüft Ihre Erwerbsberechtigung.</li>
                <li>Nach der Freigabe erhalten Sie die Zahlungsaufforderung per E-Mail.</li>
                <li>Versand mit persönlicher Übergabe oder Abholung im Geschäft, gegen Vorlage Ihrer Erlaubnis im Original.</li>
            </ol>
        @elseif ($order->payment_method_code === \App\Enums\PaymentMethod::GiftVoucher)
            <p class="font-display text-lg font-bold">Vollständig per Geschenkgutschein bezahlt</p>
            <p class="mt-2 text-muted">Eine weitere Zahlung ist nicht nötig. Wir bereiten Ihre Bestellung vor.</p>
        @elseif ($order->payment_method_code === \App\Enums\PaymentMethod::Prepayment)
            <p class="font-display text-lg font-bold">Bitte überweisen Sie {{ \App\Support\Money::format($order->amountDue()) }}</p>
            <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                <dt class="text-muted">Empfänger</dt><dd>{{ $bank['holder'] }}</dd>
                <dt class="text-muted">IBAN</dt><dd class="font-mono">{{ $bank['iban'] }}</dd>
                <dt class="text-muted">BIC</dt><dd class="font-mono">{{ $bank['bic'] }}</dd>
                <dt class="text-muted">Verwendungszweck</dt><dd class="font-semibold">{{ $order->order_number }}</dd>
            </dl>
        @else
            <p>Wir bereiten Ihre Bestellung vor und informieren Sie über den Versand.</p>
        @endif
    </div>
    <div class="mt-8 flex justify-center gap-3"><a href="{{ route('account.orders.show', $order) }}" class="btn-primary">Bestellung ansehen</a><a href="{{ route('shop.index') }}" class="btn-outline">Weiter einkaufen</a></div>
</div>
