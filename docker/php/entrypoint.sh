#!/bin/sh
set -e
cd /var/www/html

# .env aus der Vorlage erzeugen (bleibt im Volume erhalten)
if [ ! -s storage/app/.env.docker ]; then
    cp docker/env.docker storage/app/.env.docker
fi
ln -sf storage/app/.env.docker .env

# Nur der Web-Container erzeugt den Schlüssel; startet der Scheduler gleichzeitig, wartet er darauf.
# Zwei parallele key:generate hinterlassen sonst unterschiedliche Schlüssel und jede Seite liefert 500.
if [ "${ARMORY_ROLE:-web}" = "web" ]; then
    if ! grep -q '^APP_KEY=base64:' .env; then
        php artisan key:generate --force --no-interaction
    fi
else
    i=0
    until grep -q '^APP_KEY=base64:' .env; do
        i=$((i+1)); [ $i -gt 90 ] && echo "APP_KEY fehlt" && exit 1; sleep 2
    done
fi

if [ "${ARMORY_ROLE:-web}" = "web" ]; then
    echo "Warte auf die Datenbank ..."
    i=0
    until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=3306", getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
        i=$((i+1)); [ $i -gt 60 ] && echo "Datenbank nicht erreichbar" && exit 1; sleep 2
    done
    php artisan migrate --force --no-interaction
    if [ ! -f storage/app/.seeded ]; then
        php artisan db:seed --force --no-interaction && touch storage/app/.seeded
    fi
    php artisan optimize:clear >/dev/null
    php artisan config:cache && php artisan route:cache && php artisan view:cache
    php artisan filament:cache-components >/dev/null 2>&1 || true
fi

chown -R www-data:www-data storage bootstrap/cache
exec "$@"
