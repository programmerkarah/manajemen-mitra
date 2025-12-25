# SPK Re-generate dan Addendum Re-generate Feature

## Overview
Feature ini memungkinkan admin untuk me-re-generate SPK atau Addendum SPK ketika ada kegiatan/revisi baru yang ditambahkan setelah SPK/Addendum sudah di-generate sebelumnya.

## Frontend Changes (Already Implemented)

### File: `resources/js/pages/Spk/Index.tsx`

#### 1. Interface Updates
Ditambahkan 2 field baru pada `MonthlyPeriodeItem`:
```typescript
interface MonthlyPeriodeItem {
    // ... existing fields
    has_new_kegiatan_after_spk: boolean; // Kegiatan baru setelah SPK di-generate
    has_new_revision_after_addendum: boolean; // Revisi baru setelah addendum di-generate
}
```

#### 2. UI Components Added

**A. Re-generate SPK Button**
- **Kondisi muncul**: 
  - `monthData.total_spk > 0` (SPK sudah ada)
  - `monthData.has_new_kegiatan_after_spk === true` (Ada kegiatan baru)
- **Warna**: Orange (`bg-orange-600 hover:bg-orange-700`)
- **Route**: Same as generate SPK (`/spk/periode/{periode_hashed_id}/generate`)
- **Label**: "Re-generate SPK"

**B. Re-generate Addendum Button**
- **Kondisi muncul**:
  - `monthData.total_spk > 0` (SPK sudah ada)
  - `monthData.has_addendum === true` (Addendum sudah dibuat)
  - `monthData.has_new_revision_after_addendum === true` (Ada revisi baru)
- **Warna**: Purple (`bg-purple-600 hover:bg-purple-700`)
- **Route**: Same as addendum (`/spk/periode/{periode_hashed_id}/addendum?bulan={bulan}&tahun={tahun}`)
- **Label**: "Re-generate Addendum"

## Backend Implementation Required

### 1. Controller: `app/Http/Controllers/SpkController.php` (atau sejenisnya)

#### Method: `index()` atau method yang mengembalikan data untuk halaman SPK list

Tambahkan logika untuk menghitung 2 field baru:

```php
foreach ($monthlyData as $monthData) {
    // Field 1: has_new_kegiatan_after_spk
    // Cek apakah ada alokasi periode yang statusnya 'dikirim' atau 'perubahan'
    // yang created_at-nya LEBIH BARU dari SPK terakhir yang di-generate untuk bulan tersebut
    
    $latestSpkCreatedAt = Spk::where('tahun', $monthData->tahun)
        ->where('bulan', $monthData->bulan)
        ->max('created_at');
    
    if ($latestSpkCreatedAt) {
        $hasNewKegiatanAfterSpk = AlokasiPeriode::where('bulan', $monthData->bulan)
            ->where('tahun', $monthData->tahun)
            ->whereIn('status', ['dikirim', 'perubahan'])
            ->where('updated_at', '>', $latestSpkCreatedAt)
            ->exists();
    } else {
        $hasNewKegiatanAfterSpk = false;
    }
    
    $monthData->has_new_kegiatan_after_spk = $hasNewKegiatanAfterSpk;
    
    
    // Field 2: has_new_revision_after_addendum
    // Cek apakah ada revisi alokasi (status 'perubahan')
    // yang created_at-nya LEBIH BARU dari Addendum SPK terakhir yang di-generate
    
    $latestAddendumCreatedAt = SpkAddendum::where('tahun', $monthData->tahun)
        ->where('bulan', $monthData->bulan)
        ->max('created_at');
    
    if ($latestAddendumCreatedAt) {
        $hasNewRevisionAfterAddendum = AlokasiPeriode::where('bulan', $monthData->bulan)
            ->where('tahun', $monthData->tahun)
            ->where('status', 'perubahan')
            ->where('updated_at', '>', $latestAddendumCreatedAt)
            ->exists();
    } else {
        $hasNewRevisionAfterAddendum = false;
    }
    
    $monthData->has_new_revision_after_addendum = $hasNewRevisionAfterAddendum;
}
```

### 2. Generate SPK Logic Update

Ketika user klik "Re-generate SPK":
- Generate SPK baru HANYA untuk kegiatan/petugas yang belum memiliki SPK
- Jangan regenerate SPK yang sudah ada
- Gunakan nomor urut berikutnya

### 3. Generate Addendum Logic Update

Ketika user klik "Re-generate Addendum":
- Generate Addendum baru HANYA untuk petugas dengan revisi yang belum ter-cover di addendum sebelumnya
- Jangan regenerate addendum yang sudah ada
- Gunakan nomor urut addendum berikutnya

## Database Consideration

Pastikan ada tracking untuk:
1. Timestamp SPK generation (`spks.created_at`)
2. Timestamp Addendum generation (`spk_addendums.created_at`)
3. Timestamp alokasi periode update (`alokasi_periodes.updated_at`)
4. Status alokasi periode untuk membedakan original vs revision

## Testing Checklist

### Test Case 1: Re-generate SPK
1. Generate SPK untuk bulan Januari dengan 2 kegiatan
2. Tambahkan kegiatan ke-3 untuk bulan Januari (status dikirim)
3. Verify tombol "Re-generate SPK" muncul dengan warna orange
4. Klik "Re-generate SPK"
5. Verify SPK baru ter-generate hanya untuk kegiatan ke-3
6. Verify nomor urut SPK melanjutkan dari yang terakhir

### Test Case 2: Re-generate Addendum
1. Generate SPK untuk bulan Februari
2. Buat revisi alokasi untuk petugas A
3. Generate Addendum SPK
4. Buat revisi alokasi lagi untuk petugas B (setelah addendum di-generate)
5. Verify tombol "Re-generate Addendum" muncul dengan warna purple
6. Klik "Re-generate Addendum"
7. Verify Addendum baru ter-generate hanya untuk revisi petugas B
8. Verify nomor addendum melanjutkan dari yang terakhir

## UI Preview

**Button Colors:**
- Normal Generate SPK: Blue (default)
- Re-generate SPK: **Orange** (`bg-orange-600`)
- Normal Addendum: Blue (default)
- Re-generate Addendum: **Purple** (`bg-purple-600`)

## Notes
- Re-generate tidak menghapus SPK/Addendum yang sudah ada
- Re-generate hanya menambahkan SPK/Addendum baru untuk data yang belum ter-cover
- User harus memiliki permission `can.create.spk` untuk mengakses fitur ini
