<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler (Shared Hosting)
|--------------------------------------------------------------------------
| Auslösen entweder per Cronjob jede Minute:
|     * * * * * php /pfad/zum/projekt/artisan schedule:run >> /dev/null 2>&1
| oder, wenn der Hoster keinen Cron bietet, per externem Cron-Dienst über
|     https://www.armory-falter.de/cron/{CRON_TOKEN}
| Alle Aufgaben laufen bewusst im selben PHP-Prozess (Schedule::call statt Schedule::command):
| so funktionieren sie auch, wenn proc_open/exec gesperrt ist oder der Aufruf über PHP-FPM kommt.
*/

$artisan = fn (string $command, array $params = []) => fn () => Artisan::call($command, $params);

// Warteschlange abarbeiten (Mails, Bildvarianten …), danach beenden. Kein Dauerprozess nötig.
Schedule::call($artisan('queue:work', [
    '--stop-when-empty' => true,
    '--max-time' => (int) config('shop.cron.queue_max_time', 50),
    '--tries' => 3,
    '--backoff' => 30,
]))->name('queue:work --stop-when-empty')->description('Warteschlange abarbeiten')
    ->everyMinute()->withoutOverlapping(10);

// Dokumente: Ablauf markieren, Dateien nach Aufbewahrungsfrist löschen (Löschkonzept)
Schedule::call($artisan('documents:housekeeping'))->name('documents:housekeeping')->description('Kundendokumente aufräumen')
    ->dailyAt('03:15')->withoutOverlapping(60);

// Newsletter-Willkommensstrecke (Tag 1/3/7/14, nur bestätigte Abonnenten)
Schedule::call($artisan('newsletter:welcome'))->name('newsletter:welcome')->description('Newsletter-Willkommensstrecke')
    ->hourlyAt(20)->between('08:00', '20:00')->withoutOverlapping(30);

// Abgelaufene Einträge aus Datei-/Datenbank-Cache entfernen (Ganzseiten-Cache erzeugt viele Schlüssel)
Schedule::call($artisan('cache:prune-expired'))->name('cache:prune-expired')->description('Abgelaufene Cache-Einträge löschen')
    ->hourlyAt(40)->withoutOverlapping(30);

// Integrität des Audit-Logs (Hash-Kette); Fehler landen als CRITICAL im Log
Schedule::call($artisan('audit:verify'))->name('audit:verify')->description('Audit-Log-Hash-Kette prüfen')
    ->dailyAt('04:10')->withoutOverlapping(60);

// Fehlgeschlagene Jobs und Sanctum-Tokens aufräumen
Schedule::call($artisan('queue:prune-failed', ['--hours' => 24 * 14]))->name('queue:prune-failed')->dailyAt('04:30');
Schedule::call($artisan('sanctum:prune-expired', ['--hours' => 24]))->name('sanctum:prune-expired')->dailyAt('04:40');
