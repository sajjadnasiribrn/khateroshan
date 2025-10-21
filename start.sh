#!/usr/bin/env sh

set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}

#if [ "$env" != "local" ]; then
#    echo "Caching configuration..."
#    (cd /var/www/html && php artisan config:cache && php artisan route:cache && php artisan view:cache)
#fi

if [ "$role" = "app" ]; then
        php artisan migrate
        echo "***"
        php artisan optimize:clear
        echo "***"
        php artisan config:clear
        echo "***"

    exec php-fpm -y /usr/local/etc/php-fpm.conf -R
#    /usr/local/bin/docker-php-entrypoint "$@"

elif [ "$role" = "queue" ]; then
    echo "🔁 Clearing Laravel cache before Supervisor reload"
        php artisan migrate
        php artisan optimize:clear
        php artisan config:clear

        mkdir -p /var/log/supervisor

        echo "🚀 Starting Supervisor..."
        /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf


        sleep 3

        echo "🔁 Forcing reload of Supervisor programs (worker restart)"
        supervisorctl update
        supervisorctl restart all

        echo "📜 Tailing Supervisor log..."
        tail -f /var/log/supervisor/supervisord.log

else
    echo "Could not match the container role \"$role\""
    exit 1
fi