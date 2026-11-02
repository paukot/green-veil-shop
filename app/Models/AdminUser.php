<?php

namespace App\Models;

use Database\Factories\AdminUserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int $admin_role_id
 * @property bool $is_active
 * @property string|null $avatar_url
 * @property string|null $app_authentication_secret
 * @property array<string>|null $app_authentication_recovery_codes
 * @property bool $has_email_authentication
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AdminRole|null $adminRole
 */
#[Fillable([
    'name',
    'email',
    'password',
    'admin_role_id',
    'is_active',
    'avatar_url',
    'app_authentication_secret',
    'app_authentication_recovery_codes',
    'has_email_authentication',
])]
#[Hidden([
    'password',
    'remember_token',
    'app_authentication_secret',
    'app_authentication_recovery_codes',
])]
class AdminUser extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar, HasEmailAuthentication
{
    /** @use HasFactory<AdminUserFactory> */
    use HasFactory;

    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use InteractsWithEmailAuthentication;
    use Notifiable;

    protected $table = 'admin_users';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'has_email_authentication' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * Role association for the admin staff member.
     *
     * @return BelongsTo<AdminRole, $this>
     */
    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    /**
     * Determines whether the admin can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Check if this admin user has access to a given functional category.
     */
    public function hasCategoryAccess(string $category): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->adminRole?->hasPermission($category) ?? false;
    }

    /**
     * Check if this admin user has Super Admin privileges.
     */
    public function isSuperAdmin(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->adminRole?->isSuperAdmin() ?? false;
    }

    /**
     * Map a granular action ability string (e.g. view_any_order) to its parent category.
     */
    public static function mapAbilityToCategory(string $ability): ?string
    {
        return match (true) {
            str_ends_with($ability, '_order') || str_ends_with($ability, '_order_item') => 'orders',
            str_ends_with($ability, '_product') || str_ends_with($ability, '_category') || str_ends_with($ability, '_promotion') => 'shop',
            str_ends_with($ability, '_user') || str_ends_with($ability, '_customer') => 'customers',
            str_ends_with($ability, '_payment') => 'finance',
            str_ends_with($ability, '_slider_item') => 'marketing',
            str_ends_with($ability, '_delivery_option') || str_ends_with($ability, '_payment_option') => 'settings',
            str_ends_with($ability, '_admin_user') || str_ends_with($ability, '_admin_role') || str_ends_with($ability, '_stripe_event') => 'system',
            default => null,
        };
    }

    /**
     * Check if this admin user has a specific granular ability or category.
     */
    public function hasPermission(string $ability): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->adminRole->permissions;

        if (in_array('*', $permissions, true) || in_array($ability, $permissions, true)) {
            return true;
        }

        $category = self::mapAbilityToCategory($ability);
        return $category && in_array($category, $permissions, true);
    }

    /**
     * Determine if the entity has the given abilities.
     * Overrides Authorizable to resolve granular string permissions through role and category access.
     *
     * @param  iterable<int|string, mixed>|string  $abilities
     * @param  array<int|string, mixed>|mixed  $arguments
     */
    public function can($abilities, $arguments = []): bool
    {
        if (! empty($arguments)) {
            return app(Gate::class)->forUser($this)->check($abilities, $arguments);
        }

        if (is_string($abilities)) {
            return $this->hasPermission($abilities);
        }

        foreach ($abilities as $ability) {
            if ($this->hasPermission((string) $ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the avatar URL for Filament panel.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_url) {
            return null;
        }

        if (Str::startsWith($this->avatar_url, ['http://', 'https://'])) {
            return $this->avatar_url;
        }

        return Storage::disk('public')->url($this->avatar_url);
    }
}
