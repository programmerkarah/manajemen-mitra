<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResetUserTwoFactorController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        ActivityLog::log(
            'Reset 2FA User',
            'user',
            "Berhasil mereset two-factor authentication untuk user: {$user->name} ({$user->username})",
            'success',
            ['target_user_id' => $user->id, 'target_user_name' => $user->name, 'target_username' => $user->username]
        );

        return back()->with('success', 'Two-factor authentication berhasil direset untuk user ini.');
    }
}
