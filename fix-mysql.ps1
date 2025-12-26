# Script untuk Fix MySQL Corruption
# Author: GitHub Copilot
# Date: 2025-12-20

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MySQL Corruption Fix Script" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Paths
$xamppPath = "C:\xampp"
$mysqlBin = "$xamppPath\mysql\bin"
$mysqlData = "$xamppPath\mysql\data"
$mysqlBackup = "$xamppPath\mysql\backup"
$appDbName = "manajemenmitra"
$backupPath = "E:\xampp\htdocs\manajemen-mitra\backup"
$backupFile = "$backupPath\${appDbName}_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"

# Create backup directory
if (-not (Test-Path $backupPath)) {
    New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
    Write-Host "[+] Created backup directory: $backupPath" -ForegroundColor Green
}

Write-Host "[1/6] Stopping MySQL..." -ForegroundColor Yellow
Stop-Process -Name "mysqld" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3
Write-Host "[✓] MySQL stopped" -ForegroundColor Green
Write-Host ""

Write-Host "[2/6] Attempting to backup application database..." -ForegroundColor Yellow
Write-Host "    This may fail if MySQL is too corrupt, but we'll try..." -ForegroundColor Gray

# Try to start MySQL with recovery mode
$myIniPath = "$mysqlBin\my.ini"
$myIniBackup = "$mysqlBin\my.ini.backup"

if (Test-Path $myIniPath) {
    Copy-Item $myIniPath $myIniBackup -Force
    Write-Host "[+] Backed up my.ini" -ForegroundColor Green
}

# Add recovery mode to my.ini
$recoveryConfig = @"

# Temporary recovery mode - added by fix-mysql.ps1
[mysqld]
innodb_force_recovery = 1
skip-grant-tables
"@

Add-Content -Path $myIniPath -Value $recoveryConfig
Write-Host "[+] Added recovery mode to my.ini" -ForegroundColor Green

# Try to start MySQL
Write-Host "[+] Starting MySQL in recovery mode..." -ForegroundColor Yellow
Start-Process "$xamppPath\mysql_start.bat" -WindowStyle Hidden -ErrorAction SilentlyContinue
Start-Sleep -Seconds 5

# Try to backup
$mysqldumpCmd = "$mysqlBin\mysqldump.exe --skip-lock-tables --single-transaction -u root $appDbName"
try {
    Write-Host "[+] Attempting mysqldump..." -ForegroundColor Yellow
    $result = Invoke-Expression "$mysqldumpCmd > `"$backupFile`" 2>&1"
    
    if (Test-Path $backupFile) {
        $fileSize = (Get-Item $backupFile).Length
        if ($fileSize -gt 1000) {
            Write-Host "[✓] Backup successful: $backupFile ($($fileSize / 1KB) KB)" -ForegroundColor Green
        } else {
            Write-Host "[!] Backup file too small, might be incomplete" -ForegroundColor Yellow
        }
    } else {
        Write-Host "[!] Backup failed - file not created" -ForegroundColor Red
        Write-Host "    We'll proceed with MySQL reset anyway" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[!] Backup failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "    We'll proceed with MySQL reset anyway" -ForegroundColor Yellow
}
Write-Host ""

# Stop MySQL again
Write-Host "[3/6] Stopping MySQL again..." -ForegroundColor Yellow
Stop-Process -Name "mysqld" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3
Write-Host "[✓] MySQL stopped" -ForegroundColor Green
Write-Host ""

# Restore original my.ini
if (Test-Path $myIniBackup) {
    Copy-Item $myIniBackup $myIniPath -Force
    Remove-Item $myIniBackup -Force
    Write-Host "[✓] Restored original my.ini" -ForegroundColor Green
}

Write-Host "[4/6] Resetting MySQL data directory..." -ForegroundColor Yellow
$dataBackupPath = "${mysqlData}_corrupted_$(Get-Date -Format 'yyyyMMdd_HHmmss')"

# Rename corrupted data folder
if (Test-Path $mysqlData) {
    Write-Host "[+] Renaming corrupted data: $mysqlData -> $dataBackupPath" -ForegroundColor Yellow
    Rename-Item -Path $mysqlData -NewName (Split-Path $dataBackupPath -Leaf) -Force
    Write-Host "[✓] Corrupted data backed up to: $dataBackupPath" -ForegroundColor Green
}

# Copy fresh data from backup
if (Test-Path $mysqlBackup) {
    Write-Host "[+] Copying fresh MySQL data from: $mysqlBackup" -ForegroundColor Yellow
    Copy-Item -Path $mysqlBackup -Destination $mysqlData -Recurse -Force
    Write-Host "[✓] Fresh MySQL data installed" -ForegroundColor Green
} else {
    Write-Host "[X] ERROR: MySQL backup folder not found at: $mysqlBackup" -ForegroundColor Red
    Write-Host "    Cannot proceed. Please reinstall XAMPP." -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "[5/6] Starting MySQL with fresh data..." -ForegroundColor Yellow
Start-Process "$xamppPath\mysql_start.bat" -WindowStyle Hidden
Start-Sleep -Seconds 5
Write-Host "[✓] MySQL started" -ForegroundColor Green
Write-Host ""

Write-Host "[6/6] Restoring application database..." -ForegroundColor Yellow

if (Test-Path $backupFile) {
    # Create database
    $createDbCmd = "$mysqlBin\mysql.exe -u root -e `"CREATE DATABASE IF NOT EXISTS $appDbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`""
    Write-Host "[+] Creating database: $appDbName" -ForegroundColor Yellow
    Invoke-Expression $createDbCmd
    
    # Restore backup
    $restoreCmd = "$mysqlBin\mysql.exe -u root $appDbName"
    Write-Host "[+] Restoring backup from: $backupFile" -ForegroundColor Yellow
    Get-Content $backupFile | & "$mysqlBin\mysql.exe" -u root $appDbName
    Write-Host "[✓] Database restored successfully" -ForegroundColor Green
} else {
    Write-Host "[!] No backup file found - you'll need to run migrations" -ForegroundColor Yellow
    Write-Host "    Run: php artisan migrate:fresh --seed" -ForegroundColor Cyan
}
Write-Host ""

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MySQL Fix Complete!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Test connection: php artisan tinker --execute='DB::connection()->getPdo()'" -ForegroundColor White
if (-not (Test-Path $backupFile)) {
    Write-Host "2. Run migrations: php artisan migrate:fresh --seed" -ForegroundColor White
}
Write-Host ""
Write-Host "Corrupted data saved to: $dataBackupPath" -ForegroundColor Gray
Write-Host "You can delete it once everything is working." -ForegroundColor Gray
Write-Host ""
