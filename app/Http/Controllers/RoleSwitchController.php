<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleSwitchController extends Controller
{
    /**
     * Switch the user's active role.
     */
    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = $request->user();

        // Get the viewed user if viewing as another user
        $viewAsUser = $request->attributes->get('view_as_user');
        $targetUser = $viewAsUser ?? $user;

        // Verify target user has this role
        if (! $targetUser->roles()->where('role_id', $validated['role_id'])->exists()) {
            return back()->with('error', 'Maaf, Anda tidak memiliki izin untuk menggunakan role ini.');
        }

        // Set active role in session
        $role = Role::find($validated['role_id']);
        $targetUser->setActiveRole($validated['role_id']);

        ActivityLog::log(
            'Switch Role',
            'user',
            "Ganti role aktif menjadi: {$role->display_name}",
            'success',
            ['role_id' => $role->id, 'role_name' => $role->name, 'role_display_name' => $role->display_name]
        );

        $redirectUrl = $request->headers->get('referer') ?? route('dashboard');

        // Force full page reload by redirecting to current URL with X-Inertia-Location header
        // This ensures Inertia cache is cleared and fresh data is loaded
        return redirect($redirectUrl)
            ->with('success', "Peran Anda sekarang sudah berubah menjadi {$role->display_name}.")
            ->header('X-Inertia-Location', $redirectUrl);
    }
}
