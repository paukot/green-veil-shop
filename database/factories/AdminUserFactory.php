<?php

namespace Database\Factories;

use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminUser>
 */
class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

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
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'admin_role_id' => AdminRole::factory(),
            'is_active' => true,
            'avatar_url' => null,
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
            'has_email_authentication' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Super Admin user attached to the permanent system admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'admin_role_id' => AdminRole::firstOrCreate(
                ['slug' => 'admin'],
                [
                    'name' => 'Super Admin',
                    'description' => 'Permanent system role with full administrative privileges.',
                    'permissions' => ['*'],
                    'is_system' => true,
                ]
            )->id,
        ]);
    }

    /**
     * Admin user with specific role.
     */
    public function withRole(AdminRole $role): static
    {
        return $this->state(fn () => [
            'admin_role_id' => $role->id,
        ]);
    }

    /**
     * Deactivated admin user.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    /**
     * Admin user with 2FA enabled.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn () => [
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP', // dummy valid base32 TOTP secret
            'app_authentication_recovery_codes' => [
                'ABCD-1234',
                'EFGH-5678',
                'IJKL-9012',
            ],
        ]);
    }
}
