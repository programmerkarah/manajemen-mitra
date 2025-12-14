# Sisa Pagu Workflow Documentation

## Overview
This document explains the sequential sisa pagu (remaining budget) tracking workflow implemented for the Manajemen Mitra application.

## Purpose
The system tracks the remaining budget (`sisa_pagu`) for each periode within a kegiatan, storing it sequentially based on the month order (January to December). This provides:
- **Audit Trail**: Historical tracking of budget depletion
- **Sequential Calculation**: Each periode's sisa_pagu is calculated based on the previous periode
- **Multi-Periode Support**: One kegiatan can have multiple periodes across different months
- **Budget Validation**: Prevents overspending by validating against available budget

## Database Schema

### periode_alokasi Table
```sql
ALTER TABLE periode_alokasi 
ADD COLUMN sisa_pagu DECIMAL(15,2) NULL 
AFTER status
COMMENT 'Sisa pagu kegiatan setelah periode ini ditambahkan';
```

## Workflow Logic

### 1. Sequential Calculation
When a new periode is created, the system:

1. **Finds Previous Periode**:
   - Searches for the latest periode before the current one
   - Orders by year (descending) then month (descending)
   - Only considers active periodes: `draft`, `dikirim`, `direvisi`, `disetujui`

2. **Calculates Sisa Pagu**:
   ```
   IF previous periode exists:
       sisa_pagu = previous_periode.sisa_pagu - new_periode_total_honor
   ELSE:
       sisa_pagu = kegiatan.anggaran - new_periode_total_honor
   ```

3. **Stores Value**:
   - The calculated `sisa_pagu` is stored in the `periode_alokasi` record
   - This becomes the basis for the next periode's calculation

### 2. Month Ordering
Periodes are ordered chronologically:
- First by year (ascending)
- Then by month (ascending: 01=January, 12=December)

Example sequence:
```
Kegiatan: "Survei KSA Padi" (Anggaran: Rp 25,080,000)

Periode 1: 01/2025
  - Total Honor: Rp 1,320,000
  - Sisa Pagu: Rp 23,760,000 (25,080,000 - 1,320,000)

Periode 2: 02/2025
  - Total Honor: Rp 1,320,000
  - Sisa Pagu: Rp 22,440,000 (23,760,000 - 1,320,000)

Periode 3: 03/2025
  - Total Honor: Rp 1,500,000
  - Sisa Pagu: Rp 20,940,000 (22,440,000 - 1,500,000)
```

## Implementation

### Controller: AlokasiPetugasController

#### storeMultiple Method
```php
// Calculate sisa_pagu based on previous periods (sequential by month)
$previousPeriode = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
    ->where(function ($query) use ($periodeData) {
        $query->where('tahun', '<', $periodeData['tahun'])
            ->orWhere(function ($q) use ($periodeData) {
                $q->where('tahun', $periodeData['tahun'])
                    ->where('bulan', '<', $periodeData['bulan']);
            });
    })
    ->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
    ->orderByDesc('tahun')
    ->orderByDesc('bulan')
    ->first();

// Calculate sisa_pagu for this new periode
$sisaPaguPeriode = $previousPeriode 
    ? $previousPeriode->sisa_pagu - $newPeriodeTotalHonor
    : $paguAnggaran - $newPeriodeTotalHonor;

// Store the sisa_pagu when creating periode
$periode = PeriodeAlokasi::create([
    'kegiatan_id' => $kegiatan->id,
    'bulan' => $periodeData['bulan'],
    'tahun' => $periodeData['tahun'],
    'jenis_kegiatan' => $periodeData['jenis_kegiatan'],
    'status' => 'draft',
    'sisa_pagu' => $sisaPaguPeriode,
]);
```

#### index Method
```php
// Use stored sisa_pagu instead of calculating dynamically
$sisaPagu = $periode->sisa_pagu ?? 0;
```

### Model: PeriodeAlokasi
```php
protected $fillable = [
    'kegiatan_id',
    'bulan',
    'tahun',
    'jenis_kegiatan',
    'status',
    'sisa_pagu',  // Added to fillable
    // ... other fields
];
```

## Artisan Command: Update Existing Periodes

For existing periodes that don't have `sisa_pagu` values yet:

```bash
php artisan periode:update-sisa-pagu
```

### Command Implementation
```php
// File: app/Console/Commands/UpdateSisaPaguForExistingPeriodes.php

public function handle()
{
    $kegiatanList = Kegiatan::with(['periodeAlokasi' => function ($q) {
        $q->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
            ->orderBy('tahun')
            ->orderBy('bulan');
    }])->get();

    foreach ($kegiatanList as $kegiatan) {
        $paguAnggaran = $kegiatan->anggaran ?? 0;
        $sisaPaguRunning = $paguAnggaran;

        foreach ($kegiatan->periodeAlokasi as $periode) {
            $totalHonor = $periode->alokasiPetugas->sum('total_honor');
            $sisaPaguRunning -= $totalHonor;
            
            $periode->update(['sisa_pagu' => $sisaPaguRunning]);
        }
    }
}
```

## Budget Validation

Before creating a new periode, the system validates:

```php
// Calculate total spent across all active periods
$totalSpent = $kegiatan->periodeAlokasi
    ->whereIn('status', ['draft', 'dikirim', 'direvisi'])
    ->sum(function ($p) {
        return $p->alokasiPetugas->sum('total_honor');
    });

$sisaPagu = $paguAnggaran - $totalSpent;

// Validate that sisa pagu is sufficient for new periode
if ($newPeriodeTotalHonor > $sisaPagu) {
    return back()->withErrors([
        'budget' => 'Anggaran tidak mencukupi untuk menambahkan periode ini. '.
            'Sisa pagu: '.number_format($sisaPagu, 0, ',', '.').', '.
            'Estimasi honor periode baru: '.number_format($newPeriodeTotalHonor, 0, ',', '.'),
    ]);
}
```

## Frontend Display

The Index page displays each periode's sisa_pagu with color coding:

```tsx
// Red: < 20% remaining
// Yellow: 20-50% remaining  
// Green: > 50% remaining

<td className="px-6 py-4 whitespace-nowrap text-right">
    <span className={cn("font-medium", getSisaPaguColor(period))}>
        {formatCurrency(period.sisa_pagu)}
    </span>
</td>
```

## Benefits

1. **Accurate Tracking**: Each periode stores its exact sisa_pagu at creation time
2. **Performance**: No need to recalculate across all periodes on every page load
3. **Sequential Logic**: Clear month-by-month budget progression
4. **Audit Trail**: Historical record of budget allocation decisions
5. **Multi-Kegiatan**: Works independently for each kegiatan
6. **Status Aware**: Only considers active periodes (excludes 'dihapus')

## Testing

To verify the workflow:

```php
use App\Models\Kegiatan;

$kegiatan = Kegiatan::with(['periodeAlokasi' => function($q) {
    $q->whereIn('status', ['draft', 'dikirim', 'direvisi', 'disetujui'])
      ->orderBy('tahun')
      ->orderBy('bulan');
}])->first();

foreach ($kegiatan->periodeAlokasi as $periode) {
    echo "{$periode->bulan}/{$periode->tahun}: " . 
         number_format($periode->sisa_pagu, 0, ',', '.') . "\n";
}
```

## Migration History

1. **2025_12_14_182953_add_sisa_pagu_to_periode_alokasi_table.php**
   - Added `sisa_pagu` column to `periode_alokasi` table
   - Type: DECIMAL(15,2) NULL
   - Position: After `status` column

## Related Files

- **Migration**: `database/migrations/2025_12_14_182953_add_sisa_pagu_to_periode_alokasi_table.php`
- **Model**: `app/Models/PeriodeAlokasi.php`
- **Controller**: `app/Http/Controllers/AlokasiPetugasController.php`
- **Command**: `app/Console/Commands/UpdateSisaPaguForExistingPeriodes.php`
- **Frontend**: `resources/js/Pages/Alokasi/Index.tsx`
