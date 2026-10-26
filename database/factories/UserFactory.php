<?php

namespace Database\Factories;

use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password-de-test'),
            'role' => UserRole::Editor,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Admin]);
    }

    public function sales(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Sales]);
    }

    public function editor(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Editor]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * Administrateur ayant déjà configuré la MFA par application.
     */
    public function withMfa(): static
    {
        return $this->state(fn (): array => [
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
            'app_authentication_recovery_codes' => ['recovery-code-1', 'recovery-code-2'],
        ]);
    }
}
