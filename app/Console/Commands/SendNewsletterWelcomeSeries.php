<?php

namespace App\Console\Commands;

use App\Mail\NewsletterWelcome;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Willkommensstrecke: Schritt 1–4 an Tag 1/3/7/14 nach der Bestätigung (Double-Opt-in).
 * Nur bestätigte, nicht abgemeldete Abonnenten. Pro Lauf höchstens ein Schritt je Abonnent;
 * welcome_step wird vor dem Versand atomar hochgezählt, damit parallele Läufe nichts doppelt senden.
 */
class SendNewsletterWelcomeSeries extends Command
{
    /** Schritt => Tage nach confirmed_at */
    public const SCHEDULE = [1 => 1, 2 => 3, 3 => 7, 4 => 14];

    protected $signature = 'newsletter:welcome {--limit=200 : Höchstzahl E-Mails pro Lauf} {--dry-run : Nur anzeigen, nichts senden}';

    protected $description = 'Versendet die fälligen E-Mails der Newsletter-Willkommensstrecke (Tag 1/3/7/14)';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $sent = 0;
        $handled = []; // höchstens ein Schritt je Abonnent und Lauf

        foreach (self::SCHEDULE as $step => $days) {
            if ($sent >= $limit) {
                break;
            }
            $due = NewsletterSubscriber::query()
                ->where('status', 'confirmed')
                ->whereNull('unsubscribed_at')
                ->whereNotNull('confirmed_at')
                ->where('welcome_step', $step - 1)
                ->where('confirmed_at', '<=', now()->subDays($days))
                ->when($handled, fn ($q) => $q->whereNotIn('id', $handled))
                ->orderBy('id')
                ->limit($limit - $sent)
                ->get();

            foreach ($due as $subscriber) {
                $handled[] = $subscriber->id;
                if ($this->option('dry-run')) {
                    $this->line("Schritt {$step} → {$subscriber->email}");
                    $sent++;

                    continue;
                }
                $claimed = NewsletterSubscriber::whereKey($subscriber->id)->where('welcome_step', $step - 1)->update(['welcome_step' => $step]);
                if (! $claimed) {
                    continue; // von einem parallelen Lauf übernommen
                }
                Mail::to($subscriber->email)->queue(new NewsletterWelcome($subscriber, $step));
                $sent++;
            }
        }

        $this->info(($this->option('dry-run') ? 'Fällig: ' : 'Eingereiht: ').$sent.' E-Mail(s).');

        return self::SUCCESS;
    }
}
