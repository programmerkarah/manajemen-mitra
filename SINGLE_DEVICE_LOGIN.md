# Single Device Login Implementation

## Overview
Aplikasi ini telah dikonfigurasi untuk hanya mengizinkan **1 session login aktif per user** pada satu perangkat.

## Workflow

### 1. Login Pertama di PC
- User A login di PC dengan username & password
- Sistem meminta 2FA (jika sudah disetup)
- User memasukkan kode 2FA dan **mencentang "Remember this device"**
- **Session di PC disimpan** dan **device token disimpan** sebagai trusted device
- Semua session lain (jika ada) akan **dihapus otomatis**

### 2. Session Expire - Relogin di PC yang Sama
- Session user expire atau user logout
- User login kembali dengan username & password
- Karena PC adalah **trusted device**, sistem **SKIP 2FA**
- User langsung masuk tanpa perlu input kode 2FA
- Session baru dibuat, device token diperbarui

### 3. Login di Perangkat Baru (Mobile)
- User A mencoba login di Mobile dengan username & password
- Sistem **meminta 2FA** (karena Mobile belum trusted)
- User input kode 2FA dengan benar
- User centang "Remember this device" untuk menyimpan Mobile

**Hasil:**
- ✅ Session di **Mobile berhasil dibuat**
- ✅ Mobile disimpan sebagai **trusted device baru**
- ❌ Session di **PC otomatis dihapus** (logout paksa)
- ❌ PC **tidak lagi trusted device** (akan perlu 2FA saat login lagi)

### 4. Jika 2FA Gagal atau Dibatalkan di Mobile
- User mencoba login di Mobile tapi **gagal 2FA** atau **batal**
- Session Mobile **tidak dibuat**
- Session PC **tetap aktif** (tidak terpengaruh)
- PC tetap menjadi trusted device

## Technical Details

### Perubahan di FortifyServiceProvider

#### A. TwoFactorLoginResponse (Setelah 2FA Sukses)
```php
// 1. Hapus semua session lain
DB::table('sessions')
    ->where('user_id', $user->id)
    ->where('id', '!=', $currentSessionId)
    ->delete();

// 2. Expire semua trusted device lain
TrustedDevice::where('user_id', $user->id)
    ->where('user_agent', '!=', $request->userAgent())
    ->update(['expires_at' => now()->subDay()]);

// 3. Simpan device saat ini sebagai trusted (jika remember_device checked)
```

#### B. Regular Login (Untuk User Tanpa 2FA)
```php
// Listen ke event Login dan hapus session lain
Event::listen(Login::class, function ($event) {
    $currentSessionId = request()->session()->getId();
    
    DB::table('sessions')
        ->where('user_id', $event->user->id)
        ->where('id', '!=', $currentSessionId)
        ->delete();
});
```

### Database Tables

#### sessions
- `id`: Session identifier
- `user_id`: Foreign key ke users
- `ip_address`: IP address perangkat
- `user_agent`: Browser/device info
- `payload`: Session data
- `last_activity`: Timestamp aktivitas terakhir

#### trusted_devices
- `user_id`: Foreign key ke users
- `device_token`: Unique token (stored in cookie)
- `device_name`: Nama perangkat (PC, Mobile, dll)
- `user_agent`: Browser/device info
- `ip_address`: IP address perangkat
- `expires_at`: Waktu expired (30 hari)
- `last_used_at`: Timestamp terakhir digunakan

## Security Benefits

1. **Prevent Account Sharing**: User tidak bisa login di banyak device sekaligus
2. **Auto Logout**: Login di device baru otomatis logout device lama
3. **Trusted Device**: Perangkat yang dipercaya tidak perlu 2FA berulang
4. **30-Day Expiry**: Trusted device expire setelah 30 hari (harus 2FA lagi)

## User Experience

### Skenario Normal (PC Tetap)
- User selalu pakai PC yang sama
- Login pertama: Input 2FA + Remember device
- Login berikutnya: Langsung masuk (no 2FA)
- Duration: 30 hari tanpa perlu 2FA

### Skenario Ganti Device
- User ganti dari PC ke Mobile
- Login di Mobile: Input 2FA + Remember device
- **PC otomatis logout** (forced)
- Jika mau balik ke PC: Perlu 2FA lagi

### Skenario Session Expire
- Session expire setelah `SESSION_LIFETIME` menit (default 120 menit)
- Jika masih di trusted device: Relogin tanpa 2FA
- Jika trusted device expire (>30 hari): Perlu 2FA lagi

## Configuration

### .env Settings
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120  # 2 hours
```

### Trusted Device Expiry
Default: 30 hari (dapat diubah di `FortifyServiceProvider.php`)

```php
'expires_at' => now()->addDays(30),
```

## Testing

### Test Case 1: Login Normal
1. Login di Browser A dengan 2FA + remember device
2. Cek database: 1 session + 1 trusted device
3. Refresh page: Tetap login
4. Tutup browser, buka lagi: Langsung masuk (no 2FA)

### Test Case 2: Multi Device
1. Login di Browser A (Chrome) dengan 2FA + remember
2. Login di Browser B (Firefox) dengan 2FA + remember
3. Cek Browser A: Otomatis logout
4. Cek database: Hanya 1 session untuk Browser B

### Test Case 3: 2FA Gagal
1. Login di Browser A (sudah trusted)
2. Login di Browser B, tapi input 2FA salah
3. Browser B: Login gagal
4. Browser A: Tetap login (tidak logout)

## Maintenance

### Clear Expired Sessions
Laravel otomatis cleanup expired sessions jika `SESSION_DRIVER=database`.

Manual cleanup:
```bash
php artisan session:clear
```

### Clear Expired Trusted Devices
Buat command untuk cleanup:
```bash
php artisan tinker
TrustedDevice::where('expires_at', '<', now())->delete();
```

## Notes

- Cookie `trusted_device` menggunakan `httpOnly=true` dan `secure=true` (production only)
- Session menggunakan `SameSite=strict` untuk mencegah CSRF
- IP address dan User Agent digunakan untuk identifikasi device
