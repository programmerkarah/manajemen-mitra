#!/bin/bash

set -euo pipefail

cleanup() {
    echo "⬆️ Bringing application back online..."
    php artisan up || true
}

trap cleanup EXIT

echo "🚀 Starting deployment to Hostinger..."

echo "🛠️  Enabling maintenance mode..."
php artisan down --retry=60

echo "📥 Pulling latest code..."
git pull --ff-only origin main

echo "📦 Installing Composer dependencies..."
composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

echo "🎨 Installing frontend dependencies..."
npm ci --prefer-offline --no-audit --no-fund

echo "⚡ Building frontend assets..."
npm run build

echo "🔗 Ensuring storage symlink..."
if [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
    echo "✅ Symlink created"
else
    echo "ℹ️  Symlink already exists"
fi

echo "🔐 Applying targeted permissions..."
chmod -R ug+rwX storage bootstrap/cache
find public -type d -exec chmod 755 {} +
find public -type f -exec chmod 644 {} +

echo "🗄️  Running migrations..."
php artisan migrate --force

echo "🧹 Clearing stale Laravel caches..."
php artisan optimize:clear

echo "⚡ Building Laravel production caches..."
php artisan optimize

echo "✅ Deployment completed successfully!"
echo "🌐 Visit: https://manajemen-mitra.sawahlunto.io"
