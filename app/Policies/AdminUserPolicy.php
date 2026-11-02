<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\User;

class AdminUserPolicy
{
    /**
     * Determine whether the user can view any admin users.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_admin_user') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can view the specific admin user.
     */
    public function view(User|AdminUser $user, AdminUser $adminUser): bool
    {
        return $user->can('view_admin_user') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can create admin users.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_admin_user') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can update the admin user.
     */
    public function update(User|AdminUser $user, AdminUser $adminUser): bool
    {
        return $user->can('update_admin_user') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can delete the admin user.
     * Prevents self-deletion and removing the last active super admin.
     */
    public function delete(User|AdminUser $user, AdminUser $adminUser): bool
    {
        if ($user instanceof AdminUser && $user->id === $adminUser->id) {
            return false;
        }

        if ($adminUser->isSuperAdmin()) {
            $count = AdminUser::query()
                ->where('is_active', true)
                ->whereHas('adminRole', fn ($q) => $q->where('slug', 'admin'))
                ->count();

            if ($count <= 1) {
                return false;
            }
        }

        return $user->can('delete_admin_user') && $user->hasCategoryAccess('system');
    }
}
