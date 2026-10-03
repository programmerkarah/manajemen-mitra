<?php

namespace App\Actions\Fortify;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();

        ActivityLog::query()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'Reset Password',
            'type' => 'auth',
            'description' => 'Pengguna berhasil mengubah password melalui alur reset password.',
            'status' => 'success',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'user_id' => $user->id,
                'source' => 'native',
            ],
        ]);
    }
}
