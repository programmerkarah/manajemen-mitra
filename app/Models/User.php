<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function kegiatanDikelola(): HasMany
    {
        return $this->hasMany(Kegiatan::class, 'ketua_tim_user_id');
    }

    public function alokasiDiajukan(): HasMany
    {
        return $this->hasMany(AlokasiMitra::class, 'submitted_by');
    }

    public function alokasiDisetujui(): HasMany
    {
        return $this->hasMany(AlokasiMitra::class, 'approved_by');
    }

    public function skKpaDibuat(): HasMany
    {
        return $this->hasMany(SkKpa::class, 'created_by');
    }

    public function spkDibuat(): HasMany
    {
        return $this->hasMany(Spk::class, 'created_by');
    }

    public function bastDibuat(): HasMany
    {
        return $this->hasMany(Bast::class, 'created_by');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $role);
        }

        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function assignRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel && ! $this->roles()->where('role_id', $roleModel->id)->exists()) {
            $this->roles()->attach($roleModel->id);
        }
    }

    public function removeRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isOperator(): bool
    {
        return $this->hasRole('operator');
    }

    public function isPJ(): bool
    {
        return $this->hasRole('pj');
    }

    public function isKetuaTim(): bool
    {
        return $this->hasRole('ketua_tim');
    }

    public function isApprover(): bool
    {
        return $this->hasRole('approver');
    }

    /**
     * Get the user's active role from session.
     */
    public function getActiveRole(): ?Role
    {
        $activeRoleId = session('active_role_id');

        if (! $activeRoleId) {
            // Default to first role if not set
            $firstRole = $this->roles()->first();
            if ($firstRole) {
                session(['active_role_id' => $firstRole->id]);

                return $firstRole;
            }

            return null;
        }

        return $this->roles()->find($activeRoleId);
    }

    /**
     * Set the user's active role in session.
     */
    public function setActiveRole(int $roleId): bool
    {
        // Verify user has this role
        if ($this->roles()->where('role_id', $roleId)->exists()) {
            session(['active_role_id' => $roleId]);

            return true;
        }

        return false;
    }

    /**
     * Check if user's active role matches the given role name.
     */
    public function hasActiveRole(string $roleName): bool
    {
        $activeRole = $this->getActiveRole();

        return $activeRole && $activeRole->name === $roleName;
    }

    /**
     * Check if user's active role is one of the given roles.
     */
    public function hasAnyActiveRole(array $roleNames): bool
    {
        $activeRole = $this->getActiveRole();

        return $activeRole && in_array($activeRole->name, $roleNames);
    }
}
