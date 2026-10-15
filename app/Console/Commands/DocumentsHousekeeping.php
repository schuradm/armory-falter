<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use App\Services\Documents\DocumentService;
use Illuminate\Console\Command;

/** Täglich: abgelaufene Dokumente/Berechtigungen markieren und Dateien nach Ablauf der Aufbewahrungsfrist löschen. */
class DocumentsHousekeeping extends Command
{
    protected $signature = 'documents:housekeeping';

    protected $description = 'Markiert abgelaufene Kundendokumente und löscht fällige Dateien (Löschkonzept)';

    public function handle(DocumentService $documents, AuditLogger $audit): int
    {
        $result = $documents->housekeeping();
        if ($result['expired'] > 0 || $result['files_deleted'] > 0) {
            $audit->log('documents.housekeeping', new: $result, actorType: 'system');
        }
        $this->info("Abgelaufen markiert: {$result['expired']}, Dateien gelöscht: {$result['files_deleted']}");

        return self::SUCCESS;
    }
}
