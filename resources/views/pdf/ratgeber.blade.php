{{-- Leadmagnet „Der große Waffenratgeber“ (dompdf). Erzeugen mit `php artisan ratgeber:pdf`. Variablen: $company, $year, $stand. --}}
@php
    $logoFile = public_path('images/brand/armory-falter-logo-original.jpg');
    $logo = is_readable($logoFile) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoFile)) : null;
    $web = $company['web'] ?? 'www.armory-falter.de';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Der große Waffenratgeber {{ $year }} · {{ $company['name'] }}</title>
    <style>
        @page { margin: 30mm 18mm 26mm 20mm; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 9pt; color: #1a1a1a; line-height: 1.42; }
        .header { position: fixed; top: -22mm; left: 0; right: 0; height: 14mm; border-bottom: 0.5pt solid #bbb; }
        .header td { vertical-align: bottom; padding-bottom: 2mm; }
        .header .logo { height: 10mm; }
        .header .kicker { text-align: right; font-size: 7pt; color: #666; letter-spacing: 1pt; text-transform: uppercase; }
        .header .bar { position: absolute; left: 0; bottom: -0.5pt; width: 28mm; height: 2pt; background: #9d4640; }
        .footer { position: fixed; bottom: -18mm; left: 0; right: 0; font-size: 7pt; color: #555; border-top: 0.5pt solid #bbb; padding-top: 2mm; }
        .footer td { vertical-align: top; }
        .pagenum:after { content: counter(page); }
        table { border-collapse: collapse; width: 100%; }

        /* Titelseite */
        .cover { page-break-after: always; }
        .cover .logo { height: 34mm; margin-top: 4mm; }
        .cover .band { background: #111; color: #fff; padding: 12mm 10mm 11mm; margin-top: 18mm; }
        .cover .band .eyebrow { color: #e0786f; font-size: 8pt; letter-spacing: 2pt; text-transform: uppercase; font-weight: bold; }
        .cover .band h1 { font-size: 27pt; line-height: 1.15; margin: 4mm 0 5mm; letter-spacing: 0.3pt; }
        .cover .band .accent { width: 22mm; height: 2.5pt; background: #9d4640; margin-bottom: 5mm; }
        .cover .band p { font-size: 10.5pt; color: #ddd; margin: 0; }
        .cover .topics { margin-top: 10mm; }
        .cover .topics td { width: 50%; padding: 2.5mm 4mm 2.5mm 0; font-size: 9.5pt; vertical-align: top; }
        .cover .topics .n { color: #9d4640; font-weight: bold; padding-right: 2mm; }
        .cover .stand { margin-top: 12mm; font-size: 8pt; color: #666; }

        h2 { font-size: 15pt; margin: 0 0 1mm; color: #111; }
        h2 .n { color: #9d4640; }
        .rule { width: 18mm; height: 2pt; background: #9d4640; margin: 2mm 0 5mm; }
        h3 { font-size: 10.5pt; margin: 4mm 0 1.5mm; color: #111; }
        p { margin: 0 0 2.5mm; }
        ul { margin: 0 0 3mm 0; padding-left: 5mm; }
        li { margin-bottom: 1.2mm; }
        .chapter { page-break-before: always; }
        .lead { font-size: 10.5pt; color: #333; }
        .grid th { background: #111; color: #fff; font-size: 8pt; text-align: left; padding: 2mm; }
        .grid td { padding: 1.5mm 2mm; border-bottom: 0.5pt solid #ddd; vertical-align: top; font-size: 8.5pt; }
        .grid tr:nth-child(even) td { background: #f6f4f3; }
        .tip { background: #f6f4f3; border-left: 2.5pt solid #9d4640; padding: 3mm 4mm; margin: 4mm 0; font-size: 9pt; }
        .tip strong { color: #9d4640; }
        .check td { padding: 1.6mm 0; vertical-align: top; border-bottom: 0.5pt solid #e5e5e5; }
        .check .box { width: 6mm; color: #9d4640; font-weight: bold; }
        .cta { background: #111; color: #fff; padding: 7mm 8mm; margin-top: 6mm; }
        .cta h3 { color: #fff; margin-top: 0; font-size: 12pt; }
        .cta p { color: #ddd; }
        .cta .accent { color: #e0786f; font-weight: bold; }
        .small { font-size: 7.5pt; color: #666; }
    </style>
</head>
<body>

<div class="header">
    <table><tr>
        <td>@if ($logo)<img src="{{ $logo }}" class="logo" alt="{{ $company['name'] }}">@else<strong>{{ $company['name'] }}</strong>@endif</td>
        <td class="kicker">Der große Waffenratgeber {{ $year }}</td>
    </tr></table>
    <div class="bar"></div>
</div>

<div class="footer">
    <table><tr>
        <td style="width: 38%;"><strong>{{ $company['name'] }}</strong> · Inhaber {{ $company['owner'] }}<br>{{ $company['street'] }} · {{ $company['postal_code'] }} {{ $company['city'] }}</td>
        <td style="width: 38%;">Tel. {{ $company['phone'] }}<br>{{ $company['email'] }} · {{ $web }}</td>
        <td style="width: 24%; text-align: right;">Seite <span class="pagenum"></span><br>Stand {{ $stand }}</td>
    </tr></table>
</div>

{{-- Titelseite --}}
<div class="cover">
    @if ($logo)<img src="{{ $logo }}" class="logo" alt="{{ $company['name'] }}">@endif
    <div class="band">
        <div class="eyebrow">Kostenloser Ratgeber</div>
        <h1>Der große<br>Waffenratgeber {{ $year }}</h1>
        <div class="accent"></div>
        <p>Kaliber, Optik, Waffenrecht und Kaufberatung kompakt erklärt. Für Jäger, Sportschützen und alle, die es werden wollen.</p>
    </div>
    <table class="topics">
        <tr><td><span class="n">01</span> Waffenrecht verständlich</td><td><span class="n">05</span> Aufbewahrung und Transport</td></tr>
        <tr><td><span class="n">02</span> Der Weg zur eigenen Waffe</td><td><span class="n">06</span> Pflege und Wartung</td></tr>
        <tr><td><span class="n">03</span> Kaliber richtig wählen</td><td><span class="n">07</span> Kaufberatung und Checkliste</td></tr>
        <tr><td><span class="n">04</span> Optik: Zielfernrohr und Co.</td><td><span class="n">08</span> So kaufen Sie bei {{ $company['name'] }}</td></tr>
    </table>
    <p class="stand">Stand: {{ $stand }}. Dieser Ratgeber gibt einen allgemeinen Überblick und ersetzt keine Rechtsberatung. Verbindlich sind das Waffengesetz (WaffG), die Allgemeine Waffengesetz-Verordnung (AWaffV), das Bundes- und Landesjagdrecht sowie die Auskunft Ihrer zuständigen Waffenbehörde.</p>
</div>

{{-- 1 Waffenrecht --}}
<h2><span class="n">01</span> Waffenrecht verständlich</h2>
<div class="rule"></div>
<p class="lead">Das deutsche Waffenrecht ist streng, aber klar geregelt. Wer die Grundbegriffe kennt, plant den Kauf sicher und ohne Überraschungen.</p>

<h3>Die wichtigsten Begriffe</h3>
<table class="grid">
    <tr><th style="width: 30%;">Begriff</th><th>Bedeutung</th></tr>
    <tr><td><strong>Erwerb</strong></td><td>Sie erlangen die tatsächliche Gewalt über eine Waffe, zum Beispiel durch Kauf und Übergabe.</td></tr>
    <tr><td><strong>Besitz</strong></td><td>Sie üben die tatsächliche Gewalt aus, etwa zu Hause im Waffenschrank.</td></tr>
    <tr><td><strong>Führen</strong></td><td>Sie tragen eine Waffe außerhalb der eigenen Wohnung, Geschäftsräume oder des befriedeten Besitztums zugriffsbereit bei sich. Dafür ist grundsätzlich ein Waffenschein nötig.</td></tr>
    <tr><td><strong>Transport</strong></td><td>Sie befördern eine Waffe ungeladen und nicht zugriffsbereit, zum Beispiel im verschlossenen Koffer zum Schießstand oder ins Revier. Das ist kein Führen.</td></tr>
    <tr><td><strong>Bedürfnis</strong></td><td>Der anerkannte Grund für Erwerb und Besitz, etwa als Jäger oder Sportschütze. Ohne Bedürfnis keine Erlaubnis.</td></tr>
    <tr><td><strong>Voreintrag</strong></td><td>Die Erwerbsberechtigung in der grünen WBK für eine bestimmte Waffenart, ausgestellt von der Waffenbehörde.</td></tr>
</table>

<h3>Die Erlaubnisse im Überblick</h3>
<ul>
    <li><strong>Grüne Waffenbesitzkarte (WBK):</strong> die Standard-Erlaubnis. Für den Kauf einer Waffe brauchen Sie in der Regel einen Voreintrag. Sportschützen erhalten ihn mit der Bedürfnisbescheinigung ihres Schießsportverbands.</li>
    <li><strong>Gelbe WBK (Sportschützen):</strong> erlaubt den Erwerb bestimmter Waffen ohne Voreintrag, etwa Einzellader- und Repetier-Langwaffen mit gezogenem Lauf und einläufige Einzellader-Kurzwaffen. Die Zahl der Waffen auf der gelben WBK ist begrenzt.</li>
    <li><strong>Jagdschein:</strong> Inhaber eines gültigen Jahresjagdscheins dürfen Langwaffen ohne Voreintrag erwerben. Kurzwaffen benötigen auch bei Jägern einen Voreintrag.</li>
    <li><strong>Munitionserwerb:</strong> Munition dürfen Sie nur für Waffen erwerben, für die Sie eine Berechtigung haben. Sie wird in der WBK eingetragen. Jäger erwerben Langwaffenmunition mit dem Jagdschein.</li>
    <li><strong>Kleiner Waffenschein:</strong> nötig, um Schreckschuss-, Reizstoff- und Signalwaffen (PTB-Zeichen) zu führen. Erwerb und Besitz sind ab 18 Jahren erlaubnisfrei.</li>
</ul>

<div class="tip"><strong>Wichtig:</strong> Nach dem Erwerb einer Waffe müssen Sie diesen innerhalb von <strong>zwei Wochen</strong> Ihrer Waffenbehörde anzeigen und die Waffe in die WBK eintragen lassen. Wir geben Ihnen dafür alle nötigen Angaben mit.</div>

<h3>Altersgrenzen</h3>
<p>Waffen und Munition dürfen grundsätzlich erst ab 18 Jahren erworben werden. Für großkalibrige Sportwaffen gilt: Wer jünger als 25 Jahre ist, muss bei der erstmaligen Erlaubnis ein fachpsychologisches Zeugnis über die geistige Eignung vorlegen. Druckluftwaffen mit „F im Fünfeck“ (höchstens 7,5 Joule) sind ab 18 Jahren erlaubnisfrei.</p>

{{-- 2 Weg zur Waffe --}}
<div class="chapter">
<h2><span class="n">02</span> Der Weg zur eigenen Waffe</h2>
<div class="rule"></div>
<p class="lead">Ob Jagd oder Sport: Der Weg folgt immer denselben Schritten. Rechnen Sie mit mehreren Monaten, vor allem beim Sport.</p>

<h3>Als Jäger</h3>
<ul>
    <li>Jägerprüfung bestehen (Lehrgang mit Theorie, Schießen und Praxis).</li>
    <li>Jahresjagdschein bei der unteren Jagdbehörde beantragen, inklusive Jagdhaftpflichtversicherung.</li>
    <li>Waffenbesitzkarte bei der Waffenbehörde beantragen. Zuverlässigkeit und persönliche Eignung werden geprüft.</li>
    <li>Langwaffen ohne Voreintrag kaufen, für Kurzwaffen den Voreintrag beantragen.</li>
</ul>

<h3>Als Sportschütze</h3>
<ul>
    <li>Mitgliedschaft in einem Verein eines anerkannten Schießsportverbands, mindestens 12 Monate.</li>
    <li>Regelmäßiges Training nachweisen: in den letzten 12 Monaten einmal pro Monat oder 18-mal verteilt über das Jahr.</li>
    <li>Sachkundeprüfung ablegen.</li>
    <li>Bedürfnisbescheinigung des Verbands für die gewünschte Waffe einholen.</li>
    <li>WBK mit Voreintrag beantragen, beziehungsweise später die gelbe WBK.</li>
</ul>

<h3>Was die Behörde prüft</h3>
<table class="grid">
    <tr><th style="width: 30%;">Prüfung</th><th>Inhalt</th></tr>
    <tr><td>Zuverlässigkeit</td><td>Auskünfte aus Bundeszentralregister, Polizei und Verfassungsschutz. Bestimmte Verurteilungen schließen eine Erlaubnis aus.</td></tr>
    <tr><td>Persönliche Eignung</td><td>Keine Abhängigkeit, keine psychische Erkrankung, die einen sicheren Umgang verhindert. In Einzelfällen wird ein Gutachten verlangt.</td></tr>
    <tr><td>Sachkunde</td><td>Nachweis durch Jägerprüfung, Sachkundeprüfung oder gleichwertige Ausbildung.</td></tr>
    <tr><td>Bedürfnis</td><td>Jagd, Sport, Sammeln, Brauchtum oder ein anderes anerkanntes Interesse.</td></tr>
    <tr><td>Aufbewahrung</td><td>Nachweis eines geeigneten Waffenschranks, oft schon vor der Erteilung.</td></tr>
</table>
<div class="tip"><strong>Unser Tipp:</strong> Sprechen Sie vor dem Antrag mit uns. Wir sagen Ihnen, welche Waffe zu Ihrer Disziplin passt, damit der Voreintrag gleich richtig formuliert ist.</div>
</div>

{{-- 3 Kaliber --}}
<div class="chapter">
<h2><span class="n">03</span> Kaliber richtig wählen</h2>
<div class="rule"></div>
<p class="lead">Das Kaliber entscheidet über Einsatzzweck, Rückstoß, Reichweite und laufende Kosten. Für die Jagd gibt es zusätzlich gesetzliche Mindestanforderungen.</p>

<h3>Jagdrechtliche Mindestanforderungen</h3>
<p>Nach dem Bundesjagdgesetz muss Büchsenmunition auf Rehwild eine Auftreffenergie von mindestens 1.000 Joule auf 100 Meter (E100) haben. Auf übriges Schalenwild sind mindestens 2.000 Joule und ein Kaliber ab 6,5 mm vorgeschrieben. Viele Länder verlangen inzwischen bleifreie Büchsengeschosse. Prüfen Sie das Landesjagdrecht.</p>

<h3>Bewährte Kaliber im Überblick</h3>
<table class="grid">
    <tr><th style="width: 22%;">Kaliber</th><th style="width: 20%;">Typ</th><th>Typischer Einsatz</th></tr>
    <tr><td>.22 lfB</td><td>Kleinkaliber</td><td>Sportschießen, Training, Einstieg. Günstig und rückstoßarm.</td></tr>
    <tr><td>9 mm Luger</td><td>Kurzwaffe</td><td>Sportschießen (zum Beispiel IPSC, Fallscheibe), Fangschuss. Weltweiter Standard.</td></tr>
    <tr><td>.223 Rem.</td><td>Büchse</td><td>Sport, Raubwild, Rehwild (E100 beachten). Flache Flugbahn, wenig Rückstoß.</td></tr>
    <tr><td>.308 Win.</td><td>Büchse</td><td>Allrounder für Reh-, Dam- und Schwarzwild sowie Sport auf Distanz.</td></tr>
    <tr><td>8x57 IS</td><td>Büchse</td><td>Klassiker für Drückjagd und Schwarzwild.</td></tr>
    <tr><td>.30-06 Spr.</td><td>Büchse</td><td>Vielseitig für alles Schalenwild in Mitteleuropa.</td></tr>
    <tr><td>9,3x62</td><td>Büchse</td><td>Schweres Schwarzwild, Drückjagd, Auslandsjagd.</td></tr>
    <tr><td>12/70, 12/76</td><td>Flinte</td><td>Niederwild, Wurfscheibe. 20/76 ist leichter und rückstoßärmer.</td></tr>
</table>
<div class="tip"><strong>Faustregel:</strong> Lieber ein Kaliber, das Sie sicher beherrschen, als ein zu starkes. Treffersitz schlägt Energie. Und: Gängige Kaliber sind überall erhältlich und günstiger im Training.</div>
</div>

{{-- 4 Optik --}}
<div class="chapter">
<h2><span class="n">04</span> Optik: Zielfernrohr und Co.</h2>
<div class="rule"></div>
<p class="lead">Eine gute Optik ist oft wichtiger als die Waffe selbst. Investieren Sie hier lieber etwas mehr.</p>

<h3>Die Kennzahlen eines Zielfernrohrs</h3>
<p>Die Bezeichnung <strong>2,5–15x56</strong> bedeutet: 2,5- bis 15-fache Vergrößerung, 56 mm Objektivdurchmesser.</p>
<ul>
    <li><strong>Austrittspupille</strong> = Objektivdurchmesser ÷ Vergrößerung. Für die Dämmerung sind 5 bis 7 mm ideal.</li>
    <li><strong>Dämmerungszahl</strong> = Wurzel aus (Vergrößerung × Objektivdurchmesser). Je höher, desto mehr Details bei wenig Licht.</li>
    <li><strong>Absehen in der 1. Bildebene:</strong> wächst mit der Vergrößerung mit, Haltepunkte bleiben bei jeder Vergrößerung gültig. Gut für weite Schüsse.</li>
    <li><strong>Absehen in der 2. Bildebene:</strong> bleibt gleich groß, verdeckt wenig vom Ziel. Beliebt bei der Jagd.</li>
    <li><strong>Leuchtabsehen:</strong> ein beleuchteter Punkt für schnelles Zielen in der Dämmerung und auf der Drückjagd.</li>
</ul>

<h3>Welche Optik für welchen Zweck?</h3>
<table class="grid">
    <tr><th style="width: 26%;">Einsatz</th><th style="width: 30%;">Empfehlung</th><th>Warum</th></tr>
    <tr><td>Ansitz</td><td>2,5–15x56 oder 3–18x56</td><td>Großes Objektiv für Licht, Leuchtpunkt für die Dämmerung.</td></tr>
    <tr><td>Drückjagd</td><td>1–6x24 oder Rotpunkt</td><td>Großes Sehfeld, schnelle Zielerfassung.</td></tr>
    <tr><td>Pirsch, Allround</td><td>2–12x50</td><td>Guter Kompromiss aus Gewicht und Lichtleistung.</td></tr>
    <tr><td>Sport auf Distanz</td><td>5–25x56, 1. Bildebene</td><td>Präzise Verstellung, gültige Haltepunkte.</td></tr>
</table>

<h3>Montage und Zubehör</h3>
<p>Die Montage verbindet Optik und Waffe und muss absolut spielfrei sein. Gängig sind Schwenk-, Picatinny- und Prismenmontagen. Lassen Sie die Montage vom Fachmann setzen und die Waffe danach einschießen.</p>
<div class="tip"><strong>Nachtsicht und Wärmebild:</strong> Jäger dürfen Nachtsicht- und Wärmebild-Vorsatzgeräte unter bestimmten Voraussetzungen verwenden, vor allem zur Bejagung von Schwarzwild. Die Regeln unterscheiden sich je nach Bundesland. Wir beraten Sie gern zur Rechtslage in Baden-Württemberg.</div>
</div>

{{-- 5 Aufbewahrung --}}
<div class="chapter">
<h2><span class="n">05</span> Aufbewahrung und Transport</h2>
<div class="rule"></div>
<p class="lead">Waffen und Munition müssen so aufbewahrt werden, dass Unbefugte keinen Zugriff haben. Die Behörde darf das jederzeit kontrollieren.</p>

<h3>Aufbewahrung zu Hause</h3>
<ul>
    <li>Erlaubnispflichtige Schusswaffen gehören in einen zertifizierten Waffenschrank, mindestens Widerstandsgrad 0 nach DIN/EN 1143-1.</li>
    <li>Munition wird getrennt aufbewahrt oder in einem dafür vorgesehenen Innenfach des Waffenschranks.</li>
    <li>Schlüssel und Zahlenkombination gehören nicht in fremde Hände, auch nicht in die von Familienangehörigen ohne Erlaubnis.</li>
    <li>Bewahren Sie Kaufbeleg und Zertifikat des Schranks auf. Die Behörde fragt danach.</li>
</ul>

<h3>Transport</h3>
<ul>
    <li>Waffe ungeladen und nicht zugriffsbereit, also im verschlossenen Behältnis, zum Beispiel einem abschließbaren Koffer.</li>
    <li>Nur auf direktem Weg zu einem berechtigten Zweck: Schießstand, Revier, Büchsenmacher, Händler.</li>
    <li>WBK oder Jagdschein und Personalausweis immer mitführen.</li>
    <li>Auf öffentlichen Veranstaltungen gilt ein Waffenverbot, auch für erlaubnisfreie Waffen.</li>
</ul>

<h3>Checkliste Waffenschrank</h3>
<table class="check">
    <tr><td class="box">☐</td><td>Widerstandsgrad mindestens 0 nach DIN/EN 1143-1, Zertifikat vorhanden</td></tr>
    <tr><td class="box">☐</td><td>Ab 200 kg Gewicht oder fest im Boden oder in der Wand verankert</td></tr>
    <tr><td class="box">☐</td><td>Platz für alle Lang- und Kurzwaffen plus Reserve</td></tr>
    <tr><td class="box">☐</td><td>Munitionsfach oder getrennter Munitionsbehälter</td></tr>
    <tr><td class="box">☐</td><td>Standort trocken, nicht von außen einsehbar</td></tr>
</table>
</div>

{{-- 6 Pflege --}}
<div class="chapter">
<h2><span class="n">06</span> Pflege und Wartung</h2>
<div class="rule"></div>
<p class="lead">Eine gepflegte Waffe schießt präziser, funktioniert zuverlässiger und behält ihren Wert.</p>

<h3>Nach jedem Schießen</h3>
<ul>
    <li><strong>Sicherheit zuerst:</strong> Waffe entladen, Patronenlager und Magazin kontrollieren.</li>
    <li><strong>Lauf reinigen:</strong> Pulverrückstände mit Laufreiniger lösen, mit Bronzebürste und Filzpfropfen in Schussrichtung reinigen.</li>
    <li><strong>Verschluss und Mechanik:</strong> Schmutz entfernen, sparsam ölen. Zu viel Öl bindet Schmutz.</li>
    <li><strong>Außenflächen:</strong> leicht eingeölt abwischen, Holzschäfte mit Schaftöl pflegen.</li>
</ul>

<h3>Regelmäßig</h3>
<ul>
    <li>Optik und Montage auf festen Sitz prüfen, vor der Jagdsaison Probeschuss.</li>
    <li>Kurzwaffen nach etwa 3.000 bis 5.000 Schuss zerlegen und Federn prüfen lassen.</li>
    <li>Einmal im Jahr eine Inspektion beim Büchsenmacher.</li>
</ul>
<div class="tip"><strong>Werkstatt-Service:</strong> Unser Büchsenmacher übernimmt Grundreinigung, Montage, Einschießen und Reparaturen. Termin einfach online oder telefonisch vereinbaren.</div>
</div>

{{-- 7 Kaufberatung --}}
<div class="chapter">
<h2><span class="n">07</span> Kaufberatung und Checkliste</h2>
<div class="rule"></div>
<p class="lead">Die richtige Waffe passt zu Ihnen, Ihrem Einsatzzweck und Ihrem Budget. Diese Fragen helfen bei der Entscheidung.</p>

<h3>Fünf Fragen vor dem Kauf</h3>
<ul>
    <li><strong>Wofür?</strong> Ansitz, Drückjagd, Wurfscheibe, Präzisionssport oder dynamisches Schießen.</li>
    <li><strong>Welches Kaliber?</strong> Gesetzliche Vorgaben, Wildart, Disziplin, Munitionskosten.</li>
    <li><strong>Welches System?</strong> Repetierer, Geradezug, Halbautomat, Kipplauf oder Flinte.</li>
    <li><strong>Passt die Waffe?</strong> Schaftlänge, Griff, Abzug und Gewicht müssen zu Ihnen passen. Probieren geht über Studieren.</li>
    <li><strong>Gesamtbudget?</strong> Rechnen Sie Optik, Montage, Waffenschrank, Koffer und Munition mit ein.</li>
</ul>

<h3>Neu oder gebraucht?</h3>
<p>Gebrauchte Waffen vom Fachhändler sind eine gute Wahl, wenn Zustand und Laufverschleiß geprüft sind. Achten Sie auf das Beschusszeichen, einen sauberen Lauf und eine einwandfreie Funktion. Jede Waffe aus unserem Gebrauchtangebot ist geprüft.</p>

<h3>Ihre Checkliste</h3>
<table class="check">
    <tr><td class="box">☐</td><td>Gültige Erlaubnis vorhanden: WBK mit Voreintrag, gelbe WBK oder Jagdschein</td></tr>
    <tr><td class="box">☐</td><td>Waffenschrank mit passendem Widerstandsgrad steht bereit</td></tr>
    <tr><td class="box">☐</td><td>Kaliber erfüllt die Vorgaben für Wildart oder Disziplin</td></tr>
    <tr><td class="box">☐</td><td>Waffe in der Hand gehabt, Anschlag und Abzug passen</td></tr>
    <tr><td class="box">☐</td><td>Optik, Montage und Einschießen eingeplant</td></tr>
    <tr><td class="box">☐</td><td>Munition für Training und Jagd ausgewählt</td></tr>
    <tr><td class="box">☐</td><td>Eintragung bei der Behörde innerhalb von zwei Wochen eingeplant</td></tr>
</table>
</div>

{{-- 8 Armory Falter --}}
<div class="chapter">
<h2><span class="n">08</span> So kaufen Sie bei {{ $company['name'] }}</h2>
<div class="rule"></div>
<p class="lead">Ihr Waffenfachhandel in {{ $company['city'] }} bei Stuttgart, für Jäger, Sportschützen, Sammler und Behörden. Online bestellen, persönlich beraten werden.</p>

<table class="grid">
    <tr><th style="width: 8%;">#</th><th style="width: 28%;">Schritt</th><th>So geht's</th></tr>
    <tr><td><strong>1</strong></td><td>Auswählen</td><td>Waffe in den Warenkorb legen oder reservieren lassen. Fragen? Ihr Berater ist für Sie da.</td></tr>
    <tr><td><strong>2</strong></td><td>Nachweis hochladen</td><td>WBK mit Voreintrag, gelbe WBK oder Jagdschein sicher und verschlüsselt im Kundenkonto hochladen.</td></tr>
    <tr><td><strong>3</strong></td><td>Prüfung und Freigabe</td><td>Wir prüfen Ihre Unterlagen und geben die Bestellung frei. Sie erhalten eine Bestätigung per E-Mail.</td></tr>
    <tr><td><strong>4</strong></td><td>Sicher erhalten</td><td>Versand mit persönlicher Übergabe an Sie oder Abholung im Laden in {{ $company['city'] }}.</td></tr>
</table>

<h3>Mehr als ein Shop</h3>
<ul>
    <li><strong>Persönliche Beratung:</strong> vor Ort, am Telefon oder per Video. Ehrlich und ohne Verkaufsdruck.</li>
    <li><strong>Werkstatt:</strong> Montage, Einschießen, Reinigung und Reparatur.</li>
    <li><strong>Ankauf und Kommission:</strong> Wir kaufen Ihre Waffe an oder verkaufen sie für Sie.</li>
    <li><strong>Events und Testtage:</strong> Neuheiten ausprobieren, bevor Sie kaufen.</li>
</ul>

<div class="cta">
    <h3>Kostenlose Beratung vereinbaren</h3>
    <p>Sie haben Fragen zu Waffe, Kaliber, Optik oder Erlaubnis? Wir nehmen uns Zeit für Sie.</p>
    <p style="margin: 0;"><span class="accent">Tel. {{ $company['phone'] }}</span> · {{ $company['email'] }} · {{ $web }}<br>{{ $company['street'] }} · {{ $company['postal_code'] }} {{ $company['city'] }}</p>
</div>

<p class="small" style="margin-top: 6mm;">Alle Angaben ohne Gewähr. Dieser Ratgeber gibt den Rechtsstand vom {{ $stand }} in allgemeiner Form wieder und ersetzt keine Rechtsberatung. Maßgeblich sind das Waffengesetz, die Allgemeine Waffengesetz-Verordnung, das Bundesjagdgesetz, das jeweilige Landesjagdrecht und die Auskunft Ihrer Waffen- und Jagdbehörde. © {{ $year }} {{ $company['name'] }}.</p>
</div>

</body>
</html>
