<?php

namespace Database\Factories;

use App\Models\AdminRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminRole>
 */
class AdminRoleFactory extends Factory
{
    protected $model = AdminRole::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle().' Role';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'permissions' => ['shop'],
            'is_system' => false,
        ];
    }

    /**
     * Permanent Super Admin system role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'name' => 'Super Admin',
            'slug' => 'admin',
            'description' => 'Permanent system role with full administrative privileges.',
            'permissions' => ['*'],
            'is_system' => true,
        ]);
    }

    /**
     * Role with specific category permissions.
     *
     * @param  array<string>  $categories
     */
    public function withCategories(array $categories): static
    {
        return $this->state(fn () => [
            'permissions' => $categories,
        ]);
    }
}
