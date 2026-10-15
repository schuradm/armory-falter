<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::updateOrCreate(['name' => 'Regelsteuersatz 19 %'], ['rate' => 19.00]);
        TaxRate::updateOrCreate(['name' => 'Ermäßigt 7 %'], ['rate' => 7.00]);
    }
}
