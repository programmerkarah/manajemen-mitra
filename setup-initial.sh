#!/bin/bash

echo "🔧 Initial Setup for Hostinger Deployment"

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    exit 1
fi

echo "✅ .env file found"

# Fix permissions first
echo "🔐 Fixing permissions..."
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache

# Clear all caches
echo "🧹 Clearing all caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Create sessions table if using database session
echo "📊 Checking database connection..."
php artisan tinker --execute="echo 'DB Connected: ', DB::connection()->getPdo() ? 'Yes' : 'No', PHP_EOL;"

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Create storage symlink
echo "🔗 Creating storage symlink..."
if [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
    echo "✅ Symlink created"
else
    echo "ℹ️  Symlink already exists"
fi

# Cache config for production
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Test routes
echo "🧪 Testing routes..."
php artisan route:list | head -20

echo ""
echo "✅ Setup completed!"
echo "🌐 Try accessing: https://manajemen-mitra.sawahlunto.io"
