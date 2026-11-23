#!/bin/sh
set -eu

PORT="${PORT:-10000}"
case "$PORT" in
    *[!0-9]*|'')
        echo "PORT must be a numeric TCP port" >&2
        exit 1
        ;;
esac

DB_PATH="${DB_PATH:-/var/lib/ameba/ameba.sqlite}"
export DB_PATH

if [ "$DB_PATH" != ":memory:" ]; then
    DB_DIR="$(dirname -- "$DB_PATH")"
    mkdir -p "$DB_DIR"
    chown www-data:www-data "$DB_DIR"
fi

sed -i -E "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/\*:80/*:${PORT}/g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
