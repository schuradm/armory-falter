<?php

namespace App\Support;

/**
 * HMAC-Index für verschlüsselte Felder (Dokument-, Seriennummern): erlaubt exakte Suche,
 * ohne den Klartext in der Datenbank zu speichern.
 */
final class BlindIndex
{
    public static function make(string $value): string
    {
        $normalized = strtoupper(preg_replace('/[\s\-\.\/]/', '', $value));

        return hash_hmac('sha256', $normalized, config('shop.blind_index_key') ?: config('app.key'));
    }
}
