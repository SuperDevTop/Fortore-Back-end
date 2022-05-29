#!/usr/bin/env bash

if [ ! -z "$WWWUSER" ]; then
    usermod -u $WWWUSER sail
fi

if [ ! -d /.composer ]; then
    mkdir /.composer
fi

chmod -R ugo+rw /.composer
cd core  && composer install && php artisan storage:link && cp public/storage /var/www/html
if [ $# -gt 0 ]; then
    exec gosu $WWWUSER "$@"
else
    /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
