<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

if (! function_exists('terbilang')) {
    /**
     * Convert number to Indonesian text
     */
    function terbilang($nilai)
    {
        $nilai = (int) abs((int) round($nilai));
        $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $temp = '';

        if ($nilai == 0) {
            return '';
        } elseif ($nilai < 10) {
            return $huruf[$nilai].' ';
        } elseif ($nilai == 10) {
            return 'sepuluh ';
        } elseif ($nilai == 11) {
            return 'sebelas ';
        } elseif ($nilai < 20) {
            $temp = terbilang($nilai - 10).'belas ';
        } elseif ($nilai < 100) {
            $temp = terbilang(intdiv($nilai, 10)).'puluh '.terbilang($nilai % 10);
        } elseif ($nilai < 200) {
            $temp = 'seratus '.terbilang($nilai - 100);
        } elseif ($nilai < 1000) {
            $temp = terbilang(intdiv($nilai, 100)).'ratus '.terbilang($nilai % 100);
        } elseif ($nilai < 2000) {
            $temp = 'seribu '.terbilang($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $temp = terbilang(intdiv($nilai, 1000)).'ribu '.terbilang($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            $temp = terbilang(intdiv($nilai, 1000000)).'juta '.terbilang($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            $temp = terbilang(intdiv($nilai, 1000000000)).'milyar '.terbilang($nilai % 1000000000);
        } elseif ($nilai < 1000000000000000) {
            $temp = terbilang(intdiv($nilai, 1000000000000)).'trilyun '.terbilang($nilai % 1000000000000);
        }

        return $temp;
    }
}

if (! function_exists('tanggalTerbilang')) {
    /**
     * Convert date number to Indonesian text (1-31)
     */
    function tanggalTerbilang($tanggal)
    {
        return trim(terbilang($tanggal));
    }
}

if (! function_exists('tahunTerbilang')) {
    /**
     * Convert year to Indonesian text
     */
    function tahunTerbilang($tahun)
    {
        return trim(terbilang($tahun));
    }
}

if (! function_exists('effectiveUser')) {
    /**
     * Get the effective user for authorization checks.
     * Returns the viewed user if viewing as another user, otherwise returns the authenticated user.
     */
    function effectiveUser(?Request $request = null): ?User
    {
        $request = $request ?? request();

        // If session has view_as_user_id, always use that user
        if (session()->has('view_as_user_id')) {
            return User::find(session('view_as_user_id'));
        }

        // Fallback to request attribute (for legacy/edge cases)
        $viewAsUser = $request->attributes->get('view_as_user');
        if ($viewAsUser) {
            return $viewAsUser;
        }

        // Return the authenticated user
        return $request->user();
    }
}

if (! function_exists('encryptFilters')) {
    /**
     * Encrypt filter parameters using AES encryption compatible with CryptoJS
     */
    function encryptFilters(array $filters): string
    {
        // Remove empty values
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        // Get encryption key from env
        $key = config('app.filter_encryption_key', 'manajemen-mitra-filter-key-2025');

        // Convert to JSON
        $jsonString = json_encode($filters);

        // Generate random salt
        $salt = openssl_random_pseudo_bytes(8);

        // Derive key and IV using EVP_BytesToKey
        [$derivedKey, $iv] = evpBytesToKey($key, $salt, 32, 16);

        // Encrypt using OpenSSL
        $encrypted = openssl_encrypt(
            $jsonString,
            'AES-256-CBC',
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Combine "Salted__", salt, and encrypted data (CryptoJS format)
        $result = base64_encode('Salted__'.$salt.$encrypted);

        return $result;
    }
}

if (! function_exists('encryptData')) {
    /**
     * Encrypt any data (arrays, objects) for secure transmission
     */
    function encryptData($data): string
    {
        if (empty($data)) {
            return '';
        }

        // Convert to JSON
        $json = json_encode($data);

        // Get encryption key
        $key = config('app.filter_encryption_key', 'manajemen-mitra-filter-key-2025');

        // Generate random salt
        $salt = openssl_random_pseudo_bytes(8);

        // Derive key and IV using EVP_BytesToKey (compatible with CryptoJS)
        [$derivedKey, $iv] = evpBytesToKey($key, $salt, 32, 16);

        // Encrypt using AES-256-CBC
        $encrypted = openssl_encrypt(
            $json,
            'AES-256-CBC',
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Combine "Salted__", salt, and encrypted data (CryptoJS format)
        return base64_encode('Salted__'.$salt.$encrypted);
    }
}

if (! function_exists('decryptData')) {
    /**
     * Decrypt data encrypted with encryptData()
     */
    function decryptData(?string $encrypted)
    {
        if (empty($encrypted)) {
            return null;
        }

        try {
            $key = config('app.filter_encryption_key', 'manajemen-mitra-filter-key-2025');
            $data = base64_decode($encrypted);

            if (substr($data, 0, 8) === 'Salted__') {
                $salt = substr($data, 8, 8);
                $ciphertext = substr($data, 16);

                [$derivedKey, $iv] = evpBytesToKey($key, $salt, 32, 16);

                $decrypted = openssl_decrypt(
                    $ciphertext,
                    'AES-256-CBC',
                    $derivedKey,
                    OPENSSL_RAW_DATA,
                    $iv
                );

                return json_decode($decrypted, true);
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (! function_exists('decryptFilters')) {
    /**
     * Decrypt filter parameters encrypted from frontend (CryptoJS format)
     */
    function decryptFilters(?string $encrypted): array
    {
        if (empty($encrypted)) {
            return [];
        }

        try {
            // Get encryption key from env
            $key = config('app.filter_encryption_key', 'manajemen-mitra-filter-key-2025');

            // Decode base64
            $data = base64_decode($encrypted);

            // Check if it's CryptoJS "Salted__" format
            if (substr($data, 0, 8) === 'Salted__') {
                // Extract salt (8 bytes after "Salted__")
                $salt = substr($data, 8, 8);
                $ciphertext = substr($data, 16);

                // Derive key and IV using EVP_BytesToKey equivalent
                [$derivedKey, $iv] = evpBytesToKey($key, $salt, 32, 16);

                // Decrypt
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    'AES-256-CBC',
                    $derivedKey,
                    OPENSSL_RAW_DATA,
                    $iv
                );
            } else {
                // Fallback: custom format (IV prepended)
                $iv = substr($data, 0, 16);
                $ciphertext = substr($data, 16);

                $decrypted = openssl_decrypt(
                    $ciphertext,
                    'AES-256-CBC',
                    hash('sha256', $key, true),
                    0,
                    $iv
                );
            }

            if ($decrypted === false) {

                return [];
            }

            $filters = json_decode($decrypted, true);

            return is_array($filters) ? $filters : [];
        } catch (Exception $e) {

            return [];
        }
    }
}

if (! function_exists('evpBytesToKey')) {
    /**
     * EVP_BytesToKey implementation for CryptoJS compatibility
     */
    function evpBytesToKey(string $password, string $salt, int $keyLen, int $ivLen): array
    {
        $key = '';
        $iv = '';
        $hash = '';

        while (strlen($key) < $keyLen + $ivLen) {
            $hash = md5($hash.$password.$salt, true);
            $key .= $hash;
        }

        return [
            substr($key, 0, $keyLen),
            substr($key, $keyLen, $ivLen),
        ];
    }
}

if (! function_exists('getLiburNasionalIndonesia')) {
    /**
     * Get Indonesian national holidays for a given year
     * Returns array of dates in Y-m-d format
     */
    function getLiburNasionalIndonesia($tahun)
    {
        // Libur nasional tetap (tidak berubah setiap tahun)
        $liburTetap = [
            $tahun.'-01-01', // Tahun Baru Masehi
            $tahun.'-05-01', // Hari Buruh Internasional
            $tahun.'-06-01', // Hari Lahir Pancasila
            $tahun.'-08-17', // Hari Kemerdekaan RI
            $tahun.'-12-25', // Hari Raya Natal
        ];

        // Libur nasional dan cuti bersama dinamis (berubah setiap tahun)
        // Data resmi dari SKB 3 Menteri
        $liburDinamis = [];

        if ($tahun == 2025) {
            $liburDinamis = [
                '2025-03-29', // Wafat Isa Almasih
                '2025-03-30', // Cuti Bersama
                '2025-03-31', // Idul Fitri
                '2025-04-01', // Idul Fitri
                '2025-04-02', // Cuti Bersama
                '2025-04-03', // Cuti Bersama
                '2025-04-04', // Cuti Bersama
                '2025-05-29', // Kenaikan Isa Almasih
                '2025-06-06', // Idul Adha
                '2025-06-27', // Tahun Baru Islam
                '2025-09-05', // Maulid Nabi Muhammad
                '2025-12-26', // Cuti Bersama
            ];
        } elseif ($tahun == 2026) {
            // Data resmi hari libur nasional dan cuti bersama tahun 2026
            $liburDinamis = [
                // Libur Nasional
                '2026-01-16', // Isra Mikraj Nabi Muhammad S.A.W.
                '2026-02-17', // Tahun Baru Imlek 2577 Kongzili
                '2026-03-19', // Hari Suci Nyepi (Tahun Baru Saka 1948)
                '2026-03-21', // Idul Fitri 1447 Hijriah (Hari ke-1)
                '2026-03-22', // Idul Fitri 1447 Hijriah (Hari ke-2)
                '2026-04-03', // Wafat Yesus Kristus
                '2026-04-05', // Kebangkitan Yesus Kristus (Paskah)
                '2026-05-14', // Kenaikan Yesus Kristus
                '2026-05-27', // Idul Adha 1447 Hijriah
                '2026-05-31', // Hari Raya Waisak 2570 BE
                '2026-06-16', // 1 Muharam - Tahun Baru Islam 1448 Hijriah
                '2026-08-25', // Maulid Nabi Muhammad S.A.W.

                // Cuti Bersama
                '2026-02-16', // Cuti Bersama Tahun Baru Imlek
                '2026-03-18', // Cuti Bersama Hari Suci Nyepi
                '2026-03-20', // Cuti Bersama Idul Fitri
                '2026-03-23', // Cuti Bersama Idul Fitri
                '2026-03-24', // Cuti Bersama Idul Fitri
                '2026-05-15', // Cuti Bersama Kenaikan Yesus Kristus
                '2026-05-28', // Cuti Bersama Idul Adha
                '2026-12-24', // Cuti Bersama Natal
            ];
        }

        return array_merge($liburTetap, $liburDinamis);
    }
}

if (! function_exists('isHariLibur')) {
    /**
     * Check if a date is a holiday (weekend or national holiday)
     */
    function isHariLibur($date)
    {
        $carbon = Carbon::parse($date);

        // Check if weekend (Saturday or Sunday)
        if ($carbon->isWeekend()) {
            return true;
        }

        // Check if national holiday
        $liburNasional = getLiburNasionalIndonesia($carbon->year);
        $dateString = $carbon->format('Y-m-d');

        return in_array($dateString, $liburNasional);
    }
}

if (! function_exists('getHariKerjaTerakhir')) {
    /**
     * Get the last working day before or on the given date
     * If the date is a working day, return it as is
     * If the date is a holiday, return the last working day before it
     */
    function getHariKerjaTerakhir($date)
    {
        $carbon = Carbon::parse($date);

        // Loop backwards until we find a working day
        while (isHariLibur($carbon)) {
            $carbon->subDay();
        }

        return $carbon;
    }
}

if (! function_exists('bulanIndonesia')) {
    /**
     * Convert numeric month (01-12 or 1-12) to Indonesian month name
     */
    function bulanIndonesia($bulan): string
    {
        $bulanMap = [
            '01' => 'Januari', '1' => 'Januari',
            '02' => 'Februari', '2' => 'Februari',
            '03' => 'Maret', '3' => 'Maret',
            '04' => 'April', '4' => 'April',
            '05' => 'Mei', '5' => 'Mei',
            '06' => 'Juni', '6' => 'Juni',
            '07' => 'Juli', '7' => 'Juli',
            '08' => 'Agustus', '8' => 'Agustus',
            '09' => 'September', '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];

        return $bulanMap[(string) $bulan] ?? 'Unknown';
    }
}
