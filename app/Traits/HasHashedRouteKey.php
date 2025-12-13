<?php

namespace App\Traits;

use Vinkla\Hashids\Facades\Hashids;

trait HasHashedRouteKey
{
    /**
     * Get the route key for the model.
     */
    public function getRouteKey(): string
    {
        return Hashids::encode($this->getKey());
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        try {
            $id = Hashids::decode($value)[0] ?? null;

            if ($id === null) {
                return null;
            }

            return $this->where($this->getKeyName(), $id)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the hashed ID attribute.
     */
    public function getHashedIdAttribute(): string
    {
        return $this->getRouteKey();
    }
}
