<?php

namespace App\Policies;

use App\Models\Kegiatan;
use App\Models\User;

class KegiatanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin, Approver, Operator, dan PJ bisa lihat daftar kegiatan
        return $user->active_role && in_array($user->active_role, ['admin', 'approver', 'operator', 'ketua_tim']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Kegiatan $kegiatan): bool
    {
        // Admin, Approver, Operator, dan PJ bisa lihat detail kegiatan
        return $user->active_role && in_array($user->active_role, ['admin', 'approver', 'operator', 'ketua_tim']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Hanya Admin, Operator, dan Ketua Tim yang bisa buat kegiatan
        return $user->active_role && in_array($user->active_role, ['admin', 'operator', 'ketua_tim']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Kegiatan $kegiatan): bool
    {
        if (! $user->active_role) {
            return false;
        }

        // Admin dan Operator bisa update kegiatan yang draft atau divalidasi
        if (in_array($user->active_role, ['admin', 'operator'])) {
            return in_array($kegiatan->status, ['draft', 'divalidasi']);
        }

        // Ketua Tim hanya bisa update kegiatan yang dia pegang dan masih draft
        if ($user->active_role === 'ketua_tim') {
            return $kegiatan->ketua_tim_user_id === $user->id && $kegiatan->status === 'draft';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Kegiatan $kegiatan): bool
    {
        // Hanya Admin yang bisa hapus
        return $user->active_role === 'admin';
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Kegiatan $kegiatan): bool
    {
        if (! $user->active_role) {
            return false;
        }

        // Hanya Admin dan Approver yang bisa approve
        if (! in_array($user->active_role, ['admin', 'approver'])) {
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
        return $user->active_role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Kegiatan $kegiatan): bool
    {
        return $user->active_role === 'admin';
    }
}
