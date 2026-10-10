<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Unsichtbares Feld: Bots füllen es aus, Menschen nicht. */
class Honeypot implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value)) {
            $fail('Ungültige Anfrage.');
        }
    }
}
