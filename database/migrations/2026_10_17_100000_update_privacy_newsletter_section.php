<?php

use App\Models\Page;
use App\Support\LegalTexts;
use Illuminate\Database\Migrations\Migration;

/** Bestehende Installationen: Platzhalter im Datenschutz-Abschnitt „Newsletter“ durch den ausformulierten Text ersetzen. */
return new class extends Migration
{
    public function up(): void
    {
        $page = Page::where('slug', 'datenschutz')->first();
        if (! $page) {
            return;
        }
        $todo = fn (string $text) => '<mark>[PLATZHALTER: '.$text.']</mark>';
        $old = "<h2>7. Newsletter (Double-Opt-in)</h2>\n<p>".$todo('Einwilligung, Protokollierung, Abmeldung, Öffnungsmessung').'</p>';
        if (str_contains($page->body, $old)) {
            $page->update(['body' => str_replace($old, "<h2>7. Newsletter und Waffenratgeber (Double-Opt-in)</h2>\n".LegalTexts::newsletter($todo), $page->body)]);
        }
    }

    public function down(): void {}
};
