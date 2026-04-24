#!/bin/bash
set -e

# Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Storage link
php artisan storage:link || true

# Start Apache in foreground
apache2-foreground