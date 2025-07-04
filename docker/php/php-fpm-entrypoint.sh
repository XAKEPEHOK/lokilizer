#!/bin/sh
set -e

composer install
php console.php migration:run -y

exec docker-php-entrypoint php-fpm