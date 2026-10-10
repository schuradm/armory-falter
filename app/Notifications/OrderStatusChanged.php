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

    public function __construct(public Order $order, public ?string $comment = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order;
        $mail = (new MailMessage)
            ->subject("Bestellung {$o->order_number}: {$o->status->label()}")
            ->greeting("Hallo {$notifiable->first_name},")
            ->line("der Status Ihrer Bestellung {$o->order_number} hat sich geändert: **{$o->status->label()}**.");

        if ($this->comment) {
            $mail->line($this->comment);
        }
        if ($o->status === OrderStatus::PendingPayment && $o->payment_method_code === PaymentMethod::Prepayment) {
            $bank = config('shop.company.bank');
            $mail->line('Bitte überweisen Sie jetzt '.\App\Support\Money::format($o->total_gross).' an:')
                ->line("{$bank['holder']} · IBAN {$bank['iban']} · BIC {$bank['bic']}")
                ->line("Verwendungszweck: {$o->order_number}");
        }

        return $mail->action('Bestellung ansehen', route('account.orders.show', $o))->salutation('Ihr Team von Armory Falter');
    }
}
