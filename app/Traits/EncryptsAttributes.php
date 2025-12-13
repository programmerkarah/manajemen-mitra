<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait EncryptsAttributes
{
    /**
     * Get the encrypted attributes.
     */
    protected function getEncryptedAttributes(): array
    {
        return property_exists($this, 'encrypted') ? $this->encrypted : [];
    }

    /**
     * Get an attribute from the model.
     */
    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->getEncryptedAttributes()) && ! is_null($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Set a given attribute on the model.
     */
    public function setAttribute($key, $value): mixed
    {
        if (in_array($key, $this->getEncryptedAttributes()) && ! is_null($value)) {
            try {
                $value = Crypt::encryptString($value);
            } catch (\Exception $e) {
                // If encryption fails, use original value
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get the model's original attribute values (decrypted).
     */
    public function getOriginal($key = null, $default = null): mixed
    {
        $value = parent::getOriginal($key, $default);

        if ($key && in_array($key, $this->getEncryptedAttributes()) && ! is_null($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }
}
