# Disable Maintenance Mode
# Usage: .\maintenance-up.ps1

Write-Host "🚀 Disabling maintenance mode..." -ForegroundColor Yellow

php artisan up

if (Test-Path "storage/framework/down") {
    Write-Host "⚠️  Down file still exists!" -ForegroundColor Red
} else {
    Write-Host "✅ Application is now live" -ForegroundColor Green
}
