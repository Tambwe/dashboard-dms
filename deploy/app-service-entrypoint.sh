#!/bin/sh
set -eu

persistent_storage=/home/site/storage

mkdir -p \
    "$persistent_storage/app/public" \
    "$persistent_storage/framework/cache/data" \
    "$persistent_storage/framework/sessions" \
    "$persistent_storage/framework/views" \
    "$persistent_storage/logs"

if [ ! -f "$persistent_storage/.initialized" ]; then
    cp -a /var/www/html/storage/. "$persistent_storage/"
    touch "$persistent_storage/.initialized"
fi

rm -rf /var/www/html/storage
ln -s "$persistent_storage" /var/www/html/storage

if [ ! -e /var/www/html/public/storage ]; then
    ln -s "$persistent_storage/app/public" /var/www/html/public/storage
fi

chown -R www-data:www-data "$persistent_storage" /var/www/html/bootstrap/cache
chmod -R 775 "$persistent_storage" /var/www/html/bootstrap/cache

exec apache2-foreground
