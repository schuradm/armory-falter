<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Prüft die Hash-Kette des Audit-Logs: jeder Eintrag muss seinen eigenen Hash reproduzieren und
 * auf den Hash des Vorgängers verweisen. Exit-Code 1 bei Manipulation oder Lücke (auch für Monitoring).
 */
class VerifyAuditLog extends Command
{
    protected $signature = 'audit:verify {--from=0 : Ab dieser ID prüfen (Vorgänger wird als Anker genutzt)}';

    protected $description = 'Prüft die Integrität der Hash-Kette im Audit-Log';

    public function handle(): int
    {
        $from = (int) $this->option('from');
        $prev = $from > 0 ? AuditLog::where('id', '<', $from)->orderByDesc('id')->value('hash') : null;
        $checked = 0;
        $errors = [];

        AuditLog::query()->where('id', '>=', $from)->orderBy('id')->chunkById(1000, function ($logs) use (&$prev, &$checked, &$errors) {
            foreach ($logs as $log) {
                $checked++;
                $payload = [
                    'actor_id' => $log->actor_id,
                    'actor_type' => $log->actor_type,
                    'action' => $log->action,
                    'subject_type' => $log->subject_type,
                    'subject_id' => $log->subject_id,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'prev_hash' => $log->prev_hash,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s.v'),
                ];
                if ($log->prev_hash !== $prev) {
                    $errors[] = "#{$log->id}: Verkettung gebrochen (prev_hash passt nicht zum Vorgänger)";
                }
                if (! hash_equals($log->hash, AuditLogger::hash($payload))) {
                    $errors[] = "#{$log->id}: Hash ungültig (Eintrag wurde verändert)";
                }
                $prev = $log->hash;
            }
        });

        if ($errors) {
            foreach (array_slice($errors, 0, 50) as $e) {
                $this->error($e);
            }
            Log::critical('Audit-Log-Prüfung fehlgeschlagen', ['errors' => count($errors), 'first' => $errors[0]]);
            $this->error(count($errors)." Fehler in {$checked} Einträgen.");

            return self::FAILURE;
        }

        $this->info("Audit-Log in Ordnung: {$checked} Einträge geprüft.");

        return self::SUCCESS;
    }
}
