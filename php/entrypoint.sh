#!/bin/sh
chown -R www-data:www-data /var/www/html/wp-content/
exec docker-php-entrypoint php-fpm