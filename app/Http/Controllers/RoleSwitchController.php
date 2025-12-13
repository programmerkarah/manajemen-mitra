<?php

namespace App\Http\Controllers;

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

        // Verify user has this role
        if (! $user->roles()->where('role_id', $validated['role_id'])->exists()) {
            return back()->with('error', 'Anda tidak memiliki akses ke role tersebut.');
        }

        // Set active role in session
        $role = Role::find($validated['role_id']);
        $user->setActiveRole($validated['role_id']);

        return back()->with('success', "Role berhasil diubah ke {$role->display_name}.");
    }
}
