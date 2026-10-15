<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Entfernt abgelaufene Cache-Einträge. Datei- und Datenbank-Cache löschen abgelaufene Einträge nur beim
 * Lesen desselben Schlüssels; der Ganzseiten-Cache erzeugt nach jedem Flush neue Schlüssel, alte bleiben liegen.
 */
class PruneExpiredCache extends Command
{
    protected $signature = 'cache:prune-expired {--store= : Cache-Store (Standard: cache.default)}';

    protected $description = 'Löscht abgelaufene Einträge aus Datei- oder Datenbank-Cache';

    public function handle(): int
    {
        $store = $this->option('store') ?: config('cache.default');
        $config = config("cache.stores.{$store}");
        $driver = $config['driver'] ?? null;

        $deleted = match ($driver) {
            'file' => $this->pruneFiles($config['path']),
            'database' => DB::connection($config['connection'] ?? null)->table($config['table'] ?? 'cache')
                ->where('expiration', '<=', time())->delete(),
            default => null,
        };

        if ($deleted === null) {
            $this->line("Store „{$store}“ ({$driver}) räumt selbst auf, nichts zu tun.");
        } else {
            $this->info("Abgelaufene Cache-Einträge gelöscht: {$deleted}");
        }

        return self::SUCCESS;
    }

    private function pruneFiles(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }
        $deleted = 0;
        $now = time();
        foreach (File::allFiles($path) as $file) {
            // Dateiformat des FileStore: die ersten 10 Zeichen sind der Ablaufzeitpunkt (Unix-Zeit)
            $handle = @fopen($file->getPathname(), 'r');
            if (! $handle) {
                continue;
            }
            $expires = (int) fread($handle, 10);
            fclose($handle);
            if ($expires > 0 && $expires <= $now && @unlink($file->getPathname())) {
                $deleted++;
            }
        }
        foreach (array_reverse(File::directories($path)) as $dir) {
            $this->removeEmptyDirs($dir);
        }

        return $deleted;
    }

    private function removeEmptyDirs(string $dir): void
    {
        foreach (File::directories($dir) as $sub) {
            $this->removeEmptyDirs($sub);
        }
        if (count(scandir($dir)) === 2) {
            @rmdir($dir);
        }
    }
}
