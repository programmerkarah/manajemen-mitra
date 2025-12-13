#!/bin/bash

echo "🚀 Starting deployment to Hostinger..."

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

# Build frontend assets
echo "🎨 Building frontend assets..."
npm ci
npm run build

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
# Set directory permissions
find . -type d -exec chmod 755 {} \;
# Set file permissions
find . -type f -exec chmod 644 {} \;
# Storage and cache need write permissions
chmod -R 775 storage bootstrap/cache
# Make sure index.php is readable
chmod 644 public/index.php
# Make sure .htaccess files are readable
find . -name ".htaccess" -exec chmod 644 {} \;

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
