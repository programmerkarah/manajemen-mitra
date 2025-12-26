# Performance Optimization - December 26, 2025

## Masalah
Load time halaman mencapai 3-5 detik karena query database yang tidak efisien.

## Optimasi yang Dilakukan

### 1. SbmlReportController (`/rekap-honor`)
**Masalah:**
- N+1 query problem: Query SBML dilakukan berulang untuk setiap petugas dan setiap peran
- Data yang sama di-fetch berkali-kali

**Solusi:**
- Pre-fetch semua SBML data dalam 1 query dan cache dalam Collection
- Filter alokasi dengan `jumlah > 0` di level query (tidak di loop)
- Optimize eager loading dengan select spesifik columns
- Gunakan cache lookup (O(1)) instead of database query (O(n))

**Estimated improvement:** 70-80% faster (dari ~3-5 detik ke ~0.5-1 detik)

### 2. AlokasiPetugasController (`/alokasi`)
**Masalah:**
- Query `totalHonorTerpakai` dilakukan per periode dalam loop transform
- N+1 query untuk setiap periode alokasi
- Over-fetching columns yang tidak diperlukan

**Solusi:**
- Pre-calculate total honor terpakai untuk semua kegiatan dalam 1 query menggunakan JOIN
- Store hasil dalam Collection dan lookup saat transform
- Select spesifik columns di eager loading
- Menghilangkan ratusan query yang tidak perlu

**Estimated improvement:** 60-70% faster

### 3. SpkController (`/spk`)
**Masalah:**
- Over-fetching data dengan eager loading yang tidak efisien
- Ambiguitas kolom `id` dan `created_at` saat JOIN
- Filter non-organik di application level (PHP) bukan database
- Multiple query untuk data yang sama

**Solusi:**
- Select only needed columns dari tabel utama dengan prefix `spk.id`, `spk.created_at`
- Simplify eager loading relationships dengan select spesifik
- Gunakan `whereHas` untuk filter non-organik di database level
- Optimize select pada related models (kegiatan, periodeAlokasi, petugas)
- Remove unnecessary filtering di PHP loop

**Estimated improvement:** 50-60% faster

### 4. SkKpaController (`/sk-kpa`)
**Masalah:**
- Count SK dilakukan di aplikasi level (PHP loop)
- Eager loading dengan ordering memperlambat query  
- Column `hashed_id` tidak exist di tabel `sk_kpa`

**Solusi:**
- Gunakan `withCount()` untuk count di database level
- Remove `hashed_id` dari select (kolom tidak ada)
- Select only needed columns dengan spesifik list

**Estimated improvement:** 30-40% faster

### 5. KegiatanController (`/kegiatan`)
**Masalah:**
- Eager loading ketuaTim tanpa column selection
- Over-fetching data

**Solusi:**
- Select spesifik columns untuk ketuaTim: `id,name`
- Tambah select pada query utama: `kegiatan.*`

**Estimated improvement:** 20-30% faster

### 6. DashboardController (`/dashboard`)
**Masalah:**
- Eager loading banyak relasi tanpa column selection
- N+1 query pada recent activities

**Solusi:**
- Select spesifik columns untuk semua eager loading
- Optimize dengan `select('periode_alokasi.*')`
- Filter columns: kegiatan, petugas, alokasiPetugas

**Estimated improvement:** 25-35% faster

## Best Practices yang Diterapkan

1. **Pre-fetch & Cache:** Load data sekali, gunakan berkali-kali
2. **Select Specific Columns:** Hindari `SELECT *`, ambil hanya yang diperlukan
3. **Filter di Database:** Jangan filter di aplikasi level
4. **Batch Operations:** Gabungkan multiple queries menjadi satu
5. **Avoid N+1:** Selalu eager load relationships yang digunakan

## Rekomendasi Tambahan

### Database Indexes
Tambahkan index pada kolom yang sering di-query:
```sql
-- periode_alokasi
CREATE INDEX idx_periode_tahun_bulan_status ON periode_alokasi(tahun, bulan, status);
CREATE INDEX idx_periode_kegiatan ON periode_alokasi(kegiatan_id, tahun, bulan);

-- alokasi_petugas  
CREATE INDEX idx_alokasi_periode ON alokasi_petugas(periode_alokasi_id);
CREATE INDEX idx_alokasi_petugas ON alokasi_petugas(petugas_id);

-- sbml
CREATE INDEX idx_sbml_lookup ON sbml(tahun_anggaran, jenis_kegiatan, status_kepegawaian, jenis_penugasan, status);

-- spk
CREATE INDEX idx_spk_periode_petugas ON spk(periode_alokasi_id, petugas_id);
```

### Query Caching
Implement Laravel query caching untuk data yang jarang berubah:
```php
$bulanOptions = Cache::remember('bulan_options', 3600, function () {
    return collect(range(1, 12))->map(...);
});
```

### Database Connection Pooling
Pastikan MySQL connection pooling dioptimalkan di `config/database.php`:
```php
'options' => [
    PDO::ATTR_PERSISTENT => true,
]
```

### Response Caching
Gunakan Inertia SSR atau implement HTTP caching headers untuk halaman static.

## Monitoring

Gunakan Laravel Telescope atau Debugbar untuk monitoring:
- Query count per request
- Slow queries (> 100ms)
- Memory usage
- Total request time

Target metrics:
- Query count: < 20 per request
- Total time: < 500ms
- Memory: < 50MB

## Testing

Jalankan test untuk memastikan output tidak berubah:
```bash
php artisan test --filter=SbmlReportTest
php artisan test --filter=AlokasiTest
php artisan test --filter=SpkTest
```
