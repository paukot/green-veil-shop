<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            // Ensure default Super Admin role exists
            $adminRoleId = DB::table('admin_roles')->where('slug', 'admin')->value('id');

            if (! $adminRoleId) {
                $adminRoleId = DB::table('admin_roles')->insertGetId([
                    'name' => 'Super Admin',
                    'slug' => 'admin',
                    'description' => 'Permanent system role with full administrative privileges across all categories.',
                    'permissions' => json_encode(['*']),
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Copy existing admin users into admin_users table
            $existingAdmins = DB::table('users')->where('is_admin', true)->get();

            foreach ($existingAdmins as $admin) {
                $alreadyMigrated = DB::table('admin_users')->where('email', $admin->email)->exists();

                if (! $alreadyMigrated) {
                    DB::table('admin_users')->insert([
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'password' => $admin->password,
                        'admin_role_id' => $adminRoleId,
                        'is_active' => true,
                        'remember_token' => $admin->remember_token ?? null,
                        'created_at' => $admin->created_at ?? now(),
                        'updated_at' => $admin->updated_at ?? now(),
                    ]);
                }
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('password');
            });

            // Restore is_admin for any matching emails from admin_users
            $adminEmails = DB::table('admin_users')->pluck('email');
            if ($adminEmails->isNotEmpty()) {
                DB::table('users')->whereIn('email', $adminEmails)->update(['is_admin' => true]);
            }
        }
    }
};
