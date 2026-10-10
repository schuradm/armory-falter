<?php

namespace App\Notifications;

use App\Enums\DocumentStatus;
use App\Models\CustomerDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomerDocument $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->document->status === DocumentStatus::Approved;
        $mail = (new MailMessage)
            ->subject($approved ? 'Ihr Dokument wurde geprüft und freigegeben' : 'Ihr Dokument konnte nicht freigegeben werden')
            ->greeting("Hallo {$notifiable->first_name},")
            ->line("wir haben Ihr Dokument „{$this->document->type->label()}“ geprüft.");

        if ($approved) {
            $mail->line('Es wurde freigegeben. Die zugehörigen Artikel können Sie jetzt bestellen.');
        } else {
            $mail->line('Leider konnten wir es nicht freigeben. Grund: '.$this->document->rejection_reason)
                ->line('Bitte laden Sie ein aktuelles, gut lesbares Dokument hoch.');
        }

        return $mail->action('Zu meinen Dokumenten', route('account.documents'))
            ->salutation('Ihr Team von Armory Falter');
    }
}
