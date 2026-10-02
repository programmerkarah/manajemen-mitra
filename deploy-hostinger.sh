#!/bin/bash

set -euo pipefail

CACHE_DIR=".cache/deploy"
mkdir -p "$CACHE_DIR"

cleanup() {
    echo "⬆️ Bringing application back online..."
    php artisan up || true
}

hash_files() {
    sha256sum "$@" | sha256sum | awk '{print $1}'
}

hash_frontend_sources() {
    {
        sha256sum package.json package-lock.json vite.config.ts
        find resources/js resources/css -type f -print0             | sort -z             | xargs -0 sha256sum
    } | sha256sum | awk '{print $1}'
}

cache_matches() {
    local marker="$1"
    local value="$2"

    [ -f "$marker" ] && [ "$(cat "$marker")" = "$value" ]
}

trap cleanup EXIT

echo "🚀 Starting deployment to Hostinger..."

echo "🛠️  Enabling maintenance mode..."
php artisan down --retry=60

echo "📥 Pulling latest code..."
git pull --ff-only origin main

COMPOSER_HASH="$(hash_files composer.json composer.lock)"
COMPOSER_MARKER="$CACHE_DIR/composer.sha256"

if [ -f vendor/autoload.php ] && cache_matches "$COMPOSER_MARKER" "$COMPOSER_HASH"; then
    echo "📦 Composer dependencies unchanged — skipping install."
else
    echo "📦 Installing Composer dependencies..."
    composer install         --no-dev         --no-interaction         --no-progress         --prefer-dist         --optimize-autoloader
    printf '%s' "$COMPOSER_HASH" > "$COMPOSER_MARKER"
fi

NPM_HASH="$(hash_files package.json package-lock.json)"
NPM_MARKER="$CACHE_DIR/npm.sha256"

if [ -d node_modules ] && cache_matches "$NPM_MARKER" "$NPM_HASH"; then
    echo "🎨 Node dependencies unchanged — skipping npm ci."
else
    echo "🎨 Installing frontend dependencies..."
    npm ci --prefer-offline --no-audit --no-fund
    printf '%s' "$NPM_HASH" > "$NPM_MARKER"
fi

FRONTEND_HASH="$(hash_frontend_sources)"
FRONTEND_MARKER="$CACHE_DIR/frontend.sha256"

if [ -f public/build/manifest.json ] && cache_matches "$FRONTEND_MARKER" "$FRONTEND_HASH"; then
    echo "⚡ Frontend sources unchanged — skipping Vite build."
else
    echo "⚡ Building frontend assets..."
    npm run build
    printf '%s' "$FRONTEND_HASH" > "$FRONTEND_MARKER"
fi

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
echo "🌐 Visit: https://simantik.bpskotasawahlunto.cloud"
