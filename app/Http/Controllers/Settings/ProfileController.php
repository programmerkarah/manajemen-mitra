<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\ActivityLog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // If view-as mode is active, update the viewed user instead of the authenticated user
        $targetUser = $request->attributes->get('view_as_user') ?? $request->user();

        $targetUser->fill($request->validated());

        $emailChanged = $targetUser->isDirty('email');

        if ($emailChanged) {
            $targetUser->email_verified_at = null;
        }

        $targetUser->save();

        ActivityLog::log(
            'Ubah Profil',
            'user',
            'Berhasil memperbarui data profil'.($emailChanged ? ' (email berubah, verifikasi ulang diperlukan)' : ''),
            'success',
            ['changed_fields' => array_keys($request->validated())]
        );

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        ActivityLog::log(
            'Hapus Akun',
            'user',
            "Akun dihapus oleh pemilik: {$user->name} ({$user->username})",
            'success',
            ['user_id' => $user->id, 'user_name' => $user->name, 'username' => $user->username]
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
