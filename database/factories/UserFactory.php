<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($user) {
            // Assign default guest role
            $guestRole = Role::where('name', 'guest')->first();
            if ($guestRole) {
                $user->roles()->attach($guestRole->id);
            }
        });
    }

    /**
     * Indicate that the user has admin role.
     */
    public function admin(): static
    {
        return $this->afterCreating(function ($user) {
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }
        });
    }

    /**
     * Indicate that the user has operator role.
     */
    public function operator(): static
    {
        return $this->afterCreating(function ($user) {
            $operatorRole = Role::where('name', 'operator')->first();
            if ($operatorRole) {
                $user->roles()->attach($operatorRole->id);
            }
        });
    }

    /**
     * Indicate that the user has pj role.
     */
    public function pj(): static
    {
        return $this->afterCreating(function ($user) {
            $pjRole = Role::where('name', 'pj')->first();
            if ($pjRole) {
                $user->roles()->attach($pjRole->id);
            }
        });
    }

    /**
     * Indicate that the user has approver role.
     */
    public function approver(): static
    {
        return $this->afterCreating(function ($user) {
            $approverRole = Role::where('name', 'approver')->first();
            if ($approverRole) {
                $user->roles()->attach($approverRole->id);
            }
        });
    }
}
