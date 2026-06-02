#!/bin/sh
set -e

# Set port default if not defined by Render
if [ -z "$PORT" ]; then
  export PORT=80
fi

# Replace PORT placeholder in nginx config
echo "Configuring Nginx port to $PORT..."
sed -i "s/LISTEN_PORT/$PORT/g" /etc/nginx/nginx.conf

# Check if APP_KEY is configured
if [ -z "$APP_KEY" ]; then
  echo "WARNING: APP_KEY is not set. Laravel requires an APP_KEY for security."
fi

# Optimise Laravel Configuration
echo "Caching Laravel configurations, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Execute migrations if requested (Render Release Command is preferred, but this is a useful fallback)
if [ "${RUN_MIGRATIONS}" = "true" ] || [ "${RUN_MIGRATIONS}" = "1" ]; then
  echo "Running database migrations..."
  php artisan migrate --force
fi

echo "Starting Supervisor..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
