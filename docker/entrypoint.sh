#!/bin/sh
# Entrypoint container: siapkan folder storage, sesuaikan port, jalankan cron internal, lalu Apache.
set -e
APP=/var/www/html

mkdir -p "$APP/storage/cache" "$APP/storage/logs" "$APP/storage/ads"
chown -R www-data:www-data "$APP/storage"

# Beberapa hosting container menentukan port lewat variabel PORT
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi

as_www() { su -s /bin/sh www-data -c "$1"; }

# Cron internal (tidak butuh crontab). Matikan dengan CRON_ENABLED=0
if [ "${CRON_ENABLED:-1}" = "1" ]; then
    (
        sleep 20#!/bin/sh
# Entrypoint container: siapkan folder storage, jalankan cron internal, lalu Apache.
APP=/var/www/html

mkdir -p "$APP/storage/cache" "$APP/storage/logs" "$APP/storage/ads"

# NusaPod pakai port 8080, tidak perlu ubah port lagi
# (sudah di-set waktu build)

# Di NusaPod container jalan sebagai user 1000, langsung running PHP
if [ "${CRON_ENABLED:-1}" = "1" ]; then
    (
        sleep 20
        while true; do
            /usr/local/bin/php "$APP/cron/fetch.php" || true
            sleep "${FETCH_INTERVAL:-3600}"
        done
    ) &
    (
        sleep 180
        while true; do
            /usr/local/bin/php "$APP/cron/enrich.php" || true
            sleep "${ENRICH_INTERVAL:-1800}"
        done
    ) &
fi

exec "$@"
        while true; do
            as_www "/usr/local/bin/php $APP/cron/fetch.php" || true
            sleep "${FETCH_INTERVAL:-3600}"
        done
    ) &
    (
        sleep 180
        while true; do
            as_www "/usr/local/bin/php $APP/cron/enrich.php" || true
            sleep "${ENRICH_INTERVAL:-1800}"
        done
    ) &
fi

exec "$@"
