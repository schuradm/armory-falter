<?php

/**
 * Armory Falter – Einmal-Installer für Shared Hosting ohne SSH (One.com).
 *
 * Wird vom Build-Skript nach public/install.php kopiert und ist damit unter
 * https://<domain>/install.php erreichbar. Er führt die Schritte aus, für die
 * man sonst eine Kommandozeile bräuchte:
 *   APP_KEY erzeugen (nur wenn leer), Caches leeren, migrate --force,
 *   optional db:seed, optional demo:images, optional storage:link, Caches für Produktion aufbauen.
 *
 * Sicherheit:
 *   - Läuft nur, wenn in .env ein INSTALL_TOKEN mit mindestens 32 Zeichen steht.
 *   - Ausführung nur per POST mit Token (hash_equals) und Bestätigungswort.
 *   - Jeder Token funktioniert genau EINMAL (Sperrdatei mit Hash des Tokens).
 *     Für einen weiteren Lauf (z. B. nach einem Update) neuen Token in .env setzen.
 *   - Keine Ausgabe von Geheimnissen; Artisan-Ausgaben werden zusätzlich gefiltert.
 *   - Nach Gebrauch DATEI LÖSCHEN (public/install.php).
 */

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

const AF_MIN_TOKEN_LENGTH = 32;
const AF_CONFIRM_WORD = 'INSTALLIEREN';
const AF_MIN_PHP = '8.3.0';

$basePath = dirname(__DIR__);
$envFile = $basePath.'/.env';
$lockFile = $basePath.'/storage/app/install.lock';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

/** Liest einen einzelnen Wert aus der .env, ohne Laravel zu starten. */
function af_env_value(string $file, string $key): ?string
{
    if (! is_readable($file)) {
        return null;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (preg_match('/^\s*(?:export\s+)?'.preg_quote($key, '/').'\s*=\s*(.*)$/', $line, $m)) {
            $value = trim($m[1]);
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $q = $value[0];
                $end = strpos($value, $q, 1);
                $value = $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
            } else {
                $value = trim((string) preg_replace('/\s+#.*$/', '', $value));
            }

            return $value;
        }
    }

    return null;
}

function af_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Entfernt alles aus Ausgaben, was nach Schlüssel/Passwort aussieht. */
function af_redact(string $s): string
{
    $s = (string) preg_replace('/base64:[A-Za-z0-9+\/=]{20,}/', 'base64:***', $s);
    $s = (string) preg_replace('/(password|passwort|secret|token|key)(\s*[=:]\s*)\S+/i', '$1$2***', $s);

    return $s;
}

function af_page(string $title, string $body): never
{
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<meta name="robots" content="noindex,nofollow"><title>'.af_h($title).'</title><style>'
        .'body{font:16px/1.5 system-ui,sans-serif;background:#111;color:#eee;margin:0;padding:24px}main{max-width:760px;margin:auto}'
        .'h1{font-size:1.4rem;color:#e0786f}fieldset{border:1px solid #444;border-radius:6px;margin:16px 0;padding:12px 16px}'
        .'label{display:block;margin:8px 0}input[type=password],input[type=text]{width:100%;padding:8px;background:#222;color:#eee;border:1px solid #555;border-radius:4px;box-sizing:border-box}'
        .'button{background:#9d4640;color:#fff;border:0;padding:12px 20px;border-radius:4px;font-size:1rem;cursor:pointer}'
        .'pre{background:#1b1b1b;border:1px solid #333;padding:12px;overflow:auto;white-space:pre-wrap;font-size:.85rem}'
        .'.ok{color:#7fd07f}.fail{color:#ff8a80}.warn{color:#ffd27f}small{color:#aaa}</style></head><body><main>'
        .'<h1>'.af_h($title).'</h1>'.$body.'</main></body></html>';
    exit;
}

// ---------------------------------------------------------------------------
// Grundvoraussetzungen (ohne Details, solange nicht authentifiziert)
// ---------------------------------------------------------------------------
$token = (string) af_env_value($envFile, 'INSTALL_TOKEN');

if (! is_file($envFile)) {
    af_page('Installer gesperrt', '<p class="fail">Es gibt noch keine <code>.env</code>. Bitte zuerst <code>.env.onecom.example</code> zu <code>.env</code> kopieren und ausfüllen.</p>');
}
if (strlen($token) < AF_MIN_TOKEN_LENGTH) {
    af_page('Installer gesperrt', '<p class="fail">In der <code>.env</code> ist kein <code>INSTALL_TOKEN</code> mit mindestens '.AF_MIN_TOKEN_LENGTH.' Zeichen gesetzt. Der Installer ist deaktiviert.</p>');
}
$tokenHash = hash('sha256', $token);
if (is_file($lockFile) && hash_equals(trim((string) file_get_contents($lockFile)), $tokenHash)) {
    af_page('Installer gesperrt', '<p class="warn">Dieser INSTALL_TOKEN wurde bereits verwendet. Für einen neuen Lauf einen neuen Token in die <code>.env</code> schreiben.</p><p><strong>Wenn nichts mehr zu tun ist: <code>public/install.php</code> jetzt löschen.</strong></p>');
}

// ---------------------------------------------------------------------------
// GET: Formular
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $form = '<p>Dieser Installer richtet die Datenbank ein und bereitet den Shop für den Betrieb vor. Er funktioniert pro Token nur einmal.</p>'
        .'<form method="post" autocomplete="off">'
        .'<fieldset><legend>Zugang</legend>'
        .'<label>INSTALL_TOKEN aus der .env<input type="password" name="token" required minlength="'.AF_MIN_TOKEN_LENGTH.'"></label>'
        .'<label>Zur Bestätigung <strong>'.AF_CONFIRM_WORD.'</strong> eintippen<input type="text" name="confirm" required pattern="'.AF_CONFIRM_WORD.'"></label>'
        .'</fieldset><fieldset><legend>Schritte</legend>'
        .'<label><input type="checkbox" name="migrate" value="1" checked> Datenbank-Migrationen ausführen (<code>migrate --force</code>)</label>'
        .'<label><input type="checkbox" name="seed" value="1"> Grunddaten einspielen (<code>db:seed</code>) – <small>nur bei der Erstinstallation!</small></label>'
        .'<label><input type="checkbox" name="link" value="1"> Symlink <code>public/storage</code> anlegen – <small>nur nötig, wenn die Dokumentenwurzel direkt auf public/ zeigt</small></label>'
        .'<label><input type="checkbox" name="optimize" value="1" checked> Produktions-Caches aufbauen (Config, Routen, Views, Events, Filament)</label>'
        .'<label><input type="checkbox" name="maintenance" value="1"> Während der Migration Wartungsmodus aktivieren (bei Updates empfohlen)</label>'
        .'</fieldset><button type="submit">Jetzt ausführen</button></form>';
    af_page('Armory Falter – Installation', $form);
}

// ---------------------------------------------------------------------------
// POST: prüfen
// ---------------------------------------------------------------------------
$given = (string) ($_POST['token'] ?? '');
$confirm = (string) ($_POST['confirm'] ?? '');
if (! hash_equals($token, $given) || $confirm !== AF_CONFIRM_WORD) {
    usleep(random_int(1_000_000, 2_000_000)); // bremst Rateversuche
    http_response_code(403);
    af_page('Zugriff verweigert', '<p class="fail">Token oder Bestätigungswort falsch.</p>');
}

// Same-Origin-Prüfung als zusätzlicher Schutz gegen fremd ausgelöste POSTs
$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('http://'.$host, PHP_URL_HOST)) {
    http_response_code(403);
    af_page('Zugriff verweigert', '<p class="fail">Fremder Ursprung.</p>');
}

// Token sofort sperren (auch wenn ein Schritt später scheitert)
@mkdir(dirname($lockFile), 0775, true);
if (@file_put_contents($lockFile, $tokenHash) === false) {
    af_page('Fehler', '<p class="fail"><code>storage/app</code> ist nicht beschreibbar. Bitte Schreibrechte (755/775) für <code>storage/</code> und <code>bootstrap/cache/</code> setzen.</p>');
}

@set_time_limit(600);
@ignore_user_abort(true);

$log = [];
$add = function (string $step, bool $ok, string $out = '') use (&$log): void {
    $log[] = [$step, $ok, af_redact(trim($out))];
};

// ---------------------------------------------------------------------------
// Vorprüfung
// ---------------------------------------------------------------------------
$add('PHP-Version '.PHP_VERSION.' (mind. '.AF_MIN_PHP.')', version_compare(PHP_VERSION, AF_MIN_PHP, '>='));
$missing = array_values(array_filter(
    ['ctype', 'curl', 'dom', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'tokenizer', 'xml'],
    fn ($e) => ! extension_loaded($e)
));
$add('PHP-Erweiterungen (Pflicht)', $missing === [], $missing ? 'Fehlt: '.implode(', ', $missing).' – im One.com-Panel eine andere PHP-Version wählen oder den Support fragen.' : '');
$optional = array_values(array_filter(['bcmath', 'sodium', 'zip', 'exif', 'Zend OPcache'], fn ($e) => ! extension_loaded($e)));
if ($optional !== []) {
    $log[] = ['Optionale Erweiterungen fehlen (nur Hinweis): '.implode(', ', $optional), true, ''];
}
if ($missing !== []) {
    af_render($log, false);
}
foreach (['storage', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
    $p = $basePath.'/'.$dir;
    @mkdir($p, 0775, true);
    if (! is_writable($p)) {
        $add("Schreibrecht $dir", false);
    }
}
if (! is_file($basePath.'/vendor/autoload.php')) {
    $add('vendor/ vorhanden', false, 'vendor/ fehlt – ZIP unvollständig hochgeladen?');
    af_render($log, false);
}

// ---------------------------------------------------------------------------
// APP_KEY erzeugen, falls leer (wird direkt in .env geschrieben, nie angezeigt)
// ---------------------------------------------------------------------------
$appKey = (string) af_env_value($envFile, 'APP_KEY');
if ($appKey === '') {
    $newKey = 'base64:'.base64_encode(random_bytes(32));
    $env = (string) file_get_contents($envFile);
    $env = preg_match('/^APP_KEY=.*$/m', $env)
        ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$newKey, $env, 1)
        : "APP_KEY=$newKey\n".$env;
    $ok = is_writable($envFile) && file_put_contents($envFile, $env) !== false;
    $add('APP_KEY erzeugt und in .env gespeichert', $ok, $ok ? 'Bitte den Inhalt der .env zusätzlich sicher aufbewahren (Passwortmanager).' : '.env nicht beschreibbar – APP_KEY bitte von Hand eintragen.');
    if (! $ok) {
        af_render($log, false);
    }
} else {
    $add('APP_KEY vorhanden', true);
}

// Alte Caches entfernen, bevor Laravel startet (sonst würde eine veraltete
// config.php mit alten Werten geladen).
foreach (glob($basePath.'/bootstrap/cache/{config,routes-v7,events,routes}.php', GLOB_BRACE) ?: [] as $f) {
    @unlink($f);
}

// ---------------------------------------------------------------------------
// Laravel starten und Artisan-Befehle ausführen
// ---------------------------------------------------------------------------
try {
    require $basePath.'/vendor/autoload.php';
    /** @var Application $app */
    $app = require $basePath.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
} catch (Throwable $e) {
    $add('Laravel starten', false, get_class($e).': '.$e->getMessage());
    af_render($log, false);
}

$run = function (string $label, string $command, array $params = []) use ($kernel, $add): bool {
    try {
        $code = $kernel->call($command, $params);
        $add($label, $code === 0, $kernel->output());

        return $code === 0;
    } catch (Throwable $e) {
        $add($label, false, get_class($e).': '.$e->getMessage());

        return false;
    }
};

try {
    DB::connection()->getPdo();
    $add('Datenbankverbindung', true);
} catch (Throwable $e) {
    $add('Datenbankverbindung', false, 'Verbindung fehlgeschlagen – DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env prüfen. ('.get_class($e).')');
    af_render($log, false);
}

$allOk = true;
$maintenance = ! empty($_POST['maintenance']);
if ($maintenance) {
    $run('Wartungsmodus an', 'down', ['--retry' => 60]);
}

if (! empty($_POST['migrate'])) {
    $allOk = $run('Migrationen', 'migrate', ['--force' => true]) && $allOk;
}
if (! empty($_POST['seed'])) {
    $allOk = $run('Grunddaten (Seeder)', 'db:seed', ['--force' => true]) && $allOk;
}
if (! empty($_POST['demo']) && empty($_POST['seed'])) {
    $allOk = $run('Demo-Produktbilder (demo:images)', 'demo:images') && $allOk;
}
if (! empty($_POST['link'])) {
    if (! function_exists('symlink')) {
        $add('storage:link', false, 'symlink() ist auf diesem Server deaktiviert. Nicht schlimm: Die Root-.htaccess liefert /storage/… direkt aus storage/app/public aus.');
    } else {
        $run('storage:link', 'storage:link');
    }
}
// Caches leeren erst NACH der Migration (bei CACHE_STORE=database existiert
// die Cache-Tabelle vorher noch nicht).
$allOk = $run('Caches leeren (Config, Routen, Views, Anwendungs-Cache)', 'optimize:clear') && $allOk;
if (! empty($_POST['optimize'])) {
    $allOk = $run('Produktions-Caches (optimize)', 'optimize') && $allOk;
    // filament:optimize ruft icons:cache auf; das ist nur auf der Kommandozeile
    // registriert. Im Web-Kontext deshalb nur die Komponenten cachen.
    if (array_key_exists('filament:cache-components', $kernel->all())) {
        $run('Filament-Komponenten-Cache', 'filament:cache-components');
    }
}
if ($maintenance) {
    $run('Wartungsmodus aus', 'up');
}

af_render($log, $allOk);

/** @param array<int, array{0:string,1:bool,2:string}> $log */
function af_render(array $log, bool $ok): never
{
    $html = '';
    foreach ($log as [$step, $stepOk, $out]) {
        $html .= '<p class="'.($stepOk ? 'ok' : 'fail').'">'.($stepOk ? '✔' : '✘').' '.af_h($step).'</p>';
        if ($out !== '') {
            $html .= '<pre>'.af_h($out).'</pre>';
        }
    }
    $html .= $ok
        ? '<p class="ok"><strong>Fertig.</strong></p>'
        : '<p class="fail"><strong>Mindestens ein Schritt ist fehlgeschlagen.</strong> Fehler beheben, neuen INSTALL_TOKEN in die .env schreiben und erneut aufrufen. Details stehen in <code>storage/logs/</code>.</p>';
    $html .= '<p class="warn"><strong>Wichtig: Löschen Sie jetzt die Datei <code>public/install.php</code></strong> (FileZilla oder One.com-Dateimanager) und leeren Sie den Wert <code>INSTALL_TOKEN=</code> in der .env.</p>';
    af_page('Installation – Ergebnis', $html);
}
