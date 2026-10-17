<?php

namespace App\Services\Vouchers;

use App\Enums\OrderStatus;
use App\Mail\GiftVoucherMail;
use App\Models\GiftVoucher;
use App\Models\Order;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Orders\OrderService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Geschenkgutscheine als Mehrzweck-Gutscheine (§ 3 Abs. 15 UStG): Beim Verkauf steht noch nicht fest, welche
 * Ware (19 % oder 7 %) damit bezahlt wird, deshalb keine Umsatzsteuer beim Verkauf. Die Steuer entsteht bei Einlösung
 * auf die gelieferte Ware; der Gutschein ist dort ein Zahlungsmittel und mindert den Zahlbetrag, nicht das Entgelt.
 *
 * Ablauf: Kauf → nach Zahlungseingang Code erzeugen, PDF im Shop-CI, Versand per E-Mail →
 * Einlösung wie eine Zahlung (Teileinlösung, Restguthaben bleibt) → Storno bucht zurück.
 */
class GiftVoucherService
{
    /** Ohne leicht verwechselbare Zeichen (0/O, 1/I/L). */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(private AuditLogger $audit) {}

    public function find(string $input): ?GiftVoucher
    {
        $code = GiftVoucher::normalize($input);

        return strlen($code) === 16 ? GiftVoucher::where('code', $code)->first() : null;
    }

    /** @throws VoucherException */
    public function assertRedeemable(GiftVoucher $voucher): void
    {
        if ($voucher->status === 'cancelled') {
            throw new VoucherException('Dieser Gutschein ist gesperrt. Bitte wenden Sie sich an uns.');
        }
        if ($voucher->isExpired()) {
            throw new VoucherException('Dieser Gutschein ist am '.$voucher->expires_at->format('d.m.Y').' abgelaufen.');
        }
        if ($voucher->balance <= 0 || $voucher->status === 'redeemed') {
            throw new VoucherException('Dieser Gutschein ist bereits vollständig eingelöst.');
        }
    }

    /**
     * Regelmäßige Verjährung (§§ 195, 199 BGB): drei Jahre ab Ende des Jahres, in dem der Gutschein gekauft wurde.
     * Kauf am 17.10.2026 → gültig bis 31.12.2029. Eine kürzere Frist wäre in AGB regelmäßig unwirksam (§ 307 BGB).
     */
    public static function expiryFor(Carbon $issuedAt): Carbon
    {
        return $issuedAt->copy()->addYears(3)->endOfYear()->startOfDay();
    }

    public function generateCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 16; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (GiftVoucher::where('code', $code)->exists());

        return $code;
    }

    /**
     * Nach Zahlungseingang: je gekaufter Gutscheinposition (× Menge) einen Code erzeugen und versenden.
     * Idempotent (Webhook und Rückleitung können beide „bezahlt“ melden). Reine Gutscheinbestellungen sind danach erledigt.
     *
     * @return list<GiftVoucher> neu ausgegebene Gutscheine
     */
    public function issueFor(Order $order): array
    {
        $order->loadMissing('items', 'user');
        $issued = [];

        foreach ($order->items->filter->isGiftVoucher() as $item) {
            $missing = $item->quantity - GiftVoucher::where('order_item_id', $item->id)->count();
            for ($n = 0; $n < $missing; $n++) {
                $issued[] = $this->create($item->unit_price_gross, $order->user, [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'recipient_name' => $item->options['recipient_name'] ?? null,
                    'recipient_email' => $item->options['recipient_email'] ?? null,
                    'message' => $item->options['message'] ?? null,
                ], "Kauf mit Bestellung {$order->order_number}");
            }
        }

        foreach ($issued as $voucher) {
            $this->send($voucher);
        }

        $digitalOnly = $order->items->isNotEmpty() && $order->items->every->isGiftVoucher();
        if ($issued && $digitalOnly && in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing], true)) {
            app(OrderService::class)->transition($order, OrderStatus::Completed, null,
                'Gutschein per E-Mail versendet.', notify: false); // die Gutscheinmail selbst ist die Nachricht
        }

        return $issued;
    }

    /** Gutschein anlegen (Kauf im Shop oder manuell im Backend, z. B. Verkauf im Laden). */
    public function create(int $value, ?User $purchaser, array $attributes = [], ?string $note = null, ?User $by = null): GiftVoucher
    {
        return DB::transaction(function () use ($value, $purchaser, $attributes, $note, $by) {
            $voucher = GiftVoucher::create($attributes + [
                'code' => $this->generateCode(),
                'initial_value' => $value,
                'balance' => $value,
                'status' => 'active',
                'purchaser_id' => $purchaser?->id,
                'expires_at' => self::expiryFor(now()),
            ]);
            $voucher->transactions()->create(['type' => 'issue', 'amount' => $value, 'balance_after' => $value, 'note' => $note, 'created_by' => $by?->id]);
            $this->audit->log('gift_voucher.issued', $voucher, new: ['value' => $value, 'order_id' => $voucher->order_id]);

            return $voucher;
        });
    }

    public function renderPdf(GiftVoucher $voucher): string
    {
        return Pdf::loadView('pdf.gift-voucher', ['voucher' => $voucher, 'company' => config('shop.company')])->output();
    }

    /** PDF ablegen (privat) und per E-Mail an Beschenkten (Käufer in Kopie) bzw. Käufer senden. */
    public function send(GiftVoucher $voucher): void
    {
        $path = "gift-vouchers/{$voucher->created_at->year}/gutschein-{$voucher->id}.pdf";
        Storage::disk('local')->put($path, $this->renderPdf($voucher));
        $voucher->update(['pdf_path' => $path]);

        $buyer = $voucher->purchaser?->email;
        $to = $voucher->recipient_email ?: $buyer;
        if (! $to) {
            return; // im Laden verkauft: PDF wird im Backend ausgedruckt
        }
        $mail = Mail::to($to);
        if ($voucher->recipient_email && $buyer && strcasecmp($buyer, $voucher->recipient_email) !== 0) {
            $mail->cc($buyer);
        }
        $mail->queue(new GiftVoucherMail($voucher));
        $voucher->update(['sent_at' => now()]);
    }

    /**
     * Einlösung beim Bestellen, innerhalb der Bestell-Transaktion: Guthaben sperren, abbuchen, als Zahlung erfassen.
     *
     * @param  array<string,int>  $amounts  Code => anzurechnender Betrag (aus TotalsCalculator)
     *
     * @throws VoucherException
     */
    public function redeem(Order $order, array $amounts): void
    {
        foreach ($amounts as $code => $amount) {
            $voucher = GiftVoucher::where('code', $code)->lockForUpdate()->first()
                ?? throw new VoucherException('Ein Gutschein ist nicht mehr gültig. Bitte prüfen Sie Ihre Eingabe.');
            $this->assertRedeemable($voucher);
            if ($voucher->balance < $amount) {
                throw new VoucherException('Das Guthaben des Gutscheins '.$voucher->formattedCode().' hat sich geändert. Bitte prüfen Sie den Betrag.');
            }
            $voucher->balance -= $amount;
            $voucher->status = $voucher->balance === 0 ? 'redeemed' : 'active';
            $voucher->save();
            $voucher->transactions()->create(['order_id' => $order->id, 'type' => 'redeem', 'amount' => -$amount, 'balance_after' => $voucher->balance]);
            $order->payments()->create([
                'provider' => 'gift_voucher',
                'provider_reference' => $voucher->code.'-'.$order->order_number,
                'status' => 'captured',
                'amount' => $amount,
            ]);
        }
    }

    /** Storno: eingelöstes Guthaben zurückbuchen. Mehrfachaufruf ist harmlos. */
    public function release(Order $order, ?User $by = null): void
    {
        foreach ($order->giftVoucherTransactions()->where('type', 'redeem')->get() as $tx) {
            $already = $order->giftVoucherTransactions()->where('type', 'release')->where('gift_voucher_id', $tx->gift_voucher_id)->exists();
            if ($already) {
                continue;
            }
            $voucher = GiftVoucher::lockForUpdate()->find($tx->gift_voucher_id);
            $voucher->balance += -$tx->amount;
            if ($voucher->status === 'redeemed') {
                $voucher->status = 'active';
            }
            $voucher->save();
            $voucher->transactions()->create(['order_id' => $order->id, 'type' => 'release', 'amount' => -$tx->amount,
                'balance_after' => $voucher->balance, 'note' => "Storno {$order->order_number}", 'created_by' => $by?->id]);
        }
        $order->payments()->where('provider', 'gift_voucher')->where('status', 'captured')->update(['status' => 'refunded']);
    }

    /**
     * Storno einer Bestellung, mit der Gutscheine gekauft wurden: unbenutzte Gutscheine sperren.
     * Bereits (teil-)eingelöste bleiben gültig; das meldet die Rückgabe, damit das Personal die Erstattung anpasst.
     *
     * @return list<string> Hinweise für den Storno-Vermerk
     */
    public function cancelIssued(Order $order, ?User $by = null): array
    {
        $notes = [];
        foreach ($order->issuedGiftVouchers()->lockForUpdate()->get() as $voucher) {
            if ($voucher->status === 'cancelled') {
                continue;
            }
            if ($voucher->balance < $voucher->initial_value) {
                $notes[] = 'Gutschein '.$voucher->formattedCode().' ist bereits teilweise eingelöst (Rest '.Money::format($voucher->balance).') und bleibt gültig.';

                continue;
            }
            $voucher->update(['status' => 'cancelled']);
            $voucher->transactions()->create(['order_id' => $order->id, 'type' => 'cancel', 'amount' => 0, 'balance_after' => $voucher->balance,
                'note' => "Storno {$order->order_number}", 'created_by' => $by?->id]);
        }

        return $notes;
    }
}
