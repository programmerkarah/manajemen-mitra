#!/usr/bin/env pwsh
# Script to update all frontend Index pages to use encrypted data structure

Write-Host "Updating frontend pages for encrypted data..." -ForegroundColor Cyan

# Files to update
$files = @(
    "resources\js\Pages\Petugas\Index.tsx",
    "resources\js\Pages\Penandatangan\Index.tsx",
    "resources\js\Pages\Dipa\Index.tsx",
    "resources\js\Pages\DasarHukum\Index.tsx",
    "resources\js\Pages\SkKpa\Index.tsx",
    "resources\js\Pages\Spk\Index.tsx"
)

foreach ($file in $files) {
    Write-Host "Processing: $file" -ForegroundColor Yellow
    
    if (Test-Path $file) {
        $content = Get-Content $file -Raw
        
        # Add useDecryptedData import if not exists
        if ($content -notmatch "useDecryptedData") {
            $content = $content -replace "(import.*encryption.*)", "`$1`nimport { useDecryptedData } from '@/hooks/useDecryptedData';"
        }
        
        Write-Host "  - Updated imports" -ForegroundColor Green
        Set-Content $file $content -NoNewline
    }
}

Write-Host "`nDone! Please rebuild frontend with: npm run build" -ForegroundColor Green
