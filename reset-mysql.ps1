# WARNING: This script will reset (delete) all MySQL databases except the system databases.
# Backup your data before running!

# Stop MySQL service
net stop mysql
net stop mysql80
net stop mariadb

# Set your XAMPP MySQL data directory
$mysqlDataDir = "C:\xampp\mysql\data"

# List of system folders to keep
$keepFolders = @('mysql', 'performance_schema', 'phpmyadmin', 'information_schema', 'sys')

# Remove all database folders except system folders
Get-ChildItem -Path $mysqlDataDir -Directory | Where-Object { $keepFolders -notcontains $_.Name } | Remove-Item -Recurse -Force

# Remove InnoDB log and temp files if exist
$logFiles = @('ib_logfile0', 'ib_logfile1', 'ibtmp1', 'ibdata1')
foreach ($file in $logFiles) {
    $filePath = Join-Path $mysqlDataDir $file
    if (Test-Path $filePath) { Remove-Item $filePath -Force }
}

# Start MySQL service
net start mysql
net start mysql80
net start mariadb

Write-Host "MySQL reset complete. All user databases deleted."
