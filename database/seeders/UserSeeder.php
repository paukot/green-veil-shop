<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'customer@example.com'],
            ['name' => 'Storefront Customer', 'password' => 'password', 'email_verified_at' => now()],
        );
    }
}
