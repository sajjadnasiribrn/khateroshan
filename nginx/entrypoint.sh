#!/bin/sh
set -e

: "${NGINX_SERVER_NAME:=127.0.0.1 localhost}"
: "${NGINX_ROOT:=/var/www/html/public}"
: "${CORS_ALLOW_ORIGIN:=*}"


envsubst '$NGINX_SERVER_NAME $NGINX_ROOT $CORS_ALLOW_ORIGIN' \
  < /etc/nginx/default.conf > /etc/nginx/conf.d/default.conf

nginx -t
exec nginx -g 'daemon off;'
