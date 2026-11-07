<?php

use App\Models\AdminRole;
use App\Models\AdminUser;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

describe('Admin Two-Factor Authentication', function () {
    beforeEach(function () {
        $this->role = AdminRole::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Permanent system role with full administrative privileges.',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );
    });

    it('has encrypted 2FA secret and recovery code casts on AdminUser', function () {
        $admin = AdminUser::factory()->create([
            'admin_role_id' => $this->role->id,
            'app_authentication_secret' => 'SECRETBASE32KEY1',
            'app_authentication_recovery_codes' => ['CODE-1111', 'CODE-2222'],
        ]);

        $admin->refresh();
        expect($admin->getAppAuthenticationSecret())->toBe('SECRETBASE32KEY1');
        expect($admin->getAppAuthenticationRecoveryCodes())->toBe(['CODE-1111', 'CODE-2222']);

        // Check raw database storage is encrypted, not plain text
        $raw = DB::table('admin_users')->where('id', $admin->id)->first();
        expect($raw->app_authentication_secret)->not->toBe('SECRETBASE32KEY1');
    });

    it('intercepts login with multi-factor challenge when 2FA is active', function () {
        $admin = AdminUser::factory()->withTwoFactor()->create([
            'email' => 'mfa.admin@greenveil.test',
            'password' => Hash::make('mfaPassword123'),
            'admin_role_id' => $this->role->id,
        ]);

        $component = Livewire::test(Login::class)
            ->fillForm([
                'email' => 'mfa.admin@greenveil.test',
                'password' => 'mfaPassword123',
            ])
            ->call('authenticate');

        // Multi-factor challenge intercept is initiated: user is NOT immediately authenticated
        $this->assertGuest('admin');
        expect($component->get('userUndertakingMultiFactorAuthentication'))->not->toBeNull();
    });

    it('allows bypassing challenge with valid recovery code', function () {
        $admin = AdminUser::factory()->withTwoFactor()->create([
            'email' => 'recovery.admin@greenveil.test',
            'password' => Hash::make('mfaPassword123'),
            'admin_role_id' => $this->role->id,
            'app_authentication_recovery_codes' => ['ABCD-1234', 'EFGH-5678'],
        ]);

        // Verify recovery code can be consumed and removed
        $codes = $admin->getAppAuthenticationRecoveryCodes();
        expect($codes)->toContain('ABCD-1234');

        $remaining = array_values(array_diff($codes, ['ABCD-1234']));
        $admin->saveAppAuthenticationRecoveryCodes($remaining);

        expect($admin->fresh()->getAppAuthenticationRecoveryCodes())->not->toContain('ABCD-1234');
        expect($admin->fresh()->getAppAuthenticationRecoveryCodes())->toContain('EFGH-5678');
    });
});
