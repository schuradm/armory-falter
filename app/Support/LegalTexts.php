<?php

namespace App\Support;

/** Textbausteine für Rechtstexte, die Seeder und Datenmigrationen gemeinsam nutzen. */
final class LegalTexts
{
    /** Datenschutzerklärung, Abschnitt Newsletter und Waffenratgeber (Leadmagnet). */
    public static function newsletter(callable $todo): string
    {
        return <<<HTML
<p>Sie können unseren Newsletter abonnieren und erhalten als Dankeschön unseren Waffenratgeber als PDF. Pflichtangabe ist nur Ihre E-Mail-Adresse. Freiwillig können Sie Ihren Vornamen und Ihre Interessen (zum Beispiel Jagd, Sport, Optik) angeben, damit wir die Inhalte passend auswählen.</p>
<p><strong>Rechtsgrundlage</strong> ist Ihre Einwilligung (Art. 6 Abs. 1 lit. a DSGVO, § 7 Abs. 2 UWG). Den Waffenratgeber stellen wir ausschließlich im Zusammenhang mit der Newsletter-Anmeldung bereit. Darauf weisen wir im Anmeldeformular ausdrücklich hin.</p>
<p><strong>Double-Opt-in:</strong> Nach der Anmeldung erhalten Sie eine E-Mail mit einem Bestätigungslink, der 7 Tage gültig ist. Erst nach Ihrer Bestätigung senden wir Newsletter und stellen den Download-Link zum Ratgeber bereit. Der Download-Link ist 30 Tage gültig. Ohne Bestätigung verwenden wir Ihre Adresse nicht für den Versand.</p>
<p><strong>Protokollierung:</strong> Um Ihre Einwilligung nachweisen zu können (Art. 7 Abs. 1 DSGVO), speichern wir den Zeitpunkt der Anmeldung und der Bestätigung, die Quelle der Anmeldung (Newsletter-Formular oder Waffenratgeber) sowie einen verschlüsselten Prüfwert (Hash) Ihrer IP-Adresse. Die IP-Adresse selbst speichern wir nicht. Rechtsgrundlage ist unser berechtigtes Interesse an einem rechtssicheren Nachweis (Art. 6 Abs. 1 lit. f DSGVO).</p>
<p><strong>Inhalte und Erfolgsmessung:</strong> Nach der Bestätigung erhalten Sie eine kurze Willkommensserie aus vier E-Mails innerhalb von 14 Tagen, danach regelmäßig Angebote, Neuheiten, Veranstaltungen und Fachartikel. Unsere Newsletter enthalten ein Zählpixel, mit dem wir erfassen, ob und wann eine E-Mail geöffnet wurde. Die Auswertung dient dazu, unsere Inhalte zu verbessern, und ist Teil Ihrer Einwilligung.</p>
<p><strong>Versand:</strong> Der Versand erfolgt über den E-Mail-Server unseres Hosting-Anbieters. {$todo('Hosting- und E-Mail-Anbieter mit Anschrift nennen, Auftragsverarbeitungsvertrag nach Art. 28 DSGVO abschließen')}</p>
<p><strong>Widerruf und Abmeldung:</strong> Sie können Ihre Einwilligung jederzeit mit Wirkung für die Zukunft widerrufen, über den Abmeldelink am Ende jeder E-Mail oder per Nachricht an uns. Die Rechtmäßigkeit der bis dahin erfolgten Verarbeitung bleibt unberührt.</p>
<p><strong>Speicherdauer:</strong> Wir speichern Ihre Daten, solange Ihr Abonnement besteht. Nach der Abmeldung bewahren wir E-Mail-Adresse und Einwilligungsnachweis bis zu drei Jahre auf, um eine früher erteilte Einwilligung belegen zu können. Für Werbung nutzen wir die Adresse dann nicht mehr. {$todo('Speicherdauer anwaltlich bestätigen')}</p>
HTML;
    }
}
