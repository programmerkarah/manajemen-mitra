# Enable Maintenance Mode with bypass/up exceptions
# Usage: .\maintenance-down.ps1

Write-Host "🔧 Enabling maintenance mode..." -ForegroundColor Yellow

# Enable maintenance mode first
php artisan down

# Wait a moment for file creation
Start-Sleep -Milliseconds 500

# Check if down file exists
if (Test-Path "storage/framework/down") {
    # Read, modify, and save the down file to add exceptions
    $json = Get-Content 'storage/framework/down' -Raw | ConvertFrom-Json
    $json.except = @('bypass', 'up')
    $json | ConvertTo-Json -Depth 10 -Compress | Set-Content 'storage/framework/down'
    
    Write-Host "✅ Maintenance mode enabled" -ForegroundColor Green
    Write-Host "   - Admin users: auto-bypass" -ForegroundColor Cyan
    Write-Host "   - /bypass: accessible for manual bypass" -ForegroundColor Cyan
    Write-Host "   - /up: accessible to disable maintenance" -ForegroundColor Cyan
    
    # Show current config
    $config = Get-Content 'storage/framework/down' | ConvertFrom-Json
    Write-Host "`n📋 Configuration:" -ForegroundColor Blue
    Write-Host "   Exceptions: $($config.except -join ', ')" -ForegroundColor Gray
} else {
    Write-Host "❌ Error: down file not created" -ForegroundColor Red
}
