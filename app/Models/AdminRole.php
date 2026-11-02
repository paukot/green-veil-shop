<?php

namespace App\Models;

use Database\Factories\AdminRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<string> $permissions
 * @property bool $is_system
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'description', 'permissions', 'is_system'])]
class AdminRole extends Model
{
    /** @use HasFactory<AdminRoleFactory> */
    use HasFactory;

    protected $table = 'admin_roles';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    /**
     * @return HasMany<AdminUser, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'admin_role_id');
    }

    /**
     * Check if the role grants access to a specific category.
     */
    public function hasPermission(string $category): bool
    {
        $perms = $this->permissions ?? [];

        return in_array('*', $perms, true) || in_array($category, $perms, true);
    }

    /**
     * Check if the role is a Super Admin role.
     */
    public function isSuperAdmin(): bool
    {
        $perms = $this->permissions ?? [];

        return $this->slug === 'admin' || in_array('*', $perms, true);
    }
}
