{{-- Code-Eingabe und angewendete Codes. Variablen: $cart, $totals (App\Services\Checkout\Totals), $codeNotices --}}
<div class="space-y-2">
    <form wire:submit="applyCode" class="flex gap-2">
        <label for="voucher-code" class="sr-only">Gutschein- oder Rabattcode</label>
        <input id="voucher-code" type="text" wire:model="voucherCode" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40"
               class="input py-2 text-sm uppercase placeholder:normal-case" placeholder="Gutschein- oder Rabattcode"
               @error('voucherCode') aria-invalid="true" aria-describedby="voucher-code-error" @enderror>
        <button type="submit" class="btn-outline shrink-0 py-2 text-sm" wire:loading.attr="disabled" wire:target="applyCode">Einlösen</button>
    </form>
    @error('voucherCode')<p id="voucher-code-error" class="text-xs text-red-400" role="alert">{{ $message }}</p>@enderror
    @if ($codeMessage)<p class="text-xs text-emerald-400" role="status">{{ $codeMessage }}</p>@endif
    @foreach ($codeNotices as $notice)<p class="text-xs text-amber-400" role="status">{{ $notice }}</p>@endforeach
    @if ($totals->discountCode)
        <p class="flex items-center justify-between gap-2 text-xs"><span class="badge bg-surface-2">{{ $totals->discountCode->code }} · {{ $totals->discountCode->label() }}</span>
            <button type="button" wire:click="removeDiscountCode" class="text-muted underline hover:text-fg">Entfernen<span class="sr-only"> Rabattcode {{ $totals->discountCode->code }}</span></button></p>
    @endif
    @foreach ($cart->gift_voucher_codes ?? [] as $code)
        <p class="flex items-center justify-between gap-2 text-xs"><span class="badge bg-surface-2">Gutschein …{{ substr($code, -4) }}{{ isset($totals->giftVouchers[$code]) ? '' : ' · wird nicht benötigt' }}</span>
            <button type="button" wire:click="removeGiftVoucher('{{ $code }}')" class="text-muted underline hover:text-fg">Entfernen<span class="sr-only"> Gutschein …{{ substr($code, -4) }}</span></button></p>
    @endforeach
</div>
