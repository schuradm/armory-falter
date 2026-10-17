<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

/**
 * Erzeugt den Leadmagnet „Der große Waffenratgeber“ als PDF (resources/views/pdf/ratgeber.blade.php).
 * Die Datei liegt im Repo und wird ausgeliefert, solange im Backend kein eigenes PDF hochgeladen ist.
 */
class BuildRatgeber extends Command
{
    public const FILE = 'downloads/armory-falter-waffenratgeber.pdf';

    protected $signature = 'ratgeber:pdf';

    protected $description = 'Waffenratgeber-PDF im CI neu erzeugen (resources/downloads)';

    public function handle(): int
    {
        $pdf = Pdf::loadView('pdf.ratgeber', [
            'company' => config('shop.company'),
            'year' => now()->year,
            'stand' => now()->locale('de')->isoFormat('MMMM YYYY'),
        ])->setPaper('a4')->output();

        file_put_contents(resource_path(self::FILE), $pdf);
        $this->info('Ratgeber erzeugt: resources/'.self::FILE.' ('.round(strlen($pdf) / 1024).' KB)');

        return self::SUCCESS;
    }
}
