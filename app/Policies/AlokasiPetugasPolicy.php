<?php

namespace App\Policies;

use App\Models\AlokasiPetugas;
use App\Models\User;

class AlokasiPetugasPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->active_role && in_array($user->active_role, ['admin', 'approver', 'operator', 'ketua_tim']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AlokasiPetugas $alokasiPetugas): bool
    {
        return $user->active_role && in_array($user->active_role, ['admin', 'approver', 'operator', 'ketua_tim']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (! $user->active_role) {
            return false;
        }

        // Admin dan operator tetap bisa
        if (in_array($user->active_role, ['admin', 'operator'])) {
            return true;
        }

        // Ketua tim bisa create jika dia adalah ketua_tim_user_id atau pj_lainnya_id pada kegiatan
        if ($user->active_role === 'ketua_tim') {
            // Perlu akses ke model Kegiatan, jadi pengecekan lebih baik di controller
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AlokasiPetugas $alokasiPetugas): bool
    {
        if (! $user->active_role) {
            return false;
        }

        // Admin dan Operator bisa update alokasi yang draft
        if (in_array($user->active_role, ['admin', 'operator'])) {
            return $alokasiPetugas->status === 'draft';
        }

        // Ketua Tim bisa update alokasi yang dia miliki (ketua_tim_user_id) atau pj_lainnya_id dan masih draft
        if ($user->active_role === 'ketua_tim') {
            $kegiatan = $alokasiPetugas->kegiatan;
            return (
                ($kegiatan->ketua_tim_user_id === $user->id || $kegiatan->pj_lainnya_id === $user->id)
                && $alokasiPetugas->status === 'draft'
            );
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AlokasiPetugas $alokasiPetugas): bool
    {
        if (! $user->active_role) {
            return false;
        }

        // Hanya admin yang bisa delete alokasi yang masih draft
        return $user->active_role === 'admin' && $alokasiPetugas->status === 'draft';
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, AlokasiPetugas $alokasiPetugas): bool
    {
        if (! $user->active_role) {
            return false;
        }

        if (! in_array($user->active_role, ['admin', 'approver'])) {
            return false;
        }

        return $alokasiPetugas->status === 'diajukan';
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, AlokasiPetugas $alokasiPetugas): bool
    {
        return $this->approve($user, $alokasiPetugas);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AlokasiPetugas $alokasiPetugas): bool
    {
        return $user->active_role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AlokasiPetugas $alokasiPetugas): bool
    {
        return $user->active_role === 'admin';
    }
}
