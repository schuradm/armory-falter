<?php

namespace App\Console\Commands;

use Database\Seeders\DemoProductImageSeeder;
use Illuminate\Console\Command;

/**
 * Spielt nur die Demo-Produktbilder in eine bestehende Datenbank ein (ohne kompletten Reseed).
 * Kopiert die Master-WebPs nach storage/app/public/products/demo und erzeugt die Varianten synchron.
 */
class DemoImages extends Command
{
    protected $signature = 'demo:images';

    protected $description = 'Demo-Produktbilder einspielen und WebP-Varianten erzeugen (idempotent)';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => DemoProductImageSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
