#!/usr/bin/env bash
# =============================================================================
# Armory Falter – Paket per SFTP zu One.com hochladen (Linux/macOS, benötigt lftp)
#
#   deploy/upload-sftp.sh [ZIP]                 # Paket hochladen (Standard: neuestes dist/*.zip)
#   deploy/upload-sftp.sh --remove-installer    # nur public/install.php auf dem Server löschen
#   deploy/upload-sftp.sh --dry-run [ZIP]       # nur anzeigen, was übertragen würde
#
# Zugangsdaten kommen aus deploy/.deploy.env (Vorlage: deploy/.deploy.env.example).
# Diese Datei ist in .gitignore eingetragen und darf nie committet werden.
#
# Auf dem Server wird nichts gelöscht; .env, Uploads (storage/app), Logs und
# Sitzungen bleiben unberührt, weil das Paket sie nicht enthält.
# =============================================================================
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONF="$ROOT/deploy/.deploy.env"
MODE=upload
DRY=""
ZIP=""
for arg in "$@"; do
    case "$arg" in
        --remove-installer) MODE=remove-installer ;;
        --dry-run) DRY="--dry-run" ;;
        -h|--help) sed -n '2,16p' "$0"; exit 0 ;;
        *) ZIP="$arg" ;;
    esac
done

command -v lftp >/dev/null || { echo "lftp fehlt (apt install lftp / brew install lftp)" >&2; exit 1; }
[ -f "$CONF" ] || { echo "Fehlt: $CONF (Vorlage deploy/.deploy.env.example kopieren)" >&2; exit 1; }
# .deploy.env einlesen, ohne sie als Shell-Code auszuführen
while IFS='=' read -r k v; do
    [[ "$k" =~ ^[A-Z_]+$ ]] || continue
    v="${v%\"}"; v="${v#\"}"
    export "$k=$v"
done < <(grep -E '^[A-Z_]+=' "$CONF")
: "${SFTP_HOST:?SFTP_HOST fehlt}" "${SFTP_USER:?SFTP_USER fehlt}" "${REMOTE_DIR:?REMOTE_DIR fehlt}"
SFTP_PORT="${SFTP_PORT:-22}"

CONNECT_OPTS="set sftp:auto-confirm yes; set net:max-retries 3; set net:timeout 30; set xfer:clobber on;"
if [ -n "${SFTP_KEY:-}" ]; then
    CONNECT_OPTS+=" set sftp:connect-program 'ssh -a -x -i ${SFTP_KEY}';"
    AUTH=(-u "$SFTP_USER,")
else
    : "${SFTP_PASS:?SFTP_PASS oder SFTP_KEY fehlt}"
    export LFTP_PASSWORD="$SFTP_PASS"   # nicht in der Prozessliste sichtbar
    AUTH=(-u "$SFTP_USER" --env-password)
fi

run_lftp() {
    lftp "${AUTH[@]}" -p "$SFTP_PORT" "sftp://$SFTP_HOST" -e "$CONNECT_OPTS $1; bye"
}

if [ "$MODE" = remove-installer ]; then
    run_lftp "rm -f '$REMOTE_DIR/public/install.php'"
    echo "public/install.php auf dem Server gelöscht."
    exit 0
fi

if [ -z "$ZIP" ]; then
    ZIP="$(ls -1t "$ROOT"/dist/armory-falter-onecom-*.zip 2>/dev/null | head -1 || true)"
fi
[ -f "$ZIP" ] || { echo "Kein Paket gefunden – zuerst deploy/build-onecom.sh ausführen" >&2; exit 1; }

WORK="$(mktemp -d "${TMPDIR:-/tmp}/af-upload.XXXXXX")"
trap 'rm -rf "$WORK"' EXIT
unzip -q "$ZIP" -d "$WORK/pkg"
echo "Lade $(basename "$ZIP") nach sftp://$SFTP_HOST/$REMOTE_DIR hoch …"

# Das Paket enthält unter storage/ nur das Ordnergerüst (.gitignore/.htaccess)
# und keine .env. mirror -R ohne --delete überschreibt nur Dateien, die im Paket
# liegen, und löscht nichts – Uploads, Logs, Sitzungen und .env bleiben erhalten.
# Reihenfolge: zuerst vendor/ (groß), dann der Rest.
run_lftp "mkdir -p -f '$REMOTE_DIR'; \
mirror -R $DRY --parallel=8 --no-perms --verbose=1 '$WORK/pkg/vendor' '$REMOTE_DIR/vendor'; \
mirror -R $DRY --parallel=8 --no-perms --verbose=1 --exclude-glob .env --exclude ^vendor/ '$WORK/pkg' '$REMOTE_DIR'"

cat <<EOF

Upload fertig.
Nächste Schritte:
  1. https://<domain>/install.php aufrufen (neuer INSTALL_TOKEN in der .env auf dem Server)
  2. danach: deploy/upload-sftp.sh --remove-installer
EOF
