<?php

namespace App\Console\Commands;

use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;

/**
 * Löschkonzept Newsletter (siehe Datenschutzerklärung, Abschnitt 7):
 * - nie bestätigte Anmeldungen nach 30 Tagen löschen
 * - Abgemeldete: Zusatzangaben sofort entfernen, E-Mail als Sperrliste höchstens 3 Jahre aufbewahren
 */
class NewsletterPrune extends Command
{
    protected $signature = 'newsletter:prune';

    protected $description = 'Unbestätigte und lange abgemeldete Newsletter-Adressen löschen';

    public function handle(): int
    {
        $pending = NewsletterSubscriber::where('status', 'pending')->where('created_at', '<', now()->subDays(30))->delete();

        $stripped = NewsletterSubscriber::where('status', 'unsubscribed')
            ->where(fn ($q) => $q->whereNotNull('first_name')->orWhereNotNull('interests'))
            ->update(['first_name' => null, 'interests' => null]);

        $expired = NewsletterSubscriber::where('status', 'unsubscribed')->where('unsubscribed_at', '<', now()->subYears(3))->delete();

        $this->info("Unbestätigt gelöscht: {$pending}, Zusatzangaben entfernt: {$stripped}, Sperrliste abgelaufen: {$expired}");

        return self::SUCCESS;
    }
}
