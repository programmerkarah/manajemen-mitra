# Daily Database Backup Script
# Run ini setiap hari untuk backup database

$appDbName = "manajemenmitra"
$backupPath = "E:\xampp\htdocs\manajemen-mitra\backup\daily"
$backupFile = "$backupPath\${appDbName}_$(Get-Date -Format 'yyyyMMdd').sql"
$mysqlBin = "C:\xampp\mysql\bin"

# Create backup directory
if (-not (Test-Path $backupPath)) {
    New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
}

# Backup database
Write-Host "Backing up database: $appDbName" -ForegroundColor Cyan
& "$mysqlBin\mysqldump.exe" -u root --single-transaction $appDbName > $backupFile

if (Test-Path $backupFile) {
    $fileSize = (Get-Item $backupFile).Length
    Write-Host "[✓] Backup successful: $backupFile ($($fileSize / 1MB) MB)" -ForegroundColor Green
    
    # Keep only last 7 days
    Get-ChildItem $backupPath -Filter "*.sql" | 
        Where-Object { $_.CreationTime -lt (Get-Date).AddDays(-7) } | 
        Remove-Item -Force
    
    Write-Host "[+] Old backups cleaned (keeping last 7 days)" -ForegroundColor Yellow
} else {
    Write-Host "[X] Backup failed!" -ForegroundColor Red
}
