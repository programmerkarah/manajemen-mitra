<?php

if (! function_exists('terbilang')) {
    /**
     * Convert number to Indonesian text
     */
    function terbilang($nilai)
    {
        $nilai = abs($nilai);
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
            $temp = terbilang($nilai / 10).'puluh '.terbilang($nilai % 10);
        } elseif ($nilai < 200) {
            $temp = 'seratus '.terbilang($nilai - 100);
        } elseif ($nilai < 1000) {
            $temp = terbilang($nilai / 100).'ratus '.terbilang($nilai % 100);
        } elseif ($nilai < 2000) {
            $temp = 'seribu '.terbilang($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $temp = terbilang($nilai / 1000).'ribu '.terbilang($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            $temp = terbilang($nilai / 1000000).'juta '.terbilang($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            $temp = terbilang($nilai / 1000000000).'milyar '.terbilang(fmod($nilai, 1000000000));
        } elseif ($nilai < 1000000000000000) {
            $temp = terbilang($nilai / 1000000000000).'trilyun '.terbilang(fmod($nilai, 1000000000000));
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
    function effectiveUser(?Illuminate\Http\Request $request = null): ?\App\Models\User
    {
        $request = $request ?? request();

        // If session has view_as_user_id, always use that user
        if (session()->has('view_as_user_id')) {
            return \App\Models\User::find(session('view_as_user_id'));
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
                \Log::warning('Filter decryption failed: OpenSSL error');

                return [];
            }

            $filters = json_decode($decrypted, true);

            return is_array($filters) ? $filters : [];
        } catch (\Exception $e) {
            \Log::warning('Filter decryption failed: '.$e->getMessage());

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
