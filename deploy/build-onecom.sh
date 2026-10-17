#!/usr/bin/env bash
# =============================================================================
# Armory Falter – Deployment-Paket für One.com (Shared Hosting ohne SSH/Composer/Node)
#
# Erzeugt dist/armory-falter-onecom-YYYYMMDD.zip mit:
#   - Produktions-vendor/ (composer --no-dev, optimierter Autoloader)
#   - fertig gebauten Assets (public/build) – kein Node auf dem Server nötig
#   - storage/-Gerüst (nur Ordner + .gitignore), KEINE .env, keine Tests, kein .git
#   - .env.onecom.example, public/install.php (Einmal-Installer)
#
# Der ZIP-Inhalt liegt OHNE Oberordner im Archiv: im Zielordner (z. B. dem
# Ordner der Subdomain) entpacken, fertig.
#
# Aufruf:
#   deploy/build-onecom.sh            # aktueller Arbeitsstand (inkl. nicht committeter Änderungen)
#   deploy/build-onecom.sh --git      # exakt der letzte Commit (git archive HEAD)
#   deploy/build-onecom.sh --keep     # Arbeitsordner nach dem Build nicht löschen
#
# Bewusst NICHT enthalten: php artisan config:cache/route:cache – die laufen auf
# dem Server über install.php, weil dort andere .env-Werte gelten.
# =============================================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"
STAMP="$(date +%Y%m%d)"
ZIP="$DIST/armory-falter-onecom-$STAMP.zip"
FROM_GIT=0
KEEP=0
for arg in "$@"; do
    case "$arg" in
        --git) FROM_GIT=1 ;;
        --keep) KEEP=1 ;;
        -h|--help) sed -n '2,22p' "$0"; exit 0 ;;
        *) echo "Unbekannte Option: $arg" >&2; exit 2 ;;
    esac
done

need() { command -v "$1" >/dev/null 2>&1 || { echo "Fehlt: $1" >&2; exit 1; }; }
need php; need composer; need npm; need zip; need tar

WORK="$(mktemp -d "${TMPDIR:-/tmp}/af-build.XXXXXX")"
STAGE="$WORK/app"
mkdir -p "$STAGE" "$DIST"
cleanup() { if [ "$KEEP" -eq 0 ]; then rm -rf "$WORK"; else echo "Arbeitsordner behalten: $WORK"; fi; }
trap cleanup EXIT

log() { printf '\n==> %s\n' "$*"; }

# -----------------------------------------------------------------------------
log "1/7 Quellcode kopieren ($([ $FROM_GIT -eq 1 ] && echo 'git archive HEAD' || echo 'Arbeitsstand'))"
if [ "$FROM_GIT" -eq 1 ]; then
    git -C "$ROOT" archive --format=tar HEAD | tar -x -C "$STAGE"
else
    # rsync ist nicht überall vorhanden -> tar mit Ausschlüssen
    tar -C "$ROOT" \
        --exclude='./.git' \
        --exclude='./node_modules' \
        --exclude='./vendor' \
        --exclude='./dist' \
        --exclude='./tests' \
        --exclude='./storage' \
        --exclude='./deploy/.deploy.env' \
        --exclude='./.env' \
        --exclude='./.env.backup' \
        --exclude='./.env.production' \
        --exclude='./public/build' \
        --exclude='./public/hot' \
        --exclude='./public/storage' \
        --exclude='./bootstrap/cache/*.php' \
        --exclude='./.phpunit.cache' \
        --exclude='./.phpunit.result.cache' \
        --exclude='./.idea' --exclude='./.vscode' --exclude='./.fleet' --exclude='./.zed' \
        --exclude='*.log' --exclude='.DS_Store' \
        -cf - . | tar -x -C "$STAGE"
fi

# -----------------------------------------------------------------------------
log "2/7 storage/-Gerüst anlegen (nur Ordner + .gitignore)"
rm -rf "$STAGE/storage"
for d in app/public app/private framework/cache/data framework/sessions framework/testing framework/views logs; do
    mkdir -p "$STAGE/storage/$d"
done
( cd "$ROOT" && find storage -name .gitignore -print0 ) | while IFS= read -r -d '' f; do
    mkdir -p "$STAGE/$(dirname "$f")"; cp "$ROOT/$f" "$STAGE/$f"
done
mkdir -p "$STAGE/bootstrap/cache"
[ -f "$STAGE/bootstrap/cache/.gitignore" ] || printf '*\n!.gitignore\n' > "$STAGE/bootstrap/cache/.gitignore"
find "$STAGE/bootstrap/cache" -name '*.php' -delete

# -----------------------------------------------------------------------------
log "3/7 composer install --no-dev"
export COMPOSER_ALLOW_SUPERUSER=1
(
    cd "$STAGE"
    composer install --no-dev --optimize-autoloader --classmap-authoritative \
        --no-interaction --no-progress --prefer-dist
    # Fällt Composer auf "source" zurück (z. B. GitHub-Limit ohne Token), liegen
    # ganze Git-Klone samt Tests in vendor/. Das bläht das Paket auf mehrere
    # hundert MB auf -> aufräumen und Autoloader neu erzeugen.
    find vendor -type d -name .git -prune -exec rm -rf {} +
    for p in vendor/*/*/; do
        rm -rf "${p}tests" "${p}Tests" "${p}.github" "${p}docs"
    done
    rm -rf vendor/laravel/framework/bin
    composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction --no-scripts
)

# -----------------------------------------------------------------------------
log "4/7 npm ci && npm run build"
(
    cd "$STAGE"
    npm ci --no-audit --no-fund --loglevel=error
    npm run build
)
[ -f "$STAGE/public/build/manifest.json" ] || { echo "public/build/manifest.json fehlt – Build fehlgeschlagen" >&2; exit 1; }

# -----------------------------------------------------------------------------
log "5/7 Entwicklungsdateien entfernen"
rm -rf "$STAGE/node_modules" "$STAGE/tests" "$STAGE/.github" "$STAGE/deploy" "$STAGE/public/hot" "$STAGE/docker"
rm -f  "$STAGE/package.json" "$STAGE/package-lock.json" "$STAGE/vite.config.js" \
       "$STAGE/phpunit.xml" "$STAGE/.editorconfig" "$STAGE/.styleci.yml" \
       "$STAGE/.env.example" "$STAGE/.gitattributes" "$STAGE/.gitignore" \
       "$STAGE/CHANGELOG.md" "$STAGE/HANDOVER.md" "$STAGE/README.md" \
       "$STAGE/docker-compose.yml" "$STAGE/.dockerignore"
# Konzeptdokumente gehören nicht auf den Server, nur die Deployment-Anleitung
if [ -d "$STAGE/docs" ]; then
    find "$STAGE/docs" -mindepth 1 ! -name 'DEPLOYMENT-ONECOM.md' -exec rm -rf {} + 2>/dev/null || true
fi
# Sicherheitsnetz: niemals Zugangsdaten ins Paket
rm -f "$STAGE/.env" "$STAGE/deploy/.deploy.env"

# -----------------------------------------------------------------------------
log "6/7 Installer und Zugriffssperren hinzufügen"
cp "$ROOT/deploy/install.php" "$STAGE/public/install.php"

# Zusätzliche Sperren je Ordner (Schutz, falls die Root-.htaccess einmal fehlt)
DENY='# Kein Webzugriff (Armory Falter Deployment)
Require all denied'
for d in app bootstrap config database lang resources routes vendor docs; do
    [ -d "$STAGE/$d" ] && printf '%s\n' "$DENY" > "$STAGE/$d/.htaccess"
done
# storage/ selbst NICHT sperren: /storage/… wird von der Root-.htaccess intern auf
# storage/app/public umgeleitet, und Apache prüft Zugriffsrechte vor dem Rewrite.
for d in storage/app/private storage/framework storage/logs; do
    printf '%s\n' "$DENY" > "$STAGE/$d/.htaccess"
done
# Öffentliche Uploads dürfen ausgeliefert werden – aber niemals Skripte
cat > "$STAGE/storage/app/public/.htaccess" <<'EOF'
# Öffentliche Uploads (Disk "public"): Medien erlaubt, Skripte niemals.
Require all granted
<FilesMatch "\.(php\d?|phtml|phar|pl|py|cgi|sh|html?|svg|js)$">
    Require all denied
</FilesMatch>
<IfModule mod_headers.c>
    Header set Cache-Control "public, max-age=2592000"
    Header set X-Content-Type-Options "nosniff"
</IfModule>
EOF

# Plausibilitätsprüfungen
for f in .htaccess public/.htaccess public/index.php public/install.php .env.onecom.example vendor/autoload.php artisan resources/demo/products/glock-17-gen5-fs.webp; do
    [ -e "$STAGE/$f" ] || { echo "Fehlt im Paket: $f" >&2; exit 1; }
done
if find "$STAGE" -path "$STAGE/vendor" -prune -o \( -name '.env' -o -name '.deploy.env' \) -print | grep -q .; then
    echo "ABBRUCH: .env im Paket gefunden" >&2; exit 1
fi
php -r 'exit(file_exists($argv[1]."/vendor/phpunit") ? 1 : 0);' "$STAGE" || { echo "ABBRUCH: Dev-Pakete in vendor/" >&2; exit 1; }

# -----------------------------------------------------------------------------
log "7/7 ZIP erstellen"
rm -f "$ZIP"
( cd "$STAGE" && zip -rqX "$ZIP" . )
FILES="$(find "$STAGE" -type f | wc -l | tr -d ' ')"
( cd "$DIST" && sha256sum "$(basename "$ZIP")" > "$(basename "$ZIP").sha256" )

echo
echo "Fertig: $ZIP"
echo "Größe:  $(du -h "$ZIP" | cut -f1)   Dateien: $FILES"
if [ "$FILES" -gt 38000 ]; then
    echo "WARNUNG: One.com-Dateimanager entpackt höchstens 40.000 Dateien je ZIP." >&2
fi
