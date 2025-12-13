#!/bin/bash

echo "🚀 Starting deployment to Hostinger..."

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

# Create storage symlink manually (exec is disabled on Hostinger)
echo "🔗 Creating storage symlink..."
if [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
    echo "✅ Symlink created"
else
    echo "ℹ️  Symlink already exists"
fi

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Cache for production
echo "⚡ Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Fix ownership (optional, adjust username)
# chown -R u130939529:u130939529 storage bootstrap/cache

echo "✅ Deployment completed successfully!"
echo "🌐 Visit: https://manajemen-mitra.sawahlunto.io"
