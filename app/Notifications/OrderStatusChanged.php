<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** Status zum Zeitpunkt des Wechsels: die Queue lädt die Bestellung frisch, sie kann inzwischen weiter sein. */
    public OrderStatus $status;

    public function __construct(public Order $order, public ?string $comment = null)
    {
        $this->status = $order->status;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order;
        $mail = (new MailMessage)
            ->subject("Bestellung {$o->order_number}: {$this->status->label()}")
            ->greeting("Hallo {$notifiable->first_name},")
            ->line("der Status Ihrer Bestellung {$o->order_number} hat sich geändert: **{$this->status->label()}**.");

        if ($this->comment) {
            $mail->line($this->comment);
        }
        if ($this->status === OrderStatus::PendingPayment && $o->payment_method_code === PaymentMethod::Prepayment) {
            $bank = config('shop.company.bank');
            $mail->line('Bitte überweisen Sie jetzt '.\App\Support\Money::format($o->amountDue()).' an:')
                ->line("{$bank['holder']} · IBAN {$bank['iban']}".(filled($bank['bic'] ?? null) ? " · BIC {$bank['bic']}" : ''))
                ->line("Verwendungszweck: {$o->order_number}");
        }

        return $mail->action('Bestellung ansehen', route('account.orders.show', $o))->salutation('Ihr Team von Armory Falter');
    }
}
