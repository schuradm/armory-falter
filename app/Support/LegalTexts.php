<?php

namespace App\Support;

/** Textbausteine für Rechtstexte, die Seeder und Datenmigrationen gemeinsam nutzen. */
final class LegalTexts
{
    /** Firmenangaben aus config/shop.php (Anbieter laut WSSA-Impressum: Fa. MPF, Inh. Mark Falter). */
    private static function c(): array
    {
        $c = config('shop.company');
        $c = array_map(fn ($v) => is_string($v) ? e($v) : $v, $c);
        $c['legal'] = e(config('shop.company.legal_name', 'Fa. MPF'));
        $c['privacy_email'] = e(config('shop.company.privacy_email') ?: config('shop.company.email'));

        return $c;
    }

    private static function address(): string
    {
        $c = self::c();

        return "{$c['name']}<br>{$c['legal']}, Inhaber {$c['owner']}<br>{$c['street']}<br>{$c['postal_code']} {$c['city']}<br>Deutschland";
    }

    public static function impressum(callable $todo): string
    {
        $c = self::c();
        $address = self::address();
        $vat = filled(config('shop.company.vat_id')) && config('shop.company.vat_id') !== 'DE…'
            ? 'Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG: '.$c['vat_id']
            : $todo('USt-IdNr. eintragen. Hinweis: Die WSSA nutzt die Kleinunternehmerregelung (§ 19 UStG). Für den Waffenhandel mit Ausweis der Umsatzsteuer auf Rechnungen muss das mit dem Steuerberater geklärt werden.');

        return <<<HTML
<h2>Angaben gemäß § 5 Digitale-Dienste-Gesetz (DDG)</h2>
<p>{$address}</p>
<p>Vertreten durch: {$c['owner']}</p>
<h2>Kontakt</h2>
<p>Telefon: {$c['phone']}<br>E-Mail: <a href="mailto:{$c['email']}">{$c['email']}</a><br>Website: {$c['web']}</p>
<h2>Register und Umsatzsteuer</h2>
<p>Handelsregister: {$todo('Eintrag, falls vorhanden, sonst Zeile entfernen')}<br>{$vat}</p>
<h2>Waffenrechtliche Erlaubnis und Aufsicht</h2>
<p>Erlaubnis zum Waffenhandel nach § 21 Waffengesetz, erteilt durch: {$c['weapon_license_authority']} {$todo('Aktenzeichen und Datum der Erlaubnis eintragen, Behörde bestätigen')}</p>
<p>Staatlich anerkannter Lehrgangsträger für Sachkundelehrgänge, Waffensachverständiger (Württembergische Schützen- und Sicherheitsakademie, <a href="https://www.wssacademy.de" rel="noopener">www.wssacademy.de</a>).</p>
<h2>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
<p>{$c['owner']}, Anschrift wie oben.</p>
<h2>Verbraucherstreitbeilegung</h2>
<p>Wir sind nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>
<h2>Haftung für Inhalte und Links</h2>
<p>Wir erstellen die Inhalte dieser Website mit größter Sorgfalt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte, insbesondere der allgemeinen Informationen zum Waffenrecht, können wir jedoch keine Gewähr übernehmen. Sie ersetzen keine Rechtsberatung. Als Diensteanbieter sind wir für eigene Inhalte nach den allgemeinen Gesetzen verantwortlich. Für Inhalte verlinkter Websites Dritter ist stets deren Anbieter verantwortlich. Bei Bekanntwerden von Rechtsverletzungen entfernen wir entsprechende Links umgehend.</p>
<h2>Urheber- und Markenrecht</h2>
<p>Texte, Grafiken und Bilder dieser Website sind urheberrechtlich geschützt. Eine Vervielfältigung oder Verwendung ist ohne unsere ausdrückliche Zustimmung nicht gestattet. Genannte Marken und Warenzeichen gehören ihren jeweiligen Inhabern.</p>
HTML;
    }

    public static function agb(callable $todo): string
    {
        $c = self::c();
        $address = self::address();
        $widerruf = route('page.show', 'widerruf');
        $button = route('withdrawal.create');

        return <<<HTML
<p>Stand: {$todo('Datum der Freigabe durch den Anwalt')}</p>
<h2>§ 1 Geltungsbereich und Anbieter</h2>
<p>(1) Diese Allgemeinen Geschäftsbedingungen gelten für alle Verträge, die über den Online-Shop {$c['web']} oder im Ladengeschäft zwischen Ihnen und uns geschlossen werden:</p>
<p>{$address}<br>Telefon {$c['phone']}, E-Mail {$c['email']}</p>
<p>(2) Verbraucher ist jede natürliche Person, die ein Rechtsgeschäft zu Zwecken abschließt, die überwiegend weder ihrer gewerblichen noch ihrer selbständigen beruflichen Tätigkeit zugerechnet werden können (§ 13 BGB). Unternehmer ist jede natürliche oder juristische Person oder rechtsfähige Personengesellschaft, die bei Abschluss eines Rechtsgeschäfts in Ausübung ihrer gewerblichen oder selbständigen beruflichen Tätigkeit handelt (§ 14 BGB).</p>
<p>(3) Abweichende Bedingungen des Kunden gelten nur, wenn wir ihnen ausdrücklich schriftlich zugestimmt haben.</p>

<h2>§ 2 Vertragsschluss</h2>
<p>(1) Die Darstellung der Produkte im Online-Shop ist kein rechtlich bindendes Angebot, sondern eine Aufforderung zur Bestellung.</p>
<p>(2) Die Bestellung erfolgt in folgenden Schritten: Auswahl der Ware und Ablage im Warenkorb, Anmeldung im Kundenkonto, Eingabe bzw. Prüfung der Rechnungs- und Lieferadresse, Auswahl der Versand- und Zahlungsart, Prüfung aller Angaben auf der Übersichtsseite, Bestätigung dieser AGB und Absenden der Bestellung über die Schaltfläche „Zahlungspflichtig bestellen“. Bis dahin können Sie Ihre Eingaben jederzeit über die Zurück-Funktion Ihres Browsers oder die Bearbeiten-Links korrigieren oder den Vorgang abbrechen.</p>
<p>(3) Mit dem Absenden geben Sie ein verbindliches Angebot zum Kauf ab. Wir bestätigen den Eingang Ihrer Bestellung unverzüglich per E-Mail. Diese Eingangsbestätigung ist noch keine Annahme.</p>
<p>(4) Bei <strong>frei verkäuflicher Ware</strong> kommt der Vertrag zustande, wenn wir die Bestellung durch gesonderte E-Mail annehmen, die Ware versenden bzw. zur Abholung bereitstellen oder Sie zur Zahlung auffordern; bei PayPal, Klarna und Kreditkarte spätestens mit Abschluss des Zahlungsvorgangs.</p>
<p>(5) Bei <strong>erlaubnispflichtiger Ware</strong> (Schusswaffen, wesentliche Teile, Munition) sowie bei Ware mit Altersbeschränkung kommt der Vertrag erst zustande, wenn wir Ihre Erwerbsberechtigung bzw. Ihr Alter geprüft und die Bestellung ausdrücklich freigegeben haben. Bis dahin sind Sie an Ihr Angebot für 14 Tage gebunden. Wir dürfen die Annahme ohne Angabe von Gründen ablehnen, insbesondere wenn die Berechtigung nicht nachgewiesen ist oder Zweifel an der Berechtigung, Zuverlässigkeit oder Identität bestehen (§ 34 WaffG).</p>
<p>(6) Der Vertragstext wird von uns gespeichert. Sie erhalten die Bestelldaten und diese AGB per E-Mail und können Ihre Bestellungen in Ihrem Kundenkonto einsehen. Vertragssprache ist Deutsch.</p>

<h2>§ 3 Besondere Bestimmungen für Waffen und Munition</h2>
<p>(1) Erlaubnispflichtige Waffen, wesentliche Teile und Munition überlassen wir nur an Personen, die zum Erwerb berechtigt sind (Waffenbesitzkarte mit Voreintrag, gelbe Waffenbesitzkarte, Jahresjagdschein, Munitionserwerbsberechtigung oder vergleichbare Erlaubnis). Sie laden den Nachweis im Kundenkonto hoch. Vor der Übergabe prüfen wir zusätzlich das Original bzw. eine beglaubigte Kopie.</p>
<p>(2) Die Übergabe erfolgt ausschließlich persönlich an die berechtigte Person, im Geschäft gegen Vorlage von Ausweis und Original-Erlaubnis oder durch einen Versanddienst mit persönlicher Übergabe. Eine Lieferung an Packstationen, Paketshops oder abweichende Personen ist ausgeschlossen. Wir liefern erlaubnispflichtige Ware nur innerhalb Deutschlands.</p>
<p>(3) Sie sind verpflichtet, den Erwerb fristgerecht bei Ihrer Waffenbehörde anzuzeigen, soweit das Gesetz dies vorschreibt (z. B. binnen zwei Wochen bei Erwerb mit Jahresjagdschein oder gelber WBK).</p>
<p>(4) Treibladungspulver und Anzündhütchen geben wir nur im Ladengeschäft ab.</p>
<p>(5) Ist eine Bestellung mangels Berechtigung nicht durchführbar, erstatten wir bereits geleistete Zahlungen unverzüglich in voller Höhe.</p>

<h2>§ 4 Preise und Zahlung</h2>
<p>(1) Alle Preise sind Endpreise in Euro {$todo('„inklusive der gesetzlichen Umsatzsteuer“ oder Hinweis nach § 19 UStG, je nach steuerlicher Einordnung')}, zuzüglich der im Bestellvorgang ausgewiesenen Versandkosten.</p>
<p>(2) Zur Verfügung stehen die im Bestellvorgang angezeigten Zahlungsarten. PayPal, Klarna und Kreditkarte sind nur für frei verkäufliche Ware möglich. Bei erlaubnispflichtiger Ware zahlen Sie per Vorkasse erst nach unserer Freigabe oder bar bzw. per EC-Karte bei Abholung.</p>
<p>(3) Bei Vorkasse ist der Rechnungsbetrag innerhalb von 7 Tagen nach unserer Zahlungsaufforderung fällig. Wir reservieren die Ware für diesen Zeitraum.</p>
<p>(4) Rabattcodes und Geschenkgutscheine sind nur im angegebenen Zeitraum und nicht nachträglich einlösbar. Geschenkgutscheine können nicht gegen Bargeld eingetauscht werden; ein Restguthaben bleibt erhalten. Geschenkgutscheine sind drei Jahre ab Ende des Ausstellungsjahres gültig.</p>

<h2>§ 5 Lieferung</h2>
<p>(1) Lieferzeiten sind auf der Produktseite angegeben. Bei Vorkasse beginnt die Frist am Tag nach Erteilung des Zahlungsauftrags, bei erlaubnispflichtiger Ware mit unserer Freigabe.</p>
<p>(2) Ist die Ware ohne unser Verschulden nicht verfügbar, informieren wir Sie unverzüglich und erstatten bereits geleistete Zahlungen.</p>
<p>(3) Bei Verbrauchern geht die Gefahr des zufälligen Untergangs und der Verschlechterung erst mit der Übergabe der Ware auf Sie über.</p>

<h2>§ 6 Eigentumsvorbehalt</h2>
<p>Die Ware bleibt bis zur vollständigen Bezahlung unser Eigentum.</p>

<h2>§ 7 Widerrufsrecht</h2>
<p>Verbrauchern steht ein gesetzliches Widerrufsrecht zu. Einzelheiten finden Sie in der <a href="{$widerruf}">Widerrufsbelehrung</a>. Den Widerruf können Sie auch direkt online über die Funktion <a href="{$button}">„Vertrag widerrufen“</a> erklären. Die Rückgabe erlaubnispflichtiger Ware erfolgt ausschließlich in einer waffenrechtlich zulässigen Weise (persönlich im Geschäft oder per Versand mit persönlicher Übergabe an uns).</p>

<h2>§ 8 Gewährleistung und Garantie</h2>
<p>(1) Es gelten die gesetzlichen Mängelhaftungsrechte. Bei gebrauchter Ware beträgt die Verjährungsfrist gegenüber Verbrauchern ein Jahr ab Ablieferung, soweit wir dies vor Vertragsschluss gesondert vereinbart haben; dies gilt nicht für Schadensersatzansprüche wegen Verletzung von Leben, Körper oder Gesundheit sowie bei Vorsatz und grober Fahrlässigkeit. Gegenüber Unternehmern gilt bei gebrauchter Ware ein Gewährleistungsausschluss, bei neuer Ware eine Frist von einem Jahr.</p>
<p>(2) Herstellergarantien bestehen nur nach den Bedingungen des jeweiligen Herstellers.</p>
<p>(3) Beschussrechtlich geprüfte Waffen dürfen nicht unsachgemäß verändert werden. Schäden durch unsachgemäße Handhabung, ungeeignete Munition oder Eingriffe Dritter sind von der Mängelhaftung ausgenommen.</p>

<h2>§ 9 Haftung</h2>
<p>Wir haften unbeschränkt bei Vorsatz und grober Fahrlässigkeit, für Schäden aus der Verletzung von Leben, Körper oder Gesundheit sowie nach dem Produkthaftungsgesetz. Bei leicht fahrlässiger Verletzung wesentlicher Vertragspflichten ist die Haftung auf den vertragstypischen, vorhersehbaren Schaden begrenzt. Im Übrigen ist die Haftung für leichte Fahrlässigkeit ausgeschlossen.</p>

<h2>§ 10 Schlussbestimmungen</h2>
<p>(1) Es gilt das Recht der Bundesrepublik Deutschland unter Ausschluss des UN-Kaufrechts. Bei Verbrauchern gilt diese Rechtswahl nur, soweit dadurch nicht zwingende Verbraucherschutzvorschriften des Staates entzogen werden, in dem der Verbraucher seinen gewöhnlichen Aufenthalt hat.</p>
<p>(2) Ist der Kunde Kaufmann, juristische Person des öffentlichen Rechts oder öffentlich-rechtliches Sondervermögen, ist Gerichtsstand unser Geschäftssitz in {$c['city']}.</p>
<p>(3) Sollten einzelne Bestimmungen unwirksam sein, bleibt die Wirksamkeit der übrigen Bestimmungen unberührt.</p>
HTML;
    }

    public static function widerruf(callable $todo): string
    {
        $c = self::c();
        $address = self::address();
        $button = route('withdrawal.create');

        return <<<HTML
<p class="lead"><strong>Widerruf online erklären:</strong> Sie können Ihren Vertrag jederzeit innerhalb der Widerrufsfrist direkt über unsere Funktion <a href="{$button}">„Vertrag widerrufen“</a> widerrufen. Sie erhalten sofort eine Eingangsbestätigung per E-Mail.</p>
<h2>Widerrufsbelehrung</h2>
<h3>Widerrufsrecht</h3>
<p>Sie haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen.</p>
<p>Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag, an dem Sie oder ein von Ihnen benannter Dritter, der nicht der Beförderer ist, die Waren in Besitz genommen haben bzw. hat. Haben Sie mehrere Waren im Rahmen einer einheitlichen Bestellung bestellt, die getrennt geliefert werden, beginnt die Frist an dem Tag, an dem Sie oder ein von Ihnen benannter Dritter, der nicht der Beförderer ist, die letzte Ware in Besitz genommen haben bzw. hat.</p>
<p>Um Ihr Widerrufsrecht auszuüben, müssen Sie uns</p>
<p>{$address}<br>Telefon: {$c['phone']}<br>E-Mail: {$c['email']}</p>
<p>mittels einer eindeutigen Erklärung (z. B. ein mit der Post versandter Brief oder eine E-Mail) über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren. Sie können dafür das beigefügte Muster-Widerrufsformular verwenden, das jedoch nicht vorgeschrieben ist. Sie können den Widerruf auch über die Funktion <a href="{$button}">„Vertrag widerrufen“</a> auf unserer Website erklären. Machen Sie von dieser Möglichkeit Gebrauch, so übermitteln wir Ihnen unverzüglich eine Bestätigung über den Eingang eines solchen Widerrufs auf einem dauerhaften Datenträger (z. B. per E-Mail).</p>
<p>Zur Wahrung der Widerrufsfrist reicht es aus, dass Sie die Mitteilung über die Ausübung des Widerrufsrechts vor Ablauf der Widerrufsfrist absenden.</p>
<h3>Folgen des Widerrufs</h3>
<p>Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von Ihnen erhalten haben, einschließlich der Lieferkosten (mit Ausnahme der zusätzlichen Kosten, die sich daraus ergeben, dass Sie eine andere Art der Lieferung als die von uns angebotene, günstigste Standardlieferung gewählt haben), unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag zurückzuzahlen, an dem die Mitteilung über Ihren Widerruf dieses Vertrags bei uns eingegangen ist. Für diese Rückzahlung verwenden wir dasselbe Zahlungsmittel, das Sie bei der ursprünglichen Transaktion eingesetzt haben, es sei denn, mit Ihnen wurde ausdrücklich etwas anderes vereinbart; in keinem Fall werden Ihnen wegen dieser Rückzahlung Entgelte berechnet. Wir können die Rückzahlung verweigern, bis wir die Waren wieder zurückerhalten haben oder bis Sie den Nachweis erbracht haben, dass Sie die Waren zurückgesandt haben, je nachdem, welches der frühere Zeitpunkt ist.</p>
<p>Sie haben die Waren unverzüglich und in jedem Fall spätestens binnen vierzehn Tagen ab dem Tag, an dem Sie uns über den Widerruf dieses Vertrags unterrichten, an uns zurückzusenden oder zu übergeben. Die Frist ist gewahrt, wenn Sie die Waren vor Ablauf der Frist von vierzehn Tagen absenden.</p>
<p>Sie tragen die unmittelbaren Kosten der Rücksendung der Waren. {$todo('Bei Waren, die nicht per Post zurückgesandt werden können (erlaubnispflichtige Waffen und Munition), muss hier die Höhe bzw. eine Schätzung der Rücksendekosten angegeben werden.')}</p>
<p>Sie müssen für einen etwaigen Wertverlust der Waren nur aufkommen, wenn dieser Wertverlust auf einen zur Prüfung der Beschaffenheit, Eigenschaften und Funktionsweise der Waren nicht notwendigen Umgang mit ihnen zurückzuführen ist. Bei Schusswaffen kann bereits eine Schussabgabe einen erheblichen Wertverlust verursachen.</p>
<h3>Ende der Widerrufsbelehrung</h3>

<h2>Hinweise zur Rückgabe erlaubnispflichtiger Ware</h2>
<p>Erlaubnispflichtige Waffen, wesentliche Teile und Munition dürfen nicht als normales Paket zurückgesandt werden. Bitte geben Sie sie persönlich in unserem Geschäft in {$c['city']} zurück oder sprechen Sie mit uns einen Versand mit persönlicher Übergabe ab. Wir nehmen die Rückgabe im Waffenhandelsbuch auf.</p>
<h2>Ausschluss bzw. vorzeitiges Erlöschen des Widerrufsrechts</h2>
<p>Das Widerrufsrecht besteht nicht bei Verträgen zur Lieferung von Waren, die nach Kundenspezifikation angefertigt werden oder eindeutig auf die persönlichen Bedürfnisse zugeschnitten sind (z. B. Cerakote-Beschichtung, Gravuren, Schaftanpassungen), sowie bei versiegelten Waren, die aus Gründen des Gesundheitsschutzes oder der Hygiene nicht zur Rückgabe geeignet sind, wenn ihre Versiegelung nach der Lieferung entfernt wurde. {$todo('Ausnahmen anwaltlich prüfen, insbesondere für Munition')}</p>

<h2>Muster-Widerrufsformular</h2>
<p>(Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus und senden Sie es zurück.)</p>
<p>An {$c['name']} ({$c['legal']}, Inh. {$c['owner']}), {$c['street']}, {$c['postal_code']} {$c['city']}, E-Mail: {$c['email']}</p>
<p>Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über den Kauf der folgenden Waren (*)/die Erbringung der folgenden Dienstleistung (*)<br>
_____________________________________________<br>
Bestellt am (*)/erhalten am (*): ______________<br>
Name des/der Verbraucher(s): ______________<br>
Anschrift des/der Verbraucher(s): ______________<br>
Unterschrift des/der Verbraucher(s) (nur bei Mitteilung auf Papier): ______________<br>
Datum: ______________</p>
<p>(*) Unzutreffendes streichen.</p>
HTML;
    }

    public static function datenschutz(callable $todo): string
    {
        $c = self::c();
        $address = self::address();

        return <<<HTML
<p>Stand: {$todo('Datum der Freigabe')}. Der Schutz Ihrer persönlichen Daten ist uns ein besonderes Anliegen. Wir verarbeiten Ihre Daten ausschließlich auf Grundlage der Datenschutz-Grundverordnung (DSGVO), des Bundesdatenschutzgesetzes (BDSG) und des Telekommunikation-Digitale-Dienste-Datenschutz-Gesetzes (TDDDG). Hier informieren wir Sie darüber, welche Daten wir zu welchem Zweck verarbeiten.</p>

<h2>1. Verantwortlicher</h2>
<p>{$address}<br>Telefon: {$c['phone']}<br>E-Mail für Datenschutzanfragen: <a href="mailto:{$c['privacy_email']}">{$c['privacy_email']}</a></p>
<p>Einen Datenschutzbeauftragten haben wir nicht benannt, da bei uns weniger als 20 Personen ständig mit der automatisierten Verarbeitung personenbezogener Daten beschäftigt sind (§ 38 BDSG). {$todo('Wegen der systematischen Prüfung von Waffenerlaubnissen und Ausweisen anwaltlich prüfen, ob dennoch eine Pflicht nach Art. 37 DSGVO besteht')}</p>

<h2>2. Hosting und Server-Logfiles</h2>
<p>Unsere Website wird bei {$todo('One.com Group AB, Kalvebod Brygge 24, 1560 Kopenhagen, Dänemark')} gehostet. Mit dem Hoster besteht ein Vertrag zur Auftragsverarbeitung (Art. 28 DSGVO). Bei jedem Aufruf speichert der Server automatisch Browsertyp und -version, Betriebssystem, Referrer-URL, Uhrzeit der Anfrage und die IP-Adresse in Logfiles. Diese Daten benötigen wir, um die Website sicher und stabil zu betreiben (Art. 6 Abs. 1 lit. f DSGVO). Sie werden nach spätestens {$todo('7 bzw. 14')} Tagen gelöscht und nicht mit anderen Datenquellen zusammengeführt.</p>
<p>Die Verbindung zu unserer Website ist per TLS (HTTPS) verschlüsselt.</p>

<h2>3. Cookies und lokale Speicherung</h2>
<p>Wir verwenden nur technisch notwendige Cookies: die Sitzung (Anmeldung, Sicherheits-Token gegen Formularmissbrauch), den Warenkorb und die Speicherung Ihrer Cookie-Auswahl. Rechtsgrundlage ist § 25 Abs. 2 TDDDG in Verbindung mit Art. 6 Abs. 1 lit. b und f DSGVO. Ihre Ansichtseinstellungen (helles oder dunkles Design, Merkzettel ohne Konto) speichern wir nur lokal in Ihrem Browser. Analyse- oder Werbe-Cookies setzen wir nicht ein. Sollten wir künftig Statistikdienste nutzen, geschieht das nur mit Ihrer Einwilligung über das Cookie-Banner, die Sie dort jederzeit widerrufen können.</p>

<h2>4. Kundenkonto und Bestellungen</h2>
<p>Für Bestellungen benötigen wir Name, Anschrift, E-Mail-Adresse, Telefonnummer, Geburtsdatum, Bestell- und Zahlungsdaten. Die Verarbeitung erfolgt zur Vertragserfüllung (Art. 6 Abs. 1 lit. b DSGVO) und zur Erfüllung gesetzlicher Pflichten (Art. 6 Abs. 1 lit. c DSGVO). Rechnungen und Buchungsbelege bewahren wir nach Handels- und Steuerrecht bis zu 8 bzw. 10 Jahre auf (§ 257 HGB, § 147 AO). Ihr Kundenkonto können Sie jederzeit unter Konto → Datenschutz löschen. Daten mit gesetzlicher Aufbewahrungspflicht werden dann bis zum Fristablauf gesperrt.</p>

<h2>5. Erwerbsberechtigungen, Ausweise und Waffenhandelsbuch</h2>
<p>Für den Verkauf erlaubnispflichtiger oder altersbeschränkter Ware sind wir verpflichtet, Identität, Alter und Erwerbsberechtigung zu prüfen und zu dokumentieren (§ 34 WaffG, Art. 6 Abs. 1 lit. c DSGVO). Dazu laden Sie Waffenbesitzkarte, Jagdschein oder Ausweis im Kundenkonto hoch. Die Dateien werden verschlüsselt außerhalb des öffentlich zugänglichen Webservers gespeichert, nur von berechtigtem Fachpersonal eingesehen und jeder Zugriff wird protokolliert. Bitte schwärzen Sie auf Ausweisen nicht benötigte Angaben (z. B. Zugangsnummer). Nicht mehr benötigte Dokumente löschen wir automatisch: abgelehnte Dokumente nach einem Monat, abgelaufene Berechtigungen sechs Monate nach Ablauf.</p>
<p>Ein- und Ausgänge erlaubnispflichtiger Waffen tragen wir in das Waffenhandelsbuch ein und melden sie, soweit vorgeschrieben, an das Nationale Waffenregister bzw. die zuständige Waffenbehörde (§ 23 WaffG, AWaffV, NWRG). Diese Daten bewahren wir für die gesetzlich vorgeschriebene Dauer auf.</p>

<h2>6. Zahlungsdienstleister</h2>
<p>Je nach gewählter Zahlungsart übermitteln wir die für die Zahlung erforderlichen Daten (Name, Anschrift, Betrag, Bestellnummer, ggf. E-Mail-Adresse) an den jeweiligen Anbieter (Art. 6 Abs. 1 lit. b DSGVO): PayPal (Europe) S.à r.l. et Cie, S.C.A., 22–24 Boulevard Royal, 2449 Luxemburg, sowie Klarna Bank AB (publ), Sveavägen 46, 111 34 Stockholm, Schweden (Rechnung, Ratenkauf, Sofortüberweisung). Diese Zahlungsarten bieten wir nur für frei verkäufliche Ware an. Die Anbieter verarbeiten Daten in eigener Verantwortung, teilweise auch zur Identitäts- und Bonitätsprüfung. Es gelten ergänzend deren Datenschutzhinweise.</p>

<h2>7. Versand</h2>
<p>Zur Zustellung übermitteln wir Name und Lieferanschrift an den beauftragten Versanddienstleister (DHL Paket GmbH bzw. den beauftragten Waffen- und Werttransportdienst), bei Sendungsverfolgung auch E-Mail-Adresse oder Telefonnummer, sofern Sie zugestimmt haben (Art. 6 Abs. 1 lit. b bzw. a DSGVO). Für die Alterssichtprüfung und die persönliche Übergabe prüft der Zusteller Ihren Ausweis.</p>

<h2>8. Kontaktformular, Terminbuchung, Ankauf und Verkaufsservice</h2>
<p>Wenn Sie uns über ein Formular, per E-Mail, Telefon oder WhatsApp kontaktieren, einen Werkstatt- oder Beratungstermin buchen oder uns eine Waffe zum Ankauf bzw. Verkauf anbieten, speichern wir Ihre Angaben und hochgeladenen Fotos zur Bearbeitung Ihrer Anfrage und für Anschlussfragen (Art. 6 Abs. 1 lit. b DSGVO). Wir löschen die Daten, sobald sie nicht mehr erforderlich sind, spätestens zwei Jahre nach Abschluss der Anfrage, sofern keine gesetzlichen Aufbewahrungsfristen bestehen.</p>

<h2>9. Newsletter und Waffenratgeber (Double-Opt-in)</h2>
HTML.self::newsletter($todo).<<<HTML

<h2>10. Spamschutz</h2>
<p>Unsere Formulare schützen wir mit einem unsichtbaren Feld gegen automatisierte Anfragen und mit einer Begrenzung der Anfragen pro IP-Adresse. Falls aktiviert, nutzen wir zusätzlich Cloudflare Turnstile (Cloudflare, Inc., 101 Townsend St., San Francisco, CA 94107, USA). Dabei werden technische Merkmale Ihres Browsers und Ihre IP-Adresse an Cloudflare übermittelt (Art. 6 Abs. 1 lit. f DSGVO). Cloudflare ist unter dem EU-US Data Privacy Framework zertifiziert.</p>

<h2>11. Bewertungen</h2>
<p>Produktbewertungen können nur Kunden abgeben, die das Produkt bei uns gekauft haben. Veröffentlicht werden Ihr Vorname bzw. der gewählte Anzeigename, die Bewertung und das Datum (Art. 6 Abs. 1 lit. a DSGVO). Sie können die Löschung Ihrer Bewertung jederzeit verlangen.</p>

<h2>12. Ihre Rechte</h2>
<p>Sie haben das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) und Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21). Erteilte Einwilligungen können Sie jederzeit mit Wirkung für die Zukunft widerrufen. Eine Datenauskunft und die Kontolöschung finden Sie auch in Ihrem Kundenkonto unter „Datenschutz“.</p>
<p>Sie haben außerdem das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren, zum Beispiel beim Landesbeauftragten für den Datenschutz und die Informationsfreiheit Baden-Württemberg, Lautenschlagerstraße 20, 70173 Stuttgart.</p>

<h2>13. Änderungen</h2>
<p>Wir passen diese Datenschutzerklärung an, wenn sich unsere Verarbeitungen oder die Rechtslage ändern. Es gilt die jeweils hier veröffentlichte Fassung.</p>
HTML;
    }

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
