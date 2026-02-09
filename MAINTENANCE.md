# Maintenance Mode Management

Sistem ini dilengkapi dengan halaman UI untuk mengelola maintenance mode dengan mudah dan aman.

## 🔑 Konfigurasi

Tambahkan ke file `.env`:

```env
MAINTENANCE_BYPASS_SECRET=your-bypass-secret-here
MAINTENANCE_BYPASS_SESSION_KEY=your-session-key-here
MAINTENANCE_BYPASS_START_KEY=your-start-key-here
```

**⚠️ PENTING:** Ganti semua secret key dengan nilai yang lebih kuat untuk production!

## 🌐 Halaman UI

### 1. Bypass Maintenance Mode
**URL:** http://localhost:8000/bypass

- Masukkan kunci sesuai `MAINTENANCE_BYPASS_SECRET`
- Akses sementara ke sistem saat maintenance aktif
- Cookie tersimpan untuk navigasi selanjutnya
- **Sistem tetap dalam maintenance mode untuk user lain**

**Alternatif Query Parameter:**
```
http://localhost:8000/bypass?key=Jembaran2kali
```

### 2. Aktifkan Kembali Layanan
**URL:** http://localhost:8000/up

- Masukkan kunci sesuai `MAINTENANCE_BYPASS_SESSION_KEY`
- Sistem keluar dari maintenance mode sepenuhnya
- **Layanan kembali normal untuk semua user**

**Alternatif Query Parameter:**
```
http://localhost:8000/up?key=maintenanceIsOver
```

### 3. Masuk Maintenance Mode
**URL:** http://localhost:8000/mt

- Masukkan kunci sesuai `MAINTENANCE_BYPASS_START_KEY`
- Opsional: tambahkan pesan informasi untuk ditampilkan ke user
- **Sistem masuk maintenance mode**

**Alternatif Query Parameter:**
```
http://localhost:8000/mt?key=maintenanceIsStarting&message=Saat%20ini%20kami%20sedang%20melakukan%20peningkatan%20layanan
```

## 🔧 Cara Kerja

1. **Route `/mt`**: Masuk ke maintenance mode
   - Memerlukan kunci `MAINTENANCE_BYPASS_START_KEY`
   - Menyimpan pesan maintenance ke storage
   - Menjalankan `php artisan down --refresh=15`
   - Log aktivitas tersimpan di ActivityLog

2. **Route `/bypass`**: Bypass sementara untuk admin
   - Memerlukan kunci `MAINTENANCE_BYPASS_SECRET`
   - Set cookie bypass menggunakan Laravel MaintenanceModeBypassCookie
   - Hanya berlaku untuk browser dengan cookie tersebut

3. **Route `/up`**: Keluar dari maintenance mode
   - Memerlukan kunci `MAINTENANCE_BYPASS_SESSION_KEY`
   - Menjalankan `php artisan up`
   - Menghapus file maintenance-message.txt
   - Log aktivitas tersimpan di ActivityLog

4. **Auto-Bypass untuk Admin**:
   - Middleware `AdminMaintenanceBypass` otomatis mendeteksi user dengan role 'admin'
   - Jika admin login saat maintenance mode aktif, akan auto-set bypass cookie
   - Admin dapat mengakses semua fitur tanpa perlu manual bypass
   - Non-admin akan tetap melihat halaman maintenance

## 🛡️ Keamanan

- ✅ Semua routes maintenance dilindungi dengan secret key
- ✅ **User dengan role 'admin' otomatis bypass maintenance mode** tanpa perlu key
- ✅ Middleware mengecek role admin langsung dari database (session-based, bukan Auth facade)
- ✅ Routes `/bypass` dan `/up` tidak terpengaruh middleware PreventRequestsDuringMaintenance
- ✅ Validasi secret key menggunakan hash comparison untuk mencegah timing attack
- ✅ Session/cookie bypass otomatis dihapus saat maintenance mode dinonaktifkan
- ✅ Guest users (belum login) akan melihat halaman maintenance atau welcome

## 📝 Catatan

- Halaman UI menggunakan Inertia.js + React dengan desain modern
- Formulir menggunakan validasi Laravel
- Pesan error dan success ditampilkan dengan jelas
- Dark mode support
- Activity logging untuk audit trail

## 🚀 Command Artisan (Alternatif)

Anda tetap dapat menggunakan command artisan:

```bash
# Aktifkan maintenance mode
php artisan down --refresh=15 --secret=your-bypass-secret

# Nonaktifkan maintenance mode
php artisan up
```

## 📂 File Terkait

- Controller: `app/Http/Controllers/MaintenanceController.php`
- Middleware: 
  - `app/Http/Middleware/PreventRequestsDuringMaintenanceWithBypass.php` (Custom maintenance middleware dengan admin bypass)
  - `app/Http/Middleware/AdminMaintenanceBypass.php` (Auto-set bypass cookie untuk admin)
- Routes: `routes/web.php`
- Views: `resources/js/Pages/maintenance/` (bypass.tsx, up.tsx, down.tsx)
- Config: `config/app.php`
- Bootstrap: `bootstrap/app.php` (registrasi custom middleware)
