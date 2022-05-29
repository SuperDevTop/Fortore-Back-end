#!/bin/sh
cd /var/www/html/core
php artisan key:generate --force
php artisan optimize:clear
php artisan optimize
php artisan migrate --force
chmod -R 777 bootstrap/cache/
chmod -R 777 storage
php artisan storage:link
cp -rf /var/www/html/assets /var/www/html/public/
cp -rf /var/www/html/.htaccess /var/www/html/public/
cp -rf /var/www/html/robots.txt /var/www/html/public/
cp -rf /var/www/html/scripts/index-prod.php /var/www/html/public/index.php
php-fpm