# Rencana Perbaikan Workflow Alokasi Petugas

## Status Implementasi

### 1. ✅ Fix React Key Warning - SELESAI
- File: `resources/js/pages/Sbml/Report.tsx` line 306
- Perubahan: Tambahkan unique key prop ke span element

### 2. 🔄 Simpan Data Listing - DALAM PROGRESS  
File: `app/Http/Controllers/AlokasiPetugasController.php`

#### Method yang perlu diperbaiki:
1. **storeMultiple()** - line 244
2. **updateMultiple()** - perlu dicari
3. **Validasi** - tambahkan `jumlah_satuan_listing`

#### Perubahan yang diperlukan:

**a. Update Validasi (di storeMultiple dan updateMultiple)**
```php
$validated = $request->validate([
    // ... existing rules
    'alokasi.*.jumlah_satuan_listing' => 'nullable|integer|min:1',
]);
```

**b. Hitung total_honor_listing (setelah line 321)**
```php
$totalHonor = $rateHonor->rate * $alokasiData['jumlah_satuan'];
$totalHonorListing = 0;

// Calculate listing honor if kegiatan has listing phase
if ($kegiatan->has_listing_updating && isset($alokasiData['jumlah_satuan_listing'])) {
    $rateHonorListing = $kegiatan->rateHonors()
        ->where('status_kepegawaian', $statusKepegawaian)
        ->where('jenis_kegiatan', $alokasiData['jenis_kegiatan'])
        ->where('jenis_penugasan', $jenisPenugasan)
        ->where('status', 'aktif')
        ->where('tahun_berlaku', $alokasiData['tahun'])
        ->first();
    
    if ($rateHonorListing && $rateHonorListing->rate_listing) {
        $totalHonorListing = $rateHonorListing->rate_listing * $alokasiData['jumlah_satuan_listing'];
    }
}
```

**c. Tambahkan ke array alokasi (line 379-395)**
```php
$periodeGroups[$periodeKey]['alokasi'][] = [
    'petugas_id' => $alokasiData['petugas_id'],
    'jumlah_satuan' => $alokasiData['jumlah_satuan'],
    'jumlah_satuan_listing' => $alokasiData['jumlah_satuan_listing'] ?? null,
    'total_honor' => $totalHonor,
    'total_honor_listing' => $totalHonorListing,
    'peran' => $jenisPenugasan,
    'status_kepegawaian' => $rateHonor->status_kepegawaian,
    'catatan' => $alokasiData['catatan'] ?? null,
];
```

**d. Update Create AlokasiPetugas (line 479-491)**
```php
AlokasiPetugas::create([
    'periode_alokasi_id' => $periode->id,
    'petugas_id' => $alokasiItem['petugas_id'],
    'jumlah_satuan' => $alokasiItem['jumlah_satuan'],
    'jumlah_satuan_listing' => $alokasiItem['jumlah_satuan_listing'],
    'total_honor' => $alokasiItem['total_honor'],
    'total_honor_listing' => $alokasiItem['total_honor_listing'],
    'peran' => $alokasiItem['peran'],
    'status_kepegawaian' => $alokasiItem['status_kepegawaian'],
    'catatan' => $alokasiItem['catatan'],
]);
```

### 3. ⏳ Simpan sisa_pagu_listing - SUDAH ADA
- Line 461-467 sudah include `sisa_pagu_listing` 
- ✅ Sudah correct

### 4. ⏳ Update Index: Estimasi Honor Gabungan
File: `app/Http/Controllers/AlokasiPetugasController.php` method `index()`

Perlu update perhitungan:
- `total_honor` → include listing
- `sisa_pagu` → consider both pagu_pencacahan and pagu_listing
- Frontend card display

### 5. ⏳ Update Show: Total Estimasi Gabungan
File: method `show()` di AlokasiPetugasController

### 6. ⏳ Update Card Alokasi: Tambah Listing & Harga Satuan
File: Frontend component untuk card alokasi

### 7. ⏳ Fix Edit: Pagu Pencacahan 0
File: method `edit()` atau `editPeriode()` di AlokasiPetugasController

## Prioritas Implementasi
1. Task 2 (simpan data listing) - **CRITICAL**
2. Task 7 (fix pagu pencacahan 0) - **HIGH**
3. Task 4 (index estimasi gabungan) - **MEDIUM**
4. Task 5 (show total gabungan) - **MEDIUM**
5. Task 6 (card alokasi) - **LOW**

## Catatan
- Semua perubahan harus di-commit bertahap
- Test setelah setiap perubahan major
- Backup database sebelum test
