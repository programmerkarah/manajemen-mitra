# SPK Re-generate Feature - Manual Testing Guide

## Prerequisites
1. Database migrated with new columns: `nomor_urut_suffix` and `nomor_urut_base`
2. Application running (npm run dev + php artisan serve)
3. User logged in with appropriate permissions

---

## Test Case 1: Initial SPK Generation (Baseline)

### Setup
1. Create a new kegiatan for January 2025
2. Create alokasi with 3 non-organik petugas:
   - Petugas A
   - Petugas B  
   - Petugas C
3. Set periode status to "dikirim" or "disetujui"

### Steps
1. Navigate to `/spk` page
2. Find January 2025 entry
3. Click "Generate SPK" button
4. Fill in:
   - Tanggal SPK: 2025-01-15
   - Sampai Tanggal: 2025-01-31
5. Submit

### Expected Results
✅ SPK generated successfully message: "SPK berhasil dibuat: 3 baru, 0 diperbarui"
✅ 3 SPK created with numbers: 1, 2, 3
✅ Check database `spk` table:
   - `nomor_spk`: "PPIS/13730/1/K/2025", "PPIS/13730/2/K/2025", "PPIS/13730/3/K/2025"
   - `nomor_urut_base`: 1, 2, 3
   - `nomor_urut_suffix`: NULL for all
   - `tanggal_spk`: 2025-01-15 for all
✅ PDF files generated in `public/spk-export/2025/01/`
✅ Orange "Re-generate SPK" button should NOT appear yet

---

## Test Case 2: Generate SPK for Another Month

### Setup
1. Create a new kegiatan for February 2025
2. Create alokasi with 2 non-organik petugas:
   - Petugas D
   - Petugas E
3. Set periode status to "dikirim"

### Steps
1. Navigate to `/spk` page
2. Find February 2025 entry
3. Click "Generate SPK" button
4. Fill in:
   - Tanggal SPK: 2025-02-10
   - Sampai Tanggal: 2025-02-28
5. Submit

### Expected Results
✅ SPK generated successfully message: "SPK berhasil dibuat: 2 baru, 0 diperbarui"
✅ 2 SPK created with numbers: 4, 5 (continuing from January)
✅ Check database `spk` table:
   - `nomor_spk`: "PPIS/13730/4/K/2025", "PPIS/13730/5/K/2025"
   - `nomor_urut_base`: 4, 5
   - `nomor_urut_suffix`: NULL for both
   - `tanggal_spk`: 2025-02-10 for both

---

## Test Case 3: Add New Activity to January (Trigger Re-generate)

### Setup
1. Go back to January 2025 kegiatan
2. Add new alokasi with 2 more non-organik petugas:
   - Petugas F
   - Petugas G
3. Ensure periode is "dikirim" or "perubahan"

### Steps
1. Navigate to `/spk` page
2. Find January 2025 entry
3. **Verify orange "Re-generate SPK" button appears** (this is the key!)
4. Click "Re-generate SPK" button
5. Fill in:
   - Tanggal SPK: 2025-01-15 (same as before)
   - Sampai Tanggal: 2025-01-31
6. Submit

### Expected Results
✅ Message: "SPK berhasil dibuat: 2 baru, 3 diperbarui"
✅ Existing SPK (1, 2, 3) should be **UPDATED**:
   - Same `nomor_spk`
   - Same `nomor_urut_base`
   - Updated `file_path` (new PDF generated)
   - Updated `nilai_kontrak` (recalculated)
   - Updated timestamps
✅ New SPK created with **SUFFIX numbers**:
   - Petugas F → nomor_spk: "PPIS/13730/3A/K/2025"
   - Petugas G → nomor_spk: "PPIS/13730/3B/K/2025"
✅ Check database for new SPKs:
   - `nomor_urut_base`: 3 (last number on 2025-01-15)
   - `nomor_urut_suffix`: 'A', 'B'
   - `tanggal_spk`: 2025-01-15
✅ February SPKs (4, 5) remain unchanged
✅ Orange "Re-generate SPK" button should now disappear

---

## Test Case 4: Re-generate Again (No Changes)

### Setup
Nothing - use existing data from Test Case 3

### Steps
1. Navigate to `/spk` page
2. Find January 2025 entry
3. Verify "Re-generate SPK" button does NOT appear
4. If you click "Generate SPK" button manually:
   - Submit with same date

### Expected Results
✅ All existing SPKs (1, 2, 3, 3A, 3B) should be **UPDATED** only
✅ Message: "SPK berhasil dibuat: 0 baru, 5 diperbarui"
✅ No new SPKs created
✅ All nomor_spk remain the same

---

## Test Case 5: Add More Petugas to January

### Setup
1. Add 1 more petugas (Petugas H) to January alokasi

### Steps
1. Navigate to `/spk` page
2. Orange "Re-generate SPK" button should appear again
3. Click it and submit

### Expected Results
✅ Message: "SPK berhasil dibuat: 1 baru, 5 diperbarui"
✅ New SPK created:
   - Petugas H → nomor_spk: "PPIS/13730/3C/K/2025"
   - `nomor_urut_base`: 3
   - `nomor_urut_suffix`: 'C'
✅ All previous SPKs updated

---

## Test Case 6: Verify Suffix Ordering in UI

### Steps
1. Navigate to SPK list or detail view
2. Look at the SPK numbers

### Expected Results
✅ SPKs appear in correct order:
   - SPK 1
   - SPK 2
   - SPK 3
   - SPK 3A (inserted after 3)
   - SPK 3B (inserted after 3A)
   - SPK 3C (inserted after 3B)
   - SPK 4
   - SPK 5

---

## Test Case 7: Addendum Re-generation (Purple Button)

### Setup
1. Create a revision for Petugas A (change alokasi amount)
2. Set periode status to "direvisi"
3. Generate Addendum SPK for Petugas A

### Steps
1. Add another revision for Petugas B
2. Navigate to `/spk` page
3. **Verify purple "Re-generate Addendum" button appears**
4. Click it and generate addendum

### Expected Results
✅ Only Petugas B gets new addendum
✅ Petugas A addendum remains unchanged
✅ Purple button disappears after generation

---

## Database Verification Queries

```sql
-- Check all SPK numbers and suffixes for January
SELECT 
    id,
    nomor_spk,
    nomor_urut_base,
    nomor_urut_suffix,
    tanggal_spk,
    created_at,
    updated_at
FROM spk
WHERE tanggal_spk = '2025-01-15'
ORDER BY nomor_urut_base, nomor_urut_suffix;

-- Check petugas assignments
SELECT 
    s.nomor_spk,
    p.nama as petugas_nama,
    s.nilai_kontrak,
    s.file_path
FROM spk s
JOIN alokasi_petugas ap ON s.alokasi_petugas_id = ap.id
JOIN petugas p ON ap.petugas_id = p.id
WHERE s.tanggal_spk = '2025-01-15'
ORDER BY s.nomor_urut_base, s.nomor_urut_suffix;

-- Verify no duplicate nomor_spk
SELECT nomor_spk, COUNT(*) as count
FROM spk
GROUP BY nomor_spk
HAVING count > 1;
```

---

## Edge Cases to Test

### Edge Case 1: Delete Petugas After SPK Generated
- Generate SPK for 3 petugas
- Remove 1 petugas from alokasi
- Re-generate SPK
- **Expected**: 2 updated, 0 new (the removed petugas SPK stays in database)

### Edge Case 2: Multiple Re-generations in One Day
- Generate SPK
- Add petugas → re-generate (creates 3A)
- Add more petugas → re-generate (creates 3B, 3C)
- **Expected**: Suffix continues incrementing (A→B→C→D...)

### Edge Case 3: Suffix Beyond 'Z'
- Add 27+ petugas requiring suffix
- **Expected**: Suffix wraps around or uses double letters (AA, AB, etc.)
- **Note**: Current implementation uses PHP's `$suffix++` which goes A→Z→AA→AB

---

## Common Issues to Watch For

❌ **Issue 1**: Suffix not incrementing correctly
- **Symptom**: Multiple SPK get same suffix (3A, 3A)
- **Check**: `$nextSuffix++` is inside the correct conditional

❌ **Issue 2**: Wrong base number used
- **Symptom**: New SPKs use base 4 instead of 3
- **Check**: `$lastNomorUrutBase` query uses correct tanggal_spk reference

❌ **Issue 3**: Existing SPKs not updating
- **Symptom**: Re-generate creates duplicate SPKs
- **Check**: `$existingSpks->get($petugasId)` returns correct match

❌ **Issue 4**: Orange button not appearing
- **Symptom**: Button never shows even with new petugas
- **Check**: Backend `hasNewKegiatanAfterSpk()` logic

❌ **Issue 5**: PDF generation fails
- **Symptom**: Error during re-generate
- **Check**: File paths, permissions on `public/spk-export/` directory

---

## Success Criteria

✅ Initial generation creates sequential numbers (1, 2, 3)
✅ Re-generate UPDATES existing SPKs with same nomor
✅ Re-generate CREATES new SPKs with suffix (3A, 3B)
✅ Suffix uses correct base number (last number on same date)
✅ Orange button appears only when needed
✅ Purple button works for addendum re-generation
✅ Database columns populated correctly
✅ PDF files generated with correct nomor in filename
✅ No duplicate nomor_spk in database
✅ Performance acceptable (< 5 seconds for 20 petugas)

---

**Testing Date:** _____________  
**Tested By:** _____________  
**Status:** ⬜ Pass | ⬜ Fail | ⬜ Needs Review
