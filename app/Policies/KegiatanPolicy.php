<?php

namespace App\Policies;

use App\Models\Kegiatan;
use App\Models\User;

class KegiatanPolicy
{
    /**
     * Perform pre-authorization checks.
     * Handle "view as" feature by using effectiveUser instead of authenticated user.
     */
    public function before(User $user, string $ability): ?bool
    {
        // If there's a view_as session, we need to use effectiveUser for authorization
        // This allows admin to test permissions as different users
        if (session()->has('view_as_user_id')) {
            $effectiveUser = effectiveUser();

            // If effectiveUser is different from authenticated user, delegate to methods with effectiveUser
            if ($effectiveUser && $effectiveUser->id !== $user->id) {
                // We return null to continue to the actual policy methods,
                // but we'll use effectiveUser in those methods
                return null;
            }
        }

        return null; // Continue to the actual policy method
    }

    /**
     * Get the effective user (considering view_as feature).
     */
    protected function getEffectiveUser(User $user): User
    {
        if (session()->has('view_as_user_id')) {
            $effectiveUser = effectiveUser();

            return $effectiveUser ?? $user;
        }

        return $user;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        // Admin, Approver, Operator, PJ, dan Administrator bisa lihat daftar kegiatan
        return $effectiveUser->active_role && in_array($effectiveUser->active_role, ['admin', 'approver', 'operator', 'ketua_tim', 'pj', 'administrator']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Kegiatan $kegiatan): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        // Admin, Approver, Operator, PJ, dan Administrator bisa lihat detail kegiatan
        return $effectiveUser->active_role && in_array($effectiveUser->active_role, ['admin', 'approver', 'operator', 'ketua_tim', 'pj', 'administrator']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        // Hanya Admin, Operator, dan Ketua Tim yang bisa buat kegiatan
        return $effectiveUser->active_role && in_array($effectiveUser->active_role, ['admin', 'operator', 'ketua_tim']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Kegiatan $kegiatan): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        if (! $effectiveUser->active_role) {
            return false;
        }

        // Only allow editing draft or divalidasi status
        if (! in_array($kegiatan->status, ['draft', 'divalidasi'])) {
            return false;
        }

        // Admin dan Operator bisa update kegiatan yang draft atau divalidasi
        if (in_array($effectiveUser->active_role, ['admin', 'operator'])) {
            return true;
        }

        // Ketua Tim bisa update kegiatan yang dia pegang (sebagai ketua_tim atau pj_lainnya)
        if ($effectiveUser->active_role === 'ketua_tim') {
            return $kegiatan->ketua_tim_user_id === $effectiveUser->id ||
                   $kegiatan->pj_lainnya_id === $effectiveUser->id;
        }

        // PJ role bisa update jika dia assigned sebagai pj_lainnya
        if ($effectiveUser->active_role === 'pj') {
            return $kegiatan->pj_lainnya_id === $effectiveUser->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Kegiatan $kegiatan): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        // Hanya Admin yang bisa hapus
        return $effectiveUser->active_role === 'admin';
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Kegiatan $kegiatan): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        if (! $effectiveUser->active_role) {
            return false;
        }

        // Hanya Admin dan Approver yang bisa approve
        if (! in_array($effectiveUser->active_role, ['admin', 'approver'])) {
            return false;
        }

        // Hanya bisa approve kegiatan dengan status draft atau diajukan
        return in_array($kegiatan->status, ['draft', 'diajukan']);
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, Kegiatan $kegiatan): bool
    {
        // Sama dengan approve
        return $this->approve($user, $kegiatan);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Kegiatan $kegiatan): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        return $effectiveUser->active_role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Kegiatan $kegiatan): bool
    {
        $effectiveUser = $this->getEffectiveUser($user);

        return $effectiveUser->active_role === 'admin';
    }
}
