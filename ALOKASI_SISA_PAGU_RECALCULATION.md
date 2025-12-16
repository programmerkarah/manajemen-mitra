# Alokasi Petugas - Sisa Pagu Recalculation Documentation

## Overview
Dokumentasi untuk fitur recalculation sisa pagu pada periode-periode berikutnya ketika satu periode alokasi mengalami perubahan.

## Problem Statement
Sebelumnya, ketika user melakukan perubahan pada satu periode alokasi (misalnya periode April), sisa pagu untuk periode-periode berikutnya (Mei, Juni, dst) tidak otomatis direcalculate. Ini menyebabkan data sisa_pagu tidak akurat.

### Contoh Skenario:
- Kegiatan memiliki pagu 10 juta
- Periode Januari: spent 2 juta, sisa 8 juta
- Periode Februari: spent 1.5 juta, sisa 6.5 juta  
- Periode April (draft): spent 1 juta, sisa 5.5 juta
- Periode Mei (draft): spent 1.5 juta, sisa 4 juta
- Periode Juni (draft): spent 2 juta, sisa 2 juta

**Masalah:** Ketika user mengubah April dari 1 juta menjadi 2 juta, sisa pagu untuk Mei dan Juni tidak otomatis terupdate.

**Hasil yang Diharapkan:** 
- April: spent 2 juta, sisa 4.5 juta (6.5M - 2M)
- Mei: spent 1.5 juta, sisa 3 juta (4.5M - 1.5M) ← **direcalculate**
- Juni: spent 2 juta, sisa 1 juta (3M - 2M) ← **direcalculate**

## Solution Implementation

### File Modified
`app/Http/Controllers/AlokasiPetugasController.php` - Method `updatePeriode()`

### Changes Made
1. **Removed conditional check** untuk recalculation - sebelumnya hanya berjalan saat revision:
   ```php
   // OLD: Only recalculate for revisions
   if ($isRevision && $parentPeriodeId) {
       // recalculate subsequent periods
   }
   ```

2. **Always recalculate** sisa_pagu untuk semua periode berikutnya:
   ```php
   // NEW: Always recalculate for any update
   $subsequentPeriods = PeriodeAlokasi::where('kegiatan_id', $kegiatan->id)
       ->where(function ($query) use ($tahun, $bulan) {
           $query->where('tahun', '>', $tahun)
               ->orWhere(function ($q) use ($tahun, $bulan) {
                   $q->where('tahun', $tahun)
                       ->where('bulan', '>', $bulan);
               });
       })
       ->whereIn('status', ['draft', 'dikirim', 'perubahan'])
       ->orderBy('tahun')
       ->orderBy('bulan')
       ->get();

   if ($subsequentPeriods->isNotEmpty()) {
       $currentSisaPagu = $sisaPaguPeriode;
       $currentSisaPaguListing = $sisaPaguPeriodeListing;
       
       foreach ($subsequentPeriods as $nextPeriode) {
           $nextPeriode->load('alokasiPetugas');
           $nextPeriodeTotal = $nextPeriode->alokasiPetugas->sum('total_honor');
           $nextPeriodeTotalListing = $nextPeriode->alokasiPetugas->sum('total_honor_listing');
           
           $currentSisaPagu = $currentSisaPagu - $nextPeriodeTotal;
           $currentSisaPaguListing = $currentSisaPaguListing - $nextPeriodeTotalListing;

           $nextPeriode->update([
               'sisa_pagu' => $currentSisaPagu,
               'sisa_pagu_listing' => $currentSisaPaguListing,
           ]);
       }
   }
   ```

### Logic Flow
1. User melakukan update pada periode tertentu (misal April)
2. Sistem menghitung sisa_pagu untuk periode yang diupdate
3. Sistem mencari semua periode setelah periode yang diupdate (Mei, Juni, dst)
4. Untuk setiap periode berikutnya secara berurutan:
   - Load alokasi petugas untuk periode tersebut
   - Hitung total honor (pencacahan + listing)
   - Kurangi dari sisa pagu running
   - Update sisa_pagu dan sisa_pagu_listing

### Conditions
- Recalculation hanya untuk periode dengan status: `draft`, `dikirim`, `perubahan`
- Periode yang sudah `dihapus` atau `direvisi` tidak diikutkan
- Urutan periode berdasarkan tahun dan bulan (ascending)
- Mendukung dual-phase (pencacahan + listing)

## Testing

### Unit Test
File: `tests/Feature/AlokasiPetugasSisaPaguRecalculationTest.php`

**Test Cases:**
1. `it_recalculates_sisa_pagu_for_subsequent_periods_when_updating_earlier_period`
   - Menguji ketika periode tengah (April) diupdate dengan menambah budget
   - Memverifikasi periode berikutnya (Mei, Juni) terupdate otomatis

2. `it_recalculates_sisa_pagu_when_reducing_budget_in_middle_period`
   - Menguji ketika periode tengah (Maret) diupdate dengan mengurangi budget  
   - Memverifikasi sisa pagu periode berikutnya (Mei) bertambah

### Manual Testing Steps
1. Buat kegiatan dengan pagu tertentu
2. Buat beberapa periode alokasi berurutan (Jan, Feb, Apr, Mei, Jun)
3. Submit Jan dan Feb (status: dikirim)
4. Biarkan Apr, Mei, Jun dalam status draft
5. Edit periode April - ubah jumlah volume petugas
6. Verifikasi sisa_pagu untuk Mei dan Jun terupdate otomatis

### Expected Results
- Sisa pagu periode yang diubah terkalkulasi dengan benar
- Semua periode berikutnya memiliki sisa_pagu yang accurate
- Tidak ada periode yang terlewat dalam recalculation
- Dual-phase (pencacahan + listing) keduanya direcalculate

## Benefits
1. **Data Integrity**: Sisa pagu selalu akurat di semua periode
2. **User Experience**: User tidak perlu manual recalculate periode berikutnya
3. **Budget Tracking**: Admin dapat melihat impact perubahan satu periode terhadap budget keseluruhan
4. **Audit Trail**: Semua perubahan sisa_pagu tercatat dengan timestamp

## Known Limitations
- Test suite menggunakan SQLite mengalami issue dengan migration yang complex
- Recommended: Jalankan test dengan MySQL/PostgreSQL untuk hasil accurate
- Migration `2025_12_14_172300_modify_alokasi_petugas_table_add_periode_alokasi_id.php` perlu disesuaikan untuk SQLite compatibility

## Future Improvements
1. Add notification ketika sisa pagu periode berikutnya berubah significant
2. Add warning jika perubahan menyebabkan sisa pagu negative
3. Add batch recalculation command untuk fix historical data
4. Add logging untuk track recalculation history

## Related Documentation
- [SISA_PAGU_WORKFLOW.md](SISA_PAGU_WORKFLOW.md) - Original sisa pagu implementation
- [APPROVAL_WORKFLOW_IMPLEMENTATION.md](APPROVAL_WORKFLOW_IMPLEMENTATION.md) - Approval workflow
- [ALOKASI_LISTING_FIXES.md](ALOKASI_LISTING_FIXES.md) - Listing phase implementation
