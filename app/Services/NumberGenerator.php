<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Lückenlose, fortlaufende Nummern (Bestellungen, Rechnungen nach GoBD, Anfragen). */
class NumberGenerator
{
    public function next(string $type, string $prefix, int $pad = 6): string
    {
        $year = now()->year;
        $key = "{$type}:{$year}";

        $value = DB::transaction(function () use ($key) {
            DB::table('number_sequences')->insertOrIgnore(['key' => $key, 'value' => 0]);
            $current = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->value('value');
            DB::table('number_sequences')->where('key', $key)->update(['value' => $current + 1]);

            return $current + 1;
        });

        return sprintf('%s-%d-%s', $prefix, $year, str_pad((string) $value, $pad, '0', STR_PAD_LEFT));
    }
}
