<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed realistic customer accounts across European countries.
     */
    public function run(): void
    {
        $password = Hash::make('password');
        $now = Carbon::now();

        // 1. Ensure standard test accounts exist
        User::updateOrCreate(
            ['email' => 'customer@example.com'],
            ['name' => 'Storefront Customer', 'password' => $password, 'email_verified_at' => $now],
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin Customer Profile', 'password' => $password, 'email_verified_at' => $now],
        );

        // 2. Generate 75 diverse customer accounts
        $firstNames = ['Sophie', 'Emma', 'Lukas', 'Elena', 'Matteo', 'Camille', 'Liam', 'Ania', 'Jan', 'Isabella', 'Noah', 'Mia', 'Felix', 'Clara', 'Alejandro', 'Giulia', 'Lars', 'Hanna', 'Leon', 'Chloe'];
        $lastNames = ['Nowak', 'Kowalska', 'Müller', 'Schmidt', 'Dubois', 'Laurent', 'Rossi', 'Moretti', 'Garcia', 'Martinez', 'De Jong', 'Van Dijk', 'Weber', 'Becker', 'Wójcik', 'Kamińska'];

        $existingEmails = User::query()->pluck('email')->flip()->all();
        $batch = [];

        $index = 1;
        foreach ($firstNames as $fn) {
            foreach ($lastNames as $ln) {
                if ($index > 75) {
                    break 2;
                }

                $email = strtolower("{$fn}.{$ln}{$index}@example.com");
                if (isset($existingEmails[$email])) {
                    $index++;

                    continue;
                }

                $batch[] = [
                    'name' => "{$fn} {$ln}",
                    'email' => $email,
                    'password' => $password,
                    'email_verified_at' => $now->copy()->subDays($index % 120),
                    'created_at' => $now->copy()->subDays($index % 120),
                    'updated_at' => $now,
                ];

                $index++;
            }
        }

        if (! empty($batch)) {
            DB::table('users')->insert($batch);
        }
    }
}
