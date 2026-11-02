<?php

namespace App\Policies;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\User;

class AdminRolePolicy
{
    /**
     * Determine whether the user can view any admin roles.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_admin_role') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can view the specific admin role.
     */
    public function view(User|AdminUser $user, AdminRole $adminRole): bool
    {
        return $user->can('view_admin_role') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can create admin roles.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_admin_role') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can update the admin role.
     */
    public function update(User|AdminUser $user, AdminRole $adminRole): bool
    {
        return $user->can('update_admin_role') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can delete the admin role.
     * Protected system roles cannot be deleted.
     */
    public function delete(User|AdminUser $user, AdminRole $adminRole): bool
    {
        if ($adminRole->is_system) {
            return false;
        }

        return $user->can('delete_admin_role') && $user->hasCategoryAccess('system');
    }
}
