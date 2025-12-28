<?php

namespace App\Traits;

trait EncryptsFilterParams
{
    /**
     * Encrypt filter parameters for Inertia response
     * This ensures filter values are encrypted when sent to frontend
     */
    protected function encryptFilterParams(array $filters): array
    {
        // Create encrypted representation
        $encrypted = [];

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                // Encrypt each non-empty filter value
                $encrypted[$key] = encrypt($value);
            }
        }

        return $encrypted;
    }

    /**
     * Decrypt filter parameters from request
     * This decrypts filter values received from frontend
     */
    protected function decryptFilterParams(array $filters): array
    {
        $decrypted = [];

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                try {
                    // Try to decrypt the value
                    $decrypted[$key] = decrypt($value);
                } catch (\Exception $e) {
                    // If decryption fails, use the value as-is (backwards compatibility)
                    $decrypted[$key] = $value;
                }
            }
        }

        return $decrypted;
    }
}
